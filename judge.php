<?php
//本文件主要用于模拟运行
require_once("../../config.php");
global $CFG, $DB;
require_once($CFG->dirroot.'/local/onlinejudge2/judgelib.php');
//require_once($CFG->dirroot.'/local/onlinejudge2/judged.php');
echo $_SERVER['REMOTE_ADDR'];
//sub是封装的数据包
$task = new stdClass();
$cm = 13;
$user = 13;
$language = 'c_sandbox';
$source = '#include <stdio.h>
int main(void)
{
    int a, b;
    while (scanf("%d %d", &a, &b)==2)
        printf("%d\n",a+b);
    return 0;
}
';
$task->coursemodule = 13;
$task->userid = 13;
$task->language = 'c_sandbox';
$task->cpulimit = 1;
$task->memlimit = 1048576;

$task->input = '2 3';
$task->output = 5;
$task->compileonly = false;
$task->answer = null;
$task->info_teacher = null;
$task->info_student = null;
$task->cpuusage = $task->cpulimit;
$task->memusage = $task->memusage;
$task->submittime = time();
//$task->status = ONLINEJUDGE2_STATUS_PENDING;
//$task->judgetime = null;
//$task->onlinejudge2_ideone_username = 'yuzhanlaile2';
//$task->onlinejudge2_ideone_password = 'yuzhanlaile2';
//$task->onlinejudge2_ideone_delay = 100;


//$result = onlinejudge2_get_judge(onlinejudge2_submit_task($cm, $user, $language, $source, $task, $error));
//$result = onlinejudge2_get_judge(13);
//$id = onlinejudge2_submit_task($cm, $user, $language, $source, $task, $error);
//echo $result->status;
$res = $DB->get_record('onlinejudge2_tasks', array('id' => 14));
if($res->id) {
	echo "hahahahahahaha";
	echo $res->id.'sfdsgsgsg';
}
echo $res->language."<br>";
//.$res->status;
//echo $id;
echo "<br>";
//echo $result->info_teacher;
echo "<br>";
//echo microtime($result->submittime);
//echo gmdate("d-M-Y h:i:s A",$result->submittime);
//echo "<br>";
//echo gmdate("d-M-Y h:i:s A",$result->judgetime);
//echo "<br>";
//echo $result->answer;


//echo $id;




?>









