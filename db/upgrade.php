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
 * onlinejudge2 upgrade scripts
 *
 * @package   local_onlinejudge2
 * @copyright 2011 Yu Zhan <yuzhanlaile@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/*
 * 版本更新文件
 */
function xmldb_local_onlinejudge2_upgrade($oldversion=0) {
    global $CFG, $DB, $OUTPUT;

    $dbman = $DB->get_manager();
    $result = true;

    if ($oldversion < 2010090103) {
        $dbman->install_one_table_from_xmldb_file($CFG->dirroot.'/local/onlinejudge2/db/install.xml', 'onlinejudge_task');
        upgrade_plugin_savepoint(true, 2010090103, 'local', 'onlinejudge2');
    }

    if ($oldversion < 2010090107) {
        $dbman->install_one_table_from_xmldb_file($CFG->dirroot.'/local/onlinejudge2/db/install.xml', 'onlinejudge_task');
        upgrade_plugin_savepoint(true, 2010090107, 'local', 'onlinejudge2');
    }

    if ($oldversion < 2010110400) {
        $dbman->install_one_table_from_xmldb_file($CFG->dirroot.'/local/onlinejudge2/db/install.xml', 'onlinejudge_task');
        upgrade_plugin_savepoint(true, 2010110400, 'local', 'onlinejudge2');
    }

    if ($oldversion < 2011010600) {
        $dbman->install_one_table_from_xmldb_file($CFG->dirroot.'/local/onlinejudge2/db/install.xml', 'onlinejudge_task');
        upgrade_plugin_savepoint(true, 2011010600, 'local', 'onlinejudge2');
    }

    return $result;
}
