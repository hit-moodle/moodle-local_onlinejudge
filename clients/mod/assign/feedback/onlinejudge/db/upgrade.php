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
 * Upgrade database
 *
 * @package   local_onlinejudge
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

function xmldb_assignment_onlinejudge_upgrade($oldversion=0) {

    global $CFG, $THEME, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2010070400) {
        $OUTPUT->notification('You MUST upgrade to the latest onlinejudge for moodle 1.9.x in moodle 1.9.x first. Download it from https://github.com/hit-moodle/onlinejudge');
        return false;
    }

    if ($oldversion < 2011060301) {
        // Allow upload one file
        $DB->set_field('assignment', 'var1', 1, array('assignmenttype' => 'onlinejudge'));

        // Define field ideoneuser to be added to assignment_oj
        $table = new xmldb_table('assignment_oj');
        $field = new xmldb_field('ideoneuser', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'ratiope');
        // Conditionally launch add field ideoneuser
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field ideonepass to be added to assignment_oj
        $table = new xmldb_table('assignment_oj');
        $field = new xmldb_field('ideonepass', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'ideoneuser');
        // Conditionally launch add field ideonepass
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // define table assignment_oj_results to be dropped
        $table = new xmldb_table('assignment_oj_results');
        // conditionally launch drop table for assignment_oj_results
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Define table assignment_oj_tests to be renamed to assignment_oj_testcases
        $table = new xmldb_table('assignment_oj_tests');
        // Launch rename table for assignment_oj_tests
        $dbman->rename_table($table, 'assignment_oj_testcases');

        // define table assignment_oj_submissions to be dropped
        $table = new xmldb_table('assignment_oj_submissions');
        // conditionally launch drop table for assignment_oj_submissions
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Define table assignment_oj_submissions to be created
        $table = new xmldb_table('assignment_oj_submissions');
        // Adding fields to table assignment_oj_submissions
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('submission', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('testcase', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('task', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('latest', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1');

        // Adding keys to table assignment_oj_submissions
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('submission', XMLDB_KEY_FOREIGN, array('submission'), 'assignment_submissions', array('id'));
        $table->add_key('testcase', XMLDB_KEY_FOREIGN, array('testcase'), 'assignment_oj_testcases', array('id'));
        // Adding indexes to table assignment_oj_submissions
        $table->add_index('latest', XMLDB_INDEX_NOTUNIQUE, array('latest'));
        // Conditionally launch create table for assignment_oj_submissions
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // onlinejudge savepoint reached
        upgrade_plugin_savepoint(true, 2011060301, 'assignment', 'onlinejudge');
    }

    if ($oldversion < 2011060500) {
        // Set var4 to 1 which makes the new onlinejudge work
        $sql = 'UPDATE {assignment}
                SET var4 = \'1\'
                WHERE assignmenttype = \'onlinejudge\'';
        $DB->execute($sql);

        // onlinejudge savepoint reached
        upgrade_plugin_savepoint(true, 2011060500, 'assignment', 'onlinejudge');
    }

    if ($oldversion < 2011060700) {

        // migrate input and output files
        $fs = get_file_storage();

        $sqlfrom = "FROM {assignment_oj_testcases} t
                    JOIN {assignment} a ON a.id = t.assignment
                    JOIN {modules} m ON m.name = 'assignment'
                    JOIN {course_modules} cm ON (cm.module = m.id AND cm.instance = a.id)";

        $rs = $DB->get_recordset_sql("SELECT t.id, t.assignment, a.course, cm.id AS cmid, t.inputfile, t.outputfile $sqlfrom WHERE t.usefile = 1 ORDER BY a.course, t.assignment");

        if ($rs->valid()) {
            foreach ($rs as $testcase) {
                $srccontext = get_context_instance(CONTEXT_COURSE, $testcase->course);
                $dstcontext = get_context_instance(CONTEXT_MODULE, $testcase->cmid);

                $file = $fs->get_file($srccontext->id, 'course', 'legacy', 0, '/'.dirname($testcase->inputfile).'/', basename($testcase->inputfile));
                if ($file) {
                    $file_record = array('contextid'=>$dstcontext->id, 'component'=>'mod_assignment', 'filearea'=>'onlinejudge_input', 'itemid'=>$testcase->id, 'filepath'=>'/', 'filename'=>basename($testcase->inputfile));
                    $fs->create_file_from_storedfile($file_record, $file);
                }

                $file = $fs->get_file($srccontext->id, 'course', 'legacy', 0, '/'.dirname($testcase->outputfile).'/', basename($testcase->outputfile));
                if ($file) {
                    $file_record = array('contextid'=>$dstcontext->id, 'component'=>'mod_assignment', 'filearea'=>'onlinejudge_output', 'itemid'=>$testcase->id, 'filepath'=>'/', 'filename'=>basename($testcase->outputfile));
                    $fs->create_file_from_storedfile($file_record, $file);
                }
            }
        }

        // Define field sortorder to be added to assignment_oj_testcases
        $table = new xmldb_table('assignment_oj_testcases');
        $field = new xmldb_field('sortorder', XMLDB_TYPE_INTEGER, '5', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'subgrade');
        // Conditionally launch add field sortorder
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Define index sortorder (not unique) to be added to assignment_oj_testcases
        $table = new xmldb_table('assignment_oj_testcases');
        $index = new xmldb_index('sortorder', XMLDB_INDEX_NOTUNIQUE, array('sortorder'));
        // Conditionally launch add index sortorder
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        // Changing nullability of field input on table assignment_oj_testcases to null
        $table = new xmldb_table('assignment_oj_testcases');
        $field = new xmldb_field('input', XMLDB_TYPE_TEXT, 'small', null, null, null, null, 'assignment');
        // Launch change of nullability for field input
        $dbman->change_field_notnull($table, $field);
        // Changing nullability of field output on table assignment_oj_testcases to null
        $table = new xmldb_table('assignment_oj_testcases');
        $field = new xmldb_field('output', XMLDB_TYPE_TEXT, 'small', null, null, null, null, 'input');
        // Launch change of nullability for field output
        $dbman->change_field_notnull($table, $field);
        // Define field inputfile to be dropped from assignment_oj_testcases
        $table = new xmldb_table('assignment_oj_testcases');
        $field = new xmldb_field('inputfile');
        // Conditionally launch drop field inputfile
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        // Define field outputfile to be dropped from assignment_oj_testcases
        $table = new xmldb_table('assignment_oj_testcases');
        $field = new xmldb_field('outputfile');
        // Conditionally launch drop field outputfile
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // onlinejudge savepoint reached
        upgrade_plugin_savepoint(true, 2011060700, 'assignment', 'onlinejudge');
    }

    if ($oldversion < 2011062400) {

        $ideone_langs = array(
            'ada_ideone'                     => 7,
            'assembler_ideone'               => 13,
            'awk_gawk_ideone'                => 104,
            'awk_mawk_ideone'                => 105,
            'bash_ideone'                    => 28,
            'bc_ideone'                      => 110,
            'brainfxxk_ideone'               => 12,
            'c_ideone'                       => 11,
            'csharp_ideone'                  => 27,
            'cpp_ideone'                     => 1,
            'c99_strict_ideone'              => 34,
            'clojure_ideone'                 => 111,
            'cobol_ideone'                   => 118,
            'cobol85_ideone'                 => 106,
            'common_lisp_clisp_ideone'       => 32,
            'd_dmd_ideone'                   => 102,
            'erlang_ideone'                  => 36,
            'forth_ideone'                   => 107,
            'fortran_ideone'                 => 5,
            'go_ideone'                      => 114,
            'haskell_ideone'                 => 21,
            'icon_ideone'                    => 16,
            'intercal_ideone'                => 9,
            'java_ideone'                    => 10,
            'javascript_rhino_ideone'        => 35,
            'javascript_spidermonkey_ideone' => 112,
            'lua_ideone'                     => 26,
            'nemerle_ideone'                 => 30,
            'nice_ideone'                    => 25,
            'ocaml_ideone'                   => 8,
            'oz_ideone'                      => 119,
            'pascal_fpc_ideone'              => 22,
            'pascal_gpc_ideone'              => 2,
            'perl_ideone'                    => 3,
            'php_ideone'                     => 29,
            'pike_ideone'                    => 19,
            'prolog_gnu_ideone'              => 108,
            'prolog_swi_ideone'              => 15,
            'python_ideone'                  => 4,
            'python3_ideone'                 => 116,
            'r_ideone'                       => 117,
            'ruby_ideone'                    => 17,
            'scala_ideone'                   => 39,
            'scheme_guile_ideone'            => 33,
            'smalltalk_ideone'               => 23,
            'tcl_ideone'                     => 38,
            'text_ideone'                    => 62,
            'unlambda_ideone'                => 115,
            'vbdotnet_ideone'                => 101,
            'whitespace_ideone'              => 6
        );

        foreach ($ideone_langs as $name => $id) {
            $records = $DB->get_records('assignment_oj', array('language' => $name));
            if (!empty($records)) {
                foreach ($records as $record) {
                    $record->language = $id.'_ideone';
                    $DB->update_record('assignment_oj', $record);
                }
            }
        }

        $sandbox_langs = array('c', 'cpp', 'c_warn2err', 'cpp_warn2err');
        foreach ($sandbox_langs as $old) {
            $records = $DB->get_records('assignment_oj', array('language' => $old));
            if (!empty($records)) {
                foreach ($records as $record) {
                    $record->language = $old.'_sandbox';
                    $DB->update_record('assignment_oj', $record);
                }
            }
        }

        // onlinejudge savepoint reached
        upgrade_plugin_savepoint(true, 2011062400, 'assignment', 'onlinejudge');
    }

    if ($oldversion < 2011081100) {
        // Set var4 to 0 since never using finalize feature
        $sql = 'UPDATE {assignment}
                SET var4 = \'0\'
                WHERE assignmenttype = \'onlinejudge\'';
        $DB->execute($sql);

        // onlinejudge savepoint reached
        upgrade_plugin_savepoint(true, 2011081100, 'assignment', 'onlinejudge');
    }

    if ($oldversion < 2012040600) {
        // Convert all CR+LF to LF in not usefile testcases
        $rs = $DB->get_records('assignment_oj_testcases', array('usefile' => 0), '', 'id, input, output');
        foreach ($rs as $r) {
            $r->input = strtr($r->input, array("\r\n" => "\n", "\n\r" => "\n"));
            $r->output = strtr($r->output, array("\r\n" => "\n", "\n\r" => "\n"));
            $DB->update_record('assignment_oj_testcases', $r, true);
        }

        // onlinejudge savepoint reached
        upgrade_plugin_savepoint(true, 2012040600, 'assignment', 'onlinejudge');
    }

    return true;
}

