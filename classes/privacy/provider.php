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
 * Privacy implementation for local_onlinejudge.
 *
 * @package   local_onlinejudge
 * @copyright 2024 Online Judge Contributors
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_onlinejudge\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\context;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\transform;

/**
 * Privacy provider for local_onlinejudge.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Get the language string identifier with the component's language
     * file to explain why this plugin stores no data.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('onlinejudge_tasks', [
            'cmid' => 'privacy:metadata:onlinejudge_tasks:cmid',
            'userid' => 'privacy:metadata:onlinejudge_tasks:userid',
            'language' => 'privacy:metadata:onlinejudge_tasks:language',
            'input' => 'privacy:metadata:onlinejudge_tasks:input',
            'output' => 'privacy:metadata:onlinejudge_tasks:output',
            'stdout' => 'privacy:metadata:onlinejudge_tasks:stdout',
            'stderr' => 'privacy:metadata:onlinejudge_tasks:stderr',
            'compileroutput' => 'privacy:metadata:onlinejudge_tasks:compileroutput',
            'infoteacher' => 'privacy:metadata:onlinejudge_tasks:infoteacher',
            'infostudent' => 'privacy:metadata:onlinejudge_tasks:infostudent',
            'cpuusage' => 'privacy:metadata:onlinejudge_tasks:cpuusage',
            'memusage' => 'privacy:metadata:onlinejudge_tasks:memusage',
            'submittime' => 'privacy:metadata:onlinejudge_tasks:submittime',
            'judgetime' => 'privacy:metadata:onlinejudge_tasks:judgetime',
            'var1' => 'privacy:metadata:onlinejudge_tasks:var1',
            'var2' => 'privacy:metadata:onlinejudge_tasks:var2',
            'var3' => 'privacy:metadata:onlinejudge_tasks:var3',
            'var4' => 'privacy:metadata:onlinejudge_tasks:var4',
            'status' => 'privacy:metadata:onlinejudge_tasks:status',
        ], 'privacy:metadata:onlinejudge_tasks');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // Find contexts where user has submitted tasks.
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {onlinejudge_tasks} ojt ON ojt.cmid = cm.id
                 WHERE ojt.userid = :userid";

        $contextlist->add_from_sql($sql, [
            'userid' => $userid,
            'contextlevel' => CONTEXT_MODULE,
        ]);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $sql = "SELECT ojt.userid
                  FROM {onlinejudge_tasks} ojt
                  JOIN {course_modules} cm ON cm.id = ojt.cmid
                 WHERE cm.id = :cmid";

        $userlist->add_from_sql('userid', $sql, ['cmid' => $context->instanceid]);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();
        $userid = $user->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }

            $cmid = $context->instanceid;

            // Get all tasks for this user in this context.
            $tasks = $DB->get_records('onlinejudge_tasks', [
                'userid' => $userid,
                'cmid' => $cmid,
            ]);

            if (!empty($tasks)) {
                $taskdata = [];
                foreach ($tasks as $task) {
                    $taskdata[] = [
                        'language' => $task->language,
                        'submittime' => transform::datetime($task->submittime),
                        'judgetime' => $task->judgetime ? transform::datetime($task->judgetime) : null,
                        'status' => $task->status,
                        'cpuusage' => $task->cpuusage,
                        'memusage' => $task->memusage,
                        'compileroutput' => $task->compileroutput,
                        'infostudent' => $task->infostudent,
                        'var1' => $task->var1,
                        'var2' => $task->var2,
                        'var3' => $task->var3,
                        'var4' => $task->var4,
                    ];
                }

                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'local_onlinejudge')],
                    (object) ['tasks' => $taskdata]
                );
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }

        $cmid = $context->instanceid;
        $DB->delete_records('onlinejudge_tasks', ['cmid' => $cmid]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }

            $cmid = $context->instanceid;
            $DB->delete_records('onlinejudge_tasks', [
                'userid' => $userid,
                'cmid' => $cmid,
            ]);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        $cmid = $context->instanceid;

        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params = array_merge(['cmid' => $cmid], $userparams);

        $DB->delete_records_select('onlinejudge_tasks', "cmid = :cmid AND userid $usersql", $params);
    }
}