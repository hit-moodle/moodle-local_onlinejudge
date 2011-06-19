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
 * This script judges all unjudged tasks
 *
 * In Linux, it will create a daemon and exit
 * In Windows, it will never exit except killed by users
 *
 * @package    plagiarism_moss
 * @subpackage cli
 * @copyright  2011 Sun Zhigang (http://sunner.cn)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/clilib.php');      // cli only functions

// now get cli options
list($options, $unrecognized) = cli_get_params(array('help'=>false, 'nodaemon'=>true, 'once'=>false),
                                               array('h'=>'help', 'n'=>'nodaemon', 'o'=>'once'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help =
"Judge all unjudged tasks.

Options:
-h, --help            Print out this help
-n, --nodaemon        Do not run as daemon (Linux with php-posix only)
-o, --once            Exit while no more to judge

Example:
\$sudo -u www-data /usr/bin/php local/onlinejudge2/cli/judged.php
";

    echo $help;
    die;
}

// TODO: don't kill old daemon any more when we can run several judged process concurrently
// Kill old daemon if it exists
if(!empty($CFG->onlinejudge2_daemon_pid)) {
    if (function_exists('posix_kill')) { 
   	    // Linux
        mtrace('Killing old judged. PID = ' . $CFG->onlinejudge2_daemon_pid);
        posix_kill($CFG->onlinejudge2_daemon_pid, SIGTERM);
       
        // Wait for its quit
        while(posix_kill($CFG->onlinejudge2_daemon_pid, 0))
            sleep(0);
        mtrace('done');
        $CFG->onlinejudge2_daemon_pid = 0;
    } 
    else {
        mtrace("It seems that a judged (PID: $CFG->onlinejudge2_daemon_pid) is still running.");
        mtrace("It must be killed manually.");
        mtrace("If it has been killed, enter 'C' to continue.");
        //strip the whitespace character.
        $input = trim(fgets(STDIN));
        if ($input != 'C' && $input != 'c')
            die;
   }
}

if (!$options['nodaemon']) {
    // create daemon

    if (!extension_loaded('pcntl') || !extension_loaded('posix')) {
        cli_error('pcntl and posix extension must be installed!');
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

        // Handle SIGTERM so that can be killed without pain
        declare(ticks = 1); // tick use required as of PHP 4.3.0
        pcntl_signal(SIGTERM, 'sigterm_handler');

        // reconnect DB
        unset($DB);
        setup_DB();
    }
}

// Run forever until being killed or the plugin was upgraded
$stop = false;
$plugin_version = get_config('local_onlinejudge2', 'version');
while (!$stop) {

    judge_all_unjudged();

    //Check interval is 5 seconds
    sleep(5);

    // upgraded?
    $stop = $plugin_version < get_config('local_onlinejudge2', 'version');
}

/**
 * Get one unjudged task and set it as judged
 * If all tasks have been judged, return false
 * The function can be reentranced
 */
function get_unjudged_task() {
    global $CFG, $DB;
    while (!set_cron_lock('onlinejudge2_judging', time() + 10)) {}
    
    $tasks = $DB->get_records('onlinejudge2_tasks', array('status' => ONLINEJUDGE2_STATUS_PENDING));
    $task = null;
    if ($tasks != null) {
        //for test
        echo "tasks not null<br />";
        // pop one task.
        $task = array_pop($tasks);
        // Set judged mark
        $DB->set_field('onlinejudge2_tasks', 'judged', 1, array('id' => $task->id));
    }

        set_cron_lock('onlinejudge2_judging', null);

        return $task;     
}

// Judge all unjudged tasks
function judge_all_unjudged(){
        global $CFG;
        while ($task = get_unjudged_task()) {
            $cm = get_coursemodule_from_instance('assignment', $task->coursemodule);

            $this->judge($task);
        }
}

function sigterm_handler($signo) {
    global $stop;

    $stop = true;
}

?>
