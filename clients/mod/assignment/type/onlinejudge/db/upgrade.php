<?php  //$Id: upgrade.php,v 1.2 2007/08/29 14:26:26 stronk7 Exp $

// This file keeps track of upgrades to
// the assignment->onlinejudge submodule
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

function xmldb_assignment_onlinejudge_upgrade($oldversion=0) {

    global $CFG, $THEME, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2010070400) {
        echo 'You MUST upgrade to the latest onlinejudge for moodle 1.9.x first. Download it from https://github.com/hit-moodle/onlinejudge';
        return false;
    }

    if ($oldversion < 2011060301) {
        global $DB;

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
        // Adding keys to table assignment_oj_submissions
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('submission', XMLDB_KEY_FOREIGN, array('submission'), 'assignment_submissions', array('id'));
        $table->add_key('testcase', XMLDB_KEY_FOREIGN, array('testcase'), 'assignment_oj_testcases', array('id'));
        // Conditionally launch create table for assignment_oj_submissions
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // onlinejudge savepoint reached
        upgrade_plugin_savepoint(true, 2011060301, 'assignment', 'onlinejudge');
    }

    if ($oldversion < 2011060500) {
        global $DB;

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

        // Define field unused to be added to assignment_oj_testcases
        $table = new xmldb_table('assignment_oj_testcases');
        $field = new xmldb_field('unused', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'subgrade');
        // Conditionally launch add field unused
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define index unused (not unique) to be added to assignment_oj_testcases
        $table = new xmldb_table('assignment_oj_testcases');
        $index = new xmldb_index('unused', XMLDB_INDEX_NOTUNIQUE, array('unused'));
        // Conditionally launch add index unused
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

    return true;
}

?>
