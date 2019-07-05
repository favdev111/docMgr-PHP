<?php
/************************************************************************************************************
	ldap.php
	
	Holds account processing and search functions for
	an ldap database
	
	02-07-2005 - Fixed returnAccountInfo returning an error if it did not find an account Id (Eric L.)
	02-14-2005 - Split group info
	11-20-2005 - Stripped file down more and added support for an ldap map file

***********************************************************************************************************/

//loop in the permissions class
require_once("perm.php");
require_once("ldap_group.php");

class ACCOUNT 
{

	public $searchString;
	public $searchField;
	public $searchSort;
	public $searchLimit;
	public $searchOffset;
	public $accountId;
	public $error;
	public $readPlugins;
	public $writePlugins;
	
	protected $DB;			
	protected $authorizePlugin;
	protected $errorMessage;
	protected $ACCT;
	protected $mode;
	
	function __construct($aid=null) 
	{

		$this->setupPlugins();
				
		$this->DB = $GLOBALS["DB"];
		
		if ($aid) $this->accountId = $aid;
		else $this->accountId = null;
				
	}

	/**
		determines which plugins to load based on whether or not we are using
		LDAP and if it's read-only or not
		*/
	function setupPlugins()
	{

		//setup our authorize plugin.  add ldap if enabled in the config
		if (defined("USE_LDAP")) 
		{

			//setup to authorize with ldap, and fetch information using db/ldap
			$this->authorizePlugin = "ldap";
			$this->readPlugins = array("db","ldap");

			//if readonly, only write to db					
			if (defined("LDAP_READ_ONLY"))
			{
				$this->writePlugins = array("db");
			}
			else
			{
				$this->writePlugins = array("db","ldap");
			}

		}
		//just setup to use db
		else 
		{
			$this->authorizePlugin = "db";
			$this->readPlugins = array("db");
			$this->writePlugins = array("db");
		}

	}

	/**
		initializes a plugin class and returns its reference
		*/
	function loadPlugin($plugin)
	{

		$class = "ACCOUNT_".$plugin;
		
		$plugin = new $class($this->accountId);
		
		return $plugin;
	
	}

	function throwError($msg) 
	{
		$this->errorMessage = $msg;
	}
	
	function getError() 
	{
		return $this->errorMessage;
	}


	function nameFilter()
	{
	
		$filter = array();

		if (is_numeric($this->searchString)) $filter[] = "id='".$this->searchString."'";
		else
		{

  		$filter[] = "login ILIKE '".$this->searchString."%'";
			
			//name searching
			$arr = organizeName($this->searchString);
	
			if (count($arr)==1)
			{
				$filter[] = "(first_name ILIKE '".$arr["ln"]."%' OR last_name ILIKE '".$arr["ln"]."%')";
			}
			else
			{
				$filter[] = "(first_name ILIKE '".$arr["fn"]."%' AND last_name ILIKE '".$arr["ln"]."%')";
			}

    }
    
		return implode(" OR ",$filter);			
	
	}

	/**
		searches the local account database for matches
	*/
	function search()
	{
	
		$filter = null;
		$sort = null;
	 
	 	if ($this->searchString)
	 	{
	 		if ($this->searchField=="name") $filter = $this->nameFilter();
	 		else $filter = $this->searchField."='".$this->searchString."'";
		}

		if ($this->searchSort)
		{
			if ($this->searchSort=="name") $sort = "first_name,last_name";
			else $sort = $this->searchSort;
		}
		
		//run the query	 
		$sql = "SELECT auth.accounts.*,(first_name || ' ' || last_name) AS full_name FROM auth.accounts ";
		if ($filter) $sql .= " WHERE ".$filter;
		if ($sort) $sql .= " ORDER BY ".$sort;

		return $this->DB->fetch($sql);
	 
	}
	
	/**
	  legacy support
	  */
  function getInfo($aid=null)
  {
    return $this->get($aid);
  }

  function getList()
  {
    return $this->search();
  }
  
	
	/**
		gets information for the current user
		*/
	function get($aid=null)
	{
	
		if ($aid)
		{
			//passed a login instead of an id, try to get the id
			if (is_numeric($aid)) $this->accountId = $aid;
			else $this->accountId = $this->loginToId($aid);
		}

		$info = array();

		$this->DB->begin();

		//loop through our plugins and merge any additional desired information
		foreach ($this->readPlugins AS $readPlugin)
		{
			
			$plugin = $this->loadPlugin($readPlugin);
			
			if (!method_exists($plugin,"get")) continue;

			//merge in the information returned from the plugin
			$pluginInfo = $plugin->get();
			if (is_array($pluginInfo)) $info = array_merge($info,$pluginInfo);

			//bail if there's an error
			if ($plugin->getError())
			{
				//roll back any and all db changes and bail
				$this->DB->rollback();
				$this->throwError($plugin->getError());
				return false;
			}

		}

		//$info["crypt_password"] = $info["password"];
		//$info["password"] = $info["plain_password"];
		
		$this->DB->end();


		return $info;
				
	}

	/**
		converts a username to an account id
		*/
	function loginToId($login)
	{
	
		$sql = "SELECT id FROM auth.accounts WHERE login='".$login."'";
		$info = $this->DB->single($sql);

		if ($info) return $info["id"];
		else return false;
	
	}

	/**
		verifies the login doesn't contain an invalid character
	*/
	function validLogin($login)
	{
 
		//only allow special chars _ and . in a login
		$arr = array( ",","/","?","'","\"","!","@","#",
									"%","^","&","*","(",")","+","=", 
									"}","{","[","]","|","\\",":",";","<",
									">"
									);
 
		$num = count($arr);

		for ($row=0;$row<$num;$row++) if (strstr($login,$arr[$row])) return false;

		return true;
 
	}
 
 	/**
 		verifies the logi isn't in use by another account
 	*/ 
 	function checkLogin($login)
 	{
 
 		//we are creating a new account
 		if (!$this->accountId)
 		{

 			$sql = "SELECT id FROM auth.accounts WHERE login='".$login."'";
 			$info = $this->DB->single($sql);
 
 			if ($info) return false;

		}
		//they are renaming
		else
		{
			$sql = "SELECT id FROM auth.accounts WHERE id!='".$this->accountId."' AND login='".$login."'";
			$info = $this->DB->single($sql);

			if ($info) return false;
			
		}

		return true;

	}
 
	/**
		creates or updates an account
		*/
	function save($opt)
	{

		//make sure there isn't an invalid character
		if (!$this->validLogin($opt["login"]))
		{
			$this->throwError("This login contains invalid characters");
			return false;
		}
		
		//make sure it's not used by another account
		if (!$this->checkLogin($opt["login"]))
		{
			$this->throwError("This login is already in use");
			return false;
		}
		
		//if no account_id set, make sure our sequence values are synchronized with ldap
		if (!$this->accountId && in_array("ldap",$this->writePlugins))
		{
			//we are creating a new account but pulling from ldap, so update our sequence 
			$this->syncIdSequence();
		}

		$this->DB->begin();

		//loop through our plugins and merge any additional desired information
		foreach ($this->writePlugins AS $writePlugin)
		{

			$plugin = $this->loadPlugin($writePlugin);
			
			if (!method_exists($plugin,"save")) continue;

			$plugin->save($opt);

			//bail if there's an error
			if ($plugin->getError())
			{
				//roll back any and all db changes and bail
				$this->DB->rollback();
				$this->throwError($plugin->getError());
				return false;
			}
      else
      {
        //fetch the account id from our db plugin
  			if ($writePlugin=="db") $this->accountId = $plugin->accountId;
      }

		}

		//need to fetch the account id back from our db plugin

		$this->DB->end();

	}

	/**
		saves the password for the current account
		*/	
	function savePassword($password)
	{

		$this->DB->begin();

		//loop through our plugins and merge any additional desired information
		foreach ($this->writePlugins AS $writePlugin)
		{

			$plugin = $this->loadPlugin($writePlugin);
			
			if (!method_exists($plugin,"savePassword")) continue;

			$plugin->savePassword($password);

			//bail if there's an error
			if ($plugin->getError())
			{
				//roll back any and all db changes and bail
				$this->DB->rollback();
				$this->throwError($plugin->getError());
				return false;
			}

		}

		$this->DB->end();
				
	}

	function delete() 
	{

		$this->DB->begin();

		//loop through our plugins and merge any additional desired information
		foreach ($this->writePlugins AS $writePlugin)
		{

			$plugin = $this->loadPlugin($writePlugin);
			
			if (!method_exists($plugin,"delete")) continue;

			$plugin->delete();

			//bail if there's an error
			if ($plugin->getError())
			{
				//roll back any and all db changes and bail
				$this->DB->rollback();
				$this->throwError($plugin->getError());
				return false;
			}

		}

		$this->DB->end();
		
	}

	function password_check($login,$password,$digestHash=null)
	{

 		$plugin = $this->loadPlugin($this->authorizePlugin);

		//call the plugin to verify the password
		$response = $plugin->password_check($login,$password,$digestHash);

		//if authenticated, return generic info for the account
		if ($response) 
		{

			$this->accountId = $response;

			//see if the account is enabled
			$sql = "SELECT enable FROM auth.account_permissions WHERE account_id='".$this->accountId."'";
			$permInfo = $this->DB->single($sql);

			//there's no permissions record for this user.  Make one
			if (!$permInfo)
			{
			  //we are using LDAP and logging in with the new admin account
			  if (defined("USE_LDAP") && $this->accountId==LDAP_ADMIN_ID)
			  {
			    $bitmask = "00000000000000000000000000000001";
        }
        else
        {
          $bitmask = DEFAULT_PERMISSIONS;
        }

			  $opt = null;
			  $opt["account_id"] = $this->accountId;
			  $opt["enable"] = "t";
			  $opt["setup"] = "f";
			  $opt["bitmask"]  = $bitmask;
			  $this->DB->insert("auth.account_permissions",$opt);
			  
			  //default to enabled
        $permInfo["enable"] = "t";			

			}
      			
  		//store our password for use later
  		$info = $this->get();
  		$info["password"] = $password;
    	$info["enable"] = $permInfo["enable"];
      
			return $info;
			
		}
		else return false;

	}

	function syncIdSequence()
	{

		$ldap = $this->loadPlugin("ldap");
		$maxId = $ldap->maxId();

		//update our sequence to use that number
		$curVal = $this->DB->next_seq("auth.accounts_id_seq");
		
		//if ldap is higher, use it for our next id value
		if ($curVal < $maxId) 
		{
		  $this->DB->set_seq("auth.accounts_id_seq",$maxId + 1);			
    }
    
	}

	function syncLDAP()
	{

		$this->loadPlugin("db");	
		$ldap = $this->loadPlugin("ldap");

		$results = $ldap->search();
		$num = count($results);

		//for storing valid accounts
		$idArr = array();

		$this->DB->begin();
		
		for ($i=0;$i<$num;$i++)
		{

			//set the account id and sync it to the database
			$ldap->accountId = $results[$i][strtolower(LDAP_UIDNUMBER)][0];
			$ldap->syncToDB();

			if ($ldap->getError())
			{
				$this->DB->rollback();
				$this->throwError($ldap->getError());
				break;
			}				

			//save for later
			$idArr[] = $ldap->accountId;

		}	

		//no error, now remove old accounts
		if (!$this->getError())
		{

			$sql = "SELECT id FROM auth.accounts WHERE id NOT IN ('".implode("','",$idArr)."')";
			$stale = $this->DB->fetch($sql);
			
			for ($i=0;$i<$stale["count"];$i++)
			{
			  //delete from the database side
			  $db = new ACCOUNT_DB($stale[$i]["id"]);
			  $db->delete();
			}

			//make sure there's a permissions entry for the admin
			$sql = "SELECT account_id FROM auth.account_permissions WHERE account_id='".LDAP_ADMIN_ID."'";
			$info = $this->DB->single($sql);

			//no permissions entry for our admin user
			if (!$info)
			{
			  $sql = "INSERT INTO auth.account_permissions 
			            (account_id, enable, locked_time, failed_logins, failed_logins_locked, last_success_login, setup,last_activity, bitmask) 
                  VALUES 
                  ('".LDAP_ADMIN_ID."', true, NULL, 0, false, NOW(), false, NOW(), B'00000000000000000000000000000001');";
        $this->DB->query($sql);			
			}
  		
		}

		$this->DB->end();
	
	}

	//caches the results for one page run so we don't have to keep hitting the database
	function cachedGet($id)
	{
	
		$this->cachedList();

		$num = count($GLOBALS["cachedAccountList"]);
		$info = null;
	
		//loop through our cache and find the match	
		for ($i=0;$i<$num;$i++)
		{
		
			if ($GLOBALS["cachedAccountList"][$i]["id"]==$id)
			{
				$info = $GLOBALS["cachedAccountList"][$i];		
				break;
			}
			
		}
		
		return $info;

	}

	//caches the results for one page run so we don't have to keep hitting the database
	function cachedList()
	{
	
		//if not cached, perform a search to get our results
		if (!$GLOBALS["cachedAccountList"])
		{
			$GLOBALS["cachedAccountList"] = $this->search();
		}
				
		return $GLOBALS["cachedAccountList"];

	}

}


function auth_autoload($class_name)
{

  //don't allow any non-alphanumeric info since class names can be passed from the url
  $class = preg_replace("/^a-z0-9_-/i","",$class_name);

  //if they don't match, let the user know so they don't get confused
  if ($class!=$class_name)
  {
    throw new Exception('Class '.$class_name.' not exists');
  }

  $cn = strtolower($class_name);
  $file = SITE_PATH."/lib/account/plugins".str_replace("account_","/",$cn.".php");

  if (file_exists($file)) require_once($file);

}

spl_autoload_register('auth_autoload');
