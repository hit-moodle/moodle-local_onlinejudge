<?php

require_once(dirname(__FILE__).'/../../config.php');
global $CFG,$DB;
require_once($CFG->dirroot."/lib/dml/moodle_database.php");


class judge_base{
	var $langs;
	var $onlinejudge;

	/**
     * Returns an array of installed programming languages indexed and sorted by name
     */
	abstract function get_languages();
    
    /**
     * 
     * 将数字id转换为编译器可以执行的语言名字，如301转换为c（不可执行名字为c_sandbox）
     * @param integer $id
     */
    function translator($id){}
    
    /**
     * 将status从英文翻译为id值，便于存储到数据库中
     * @param status表示结果状态的缩写，不同编译器结果不同。
     * @return 返回表示status的整数值。
     */
    function translate_status($status) {
     }
    
	/**
	 * 通过传递任务id值来查看评测的结果
	 * @param id 是数据库表onlinejudge_result中的taskid
	 * @return 返回结果对象
	 */
    function get_result($id){
        $result = stdClass(); //结果对象
        $result = $DB->get_record('onlinejudge_result', array('taskid'=>$id));
        return $result;
    }
    
    //打印结果
    function output_result($result){}
    
    /**
     * TODO: rewrite the comments
     * @param cases is the testcase for input and output.
     * @param extra is the extra limit information, 
     *        eg: runtime limit and cpu limit.
     * @param compiler is the need of certain compiler,
     *        eg: ideone.com need the username and password;
     *            sandbox need the executable file(.o).
     */
    abstract function judge($task);
    
    /**
     * 
     * function diff() compare the output and the answer 
     */  
    function diff($answer, $output) {
        $answer = strtr(trim($answer), array("\r\n" => "\n", "\n\r" => "\n"));
        $output = trim($output);

        if (strcmp($answer, $output) == 0)
            return 'ac';
        else {
            $tokens = array();
            $tok = strtok($answer, " \n\r\t");
            while ($tok) {
                $tokens[] = $tok;
                $tok = strtok(" \n\r\t");
            }

            $tok = strtok($output, " \n\r\t");
            foreach ($tokens as $anstok) {
                if (!$tok || $tok !== $anstok)
                    return 'wa';
                $tok = strtok(" \n\r\t");
            }

            return 'pe';
        }
    }
}

require_once($CFG->dirroot."/local/onlinejudge2/judge/sandbox/sandbox.php");
require_once($CFG->dirroot."/local/onlinejudge2/judge/ideone/ideone.php");
/*利用设计模式中的工厂模式来设计一个类，这个类根据id值的不同来选择创建不同
 *的ideone或sandbox实例
 */
class judgelib {
    var $sandbox_obj;
    var $ideone_obj;
    var $langs;
    
    function __construct(){
        $this->sandbox_obj = new judge_sandbox();
        $this->ideone_obj  = new judge_ideone(); 
        $this->langs = array_merge($this->sandbox_obj->langs, $this->ideone_obj->langs);
    }
    
    /**
     * 函数get_langs列出可以使用的编译器语言的id，
     * 然后用户通过提供id值来进行以后的编译操作。 
     */
    function get_langs_temp() {
    	$this->langs = array_merge($this->sandbox_obj->get_languages(),$this->ideone_obj->get_languages());
    }
    
    /**
     * 将数字id转换为编译器语言名字，如301转换为c_sandbox
     */
    function translate_into_langs($id) {
        $lang_temp = array();
        $lang_temp = array_flip($this->langs);
        return $lang_temp[$id];
    }
    
    
    /**
     * 
     * 将数字id转换为编译器可以执行的语言名字，如301转换为c（不可执行名字为c_sandbox）
     * @param integer $id
     */
    function translator($id) {
        $lang_temp = array();
        //将数组的键值调换，存入temp数组
        $lang_temp = array_flip($this->langs);
        //获取翻译后的编译语言，比如‘c_ideone’变成‘c’
        $selected_lang = substr($lang_temp[$id],0,strrpos($lang_temp[$id],'_'));
        
        
        return $selected_lang;        
    }
	
    /**
     * 函数get_judge根据传入的数据来创建judge_ideone或者judge_sandbox对象
     * $task数据包就是数据库中的一个数据,包括judgeName,memlimit,cpulimit,input,output等数据.
     * 
     */
    function get_judge($judgeName) {
        //检测id值是否在支持的编译器语言里
        if(in_array($judgeName, $this->langs)) {   
            // test result -> ok  
            $judgeName_temp = $judgeName; //保存原先的id值
            //将id翻译为c_sandbox这种形式的语言  	
            $judgeName = $this->translate_into_langs($judgeName);
            //获取编译器类型，结果表示 _ideone或者_sandbox
            $judge_type = substr($judgeName, strrpos($judgeName, '_'));
            //选择的为sandbox的引擎以及语言,
            //还原原先的id值
            $judgeName = $judgeName_temp;
            if($judge_type == "_sandbox" ) {
                //echo "sandbox compiler...<br>";
                $judgeName = $this->translator($judgeName);
                //echo "语言为".$judgeName;
                $judge_obj = new judge_sandbox();
                return $judge_obj;	
            }
            //选择的为ideone的引擎以及语言
            else if($judge_type == "_ideone") {
                $judgeName = $this->translator($judgeName);
                $judge_obj = new judge_ideone(); 
                return $judge_obj;              
            }
            else {
                //其他的编译器引擎
            }
        }
        //提示出错，重新传入id值
        else {	
            echo "所选择的语言不支持，请重新选择.<br>";
        }
    }
    
    /**
     * 查询数据库表onlinejudge_result,获取结果，存入结果对象中
     * @param taskid是onlinejudge_result表中的taskid。
     * @return 返回结果对象
     */
    function get_result($taskid) {
        global $DB;
        $result = new stdClass();
        $result = $DB->get_record('onlinejudge_result', array('taskid' => $taskid));
        
        return $result ;
    }
   
}
?>
