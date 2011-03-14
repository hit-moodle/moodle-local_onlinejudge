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
 * Configuration of this onlinejudge2 installation
 *
 * @package   local_onlinejudge2
 * @copyright 2011 Yu Zhan <yuzhanlaile@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') or die();

/**
 * Full path to the git clone of Moodle. Used by parse-core.php
 */
define('onlinejudge2_REPO_MOODLE', $CFG->dataroot . '/onlinejudge2/repos/moodle');

/**
 * Full path to the git clone of legacy language translation files.
 * User by parse-lang.php to get the history of strings into database.
 */
define('onlinejudge2_REPO_LANGS', $CFG->dataroot . '/onlinejudge2/repos/moodle-lang');

/**
 * Full path to the directory where onlinejudge2 will generate language packs
 */
define('onlinejudge2_EXPORT_DIR', $CFG->dataroot . '/onlinejudge2/export');

/**
 * Full path to the directory where onlinejudge2 will generate language ZIP packs
 * This can be synced with the download server.
 */
define('onlinejudge2_EXPORT_ZIP_DIR', $CFG->dataroot . '/onlinejudge2/export-zip');

/**
 * Full path to the directory where onlinejudge2 will generate installer strings
 */
define('onlinejudge2_EXPORT_INSTALLER_DIR', $CFG->dataroot . '/onlinejudge2/export-install');

/**
 * Full path to git
 */
define('onlinejudge2_PATH_GIT', '/usr/local/bin/git');
