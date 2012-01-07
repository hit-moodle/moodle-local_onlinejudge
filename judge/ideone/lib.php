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
 * ideone.com judge engine
 *
 * @package   local_onlinejudge
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__)."/../../../../config.php");
require_once($CFG->dirroot."/local/onlinejudge/judgelib.php");

class judge_ideone extends judge_base
{
    //TODO: update latest language list through ideone API
    protected static $supported_languages = array(
        7   => 'Ada (gnat-4.3.2, ideone.com)',
        13  => 'Assembler (nasm-2.07, ideone.com)',
        45  => 'Assembler (gcc-4.3.4, ideone.com)',
        104 => 'AWK (gawk) (gawk-3.1.6, ideone.com)',
        105 => 'AWK (mawk) (mawk-1.3.3, ideone.com)',
        28  => 'Bash (bash 4.0.35, ideone.com)',
        110 => 'bc (bc-1.06.95, ideone.com)',
        12  => 'Brainf**k (bff-1.0.3.1, ideone.com)',
        11  => 'C (gcc-4.3.4, ideone.com)',
        27  => 'C# (mono-2.8, ideone.com)',
        1   => 'C++ (gcc-4.3.4, ideone.com)',
        44  => 'C++0x (gcc-4.5.1, ideone.com)',
        34  => 'C99 strict (gcc-4.3.4, ideone.com)',
        14  => 'CLIPS (clips 6.24, ideone.com)',
        111 => 'Clojure (clojure 1.1.0, ideone.com)',
        118 => 'COBOL (open-cobol-1.0, ideone.com)',
        106 => 'COBOL 85 (tinycobol-0.65.9, ideone.com)',
        32  => 'Common Lisp (clisp) (clisp 2.47, ideone.com)',
        102 => 'D (dmd) (dmd-2.042, ideone.com)',
        36  => 'Erlang (erl-5.7.3, ideone.com)',
        124 => 'F# (fsharp-2.0.0, ideone.com)',
        123 => 'Factor (factor-0.93, ideone.com)',
        125 => 'Falcon (falcon-0.9.6.6, ideone.com)',
        107 => 'Forth (gforth-0.7.0, ideone.com)',
        5   => 'Fortran (gfortran-4.3.4, ideone.com)',
        114 => 'Go (gc-2010-07-14, ideone.com)',
        121 => 'Groovy (groovy-1.7, ideone.com)',
        21  => 'Haskell (ghc-6.8.2, ideone.com)',
        16  => 'Icon (iconc 9.4.3, ideone.com)',
        9   => 'Intercal (c-intercal 28.0-r1, ideone.com)',
        10  => 'Java (sun-jdk-1.6.0.17, ideone.com)',
        35  => 'JavaScript (rhino) (rhino-1.6.5, ideone.com)',
        112 => 'JavaScript (spidermonkey) (spidermonkey-1.7, ideone.com)',
        26  => 'Lua (luac 5.1.4, ideone.com)',
        30  => 'Nemerle (ncc 0.9.3, ideone.com)',
        25  => 'Nice (nicec 0.9.6, ideone.com)',
        122 => 'Nimrod (nimrod-0.8.8, ideone.com)',
        43  => 'Objective-C (gcc-4.5.1, ideone.com)',
        8   => 'Ocaml (ocamlopt 3.10.2, ideone.com)',
        119 => 'Oz (mozart-1.4.0, ideone.com)',
        22  => 'Pascal (fpc) (fpc 2.2.0, ideone.com)',
        2   => 'Pascal (gpc) (gpc 20070904, ideone.com)',
        3   => 'Perl (perl 5.12.1, ideone.com)',
        54  => 'Perl 6 (rakudo-2010.08, ideone.com)',
        29  => 'PHP (php 5.2.11, ideone.com)',
        19  => 'Pike (pike 7.6.86, ideone.com)',
        108 => 'Prolog (gnu) (gprolog-1.3.1, ideone.com)',
        15  => 'Prolog (swi) (swipl 5.6.64, ideone.com)',
        4   => 'Python (python 2.6.4, ideone.com)',
        116 => 'Python 3 (python-3.1.2, ideone.com)',
        117 => 'R (R-2.11.1, ideone.com)',
        17  => 'Ruby (ruby-1.9.2, ideone.com)',
        39  => 'Scala (scala-2.8.0.final, ideone.com)',
        33  => 'Scheme (guile) (guile 1.8.5, ideone.com)',
        23  => 'Smalltalk (gst 3.1, ideone.com)',
        40  => 'SQL (sqlite3-3.7.3, ideone.com)',
        38  => 'Tcl (tclsh 8.5.7, ideone.com)',
        62  => 'Text (text 6.10, ideone.com)',
        115 => 'Unlambda (unlambda-2.0.0, ideone.com)',
        101 => 'Visual Basic .NET (mono-2.4.2.3, ideone.com)',
        6   => 'Whitespace (wspace 0.3, ideone.com)',
    );

    static function get_languages() {
    	$langs = array();
        if (!self::is_available()) {
            return $langs;
        }
        foreach (self::$supported_languages as $langid => $name) {
            $langs[$langid.'_ideone'] = $name;
        }
        return $langs;
    }

    /**
     * Judge the current task
     *
     * @return updated task
     */
    function judge() {

        $task = &$this->task;

    	// create client.
        $client = new SoapClient("http://ideone.com/api/1/service.wsdl");

    	$user = $task->var1;
    	$pass = $task->var2;
        $language = $this->language;
        $input = $task->input;

        // Get source code
        $fs = get_file_storage();
        $files = $fs->get_area_files(get_context_instance(CONTEXT_SYSTEM)->id, 'local_onlinejudge', 'tasks', $task->id, 'sortorder, timemodified', false);
        $source = '';
        foreach ($files as $file) {
            $source = $file->get_content();
            break;
        }

        $status_ideone = array(
            0   => ONLINEJUDGE_STATUS_PENDING,
            11  => ONLINEJUDGE_STATUS_COMPILATION_ERROR,
            12  => ONLINEJUDGE_STATUS_RUNTIME_ERROR,
            13  => ONLINEJUDGE_STATUS_TIME_LIMIT_EXCEED,
            15  => ONLINEJUDGE_STATUS_COMPILATION_OK,
            17  => ONLINEJUDGE_STATUS_MEMORY_LIMIT_EXCEED,
            19  => ONLINEJUDGE_STATUS_RESTRICTED_FUNCTIONS,
            20  => ONLINEJUDGE_STATUS_INTERNAL_ERROR
        );

        // Begin soap
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
        $webid = $client->createSubmission($user, $pass, $source, $language, $input, true, true);
        $delay = get_config('local_onlinejudge', 'ideonedelay');
        sleep($delay);  // ideone reject bulk access

        if ($webid['error'] == 'OK') {
            $link = $webid['link'];
        } else {
            throw new onlinejudge_exception('ideoneerror', $webid['error']);
        }

        // Get ideone results
        while (1) {
            $status = $client->getSubmissionStatus($user, $pass, $link);
            sleep($delay);  // ideone reject bulk access. Always add delay between accesses
            if($status['status'] == 0) {
                break;
            }
        }

        $details = $client->getSubmissionDetails($user,$pass,$link,false,false,true,true,true);
        $task->stdout = $details['output'];
        $task->stderr = $details['stderr'];
        $task->compileroutput = $details['cmpinfo'];
        $task->memusage = $details['memory'] * 1024;
        $task->cpuusage = $details['time'];
        $task->infoteacher = get_string('ideoneresultlink', 'local_onlinejudge', $link);
        $task->infostudent = get_string('ideonelogo', 'local_onlinejudge');

        $task->status = $status_ideone[$details['result']];

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
    }

    /**
     * Whether the judge is avaliable
     *
     * @return true for yes, false for no
     */
    static function is_available() {
        return true;
    }
}

