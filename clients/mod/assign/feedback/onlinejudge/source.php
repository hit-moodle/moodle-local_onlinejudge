<?php
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
    
    global $CFG, $USER;
    
    $id = optional_param('id', 0, PARAM_INT);  // Course Module ID
    $e  = optional_param('a', 0, PARAM_INT);   // Epaile ID
    $userid = optional_param('userid', 0, PARAM_INT);   // User ID
    $file = required_param('file', PARAM_CLEANHTML); // File to show
    
    if ($id) {
        if (!$cm = get_coursemodule_from_id('assignment', $id)) {
            error(get_string('error_invalidcoursemodule','assignment'));
        }

        if (!$assignment = get_record('assignment', 'id', $cm->instance)) {
            error(get_string('error_invalidepaileid','assignment'));
        }

        if (!$course = get_record('course', 'id', $assignment->course)) {
            error(get_string('error_misconfiguredcourse','assignment'));
        }
    } else {
        if (!$assignment = get_record('assignment', 'id', $e)) {
            error(get_string('error_incorrectmodule','assignment'));
        }
        if (!$course = get_record('course', 'id', $assignment->course)) {
            error(get_string('error_misconfiguredcourse','assignment'));
        }
        if (!$cm = get_coursemodule_from_instance('assignment', $assignment->id, $course->id)) {
            error(get_string('error_invalidcoursemodule','assignment'));
        }
    }


    require_login($course->id, false, $cm);

    if(!$userid)
        $userid = $USER->id;

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    if ($userid != $USER->id and !has_capability('mod/assignment:grade', $context))
        print_error('denytoreadfile', 'assignment_onlinejudge');

    // Load up the required assignment code
    require('assignment.class.php');
    $assignmentinstance = new assignment_onlinejudge($cm->id, $assignment, $cm, $course);

    $filearea = $assignmentinstance->file_area_name($userid);

    if ($basedir = $assignmentinstance->file_area($userid)) {
        $fpath = "$CFG->dataroot/$filearea/$file";
    }

    $ffurl = get_file_url("$filearea/$file");

    if($gestor = fopen($fpath,'r')) {
        $code = fread($gestor, filesize($fpath));
        if ($charset = mb_detect_encoding($code, 'UTF-8, GBK')) {
            $code = iconv($charset, 'utf-8', $code);
        }
        $code = htmlspecialchars($code);
        fclose($gestor);
    } else {
        error(get_string('filereaderror','assignment_onlinejudge'));   
    }

    $lang = strtok($assignmentinstance->onlinejudge->language, '_');
    
    include('source.html');
?>
