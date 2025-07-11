<?php
// This file is part of Moodle - https://moodle.org
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * NOTICE OF COPYRIGHT
 *
 *                      Online Judge for Moodle
 *        https://github.com/hit-moodle/moodle-local_onlinejudge
 *
 * Copyright (C) 2009 onwards
 *                      Sun Zhigang  http://sunner.cn
 *                      Andrew Naguib <andrew at fci helwan edu eg>
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details:
 *
 *          http://www.gnu.org/copyleft/gpl.html
 */

/**
 * online judge library
 *
 * @package   local_onlinejudge
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

define("ONLINEJUDGE_STATUS_PENDING", 0);

define("ONLINEJUDGE_STATUS_ACCEPTED", 1);
define("ONLINEJUDGE_STATUS_ABNORMAL_TERMINATION", 2);
define("ONLINEJUDGE_STATUS_COMPILATION_ERROR", 3);
define("ONLINEJUDGE_STATUS_COMPILATION_OK", 4);
define("ONLINEJUDGE_STATUS_MEMORY_LIMIT_EXCEED", 5);
define("ONLINEJUDGE_STATUS_OUTPUT_LIMIT_EXCEED", 6);
define("ONLINEJUDGE_STATUS_PRESENTATION_ERROR", 7);
define("ONLINEJUDGE_STATUS_RESTRICTED_FUNCTIONS", 8);
define("ONLINEJUDGE_STATUS_RUNTIME_ERROR", 9);
define("ONLINEJUDGE_STATUS_TIME_LIMIT_EXCEED", 10);
define("ONLINEJUDGE_STATUS_WRONG_ANSWER", 11);

define("ONLINEJUDGE_STATUS_INTERNAL_ERROR", 21);
define("ONLINEJUDGE_STATUS_JUDGING", 22);
define("ONLINEJUDGE_STATUS_MULTI_STATUS", 23);

define("ONLINEJUDGE_STATUS_UNSUBMITTED", 255);

require_once(dirname(__FILE__) . '/exceptions.php');

// Load new classes
require_once(dirname(__FILE__) . '/classes/exception.php');
require_once(dirname(__FILE__) . '/classes/judge/base.php');

// Backward compatibility aliases
if (!class_exists('onlinejudge_exception')) {
    class_alias('\\local_onlinejudge\\exception', 'onlinejudge_exception');
}
if (!class_exists('judge_base')) {
    class_alias('\\local_onlinejudge\\judge\\base', 'judge_base');
}

$judgeplugins = get_list_of_plugins('local/onlinejudge/judge');
foreach ($judgeplugins as $dir) {
    require_once("$CFG->dirroot/local/onlinejudge/judge/$dir/lib.php");
}

// Load new judge classes
require_once(dirname(__FILE__) . '/classes/judge/sandbox.php');
require_once(dirname(__FILE__) . '/classes/judge/sphere_engine.php');

// Backward compatibility aliases for judge classes
if (!class_exists('judge_sandbox')) {
    class_alias('\\local_onlinejudge\\judge\\sandbox', 'judge_sandbox');
}
if (!class_exists('judge_sphere_engine')) {
    class_alias('\\local_onlinejudge\\judge\\sphere_engine', 'judge_sphere_engine');
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
    return $langs;
}

/**
 * Return the human-readable name of specified language
 *
 * @param string $language ID of the language
 * @return string [name]
 */
function onlinejudge_get_language_name($language) {
    $langs = onlinejudge_get_languages();
    return $langs[$language];
}

/**
 * Return the infomation of the compiler of specified language
 *
 * @param string $language ID of the language
 * @return null|string [compiler information]
 */
function onlinejudge_get_compiler_info($language) {
    $judgeclasses = onlinejudge_get_judge_classes();
    $judgeclass = 'judge_' . onlinejudge_judge_name($language);
    return $judgeclass != 'judge_' ? $judgeclass::get_compiler_info($language) : false;
}

/**
 * Submit task to judge
 *
 * @param int $cmid ID of coursemodule
 * @param int $userid ID of user
 * @param string $language ID of the language
 * @param array $files array of stored_file of source code or array of filename => filecontent
 * @param $component
 * @param object $options include input, output and etc.
 * @return bool|int [id of the task]
 * @throws onlinejudge_exception
 */
function onlinejudge_submit_task($cmid, $userid, $language, $files, $component, $options) {
    global $DB;

    $task = new \stdClass;
    $task->cmid = $cmid;
    $task->userid = $userid;
    $task->status = ONLINEJUDGE_STATUS_PENDING;
    $task->submittime = time();
    if (!array_key_exists($language, onlinejudge_get_languages())) {
        throw new onlinejudge_exception('invalidlanguage', $language);
    }
    $task->language = $language;
    $task->component = $component;

    $judgeclass = 'judge_' . onlinejudge_judge_name($language);
    $judgeclasses = onlinejudge_get_judge_classes();
    if (!in_array($judgeclass, $judgeclasses)) {
        throw new onlinejudge_exception('invalidjudgeclass', $judgeclass);
    }

    $judgeclass::parse_options($options, $task);

    $task->id = $DB->insert_record('onlinejudge_tasks', $task);

    $fs = get_file_storage();
    $filerecord = new \stdClass;
    $filerecord->contextid = context_system::instance()->id;
    $filerecord->component = 'local_onlinejudge';
    $filerecord->filearea = 'tasks';
    $filerecord->itemid = $task->id;
    foreach ($files as $key => $value) {
        if ($value instanceof stored_file) {
            $fs->create_file_from_storedfile($filerecord, $value);
        } else {
            $filerecord->filepath = dirname($key);
            if (strpos($filerecord->filepath, '/') !== 0) {
                $filerecord->filepath = '/' . $filerecord->filepath;
            }
            if (strrpos($filerecord->filepath, '/') !== strlen($filerecord->filepath) - 1) {
                $filerecord->filepath .= '/';
            }
            $filerecord->filename = basename($key);
            $fs->create_file_from_string($filerecord, $value);
        }
    }

    return $task->id;
}

/**
 * Judge specified task
 *
 * @param $taskorid object of task or task id
 * @return stdClass task updated
 * @throws Exception
 * @throws onlinejudge_exception
 */

function onlinejudge_judge($taskorid) {
    global $DB;
    require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'event' . DIRECTORY_SEPARATOR . 'onlinejudge_task_judged.php');

    if (is_object($taskorid)) {
        $task = $taskorid;
    } else {
        $task = $DB->get_record('onlinejudge_tasks', array('id' => $taskorid));
    }

    $task->judgetime = time();

    $judgeclass = 'judge_' . onlinejudge_judge_name($task->language);
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
        $context = context_module::instance($task->cmid);
        $event = \mod_onlinejudge\event\onlinejudge_task_judged::create(array('context' => $context, 'objectid' => $task->id));
        $event->add_record_snapshot('onlinejudge_tasks', $task);
        $event->trigger();
        throw $e;
    }

    $DB->update_record('onlinejudge_tasks', $task);
    $context = context_module::instance($task->cmid);
    $event = \mod_onlinejudge\event\onlinejudge_task_judged::create(array('context' => $context, 'objectid' => $task->id));
    $event->add_record_snapshot('onlinejudge_tasks', $task);
    $event->trigger();
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
 * @return int status
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
            foreach ($plugins as $plugin => $dir) {
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
    return substr($language, strpos($language, '-') + 1);
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
        $tmpdir = $CFG->dataroot . '/temp/onlinejudge/' . getmypid();
    }

    if (!check_dir_exists($tmpdir)) {
        throw new moodle_exception('errorcreatingdirectory', '', '', $tmpdir);
    }

    return $tmpdir;
}

function onlinejudge_clean_temp_dir($content_only = true) {
    remove_dir(onlinejudge_get_temp_dir(), $content_only);
}