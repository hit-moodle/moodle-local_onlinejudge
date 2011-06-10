<?php
//本文件主要用于模拟运行


require_once("../../config.php");
require_once($CFG->dirroot."/local/onlinejudge2/judge/sandbox/lib.php");
require_once($CFG->dirroot."/local/onlinejudge2/judge/ideone/lib.php");
//require_once("judgelib.php");
global $CFG, $DB;
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

$str = 'cpp_sandbox';
$st = substr($str,0,strlen($str)-8);
echo $str;
echo $st;







?>









