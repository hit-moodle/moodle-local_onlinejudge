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
    set_config('name', $fromform->name, 'local_hub');
    set_config('hubenabled', 
            empty($fromform->enabled)?0:$fromform->enabled, 'local_hub');
    set_config('description', $fromform->desc, 'local_hub');
    set_config('contactname', $fromform->contactname, 'local_hub');
    set_config('contactemail', $fromform->contactemail, 'local_hub');
    set_config('maxwscourseresult', $fromform->maxwscourseresult, 'local_hub');
    set_config('maxcoursesperday', $fromform->maxcoursesperday, 'local_hub');
    set_config('searchfornologin', empty($fromform->searchfornologin)?0:1, 'local_hub');
    set_config('enablerssfeeds', 
            empty($fromform->enablerssfeeds)?0:$fromform->enablerssfeeds, 'local_hub');
    set_config('rsssecret',
            empty($fromform->rsssecret)?'':$fromform->rsssecret, 'local_hub');
    
    set_config('language', $fromform->lang, 'local_hub');

    set_config('password', 
            empty($fromform->password)?null:$fromform->password, 'local_hub');



    //display confirmation
    echo $OUTPUT->notification(get_string('settingsupdated', 'local_hub'), 'notifysuccess');
} else {
    $ojsettingsform->display();
}
echo $OUTPUT->footer();

