<?php
//本文件主要用于模拟运行


require_once("../../config.php");
require_once("judgelib.php");
global $CFG, $DB;

$jf = new judge_factory();
$jf->get_judge_methods();
$result = new Object; //结果


//sub是封装的数据包
$sub = array();
$sub['id'] = 1;
$sub['cpulimit'] = 1;
$sub['memlimit'] = 1048576;
$sub['judgeName'] = 301;
$sub['source'] = '#include "stdio.h" 
                   int main()
                   {
                       int a, b;
                       scanf("%d,%d",&a,&b);
                       printf("%d",a+b);
                       return 0;
                   }
';
$sub['input'] = '2,3';
$sub['output'] = '5';
$sub['usefile'] = 0;
$sub['inputfile'] = 0;
$sub['outputfile'] = 0; 


$result = $jf->get_judge($sub);
//输出结果，实际上是空内容，因为在sandbox.php的run_in_sandbox里面，sand不可执行,直接return了
echo $result->output;











?>