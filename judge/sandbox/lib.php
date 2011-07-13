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
 * Sandbox judge engine
 *
 * @package   local_onlinejudge
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__)."/../../../../config.php");
require_once($CFG->dirroot."/local/onlinejudge/judgelib.php");

define('SANDBOX_SAND', $CFG->dirroot.'/local/onlinejudge/judge/sandbox/sand/sand');

class judge_sandbox extends judge_base {
    protected static $supported_languages = array(
        'c' => 'gcc -D_MOODLE_ONLINE_JUDGE_ -Wall -static -o %DEST% %SOURCE% -lm',
        'c_warn2err' => 'gcc -D_MOODLE_ONLINE_JUDGE_ -Wall -Werror -static -o %DEST% %SOURCE% -lm',
        'cpp' => 'g++ -D_MOODLE_ONLINE_JUDGE_ -Wall -static -o %DEST% %SOURCE% -lm',
        'cpp_warn2err' => 'g++ -D_MOODLE_ONLINE_JUDGE_ -Wall -Werror -static -o %DEST% %SOURCE% -lm'
    );

    static function get_languages() {
        $langs = array();
        if (!self::is_available()) {
            return $langs;
        }
        foreach (self::$supported_languages as $key => $value) {
            $langs[$key.'_sandbox'] = get_string('lang'.$key.'_sandbox', 'local_onlinejudge');
        }
        return $langs;
    }

    protected function compile($files) {
    	global $CFG;

        $search = array('%SOURCE%', '%DEST%');
        $replace = array(implode(' ', $files), $this->get_temp_dir().'/a.out');
        // construct compiler command
        $command = str_replace($search, $replace, self::$supported_languages[$this->language]);

        // run compiler and redirect stderr to stdout
        $output = array();
        $return = 0;
        exec($command.' 2>&1', $output, $return);

        $this->task->compileroutput = str_replace($this->get_temp_dir().'/', '', implode("\n", $output));
        if ($return != 0) {
            // TODO: if the command can not be executed, it should be internal error
            $this->task->status = ONLINEJUDGE_STATUS_COMPILATION_ERROR;
        } else {
            $this->task->status = ONLINEJUDGE_STATUS_COMPILATION_OK;
        }

        return $replace[1];
    }

    /**
     * Judge the current task
     *
     * @return updated task
     */
    function judge() {

        $files = $this->create_temp_files();

        $binfile = $this->compile($files);

        if ($this->task->status == ONLINEJUDGE_STATUS_COMPILATION_OK && !$this->task->compileonly) {
            $this->run_in_sandbox($binfile);
        }

        return $this->task;
    }

    protected function run_in_sandbox($binfile) {
    	global $CFG;

        $rval_status = array (
                ONLINEJUDGE_STATUS_PENDING,
                ONLINEJUDGE_STATUS_ACCEPTED,
                ONLINEJUDGE_STATUS_RESTRICTED_FUNCTIONS,
                ONLINEJUDGE_STATUS_MEMORY_LIMIT_EXCEED,
                ONLINEJUDGE_STATUS_OUTPUT_LIMIT_EXCEED,
                ONLINEJUDGE_STATUS_TIME_LIMIT_EXCEED,
                ONLINEJUDGE_STATUS_RUNTIME_ERROR,
                ONLINEJUDGE_STATUS_ABNORMAL_TERMINATION,
                ONLINEJUDGE_STATUS_INTERNAL_ERROR
        );

        $sand = SANDBOX_SAND;
        if (!is_executable($sand)){
            throw new onlinejudge_exception('cannotrunsand');
        }

        $sand .= ' -l cpu='.(($this->task->cpulimit)*1000).' -l memory='.$this->task->memlimit.' -l disk=512000 '.$binfile;

        // run it in sandbox!
        $descriptorspec = array(
            0 => array('pipe', 'r'),  // stdin is a pipe that the child will read from
            1 => array('file', $binfile.'.out', 'w'),  // stdout is a file that the child will write to
            2 => array('file', $binfile.'.err', 'w') // stderr is a file that the child will write to
        );
        $proc = proc_open($sand, $descriptorspec, $pipes);
        if (!is_resource($proc)) {
            throw new onlinejudge_exception('sandboxerror');
        }

        // $pipes now looks like this:
        // 0 => writeable handle connected to child stdin
        // 1 => readable handle connected to child stdout
        // Any error output will be appended to $exec_file.err
        fwrite($pipes[0], $this->task->input);
        fclose($pipes[0]);
        $return_value = proc_close($proc);

        $this->task->stdout = file_get_contents($binfile.'.out');
        $this->task->stderr = file_get_contents($binfile.'.err');

        if ($return_value == 255) {
            throw new onlinejudge_exception('sandboxerror', $return_value);
        } else if ($return_value >= 2) {
            $this->task->status = $rval_status[$return_value];
            return;
        } else if ($return_value == 0) {
            throw new onlinejudge_exception('sandboxerror', $return_value);
        }

        $this->task->status = $this->diff();
    }

    /**
     * Return the infomation of the compiler of specified language
     *
     * @param string $language ID of the language
     * @return compiler information or null
     */
    static function get_compiler_info($language) {
        $language = substr($language, 0, strrpos($language, '_'));
        return self::$supported_languages[$language];
    }

    /**
     * Whether the judge is avaliable
     *
     * @return true for yes, false for no
     */
    static function is_available() {
        global $CFG;

        if ($CFG->ostype == 'WINDOWS') {
            return false;
        } else if (!is_executable(SANDBOX_SAND)) {
            return false;
        }

        return true;
    }
}

