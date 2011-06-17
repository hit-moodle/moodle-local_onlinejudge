<?php

/**
 * Evaluate student submissions
 */
function cron() {

	global $CFG;

	// Detect the frequence of cron
	//should modify this cron.
	$lastcron = $DB->get_field('modules', 'lastcron', 'name', 'assignment');
    if ($lastcron) {
        set_config('assignment_oj_cronfreq', time() - $lastcron);
    }

    // There are two judge routines
    //  1. Judge only when cron job is running. 
    //  2. After installation, the first cron running will fork a daemon to be judger.
    // Routine two works only when the cron job is executed by php cli
    //
    if (function_exists('pcntl_fork')) { // pcntl_fork supported. Use routine two
        $this->fork_daemon();
    }
     else if ($CFG->onlinejudge2_judge_in_cron) { // pcntl_fork is not supported. So use routine one if configured.
        $this->judge_all_unjudged();
    }
}

function fork_daemon() {
    global $CFG, $db;

    if(empty($CFG->onlinejudge2_daemon_pid) || !posix_kill($CFG->onlinejudge2_daemon_pid, 0)){ // No daemon is running
       $pid = pcntl_fork(); 
       if ($pid == -1) {
            mtrace('Could not fork');
       } else if ($pid > 0){ //Parent process
       //Reconnect db, so that the parent won't close the db connection shared with child after exit.
       reconnect_db();

        set_config('onlinejudge2_daemon_pid' , $pid);
       } 
       else { //Child process
            $this->daemon(); 
       }
    }
}

function daemon(){
    global $CFG;

    $pid = getmypid();
    mtrace('Judge daemon created. PID = ' . $pid);

    if (function_exists('pcntl_fork')) { // In linux, this is a new session
        // Start a new sesssion. So it works like a daemon
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
        global $db;
        $this->judge_all_unjudged();

        // If error occured, reconnect db
        if ($db->ErrorNo())
        reconnect_db();

        //Check interval is 5 seconds
        sleep(5);

        //renew the config value which could be modified by other processes
        $CFG->onlinejudge2_daemon_pid = get_config(NULL, 'onlinejudge2_daemon_pid');
    }
}
?>