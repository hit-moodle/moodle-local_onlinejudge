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
 * @package local_onlinejudge
 * @subpackage backup-moodle2
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @copyright 2011 onwards Sun Zhigang (sunner) {@link http://sunner.cn}
 * @copyright 2017 onwards Andrew Naguib (ndrwnaguib) {@link http://ndrwnaguib.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * restore subplugin class that provides the necessary information
 * needed to restore one assignment->onlinejudge subplugin.
 *
 * Note: Offline assignments really haven't any special subplugin
 * information to backup/restore, hence code below is skipped (return false)
 * but it's a good example of subplugins supported at different
 * elements (assignment and submission)
 */
class restore_assignfeedback_onlinejudge_subplugin extends restore_subplugin {

    /**
     * Returns the paths to be handled by the subplugin at assignment level
     */
    protected function define_grade_subplugin_structure() {

        $paths = array();

        $elename = $this->get_namefor('onlinejudge');
        $elepath = $this->get_pathfor('/onlinejudges/onlinejudge'); // because we used get_recommended_name() in backup this works
        $paths[] = new restore_path_element($elename, $elepath);

        $elename = $this->get_namefor('testcase');
        $elepath = $this->get_pathfor('/testcases/testcase'); // because we used get_recommended_name() in backup this works
        $paths[] = new restore_path_element($elename, $elepath);

        // Returns the paths to be handled by the subplugin at submission level

        $elename = $this->get_namefor('onlinejudge_submission');
        $elepath = $this->get_pathfor('/onlinejudge_submissions/onlinejudge_submission'); // because we used get_recommended_name() in backup this works
        $paths[] = new restore_path_element($elename, $elepath);

        $elename = $this->get_namefor('task');
        $elepath = $this->get_pathfor('/onlinejudge_submissions/onlinejudge_submission/tasks/task'); // because we used get_recommended_name() in backup this works
        $paths[] = new restore_path_element($elename, $elepath);

        return $paths; // And we return the interesting paths
    }

    /**
     * This method processes the onlinejudge element inside one onlinejudge assignment (see onlinejudge subplugin backup)
     */
    public function process_assignfeedback_onlinejudge_onlinejudge($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->assignment = $this->get_new_parentid('assign');

        $newitemid = $DB->insert_record('assignment_oj', $data);
        $this->set_mapping($this->get_namefor('onlinejudge'), $oldid, $newitemid);
    }

    /**
     * This method processes the testcase element inside one onlinejudge assignment (see onlinejudge subplugin backup)
     */
    public function process_assignfeedback_onlinejudge_testcase($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->assignment = $this->get_new_parentid('assign');

        $newitemid = $DB->insert_record('assignment_oj_testcases', $data);
        $this->set_mapping($this->get_namefor('testcase'), $oldid, $newitemid, true);

        $this->add_related_files('assignfeedback_onlinejudge', 'onlinejudge_input', $this->get_namefor('testcase'), null, $oldid);
        $this->add_related_files('assignfeedback_onlinejudge', 'onlinejudge_output', $this->get_namefor('testcase'), null, $oldid);
    }

    /**
     * This method processes the task element inside one onlinejudge assignment (see onlinejudge subplugin backup)
     */
    public function process_assignfeedback_onlinejudge_task($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->cmid = $this->task->get_moduleid();
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('onlinejudge_tasks', $data);

        // Since process_assignfeedback_onlinejudge_onlinejudge_submission() is called before this function,
        // we must update assignment_oj_submissions table's task by this way
        $DB->set_field('assignment_oj_submissions', 'task', $newitemid, array('task' => $oldid, 'submission' => $this->get_new_parentid('assign_submission')));
    }

    /**
     * This method processes the onlinejudge_submission element inside one onlinejudge assignment (see onlinejudge subplugin backup)
     */
    public function process_assignfeedback_onlinejudge_onlinejudge_submission($data) {
        global $DB;

        $data = (object)$data;

        $data->testcase = $this->get_mappingid($this->get_namefor('testcase'), $data->testcase);
        $data->submission = $this->get_mappingid('submission', $data->submission);

        $DB->insert_record('assignment_oj_submissions', $data);
    }
}
