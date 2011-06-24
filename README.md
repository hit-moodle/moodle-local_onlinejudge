**THIS PLUGIN IS STILL UNDER DEVELOPMENT AND NOT SUTIABLE FOR PRODUCTION SITES**

Introduction
============

The Online Judge 2 plugin for Moodle 2 is designed for courses involving programming.
It can automatically grade submitted source code by testing them against customizable
test cases (ACM-ICPC/Online Judge style).

It contains:

1. A local plugin call judgelib which provides the essential abilities of online judge.
   It works like a library.
2. A assignment type plugin call online judge which provides a UI to teachers and students
   and calls the judgelib to judge submissions.

There are two judge engines:

1. Sandbox compiles and executes programs on the server in a protected environment.
   Supports C and C++ only.
2. Ideone posts all data to ideone.com which compiles and executes programs remotely.
   Supports 40+ languaes, such as C/C++, Java, Python, C#, JavaScript, Perl, PHP.

The workflow is:

1. Administrators set global settings in judgelib.
2. Teachers create Online Judge Assignment Activities and setup testcases etc.
3. Students submit code in Online Judge Assignment Activities.
4. A judge daemon judges the submissions in backgroud.
5. Teachers and students get judge results in Online Judge Assignment Activities.


Prerequisite
============

For Linux Users
---------------

* php-cli
* make, gcc and g++ (optional but recommended)
* pcntl and posix extension in php-cli (optional but recommended)

For Windows Users
-----------------

* php-cli


Download
========

Download it from https://github.com/hit-moodle/moodle-local_onlinejudge/archives/master

or using git:

`git clone git://github.com/hit-moodle/moodle-local_onlinejudge.git onlinejudge`


Installation & Upgrading
========================

*MOODLE_PATH means the root path of your moodle installation.*

For Linux Users
---------------

1. Make sure the directory name of this plugin is `onlinejudge`. If not, rename it.
2. If the directory `MOODLE_PATH/local/onlinejudge/` exists, remove it.
3. Put `onlinejudge` into `MOODLE_PATH/local/`
4. `cd MOODLE_PATH/local/onlinejudge && ./install_assignment_type`
5. Login your site as admin and the plugins can be installed/upgraded.
6. `sudo -u www-data php MOODLE_PATH/local/onlinejudge/cli/judged.php`
7. If you want to use sandbox judge engine, then
   `cd MOODLE_PATH/mod/assignment/type/onlinejudge/judge/sandbox/sand/ && make`

For Windows Users
---------------

1. Make sure the folder name of this plugin is `onlinejudge`. If not, rename it.
2. If the folder `MOODLE_PATH\local\onlinejudge\` exists, remove it.
3. If the folder `MOODLE_PATH\mod\assignment\type\onlinejudge` exists, remove it.
4. Put `onlinejudge` into `MOODLE_PATH\local\`
5. copy MOODLE_PATH\local\onlinejudge\clients\mod\assignment\type\onlinejudge\ 
   to MOODLE_PATH\mod\assignment\type\
6. Login your site as admin and the plugins can be installed/upgraded.
7. `php.exe MOODLE_PATH\local\onlinejudge\cli\judged.php`


Links
=====

Home:

* <https://github.com/hit-moodle/moodle-local_onlinejudge>

Bug reports, feature requests, help wanted and other issues:

* <https://github.com/hit-moodle/moodle-local_onlinejudge/issues>
