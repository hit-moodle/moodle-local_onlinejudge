<?php
global $CFG,$DB;
require_once($CFG->dirroot."/lib/dml/moodle_database.php");
require_once("./judge/ideone/ideone.php");
require_once($CFG->dirroot."/mod/assignment/type/onlinejudge/assignment.class.php");

class judge_base
{
    function judge($sub){}
}
    
class judge_sandbox extends judge_base
{
    /*
     * the main function of judge_local.
     * use the sandbox compiler to compile and run 
     * the codes submitted or file-uploaded by user.
     * 
     */
    function judge($sub)
    {
        // Make temp dir
        $temp_dir = $CFG->dataroot.'/temp/assignment_onlinejudge/'.$sub->id;
        if (!check_dir_exists($temp_dir, true, true)) {
            mtrace("Can't mkdir ".$temp_dir);
            return false;
        }
        
        if ($result = $this->compile($sub, $temp_dir)) {
            $result->grade = -1;
            if ($result->status === 'compileok' && !$this->onlinejudge->compileonly) { //Run and test!
                $results = array();
                $cases = $this->get_tests();
                foreach ($cases as $case) 
                {
                    $results[] = $this->run_in_sandbox($temp_dir.'/a.out', $case);
                }
                $result = $this->merge_results($results, $cases);
            }
            else if ($result->status === 'ce') 
            {
                $result->grade = $this->grade_marker('ce', $this->assignment->grade);
                $result->output = '';
            }
        }
        // Clean temp dir
        fulldelete($temp_dir);
        return $result;
    }
		
}
	
class judge_ideone extends judge_base
{
    //将全局函数get_judge_methods中数组judge_methods中的ideone_xxx翻译为可被ideone引擎支持的id
    function translate($judge_methods)
    {
        //将judge_methods数组中的键值颠倒
        $judge_methods_temp = array_flip($judge_methods);
        //通过遍历数组，将数组$judge_methods中的数据变成符合$ideone_langs的数据
        foreach($judge_methods_temp as $key=>$value)
        {
            //表示是ideone的编译器语言
            if($key >= 3)
            {
                //将类似'ideone_XXX'变成'XXX_ideone'
                $value = str_replace("ideone_", "", $value)."_ideone";
            }
        }
        //保存翻译完后的数组
        $judge_methods_translated = array_flip($judge_methods_temp);	
    }
    /*
     * 函数judge是主要的编译运行函数,
     * 这里参考了老师原先的代码，有的地方还不是很了解，需要自己的修改。
     */
    function judge($sub)
    {
        $ass_oj = new assignment_onlinejudge();
        // creating soap client
        $client = new SoapClient("http://ideone.com/api/1/service.wsdl");

        //user和pass是ideone网站的用户名和密码
        //assignment_oj_ideone_(username/password)
        $user = $CFG->assignment_oj_ideone_username;                                               
        $pass = $CFG->assignment_oj_ideone_password;

        if ($source = $ass_oj->get_submission_file_content($sub->userid)) 
        {
            $cases = $ass_oj->get_tests();

            $status_ideone = array
            (
                11  => 'ce',
                12  => 're',
                13  => 'tle',
                15  => 'ok',
                17  => 'mle',
                19  => 'rf',
                20  => 'ie'
            );

           $result->grade = -1;

            try { 
                // Begin soap
                // Submit all cases first to save time.
                $links = array();
                foreach ($cases as $case) 
                {
                    $webid = $client->createSubmission($user,$pass,$source,$this->ideone_langs[$this->onlinejudge->language],$case->input,true,true);     
                    if ($webid['error'] == 'OK')
                        $links[] = $webid['link'];
                    else 
                    {
                        $result->status = 'ie';
                        $result->info = $webid['error'];
                        return $result;
                    }
                }

                // Get ideone results
                $delay = $CFG->assignment_oj_ideone_delay;
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
              } catch (SoapFault $ex) 
              {
                $result->status = 'ie';
                $result->info = 'faultcode='.$ex->faultcode.'|faultstring='.$ex->faultstring;
                return $result;
              }

            $result = $this->merge_results($results, $cases);
            $result->info .= '<br />'.get_string('ideonelogo', 'assignment_onlinejudge');
            return $result;
        } 
        else 
        {
            return false;
        }	
    }
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
    var $judge_methods_translated = array();
}

/*利用设计模式中的工厂模式来设计一个类，这个类根据id值的不同来选择创建不同
 *的ideone或sandbox实例
 */
class judge_factory
{
    //自定义的编译器以及语言，可以根据需要添加，但是id值也要跟着变，这是雏形，以后还会完善。
    var $judge_methods = array(
            'sandbox_c'                      => 1,
            'sandbox_cpp'                    => 2,
            'ideone_c'                       => 3,
            'ideone_cpp'                     => 4,
            'ideone_csharp'                  => 5,
            'ideone_c99_strict'              => 6,
            'ideone_java'                    => 7,
            'ideone_javascript_rhino'        => 8,                    
            'ideone_javascript_spidermonkey' => 9, 
            'ideone_perl'                    => 10,              
            'ideone_php'                     => 11,  
            'ideone_pascal_fpc'              => 12,             
            'ideone_pascal_gpc'              => 13,                       
            'ideone_pike'                    => 14,            
            'ideone_prolog_gnu'              => 15,   
            'ideone_prolog_swi'              => 16,      
            'ideone_python'                  => 17,             
            'ideone_python3'                 => 18,         
            'ideone_ada'                     => 19,                      
            'ideone_assembler'               => 20,                  
            'ideone_awk_gawk'                => 21,            
            'ideone_awk_mawk'                => 22,             
            'ideone_bash'                    => 23,             
            'ideone_bc'                      => 24,                        
            'ideone_brainfxxk'               => 25,                                                                                    
            'ideone_clojure'                 => 26,                
            'ideone_cobol'                   => 27,                      
            'ideone_cobol85'                 => 28,                      
            'ideone_common_lisp_clisp'       => 29,    
            'ideone_d_dmd'                   => 30,                 
            'ideone_erlang'                  => 31,                     
            'ideone_forth'                   => 32,                     
            'ideone_fortran'                 => 33,                 
            'ideone_go'                      => 34,                
            'ideone_haskell'                 => 35,                   
            'ideone_icon'                    => 36,             
            'ideone_intercal'                => 37,                   
            'ideone_lua'                     => 38,                       
            'ideone_nemerle'                 => 39,                  
            'ideone_nice'                    => 40,                     
            'ideone_ocaml'                   => 41,                      
            'ideone_oz'                      => 42,                                     
            'ideone_r'                       => 43,             
            'ideone_ruby'                    => 44,             
            'ideone_scala'                   => 45,             
            'ideone_scheme_guile'            => 46,    
            'ideone_smalltalk'               => 47,          
            'ideone_tcl'                     => 48,              
            'ideone_text'                    => 49,               
            'ideone_unlambda'                => 50,         
            'ideone_vbdotnet'                => 51, 
            'ideone_whitespace'              => 52
        );	
    /*
     * 函数get_judge_methods列出可以使用的编译器语言的id，
     * 然后用户通过提供id值来进行以后的编译操作。 
     */
    function get_judge_methods()
    {
        echo "本系统支持的编译语言以及id值如下：<br>";
        $judge_methods_temp = array_flip($this->judge_methods);
        foreach($judge_methods_temp as $key=>$value)
        {
            //打印键值对，这里后期会利用语言来给每一个编译器提供注释，待完善。
            echo "$key----------$value<br>";
        }
        echo "<br><br><br>";
    }
	
    /*
     * 函数get_judge根据传入的id值来创建judge_ideone或者judge_sandbox对象
     */
    function get_judge($id)
    {	
        //检测id值是否在支持的编译器以及语言里
        if(in_array("$id", $judge_methods))
        {
            //选择的为sandbox的引擎以及语言
            if(id<=2)
            {
                $judge_obj = new judge_sandbox();
                $judge_obj->judge($sub);
            }
            //选择的为ideone的引擎以及语言
            else if(id>2 && id<53)
            {
                $judge_obj = new judge_ideone();
                //先对judge_methods进行翻译
                $judge_obj->translate($judge_methods);
                $judge_obj->judge($sub);
            }
        }
        //提示出错，重新传入id值
        else
        {	
            echo "所选择的语言不支持，请重新选择.<br>";
        }
    }	
}
?>