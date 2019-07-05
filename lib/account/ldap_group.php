<?php
/************************************************************************************************************
	ldap_group.inc.php
	
	Holds group processing and search functions for
	an ldap database
	
	02-07-2005 - Split from ldap.inc.php

***********************************************************************************************************/

class LDAP_GROUP 
{

	protected $groupId;
	protected $conn;
	protected $errorMessage;
	protected $DB;
 
	function __construct($gid=null) {

		if ($gid) $this->groupId = $gid;
		$this->DB = $GLOBALS["DB"];
 
	}

	/***************************************************
		connect to the database
	***************************************************/
	function connect() {
  
		if ($this->conn) return true;
  
		$this->conn = ldap_connect(LDAP_SERVER,LDAP_PORT);
		ldap_set_option($this->conn, LDAP_OPT_PROTOCOL_VERSION, LDAP_PROTOCOL);
		$r = ldap_bind($this->conn,BIND_DN,BIND_PASSWORD);
  
	}

	function close() {
  
		ldap_close($this->conn);
		$this->conn = null;
  
	}

	function search($filter=null) {

		$this->connect();

		if ($filter) $filter = "(&".$filter."(gidNumber=*))";
		else $filter = "(gidNumber=*)";

		$sr = ldap_search($this->conn,GROUP_BASE,$filter);
		$res = ldap_get_entries($this->conn,$sr);

		return $this->reformat($res);

	}

	
	function setGroupId($name) 
	{

		$res = $this->search("(cn=".$name.")");
		
		if (count($res)==0) return false;
		else 
		{
			$this->groupId = $res[0]["id"];
			return $this->groupId;
		}	
	
	}

	function throwError($msg) {
		$this->errorMessage = $msg;
	}

	function getError() {
		return $this->errorMessage;
	}

	function get($gid=null) {
	
		if (!$gid) $gid = $this->groupId;

		$res = $this->search("(gidNumber=".$gid.")");
	
		return $res[0];

	}
	
	function reformat($res) {

		$arr = array();
		$c = 0;
		
		foreach ($res AS $info) 
		{

			if (!is_array($info)) continue;

			$arr[$c] = array();					
			$arr[$c]["id"] = $info["gidnumber"][0];
			$arr[$c]["name"] = $info["cn"][0];
			$arr[$c]["samba_group_name"] = $info["displayname"][0];
			$arr[$c]["samba_sid"] = $info["sambasid"][0];
		
			//populate our member list	
			$num = count($info["memberuid"]);
			$arr[$c]["member_uid"] = array();
			
			for ($i=0;$i<$num;$i++) 
			{
				if (trim($info["memberuid"][$i])) $arr[$c]["member_uid"][]  = $info["memberuid"][$i];
			}
				
			$c++;
	
		}

		return $arr;
		
	}
                                                                                                     
	private function nextGroupNumber() 
	{

		$res = $this->search();
		$res = transposeArray($res);
		
		$num = max($res["id"]) + 1;
		
		return $num;
		
	}

	private function checkGroup($name) 
	{
	
		$res = $this->search("(cn=".$name.")");
		if ($res["count"]>0) return false;
		else return true;
		
	}

	function save($option)
	{
		if ($this->groupId) return $this->update($option);
		else return $this->add($option);
	
	}

	function add($option) 
	{

		$this->connect();
		
		if (!$option["name"]) 
		{
			$this->throwError("No group name was specified");
			return false;
		}

		if (!$this->checkGroup($option["name"])) 
		{
			$this->throwError("A group with this name already exists");
			return false;
		}

		//get the next id available 		
		$this->groupId = $this->nextGroupNumber();

		// prepare our objectclass and common data
		$info = array();
		$info["objectclass"][0]="top";
		$info["objectclass"][1]="posixGroup";
		$info["objectclass"][2] = "sambaGroupMapping";
		$info["sambaGroupType"] = "2";
		$info["displayName"] = $option["samba_group_name"];
		$info["sambaSID"] = $option["samba_sid"];
		$info["gidNumber"] = $this->groupId;
		$info["cn"] = $option["name"];

		//set our cn
		$cn = "cn=".$option["name"].",".GROUP_BASE;

		//add the data
		if (ldap_add($this->conn, $cn, $info)) 
		{
			return $this->groupId;
		} 
		else 
		{
			$this->throwError("Group unable to be added");
		}
		
	}

	//for now, this function does not support renaming
	function update($option) 
	{
	
		$this->connect();
	
		if (!$this->groupId) 
		{
			$this->throwError("You must have a group id to modify settings");
			return false;
		}

		$info = array();
		$info["objectclass"][0]="top";
		$info["objectclass"][1]="posixGroup";
		$info["objectclass"][2] = "sambaGroupMapping";
		$info["sambaGroupType"] = "2";
		$info["displayName"] = $option["samba_group_name"];
		$info["sambaSID"] = $option["samba_sid"];
		$info["cn"] = $option["name"];
	
		$cn = "cn=".$option["name"].",".GROUP_BASE;

	
		//add the data
		if (!ldap_modify($this->conn, $cn, $info)) 
		{
			$this->throwError("Error updating group information");
		}
		else
		{
			return $this->groupId;		
		}		

	}
	
	
	//for now, this function does not support renaming
	function delete() 
	{

		$this->connect();
	
		if (!$this->groupId) 
		{
			$this->throwError("You must have a group id to modify settings");
			return false;
		}

		$info = $this->get();
	
		//set our cn
		$cn = "cn=".$info["name"].",".GROUP_BASE;
	
		//add the data
		if (!ldap_delete($this->conn, "$cn")) $this->throwError("Unable to remove group");
	
	}

	function addMember($newMember)
	{

		$this->connect();

		if (!$this->groupId) 
		{
			$this->throwError("You must have a group id to modify settings");
			return false;
		}

		$info = $this->get();
		$info["member_uid"][] = $newMember;

		$arr = array();
		$arr["memberUid"] = array_values(array_unique($info["member_uid"]));

		
		//set our cn
		$dn = "cn=".$info["name"].",".GROUP_BASE;
		
		//add the data
		if (!ldap_modify($this->conn, $dn, $arr)) $this->throwError("Error updating group members");

	
	}
	
	function removeMember($removeMember)
	{

		$this->connect();

		if (!$this->groupId) 
		{
			$this->throwError("You must have a group id to modify settings");
			return false;
		}

		$info = $this->get();
		$curmem = $info["member_uid"];

		$members = array_values(array_diff($curmem,array($removeMember)));

		$arr = array();
		$arr["memberUid"] = $members;
	
		//set our cn
		$dn = "cn=".$info["name"].",".GROUP_BASE;

		//add the data
		if (!ldap_modify($this->conn, "$dn", $arr)) $this->throwError("Error updating group members");
	
	}

}
