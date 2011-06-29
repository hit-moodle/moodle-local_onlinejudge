<?php

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
//                      Online Judge for Moodle                          //
//        https://github.com/hit-moodle/moodle-local_onlinejudge         //
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
 * Strings for local_onlinejudge
 * 
 * @package   local_onlinejudge
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['about'] = '关于';
$string['aboutcontent'] = '<a href="https://github.com/hit-moodle/moodle-local_onlinejudge">在线评测</a>由<a href="http://www.hit.edu.cn">哈尔滨工业大学</a>开发，采用<a href="http://www.gnu.org/copyleft/gpl.html">GPL</a>协议发布。';
$string['badvalue'] = '无效的数值';
$string['cannotcreatetmpdir'] = '无法创建临时目录 {$a}';
$string['cannotrunsand'] = '无法运行sand';
$string['cpuusage'] = 'CPU使用时间';
$string['ideonedelay'] = '发往ideone.com的访问请求之间的时间间隔（秒）';
$string['ideonedelay_help'] = '向ideone.com发送评测请求后，不可能立即得到结果。在查询结果之前，我们应该等待多久？建议设为5秒或稍高。';
$string['ideoneerror'] = 'Ideone返回错误：{$a}';
$string['ideonelogo'] = '<a href="https://github.com/hit-moodle/moodle-local_onlinejudge">Moodle在线评测</a>使用了<a href="http://sphere-research.com">Sphere Research Labs</a>提供的<a href="http://ideone.com">Ideone API</a> &copy;';
$string['ideoneresultlink'] = '在<a href="http://ideone.com/{$a}">http://ideone.com/{$a}</a>查看更多信息。';
$string['ideoneuserrequired'] = '如果选择了用ideone.com评测，就必须输入';
$string['info'] = '信息';
$string['info0'] = '如果已经等了很久，请报告管理员';
$string['info1'] = '祝贺！！！';
$string['info2'] = '一个好程序在没遇到错误的时候，必须“return 0”';
$string['info3'] = '编译器不大喜欢你的程序';
$string['info4'] = '看上去，编译器好像挺喜欢你的程序';
$string['info5'] = '你使用了太多内存';
$string['info6'] = '你的代码向stdout输出太多数据了';
$string['info7'] = '几乎完美，除了几个用错的空格、tab和换行等';
$string['info8'] = '你的程序调用了一些<em>不</em>允许使用的函数';
$string['info9'] = '[SIGSEGV, Segment fault] 下标越界、无效的指针访问或者其它更糟糕的错误';
$string['info10'] = '此程序运行得太久了';
$string['info11'] = '请仔细检查您的代码，不要在输出中有手误或任何未要求的字符';
$string['info21'] = '评测引擎出错，请报告管理员！';
$string['info22'] = '如果已经等了很久，请报告管理员';
$string['infostudent'] = '信息';
$string['infoteacher'] = '信息';
$string['invalidlanguage'] = '无效的语言ID：{$a}';
$string['invalidjudgeclass'] = '无效的judge类：{$a}';
$string['judgestatus'] = '在线评测已经评测了<strong>{$a->judged}</strong>个任务。现在等待队列中还有<strong>{$a->pending}</strong>个任务。';
$string['langc_sandbox'] = 'C (本地运行)';
$string['langc_warn2err_sandbox'] = 'C (本地运行，警告视为错误)';
$string['langcpp_sandbox'] = 'C++ (本地运行)';
$string['langcpp_warn2err_sandbox'] = 'C++ (本地运行，警告视为错误)';
$string['maxcpulimit'] = '最多可用CPU时间（秒）';
$string['maxcpulimit_help'] = '一个评测任务最多可以运行多久。';
$string['maxmemlimit'] = '最多可用内存（MB）';
$string['maxmemlimit_help'] = '一个评测任务最多可以使用多少内存。';
$string['memusage'] = '内存使用量';
$string['mystat'] = '我的统计';
$string['onefileonlyideone'] = 'Ideone.com不支持多文件';
$string['pluginname'] = '在线评测';
$string['sandboxerror'] = '沙箱错误：{$a}';
$string['settingsform'] = '在线评测设置';
$string['settingsupdated'] = '设置已更新。';
$string['status0'] = '等待评测...';
$string['status2'] = '非正常结束';
$string['status3'] = '编译错误';
$string['status4'] = '编译通过';
$string['status5'] = '内存超限';
$string['status6'] = '输出超限';
$string['status7'] = '格式错误';
$string['status9'] = '运行时错误';
$string['status8'] = '受限函数';
$string['status10'] = '运行超时';
$string['status11'] = '错误答案';
$string['status21'] = '内部错误';
$string['status22'] = '评测中...';
$string['status23'] = '多种状态';
$string['status255'] = '未提交评测请求';
$string['stderr'] = '标准输出';
$string['stdout'] = '标准错误输出';

