<?php
// $Id: assignment.class.php,v 1.7 2007/09/04 09:27:20 arkaitz_garro Exp $

//define('ASSIGNMENT_STATUS_SUBMITTED', 'submitted');
define('NUMTESTS', 5); // Default number of test cases
define('ASSIGNMENT_ONLINEJUDGE_MAX_CPU', 10); // Default maximum cpu time (seconds) for all assignments
define('ASSIGNMENT_ONLINEJUDGE_MAX_MEM', 16 * 1024 * 1024); // Default memory usage (bytes) for all assignments

if (!isset($CFG->assignment_oj_max_cpu)) {
    set_config('assignment_oj_max_cpu', ASSIGNMENT_ONLINEJUDGE_MAX_CPU);
}

if (!isset($CFG->assignment_oj_max_mem)) {
    set_config('assignment_oj_max_mem', ASSIGNMENT_ONLINEJUDGE_MAX_MEM);
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
        
        // Cron date
        // Get assignment cron frequency
        if(get_field('modules','cron','name','assignment')) {
            $mform->addElement('select', 'duejudge', get_string('duejudge', 'assignment_onlinejudge'), $ynoptions);
            $mform->setHelpButton('duejudge', array('duejudge', get_string('duejudge', 'assignment_onlinejudge'), 'assignment_onlinejudge'));
            $mform->setDefault('duejudge', $onlinejudge ? $onlinejudge->duejudge : 0);
        }
        
        // Max. CPU time
        unset($choices);
        $choices = $this->get_max_cpu_times($CFG->assignment_oj_max_cpu);
        $mform->addElement('select', 'cpulimit', get_string('cpulimit', 'assignment_onlinejudge'), $choices);
        $mform->setDefault('cpulimit', $onlinejudge ? $onlinejudge->cpulimit : $CFG->assignment_oj_max_cpu);
        
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
        $repeatarray[] = &$mform->createElement('text', 'feedback', get_string('feedback', 'quiz'), array('size' => 50));
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
        $sql = 'submission IN (SELECT id FROM '.$CFG->prefix.'assignment_submissions WHERE assignment='.$assignment->id.')';
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
        // Delete actual tests
        delete_records('assignment_oj_tests', 'assignment', $assignment->id);
        
        // Insert new tests
        for ($i = 0; $i < $assignment->boundary_repeats; $i++) {
            // Check if tests is not empty
            if(!empty($assignment->input[$i]) || !empty($assignment->output[$i])
               || !empty($assignment->feedback[$i]) || !empty($assignment->subgrade[$i])) {
                $test = new Object();
                $test->assignment = $assignment->id;
                $test->input = $assignment->input[$i];
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
            update_record('assignment_oj', $onlinejudge);
        } else {
            $onlinejudge->assignment = $assignment->id;
            $onlinejudge->language = $assignment->lang;
            $onlinejudge->memlimit = $assignment->memlimit;
            $onlinejudge->cpulimit = $assignment->cpulimit;
            $onlinejudge->compileonly = $assignment->compileonly;
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
           global $CFG, $USER;

        $filearea = $this->file_area_name($userid);

        $output = '';

        if ($basedir = $this->file_area($userid)) {
            if ($files = get_directory_list($basedir)) {
                require_once($CFG->libdir.'/filelib.php');
                foreach ($files as $key => $file) {

                    $icon = mimeinfo('icon', $file);
                    // Syntax Highlighert source code
                    $viewlink = link_to_popup_window('/mod/assignment/type/onlinejudge/source.php?id='
                                .$this->cm->id.'&amp;userid='.$userid.'&amp;file='.$file,
                                $file . 'sourcecode', $file, 710, 780, $file, 'none', true, 'button'.$userid);

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
        global $USER, $CFG;
        
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

            if ($submission->status === 'pending') {
                $lastcron = get_field('modules', 'lastcron', 'name', 'assignment');
                $left = ceil(($lastcron + $CFG->assignment_oj_cronfreq - time()) / 60);
                $left = $left > 0 ? $left : 0;
                $submission->info = get_string('infopending', 'assignment_onlinejudge', $left);
            } else if ($submission->status !== 'ac' && $submission->status !== 'ce' && empty($submission->info))
                $submission->info = get_string('info'.$submission->status, 'assignment_onlinejudge');

            if (!empty($submission->info)) {
                $table->data[] = array(get_string('info', 'assignment_onlinejudge').':', $submission->info);
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
                $submission->info = $result->info;
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

    function compile($sub, $temp_dir) {
        global $CFG;
        $result = null;

        if ($basedir = $this->file_area($sub->userid)) {
            if ($files = get_directory_list($basedir)) {
                foreach ($files as $key => $file) {

                    copy($basedir.'/'.$file, $temp_dir.'/'.$file);
                    $compiler = $CFG->dirroot.'/mod/assignment/type/onlinejudge/languages/'.$this->onlinejudge->language.'.sh';
                    if (!is_executable($compiler)) {
                        $result->status = 'ie';
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
                    $error = implode('<br />', $output);
                    $result->info = addslashes($error);

                    return $result;
                }  
            }
        }          

        $result->status = 'badfile';
        return $result;
    }

    function merge_results($results, $testcases) {
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

            if ($one->status === 'ac') {
                $result->grade += $testcase['value']->subgrade;
            }

            if ($i == 0)
                $result->status = $one->status;
            else if ($result->status !== $one->status)
                $result->status = 'multiple';
        }

        //Make sure the grade is not too big
        $result->grade = min($result->grade, $this->assignment->grade);

        return $result;
    }

    // Judge in local
    function judge($sub) {
        
        global $CFG;

        $ret = false;

        // Make temp dir
        $temp_dir = $CFG->dataroot.'/temp/assignment_onlinejudge/'.$sub->id;
        if (!check_dir_exists($temp_dir, true, true)) {
            mtrace("Can't mkdir ".$temp_dir);
            return false;
        }

        $result = $this->compile($sub, $temp_dir);
        $result->grade = -1;
        if ($result->status === 'compileok' && !$this->onlinejudge->compileonly) { //Run and test!
            $results = array();
            $cases = $this->get_tests();
            foreach ($cases as $case) {
                $results[] = $this->run_in_sandbox($temp_dir.'/a.out', $case);
            }

            $result = $this->merge_results($results, $cases);
        }

        $result->submission = $sub->id;
        $result->judgetime = time();
        $result->info = addslashes($result->info);
        $result->output = addslashes($result->output);
        if ($ret = insert_record('assignment_oj_results', $result, false)) {
            $newsub = null;
            $newsub->id = $sub->id;
            $newsub->teacher = 0;
            $newsub->mailed = 0;
            $newsub->timemarked = time();
            $newsub->grade = $result->grade;
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

        // Detect the frequence of cron
        if (!isset($CFG->assignment_oj_cronfreq)) {
            $lastcron = get_field('modules', 'lastcron', 'name', 'assignment');
            if ($lastcron) {
                set_config('assignment_oj_cronfreq', time() - $lastcron);
            }
        }

        // Judge unjudged
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
            $where = "(duejudge >= $now AND duejudge <= $later) AND assignmenttype LIKE 'program'";
            $where = "assignmenttype LIKE 'program'";

            // Get program type assignments with cron date in next $cronfreq minutes
            if($assignments = get_records_select('assignment',$where,'duejudge ASC')) {
                
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
