<?php  
global $CFG;
//require($CFG->dirroot.'/mod/assignment/lib.php');
//echo "ok";
require_once(dirname(dirname(__FILE__)) . '/../config.php');
require_once($CFG->dirroot.'/mod/assignment/type/onlinejudge/assignment.class.php');
require_once($CFG->dirroot.'/lib/formslib.php');
//require_once($CFG->dirroot.'/local/onlinejudge2/lang/en_utf8/assingment_onlinejudge.php');

class config extends moodleform 
{
	function definition()
	{
		$mform =& $this->_form;
    	$choices = array('No','Yes');
    	//\
    	$mform->addElement('select', 'lang', get_string('assignmentlangs', 'local_onlinejudge2'));
        $mform->setDefault('lang', 'c');
        
        // Allow resubmit
        $mform->addElement('select', 'resubmit', get_string('allowresubmit', 'local_onlinejudge2'));
       // $mform->setHelpButton('resubmit', array('resubmit',get_string('allowresubmit','assignment'), 'assignment'));
        $mform->setDefault('resubmit', 1);

        // Compile only?
        $mform->addElement('select', 'compileonly', get_string('compileonly', 'local_onlinejudge2'));
      //  $mform->setHelpButton('compileonly', array('compileonly', get_string('compileonly', 'assignment_onlinejudge'), 'assignment_onlinejudge'));
        $mform->setDefault('compileonly', 0);

        // Email teachers
        $mform->addElement('select', 'emailteachers', get_string('emailteachers', 'local_onlinejudge2'));
      //  $mform->setHelpButton('emailteachers', array('emailteachers',get_string('emailteachers','assignment'), 'assignment'));
        $mform->setDefault('emailteachers', 0);
        $this->add_action_buttons(true);
	}
	
}

$mform = new config();
if($mform->is_cancelled())
{
redirect('');
$mform->display();

}

//echo $OUTPUT->header();
//echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');

//echo $OUTPUT->box_end();
//echo $OUTPUT->footer();




/**
$settings->add(new admin_setting_configselect('ASSIGNMENT_ONLINEJUDGE_MAX_CPU', get_string('maxcpuusage', 'assignment_onlinejudge'),
                                             get_string('configmaxcpu', 'assignment_onlinejudge'), ASSIGNMENT_ONLINEJUDGE_MAX_CPU,
                                             assignment_onlinejudge::get_max_cpu_times(ASSIGNMENT_ONLINEJUDGE_MAX_CPU)));

$settings->add(new admin_setting_configselect('ASSIGNMENT_ONLINEJUDGE_MAX_MEM', get_string('maxmemusage', 'assignment_onlinejudge'),
                                             get_string('configmaxmem', 'assignment_onlinejudge'), ASSIGNMENT_ONLINEJUDGE_MAX_MEM,
                                             assignment_onlinejudge::get_max_memory_usages(ASSIGNMENT_ONLINEJUDGE_MAX_MEM)));
*/

?>
