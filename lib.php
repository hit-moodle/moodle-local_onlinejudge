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
 * ONLINEJUDGE2 interface library
 *
 * @package   onlinejudge2
 * @copyright 2011 Yu Zhan <yuzhanlaile@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * add the onlinejudge plugin into navigation
 */
function onlinejudge2_extends_navigation(global_navigation $navigation) {
    $onlinejudge2 = $navigation->add('Onlinejudge2', new moodle_url('/local/onlinejudge2/'));

    $onlinejudge2->add("配置", new moodle_url("/local/onlinejudge2/settings.php"));
    $onlinejudge2->add("提交作业", new moodle_url("/local/onlinejudge2/judgelib.php"));
    $onlinejudge2->add("查看状态", new moodle_url("/local/onlinejudge2/result.php"));

}




