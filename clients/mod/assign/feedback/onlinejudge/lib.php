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


// Required to initialize an assignfeedback_onlinejudge class.
require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->dirroot . "/mod/assign/locallib.php");
/**
 * Create a new onlinejudge type assignment activity
 *
 * @param stdClass $assign The data from the form
 * @param $assignid
 * @return int The id of the assignment
 */
function add_instance(stdClass $assign, $assignid) {
    global $DB;
    $returnid = null;
    if ($assignid) {
        $onlinejudge = $assign;
        $onlinejudge->assignment = $assignid;
        $returnid = $DB->insert_record('assignment_oj', $onlinejudge);
    }

    return $returnid;
}

/**
 * Updates a program assignment activity
 *
 * @param object $assignment The data from the form
 * @return int The assignment id
 */
function update_instance($assign, $assignid) {
    global $DB;
    $returnid = null;
    if ($assignid) {
        $onlinejudge = $assign;
        $oldonlinejudge = $DB->get_record('assignment_oj', array('assignment' => $assignid));
        if ($oldonlinejudge) {
            $onlinejudge->id = $oldonlinejudge->id;
            $returnid = $DB->update_record('assignment_oj', $onlinejudge);
        }
    }

    return $returnid;
}


/**
 * return all results of the submission
 *
 * it will update the grade if necessary
 * @param object [submission]
 * @param int [assigngrade]
 * @return object
 */
function get_onlinejudge_result($submission, $assigngrade) {
    global $DB;
    if (empty($submission)) return null;

    $sql = 'SELECT s.*, t.feedback, t.subgrade
                FROM {assignment_oj_submissions} s LEFT JOIN {assignment_oj_testcases} t
                ON s.testcase = t.id
                WHERE s.submission = ? AND s.latest = 1
                ORDER BY t.sortorder ASC';
    $onlinejudges = $DB->get_records_sql($sql, array($submission->id));
    $assoj = $DB->get_record('assignment_oj', array('assignment' => $submission->assignment));
    $cases = array();
    $result = new \stdClass;
    $result->judgetime = 0;
    foreach ($onlinejudges as $oj) {
        if ($task = onlinejudge_get_task($oj->task)) {
            $task->testcase = $oj->testcase;
            $task->feedback = $oj->feedback;

            $task->grade = grade_marker($task->status, $oj->subgrade, $assigngrade, $assoj->ratiope);

            if ($task->judgetime > $result->judgetime) {
                $result->judgetime = $task->judgetime;
            }

            $cases[] = $task;
        } else {
            $cases[] = null;
        }
    }

    $result->testcases = $cases;
    $result->status = onlinejudge_get_overall_status($cases);

    return $result;
}

function update_submission($submission, $newoj = false) {
    global $DB;

    $DB->update_record('assign_submission', $submission);

    if ($newoj) {
        $submission->submission = $submission->id;
        $DB->insert_record('assignment_oj_submissions', $submission);
    } else {
        $submission->id = $submission->oj_id;
        $DB->update_record('assignment_oj_submissions', $submission);
    }
}

/**
 * This function returns an
 * array of possible memory sizes in an array, translated to the
 * local language.
 *
 * @return array
 */
function get_max_memory_usages() {

    // Get max size
    $maxsize = 1024 * 1024 * get_config('local_onlinejudge', 'maxmemlimit');
    $memusage[$maxsize] = display_size($maxsize);

    $sizelist = array(1048576, 2097152, 4194304, 8388608, 16777216, 33554432, 67108864, 134217728, 268435456, 536870912);

    foreach ($sizelist as $sizebytes) {
        if ($sizebytes < $maxsize) {
            $memusage[$sizebytes] = display_size($sizebytes);
        }
    }

    ksort($memusage, SORT_NUMERIC);

    return $memusage;
}

/**
 * This function returns an
 * array of possible CPU time (in seconds) in an array
 *
 * @return array
 */
function get_max_cpu_times() {

    // Get max size
    $maxtime = get_config('local_onlinejudge', 'maxcpulimit');
    $cputime[$maxtime] = get_string('numseconds', 'moodle', $maxtime);

    $timelist = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 20, 25, 30, 40, 50, 60);

    foreach ($timelist as $timesecs) {
        if ($timesecs < $maxtime) {
            $cputime[$timesecs] = get_string('numseconds', 'moodle', $timesecs);
        }
    }

    ksort($cputime, SORT_NUMERIC);

    return $cputime;
}

/**
 * return grade
 *
 * @param int [$status]
 * @param float [$fraction]
 * @param int [$grade]
 * @param int [$ratiope]
 * @return int [grade]
 */
function grade_marker($status, $fraction, $grade, $ratiope) {
    $grades = array(ONLINEJUDGE_STATUS_PENDING => -1, ONLINEJUDGE_STATUS_JUDGING => -1, ONLINEJUDGE_STATUS_INTERNAL_ERROR => -1, ONLINEJUDGE_STATUS_WRONG_ANSWER => 0, ONLINEJUDGE_STATUS_RUNTIME_ERROR => 0, ONLINEJUDGE_STATUS_TIME_LIMIT_EXCEED => 0, ONLINEJUDGE_STATUS_MEMORY_LIMIT_EXCEED => 0, ONLINEJUDGE_STATUS_OUTPUT_LIMIT_EXCEED => 0, ONLINEJUDGE_STATUS_COMPILATION_ERROR => 0, ONLINEJUDGE_STATUS_COMPILATION_OK => 0, ONLINEJUDGE_STATUS_RESTRICTED_FUNCTIONS => 0, ONLINEJUDGE_STATUS_ABNORMAL_TERMINATION => 0, ONLINEJUDGE_STATUS_ACCEPTED => $fraction * $grade, ONLINEJUDGE_STATUS_PRESENTATION_ERROR => $fraction * $grade * $ratiope,);
    return $grades[$status];
}

/**
 * Adds specific settings to the settings block
 */
function extend_settings_navigation($assignmentnode) {
    global $PAGE, $DB, $USER, $CFG;

    if (has_capability('mod/assignment:grade', $PAGE->cm->context)) {
        $string = get_string('rejudgeall', 'assignfeedback_onlinejudge');
        $link = $CFG->wwwroot . '/mod/assignment/type/onlinejudge/rejudge.php?id=' . $this->cm->id;
        $assignmentnode->add($string, $link, navigation_node::TYPE_SETTING);

        $string = get_string('managetestcases', 'assignfeedback_onlinejudge');
        $link = $CFG->wwwroot . '/mod/assignment/type/onlinejudge/testcase.php?id=' . $this->cm->id;
        $assignmentnode->add($string, $link, navigation_node::TYPE_SETTING);
    }
}

/**
 * Fired by submission_created/updated events.
 * Function that is called by assignment created/updated event.
 * We are not calling request judge directly from the event since "request_judge" method can be called from "rejudge all" button that passes different stdClass attributes.
 * Check rejudge_all method in locallib.php for further illustration.
 * @param $event
 */
function invoke_judge($event) {
    global $DB;
    $submissionid = $event->get_record_snapshot($event->objecttable, $event->objectid)->submission;
    $submission = $DB->get_record('assign_submission', array('id' => $submissionid));
    request_judge($submission);
}

/**
 * Send judge task request to judgelib
 */

function request_judge($submission) {
    global $DB;

    $oj = $DB->get_record('assignment_oj', array('assignment' => $submission->assignment), '*', MUST_EXIST);

    $fs = get_file_storage();
    $cm = get_coursemodule_from_instance('assign', $submission->assignment);
    $context = context_module::instance($cm->id);
    $files = $fs->get_area_files($context->id, 'assignsubmission_file', ASSIGNSUBMISSION_FILE_FILEAREA, $submission->id, 'sortorder, timemodified', false);
    // Mark all old tasks as old
    $DB->set_field('assignment_oj_submissions', 'latest', 0, array('submission' => $submission->id));
    $tests = get_testcases($submission->assignment);
    foreach ($tests as $test) {
        $oj->input = $test->input;
        $oj->output = $test->output;
        $oj->var1 = $oj->clientid;
        $oj->var2 = $oj->accesstoken;
        // Submit task. Use transaction to avoid task is been judged before inserting into assignment_oj_submissions
        try {
            $transaction = $DB->start_delegated_transaction();
            $taskid = onlinejudge_submit_task($cm->id, $submission->userid, $oj->language, $files, 'assignfeedback_onlinejudge', $oj);
            $DB->insert_record('assignment_oj_submissions', array('submission' => $submission->id, 'testcase' => $test->id, 'task' => $taskid, 'latest' => 1));
            $transaction->allow_commit();
        } catch (Exception $e) {
            //TODO: reconnect db ?
            $transaction->rollback($e); // rethrows exception
        }
    }
}

/**
 * Get testcases data of current assignment.
 *
 * @return array of testcases objects. All testcase files are read into memory
 */
function get_testcases($assign_id) {
    global $CFG, $DB;

    $records = $DB->get_records('assignment_oj_testcases', array('assignment' => $assign_id), 'sortorder ASC');
    $tests = array();
    $cm = get_coursemodule_from_instance('assign', $assign_id);
    $context = context_module::instance($cm->id);
    foreach ($records as $record) {
        if ($record->usefile) {
            $fs = get_file_storage();

            if ($files = $fs->get_area_files($context->id, 'mod_assign', 'onlinejudge_input', $record->id)) {
                $file = array_pop($files);
                $record->input = $file->get_content();
            }
            if ($files = $fs->get_area_files($context->id, 'mod_assign', 'onlinejudge_output', $record->id)) {
                $file = array_pop($files);
                $record->output = $file->get_content();
            }
        }
        $tests[] = $record;
    }

    return $tests;
}

/**
 * Onlinejudge_task_judged event handler
 * Fired by method [onlinejudge_judge]
 * Update the grade and etc.
 *
 * @param event [$event]
 * @return bool
 */

function onlinejudge_task_judged($event) {
    $task = $event->get_record_snapshot($event->objecttable, $event->objectid);
    global $DB;
    $sql = 'SELECT s.*
        FROM {assign_submission} s LEFT JOIN {assignment_oj_submissions} o
        ON s.id = o.submission
        WHERE o.task = ?';
    if (!$submission = $DB->get_record_sql($sql, array($task->id))) {
        return true;    // true means the event is pr   ocessed. false will cause retry
    }

    $cm = get_coursemodule_from_instance('assign', $submission->assignment, 0, false, MUST_EXIST);
    $context = context_course::instance($cm->course);
    $ass = new assign($context, $cm, get_course($cm->course));
    $assoj = $DB->get_record('assignment_oj', array('assignment' => $submission->assignment));
    $sql = 'SELECT s.*, t.subgrade
        FROM {assignment_oj_submissions} s LEFT JOIN {assignment_oj_testcases} t
        ON s.testcase = t.id
        WHERE s.submission = ? AND s.latest = 1';
    if (!$onlinejudges = $DB->get_records_sql($sql, array($submission->id))) {
        return true;    // true means the event is processed. false will cause retry
    }

    $finalgrade = 0;
    foreach ($onlinejudges as $oj) {
        if ($task = onlinejudge_get_task($oj->task)) {
            $task->grade = grade_marker($task->status, $oj->subgrade, $ass->get_instance()->grade, $assoj->ratiope);
            if ($task->grade == -1) { // Not all testcases are judged, or judge engines internal error
                // In the case of internal error, keep old grade is reasonable
                // since most of internal errors are caused by system
                return true;
            }
            $finalgrade += $task->grade;
        }
    }
    $gradeitem = $ass->get_user_grade($submission->userid, true);
    $gradeitem->grade = $finalgrade;
    $gradeitem->timemodified = time();
    $gradeitem->grader = get_admin()->id;
    $ass->update_grade($gradeitem);
    return true;
}

