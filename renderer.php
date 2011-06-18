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
 * onlinejudge2 renderer class is defined here
 *
 * @package   local-onlinejudge2
 * @copyright 2011 Yu Zhan <yuzhanlaile@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
global $CFG;
require_once(dirname(__FILE__).'/locallib.php');
require_once($CFG->dirroot.'/mod/assignment/type/uploadsingle/assignment.class.php');
defined('MOODLE_INTERNAL') || die();

/**
 * onlinejudge2 renderer class
 */
class local_onlinejudge2_renderer extends plugin_renderer_base {
	
	 /**
     * Renders the filter form
     *
     * @todo this code was used as sort of prototype of the HTML produced by the future forms framework, to be replaced by proper forms library
     * @param local_onlinejudge2_filter $filter
     * @return string
     */
    function __construct() {
    	
    	$PAGE->set_pagelayout('standard');
        $PAGE->set_url('/local/onlinejudge2/renderer.php');
        $PAGE->set_title('ONLINEJUDGE2');
        $PAGE->set_heading('ONLINEJUDGE2');

        $output = $PAGE->get_renderer('local_onlinejudge2');
        
        global $CFG, $COURSE;

        $add       = optional_param('add', '', PARAM_ALPHA);
        $update    = optional_param('update', 0, PARAM_INT);

        // Get course module instance
        $cm = null;
        $onlinejudge = null;
        if (!empty($update)) {
            $cm = $DB->get_record('course_modules', array('id'=>$update));
            $onlinejudge = $DB->get_record('onlinejudge2_tasks', array('coursemodule'=>$cm->instance));
        }

        $ynoptions = array(0 => get_string('no'), 1 => get_string('yes'));

        // Programming languages
        $choices = $this->get_languages();
        $mform->addElement('select', 'lang', get_string('assignmentlangs', 'local_onlinejudge2'), $choices);
        $mform->setDefault('lang', 'c');

        // Max. CPU time
        unset($choices);
        $choices = $this->get_max_cpu_times($CFG->assignment_oj_max_cpu);
        $mform->addElement('select', 'cpulimit', get_string('cpulimit', 'local_onlinejudge2'), $choices);
        $mform->setDefault('cpulimit', 1);

        // Max. memory usage
        unset($choices);
        $choices = $this->get_max_memory_usages($CFG->assignment_oj_max_mem);
        $mform->addElement('select', 'memlimit', get_string('memlimit', 'local_onlinejudge2'), $choices);
        $mform->setDefault('memlimit', $CFG->assignment_oj_max_mem);

        // Allow resubmit
        $mform->addElement('select', 'resubmit', get_string('allowresubmit', 'assignment'), $ynoptions);
        $mform->setHelpButton('resubmit', array('resubmit',get_string('allowresubmit','assignment'), 'assignment'));
        $mform->setDefault('resubmit', 1);

        // Compile only?
        $mform->addElement('select', 'compileonly', get_string('compileonly', 'local_onlinejudge2'), $ynoptions);
        $mform->setHelpButton('compileonly', array('compileonly', get_string('compileonly', 'local_onlinejudge2'), 'local_onlinejudge2'));
        $mform->setDefault('compileonly',  0);


        // Submission max bytes
        $choices = get_max_upload_sizes($CFG->maxbytes, $COURSE->maxbytes);
        $choices[1] = get_string('uploadnotallowed');
        $choices[0] = get_string('courseuploadlimit') . ' (' . display_size($COURSE->maxbytes) . ')';
        $mform->addElement('select', 'maxbytes', get_string('maximumfilesize', 'local_onlinejudge2'), $choices);
        $mform->setDefault('maxbytes', $CFG->assignment_maxbytes);
        
    }
	    
    
}
