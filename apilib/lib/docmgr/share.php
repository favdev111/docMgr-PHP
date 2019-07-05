<?php

/**************************************************************************
	CLASS:	share
	PURPOSE:	handle specific processing for user file sharing
**************************************************************************/
class DOCMGR_SHARE extends DOCMGR 
{

  /***********************************************************************
    FUNCTION:	getlist
    PURPOSE:	returns all share settings for the current user and
              current object
  ***********************************************************************/
  function search()
  {

    if (!is_array($this->objectId)) $this->objectId = array($this->objectId);
  
    $sql = "SELECT * FROM docmgr.dm_share WHERE object_id IN (".implode(",",$this->objectId).") AND account_id='".USER_ID."'";
    $list = $this->DB->fetch($sql);
    
    $a = new ACCOUNT();
    
    for ($i=0;$i<$list["count"];$i++)
    {
    
      //convert the shared account name
      $info = $a->cachedGet($list[$i]["share_account_id"]);
      $list[$i]["share_account_name"] = $info["full_name"];

      //current perms of object based on hiearchy 
      if (PERM::is_set($list[$i]["bitmask"],OBJ_EDIT))	$list[$i]["bitmask_text"] = "edit";
      elseif (PERM::is_set($list[$i]["bitmask"],OBJ_VIEW))	$list[$i]["bitmask_text"] = "view";

    }

		if ($list["count"] > 0)
		{ 	   
    	unset($list["count"]);
    	$list = arrayMSort($list,"share_account_name");
    	$this->PROTO->add("record",$list);
		}
		
  }

  /***********************************************************************
    FUNCTION:	getaccounts
    PURPOSE:	returns a list of all accounts we can share with
  ***********************************************************************/
  function getaccounts()
  {
  
    $a = new ACCOUNT();
    $filter = null;
    
    if ($this->apidata["search_string"])
    {
      $filter["login"] = $this->apidata["search_string"];
      $filter["name"] = $this->apidata["search_string"];
    }
    
    $results = $a->search($filter);

    for ($i=0;$i<$results["count"];$i++)
    {

    	//skip ourselves
    	if ($results[$i]["id"]==USER_ID) continue;
    
      $arr = array();
      $arr["id"] = $results[$i]["id"];
      $arr["name"] = $results[$i]["full_name"];
      $arr["login"] = $results[$i]["login"];
      
      $this->PROTO->add("account",$arr);

    }
  
  
  }

  /***********************************************************************
    FUNCTION:	save
    PURPOSE:	saves the share settings for the current user, object,
              and the passed share accounts.  note, this stores a separate
							permission for the object for the passed user from what
							they may already have.  So, if the user has "view" and
							we give them "edit" here, they will have edit permisssions
							so long as the share is active.  The objperm::getuser function
							merges all set permissions so the highest given is available.
							Once the share is deleted, they will drop back to "view"
  ***********************************************************************/
  function save()
  {
  
    if (!is_array($this->objectId)) $this->objectId = array($this->objectId);

		//begin our transaction
    $this->DB->begin();
  
    foreach ($this->objectId AS $obj)
    {

      //base permissions
      $cb = "00000000";

      //delete the current row
      $sql = "DELETE FROM docmgr.dm_share WHERE object_id='".$obj."' AND
                                            account_id='".USER_ID."' AND
                                            share_account_id='".$this->apidata["share_account_id"]."';";

			//we also need to delete shared settings on the children.  THINK OF THE CHILDREN!

			//get child objects of this object so we can clear their permissions
			$d = new DOCMGR_OBJECT();
      $arr = $d->getChildObjects($obj);    
      $arr[] = $obj;
 
			//delete all permissions set for this account through the sharing utility
      $sql .= "DELETE FROM docmgr.dm_object_perm WHERE object_id IN (".implode(",",$arr).") AND 
      																			account_id='".$this->apidata["share_account_id"]."' AND
      																			share='t';";

			//run the query
      $this->DB->query($sql);

			//handle passed permission setting.  "none" usually means we are deleting the share
      if ($this->apidata["share_level"]=="none") 
      {
      	continue;
      }
      else if ($this->apidata["share_level"]=="edit") 
      {
      	//set edit mode
      	$cb = PERM::bit_set($cb,OBJ_EDIT);
      }
      else if ($this->apidata["share_level"]=="view") 
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

      //build the share query
      $opt = null;
      $opt["object_id"] = $obj;
      $opt["account_id"] = USER_ID;
      $opt["share_account_id"] = $this->apidata["share_account_id"];
      $opt["bitmask"] = $cb;

			//run it      
      $this->DB->insert("docmgr.dm_share",$opt);    

      //add the permission
			$opt = null;
			$opt["object_id"] = $obj;
			$opt["type"] = "account";
			$opt["id"] = $this->apidata["share_account_id"];
			$opt["bitmask"] = $cb;
			$opt["share"] = "t";

			//and set the permissions for the share user on the object, also reset perms on sub-objects if a collection
      DOCMGR_UTIL_OBJPERM::save($obj,$opt);

      //make sure we have a saved search link for this
      $this->storeSavedSearch($this->apidata["share_account_id"]);
			
			//show an alert that the object was shared w/ that user
			$n = new NOTIFICATION_DOCMGR();
			$n->send($obj,"OBJ_SHARE_NOTIFICATION");     
			                               
		}  

		//end transaction
		$this->DB->end();
  
    $err = $this->DB->error();
    
    if ($err) $this->throwError($err);  
  
  }  

  protected function storeSavedSearch($accountId)
  {	

    $accounts = array(USER_ID,$accountId);

    foreach ($accounts AS $account)
    {

	  	//see if there is a saved search for subscriptions
	  	$sql = "SELECT id FROM docmgr.saved_searches WHERE account_id='".$account."' AND name='Shared With Me'";
	  	$info = $this->DB->single($sql);
	
	  	//create if there isn't
	  	if (!$info)
	  	{
	
	  		$opt = null;
	  		$opt["name"] = "Shared With Me";
	  		$opt["params"] = "{\"command\":\"docmgr_query_search\",\"account_shared_with\":\"t\",\"sort_field\":\"name\"}";
	
	  		$d = new DOCMGR_SEARCH($opt);
	  		$d->save($account);
	
	  		if ($d->getError()) $this->throwError($d->getError());
	  
			}
	
	  	//see if there is a saved search for subscriptions
	  	$sql = "SELECT id FROM docmgr.saved_searches WHERE account_id='".$account."' AND name='Shared By Me'";
	  	$info = $this->DB->single($sql);
	
	  	//create if there isn't
	  	if (!$info)
	  	{
	
	  		$opt = null;
	  		$opt["name"] = "Shared By Me";
	  		$opt["params"] = "{\"command\":\"docmgr_query_search\",\"account_shared_by\":\"t\",\"sort_field\":\"name\"}";
	
	  		$d = new DOCMGR_SEARCH($opt);
	  		$d->save($account);
	
	  		if ($d->getError()) $this->throwError($d->getError());
	  
			}
    
    }


	}

	public function delete($obj=null,$aid=null)
	{

		if (!$obj) $obj = $this->objectId;
		if (!$aid) $aid = $this->apidata["share_account_id"];
		
		$ret = false;
		
		//see if the user is being shared this object
		$sql = "SELECT object_id,account_id,share_account_id FROM docmgr.dm_share WHERE object_id='$obj' AND share_account_id='".$aid."'";
		$info = $this->DB->single($sql);	

		if ($info)
		{

			//delete linked share info		
			$sql = "DELETE FROM docmgr.dm_object_perm WHERE object_id='$obj' AND account_id='".$info["share_account_id"]."' AND share='t';";
			$sql .= "DELETE FROM docmgr.dm_object_parent WHERE object_id='$obj' AND account_id='".$info["share_account_id"]."' AND share='t';";
			$sql .= "DELETE FROM docmgr.dm_share WHERE object_id='$obj' AND 
																									account_id='".$info["account_id"]."' AND
																									share_account_id='".$info["share_account_id"]."';";
			$this->DB->query($sql);

			$ret = true;	

		}
		
		return $ret;

	}

//end class
}


