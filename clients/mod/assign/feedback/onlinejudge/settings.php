<?php

if (!defined('ASSIGNMENT_ONLINEJUDGE_MAX_CPU') && !defined('ASSIGNMENT_ONLINEJUDGE_MAX_MEM')) {
    define('ASSIGNMENT_ONLINEJUDGE_MAX_CPU', get_config('local_onlinejudge', 'maxcpulimit'));
    define('ASSIGNMENT_ONLINEJUDGE_MAX_MEM', 1024 * 1024 * get_config('local_onlinejudge', 'maxmemlimit'));
}

require_once($CFG->dirroot . '/mod/assign/feedback/onlinejudge/lib.php');


$settings->add(new admin_setting_heading('onlinejudge_help', get_string('user_help_heading', 'assignfeedback_onlinejudge'), get_string('user_help', 'assignfeedback_onlinejudge')));
$settings->add(new admin_setting_configselect('assignment_oj_max_cpu', get_string('maxcpuusage', 'assignfeedback_onlinejudge'), get_string('configmaxcpu', 'assignfeedback_onlinejudge'), ASSIGNMENT_ONLINEJUDGE_MAX_CPU, get_max_cpu_times()));

$settings->add(new admin_setting_configselect('assignment_oj_max_mem', get_string('maxmemusage', 'assignfeedback_onlinejudge'), get_string('configmaxmem', 'assignfeedback_onlinejudge'), ASSIGNMENT_ONLINEJUDGE_MAX_MEM, get_max_memory_usages()));