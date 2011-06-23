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
 * ONLINEJUDGE2 home page
 *
 * @package   local-onlinejudge
 * @copyright 2011 Yu Zhan <yuzhanlaile@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once(dirname(__FILE__).'/locallib.php');

require_login(SITEID, false);

global $CFG, $PAGE;

$PAGE->set_pagelayout('standard');
$PAGE->set_url('/local/onlinejudge/index.php');
$PAGE->set_title('Onlinejudge2');
$PAGE->set_heading('Onlinejudge2');

$output = $PAGE->get_renderer('local_onlinejudge');

/// Output starts here
echo $output->header();
echo $output->heading(get_string('onlinejudge', 'local_onlinejudge'), 1);
echo $output->container(get_string('about', 'local_onlinejudge'));

echo $output->heading(get_string('privileges', 'local_onlinejudge'));

$caps = array();
if (has_capability('local/onlinejudge:manage', get_system_context())) {
    $caps[] = get_string('onlinejudge:manage', 'local_onlinejudge');
}
if (has_capability('local/onlinejudge:commit', get_system_context())) {
    $caps[] = get_string('onlinejudge:commit', 'local_onlinejudge');
}
if (empty($caps)) {
    get_string('privilegesnone', 'local_onlinejudge');
} 
else {
    $caps = '<li>' . implode("</li>\n<li>", $caps) . '</li>';
    echo html_writer::tag('ul', $caps);
}

echo $output->footer();
?>



