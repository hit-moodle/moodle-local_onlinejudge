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
 * Capability definitions for onlinejudge2 local plugin
 *
 * @package   local_onlinejudge2
 * @copyright 2010 Yu Zhan <yuzhanlaile@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    // Ability to set-up Onlinejudge2
    'local/onlinejudge2:manage' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array()
    ),
    
    // Ability to view the tasks
    'local/onlinejudge2:view' => array(
        'captype' => 'view',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'user' => CAP_ALLOW,
        )
    ),

    // Ability to query the task.
    'local/onlinejudge2:query' => array(
        'captype' => 'query',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'user' => CAP_ALLOW,
        )
    ),

    // Ability to delete the task
    'local/onlinejudge2:delete' => array(
        'captype' => 'delete',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array()
    ),

    // Ability to ...
    'local/onlinejudge2:add' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array()
    ),


);
