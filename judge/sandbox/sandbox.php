<?php
//require_once("../../judgelib.php");
global $CFG, $DB;
require_once($CFG->dirroot."/local/onlinejudge2/judgelib.php");

class judge_sandbox extends judge_base
{
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
    
    // Compile submission $sub in temp_dir
    // return result class on success, false on error
    function compile($sub, $temp_dir) {
      //  echo "现在开始执行compile方法<br>";
        global $CFG;
        $result = false;
        //$file 是.c文件或者源代码
        $file = 'prog.c';
        //将代码写入文件里
        file_put_contents("$temp_dir/$file", $sub['source']);
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
     * @param sub is the data passed by get_judge method in class judge_factory in file judgelib.php
     * returns the result of compiled.
     */   
    function judge($sub)
    {
        //生成.o文件
       $this->compile($sub['source'], '/home/yu/exec_file');
       $exec_file = '/home/yu/exec_file/a';
    	
    	//用例
        $case = new stdClass();
        if($sub['usefile'])
    	{
    	    $case->input = $sub['inputfile'];
    	    $case->output = $sub['outputfile'];
        }
        else 
    	{
    	    $case->input = $sub['input'];
    	    $case->output = $sub['output'];
    		
        }
        //利用sandbox引擎编译
    	return $this->run_in_sandbox($exec_file, $case);
    	
    }
    
    function run_in_sandbox($exec_file, $case) 
    {
        global $CFG;
        //ret表示输出结果
        $ret = new Object();
        $ret->output = '空内容';
        $result = array('pending', 'ac', 'rf', 'mle', 'ole', 'tle', 're', 'at', 'ie');

        $sand = $CFG->dirroot . '/local/onlinejudge2/sandbox/sand/sand';
        //这里sand不可执行
        //不可执行
        if (!is_executable($sand)){
            $ret->status = 'ie';
            return $ret;
        }
       
        //命令行
        //$sand .= ' -l cpu='.($this->onlinejudge->cpulimit*1000).' -l memory='.$this->onlinejudge->memlimit.' -l disk=512000 '.$exec_file; 
        $sand .= ' -l cpu=1000'.' -l memory=1048576'.' -l disk=512000 '.$exec_file; 
        
        //标准输入，标准输出和错误输出
        $descriptorspec = array(
            0 => array('pipe', 'r'),  // stdin is a pipe that the child will read from
            1 => array('file', $exec_file.'.out', 'w'),  // stdout is a file to write to
            2 => array('pipe', '$exec_file.err', 'w') // stderr is a file to write to
        );
        $ret->output = 'null';
        //打开进程，执行命令行，并且打开用于输入输出的文件指针
        $proc = proc_open($sand, $descriptorspec, $pipes);
        
        if (!is_resource($proc)) {
            $ret->status = 'ie';
            return $ret;
        }
        
        fwrite($pipes[0], $case->input);
        fclose($pipes[0]);

        //关闭proc_open打开的进程，并且返回进程的退出代码
        $return_value = proc_close($proc);
        //将文件变成字符串流
        $ret->output = file_get_contents($exec_file.'.out');

        if ($return_value == 255) {
            echo 'return_value==255';
            $ret->status = 'ie';
            return $ret;
        } else if ($return_value >= 2) {
            echo 'return_value>=2';
            $ret->status = $result[$return_value];
            return $ret;
        } else if ($return_value == 0) {
            echo 'return_value==0';
            mtrace('Pending? Why?');
            exit();
        }

        //$ret->status = $this->diff($case->output, $ret->output);
        //test
        $ret->status = 'ac';
        
        return $ret;
    }
}


?>
