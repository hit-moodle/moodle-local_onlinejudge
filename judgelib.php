<?php

require_once(dirname(__FILE__).'/../../config.php');
global $CFG,$DB;
require_once($CFG->dirroot."/lib/dml/moodle_database.php");

global $judgeclasses;
$judgeclasses = array();
if ($plugins = get_list_of_plugins('local/onlinejudge2/judge')) {
    foreach ($plugins as $plugin=>$dir) {
        require_once("$CFG->dirroot/local/onlinejudge2/judge/$dir/lib.php");
        $judgeclasses[] = "judge_$dir";
    }
}

class judge_base{
	var $langs;
	var $onlinejudge;

	/**
     * Return an array of programming languages supported by this judge
     *
     * The array key must be the language's ID, such as c_sandbox, python_ideone.
     * The array value must be a human-readable name of the language, such as 'C (local)', 'Python (ideone.com)'
     */
    static function get_languages() {
        return array();
    }

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
     * 将status从整数id值译为英文，便于显示给用户看
     * @param statusid表示结果状态的id值，不同编译器结果不同。
     * @return 返回表示statusid的英文描述。
     */
    function flip_status($statusid) {
    
    }
    
	/**
	 * 通过传递任务id值来查看评测的结果
	 * @param id 是数据库表onlinejudge_result中的taskid
	 * @return 返回结果对象
	 */
    function get_result($taskid){
        global $DB;
        if(! $DB->record_exists('onlinejudge_result', array('taskid' => $taskid))) {
            echo get_string('nosuchrecord', 'local_onlinejudge2');
        } 
        $result = null; //结果对象
        $result = $DB->get_record('onlinejudge_result', array('taskid' => $id));
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
    function judge($task) {
        return false;
    }

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

const ONLINEJUDGE2_STATUS_ACCEPTED               = 1;
const ONLINEJUDGE2_STATUS_ABNORMAL_TERMINATION   = 2;
const ONLINEJUDGE2_STATUS_COMPILATION_ERROR      = 3;
const ONLINEJUDGE2_STATUS_COMPILATION_OK         = 4;
const ONLINEJUDGE2_STATUS_MEMORY_LIMIT_EXCEED    = 5;
const ONLINEJUDGE2_STATUS_OUTPUT_LIMIT_EXCEED    = 6;
const ONLINEJUDGE2_STATUS_PRESENTATION_ERROR     = 7;
const ONLINEJUDGE2_STATUS_RESTRICTED_FUNCTIONS   = 8;
const ONLINEJUDGE2_STATUS_RUNTIME_ERROR          = 9;
const ONLINEJUDGE2_STATUS_TIME_LIMIT_EXCEED      = 10;
const ONLINEJUDGE2_STATUS_WRONG_ANSWER           = 11;

const ONLINEJUDGE2_STATUS_INTERNAL_ERROR         = 21;
const ONLINEJUDGE2_STATUS_PENDING                = 22;
const ONLINEJUDGE2_STATUS_JUDGING                = 23;
const ONLINEJUDGE2_STATUS_MULTI_STATUS           = 24;

/**
 * Returns an sorted array of all programming languages supported
 *
 * The array key must be the language's ID, such as c_sandbox, python_ideone.
 * The array value must be a human-readable name of the language, such as 'C (local)', 'Python (ideone.com)'
 */
function onlinejudge2_get_languages() {
    global $judgeclasses;

    $langs = array();
    foreach ($judgeclasses as $judgeclass) {
        $langs = array_merge($langs, $judgeclass::get_languages());
    }

    asort($langs);
    return $langs;
}

/**
 * Return the human-readable name of specified language 
 *
 * @param string $language ID of the language
 * @return name 
 */
function onlinejudge2_get_language_name($language) {
    $langs = onlinejudge2_get_languages();
    return $langs[$language];
}

/**
 * Submit task to judge of specified language
 *
 * @param string $language ID of the language
 * @param string $source Source code
 * @param object $options include input, output and etc. TODO: enrich details
 * @param string $error error message if error occurs
 * @return id of the task or false
 */
function onlinejudge2_submit_task($language, $source, $options, &$error) {
    //TODO: recode this function

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
        //TODO: 这里要面向未来编程，不能写死sandbox、ideone这样的字眼
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
 * Return detail of the task
 *
 * @param int $taskid
 * @return object of task or null if unavailable
 */
function onlinejudge2_get_task_status($taskid) {
    global $DB;
    $result = new stdClass();
    $result = $DB->get_record('onlinejudge2_result', array('taskid' => $taskid));

    return $result ;
}

/**
 * Return the name of specified status
 *
 * @param int $status
 * @return name
 */
function onlinejudge2_get_status_name($status) {
    return get_string('status'.$status, 'local_onlinejudge2');
}
