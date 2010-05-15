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
    set_config('assignment_oj_max_mem', 16 * 1024 *1024);
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


require_once($CFG->dirroot.'/mod/assignment/type/uploadsingle/assignment.class.php');
require_once($CFG->dirroot.'/lib/filelib.php');
require_once($CFG->dirroot.'/lib/questionlib.php'); //for get_grade_options()
require_once($CFG->dirroot.'/lib/adminlib.php'); //for set_cron_lock()

/**
 * Extends the uploadsingle assignment class
 * 
 * @author Arkaitz Garro, Sunner Sun
 */
class assignment_onlinejudge extends assignment_uploadsingle {

    var $onlinejudge;

    // ideone.com supports the following languages.
    // id_in_moodle => id_in_ideone
    var $ideone_langs = array(
        'ada_ideone'                     => 7,                      
        'assembler_ideone'               => 13,                  
        'awk_gawk_ideone'                => 104,            
        'awk_mawk_ideone'                => 105,             
        'bash_ideone'                    => 28,             
        'bc_ideone'                      => 110,                        
        'brainfxxk_ideone'               => 12,            
        'c_ideone'                       => 11,                     
        'csharp_ideone'                  => 27,                        
        'cpp_ideone'                     => 1,                  
        'c99_strict_ideone'              => 34,             
        'clojure_ideone'                 => 111,                
        'cobol_ideone'                   => 106,                      
        'common_lisp_clisp_ideone'       => 32,    
        'd_dmd_ideone'                   => 102,                 
        'erlang_ideone'                  => 36,                     
        'forth_ideone'                   => 107,                     
        'fortran_ideone'                 => 5,                 
        'go_ideone'                      => 114,                
        'haskell_ideone'                 => 21,                   
        'icon_ideone'                    => 16,             
        'intercal_ideone'                => 9,                 
        'java_ideone'                    => 10,                    
        'javascript_rhino_ideone'        => 35,         
        'javascript_spidermonkey_ideone' => 112,  
        'lua_ideone'                     => 26,                       
        'nemerle_ideone'                 => 30,                  
        'nice_ideone'                    => 25,                     
        'ocaml_ideone'                   => 8,                      
        'pascal_fpc_ideone'              => 22,             
        'pascal_gpc_ideone'              => 2,            
        'perl_ideone'                    => 3,              
        'php_ideone'                     => 29,            
        'pike_ideone'                    => 19,            
        'prolog_gnu_ideone'              => 108,   
        'prolog_swi_ideone'              => 15,      
        'python_ideone'                  => 4,             
        'ruby_ideone'                    => 17,             
        'scala_ideone'                   => 39,             
        'scheme_guile_ideone'            => 33,    
        'smalltalk_ideone'               => 23,          
        'tcl_ideone'                     => 38,              
        'text_ideone'                    => 62,               
        'unlambda_ideone'                => 115,         
        'vbdotnet_ideone'                => 101, 
        'whitespace_ideone'              => 6
    );

    function assignment_onlinejudge($cmid='staticonly', $assignment=NULL, $cm=NULL, $course=NULL) {
        parent::assignment_uploadsingle($cmid, $assignment, $cm, $course);
        $this->type = 'onlinejudge';

        if (isset($this->assignment->id)) {
            $this->onlinejudge = get_record('assignment_oj', 'assignment', $this->assignment->id);
        }
    }

    /**
     * Print the form for this assignment type
     * 
     * @param $mform object Allready existant form
     */
    function setup_elements(& $mform, & $modform) {
        global $CFG, $COURSE;

        $add       = optional_param('add', '', PARAM_ALPHA);
        $update    = optional_param('update', 0, PARAM_INT);

        // Get course module instance
        $cm = null;
        $onlinejudge = null;
        if (!empty($update)) {
            $cm = get_record('course_modules', 'id', $update);
            $onlinejudge = get_record('assignment_oj', 'assignment', $cm->instance);
        }

        $ynoptions = array(0 => get_string('no'), 1 => get_string('yes'));

        // Programming languages
        $choices = $this->get_languages();
        $mform->addElement('select', 'lang', get_string('assignmentlangs', 'assignment_onlinejudge'), $choices);
        $mform->setDefault('lang', $onlinejudge ? $onlinejudge->language : 'c');

        // Presentation error grade ratio
        unset($choices);
        $choices = get_grade_options()->gradeoptions; // Steal from question lib
        $mform->addElement('select', 'ratiope', get_string('ratiope', 'assignment_onlinejudge'), $choices);
        $mform->setHelpButton('ratiope', array('ratiope', get_string('descratiope', 'assignment_onlinejudge'), 'assignment_onlinejudge'));
        $mform->setDefault('ratiope', $onlinejudge ? $onlinejudge->ratiope : 0);

        // Max. CPU time
        unset($choices);
        $choices = $this->get_max_cpu_times($CFG->assignment_oj_max_cpu);
        $mform->addElement('select', 'cpulimit', get_string('cpulimit', 'assignment_onlinejudge'), $choices);
        $mform->setDefault('cpulimit', $onlinejudge ? $onlinejudge->cpulimit : 1);

        // Max. memory usage
        unset($choices);
        $choices = $this->get_max_memory_usages($CFG->assignment_oj_max_mem);
        $mform->addElement('select', 'memlimit', get_string('memlimit', 'assignment_onlinejudge'), $choices);
        $mform->setDefault('memlimit', $onlinejudge ? $onlinejudge->memlimit : $CFG->assignment_oj_max_mem);

        // Allow resubmit
        $mform->addElement('select', 'resubmit', get_string('allowresubmit', 'assignment'), $ynoptions);
        $mform->setHelpButton('resubmit', array('resubmit',get_string('allowresubmit','assignment'), 'assignment'));
        $mform->setDefault('resubmit', 1);

        // Compile only?
        $mform->addElement('select', 'compileonly', get_string('compileonly', 'assignment_onlinejudge'), $ynoptions);
        $mform->setHelpButton('compileonly', array('compileonly', get_string('compileonly', 'assignment_onlinejudge'), 'assignment_onlinejudge'));
        $mform->setDefault('compileonly', $onlinejudge ? $onlinejudge->compileonly : 0);

        // Email teachers
        $mform->addElement('select', 'emailteachers', get_string('emailteachers', 'assignment'), $ynoptions);
        $mform->setHelpButton('emailteachers', array('emailteachers',get_string('emailteachers','assignment'), 'assignment'));
        $mform->setDefault('emailteachers', 0);

        // Submission max bytes
        $choices = get_max_upload_sizes($CFG->maxbytes, $COURSE->maxbytes);
        $choices[1] = get_string('uploadnotallowed');
        $choices[0] = get_string('courseuploadlimit') . ' (' . display_size($COURSE->maxbytes) . ')';
        $mform->addElement('select', 'maxbytes', get_string('maximumfilesize', 'assignment_onlinejudge'), $choices);
        $mform->setDefault('maxbytes', $CFG->assignment_maxbytes);

        // Testcases form ----------------------------------------------------------------------------------------
        $mform->addElement('header', 'testcases', get_string('testcases', 'assignment_onlinejudge'));
        $mform->setHelpButton('testcases', array('testcases', get_string('testcases', 'assignment_onlinejudge'), 'assignment_onlinejudge'));
        $repeatarray = array();
        $repeatarray[] = &$mform->createElement('modgrade', 'subgrade', get_string('grade'));
        $repeatarray[] = &$mform->createElement('textarea', 'input', get_string('input', 'assignment_onlinejudge'), 'wrap="virtual" rows="1" cols="50"');
        $mform->setType('input', PARAM_RAW);
        $repeatarray[] = &$mform->createElement('textarea', 'output', get_string('output', 'assignment_onlinejudge'), 'wrap="virtual" rows="1" cols="50"');
        $mform->setType('output', PARAM_RAW);
        $repeatarray[] = &$mform->createElement('text', 'feedback', get_string('feedbackforwa', 'assignment_onlinejudge'), array('size' => 50));
        $mform->setType('feedbacktext', PARAM_RAW);

        $repeateloptions = array();
        $repeateloptions['subgrade']['default'] = 0;

        $tests = $this->get_tests($cm);
        $numtestcases = max(count($tests) + 1, NUMTESTS);

        $modform->repeat_elements($repeatarray, $numtestcases, $repeateloptions, 'boundary_repeats', 'add_testcases', 1, get_string('addtestcases', 'assignment_onlinejudge', 1), true);
        $mform->setDefault('subgrade[0]', 100);

        if ($tests) {
            $i = 0;
            foreach ($tests as $tstObj => $tstValue) {
                $mform->setDefault("input[$i]", $tstValue->input);
                $mform->setDefault("output[$i]", $tstValue->output);
                $mform->setDefault("feedback[$i]", $tstValue->feedback);
                $mform->setDefault("subgrade[$i]", $tstValue->subgrade);
                $i++;
            }
        }
    }

    /**
     * Create a new program type assignment activity
     *
     * Given an object containing all the necessary data,
     * (defined by the form in mod.html) this function
     * will create a new instance and return the id number
     * of the new instance.
     * The due data is added to the calendar
     * Tests are added to assignment_oj_tests table
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
        global $CFG;

        // DELETE submissions results
        $submissions = get_records('assignment_submissions', 'assignment', $assignment->id);
        foreach ($submissions as $submission) {
            if (!delete_records('assignment_oj_results', 'submission', $submission->id))
                return false;
            if (!delete_records('assignment_oj_submissions', 'submission', $submission->id))
                return false;
        }

        // DELETE tests
        if (!delete_records('assignment_oj_tests', 'assignment', $assignment->id)) {
            return false;
        }

        // DELETE programming language
        if (!delete_records('assignment_oj', 'assignment', $assignment->id)) {
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
        // Delete actual tests
        delete_records('assignment_oj_tests', 'assignment', $assignment->id);

        // Insert new tests
        for ($i = 0; $i < $assignment->boundary_repeats; $i++) {
            // Check if tests is not empty
            if(!empty($assignment->input[$i]) || !empty($assignment->output[$i])
                || !empty($assignment->feedback[$i]) || !empty($assignment->subgrade[$i])) {
                    $test = new Object();
                    $test->assignment = $assignment->id;
                    $test->input = str_replace("\r", "", $assignment->input[$i]);
                    $test->output = $assignment->output[$i];
                    $test->feedback = $assignment->feedback[$i];
                    $test->subgrade = $assignment->subgrade[$i];

                    if (!insert_record('assignment_oj_tests', $test)) {
                        error('Can\'t insert testcase');
                    }

                    unset ($test);
                }
        }

        $onlinejudge = new Object();
        $onlinejudge = get_record('assignment_oj', 'assignment', $assignment->id);
        if ($onlinejudge) {
            $onlinejudge->language = $assignment->lang;
            $onlinejudge->memlimit = $assignment->memlimit;
            $onlinejudge->cpulimit = $assignment->cpulimit;
            $onlinejudge->compileonly = $assignment->compileonly;
            $onlinejudge->ratiope = $assignment->ratiope;
            update_record('assignment_oj', $onlinejudge);
        } else {
            $onlinejudge->assignment = $assignment->id;
            $onlinejudge->language = $assignment->lang;
            $onlinejudge->memlimit = $assignment->memlimit;
            $onlinejudge->cpulimit = $assignment->cpulimit;
            $onlinejudge->compileonly = $assignment->compileonly;
            $onlinejudge->ratiope = $assignment->ratiope;
            insert_record('assignment_oj', $onlinejudge);
        }
    }

    /**
     * Get tests data for current assignment.
     *
     * @param object $cm Course module
     * @return array tests An array of tests objects
     */
    function get_tests($cm=null) {
        if (isset($cm->instance))
            $instanceid = $cm->instance;
        else if (isset($this->cm->instance))
            $instanceid = $this->cm->instance;
        else
            return null;

        return get_records('assignment_oj_tests', 'assignment', $instanceid, 'id ASC');
    }

    /**
     * Append rejudge all link to the teachers' view subimissions link
     */
    function submittedlink($allgroups=false) {

        global $USER;

        $parent_link = parent::submittedlink($allgroups);

        $context = get_context_instance(CONTEXT_MODULE,$this->cm->id);
        if (has_capability('mod/assignment:grade', $context))
        {
            $rejudge_link = element_to_popup_window ('link', '/mod/assignment/type/onlinejudge/rejudge.php?id='.$this->cm->id, null, 
                get_string('rejudgeall','assignment_onlinejudge'), 
                330, 500, null, null, true, null, null);
            return $parent_link .'<br />'.$rejudge_link;
        } else {
            return $parent_link;
        }    
    }

    /**
     * Rejudge all submissions
     * return bool Success
     */
    function rejudge_all() {
        global $CFG;

        $sql = 'UPDATE '.
            $CFG->prefix.'assignment_oj_submissions '.
            'SET '.
            'judged = 0 '.
            'WHERE '.
            'submission in '.
            '(SELECT id FROM '.$CFG->prefix.'assignment_submissions '.
            'WHERE assignment = '.$this->assignment->id.')';

        return execute_sql($sql, false);
    }

    /**
     * Display the assignment intro
     *
     */
    function view_intro() {
        parent::view_intro();
        print_simple_box($this->view_summary(null, true), 'center', '', '', 5, 'generalbox', 'intro');
    }

    /**
     * Upload file
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
        global $CFG, $USER;

        $output = '';

        if ($basedir = $this->file_area($userid)) {
            if ($files = get_directory_list($basedir)) {
                require_once($CFG->libdir.'/filelib.php');
                foreach ($files as $key => $file) {

                    $icon = mimeinfo('icon', $file);
                    // Syntax Highlighert source code
                    $viewlink = link_to_popup_window('/mod/assignment/type/onlinejudge/source.php?id='
                        .$this->cm->id.'&amp;userid='.$userid.'&amp;file='.$file,
                        $file . 'sourcecode', $file, 500, 740, $file, 'none', true, 'button'.$userid);

                    //died right here
                    //require_once($ffurl);
                    $output = '<img src="'.$CFG->pixpath.'/f/'.$icon.'" class="icon" alt="'.$icon.'" />'.$viewlink.'<br />';
                }
            }
        }

        $submission = $this->get_submission($userid);
        if ($submission->timemodified) {
            $output = '<strong>'.get_string('status'.$submission->status, 'assignment_onlinejudge') . ': </strong>'.$output;
        }
        $output = '<div class="files">'.$output.'</div>';

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
        global $CFG, $USER;

        if (!$userid) {
            if (!isloggedin()) {
                return '';
            }
            $userid = $USER->id;
        }

        $filearea = $this->file_area_name($userid);

        $output = '';

        if ($basedir = $this->file_area($userid)) {
            if ($files = get_directory_list($basedir)) {
                require_once($CFG->libdir.'/filelib.php');
                foreach ($files as $key => $file) {

                    $icon = mimeinfo('icon', $file);
                    $ffurl = get_file_url("$filearea/$file", array('forcedownload'=>1));

                    // Syntax Highlighert source code
                    $viewlink = link_to_popup_window('/mod/assignment/type/onlinejudge/source.php?id='
                        .$this->cm->id.'&amp;userid='.$userid.'&amp;file='.$file,
                        $file . 'sourcecode', get_string('preview'), 500, 740, $file, 'none', true, 'button'.$userid);

                    $output .= '<img src="'.$CFG->pixpath.'/f/'.$icon.'" class="icon" alt="'.$icon.'" />'.
                        '<a href="'.$ffurl.'" >'.$file.'</a> ('.$viewlink.')<br />';
                }
            }
        }

        $output = '<div class="files">'.$output.'</div>';

        if ($return) {
            return $output;
        }
        echo $output;
    }

    /**
     * Display auto generated info about the assignment
     */
    function view_summary($submission=null, $return = false) {
        global $USER, $CFG;

        $table = new Object();
        $table->id = 'summary';
        $table->class = 'generaltable';
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
        $item_name = get_string('status').helpbutton('status', get_string('status'), 'assignment_onlinejudge', true, false, '', true).':';
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
                    $lastcron = get_field('modules', 'lastcron', 'name', 'assignment');
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

        // Output (Show to teacher only)
        $context = get_context_instance(CONTEXT_MODULE, $this->cm->id);
        if (has_capability('mod/assignment:grade', $context) && isset($submission->output)) {
            $table->data[] = array(get_string('output', 'assignment_onlinejudge').':', format_text(stripslashes($submission->output), FORMAT_PLAIN));
        }

        $output = print_table($table, true);

        if($return)
            return $output;
            
        echo $output;
    }

    function get_submission($userid=0, $createnew=false, $teachermodified=false) {
        global $CFG;

        $submission = parent::get_submission($userid, $createnew, $teachermodified);

        if ($submission) {

            $onlinejudge = get_record('assignment_oj_submissions', 'submission', $submission->id);
            if (empty($onlinejudge) && $createnew) {
                $newsubmission = new Object; 
                $newsubmission->submission = $submission->id;
                if (!insert_record("assignment_oj_submissions", $newsubmission)) {
                    error("Could not insert a new empty onlinejudge submission");
                }
                unset($newsubmission);
            }

            $onlinejudge = get_record('assignment_oj_submissions', 'submission', $submission->id);
            if ($onlinejudge) {
                $submission->judged = $onlinejudge->judged;
                $submission->oj_id = $onlinejudge->id;
            } else {
                $submission->judged = 0;
            }

            if ($submission->judged) {
                $results = get_recordset_select('assignment_oj_results', 'submission = '.$submission->id.' AND judgetime >= '.$submission->timemodified, 'judgetime DESC', '*', '', '1');
                $results = recordset_to_array($results);
                if ($results) {
                    $result = array_pop($results);
                    $submission->info = $result->info;
                    $submission->status = $result->status;
                    $submission->judgetime = $result->judgetime;
                    $submission->output = $result->output;
                } else {
                    $submission->judged = 0; //It is been judging
                    $submission->status = 'pending';
                }
            } else if (($files = get_directory_list($CFG->dataroot.'/'.$this->file_area_name($userid))) && count($files) != 0) { // Submitted but unjudged
                $submission->status = 'pending';
            }
        }

        return $submission;
    }

    function update_submission($submission, $new_oj=false) {

        update_record('assignment_submission', $submission);

        if ($new_oj) {
            $submission->submission = $submission->id;
            insert_record('assignment_oj_submissions', $submission);
        } else {
            $submission->id = $submission->oj_id;
            update_record('assignment_oj_submissions', $submission);
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
        
        // Get local languages
        $dir = $CFG->dirroot . '/mod/assignment/type/onlinejudge/languages/';
        $files = get_directory_list($dir);
        $names = preg_replace('/\.(\w+)/', '', $files); // Replace file extension with nothing
        foreach ($names as $name) {
            $lang[$name] = get_string('lang'.$name, 'assignment_onlinejudge');
        }

        // Get ideone.com languages
        foreach ($this->ideone_langs as $name => $id) {
            $lang[$name] = get_string('lang'.$name, 'assignment_onlinejudge');
        }

        asort($lang);
        return $lang;
    }

    /**
     * Get one unjudged submission and set it as judged
     * If all submissions have been judged, return false
     * The function can be reentranced
     */
    function get_unjudged_submission() {
        global $CFG;

        while (!set_cron_lock('assignment_judging', time() + 10)) {}
        //set_cron_lock('assignment_judging', time()+10);

        $sql = 'SELECT 
                    sub.*, epsub.judged, epsub.submission, epsub.id AS epsubid '.
               'FROM '
                    .$CFG->prefix.'assignment_submissions AS sub, '
                    .$CFG->prefix.'assignment_oj_submissions AS epsub '.
               'WHERE '.
                    'sub.id = epsub.submission '.
                    'AND epsub.judged = 0 ';

        $submissions = get_records_sql($sql, '', 1);
        $submission = null;
        if ($submissions) {
            $submission = array_pop($submissions);
            // Set judged mark
            set_field('assignment_oj_submissions', 'judged', 1, 'id', $submission->epsubid);
        }

        set_cron_lock('assignment_judging', null);

        return $submission;
    }

    function diff($answer, $output) {
        $answer = strtr(trim($answer), array("\r\n" => "\n", "\n\r" => "\n"));
        $output = trim($output);

        if (strcmp($answer, $output) == 0)
            return 'ac';
        else {
            $tokens = Array();
            $tok = strtok($answer, " \n\r\t");
            while ($tok) {
                $tokens[] = $tok;
                $tok = strtok(" \n\r\t");
            }

            $tok = strtok($output, " \n\r\t");
            foreach ($tokens as $anstok) {
                if (!$tok || $tok !== $anstok)
                    return 'wa';
                $tok = strtok(" \n\r\t");
            }

            return 'pe';
        }
    }

    function run_in_sandbox($exec_file, $case) {
        global $CFG;

        $ret = new Object();
        $ret->output = '';
        $result = array('pending', 'ac', 'rf', 'mle', 'ole', 'tle', 're', 'at', 'ie');
        /* Only root can chroot(set jail)
        $jail = $CFG->dataroot.'/temp/sandbox_jail/';
        if (!check_dir_exists($jail, true, true)) {
            mtrace("Can't mkdir ".$jail);
            return 'ie';
        }
         */

        $sand = $CFG->dirroot . '/mod/assignment/type/onlinejudge/sandbox/sand';
        if (!is_executable($sand)){
            $ret->status = 'ie';
            return $ret;
        }

        $sand .= ' -l cpu='.($this->onlinejudge->cpulimit*1000).' -l memory='.$this->onlinejudge->memlimit.' -l disk=512000 '.$exec_file; 

        $descriptorspec = array(
            0 => array('pipe', 'r'),  // stdin is a pipe that the child will read from
            1 => array('file', $exec_file.'.out', 'w'),  // stdout is a file to write to
            2 => array('pipe', '$exec_file.err', 'w') // stderr is a file to write to
        );

        $proc = proc_open($sand, $descriptorspec, $pipes);

        if (!is_resource($proc)) {
            $ret->status = 'ie';
            return $ret;
        }

        fwrite($pipes[0], $case->input);
        fclose($pipes[0]);


        $return_value = proc_close($proc);
        $ret->output = file_get_contents($exec_file.'.out');

        if ($return_value == 255) {
            $ret->status = 'ie';
            return $ret;
        } else if ($return_value >= 2) {
            $ret->status = $result[$return_value];
            return $ret;
        } else if ($return_value == 0) {
            mtrace('Pending? Why?');
            exit();
        }

        $ret->status = $this->diff($case->output, $ret->output);
        return $ret;
    }

    /**
     * return grage
     * status means ac, wa, pe and etc.
     * maxgrade means maxgrade, :-)
     */
    function grade_marker($status, $maxgrade) {
        $grades = array('pending' => -1,
                        'ac'      => $maxgrade,
                        'wa'      => 0,
                        'pe'      => $maxgrade * $this->onlinejudge->ratiope,
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

    // Compile submission $sub in temp_dir
    // return result class on success, false on error
    function compile($sub, $temp_dir) {
        global $CFG;
        $result = false;

        if ($content = $this->get_submission_file_content($sub->userid)) {

            $file = 'prog.c';
            file_put_contents("$temp_dir/$file", $content);
            $compiler = $CFG->dirroot.'/mod/assignment/type/onlinejudge/languages/'.$this->onlinejudge->language.'.sh';
            if (!is_executable($compiler)) {
                $result->status = 'ie';
                $result->info = get_string('cannotruncompiler', 'assignment_onlinejudge');
                break;
            }

            $output = null;
            $return = null;
            $command = "$compiler $temp_dir/$file $temp_dir/a.out 2>&1";
            exec($command, $output, $return);

            if ($return) { //Compile error
                $result->status = 'ce';
            } else { 
                $result->status = 'compileok';
            }

            //strip path info
            $output = str_replace($temp_dir.'/', '', $output);
            $error = htmlspecialchars(implode("\n", $output));
            $result->info = addslashes($error);

            //Compile the first file only
            return $result;
        }          

        return $result;
    }

    function merge_results($results, $testcases, $appends = null) {
        $result = null;
        $result->output = '';
        $result->info = '';
        $result->grade = 0;

        reset($testcases);

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

    // Judge begins here
    function judge($sub) {
        
        global $CFG;

        $ret = false;

        $judge_type = substr($this->onlinejudge->language, strrpos($this->onlinejudge->language, '_'));

        if($judge_type == '_ideone') {
            $result = $this->judge_ideone($sub);
        } else {
            $result = $this->judge_local($sub);
        }

        if ($result) {
            $result->submission = $sub->id;
            $result->judgetime = time();
            $result->info = addslashes($result->info);
            $result->output = addslashes($result->output);
            if ($ret = insert_record('assignment_oj_results', $result, false)) {
                $newsub = null;
                $newsub->id = $sub->id;
                $newsub->teacher = get_admin()->id;
                $newsub->timemarked = time();
                $newsub->grade = $result->grade;
                $ret = update_record('assignment_submissions', $newsub);
                $this->update_grade($sub);
            }
        }

        return $ret;
    }

    // Judge submission $sub in local
    // return result object on success, false on error
    function judge_local($sub) {

        global $CFG;

        // Make temp dir
        $temp_dir = $CFG->dataroot.'/temp/assignment_onlinejudge/'.$sub->id;
        if (!check_dir_exists($temp_dir, true, true)) {
            mtrace("Can't mkdir ".$temp_dir);
            return false;
        }

        if ($result = $this->compile($sub, $temp_dir)) {
            $result->grade = -1;
            if ($result->status === 'compileok' && !$this->onlinejudge->compileonly) { //Run and test!
                $results = array();
                $cases = $this->get_tests();
                foreach ($cases as $case) {
                    $results[] = $this->run_in_sandbox($temp_dir.'/a.out', $case);
                }

                $result = $this->merge_results($results, $cases);
            } else if ($result->status === 'ce') {
                $result->grade = $this->grade_marker('ce', $this->assignment->grade);
                $result->output = '';
            }
        }

        // Clean temp dir
        fulldelete($temp_dir);

        return $result;
    }


    // Judge submission $sub in ideone.com
    // return result object on success, false on error
    function judge_ideone($sub){
        global $CFG;

        // creating soap client


        $client = new SoapClient("http://ideone.com/api/1/service.wsdl");

        $user = $CFG->assignment_oj_ideone_username;                                               
        $pass = $CFG->assignment_oj_ideone_password;

        if ($source = $this->get_submission_file_content($sub->userid)) {
            $results = array();
            $cases = $this->get_tests();

            $status_ideone = array(
                11  => 'ce',
                12  => 're',
                13  => 'tle',
                15  => 'ok',
                17  => 'mle',
                19  => 'rf',
                20  => 'ie'
            );

            foreach ($cases as $case) {
                $webid = $client->createSubmission($user,$pass,$source,$this->ideone_langs[$this->onlinejudge->language],$case->input,true,true);     

                while(1){
                    sleep($CFG->assignment_oj_ideone_delay); 
                    $status =  $client->getSubmissionStatus($user, $pass, $webid['link']);
                    if(!$status['status'])
                        break;
                }

                $details = $client->getSubmissionDetails($user,$pass,$webid['link'],false,true,true,true,true,false);         

                $result->status = $status_ideone[$details['result']];

                // If got ce or compileonly, don't need to test other case
                if ($result->status == 'ce' || $this->onlinejudge->compileonly) {
                    if ($result->status != 'ce' && $result->status != 'ie')
                        $result->status = 'compileok';
                    $result->info = $details['cmpinfo'] . '<br />'.get_string('ideonelogo', 'assignment_onlinejudge');
                    $result->grade = $this->grade_marker('ce', $this->assignment->grade);
                    return $result;
                }

                // Check for wa, pe or accept
                if ($result->status == 'ok') {
                    $result->output = $details['output'];
                    $result->status = $this->diff($case->output, $result->output);
                }

                $results[] = $result;
            }

            $result = $this->merge_results($results, $cases);
            $result->info .= '<br />'.get_string('ideonelogo', 'assignment_onlinejudge');
            return $result;
        } else {
            return false;
        }
    }       

    /**
     * Evaluate student submissions
     */
    function cron() {

        global $CFG;

        // Detect the frequence of cron
        $lastcron = get_field('modules', 'lastcron', 'name', 'assignment');
        if ($lastcron) {
            set_config('assignment_oj_cronfreq', time() - $lastcron);
        }

        // There are two judge routines
        //  1. Judge only when cron job is running. 
        //  2. After installation, the first cron running will fork a daemon to be judger.
        // Routine two works only when the cron job is executed by php cli
        //
        if (function_exists('pcntl_fork')) { // pcntl_fork supported. Use routine two
            $this->run_daemon();
        } else if ($CFG->assignment_oj_judge_in_cron) { // pcntl_fork is not supported. So use routine one if configured.
            $this->judge_all_unjudged();
        }
    }

    function run_daemon() 
    {
        global $CFG, $db;

        if(empty($CFG->assignment_oj_daemon_pid) || !posix_kill($CFG->assignment_oj_daemon_pid, 0)){ // No daemon is running
            $pid = pcntl_fork(); 

            if ($pid == -1) {
                mtrace('Could not fork');
            } else if ($pid > 0){ //Parent process
                //Reconnect db, so that the parent won't close the db connection shared with child after exit.
                reconnect_db();

                set_config('assignment_oj_daemon_pid' , $pid);
            } else { //Child process
                $this->daemon(); 
            }
        }
    }

    function daemon()
    {
        global $CFG;

        mtrace('Judge daemon created. PID = ' . posix_getpid());

        // Start a new sesssion. So it works like a daemon
        $sid = posix_setsid();
        if ($sid < 0) {
            mtrace('Can not setsid');
            exit;
        }
        
        //Redirect error output to php log
        $CFG->debugdisplay = false;
        @ini_set('display_errors', '0');
        @ini_set('log_errors', '1');

        // Close unused fd
        fclose(STDIN);
        fclose(STDOUT);
        fclose(STDERR);

        reconnect_db();

        // Handle SIGTERM so that can be killed without pain
        declare(ticks = 1); // tick use required as of PHP 4.3.0
        pcntl_signal(SIGTERM, 'sigterm_handler');

        set_config('assignment_oj_daemon_pid' , posix_getpid());

        // Run forever until being killed
        while(!empty($CFG->assignment_oj_daemon_pid)){
            global $db;

            $this->judge_all_unjudged();

            // If error occured, reconnect db
            if ($db->ErrorNo())
                reconnect_db();

            //Check interval is 5 seconds
            sleep(5);
        }
    }

    // Judge all unjudged submissions
    function judge_all_unjudged()
    {
        global $CFG;
        while ($submission = $this->get_unjudged_submission()) {
            $cm = get_coursemodule_from_instance('assignment', $submission->assignment);
            $this->assignment_onlinejudge($cm->id);

            $this->judge($submission);
        }
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

function sigterm_handler($signo)
{
    set_config('assignment_oj_daemon_pid' , '0');
}

function reconnect_db()
{
    global $db;
    // Reconnect db
    $db->Close();

    while (!$db->NConnect())
        sleep(5);

    configure_dbconnection();
}
?>
