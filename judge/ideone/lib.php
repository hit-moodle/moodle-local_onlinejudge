<?php
global $DB,$CFG;
require_once($CFG->dirroot."/local/onlinejudge2/judgelib.php");

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
	var $langs = array(
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
    
    /**
     * Returns an array of installed programming languages indexed and sorted by name
     */
    static function get_languages()
    {
    	$lang = array();
        // Get ideone.com languages
        foreach ($this->langs as $name => $id) 
        {
            $lang[$name] = get_string('lang'.$name, 'local_onlinejudge2');
        }
        asort($lang);
        return $lang;
    }
    
    /**
     * 
     * 将数字id转换为编译器可以执行的语言名字，如301转换为c（不可执行名字为c_sandbox）
     * @param integer $id
     */
    function translator($id)
    {
        $lang_temp = array();
        //将数组的键值调换，存入temp数组
        $lang_temp = array_flip($this->langs);
        //获取翻译后的编译语言，比如‘c_ideone’变成‘c’
        $selected_lang = substr($lang_temp[$id],0,strrpos($lang_temp[$id],'_'));
        
        
        return $selected_lang;        
    }
    
    //function judge($cases, $extra, $compiler)
    function judge($sub)
    {
    	//get the username and password form param compiler.
    	//onelinejude_ideone_username and onlinejudge_ideone_password
    	//are defined in file config.php in the root. 
    	$user = $CFG->onlinejudge_ideone_username;
    	$pass = $CFG->onlinejudge_ideone_password;
        $client = new SoapClient("http://ideone.com/api/1/service.wsdl");
        /**
         *  0=>'nr' : not running – the paste has been created 
            with run parameter set to false
         * 11=>'ce' : compilation error – the program could not 
            be executed due to compilation errors
         * 12=>'re' : runtime error – the program finished 
            because of  the runtime error, for example: 
            division by zero,  array index out of bounds, uncaught exception
         * 13=>‘tle’： time limit exceeded – the program didn't 
            stop before the time limit
         * 15=>'ok' : success – everything went ok
         * 17=>'mle': memory limit exceeded – the program tried 
            to use more memory than it is allowed
         * 19=>'rf' : illegal system call – the program tried to call 
            illegal system function
         * 20=>'ie' : internal error – some problem occurred on 
            ideone.com; try to submit the paste again and if that fails too, 
            then please contact us at contact@ideone.com
         */

        $source = $cases; // source code of the paste.
        $status = array(
                0   => 'nr',
                11  => 'ce',
                12  => 're',
                13  => 'tle',
                15  => 'ok',
                17  => 'mle',
                19  => 'rf',
                20  => 'ie'
            );
        $result  = false;
        try { 
	        // Begin soap
            // Submit all cases first to save time.
            $links = array();
            // loop: get data from database table onlinejudge_task.
            global $DB;
            $tasks = $DB->get_records($CFG->prefix.'onlinejudge_task');
            foreach ($tasks as $task) {
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
                 */
    	        $webid = $client->createSubmission($user,$pass,$source,translator($task['judgeName']),$task->input,true,true);     
                if ($webid['error'] == 'OK')
                    $links[] = $webid['link'];
                else {
                    $result->status = 'ie';
                    $result->info = $webid['error'];
                    return $result;
                }
                // Get ideone results
                //onlinejudge_ideone_delay在config.php文件中自己定义,他是表示ideone网站检测的延迟。
                $delay = $CFG->onlinejudge_ideone_delay;
                $i = 0;
                $results = array();
                foreach ($cases as $case) 
                {
                    while(1)
                    {
                        if ($delay > 0) 
                        {
                            sleep($delay); 
                            $delay = ceil($delay / 2);
                        }
                        $status = $client->getSubmissionStatus($user, $pass, $links[$i]);
                        if($status['status'] == 0) 
                        {
                            $delay = 0;
                            break;
                        }
                    }
                   /**
                    * 
                    * function getSubmissionDetails retrieve detailed information about 
                    * the execution of the program.
                    */
                    $details = $client->getSubmissionDetails($user,$pass,$links[$i],false,true,true,true,true,false);         

                    $result->status = $status_ideone[$details['result']];
                    // If got ce or compileonly, don't need to test other case
                    if ($result->status == 'ce' || $this->onlinejudge->compileonly) 
                    {
                        if ($result->status != 'ce' && $result->status != 'ie')
                            $result->status = 'compileok';
                        $result->info = $details['cmpinfo'] . '<br />'.get_string('ideonelogo', 'assignment_onlinejudge');
                        $result->grade = $this->grade_marker('ce', $this->assignment->grade);
                        return $result;
                    }

                    // Check for wa, pe, tle, mle or accept
                    if ($result->status == 'ok') 
                    {
                        if ($details['time'] > $this->onlinejudge->cpulimit)
                            $result->status = 'tle';
                        else if ($details['memory']*1024 > $this->onlinejudge->memlimit)
                            $result->status = 'mle';
                        else 
                        {
                            $result->output = $details['output'];
                            $result->status = $this->diff($case->output, $result->output);
                        }
                    }

                    $results[] = $result;
                    unset($result);
                    $i++;
                }
            } 
        }catch(SoapFault $sf) {
            $result->status = 'ie';
            $result->info = 'faultcode='.$sf->faultcode.'|faultstring='.$sf->faultstring;
            return $result;
        } 
        //这里代码原先是用了作业模块，也需要修改
        $result = $this->merge_results($results, $cases);
        $result->info .= '<br />'.get_string('ideonelogo', 'assignment_onlinejudge');
        return $result;    
    echo "onlinejudge2 uses <a href='http://ideone.com'>ideone.com</a> &copy;
by <a href='http://sphere-research.com'>Sphere Research Labs</a>";
    }
    
}
?>
