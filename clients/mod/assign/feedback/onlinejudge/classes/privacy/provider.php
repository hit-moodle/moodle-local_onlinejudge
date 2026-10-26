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
 * Privacy implementation for assignfeedback_onlinejudge.
 *
 * @package   assignfeedback_onlinejudge
 * @copyright 2024 Online Judge Contributors
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignfeedback_onlinejudge\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\transform;

/**
 * Privacy provider for assignfeedback_onlinejudge.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\subplugin_provider,
    \core_privacy\local\request\shared_userlist_provider {

    /**
     * Get the language string identifier with the component's language
     * file to explain why this plugin stores no data.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('assignment_oj_submissions', [
            'submission' => 'privacy:metadata:assignment_oj_submissions:submission',
            'testcase' => 'privacy:metadata:assignment_oj_submissions:testcase',
            'task' => 'privacy:metadata:assignment_oj_submissions:task',
            'latest' => 'privacy:metadata:assignment_oj_submissions:latest',
        ], 'privacy:metadata:assignment_oj_submissions');

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

        // Find contexts where user has submissions with online judge feedback.
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {assign} a ON a.id = cm.instance
                  JOIN {assign_submission} sub ON sub.assignment = a.id
                  JOIN {assignment_oj_submissions} ojs ON ojs.submission = sub.id
                 WHERE sub.userid = :userid";

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

        $sql = "SELECT sub.userid
                  FROM {assign_submission} sub
                  JOIN {assign} a ON a.id = sub.assignment
                  JOIN {course_modules} cm ON cm.instance = a.id
                  JOIN {assignment_oj_submissions} ojs ON ojs.submission = sub.id
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

            $cm = get_coursemodule_from_id('assign', $context->instanceid);
            if (!$cm) {
                continue;
            }

            // Get assignment submissions by this user that have online judge data.
            $sql = "SELECT ojs.*, sub.timecreated, sub.timemodified, tc.feedback as testcase_feedback
                      FROM {assign_submission} sub
                      JOIN {assignment_oj_submissions} ojs ON ojs.submission = sub.id
                      LEFT JOIN {assignment_oj_testcases} tc ON tc.id = ojs.testcase
                     WHERE sub.assignment = :assignmentid AND sub.userid = :userid";

            $submissions = $DB->get_records_sql($sql, [
                'assignmentid' => $cm->instance,
                'userid' => $userid,
            ]);

            if (!empty($submissions)) {
                $submissiondata = [];
                foreach ($submissions as $submission) {
                    $submissiondata[] = [
                        'testcase' => $submission->testcase,
                        'task_id' => $submission->task,
                        'latest' => $submission->latest ? get_string('yes') : get_string('no'),
                        'testcase_feedback' => $submission->testcase_feedback,
                        'submission_created' => transform::datetime($submission->timecreated),
                        'submission_modified' => transform::datetime($submission->timemodified),
                    ];
                }

                $assignpath = [get_string('privacy:path:assignments', 'mod_assign'), $cm->name];
                writer::with_context($context)->export_data(
                    array_merge($assignpath, [get_string('pluginname', 'assignfeedback_onlinejudge')]),
                    (object) ['submissions' => $submissiondata]
                );
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('assign', $context->instanceid);
        if (!$cm) {
            return;
        }

        // Delete all assignment_oj_submissions for this assignment.
        $sql = "DELETE FROM {assignment_oj_submissions}
                 WHERE submission IN (
                    SELECT id FROM {assign_submission} WHERE assignment = :assignmentid
                 )";
        $DB->execute($sql, ['assignmentid' => $cm->instance]);
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

            $cm = get_coursemodule_from_id('assign', $context->instanceid);
            if (!$cm) {
                continue;
            }

            // Delete assignment_oj_submissions for this user in this assignment.
            $sql = "DELETE FROM {assignment_oj_submissions}
                     WHERE submission IN (
                        SELECT id FROM {assign_submission} 
                        WHERE assignment = :assignmentid AND userid = :userid
                     )";
            $DB->execute($sql, [
                'assignmentid' => $cm->instance,
                'userid' => $userid,
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

        $cm = get_coursemodule_from_id('assign', $context->instanceid);
        if (!$cm) {
            return;
        }

        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params = array_merge(['assignmentid' => $cm->instance], $userparams);

        $sql = "DELETE FROM {assignment_oj_submissions}
                 WHERE submission IN (
                    SELECT id FROM {assign_submission} 
                    WHERE assignment = :assignmentid AND userid $usersql
                 )";
        $DB->execute($sql, $params);
    }
}