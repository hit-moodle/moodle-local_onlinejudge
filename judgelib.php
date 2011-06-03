<?php
global $CFG,$DB;

require_once($CFG->dirroot."/lib/dml/moodle_database.php");
//require_once($CFG->dirroot."/mod/assignment/type/onlinejudge/assignment.class.php");



class judge_base
{
	var $langs;
	var $onlinejudge;
	/**
     * Returns an array of installed programming languages indexed and sorted by name
     */
	function get_languages(){}
	/**
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
    */
	
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

        $taskmissions = $DB->get_records_sql($sql, '', 1);
        $taskmission = null;
        if ($taskmissions) {
            $taskmission = array_pop($taskmissions);
            // Set judged mark
            $DB->set_field('onlinejudge_result', 'judged', 1, 'id', $taskmission->taskid);
        }

        set_cron_lock('task_judging', null);

        return $taskmission;
    }
    
    /**
     * @param cases is the testcase for input and output.
     * @param extra is the extra limit information, 
     *        eg: runtime limit and cpu limit.
     * @param compiler is the need of certain compiler,
     *        eg: ideone.com need the username and password;
     *            sandbox need the executable file(.o).
     */
    function judge($task)
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


require_once($CFG->dirroot."/local/onlinejudge2/judge/sandbox/sandbox.php");
require_once($CFG->dirroot."/local/onlinejudge2/judge/ideone/ideone.php");
/*利用设计模式中的工厂模式来设计一个类，这个类根据id值的不同来选择创建不同
 *的ideone或sandbox实例
 */
class judge_factory
{
    var $sandbox_obj;
    var $ideone_obj;
    var $langs;
    
    function __construct()
    {
        $sandbox_obj = new judge_sandbox();
        $ideone_obj  = new judge_ideone(); 
        $langs = array();
    }
    /**
     * 函数get_langs列出可以使用的编译器语言的id，
     * 然后用户通过提供id值来进行以后的编译操作。 
     */
    function get_langs()
    {
    	$langs = array_merge($sandbox_obj->get_languages(),$ideone_obj->get_languages());
    }
    
    /**
     * 
     */
    function translate_into_langs($id)
    {
        $lang_temp = array();
        $lang_temp = array_flip($this->langs);
        return $lang_temp[$id];
    }
    
    
    /**
     * 
     * translator the param id into the language that  be available for compiler 
     * @param integer $id
     */
    function translator($id)
    {
        $lang_temp = array();
        //将数组的键值调换，存入temp数组
        $lang_temp = array_flip($this->langs);
        //获取翻译后的编译语言，比如‘c_ideone’变成‘c’
        $selected_lang = substr($lang_temp[$id],0,strrpos($lang_temp[$id],'_'));
        
        
        return $selected_lang;        
    }
	
    /**
     * 函数get_judge根据传入的数据来创建judge_ideone或者judge_sandbox对象
     * $task数据包就是数据库中的一个数据,包括judgeName,memlimit,cpulimit,input,output等数据.
     * 
     */
    function get_judge(& $judgeName)
    {
        //检测id值是否在支持的编译器语言里
        if(in_array($judgeName, $this->langs))
        {   
            // test result -> ok  
            $judgeName_temp = $judgeName; //保存原先的id值  	
            $judgeName = $this->translate_into_langs($judgeName);
            
            //获取编译器类型，结果表示 _ideone或者_sandbox
            $judge_type = substr($judgeName, strrpos($judgeName, '_'));
            //选择的为sandbox的引擎以及语言,
            //还原原先的id值
            $judgeName = $judgeName_temp;
            if($judge_type == "_sandbox" )
            {
                $judgeName = $this->translator($judgeName);
                $judge_obj = new judge_sandbox();
                return $judge_obj;	
                //return $judge_obj->judge($task);
            }
            //选择的为ideone的引擎以及语言
            else if($judge_type == "_ideone")
            {
                $judgeName = $this->translator($judgeName);
                $judge_obj = new judge_ideone(); 
                return $judge_obj;              
                //return $judge_obj->judge($task);
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