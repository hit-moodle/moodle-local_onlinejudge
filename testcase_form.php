<?php

require_once('../../../../config.php');
require_once("$CFG->libdir/formslib.php");
require ("$CFG->dirroot/mod/assignment/type/onlinejudge/assignment.class.php");

class testcase_form extends moodleform {
    var $testcasecount;

    function testcase_form($testcasecount) {
        $this->testcasecount = $testcasecount;
        parent::moodleform();
    }

	function definition() {
		global $CFG, $COURSE,$cm,$id;

		$mform =& $this->_form; // Don't forget the underscore! 

		$repeatarray = array();
		$repeatarray[] = &$mform->createElement('header', 'testcases', get_string('testcases', 'assignment_onlinejudge').'{no}');

        require_once($CFG->dirroot.'/lib/questionlib.php'); //for get_grade_options()
        $choices = get_grade_options()->gradeoptions; // Steal from question lib
		$repeatarray[] = &$mform->createElement('select', 'subgrade', get_string('grade'), $choices);

		$repeatarray[] = &$mform->createElement('checkbox', 'usefile', get_string('usefile', 'assignment_onlinejudge'));
		$repeatarray[] = &$mform->createElement('textarea', 'input', get_string('input', 'assignment_onlinejudge'), 'wrap="virtual" rows="5" cols="50"');
		$repeatarray[] = &$mform->createElement('textarea', 'output', get_string('output', 'assignment_onlinejudge'), 'wrap="virtual" rows="5" cols="50"');
		$repeatarray[] = &$mform->createElement('choosecoursefile', 'inputfile', get_string('inputfile', 'assignment_onlinejudge'), array('courseid'=>$COURSE->id));
		$repeatarray[] = &$mform->createElement('choosecoursefile', 'outputfile', get_string('outputfile', 'assignment_onlinejudge'), array('courseid'=>$COURSE->id));
		$repeatarray[] = &$mform->createElement('text', 'feedback', get_string('feedbackforwa', 'assignment_onlinejudge'), array('size' => 50));

		$repeateloptions = array();
		$repeateloptions['input']['type'] = PARAM_RAW;
		$repeateloptions['output']['type'] = PARAM_RAW;
		$repeateloptions['feedbacktext']['type'] = PARAM_RAW;
		$repeateloptions['inputfile']['type'] = PARAM_FILE;
		$repeateloptions['outputfile']['type'] = PARAM_FILE;
		$repeateloptions['testcases']['helpbutton'] =  array('testcases', get_string('testcases', 'assignment_onlinejudge'), 'assignment_onlinejudge');
		$repeateloptions['input']['helpbutton'] =  array('input', get_string('input', 'assignment_onlinejudge'), 'assignment_onlinejudge');
		$repeateloptions['output']['helpbutton'] =  array('output', get_string('output', 'assignment_onlinejudge'), 'assignment_onlinejudge');
		$repeateloptions['inputfile']['helpbutton'] =  array('inputfile', get_string('inputfile', 'assignment_onlinejudge'), 'assignment_onlinejudge');
		$repeateloptions['outputfile']['helpbutton'] =  array('outputfile', get_string('outputfile', 'assignment_onlinejudge'), 'assignment_onlinejudge');
		$repeateloptions['subgrade']['helpbutton'] =  array('grade', get_string('grade'), 'assignment_onlinejudge');
		$repeateloptions['feedback']['helpbutton'] =  array('feedback', get_string('feedbackforwa', 'assignment_onlinejudge'), 'assignment_onlinejudge');
        $repeateloptions['subgrade']['default'] = 0;
		$repeateloptions['inputfile']['disabledif'] = array( 'usefile', 'notchecked');
        $repeateloptions['outputfile']['disabledif'] = array( 'usefile', 'notchecked');
        $repeateloptions['input']['disabledif'] = array( 'usefile', 'checked');
        $repeateloptions['output']['disabledif'] = array( 'usefile', 'checked');

        $repeatnumber = max($this->testcasecount + 1, 5);
		$this->repeat_elements($repeatarray, $repeatnumber, $repeateloptions, 'boundary_repeats', 'add_testcases', 1, get_string('addtestcases', 'assignment_onlinejudge', 1), true);

		$buttonarray=array();
		$buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechangesanddisplay'));
		$buttonarray[] = &$mform->createElement('cancel');
		$mform->addElement('hidden', 'id', $id);
		$mform->setType('id', PARAM_INT);
		$mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
		$mform->closeHeaderBefore('buttonar');
	}

    //Fix MDL-18539
    function get_data($slashed=true) {
        $data = parent::get_data($slashed);
        if ($data) {
            $data->inputfile = array();
            $data->outputfile = array();
            foreach (array_keys($data->feedback) as $key) { 
                $prop = "inputfile[$key]";
                $data->inputfile[$key] = $data->$prop;
                unset($data->$prop);
                $prop = "outputfile[$key]";
                $data->outputfile[$key] = $data->$prop;
                unset($data->$prop);
            }
        }

        return $data;
    }

    function validation($data) {
        global $CFG, $COURSE;

        $errors = array();

        foreach ($data['usefile'] as $key => $usefile) {
            if ($usefile) {
                $file = $data["inputfile[$key]"];
                if (! is_readable("$CFG->dataroot/$COURSE->id/$file")) {
                    $errors["inputfile[$key]"] = get_string('badtestcasefile', 'assignment_onlinejudge');
                }

                $file = $data["outputfile[$key]"];
                if (! is_readable("$CFG->dataroot/$COURSE->id/$file")) {
                    $errors["outputfile[$key]"] = get_string('badtestcasefile', 'assignment_onlinejudge');
                }
            }
        }

        return $errors;
    }
}



?>
