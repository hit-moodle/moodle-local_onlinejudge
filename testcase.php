<?php

require_once('../../../../config.php');
require_once("$CFG->dirroot/mod/assignment/lib.php");
require_once('testcase_form.php');

$id = optional_param('id', 0, PARAM_INT);  // Course Module ID
$a  = optional_param('a', 0, PARAM_INT);   // Assignment ID


if ($id) {
	if (! $cm = get_coursemodule_from_id('assignment', $id)) {
		error("Course Module ID was incorrect");
	}

	if (! $assignment = get_record("assignment", "id", $cm->instance)) {
		error("assignment ID was incorrect");
	}

	if (! $course = get_record("course", "id", $assignment->course)) {
		error("Course is misconfigured");
	}
} else {
	if (!$assignment = get_record("assignment", "id", $a)) {
		error("Course module is incorrect");
	}
	if (! $course = get_record("course", "id", $assignment->course)) {
		error("Course is misconfigured");
	}
	if (! $cm = get_coursemodule_from_instance("assignment", $assignment->id, $course->id)) {
		error("Course Module ID was incorrect");
	}
}

require_login($course->id, false, $cm);

require_capability('mod/assignment:grade', get_context_instance(CONTEXT_MODULE, $cm->id));

$testform = new testcase_form(count_records('assignment_oj_tests', 'assignment', $assignment->id));

if ($testform->is_cancelled()){

	redirect($CFG->wwwroot.'/mod/assignment/view.php?id='.$id);

} else if ($fromform = $testform->get_data()){

	delete_records('assignment_oj_tests', 'assignment', $assignment->id);

	for ($i = 0; $i < $fromform->boundary_repeats; $i++) {
        if ($fromform->subgrade[$i] == 0.0)  //Ignore no grade testcases
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
        insert_record('assignment_oj_tests', $testcase);
        unset($testcase);
	}

	redirect($CFG->wwwroot.'/mod/assignment/view.php?id='.$id);    

} else {

	$navigation = build_navigation(get_string('testcases','assignment_onlinejudge'), $cm);
	$title = get_string('modulename', 'assignment').': '.$cm->name.': '.get_string('testcases','assignment_onlinejudge');
	print_header_simple($title, '', $navigation, $testform->focus(), "", false);

    $tests = get_records('assignment_oj_tests', 'assignment', $assignment->id, 'id ASC');

    $toform = array();
    if ($tests) {
        $i = 0;
        foreach ($tests as $tstObj => $tstValue) {
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
	print_footer($course);

}

?>
