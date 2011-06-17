<?php
global $DB,$CFG;
require_once($CFG->dirroot."/local/onlinejudge2/judgelib.php");

//TODO: use oj2 manager to update latest language list
global $supported_langs;
$supported_langs = array(
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

class judge_ideone extends judge_base 
{
    /**
     * how to use ideone.com
     * step1: 使用getLanguages方法获取可用的语言
     * step2: 使用createSubmission方法创建一个数据包
     * step3: 使用getSubmissionsStatus方法来检查ideone.com是否成功编译了程序
     *        如果成功了，进入step4，如果失败了，等待3-5秒回到步骤3
     * step4: 使用getSubmissionDetails方法来获取程序编译运行的详细信息
     * step5: 返回step2来编译其他需要编译的程序.
     */
	//var $cases = parent::get_tests;
    
    static function get_languages() {
        global $supported_langs;

    	$langs = array();
        foreach ($supported_langs as $langid => $var) {
            $langs[$langid] = get_string('lang'.$langid, 'local_onlinejudge2');
        }

        return $langs;
    }
    
    /**
     * 
     * translate the language(cpp_ideone) into the interger id, that 
     * can be identified by ideone.com compiler. 
     * @param language id the name of language, such as cpp_ideone
     * @return id of language, such as 1 if $language is cpp_ideone.
     */
    function translator($language)
    {
        global $supported_langs;
        $id = false;
        if(in_array($language, array_flip($supported_langs))) {
            return $supported_langs[$language]; 
        }
        echo get_string('nosuchlanguage', 'local_onlinejudge2');
        return $id;     
    }
    
    /**
     * judge in ideone, and return the result object.
     * @param $task is get from the database or introduced by user.
     * @return result class.
     * @see judge_base::judge()
     */
    
    function judge(& $task)
    {
    	global $CFG, $DB;
    	
    	//get the username and password 
    	if(! set($CFG->onlinejudge2_ideone_username)) {
    	    set_config('onlinejudge2_ideone_username', 'yuzhanlaile2');
    	}
    	if(! set($CFG->onlinejudge2_ideone_password)) {
    	    set_config('onlinejudge2_ideone_password', 'yuzhanlaile2');
    	}
    	// delay between submitting and getting result
    	if(! set($CFG->onlinejudge2_ideone_delay)) {
    	    set_config('onlinejudge2_ideone_delay', 3);
    	}
        
    	// create client.
        $client = new SoapClient("http://ideone.com/api/1/service.wsdl");
        
        // source code of the paste.
        $source = $task->source;
        
        /*
         *  0=>'nr' : not running 
         * 11=>'ce' : compilation error 
         * 12=>'re' : runtime error
         * 13=>‘tle’： time limit exceeded 
         * 15=>'ok' : success
         * 17=>'mle': memory limit exceeded
         * 19=>'rf' : illegal system call
         * 20=>'ie' : internal error 
         */    
        
        $status_ideone = array(
                0   => 'nr',
                11  => 'ce',
                12  => 're',
                13  => 'tle',
                15  => 'ok',
                17  => 'mle',
                19  => 'rf',
                20  => 'ie'
            );
        
        //result class
        $result = new stdClass();
        $result  = $task;
       
        try { 
	        // Begin soap
            // Submit all cases first to save time.
            $link = null;
            
            //get the language id ,cpp_ideone as 21
            $language = $this->translator($task->language);
            $input = $task->input;
            /**
             * function createSubmission create a paste.
             * @param user is the user name.
             * @param pass is the user's password.
             * @param source is the source code of the paste.
             * @param language is language identifier. these identifiers can be 
             *     retrieved by using the getLanguages methods.
             * @param input is the data that will be given to the program on the stdin
             * @param run is the determines whether the source code should be executed.
             * @param private is the determines whether the paste should be private.   
             *     Private pastes do not appear on the recent pastes page on ideone.com. 
             *     Notice: you can only set submission's visibility to public or private through
             *     the API (you cannot set the user's visibility).
             * @return array(
             *         error => string
             *         link  => string
             *     )
             */
            $webid = $client->createSubmission($user,$pass,$source,$language,$input,true,true); 
            
            if ($webid['error'] == 'OK') {
                $link = $webid['link'];
            }
            else {
                mtrace(get_string('createsubmissionerror', 'local_onlinejudge2'));
                $result->status = ONLINEJUDGE2_STATUS_INTERNAL_ERROR;
                $result->info_teacher = $webid['error'];
                $result->info_student = $webid['error'];
                $result->judgetime = time();
                return $result;
            }
            
            // Get ideone results
            $delay = $CFG->assignment_oj_ideone_delay;
            $i = 0;
            while(1){
                if ($delay > 0) {
                    sleep($delay); 
                    $delay = ceil($delay / 2);
                }
                $status = $client->getSubmissionStatus($user, $pass, $link);
               // echo "status:".print_r($status);
                /*status's id
                 *  0 => done
                 * <0 => waiting for compilation
                 *  1 => compilation, being compiled
                 *  3 => running.
                 */
                if($status['status'] == 0) {
                    $delay = 0;
                    break;
                }
            }
            
            $details = $client->getSubmissionDetails($user,$pass,$link,false,true,true,true,true,false); 
            //for test
            echo "details:<br>";
            print_r($details);
            
            $result->status = $status_ideone[$details['result']];
            
            if ($result->status == 'ce' || $task->compileonly) {
                if ($result->status != 'ce' && $result->status != 'ie') {
                    //change status to global status.
                    $result->status = ONLINEJUDGE2_STATUS_COMPILATION_OK;
                }
                else {
                    //change status to global status.
                	$result->status = ONLINEJUDGE2_STATUS_COMPILATION_ERROR;
                }
                //echo $result->status;
                $result->info_teacher = $details['cmpinfo'] . '<br />'.get_string('ideonelogo', 'local_onlinejudge2');
                $result->info_student = $details['cmpinfo'] . '<br />'.get_string('ideonelogo', 'local_onlinejudge2');
                
                
                //packing $record to be update.
                $record = new stdClass();
                $record = null;
                //update database.
                $record->id = $task->id;
                $record->coursemodule = $task->cm;
                $record->userid = $task->user;
                $record->language = $task->language;
                $record->source = $task->source;
                $record->memlimit = $task->memlimit;
                $record->cpulimit = $task->cpulimit;    
                $record->input = $task->input;
                $record->output = $task->output;
                $record->compileonly = $task->compileonly;
                $record->submittime = $task->submittime;
                
                //set record as result 
                $record->status = $result->status;             
                $record->info_teacher = $result->info_teacher;
                $record->info_student = $result->info_student;
                
                if(!$DB->update_record('onlinejudge2', $record)) {
                    mtrace(get_string('cannotupdaterecord', 'local_onlinejudge2'));
                }
                //return $record;             
            }
            
            // Check for wa, pe, tle, mle or accept
            if ($result->status == 'ok') {
                if ($details['time'] > $task->cpulimit) {
                    //change status
                    $result->status = ONLINEJUDGE2_STATUS_TIME_LIMIT_EXCEED;
                }
                else if ($details['memory']*1024 > $task->memlimit) {
                    //change status
                    $result->status = ONLINEJUDGE2_STATUS_MEMORY_LIMIT_EXCEED;
                }
                   
                else {
                    $result->status = ONLINEJUDGE2_STATUS_COMPILATION_OK;
                    $result->output = $details['output'];
                    $result->memusage = $details['memory'];
                    //date format: YYYY-MM-DD HH-MM-SS eg:2011-06-11 14-52-50
                    //$result->judgetime = $details['date'];
                    $result->judgetime = $details['time']+$task->submittime;
                    // for test
                    echo "<br> 即将进行结果和用例输出的diff。。。当前status是$result->status<br>";
                    
                    //TODO use diff function to get the real status.
                    //$result->status = $this->diff($case->output, $result->output);
                }
            }           
        }catch (SoapFault $ex) {
            $result->status = ONLINEJUDGE2_STATUS_INTERNAL_ERROR;
            $result->info_teacher = 'faultcode='.$ex->faultcode.'|faultstring='.$ex->faultstring;
            $result->info_student = 'faultcode='.$ex->faultcode.'|faultstring='.$ex->faultstring;
            
            //packing the record to be updated
            $record =  new stdClass();
            $record = null;
            //update database.
            $record->id = $id;
            $record->coursemodule = $task->cm;
            $record->userid = $task->user;
            $record->language = $task->language;
            $record->source = $task->source;
            $record->memlimit = $task->memlimit;
            $record->cpulimit = $task->cpulimit;    
            $record->input = $task->input;
            $record->output = $task->output;
            $record->compileonly = $task->compileonly;
            $record->submittime = $task->submittime;
            
            //set status to result status.
            $record->status = $result->status;
            $record->judgetime = time();
            $record->info_teacher = $result->info_teacher;
            $record->info_student = $result->info_student;
            //$id = $DB->insert_record('onlinejudge2_tasks', $record, true);
            if(!$DB->update_record('onlinejudge2', $final_record)) {
                mtrace(get_string('cannotupdaterecord', 'local_onlinejudge2'));
            }
            return $record;
        
        }
        
        $result->info_teacher .= '<br />'.get_string('ideonelogo', 'assignment_onlinejudge');
        $result->info_teacher .= '<br />'.get_string('ideonelogo', 'assignment_onlinejudge');
        
        //packing record to be updated
        $record = new stdClass();
        $record = null;
        //update database.
        $record->id = $id;
        $record->coursemodule = $task->cm;
        $record->userid = $task->user;
        $record->language = $task->language;
        $record->source = $task->source;
        $record->memlimit = $task->memlimit;
        $record->cpulimit = $task->cpulimit;    
        $record->input = $task->input;
        $record->output = $task->output;
        $record->compileonly = $task->compileonly;
        $record->submittime = $task->submittime;
        
        //set status to result status.
        $record->status = $result->status;     
        $record->info_teacher = $result->info_teacher;
        $record->info_student = $result->info_student;
        $record->memusate = $result->memusage;
        $record->answer = $result->output;
        $record->judgetime = $result->judgetime;
        
        if(!$DB->update_record('onlinejudge2', $final_record)) {
            mtrace(get_string('cannotupdaterecord', 'local_onlinejudge2'));
        }
        return $record;
    }
    
}
?>
