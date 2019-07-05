<?php

/**********************************************************************
	CLASS:	URL
	PURPOSE:	handles specific processing for document objects
**********************************************************************/
class DOCMGR_WORKFLOW extends DOCMGR 
{

	private $workflowId;
	private $routeId;
	private $accountId;

  /*******************************************************************************
  	called from DOCMGR class
	*******************************************************************************/
	function ___construct()
	{

		//try to snag from api
		if ($this->apidata["route_id"]) 		$this->routeId = $this->apidata["route_id"];
		if ($this->apidata["workflow_id"]) 	$this->workflowId = $this->apidata["workflow_id"];

		if ($this->routeId && !$this->workflowId) 
		{

			$sql = "SELECT workflow_id FROM docmgr.dm_workflow_route WHERE id='".$this->routeId."'";
			$info = $this->DB->single($sql);
			
			$this->workflowId = $info["workflow_id"];
			
		}

	}

	function checkPermissions($mode="view")
	{

		$check = true;
	
		if ($this->workflowId)
		{

			if ($mode=="view")
			{

				$sql = "SELECT id FROM docmgr.dm_workflow WHERE 
								(id='".$this->workflowId."' AND	account_id='".USER_ID."') OR 
								id IN (SELECT workflow_id FROM docmgr.dm_workflow_route WHERE account_id='".USER_ID."')
								";

				$info = $this->DB->single($sql);

				if (!$info) $check = false;

			}
			else
			{

				//convert to an array so we can check against multiple workflows.  usually this only happens during deletion		
				if (is_array($this->workflowId)) $arr = $this->workflowId;
				else $arr = array($this->workflowId);
		
				$sql = "SELECT id FROM docmgr.dm_workflow WHERE id IN (".implode(",",$arr).") AND account_id='".USER_ID."'";
				$results = $this->DB->fetch($sql);
				
				//all of them must be accessible by the current user
				if ($results["count"] < count($arr)) $check = false;				

			}
			
			
		}
		
		return $check;
	
	}

  /********************************************************************
  	FUNCTION: getlist
  	PURPOSE:  retrieves document from the system
	********************************************************************/
	public function search() 
	{
	
		//get all workflows we own, or are part of
		$sql = "SELECT * FROM docmgr.dm_workflow
						WHERE 
							(account_id='".USER_ID."' OR 
							dm_workflow.id IN (SELECT workflow_id FROM docmgr.dm_workflow_route WHERE account_id='".USER_ID."')
							)";

		if ($this->apidata["filter"]=="current")
			$sql .= "	AND (dm_workflow.status IN ('nodist','pending') OR dm_workflow.status IS NULL) ";
		else if ($this->apidata["filter"]=="history")
			$sql .= "	AND (dm_workflow.status IN ('forcecomplete','complete','rejected')) ";

		$sql .= "	ORDER BY id DESC";

		$list = $this->DB->fetch($sql);

		//loop through and add sme extra information		
		for ($i=0;$i<$list["count"];$i++) 
		{

			$sql = "SELECT id,name FROM docmgr.dm_object WHERE id IN 
							(SELECT object_id FROM docmgr.dm_workflow_object WHERE workflow_id='".$list[$i]["id"]."')";
			$objlist = $this->DB->fetch($sql);
			unset($objlist["count"]);
     
			$list[$i]["object"] = $objlist;
                             
			$a = new ACCOUNT($list[$i]["account_id"]);
			$ainfo = $a->getInfo();

			$list[$i]["account_name"] = $ainfo["first_name"]." ".$ainfo["last_name"];
		
			$list[$i]["status_view"] = $this->viewStatus($list[$i]["status"]);	
			$list[$i]["date_create_view"] = dateView($list[$i]["date_create"]);
			$list[$i]["date_complete_view"] = dateView($list[$i]["date_complete"]);

			$this->PROTO->add("record",$list[$i]);
			
		}

	}

	public function save()
	{

		if ($this->workflowId)
		{
		
			//create a new workflow w/ the default values
			$opt = null;
			$opt["name"] = $this->apidata["name"];
			$opt["where"] = "id='".$this->workflowId."'";
			$this->DB->update("docmgr.dm_workflow",$opt);
		
		}
		else
		{
		
			//must have an object to link this to
			if (!$this->objectId)
			{
				$this->throwError(_I18N_OBJECT_ID_ERROR);
				return false;
			}
	
			//must have an object to link this to
			if (!$this->apidata["name"])
			{
				$this->throwError(_I18N_WORKFLOW_NAME_ERROR);
				return false;
			}
	
			$this->DB->begin();
				
			//create a new workflow w/ the default values
			$opt = null;
			$opt["name"] = $this->apidata["name"];
			$opt["status"] = "nodist";
			$opt["account_id"] = USER_ID;
			$opt["date_create"] = date("Y-m-d H:i:s");
			$this->workflowId = $this->DB->insert("docmgr.dm_workflow",$opt,"id");
	
			if ($this->workflowId)
			{
	
				for ($i=0;$i<count($this->objectId);$i++)
				{
	
					$opt = null;
					$opt["workflow_id"] = $this->workflowId;
					$opt["object_id"] = $this->objectId[$i];
					$this->DB->insert("docmgr.dm_workflow_object",$opt);		
	
				}
				
			}
		
			$this->DB->end();
			
			$this->PROTO->add("workflow_id",$this->workflowId);
	
			return $this->workflowId;
		
		}

				
	}

	public function get() 
	{

		//permissions checking.  here, since we are just viewing, we see what object
		//this pertains to and check against that
		if (!$this->checkPermissions("view"))
		{
			$this->throwError(_I18N_WORKFLOW_VIEW_ERROR);
			return false;
		}

		//get some basic info
		$sql = "SELECT * FROM docmgr.dm_workflow WHERE id='".$this->workflowId."'";
		$info = $this->DB->single($sql);

		//not found		
		if (!$info) $this->throwError(_I18N_WORKFLOW_GET_ERROR);
		else 
		{

			//recipients
			$sql = "SELECT * FROM docmgr.dm_workflow_route WHERE workflow_id='".$this->workflowId."' ORDER BY sort_order";
			$reciplist = $this->DB->fetch($sql);
		
			//translate status
			$info["status_view"] = $this->viewStatus($info["status"]);	
			$info["date_create_view"] = dateView($info["date_create"]);
			$info["date_complete_view"] = dateView($info["date_complete"]);

			//merge and extendin recipient info
			for ($i=0;$i<$reciplist["count"];$i++) 
			{

				$a = new ACCOUNT();
				$accountinfo = $a->get($reciplist[$i]["account_id"]);

				$reciplist[$i]["date_due_view"] = noTimeDateView($reciplist[$i]["date_due"]);
				$reciplist[$i]["date_complete_view"] = dateView($reciplist[$i]["date_complete"]);
				$reciplist[$i]["account_name"] = $accountinfo["first_name"]." ".$accountinfo["last_name"];
				$reciplist[$i]["account_login"] = $accountinfo["login"];
				$reciplist[$i]["status_view"] = $this->viewStatus($reciplist[$i]["status"]);
	
				$info["recipient"][$i] = $reciplist[$i];
			
			}

			//merge in some object info
			$sql = "SELECT object_id FROM docmgr.dm_workflow_object WHERE workflow_id='".$this->workflowId."'";
			$objs = $this->DB->fetch($sql);
			
			$info["object"] = array();
			
			for ($i=0;$i<$objs["count"];$i++)
			{
			
				$d = new DOCMGR_OBJECT($objs[$i]["object_id"]);
				$obj = $d->getInfo();
			
				$arr = array();
				$arr["object_id"] = $objs[$i]["object_id"];
				$arr["name"] = $obj["name"];
				$arr["object_path"] = $obj["object_path"];
				
				$info["object"][$i] = $arr;				
			
			}

			//clear out proto from incidental data from our system calls
			$this->PROTO->clearData();	

			//output to proto
			$this->PROTO->add("record",$info);
			
		}

	}

	protected function viewStatus($stat) 
	{
	
			$view = null;
			
			if ($stat=="forcecomplete") $view = _I18N_FORCECOMPLETE;
			else if ($stat=="nodist") $view = _I18N_NOTDISTRIBUTED;
			else if ($stat=="pending") $view = _I18N_INPROGRESS;
			else if ($stat=="complete") $view = _I18N_COMPLETED;
			else if ($stat=="rejected") $view = _I18N_REJECTED;
			else $view = _I18N_NOTDISTRIBUTED;
				
			return $view;
	}

	
	public function saveRecipient() 
	{

		//permissions checking
		if (!$this->checkPermissions("edit"))
		{
			$this->throwError(_I18N_WORKFLOW_EDIT_ERROR);
			return false;
		}
	
		$this->DB->begin();

		$accountId = $this->apidata["account_id"];
		if (!is_array($accountId)) $accountId = array($accountId);

		//update existing route
		if ($this->routeId)
		{
			$opt = null;
			$opt["task_type"] = $this->apidata["task_type"];
			$opt["task_notes"] = $this->apidata["task_notes"];
			$opt["date_due"] = dateProcess($this->apidata["date_due"]);
			$opt["where"] = "id='".$this->routeId."'";
			$this->DB->update("docmgr.dm_workflow_route",$opt);
		
		} 
		else 
		{

			$sql = "SELECT object_id FROM docmgr.dm_workflow_object WHERE workflow_id='".$this->workflowId."'";
			$objarr = $this->DB->fetch($sql,1);
			
			//create a new task for every account and every object passed		
			for ($i=0;$i<count($accountId);$i++) 
			{

				//make sure this account doesn't already have an entry in this stage
				$sql = "SELECT account_id FROM docmgr.dm_workflow_route WHERE workflow_id='".$this->workflowId."' AND
																	account_id='".$accountId[$i]."' AND sort_order='".$this->apidata["stage"]."'";
				$info = $this->DB->single($sql);
			
				if ($info) 
				{
					$this->throwError(_I18N_WORKFOW_ACCOUNT_STAGE_ERROR);
					break;
				}
		
				$opt = null;
				$opt["workflow_id"] = $this->workflowId;
				$opt["account_id"] = $accountId[$i];
				$opt["task_type"] = $this->apidata["task_type"];
				$opt["task_notes"] = $this->apidata["task_notes"];
				$opt["date_due"] = dateProcess($this->apidata["date_due"]);
				$opt["sort_order"] = $this->apidata["stage"];
				$opt["status"] = "nodist";
				
				$routeId = $this->DB->insert("docmgr.dm_workflow_route",$opt,"id");

				foreach ($objarr["object_id"] AS $obj)
				{

					$opt = null;
					$opt["route_id"] = $routeId;
					$opt["object_id"] = $obj;
					$this->DB->insert("docmgr.dm_workflow_route_object",$opt);

				}
					
			}

		}

		$this->consolidate();
		
		$this->DB->end();

		$err = $this->DB->error();
		
		if ($err) $this->throwError($err);
	
	}

	public function deleteRecipient() 
	{

		//permissions checking
		if (!$this->checkPermissions("edit"))
		{
			$this->throwError(_I18N_WORKFLOW_EDIT_ERROR);
			return false;
		}

		$this->DB->begin();
			
		$sql = "DELETE FROM docmgr.dm_workflow_route WHERE id='".$this->routeId."';";
		$sql .= "DELETE FROM docmgr.dm_workflow_route_object WHERE route_id='".$this->routeId."';";
		$this->DB->query($sql);
		
		$this->consolidate();
		
		$this->DB->end();
		
		$err = $this->DB->error();
		if ($err) $this->throwError($err);

	}	

	//keeps everything in order in case someone gets goofy w/ the adding of stuff
	protected function consolidate() 
	{

		$sql = "SELECT DISTINCT sort_order FROM docmgr.dm_workflow_route WHERE workflow_id='".$this->workflowId."' ORDER BY sort_order"; 
		$list = $this->DB->fetch($sql);
		
		for ($i=0;$i<$list["count"];$i++) 
		{
		
			$so = $list[$i]["sort_order"];

			$sql = "SELECT id FROM docmgr.dm_workflow_route WHERE sort_order='$so' AND workflow_id='".$this->workflowId."' ORDER BY id";
			$matches = $this->DB->fetch($sql,1);
			
			$sql = "UPDATE docmgr.dm_workflow_route SET sort_order='".($i)."' WHERE id IN (".implode(",",$matches["id"]).")";
			$this->DB->query($sql);
			
		}
	
	}

	//set workflow options
	public function setOpt() 
	{

		//permissions checking
		if (!$this->checkPermissions("edit"))
		{
			$this->throwError(_I18N_WORKFLOW_EDIT_ERROR);
			return false;
		}
	
		//emailcomplete, emailexpired
		if ($this->apidata["option"]=="emailcomplete") $field = "email_notify";
		else $field = "expire_notify";
		
		if ($this->apidata["action"]=="set") $val = "t";
		else $val = "f";
		
		$opt = null;
		$opt[$field] = $val;
		$opt["where"] = "id='".$this->workflowId."'";
		$this->DB->update("docmgr.dm_workflow",$opt);
		
		$err = $this->DB->error();
		if ($err) $this->throwError($err);
		
	
	}	

	//transfer all the current routes into a template
	protected function transferTemplate($templateId) 
	{

		$workflowId = $this->workflowId;

  	$sql = "SELECT * FROM docmgr.dm_workflow_route WHERE workflow_id='$workflowId'";
  	$list = $this->DB->fetch($sql);
  
  	//delete any current template info
  	$sql = "DELETE FROM docmgr.dm_saveroute_data WHERE save_id='$templateId';";

  	//get the time in seconds
  	$today = time();
  
  	for ($i=0;$i<$list["count"];$i++) 
  	{

  		$opt = null;

    	//get the date in seconds
    	if ($list[$i]["date_due"])
			{
			
    		$sec = strtotime($list[$i]["date_due"]);
    
    		$diff = $sec - $today;
    		$days = intValue($diff/86400);
    		if (!$days) $days = "0";

	    	$opt["date_due"] = $days;

			}
			  
    	$opt["save_id"] = $templateId;
    	$opt["account_id"] = $list[$i]["account_id"];
    	$opt["task_type"] = $list[$i]["task_type"];
    	$opt["task_notes"] = $this->DB->sanitize($list[$i]["task_notes"]);
    	$opt["sort_order"] = $list[$i]["sort_order"];
    	$opt["query"] = 1;
    	$sql .= $this->DB->insert("docmgr.dm_saveroute_data",$opt);

		}

		$this->DB->query($sql);

	}
	
	public function saveTemplate() 
	{

		$this->DB->begin();

    $opt = null;

    if ($this->apidata["template_id"]) 
    {

    	//permcheck
			$sql = "SELECT account_id FROM docmgr.dm_saveroute WHERE id='".$this->apidata["template_id"]."'";
			$info = $this->DB->single($sql);
			
			if ($info["account_id"]!=USER_ID) 
			{
				$this->throwError(_I18N_TEMPLATE_EDIT_ERROR);
				return false;
			}

			$templateId = $this->apidata["template_id"];    
    	$opt["where"] = "id='".$templateId."'";
    	$this->DB->update("docmgr.dm_saveroute",$opt);
    
    } else 
    {

	    $opt["account_id"] = USER_ID;
	    $opt["name"] = $this->apidata["template_name"];
	    $templateId = $this->DB->insert("docmgr.dm_saveroute",$opt,"id");

		}
		
    $this->transferTemplate($templateId);

    $this->DB->end();

		$err = $this->DB->error();
		if ($err) $this->throwError($err);

  }  

  public function getTemplates() 
  {
  
  	//get all saved templates for this user
  	$sql = "SELECT * FROM docmgr.dm_saveroute WHERE account_id='".USER_ID."'";
  	$tempList = $this->DB->fetch($sql);
  	
  	for ($i=0;$i<$tempList["count"];$i++) 
  	{
  
  		$this->PROTO->add("record",$tempList[$i]);
  		
		}
		
	}

	function deleteTemplate() 
	{

   	//permcheck
		$sql = "SELECT account_id FROM docmgr.dm_saveroute WHERE id='".$this->apidata["template_id"]."'";
		$info = $this->DB->single($sql);
			
		if ($info["account_id"]!=USER_ID) 
		{
			$this->throwError(_I18N_TEMPLATE_EDIT_ERROR);
			return false;
		}
	
		$sql = "DELETE FROM docmgr.dm_saveroute_data WHERE save_id='".$this->apidata["template_id"]."';";
		$sql .= "DELETE FROM docmgr.dm_saveroute WHERE id='".$this->apidata["template_id"]."';";
		$this->DB->query($sql);

		$err = $this->DB->error();
		if ($err) $this->throwError($err);
		
	}	

	public function getFromTemplate() 
	{

   	//permcheck
		$sql = "SELECT account_id FROM docmgr.dm_saveroute WHERE id='".$this->apidata["template_id"]."'";
		$info = $this->DB->single($sql);
			
		if ($info["account_id"]!=USER_ID) 
		{
			$this->throwError(_I18N_TEMPLATE_EDIT_ERROR);
			return false;
		}

		$this->DB->begin();
		
		//transfer saveroute data into workflow data
		$sql = "SELECT * FROM docmgr.dm_saveroute_data WHERE save_id='".$this->apidata["template_id"]."'
											ORDER BY sort_order";
		$reciplist = $this->DB->fetch($sql);

		$sql = "DELETE FROM docmgr.dm_workflow_route WHERE workflow_id='".$this->workflowId."'";
		$this->DB->query($sql);

		//get the objects
		$sql = "SELECT object_id FROM docmgr.dm_workflow_object WHERE workflow_id='".$this->workflowId."'";
		$objlist = $this->DB->fetch($sql);
		
		for ($i=0;$i<$reciplist["count"];$i++) 
		{

			$opt = null;
		
			//calculate the date due
			if ($reciplist[$i]["date_due"])
			{
			
				$time = time();
				$diff = $time + ($list[$i]["date_due"] * 86400);
				$dateDue = date("Y-m-d H:i:s",$diff);

				$opt["date_due"] = $dateDue;

			}
			
			//rn the insert
			$opt["workflow_id"] = $this->workflowId;
			$opt["account_id"] = $reciplist[$i]["account_id"];
			$opt["task_type"] = $this->DB->sanitize($reciplist[$i]["task_type"]);
			$opt["status"] = "nodist";
			$opt["sort_order"] = $reciplist[$i]["sort_order"];
			$opt["task_notes"] = $this->DB->sanitize($reciplist[$i]["task_notes"]);			
			$rid = $this->DB->insert("docmgr.dm_workflow_route",$opt,"id");

			for ($c=0;$c<$objlist["count"];$c++)
			{
				$opt = null;
				$opt["route_id"] = $rid;
				$opt["object_id"] = $objlist[$c]["object_id"];
				$opt["completed"] = "f";
				$this->DB->insert("docmgr.dm_workflow_route_object",$opt);
			}
			
		}

		//fetch the new info
		$this->get();

		$this->DB->end();
		
		$err = $this->DB->error();
		if ($err) $this->throwError($err);

	}

	function begin()
	{

		//permissions checking
		if (!$this->checkPermissions("edit"))
		{
			$this->throwError(_I18N_WORKFLOW_EDIT_ERROR);
			return false;
		}
	
		$this->nextStage("0");
	
	}

	//issue our alerts for a specific stage of a route
	protected function nextStage($stage) 
	{

		$workflowId = $this->workflowId;

		$sql = "SELECT object_id FROM docmgr.dm_workflow_object WHERE workflow_id='$workflowId'";
		$objarr = $this->DB->fetch($sql,1);

		$this->DB->begin();
		
	  //get our list of routes assigned to this object
	  $sql = "SELECT * FROM docmgr.dm_workflow_route WHERE workflow_id='$workflowId' AND sort_order='$stage';";
	  $list = $this->DB->fetch($sql);

		if ($list["count"]==0) 
		{

		    //log that it's finished
	      foreach ($objarr["object_id"] AS $obj) logEvent(OBJ_WORKFLOW_END,$obj);

		    //see if we are supposed to notify the user this is complete
		    $this->notifyComplete();

		    $sql = "UPDATE docmgr.dm_workflow SET status='complete',date_complete='".date("Y-m-d H:i:s")."' WHERE id='$workflowId';";
		    $this->DB->query($sql);

				//clear all shares
				$this->cleanupPermissions();
	
	  } 
	  else 
	  {

	  	//starting a new one
	  	if ($stage=="0") foreach ($objarr["object_id"] AS $obj) logEvent(OBJ_WORKFLOW_BEGIN,$obj);

	  	$sql = "SELECT name FROM docmgr.dm_workflow WHERE id='$workflowId'";
	  	$info = $this->DB->single($sql);

	 	  //make sure our primary status is still set to pending since we are not done
	 	  $sql = "Update docmgr.dm_workflow SET status='pending' WHERE id='$workflowId';";
			$this->DB->query($sql);
			
			$accounts = array();	
			
	  	for ($i=0;$i<$list["count"];$i++) 
	  	{

				$sql = "UPDATE docmgr.dm_workflow_route SET status='pending' WHERE id='".$list[$i]["id"]."';";
				$this->DB->query($sql);

				//setup collections for sharing
				$this->setupPermissions($list[$i]["task_type"],$list[$i]["account_id"]);

				//notify the user a task was assigned
				$this->notifyTask($list[$i]);
				
			}
		
		}	

		$this->DB->end();
		
		$err = $this->DB->error();
		if ($err) $this->throwError($err);
			  
	}
	
	protected function notifyTask($data)
	{
	
			//just pass the date itself for now
			if ($data["date_due"]) 
			{
				$arr = explode(" ",$data["date_due"]);
				$due = dateView($arr[0]);
			}
			else $due = "Not Set";

			//get workflow owner and name	
			$sql = "SELECT account_id,name FROM docmgr.dm_workflow WHERE id='".$this->workflowId."'";
			$info = $this->DB->single($sql);
			
			$as = new ACCOUNT($info["account_id"]);
			$asinfo = $as->getInfo();

			//link to the task
			$link = SITE_URL."index.php?module=workflow&workflowId=".$this->workflowId."&routeId=".$data["id"];
			
			//setup our email message
			$message = "<html>
									<body>
										<p>
											<a href=\"".$link."\">"._I18N_CLICKHERE_PERFORMTASK."</a>
										</p>
										<h3>"._I18N_TASKDETAILS."</h3>
										<table border=\"0\" cellpadding=\"3\" cellspacing=\"0\">
										<tr><td align=\"right\">
											"._I18N_OBJECT.": 
										</td><td>
											".$info["name"]."
										</td></tr>
										<tr><td align=\"right\">
											"._I18N_TASKTYPE.":
										</td><td>
											".ucfirst($data["task_type"])."
										</td></tr>
										<tr><td align=\"right\">
											"._I18N_DUE.":
										</td><td>
											".$due."
										</td></tr>
										<tr><td align=\"right\">
											"._I18N_ASSIGNEDBY.":
										</td><td>
											".$asinfo["first_name"]." ".$asinfo["last_name"]."
										</td></tr>
										<tr><td align=\"right\">
											"._I18N_NOTES.":
										</td><td>
											".$data["task_notes"]."
										</td></tr>
										</table>
										</body>
										</html>
										";


			//set our notification type
	    if ($data["task_type"]=="edit") $type = WORKFLOW_EDIT_NOTIFICATION;
	    elseif ($data["task_type"]=="approve") $type = WORKFLOW_APPROVE_NOTIFICATION;
	    elseif ($data["task_type"]=="comment") $type = WORKFLOW_COMMENT_NOTIFICATION;
	    else $type = WORKFLOW_VIEW_NOTIFICATION;

			//record our notification and send the message if configured
			$n = new NOTIFICATION_NOTIFY();
			$n->send($type,$data["account_id"],$this->workflowId,$info["name"],$link,$message,null);
										
	}
	
	protected function notifyComplete($rejected=null) 
	{
	
			$workflowId = $this->workflowId;
			$routeId = $this->routeId;
			$objectId = $this->objectId;

			//fetch workflow name
			$sql = "SELECT name,account_id FROM docmgr.dm_workflow WHERE id='".$this->workflowId."'";
			$info = $this->DB->single($sql);

			//link to the task
			$link = SITE_URL."index.php?module=workflow&workflowId=".$this->workflowId;

     	//send a rejected message if workflow was stopped that way
			if ($rejected) 
			{
						
       	$type = WORKFLOW_REJECTED_NOTIFICATION;
       	$message = $info["name"].": "._I18N_REJECTEDBY." ".USER_FN." ".USER_LN." "._I18N_FOROBJECT." \"".$this->objectInfo["name"]."\"";

			//send a completed message	      
			} 
			else 
			{
					
				$type = WORKFLOW_COMPLETED_NOTIFICATION;
	    	$message = $info["name"].": "._I18N_WORKFLOW_COMPLETED;
						
			}

			//record our notification and send the message if configured
			$n = new NOTIFICATION_NOTIFY();
			$n->send($type,$info["account_id"],$this->workflowId,$info["name"],$link,$message,null);
					
	}
	
	public function forceComplete() 
	{

		//permissions checking
		if (!$this->checkPermissions("edit"))
		{
			$this->throwError(_I18N_WORKFLOW_EDIT_ERROR);
			return false;
		}

		$this->DB->begin();

		//clear the shared folders
		$this->cleanupPermissions();

		$sql = "SELECT id FROM docmgr.dm_workflow_route WHERE workflow_id='".$this->workflowId."';";
		$list = $this->DB->fetch($sql,1);
	
		//delete the tasks
		$sql = "UPDATE docmgr.dm_workflow_route SET status='forcecomplete' WHERE workflow_id='".$this->workflowId."' AND status!='complete';";
		$sql .= "UPDATE docmgr.dm_workflow_route_object SET completed='t' WHERE route_id IN 
														(SELECT id FROM docmgr.dm_workflow_route WHERE workflow_id='".$this->workflowId."');";
		$sql .= "UPDATE docmgr.dm_workflow SET status='forcecomplete',date_complete='".date("Y-m-d H:i:s")."' WHERE id='".$this->workflowId."';";
	
		$this->DB->query($sql);

		//log that it's completed
	  $sql = "SELECT object_id FROM docmgr.dm_workflow_object WHERE workflow_id='".$this->workflowId."'";
	  $objlist = $this->DB->fetch($sql);

		for ($i=0;$i<$objlist["count"];$i++)
		{
			logEvent(OBJ_WORKFLOW_CLEAR,$list[$i]["object_id"]);
		}
		
		$this->DB->end();
		
		$err = $this->DB->error();
		if ($err) $this->throwError($err);
	
	}

	public function markComplete() 
	{

		//make sure this user can edit this task
		$sql = "SELECT id FROM docmgr.dm_workflow_route WHERE id='".$this->routeId."' AND account_id='".USER_ID."'";
		$info = $this->DB->single($sql);
		
		if (!$info)
		{
			$this->throwError(_I18N_WORKFLOWTASK_EDIT_ERROR);
			return false;
		}

		$this->DB->begin();

		//see if we've finished all the objects
		$sql = "UPDATE docmgr.dm_workflow_route_object SET completed='t' WHERE route_id='".$this->routeId."' AND object_id='".$this->objectId."'";
		$this->DB->query($sql);
		
		$sql = "SELECT route_id FROM docmgr.dm_workflow_route_object WHERE route_id='".$this->routeId."' AND completed='f'";
		$check = $this->DB->single($sql);

		//all objects have been finished, mark the task complete
		if ($check)
		{
		
			//determine how to log
			$sql = "SELECT task_type FROM docmgr.dm_workflow_route WHERE id='".$this->routeId."';";
			$info = $this->DB->single($sql);
		
		}
		else
		{

			//update this route and delete the task from the alert
			$opt = null;
			$opt["status"] = "complete";
			$opt["comment"] = $this->apidata["comment"];
			$opt["where"] = "id='".$this->routeId."'";
			$this->DB->update("docmgr.dm_workflow_route",$opt);
	
			//figure out how many other tasks are left at this level
			$sql = "SELECT sort_order,workflow_id,task_type FROM docmgr.dm_workflow_route WHERE id='".$this->routeId."'";
			$info = $this->DB->single($sql);
	
			$sql = "SELECT id FROM docmgr.dm_workflow_route WHERE workflow_id='".$info["workflow_id"]."' 
									AND sort_order='".$info["sort_order"]."' AND status!='complete'";
			$list = $this->DB->fetch($sql);
	
			//set for beginWorkflow() later
			$this->workflowId = $info["workflow_id"];
			
			//if there are some left at this level, do nothing.If there are not, queue the approvers at the next stage
			if ($list["count"]=="0") 
			{
	
				$nextOrder = $info["sort_order"] + 1;
	
				//queue the tasks for the next stage.If this returns false, there are no objects left
				$this->nextStage($nextOrder);
	
			}

		}
		
		//log in the object's logs
		if ($info["task_type"]=="approve") $lt = OBJ_WORKFLOW_APPROVE;
		else if ($info["task_type"]=="edit") $lt = OBJ_WORKFLOW_EDIT;
		else if ($info["task_type"]=="comment") $lt = OBJ_WORKFLOW_COMMENT;
		else $lt = OBJ_WORKFLOW_VIEW;
		
		logEvent($lt,$this->objectId);

  	$this->DB->end();
  	
  	$err = $this->DB->error();
  	if ($err) $this->throwError($err);

	}

	public function rejectApproval() 
	{

		//make sure this user can edit this task
		$sql = "SELECT id FROM docmgr.dm_workflow_route WHERE id='".$this->routeId."' AND account_id='".USER_ID."'";
		$info = $this->DB->single($sql);
		
		if (!$info)
		{
			$this->throwError(_I18N_PERMISSION_DENIED);
			return false;
		}

		$routeId = $this->routeId;
		
		$this->DB->begin();

		//figure out how many other tasks are left at this left
		$sql = "SELECT sort_order,workflow_id FROM docmgr.dm_workflow_route WHERE id='$routeId';";
		$info = $this->DB->single($sql);

		$workflowId = $info["workflow_id"];

		//update this route and delete the task from the alert
		$sql = "UPDATE docmgr.dm_workflow_route SET status='rejected',comment='".$this->apidata["comment"]."' WHERE id='$routeId';";
		$sql .= "UPDATE docmgr.dm_workflow SET status='rejected',date_complete='".date("Y-m-d H:i:s")."' WHERE id='".$workflowId."';";
		$this->DB->query($sql);

  	//clear all shares
  	$this->cleanupPermissions();
  	
  	//notify the user it's done
  	$this->notifyComplete(1);

  	//log the rejection
  	logEvent(OBJ_WORKFLOW_REJECT,$objectId);

  	$this->DB->end();
  	
  	$err = $this->DB->error();
  	if ($err) $this->throwError($err);

	}

	//get all tasks for this user
	public function gettasks() 
	{

		$sql = "SELECT dm_workflow_route.*,dm_workflow.name AS workflow_name,dm_workflow.account_id AS workflow_account_id,
							object_id,(SELECT name FROM docmgr.dm_object WHERE id=object_id) AS object_name
							FROM docmgr.dm_workflow_route
							LEFT JOIN docmgr.dm_workflow ON dm_workflow_route.workflow_id=dm_workflow.id
							LEFT JOIN docmgr.dm_workflow_route_object ON dm_workflow_route.id=dm_workflow_route_object.route_id
							WHERE dm_workflow_route.account_id='".USER_ID."'
							AND
							completed='f'
							";

			
		if ($this->routeId) $sql .= " AND dm_workflow_route.id='".$this->routeId."'";
		else	$sql .= "  AND dm_workflow_route.status='pending'";

		$routes = $this->DB->fetch($sql);

			for ($i=0;$i<$routes["count"];$i++) 
			{

				$info = returnAccountInfo($routes[$i]["workflow_account_id"]);
				$routes[$i]["workflow_account_name"] = $info["first_name"]." ".$info["last_name"];
				$routes[$i]["workflow_account_login"] = $info["login"];
				if ($routes[$i]["date_due"] > '1980-01-01') $routes[$i]["date_due"] = dateView($routes[$i]["date_due"]);
				else $routes[$i]["date_due"] = null;

				$routes[$i]["object_path"] = $this->objectPath($routes[$i]["object_id"]);
				
				$this->PROTO->add("record",$routes[$i]);

			}
	
	}

	public function deleteObjectWorkflows($objId)
	{
	
		//bail if called from the API
		if (!$objId) return false;
		
		$sql = "SELECT DISTINCT workflow_id FROM docmgr.dm_workflow_object WHERE object_id='".$objId."'";
		$list = $this->DB->fetch($sql);
		
		for ($i=0;$i<$list["count"];$i++)
		{

			//delete the tasks
			$sql = "DELETE FROM docmgr.dm_workflow_route WHERE workflow_id='".$list[$i]["workflow_id"]."';";
			$sql .= "DELETE FROM docmgr.dm_workflow_object WHERE workflow_id='".$list[$i]["workflow_id"]."';";
			$sql .= "DELETE FROM docmgr.dm_workflow WHERE id='".$list[$i]["workflow_id"]."';";
		
			$this->DB->query($sql);

		}
	
	}

	public function delete($workflowId = null) 
	{

		//permissions checking
		if (!$this->checkPermissions("edit"))
		{
			$this->throwError(_I18N_WORKFLOW_EDIT_ERROR);
			return false;
		}

		//if not passed internally
		if (!$workflowId) $workflowId = $this->workflowId;

		$this->DB->begin();

		//convert to an array so we can handle multiples
		if (is_array($workflowId)) $workflows = $workflowId;
		else $workflows = array($workflowId);

		foreach ($workflows AS $workId)
		{
		
			$this->workflowId = $workId;

			//clear the shared folders
			$this->cleanupPermissions();
	
			$sql = "SELECT object_id FROM docmgr.dm_workflow_object WHERE workflow_id='".$this->workflowId."'";
			$list = $this->DB->fetch($sql);
	        
			//log the clear for all affected objects    
			for ($i=0;$i<$list["count"];$i++) logEvent(OBJ_WORKFLOW_CLEAR,$list[$i]["object_id"]);
	                		
			//delete the tasks
			$sql = "DELETE FROM docmgr.dm_workflow_route WHERE workflow_id='".$this->workflowId."';";
			$sql .= "DELETE FROM docmgr.dm_workflow_object WHERE workflow_id='".$this->workflowId."';";
			$sql .= "DELETE FROM docmgr.dm_workflow WHERE id='".$this->workflowId."';";
		
			$this->DB->query($sql);
		
		}


		$this->DB->end();
		
		$err = $this->DB->error();
		if ($err) $this->throwError($err);
	
	}


	protected function cleanupPermissions()
	{

		//delete related file links
		$sql = "DELETE FROM docmgr.dm_object_perm WHERE workflow_id='".$this->workflowId."';";
		$this->DB->query($sql);

	}


  /***********************************************************************
    FUNCTION:	setupPermissions
    PURPOSE:	setupPermissions the share settings for the current user, object,
              and the passed share accounts.  note, this stores a separate
							permission for the object for the passed user from what
							they may already have.  So, if the user has "view" and
							we give them "edit" here, they will have edit permisssions
							so long as the share is active.  The objperm::getuser function
							merges all set permissions so the highest given is available.
							Once the share is deleted, they will drop back to "view"
  ***********************************************************************/
  protected function setupPermissions($levelName,$aid)
  {

		//edit level for user
    if ($levelName=="edit" || $levelName=="approve") $level = "edit";
    else if ($levelName=="comment" || $levelName=="view") $level = "view";
    
		//begin our transaction
    $this->DB->begin();
  
    //base permissions
    $cb = "00000000";

		//delete all permissions set for this account through the sharing utility
    $sql = "DELETE FROM docmgr.dm_object_perm WHERE account_id='".$aid."' AND workflow_id='".$this->workflowId."'";
    $this->DB->query($sql);

    if ($level=="edit") 
    {
    	//set edit mode
    	$cb = PERM::bit_set($cb,OBJ_EDIT);
    }
    else if ($level=="view") 
    {
    	//view only
    	$cb = PERM::bit_set($cb,OBJ_VIEW);
    }
    else
    {
    	//something wacky was passed
      $this->throwError(_I18N_SHARE_INVALID_ERROR);
      break;
    }

    $sql = "SELECT object_id FROM docmgr.dm_workflow_object WHERE workflow_id='".$this->workflowId."'";
    $objarr = $this->DB->fetch($sql,1);
   
   	//perform for every object we are sharing 
    for ($i=0;$i<$objarr["count"];$i++)
    {
  
    	$obj = $objarr["object_id"][$i];
    
	    //add permissions for object
	    $opt = null;
	    $opt["object_id"] = $obj;
	    $opt["type"] = "account";
	    $opt["id"] = $aid;
	    $opt["bitmask"] = $cb;
	    $opt["workflow_id"] = $this->workflowId;

			//and set the permissions for the share user on the object, also reset perms on sub-objects if a collection
	    DOCMGR_UTIL_OBJPERM::save($obj,$opt);
			DOCMGR_UTIL_OBJPERM::resetChildren($obj);
			
		}
		
		//end transaction
		$this->DB->end();

    $err = $this->DB->error();
    
    if ($err) $this->throwError($err);  
  
  }  

}

