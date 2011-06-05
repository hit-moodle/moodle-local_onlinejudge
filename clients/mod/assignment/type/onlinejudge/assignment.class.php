<?php

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
//            Online Judge assignment type for Moodle                    //
//           http://code.google.com/p/sunner-projects/                   //
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

define('NUMTESTS', 5); // Default number of test cases

// Default maximum cpu time (seconds) for all assignments
if (!isset($CFG->assignment_oj_max_cpu)) {
    set_config('assignment_oj_max_cpu', 10);
}

// Default memory usage (bytes) for all assignments
if (!isset($CFG->assignment_oj_max_mem)) {
    set_config('assignment_oj_max_mem', 256 * 1024 * 1024);
}

// Judge everytime when cron is running if set to true. Default is false. Use daemon is recommanded
if (!isset($CFG->assignment_oj_judge_in_cron)) {
    set_config('assignment_oj_judge_in_cron', 0);
}


// IDEONE.com configure
if (!isset($CFG->assignment_oj_ideone_username)) {
	set_config('assignment_oj_ideone_username' , 'test');
}
if (!isset($CFG->assignment_oj_ideone_password)) {
	set_config('assignment_oj_ideone_password' , 'test');
}
if (!isset($CFG->assignment_oj_ideone_delay)) { //delay between submitting and getting result
	set_config('assignment_oj_ideone_delay' , 3);
}


require_once($CFG->dirroot.'/mod/assignment/type/upload/assignment.class.php');
require_once($CFG->dirroot.'/lib/filelib.php');
require_once($CFG->dirroot.'/lib/questionlib.php'); //for get_grade_options()
require_once($CFG->dirroot.'/lib/adminlib.php'); //for set_cron_lock()

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
        $choices = $this->get_languages();
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
        $choices = $this->get_max_cpu_times($CFG->assignment_oj_max_cpu);
        $mform->addElement('select', 'cpulimit', get_string('cpulimit', 'assignment_onlinejudge'), $choices);
        $mform->setDefault('cpulimit', $onlinejudge ? $onlinejudge->cpulimit : 1);
        $mform->setAdvanced('cpulimit');

        // Max. memory usage
        unset($choices);
        $choices = $this->get_max_memory_usages($CFG->assignment_oj_max_mem);
        $mform->addElement('select', 'memlimit', get_string('memlimit', 'assignment_onlinejudge'), $choices);
        $mform->setDefault('memlimit', $onlinejudge ? $onlinejudge->memlimit : $CFG->assignment_oj_max_mem);
        $mform->setAdvanced('memlimit');

        // Compile only?
        $mform->addElement('select', 'compileonly', get_string('compileonly', 'assignment_onlinejudge'), $ynoptions);
        $mform->addHelpButton('compileonly', 'compileonly', 'assignment_onlinejudge');
        $mform->setDefault('compileonly', $onlinejudge ? $onlinejudge->compileonly : 0);
        $mform->setAdvanced('compileonly');

        //ideone.com
        $mform->addElement('text', 'ideoneuser', get_string('ideoneuser', 'assignment_onlinejudge'), array('size' => 20));
        $mform->addHelpButton('ideoneuser', 'ideoneuser', 'assignment_onlinejudge');
        $mform->setDefault('ideoneuser', $onlinejudge ? $onlinejudge->ideoneuser : '');
        $mform->addElement('password', 'ideonepass', get_string('ideonepass', 'assignment_onlinejudge'), array('size' => 20));
        $mform->addHelpButton('ideonepass', 'ideonepass', 'assignment_onlinejudge');
        $mform->setDefault('ideonepass', $onlinejudge ? $onlinejudge->ideonepass : '');
        $mform->addElement('password', 'ideonepass2', get_string('ideonepass2', 'assignment_onlinejudge'), array('size' => 20));
        $mform->setDefault('ideonepass2', $onlinejudge ? $onlinejudge->ideonepass : '');

        $course_context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
        plagiarism_get_form_elements_module($mform, $course_context);
    }

    /**
     * Any extra validation checks needed for the settings
     * form for this assignment type
     *
     */
    function form_validation($data, $files) {
        $errors = array();

        if ($data['ideonepass'] != $data['ideonepass2']) {
            $errors['ideonepass'] = $errors['ideonepass2'] = get_string('ideonepassmismatch', 'assignment_onlinejudge');
        }

        return $errors;
    }

    /**
     * Create a new program type assignment activity
     *
     * Given an object containing all the necessary data,
     * (defined by the form in mod.html) this function
     * will create a new instance and return the id number
     * of the new instance.
     * The due data is added to the calendar
     * Tests are added to assignment_oj_testcases table
     *
     * @param object $assignment The data from the form on mod.html
     * @return int The id of the assignment
     */
    function add_instance($assignment) {
        // Add assignment instance
        $assignment->id = parent::add_instance($assignment);

        if ($assignment->id) {
            $this->after_add_update($assignment);
        }

        return $assignment->id;
    }

    /**
     * Updates a program assignment activity
     *
     * Given an object containing all the necessary data,
     * (defined by the form in mod.html) this function
     * will update the assignment instance and return the id number
     * The due date is updated in the calendar
     *
     * @param object $assignment The data from the form on mod.html
     * @return int The assignment id
     */
    function update_instance($assignment) {
        // Add assignment instance
        $returnid = parent::update_instance($assignment);

        if ($returnid) {
            $this->after_add_update($assignment);
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
            if (!$DB->delete_records('assignment_oj_submissions', 'submission', $submission->id))
                return false;
        }

        // DELETE tests
        if (!$DB->delete_records('assignment_oj_testcases', 'assignment', $assignment->id)) {
            return false;
        }

        // DELETE programming language
        if (!$DB->delete_records('assignment_oj', 'assignment', $assignment->id)) {
            return false;
        }

        $result = parent::delete_instance($assignment);

        return $result;
    }

    /**
     * This function is called at the end of add_instance
     * and update_instance, to add or update tests and add or update programming language
     * 
     * @param object $assignment the onlinejudge object.
     */
    function after_add_update($assignment) {
        global $DB;

        $onlinejudge = $assignment;
        $old_onlinejudge = $DB->get_record('assignment_oj', array('assignment' => $assignment->id));
        if ($old_onlinejudge) {
            $onlinejudge->id = $old_onlinejudge->id;
            print_object($onlinejudge);
            $DB->update_record('assignment_oj', $onlinejudge);
        } else {
            $onlinejudge->assignment = $onlinejudge->id;
            $DB->insert_record('assignment_oj', $onlinejudge);
        }
    }

    /**
     * Get tests data for current assignment.
     *
     * @return array tests An array of tests objects. All testcase files are read into memory
     */
    function get_tests() {
        global $CFG, $DB;

        $records = $DB->get_records('assignment_oj_testcases', array('assignment' => $this->assignment->id), 'id ASC');
        $tests = array();

        foreach ($records as $record) {
            if ($record->usefile) {
                if (! $record->input = file_get_contents("$CFG->dataroot/{$this->assignment->course}/$record->inputfile"))
                    continue; //Skip case whose file(s) can't be read
                if (! $record->output = file_get_contents("$CFG->dataroot/{$this->assignment->course}/$record->outputfile"))
                    continue; //Skip case whose file(s) can't be read
            }
            $tests[] = $record;
        }

        return $tests;
    }

    /**
     * Append rejudge all link to the teachers' view subimissions link
     */
    function submittedlink($allgroups=false) {

        global $USER, $CFG;

        $parent_link = parent::submittedlink($allgroups);

        $context = get_context_instance(CONTEXT_MODULE,$this->cm->id);
        if (has_capability('mod/assignment:grade', $context))
        {
            $rejudge_link = get_string('rejudgeall','assignment_onlinejudge');
            $testcase_link = '<a href = "'.$CFG->wwwroot.'/mod/assignment/type/onlinejudge/testcase.php?id='.$this->cm->id.'">'.get_string('managetestcases','assignment_onlinejudge').'</a>';
            return $parent_link .'<br />'.$rejudge_link.'<br />'.$testcase_link;
        } else {
            return $parent_link;
        }    
    }

    /**
     * Rejudge all submissions
     * return bool Success
     */
    function rejudge_all() {
        global $CFG, $DB;

        $sql = 'UPDATE '.
            $CFG->prefix.'assignment_oj_submissions '.
            'SET '.
            'judged = 0 '.
            'WHERE '.
            'submission in '.
            '(SELECT id FROM '.$CFG->prefix.'assignment_submissions '.
            'WHERE assignment = '.$this->assignment->id.')';

        return $DB->execute_sql($sql, false);
    }

    /**
     * Display the assignment intro
     *
     */
    function view_intro() {
        global $OUTPUT;

        parent::view_intro();

        echo $OUTPUT->box($this->view_summary(null, true), 'generalbox boxaligncenter', 'intro');
    }

    /**
     * TODO: Change to grade request
     */
    function upload() {

        global $CFG, $USER;

        $oldtimemodified = 0;
        if ($submission = $this->get_submission()) {
            $oldtimemodified = $submission->timemodified;
        }

        parent::upload();

        if ($submission = $this->get_submission()) {
            if ($oldtimemodified != $submission->timemodified) { //submitting successfully.

                $submission->grade = -1;
                $submission->judged = 0;

                $this->update_submission($submission, $oldtimemodified == 0);

                $submission = $this->get_submission();
                $this->update_grade($submission);
            }
        }
    }

    /**
     * Print a link to student submitted file.
     * 
     * @param int $userid User Id
     * @param boolean $return Return the link or print it directly
     */
    function print_student_answer($userid, $return = false) {
        $output = parent::print_student_answer($userid, $return);

        // TODO: Syntax Highlight source code link

        return $output;
    }

    /**
     * Display submit history
     */
    function custom_feedbackform($submission, $return=false) {
        return parent::custom_feedbackform($submission, $return) . $this->view_summary($submission, true);
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

        // TODO: Syntax Highlighert source code link

        if ($return) {
            return $output;
        }
        echo $output;
    }

    /**
     * Display auto generated info about the submission
     */
    function view_summary($submission=null, $return = false) {
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
        $lang = get_string('lang' . $this->onlinejudge->language, 'assignment_onlinejudge');
        $table->data[] = array($item_name, $lang);

        if (is_null($submission))
            $submission = $this->get_submission();

        // Status
        $item_name = get_string('status', 'assignment_onlinejudge').$OUTPUT->help_icon('status', 'assignment_onlinejudge').':';
        $item = get_string('notavailable');
        if (!empty($submission->status)) {
            $item = get_string('status' . $submission->status, 'assignment_onlinejudge');
        }
        $table->data[] = array($item_name, $item);

        // Judge time
        $item_name = get_string('judgetime','assignment_onlinejudge').':';
        $item = get_string('notavailable');
        if (isset($submission->judgetime)) {
            $item = userdate($submission->judgetime).'&nbsp('.get_string('early', 'assignment', format_time(time() - $submission->judgetime)) . ')';
        }
        $table->data[] = array($item_name, $item);

        // Information
        $item_name = get_string('info','assignment_onlinejudge').':';
        $item = get_string('notavailable');
        if (isset($submission->status)) {
            if ($submission->status === 'pending') {
                if (empty($CFG->assignment_oj_daemon_pid)) { //Judge from cron
                    $lastcron = $DB->get_field('modules', 'lastcron', 'name', 'assignment');
                    $left = ceil(($lastcron + $CFG->assignment_oj_cronfreq - time()) / 60);
                    $left = $left > 0 ? $left : 0;
                    $submission->info = get_string('infopending', 'assignment_onlinejudge', $left);
                } else {
                    $submission->info = get_string('notavailable');
                }
            } else if ($submission->status !== 'ac' && $submission->status !== 'ce' && empty($submission->info)) {
                $submission->info = get_string('info'.$submission->status, 'assignment_onlinejudge');
            }

            if (!empty($submission->info)) {
                $item = $submission->info;
            }
        }
        $options = new stdClass();
        $options->para = false;
        $table->data[] = array($item_name, format_text(stripslashes($item), FORMAT_MOODLE, $options));

        // Statistics
        $item_name = get_string('statistics','assignment_onlinejudge').':';
        $item = get_string('notavailable');
        if (isset($submission->id)) {
            $item = '';
            $ac_rate = $this->get_statistics($submission, &$item);
            if (!empty($item)) {
                $item .= '<br />'.get_string('successrate', 'assignment_onlinejudge').': '.round($ac_rate*100, 2).'%';
            }
        }
        $table->data[] = array($item_name, $item);

        // Output (Show to teacher only)
        $context = get_context_instance(CONTEXT_MODULE, $this->cm->id);
        if (has_capability('mod/assignment:grade', $context) && isset($submission->output)) {
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

    function get_submission($userid=0, $createnew=false, $teachermodified=false) {
        global $CFG, $DB;

        if ($submission = parent::get_submission($userid, $createnew, $teachermodified)) {
            //TODO: Construct submission judge infos, such as status etc.
        }

        return $submission;
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
     * @uses SORT_NUMERIC
     * @param int $sizebytes Moodle site $CGF->assignment_oj_max_mem
     * @return array
     */
    static function get_max_memory_usages($sitebytes=0) {
        global $CFG;

        // Get max size
        $maxsize = $sitebytes;
    
        $memusage[$maxsize] = display_size($maxsize);
    
        $sizelist = array(4194304, 8388608, 16777216, 33554432,
                          67108864, 134217728, 268435456, 536870912);
    
        // Allow maxbytes to be selected if it falls outside the above boundaries
        if( isset($CFG->assignment_oj_max_mem) && !in_array($CFG->assignment_oj_max_mem, $sizelist) ){
                $sizelist[] = $CFG->assignment_oj_max_mem;
        }
    
        foreach ($sizelist as $sizebytes) {
           if ($sizebytes < $maxsize) {
               $memusage[$sizebytes] = display_size($sizebytes);
           }
        }
    
        krsort($memusage, SORT_NUMERIC);
    
        return $memusage;
    }
    
    /**
     * This function returns an
     * array of possible CPU time (in seconds) in an array
     *
     * @uses SORT_NUMERIC
     * @param int $time Moodle site $CGF->assignment_oj_max_cpu
     * @return array
     */
    static function get_max_cpu_times($time=0) {
        global $CFG;

        // Get max size
        $maxtime = $time;
    
        $cputime[$maxtime] = get_string('numseconds', 'moodle', $maxtime);
    
        $timelist = array(1, 2, 3, 4, 5, 6, 7, 8, 9,
                          10, 11, 12, 13, 14, 15, 20,
                          25, 30, 40, 50, 60);
    
        // Allow maxtime to be selected if it falls outside the above boundaries
        if( isset($CFG->assignment_oj_max_cpu) && !in_array($CFG->assignment_oj_max_cpu, $timelist) ){
                $cputime[] = $CFG->assignment_oj_max_cpu;
        }
    
        foreach ($timelist as $timesecs) {
           if ($timesecs < $maxtime) {
               $cputime[$timesecs] = get_string('numseconds', 'moodle', $timesecs);
           }
        }
    
        ksort($cputime, SORT_NUMERIC);
    
        return $cputime;
    }
    
    /**
     * Returns an array of installed programming languages indexed and sorted by name
     *
     * @return array The index is the name of the assignment type,the value its full name from the language strings
     */
    function get_languages() {
        global $CFG;
        
        $lang = array ();

        //TODO: Get languages from judgelib
        $lang['example'] = 'Example';

        asort($lang);
        return $lang;
    }


    /**
     * return grade
     * status means ac, wa, pe and etc.
     * fraction means max fraction in modgrade, :-)
     */
    function grade_marker($status, $fraction) {
        $grades = array('pending' => -1,
                        'ac'      => $fraction * $this->assignment->grade,
                        'wa'      => 0,
                        'pe'      => $fraction * $this->assignment->grade * $this->onlinejudge->ratiope,
                        're'      => 0,
                        'tle'     => 0,
                        'mle'     => 0,
                        'ole'     => 0,
                        'ce'      => 0,
                        'ie'      => -1,
                        'rf'      => 0,
                        'at'      => 0);

        return $grades[$status];
    }


    function merge_results($results, $testcases, $appends = null) {
        $result = null;
        $result->output = '';
        $result->info = '';
        $result->grade = 0;

        reset($testcases);

        $result->output = '';
        foreach ($results as $i => $one) {
            $testcase = each($testcases);
            $result->output .= $one->output;

            $result->info .= get_string('case', 'assignment_onlinejudge', $i+1).' '.get_string('status'.$one->status, 'assignment_onlinejudge');
            if ($one->status === 'wa' && !empty($testcase['value']->feedback)) {
                $result->info .= '('.$testcase['value']->feedback.')';
            } else if ($one->status !== 'ac') {
                $result->info .= '('.get_string('info'.$one->status, 'assignment_onlinejudge').')';
            }
            $result->info .= '<br />';

            if (!empty($appends))
                $result->info .= $appends[$i];

            $grade = $this->grade_marker($one->status, $testcase['value']->subgrade);
            if ($grade != -1)
                $result->grade += $grade;

            if ($i == 0)
                $result->status = $one->status;
            else if ($result->status !== $one->status)
                $result->status = 'multiple';
        }

        //Make sure the grade is not too big
        $result->grade = min($result->grade, $this->assignment->grade);

        return $result;
    }


    /**
     * Evaluate student submissions
     */
    function cron() {

        global $CFG;

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
}

?>
