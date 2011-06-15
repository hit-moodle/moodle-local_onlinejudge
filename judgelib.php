<?php

require_once(dirname(__FILE__).'/../../config.php');
global $CFG,$DB;
require_once($CFG->dirroot."/lib/dml/moodle_database.php");

global $judgeclasses;
$judgeclasses = array();
//得到结果表示为judge_sandbox, judge_ideone等数组
if ($plugins = get_list_of_plugins('local/onlinejudge2/judge')) {
    foreach ($plugins as $plugin=>$dir) {
        require_once("$CFG->dirroot/local/onlinejudge2/judge/$dir/lib.php");
        $judgeclasses[] = "judge_$dir";
    }
}

if (!isset($CFG->onlinejudge2_judge_in_cron)) {
    set_config('onlinejudge2_judge_in_cron', 0);
}

class judge_base{
	var $langs;
	var $onlinejudge;

	/**
     * Return an array of programming languages supported by this judge
     *
     * The array key must be the language's ID, such as c_sandbox, python_ideone.
     * The array value must be a human-readable name of the language, such as 'C (local)', 'Python (ideone.com)'
     */
    static function get_languages() {
        return array();
    }

    /**
     * 
     * 将数字id转换为编译器可以执行的语言名字，如301转换为c（不可执行名字为c_sandbox）
     * @param integer $id
     */
    function translator($id){}
    
    /**
     * 将status从英文翻译为id值，便于存储到数据库中
     * @param status表示结果状态的缩写，不同编译器结果不同。
     * @return 返回表示status的整数值。
     */
    function translate_status($status) {
     }
     
    /**
     * 将status从整数id值译为英文，便于显示给用户看
     * @param statusid表示结果状态的id值，不同编译器结果不同。
     * @return 返回表示statusid的英文描述。
     */
    function flip_status($statusid) {
    
    }
    
	/**
	 * 通过传递任务id值来查看评测的结果
	 * @param id 是数据库表onlinejudge_result中的taskid
	 * @return 返回结果对象
	 */
    function get_result($taskid){
        global $DB;
        if(! $DB->record_exists('onlinejudge_result', array('taskid' => $taskid))) {
            echo get_string('nosuchrecord', 'local_onlinejudge2');
        } 
        $result = null; //结果对象
        $result = $DB->get_record('onlinejudge_result', array('taskid' => $id));
        return $result;
    }
    
    //打印结果
    function output_result($result){}
    
    /**
     * judge the source of task, and return the id of the certain database record.
     * @param task is configed by clients, include the memlimit, cpulimit, case(input,output) etc.
     * @return the id of the task in the database.
     */
    function judge($task) {
        return false;
    }

    /**
     * 
     * function diff() compare the output and the answer
     */  
    function diff($answer, $output) {
        $answer = strtr(trim($answer), array("\r\n" => "\n", "\n\r" => "\n"));
        $output = trim($output);

        if (strcmp($answer, $output) == 0)
            return ONLINEJUDGE2_STATUS_ACCEPTED;
        else {
            $tokens = array();
            $tok = strtok($answer, " \n\r\t");
            while ($tok) {
                $tokens[] = $tok;
                $tok = strtok(" \n\r\t");
            }

            $tok = strtok($output, " \n\r\t");
            foreach ($tokens as $anstok) {
                if (!$tok || $tok !== $anstok)
                    return ONLINEJUDGE2_STATUS_WRONG_ANSWER;
                $tok = strtok(" \n\r\t");
            }

            return ONLINEJUDGE2_STATUS_PRESENTATION_ERROR;
        }
    }
}
/*
const ONLINEJUDGE2_STATUS_PENDING                = 0;

const ONLINEJUDGE2_STATUS_ACCEPTED               = 1;
const ONLINEJUDGE2_STATUS_ABNORMAL_TERMINATION   = 2;
const ONLINEJUDGE2_STATUS_COMPILATION_ERROR      = 3;
const ONLINEJUDGE2_STATUS_COMPILATION_OK         = 4;
const ONLINEJUDGE2_STATUS_MEMORY_LIMIT_EXCEED    = 5;
const ONLINEJUDGE2_STATUS_OUTPUT_LIMIT_EXCEED    = 6;
const ONLINEJUDGE2_STATUS_PRESENTATION_ERROR     = 7;
const ONLINEJUDGE2_STATUS_RESTRICTED_FUNCTIONS   = 8;
const ONLINEJUDGE2_STATUS_RUNTIME_ERROR          = 9;
const ONLINEJUDGE2_STATUS_TIME_LIMIT_EXCEED      = 10;
const ONLINEJUDGE2_STATUS_WRONG_ANSWER           = 11;

const ONLINEJUDGE2_STATUS_INTERNAL_ERROR         = 21;
const ONLINEJUDGE2_STATUS_JUDGING                = 22;
const ONLINEJUDGE2_STATUS_MULTI_STATUS           = 23;
*/

define("ONLINEJUDGE2_STATUS_PENDING",               0 );

define("ONLINEJUDGE2_STATUS_ACCEPTED",              1 );
define("ONLINEJUDGE2_STATUS_ABNORMAL_TERMINATION",  2 );
define("ONLINEJUDGE2_STATUS_COMPILATION_ERROR",     3 );
define("ONLINEJUDGE2_STATUS_COMPILATION_OK",        4 );
define("ONLINEJUDGE2_STATUS_MEMORY_LIMIT_EXCEED",   5 );
define("ONLINEJUDGE2_STATUS_OUTPUT_LIMIT_EXCEED",   6 );
define("ONLINEJUDGE2_STATUS_PRESENTATION_ERROR",    7 );
define("ONLINEJUDGE2_STATUS_RESTRICTED_FUNCTIONS",  8 );
define("ONLINEJUDGE2_STATUS_RUNTIME_ERROR",         9 );
define("ONLINEJUDGE2_STATUS_TIME_LIMIT_EXCEED",     10);
define("ONLINEJUDGE2_STATUS_WRONG_ANSWER",          11);

define("ONLINEJUDGE2_STATUS_INTERNAL_ERROR",        21);
define("ONLINEJUDGE2_STATUS_JUDGING",               22);
define("ONLINEJUDGE2_STATUS_MULTI_STATUS",          23);

/**
 * Returns an sorted array of all programming languages supported
 *
 * The array key must be the language's ID, such as c_sandbox, python_ideone.
 * The array value must be a human-readable name of the language, such as 'C (local)', 'Python (ideone.com)'
 */
function onlinejudge2_get_languages() {
    global $judgeclasses;

    $langs = array();
    foreach ($judgeclasses as $judgeclass) {
        $langs = array_merge($langs, $judgeclass::get_languages());
    }

    asort($langs);
    //print_r($langs);
    return $langs;
}

/**
 * Return the human-readable name of specified language 
 *
 * @param string $language ID of the language
 * @return name 
 */
function onlinejudge2_get_language_name($language) {
    $langs = onlinejudge2_get_languages();
    return $langs[$language];
}

/**
 * Submit task to judge of specified language
 *
 * @param int $cm ID of coursemodule
 * @param int $user ID of user
 * @param string $language ID of the language
 * @param string $source Source code
 * @param object $options include input, output and etc. 
 * @return id of the task or false
 */
function onlinejudge2_submit_task($cm, $user, $language, $source, $options) {
    global $judgeclasses, $CFG, $DB;
	//TODO: complete this function
    $id = false; //return id
    //get the languages.
    $langs_arr = array_flip(onlinejudge2_get_languages());
    //check if @param language is the the langs array.
    if(in_array($language, $langs_arr)) {
    	//echo $language.' in the language lib';
        //get the judge type, such as sandbox, ideone etc.
        $judge_type = substr($language, strrpos($language, '_')+1);
        
        //get the compiler, such as judge_sandbox, judge_ideone etc.
        $judge_compiler = 'judge_'.$judge_type;
        
        //select the certain compiler by judge_type
        //TODO: 这里要面向未来编程，不能写死sandbox、ideone这样的字眼
        if(in_array($judge_compiler, $judgeclasses)) {
            require_once("$CFG->dirroot/local/onlinejudge2/judge/$judge_type/lib.php");
            
            $judge_obj = new $judge_compiler();
            
            //packing the task data.
            $task = new stdClass();
            $task->cm = $cm;
            $task->user = $user;
            $task->language = $language;
            $task->source = $source;
            $task->memlimit = $options->memlimit;
            $task->cpulimit = $options->cpulimit;
            $task->input = $options->input;
            $task->output = $options->output;
            $task->compileonly = $options->compileonly;
            $task->status = ONLINEJUDGE2_STATUS_PENDING;
            $task->submittime = time();
            $task->error = $error;
            $task->onlinejudge2_ideone_username = $options->onlinejudge2_ideone_username;
            $task->onlinejudge2_ideone_password = $options->onlinejudge2_ideone_password;
            $task->onlinejudge_ideone_delay = $options->onlinejudge2_ideone_delay;
            //get the id
            $id = $judge_obj->judge($task);
            
            //save the task into database
            //$id = $DB->insert_record('onlinejudge2_tasks', $task, true);         
        }
    }
    
    return $id;
}


/**
 * Return detail of the task
 *
 * @param int $taskid
 * @return object of task or null if unavailable
 */
function onlinejudge2_get_task($taskid) {
    global $DB;
    $result = new stdClass();
    $result = $DB->get_record('onlinejudge2_tasks', array('id' => $taskid));

    if($result->status == ONLINEJUDGE2_STATUS_JUDGING) {
        echo get_string('status22', 'local_onlinejudge2');
        return null;
    }
    else {
        return $result;
    }
}

/**
 * Return the overall status of a list of tasks
 *
 * @param array $tasks
 * @return Overall status
 */
function onlinejudge2_get_overall_status($tasks) {

    $status = 0;
    foreach ($tasks as $task) {
        if (is_null($task)) // We can't give out any status on null task
            return 0;

        if ($status == 0) {
            $status = $task->status;
        } else if ($status != $task->status) {
            $status = ONLINEJUDGE2_STATUS_MULTI_STATUS;
            break;
        }
    }

    return $status;
}

/**
 * Get one unjudged tasks and set it as judged
 * If all tasks have been judged, return false
 * The function can be reentranced
 */
function get_unjudged_tasks() {
    global $CFG;
    while (!set_cron_lock('onlinejudge2_judging', time() + 10)) {}
    $tasks = get_records_sql('onlinejudge2_tasks', array('status' => ONLINEJUDGE2_STATUS_PENDING));
    $task = null;
    if ($tasks != null) {
        $task = array_pop($tasks);
        // Set judged mark
        $DB->set_field('onlinejudge2_tasks', 'judged', 1, array('id' => $task->id));
    }

        set_cron_lock('onlinejudge2_judging', null);

        return $task;     
}


/**
 * Evaluate student submissions
 */
function cron() {

	global $CFG;

	// Detect the frequence of cron
	//should modify this cron.
	$lastcron = $DB->get_field('modules', 'lastcron', 'name', 'assignment');
    if ($lastcron) {
        set_config('assignment_oj_cronfreq', time() - $lastcron);
    }

    // There are two judge routines
    //  1. Judge only when cron job is running. 
    //  2. After installation, the first cron running will fork a daemon to be judger.
    // Routine two works only when the cron job is executed by php cli
    //
    if (function_exists('pcntl_fork')) { // pcntl_fork supported. Use routine two
        $this->fork_daemon();
    }
     else if ($CFG->onlinejudge2_judge_in_cron) { // pcntl_fork is not supported. So use routine one if configured.
        $this->judge_all_unjudged();
    }
}

function fork_daemon() {
    global $CFG, $db;

    if(empty($CFG->onlinejudge2_daemon_pid) || !posix_kill($CFG->onlinejudge2_daemon_pid, 0)){ // No daemon is running
       $pid = pcntl_fork(); 
       if ($pid == -1) {
            mtrace('Could not fork');
       } else if ($pid > 0){ //Parent process
       //Reconnect db, so that the parent won't close the db connection shared with child after exit.
       reconnect_db();

        set_config('onlinejudge2_daemon_pid' , $pid);
       } 
       else { //Child process
            $this->daemon(); 
       }
    }
}

function daemon(){
    global $CFG;

    $pid = getmypid();
    mtrace('Judge daemon created. PID = ' . $pid);

    if (function_exists('pcntl_fork')) { // In linux, this is a new session
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

    set_config('onlinejudge2_daemon_pid' , $pid);

    // Run forever until be killed or plugin was upgraded
    while(!empty($CFG->onlinejudge2_daemon_pid)){
        global $db;
        $this->judge_all_unjudged();

        // If error occured, reconnect db
        if ($db->ErrorNo())
        reconnect_db();

        //Check interval is 5 seconds
        sleep(5);

        //renew the config value which could be modified by other processes
        $CFG->onlinejudge2_daemon_pid = get_config(NULL, 'onlinejudge2_daemon_pid');
    }
}

// Judge all unjudged tasks
function judge_all_unjudged(){
        global $CFG;
        while ($task = $this->get_unjudged_tasks()) {
            $cm = get_coursemodule_from_instance('assignment', $task->coursemodule);

            $this->judge($task);
        }
    }




