<?php

/****************************************************************************
	CLASS:	LOCK
	PURPOSE:	master function for managing docmgr objects.  this includes
				    creating, saving, update, moving, or deleting
****************************************************************************/

class DOCMGR_UTIL_LOCK
{

	private $objectId;
	private $errorMessage;
	private $DB;
		
	function __construct($objId=null)
	{

		if ($objId) $this->objectId = $objId;
		else $this->objectId = "0";

		$this->DB = $GLOBALS["DB"];
			
	}

	public function throwError($err)
	{
		$this->errorMessage = $err;
	}
	
	public function error()
	{
		return $this->errorMessage;
	}

	/************************************************************
		FUNCTION:	get
		PURPOSE:	pulls all current lock info for a file
		INPUTS:		objectId -> id object to get locks for
							childLocks -> fetch locks for all children
														of object too
		RETURNS:	array -> array of db lock info
		OUTPUTS:	array["lock"] -> array of db lock info
	************************************************************/
	public function get($objId=null,$childLocks=null,$showSelf=null)
	{

		if ($objId) $objArr = $objId;
		else $objArr = $this->objectId;

		//bail if no object set or passed
		if (!$objArr) return array();

		//convert to array if necessary		
		if (!is_array($objArr)) $objArr = array($objArr);

		//clear any old locks
		$sql = "DELETE FROM docmgr.dm_locks WHERE (created + timeout <= '".time()."')";
		$this->DB->query($sql);

		//if child locks is set, get all children of this object
    if ($childLocks)
    {  

    	$children = array();
    	
    	//loop through each passed and get its children
    	foreach ($objArr AS $obj)
    	{

	    	$d = new DOCMGR_OBJECT();
	    	$arr = $d->getChildObjects($obj);    

	    	if (count($arr) > 0) $children = array_merge($children,$arr);

			}

			//merge and make unique
			$objArr = array_merge($objArr,$children);
			$objArr = array_values(array_unique($objArr));
			
    }
           
		//get locks (this should be any left over)
		$sql = "SELECT * FROM docmgr.dm_locks WHERE object_id IN (".implode(",",$objArr).")";

		//if not set, skip locks the current user owns
		if (!$showSelf) $sql .= " AND account_id!='".USER_ID."'";

		$list = $this->DB->fetch($sql);					

		return $list;

	}

	/************************************************************
		FUNCTION:	set
		PURPOSE:	locks an object so it can only be edited
							by the locking user
		INPUTS:		apidata or data
								timeout -> lock timeout
								scope -> 1 or 2 (shared or exclusive)
								depth -> -1 or 0 (indirect or direct)
								uri -> path of file to lock
								owner -> owner of lock creation
								token -> lock token
		RETURNS:	none
		OUTPUTS:	none
	************************************************************/
	public function set($data=null)
	{

		if (!$data) $data = array();

		//must have at least edit permissions
		if ($this->isLocked($data["token"]))
		{
			$this->throwError(_I18N_OBJECT_LOCKED_ERROR);
			return false;
		}

		//make sure we aren't trying to do an exclusive lock on a shared lock
		$locks = $this->get($this->objectId);
		if ($locks["count"] > 0 && $locks[0]["scope"]=="1" && $data["scope"]=="2")
		{
			$this->throwError(_I18N_OBJECT_LOCK_SHARED_ERROR);
			return false;
		}

		//create a client token if not passed one
		if (!$data["token"]) $data["token"] = $this->createToken();

		$now = time();
		
		//if timeout is set to infinity, make it last until 2031.  this is
		//for indefinite checkouts
		if ($data["timeout"]=="-1") $data["timeout"] = strtotime("2031-12-31") - $now; 

		//set some defaults
		if (!$data["timeout"]) 	$data["timeout"] = "1800";				//default to 30 min timeout
		if (!$data["scope"]) 		$data["scope"] = "2";							//default to exclusive lock
		if (!$data["depth"]) 		$data["depth"] = "0";							//default to direct lock
		if (!$data["uri"]) 			$data["uri"] = $this->path;				//default to current object's path
		if (!$data["owner"]) 		$data["owner"] = USER_LOGIN;			//default to current user login

		$sql = "SELECT object_id FROM docmgr.dm_locks WHERE object_id='".$this->objectId."' AND token='".$data["token"]."'";
		$info = $this->DB->single($sql);

		//setup the query
		$opt = null;
		$opt["owner"] = $data["owner"];
		$opt["timeout"] = $data["timeout"];
		$opt["scope"] = $data["scope"];
		$opt["depth"] = $data["depth"];
		$opt["uri"] = $data["uri"];
		$opt["created"] = $now;

		//update the lock				
		if ($info)
		{
			$opt["where"] = "object_id='".$this->objectId."' AND token='".$data["token"]."'";
			$this->DB->update("docmgr.dm_locks",$opt);
		}
		else
		{
			$opt["account_id"] = USER_ID;
			$opt["account_name"] = USER_FN." ".USER_LN;
			$opt["object_id"] = $this->objectId;
			$opt["token"] = $data["token"];
			$this->DB->insert("docmgr.dm_locks",$opt);
		}

		if ($this->DB->error()) $this->throwError($this->DB->error());
		else
		{

			//log the checkout
			logEvent(OBJ_LOCKED,$this->objectId);
			                  
			//send a subscription alert
			$n = new NOTIFICATION_DOCMGR();
      $n->send($this->objectId,"OBJ_LOCK_NOTIFICATION");     
		
		}
		
	}


	/************************************************************
		FUNCTION:	clear
		PURPOSE:	unlocks an object so it can be edited by anyone
		INPUTS:		apidata or data
								token -> lock token
		RETURNS:	none
		OUTPUTS:	none
	************************************************************/
	function clear($token=null)
	{

		if (!$token) $token = $this->getToken();

		if (!$token)
		{
			$this->throwError(_I18N_USER_TOKEN_ERROR);
			return false;
		}

		$sql = "DELETE FROM docmgr.dm_locks WHERE object_id='".$this->objectId."' AND token='".$token."';";
		$sql .= "DELETE FROM docmgr.dm_locktoken WHERE object_id='".$this->objectId."' AND token='".$token."';";
		$this->DB->query($sql);

		if ($this->DB->error()) $this->throwError($this->DB->error());
		else
		{

			//log the checkout
			logEvent(OBJ_UNLOCKED,$this->objectId);

			$n = new NOTIFICATION_DOCMGR();
      $n->send($this->objectId,"OBJ_UNLOCK_NOTIFICATION");     
		
		}	

	}

	public function validate($token=null)
	{

		//no token.  query the token table to see if this user has a locktoken for this object
		if (!$token) $token = $this->getToken();

		if ($this->isLocked($token))
		{
			//throw an error
			$this->throwError(_I18N_OBJECT_LOCKED_ERROR,OBJ_LOCKED);		
		
		}		
	
	}
	

	public function isLocked($token=null)
	{
	
		//if we have a token, search for it.  otherwise search by account id	
		$locks = $this->get();

		$ret = true;

		//not locked
		if (!$locks || $locks["count"]=="0") $ret = false;

		//if it's a shared lock, continue
		else if ($locks[0]["scope"]==1) $ret = false;

		else
		{

			//no token.  query the token table to see if this user has a locktoken for this object
			if (!$token) $token = $this->getToken();

			if ($token)
			{
		
				for ($i=0;$i<$locks["count"];$i++)
				{

					//locks match up, continue
					if ($locks[$i]["token"]==$token) 
					{
						$ret = false;
						break;
					}
					
				}

			//fall back on seeing if the user owns the lock			
			}
			else
			{
			
				for ($i=0;$i<$locks["count"];$i++)
				{

					//locks match up, continue
					if ($locks[$i]["account_id"]==USER_ID)
					{
						$ret = false;
						break;
					}
			
				}
			
			}

		}

		return $ret;
			
	}

	private function createToken()
	{

		$token = uuid();

		$opt = null;
		$opt["account_id"] = USER_ID;
		$opt["object_id"] = $this->objectId;
		$opt["token"] = $token;
		$this->DB->insert("docmgr.dm_locktoken",$opt);
	
		return $token;
		
	}

	private function getToken()
	{

			//no token.  query the token table to see if this user has a locktoken for this object
			$sql = "SELECT token FROM docmgr.dm_locktoken WHERE object_id='".$this->objectId."' AND account_id='".USER_ID."'";
			$info = $this->DB->single($sql);

			return $info["token"];
		
	}

	/************************************************************
		FUNCTION: clearAll
		PURPOSE:	clears all locks on an object
		INPUTS:		none
		RETURNS:	none
		OUTPUTS:	none
	************************************************************/
	function clearAll()
	{

		$sql = "DELETE FROM docmgr.dm_locks WHERE object_id='".$this->objectId."';";
		$sql .= "DELETE FROM docmgr.dm_locktoken WHERE object_id='".$this->objectId."';";
		$info = $this->DB->single($sql);

		if ($this->DB->error()) 
			$this->throwError($this->DB->error());
		else 
			logEvent(OBJ_UNLOCKED,$this->objectId);

	}

	function addToObject(&$objarr)
	{

		global $DB;

		$idarr = array();
		
		for ($i=0;$i<$objarr["count"];$i++) $idarr[] = $objarr[$i]["id"];	

		$list = $this->get(implode(",",$idarr),null,true);

		unset($list["count"]);

		if (count($list) > 0)
		{

			unset($list["count"]);
			$list = transposeArray($list);

			for ($i=0;$i<$objarr["count"];$i++)
			{
			
				$keys = array_keys($list["object_id"],$objarr[$i]["id"]);
				
				if (count($keys) > 0)
				{
	
					$key = $keys[0];
								
					if ($list["scope"][$key]=="1") $scope = "Shared";
					else $scope = "Exclusive";

					$objarr[$i]["locked"] = "t";
					$objarr[$i]["lock_created"] = dateView(date("Y-m-d H:i:s",$list["created"][$key]));				
					$objarr[$i]["lock_expires"] = dateView(date("Y-m-d H:i:s",($list["created"][$key] + $list["timeout"][$key])));				
					$objarr[$i]["lock_scope"] = $list["scope"][$key];
					$objarr[$i]["lock_type"] = $scope;
					$objarr[$i]["lock_token"] = $list["token"][$key];

					//figure out which client locked it.  sabredav uses 44445502 as a lock prefix
					//so we always know when it's locked via webdav
					if (strstr($list["token"][$key],"44445502")) $client = "WebDAV";
					else $client = "Web";

					$objarr[$i]["lock_client"] = $client;

					//show all lock owners
					$objarr[$i]["lock_owner"] = $list["account_id"];
					$objarr[$i]["lock_owner_name"] = $list["account_name"];
	
				}
				else $objarr[$i]["locked"] = "f";
	
			}
		
		} 
		else
		{
		
			for ($i=0;$i<$objarr["count"];$i++) $objarr[$i]["locked"] = "f";
			
		}
		
	}

}
