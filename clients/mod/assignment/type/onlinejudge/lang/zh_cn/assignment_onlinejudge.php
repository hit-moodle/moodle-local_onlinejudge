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
 * Strings for Online Judge Assignment Type
 * 
 * @package   local_onlinejudge
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['addtestcases'] = '增加 {$a} 组测试用例';
$string['assignmentlangs'] = '编程语言';
$string['badtestcasefile'] = '此文件不存在，或不可读';
$string['cannotruncompiler'] = '无法执行编译器脚本';
$string['case'] = '用例{$a}:';
$string['compileonly'] = '只编译';
$string['compileonly_help'] = '如选择是，提交的作业将只被编译，不被运行和测试。教师必须手工评分。';
$string['compiler'] = '编译器';
$string['configmaxcpu'] = '缺省的CPU使用时间上限，对全站所有作业有效（每个作业可在此范围内自行设定）';
$string['configmaxmem'] = '缺省的最大内存使用量，对全站所有作业有效（每个作业可在此范围内自行设定）';
$string['cpulimit'] = 'CPU使用时间上限';
$string['denytoreadfile'] = '您没有访问此文件的权限。';
$string['download'] = '下载 ';
$string['duejudge'] = '到截止时间后才评测';
$string['feedback'] = '给错误答案的反馈';
$string['feedback_help'] = '没能通过测试的学生会看到这条信息。可以通过它给学生一些提示或指导，很方便。';
$string['filereaderror'] = '此文件不可读';
$string['forcejudge'] = '强制评测';
$string['ideoneuser'] = 'Ideone用户名';
$string['ideoneuser_help'] = '如果您选择了一个在ideone.com运行的语言，那么您就必须提供一个<a href="http://ideone.com">ideone.com</a>用户名。';
$string['ideonepass'] = 'Ideone API密码';
$string['ideonepass_help'] = '这不是ideone网站的密码，而是ideone <em>API</em>密码。在<a href="https://ideone.com/account/">https://ideone.com/account/</a>修改API密码。';
$string['ideonepass2'] = '再次输入API密码';
$string['ideonepassmismatch'] = '两个密码不匹配';
$string['input'] = '输入';
$string['input_help'] = '输入数据会被送入被测程序的标准输入。

注意：所有Windows风格的换行符（ CR+LF 或 \r\n ）会被转换为Unix风格（LF 或 \n）';
$string['inputfile'] = '输入文件';
$string['inputfile_help'] = '此文件中的数据会被送入被测程序的标准输入。

如果此文件不可读，这条测试用例会被忽略。';
$string['judgetime'] = '评测时间';
$string['langc_warn2err'] = 'C (警告视为错误)';
$string['langcpp_warn2err'] = 'C++ (警告视为错误)';
$string['managetestcases'] = '管理测试用例';
$string['maxcpuusage'] = '最长CPU运行时间';
$string['maximumfilesize'] = '源文件最大长度';
$string['maxmemusage'] = '最大内存用量';
$string['memlimit'] = '内存最多可用';
$string['notestcases'] = '未设置测试用例';
$string['output'] = '输出';
$string['output_help'] = '输出数据会和被测程序向标准输出的输出相比较，以检验程序的正确性。

注意：所有Windows风格的换行符（ CR+LF 或 \r\n ）会被转换为Unix风格（LF 或 \n）';
$string['outputfile'] = '输出文件';
$string['outputfile_help'] = '此文件中的数据会和被测程序向标准输出的输出相比较，以检验程序的正确性。

如果此文件不可读，这条测试用例会被忽略。';
$string['pluginname'] = '在线评测';
$string['ratiope'] = '格式错误得分比例';
$string['ratiope_help'] = '格式错误获得的分数等于用例最高分乘以这个比例

格式错误是指程序输出的数据都是正确的，但数据之间的分隔符存在错误。它通常是由多余的空格或者换行符导致的。如果严格要求，可以把比例设为0%，那么格式错误就得不到任何分数。如果不介意这些琐碎的问题，可以把比例设为100%，那么格式错误就相当于正确。';
$string['readytojudge'] = '已准备好接受评测';
$string['rejudgeall'] = '全部重新评测';
$string['rejudgeallnotice'] = '重新评测所有作业可能会消耗较长时间。您确信要继续吗？';
$string['rejudgeallrequestsent'] = '重新评测全部作业的请求已发送。';
$string['rejudgefailed'] = '无法提交重新评测请求。';
$string['rejudgelater'] = '后台评测进程现在非常繁忙。请稍后再试。';
$string['rejudgesuccess'] = '重新评测请求已经成功提交。';
$string['requestjudge'] = '请求评测';
$string['statistics'] = '统计';
$string['status'] = '状态';
$string['status_help'] = '状态是自动评测的结果。不同状态的含义如下：

* Accepted - 通过评测。获得所有用例所设满分的总和
* 编译错误 - 程序不能通过编译。得0分
* 编译通过 - 只有作业被设置为只编译不评测时，才会出现这种状态。不评分
* 错误答案 - 程序输出与标准答案不匹配。得0分
* 等待评测 - 您的程序正在队列中等待被评测，请稍候。如果这种状态持续的时间很长，则可能是系统内部出现问题。不评分
* 多种状态 - 当有多组测试用例，且各组用例测试的结果不完全相同时，会得到这种状态。“信息”中会给出每组用例单独的评测结果。得分为所有用例得分的和
* 非正常结束 - 程序退出时没有返回0。得0分
* 格式错误 - 输出的关键数据都对，但与标准答案相比，缺少或多余一些分隔符（空格、回车、制表符等）。可能是0到满分之间的任何值，由教师的设置决定。
* 内部错误 - 系统内部配置不当，或评测程序失效导致的错误。只有系统管理员才能解决这个问题。不评分
* 内存超限 - 每次作业都会设置一个允许程序使用内存的最大值。如果实际使用的内存超出这个值，就会得到这个状态。得0分
* 受限函数 - 程序中调用了一些不应该调用的系统功能。得0分
* 数据输出超限 - 程序输出了过多的数据，超出了系统限制。通常是程序发生了死循环，且在循环体内不断输出数据导致的。得0分
* 运行超时 - 每次作业都会设置一个允许程序在CPU中运行的最长时间。如果实际使用的时间超出这个值，就会得到这个状态。得0分
* 运行时错误 - 程序执行了非法操作。一般是试图访问不可访问的内存，或试图执行无权执行的指令。得0分';
$string['statusat'] = '非正常结束';
$string['statusce'] = '编译错误';
$string['statuscompileok'] = '编译通过';
$string['statusie'] = '内部错误';
$string['statusmle'] = '内存超限';
$string['statusmultiple'] = '多个状态';
$string['statusole'] = '数据输出超限';
$string['statuspe'] = '格式错误';
$string['statuspending'] = '等待评测...';
$string['statusre'] = '运行时错误';
$string['statusrf'] = '受限函数';
$string['statustle'] = '运行超时';
$string['statuswa'] = '错误答案';
$string['subgrade'] = '成绩';
$string['subgrade_help'] = '通过此测试用例能获得百分之几的分数。

如果作业最高分设为50，此测试用例的成绩设为20%，那么通过测试的学生可以获得10分，没能通过测试的学生只能获得0分。最终成绩是所有测试用例得分的总和。如果该和超过了作业的最高分，那么将以最高分做为最终成绩。

所有测试用例成绩的总和<em>并非</em>必须为100%。这样，把总和设成低于100%，可以留一些分数给人工评分；把总和设成高于100%，那么没通过所有的测试用例也有可能获得满分。';
$string['successrate'] = '成功率';
$string['testcases'] = '测试用例';
$string['testcases_help'] = '每个测试用例都会被单独评测。例如，有3个测试用例，那么被测程序会被运行三次，每次处理不同的用例。';
$string['typeonlinejudge'] = '在线评测';
$string['usefile'] = '测试用例来自文件';
$string['waitingforjudge'] = '等待评测结果';

