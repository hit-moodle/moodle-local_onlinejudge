<?
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
 * Global settings setup page
 * 
 * @package   local_onlinejudge
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require(dirname(__FILE__).'../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/onlinejudge/admin/forms.php');
require_once($CFG->dirroot . '/local/onlinejudge/judgelib.php'); // Invoke the default settings

admin_externalpage_setup('local_onlinejudge');

$ojsettingsform = new onlinejudge_settings_form();
$fromform = $ojsettingsform->get_data();

echo $OUTPUT->header();

// TODO: check environments

if (!empty($fromform) and confirm_sesskey()) {

    //Save settings
    set_config('maxmemlimit', $fromform->maxmemlimit*1024*1024, 'local_onlinejudge');
    set_config('maxcpulimit', $fromform->maxcpulimit, 'local_onlinejudge');
    set_config('ideonedelay', $fromform->ideonedelay, 'local_onlinejudge');

    //display confirmation
    echo $OUTPUT->notification(get_string('settingsupdated', 'local_onlinejudge'), 'notifysuccess');
    $ojsettingsform->display();
} else {
    $data->maxmemlimit = get_config('local_onlinejudge', 'maxmemlimit')/1024/1024;
    $data->maxcpulimit = get_config('local_onlinejudge', 'maxcpulimit');
    $data->ideonedelay = get_config('local_onlinejudge', 'ideonedelay');
    $ojsettingsform->set_data($data);
    $ojsettingsform->display();
}

echo $OUTPUT->footer();

