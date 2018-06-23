<?php
///////////////////////////////////////////////////////////////////////////
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
//                       Online Judge Moodle 3.4+                        //
//                 Copyright (C) 2018 onwards Andrew Nagyeb              //
// This program is based on the work of Sun Zhigang (C) 2009 Moodle 2.6. //
//                                                                       //
//    Modifications were made in order to upgrade the program so that    //
//                     it is compatible to Moodle 3.4+.                  //
//                       Original License Follows                        //
///////////////////////////////////////////////////////////////////////////

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
 * online judge assignment type for online judge 2
 *
 * @package   local_onlinejudge
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/questionlib.php'); //for get_grade_options()
$locallib_file = $CFG->dirroot . '/local/onlinejudge/judgelib.php';
file_exists($locallib_file) AND require_once $locallib_file;
require_once($CFG->dirroot . '/mod/assign/feedbackplugin.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once('testcase_form.php');

/**
 * Extends the assign feedback plugin class
 *
 * @author Arkaitz Garro, Sunner Sun
 */

/**
 * @developer Andrew Nagyeb
 */

use SphereEngine\Api\CompilersClientV4;

class assign_feedback_onlinejudge extends assign_feedback_plugin {
    public function get_name() {
        return get_string('pluginname', 'assignfeedback_onlinejudge');
    }

    /**
     * Print the settings form for this feedback plugin.
     * @param MoodleQuickForm object already existent form
     * @throws coding_exception
     * @throws dml_exception
     */

    function get_settings(MoodleQuickForm $mform) {
        global $CFG, $COURSE, $DB;

        $ynoptions = array(0 => get_string('no'), 1 => get_string('yes'));

        // Get existing onlinejudge settings
        $update = optional_param('update', 0, PARAM_INT);
        if (!empty($update)) {
            $onlinejudge = $DB->get_record('assignment_oj', array('assignment' => $this->assignment->get_instance()->id));
        }


        // Programming languages
        unset($choices);
        $choices = onlinejudge_get_languages();
        $mform->addElement('select', 'language', get_string('assignmentlangs', 'assignfeedback_onlinejudge'), $choices);
        $mform->setDefault('language', isset($onlinejudge) ? $onlinejudge->language : get_config('local_onlinejudge', 'defaultlanguage'));
        // Disabling element if online judge enable button is not checked.
        $mform->disabledIf('language', 'assignfeedback_onlinejudge_enabled', 'notchecked');

        // Presentation error grade ratio
        unset($choices);
        $choices = question_bank::fraction_options(); // Steal from question lib
        $mform->addElement('select', 'ratiope', get_string('ratiope', 'assignfeedback_onlinejudge'), $choices);
        $mform->addHelpButton('ratiope', 'ratiope', 'assignfeedback_onlinejudge');
        $mform->setDefault('ratiope', isset($onlinejudge) ? $onlinejudge->ratiope : 0);
        $mform->setAdvanced('ratiope');
        $mform->disabledIf('ratiope', 'assignfeedback_onlinejudge_enabled', 'notchecked');


        // Max. CPU time
        unset($choices);
        $choices = get_max_cpu_times();
        $mform->addElement('select', 'cpulimit', get_string('cpulimit', 'assignfeedback_onlinejudge'), $choices);
        $mform->setDefault('cpulimit', isset($onlinejudge) ? $onlinejudge->cpulimit : 1);
        $mform->disabledIf('cpulimit', 'assignfeedback_onlinejudge_enabled', 'notchecked');

        // Max. memory usage
        unset($choices);
        $choices = get_max_memory_usages();
        $mform->addElement('select', 'memlimit', get_string('memlimit', 'assignfeedback_onlinejudge'), $choices);
        $mform->setDefault('memlimit', isset($onlinejudge) ? $onlinejudge->memlimit : 1048576);
        $mform->disabledIf('memlimit', 'assignfeedback_onlinejudge_enabled', 'notchecked');

        // Compile only?
        $mform->addElement('select', 'compileonly', get_string('compileonly', 'assignfeedback_onlinejudge'), $ynoptions);
        $mform->addHelpButton('compileonly', 'compileonly', 'assignfeedback_onlinejudge');
        $mform->setDefault('compileonly', isset($onlinejudge) ? $onlinejudge->compileonly : 0);
        $mform->setAdvanced('compileonly');
        $mform->disabledIf('compileonly', 'assignfeedback_onlinejudge_enabled', 'notchecked');

        //sphere-engine.com
        if (judge_sphere_engine::is_available()) {
            $mform->addElement('text', 'clientid', get_string('clientid', 'assignfeedback_onlinejudge'), array('size' => 20));
            $mform->addHelpButton('clientid', 'clientid', 'assignfeedback_onlinejudge');
            $mform->setType('clientid', PARAM_ALPHANUMEXT);
            $mform->setDefault('clientid', isset($onlinejudge) ? $onlinejudge->clientid : '');
            $mform->disabledIf('clientid', 'assignfeedback_onlinejudge_enabled', 'notchecked');

            $mform->addElement('password', 'accesstoken', get_string('accesstoken', 'assignfeedback_onlinejudge'), array('size' => 20));
            $mform->addHelpButton('accesstoken', 'accesstoken', 'assignfeedback_onlinejudge');
            $mform->setDefault('accesstoken', isset($onlinejudge) ? $onlinejudge->accesstoken : '');
            $mform->disabledIf('accesstoken', 'assignfeedback_onlinejudge_enabled', 'notchecked');
        }
        // Newly added tags
        $mform->addElement('select', 'compile_lm_option', get_string('compile_lm_option', 'assignfeedback_onlinejudge'), $ynoptions);
        $mform->addHelpButton('compile_lm_option', 'compile_lm_option', 'assignfeedback_onlinejudge');
        $mform->setDefault('compile_lm_option', isset($onlinejudge) ? $onlinejudge->compile_lm_option : 1);
        $mform->setAdvanced('compile_lm_option');
        $mform->disabledIf('compile_lm_option', 'assignfeedback_onlinejudge_enabled', 'notchecked');

        $mform->addElement('select', 'compile_warnings_option', get_string('compile_warnings_option', 'assignfeedback_onlinejudge'), $ynoptions);
        $mform->addHelpButton('compile_warnings_option', 'compile_warnings_option', 'assignfeedback_onlinejudge');
        $mform->setDefault('compile_warnings_option', isset($onlinejudge) ? $onlinejudge->compile_warnings_option : 1);
        $mform->setAdvanced('compile_warnings_option');
        $mform->disabledIf('compile_warnings_option', 'assignfeedback_onlinejudge_enabled', 'notchecked');

        $mform->addElement('select', 'compile_static_option', get_string('compile_static_option', 'assignfeedback_onlinejudge'), $ynoptions);
        $mform->addHelpButton('compile_static_option', 'compile_static_option', 'assignfeedback_onlinejudge');
        $mform->setDefault('compile_static_option', isset($onlinejudge) ? $onlinejudge->compile_static_option : 1);
        $mform->setAdvanced('compile_static_option');
        $mform->disabledIf('compile_static_option', 'assignfeedback_onlinejudge_enabled', 'notchecked');

        $course_context = context_course::instance($COURSE->id);
        plagiarism_get_form_elements_module($mform, $course_context);

    }

    /**
     * @param stdClass $data
     * @return bool
     */
    public function save_settings(stdClass $data) {
        global $DB;

        if (!empty($errors = $this->form_validation($data))) {
            $table = new html_table();
            foreach ($errors as $error => $value) $table->data[] = array($error, $value);
            $output = html_writer::table($table);
            $this->set_error($output);
            return false;
        }
        $exists = $DB->get_record('assignment_oj', array('assignment' => $this->assignment->get_instance()->id)) ? true : false;
        if (!$exists) {
            return add_instance($data, $this->assignment->get_instance()->id);
        } else {
            return update_instance($data, $this->assignment->get_instance()->id);
        }

    }

    /**
     * Any extra validation checks needed for the settings
     * form for this feedback plugin should be added to this method.
     */
    function form_validation($data) {
        global $CFG;

        $errors = array();
        if (substr($data->language, -13) == 'sphere_engine') {
            // sphere-engine does support multifiles
            // TODO: allow multi-files submissions when sphere engine is used.
            if ($data->assignsubmission_file_maxfiles > 1) {
                $errors['Files Allowed'] = get_string('onefileonlyse', 'local_onlinejudge');
            }

            if (empty($data->clientid)) {
                $errors['clientid'] = get_string('seclientidrequired', 'local_onlinejudge');
            }
            if (empty($data->accesstoken)) {
                $errors['accesstoken'] = get_string('seclientidrequired', 'local_onlinejudge');
            } else if (!empty($data->clientid)) { // test username and password
                // requiring the sphere engine api files.
                require_once($CFG->dirroot . "/local/onlinejudge/judge/sphere_engine/api/CompilersClientV4.php");
                require_once($CFG->dirroot . "/local/onlinejudge/judge/sphere_engine/api/SphereEngineConnectionException.php");
                require_once($CFG->dirroot . "/local/onlinejudge/judge/sphere_engine/api/SphereEngineResponseException.php");
                require_once($CFG->dirroot . "/local/onlinejudge/judge/sphere_engine/api/vendor/autoload.php");
                // define access parameters
                $access_token = $data->accesstoken;
                $end_point = $data->clientid;

                $client = new CompilersClientV4($access_token, $end_point);

                // API usage
                try {
                    $response = $client->test();
                } catch (\SphereEngine\Api\SphereEngineResponseException $e) {
                    if ($e->getCode() == 401 or $e->getCode() == 402) {
                        $errors['accesstoken'] = get_string('seautherror', 'local_onlinejudge');
                    }
                }
            }

        }
        return $errors;
    }

    /**
     * Deletes a program assignment activity
     *
     * Deletes all database records, files and calendar eventsevent for this assignment.
     *
     * @return boolean False indicates error
     * @throws coding_exception
     * @throws dml_exception
     */
    function delete_instance() {
        global $CFG, $DB;

        // delete onlinejudge submissions
        $submissions = $DB->get_records('assignment_submissions', array('assignment' => $this->assignment->get_instance()->id));
        foreach ($submissions as $submission) {
            if (!$DB->delete_records('assignment_oj_submissions', array('submission' => $submission->id))) return false;
        }

        // delete testcases
        // parent will delete all files in this context
        if (!$DB->delete_records('assignment_oj_testcases', array('assignment' => $this->assignment->get_instance()->id))) {
            return false;
        }

        // delete onlinejudge settings
        if (!$DB->delete_records('assignment_oj', array('assignment' => $this->assignment->get_instance()->id))) {
            return false;
        }

        // inform judgelib to delete related tasks
        if (!onlinejudge_delete_coursemodule($this->assignment->get_course_module()->id)) {
            return false;
        }


        return true;
    }

    /**
     * Shows the 'test cases management' and 'rejudge all' buttons.
     * @return string
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function view_header() {
        $course_context = context_module::instance($this->assignment->get_course_module()->id);
        $cmid = $this->assignment->get_course_module()->id;
        // Checking if the user is allowed to edit/update course [ Not Student ].
        $output = $this->view_judge_info();
        $output .= '<div class="p-y-2">';
        if (has_capability('mod/assign:grade', $course_context)) {
            if (empty(get_testcases($this->assignment->get_instance()->id))) {
                $message = get_string('testcasesrequired', 'assignfeedback_onlinejudge');
                $output .= '<div class="alert alert-warning">';
                $output .= $message;
                $output .= '</div>';
            }
            $urlparams = array('id' => $cmid, 'a' => $this->assignment->get_instance()->id);
            $url = new moodle_url('/mod/assign/feedback/onlinejudge/testcase.php', $urlparams);
            $output .= "<a href='$url' class='btn btn-primary' type='button'>" . get_string('testcasemanagement', 'assignfeedback_onlinejudge') . "</a> ";
            $url = new moodle_url('/mod/assign/feedback/onlinejudge/rejudge.php', array('id' => $cmid, 'a' => $this->assignment->get_instance()->id));
            $output .= "<a href='$url' class='btn btn-info' type='button'>" . get_string('rejudgeall', 'assignfeedback_onlinejudge') . "</a>";
        }
        $output .= '</div>';
        return $output;
    }

    /**
     * Display judge info about the assignment
     */

    function view_judge_info() {
        global $DB;
        $onlinejudge = $DB->get_record('assignment_oj', array('assignment' => $this->assignment->get_instance()->id));

        $table = new html_table();
        $table->id = 'assignment_onlinejudge_information';
        $table->attributes['class'] = 'generaltable';
        $table->size = array('30%', '');

        // Language
        $item_name = get_string('assignmentlangs', 'assignfeedback_onlinejudge') . ':';
        $item = onlinejudge_get_language_name($onlinejudge->language);
        $table->data[] = array($item_name, $item);

        // Compiler
        if ($compiler_info = onlinejudge_get_compiler_info($onlinejudge->language)) {
            $item_name = get_string('compiler', 'assignfeedback_onlinejudge') . ':';
            $table->data[] = array($item_name, $compiler_info);
        }

        // Limits
        $item_name = get_string('memlimit', 'assignfeedback_onlinejudge') . ':';
        $item = display_size($onlinejudge->memlimit);
        $table->data[] = array($item_name, $item);
        $item_name = get_string('cpulimit', 'assignfeedback_onlinejudge') . ':';
        $item = $onlinejudge->cpulimit . ' ' . get_string('sec');
        $table->data[] = array($item_name, $item);

        return html_writer::table($table);
    }

    /**
     * The judge works as a daemon so there is nothing to be saved through the normal interface.
     *
     * @param stdClass $grade The grade.
     * @param stdClass $data Form data from the feedback form.
     * @return boolean - False
     */
    public function is_feedback_modified(stdClass $grade, stdClass $data) {
        return false;
    }

    /**
     * Display judge info about the submission
     * @param stdClass grade data
     * @return string - return a string representation of the submission in full
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function view(stdClass $grade) {
        global $OUTPUT;
        ///////////////////////////////////////////

        $table = new html_table();
        $table->id = 'assignment_onlinejudge_summary';
        $table->attributes['class'] = 'generaltable';
        $table->size = array('30%', '80%');
        $submission = $this->assignment->get_user_submission($grade->userid, false);
        $onlinejudge_result = get_onlinejudge_result($submission, $this->assignment->get_instance()->grade);
        // Status
        $item_name = get_string('status', 'assignfeedback_onlinejudge') . $OUTPUT->help_icon('status', 'assignfeedback_onlinejudge');
        $item = get_string('notavailable');
        if (isset($onlinejudge_result->status)) {
            $itemstyle = $onlinejudge_result->status == ONLINEJUDGE_STATUS_ACCEPTED ? 'label label-success' : 'label label-warning';
            $item = html_writer::tag('h5', html_writer::tag('span', get_string('status' . $onlinejudge_result->status, 'local_onlinejudge'), array('class' => $itemstyle)));
            #region force judge button.
            if (has_capability('mod/assign:grade', $this->assignment->get_context())) {
                $url = new moodle_url('/mod/assign/view.php', array('action' => 'viewpluginpage', 'pluginsubtype' => 'assignfeedback', 'plugin' => 'onlinejudge', 'pluginaction' => 'forcejudge', 'id' => $this->assignment->get_course_module()->id, 'userid' => $submission->userid));

                $attributes = array('href' => $url, 'class' => 'btn btn-info btn-sm');
                $item .= html_writer::tag('a', get_string('forcejudge', 'assignfeedback_onlinejudge'), $attributes);
            }
            #endregion
        }
        $table->data[] = array($item_name, $item);
        ///////////////////////////////////////////

        // Judge time
        $item_name = get_string('judgetime', 'assignfeedback_onlinejudge');
        $item = get_string('notavailable');
        if (!empty($onlinejudge_result->judgetime)) {
            $item = userdate($onlinejudge_result->judgetime) . '&nbsp(' . get_string('submittedearly', 'assign', format_time(time() - $onlinejudge_result->judgetime)) . ')';
        }
        $table->data[] = array($item_name, $item);
        ///////////////////////////////////////////

        // Source code
        $urlparams = array('id' => $this->assignment->get_course_module()->id, 'a' => $this->assignment->get_instance()->id, 'submissionid' => $submission->id,);
        $url = new moodle_url('/mod/assign/feedback/onlinejudge/source.php', $urlparams);
        $icon = $OUTPUT->pix_icon('docs', get_string('more'));
        $attributes = array('href' => $url, 'title' => get_string('more'),);
        $attributes['id'] = $OUTPUT->add_action_handler(new popup_action('click', $url));
        $icon = '<i class="fa fa-file-text fa-2x" aria-hidden="true"></i>';
        $item = html_writer::tag('a', $icon, $attributes);
        $item_name = get_string('source_code', 'assignfeedback_onlinejudge');
        $table->data[] = array($item_name, $item);
        ///////////////////////////////////////////

        // Details
        $item_name = get_string('details', 'local_onlinejudge');
        $item = get_string('notavailable');
        if ($onlinejudge_result->status == ONLINEJUDGE_STATUS_COMPILATION_ERROR) {
            $item = htmlspecialchars(reset($onlinejudge_result->testcases)->compileroutput);
        } else if (!empty($onlinejudge_result->testcases)) {
            $i = 1;
            $lines = array();
            foreach ($onlinejudge_result->testcases as $case) {
                if (!is_null($case)) {
                    $line = get_string('case', 'assignfeedback_onlinejudge', $i) . ' ' . get_string('status' . $case->status, 'local_onlinejudge');

                    // details icon link
                    $url = new moodle_url('/local/onlinejudge/details.php', array('task' => $case->id, 'course' => $this->assignment->get_course()->id));
                    $attributes = array('href' => $url);
                    $attributes['id'] = $OUTPUT->add_action_handler(new popup_action('click', $url));
                    // $OUTPUT->pix_icon is not used as it renders extra space for the first element.
                    // TODO: find a better way?
                    $icon = html_writer::tag('i', '', array('class' => 'fa fa-info-circle fa-fw', 'aria-hidden' => 'true', 'style' => 'color: black;'));
                    $line .= html_writer::tag('a', $icon, $attributes);

                    // show teacher defined feedback
                    if ($case->status == ONLINEJUDGE_STATUS_WRONG_ANSWER and !empty($case->feedback)) {
                        $line .= ' (' . $case->feedback . ')';
                    }
                    $lines[] = $line;
                }
                $i++;
            }
            if (!empty($lines)) {
                $item = implode($lines, '<hr>');
            }
        }
        $item = format_text($item, FORMAT_MOODLE, array('allowid' => true)); // popup details links require id
        $table->data[] = array($item_name, $item);
        ///////////////////////////////////////////

        // Success Rate
        $item_name = get_string('successrate', 'assignfeedback_onlinejudge');
        // Extra details String
        $item = "";
        $success_rate = $this->get_statistics($submission, $item, $onlinejudge_result->status);
        $table->data[] = array($item_name, $success_rate);
        ///////////////////////////////////////////

        // Statistics
        $item_name = get_string('statistics', 'assignfeedback_onlinejudge');
        $table->data[] = array($item_name, $item);

        $output = html_writer::table($table);
        return $output;
    }

    /**
     * return success rate. return more details if $detail is set
     * @throws coding_exception
     */
    function get_statistics($submission = null, &$detail = null, $judge_status) {
        global $DB;
        if (is_null($submission)) $submission = $this->assignment->get_user_submission(0, false);
        $judged = $judge_status != ONLINEJUDGE_STATUS_JUDGING or $judge_status != ONLINEJUDGE_STATUS_PENDING ? true : false;
        if (isset($submission->id) && $judged) {
            $statistics = array();
            $sql = 'SELECT s.*, t.submission FROM {onlinejudge_tasks} s 
                    LEFT JOIN {assignment_oj_submissions} t 
                    ON s.id = t.task 
                    WHERE t.submission = ? and s.deleted != 1 and s.status != -1';
            $results = $DB->get_records_sql($sql, array($submission->id));
            foreach ($results as $result) {
                $status = $result->status;
                if (!array_key_exists($status, $statistics)) $statistics[$status] = 0;
                $statistics[$status]++;
            }
            $judge_count = 0;
            foreach ($statistics as $status => $count) {
                if (empty($detail)) $detail = get_string('status' . $status, 'local_onlinejudge') . ': ' . $count; else
                    $detail .= '<br />' . get_string('status' . $status, 'local_onlinejudge') . ': ' . $count;
                if ($status == 1) // Means Acceptance
                    $judge_count += $count;
            }

            if (array_key_exists(1, $statistics)) return $judge_count / count($results); else
                return 0;
        }
        $detail = get_string('notavailable');
        return get_string('notavailable');
    }

    /**
     * Allows students to view their submission status in the assignment page context.
     * @param stdClass $grade
     * @return bool [Return true if there are submission is not yet judged.]
     * @throws coding_exception
     */
    public function is_empty(stdClass $grade) {

        $submission = $this->assignment->get_user_submission($grade->userid, false);
        $onlinejudge_result = get_onlinejudge_result($submission, $this->assignment->get_instance()->grade);

        return is_null($onlinejudge_result);
    }

    /**
     * @param stdClass $grade
     * @param $showviewlink
     * @return string - return a string representation of the submission status.
     * @throws coding_exception
     */
    public function view_summary(stdClass $grade, & $showviewlink) {
        $output = "";
        // Allowing view link to be rendered.
        $showviewlink = true;
        $submission = $this->assignment->get_user_submission($grade->userid, false);
        $onlinejudge_result = get_onlinejudge_result($submission, $this->assignment->get_instance()->grade);
        $statusstyle = $onlinejudge_result->status == ONLINEJUDGE_STATUS_ACCEPTED ? 'notifysuccess' : 'notifyproblem';
        $statustext = html_writer::tag('span', get_string('status' . $onlinejudge_result->status, 'local_onlinejudge'), array('class' => $statusstyle));
        $output .= $statustext;

        return $output; // Always return since parent do so too

    }

    /**
     * Rejudge all submissions
     */
    function rejudge_all() {

        global $DB;
        $submissions = $DB->get_records('assign_submission', array('assignment' => $this->assignment->get_instance()->id, 'status' => 'submitted'));
        if (!empty($submissions)) {
            foreach ($submissions as $submission) {
                request_judge($submission);
            }
            return true;
        }
        return false;
    }

    /**
     * Sends a signal to judge daemon to rejudge the selected submission.
     * @param string $action
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     * @throws required_capability_exception
     */
    public function view_page($action) {
        global $DB;
        if ($action == 'forcejudge') {
            $context = $this->assignment->get_course_context();
            require_capability('mod/assign:grade', $context);
            $cmid = $this->assignment->get_course_module()->id;
            $urlparams = array('id' => $cmid, 'action' => 'grading');

            $url = new moodle_url('/mod/assign/view.php', $urlparams);
            $userid = required_param('userid', PARAM_INT);
            $submission = $this->assignment->get_user_submission($userid, false);
            request_judge($submission);
            $user_data = $DB->get_record('user', array('id' => $userid));
            $user_fullname = $user_data->firstname . ' ' . $user_data->lastname;
            redirect($url, get_string('forcejudgerequestsent', 'assignfeedback_onlinejudge', $user_fullname));
        }
        return '';
    }

}