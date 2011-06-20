<?php
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once('styles.php');


require_login(SITEID, false);
require_capability('local/onlinejudge2:manage', get_system_context());

global $CFG;
global $PAGE;
global $DB;
global $USER;

$PAGE->set_pagelayout('standard');
$PAGE->set_url('/local/onlinejudge2/result.php');
$PAGE->set_title('Onlinejudge2 ---- ' . get_string('page_result', 'local_onlinejudge2'));
$PAGE->set_heading('Onlinejudge2 ---- ' . get_string('page_result', 'local_onlinejudge2'));

$output = $PAGE->get_renderer('local_onlinejudge2');


/// Output starts here
echo $output->header();
echo $output->heading("提交历史显示");
$currentid = $USER->id;
$tasks = array();
//$tasks = $DB->get_records('onlinejudge2_tasks', array('userid' => $currentid));
$tasks = $DB->get_records('onlinejudge2_tasks'); //for test
//由于不大会用moodle自带的html编辑器,这里就用html代替
?>
<script language="javascript">

function view_info() {
   window.open('view.php?id=8');
}


</script>
<?php 
echo "<table border='4' width='1040'>
              <th>
                  id
              </th>
              <th>
                  作业id
              </th>
              <th colspan='2'>
                  状态(鼠标移动到数值上显示提示信息)
              </th>
              <th colspan='2'>
                  次数统计(通过次数/总次数)
              </th>
              <th>
                  提交时间
              </th>
              <th>
                  结束时间
              </th>
              <th>
                  得分
              </th>
              <th>
                  详细信息
              </th>
       ";
    foreach($tasks as $task) {
       //首先计算通过次数和总次数,这里为了测试方便,先不计算了
       $total = count($tasks) + rand(0, 20);
       $success = rand(0, $total); // for test
       //得分同样也先不计算了,
       $num = rand(50, 100);
       $score = $num;
       $status = $task->status;
       $status_trans = translate_status($status);
       echo  "<tr>
              <td align='center'>".
                  $task->id.
             "</td>
              <td align='center'>".
                  $task->coursemodule.
             "</td>
              <td colspan='2' align='center'>".
                  "<a href='#' title=$status_trans>".$task->status.
             "</td>
              <td colspan='2' align='center'>".
                  $success.'/'.$total.
             "</td>
              <td align='center'>".
                  $task->submittime.
             "</td>
              <td align='center'>".
                  $task->judgetime.
             "</td>
              <td align='center'>".
                  $score.
             "</td>
              <td align='center'>".
                  //"<a href='#' onClick='view_info();'>点击查看</a>".
                  "<a href='view.php?id=$task->id'>点击查看</a>".
             "</td>
          </tr>"
         ;
    }

echo  "</table>";

echo $output->footer();
?>











