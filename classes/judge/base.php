<?php
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

/**
 * Judge base class
 *
 * @package   local_onlinejudge
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_onlinejudge\judge;

defined('MOODLE_INTERNAL') || die();

class base {

    // object of the task
    protected $task;

    // language id without judge id
    protected $language;

    function __construct($task) {
        $this->task = $task;
        $this->language = substr($this->task->language, 0, strpos($this->task->language, '-'));
    }

    /**
     * Return an array of programming languages supported by this judge
     *
     * The array key must be the language's ID, such as c_sandbox, python_ideone.
     * The array value must be a human-readable name of the language, such as 'C (local)', 'Python (ideone.com)'
     */
    static function get_languages() {
        return array();
    }

    /**
     * Put options into task
     *
     * @param object options
     * @return throw exceptions on error
     */
    static function parse_options($options, & $task) {
        $options = (array)$options;
        // only common options are parsed here.
        // special options should be parsed by childclass
        foreach ($options as $key => $value) {
            if ($key == 'memlimit' and $value > 1024 * 1024 * get_config('local_onlinejudge', 'maxmemlimit')) {
                $value = 1024 * 1024 * get_config('local_onlinejudge', 'maxmemlimit');
            }
            if ($key == 'cpulimit' and $value > get_config('local_onlinejudge', 'maxcpulimit')) {
                $value = get_config('local_onlinejudge', 'maxcpulimit');
            }
            $task->$key = $value;
        }
    }

    /**
     * Return the infomation of the compiler of specified language
     *
     * @param string $language ID of the language
     * @return compiler information or null
     */
    static function get_compiler_info($language) {
        return array();
    }

    /**
     * Whether the judge is avaliable
     *
     * @return true for yes, false for no
     */
    static function is_available() {
        return false;
    }

    /**
     * Judge the current task
     *
     * @return bool [updated task or false]
     */
    function judge() {
        return false;
    }

    /**
     * Compare the stdout of program and the output of testcase
     */
    protected function diff() {
        $task = &$this->task;

        // convert data into UTF-8 charset if possible
        $task->stdout = $this->convert_to_utf8($task->stdout);
        $task->stderr = $this->convert_to_utf8($task->stderr);
        $task->output = $this->convert_to_utf8($task->output);

        // trim tailing return chars which are meaning less
        $task->output = rtrim($task->output, "\r\n");
        $task->stdout = rtrim($task->stdout, "\r\n");

        if (strcmp($task->output, $task->stdout) == 0) return ONLINEJUDGE_STATUS_ACCEPTED; else {
            $tokens = array();
            $tok = strtok($task->output, " \n\r\t");
            while ($tok !== false) {
                $tokens[] = $tok;
                $tok = strtok(" \n\r\t");
            }

            $tok = strtok($task->stdout, " \n\r\t");
            foreach ($tokens as $anstok) {
                if ($tok === false || $tok !== $anstok) return ONLINEJUDGE_STATUS_WRONG_ANSWER;
                $tok = strtok(" \n\r\t");
            }
            if ($tok !== false) {
                return ONLINEJUDGE_STATUS_WRONG_ANSWER;
            }
            return ONLINEJUDGE_STATUS_PRESENTATION_ERROR;
        }
    }

    /**
     * If string is not encoded in UTF-8, convert it into utf-8 charset
     */
    protected function convert_to_utf8($string) {
        $localwincharset = get_string('localewincharset', 'langconfig');
        if (!empty($localwincharset) and !mb_check_encoding($string, 'UTF-8') and mb_check_encoding($string, $localwincharset)) {
            return core_text::convert($string, $localwincharset);
        } else {
            return $string;
        }
    }

    /**
     * Save files of current task to a temp directory
     *
     * @return array of the full path of saved files
     */
    protected function create_temp_files() {
        $dstfiles = array();

        $fs = get_file_storage();
        $files = $fs->get_area_files(\context_system::instance()->id, 'local_onlinejudge', 'tasks', $this->task->id, 'sortorder', false);
        foreach ($files as $file) {
            $path = onlinejudge_get_temp_dir() . $file->get_filepath();
            $fullpath = $path . $file->get_filename();
            if (!check_dir_exists($path)) {
                throw new \moodle_exception('errorcreatingdirectory', '', '', $path);
            }
            $file->copy_content_to($fullpath);
            $dstfiles[] = $fullpath;
        }

        return $dstfiles;
    }
}