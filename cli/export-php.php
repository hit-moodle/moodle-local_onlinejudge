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
 * Exports the most recent version of Moodle strings into Moodle PHP format
 *
 * @package   local_onlinejudge2
 * @copyright 2011 Yu Zhan <yuzhanlaile@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->dirroot . '/local/onlinejudge2/cli/config.php');
require_once($CFG->dirroot . '/local/onlinejudge2/mlanglib.php');

// Let us get an information about existing components
$sql = "SELECT branch,lang,component,COUNT(stringid) AS numofstrings
          FROM {onlinejudge2_repository}
         WHERE deleted=0
      GROUP BY branch,lang,component
      ORDER BY branch,lang,component";
$rs = $DB->get_recordset_sql($sql);
$tree = array();    // [branch][language][component] => numofstrings
foreach ($rs as $record) {
    $tree[$record->branch][$record->lang][$record->component] = $record->numofstrings;
}
$rs->close();

remove_dir(onlinejudge2_EXPORT_DIR, true);
foreach ($tree as $vercode => $languages) {
    $version = mlang_version::by_code($vercode);
    foreach ($languages as $langcode => $components) {
        if ($langcode == 'en') {
            continue;
        }
        foreach ($components as $componentname => $unused) {
            $component = mlang_component::from_snapshot($componentname, $langcode, $version);
            if ($component->has_string()) {
                $file = onlinejudge2_EXPORT_DIR . '/' . $version->dir . '/' . $langcode . '/' . $component->get_phpfile_location(false);
                if (!file_exists(dirname($file))) {
                    mkdir(dirname($file), 0755, true);
                }
                echo "$file\n";
                $component->export_phpfile($file);
            }
            $component->clear();
        }
    }
}
echo "DONE\n";
