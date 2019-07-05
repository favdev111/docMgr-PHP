<?php

/**********************************************************************
	CLASS:	LOG
	PURPOSE:	handles specific processing for document objects
**********************************************************************/
class DOCMGR_LOG extends DOCMGR 
{

  /*******************************************************************************
  	called from DOCMGR class
	*******************************************************************************/

  /********************************************************************
  	FUNCTION: get
  	PURPOSE:  retrieves document from the system
	********************************************************************/
	public function getlist() 
	{

		if (!$this->apidata["filter"] || $this->apidata["filter"]=="lastten") $limit = " limit 10 ";
		else $limit = null;
		
		$filter = $this->createFilter();
		
		$sql = "SELECT * FROM docmgr.dm_object_log WHERE object_id='".$this->objectId."' ".$filter." ORDER BY log_time DESC ".$limit."";
		$list = $this->DB->fetch($sql);

		//get our log list for later
		$loglist = returnLogList();

		for ($i=0;$i<$list["count"];$i++) 
		{

			$list[$i]["log_time_view"] = dateView($list[$i]["log_time"]);
			$list[$i]["log_type_view"] = returnLogType($loglist,$list[$i]["log_type"]);

			if (!$list[$i]["account_id"]) $list[$i]["account_name"] = "Unauthorized User";
			else $list[$i]["account_name"] = returnAccountName($list[$i]["account_id"]);
									
			$this->PROTO->add("log",$list[$i]);
		
		}

	}

	private function createFilter() 
	{
	
		$filter = null;
		
		if ($this->apidata["filter"]=="myentries") {
			$filter = "AND account_id='".USER_ID."'";
		} elseif ($this->apidata["filter"]=="virus") {
			$filter = "AND log_type IN ('OBJ_VIRUS_PASS','OBJ_VIRUS_FAIL','OBJ_VIRUS_ERROR')";
		} elseif ($this->apidata["filter"]=="email") {
			$filter = "AND log_type IN ('OBJ_ANON_EMAILED','OBJ_EMAILED')";
		} elseif ($this->apidata["filter"]=="view") {
			$filter = "AND log_type IN ('OBJ_VIEWED','OBJ_ANON_VIEWED')";
		} elseif ($this->apidata["filter"]=="checkin") {
			$filter = "AND log_type IN ('OBJ_LOCK','OBJ_UNLOCK')";
		} elseif ($this->apidata["filter"]=="all") {
			$filter = null;
		} else {
			$filter = null;	
		}

		return $filter;
      	
	}	

	public static function log($logType,$objectId,$data = null,$accountId = null)
	{

		global $DB;
			
		if ($accountId==null && defined(USER_ID)) $accountId = USER_ID;
		
		if (!$accountId) $accountId = 0;
	
		$opt = null;
		$opt["object_id"] = $objectId;
		$opt["log_type"] = $logType;
		$opt["account_id"] = $accountId;
		$opt["log_time"] = date("c"); 
		$opt["ip_address"] = $_SERVER["REMOTE_ADDR"];
	
		//optional data for the log
		if ($data) $opt["log_data"] = sanitize($data);
		$DB->insert("docmgr.dm_object_log",$opt);
	
	}
	
	
}
	
