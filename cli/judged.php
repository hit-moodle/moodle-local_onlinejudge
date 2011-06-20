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
require_once($CFG->dirroot.'/local/onlinejudge2/judgelib.php');

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
\$sudo -u www-data /usr/bin/php local/onlinejudge2/cli/judged.php
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

if ($CFG->ostype != 'WINDOWS' and method_exists('pcntl_signal')) {
    // Handle SIGTERM and SIGINT so that can be killed without pain
    declare(ticks = 1); // tick use required as of PHP 4.3.0
    pcntl_signal(SIGTERM, 'sigterm_handler');
    pcntl_signal(SIGINT, 'sigterm_handler');
}

// Run forever until being killed or the plugin was upgraded
$forcestop = false;
$upgraded = false;
$plugin_version = get_config('local_onlinejudge2', 'version');
while (!$forcestop and !$upgraded) {

    judge_all_unjudged();

    if ($options['once']) {
        break;
    }

    //Check interval is 5 seconds
    // TODO: definable by admin
    sleep(5);

    $upgraded = $plugin_version < get_config('local_onlinejudge2', 'version');
    if ($upgraded) {
        verbose('Plugin was upgraded.');
    }
}

verbose('Judge daemon exits.');

/**
 * Return one unjudged task's id and set it status as PENDING
 *
 * @return an unjudged task's id or false
 */
function get_one_unjudged_task() {
    global $CFG, $DB;

    $transaction = $DB->start_delegated_transaction();

    try {
        $tasks = $DB->get_records('onlinejudge2_tasks', array('status' => ONLINEJUDGE2_STATUS_PENDING), '', 'id', 0, 1);

        if (!empty($tasks)) {
            $task = array_pop($tasks);
            $DB->set_field('onlinejudge2_tasks', 'status', ONLINEJUDGE2_STATUS_JUDGING, array('id' => $task->id));
            verbose(cli_heading('TASK: '.$task->id, true));
        }

        $transaction->allow_commit();
    } catch (Exception $e) {
        //TODO: reconnect db ?
        $transaction->rollback($e); // rethrows exception
    }

    return isset($task) ? $task->id : false;
}

// Judge all unjudged tasks
function judge_all_unjudged(){
    while ($task = get_one_unjudged_task()) {
        verbose('Judging...');
        onlinejudge2_judge($task->id);
        verbose('Successfully judged');
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
?>
