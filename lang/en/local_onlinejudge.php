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

$string['about'] = 'About';
$string['aboutcontent'] = '<a href="https://github.com/hit-moodle/moodle-local_onlinejudge">Online Judge</a> is developed by <a href="http://www.hit.edu.cn">Harbin Institute of Technology</a>, and licensed by <a href="http://www.gnu.org/copyleft/gpl.html">GPL</a>.';
$string['badvalue'] = 'Incorrect value';
$string['cannotrunsand'] = 'Can not run the sand';
$string['compileroutput'] = 'Output of compiler';
$string['cpuusage'] = 'CPU usage';
$string['defaultlanguage'] = 'Default language';
$string['defaultlanguage_help'] = 'Default language setting for new online judge assignments.';
$string['details'] = 'Details';
$string['ideoneautherror'] = 'Wrong username or wrong password';
$string['ideonedelay'] = 'Delay between requests to ideone.com (seconds)';
$string['ideonedelay_help'] = 'If the delay between sending judge requests and getting results is too short, ideone.com will reject.';
$string['ideoneerror'] = 'Ideone returns error: {$a}';
$string['ideonelogo'] = '<a href="https://github.com/hit-moodle/moodle-local_onlinejudge">Moodle Online Judge</a> uses <a href="http://ideone.com">Ideone API</a> &copy; by <a href="http://sphere-research.com">Sphere Research Labs</a>';
$string['ideoneresultlink'] = 'See details at <a href="http://ideone.com/{$a}">http://ideone.com/{$a}</a>.';
$string['ideoneuserrequired'] = 'Required if ideone.com judge is selected';
$string['info'] = 'Information';
$string['info0'] = 'If you have been waiting too long, please inform the admin';
$string['info1'] = 'Congratulation!!!';
$string['info2'] = 'A good program must return 0 if no error occurs';
$string['info3'] = 'The compiler dislikes your code';
$string['info4'] = 'It seems that the compiler likes your code';
$string['info5'] = 'You ate too much memory';
$string['info6'] = 'Your code sent too much to stdout';
$string['info7'] = 'Almost perfect, except some bad white spaces, tabs, new lines and etc';
$string['info8'] = 'Your code called some functions which are <em>not</em> allowed to run';
$string['info9'] = '[SIGSEGV, Segment fault] Bad array index, bad pointer access or even worse';
$string['info10'] = 'The program has been running for too long time';
$string['info11'] = 'Double check your code. Don\'t output any typo or non-required characters';
$string['info21'] = 'The judge engine does not work well. Please inform the admin';
$string['info22'] = 'If you have been waiting too long, please inform the admin';
$string['infostudent'] = 'Information';
$string['infoteacher'] = 'Sensitive information';
$string['invalidlanguage'] = 'Invalid language ID: {$a}';
$string['invalidjudgeclass'] = 'Invalid judge class: {$a}';
$string['invalidtaskid'] = 'Invalid task id: {$a}';
$string['judgedcrashnotify'] = 'Judge daemon crashed notification';
$string['judgedcrashnotify_help'] = 'Judge daemon may be crashed or quit due to software bugs or upgrading. If so, who will receive the notification? It should be a person who can access the shell of the server and launch the judge daemon.';
$string['judgednotifybody'] = 'Among the {$a->count} pending tasks, the oldest task has been in the waiting queue for {$a->period}.

It is possible that the judge daemon of online judge was crashed or quitted. You must launch it as soon as possible!

Or, it is possible that there are too much tasks in the queue and you should consider to run multiply judge daemons.';
$string['judgednotifysubject'] = '{$a->count} pending tasks have been waiting too long';
$string['judgestatus'] = 'Online Judge has judged <strong>{$a->judged}</strong> tasks and there are <strong>{$a->pending}</strong> tasks in the waiting queue.';
$string['langc_sandbox'] = 'C (run locally)';
$string['langc_warn2err_sandbox'] = 'C (run locally, warnings as errors)';
$string['langcpp_sandbox'] = 'C++ (run locally)';
$string['langcpp_warn2err_sandbox'] = 'C++ (run locally, warnings as errors)';
$string['maxcpulimit'] = 'Maximum CPU usage (second)';
$string['maxcpulimit_help'] = 'How long can a program been judged keep running.';
$string['maxmemlimit'] = 'Maximum memory usage (MB)';
$string['maxmemlimit_help'] = 'How many memory can a program been judged use.';
$string['memusage'] = 'Memory usage';
$string['messageprovider:judgedcrashed'] = 'Online judge daemon crashed notification';
$string['mystat'] = 'My Statistics';
$string['notesensitive'] = '* Shown to teachers only';
$string['onefileonlyideone'] = 'Ideone.com does not support multi-files';
$string['onlinejudge:viewjudgestatus'] = 'View judge status';
$string['onlinejudge:viewmystat'] = 'View self statistics';
$string['onlinejudge:viewsensitive'] = 'View sensitive details';
$string['pluginname'] = 'Online Judge';
$string['sandboxerror'] = 'Sandbox error occurs: {$a}';
$string['settingsform'] = 'Online Judge Settings';
$string['settingsupdated'] = 'Settings updated.';
$string['status0'] = 'Pending...';
$string['status1'] = 'Accepted';
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
$string['status255'] = 'Unsubmitted';
$string['stderr'] = 'Standard error output';
$string['stdout'] = 'Standard output';
$string['upgradenotify'] = 'Do NOT forget to execute cli/install_assignment_type and cli/judged.php. Details in <a href="https://github.com/hit-moodle/moodle-local_onlinejudge/blob/master/README.md" target="_blank">README</a>.';

