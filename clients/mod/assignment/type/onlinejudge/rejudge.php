<?php

// Threshhold to prevent further rejudge-all requests
define('PREVENTION_THRESHHOLD', 500);

require_once(dirname(__FILE__).'/../../../../config.php');
require_once("$CFG->dirroot/mod/assignment/lib.php");
require_once("$CFG->dirroot/mod/assignment/type/onlinejudge/assignment.class.php");

$id = optional_param('id', 0, PARAM_INT);  // Course Module ID
$a  = optional_param('a', 0, PARAM_INT);   // Assignment ID
$confirm = optional_param('confirm', 0, PARAM_INT);   // Force to rejudge

$url = new moodle_url('/mod/assignment/type/onlinejudge/rejudge.php');
if ($id) {
    if (! $cm = get_coursemodule_from_id('assignment', $id)) {
        print_error('invalidcoursemodule');
    }

    if (! $assignment = $DB->get_record("assignment", array("id"=>$cm->instance))) {
        print_error('invalidid', 'assignment');
    }

    if (! $course = $DB->get_record("course", array("id"=>$assignment->course))) {
        print_error('coursemisconf', 'assignment');
    }
    $url->param('id', $id);
} else {
    if (!$assignment = $DB->get_record("assignment", array("id"=>$a))) {
        print_error('invalidid', 'assignment');
    }
    if (! $course = $DB->get_record("course", array("id"=>$assignment->course))) {
        print_error('coursemisconf', 'assignment');
    }
    if (! $cm = get_coursemodule_from_instance("assignment", $assignment->id, $course->id)) {
        print_error('invalidcoursemodule');
    }
    $url->param('a', $a);
}

$PAGE->set_url($url);
require_login($course, true, $cm);

require_capability('mod/assignment:grade', get_context_instance(CONTEXT_MODULE, $cm->id));

$assignmentinstance = new assignment_onlinejudge($cm->id, $assignment, $cm, $course);

$pending = $DB->count_records('onlinejudge_tasks', array('status' => ONLINEJUDGE_STATUS_PENDING));
if ($pending > PREVENTION_THRESHHOLD) {
    // Prevent rejudge all requests if judged is busy
    $assignmentinstance->view_header();
    echo $OUTPUT->heading(get_string('rejudgeall', 'assignment_onlinejudge'));
    echo $OUTPUT->box(get_string('rejudgelater', 'assignment_onlinejudge'));
} else if ( $confirm == 1 && confirm_sesskey()){
    $assignmentinstance->rejudge_all();
    redirect($CFG->wwwroot.'/mod/assignment/submissions.php?id='.$id, get_string('rejudgeallrequestsent', 'assignment_onlinejudge'), 10);
} else {
    $optionsno = array ('id'=>$id);
    $optionsyes = array ('id'=>$id, 'confirm'=>1, 'sesskey'=>sesskey());
    $assignmentinstance->view_header();
    echo $OUTPUT->heading(get_string('rejudgeall', 'assignment_onlinejudge'));
    echo $OUTPUT->confirm(get_string('rejudgeallnotice', 'assignment_onlinejudge'), new moodle_url('rejudge.php', $optionsyes),new moodle_url( '../../view.php', $optionsno));
}

$assignmentinstance->view_footer();

