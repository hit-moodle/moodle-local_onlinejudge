<?php
//本文件主要用于模拟运行


require_once("../../config.php");
//require_once($CFG->dirroot."/local/onlinejudge2/judge/sandbox/lib.php");
//require_once($CFG->dirroot."/local/onlinejudge2/judge/ideone/lib.php");
require_once("judgelib.php");
global $CFG, $DB;
//sub是封装的数据包
$task = new stdClass();
$cm = 1;
$user = 1;
$language = 'c_sandbox';
$source = '#include "stdio.h" 
               int main()
               {
                   int a, b;
                   scanf("%d%d",&a,&b);
                   printf("%d",a+b);
                   return 0;
               }
';

$task->cpulimit = 1;
$task->memlimit = 1048576;

$task->input = '2 3';
$task->output = '5';
$task->compileonly = false;
$task->answer = null;
$task->info_teacher = null;
$task->info_student = null;
$task->cpuusage = $task->cpulimit;
$task->memusage = $task->memusage;
$task->submittime = null;
$task->judgetime = null;

$result = onlinejudge2_get_task(onlinejudge2_submit_task($cm, $user, $language, $source, $task, $error));
echo $result->status;
echo $result->info_teacher;






?>









