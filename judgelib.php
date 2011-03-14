<?php
	class judge_base 
	{
		int        $taskID;
		string     $taskName;
		string     $taskContent;
		string     $taskLang;
		int        $compiler; // 0->sandbox, 1->ideone
        string     $input;
		string     $output;
		int        $status;	

		/*
			the construct function get the task information. 
		*/
		function __Construct($taskID, $taskName, $taskContent, $taskLang, $compiler, $input, $output, $status)
		{
			$this->taskID = $taskID;
			$this->taskName = $taskName;
			$this->taskContent = $taskContent;
			$this->taskLang = $taskLang;
			$this->compiler = $compiler;
			$this->input = $input;
			$this->output = $output;	
			$this->status = $status;
			
		}
		
		function judge($id)
		{	
			$sql = "select * from onlinejudge2_type where $id==onlinejudge_type.id;";
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
