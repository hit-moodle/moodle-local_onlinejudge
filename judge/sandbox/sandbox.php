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
    
    // Compile submission $sub in temp_dir
    // return result class on success, false on error
    function compile($sub, $temp_dir) {
        global $CFG;
        $result = false;

        $file = 'prog.c';
        file_put_contents("$temp_dir/$file", get_submission_file_content($sub->id));
        $compiler = $CFG->dirroot.'/local/onlinejudge2/languages/'.$this->onlinejudge->language.'.sh';
        if (!is_executable($compiler)) 
        {
            $result->status = 'ie';
            $result->info = get_string('cannotruncompiler', 'assignment_onlinejudge');
            break;
        }

        $output = null;
        $return = null;
        $command = "$compiler $temp_dir/$file $temp_dir/a.out 2>&1";
        exec($command, $output, $return);

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
     * there are still some problem in this function, 
     * @param extra should not be used here.
     */
//    function judge($cases, $extra, $compiler)
    function judge($sub)
    {
        //生成.o文件	
    	$exec_file = compile($sub['inputfile'],"/var/www/moodle/local/onlinejudge/judge/sandbox/exec_file/");
    	//用例
    	$case = new stdClass();
    	$case->input = $sub['inputfile'];
    	$case->output = $sub['outputfile'];
    	run_in_sandbox($exec_file, $case);
    	
    }
    
    function run_in_sandbox($exec_file, $case) 
    {
        global $CFG;
        //ret表示输出结果
        $ret = new Object();
        $ret->output = '';
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