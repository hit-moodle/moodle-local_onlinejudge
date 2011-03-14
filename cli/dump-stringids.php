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
 * Prints the list of all currently known strings
 *
 * @package   local_onlinejudge2
 * @copyright 2011 Yu Zhan <yuzhanlaile@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->dirroot . '/local/onlinejudge2/cli/config.php');
require_once($CFG->dirroot . '/local/onlinejudge2/mlanglib.php');
require_once($CFG->libdir.'/clilib.php');

list($options, $unrecognized) = cli_get_params(array('branch'=>false, 'help'=>false), array('h'=>'help'));

if ($options['help'] or !$options['branch']) {
    echo 'Usage: '.basename(__FILE__).' --branch=MOODLE_XX_STABLE' . PHP_EOL;
    exit(0);
}

$version = mlang_version::by_branch($options['branch']);

if (is_null($version)) {
    echo 'Unknown branch' . PHP_EOL;
    exit(1);
}

// Let us get an information about existing components on the given branch
$sql = "SELECT DISTINCT component
          FROM {onlinejudge2_repository}
         WHERE deleted=0 AND lang='en' AND branch=?
      ORDER BY component";

$rs = $DB->get_recordset_sql($sql, array($version->code));
$components = array();
foreach ($rs as $record) {
    $components[$record->component] = true;
}
$rs->close();

foreach (array_keys($components) as $componentname) {
    $component = mlang_component::from_snapshot($componentname, 'en', $version);
    foreach ($component->get_iterator() as $string) {
        echo '['.$string->id.','.$component->name.']'.PHP_EOL;
    }
    $component->clear();
}
