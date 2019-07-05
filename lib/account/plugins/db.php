<?php
/************************************************************************************************************
	db.php
***********************************************************************************************************/

class ACCOUNT_DB 
{

	public $accountId;
	public $searchString;
	public $searchField;
	public $searchSort;
	
	protected $errorMessage;
	protected $DB;
	
	function __construct($aid=null) 
	{

		$this->DB = $GLOBALS["DB"];
		if ($aid) $this->accountId = $aid;

	}

	function throwError($msg) 
	{
		$this->errorMessage = $msg;
	}
	
	function getError() 
	{
		return $this->errorMessage;
	}

	/**
		gets general information for current user
		*/
	function get() 
	{
		$sql = "SELECT * FROM auth.accounts WHERE	id='".$this->accountId."'";
		return $this->DB->single($sql);
	}

	/**
		saves general account information in database
		*/
	function save($data,$autocreate=null)
	{

		//if passed autocreate, check to see if this accountId actually exists
		if ($autocreate)
		{

			$sql = "SELECT id FROM auth.accounts WHERE id='".$this->accountId."'";
			$info = $this->DB->single($sql);

			//not found, we are syncing from an external source
			if (!$info) $this->accountId = null;

		}

		//see which fields were passed to update
		$keys = array("id","login","first_name","last_name","email","home_phone","work_phone","fax","mobile");
		
		$opt = null;

		foreach ($keys AS $key)
		{
			if (isset($data[$key])) $opt[$key] = $data[$key];
		}

		//creating a new account
		if (!$this->accountId)
		{

			
			$password = uuid();

			//set a random password for now
			$opt["password"] = $this->cryptPassword($password);
			$opt["digest_hash"] = md5($data["login"].":".DIGEST_REALM.":".$password);

			//if passed the id to set, we don't need to ask for one
			if ($opt["id"]) $retField = null;
			else $retField = "id";

			$this->accountId = $this->DB->insert("auth.accounts",$opt,$retField);

		}
		else
		{
			$opt["where"] = "id='".$this->accountId."'";
			$this->DB->update("auth.accounts",$opt);
		}

		if ($this->DB->error()) $this->throwError($this->DB->error());

		return $this->accountId;

	}


	/**
		delete account from database
		*/
	function delete() 
	{

		$sql = "DELETE FROM auth.accounts WHERE id='".$this->accountId."';";
		$sql .= "DELETE FROM auth.account_permissions WHERE account_id='".$this->accountId."';";
		$sql .= "DELETE FROM auth.account_groups WHERE account_id='".$this->accountId."';";
		$this->DB->query($sql);

		if ($this->DB->error()) $this->throwError($this->DB->error());

	}


	/**
		save password for current user
		*/
	function savePassword($password) 
	{

		$info = $this->get();

		$opt = null;
		$opt["password"] = $this->cryptPassword($password);
		$opt["digest_hash"] = md5($info["login"].":".DIGEST_REALM.":".$password);
		$opt["where"] = "id='".$this->accountId."'";
		$this->DB->update("auth.accounts",$opt);

		if ($this->DB->error()) $this->throwError($this->DB->error());
		
	}

	/**
		verify password for passed login
		*/
	function password_check($login,$password,$digestHash=null) 
	{
	
		if ($digestHash)
		{
			$sql = "SELECT id FROM auth.accounts WHERE login='".$login."' AND digest_hash='".$digestHash."'";  		
  		$info = $this->DB->single($sql);

  		if ($info) return $info["id"];
  		else return false;
		
		}
		else
		{

		  $sql = "SELECT id,password FROM auth.accounts WHERE login='".$login."'";
		  $info = $this->DB->single($sql);

		  //crypt the passed password using our password as the salt.  this will guarantee
		  //the same encryption method will be used
      $crypt = $this->cryptPassword($password,$info["password"]);

		  if ($crypt==$info["password"]) return $info["id"];
		  else return false;

		}

		
	}

	function cryptPassword($password,$salt = null)
	{

	  //passed a salt to compare our password against
	  if ($salt)
	  {
	  
	    //if there actually isn't a salt, use md5() to verify our old passwords
	    if ($salt[0]!='$')
	    {
	      $crypt = md5($password);
      }
      else
      {
  	    $crypt = crypt($password,$salt);
      }
      
    }
    //no salt, we are generating a new encrypted password
    else
    {

  		if (CRYPT_SHA512 == 1) 
  		{
		    $random = strtolower(str_replace("-","",uuid()));
		    $salt = '$6$rounds=5000$'.$random.'$';
  		  $crypt = crypt($password,$salt);
      }
  		else if (CRYPT_SHA256 == 1)
  		{
  		  $random = strtolower(str_replace("-","",uuid()));
  		  $salt = '$5$rounds=5000$'.$random.'$';
  		  $crypt = crypt($password,$salt);
  		}
  		else if (CRYPT_BLOWFISH == 1)
  		{
  		  $random = strtolower(str_replace("-","",uuid()));
  		  $salt = '$2a$07$'.$random.'$';
  		  $crypt = crypt($password,$salt);
      }
      //fallback on CRYPT_MD5
      else 
      {
        $random = strtolower(str_replace("-","",uuid()));
        $salt = '$1$'.$random.'$';
        $crypt = crypt($password,$salt);
      }

		}

		return $crypt;

	}
		
		
}
			