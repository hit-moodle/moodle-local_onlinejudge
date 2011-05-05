<?php
/**
 * Unit tests for (some of) the main features.
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package local
 */
 
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}
 
// Make sure the code being tested is accessible.
require_once($CFG->dirroot . '/local/onlinejudge2/judgelib.php'); // Include the code to test

// access to use global variables.
require_once(dirname(__FILE__) . '/../../config.php');
global $CFG; 
require_once($CFG->libdir . '/moodlelib.php'); 
global $DB;



/** This class contains the test cases for the functions in judegelib.php. */
class local_onlinejudge_test extends UnitTestCase {
	function setUp() {
		//test setup
	}
	
	function tearDown() {
		//test tearDown
	}
	
	function test_memlimit() {
	    // test the mem limit.	
	}
	
	function  test_cpulimit() {
		//test the cpu limit
	}
	
    function test_input() {
        // test the input 
    }
    
    function test_output() {
    	//test the output 
    }
    
    function test_usefile() {
    	//test usefile include inputfile and outputfile.
    }
    
    function test_sandbox() {
    	//test the sandbox compiler
    }
    
    function test_ideone() {
    	//test the ideone compiler
    }
    
    function test_changeTestcase() {
    	//test the change testcase.
    }
 
    // ... more test methods.
}
?>