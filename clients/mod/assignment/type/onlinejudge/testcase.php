<?php

require_once('../../../../config.php');
require_once("$CFG->dirroot/mod/assignment/lib.php");
require_once('testcase_form.php');

$id = optional_param('id', 0, PARAM_INT);  // Course Module ID
$a  = optional_param('a', 0, PARAM_INT);   // Assignment ID

$url = new moodle_url('/mod/assignment/type/onlinejudge/testcase.php');
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

$testform = new testcase_form($DB->count_records('assignment_oj_testcases', array('assignment' => $assignment->id)));

if ($testform->is_cancelled()){

	redirect($CFG->wwwroot.'/mod/assignment/view.php?id='.$id);

} else if ($fromform = $testform->get_data()){

	$DB->delete_records('assignment_oj_testcases', array('assignment' => $assignment->id));

	for ($i = 0; $i < $fromform->boundary_repeats; $i++) {
        if (emptycase($fromform, $i))
            continue;

        if (isset($fromform->usefile[$i])) {
            $testcase->usefile = $fromform->usefile[$i];
        }
        if (isset($fromform->input[$i]) && isset($fromform->output[$i])) {
			$testcase->input = $fromform->input[$i];
			$testcase->output = $fromform->output[$i];
        }
        if (isset($fromform->inputfile[$i]) && isset($fromform->outputfile[$i])) {
			$testcase->inputfile = $fromform->inputfile[$i];
			$testcase->outputfile = $fromform->outputfile[$i];
        }

        $testcase->feedback = $fromform->feedback[$i];
        $testcase->subgrade = $fromform->subgrade[$i];
        $testcase->assignment = $assignment->id;
        $DB->insert_record('assignment_oj_testcases', $testcase);
        unset($testcase);
	}

	redirect($CFG->wwwroot.'/mod/assignment/view.php?id='.$id);    

} else {

    $assignmentinstance = new assignment_onlinejudge($cm->id, $assignment, $cm, $course);
    $assignmentinstance->view_header();

    $testcases = $DB->get_records('assignment_oj_testcases', array('assignment' => $assignment->id), 'id ASC');

    $toform = array();
    if ($testcases) {
        $i = 0;
        foreach ($testcases as $tstObj => $tstValue) {
            $toform["input[$i]"] = $tstValue->input;
            $toform["output[$i]"] = $tstValue->output;
            $toform["feedback[$i]"] = $tstValue->feedback;
            $toform["subgrade[$i]"] = $tstValue->subgrade;
            $toform["usefile[$i]"] = $tstValue->usefile;
            $toform["inputfile[$i]"] = $tstValue->inputfile;
            $toform["outputfile[$i]"] = $tstValue->outputfile;
            $i++;
        }
    }

	$testform->set_data($toform);
	$testform->display();

	$assignmentinstance->view_footer();
}

function emptycase(&$form, $i) {
    if ($form->subgrade[$i] != 0.0)
        return false;

    if (isset($form->usefile[$i]))
        return empty($form->inputfile[$i]) && empty($form->outputfile[$i]);
    else
        return empty($form->input[$i]) && empty($form->output[$i]);
}
?>
