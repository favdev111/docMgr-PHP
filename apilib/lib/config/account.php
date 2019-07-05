<?php

class CONFIG_ACCOUNT extends CONFIG
{

  private $accountId;
  
  function ___construct()
  {

    //if passed an account_id, make sure it's ours, or we have permissions to edit it  
    if ($this->apidata["account_id"])
    {

      if ($this->apidata["account_id"]==USER_ID) $this->accountId = USER_ID;
      else
      {
      
        if (!PERM::check(ADMIN))
        {
          $this->throwError(_I18N_PERMISSION_DENIED);
          return false;
        }
        else
        {
          $this->accountId = $this->apidata["account_id"];
        }

      }
    
    }
    else if (!PERM::check(ADMIN))  $this->accountId = USER_ID;

  }

  function sync()
  {

    if (!PERM::check(ADMIN))
    {
      $this->throwError(_I18N_PERMISSION_DENIED);
      return false;
    }

    $a = new ACCOUNT();
    $a->syncLDAP();
    
    if ($a->getError()) $this->throwError($a->getError());

  }
  

  function search()
  {

    $filter = null;
    $sortOrder = "login";

    $a = new ACCOUNT();
    $a->searchField = "name";
    $a->searchSort = "login";
    $a->searchString = $this->apidata["search_string"];

    $results = $a->search();

    if ($results["count"] > 0)
    {
    
      $sql = "SELECT account_id,last_success_login FROM auth.account_permissions ORDER BY account_id";
      $arr = $this->DB->fetch($sql,1);
      
      for ($i=0;$i<$results["count"];$i++)
      {

        $key = @array_search($results[$i]["id"],$arr["account_id"]);
        
        if ($key!==FALSE) $results[$i]["last_success_login"] = dateFormat($arr["last_success_login"][$key]);
        
      }
    
    }

    unset($results["count"]);

    $this->PROTO->add("record",$results);

    return $results;
    
  }

  function get()
  {

    if ($this->accountId != USER_ID && !PERM::check(ADMIN))
    {
      $this->throwError(_I18N_PERMISSION_DENIED);
      return false;
    }

    $a = new ACCOUNT();
    $info = $a->get($this->accountId);

    $sql = "SELECT enable FROM auth.account_permissions WHERE account_id='".$this->accountId."'";
    $perms = $this->DB->single($sql);
    $info["enable"] = $perms["enable"];
    
    $this->PROTO->add("record",$info);

  }

  function delete()
  {

    //sanity checking
    if (!$this->accountId)
    {
      $this->throwError(_I18N_ACCOUNT_SPECIFY_ERROR);
      return false;
    }

    if (!PERM::check(ADMIN))
    {
      $this->throwError(_I18N_PERMISSION_DENIED);
      return false;
    }

    $this->DB->begin();

    //now delete the account
    $a = new ACCOUNT($this->accountId);
    $info = $a->getInfo();
    $a->delete();
    
    if ($a->getError()) $this->throwError($a->getError());
    else
    {

      //what else needs to go here?
      $sql= " DELETE FROM auth.account_config WHERE account_id='".$this->accountId."';
              DELETE FROM auth.account_permissions WHERE account_id='".$this->accountId."';
              DELETE FROM auth.account_groups WHERE account_id='".$this->accountId."';
              ";
      $this->DB->query($sql);

      if ($this->DB->error())
      {
        $this->throwError($this->DB->error());
      } 

      //unprotect their home folder
      $a = new DOCMGR("/Users/".$info["login"]);

      if ($a->objectId)
      {
        $sql = "UPDATE docmgr.dm_object SET protected='f' WHERE id='".$a->objectId."'";
        $this->DB->query($sql);
      }

    }
   
    $this->DB->end();
    
  }
                                                                                                        
  function save()
  {

    if (!PERM::check(ADMIN))
    {
      $this->throwError(_I18N_PERMISSION_DENIED);
      return false;
    }

    //if no account id, they have to specify a password for the new account
    if (!$this->accountId && !$this->apidata["password"])
    {
      $this->throwError(_I18N_ACCOUNT_SPECIFY_PASSWORD_ERROR);
      return false;
    }

    $fields = array("login","first_name","last_name","email","home_phone","work_phone","mobile","fax");

    //for storing our values to submit to save
    $opt = null;
        
    foreach ($fields AS $field) 
    {
      if (isset($this->apidata[$field])) $opt[$field] = $this->apidata[$field];
    }

    //init for account management
    $a = new ACCOUNT();

    //update our submit default info
    if ($this->accountId) 
    {
      $a->accountId = $this->accountId;  
    }

    //write our data if there is anything to write
    $a->save($opt);

    //any problems so far?
    if ($a->getError()) $this->throwError($a->getError());    
    else
    {

      //this is a new entry, store our id and save the password
      if (!$this->accountId)
      {
        $this->accountId = $a->accountId;

        //set the password
	      $a->savePassword($this->apidata["password"]);

      }

      //check to make sure we have a permissions record, and update "enable" if passed
      $this->checkPermissions();
      
    }

    if ($this->accountId) $this->PROTO->add("account_id",$this->accountId);
  
  }

  function saveProfile()
  {

    if (!PERM::check(EDIT_PROFILE))
    {
      $this->throwError(_I18N_PERMISSION_DENIED);
      return false;
    }
    
    if ($this->accountId != USER_ID)
    {
      $this->throwError(_I18N_PERMISSION_DENIED);
      return false;
    }

    //for storing our values to submit to save
    $opt = null;

    $fields = array("first_name","last_name","email","home_phone","work_phone","mobile","fax");
        
    foreach ($fields AS $field) 
    {
      if (isset($this->apidata[$field])) $opt[$field] = $this->apidata[$field];
    }

    //init for account management
    $a = new ACCOUNT();
    $a->accountId = $this->accountId;  
    $a->save($opt);

    //any problems so far?
    if ($a->getError()) $this->throwError($a->getError());    

    $this->PROTO->add("account_id",$this->accountId);
  
  }

  /**
    checks to make sure we have a permissions record, and updates "enable" if passed
    */
  private function checkPermissions()
  {

      $sql = "SELECT account_id FROM auth.account_permissions WHERE account_id='".$this->accountId."'";
	    $info = $this->DB->single($sql);
	      
      //if a record exists and the user passed an enable flag
      if ($info && $this->apidata["enable"])
      {
        $opt = null;
        $opt["enable"] = $this->apidata["enable"];
        $opt["where"] = "account_id='".$this->accountId."'";
        $this->DB->update("auth.account_permissions",$opt);
      }
      //account doesn't exist, make a record
      else
      {

        $opt = null;
        $opt["account_id"] = $this->accountId;

        //if passed an enable option, use it.  otherwise default to enabled
        if ($this->apidata["enable"]) $opt["enable"] = $this->apidata["enable"];
        else $opt["enable"] = "t";

        //store some default permissions while we're at it        
	      if (defined("DEFAULT_PERMISSIONS")) $opt["bitmask"] = DEFAULT_PERMISSIONS;

        //insert the record
        $this->DB->insert("auth.account_permissions",$opt);
	      
      }

  
  }

  function savePassword()
  {

    //sanity checking
    if (!$this->accountId)
    {
      $this->throwError(_I18N_ACCOUNT_SPECIFY_ERROR);
      return false;
    }

    $a = new ACCOUNT($this->accountId);
    $a->savePassword($this->apidata["password"]);
    
    if ($a->getError()) $this->throwError($a->getError());
        
  }
  
  function getPermissions()
  {

    //sanity checking
    if (!$this->accountId)
    {
      $this->throwError(_I18N_ACCOUNT_SPECIFY_ERROR);
      return false;
    }

    if (!PERM::check(ADMIN))
    {
      $this->throwError(_I18N_PERMISSION_DENIED);
      return false;
    }

    //get bitmask for this account
    $sql = "SELECT bitmask FROM auth.account_permissions WHERE account_id='".$this->accountId."'";
    $info = $this->DB->single($sql);
    $bitmask = $info["bitmask"];

    $str = file_get_contents("config/permissions.xml");
    
    $arr = XML::decode($str);
   
    //create a new permissions object for this account
    $p = new PERM($this->accountId);
    
    foreach ($arr["perm"] AS $perm)
    {
    
      if ($p->is_set($bitmask,$perm["bitpos"])) $perm["enabled"] = "t";
      else $perm["enabled"] = "f";
    
      $this->PROTO->add("record",$perm);
          
    }
  
  }

  function savePermissions()
  {

    //sanity checking
    if (!$this->accountId)
    {
      $this->throwError(_I18N_ACCOUNT_SPECIFY_ERROR);
      return false;
    }

    if (!PERM::check(ADMIN))
    {
      $this->throwError(_I18N_PERMISSION_DENIED);
      return false;
    }
  
    //create a new permissions object for this account
    $p = new PERM($this->accountId);

    $p->saveAccount($this->apidata["permission"]);
 
    if ($this->DB->error())
    {
      $this->throwError($this->DB->error());
    } 

  }

  function getGroups()
  {

    //sanity checking
    if (!$this->accountId)
    {
      $this->throwError(_I18N_ACCOUNT_SPECIFY_ERROR);
      return false;
    }

    if (!PERM::check(ADMIN))
    {
      $this->throwError(_I18N_PERMISSION_DENIED);
      return false;
    }
 
    //get the groups of the current user
    $sql = "SELECT group_id FROM auth.account_groups WHERE account_id='".$this->accountId."'";
    $results = $this->DB->fetch($sql,1);

    //dprint(print_r($results,1));
    
    $g = new CONFIG_GROUP($this->apidata);
    $groups = $g->search(1);
    
    $num = count($groups);
    
    for ($i=0;$i<$num;$i++)
    {
      
      if (@in_array($groups[$i]["id"],$results["group_id"])) $groups[$i]["member"] = "t";
      else $groups[$i]["member"] = "f";

      //dprint($groups[$i]["member"]."\n");
      
      $this->PROTO->add("record",$groups[$i]);      
    
    }   
  
  }

  function saveGroups()
  {
  
    //sanity checking
    if (!$this->accountId)
    {
      $this->throwError(_I18N_ACCOUNT_SPECIFY_ERROR);
      return false;
    }

    if (!PERM::check(ADMIN))
    {
      $this->throwError(_I18N_PERMISSION_DENIED);
      return false;
    }

    $this->DB->begin();
    
    //clear out existing records
    $sql = "DELETE FROM auth.account_groups WHERE account_id='".$this->accountId."'";
    $this->DB->query($sql);

    //add new ones   
    for ($i=0;$i<count($this->apidata["group_id"]);$i++)
    {
      $gid = $this->apidata["group_id"][$i];
      $opt["account_id"] = $this->accountId;
      $opt["group_id"] = $gid;
      $this->DB->insert("auth.account_groups",$opt);
    }

    $this->DB->end();
        
    if ($this->DB->error())
    {
      $this->throwError($this->DB->error());
    } 
  
  }

  function getSettings()
  {

    //sanity checking
    if (!$this->accountId)
    {
      $this->throwError(_I18N_ACCOUNT_SPECIFY_ERROR);
      return false;
    }


    if ($this->accountId != USER_ID && !PERM::check(ADMIN))
    {
      $this->throwError(_I18N_PERMISSION_DENIED);
      return false;
    }
 
    //get the groups of the current user
    $sql = "SELECT * FROM auth.account_config WHERE account_id='".$this->accountId."'";
    $results = $this->DB->fetch($sql);

    if ($results["count"] > 0)
    {
      unset($results["count"]);
      $this->PROTO->add("record",$results);
    }
  
  }

  function saveSettings()
  {
  
    //sanity checking
    if (!$this->accountId)
    {
      $this->throwError(_I18N_ACCOUNT_SPECIFY_ERROR);
      return false;
    }

    if (!PERM::check(EDIT_PROFILE))
    {
      $this->throwError(_I18N_PERMISSION_DENIED);
      return false;
    }

    if ($this->accountId != USER_ID && !PERM::check(ADMIN))
    {
      $this->throwError(_I18N_PERMISSION_DENIED);
      return false;
    }

    $this->DB->begin();

    //see if there's an entry
    $sql = "SELECT account_id FROM auth.account_config WHERE account_id='".$this->accountId."'";
    $info = $this->DB->single($sql);

    //collect and store our basic information
    $keys = array("language","editor","email_notifications");
   
    $opt = null;
     
    foreach ($keys AS $key) 
    {
      if (isset($this->apidata[$key])) $opt[$key] = $this->apidata[$key];
    }

    if ($info)
    {
      $opt["where"] = "account_id='".$this->accountId."'";
      $this->DB->update("auth.account_config",$opt);
    }
    else
    {
      $opt["account_id"] = $this->accountId;
      $this->DB->insert("auth.account_config",$opt);
    }
    
    $this->DB->end();
        
    if ($this->DB->error())
    {
      $this->throwError($this->DB->error());
    }
    else
    {
      unset($_SESSION["api"]["accountInfo"]);      

      //get our new settings
      $auth = new AUTH();
      $auth->accountId = USER_ID;
      $auth->setAccountInfo();
      
    }  

  }

  function getLanguages()
  { 

    $results = array();
    $arr = scandir("lang");

    foreach ($arr AS $lang)
    {

      if ($lang=="." || $lang=="..") continue;

      $l = array();
      $l["id"] = $lang;
      $l["name"] = trim(file_get_contents("lang/".$lang."/description"));
      $results[] = $l;

    }

    return $results;

  }

}
