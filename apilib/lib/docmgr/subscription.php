<?php

/**
	manages and fetches subscription information for the current object and account
	*/
	
class DOCMGR_SUBSCRIPTION extends DOCMGR
{

	/**
		returns all subscriptions for the current user and object
		*/
	public function get() 
	{

		$sql = "SELECT * FROM docmgr.subscriptions WHERE account_id='".USER_ID."' AND object_id='".$this->objectId."';";
		$info = $this->DB->single($sql);
		
		if ($info) $this->PROTO->add("record",$info);

		//look for db errors	
		$err = $this->DB->error();
		if ($err) $this->throwError($err);
		
	}

	/**
		stores the current subscription information
		*/
	public function save()
	{

		//see if we have a record to update
		$sql = "SELECT object_id FROM docmgr.subscriptions WHERE account_id='".USER_ID."' AND object_id='".$this->objectId."';";
		$info = $this->DB->single($sql);

		if ($info) $update = true;
		else $update = false;

		//the fields we will update		
		$fields = array("locked","unlocked","created","removed","comment_posted","notify_email","notify_send_file");

		$opt = null;
		
		//only update data that was passed
		foreach ($fields AS $field)
		{
			if (isset($this->apidata[$field])) $opt[$field] = $this->apidata[$field];
		}		

		//update our record if we already have one		
		if ($update==true)
		{
			//where clause
			$opt["where"] = "account_id='".USER_ID."' AND object_id='".$this->objectId."'";

			//run the udpate
			$this->DB->update("docmgr.subscriptions",$opt);

		}
		else
		{
			//required fields
			$opt["account_id"] = USER_ID;
			$opt["object_id"] = $this->objectId;

			//run the insert
			$this->DB->insert("docmgr.subscriptions",$opt);

		}

		//update a link for the object to the Subscribed folder
		$this->storeSavedSearch();
		
		//look for db errors
		$err = $this->DB->error();
		if ($err) $this->throwError($err);

	}

	/**
		returns all subscriptions this user owns
		*/
	public function search() 
	{

		$sql = "SELECT subscriptions.*,name,object_type FROM docmgr.subscriptions
						LEFT JOIN docmgr.dm_object ON subscriptions.object_id = dm_object.id
						WHERE subscriptions.account_id='".USER_ID."'";
		$list = $this->DB->fetch($sql);

		if ($list["count"] > 0)
		{
			unset($list["count"]);
			$this->PROTO->add("record",$list);
		}

		//look for db errors	
		$err = $this->DB->error();
		if ($err) $this->throwError($err);
		
	}

	protected function storeSavedSearch()
	{

		//see if there is a saved search for subscriptions
		$sql = "SELECT id FROM docmgr.saved_searches WHERE account_id='".USER_ID."' AND name='Subscribed'";
		$info = $this->DB->single($sql);
		
		//create if there isn't
		if (!$info)
		{
			$opt = null;
			$opt["name"] = "Subscribed";
			$opt["params"] = "{\"command\":\"docmgr_query_search\",\"account_subscribed\":\"t\",\"sort_field\":\"name\"}";

			$d = new DOCMGR_SEARCH($opt);
			$d->save();		

			if ($d->getError()) $this->throwError($d->getError());		

		}

	}

}


	
	