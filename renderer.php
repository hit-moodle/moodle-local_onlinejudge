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
 * online judge render class
 *
 * @package   local_onlinejudge
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/judgelib.php');

/**
 * OJ renderer class
 */
class local_onlinejudge_renderer extends plugin_renderer_base {

    /**
     * Renders the online judge status
     *
     * @return string
     */
    function judgestatus() {
        global $DB;

        $a = new object();
        $a->judged = $DB->count_records('onlinejudge_tasks');
        $a->pending = $DB->count_records('onlinejudge_tasks', array('status' => ONLINEJUDGE_STATUS_PENDING));

        return get_string('judgestatus', 'local_onlinejudge', $a);
    }

    /**
     * Renders the current user's statistics
     *
     * @todo finish it
     * @return string
     */
    function mystatistics() {
        return '';
    }
}

