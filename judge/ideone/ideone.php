<?php
	define('NUMTESTS', 5) //Default number of test cases
	
	//default maximum cpu time (seconds) for all assignments
	if(!isset($CFG->assignment_oj_max_cpu))
		set_config('assignment_oj_max_cpu', 10);
	
f (!isset($CFG->assignment_oj_max_mem)) {
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
        'cobol_ideone'                   => 118,                      
        'cobol85_ideone'                 => 106,                      
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
        'oz_ideone'                      => 119,                      
        'pascal_fpc_ideone'              => 22,             
        'pascal_gpc_ideone'              => 2,            
        'perl_ideone'                    => 3,              
        'php_ideone'                     => 29,            
        'pike_ideone'                    => 19,            
        'prolog_gnu_ideone'              => 108,   
        'prolog_swi_ideone'              => 15,      
        'python_ideone'                  => 4,             
        'python3_ideone'                 => 116,             
        'r_ideone'                       => 117,             
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

		

?>
