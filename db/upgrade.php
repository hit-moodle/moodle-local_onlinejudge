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

function xmldb_assignment_type_onlinejudge_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;

    $result = true;

    if ($result && $oldversion < 2010032700) {

        /// Define field ratiope to be added to assignment_oj
        $table = new XMLDBTable('assignment_oj');
        $field = new XMLDBField('ratiope');
        $field->setAttributes(XMLDB_TYPE_NUMBER, '20, 10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0.0', 'compileonly');

        /// Launch add field ratiope
        $result = $result && add_field($table, $field);
    }

    return $result;
}

?>
