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
 * ideone.com judge engine
 *
 * @package   local_onlinejudge
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @developer Andrew Naguib
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . "/../../../../config.php");
require_once($CFG->dirroot . "/local/onlinejudge/judgelib.php");

use SphereEngine\Api\CompilersClientV4;
use SphereEngine\Api\SphereEngineResponseException;

class judge_sphere_engine extends judge_base {

    //TODO: update latest language list through Sphere Engine Compilers API
    protected static $supported_languages = array(7 => 'Ada (gnat-5.1.1, sphere-engine.com)', 13 => 'Assembler (nasm-2.11.05, sphere-engine.com)', 45 => 'Assembler (gcc-4.9.3, sphere-engine.com)', 104 => 'AWK (gawk) (fawk-4.1.1, sphere-engine.com)', 105 => 'AWK (mawk) (mawk-1.3.3, sphere-engine.com)', 28 => 'Bash (bash 4.3.33, sphere-engine.com)', 110 => 'bc (bc-1.06.95, sphere-engine.com)', 12 => 'Brainf**k (bff-1.0.6, sphere-engine.com)', 11 => 'C (gcc-5.1.1, sphere-engine.com)', 27 => 'C# (mono-4.0.2, sphere-engine.com)', 1 => 'C++ (gcc-5.1.1, sphere-engine.com)', 44 => 'C++0x (gcc-5.1.1, sphere-engine.com)', 34 => 'C99 strict (gcc-5.1.1, sphere-engine.com)', 14 => 'CLIPS (clips 6.24, sphere-engine.com)', 111 => 'Clojure (clojure 1.7.0, sphere-engine.com)', 118 => 'COBOL (open-cobol-1.1.0, sphere-engine.com)', 106 => 'COBOL 85 (tinycobol-0.65.9, sphere-engine.com)', 32 => 'Common Lisp (clisp) (clisp 2.49, sphere-engine.com)', 102 => 'D (dmd) (dmd-2.072.2, sphere-engine.com)', 36 => 'Erlang (erl-5.7.3, sphere-engine.com)', 124 => 'F# (fsharp-1.3, sphere-engine.com)', 107 => 'Forth (gforth-0.7.2, sphere-engine.com)', 5 => 'Fortran (gfortran-5.1.1, sphere-engine.com)', 114 => 'Go (gc-1.4, sphere-engine.com)', 121 => 'Groovy (groovy-2.4, sphere-engine.com)', 21 => 'Haskell (ghc-7.8, sphere-engine.com)', 16 => 'Icon (iconc 9.4.3, sphere-engine.com)', 9 => 'Intercal (c-intercal 28.0-r1, sphere-engine.com)', 10 => 'Java (jdk 8u51, sphere-engine.com)', 55 => 'Java7 (sun-jdk-1.7.0_10, sphere-engine.com)', 35 => 'JavaScript (rhino) (rhino-1.7.7, sphere-engine.com)', 112 => 'JavaScript (spidermonkey) (24.2.0, sphere-engine.com)', 26 => 'Lua (luac 7.2, sphere-engine.com)', 30 => 'Nemerle (ncc 1.2.0, sphere-engine.com)', 25 => 'Nice (nicec 0.9.13, sphere-engine.com)', 43 => 'Objective-C (gcc-5.1.1, sphere-engine.com)', 8 => 'Ocaml (ocamlopt 4.01.0, sphere-engine.com)', 22 => 'Pascal (fpc) (fpc 2.6.4+dfsg-6, sphere-engine.com)', 2 => 'Pascal (gpc) (gpc 20070904, sphere-engine.com)', 3 => 'Perl (perl6 2014.07,, sphere-engine.com)', 54 => 'Perl 6 (rakudo-2010.08, sphere-engine.com)', 29 => 'PHP (PHP 5.6.11-1, sphere-engine.com)', 19 => 'Pike (pike v7.8, sphere-engine.com)', 108 => 'Prolog (gnu) (prolog 1.4.5, sphere-engine.com)', 15 => 'Prolog (swi) (swi 7.2, sphere-engine.com)', 4 => 'Python (python 2.7.10, sphere-engine.com)', 116 => 'Python 3 (python 3.4.3+, sphere-engine.com)', 117 => 'R (R-3.2.2, sphere-engine.com)', 17 => 'Ruby (ruby-2.1.5, sphere-engine.com)', 39 => 'Scala (scala-2.11.7.final, sphere-engine.com)', 33 => 'Scheme (guile) (guile 2.0.11, sphere-engine.com)', 23 => 'Smalltalk (gst 3.2.4, sphere-engine.com)', 40 => 'SQL (sqlite3-3.8.7, sphere-engine.com)', 38 => 'Tcl (tclsh 8.6, sphere-engine.com)', 6 => 'Whitespace (wspace 0.3, sphere-engine.com)',);

    static function get_languages() {
        $langs = array();
        if (!self::is_available()) {
            return $langs;
        }
        foreach (self::$supported_languages as $langid => $name) {
            $langs[$langid . '-' . 'sphere_engine'] = $name;
        }
        return $langs;
    }

    /**
     * Whether the judge is available or not
     *
     * @return true for yes, false for no
     */
    static function is_available() {
        return true;
    }

    /**
     * Used to separate language name from the compiler name. Used for the syntax highlighter.
     * @param $compilerid
     * @return string
     */
    static function get_language_name($compilerid) {
        return strtolower(strtok(self::$supported_languages[$compilerid], ' '));
    }

    /**
     * Judge the current task
     *
     * @return updated task
     */
    function judge() {
        global $CFG;
        require_once($CFG->dirroot . "/local/onlinejudge/judge/sphere_engine/api/CompilersClientV4.php");
        require_once($CFG->dirroot . "/local/onlinejudge/judge/sphere_engine/api/SphereEngineConnectionException.php");
        require_once($CFG->dirroot . "/local/onlinejudge/judge/sphere_engine/api/SphereEngineResponseException.php");
        require_once($CFG->dirroot . "/local/onlinejudge/judge/sphere_engine/api/vendor/autoload.php");

        $task = &$this->task;

        $end_point = $task->var1;
        $access_token = $task->var2;

        // create client.
        $client = new CompilersClientV4($access_token, $end_point);


        $language = $this->language;
        $input = $task->input;

        // Get source code
        $fs = get_file_storage();
        $files = $fs->get_area_files(context_system::instance()->id, 'local_onlinejudge', 'tasks', $task->id, 'sortorder, timemodified', false);
        $source = '';
        foreach ($files as $file) {
            $source = $file->get_content();
            break;
        }

        $status_ideone = array(0 => ONLINEJUDGE_STATUS_PENDING, 11 => ONLINEJUDGE_STATUS_COMPILATION_ERROR, 12 => ONLINEJUDGE_STATUS_RUNTIME_ERROR, 13 => ONLINEJUDGE_STATUS_TIME_LIMIT_EXCEED, 15 => ONLINEJUDGE_STATUS_COMPILATION_OK, 17 => ONLINEJUDGE_STATUS_MEMORY_LIMIT_EXCEED, 19 => ONLINEJUDGE_STATUS_RESTRICTED_FUNCTIONS, 20 => ONLINEJUDGE_STATUS_INTERNAL_ERROR);

        // Begin REST API
        /**
         * function createSubmission create a paste.
         * @param user is the user name.
         * @param pass is the user's password.
         * @param source is the source code of the paste.
         * @param language is language identifier. these identifiers can be
         *     retrieved by using the getLanguages methods.
         * @param input is the data that will be given to the program on the stdin
         * @param run is the determines whether the source code should be executed.
         * @param private is the determines whether the paste should be private.
         *     Private pastes do not appear on the recent pastes page on ideone.com.
         *     Notice: you can only set submission's visibility to public or private through
         *     the API (you cannot set the user's visibility).
         * @return array(
         *         error => string
         *         link  => string
         *     )
         */
        try {
            $webid = $client->createSubmission($source, $language, $input, true, true);
            $delay = get_config('local_onlinejudge', 'sedelay');
            sleep($delay);  // ideone reject bulk access
            $submisison_id = $webid['id'];
            // Get sphere engine results
            while (1) {
                $submission_data = $client->getSubmission($submisison_id);
                sleep($delay);  // ideone reject bulk access. Always add delay between accesses
                if ($submission_data['result']['status']['code'] != 0 and $submission_data['executing'] == false) {
                    break;
                }
            }

            $details = $submission_data['result'];
            $task->stdout = $client->getSubmissionStream($submisison_id, 'output');;
            $task->stderr = $details['streams']['error'];
            $task->compileroutput = $details['streams']['cmpinfo'];
            $task->memusage = $details['memory'];
            $task->cpuusage = $details['time'];
            $task->infoteacher = get_string('seresultlink', 'local_onlinejudge', array('end_point' => $end_point, 'submission_id' => $submisison_id, 'access_token' => $access_token));
            $task->infostudent = get_string('selogo', 'local_onlinejudge');

            $task->status = $status_ideone[$details['status']['code']];

            if ($task->compileonly) {
                if ($task->status != ONLINEJUDGE_STATUS_COMPILATION_ERROR && $task->status != ONLINEJUDGE_STATUS_INTERNAL_ERROR) {
                    $task->status = ONLINEJUDGE_STATUS_COMPILATION_OK;
                }
            } else {
                if ($task->status == ONLINEJUDGE_STATUS_COMPILATION_OK) {
                    if ($task->cpuusage > $task->cpulimit) {
                        $task->status = ONLINEJUDGE_STATUS_TIME_LIMIT_EXCEED;
                    } else if ($task->memusage > $task->memlimit) {
                        $task->status = ONLINEJUDGE_STATUS_MEMORY_LIMIT_EXCEED;
                    } else {
                        $task->status = $this->diff();
                    }
                }
            }

            return $task;
        } catch (SphereEngineResponseException $e) {
            verbose($e);
        }
    }


}