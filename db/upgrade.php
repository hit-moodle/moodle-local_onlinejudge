<?php

// This file is part of Moodle - http://moodle.org/
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
 * onlinejudge2 upgrade scripts
 *
 * @package   local_onlinejudge2
 * @copyright 2011 Yu Zhan <yuzhanlaile@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_local_onlinejudge2_upgrade($oldversion) {
    global $CFG, $DB, $OUTPUT;

    $dbman = $DB->get_manager();
    $result = true;

    if ($oldversion < 2010090103) {
        $dbman->install_one_table_from_xmldb_file($CFG->dirroot.'/local/onlinejudge2/db/install.xml', 'onlinejudge2_stashes');
        upgrade_plugin_savepoint(true, 2010090103, 'local', 'onlinejudge2');
    }

    if ($oldversion < 2010090107) {
        $dbman->install_one_table_from_xmldb_file($CFG->dirroot.'/local/onlinejudge2/db/install.xml', 'onlinejudge2_hidden_requests');
        upgrade_plugin_savepoint(true, 2010090107, 'local', 'onlinejudge2');
    }

    if ($oldversion < 2010110400) {
        $dbman->install_one_table_from_xmldb_file($CFG->dirroot.'/local/onlinejudge2/db/install.xml', 'onlinejudge2_greylist');
        upgrade_plugin_savepoint(true, 2010110400, 'local', 'onlinejudge2');
    }

    if ($oldversion < 2011010600) {
        $dbman->install_one_table_from_xmldb_file($CFG->dirroot.'/local/onlinejudge2/db/install.xml', 'onlinejudge2_contributions');
        upgrade_plugin_savepoint(true, 2011010600, 'local', 'onlinejudge2');
    }

    if ($oldversion < 2011011000) {
        require_once(dirname(dirname(__FILE__)).'/mlanglib.php');

        // convert legacy stashes that were pull-requested
        $stashids = $DB->get_records('onlinejudge2_stashes', array('pullrequest' => 1), 'timemodified ASC', 'id');

        foreach ($stashids as $stashrecord) {
            $stash = mlang_stash::instance_from_id($stashrecord->id);

            // split the stashed components into separate packages by their language
            $stage = new mlang_stage();
            $langstages = array();  // (string)langcode => (mlang_stage)
            $stash->apply($stage);
            foreach ($stage->get_iterator() as $component) {
                $lang = $component->lang;
                if (!isset($langstages[$lang])) {
                    $langstages[$lang] = new mlang_stage();
                }
                $langstages[$lang]->add($component);
            }
            $stage->clear();
            unset($stage);

            // create new contribution record for every language and attach a new stash to it
            foreach ($langstages as $lang => $stage) {
                if (!$stage->has_component()) {
                    // this should not happen, but...
                    continue;
                }
                $copy = new mlang_stage();
                foreach ($stage->get_iterator() as $component) {
                    $copy->add($component);
                }
                $copy->rebase();
                if ($copy->has_component()) {
                    $tostatus = 0;  // new
                } else {
                    $tostatus = 30; // nothing left after rebase - consider it accepted
                }

                $langstash = mlang_stash::instance_from_stage($stage, 0, $stash->name);
                $langstash->message = $stash->message;
                $langstash->push();

                $contribution               = new stdClass();
                $contribution->authorid     = $stash->ownerid;
                $contribution->lang         = $lang;
                $contribution->assignee     = null;
                $contribution->subject      = $stash->name;
                $contribution->message      = $stash->message;
                $contribution->stashid      = $langstash->id;
                $contribution->status       = $tostatus;
                $contribution->timecreated  = $stash->timemodified;
                $contribution->timemodified = null;

                $contribution->id = $DB->insert_record('onlinejudge2_contributions', $contribution);

                // add a comment there
                $comment = new stdClass();
                $comment->contextid = SITEID;
                $comment->commentarea = 'onlinejudge2_contribution';
                $comment->itemid = $contribution->id;
                $comment->content = 'This contribution was automatically created during the conversion of legacy pull-requested stashes.';
                $comment->format = 0;
                $comment->userid = 2;
                $comment->timecreated = time();
                $DB->insert_record('comments', $comment);
            }
            $stash->drop();
        }

        upgrade_plugin_savepoint(true, 2011011000, 'local', 'onlinejudge2');
    }

    if ($oldversion < 2011011001) {

        $table = new xmldb_table('onlinejudge2_stashes');

        $field = new xmldb_field('shared');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        $field = new xmldb_field('pullrequest');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        $table = new xmldb_table('onlinejudge2_hidden_requests');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        upgrade_plugin_savepoint(true, 2011011001, 'local', 'onlinejudge2');
    }

    return $result;
}
