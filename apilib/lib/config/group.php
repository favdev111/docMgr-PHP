<?php

/*********************************************



*********************************************/

class CONFIG_GROUP extends CONFIG
{

  private $groupId;
  
  function ___construct()
  {

    //if passed an group_id, make sure it's ours, or we have permissions to edit it  
    if ($this->apidata["group_id"]!=null) $this->groupId = $this->apidata["group_id"];
  
  }
  
  function search($internal=null)
  {

    $sql = "SELECT groups.*,(SELECT count(account_id) FROM auth.account_groups WHERE group_id=groups.id) AS member_count FROM auth.groups ";

    if ($this->apidata["search_string"]) $sql .= " WHERE name ILIKE '%".$this->apidata["search_string"]."%'";

    $sql .= " ORDER BY name";

    $results = $this->DB->fetch($sql);
    
    unset($results["count"]);
    
    //not called internally, output to proto
    if (!$internal) $this->PROTO->add("record",$results);
    
    return $results;
   
  }

  function get()
  {

    //sanity checking
    if ($this->groupId==null)
    {
      $this->throwError(_I18N_GROUP_SPECIFY_ERROR);
      return false;
    }

    if (!PERM::check(ADMIN))
    {
      $this->throwError(_I18N_PERMISSION_DENIED);
      return false;
    }

    $sql = "SELECT * FROM auth.groups WHERE id='".$this->groupId."'";
    $info = $this->DB->single($sql);
    
    if ($info) $this->PROTO->add("record",$info);

    if ($this->DB->error())
    {
      $this->throwError($this->DB->error());
    } 

  }

  function save()
  {

    if (!PERM::check(ADMIN))
    {
      $this->throwError(_I18N_PERMISSION_DENIED);
      return false;
    }

    $opt = null;
    $opt["name"] = $this->apidata["name"];
    
    if ($this->groupId)
    {
      $opt["where"] = "id='".$this->groupId."'";
      $this->DB->update("auth.groups",$opt);
    }
    else
    {
      $this->groupId = $this->DB->insert("auth.groups",$opt,"id");
    }

    if ($this->DB->error())
    {
      $this->throwError($this->DB->error());
    } 

		if ($this->groupId) $this->PROTO->add("group_id",$this->groupId);
		
  }

  function remove()
  {

    if (!PERM::check(ADMIN))
    {
      $this->throwError(_I18N_PERMISSION_DENIED);
      return false;
    }

		//sanity checking
		if ($this->groupId==null)
		{
		  $this->throwError(_I18N_GROUP_SPECIFY_ERROR);
		  return false;
		}

		$sql  = "	DELETE FROM auth.account_groups WHERE group_id='".$this->groupId."';
							DELETE FROM auth.group_permissions WHERE group_id='".$this->groupId."';
							DELETE FROM auth.group_location WHERE group_id='".$this->groupId."';
							DELETE FROM auth.groups WHERE id='".$this->groupId."';
							";
		$this->DB->query($sql);

    if ($this->DB->error())
    {
      $this->throwError($this->DB->error());
    } 

  }

  function addMember()
  {

    if (!PERM::check(ADMIN))
    {
      $this->throwError(_I18N_PERMISSION_DENIED);
      return false;
    }

    //sanity checking
    if ($this->groupId==null)
    {
      $this->throwError(_I18N_GROUP_SPECIFY_ERROR);
      return false;
    }

    if (!$this->apidata["account_id"])
    {
      $this->throwError(_I18N_ACCOUNT_SPECIFY_ERROR);
      return false;
    }

    //see if we are already a member
    $sql = "SELECT account_id FROM auth.account_groups WHERE group_id='".$this->groupId."' AND account_id='".$this->apidata["account_id"]."'";
    $info = $this->DB->single($sql);
    
    if (!$info)
    {
      $opt = null;
      $opt["account_id"] = $this->apidata["account_id"];
      $opt["group_id"] = $this->groupId;
      $this->DB->insert("auth.account_groups",$opt);    
    }

    if ($this->DB->error())
    {
      $this->throwError($this->DB->error());
    } 
  
  }

  function removeMember()
  {

    if (!PERM::check(ADMIN))
    {
      $this->throwError(_I18N_PERMISSION_DENIED);
      return false;
    }

    //sanity checking
    if ($this->groupId==null)
    {
      $this->throwError(_I18N_GROUP_SPECIFY_ERROR);
      return false;
    }

    if (!$this->apidata["account_id"])
    {
      $this->throwError(_I18N_ACCOUNT_SPECIFY_ERROR);
      return false;
    }

    //see if we are already a member
    $sql = "DELETE FROM auth.account_groups WHERE group_id='".$this->groupId."' AND account_id='".$this->apidata["account_id"]."'";
    $this->DB->query($sql);
    
    if ($this->DB->error())
    {
      $this->throwError($this->DB->error());
    } 
  
  }

  function getMembers()
  {

    if (!PERM::check(ADMIN))
    {
      $this->throwError(_I18N_PERMISSION_DENIED);
      return false;
    }

    //sanity checking
    if ($this->groupId==null)
    {
      $this->throwError(_I18N_GROUP_SPECIFY_ERROR);
      return false;
    }

    //get all accounts 
    $a = new ACCOUNT();
    $a->searchField = "name";
    $a->searchSort = "login";
    $a->searchString = $this->apidata["search_string"];
    $accounts = $a->search();

    //get all links for this group
    $sql = "SELECT account_id FROM auth.account_groups WHERE group_id='".$this->groupId."'";
    $results = $this->DB->fetch($sql,1);
    $members = $results["account_id"];

    for ($i=0;$i<$accounts["count"];$i++)
    {

      $account = &$accounts[$i];
      
      //mark if this account is a member of the current group
      if (@in_array($account["id"],$members)) $account["member"] = "t";
      else $account["member"] = "f";
    
      $this->PROTO->add("record",$account);
          
    }

    if ($this->DB->error())
    {
      $this->throwError($this->DB->error());
    } 

  }

  function getPermissions()
  {

    if (!PERM::check(ADMIN))
    {
      $this->throwError(_I18N_PERMISSION_DENIED);
      return false;
    }

    //sanity checking
    if ($this->groupId==null)
    {
      $this->throwError(_I18N_GROUP_SPECIFY_ERROR);
      return false;
    }

    //get bitmask for this group
    $sql = "SELECT bitmask FROM auth.group_permissions WHERE group_id='".$this->groupId."'";
    $info = $this->DB->single($sql);
    $bitmask = $info["bitmask"];

    $str = file_get_contents("config/permissions.xml");
    
    $arr = XML::decode($str);
   
    //create a new permissions object for this group
    $p = new PERM($this->groupId,"group");
    
    foreach ($arr["perm"] AS $perm)
    {
    
      if ($p->is_set($bitmask,$perm["bitpos"])) $perm["enabled"] = "t";
      else $perm["enabled"] = "f";
    
      $this->PROTO->add("record",$perm);
          
    }
  
  }

  function savePermissions()
  {

    if (!PERM::check(ADMIN))
    {
      $this->throwError(_I18N_PERMISSION_DENIED);
      return false;
    }

    //sanity checking
    if ($this->groupId==null)
    {
      $this->throwError(_I18N_GROUP_SPECIFY_ERROR);
      return false;
    }
  
    //create a new permissions object for this group
    $p = new PERM($this->groupId,"group");

    $p->saveGroup($this->apidata["permission"]);
 
    if ($this->DB->error())
    {
      $this->throwError($this->DB->error());
    } 

  }

}


	 
