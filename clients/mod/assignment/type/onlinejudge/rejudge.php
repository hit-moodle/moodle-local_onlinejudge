<?php

require_once("../../../../config.php");
require_once("../../lib.php");
require_once("../../../../lib/weblib.php");

$id = optional_param('id', 0, PARAM_INT);  // Course Module ID
$a  = optional_param('a', 0, PARAM_INT);   // Assignment ID
$force  = optional_param('force', 0, PARAM_INT);   // Force to rejudge

if ($id) {
    if (! $cm = get_coursemodule_from_id('assignment', $id)) {
        error("Course Module ID was incorrect");
    }

    if (! $assignment = get_record("assignment", "id", $cm->instance)) {
        error("assignment ID was incorrect");
    }

    if (! $course = get_record("course", "id", $assignment->course)) {
        error("Course is misconfigured");
    }
} else {
    if (!$assignment = get_record("assignment", "id", $a)) {
        error("Course module is incorrect");
    }
    if (! $course = get_record("course", "id", $assignment->course)) {
        error("Course is misconfigured");
    }
    if (! $cm = get_coursemodule_from_instance("assignment", $assignment->id, $course->id)) {
        error("Course Module ID was incorrect");
    }
}

require_login($course->id, false, $cm);

require_capability('mod/assignment:grade', get_context_instance(CONTEXT_MODULE, $cm->id));

require ("$CFG->dirroot/mod/assignment/type/onlinejudge/assignment.class.php");
$assignmentinstance = new assignment_onlinejudge($cm->id, $assignment, $cm, $course);

if($force == 1 && confirm_sesskey()){
    rejudge_showresult($assignmentinstance->rejudge_all());
} else {
    rejudge_notice();
} 

function rejudge_notice() {
    global $assignment, $id;

    print_header(get_string('notice'));

    $message = get_string('rejudgeallnotice', 'assignment_onlinejudge', $assignment->name);
    $link = 'rejudge.php?id='.$id.'&force=1';

    notice_okcancel($message, $link, array('sesskey' => sesskey()));

    print_footer('none');
}

function rejudge_showresult($success=true) {
    
    print_header(get_string('notice'));

    if ($success){
        $message = get_string('rejudgesuccess', 'assignment_onlinejudge');
    } else {
        $message = get_string('rejudgefailed', 'assignment_onlinejudge');
    }
    print_box($message, 'generalbox', 'notice');

    close_window_button();
    print_footer('none');
}

/**
 * Print a message along with "Ok" link for the user to continue and "Cancel" link to close window.
 *
 * @param string $message The text to display
 * @param string $linkok The link to take the user to if they choose "Ok"
 * TODO Document remaining arguments
 */
function notice_okcancel ($message, $linkok, $optionsok=NULL, $methodok='post') {

    global $CFG;

    $message = clean_text($message);
    $linkok = clean_text($linkok);

    print_box_start('generalbox', 'notice');
    echo '<p>'. $message .'</p>';
    echo '<div class="buttons">';
    print_single_button($linkok, $optionsok, get_string('ok'), $methodok, $CFG->framename);
    close_window_button('cancel');
    echo '</div>';
    print_box_end();
}
?>


