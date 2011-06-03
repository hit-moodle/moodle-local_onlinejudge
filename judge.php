<?php
require_once($CFG->dirroot."/local/onlinejudge2/judge/sandbox/sandbox.php");
require_once($CFG->dirroot."/local/onlinejudge2/judge/ideone/ideone.php");
//本文件主要用于模拟运行


require_once("../../config.php");
require_once("judgelib.php");
global $CFG, $DB;

$jf = new judge_factory();
$jf->get_judge_methods();


//sub是封装的数据包
$task = array();
$task['id'] = 1;
$task['cpulimit'] = 1;
$task['memlimit'] = 1048576;
$task['judgeName'] = 301;
$task['source'] = '#include "stdio.h" 
               int main()
               {
                   int a, b;
                   scanf("%d,%d",&a,&b);
                   printf("%d",a+b);
                   return 0;
               }
';
$task['input'] = '2,3';
$task['output'] = '5';
$task['usefile'] = 0;
$task['inputfile'] = 0;
$task['outputfile'] = 0; 

$judge_obj = $jf->get_judge($task['judgeName']);

$result = $judge_obj->judge($task); //获取


//输出结果，实际上是空内容，因为在sandbox.php的run_in_sandbox里面，sand不可执行,直接return了
echo $result->output;

?>









