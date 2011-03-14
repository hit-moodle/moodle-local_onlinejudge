<?php

/**
 * This file can only be invoked from cli by the following command:
 *   /usr/bin/php /PATH/TO/MOODLE/mod/assignment/type/onlinejudge/judged.php
 * A judge daemon will be created.
 */

require_once(dirname(__FILE__) . '/../../../../config.php');
require_once("$CFG->dirroot/mod/assignment/lib.php");
require_once('assignment.class.php');

if (isset($_SERVER['REMOTE_ADDR'])) { // if the script is accessed via the web.
    print_error('errorclionly', 'assignment_onlinejudge');
    exit;
}

// Kill old daemon if it exists
if(!empty($CFG->assignment_oj_daemon_pid)) {
   if (function_exists('posix_kill')) { // Linux
       mtrace('Killing old judged. PID = ' . $CFG->assignment_oj_daemon_pid);
       posix_kill($CFG->assignment_oj_daemon_pid, SIGTERM);
       // Wait for its quit
       while(posix_kill($CFG->assignment_oj_daemon_pid, 0))
           sleep(0);
       mtrace('done');
       $CFG->assignment_oj_daemon_pid = 0;
   } else {
       mtrace("It seems that a judged (PID: $CFG->assignment_oj_daemon_pid) is still running.");
       mtrace("It must be killed manually.");
       mtrace("If it has been killed, enter 'C' to continue.");
       $input = trim(fgets(STDIN));
       if ($input != 'C' && $input != 'c')
           die;
   }
}

// Create daemon
$oj = new assignment_onlinejudge();
if (function_exists('pcntl_fork')) // Linux
    $oj->fork_daemon();
else // Windows
    $oj->daemon();

?>
