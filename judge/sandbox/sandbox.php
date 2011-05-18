<?php
require_once("../../judgelib.php");

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
            foreach ($names as $name) {
                $lang[$name] = get_string('lang'.$name, 'local_onlinejudge2');
            }
        }
        asort($lang);
        return $lang;
    }
    
    
    /**
     * @param cases表示用例的输入输出
     * @param extra表示额外的限制信息，比如运行时间限制，内存占用限制
     * @param compiler表示编译器需要的参数,比如ideone需要用户名和密码，sandbox需要可执行程序.o文件
     */
    function judge($cases, $extra, $compiler)
    {
    	
    	
    }
    
    function run_in_sandbox($exec_file, $case) 
    {
        global $CFG;
        //ret表示输出结果
        $ret = new Object();
        $ret->output = '';
        /**
         *  0=>'nr' : not running – the paste has been created 
            with run parameter set to false
         * 11=>'ce' : compilation error – the program could not 
            be executed due to compilation errors
         * 12=>'re' : runtime error – the program finished 
            because of  the runtime error, for example: 
            division by zero,  array index out of bounds, uncaught exception
         * 13=>‘tle’： time limit exceeded – the program didn't 
            stop before the time limit
         * 15=>'ok' : success – everything went ok
         * 17=>'mle': memory limit exceeded – the program tried 
            to use more memory than it is allowed
         * 19=>'rf' : illegal system call – the program tried to call 
            illegal system function
         * 20=>'ie' : internal error – some problem occurred on 
            ideone.com; try to submit the paste again and if that fails too, 
            then please contact us at contact@ideone.com
         */
        $result = array('pending', 'ac', 'rf', 'mle', 'ole', 'tle', 're', 'at', 'ie');

        $sand = $CFG->dirroot . '/local/onlinejudge2/sandbox/makefile/sand';
        //可执行
        if (!is_executable($sand)){
            $ret->status = 'ie';
            return $ret;
        }
        //命令行
        $sand .= ' -l cpu='.($this->onlinejudge->cpulimit*1000).' -l memory='.$this->onlinejudge->memlimit.' -l disk=512000 '.$exec_file; 
        //标准输入，标准输出和错误输出
        $descriptorspec = array(
            0 => array('pipe', 'r'),  // stdin is a pipe that the child will read from
            1 => array('file', $exec_file.'.out', 'w'),  // stdout is a file to write to
            2 => array('pipe', '$exec_file.err', 'w') // stderr is a file to write to
        );
        
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
            $ret->status = 'ie';
            return $ret;
        } else if ($return_value >= 2) {
            $ret->status = $result[$return_value];
            return $ret;
        } else if ($return_value == 0) {
            mtrace('Pending? Why?');
            exit();
        }

        $ret->status = $this->diff($case->output, $ret->output);
        return $ret;
    }
}


?>