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
 * Online Judge cron job
 * 
 * @package   local_onlinejudge
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once('judgelib.php');

mtrace('Starting online judge cron');
// Nofity admin if judged is not running
$a = new stdClass();
$a->count = $DB->count_records('onlinejudge_tasks', array('status' => ONLINEJUDGE_STATUS_PENDING));
if ($a->count > 0) {
    $oldest_unjudged = $DB->get_records('onlinejudge_tasks', array('status' => ONLINEJUDGE_STATUS_PENDING), 'submittime ASC', 'submittime', 0, 1);
    $pending_period = time() - reset($oldest_unjudged)->submittime;
    $a->period = format_time($pending_period);

    // if there is at least one task has been keeping unjudged in queue for more than 5 mins
    if ($pending_period > 5 * 60) {
        mtrace("    Found $a->count long time pending tasks.");

        if ($users = get_users_from_config(get_config('local_onlinejudge', 'judgedcrashnotify'), 'moodle/site:config')) {
            $admin = get_admin();
            foreach ($users as $user) {
                $eventdata = new stdClass();
                $eventdata->component         = 'local_onlinejudge';
                $eventdata->name              = 'judgedcrashed';
                $eventdata->userfrom          = $admin;
                $eventdata->userto            = $user;
                $eventdata->subject           = get_string('judgednotifysubject', 'local_onlinejudge', $a);
                $eventdata->fullmessage       = get_string('judgednotifybody', 'local_onlinejudge', $a);
                $eventdata->fullmessageformat = FORMAT_PLAIN;
                $eventdata->fullmessagehtml   = '';
                $eventdata->smallmessage      = '';
                $eventdata->notification      = 1;
                message_send($eventdata);
                mtrace('    Sent notification to '.fullname($user));
            }
        }
    }
}

mtrace('Finished online judge cron');
