<?php

/**********************************************************************
	CLASS:		MAILBOX
	PURPOSE:	contains public functions for processing that aren't
						available to the outside world
**********************************************************************/
class EMAIL_SUGGEST extends EMAIL
{

	function search()
	{

		$abSearchString = $this->parseSearchString();
		$accountSearchString = $this->parseSearchString(1);
		$filter = $this->apidata["filter"];
		
		if ($filter=="account")
		{

		  $sql = "SELECT id,first_name,last_name,email FROM auth.accounts WHERE email IS NOT NULL'";

			if ($accountSearchString) $sql .= " AND ".$accountSearchString;

	    $sql .= " ORDER BY first_name,last_name LIMIT 20";

		}
		else if ($filter=="both")
		{

		  $sql = "SELECT id,first_name,last_name,email FROM auth.accounts WHERE email IS NOT NULL";

			if ($accountSearchString) $sql .= " ".$accountSearchString;

			$sql .= " UNION ";

		  $sql .= "SELECT id,first_name,last_name,email FROM addressbook.contact
		              LEFT JOIN addressbook.contact_account ON contact.id = contact_account.contact_id
		              WHERE account_id='".USER_ID."' AND email IS NOT NULL";

      if ($abSearchString) $sql .= $abSearchString;

	    $sql .= " ORDER BY first_name,last_name LIMIT 20";

		}
		else
		{
		
		  $sql = "SELECT id,first_name,last_name,email FROM addressbook.contact
		              LEFT JOIN addressbook.contact_account ON contact.id = contact_account.contact_id
		              WHERE account_id='".USER_ID."' AND email IS NOT NULL";

      if ($abSearchString) $sql .= $abSearchString;

	    $sql .= " ORDER BY first_name,last_name LIMIT 20";
		
		}

		$results = $this->DB->fetch($sql);
		
		if ($results["count"] > 0)
		{
			unset($results["count"]);
			$this->PROTO->add("record",$results);
		}
	
	}


	private function parseSearchString($account=null)
	{
	
		$firstName = null;
		$lastName = null;

		if ($this->apidata["search_string"])
		{
			$arr = organizeName($this->apidata["search_string"]);
			$firstName = $arr["fn"];
			$lastName = $arr["ln"];
		}
		else if ($this->apidata["first_name"])
		{
			$firstName = $this->apidata["first_name"];
		}
		else if ($this->apidata["last_name"])
		{
			$lastName = $this->apidata["last_name"];
		}

	  $firstName = strtolower($firstName);
	  $lastName = strtolower($lastName);

	  //merge in first and last name
	  if ($firstName || $lastName) 
	  {
	 
	 		$parms = array();
	 		 
	    if ($firstName && $lastName) 
	    {
	   		$parms[] = "lower(first_name) LIKE '".$firstName."%' AND lower(last_name) LIKE '".$lastName."%'";
			}
			else if ($lastName)
			{
	   		$parms[] = "lower(first_name) LIKE '".$lastName."%'";
				$parms[] = "lower(last_name) LIKE '".$lastName."%'";
				$parms[] = "lower(email) LIKE '".$lastName."%'";

				//search login, too, if we are looking in the account table
				if ($account) $parms[] = "lower(login) LIKE '".$lastName."%'";
			}	   		 
	
			$sql = " AND (".implode(" OR ",$parms).")";

			return $sql;

	  }
		else
		{
			return null;
		}
			
	}
		
}