<?php
// This file is part of Moodle - https://moodle.org
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
 * NOTICE OF COPYRIGHT
 *
 *                      Online Judge for Moodle
 *        https://github.com/hit-moodle/moodle-local_onlinejudge
 *
 * Copyright (C) 2009 onwards
 *                      Sun Zhigang  http://sunner.cn
 *                      Andrew Naguib <andrew at fci helwan edu eg>
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details:
 *
 *          http://www.gnu.org/copyleft/gpl.html
 */

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
$string['seautherror'] = 'Wrong access token or client id';
$string['sedelay'] = 'Delay between requests to sphere-engine.com (seconds)';
$string['sedelay_help'] = 'If the delay between sending judge requests and getting results is too short, sphere-engine.com will reject.';
$string['seerror'] = 'Sphere Engine returns error: {$a}';
$string['selogo'] = '<a href="https://github.com/hit-moodle/moodle-local_onlinejudge">Moodle Online Judge</a> uses <a href="https://sphere-engine.com/">Sphere Engine Compilers API</a> &copy; by <a href="http://sphere-research.com">Sphere Research Labs</a>';
$string['seresultlink'] = 'See details at <a href="https://{$a->end_point}.compilers.sphere-engine.com/api/v4/submissions/{$a->submission_id}?access_token={$a->access_token}">https://{$a->end_point}.compilers.sphere-engine.com/api/v4/submissions/{$a->submission_id}?access_token={$a->access_token}</a>.';
$string['seclientidrequired'] = 'Required if sphere-engine.com judge is selected';
$string['info'] = 'Information';
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
$string['langc-sandbox'] = 'C (run locally)';
$string['langc_warn2err-sandbox'] = 'C (run locally, warnings as errors)';
$string['langcpp-sandbox'] = 'C++ (run locally)';
$string['langcpp_warn2err-sandbox'] = 'C++ (run locally, warnings as errors)';
$string['judgecheckinterval'] = 'Judge Daemon Check Interval (second)';
$string['judgecheckinterval_help'] = 'How many seconds the judge daemon should wait before judging all un-judged tasks.';
$string['maxcpulimit'] = 'Maximum CPU usage (second)';
$string['maxcpulimit_help'] = 'How long can a program been judged keep running.';
$string['maxmemlimit'] = 'Maximum memory usage (MB)';
$string['maxmemlimit_help'] = 'How many memory can a program been judged use.';
$string['memusage'] = 'Memory usage';
$string['messageprovider:judgedcrashed'] = 'Online judge daemon crashed notification';
$string['mystat'] = 'My Statistics';
$string['notesensitive'] = '* Shown to teachers only';
$string['onefileonlyse'] = 'sphere-engine.com does not support multi-files';
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
$string['upgradenotify'] = 'Do NOT forget to execute cli/install_assign_feedback and cli/judged.php. Details in <a href="https://github.com/hit-moodle/moodle-local_onlinejudge/blob/master/README.md" target="_blank">README</a>.';
$string['event_onlinejudge_task_judged'] = "Event Online Judge Task Judged";
$string['event_onlinejudge_task_description'] = "The event is concerned with firing the 'onlinejudge_task_judged' located in MOODLE_PATH/mod/assign/feedback/onlinejudge/lib.php.";
$string['privacy:metadata:onlinejudge_tasks'] = 'Information about judge tasks submitted by users.';
$string['privacy:metadata:onlinejudge_tasks:cmid'] = 'The course module ID where the task was submitted.';
$string['privacy:metadata:onlinejudge_tasks:userid'] = 'The ID of the user who submitted the task.';
$string['privacy:metadata:onlinejudge_tasks:language'] = 'The programming language used for the submission.';
$string['privacy:metadata:onlinejudge_tasks:input'] = 'The input data for the judge task.';
$string['privacy:metadata:onlinejudge_tasks:output'] = 'The expected output data for the judge task.';
$string['privacy:metadata:onlinejudge_tasks:stdout'] = 'The standard output produced by the user\'s program.';
$string['privacy:metadata:onlinejudge_tasks:stderr'] = 'The standard error output produced by the user\'s program.';
$string['privacy:metadata:onlinejudge_tasks:compileroutput'] = 'The output from the compiler when compiling the user\'s code.';
$string['privacy:metadata:onlinejudge_tasks:infoteacher'] = 'Information visible to teachers about the submission.';
$string['privacy:metadata:onlinejudge_tasks:infostudent'] = 'Information visible to students about the submission.';
$string['privacy:metadata:onlinejudge_tasks:cpuusage'] = 'The CPU usage recorded for the task execution.';
$string['privacy:metadata:onlinejudge_tasks:memusage'] = 'The memory usage recorded for the task execution.';
$string['privacy:metadata:onlinejudge_tasks:submittime'] = 'The time when the task was submitted.';
$string['privacy:metadata:onlinejudge_tasks:judgetime'] = 'The time when the task was judged.';
$string['privacy:metadata:onlinejudge_tasks:var1'] = 'Additional variable data for the task.';
$string['privacy:metadata:onlinejudge_tasks:var2'] = 'Additional variable data for the task.';
$string['privacy:metadata:onlinejudge_tasks:var3'] = 'Additional variable data for the task.';
$string['privacy:metadata:onlinejudge_tasks:var4'] = 'Additional variable data for the task.';
$string['privacy:metadata:onlinejudge_tasks:status'] = 'The status of the judge task.';
$string['event_task_judged'] = "Online Judge Task Judged";
$string['event_task_judged_description'] = "The event is fired when an online judge task has been judged.";
// TODO: add translations to other languages.
