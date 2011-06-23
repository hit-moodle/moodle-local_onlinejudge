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
 
// access to use global variables.
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

// Make sure the code being tested is accessible.
require_once($CFG->dirroot . '/local/onlinejudge/judgelib.php'); // Include the code to test

/** This class contains the test cases for the functions in judegelib.php. */
class local_onlinejudge_test extends UnitTestCase {
	function setUp() {
        global $DB, $CFG;

        $this->realDB = $DB;
        $dbclass = get_class($this->realDB);
        $DB = new $dbclass();
        $DB->connect($CFG->dbhost, $CFG->dbuser, $CFG->dbpass, $CFG->dbname, $CFG->unittestprefix);

        if ($DB->get_manager()->table_exists('onlinejudge_tasks')) {
            $DB->get_manager()->delete_tables_from_xmldb_file($CFG->dirroot . '/local/onlinejudge/db/install.xml');
        }
        $DB->get_manager()->install_from_xmldb_file($CFG->dirroot . '/local/onlinejudge/db/install.xml');
	}

	function tearDown() {
		global $DB, $CFG;
        $DB = $this->realDB;
	}

	function test_memlimit() {
        $cm = 1;
        $user = 1;
        $language = 'c_sandbox';
        $file = array('test.c' => '
                  #include <stdlib.h>

                 int main(void)
                 {
                     int *p = malloc(1024*1024*1024);
                     free(p);

                     return 0;
                  }
                  ');
        $options->cpulimit = 1;
        $options->memlimit = 1048576;

        $options->input = null;
        $options->output = null;
        $options->compileonly = false;
        $options->answer = null;
        $options->info_teacher = null;
        $options->info_student = null;
        $options->submittime = null;
        $options->judgetime = null;

        $result = onlinejudge_get_task(onlinejudge_submit_task($cm, $user, $language, $file, $options));
	}
	
	function  test_cpulimit() {
		$task = new stdClass();
        $cm = 1;
        $user = 1;
        $language = 'c_sandbox';
        $source = '
                  #include "stdio.h"
                  int main(void)
                  {
                      while(1)
                          ;

                      return 0;
                  }
                  ';
        $task->cpulimit = 1;
        $task->memlimit = 1048576;

        $task->input = null;
        $task->output = null;
        $task->compileonly = false;
        $task->answer = null;
        $task->info_teacher = null;
        $task->info_student = null;
        $task->cpuusage = $task->cpulimit;
        $task->memusage = $task->memusage;
        $task->submittime = null;
        $task->judgetime = null;
        //$task->onlinejudge_ideone_username = 'yuzhanlaile2';
        //$task->onlinejudge_ideone_password = 'yuzhanlaile2';
        //$task->onlinejudge_ideone_delay = 100;

        $result = onlinejudge_get_task(onlinejudge_submit_task($cm, $user, $language, $source, $task, $error));
	}
	
    function test_stdin() {
        $task = new stdClass();
        $cm = 1;
        $user = 1;
        $language = 'c_sandbox';
        $source = '
                  #include <stdio.h>

                  int main(void)
                  {
                      int c;
                      while ( (c = getchar()) != EOF)
                          ;
                      return 0;
                  }
                  ';
        $task->cpulimit = 1;
        $task->memlimit = 1048576;

        $task->input = null;
        $task->output = null;
        $task->compileonly = false;
        $task->answer = null;
        $task->info_teacher = null;
        $task->info_student = null;
        $task->cpuusage = $task->cpulimit;
        $task->memusage = $task->memusage;
        $task->submittime = null;
        $task->judgetime = null;
        //$task->onlinejudge_ideone_username = 'yuzhanlaile2';
        //$task->onlinejudge_ideone_password = 'yuzhanlaile2';
        //$task->onlinejudge_ideone_delay = 100;

        $result = onlinejudge_get_task(onlinejudge_submit_task($cm, $user, $language, $source, $task, $error));
    }
    
    function test_usefile() {
    	//test usefile include inputfile and outputfile.
    }
    
    function test_sandbox() {
    	//test the sandbox compiler
        $task = new stdClass();
        $cm = 1;
        $user = 1;
        $language = 'c_sandbox';
        $source = '
                  #include <stdio.h>
                  int main(void)
                  {
                      int a, b;
                      while (scanf("%d %d", &a, &b)==2)
                      printf("%d\n",a+b);
                   return 0;
                   }
                  ';
        $task->cpulimit = 1;
        $task->memlimit = 1048576;

        $task->input = '2 3';
        $task->output = '5';
        $task->compileonly = false;
        $task->answer = null;
        $task->info_teacher = null;
        $task->info_student = null;
        $task->cpuusage = $task->cpulimit;
        $task->memusage = $task->memusage;
        $task->submittime = null;
        $task->judgetime = null;
        //$task->onlinejudge_ideone_username = 'yuzhanlaile2';
        //$task->onlinejudge_ideone_password = 'yuzhanlaile2';
        //$task->onlinejudge_ideone_delay = 100;

        $result = onlinejudge_get_task(onlinejudge_submit_task($cm, $user, $language, $source, $task, $error));
    }
    
    function test_ideone() {
    	//test the ideone compiler
        $task = new stdClass();
        $cm = 1;
        $user = 1;
        $language = 'c_ideone';
        $source = '
                  #include <stdio.h>
                  int main(void)
                  {
                      int a, b;
                      while (scanf("%d %d", &a, &b)==2)
                      printf("%d\n",a+b);
                   return 0;
                   }
                  ';
        $task->cpulimit = 1;
        $task->memlimit = 1048576;

        $task->input = '2 3';
        $task->output = '5';
        $task->compileonly = false;
        $task->answer = null;
        $task->info_teacher = null;
        $task->info_student = null;
        $task->cpuusage = $task->cpulimit;
        $task->memusage = $task->memusage;
        $task->submittime = null;
        $task->judgetime = null;
        //$task->onlinejudge_ideone_username = 'yuzhanlaile2';
        //$task->onlinejudge_ideone_password = 'yuzhanlaile2';
        //$task->onlinejudge_ideone_delay = 100;

        $result = onlinejudge_get_task(onlinejudge_submit_task($cm, $user, $language, $source, $task, $error));
    }
    
    function test_changeTestcase() {
    	//test the change testcase.
    }
 
    // ... more test methods.
}

