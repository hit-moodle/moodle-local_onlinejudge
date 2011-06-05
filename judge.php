<?php
//本文件主要用于模拟运行


require_once("../../config.php");
require_once($CFG->dirroot."/local/onlinejudge2/judge/sandbox/sandbox.php");
require_once($CFG->dirroot."/local/onlinejudge2/judge/ideone/ideone.php");
//require_once("judgelib.php");
global $CFG, $DB;

$jf = new judge_factory();



//sub是封装的数据包
$task = array();
$task['id'] = 1;
$task['taskname'] = 'c';
$task['cpulimit'] = 1;
$task['memlimit'] = 1048576;
$task['judgeName'] = 301;
$task['source'] = '#include "stdio.h" 
               int main()
               {
                   int a, b;
                   scanf("%d%d",&a,&b);
                   printf("%d",a+b);
                   return 0;
               }
';
$task['input'] = '2 3';
$task['output'] = '5';
$task['usefile'] = 0;
$task['inputfile'] = 0;
$task['outputfile'] = 0; 
$judge_obj = new object();
$judge_obj = $jf->get_judge($task['judgeName']);

$taskid = $judge_obj->judge($task); //获取任务id
$result = new stdClass(); //结果
$result = $judge_obj->get_result($taskid); //得到结果对象
//输出结果，实际上是空内容，因为在sandbox.php的run_in_sandbox里面，sand不可执行,直接return了
//两种方式输出结果
//方式一
//echo $result->status;
//echo $result->info;
//方式二
//$judge_obj->output_result($result);



?>









