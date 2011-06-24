<?php

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
//                      Online Judge for Moodle                          //
//        https://github.com/hit-moodle/moodle-local_onlinejudge         //
//                                                                       //
// Copyright (C) 2009 onwards  Sun Zhigang  http://sunner.cn             //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 3 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
 * Strings for local_onlinejudge
 * 
 * @package   local_onlinejudge
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['about'] = '<p>Based on Moodle, Onlinejudge2 is a tool for onlinejudging work.</p>
<p>It provides kinds of compiler(sandbox, ideone, etc...)</p>
<p>If you want to join us,see：<a href="https://git.github.com/hit-moodle/onlinejudge">git site</a></p>' ;
$string['badvalue'] = 'Incorrect value';
$string['cannotcreatetmpdir'] = 'Can not create temp directory {$a}';
$string['cannotrunsand'] = 'Can not run the sand';
$string['ideonedelay'] = 'Delay between requests to ideone.com (second)';
$string['ideonedelay_help'] = 'After sending a judge request to ideone.com, we can not get the result immediately. How long should we wait before querying the result? 5 seconds or so is reasonable.';
$string['ideoneerror'] = 'Ideone returns error: {$a}';
$string['ideonelogo'] = '<a href=\"https://github.com/yuxiaoye1223/onlinejudge\">Moodle Online Judge2</a> uses <a href=\"http://ideone.com\">Ideone API</a> &copy; by <a href=\"http://sphere-research.com\">Sphere Research Labs</a>';
$string['ideoneresultlink'] = 'See details at <a href="http://ideone.com/{$a}">http://ideone.com/{$a}</a>.';
$string['info'] = 'Information';
$string['infoat'] = 'A good program must return 0 if no error occur.';
$string['infocompileok'] = 'It seems that the compiler likes your code.';
$string['infoie'] = 'Sandbox error. Report to admin please.';
$string['infomle'] = 'You ate too much memory.';
$string['infoole'] = 'Your code sent too much to stdout.';
$string['infope'] = 'Almost perfect, except some bad white spaces, tabs, new lines and etc.';
$string['infore'] = '[SIGSEGV, Segment fault] Bad array index, bad pointer access or even worse.';
$string['inforf'] = 'Your code calls some functions which are <em>not</em> allowed to run.';
$string['infotle'] = 'The program has been running for a too long time.';
$string['infowa'] = 'Double check your code. Don\'t output any typo or unrequired character.';
$string['invalidlanguage'] = 'Invalid language ID ({$a})';
$string['invalidjudgeclass'] = 'Invalid judge class ({$a})';
$string['langc_sandbox'] = 'C (locally)';
$string['langc_warn2err_sandbox'] = 'C (locally, warnings as errors)';
$string['langcpp_sandbox'] = 'C++ (locally)';
$string['langcpp_warn2err_sandbox'] = 'C++ (locally, warnings as errors)';
$string['maxcpulimit'] = 'Maximum CPU usage (second)';
$string['maxcpulimit_help'] = 'How long can a judge task keep running.';
$string['maxmemlimit'] = 'Maximum memory usage (MB)';
$string['maxmemlimit_help'] = 'How many memory can a judge task use.';
$string['onefileonlyideone'] = 'Ideone.com does not support multi-files';
$string['pluginname'] = 'Online Judge';
$string['sandboxerror'] = 'Sandbox error occurs: {$a}';
$string['settingsform'] = 'Online Judge Settings';
$string['settingsupdated'] = 'Settings updated.';
$string['status0'] = 'Pending...';
$string['status1'] = '<font color=red>Accepted</font>';
$string['status2'] = 'Abnormal Termination';
$string['status3'] = 'Compilation Error';
$string['status4'] = 'Compilation Ok';
$string['status5'] = 'Memory-Limit Exceed';
$string['status6'] = 'Output-Limit Exceed';
$string['status7'] = 'Presentation Error';
$string['status9'] = 'Runtime Error';
$string['status8'] = 'Restricted Functions';
$string['status10'] = 'Time-Limit Exceed';
$string['status11'] = 'Wrong answer';
$string['status21'] = 'Internal Error';
$string['status22'] = 'Judging...';
$string['status23'] = 'Multi-status';
$string['status24'] = 'Judged...';
$string['status255'] = 'Unsubmitted';
$string['uninitedjudge'] = 'Judge object has not a valid $task';
