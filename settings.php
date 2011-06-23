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
    $ADMIN->add('localplugins', new admin_externalpage('local_onlinejudge',
            get_string('pluginname', 'local_onlinejudge'),
            new moodle_url('/local/onlinejudge/admin/settings.php')));
}

