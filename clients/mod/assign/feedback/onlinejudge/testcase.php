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
 * Testcases management
 *
 * @package   local_onlinejudge
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @developer Mina Mofreh
 * @co-developer Andrew Nagyeb
 */

require_once(dirname(__FILE__) . '/../../../../config.php');
require_once($CFG->dirroot . "/mod/assign/lib.php");
require_once('testcase_form.php');
require_once($CFG->dirroot . "/mod/assign/locallib.php");

$id = optional_param('id', 0, PARAM_INT);  // Course Module ID
$a = optional_param('a', 0, PARAM_INT);   // Assignment ID

$url = new moodle_url('/mod/assign/feedback/onlinejudge/testcase.php');

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

$context = context_module::instance($cm->id);
require_capability('mod/assign:grade', $context);

$testform = new testcase_form($DB->count_records('assignment_oj_testcases', array('assignment' => $assignment->id)));

if ($testform->is_cancelled()) {

    redirect($CFG->wwwroot . '/mod/assign/view.php?id=' . $id);

} else if ($fromform = $testform->get_data()) {

    for ($i = 0; $i < $fromform->boundary_repeats; $i++) {
        if (emptycase($fromform, $i)) {
            if ($fromform->caseid[$i] != -1) {
                $DB->delete_records('assignment_oj_testcases', array('id' => $fromform->caseid[$i]));
                $fs = get_file_storage();
                $fs->delete_area_files($context->id, 'mod_assignment', 'onlinejudge_input', $fromform->caseid[$i]);
                $fs->delete_area_files($context->id, 'mod_assignment', 'onlinejudge_output', $fromform->caseid[$i]);
            }
            continue;
        }
        $testcase = new \stdClass;
        if (isset($fromform->usefile[$i])) {
            $testcase->usefile = true;
            // Keep file as is
            $testcase->inputfile = $fromform->inputfile[$i];
            $testcase->outputfile = $fromform->outputfile[$i];
        } else {
            $testcase->usefile = false;
            // Translate textbox inputs to Unix text format
            $testcase->input = crlf2lf($fromform->input[$i]);
            $testcase->output = crlf2lf($fromform->output[$i]);
        }

        $testcase->feedback = $fromform->feedback[$i];
        $testcase->subgrade = $fromform->subgrade[$i];
        $testcase->assignment = $assignment->id;
        $testcase->id = $fromform->caseid[$i];
        $testcase->sortorder = $i;

        if ($testcase->id != -1) {
            $DB->update_record('assignment_oj_testcases', $testcase);
        } else {
            $testcase->id = $DB->insert_record('assignment_oj_testcases', $testcase);
        }

        if ($testcase->usefile) {
            file_save_draft_area_files($testcase->inputfile, $context->id, 'mod_assign', 'onlinejudge_input', $testcase->id);
            file_save_draft_area_files($testcase->outputfile, $context->id, 'mod_assign', 'onlinejudge_output', $testcase->id);
        }

        unset($testcase);
    }

    redirect($CFG->wwwroot . '/mod/assign/view.php?id=' . $id);

} else {

    echo $OUTPUT->header();
    $testcases = $DB->get_records('assignment_oj_testcases', array('assignment' => $assignment->id), 'sortorder ASC');

    $toform = array();
    if ($testcases) {
        $i = 0;
        foreach ($testcases as $tstObj => $tstValue) {
            $toform["input[$i]"] = $tstValue->input;
            $toform["output[$i]"] = $tstValue->output;
            $toform["feedback[$i]"] = $tstValue->feedback;
            $toform["subgrade[$i]"] = $tstValue->subgrade;
            $toform["usefile[$i]"] = $tstValue->usefile;
            $toform["caseid[$i]"] = $tstValue->id;

            file_prepare_draft_area($toform["inputfile[$i]"], $context->id, 'mod_assign', 'onlinejudge_input', $tstValue->id, array('subdirs' => 0, 'maxfiles' => 1));
            file_prepare_draft_area($toform["outputfile[$i]"], $context->id, 'mod_assign', 'onlinejudge_output', $tstValue->id, array('subdirs' => 0, 'maxfiles' => 1));

            $i++;
        }
    }

    $testform->set_data($toform);
    $testform->display();
    echo $OUTPUT->footer();

}

function emptycase(&$form, $i)
{
    if ($form->subgrade[$i] != 0.0)
        return false;

    if (isset($form->usefile[$i]))
        return empty($form->inputfile[$i]) && empty($form->outputfile[$i]);
    else
        return empty($form->input[$i]) && empty($form->output[$i]);
}

/* Translate CR+LF (\r\n) to LF (\n) */
function crlf2lf(&$text)
{
    return strtr($text, array("\r\n" => "\n", "\n\r" => "\n"));
}