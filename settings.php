<?php  
require_once(dirname(dirname(__FILE__)) . '/../config.php');
require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/local/onlinejudge2/judgelib.php');

$settings->add(new admin_setting_configselect('onlinejudge2_max_cpu', get_string('maxcpuusage', 'local_onlinejudge2'),
                                             get_string('configmaxcpu', 'local_onlinejudge2'), ONLINEJUDGE2_MAX_CPU,
                                             get_max_cpu_times(ONLINEJUDGE2_MAX_CPU)));

$settings->add(new admin_setting_configselect('onlinejudge2_max_mem', get_string('maxmemusage', 'local_onlinejudge2'),
                                             get_string('configmaxmem', 'local_onlinejudge2'), ONLINEJUDGE2_MAX_MEM,
                                             get_max_memory_usages(ONLINEJUDGE2_MAX_MEM)));

?>