<?php

class AUTH 
{

	private $error;
	private $DB;
	private $login;
	private $password;
	private $digestHash;
	private $logger;

	public $accountId;
	public $writeCookie;

	/**
		constructor.  saves our login/password info for use by other methods
		*/							
	function __construct($login=null,$password=null,$digestHash=null) 
	{

		if ($login) $this->login = $login;
		else if (isset($_REQUEST["login"])) $this->login = $_REQUEST["login"];

		if ($password) $this->password = $password;
		else if (isset($_REQUEST["password"]))  $this->password = $_REQUEST["password"];

		if ($digestHash) $this->digestHash = $digestHash;

		$this->DB = $GLOBALS["DB"];
		$this->logger = $GLOBALS["logger"];

	}
      
	/**
		stores an error
		*/
	function throwError($err) 
	{
	
		$this->error = $err;

		//record the login error
		$this->logger->log($err,LOGGER_DEBUG,"AUTH");

	}
	
	/**
		returns the current error
		*/
	function getError() 
	{
		return $this->error;
	}

	/**
		main method for authorizing a user.  Once a cookie or login/password combo has been validated,
		this method stores generic user information for use by the api
		*/	
	function authorize() 
	{

		//if we don't have an authorized session, authorize us by either cookie or login/password
		if (!$_SESSION["api"]["authorize"])
		{
		
			//try the cookie first
			if (defined("USE_COOKIES")) $this->authorize_cookie();		
		
			//if the cookie didn't work
			if (!$_SESSION["api"]["authorize"]) $this->authorize_password();

		}

		//so far so good, setup our info to be used by the api
		if ($_SESSION["api"]["authorize"])
		{

			//store their login info and settings in a session
			$this->setAccountInfo();

			//set the user's permission level based on individual perms and group membership
			$p = new PERM($this->accountId);
			$p->set();

			//set our user information from that which is returned from the function
			define("USER_ID",$_SESSION["api"]["accountInfo"]["id"]);
			define("USER_LOGIN",$_SESSION["api"]["accountInfo"]["login"]);
			define("USER_EMAIL",$_SESSION["api"]["accountInfo"]["email"]);
			define("USER_FN",$_SESSION["api"]["accountInfo"]["first_name"]);
			define("USER_LN",$_SESSION["api"]["accountInfo"]["last_name"]);

			$this->reset_failed_login_count();
			$this->update_activity();

			return true;
					
		} 
		else
		{

			//update the number of login failures
			$this->update_failed_login_attempts();

			return false;
			
		}
		
	}

	/**
		verifies the passed username and password against those in our database
		*/
	function authorize_password()
	{

		$ACCOUNT = new ACCOUNT();

		//user is trying to login, process the information
		if (!$this->login || (!$this->password && !$this->digestHash)) 
		{
			return false;
		}

		//re-enable the account if desired
		$this->time_unlock_account($this->login);

		//check to see if the user and password combo exist
		$accountInfo = $ACCOUNT->password_check($this->login,$this->password,$this->digestHash);

		//store our info in sessions for later
		if ($accountInfo) 
		{

			if (!$this->accountEnabled($accountInfo["id"]))
			{
				$this->throwError(_I18N_ACCOUNT_DISABLED);
			} 
			else 
			{

				$this->accountId = $accountInfo["id"];
				
				//set our session value so we do not get requeried.
				$_SESSION["api"]["authorize"] = "1";

				//record the login
				$this->logger->log($accountInfo["login"]." logged in",LOGGER_DEBUG,"AUTH");

				//if requested by the client to save a cookie for our auth, do it
				if ($this->writeCookie) $this->save_cookie();

			}
			
		} 
		else 
		{

			$this->throwError(_I18N_ACCOUNT_INVALID_USERNAME_PASSWORD);
			return false;		

		}
	
	}

	/**
		verifies stored cookie information against the key/hash combo stored in our database
		*/
	function authorize_cookie()
	{

		//no cookie saved to work with
		if (!$_COOKIE["authentication"]) return false;

		//get our cookie info	
		$info = explode(":",$_COOKIE["authentication"]);

		if (count($info)!=2) return false;

		$sql = "SELECT account_id FROM auth.cookies WHERE key='".$info[0]."' AND uuid='".$info[1]."' AND expires>'".time()."'";
		$results = $this->DB->single($sql);

		if ($results)
		{
			$a = new ACCOUNT($results["account_id"]);

			//get basic account info
			$accountInfo = $a->get();
			
			if (!$this->accountEnabled($accountInfo["id"]))
			{
				$this->throwError(_I18N_ACCOUNT_DISABLED);
			} 
			else 
			{

				$this->accountId = $accountInfo["id"];

				//set our session value so we do not get requeried.
				$_SESSION["api"]["authorize"] = "1";

				//record the login
				$this->logger->log($accountInfo["login"]." logged in with cookie",LOGGER_DEBUG,"AUTH");

			}
		
		}
		else
		{
			//for one reason or another, our cookie did not work.  delete it
			$this->delete_cookie();
		}
	
	}

	private function accountEnabled($aid)
	{
		$sql = "SELECT enable FROM auth.account_permissions WHERE account_id='".$aid."'";
		$info = $this->DB->single($sql);
	
		//if no entry, then they haven't been setup yet and we can proceed
		if (!$info || $info["enable"]=="t") return true;
		else return false;
		
	}

	/**
		creates a key/value combo using crypt using the login and a uuid,
		then stores in a cookie for session persistence
		*/
	function save_cookie()
	{

		$key = $this->crypt_cookie($this->login);
		$uuid = $this->crypt_cookie(uuid());
		$cookie = $key.":".$uuid;
	
		$expire = time() + (COOKIE_TIMEOUT * 86400);

		//remove "index.php" from the script name
		$path = $_SERVER["PHP_SELF"];
    $path = str_replace("index.php","",$path);
		$path = str_replace("api.php","",$path);  
        
		$domain = $_SERVER["SERVER_NAME"];

		//send only over secure site if necessary
		if (defined("HTTPS_ONLY")) $secure = true;
		else $secure = false;

		//set the cookie
		setcookie("authentication",$cookie,$expire,$path,$domain,$secure);	

		//store in the database
		$sql = "DELETE FROM auth.cookies WHERE account_id='".$this->accountId."';";
		$sql .= "INSERT INTO auth.cookies VALUES ('".$this->accountId."','".$key."','".$uuid."','".$expire."');";
		$this->DB->query($sql);
	
		if ($this->DB->error()) $this->throwError($this->DB->error());

	}

	/**
		deletes any current authentication cookie for the user
		*/
	function delete_cookie()
	{
	
		$expire = time() - 3600;

		//remove "index.php" from the script name
		$path = $_SERVER["PHP_SELF"];
    $path = str_replace("index.php","",$path);
		$path = str_replace("api.php","",$path);  
        
		$domain = $_SERVER["SERVER_NAME"];

		//set the cookie
		setcookie("authentication",null,$expire,$path,$domain,0);	

		if (isset($_SESSION["api"]["accountInfo"]))
		{
			$sql = "DELETE FROM auth.cookies WHERE account_id='".$_SESSION["api"]["accountInfo"]["id"]."';";
			$this->DB->query($sql);
		}
		
	}

	/**
		encrypts the strings used in our auth cookie
		*/
	function crypt_cookie($str)
	{

		if (CRYPT_SHA512 == 1)
		{
			$random = strtolower(str_replace("-","",uuid()));
			$salt = '$6$rounds=5000$'.$random.'$';
			$crypt = crypt($str,$salt);
		}
		else if (CRYPT_SHA256 == 1)
		{
			$random = strtolower(str_replace("-","",uuid()));
			$salt = '$5$rounds=5000$'.$random.'$';
			$crypt = crypt($str,$salt);
		}
		else if (CRYPT_BLOWFISH == 1)
		{
			$random = strtolower(str_replace("-","",uuid()));
			$salt = '$2a$07$'.$random.'$';
			$crypt = crypt($str,$salt);
		}
		//fallback on CRYPT_MD5
		else 
		{
			$random = strtolower(str_replace("-","",uuid()));
			$salt = '$1$'.$random.'$';
			$crypt = crypt($str,$salt);
		}
	
		return $crypt;
		
	}

	// this function will reset the number of login attempts to 0
	function reset_failed_login_count()
	{

		$sql = "UPDATE auth.account_permissions SET failed_logins=0,last_success_login=now() WHERE account_id='".$this->accountId."';";
		$this->DB->query($sql);

	}
	
	// this function will increment the number of login attempts
	function update_failed_login_attempts()
	{

		$ACCOUNT = new ACCOUNT();
		$aid = $ACCOUNT->loginToId($this->login);

		if ($aid)
		{
			$sql = "UPDATE auth.account_permissions SET failed_logins=(failed_logins+1) WHERE account_id='$aid'";
			$this->DB->query($sql);

			if (defined("ENABLE_ACCOUNT_LOCKOUT")) $this->lock_account($aid);	
		}
		
	}
	
	// this function will lock an account if the number of failed logins exceeds
	// the allowed number, account lockout is enabled, and so long as it is not an administrative account
	function lock_account($aid)
	{

		if ($aid)
		{

			// verify that the number of login attempts exceeds the allowed number
			$sql="SELECT failed_logins FROM auth.account_permissions WHERE account_id='$aid';";
			$failLogin = $this->DB->single($sql);

			if ( $failLogin["failed_logins"] >= ACCOUNT_LOCKOUT_ATTEMPTS )
			{
	
				// disable account and timestamp
				$sql = "UPDATE auth.account_permissions SET failed_logins_locked=TRUE,enable=FALSE,locked_time=now() WHERE account_id='$aid';";
				$this->DB->query($sql);

			}

		}

	}
	
	//this function will unlock an account after a specified period of time
	function time_unlock_account($login) 
	{
	
		if (defined("ACCOUNT_LOCKOUT_TIME") && ACCOUNT_LOCKOUT_TIME > 0)
		{

			$lockout_time=ACCOUNT_LOCKOUT_TIME." minutes";
			
			// see if this user has been locked out and if the lockout time has passed
			$sql = "SELECT * FROM auth.account_permissions WHERE account_id=(SELECT id FROM auth.accounts WHERE login='".$login."') AND failed_logins_locked=TRUE AND locked_time < now() - INTERVAL '$lockout_time'";
			$login_attempts = $this->DB->single($sql);
			
			if ($login_attempts["failed_logins_locked"]=="t")
			{
				$sql = "UPDATE auth.account_permissions SET failed_logins_locked=FALSE,enable=TRUE,locked_time=NULL WHERE account_id=(SELECT id FROM auth.accounts WHERE login='".$login."');";
				$this->DB->query($sql);
			}
		
		}

	}

	function update_activity()
	{

		$sql = "UPDATE auth.account_permissions SET last_activity='".date("Y-m-d H:i:s")."' WHERE account_id='".$this->accountId."'";
		$this->DB->query($sql);
	
	}

	/**
		stores our user's login info and settings in a session, if not done so already
		*/
	function setAccountInfo()
	{

		//bail if already done
		if (isset($_SESSION["api"]["accountInfo"]))
		{
			//set accountId because it wouldn't have been set by our login processors
			$this->accountId = $_SESSION["api"]["accountInfo"]["id"];
			return false;
		}
		
		//get the account information
		$a = new ACCOUNT($this->accountId);
		$accountInfo = $a->get();
	
		//not needed anymore
		unset($accountInfo["password"]);
		
		//merge in their personal settings
		$sql = "SELECT * FROM auth.account_config WHERE account_id='".$this->accountId."'";
		$config = $this->DB->single($sql);

		if ($config) $accountInfo = array_merge($accountInfo,$config);
		
		//store for later
		$_SESSION["api"]["accountInfo"] = $accountInfo;		
		
	
	}


	/**
		clears all our authentication information
		*/
	function logout()
	{
	
		$this->delete_cookie();
		
		@session_unset();
		@session_destroy();
		$_SESSION = null;
	
	}
	
}


	