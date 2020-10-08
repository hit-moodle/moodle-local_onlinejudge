<?PHP
// This file is part of Moodle - https://moodle.org
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
 * NOTICE OF COPYRIGHT
 *
 *                      Online Judge for Moodle
 *        https://github.com/hit-moodle/moodle-local_onlinejudge
 *
 * Copyright (C) 2009 onwards
 *                      Sun Zhigang  http://sunner.cn
 *                      Andrew Naguib <andrew at fci helwan edu eg>
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details:
 *
 *          http://www.gnu.org/copyleft/gpl.html
 */

////////////////////////////////////////////////////////////////////////////////
//  Code fragment to define the plugin version etc.
//  This fragment is called by /admin/index.php
////////////////////////////////////////////////////////////////////////////////

$plugin->version = 2020100401;
$plugin->requires = 2017111300;  // Requires this Moodle version
$plugin->component = 'assignfeedback_onlinejudge';
$plugin->maturity = MATURITY_BETA;
$plugin->release = "v3.2.0-r2"; // User-friendly version number
$plugin->dependencies = array('local_onlinejudge' => 2020100700);

