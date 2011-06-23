<?php

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
//                      Online Judge for Moodle                          //
//       https://github.com/hit-moodle/moodle-local_onlinejudge2         //
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

/**
 * Strings for local_onlinejudge2
 * 
 * @package   local_onlinejudge2
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['about'] = '<p>Based on Moodle, Onlinejudge2 is a tool for onlinejudging work.</p>
<p>It provides kinds of compiler(sandbox, ideone, etc...)</p>
<p>If you want to join us,see：<a href="https://git.github.com/hit-moodle/onlinejudge">git site</a></p>' ;
$string['badvalue'] = 'Incorrect value';
$string['cannotruncompiler'] = 'Can not execute the script of compiler';
$string['cannotmakedir'] = 'cannot make directory.';
$string['cannotrunsand'] = 'cannot run this sand';
$string['ideonedelay'] = 'Delay between requests to ideone.com (second)';
$string['ideonedelay_help'] = 'After sending a judge request to ideone.com, we can not get the result immediately. How long should we wait before querying the result? 5 seconds or so is reasonable.';
$string['ideonelogo'] = '<a href=\"https://github.com/yuxiaoye1223/onlinejudge2\">Moodle Online Judge2</a> uses <a href=\"http://ideone.com\">Ideone API</a> &copy; by <a href=\"http://sphere-research.com\">Sphere Research Labs</a>';
$string['ideoneexception'] = 'Ideone exception...';
$string['info'] = 'Information';
$string['infoat'] = 'A good program must return 0 if no error occur.';
$string['infocompileok'] = 'It seems that the compiler likes your code.';
$string['infoie'] = 'Sandbox error. Report to admin please.';
$string['infomle'] = 'You ate too much memory.';
$string['infoole'] = 'Your code sent too much to stdout.';
$string['infope'] = 'Almost perfect, except some bad white spaces, tabs, new lines and etc.';
$string['infopending'] = 'About $a minute(s) left.';
$string['infore'] = '[SIGSEGV, Segment fault] Bad array index, bad pointer access or even worse.';
$string['inforf'] = 'Your code calls some functions which are <em>not</em> allowed to run.';
$string['infotle'] = 'The program has been running for a too long time.';
$string['infowa'] = 'Double check your code. Don\'t output any typo or unrequired character.';
$string['invalidlanguage'] = 'Invalid language ID ($a)';
$string['invalidjudgeclass'] = 'Invalid judge class ($a)';
$string['langc_sandbox'] = 'C';
$string['langc_warn2err_sandbox'] = 'C (Warnings as Errors)';
$string['langcpp_sandbox'] = 'C++';
$string['langcpp_warn2err_sandbox'] = 'C++ (Warnings as Errors)';
$string['langada_ideone'] = 'Ada (ideone.com)';                      
$string['langassembler_ideone'] = 'Assembler (ideone.com)';                  
$string['langawk_gawk_ideone'] = 'AWK (gawk, ideone.com)';            
$string['langawk_mawk_ideone'] = 'AWK (mawk, ideone.com)';             
$string['langbash_ideone'] = 'Bash (ideone.com)';             
$string['langbc_ideone'] = 'bc (ideone.com)';                        
$string['langbrainfxxk_ideone'] = 'Brainf**k (ideone.com)';            
$string['langc_ideone'] = 'C (ideone.com)';                     
$string['langcsharp_ideone'] = 'C# (ideone.com)';                        
$string['langcpp_ideone'] = 'C++ (ideone.com)';                  
$string['langc99_strict_ideone'] = 'C99 strict (ideone.com)';             
$string['langclojure_ideone'] = 'Clojure (ideone.com)';                
$string['langcobol_ideone'] = 'COBOL (ideone.com)';                      
$string['langcobol85_ideone'] = 'COBOL 85 (ideone.com)';                      
$string['langcommon_lisp_clisp_ideone'] = 'Common Lisp (clisp, ideone.com)';    
$string['langd_dmd_ideone'] = 'D (dmd, ideone.com)';                 
$string['langerlang_ideone'] = 'Erlang (ideone.com)';                     
$string['langforth_ideone'] = 'Forth (ideone.com)';                     
$string['langfortran_ideone'] = 'Fortran (ideone.com)';                 
$string['langgo_ideone'] = 'Go (ideone.com)';                
$string['langhaskell_ideone'] = 'Haskell (ideone.com)';                   
$string['langicon_ideone'] = 'Icon (ideone.com)';             
$string['langintercal_ideone'] = 'Intercal (ideone.com)';                 
$string['langjava_ideone'] = 'Java (ideone.com)';                    
$string['langjavascript_rhino_ideone'] = 'JavaScript (rhino, ideone.com)';         
$string['langjavascript_spidermonkey_ideone'] = 'JavaScript (spidermonkey, ideone.com)';  
$string['langlua_ideone'] = 'Lua (ideone.com)';                       
$string['langnemerle_ideone'] = 'Nemerle (ideone.com)';                  
$string['langnice_ideone'] = 'Nice (ideone.com)';                     
$string['langocaml_ideone'] = 'Ocaml (ideone.com)';                      
$string['langoz_ideone'] = 'Oz (ideone.com)';                      
$string['langpascal_fpc_ideone'] = 'Pascal (fpc, ideone.com)';             
$string['langpascal_gpc_ideone'] = 'Pascal (gpc, ideone.com)';            
$string['langperl_ideone'] = 'Perl (ideone.com)';              
$string['langphp_ideone'] = 'PHP (ideone.com)';            
$string['langpike_ideone'] = 'Pike (ideone.com)';            
$string['langprolog_gnu_ideone'] = 'Prolog (gnu, ideone.com)';   
$string['langprolog_swi_ideone'] = 'Prolog (swi, ideone.com)';      
$string['langpython_ideone'] = 'Python (ideone.com)';             
$string['langpython3_ideone'] = 'Python3 (ideone.com)';             
$string['langr_ideone'] = 'R (ideone.com)';             
$string['langruby_ideone'] = 'Ruby (ideone.com)';             
$string['langscala_ideone'] = 'Scala (ideone.com)';             
$string['langscheme_guile_ideone'] = 'Scheme (guile, ideone.com)';    
$string['langsmalltalk_ideone'] = 'Smalltalk (ideone.com)';          
$string['langtcl_ideone'] = 'Tcl (ideone.com)';              
$string['langtext_ideone'] = 'Text (ideone.com)';               
$string['langunlambda_ideone'] = 'Unlambda (ideone.com)';         
$string['langvbdotnet_ideone'] = 'Visual Basic .NET (ideone.com)'; 
$string['langwhitespace_ideone'] = 'Whitespace (ideone.com)';         
$string['maxcpulimit'] = 'Maximum CPU usage (second)';
$string['maxcpulimit_help'] = 'How long can a judge task keep running.';
$string['maxmemlimit'] = 'Maximum memory usage (MB)';
$string['maxmemlimit_help'] = 'How many memory can a judge task use.';
$string['pluginname'] = 'Online Judge 2';
$string['settingsform'] = 'Online Judge 2 Settings';
$string['settingsupdated'] = 'Settings updated.';
$string['status0'] = 'Pending...';
$string['status1'] = '<font color=red>Accepted</font>';
$string['status2'] = 'Abnormal Termination';
$string['status3'] = 'Compilation Error';
$string['status4'] = 'Compilation Ok';
$string['status5'] = 'Memory-Limit Exceed';
$string['status6'] = 'Output-Limit Exceed';
$string['status7'] = 'Presentation Error';
$string['status9'] = 'Runtime Error';
$string['status8'] = 'Restricted Functions';
$string['status10'] = 'Time-Limit Exceed';
$string['status11'] = 'Wrong answer';
$string['status21'] = 'Internal Error';
$string['status22'] = 'Judging...';
$string['status23'] = 'Multi-status';
$string['status24'] = 'Judged...';
$string['status255'] = 'Unsubmitted';
