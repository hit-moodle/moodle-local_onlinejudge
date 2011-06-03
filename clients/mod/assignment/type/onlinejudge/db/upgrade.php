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
    $result = true;

    if ($result && $oldversion < 2010032700) {

        /// Define field ratiope to be added to assignment_oj
        $table = new XMLDBTable('assignment_oj');
        $field = new XMLDBField('ratiope');
        $field->setAttributes(XMLDB_TYPE_NUMBER, '20, 10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0.0', 'compileonly');

        /// Launch add field ratiope
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2010040701) {

    /// Define index judged (not unique) to be added to assignment_oj_submissions
        $table = new XMLDBTable('assignment_oj_submissions');
        $index = new XMLDBIndex('judged');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('judged'));

    /// Launch add index judged
        $result = $result && add_index($table, $index);

    /// Define index judgetime (not unique) to be added to assignment_oj_results
        $table = new XMLDBTable('assignment_oj_results');
        $index = new XMLDBIndex('judgetime');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('judgetime'));

    /// Launch add index judgetime
        $result = $result && add_index($table, $index);
    }

    if ($result && $oldversion < 2010042800) {

    /// Define field duejudge to be dropped from assignment_oj
        $table = new XMLDBTable('assignment_oj');
        $field = new XMLDBField('duejudge');

    /// Launch drop field duejudge
        $result = $result && drop_field($table, $field);

    /// Define key test (foreign) to be dropped form assignment_oj_results
        $table = new XMLDBTable('assignment_oj_results');
        $key = new XMLDBKey('test');
        $key->setAttributes(XMLDB_KEY_FOREIGN, array('test'), 'assignment_oj_tests', array('id'));

    /// Launch drop key test
        $result = $result && drop_key($table, $key);

    /// Define field test to be dropped from assignment_oj_results
        $field = new XMLDBField('test');

    /// Launch drop field test
        $result = $result && drop_field($table, $field);
    }


    if ($result && $oldversion < 2010070400) {

    /// Define field usefile to be added to assignment_oj_tests
        $table = new XMLDBTable('assignment_oj_tests');
        $field = new XMLDBField('usefile');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'output');
    /// Launch add field usefile
        $result = $result && add_field($table, $field);

    /// Define field inputfile to be added to assignment_oj_tests
        $table = new XMLDBTable('assignment_oj_tests');
        $field = new XMLDBField('inputfile');
        $field->setAttributes(XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null, 'usefile');
    /// Launch add field inputfile
        $result = $result && add_field($table, $field);

    /// Define field outputfile to be added to assignment_oj_tests
        $table = new XMLDBTable('assignment_oj_tests');
        $field = new XMLDBField('outputfile');
        $field->setAttributes(XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null, 'inputfile');
    /// Launch add field outputfile
        $result = $result && add_field($table, $field);


    /// Changing type of field subgrade on table assignment_oj_tests to number
        $table = new XMLDBTable('assignment_oj_tests');
        $field = new XMLDBField('subgrade');
        $field->setAttributes(XMLDB_TYPE_NUMBER, '20, 10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'feedback');
    /// Launch change of type for field subgrade
        $result = $result && change_field_type($table, $field);

    /// Upgrade the value in subgrade field
        if ($result) {
            $ojs = get_records('assignment_oj');
            foreach ($ojs as $oj) {
                $modgrade = get_field('assignment', 'grade', 'id', $oj->assignment); 
                if ($modgrade) {
                    $sql = 'UPDATE '.$CFG->prefix.'assignment_oj_tests '.
                           'SET subgrade=subgrade/'.$modgrade.' '.
                           'WHERE assignment='.$oj->assignment;
                    $result = $result && execute_sql($sql);
                }
            }
        }

    }

    if ($oldversion < 2011060300) {

        // Define table assignment_oj_results to be dropped
        $table = new xmldb_table('assignment_oj_results');

        // Conditionally launch drop table for assignment_oj_results
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Define index judged (not unique) to be dropped form assignment_oj_submissions
        $table = new xmldb_table('assignment_oj_submissions');
        $index = new xmldb_index('judged', XMLDB_INDEX_NOTUNIQUE, array('judged'));

        // Conditionally launch drop index judged
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define table assignment_oj_tests to be renamed to assignment_oj_testcases
        $table = new xmldb_table('assignment_oj_tests');

        // Launch rename table for assignment_oj_tests
        $dbman->rename_table($table, 'assignment_oj_testcases');

        // Define field judged to be dropped from assignment_oj_submissions
        $table = new xmldb_table('assignment_oj_submissions');
        $field = new xmldb_field('judged');

        // Conditionally launch drop field judged
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field testcase to be added to assignment_oj_submissions
        $table = new xmldb_table('assignment_oj_submissions');
        $field = new xmldb_field('testcase', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'submission');

        // Conditionally launch add field testcase
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field task to be added to assignment_oj_submissions
        $table = new xmldb_table('assignment_oj_submissions');
        $field = new xmldb_field('task', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'testcase');

        // Conditionally launch add field task
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define key testcase (foreign) to be added to assignment_oj_submissions
        $table = new xmldb_table('assignment_oj_submissions');
        $key = new xmldb_key('testcase', XMLDB_KEY_FOREIGN, array('testcase'), 'assignment_oj_testcases', array('id'));

        // Launch add key testcase
        $dbman->add_key($table, $key);

        // onlinejudge savepoint reached
        upgrade_plugin_savepoint(true, 2011060300, 'assignment', 'onlinejudge');
    }

    return $result;
}

function assignment_onlinejudge_clean_submissions() {

    global $DB;

    $sql = 
    $oj_submissions = $DB->get_records_sql('assignment_oj_submissions');
}

?>
