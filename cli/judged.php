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
 * Judges all unjudged tasks
 *
 * In Linux, it will create a daemon and exit
 * In Windows, it will never exit except killed by users
 *
 * @package    local_onlinejudge
 * @copyright  2011 Sun Zhigang (http://sunner.cn)
 * @author     Sun Zhigang
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
define('LOCK_FILE', '/temp/onlinejudge/lock');

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/clilib.php');      // cli only functions
require_once($CFG->dirroot.'/local/onlinejudge/judgelib.php');

// Ensure errors are well explained
if ($CFG->debug < DEBUG_NORMAL) {
    $CFG->debug = DEBUG_NORMAL;
}

// now get cli options
$longoptions  = array('help'=>false, 'nodaemon'=>false, 'once'=>false, 'verbose'=>false);
$shortoptions = array('h'=>'help', 'n'=>'nodaemon', 'o'=>'once', 'v'=>'verbose');
list($options, $unrecognized) = cli_get_params($longoptions, $shortoptions);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help =
"Judge all unjudged tasks.

Options:
-h, --help            Print out this help
-n, --nodaemon        Do not run as daemon (Linux only)
-o, --once            Exit while no more to judge
-v, --verbose         Verbose output

Example:
\$sudo -u www-data /usr/bin/php local/onlinejudge/cli/judged.php
";

    echo $help;
    die;
}

if ($CFG->ostype != 'WINDOWS' and !$options['nodaemon']) {
    // create daemon
    verbose(cli_heading('Creating daemon', true));

    if (!extension_loaded('pcntl') || !extension_loaded('posix')) {
        cli_error('PHP pcntl and posix extension must be installed!');
    }

    $pid = pcntl_fork();

    if ($pid == -1) {
        cli_error('Could not fork');
    } else if ($pid > 0) { // parent process
        mtrace('Judge daemon successfully created. PID = '.$pid);
        die;
    } else { // child process
        // make the current process a session leader.
        $sid = posix_setsid();
        if ($sid < 0) {
            cli_error('Can not setsid()');
        }

        // reconnect DB
        unset($DB);
        setup_DB();
    }
}

verbose(cli_separator(true));
verbose('Judge daemon is running now.');

if ($CFG->ostype != 'WINDOWS' and function_exists('pcntl_signal')) {
    // Handle SIGTERM and SIGINT so that can be killed without pain
    declare(ticks = 1); // tick use required as of PHP 4.3.0
    pcntl_signal(SIGTERM, 'sigterm_handler');
    pcntl_signal(SIGINT, 'sigterm_handler');
}

// Run forever until being killed or the plugin was upgraded
$lockfile = $CFG->dataroot . LOCK_FILE;
if (!check_dir_exists(dirname($lockfile))) {
    throw new moodle_exception('errorcreatingdirectory', '', '', $lockfile);
}
$LOCK = fopen($lockfile, 'w');
if (!$LOCK) {
    mtrace('Can not create'.$CFG->dataroot.LOCK_FILE);
    die;
}

$forcestop = false;
$plugin_version = get_config('local_onlinejudge', 'version');
while (!$forcestop) {
    judge_all_unjudged();

    if ($options['once']) {
        break;
    }

    //Check interval is 5 seconds
    // TODO: definable by admin
    sleep(5);

    if ($plugin_version < get_config('local_onlinejudge', 'version')) {
        verbose('Plugin was upgraded.');
        break;
    }
}

verbose('Clean temp files.');
onlinejudge_clean_temp_dir(false);  // Clean full tree of temp dir
verbose('Judge daemon exits.');
fclose($LOCK);

/**
 * Return one unjudged task and set it status as JUDGING
 *
 * @return an unjudged task or null;
 */
function get_one_unjudged_task() {
    global $CFG, $DB, $LOCK;

    $task = null;

    flock($LOCK, LOCK_EX); // try locking, but ignore if not available (eg. on NFS and FAT)
    try {
        if ($task = $DB->get_record('onlinejudge_tasks', array('status' => ONLINEJUDGE_STATUS_PENDING), '*', IGNORE_MULTIPLE)) {
            $DB->set_field('onlinejudge_tasks', 'status', ONLINEJUDGE_STATUS_JUDGING, array('id' => $task->id));
        }
    } catch (Exception $e) {
        flock($LOCK, LOCK_UN);
        throw $e;
    }

    flock($LOCK, LOCK_UN);

    return $task;
}

// Judge all unjudged tasks
function judge_all_unjudged() {
    while ($task = get_one_unjudged_task()) {
        verbose(cli_heading('TASK: '.$task->id, true));
        verbose('Judging...');
        try {
            $task = onlinejudge_judge($task);
            verbose("Successfully judged: $task->status");
        } catch (Exception $e) {
            $info = get_exception_info($e);
            $errmsg = "Judged inner level exception handler: ".$info->message.' Debug: '.$info->debuginfo."\n".format_backtrace($info->backtrace, true);
            cli_problem($errmsg);
            // Continue to get next unjudged task
        }
    }
}

function sigterm_handler($signo) {
    global $forcestop;
    $forcestop = true;
    verbose("Signal $signo catched");
}

function verbose($msg) {
    global $options;
    if ($options['verbose']) {
        mtrace(rtrim($msg));
    }
}

