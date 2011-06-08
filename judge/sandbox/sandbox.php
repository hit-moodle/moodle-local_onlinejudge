<?php
//require_once("../../judgelib.php");
require_once(dirname(dirname(__FILE__))."/../../../config.php");
global $CFG, $DB;
require_once($CFG->dirroot."/local/onlinejudge2/judgelib.php");

class judge_sandbox extends judge_base {
   // var $cases = parent::get_tests;
    var $langs = array(
        //sandbox languages
        'c_warn2err_sandbox'                     =>300,
        'c_sandbox'                              =>301,
        'cpp_warn2err_sandbox'                   =>302,
        'cpp_sandbox'                            =>303,
	);
	
	var $status_arr = array(
            'pending' => 4,
            'nr'      => 0,
            'ac'      => 1,
            'wa'      => 2,
            'pe'      => 3,
            're'      => 12,
            'tle'     => 13,
            'mle'     => 17,
            'ole'     => 16,
            'ce'      => 11,
            'ie'      => 20,
            'rf'      => 5,
            'at'      => 6
        );
    function get_languages()
    {
    	global $CFG;
    	global $DB ;
    	$lang = array();
        // Get local languages. Linux only
        if ($CFG->ostype != 'WINDOWS') {
            $dir = $CFG->dirroot.'/local/onlinejudge2/languages/';
            $files = get_directory_list($dir);
            $names = preg_replace('/\.(\w+)/', '', $files); // Replace file extension with nothing
            foreach ($names as $name) {
                $lang[$name] = get_string('lang'.$name, 'local_onlinejudge2');
            }
        }
        asort($lang);
        return $lang;
    }
    
    function translate_status($status) {
        return $this->status_arr[$status];
    }
    
    function flip_status($statusid) {
        $status_arr_temp = array_flip($this->status_arr);
        return $status_arr_temp[$statusid];
    }
    
    /**
     * 
     * 将数字id转换为编译器可以执行的语言名字，如301转换为c（不可执行名字为c_sandbox）
     * @param integer $id
     */
    function translator($id) {
        $lang_temp = array();
        //将数组的键值调换，存入temp数组
        $lang_temp = array_flip($this->langs);
        //获取翻译后的编译语言，比如‘c_ideone’变成‘c’
        $selected_lang = substr($lang_temp[$id],0,strrpos($lang_temp[$id],'_'));
        
        
        return $selected_lang;        
    }
    
    // Compile submission $task in temp_dir
    // return result class on success, false on error
    function compile($task, $temp_dir) {
    	global $CFG, $DB;
        $result = false;
        //创建存储源代码的.c文件
        $file = 'prog.c';
        //将代码写入文件里
        file_put_contents("$temp_dir/$file", $task['source']);
        
        //将id转换为可识别的语言
        $judgeName = $this->translator($task['judgeName']);
        echo $judgeName.'<br>';
        //根据需要选择编译器
        //gcc -D_MOODLE_ONLINE_JUDGE_ 	-Wall -static -o $DEST $SOURCE -lm
        $compiler = $CFG->dirroot.'/local/onlinejudge2/languages/'.$judgeName.'.sh';
        if (!is_executable($compiler)) {
            //echo '.sh脚本文件不可执行，请查看有无执行权限或者脚本错误';
            echo get_string('cannotruncompiler', 'local_onlinejudge2');
            $result->status = 'ie';
            $result->info = get_string('cannotruncompiler', 'local_onlinejudge2');
            //break;
        }
        
        //output是一个数组，保存输出信息
        $output = null;
        $return = null;
        // $compiler后面第一个参数为source,第二个参数为dest,2>&1表示将标准错误输出信息定向到标准输出里
        $command = "$compiler $temp_dir/$file $temp_dir/a.out 2>&1";
        
        //return是命令行结果的最后一行
        exec($command, $output, $return);
       
        if ($return) { 
        	//Compile error
            $result->status = 'ce';
        } 
        else { 
            $result->status = 'compileok';
        }

        //strip path info
        //将output里面的$temp_dir.'/'替换为空
        $output = str_replace($temp_dir.'/', '', $output);
        //将output数组结合为字符串，元素中间用\n换行符放置,并转换为html元素
        $error = htmlspecialchars(implode("\n", $output));
        $result->info = addslashes($error);

        //Compile the first file only
        return $result;       
    }
    
    /**
     * @param task is an array, including the user's data.
     * returns the id of onlinejudge_task table in the database, after compile,
     *         the ide returned reference to the onlinejudge_result table in the database.
     */   
    function judge($task) {
        global $CFG, $DB;
        //存入数据库的数据包
        $record = new stdClass();
        $record->taskname = $task['taskname'];
        $record->judgename = $task['judgeName'];//这是编译器语言的数字id
        $record->memlimit = $task['memlimit'];
        $record->cpulimit = $task['cpulimit'];    
        $record->input = $task['input'];
        $record->output = $task['output'];
        $record->usefile = $task['usefile'];
        $record->inputfile = $task['inputfile'];
        $record->outputfile = $task['outputfile'];
        //存入数据库,并获取id值
        $id = $DB->insert_record('onlinejudge_task', $record, true);
        
        //创建存储目录
        $temp_dir = $CFG->dirroot.'/temp/onlinejudge2/'.$id;
        if (!check_dir_exists($temp_dir, true, true)) {
            mtrace("Can't mkdir ".$temp_dir);
            return false;
        }
        
        //得到结果对象
        if($result = $this->compile($task, $temp_dir)) {
            
            $result->grade = -1;
            if ($result->status === 'compileok') {
               // echo '运行成功，现在开始存入数据库';
                //Run and test!
            	/*
                $results = array();
                $cases = $this->get_tests();
                foreach ($cases as $case) 
                {
                    $results[] = $this->run_in_sandbox($temp_dir.'/a.out', $case);
                }

                $result = $this->merge_results($results, $cases);
                */
                $result = new stdClass();
                
                $result = $this->run_for_test($temp_dir.'a.out', $task);
                //$result = $this->run_in_sandbox($temp_dir.'a.out', $case);	
            } 
            else if ($result->status === 'ce') {
                $result->grade = 'ce';
                $result->output = '';
            }	
        } 
        //保存结果到数据库的数据包
        $task_result = new stdClass(); 
        $task_result = $record; //先保存原先数据
        $task_result->taskid = $id;
        $task_result->judged = 1; //已经编译运行完
        $task_result->status = $this->translate_status($result->status); //执行状态，'ac','ie'等
        $task_result->info = $result->info; //描述,比如内存不足，程序不能运行等.
        $task_result->starttime = $result->starttime;//开始时间
        $task_result->endtime = $result->endtime; //结束时间
        //将结果存入数据库表onlinejudge_result中
        $DB->insert_record('onlinejudge_result',$task_result,false);
       
        //删除原先的onlinejudge_task表格
        $DB->delete_records('onlinejudge_task',array('id'=>$id)); 	
        //返回id值
        return $id;
    }
    
    function run_in_sandbox($exec_file, $task) {
        //用例
        $case = new stdClass();          
        if($task['usefile']) {
            $case->input = $task['inputfile'];
            $case->output = $task['outputfile'];
        }
        else {
            $case->input = $task['input'];
            $case->output = $task['output'];   		
        }
    	
         //结果对象
        $ret = new stdClass();
        //利用sandbox引擎编译,这里需要用到后台进程，暂时还没添加。
        $ret->output = null;
        
        $status = array('pending', 'ac', 'rf', 'mle', 'ole', 'tle', 're', 'at', 'ie');
        $sand = $CFG->dirroot . '/local/onlinejudge2/sandbox/sand/sand';
        
        //目前sand不可执行
        //如果sand不可执行，则返回空的结果对象
        if (!is_executable($sand)){
            $ret->status = 'ie';
            return $ret;
        }
        
        $sand .= ' -l cpu='.($task['cpulimit']*1000).' -l memory='.$task['memlimit'].' -l disk=512000 '.$exec_file; 
        //test
        //$sand .= ' -l cpu=1000'.' -l memory=1048576'.' -l disk=512000 '.$exec_file; 
    
        //描述符，包含标准输入，标准输出和标准错误输出
        $descriptorspec = array(
            0 => array('pipe', 'r'),  // stdin is a pipe that the child will read from
            1 => array('file', $exec_file.'.out', 'w'),  // stdout is a file to write to
            2 => array('file', $exec_file.'err', 'w') // stderr is a file to write to
        );
        
        //执行sand命令行，打开文件指针，用于输入输出,返回表示进程的资源
        $proc = proc_open($sand, $descriptorspec, $pipes);
        
        //如果返回的不是资源，即不成功
        if (!is_resource($proc)) {
            $ret->status = 'ie';
            return $ret;
        }
        
        //将用例输入写入到$pipes[0]指定的文件中。
        fwrite($pipes[0], $case->input);
        fclose($pipes[0]);

        //关闭proc_open打开的进程，并且返回进程的退出代码
        $return_value = proc_close($proc);
        
        //将文件变成字符串，存入结果的输出中
        $ret->output = file_get_contents($exec_file.'.out');

        if ($return_value == 255) {
            $ret->status = 'ie';
            return $ret;
        } 
        else if ($return_value >= 2) {
            $ret->status = $result[$return_value];
            return $ret;
        } 
        else if($return_value == 0) {
            mtrace('Pending? Why?');
            exit();
        }
        else {
            exit();	
        }
        
        //比较结果俞用例输出
        $ret->status = $this->diff($case->output, $ret->output);
        
        return $ret;
    }
    
    //测试函数，测试是否可以运行
    function run_for_test($exec_file, $case)
    {
    	//echo 'run_for_test' ;
        $result = new stdClass(); //保存结果对象
        $result->output = null;
        $output = array();
        $return = null;
        $command = $exec_file.' '.$case->input;
        exec($command, $output, $return);
        if($case->output == $output[0])
        {
            //echo "执行成功！！！";
            $result->status = 'ac';
        }
        else 
        {
            //echo "执行失败";	
            $result->status = 'ie';
        }
        $result->output = $output[0];
        $result->info = '运行成功～';
        $result->starttime = time();
        $result->endtime = time();
        
        return $result;
    }
}


?>
