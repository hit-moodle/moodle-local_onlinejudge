NOTE: domjudge in this directory needs to be installed separately and configured before this Moodle assignment plugin can work.

DOMjudge installation
---------------------

1) Software requirements
    * gcc with standard libraries
    * make
    * For every supported programming language a compiler is needed; preferably one that can generate statically linked stand-alone executables.
    * Apache web server with support for PHP >= 4.3.2.
    * Bash >= 2, located in /bin/bash.
    * Statically compiled Bash >= 2 (included for Linux IA32)
    * glibc >= 2.1
    * Root privileges

2) Software installation
    * Copy domjudge into web server directory.
    
    * Configure the system (1): edit system/etc/global.cfg file.
    	+ ROOT_BASE: Root directory of domjudge. WITHOUT final slash (e.g. in Apache: /var/www/domjudge)
    	+ WEBSERVER: Server name or IP direction. Only use 'localhost' if domjudge is installed on same server as Moodle.
    	+ WEBBASEURI: Defaults to http://WEBSERVER/domjudge but can be changed e.g. when running in a subdir, at a https-site or different server port.
		+ RUNUSER: User under which to run solutions (ID or name). Default domjudge-run.
		+ Restrictions during testing can be defined too. See file for more information.
		
    * Make sure that all of the DOMjudge stuff is in the directory set as ROOT_BASE.
    
    * Configure the system (2): edit system/Makefile.global file.
    	+ Default DOMjudge installation is based on Debian Linux.
    	+ If you are installing DOMjudge in other system (e.g. RedHat, MacOS X...), check specific options of ROOTCMD and USERADD.
    	+ ROOTCMD: How to execute a command as root. Default: sudo -u root /bin/bash -c
    	+ USERADD: How to add RUNUSER user. Default: useradd -d /nonexistent -g nogroup -s /bin/false $(RUNUSER)
    		- NOTE: for security reasons, we create the user with no home folder, no shell and belongs to non existent group.
    	
    * From ROOT_BASE directory: make ; make install
    
    * At this point, It should be done by now. Open a new web browser window and type:
    	+ http://WEBSERVER/domjudge/system/judge/judge.php?wsdl or
    	+ http://WEBBASEURI/system/judge/judge.php?wsdl
    	
    * A XML file must be shown on screen. It means that web service runs. Copy URL and go back to Moodle.
    
	* In Moodle Administration Site, go to Modules -> Activities -> Assignment / Settings and paste the URL into assignment_judgehost textbox. This would be enough to have Epaile running.

	
Contact
-------

If you have problems installing DOMjudge, please feel free to contact me at:
	email: arkaitz.garro@gmail.com
	skype: arkaitz.garro
	