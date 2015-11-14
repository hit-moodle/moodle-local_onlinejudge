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

require_once(dirname(__FILE__).'/exceptions.php');

$judge_plugins = get_list_of_plugins('local/onlinejudge/judge');
foreach ($judge_plugins as $dir) {
    require_once("$CFG->dirroot/local/onlinejudge/judge/$dir/lib.php");
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
            if ($key == 'memlimit' and $value > 1024 * 1024 * get_config('local_onlinejudge', 'maxmemlimit')) {
                $value = 1024 * 1024 * get_config('local_onlinejudge', 'maxmemlimit');
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
     * If string is not encoded in UTF-8, convert it into utf-8 charset
     */
    protected function convert_to_utf8($string) {
        $localwincharset = get_string('localewincharset', 'langconfig');
        if (!empty($localwincharset) and !mb_check_encoding($string, 'UTF-8') and mb_check_encoding($string, $localwincharset)) {
            $textlib = textlib_get_instance();
            return $textlib->convert($string, $localwincharset);
        } else {
            return $string;
        }
    }

    /**
     * Compare the stdout of program and the output of testcase
     */
    protected function diff() {
        $task = & $this->task;

        // convert data into UTF-8 charset if possible
        $task->stdout = $this->convert_to_utf8($task->stdout);
        $task->stderr = $this->convert_to_utf8($task->stderr);
        $task->output = $this->convert_to_utf8($task->output);

        // trim tailing return chars which are meaning less
        $task->output = rtrim($task->output, "\r\n");
        $task->stdout = rtrim($task->stdout, "\r\n");

        if (strcmp($task->output, $task->stdout) == 0)
            return ONLINEJUDGE_STATUS_ACCEPTED;
        else {
            $tokens = array();
            $tok = strtok($task->output, " \n\r\t");
            while ($tok !== false) {
                $tokens[] = $tok;
                $tok = strtok(" \n\r\t");
            }

            $tok = strtok($task->stdout, " \n\r\t");
            foreach ($tokens as $anstok) {
                if ($tok === false || $tok !== $anstok)
                    return ONLINEJUDGE_STATUS_WRONG_ANSWER;
                $tok = strtok(" \n\r\t");
            }
            if ($tok !== false) {
                return ONLINEJUDGE_STATUS_WRONG_ANSWER;
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
            $path = onlinejudge_get_temp_dir().$file->get_filepath();
            $fullpath = $path.$file->get_filename();
            if (!check_dir_exists($path)) {
                throw new moodle_exception('errorcreatingdirectory', '', '', $path);
            }
            $file->copy_content_to($fullpath);
            $dstfiles[] = $fullpath;
        }

        return $dstfiles;
    }

    /**
     * Return the infomation of the compiler of specified language
     *
     * @param string $language ID of the language
     * @return compiler information or null
     */
    static function get_compiler_info($language) {
        return array();
    }

    /**
     * Whether the judge is avaliable
     *
     * @return true for yes, false for no
     */
    static function is_available() {
        return false;
    }
}

/**
 * Returns an sorted array of all programming languages supported
 *
 * The array key must be the language's ID, such as c_sandbox, python_ideone.
 * The array value must be a human-readable name of the language, such as 'C (local)', 'Python (ideone.com)'
 */
function onlinejudge_get_languages() {
    $langs = array();
    $judgeclasses = onlinejudge_get_judge_classes();
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
    $judgeclasses = onlinejudge_get_judge_classes();
    $judgeclass = 'judge_'.onlinejudge_judge_name($language);
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
function onlinejudge_submit_task($cmid, $userid, $language, $files, $component, $options) {
    global $DB;

    $task->cmid = $cmid;
    $task->userid = $userid;
    $task->status = ONLINEJUDGE_STATUS_PENDING;
    $task->submittime = time();

    if (!array_key_exists($language, onlinejudge_get_languages())) {
        throw new onlinejudge_exception('invalidlanguage', $language);
    }
    $task->language = $language;
    $task->component = $component;

    $judgeclass = 'judge_'.onlinejudge_judge_name($language);
    $judgeclasses = onlinejudge_get_judge_classes();
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
            if (strpos($file_record->filepath, '/') !== 0) {
                $file_record->filepath = '/'.$file_record->filepath;
            }
            if (strrpos($file_record->filepath, '/') !== strlen($file_record->filepath)-1) {
                $file_record->filepath .= '/';
            }
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
    global $CFG, $DB;

    if (is_object($taskorid)) {
        $task = $taskorid;
    } else {
        $task = $DB->get_record('onlinejudge_tasks', array('id' => $taskorid));
    }

    $task->judgetime = time();

    $judgeclass = 'judge_'.onlinejudge_judge_name($task->language);
    $judgeclasses = onlinejudge_get_judge_classes();
    if (!in_array($judgeclass, $judgeclasses)) {
        $task->status = ONLINEJUDGE_STATUS_INTERNAL_ERROR;
        $task->infostudent = get_string('invalidjudgeclass', 'local_onlinejudge', $judgeclass);
        $DB->update_record('onlinejudge_tasks', $task);
        throw new onlinejudge_exception('invalidjudgeclass', $judgeclass);
    }

    $judge = new $judgeclass($task);

    try {
        $task = $judge->judge();
    } catch (Exception $e) {
        $task->status = ONLINEJUDGE_STATUS_INTERNAL_ERROR;
        $task->infostudent = $e->getMessage();
        $DB->update_record('onlinejudge_tasks', $task);
        events_trigger('onlinejudge_task_judged', $task);
        throw $e;
    }

    $DB->update_record('onlinejudge_tasks', $task);
    events_trigger('onlinejudge_task_judged', $task);

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

function onlinejudge_get_judge_classes() {
    global $CFG;

    static $judgeclasses = array();

    if (empty($judgeclasses)) {
        if ($plugins = get_list_of_plugins('local/onlinejudge/judge')) {
            foreach ($plugins as $plugin=>$dir) {
                $judgeclasses[] = "judge_$dir";
            }
        }
    }

    return $judgeclasses;
}

/**
 * Parse judge engine name from language
 */
function onlinejudge_judge_name($language) {
    return  substr($language, strrpos($language, '_')+1);
}

/**
 * Delete related records
 *
 * @param int $cmid
 */
function onlinejudge_delete_coursemodule($cmid) {
    global $DB;

    // Mark them as deleted only and keep the statistics.
    // Delete them really in cron
    return $DB->set_field('onlinejudge_tasks', 'deleted', 1, array('cmid' => $cmid));
}

function onlinejudge_get_temp_dir() {
    global $CFG;

    // Use static variable to suppress getmypid() calls
    // The same process use the same temp dir so that
    // it is possable to reuse some temp files
    static $tmpdir = '';
    if (empty($tmpdir)) {
        $tmpdir = $CFG->dataroot.'/temp/onlinejudge/'.getmypid();
    }

    if (!check_dir_exists($tmpdir)) {
        throw new moodle_exception('errorcreatingdirectory', '', '', $tmpdir);
    }

    return $tmpdir;
}

function onlinejudge_clean_temp_dir($content_only=true) {
    remove_dir(onlinejudge_get_temp_dir(), $content_only);
}

