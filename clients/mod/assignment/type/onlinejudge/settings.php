<?php  

require_once($CFG->dirroot.'/mod/assignment/lib.php');
require_once($CFG->dirroot.'/mod/assignment/type/onlinejudge/assignment.class.php');


$settings->add(new admin_setting_configselect('assignment_oj_max_cpu', get_string('maxcpuusage', 'assignment_onlinejudge'),
                                             get_string('configmaxcpu', 'assignment_onlinejudge'), ASSIGNMENT_ONLINEJUDGE_MAX_CPU,
                                             assignment_onlinejudge::get_max_cpu_times(ASSIGNMENT_ONLINEJUDGE_MAX_CPU)));

$settings->add(new admin_setting_configselect('assignment_oj_max_mem', get_string('maxmemusage', 'assignment_onlinejudge'),
                                             get_string('configmaxmem', 'assignment_onlinejudge'), ASSIGNMENT_ONLINEJUDGE_MAX_MEM,
                                             assignment_onlinejudge::get_max_memory_usages(ASSIGNMENT_ONLINEJUDGE_MAX_MEM)));

?>
