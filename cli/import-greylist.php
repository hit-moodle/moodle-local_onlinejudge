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
 * Imports greylisted strings from text file
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

list($options, $unrecognized) = cli_get_params(array('greylist'=>false, 'help'=>false), array('h'=>'help'));

if ($options['help'] or !$options['greylist']) {
    echo 'Usage: '.basename(__FILE__).' --greylist=greylist.txt' . PHP_EOL;
    exit(0);
}

$DB->delete_records('onlinejudge2_greylist'); // truncate the table
$greylist = fopen($options['greylist'], 'r');
while (($string = fgets($greylist, 514)) !== false) {
    $matches = array();
    if (preg_match('/^\[(.+),(.+)\]$/', $string, $matches)) {
        $item = new stdClass();
        $item->branch = mlang_version::MOODLE_20;
        $item->stringid = $matches[1];
        $item->component = $matches[2];
        try {
            $DB->insert_record('onlinejudge2_greylist', $item, false, true);
        }
        catch (dml_write_exception $e) {
            echo $e->getMessage() . ' ' . $string . PHP_EOL;
        }
    }
}
fclose($greylist);
