<?php  //$Id: settings.php,v 1.1.2.3 2008/01/24 20:29:36 skodak Exp $

require_once($CFG->dirroot.'/mod/assignment/lib.php');
require_once($CFG->dirroot.'/mod/assignment/type/program/assignment.class.php');


$settings->add(new admin_setting_configselect('assignment_max_cpu', get_string('maximumcpu', 'assignment_program'),
                                             get_string('configmaxcpu', 'assignment_program'), ASSIGNMENT_PROGRAM_MAX_CPU,
                                             assignment_program::get_max_cpu_times(ASSIGNMENT_PROGRAM_MAX_CPU)));

$settings->add(new admin_setting_configselect('assignment_max_mem', get_string('maximummem', 'assignment_program'),
                                             get_string('configmaxmem', 'assignment_program'), ASSIGNMENT_PROGRAM_MAX_MEM,
                                             assignment_program::get_max_memory_usages(ASSIGNMENT_PROGRAM_MAX_MEM)));

$options = array('sandbox'   => trim(get_string('sandbox', 'assignment_program')),
                 'domjudge'  => trim(get_string('domjudge', 'assignment_program')),
                 'compileonly'  => trim(get_string('compileonly', 'assignment_program')));

$settings->add(new admin_setting_configselect('assignment_judger', get_string('judger', 'assignment_program'),
                   get_string('configjudger', 'assignment_program'), ASSIGNMENT_PROGRAM_DEFAULT_JUDGER, $options));

$settings->add(new admin_setting_configtext('assignment_judgehost', get_string('judgehost', 'assignment_program'),
                                            get_string('configjudgehost', 'assignment_program'), ''));

?>
