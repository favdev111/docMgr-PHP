<?php

class DOCMGR_SEARCH extends DOCMGR_AOBJECT
{

	/********************************************************************
		FUNCTION:	save
		PURPOSE:	retrieves document from the system
	********************************************************************/
	public function save($aid = null)
	{

		//if called directly, allow them to pass one
		if (!$aid) $aid = USER_ID;

		$opt = null;
		$opt["name"] = $this->apidata["name"];

		if ($this->apidata["search_id"])
		{
			$opt["where"] = "id='".$this->apidata["search_id"]."'";
			$this->DB->update("docmgr.saved_searches",$opt);		
		}
		else
		{
			$opt["account_id"] = $aid;
			$opt["params"] = $this->apidata["params"];
			$this->DB->insert("docmgr.saved_searches",$opt);
		}

		//toss and error if we have one
		if ($this->DB->error()) $this->throwError($this->DB->getError());

	}

	/********************************************************************
		FUNCTION:	get
		PURPOSE:	retrieves document from the system
	********************************************************************/
	function get() 
	{

		$sql = "SELECT * FROM docmgr.saved_searches WHERE id='".$this->apidata["search_id"]."'";
		$info = $this->DB->single($sql);

		if ($info["account_id"]!=USER_ID)
		{
			$this->throwError(_I18N_PERMISSION_DENIED);
		}
		else
		{
			$this->PROTO->add("record",$info);
		}
		
	}

	/********************************************************************
		FUNCTION:	run
		PURPOSE:	retrieves document from the system
	********************************************************************/
	function run() 
	{

		$sql = "SELECT account_id,params FROM docmgr.saved_searches WHERE id='".$this->apidata["search_id"]."'";
		$info = $this->DB->single($sql);

		if ($info["account_id"]!=USER_ID)
		{
			$this->throwError(_I18N_PERMISSION_DENIED);
		}
		else
		{
		
			//decode the command string to an array
			$apidata = $this->PROTO->decode($info["params"]); 

			//init the api and run the search
			$s = new DOCMGR_QUERY($apidata);
			$s->search();

			$this->PROTO->add("params",$info["params"]);
			
		}
		
	}

	/********************************************************************
		FUNCTION: search
		PURPOSE:	retrieves document from the system
	********************************************************************/
	function search() 
	{

		$sql = "SELECT id,name FROM docmgr.saved_searches WHERE account_id='".USER_ID."' ORDER BY name";
		$results = $this->DB->fetch($sql);

		if ($results["count"] > 0)
		{
			unset($results["count"]);
			$this->PROTO->add("record",$results);
		}
		
	}

	/********************************************************************
		FUNCTION:	delete
		PURPOSE:	retrieves document from the system
	********************************************************************/
	function delete() 
	{

		$sql = "SELECT * FROM docmgr.saved_searches WHERE id='".$this->apidata["search_id"]."'";
		$info = $this->DB->single($sql);

		if ($info["account_id"]!=USER_ID)
		{
			$this->throwError(_I18N_PERMISSION_DENIED);
		}
		else
		{
			$sql = "DELETE FROM docmgr.saved_searches WHERE id='".$this->apidata["search_id"]."'";
			$this->DB->query($sql);
		}
		
	}

}

