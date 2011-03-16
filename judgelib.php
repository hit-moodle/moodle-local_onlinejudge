<?php
	global $CFG,$DB;
	require_once($CFG->rootdir."/lib/dml/moodle_database.php");
	require_once("./judge/ideone/ideone.php");
	class judge_base 
	{
		public        $taskID;
		public        $taskName;
		public        $taskContent;
		public        $taskLang;
		public        $compiler; // 0->sandbox, 1->ideone
		public        $max_cpu = 10;
		public        $max_mem = 268435456;
        public        $input;
		public        $output;
		public        $status;	

		/*
			the construct function get the task information. 
		*/
		public function __Construct($taskID, $taskName, $taskContent, $taskLang, $compiler, $max_cpu, $max_mem, $input, $output, $status)
		{
			$this->taskID = $taskID;
			$this->taskName = $taskName;
			$this->taskContent = $taskContent;
			$this->taskLang = $taskLang;
			$this->compiler = $compiler;
			$this->max_cpu = $max_cpu;
			$this->max_mem = $max_mem;
			$this->input = $input;
			$this->output = $output;	
			$this->status = $status;
			
		}
		
		public function insertToDB()
		{
			$sql = "insert into onlinejudge2_task values($this->taskID, '$this->taskName', 
							'$this->taskContent', $this->taskLang, $this->compiler, $this->max_cpu, 
							$this->max_mem, '$this->input', '$this->output', $this->status";
			$DB->query-start($sql, null, SQL_QUERY_INSERT, null);
		}
		
		public function judge($sub)
		{
        	$ret = false;

        	$judge_type = substr($this->onlinejudge->language, strrpos($this->onlinejudge->language, '_'));

        	if($judge_type == '_ideone') 
        	{
           		$result = $this->judge_ideone($sub);
        	} 
        	else 
        	{
            	$result = $this->judge_local($sub);
        	}

        	if ($result) 
        	{
            	$result->submission = $sub->id;
            	$result->judgetime = time();
            	$result->info = addslashes($result->info);
            	$result->output = addslashes($result->output);
            	if ($ret = insert_record('assignment_oj_results', $result, false)) 
            	{
                	$newsub = null;
                	$newsub->id = $sub->id;
                	$newsub->teacher = get_admin()->id;
                	$newsub->timemarked = time();
                	$newsub->grade = $result->grade;
                	$ret = update_record('assignment_submissions', $newsub);
                	$this->update_grade($sub);
            	}
        	}

        	return $ret;
		}
		
		/**
     	* Evaluate student submissions
     	*/
    	function cron() {

        	global $CFG;

        	// Detect the frequence of cron
        	$lastcron = get_field('modules', 'lastcron', 'name', 'assignment');
        	if ($lastcron) {
            	set_config('assignment_oj_cronfreq', time() - $lastcron);
        	}

        	// There are two judge routines
        	//  1. Judge only when cron job is running. 
        	//  2. After installation, the first cron running will fork a daemon to be judger.
       		// Routine two works only when the cron job is executed by php cli
        	//
        	if (function_exists('pcntl_fork')) { // pcntl_fork supported. Use routine two
            	$this->fork_daemon();
        	} else if ($CFG->assignment_oj_judge_in_cron) { // pcntl_fork is not supported. So use routine one if configured.
            	$this->judge_all_unjudged();
        	}
    	}
    	
		// Judge all unjudged submissions
    	function judge_all_unjudged()
    	{
        	global $CFG;
        	while ($submission = $this->get_unjudged_submission()) {
            	$cm = get_coursemodule_from_instance('assignment', $submission->assignment);
            	$this->assignment_onlinejudge($cm->id);

            	$this->judge($submission);
        	}
    	}
		
		function getResult($sub)
		{
			// return the result..
		}
	}
	
	class judge_sandbox extends judge_base
	{
		public function judge($sub)
		{
			//use cron function ? to judge, 
			$sql = "select * from onlinejudge2_task where".$sub."==onlinejudge_task.id
					and onlinejudge2_task.status == 0";
			$result = $DB->query($sql, null, SQL_QUERY_SELECT, null); 
		    $rs = mysql_fetch_array($result, MYSQL_ASSOC);
		    if($rs['compiler'] == 0)
		    {
        		$ret = new Object();
       			$ret->output = '';
        		$result = array('pending', 'ac', 'rf', 'mle', 'ole', 'tle', 're', 'at', 'ie');
        		/* Only root can chroot(set jail)
        		$jail = $CFG->dataroot.'/temp/sandbox_jail/';
        		if (!check_dir_exists($jail, true, true)) {
           			mtrace("Can't mkdir ".$jail);
            		return 'ie';
        		}
         		*/

        		$sand = $CFG->dirroot . '/mod/assignment/type/onlinejudge/sandbox/sand';
        		if (!is_executable($sand)){
            		$ret->status = 'ie';
            		return $ret;
        		}
				$sand .= ' -l cpu='.($this->onlinejudge->cpulimit*1000).' -l memory='.$this->onlinejudge->memlimit.' -l disk=512000 '.$exec_file; 

        		$descriptorspec = array(
            		0 => array('pipe', 'r'),  // stdin is a pipe that the child will read from
            		1 => array('file', $exec_file.'.out', 'w'),  // stdout is a file to write to
            		2 => array('pipe', '$exec_file.err', 'w') // stderr is a file to write to
        		);

        		$proc = proc_open($sand, $descriptorspec, $pipes);

        		if (!is_resource($proc)) {
            		$ret->status = 'ie';
            		return $ret;
       			 }

        		fwrite($pipes[0], $case->input);
        		fclose($pipes[0]);


        		$return_value = proc_close($proc);
        		$ret->output = file_get_contents($exec_file.'.out');

        		if ($return_value == 255) {
            		$ret->status = 'ie';
            		return $ret;
        		} else if ($return_value >= 2) {
            		$ret->status = $result[$return_value];
            	return $ret;
        		} else if ($return_value == 0) {
            		mtrace('Pending? Why?');
            		exit();
        		}

        		$ret->status = $this->diff($case->output, $ret->output);
        		return $ret;
		    }
		}	
	}
	
	class judge_ideone extends judge_base
	{
		public function judge($sub)
		{
			//use cron function ? to judge, 
			$sql = "select * from onlinejudge2_task where".$sub."==onlinejudge_task.id
					and onlinejudge2_task.status == 0";
			$result = $DB->query($sql, null, SQL_QUERY_SELECT, null); 
		    $rs = mysql_fetch_array($result, MYSQL_ASSOC);

		    if($rs['compiler'] == 1)
		    {
		    	// creating soap client
		    	$client = new SoapClient("http://ideone.com/api/1/service.wsdl");
				$user = $CFG->assignment_oj_ideone_username;                                               
        		$pass = $CFG->assignment_oj_ideone_password;

        		if ($source = $this->get_submission_file_content($sub->userid)) 
        		{
           			$cases = $this->get_tests();
					$status_ideone = array(
                		11  => 'ce',
                		12  => 're',
                		13  => 'tle',
                		15  => 'ok',
                		17  => 'mle',
                		19  => 'rf',
                		20  => 'ie'
            		);

            	$result->grade = -1;

            	try { // Begin soap
                	// Submit all cases first to save time.
                	$links = array();
                	foreach ($cases as $case) 
                	{
                    	$webid = $client->createSubmission($user,$pass,$source,$this->ideone_langs[$this->onlinejudge->language],$case->input,true,true);     
                    	if ($webid['error'] == 'OK')
                        	$links[] = $webid['link'];
                    	else 
                    	{
                        	$result->status = 'ie';
                        	$result->info = $webid['error'];
                        	return $result;
                    	}
                	}

                	// Get ideone results
                	$delay = $CFG->assignment_oj_ideone_delay;
                	$i = 0;
                	$results = array();
                	foreach ($cases as $case) 
                	{
                    	while(1)
                    	{
                        	if ($delay > 0) 
                        	{
                            	sleep($delay); 
                            	$delay = ceil($delay / 2);
                        	}
                        	$status = $client->getSubmissionStatus($user, $pass, $links[$i]);
                        	if($status['status'] == 0) 
                        	{
                            	$delay = 0;
                            	break;
                        	}
                    	}

                    	$details = $client->getSubmissionDetails($user,$pass,$links[$i],false,true,true,true,true,false);         

                    	$result->status = $status_ideone[$details['result']];

                    	// If got ce or compileonly, don't need to test other case
                    	if ($result->status == 'ce' || $this->onlinejudge->compileonly) 
                    	{
                        	if ($result->status != 'ce' && $result->status != 'ie')
                            	$result->status = 'compileok';
                        	$result->info = $details['cmpinfo'] . '<br />'.get_string('ideonelogo', 'assignment_onlinejudge');
                        	$result->grade = $this->grade_marker('ce', $this->assignment->grade);
                        	return $result;
                   		}

                    	// Check for wa, pe, tle, mle or accept
                    	if ($result->status == 'ok') 
                    	{
                        	if ($details['time'] > $this->onlinejudge->cpulimit)
                            	$result->status = 'tle';
                        	else if ($details['memory']*1024 > $this->onlinejudge->memlimit)
                            	$result->status = 'mle';
                        else 
                        {
                            $result->output = $details['output'];
                            $result->status = $this->diff($case->output, $result->output);
                        }
                    }

                    $results[] = $result;
                    unset($result);
                    $i++;
                } 
            }catch (SoapFault $ex) 
            {
                $result->status = 'ie';
                $result->info = 'faultcode='.$ex->faultcode.'|faultstring='.$ex->faultstring;
                return $result;
            }
            $result = $this->merge_results($results, $cases);
            $result->info .= '<br />'.get_string('ideonelogo', 'assignment_onlinejudge');
            return $result;
        } 
        else 
        {
            return false;
        }
		    	
	}
}	
	
	}

?>
