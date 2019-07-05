<?php

/**
	manages and fetches subscription information for the current object and account
	*/
	
class NOTIFICATION_MANAGE extends NOTIFICATION
{

	/**
		returns all notifications created for the current user
		*/
	public function search() 
	{

		$sql = "SELECT * FROM notification.view_notifications WHERE account_id='".USER_ID."' ORDER BY date_created DESC";
		$list = $this->DB->fetch($sql);

		//let's make some stuff pretty
		for ($i=0;$i<$list["count"];$i++)
		{
			$def = "_I18N_".$list[$i]["define_name"];
			
			$list[$i]["date_created_view"] = dateView($list[$i]["date_created"]);			

			//look for a language define first before falling back on the record name
			if (defined($def)) $list[$i]["i18n_name"] = constant($def);
			else $list[$i]["i18n_name"] = $list[$i]["name"];

			$this->PROTO->add("record",$list[$i]);

		}

		//look for db errors
		if ($this->DB->error()) $this->throwError($this->DB->error());
		
	}

	/**
		gets all info for the pass notification id
		*/
	public function get()
	{

		$sql = "SELECT * FROM notification.view_notifications WHERE id='".$this->apidata["notification_id"]."'";
		$info = $this->DB->single($sql);
		
		if ($info["account_id"]!=USER_ID)
		{
			$this->throwError(_I18N_PERMISSION_DENIED);
			return false;
		}
		
		//look for db errors
		if ($this->DB->error()) $this->throwError($this->DB->error());
	
	}

	/**
		removes the passed notification
		*/
	public function delete()
	{

		$this->DB->begin();
			
		$sql = "SELECT account_id FROM notification.notifications WHERE id='".$this->apidata["notification_id"]."'";
		$info = $this->DB->single($sql);
		
		if ($info["account_id"]!=USER_ID)
		{
			$this->throwError(_I18N_PERMISSION_DENIED);
			return false;
		}
		
		$sql = "DELETE FROM notification.notifications WHERE id='".$this->apidata["notification_id"]."'";
		$this->DB->query($sql);
	
		$this->DB->end();
		
		//look for db errors
		if ($this->DB->error()) $this->throwError($this->DB->error());

	}

	/**
		clears all notifications for the current user
		*/
	public function clear() 
	{

		$sql = "DELETE FROM notification.notifications WHERE account_id='".USER_ID."'";
		$this->DB->query($sql);

		//look for db errors
		if ($this->DB->error()) $this->throwError($this->DB->error());
		
	}


}


	
	