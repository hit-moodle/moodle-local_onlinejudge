<?php
//本文件主要用于模拟运行


require_once("../../config.php");
require_once("judgelib.php");
global $CFG, $DB;

$jf = new judge_factory();
$jf->get_judge_methods();
/**
 * test translator,result -> ok
echo $jf->translator(301);
*/
//数据包
$sub = array();
$sub['id'] = 1;
$sub['cpulimit'] = 1;
$sub['memlimit'] = 1048576;
$sub['judgeName'] = 301;
$sub['source'] = '#include "stdio.h" 
                   int main()
                   {
                       int a, b;
                       scanf("%d,%d",&a,&b);
                       printf("%d",a+b);
                       return 0;
                   }
';
$sub['input'] = '2,3';
$sub['output'] = '5';
$sub['usefile'] = 0;
$sub['inputfile'] = 0;
$sub['outputfile'] = 0; 


$jf->get_judge($sub);



/*利用设计模式中的工厂模式来设计一个类，这个类根据id值的不同来选择创建不同
 *的ideone或sandbox实例
 */
class judge_factory
{
	
	var $judge_methods = array(
	    //ideone languages
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
        'whitespace_ideone'              => 6,
        
        //sandbox languages
        'c_warn2err_sandbox'                     =>300,
        'c_sandbox'                              =>301,
        'cpp_warn2err_sandbox'                   =>302,
        'cpp_sandbox'                            =>303,    
    );
    	
    /*
     * 函数get_judge_methods列出可以使用的编译器语言的id，
     * 然后用户通过提供id值来进行以后的编译操作。 
     */
    function get_judge_methods()
    {
        $lang = array();
        //echo "本系统支持的编译语言以及id值如下：<br>";
        foreach ($this->judge_methods as $name => $id) 
        {
            $lang[$name] = get_string('lang'.$name, 'local_onlinejudge2');
            //这里需要使用表格来显示.
            //echo get_string('lang'.$name, 'local_onlinejudge2') ;
            //echo "====>  $id<br>";
        }
        //echo "<br><br><br>";
    }
    
    /**
     * 
     */
    function translate_into_judge_methods($id)
    {
        $lang_temp = array();
        $lang_temp = array_flip($this->judge_methods);
        return $lang_temp[$id];
    }
    
    
    /**
     * 
     * translator the param id into the language that  be available for compiler 
     * @param integer $id
     */
    function translator($id)
    {
        $lang_temp = array();
        //将数组的键值调换，存入temp数组
        $lang_temp = array_flip($this->judge_methods);
        //获取翻译后的编译语言，比如‘c_ideone’变成‘c’
        $selected_lang = substr($lang_temp[$id],0,strrpos($lang_temp[$id],'_'));
        
        
        return $selected_lang;        
    }
	
    /*
     * 函数get_judge根据传入的数据来创建judge_ideone或者judge_sandbox对象
     * $sub数据包就是数据库中的一个数据,包括judgeName,memlimit,cpulimit,input,output等数据.
     * 
     */
    function get_judge(& $sub)
    {
    //echo "现在开始执行get_judge方法<br>";
        //检测id值是否在支持的编译器语言里
        if(in_array($sub['judgeName'], $this->judge_methods))
        {   
            // test result -> ok  
            $judgeName = $sub['judgeName']; //保存原先的id值  	
            $sub['judgeName'] = $this->translate_into_judge_methods($sub['judgeName']);
            //echo "语言类型为:".$judgeName;
            
            //获取编译器类型，结果表示 _ideone或者_sandbox
            $judge_type = substr($sub['judgeName'], strrpos($sub['judgeName'], '_'));
            //输出类型，test
            //echo $judge_type.'<br>';
            //选择的为sandbox的引擎以及语言,
            //还原原先的id值
            $sub['judgeName'] = $judgeName;
            //echo $sub['judgeName'].'<br>'; //test
            if($judge_type == "_sandbox" )
            {
                require_once("./judge/sandbox/sandbox.php");
                $judge_obj = new judge_sandbox();
                $sub['judgeName'] = $this->translator($sub['judgeName']);	
                $judge_obj->judge($sub);
            }
            //选择的为ideone的引擎以及语言
            else if($judge_type == "_ideone")
            {
                require_once("./judge/ideone/ideone.php");
                $judge_obj = new judge_ideone();
                $sub['judgeName'] = $this->translator($sub['judgeName']);	               
                $judge_obj->judge($sub);
            }
            else 
            {
                //其他的编译器引擎
            }
        }
        //提示出错，重新传入id值
        else
        {	
            echo "所选择的语言不支持，请重新选择.<br>";
        }
       //  echo "get_judge方法执行完毕<br>";	
    }
   
}







?>