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

require_login(SITEID, false);

$PAGE->set_pagelayout('standard');
$PAGE->set_url('/local/onlinejudge2/index.php');
$PAGE->set_title('Onlinejudge');
$PAGE->set_heading('Onlinejudge');

$output = $PAGE->get_renderer('local_onlinejudge2');

/// Output starts here
echo $output->header();
echo $output->heading(get_string('onlinejudge', 'local_onlinejudge2'), 1);
echo $output->container(get_string('about', 'local_onlinejudge2'));
echo "这里是关于Onlinejudge的相关说明（功能，版权等）";
echo get_string('about', 'local_onlinejudge2');

echo $output->heading(get_string('privileges', 'local_onlinejudge2'));
echo "这里是一些当前用户的权限说明";
echo $output->heading(get_string('judge_methods', 'local_onlinejudge2'));
//输出可以进行编译的语言
echo "当前支持编译的语言以及id值如下：<br>";
echo "";


echo $output->footer();
