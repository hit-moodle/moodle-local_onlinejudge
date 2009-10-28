Installation of onlinejudge assignment type for moodle

PREPARE
-------

Edit mod/assignment/mod_form.php. Change

$assignmentinstance->setup_elements($mform);

to

$assignmentinstance->setup_elements($mform, $this);


DOWNLOAD
--------

Download from http://code.google.com/p/sunner-projects/downloads/list
Untar and put onlinejudge/ into mod/assignment/type/ 


MAKE sandbox
------------
In mod/assignment/type/onlinejudge/sandbox/, run:

make


INSTALLATION
------------

   1. Login moodle as admin
   2. Access http://site.domain.name/admin/index.php
   3. Follow the instructions shown by above url 


USAGE
-----
The same as other standard assignment types. 


Contact
-------

http://code.google.com/p/sunner-projects/

If you have problems, please feel free to contact me at:


CREDIT
------

This project uses some idea and code of arkaitz.garro@gmail.com
	
