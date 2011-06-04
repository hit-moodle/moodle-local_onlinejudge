<?php
//require_once("../../judgelib.php");
require_once(dirname(__FILE__) ."/../../../../config.php");
global $CFG, $DB;
require_once($CFG->dirroot."/local/onlinejudge2/judgelib.php");

class judge_sandbox extends judge_base
{
    var $cases = parent::get_tests;
    var $langs = array(
        //sandbox languages
        'c_warn2err_sandbox'                     =>300,
        'c_sandbox'                              =>301,
        'cpp_warn2err_sandbox'                   =>302,
        'cpp_sandbox'                            =>303,
	);
    function get_languages()
    {
    	$lang = array();
        // Get local languages. Linux only
        if ($CFG->ostype != 'WINDOWS') 
        {
            $dir = $CFG->dirroot.'/local/onlinejudge2/languages/';
            $files = get_directory_list($dir);
            $names = preg_replace('/\.(\w+)/', '', $files); // Replace file extension with nothing
            foreach ($names as $name) 
            {
                $lang[$name] = get_string('lang'.$name, 'local_onlinejudge2');
            }
        }
        asort($lang);
        return $lang;
    }
    
    // Compile submission $task in temp_dir
    // return result class on success, false on error
    function compile($task, $temp_dir) {
      //  echo "现在开始执行compile方法<br>";
        global $CFG;
        $result = false;
        //$file 是.c文件或者源代码
        $file = 'prog.c';
        //将代码写入文件里
        file_put_contents("$temp_dir/$file", $task['source']);
        //根据需要选择编译器，这里举例为c.sh
        //gcc -D_MOODLE_ONLINE_JUDGE_ -Wall -static -o $DEST $SOURCE -lm
        $compiler = $CFG->dirroot.'/local/onlinejudge2/languages/c.sh';
        if (!is_executable($compiler)) 
        {
            $result->status = 'ie';
            $result->info = get_string('cannotruncompiler', 'local_onlinejudge2');
            break;
        }

        $output = null;
        $return = null;
        // $compiler后面第一个参数为$SOURCE,第二个参数为$DEST,2>&1表示将标准错误输出信息定向到标准输出里
        $command = "$compiler $temp_dir/$file $temp_dir/a.out 2>&1";
        
        //output是一个数组，保存输出信息
        //return是命令行执行的状态
        exec($command, $output, $return);
       // echo 'exec执行完毕';
        if ($return) 
        { 
        	//Compile error
            $result->status = 'ce';
        } 
        else 
        { 
            $result->status = 'compileok';
        }

        //strip path info
        $output = str_replace($temp_dir.'/', '', $output);
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
    function judge($task)
    {
        //生成.o文件
        $this->compile($task['source'], '/～/exec_file');
        $exec_file = '/～/exec_file/a.out';
        
        //用例
        $case = new stdClass();          
        if($task['usefile'])
    	{
    	    $case->input = $task['inputfile'];
    	    $case->output = $task['outputfile'];
        }
        else 
    	{
    	    $case->input = $task['input'];
    	    $case->output = $task['output'];   		
        }
        
        //存入数据库的数据包
        $record = new stdClass();
        $record->judgeName = $task['judgeName'];
        $record->memlimit = $task['memlimit'];
        $record->cpulimit = $task['cpulimit'];    
        $record->input = $task['input'];
        $record->output = $task['output'];
        $record->usefile = $task['usefile'];
        $record->inputfile = $task['inputfile'];
        $record->outputfile = $task['outputfile'];
        //存入数据库,并获取id值
        $id = $DB->insert_record('onlinejudge_task', $record, true);
        
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
        
        //原先命令命令行是注释掉的部分，这里为了方便测试直接指定数字了。
        //$sand .= ' -l cpu='.($this->onlinejudge->cpulimit*1000).' -l memory='.$this->onlinejudge->memlimit.' -l disk=512000 '.$exec_file; 
        $sand .= ' -l cpu=1000'.' -l memory=1048576'.' -l disk=512000 '.$exec_file; 
        
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

        if ($return_value == 255) 
        {
            $ret->status = 'ie';
            return $ret;
        } 
        else if ($return_value >= 2) 
        {
            $ret->status = $result[$return_value];
            return $ret;
        } 
        else if($return_value == 0) 
        {
            mtrace('Pending? Why?');
            exit();
        }
        else 
        {
            exit();	
        }
        
        //比较结果俞用例输出
        $ret->status = $this->diff($case->output, $ret->output);
    
        //$ret应该还有很多其他属性，需要保存到下面的$result对象中。
        //保存结果数据包
        $result = new stdClass(); 
        $result = $record; //先保存原先数据
        $result->taskid = $id;
        $result->judged = 1; //已经编译运行完
        $result->status = $ret->status; //执行状态，'ac','ie'等
        $result->info = $ret->info; //描述,比如内存不足，程序不能运行等.
        $result->starttime = $ret->starttime;//开始时间
        $result->endtime = $ret->endtime; //结束时间
        //将结果存入数据库表onlinejudge_result中
        $DB->insert_record('onlinejudge_result',$result,false);
        
        //删除原先的onlinejudge_task表格
        $DB->delete_records('onlinejudge_task',array('id'=>$id));
    	
        //返回id值
        return $id;
    }
}


?>
