<?php
// $Id: assignment.class.php,v 1.7 2007/09/04 09:27:20 arkaitz_garro Exp $

//define('ASSIGNMENT_STATUS_SUBMITTED', 'submitted');
define('NUMTESTS', 1); // Default number of test cases
define('ASSIGNMENT_PROGRAM_MAX_CPU', 10); // Default maximum cpu time (seconds) for all assignments
define('ASSIGNMENT_PROGRAM_MAX_MEM', 16777216); // Default memory usage (bytes) for all assignments

define('ASSIGNMENT_PROGRAM_DEFAULT_JUDGER', 'sandbox'); // Default judger

if (!isset($CFG->assignment_max_cpu)) {
    set_config('assignment_max_cpu', ASSIGNMENT_PROGRAM_MAX_CPU);
}

if (!isset($CFG->assignment_max_mem)) {
    set_config('assignment_max_mem', ASSIGNMENT_PROGRAM_MAX_MEM);
}

if (!isset($CFG->assignment_judger)) {
    set_config('assignment_judger', ASSIGNMENT_PROGRAM_DEFAULT_JUDGER);
}

require_once($CFG->dirroot.'/mod/assignment/type/uploadsingle/assignment.class.php');

/**
 * Extends the uploadsingle assignment class
 * 
 * @author Arkaitz Garro, Sunner Sun
 */
class assignment_onlinejudge extends assignment_uploadsingle {

    var $onlinejudge;
    
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
    function setup_elements(& $mform) {
        global $CFG, $COURSE;
        
        $add       = optional_param('add', '', PARAM_ALPHA);
        $update    = optional_param('update', 0, PARAM_INT);
        
        // Get course module instance
        $cm = false;
        if (!empty($update)) {
            $cm = get_record('course_modules', 'id', $update);
        }
        
        $lang = 'c'; // Language by default
        if($cm) {
            $onlinejudge = get_record('assignment_oj', 'assignment', $cm->instance);
            $lang = $onlinejudge->language;
        }
        
        $ynoptions = array(0 => get_string('no'), 1 => get_string('yes'));
        
        // Programming languages
        $choices = $this->get_languages();
        $mform->addElement('select', 'lang', get_string('assignmentlangs', 'assignment_onlinejudge'), $choices);
        $mform->setHelpButton('lang', array('lang',get_string('assignmentlangs','assignment_onlinejudge'),'assignment'));
        $mform->setDefault('lang', $lang);
        
        // Cron date
        // Get assignment cron frequency
        if(get_field('modules','cron','name','assignment')) {
            $mform->addElement('select', 'var1', get_string('duejudge', 'assignment_onlinejudge'), $ynoptions);
            $mform->setHelpButton('var1', array('timecron',get_string('crondate','assignment_onlinejudge'), 'assignment'));
            $mform->setDefault('var1', 0);
        }
        
        // Max. CPU time
        unset($choices);
        $choices = $this->get_max_cpu_times($CFG->assignment_max_cpu);
        $mform->addElement('select', 'var2', get_string('maximumcpu', 'assignment_onlinejudge'), $choices);
        $mform->setHelpButton('var2', array('maximumcpu',get_string('maximumcpu','assignment_onlinejudge'), 'assignment'));
        $mform->setDefault('var2', $CFG->assignment_max_cpu);
        
        // Max. memory usage
        unset($choices);
        $choices = $this->get_max_memory_usages($CFG->assignment_max_mem);
        $mform->addElement('select', 'var3', get_string('maximummem', 'assignment_onlinejudge'), $choices);
        $mform->setHelpButton('var3', array('maximummem',get_string('maximummem','assignment_onlinejudge'), 'assignment'));
        $mform->setDefault('var3', $CFG->assignment_max_mem);
        
        // Allow resubmit
        $mform->addElement('select', 'resubmit', get_string('allowresubmit', 'assignment'), $ynoptions);
        $mform->setHelpButton('resubmit', array('resubmit',get_string('allowresubmit','assignment'), 'assignment'));
        $mform->setDefault('resubmit', 0);
        
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
        
        // Tests form
        $mform->addElement('header', 'tests', get_string('tests', 'assignment_onlinejudge'));
        
        // Get tests data
        $tests = array ();
        $tests = $this->get_tests($cm);
        if ($tests) {
            // Tests allready defined (update assignment)
            $i = 1;
            foreach ($tests as $tstObj => $tstValue) {
                $mform->addElement('textarea', "input[$i]", get_string('input', 'assignment_onlinejudge') . $i);
                $mform->setDefault("input[$i]",$tstValue->input);
                $mform->addElement('textarea', "output[$i]", get_string('output', 'assignment_onlinejudge') . $i);
                $mform->setDefault("output[$i]",$tstValue->output);
                
                $i++;
            }
        } else {
            // New assignment
            for ($i = 1; $i <= NUMTESTS; $i++) {
                $mform->addElement('textarea', "input[$i]", get_string('input', 'assignment_onlinejudge') . $i);
                $mform->addElement('textarea', "output[$i]", get_string('output', 'assignment_onlinejudge') . $i);
            }
        }
        
        $mform->addRule('input[1]', null, 'required', null, 'client');
        $mform->addRule('output[1]', null, 'required', null, 'client');
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
        $sql = 'test IN (SELECT id FROM '.$CFG->prefix.'assignment_oj_tests WHERE assignment='.$assignment->id.')';
        if (!delete_records_select('assignment_oj_results', $sql)) {
            return false;
        }
        
        // DELETE submissions
        $sql = 'submission IN (SELECT id FROM '.$CFG->prefix.'assignment_submissions WHERE assignment='.$assignment->id.')';
        if (!delete_records_select('assignment_oj_submissions', $sql)) {
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
        // Count real input/output (not empty tests)
        $assignment->numtests = count($assignment->input);
            
        // Delete actual tests
        delete_records('assignment_oj_tests', 'assignment', $assignment->id);
        
        // Insert new tests
        for ($i = 0; $i < $assignment->numtests; $i++) {
            // Check if tests is not empty
            if(!empty($assignment->input[$i+1]) && !empty($assignment->output[$i+1])) {
                $test = new Object();
                $test->assignment = $assignment->id;
                $test->input = $assignment->input[$i+1];
                $test->output = $assignment->output[$i+1];
                
                if (!insert_record('assignment_oj_tests', $test)) {
                    return get_string('notestinsert', 'assignment_onlinejudge');
                }
                
                unset ($test);
            }
        }
        
        $onlinejudge = new Object();
        $onlinejudge = get_record('assignment_oj', 'assignment', $assignment->id);
        if ($onlinejudge) {
            $onlinejudge->language = $assignment->lang;
            update_record('assignment_oj', $onlinejudge);
        } else {
            $onlinejudge->assignment = $assignment->id;
            $onlinejudge->language = $assignment->lang;
            insert_record('assignment_oj', $onlinejudge);
        }
    }
    
    /**
     * Get tests data for current assignment.
     *
     * @param object $cm Course module
     * @return array tests An array of tests objects
     */
    function get_tests($cm) {
        if (isset($cm->instance))
            return get_records('assignment_oj_tests', 'assignment', $cm->instance, 'id ASC');
        else
            return null;
    }

    function get_test() {
        return get_record('assignment_oj_tests', 'assignment', $this->assignment->id);
    }
    
    /**
     * Display the assignment intro
     *
     */
    function view_intro() {
        parent::view_intro();
        $this->view_summary();
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
        $mark = '<div class="files">';
        $submission = $this->get_submission($userid);
        if ($submission->timemodified) {
            /*
                $output = link_to_popup_window ('/mod/assignment/type/program/history.php?id='.$this->cm->id.'&amp;userid='.$userid,
                                                'history'.$userid, get_string('status'.$submission->status, 'assignment_onlinejudge'), 500, 780, get_string('history', 'assignment_onlinejudge', fullname($userid)), 'none', true);
            $output .= '&nbsp;';
             */
            $output = '<strong>'.get_string('status'.$submission->status, 'assignment_onlinejudge') . ': </strong>';
        }

        $output = $mark . $output . str_replace($mark, '', parent::print_student_answer($userid, true));
        /*
                    
                    $output = link_to_popup_window('/mod/assignment/type/program/source.php?id='
                                .$this->cm->id.'&amp;userid='.$userid.'&amp;file='.$file,
                                $file.'source code', $file, 710, 780, $file, 'none', true, 'button'.$userid);
         */
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
                                $file . 'sourcecode', get_string('preview'), 710, 780, $file, 'none', true, 'button'.$userid);

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
        global $USER;
        
        $table = new Object();
        //$table->class = 'box generalbox boxaligncenter';
        $table->id = 'summary';

        $item_name = get_string('assignmentlangs','assignment_onlinejudge').':';
        $lang = get_string('lang' . $this->onlinejudge->language, 'assignment_onlinejudge');
        $table->data[] = array($item_name, $lang);

        if (is_null($submission))
            $submission = $this->get_submission();
        if ($submission) {
            $item_name = get_string('status').':';
            $item = get_string('status' . $submission->status, 'assignment_onlinejudge');
            $table->data[] = array($item_name, $item);

            if (isset($submission->judgetime)) {
                $item_name = get_string('judgetime','assignment_onlinejudge').':';
                $item = userdate($submission->judgetime);
                $table->data[] = array($item_name, $item);
            }

            if (!empty($submission->error)) {
                $table->data[] = array(get_string('errorinfo', 'assignment_onlinejudge').':', '<pre>'.$submission->error.'</pre>');
            }

            $context = get_context_instance(CONTEXT_MODULE, $this->cm->id);
            if (has_capability('mod/assignment:grade', $context) && isset($submission->output)) {
                $table->data[] = array(get_string('output', 'assignment_onlinejudge').':', '<pre>'.$submission->output.'</pre>');
            }
        }

        $output = print_table($table, true);

        if($return)
            return $output;
            
        echo $output;
    }

    function get_submission($userid=0, $createnew=false, $teachermodified=false) {
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
            }

            $results = get_recordset_select('assignment_oj_results', 'submission = '.$submission->id.' AND judgetime > '.$submission->timemodified, 'judgetime DESC', '*', '', '1');
            $results = recordset_to_array($results);
            if ($results) {
                $result = array_pop($results);
                $submission->error = $result->error;
                $submission->status = $result->status;
                $submission->judgetime = $result->judgetime;
                $submission->output = $result->output;
            } else {
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
     * @param int $sizebytes Moodle site $CGF->assignment_maxmem
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
        if( isset($CFG->assignment_max_mem) && !in_array($CFG->assignment_max_mem, $sizelist) ){
                $sizelist[] = $CFG->assignment_max_mem;
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
     * @param int $time Moodle site $CGF->assignment_maxcpu
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
        if( isset($CFG->assignment_max_cpu) && !in_array($CFG->assignment_max_cpu, $timelist) ){
                $cputime[] = $CFG->assignment_max_cpu;
        }
    
        foreach ($timelist as $timesecs) {
           if ($timesecs < $maxtime) {
               $cputime[$timesecs] = get_string('numseconds', 'moodle', $timesecs);
           }
        }
    
        krsort($cputime, SORT_NUMERIC);
    
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
        
        $dir = $CFG->dirroot . '/mod/assignment/type/onlinejudge/languages/';
        $files = get_directory_list($dir);
        
        $names = preg_replace('/\.(\w+)/', '', $files); // Replace file extension with nothing
        foreach ($names as $name) {
            $lang[$name] = get_string('lang' . $name, 'assignment_onlinejudge');
        }
        
        asort($lang);
        
        return $lang;
    }

    /**
     * Get one unjudged submission and set it as judged
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
                    'AND epsub.judged = 0 '.
               'ORDER BY '.
                    'sub.timemodified ASC';

        $submissions = get_records_sql($sql, '', 1);
        $submission = null;
        if ($submissions) {
            $submission = array_pop($submissions);

            // Set judged mark
            $ojsubmission = new Object;
            $ojsubmission->id = $submission->epsubid;
            $ojsubmission->judged = 1;
            update_record('assignment_oj_submissions', $ojsubmission);
        }

        set_cron_lock('assignment_judging', null);

        return $submission;
    }

    function diff($answer, $output) {
        $answer = rtrim($answer);
        $output = rtrim($output);

        if ($answer === $output)
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

    function run_in_sandbox($exec_file, &$output) {
        global $CFG;

        $result = array('pending', 'ac', 'rf', 'mle', 'ole', 'tle', 're', 'at', 'ie');
        /* Only root can chroot(set jail)
        $jail = $CFG->dataroot.'/temp/sandbox_jail/';
        if (!check_dir_exists($jail, true, true)) {
            mtrace("Can't mkdir ".$jail);
            return 'ie';
        }
         */

        $sand = $CFG->dirroot . '/mod/assignment/type/program/sandbox/sand';
        $sand .= ' -l cpu='.$this->assignment->var2.' -l memory='.$this->assignment->var3.' '.$exec_file; 

        $descriptorspec = array(
            0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
            1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
            2 => array("pipe", "w") // stderr is a file to write to
        );

        $proc = proc_open($sand, $descriptorspec, $pipes);

        if (!is_resource($proc))
            return 'ie';

        $test = $this->get_test();
        fwrite($pipes[0], $test->input);
        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $error = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        if ($error)
            mtrace('('.$exec_file.')'.$error);

        $return_value = proc_close($proc);

        if ($return_value == 255)
            return 'ie';
        else if ($return_value >= 2)
            return $result[$return_value];
        else if ($return_value == 0) {
            mtrace('Pending? Why?');
            exit();
        }

        return $this->diff($test->output, $output);
    }

    function grade_marker($result) {
        $grades = array('pending' => -1,
                        'ac'      => $this->assignment->grade,
                        'wa'      => 0,
                        'pe'      => 0,
                        're'      => 0,
                        'tle'     => 0,
                        'mle'     => 0,
                        'ole'     => 0,
                        'ce'      => 0,
                        'badfile' => 0,
                        'ie'      => -1,
                        'rf'      => 0,
                        'at'      => 0);

        return $grades[$result];
    }

    // Judge in local
    function judge($sub) {
        
        global $CFG;

        $newsub = null;
        $result = null;

        $result->submission = $newsub->id = $sub->id;
        $newsub->teacher = 0;
        $newsub->mailed = 0;
        $ret = true;

        // Make temp dir
        $temp_dir = $CFG->dataroot.'/temp/assignment_onlinejudge/'.$sub->id;
        if (!check_dir_exists($temp_dir, true, true)) {
            mtrace("Can't mkdir ".$temp_dir);
            return false;
        }

        if ($basedir = $this->file_area($sub->userid)) {
            if ($files = get_directory_list($basedir)) {
                foreach ($files as $key => $file) {
                    if (!substr_compare($file, '.c', -2, 2, true)) { //It is a c file
                        $output = null;
                        $return = null;

                        copy($basedir.'/'.$file, $temp_dir.'/'.$file);
                        $shell_script = $CFG->dirroot.'/mod/assignment/type/onlinejudge/languages/'.$this->onlinejudge->language.'.sh';
                        $command = "$shell_script $temp_dir/$file $temp_dir/a.out 2>&1";
                        exec($command, $output, $return);

                        if ($return) { //Compile error
                            $output = str_replace($temp_dir.'/', '', $output);
                            $error = implode("\n", $output);

                            $result->error = addslashes($error);
                            $result->status = 'ce';

                        } else {  //Run it!
                            $result->status = $this->run_in_sandbox($temp_dir.'/a.out', $output);
                            $result->error = '';
                            $result->output = $output;
                        }

                        break;
                    }
                }

                if (!isset($result->status)) {
                    $result->error = '';
                    $result->status = 'badfile';
                }
            }
        }

        $result->judgetime = time();
        if ($ret = insert_record('assignment_oj_results', $result, false)) {
            $newsub->timemarked = time();
            $newsub->grade = $this->grade_marker($result->status);
            $ret = update_record('assignment_submissions', $newsub);
            $this->update_grade($sub);
        }

        // Clean temp dir
        exec('rm -Rf '.$temp_dir);

        return $ret;
    }

    /**
     * Evaluate student submissions
     */
    function cron() {
        global $CFG;

        while ($submission = $this->get_unjudged_submission()) {
            // Construct
            $cm = get_coursemodule_from_instance('assignment', $submission->assignment);
            $this->assignment_onlinejudge($cm->id);

            $this->judge($submission);
        }
/*
        // If defined cron for assignment...
        if($cronfreq = get_field('modules','cron','name','assignment')) {
            echo "\n\tProcessing program assignment type ...\n";

            $now = time();
            $later = $now + ($cronfreq*60);
            $where = "(var1 >= $now AND var1 <= $later) AND assignmenttype LIKE 'program'";
            $where = "assignmenttype LIKE 'program'";

            // Get program type assignments with cron date in next $cronfreq minutes
            if($assignments = get_records_select('assignment',$where,'var1 ASC')) {
                
                foreach($assignments as $ass) {
                    // Get submissions for this assignments
                    if($submissions = assignment_get_all_submissions($ass)) {
                        
                        // Judge each submission
                        foreach($submissions as $submission) {
                            $cm = get_coursemodule_from_instance('assignment',$ass->id);
                            $basedir = $CFG->dataroot.'/'.$cm->course.'/'.$CFG->moddata.'/assignment/'.$ass->id.'/'.$submission->userid.'/';
                            
                            if($files = get_directory_list($basedir)) {
                                foreach ($files as $key => $file)
                                    $file_path = $basedir.$file;
                                
                                $ass->cm = $cm;
                                echo "\t\tJudging assignment [$ass->id] / submission [$submission->id] / Return:";    
                                $return = $this->judge($ass,$submission,$file_path);
                                echo "$return\n";
                            }
                        }
                    }
                }
            }
         }
        echo "\tDone\n";*/
    }
}
?>
