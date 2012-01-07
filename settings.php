<?php
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// This file is part of Online Judge 2//moodle.org/                      //
//                                                                       //
// Online Judge 2 is free software: you can redistribute it and/or modify//
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation, either version 3 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// Online Judge 2 is distributed in the hope that it will be useful,     //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details.                          //
//                                                                       //
// You should have received a copy of the GNU General Public License     //
// along with Online Judge 2. If not, see <http://www.gnu.org/licenses/>.//
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
 * Administration forms of the online judge
 * 
 * @package   local_onlinejudge
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) { // needs this condition or there is error on login page
    require_once($CFG->dirroot.'/local/onlinejudge/judgelib.php');

    $temp = new admin_settingpage('onlinejudge', get_string('pluginname', 'local_onlinejudge'));

    $temp->add(new admin_setting_configtext('local_onlinejudge/maxmemlimit', get_string('maxmemlimit', 'local_onlinejudge'), get_string('maxmemlimit_help', 'local_onlinejudge'), 64, PARAM_INT));
    $temp->add(new admin_setting_configtext('local_onlinejudge/maxcpulimit', get_string('maxcpulimit', 'local_onlinejudge'), get_string('maxcpulimit_help', 'local_onlinejudge'), 10, PARAM_INT));
    $temp->add(new admin_setting_configtext('local_onlinejudge/ideonedelay', get_string('ideonedelay', 'local_onlinejudge'), get_string('ideonedelay_help', 'local_onlinejudge'), 10, PARAM_INT));

    $choices = onlinejudge_get_languages();
    $temp->add(new admin_setting_configselect('local_onlinejudge/defaultlanguage', get_string('defaultlanguage', 'local_onlinejudge'), get_string('defaultlanguage_help', 'local_onlinejudge'), '', $choices));

    $temp->add(new admin_setting_users_with_capability('local_onlinejudge/judgedcrashnotify', get_string('judgedcrashnotify', 'local_onlinejudge'), get_string('judgedcrashnotify_help', 'local_onlinejudge'), array(), 'moodle/site:config'));

    $ADMIN->add('localplugins', $temp);
}

