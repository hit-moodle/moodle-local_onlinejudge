<?php
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
		
		function judge($id)
		{	
			//To Do: restore the data to database onlinejudge2_task. then use the function cron 
			//       to scan the certain data that task.id =1 ,then judge it.
			//       it's the next big thing to do.
			
			
			$sql = "select * from onlinejudge2_task where".$id."==onlinejudge_task.id;";
			$result = mysql_query($sql); 
		    $rs = mysql_fetch_array($result, MYSQL_ASSOC);
			if($rs['compiler'] == 0) 
			{
				// call sandbox compiler.
			}		
			else if($rs['compiler'] == 1)
			{
				//call ideone compiler..
			}
			else 
			{
				//error or another compiler to be added.
			}
			mysql_close();
		}

		function getResult($id)
		{
			// return the result..
		}
	}
	
	class judge_sandbox extends judge_base
	{
	}
	class judge_ideone extends judge_base
	{
	}

?>
