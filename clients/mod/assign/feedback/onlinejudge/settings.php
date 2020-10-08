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

if (!defined('ASSIGNMENT_ONLINEJUDGE_MAX_CPU') && !defined('ASSIGNMENT_ONLINEJUDGE_MAX_MEM')) {
    define('ASSIGNMENT_ONLINEJUDGE_MAX_CPU', get_config('local_onlinejudge', 'maxcpulimit'));
    define('ASSIGNMENT_ONLINEJUDGE_MAX_MEM', 1024 * 1024 * get_config('local_onlinejudge', 'maxmemlimit'));
}

require_once($CFG->dirroot . '/mod/assign/feedback/onlinejudge/lib.php');


$settings->add(new admin_setting_heading('onlinejudge_help', get_string('user_help_heading', 'assignfeedback_onlinejudge'), get_string('user_help', 'assignfeedback_onlinejudge')));
$settings->add(new admin_setting_configselect('assignment_oj_max_cpu', get_string('maxcpuusage', 'assignfeedback_onlinejudge'), get_string('configmaxcpu', 'assignfeedback_onlinejudge'), ASSIGNMENT_ONLINEJUDGE_MAX_CPU, get_max_cpu_times()));

$settings->add(new admin_setting_configselect('assignment_oj_max_mem', get_string('maxmemusage', 'assignfeedback_onlinejudge'), get_string('configmaxmem', 'assignfeedback_onlinejudge'), ASSIGNMENT_ONLINEJUDGE_MAX_MEM, get_max_memory_usages()));