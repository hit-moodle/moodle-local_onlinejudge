<?php
///////////////////////////////////////////////////////////////////////////
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
//                       Online Judge Moodle 3.4+                        //
//                 Copyright (C) 2018 onwards Andrew Nagyeb              //
// This program is based on the work of Sun Zhigang (C) 2009 Moodle 2.6. //
//                                                                       //
//    Modifications were made in order to upgrade the program so that    //
//                     it is compatible to Moodle 3.4+.                  //
//                       Original License Follows                        //
///////////////////////////////////////////////////////////////////////////

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

// Threshhold to prevent further rejudge-all requests
define('PREVENTION_THRESHHOLD', 500);

require_once(dirname(__FILE__) . '/../../../../config.php');
require_once($CFG->dirroot . "/mod/assign/locallib.php");
require_once("$CFG->dirroot/mod/assign/feedback/onlinejudge/lib.php");
require_once("$CFG->dirroot/mod/assign/feedback/onlinejudge/locallib.php");

$id = optional_param('id', 0, PARAM_INT);  // Course Module ID
$a = optional_param('a', 0, PARAM_INT);   // Assignment ID
$confirm = optional_param('confirm', 0, PARAM_INT);   // Force to rejudge

$url = new moodle_url('/mod/assign/feedback/onlinejudge/rejudge.php');
global $DB, $PAGE, $OUTPUT;

if ($id) {
    if (!$cm = get_coursemodule_from_id('assign', $id)) {
        print_error('invalidcoursemodule');
    }

    if (!$assignment = $DB->get_record("assign", array("id" => $cm->instance))) {
        print_error('invalidid', 'assign');
    }

    if (!$course = $DB->get_record("course", array("id" => $assignment->course))) {
        print_error('coursemisconf', 'assign');
    }
    $url->param('id', $id);
} else {
    if (!$assignment = $DB->get_record("assign", array("id" => $a))) {
        print_error('invalidid', 'assign');
    }
    if (!$course = $DB->get_record("course", array("id" => $assignment->course))) {
        print_error('coursemisconf', 'assign');
    }
    if (!$cm = get_coursemodule_from_instance("assign", $assignment->id, $course->id)) {
        print_error('invalidcoursemodule');
    }
    $url->param('a', $a);
}

$PAGE->set_url($url);
require_login($course, true, $cm);

$context = context_course::instance($cm->course);
require_capability('mod/assign:grade', $context);
$assign = new assign($context, $cm, $course);
$assignmentinstance = new assign_feedback_onlinejudge($assign, 'feedback');


$pending = $DB->count_records('onlinejudge_tasks', array('status' => ONLINEJUDGE_STATUS_PENDING));
if ($pending > PREVENTION_THRESHHOLD) {
    // Prevent rejudge all requests if judged is busy
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('rejudgeall', 'assignfeedback_onlinejudge'));
    echo $OUTPUT->box(get_string('rejudgelater', 'assignfeedback_onlinejudge'));
    echo $OUTPUT->footer();

} else if ($confirm == 1 && confirm_sesskey()) {
    $rejudge_state = $assignmentinstance->rejudge_all();

    if ($rejudge_state) {
        $urlparams = array('id' => $cm->id, 'action' => 'grading');
        $url = new moodle_url('/mod/assign/view.php', $urlparams);
        redirect($url, get_string('rejudgeallrequestsent', 'assignfeedback_onlinejudge'), 10, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        $urlparams = array('id' => $cm->id,);
        $url = new moodle_url('/mod/assign/view.php', $urlparams);
        redirect($url, get_string('rejudgeallrequestfailed', 'assignfeedback_onlinejudge'), 10, \core\output\notification::NOTIFY_ERROR);
    }
} else {
    $optionsno = array('id' => $id);
    $optionsyes = array('id' => $id, 'confirm' => 1, 'sesskey' => sesskey());
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('rejudgeall', 'assignfeedback_onlinejudge'));
    echo $OUTPUT->confirm(get_string('rejudgeallnotice', 'assignfeedback_onlinejudge'), new moodle_url('rejudge.php', $optionsyes), new moodle_url('../../view.php', $optionsno));
    echo $OUTPUT->footer();

}