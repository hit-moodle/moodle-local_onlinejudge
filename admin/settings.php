<?
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
 * @package   local_onlinejudge2
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/onlinejudge2/admin/forms.php');

admin_externalpage_setup('local_onlinejudge2');

$ojsettingsform = new onlinejudge2_settings_form();
$fromform = $ojsettingsform->get_data();

echo $OUTPUT->header();

// TODO: check environments

if (!empty($fromform) and confirm_sesskey()) {

    //Save settings
    set_config('maxmemlimit', $fromform->maxmemlimit, 'local_onlinejudge2');
    set_config('maxcpulimit', $fromform->maxcpulimit, 'local_onlinejudge2');
    set_config('ideonedelay', $fromform->ideonedelay, 'local_onlinejudge2');

    //display confirmation
    echo $OUTPUT->notification(get_string('settingsupdated', 'local_onlinejudge2'), 'notifysuccess');
    $ojsettingsform->display();
} else {
    $data->maxmemlimit = get_config('local_onlinejudge2', 'maxmemlimit');
    $data->maxcpulimit = get_config('local_onlinejudge2', 'maxcpulimit');
    $data->ideonedelay = get_config('local_onlinejudge2', 'ideonedelay');
    $ojsettingsform->set_data($data);
    $ojsettingsform->display();
}

echo $OUTPUT->footer();

