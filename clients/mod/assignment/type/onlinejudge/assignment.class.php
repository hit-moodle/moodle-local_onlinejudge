<?php

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
//                      Online Judge for Moodle                          //
//       https://github.com/hit-moodle/moodle-local_onlinejudge2         //
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
 * @package   local_onlinejudge2
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/assignment/type/upload/assignment.class.php');
require_once($CFG->libdir.'/filelib.php');
require_once($CFG->libdir.'/questionlib.php'); //for get_grade_options()
require_once($CFG->dirroot.'/local/onlinejudge2/judgelib.php');

/**
 * Extends the upload assignment class
 * 
 * @author Arkaitz Garro, Sunner Sun
 */
class assignment_onlinejudge extends assignment_upload {

    var $onlinejudge;

    function assignment_onlinejudge($cmid='staticonly', $assignment=NULL, $cm=NULL, $course=NULL) {
        global $DB;

        parent::assignment_upload($cmid, $assignment, $cm, $course);
        $this->type = 'onlinejudge';

        if (isset($this->assignment->id)) {
            $this->onlinejudge = $DB->get_record('assignment_oj', array('assignment' => $this->assignment->id));
        }
    }

    /**
     * Print the form for this assignment type
     * 
     * @param $mform object Allready existant form
     */
    function setup_elements(&$mform ) {
        global $CFG, $COURSE, $DB;

        // Some code are copied from parent::setup_elements(). Keep sync please.

        $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));

        $choices = get_max_upload_sizes($CFG->maxbytes, $COURSE->maxbytes);
        $choices[0] = get_string('courseuploadlimit') . ' ('.display_size($COURSE->maxbytes).')';
        $mform->addElement('select', 'maxbytes', get_string('maximumsize', 'assignment'), $choices);
        $mform->setDefault('maxbytes', $CFG->assignment_maxbytes);

        $mform->addElement('select', 'resubmit', get_string('allowdeleting', 'assignment'), $ynoptions);
        $mform->addHelpButton('resubmit', 'allowdeleting', 'assignment');
        $mform->setDefault('resubmit', 1);

        $options = array();
        for($i = 1; $i <= 20; $i++) {
            $options[$i] = $i;
        }
        $mform->addElement('select', 'var1', get_string('allowmaxfiles', 'assignment'), $options);
        $mform->addHelpButton('var1', 'allowmaxfiles', 'assignment');
        $mform->setDefault('var1', 1);

        $mform->addElement('select', 'var2', get_string('allownotes', 'assignment'), $ynoptions);
        $mform->addHelpButton('var2', 'allownotes', 'assignment');
        $mform->setDefault('var2', 0);

        $mform->addElement('select', 'var3', get_string('hideintro', 'assignment'), $ynoptions);
        $mform->addHelpButton('var3', 'hideintro', 'assignment');
        $mform->setDefault('var3', 0);

        $mform->addElement('select', 'emailteachers', get_string('emailteachers', 'assignment'), $ynoptions);
        $mform->addHelpButton('emailteachers', 'emailteachers', 'assignment');
        $mform->setDefault('emailteachers', 0);

        // Get existing onlinejudge settings
        $cm = null;
        $onlinejudge = null;
        $update = optional_param('update', 0, PARAM_INT);
        if (!empty($update)) {
            $cm = $DB->get_record('course_modules', array('id' => $update));
            $onlinejudge = $DB->get_record('assignment_oj', array('assignment' => $cm->instance));
        }

        // Programming languages
        unset($choices);
        $choices = onlinejudge2_get_languages();
        $mform->addElement('select', 'language', get_string('assignmentlangs', 'assignment_onlinejudge'), $choices);
        /// TODO: Set global default language
        $mform->setDefault('language', $onlinejudge ? $onlinejudge->language : 'c');

        // Presentation error grade ratio
        unset($choices);
        $choices = get_grade_options()->gradeoptions; // Steal from question lib
        $mform->addElement('select', 'ratiope', get_string('ratiope', 'assignment_onlinejudge'), $choices);
        $mform->addHelpButton('ratiope', 'ratiope', 'assignment_onlinejudge');
        $mform->setDefault('ratiope', $onlinejudge ? $onlinejudge->ratiope : 0);
        $mform->setAdvanced('ratiope');

        // Max. CPU time
        unset($choices);
        $choices = $this->get_max_cpu_times();
        $mform->addElement('select', 'cpulimit', get_string('cpulimit', 'assignment_onlinejudge'), $choices);
        $mform->setDefault('cpulimit', $onlinejudge ? $onlinejudge->cpulimit : 1);

        // Max. memory usage
        unset($choices);
        $choices = $this->get_max_memory_usages();
        $mform->addElement('select', 'memlimit', get_string('memlimit', 'assignment_onlinejudge'), $choices);
        $mform->setDefault('memlimit', $onlinejudge ? $onlinejudge->memlimit : 1048576);

        // Compile only?
        $mform->addElement('select', 'compileonly', get_string('compileonly', 'assignment_onlinejudge'), $ynoptions);
        $mform->addHelpButton('compileonly', 'compileonly', 'assignment_onlinejudge');
        $mform->setDefault('compileonly', $onlinejudge ? $onlinejudge->compileonly : 0);
        $mform->setAdvanced('compileonly');

        //ideone.com
        $mform->addElement('text', 'ideoneuser', get_string('ideoneuser', 'assignment_onlinejudge'), array('size' => 20));
        $mform->addHelpButton('ideoneuser', 'ideoneuser', 'assignment_onlinejudge');
        $mform->setType('ideoneuser', PARAM_ALPHANUMEXT);
        $mform->setDefault('ideoneuser', $onlinejudge ? $onlinejudge->ideoneuser : '');
        $mform->addElement('password', 'ideonepass', get_string('ideonepass', 'assignment_onlinejudge'), array('size' => 20));
        $mform->addHelpButton('ideonepass', 'ideonepass', 'assignment_onlinejudge');
        $mform->setDefault('ideonepass', $onlinejudge ? $onlinejudge->ideonepass : '');
        $mform->addElement('password', 'ideonepass2', get_string('ideonepass2', 'assignment_onlinejudge'), array('size' => 20));
        $mform->setDefault('ideonepass2', $onlinejudge ? $onlinejudge->ideonepass : '');
        $mform->addRule(array('ideonepass', 'ideonepass2'), get_string('ideonepassmismatch', 'assignment_onlinejudge'), 'compare');

        $course_context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
        plagiarism_get_form_elements_module($mform, $course_context);
    }

    /**
     * Create a new onlinejudge type assignment activity
     *
     * @param object $assignment The data from the form
     * @return int The id of the assignment
     */
    function add_instance($assignment) {
        global $DB;

        // Add assignment instance
        $assignment->id = parent::add_instance($assignment);

        if ($assignment->id) {
            $onlinejudge = $assignment;
            $onlinejudge->assignment = $onlinejudge->id;
            $DB->insert_record('assignment_oj', $onlinejudge);
        }

        return $assignment->id;
    }

    /**
     * Updates a program assignment activity
     *
     * @param object $assignment The data from the form
     * @return int The assignment id
     */
    function update_instance($assignment) {
        global $DB;

        // Add assignment instance
        $returnid = parent::update_instance($assignment);

        if ($returnid) {
            $onlinejudge = $assignment;
            $old_onlinejudge = $DB->get_record('assignment_oj', array('assignment' => $assignment->id));
            if ($old_onlinejudge) {
                $onlinejudge->id = $old_onlinejudge->id;
                $DB->update_record('assignment_oj', $onlinejudge);
            }
        }

        return $returnid;
    }

    /**
     * Deletes a program assignment activity
     *
     * Deletes all database records, files and calendar events for this assignment.
     * 
     * @param object $assignment The assignment to be deleted
     * @return boolean False indicates error
     */
    function delete_instance($assignment) {
        global $CFG, $DB;

        // DELETE submissions results
        $submissions = $DB->get_records('assignment_submissions', array('assignment' => $assignment->id));
        foreach ($submissions as $submission) {
            // TODO: inform judgelib to delete related tasks
            if (!$DB->delete_records('assignment_oj_submissions', array('submission' => $submission->id)))
                return false;
        }

        // DELETE tests
        if (!$DB->delete_records('assignment_oj_testcases', array('assignment' => $assignment->id))) {
            return false;
        }

        // DELETE programming language
        if (!$DB->delete_records('assignment_oj', array('assignment' => $assignment->id))) {
            return false;
        }

        $result = parent::delete_instance($assignment);

        return $result;
    }

    /**
     * Get testcases data of current assignment.
     *
     * @return An array of testcases objects. All testcase files are read into memory
     */
    function get_testcases() {
        global $CFG, $DB;

        $records = $DB->get_records('assignment_oj_testcases', array('assignment' => $this->assignment->id, 'unused' => 0), 'id ASC');
        $tests = array();

        foreach ($records as $record) {
            if ($record->usefile) {
                $fs = get_file_storage();

                if ($files = $fs->get_area_files($this->context->id, 'mod_assignment', 'onlinejudge_input', $record->id)) {
                    $file = array_pop($files);
                    $record->input = $file->get_content();
                }
                if ($files = $fs->get_area_files($this->context->id, 'mod_assignment', 'onlinejudge_output', $record->id)) {
                    $file = array_pop($files);
                    $record->output = $file->get_content();
                }
            }
            $tests[] = $record;
        }

        return $tests;
    }

    /**
     * Rejudge all submissions
     *
     * @return bool
     */
    function rejudge_all() {
        global $DB;

        $submissions = $DB->get_records('assignment_submissions', array('assignment' => $this->assignment->id));
        foreach ($submissions as $submission) {
            if (! $this->request_judge($submission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Display the assignment intro
     *
     */
    function view_intro() {
        global $OUTPUT;

        parent::view_intro();

        //TODO: Show info of judge. E.g. compiler parameters
    }

    /**
     * Print a link to student submitted file.
     * 
     * @param int $userid User Id
     * @param boolean $return Return the link or print it directly
     */
    function print_student_answer($userid, $return = false) {
        $output = parent::print_student_answer($userid, true);

        $submission = $this->get_submission($userid);
        // replace draft status with onlinejudge status
        if ($this->is_finalized($submission)) {
            $pattern = '/(<div class="box files">).*(<div )/';
            $replacement = '$1<strong>'.get_string('status'.$submission->oj_result->status, 'local_onlinejudge2').'</strong>$2';
            $output = preg_replace($pattern, $replacement, $output, 1);
        }

        // TODO: Syntax Highlight source code link

        return $output; // Always return since parent do so too
    }

    /**
     * Produces a list of links to the files uploaded by a user
     *
     * @param $userid int optional id of the user. If 0 then $USER->id is used.
     * @param $return boolean optional defaults to false. If true the list is returned rather than printed
     * @return string optional
     */
    function print_user_files($userid=0, $return=false) {
        $output = parent::print_user_files($userid, true);

        $patterns = array();
        $replacements = array();

        // TODO: Syntax Highlighert source code link

        // Replace upload strings with onlinejudge strings
        $patterns[] = '/<input type="submit" name="unfinalize" .+ \\/><\\/a><\\/span>/';
        $replacements[] = get_string('waitingforjudge', 'assignment_onlinejudge');
        $patterns[] = '/(<input type="submit" name="finalize" value=")[^"]*(" \\/>)/';
        $replacements[] = '$1'.get_string('rejudge', 'assignment_onlinejudge').'$2';

        $output = preg_replace($patterns, $replacements, $output, 1);

        $output .= $this->view_summary($userid);

        if ($return) {
            return $output;
        }
        echo $output;
    }

    /**
     * Load the submission object for a particular user
     *
     * online judge result is in the return object
     * @global object
     * @global object
     * @param $userid int The id of the user whose submission we want or 0 in which case USER->id is used
     * @param $createnew boolean optional Defaults to false. If set to true a new submission object will be created in the database
     * @param bool $teachermodified student submission set if false
     * @return object The submission
     */
    function get_submission($userid=0, $createnew=false, $teachermodified=false) {
        $submission = parent::get_submission($userid, $createnew, $teachermodified);

        if (!empty($submission)) {
            $submission->oj_result = $this->get_onlinejudge_result($submission);
        }

        return $submission;
    }

    /**
     * Print the request grade button
     *
     * This function is forked from upload type. Keep syncing if necessary
     */
    function view_final_submission() {
        global $CFG, $USER, $OUTPUT;

        $submission = $this->get_submission($USER->id);

        if ($this->isopen() and $this->can_finalize($submission)) {
            //print final submit button
            echo $OUTPUT->heading(get_string('readytojudge','assignment_onlinejudge'), 3);
            echo '<div style="text-align:center">';
            echo '<form method="post" action="upload.php">';
            echo '<fieldset class="invisiblefieldset">';
            echo '<input type="hidden" name="id" value="'.$this->cm->id.'" />';
            echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
            echo '<input type="hidden" name="action" value="finalize" />';
            echo '<input type="hidden" name="confirm" value="1" />';
            echo '<input type="submit" name="formarking" value="'.get_string('requestjudge', 'assignment_onlinejudge').'" />';
            echo '</fieldset>';
            echo '</form>';
            echo '</div>';
        } else if (!$this->isopen()) {
            echo $OUTPUT->heading(get_string('nomoresubmissions','assignment'), 3);

        } else if ($this->drafts_tracked() and $state = $this->is_finalized($submission)) {
            if ($state == ASSIGNMENT_STATUS_SUBMITTED) {
                echo $OUTPUT->heading(get_string('waitingforjudge','assignment_onlinejudge'), 3);
            } else {
                echo $OUTPUT->heading(get_string('nomoresubmissions','assignment'), 3);
            }
        } else {
            //no submission yet
        }
    }

    function finalize($forcemode=null) {
        global $USER, $DB, $OUTPUT;
        $userid = optional_param('userid', $USER->id, PARAM_INT);
        $submission = $this->get_submission($userid);

        if ($this->can_finalize($submission)) {
            $this->request_judge($submission);
        }

        parent::finalize($forcemode);
    }

    /**
     * Display auto generated info about the submission
     */
    function view_summary($user=0, $return = true) {
        global $USER, $CFG, $DB, $OUTPUT;

        //TODO: links on testcases to show outputs

        $table = new html_table();
        $table->id = 'assignment_onlinejudge_summary';
        $table->attributes['class'] = 'generaltable';
        $table->align = array ('right', 'left');
        $table->size = array('20%', '');
        $table->width = '100%';

        // Language
        $item_name = get_string('assignmentlangs','assignment_onlinejudge').':';
        $lang = onlinejudge2_get_language_name($this->onlinejudge->language);
        $table->data[] = array($item_name, $lang);

        $submission = $this->get_submission($user);

        // Status
        $item_name = get_string('status', 'assignment_onlinejudge').$OUTPUT->help_icon('status', 'assignment_onlinejudge').':';
        $item = get_string('notavailable');
        if (!empty($submission->oj_result->status)) {
            $item = get_string('status'.$submission->oj_result->status, 'local_onlinejudge2');
        }
        $table->data[] = array($item_name, $item);

        // Judge time
        $item_name = get_string('judgetime','assignment_onlinejudge').':';
        $item = get_string('notavailable');
        if (!empty($submission->oj_result->judgetime)) {
            $item = userdate($submission->oj_result->judgetime).'&nbsp('.get_string('early', 'assignment', format_time(time() - $submission->oj_result->judgetime)) . ')';
        }
        $table->data[] = array($item_name, $item);

        // Details
        $item_name = get_string('details','assignment_onlinejudge').':';
        $item = get_string('notavailable');
        if (!empty($submission->oj_result->testcases)) {
            $i = 1;
            $lines = array();
            foreach ($submission->oj_result->testcases as $case) {
                if (!is_null($case))
                    $lines[] = get_string('case', 'assignment_onlinejudge', $i).' '.get_string('status'.$case->status, 'local_onlinejudge2');
                $i++;
            }
            if (!empty($lines)) {
                $item = implode($lines, '<br />');
            }
        }
        $table->data[] = array($item_name, $item);

        // Output (Show to teacher only)
        if (has_capability('mod/assignment:grade', $this->context) && isset($submission->output)) {
            $table->data[] = array(get_string('output', 'assignment_onlinejudge').':', format_text(stripslashes($submission->output), FORMAT_PLAIN));
        }

        $output = html_writer::table($table);

        if($return)
            return $output;
            
        echo $output;
    }

    /**
     * return success rate. return more details if $detail is set
     */
    function get_statistics($submission = null, &$detail = null) {
        global $DB;

        if (is_null($submission))
            $submission = $this->get_submission();
        if (isset($submission->id) && true /*TODO: judged? */) {
            $statistics = array();
            foreach ($results as $result) {
                $status = $result->status;
                if (!array_key_exists($status, $statistics))
                    $statistics[$status] = 0;
                $statistics[$status]++;
            }

            $judge_count = 0;
            foreach($statistics as $status => $count) {
                if (empty($detail))
                    $detail = get_string('status'.$status, 'assignment_onlinejudge').': '.$count;
                else
                    $detail .= '<br />'.get_string('status'.$status, 'assignment_onlinejudge').': '.$count;
                if ($status == 'ac') // all ac count as one
                    $judge_count += 1;
                else
                    $judge_count += $count;
            }

            if (array_key_exists('ac', $statistics))
                return 1/$judge_count;
            else
                return 0;
        }
        $detail = get_string('notavailable');
        return 0;
    }

    /**
     * return all results of the submission
     *
     * it will update the grade if necessary
     * @param object submission
     * @return object
     */
    function get_onlinejudge_result($submission) {
        global $DB;

        if (empty($submission))
            return null;

        $sql = 'SELECT s.*, t.feedback, t.subgrade
                FROM {assignment_oj_submissions} s LEFT JOIN {assignment_oj_testcases} t
                ON s.testcase = t.id
                WHERE s.submission = ? AND t.unused = 0 AND s.id IN (
                    SELECT MAX(id)
                    FROM {assignment_oj_submissions}
                    WHERE submission = ?
                    GROUP BY testcase)
                ORDER BY s.testcase ASC';
        $onlinejudges = $DB->get_records_sql($sql, array($submission->id, $submission->id)); 

        $cases = array();
        $result->judgetime = 0;
        $result->grade = 0;
        foreach ($onlinejudges as $oj) {
            if ($task = onlinejudge2_get_task($oj->task)) {
                $task->testcase = $oj->testcase;
                $task->feedback = $oj->feedback;

                $task->grade = $this->grade_marker($task->status, $oj->subgrade);
                if ($task->grade != -1 && $result->grade != -1) {
                    $result->grade += $task->grade;
                } else {
                    // Never count grade again
                    $result->grade = -1;
                }

                if ($task->judgetime > $result->judgetime) {
                    $result->judgetime = $task->judgetime;
                }

                $cases[] = $task;
            } else {
                $cases[] = null;
            }
        }

        // should we update the grade?
        if ($submission->timemarked < $result->judgetime and $this->is_finalized($submisssion)) {
            $submission->grade = $result->grade;
            $submission->timemarked = time();
            $submission->mailed = 1; // do not notify student by mail
            $DB->update_record('assignment_submissions', $submission);
            // triger grade event
            $this->update_grade($submission);
        }

        $result->testcases = $cases;
        $result->status = onlinejudge2_get_overall_status($cases);

        return $result;
    }

    function update_submission($submission, $new_oj=false) {
        global $DB;

        $DB->update_record('assignment_submission', $submission);

        if ($new_oj) {
            $submission->submission = $submission->id;
            $DB->insert_record('assignment_oj_submissions', $submission);
        } else {
            $submission->id = $submission->oj_id;
            $DB->update_record('assignment_oj_submissions', $submission);
        }
    }

    /**
     * This function returns an
     * array of possible memory sizes in an array, translated to the
     * local language.
     *
     * @return array
     */
    static function get_max_memory_usages() {

        // Get max size
        $maxsize = 1024*1024*get_config('local_onlinejudge2', 'maxmemlimit');
        $memusage[$maxsize] = display_size($maxsize);

        $sizelist = array(1048576, 2097152, 4194304, 8388608, 16777216, 33554432,
                          67108864, 134217728, 268435456, 536870912);

        foreach ($sizelist as $sizebytes) {
           if ($sizebytes < $maxsize) {
               $memusage[$sizebytes] = display_size($sizebytes);
           }
        }

        ksort($memusage, SORT_NUMERIC);

        return $memusage;
    }
    
    /**
     * This function returns an
     * array of possible CPU time (in seconds) in an array
     *
     * @return array
     */
    static function get_max_cpu_times() {

        // Get max size
        $maxtime = get_config('local_onlinejudge2', 'maxcpulimit');
        $cputime[$maxtime] = get_string('numseconds', 'moodle', $maxtime);

        $timelist = array(1, 2, 3, 4, 5, 6, 7, 8, 9,
                          10, 11, 12, 13, 14, 15, 20,
                          25, 30, 40, 50, 60);

        foreach ($timelist as $timesecs) {
           if ($timesecs < $maxtime) {
               $cputime[$timesecs] = get_string('numseconds', 'moodle', $timesecs);
           }
        }

        ksort($cputime, SORT_NUMERIC);

        return $cputime;
    }

    /**
     * Send judge task request to judgelib
     */
    function request_judge($submission) {
        global $DB;

        $oj = $DB->get_record('assignment_oj', array('assignment' => $submission->assignment));

        $source = array();
        $fs = get_file_storage();
        $files = $fs->get_area_files($this->context->id, 'mod_assignment', 'submission', $submission->id, 'sortorder, timemodified', false);

        onlinejudge2_submit_task($this->cm->id, $submission->userid, $oj->language, $files, $oj);
    }

    /**
     * return grade
     *
     * @param int status
     * @param float $fraction
     * @return grade
     */
    function grade_marker($status, $fraction) {
        $grades = array(
            ONLINEJUDGE2_STATUS_PENDING                 => -1,
            ONLINEJUDGE2_STATUS_JUDGING                 => -1,
            ONLINEJUDGE2_STATUS_INTERNAL_ERROR          => -1,
            ONLINEJUDGE2_STATUS_WRONG_ANSWER            => 0,
            ONLINEJUDGE2_STATUS_RUNTIME_ERROR           => 0,
            ONLINEJUDGE2_STATUS_TIME_LIMIT_EXCEED       => 0,
            ONLINEJUDGE2_STATUS_MEMORY_LIMIT_EXCEED     => 0,
            ONLINEJUDGE2_STATUS_OUTPUT_LIMIT_EXCEED     => 0,
            ONLINEJUDGE2_STATUS_COMPILATION_ERROR       => 0,
            ONLINEJUDGE2_STATUS_COMPILATION_OK          => 0,
            ONLINEJUDGE2_STATUS_RESTRICTED_FUNCTIONS    => 0,
            ONLINEJUDGE2_STATUS_ABNORMAL_TERMINATION    => 0,
            ONLINEJUDGE2_STATUS_ACCEPTED                => $fraction * $this->assignment->grade,
            ONLINEJUDGE2_STATUS_PRESENTATION_ERROR      => $fraction * $this->assignment->grade * $this->onlinejudge->ratiope
        );

        return $grades[$status];
    }

    function cron() {
        //TODO: clean never unused testcases
        //TODO: grade ungraded submissions
    }


    // Return the content of the file submitted by userid. The charset of the content is translated into UTF8.
    // If the file doesn't exist, return false
    function get_submission_file_content($userid)
    {
        if ($basedir = $this->file_area($userid)) {
            if ($files = get_directory_list($basedir)) {
                foreach ($files as $key => $file) {
                    return mb_convert_encoding(file_get_contents($basedir.'/'.$file), 'UTF-8', 'UTF-8, GBK');
                }
            }
        }

        return false;
    }

    /**
     * Adds specific settings to the settings block
     */
    function extend_settings_navigation($assignmentnode) {
        global $PAGE, $DB, $USER, $CFG;

        if (has_capability('mod/assignment:grade', $PAGE->cm->context)) {
            $string = get_string('rejudgeall','assignment_onlinejudge');
            $link = $CFG->wwwroot.'/mod/assignment/type/onlinejudge/rejudge.php?id='.$this->cm->id;
            $assignmentnode->add($string, $link, navigation_node::TYPE_SETTING);

            $string = get_string('managetestcases','assignment_onlinejudge');
            $link = $CFG->wwwroot.'/mod/assignment/type/onlinejudge/testcase.php?id='.$this->cm->id;
            $assignmentnode->add($string, $link, navigation_node::TYPE_SETTING);
        }
    }
}

?>
