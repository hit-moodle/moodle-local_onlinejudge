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
 * Displays details of one task
 *
 * @package   local_onlinejudge
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/local/onlinejudge/judgelib.php');

require_login(SITEID, false);

$taskid = required_param('task', PARAM_INT);
$ajax = optional_param('ajax', 0, PARAM_BOOL);

$task = onlinejudge_get_task($taskid);
if (empty($task)) {
    print_error('invalidtaskid', 'local_onlinejudge', '', $taskid);
}

$context = context_module::instance($task->cmid);

$PAGE->set_url('/mod/assign/feedeback/onlinejudge/details.php');
$PAGE->set_pagelayout('popup');
$PAGE->set_context($context);
$PAGE->set_title(get_string('details', 'local_onlinejudge'));
$PAGE->set_heading(get_string('details', 'local_onlinejudge'));
$PAGE->set_course($COURSE);
$PAGE->navbar->add(get_string('details', 'local_onlinejudge'));

if ($ajax) {
    @header('Content-Type: text/plain; charset=utf-8');
} else {
    echo $OUTPUT->header();
}

if ($task->userid != $USER->id) {
    require_capability('local/onlinejudge:viewsensitive', $context);
}

// fields of table tasks which should be shown to teachers
$sensitivefields = array('stdout', 'stderr', 'infoteacher');
// fields of table tasks which should be shown to students
$normalfields = array('compileroutput', 'memusage', 'cpuusage', 'infostudent');

$task = (array)$task; // Easier to enum

$table = new html_table();
$table->attributes['class'] = 'generaltable';

foreach ($task as $key => $content) {
    if ((!in_array($key, $normalfields) and !in_array($key, $sensitivefields)) or (in_array($key, $sensitivefields) and !has_capability('local/onlinejudge:viewsensitive', $context))) {
        continue;
    }

    $titlecell = new html_table_cell();
    $contentcell = new html_table_cell();

    $titlecell->text = get_string($key, 'local_onlinejudge');
    if (in_array($key, $sensitivefields)) {
        $titlecell->text .= '*';
    }
    // empty is not used as it treats '0' as empty.
    if (!isset($content) and is_null($content)) {
        $content = get_string('notavailable');
    } else {
        $formatter = 'format_' . $key;
        if (function_exists($formatter)) {
            $content = $formatter($content);
        }
    }

    $contentcell->text = $content;

    $row = new html_table_row(array($titlecell, $contentcell));
    $table->data[] = $row;
}

echo html_writer::table($table);

if (has_capability('local/onlinejudge:viewsensitive', $context)) {
    print_string('notesensitive', 'local_onlinejudge');
}

if (!$ajax) {
    echo $OUTPUT->footer();
}

function format_compileroutput($string) {
    return '<pre>' . htmlspecialchars($string) . '</pre>';
}

function format_cpuusage($string) {
    return $string . ' ' . get_string('sec');
}

function format_memusage($string) {
    return display_size($string);
}

function format_stdout($string) {
    return format_compileroutput($string);
}

function format_stderr($string) {
    return format_compileroutput($string);
}