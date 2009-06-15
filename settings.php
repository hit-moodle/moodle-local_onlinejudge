<?php  //$Id: settings.php,v 1.1.2.3 2008/01/24 20:29:36 skodak Exp $

require_once($CFG->dirroot.'/mod/assignment/lib.php');
require_once($CFG->dirroot.'/mod/assignment/type/onlinejudge/assignment.class.php');


$settings->add(new admin_setting_configselect('assignment_oj_max_cpu', get_string('maximumcpu', 'assignment_onlinejudge'),
                                             get_string('configmaxcpu', 'assignment_onlinejudge'), ASSIGNMENT_ONLINEJUDGE_MAX_CPU,
                                             assignment_onlinejudge::get_max_cpu_times(ASSIGNMENT_ONLINEJUDGE_MAX_CPU)));

$settings->add(new admin_setting_configselect('assignment_oj_max_mem', get_string('maximummem', 'assignment_onlinejudge'),
                                             get_string('configmaxmem', 'assignment_onlinejudge'), ASSIGNMENT_ONLINEJUDGE_MAX_MEM,
                                             assignment_onlinejudge::get_max_memory_usages(ASSIGNMENT_ONLINEJUDGE_MAX_MEM)));

$options = array('sandbox'   => trim(get_string('sandbox', 'assignment_onlinejudge')),
                 'compileonly'  => trim(get_string('compileonly', 'assignment_onlinejudge')));

$settings->add(new admin_setting_configselect('assignment_oj_judger', get_string('judger', 'assignment_onlinejudge'),
                   get_string('configjudger', 'assignment_onlinejudge'), ASSIGNMENT_ONLINEJUDGE_DEFAULT_JUDGER, $options));

?>
