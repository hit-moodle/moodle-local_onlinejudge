<?php
global $CFG,$DB;
require_once($CFG->dirroot."/lib/dml/moodle_database.php");
require_once("./judge/ideone/ideone.php");
require_once($CFG->dirroot."/mod/assignment/type/onlinejudge/assignment.class.php");

class judge_base
{
	var $onlinejudge;
	/**
     * Returns an array of installed programming languages indexed and sorted by name
     */
	function get_languages(){}
	
    function get_tests() 
    {
        global $CFG;
        // 从数据库中读取任务，待完善。
        $records = $DB->get_records('onlinejudge_task', 'assignment', $this->assignment->id, 'id ASC');
        $tests = array();

        foreach ($records as $record) {
            $tests[] = $record;
        }

        return $tests;
    }
	
      /**
     * Get one unjudged submission and set it as judged
     * If all submissions have been judged, return false
     * The function can be reentranced
     */
    function get_unjudged_submission() 
    {
        //try to obtain or release the cron lock.
        while (!set_cron_lock('task_judging', time() + 10)) {}
        //set_cron_lock('assignment_judging', time()+10);
        //query the unjudged data from table.
        $sql = 'SELECT 
                    id, taskid, judged '.
               'FROM '
                    .$CFG->prefix.'onlinejudge_task AS task, '
                    .$CFG->prefix.'onlinejudge_result AS result '.
               'WHERE '.
                    'task.id = result.taskid '.
                    'AND result.judged = 0 ';

        $submissions = $DB->get_records_sql($sql, '', 1);
        $submission = null;
        if ($submissions) {
            $submission = array_pop($submissions);
            // Set judged mark
            $DB->set_field('onlinejudge_result', 'judged', 1, 'id', $submission->taskid);
        }

        set_cron_lock('task_judging', null);

        return $submission;
    }
    
    /**
     * @param cases is the testcase for input and output.
     * @param extra is the extra limit information, 
     *        eg: runtime limit and cpu limit.
     * @param compiler is the need of certain compiler,
     *        eg: ideone.com need the username and password;
     *            sandbox need the executable file(.o).
     */
    function judge($sub)
    {
    	// TO DO
    }
    
    /**
     * 
     * function diff() compare the output and the answer 
     */  
    function diff($answer, $output) 
    {
        $answer = strtr(trim($answer), array("\r\n" => "\n", "\n\r" => "\n"));
        $output = trim($output);

        if (strcmp($answer, $output) == 0)
            return 'ac';
        else 
        {
            $tokens = array();
            $tok = strtok($answer, " \n\r\t");
            while ($tok) 
            {
                $tokens[] = $tok;
                $tok = strtok(" \n\r\t");
            }

            $tok = strtok($output, " \n\r\t");
            foreach ($tokens as $anstok) 
            {
                if (!$tok || $tok !== $anstok)
                    return 'wa';
                $tok = strtok(" \n\r\t");
            }

            return 'pe';
        }
    }
    
    /**
     * Evaluate student submissions
     */
    function cron() {

        global $CFG;

        // Detect the frequence of cron
        //从数据库中获取还没有编译过的数据
        $lastcron = $DB->get_field('onlinejudge_task', 'lastcron', 'status', '0');
        if ($lastcron) {
            set_config('onlinejudge_cronfreq', time() - $lastcron);
        }

        // There are two judge routines
        //  1. Judge only when cron job is running. 
        //  2. After installation, the first cron running will fork a daemon to be judger.
        // Routine two works only when the cron job is executed by php cli
        //
        if (function_exists('pcntl_fork')) { // pcntl_fork supported. Use routine two
            $this->fork_daemon();
        } else if ($CFG->onlinejudge_judge_in_cron) { // pcntl_fork is not supported. So use routine one if configured.
            $this->judge_all_unjudged();
        }
    }
    
    function fork_daemon() 
    {
        global $CFG, $db;

        if(empty($CFG->onlinejudge_daemon_pid) || !posix_kill($CFG->onlinejudge_daemon_pid, 0)){ // No daemon is running
            $pid = pcntl_fork(); 

            if ($pid == -1) {
                mtrace('Could not fork');
            } else if ($pid > 0){ 
                //Parent process
                //Reconnect db, so that the parent won't close the db connection shared with child after exit.
                reconnect_db();

                set_config('onlinejudge_daemon_pid' , $pid);
            } else { //Child process
                $this->daemon(); 
            }
        }
    }
    
    function daemon()
    {
        global $CFG;

        $pid = getmypid();
        mtrace('Judge daemon created. PID = ' . $pid);

        if (function_exists('pcntl_fork')) { 
            // In linux, this is a new session
            // Start a new sesssion. So it works like a daemon
            $sid = posix_setsid();
            if ($sid < 0) {
                mtrace('Can not setsid');
                exit;
            }

            //Redirect error output to php log
            $CFG->debugdisplay = false;
            @ini_set('display_errors', '0');
            @ini_set('log_errors', '1');

            // Close unused fd
            fclose(STDIN);
            fclose(STDOUT);
            fclose(STDERR);

            reconnect_db();

            // Handle SIGTERM so that can be killed without pain
            declare(ticks = 1); // tick use required as of PHP 4.3.0
            pcntl_signal(SIGTERM, 'sigterm_handler');
        }

        set_config('onlinejudge_daemon_pid' , $pid);

        // Run forever until be killed or plugin was upgraded
        while(!empty($CFG->onlinejudge_daemon_pid)){
            global $db;

            $this->judge_all_unjudged();

            // If error occured, reconnect db
            if ($db->ErrorNo())
                reconnect_db();

            //Check interval is 5 seconds
            sleep(5);

            //renew the config value which could be modified by other processes
            $CFG->assignment_oj_daemon_pid = get_config(NULL, 'onlinejudge_daemon_pid');
        }
    }
}
    

/*利用设计模式中的工厂模式来设计一个类，这个类根据id值的不同来选择创建不同
 *的ideone或sandbox实例
 */
class judge_factory
{
	
	var $judge_methods = array(
	    //ideone languages
        'ada_ideone'                     => 7,                      
        'assembler_ideone'               => 13,                  
        'awk_gawk_ideone'                => 104,            
        'awk_mawk_ideone'                => 105,             
        'bash_ideone'                    => 28,             
        'bc_ideone'                      => 110,                        
        'brainfxxk_ideone'               => 12,            
        'c_ideone'                       => 11,                     
        'csharp_ideone'                  => 27,                        
        'cpp_ideone'                     => 1,                  
        'c99_strict_ideone'              => 34,             
        'clojure_ideone'                 => 111,                
        'cobol_ideone'                   => 118,                      
        'cobol85_ideone'                 => 106,                      
        'common_lisp_clisp_ideone'       => 32,    
        'd_dmd_ideone'                   => 102,                 
        'erlang_ideone'                  => 36,                     
        'forth_ideone'                   => 107,                     
        'fortran_ideone'                 => 5,                 
        'go_ideone'                      => 114,                
        'haskell_ideone'                 => 21,                   
        'icon_ideone'                    => 16,             
        'intercal_ideone'                => 9,                 
        'java_ideone'                    => 10,                    
        'javascript_rhino_ideone'        => 35,         
        'javascript_spidermonkey_ideone' => 112,  
        'lua_ideone'                     => 26,                       
        'nemerle_ideone'                 => 30,                  
        'nice_ideone'                    => 25,                     
        'ocaml_ideone'                   => 8,                      
        'oz_ideone'                      => 119,                      
        'pascal_fpc_ideone'              => 22,             
        'pascal_gpc_ideone'              => 2,            
        'perl_ideone'                    => 3,              
        'php_ideone'                     => 29,            
        'pike_ideone'                    => 19,            
        'prolog_gnu_ideone'              => 108,   
        'prolog_swi_ideone'              => 15,      
        'python_ideone'                  => 4,             
        'python3_ideone'                 => 116,             
        'r_ideone'                       => 117,             
        'ruby_ideone'                    => 17,             
        'scala_ideone'                   => 39,             
        'scheme_guile_ideone'            => 33,    
        'smalltalk_ideone'               => 23,          
        'tcl_ideone'                     => 38,              
        'text_ideone'                    => 62,               
        'unlambda_ideone'                => 115,         
        'vbdotnet_ideone'                => 101, 
        'whitespace_ideone'              => 6,
        
        //sandbox languages
        'c_warn2err_sandbox'                     =>300,
        'c_sandbox'                              =>301,
        'cpp_warn2err_sandbox'                   =>302,
        'cpp_sandbox'                            =>303,    
    );
    	
    /*
     * 函数get_judge_methods列出可以使用的编译器语言的id，
     * 然后用户通过提供id值来进行以后的编译操作。 
     */
    function get_judge_methods()
    {
        $lang = array();
        echo "本系统支持的编译语言以及id值如下：<br>";
        foreach ($this->judge_methods as $name => $id) 
        {
            $lang[$name] = get_string('lang'.$name, 'local_onlinejudge2');
            //这里需要使用表格来显示.
            echo "$lang[$name] ====>  $id";
        }
        echo "<br><br><br>";
    }
	
    /*
     * 函数get_judge根据传入的数据来创建judge_ideone或者judge_sandbox对象
     * $sub数据包就是数据库中的一个数据,包括judgeName,memlimit,cpulimit,input,output等数据.
     * 
     */
    function get_judge($sub)
    {	
        //检测id值是否在支持的编译器以及语言里
        if(in_array($sub['judgeName'], $judge_methods))
        {
        	//获取编译器类型，结果表示 _ideone或者_sandbox
            $judge_type = substr($sub['judgeName'], strrpos($sub['judgeName'], '_'));
            
            //选择的为sandbox的引擎以及语言,
            if($judge_type == "_sandbox" )
            {
                $judge_obj = new judge_sandbox();
                $judge_obj->judge($sub);
            }
            //选择的为ideone的引擎以及语言
            else if($judge_type = "_ideone")
            {
                $judge_obj = new judge_ideone();
                $judge_obj->judge($sub);
            }
            else 
            {
                //其他的编译器引擎
            }
        }
        //提示出错，重新传入id值
        else
        {	
            echo "所选择的语言不支持，请重新选择.<br>";
        }
    }	
}
?>