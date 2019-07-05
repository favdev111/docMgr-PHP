<?php

/***************************************************************
  CLASS:		PERM
  PURPOSE:	a wrapper for bit string comparison and manipulation
            for app account and group permissions.  also does
            sets account permissions for the app, and can
            save account and group perms back to database
***************************************************************/  

class PERM
{

  private $DB;
  private $accountId;
  private $groupId;

  /***************************************************************
    FUNCTION:	construct
    PURPOSE:	class initializer.  this isn't needed for the
              statically called functions, like "check",
              "is_set", and "bit_or"
    INPUTS:		id -> account id or group id we are working on
              mode -> "account" or "group"
  ***************************************************************/  
  function __construct($id,$mode="account")
  {

    $this->DB = $GLOBALS["DB"];

    if ($mode=="group") $this->groupId = $id;
    else $this->accountId = $id;
    
  }

  /*****************************************************
    STATICALLY CALLED
  *****************************************************/


  /***************************************************************
    FUNCTION:	check
    PURPOSE:	checks to see if the current user meets
              the passed permission requirement
    INPUTS:		$bc -> bit position to check
              $noadmin -> if set, doesn't check for admin perms
                          as a fallback
  ***************************************************************/  
  public static function check($bc,$noadmin=null)
  {
  
    $auth = false;

    $bitmask = BITSET;

    //passed an array of possible options
    if (is_array($bc))
    {

      foreach ($bc AS $bit)
      {
        $auth = PERM::is_set($bitmask,"$bit");

        //if we found one, just top here
        if ($auth==true) break;
      }    

    }
    else
    {
      $auth = PERM::is_set($bitmask,"$bc");
    }

    //unless told otherwise, see if the admin can access this
    if (!$auth && !$noadmin && $bc!=ADMIN) $auth = PERM::is_set($bitmask,ADMIN);

    return $auth;
    
  }

  /***************************************************************
    FUNCTION:	customCheck
    PURPOSE:	checks to see if the current user meets
              the passed permission requirement for the apps
              CUSTOM_BITSET setting
    INPUTS:		$bc -> bit position to check
  ***************************************************************/  
  public static function checkCustom($bc)
  {
  
    $auth = false;

    $bitmask = CUSTOM_BITSET;

    $auth = PERM::is_set($bitmask,$bc);
    
    return $auth;
    
  }

  /***************************************************************
    FUNCTION:	is_set
    PURPOSE:	checks to see if a position on the passed bitmask
              is set
    INPUTS:		bitmask -> bit mask to check for the set position
              checkpos -> position to check if set
  ***************************************************************/  
  public static function is_set($bitmask,$checkpos)
  {

    $auth = false;

    //reverse the string to make life easier
    $bitmask = strrev($bitmask);
  
    //see if the bit at the passed position is set
    if ($bitmask[$checkpos]=="1") $auth = true;

    return $auth;
  
  }

  /***************************************************************
    FUNCTION:	setDefines
    PURPOSE:	sets the defines in the passed xml file.  sets the
              define name to the bit position
    INPUTS:		$file -> file to parse for define info
  ***************************************************************/  
  
  public static function setDefines($file)
  {
  
    $arr = explode("/",$file);
    $sessName = array_pop($arr);

    if (!$_SESSION[$sessName] || defined("DEV_MODE")) 
    {

      if (defined("ALT_FILE_PATH")) $file = ALT_FILE_PATH."/".$file;


      $str = file_get_contents($file);
      $_SESSION[$sessName] = xml2Array($str);

    }

    if ($_SESSION[$sessName]["perm"])
    {

      foreach ($_SESSION[$sessName]["perm"] AS $perm)
      {
      
        $dn = $perm["define_name"]; 
        $bitVal = $perm["bitpos"];
        define("$dn","$bitVal");
        
      }

    }

  }

  /***************************************************************
    FUNCTION:	bit_or
    PURPOSE:	version of bit1 |= bit2.  Returns a number with
              positions set in both bit1 and bit2 set in new number
    INPUTS:		bit1 -> bit string to check
              bit2 -> bit string to check
  ***************************************************************/  
  public static function bit_or($bit1,$bit2)
  {

    $len = strlen($bit1);
    $len2 = strlen($bit2);

    if ($len2 > $len) $max = $len2;
    else $max = $len;

    $ret = null;

    //pad to max length bit
    $bit1 = PERM::pad($bit1,$max);
    $bit2 = PERM::pad($bit2,$max);
              
    for ($i=0;$i<$max;$i++)
    {

      if ($bit1[$i]=="1" || $bit2[$i]=="1") $ret .= "1";
      else $ret .= "0";    
    
    }          
  
    return $ret;
  
  }

  /***************************************************************
    FUNCTION:	bit_set
    PURPOSE:	sets the desired position to 1 on the passed bit
              string
    INPUTS:		bit -> bit string to operate on
              pos -> position to set
  ***************************************************************/  
  public static function bit_set($bit,$pos)
  {

    $bit = PERM::pad($bit,$pos);

    $bit = strrev($bit);
    
    $bit[$pos] = "1";
    
    return strrev($bit);

  }

  /***************************************************************
    FUNCTION:	pad
    PURPOSE:	pads a binary string to the desired length.  if no
              length passed, defaults to BITLEN set at top of
              class
    INPUTS:		bit -> bit string to operate on
              len -> length to pad string to
  ***************************************************************/  
  public static function pad($bit,$len=null)
  {

    if ($len==null) $len = PERM_BITLEN;

    $bit = str_pad($bit,$len,"0",STR_PAD_LEFT);
    return $bit;
    
  }

  /*****************************************************
    CALLED FROM CONSTRUCT
  *****************************************************/

  /***************************************************************
    FUNCTION:	set
    PURPOSE:	sets the user's permissions for the app
    INPUTS:		none
  ***************************************************************/  
  public function set()
  {

    //if this has already been done, set our defines and get out of here
    if ($_SESSION["api"]["bitmask"])
    {

      define("BITSET",$_SESSION["api"]["bitmask"]);
      define("USER_GROUPS",$_SESSION["api"]["user_groups"]);

    } 
    else
    {

      //get the total bitmask for this user
      $bitmask_temp = $this->userBitset();

      //set the combined bit value
      if (!defined("BITSET")) define("BITSET",$bitmask_temp);

      //store bitmask in a session
      $_SESSION["api"]["bitmask"] = BITSET;
      
    }

    return true;

  }


  /*****************************************************
    CALLED FROM WITHIN CLASS
  *****************************************************/

  /***************************************************************
    FUNCTION:	userBitset
    PURPOSE:	pulls user's permissions from the db, along with
              perms for the groups they belong to, and combines
              them into their main app BITSET value
    INPUTS:		none
  ***************************************************************/  
  private function userBitset()
  {

		//get the account permissions
		$sql = "SELECT bitmask FROM auth.account_permissions WHERE account_id='".$this->accountId."';";
		$accountInfo = $this->DB->single($sql);

		$bitmask_temp = $accountInfo["bitmask"];
    		
		//nothing returned or nothing is set.  new account, set some defaults
    if ($bitmask_temp==NULL || !strstr($bitmask_temp,"1"))
    {
      $bitmask_temp = $this->defaultPerms();
    }
                     
    //Now, figure out what groups this user belongs to, and get all group permissions
    $sql = "SELECT 
              account_groups.group_id AS group_id,group_permissions.bitmask AS bitmask 
              FROM auth.account_groups
              LEFT JOIN auth.group_permissions ON 
              (account_groups.group_id = group_permissions.group_id )  
              WHERE account_groups.account_id='".$this->accountId."'
            UNION
              SELECT group_id AS group_id,bitmask FROM auth.group_permissions WHERE group_id='0'";
    $groupInfo = $this->DB->fetch($sql,1);

    //we are going to explode this into a delimited string so I can store all groups in a single define.but everyone belongs
    //to the "0" group, which is "Everyone"
    if (is_array($groupInfo["group_id"])) 
    {

      $groupArray = array_values(array_unique($groupInfo["group_id"]));

      //make sure Everyone group is in there
      if (!in_array("0",$groupArray)) $groupArray[] = "0";

      $group_string = implode(",",$groupArray);
      
    }
    else $group_string = "0";

    //store user group string for later 
    $_SESSION["api"]["user_groups"] = $group_string;

    define("USER_GROUPS",$group_string);

		$permArr = array();

    //now loop through all our bitmask values, and combine them to create our users bitmask
    if ($groupInfo) $permArr = $groupInfo["bitmask"];

    for ($row=0;$row<count($permArr);$row++)
    {

      //if these are not the same number, set bits present in either
      $bitmask_temp = $this->bit_or($bitmask_temp,$permArr[$row]);

    }

    return $bitmask_temp;

  }

  
  /***************************************************************
    FUNCTION:	defaultPerms
    PURPOSE:	if no perms are set for the user, sets some
    INPUTS:		none
  ***************************************************************/  
  private function defaultPerms()
  {	

    $a = new ACCOUNT($this->accountId);
    $info = $a->get();

    //get our permissions from the Everyone group and use as default
    $sql = "SELECT bitmask FROM auth.group_permissions WHERE group_id='0'";
    $ginfo = $this->DB->single($sql);

    if ($ginfo["bitmask"]) $cb = $ginfo["bitmask"];
    else $cb = "0";

    //pad it
    $cb = str_pad($cb,PERM_BITLEN,"0",STR_PAD_LEFT);

    //now set the user's default permissions
    $sql = "UPDATE auth.account_permissions SET bitmask='$cb' WHERE account_id='".$this->accountId."'";
    $this->DB->query($sql);

    return $cb;

  }

  /***************************************************************
    FUNCTION:	storeLevel
    PURPOSE:	converts the positions passed from a checkbox form
              made from our permissions.xml file into something
              we can insert into the database
    INPUTS:		$level -> posted data from checkbox form
  ***************************************************************/  
  private function storeLevel($level)
  {
  
    //if it's an array, setup the bitmask.  otherwise convert the integer
    if (is_array($level))
    {
    
      $ret = "0";

      foreach ($level AS $pos)
      {
      
        $ret = PERM::bit_set($ret,$pos);

      }
    
    } 
    else
    {
    
      $ret = decbin($level);
    
    }

    return $ret;
  
  }

  /***************************************************************
    FUNCTION:	saveAccount
    PURPOSE:	saves account's permissions to db
    INPUTS:		$level -> posted data from checkbox form
  ***************************************************************/  
  public function saveAccount($level)
  {

    if (!$level) $level = "0";

    $sql = "SELECT bitmask,account_id FROM auth.account_permissions WHERE account_id='".$this->accountId."'";
    $info = $this->DB->single($sql);

    //convert the form submitted values to a mask
    $level = $this->storeLevel($level,strlen($info["bitmask"]));
    $level = PERM::pad($level,PERM_BITLEN);

    //create our query
    $opt = null;
    $opt["bitmask"] = $level;	//the permissions level we are storing

    //if there are no entries, add the new ones, otherwise update
    if ($info)
    {
      $opt["where"] = "account_id='$this->accountId'";
      $this->DB->update("auth.account_permissions",$opt);
    } 
    else 
    {
      $opt["account_id"] = $this->accountId;
      $this->DB->insert("auth.account_permissions",$opt);
    }

  }

  /***************************************************************
    FUNCTION:	saveGroup
    PURPOSE:	saves group's permissions to db
    INPUTS:		$level -> posted data from checkbox form
  ***************************************************************/  
  public function saveGroup($level)
  {

    if (!$level) $level = "0";

    $sql = "SELECT bitmask FROM auth.group_permissions WHERE group_id='".$this->groupId."'";
    $info = $this->DB->single($sql);

    //convert the form submitted values to a mask
    $level = $this->storeLevel($level,strlen($info["bitmask"]));
    $level = PERM::pad($level,PERM_BITLEN);

    //create our query
    $opt = null;
    $opt["bitmask"] = $level;	//the permissions level we are storing

    //if there are no entries, add the new ones, otherwise update
    if ($info)
    {
      $opt["where"] = "group_id='".$this->groupId."'";
      $this->DB->update("auth.group_permissions",$opt);
    } 
    else 
    {
      $opt["group_id"] = $this->groupId;
      $this->DB->insert("auth.group_permissions",$opt);
    }

  }

}
