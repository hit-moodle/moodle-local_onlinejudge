<?php 
require_once("../../config.php");
global $CFG;


//translate status's meaning into human readble.
function translate_status($status) {
    $msg = null;
    //TODO 这里应该使用语言里的翻译输出，但是该函数更应该放入js文件里
    switch($status) {
        case 0:
            $msg = "程序尚未编译运行，正在队列 ...";
            break;
        case 1:
            $msg = "程序运行通过 ...";
            break;
        case 2:
            $msg = "程序运行时中断 ...";
            break;
        case 3:
            $msg = "程序编译错误 ...";
            break;
        case 4:
            $msg = "程序编译成功 ...";
            break;
        case 5:
            $msg = "程序运行时超过最大内存限制 ...";
            break;
        case 6:
            $msg = "程序运行时超过最大CPU限制 ...";
            break;
        case 7:
            $msg = "程序陈述出错...";
            break;
        case 8:
            $msg = "程序使用受限制的函数 ...";
            break;
        case 9:
            $msg = "程序运行错误 ...";
            break;
        case 10:
            $msg = "程序运行时超出最长时间限制 ...";
            break;
        case 11:
            $msg = "程序运行输出结果与用例输出不一致 ...";
            brak;
        case 21:
            $msg = "程序内部出错 ...";
            break;
        case 22:
            $msg = "程序正在运行中 ...";
            break;
        case 23:
            $msg = "程序运行结果有多个状态，即有多个问题 ...";
            break;
        default:
        	$msg = "程序发生未知错误";
    }
    return $msg;
}
?>