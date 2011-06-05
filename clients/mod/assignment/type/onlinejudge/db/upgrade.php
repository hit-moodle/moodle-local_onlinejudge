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

        assignment_onlinejudge_clean_deleted_submissions();

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

    return true;
}

/// Clean up old records related with deleted submissions
function assignment_onlinejudge_clean_deleted_submissions() {

    global $DB;

    $sql = 'SELECT t1.id 
            FROM {assignment_oj_submissions} t1
            LEFT JOIN {assignment_submissions} t2
            ON t1.submission = t2.id
            WHERE t2.id IS NULL';
    if ($oj_submissions = $DB->get_records_sql($sql)) {
        $DB->delete_records_list('assignment_oj_submissions', 'submission', array_keys($oj_submissions));
    }

}

?>
