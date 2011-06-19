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

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/clilib.php');      // cli only functions

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

// Create daemon
if (function_exists('pcntl_fork')) {
    // Linux
    fork_daemon();
}
else {
    // Windows
    daemon();
}

function fork_daemon() {
    global $CFG, $DB;

    if(empty($CFG->onlinejudge2_daemon_pid) || !posix_kill($CFG->onlinejudge2_daemon_pid, 0)){ 
        // No daemon is running
        //Forks the currently running process
        $pid = pcntl_fork(); 
        if ($pid == -1) {
             mtrace('Could not fork');
        } 
        else if ($pid > 0){ 
            //Parent process
            //Reconnect db, so that the parent won't close the db connection shared with child after exit.
            reconnect_db();

            set_config('onlinejudge2_daemon_pid' , $pid);
        } 
        else { 
            //Child process
            daemon(); 
        }
    }
}

function daemon(){
    global $CFG;
    
    // get process pid
    $pid = getmypid();
    mtrace('Judge daemon created. PID = ' . $pid);

    if (function_exists('pcntl_fork')) { 
        // In linux, this is a new session
        // Start a new sesssion. So it works like a daemon
        //make the current process a session leader.
        $sid = posix_setsid();
        if ($sid < 0) {
            mtrace('Can not setsid');
            exit;
        }

        //Redirect error output to php log
        $CFG->debugdisplay = false;
        @ini_set('display_errors', '0');
        @ini_set('log_errors', '1');

        // Close unused fd
        fclose(STDIN);
        fclose(STDOUT);
        fclose(STDERR);

        reconnect_db();

        // Handle SIGTERM so that can be killed without pain
        declare(ticks = 1); // tick use required as of PHP 4.3.0
        pcntl_signal(SIGTERM, 'sigterm_handler');
    }

    set_config('onlinejudge2_daemon_pid' , $pid);

    // Run forever until be killed or plugin was upgraded
    while(!empty($CFG->onlinejudge2_daemon_pid)){
        global $DB;
        $this->judge_all_unjudged();

        // If error occured, reconnect db
        if ($DB->ErrorNo()) {
            reconnect_db();
        }

        //Check interval is 5 seconds
        sleep(5);

        //renew the config value which could be modified by other processes
        $CFG->onlinejudge2_daemon_pid = get_config(NULL, 'onlinejudge2_daemon_pid');
    }
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


?>
