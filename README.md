**THIS PLUGIN IS IN BETA VERSION AND NOT RECOMMANDED TO USE IN PRODUCTION SITES**

Introduction
============

The Online Judge 2 plugin for Moodle 2 is designed for courses involving programming.
It can automatically grade submitted source code by testing them against customizable
test cases (ACM-ICPC/Online Judge style).

It contains three modules:

1. *judgelib* - a local plugin which provides an online judge function library to any part
   of Moodle.
2. *judges* - judge engine plugins for judgelib.
3. *clients* - any kind of moodle plugins can work as the client of judgelib to provide UI
   to teachers and students and call the judgelib to judge submissions.

Now, the project includes two judge engines:

1. *sandbox* compiles and executes programs on the server in a protected environment.
   Supports C and C++ only.
2. *ideone* posts all data to ideone.com which compiles and executes programs remotely.
   Supports 40+ languaes, such as C/C++, Java, Python, C#, JavaScript, Perl, PHP.

Now, the project includes one client:

1. Online Judge Assignment Type - online judge version of the official *advanced uploading of files* assignment type.

The workflow is:

1. Administrators set global settings in judgelib.
2. Teachers create Online Judge Assignment Activities and setup testcases etc.
3. Students submit code in Online Judge Assignment Activities.
4. A judge daemon judges the submissions in backgroud.
5. Teachers and students get judge results in Online Judge Assignment Activities.


Prerequisite
============

In Linux
--------

* Moodle 2.0 or above
* php-cli
* make, gcc and g++ (optional but recommended)
* pcntl and posix extension in php-cli (optional but recommended)

In Windows
----------

* Moodle 2.0 or above
* php-cli


Download
========

Download it from https://github.com/hit-moodle/moodle-local_onlinejudge/archives/master

or using git:

`git clone git://github.com/hit-moodle/moodle-local_onlinejudge.git onlinejudge`


Installation & Upgrading
========================

*MOODLE_PATH means the root path of your moodle installation.*

In Linux
--------

1. If the directory `MOODLE_PATH/local/onlinejudge` exists, remove it.
2. Make sure the directory name of this plugin is `onlinejudge`. If not, rename it.
3. Put `onlinejudge` into `MOODLE_PATH/local/`
4. `cd MOODLE_PATH/local/onlinejudge && ./install_assignment_type`
5. Login your site as admin and the plugins will be installed/upgraded.
6. `sudo -u www-data php MOODLE_PATH/local/onlinejudge/cli/judged.php`
7. If you want to use sandbox judge engine, then
   `cd MOODLE_PATH/local/onlinejudge/judge/sandbox/sand/ && make`

In Windows
----------

1. If the folder `MOODLE_PATH\local\onlinejudge` exists, remove it.
2. If the folder `MOODLE_PATH\mod\assignment\type\onlinejudge` exists, remove it.
3. Make sure the folder name of this plugin is `onlinejudge`. If not, rename it.
4. Put `onlinejudge` into `MOODLE_PATH\local\`
5. Enter folder `MOODLE_PATH\local\onlinejudge\` and run `install_assignment_type`
6. Login your site as admin and the plugins will be installed/upgraded.
7. `php.exe MOODLE_PATH\local\onlinejudge\cli\judged.php`

Usage
=====

Online Judge Assignment Type
----------------------------

After installation, there will be a new assignment type called *Online Judge* appears in the *"Add an activity..."* drop down list. Simply click it and follow the inline help.

Judge Daemon
------------

The judge daemon has several helpful options for debugging propose. Try to add `--help` argument in the command line.

Links
=====

Home:

* <https://github.com/hit-moodle/moodle-local_onlinejudge>

Bug reports, feature requests, help wanted and other issues:

* <https://github.com/hit-moodle/moodle-local_onlinejudge/issues>
