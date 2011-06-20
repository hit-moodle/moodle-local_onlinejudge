** THIS PLUGIN IS STILL UNDER DEVELOPMENT AND NOT SUTIABLE TO PRODUCTION SITES **

Summary
=======

The Online Judge 2 plugin for Moodle 2 is designed for courses involving programming.
It can automatically grade submitted source code by testing them against customizable
test cases (ACM-ICPC/Online Judge style).

It contains:

1. A local plugin call judgelib which provides the essential abilities of online judge.
   It works like a library.
2. A assignment type plugin call online judge which provides a UI to teachers and students
   and calls the judgelib to judge submissions.


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


Installation & Upgrading
========================

* MOODLE_PATH means the root path of your moodle installation. *

For Linux Users
---------------

1. Make sure the directory name of this plugin is `onlinejudge2`. If not, rename it.
2. If the directory `MOODLE_PATH/local/onlinejudge2/` exists, remove it.
2. Put `onlinejudge2` into `MOODLE_PATH/local/`
3. `cd MOODLE_PATH/local/onlinejudge2 && ./install_assignment_type`
4. Login your site as admin and the plugins can be installed/upgraded.
5. If you want to use sandbox judge engine, then
   `cd MOODLE_PATH/local/onlinejudge2/clients/mod/assignment/type/onlinejudge/judge/sandbox/sand/ && make`


Links
=====

Home:
    https://github.com/hit-moodle/moodle-local_onlinejudge2

Bug reports, feature requests, help wanted and other issues:
    https://github.com/hit-moodle/moodle-local_onlinejudge2/issues
