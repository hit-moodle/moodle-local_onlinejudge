<?php

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
//                      Online Judge for Moodle                          //
//        https://github.com/hit-moodle/moodle-local_onlinejudge         //
//                                                                       //
// Copyright (C) 2009 onwards  Sun Zhigang  http://sunner.cn             //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 3 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
 * online judge library
 *
 * @package   local_onlinejudge
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

define("ONLINEJUDGE_STATUS_PENDING",               0 );

define("ONLINEJUDGE_STATUS_ACCEPTED",              1 );
define("ONLINEJUDGE_STATUS_ABNORMAL_TERMINATION",  2 );
define("ONLINEJUDGE_STATUS_COMPILATION_ERROR",     3 );
define("ONLINEJUDGE_STATUS_COMPILATION_OK",        4 );
define("ONLINEJUDGE_STATUS_MEMORY_LIMIT_EXCEED",   5 );
define("ONLINEJUDGE_STATUS_OUTPUT_LIMIT_EXCEED",   6 );
define("ONLINEJUDGE_STATUS_PRESENTATION_ERROR",    7 );
define("ONLINEJUDGE_STATUS_RESTRICTED_FUNCTIONS",  8 );
define("ONLINEJUDGE_STATUS_RUNTIME_ERROR",         9 );
define("ONLINEJUDGE_STATUS_TIME_LIMIT_EXCEED",     10);
define("ONLINEJUDGE_STATUS_WRONG_ANSWER",          11);

define("ONLINEJUDGE_STATUS_INTERNAL_ERROR",        21);
define("ONLINEJUDGE_STATUS_JUDGING",               22);
define("ONLINEJUDGE_STATUS_MULTI_STATUS",          23);

define("ONLINEJUDGE_STATUS_UNSUBMITTED",          255);

require_once(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/exceptions.php');

global $judgeclasses;
$judgeclasses = array();

if ($plugins = get_list_of_plugins('local/onlinejudge/judge')) {
    foreach ($plugins as $plugin=>$dir) {
        require_once("$CFG->dirroot/local/onlinejudge/judge/$dir/lib.php");
        $judgeclasses[] = "judge_$dir";
    }
}

class judge_base{

    // object of the task
    protected $task;

    // language id without judge id
    protected $language;

    function __construct($task) {
        $this->task = $task;
        $this->language = substr($this->task->language, 0, strrpos($this->task->language, '_'));
    }

    function __destruct() {
        if (!debugging()) {
            remove_dir($this->get_temp_dir());
        }
    }

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
     * Put options into task
     *
     * @param object options
     * @return throw exceptions on error
     */
    static function parse_options($options, & $task) {
        $options = (array)$options;

        // only common options are parsed here.
        // special options should be parsed by childclass
        foreach ($options as $key=>$value) {
            if ($key == 'memlimit' and $value > get_config('local_onlinejudge', 'maxmemlimit')) {
                $value = get_config('local_onlinejudge', 'maxmemlimit');
            }
            if ($key == 'cpulimit' and $value > get_config('local_onlinejudge', 'maxcpulimit')) {
                $value = get_config('local_onlinejudge', 'maxcpulimit');
            }
            $task->$key = $value;
        }
    }

    /**
     * Judge the current task
     *
     * @return updated task or false
     */
    function judge() {
        return false;
    }

    /**
     * Compare the output and the answer
     */  
    protected function diff($output, $answer) {
    	//format
        $answer = strtr(trim($answer), array("\r\n" => "\n", "\n\r" => "\n"));
        $output = strtr(trim($output), array("\r\n" => "\n", "\n\r" => "\n"));
        if (strcmp($answer, $output) == 0)
            return ONLINEJUDGE_STATUS_ACCEPTED;
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
                    return ONLINEJUDGE_STATUS_WRONG_ANSWER;
                $tok = strtok(" \n\r\t");
            }

            return ONLINEJUDGE_STATUS_PRESENTATION_ERROR;
        }
    }

    /**
     * Save files of current task to a temp directory
     *
     * @return array of the full path of saved files
     */
    protected function create_temp_files() {
        $dstfiles = array();

        $fs = get_file_storage();
        $files = $fs->get_area_files(get_context_instance(CONTEXT_SYSTEM)->id, 'local_onlinejudge', 'tasks', $this->task->id, 'sortorder', false);
        foreach ($files as $file) {
            $path = $this->get_temp_dir().$file->get_filepath();
            $fullpath = $path.$file->get_filename();
            if (!check_dir_exists($path)) {
                throw new onlinejudge_exception('cannotcreatetmpdir', $path);
            }
            $file->copy_content_to($fullpath);
            $dstfiles[] = $fullpath;
        }

        return $dstfiles;
    }

    protected function get_temp_dir() {
        global $CFG;

        $tmpdir = "$CFG->dataroot/temp/onlinejudge/{$this->task->id}";
        if (!check_dir_exists($tmpdir)) {
            throw new onlinejudge_exception('cannotcreatetmpdir', $tmpdir);
        }

        return $tmpdir;
    }

    /**
     * Return the infomation of the compiler of specified language
     *
     * @param string $language ID of the language
     * @return compiler information or null
     */
    static function get_compiler_info($language) {
        return null;
    }

}

/**
 * Returns an sorted array of all programming languages supported
 *
 * The array key must be the language's ID, such as c_sandbox, python_ideone.
 * The array value must be a human-readable name of the language, such as 'C (local)', 'Python (ideone.com)'
 */
function onlinejudge_get_languages() {
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
function onlinejudge_get_language_name($language) {
    $langs = onlinejudge_get_languages();
    return $langs[$language];
}

/**
 * Return the infomation of the compiler of specified language
 *
 * @param string $language ID of the language
 * @return compiler information or null
 */
function onlinejudge_get_compiler_info($language) {
    global $judgeclasses;
    $judgeclass = 'judge_'.substr($language, strrpos($language, '_')+1);
    return $judgeclass::get_compiler_info($language);
}

/**
 * Submit task to judge
 *
 * @param int $cmid ID of coursemodule
 * @param int $userid ID of user
 * @param string $language ID of the language
 * @param array $files array of stored_file of source code or array of filename => filecontent
 * @param object $options include input, output and etc.
 * @return id of the task or throw exception
 */
function onlinejudge_submit_task($cmid, $userid, $language, $files, $options) {
    global $judgeclasses, $DB;

    $task->cmid = $cmid;
    $task->userid = $userid;
    $task->status = ONLINEJUDGE_STATUS_PENDING;
    $task->submittime = time();

    if (!array_key_exists($language, onlinejudge_get_languages())) {
        throw new onlinejudge_exception('invalidlanguage', $language);
    }
    $task->language = $language;

    $judgeclass = 'judge_'.substr($language, strrpos($language, '_')+1);
    if (!in_array($judgeclass, $judgeclasses)) {
        throw new onlinejudge_exception('invalidjudgeclass', $judgeclass);
    }

    $judgeclass::parse_options($options, $task);

    $task->id = $DB->insert_record('onlinejudge_tasks', $task);

    $fs = get_file_storage();
    $file_record->contextid = get_context_instance(CONTEXT_SYSTEM)->id;
    $file_record->component = 'local_onlinejudge';
    $file_record->filearea = 'tasks';
    $file_record->itemid = $task->id;
    foreach ($files as $key => $value) {
        if ($value instanceof stored_file) {
            $fs->create_file_from_storedfile($file_record, $value);
        } else {
            $file_record->filepath = dirname($key);
            $file_record->filename = basename($key);
            $fs->create_file_from_string($file_record, $value);
        }
    }

    return $task->id;
}

/**
 * Judge specified task
 * 
 * @param $taskorid object of task or task id
 * @return updated task
 */
function onlinejudge_judge($taskorid) {
    global $CFG, $DB, $judgeclasses;
    
    if (is_object($taskorid)) {
        $task = $taskorid;
    } else {
        $task = $DB->get_record('onlinejudge_tasks', array('id' => $taskorid));
    } 

    $task->judgetime = time();

    $judgeclass = 'judge_'.substr($task->language, strrpos($task->language, '_')+1);
    if (!in_array($judgeclass, $judgeclasses)) {
        throw new onlinejudge_exception('invalidjudgeclass', $judgeclass);
    }

    $judge = new $judgeclass($task);
    $task = $judge->judge();
    $DB->update_record('onlinejudge_tasks', $task);

    return $task;
}

/**
 * Return detail of the task
 *
 * @param int $taskid
 * @return object of task or null if unavailable
 */
function onlinejudge_get_task($taskid) {
    global $DB;

    return $DB->get_record('onlinejudge_tasks', array('id' => $taskid));
}

/**
 * Return the overall status of a list of tasks
 *
 * @param array $tasks
 * @return Overall status
 */
function onlinejudge_get_overall_status($tasks) {

    $status = ONLINEJUDGE_STATUS_UNSUBMITTED;
    foreach ($tasks as $task) {
        if (is_null($task)) // We can't give out any status on null task
            return ONLINEJUDGE_STATUS_UNSUBMITTED;

        if ($status == ONLINEJUDGE_STATUS_UNSUBMITTED) {
            $status = $task->status;
        } else if ($status != $task->status) {
            $status = ONLINEJUDGE_STATUS_MULTI_STATUS;
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


