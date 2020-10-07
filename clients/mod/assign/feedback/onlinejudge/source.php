<?php

///////////////////////////////////////////////////////////////////////////
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
//                       Online Judge Moodle 3.4+                        //
//                 Copyright (C) 2018 onwards Andrew Nagyeb              //
//       This program is based on the work of Arkaitz Garro (C) 2009     //
//                             Moodle 2.6.                               //
//    Modifications were made in order to upgrade the program so that    //
//                     it is compatible to Moodle 3.4+.                  //
//                       Original License Follows                        //
///////////////////////////////////////////////////////////////////////////

/* source.php (v1.0 - 2007/06/26)
 * ********************************************************************* *
 * by Arkaitz Garro, July 2007                                           *
 * Copyright (c) 2007 Arkaitz Garro. All Rights Reserved.                *
 * This code is based in actual assigment module.                        *
 *                                                                       *
 * This code is free software; you can redistribute it and/or modify     *
 * it under the terms of the GNU General Public License as published by  *
 * the Free Software Foundation; either version 2 of the License, or     *
 * (at your option) any later version.                                   *
 *                                                                       *
 * This program is distributed in the hope that it will be useful,       *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of        *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         *
 * GNU General Public License for more details:                          *
 *                                                                       *
 *          http://www.gnu.org/copyleft/gpl.html                         *
 * ********************************************************************* *
 * Use of SyntaxHighlighter                                              *
 * Free syntax highlighter written in Javascript                         *
 * http://code.google.com/p/syntaxhighlighter/                           *
 * ********************************************************************* *
 */


require_once('../../../../config.php');
require_once('../../lib.php');
require_once('../../../../lib/filelib.php');
require_once($CFG->dirroot . "/mod/assign/locallib.php");
global $CFG, $USER;

$id = optional_param('id', 0, PARAM_INT);  // Course Module ID
$a = optional_param('a', 0, PARAM_INT);   // Assignment ID
$userid = optional_param('userid', 0, PARAM_INT);   // User ID
$submissionid = required_param('submissionid', PARAM_INT); // File to show

global $DB;
if ($id) {
    if (!$cm = get_coursemodule_from_id('assign', $id)) {
        print_error(get_string('error_invalidcoursemodule', 'assign'));
    }

    if (!$assignment = $DB->get_record('assign', array('id' => $cm->instance))) {
        print_error(get_string('error_invalidepaileid', 'assign'));
    }

    if (!$course = $DB->get_record('course', array('id' => $assignment->course))) {
        print_error(get_string('error_misconfiguredcourse', 'assign'));
    }
} else {
    if (!$assignment = $DB->get_record('assign', array('id' => $a))) {
        print_error(get_string('error_incorrectmodule', 'assign'));
    }
    if (!$course = $DB->get_record('course', array('id' => $assignment->course))) {
        print_error(get_string('error_misconfiguredcourse', 'assign'));
    }
    if (!$cm = get_coursemodule_from_instance('assign', $assignment->id, $course->id)) {
        print_error(get_string('error_invalidcoursemodule', 'assign'));
    }
}


require_login($course->id, false, $cm);

if (!$userid) $userid = $USER->id;

$context = context_course::instance($cm->course);
if ($userid != $USER->id and !has_capability('mod/assign:grade', $context)) print_error('denytoreadfile', 'assignfeedback_onlinejudge');

// Load up the required assignment code
require('locallib.php');

$assignmentinstance = new assign($context, $cm, $course);

$filearea = ASSIGNSUBMISSION_FILE_FILEAREA;
$fs = get_file_storage();
$context = context_module::instance($cm->id);
$files = $fs->get_area_files($context->id, 'assignsubmission_file', ASSIGNSUBMISSION_FILE_FILEAREA, $submissionid, 'sortorder, timemodified', false);
$onlinejudge = $DB->get_record('assignment_oj', array('assignment' => $a));

/** checking if after tokenize whether the language is sandbox or sphere-engine
 * [ for sphere-engine $lang will be the compiler's id]
 *  which is not valid for the syntax highlighter so we have to reformat it.
 */
if (onlinejudge_judge_name($onlinejudge->language) == 'sphere_engine') {
    $lang = strtok($onlinejudge->language, '-');
    $lang = judge_sphere_engine::get_language_name($lang);
} else
    $lang = strtok($onlinejudge->language, '-');

$lines = array();
foreach ($files as $file) {
    if (!is_null($files)) {
        if (!is_null($file)) {
            $code = $file->get_content();
            if ($charset = mb_detect_encoding($code, 'UTF-8, GBK')) {
                $code = iconv($charset, 'utf-8', $code);
            }
            $code = htmlspecialchars($code);
            $attributes = array('class' => "brush: $lang");
            $line = html_writer::tag('pre', $code, $attributes);
        }
    }
    $lines[] = $line;
}
if (!empty($lines)) {
    $item = implode($lines, '<hr>');
}
include('source.html');