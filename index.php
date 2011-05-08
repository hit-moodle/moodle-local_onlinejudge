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
 * ONLINEJUDGE2 home page
 *
 * @package   local-onlinejudge2
 * @copyright 2011 Yu Zhan <yuzhanlaile@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/judgelib.php');

require_login(SITEID, false);

$PAGE->set_pagelayout('standard');
$PAGE->set_url('/local/onlinejudge2/index.php');
$PAGE->set_title('Onlinejudge2');
$PAGE->set_heading('Onlinejudge2');

$output = $PAGE->get_renderer('local_onlinejudge2');

/// Output starts here
echo $output->header();
echo $output->heading(get_string('onlinejudge2', 'local_onlinejudge2'), 1);
echo $output->container(get_string('about', 'local_onlinejudge2'));
echo "这里是关于Onlinejudge的相关说明（功能，版权等）<br>";

echo $output->heading(get_string('privileges', 'local_onlinejudge2'));
echo "这里是一些当前用户的权限说明<br>";
$caps = array();
if (has_capability('local/onlinejudge2:manage', get_system_context())) {
    $caps[] = get_string('onlinejudge2:manage', 'local_onlinejudge2');
}
if (has_capability('local/onlinejudge2:query', get_system_context())) {
    $caps[] = get_string('onlinejudge2:query', 'local_onlinejudge2');
}
if (has_capability('local/onlinejudge2:delete', get_system_context())) {
    $caps[] = get_string('amos:delete', 'local_onlinejudge2');
}
if (empty($caps)) {
    get_string('privilegesnone', 'local_onlinejudge2');
} else {
    $caps = '<li>' . implode("</li>\n<li>", $caps) . '</li>';
    echo html_writer::tag('ul', $caps);
}
echo $output->heading(get_string('judge_methods', 'local_onlinejudge2'));

//output the supported-languages' id.
echo "当前支持编译的语言的id值如下：<br>";
$jf = new judge_factory();
?>
<?php 
/** the following codes is writted before I read the Form_API@moodle, 
 *  I noticed that such codes is not moodle like. so I put all of 
 *  them into comments.
 * 
 * 
 *  I have read the API, now I know that the html part should be edited
 *  by the tool called Moodle HTML Editor
 */
?>
<!--  
<table width='700'  cellspacing="10" ">
<tr>
<th align='left'>支持的语言</th>
<th align='right'>ID值</th>
<th align='right'>对语言的说明介绍</th>
</tr>
-->
<?php 
/*
foreach($jf->judge_methods as $key=>$value)
{
*/
?>
<?php 
/*
if($value%2 != 0)
{
	echo "
	<tr bgcolor='pink'>
		<td>
			$key	
		</td>
		<td align='right'>
			$value
		</td>
		<td align='right'>
			'这里是对语言的说明'
		</td>
	</tr>
	";
}
else
{
	echo "
	<tr>
		<td>
			$key	
		</td>
		<td align='right'>
			$value
		</td>
		<td align='right'>
			'这里是对语言的说明'
		</td>
	</tr>
	";
	
}
*/


?>

<?php 
// }
?>
<!--  </table>   -->
<?php 
echo $output->footer();

?>



