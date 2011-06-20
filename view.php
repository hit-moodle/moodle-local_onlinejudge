<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Main onlinejudge2 translation page
 *
 * Displays strings filter and the translation table. Data submitted from the
 * whole translation table are handled by savebulk.php which should redirect
 * back here.
 *
 * @package   local-onlinejudge2
 * @copyright 2011 Yu Zhan <yuzhanlaile@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//此页面用于显示程序运行的详细信息
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once('styles.php');

global $CFG;
require($CFG->dirroot.'/local/onlinejudge2/judgelib.php');

global $PAGE;
global $DB;
global $USER;
require_capability('local/onlinejudge2:manage', get_system_context());
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/local/onlinejudge2/view.php');
$PAGE->set_title('Onlinejudge2 ---- ' . get_string('page_view', 'local_onlinejudge2'));
$PAGE->set_heading('Onlinejudge2 ---- ' . get_string('page_view', 'local_onlinejudge2'));

$output = $PAGE->get_renderer('local_onlinejudge2');

/// Output starts here
echo $output->header();
echo $output->heading("程序详细信息");


$id = optional_param('id', null, PARAM_INT); //get id

if(is_null($id)) {
    mtrace('没有指定需要查看的作业，请返回...');
    exit;
}
//get the record from database , based on the $id
$task = new stdClass();
$task = null;
$task = $DB->get_record('onlinejudge2_tasks', array('id' => $id));

//output
//base information
echo $output->container_start('base_info', 'base_info'); //first is class, second is id
echo 'Base information';
echo $output->container_end();
//trip the langguages's suffix， eg:'cpp_ideone'=> 'cpp'
$language = substr($task->language, 0, strrpos($task->language, '_'));
//get compiler
$compiler = substr($task->language, strrpos($task->language, '_')+1);
//translate status
$status = translate_status($task->status);
$base_info =array(
        'language:        '=>    $language,
        'compiler:        '=>    $compiler,
        'status:          '=>    $status,
        'date(submit):    '=>    $task->submittime,
        'date(judge):     '=>    $task->judgetime,
);
foreach($base_info as $name=>$value) {
    $base_info[$name] = $name.$value;
}
$base_info = '<li>' . implode("</li>\n<li>", $base_info) . '</li>';
echo html_writer::tag('ul', $base_info);

//detail information
echo $output->container_start('detail_info', 'detail_info'); //first is class, second is id
echo 'Detail information';
echo $output->container_end();

echo "<p></p>";
echo "source:";
$source = $task->source; 
//TODO format the source, use html_writer and use syntaxhighliter to show.
echo "<table width = '300', height='200' border='2'>
          <tr>
              <td>
                  $source
              </td>
          </tr>
      </table>
     ";
echo "<p></p>";
$detail_info =array(
        'input:           '=>    $task->input,
        'output:          '=>    $task->output,
        'answer:          '=>    $task->answer,
        'memusage:        '=>    $task->memusage,
        'cpuusage:        '=>    $task->cpuusage,
        'maxmem:          '=>    $task->memlimit,
        'maxcpu:          '=>    $task->cpulimit,
        'information:     '=>    $task->info_teacher,
);
if(! is_null($task->error)) {
    array_push($detail_info, $task->error);
}

foreach($detail_info as $name=>$value) {
    $detail_info[$name] = $name.$value;
}
$detail_info = '<li>' . implode("</li>\n<li>", $detail_info) . '</li>';
echo html_writer::tag('ul', $detail_info);

echo $output->heading();

echo $output->footer();
?>















