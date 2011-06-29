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
 * Displays details of one task
 * 
 * @package   local_onlinejudge
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->dirroot.'/local/onlinejudge/judgelib.php');

require_login(SITEID, false);

$task = required_param('task', PARAM_INT);
$ajax = optional_param('ajax', 0, PARAM_BOOL);

$PAGE->set_url('/mod/assignment/type/onlinejudge/details.php');
$PAGE->set_pagelayout('popup');
$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));

if ($ajax) {
    @header('Content-Type: text/plain; charset=utf-8');
} else {
    echo $OUTPUT->header();
}

$task = onlinejudge_get_task($task);

if (empty($task)) {
    print_error('invaliddetailsparams', 'assignment_onlinejudge');
}

if ($task->userid != $USER->id) {
    require_capability('mod/assignment:grade', get_system_context());
}

$task = (array)$task; // Easier to enum

$table = new html_table();
$table->attributes['class'] = 'generaltable';

// details to students first
foreach ($task as $key => $content) {
    if ($key != 'compileroutput' and $key != 'memusage' and $key != 'cpuusage' and $key != 'infostudent') {
        continue;
    }

    $titlecell   = new html_table_cell();
    $contentcell = new html_table_cell();

    $titlecell->text = get_string($key, 'local_onlinejudge');

    $formatter = 'format_'.$key;
    if (function_exists($formatter)) {
        $content = $formatter($content);
        if (!$content)
            continue;
    }
    $contentcell->text = $content;

    $row = new html_table_row(array($titlecell, $contentcell));
    $table->data[] = $row;
}

echo html_writer::table($table);


//details to teachers
if (has_capability('mod/assignment:grade', get_system_context())) {
    echo $OUTPUT->heading(get_string('teacheronly', 'assignment_onlinejudge'), 3);

    $table = new html_table();
    $table->attributes['class'] = 'generaltable';

    foreach ($task as $key => $content) {
        if ($key != 'stdout' and $key != 'stderr' and $key != 'infoteacher') {
            continue;
        }

        $titlecell   = new html_table_cell();
        $contentcell = new html_table_cell();

        $titlecell->text = get_string($key, 'local_onlinejudge');

        $formatter = 'format_'.$key;
        if (function_exists($formatter)) {
            $content = $formatter($content);
            if (!$content)
                continue;
        }
        $contentcell->text = $content;

        $row = new html_table_row(array($titlecell, $contentcell));
        $table->data[] = $row;
    }

    echo html_writer::table($table);
}

if (!$ajax) {
    echo $OUTPUT->footer();
}

function format_compileroutput($string) {
    if (empty($string)) {
        return false;
    }
    return nl2br($string);
}

function format_infostudent($string) {
    if (empty($string)) {
        return false;
    }
    return $string;
}

function format_cpuusage($string) {
    if (strlen($string) == 0) {
        return false;
    }
    return $string.' '.get_string('sec');
}

function format_memusage($string) {
    if (strlen($string) == 0) {
        return false;
    }
    return display_size($string);
}

function format_stdout($string) {
    return format_compileroutput($string);
}

function format_stderr($string) {
    return format_compileroutput($string);
}

function format_infoteacher($string) {
    return format_infostudent($string);
}

