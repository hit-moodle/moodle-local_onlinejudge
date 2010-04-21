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
    mtrace('Kill old judged. PID = ' . $CFG->assignment_oj_daemon_pid);
    posix_kill($CFG->assignment_oj_daemon_pid, SIGTERM);
    set_config('assignment_oj_daemon_pid' , 0);
}

// Create daemon
$oj = new assignment_onlinejudge();
$oj->cron();

?>
