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
            mtrace(get_string('nosuchrecord', 'local_onlinejudge2'));
        } 
        //result class
        $result = new stdClass();
        $result = null; 
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
    function judge(& $task) {
        return false;
    }

    /**
     * 
     * function diff() compare the output and the answer
     */  
    function diff($output, $answer) {
    	//format
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

define("ONLINEJUDGE2_STATUS_UNSUBMITTED",          255);

// define max_mem and max_cpu
define("ONLINEJUDGE2_MAX_CPU",                       1);
define("ONLINEJUDGE2_MAX_MEM",                 1048576);

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
 * @param int $cmid ID of coursemodule
 * @param int $userid ID of user
 * @param string $language ID of the language
 * @param string $source Source code
 * @param object $options include input, output and etc. 
 * @return id of the task or false
 */
function onlinejudge2_submit_task($cmid, $userid, $language, $source, $options) {
    global $judgeclasses, $CFG, $DB;
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
        if(in_array($judge_compiler, $judgeclasses)) {
            //require_once("$CFG->dirroot/local/onlinejudge2/judge/$judge_type/lib.php");
            
            //$judge_obj = new $judge_compiler();
            
            //packing the task data.
            $task = new stdClass();
            $task->coursemodule = $cmid;
            $task->userid = $userid;
            $task->language = $language;
            $task->source = $source;
            $task->memlimit = $options->memlimit;
            $task->cpulimit = $options->cpulimit;
            $task->input = $options->input;
            $task->output = $options->output;
            $task->compileonly = $options->compileonly;
            $task->status = ONLINEJUDGE2_STATUS_PENDING;
            $task->submittime = time();

            //other info.
            $task->onlinejudge2_ideone_username = $options->onlinejudge2_ideone_username;
            $task->onlinejudge2_ideone_password = $options->onlinejudge2_ideone_password;
            $task->onlinejudge_ideone_delay = $options->onlinejudge2_ideone_delay;
            
            //save the task into database
            $id = $DB->insert_record('onlinejudge2_tasks', $task, true);
            
            if(! $id) {
                //print error info
                mtrace(get_string('nosuchrecord', 'local_onlinejudge2'));
            }         
        }
    }
    
    return $id;
}

/**
 * select the compiler and judge the task introduced by user,
 *  this should based on backup process
 * and start by judged.php in /moodle/local/onlinejudge2/ 
 * 
 * ps:this function update the record in the database after judge.
 * 
 * @param $taskid should be got from onlinejudge2_submit_task function.
 * @return the result after judge.
 */
function onlinejudge2_judge($taskid) {
    global $CFG, $DB, $judgeclasses;
    //result class
    $result = new stdClass(); 
    $result = null; 
    
    $task = $DB->get_record('onlinejudge2_tasks', array('id' => $taskid));
    $result = $task;
    // doesn't has task
    if(is_null($task)) {
        //print error info
        mtrace(get_string('nosuchrecord', 'local_onlinejudge2'));
        return $result;
    }
    
    // check the status
    // pending
    if($task->status == ONLINEJUDGE2_STATUS_PENDING) {
    //if(1) { //for test
        //get judge language, such as cpp_ideone, c_sandbox.
        $language = $task->language;
        
        //get the judge type, such as sandbox, ideone etc.
        $judge_type = substr($language, strrpos($language, '_')+1);
        
        //get the compiler, such as judge_sandbox, judge_ideone etc.
        $judge_compiler = 'judge_'.$judge_type;
        
        //select the certain compiler by judge_type
        if(in_array($judge_compiler, $judgeclasses)) {
            require_once($CFG->dirroot."/local/onlinejudge2/judge/$judge_type/lib.php");    
            $judge_obj = new $judge_compiler();
            // call the judge function. 
            $result = $judge_obj->judge(& $task);
            
            //before return , update the record.
            $new_record = new stdClass();
            $new_record = null;
            //packing
            $new_record->id = $task->id;
            $new_record->coursemodule = $task->coursemodule;
            $new_record->userid = $task->userid;
            $new_record->language = $task->language;
            $new_record->source = $task->source;
            $new_record->memlimit = $task->memlimit;
            $new_record->cpulimit = $task->cpulimit;    
            $new_record->input = $task->input;
            $new_record->output = $task->output;
            $new_record->compileonly = $task->compileonly;
            $new_record->submittime = $task->submittime;
                
            //set record as result 
            $new_record->answer = $result->answer;
            $new_record->judgetime = $result->judgetime;
            $new_record->cpuusage = $result->cpuusage;
            $new_record->memusage = $result->memusage;
            $new_record->status = $result->status;             
            $new_record->info_teacher = $result->info_teacher;
            $new_record->info_student = $result->info_student;
                
            //record error ,if exists.
            $new_record->error = $result->error;
            
            if(!$DB->update_record('onlinejudge2_tasks', $new_record)) {
                mtrace(get_string('cannotupdaterecord', 'local_onlinejudge2'));
            }
            
            return $result;
        }
        else {
            mtrace(get_string('nosuchlanguage', 'local_onlinejudge2'));
            return $result;
        }
    }
    // been judging
    else if($task->status == ONLINEJUDGE2_STATUS_JUDGING) {
        //judging
        mtrace('status22', 'local_onlinejudge2');
    }
    // been judged
    else {
        mtrace(get_string('status24', 'local_onlinejudge2'));
        //返回task
        return $task;
    }
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

    $status = ONLINEJUDGE2_STATUS_UNSUBMITTED;
    foreach ($tasks as $task) {
        if (is_null($task)) // We can't give out any status on null task
            return ONLINEJUDGE2_STATUS_UNSUBMITTED;

        if ($status == 0) {
            $status = $task->status;
        } 
        else if ($status != $task->status) {
            $status = ONLINEJUDGE2_STATUS_MULTI_STATUS;
            break;
        }
    }

    return $status;
}


/********************    events    ******************/

// when judge begin, call cron
function event_judge_begin() {
}

// when judge over, notify the user or others
function event_judge_over() {
   
}

// when judge error, notify the user or others.
function event_judge_error() {
    
}

/***************    for settings.php    **************/

/**
 * This function returns an
 * array of possible memory sizes in an array, translated to the
 * local language.
 *
 * @uses SORT_NUMERIC
 * @param int $sizebytes Moodle site $CFG->onlinejudge2_max_mem
 * @return array
 */
function get_max_memory_usages($sitebytes=0) {
    global $CFG;

    // Get max size
    $maxsize = $sitebytes;
    
    $memusage[$maxsize] = display_size($maxsize);
    
    $sizelist = array(4194304, 8388608, 16777216, 33554432,
                      67108864, 134217728, 268435456, 536870912);
    
    // Allow maxbytes to be selected if it falls outside the above boundaries
    if( isset($CFG->onlinejudge2_max_mem) && !in_array($CFG->onlinejudge2_max_mem, $sizelist) ){
        $sizelist[] = $CFG->onlinejudge2_max_mem;
    }
    
    foreach ($sizelist as $sizebytes) {
       if ($sizebytes < $maxsize) {
           $memusage[$sizebytes] = display_size($sizebytes);
       }
    }
    
    krsort($memusage, SORT_NUMERIC);
    
    return $memusage;
}
    
/**
 * This function returns an
 * array of possible CPU time (in seconds) in an array
 *
 * @uses SORT_NUMERIC
 * @param int $time Moodle site $CGF->onlinejudge2_max_cpu
 * @return array
 */
function get_max_cpu_times($time=0) {
    global $CFG;

    // Get max size
    $maxtime = $time;
    
    $cputime[$maxtime] = get_string('numseconds', 'moodle', $maxtime);
    
    $timelist = array(1, 2, 3, 4, 5, 6, 7, 8, 9,
                      10, 11, 12, 13, 14, 15, 20,
                      25, 30, 40, 50, 60);
    
    // Allow maxtime to be selected if it falls outside the above boundaries
    if( isset($CFG->onlinejudge2_max_cpu) && !in_array($CFG->onlinejudge2_max_cpu, $timelist) ){
        $cputime[] = $CFG->onlinejudge2_max_cpu;
    }
    
    foreach ($timelist as $timesecs) {
       if ($timesecs < $maxtime) {
           $cputime[$timesecs] = get_string('numseconds', 'moodle', $timesecs);
       }
    }
    
    ksort($cputime, SORT_NUMERIC);
    
    return $cputime;
}

function reconnect_db()
{
    global $DB;
    // Reconnect db
    $DB->Close();

    while (!$DB->NConnect())
        sleep(5);

    $DB->configure_dbconnection();
}    

