<?php
/************************************************************************************************************
	ldap.php
	
	Holds account processing and search functions for
	an ldap database
	
	02-07-2005 - Fixed returnAccountInfo returning an error if it did not find an account Id (Eric L.)
	02-14-2005 - Split group info
	11-20-2005 - Stripped file down more and added support for an ldap map file

***********************************************************************************************************/

class ACCOUNT_LDAP
{

	public $accountId;
	protected $conn;
	protected $errorMessage;
	protected $DB;
	protected $map;

	function __construct($aid=null) 
	{

		$this->connect();
		$this->setupMap();
		
		if ($aid) $this->accountId = $aid;

		$this->DB = $GLOBALS["DB"];

	}

	function setupMap()
	{
	
		$this->map = array();

		//maps our database fields to their ldap counterparts		
		$this->map["id"] = LDAP_UIDNUMBER;
		$this->map["login"] = LDAP_UID;
		$this->map["first_name"] = LDAP_GIVENNAME;
		$this->map["last_name"] = LDAP_SN;
		$this->map["email"] = LDAP_MAIL;
		$this->map["work_phone"] = LDAP_TELEPHONENUMBER;
		$this->map["home_phone"] = LDAP_HOMETELEPHONENUMBER;
		$this->map["fax"] = LDAP_FACSIMILETELEPHONENUMBER;
		$this->map["mobile"] = LDAP_MOBILETELEPHONENUMBER;
		
	}

	/***************************************************
		connect to the database
	***************************************************/
	function connect() 
	{

		if ($this->conn) return true;

		$this->conn = ldap_connect(LDAP_SERVER,LDAP_PORT);
		ldap_set_option($this->conn, LDAP_OPT_PROTOCOL_VERSION, LDAP_PROTOCOL);
		$r = ldap_bind($this->conn,BIND_DN,BIND_PASSWORD);
			
	}

	function close() 
	{
	
		ldap_close($this->conn);
		$this->conn = null;
			
	}

	function search($addFilter=null)
	{

		$filter = "(&".LDAP_FILTER.$addFilter.")";

		//run the search
    $sr = ldap_search($this->conn,LDAP_BASE,$filter);

		//getthe records
		$res = ldap_get_entries($this->conn,$sr);     

		if ($res["count"]==0) 
		{
			return array();
		}
		else
		{
			unset($res["count"]);
			return $res;
		}
        	
	}

	function throwError($msg) 
	{
		$this->errorMessage = $msg;
	}
	
	function getError() 
	{
		return $this->errorMessage;
	}

	function get() 
	{

		$res = $this->search("(".LDAP_UIDNUMBER."=".$this->accountId.")");
		
		if ($res) return $res[0];
		else return false;
	
	}

	function maxId() 
	{

		$ids = array();
		$res = $this->search();

		//put all the account ids into an array
		unset($res["count"]);
		$num = count($res);
		for ($i=0;$i<$num;$i++) $ids[] = $res[$i][strtolower(LDAP_UIDNUMBER)][0];
		
		return max($ids);
		
	}

	function save($option)
	{

		//we will always be passed an account id, see if it exists in the database
		$info = $this->get();

		if ($info)
		{
			$this->update($option);
		}
		else
		{
			$this->insert($option);
		}

	}
                                      

	function insert($option) 
	{

		$arr = array();
		$result = array();
	
		// prepare our objectclass and common data
		$arr = array();
		$arr["objectclass"][0]="person";
		$arr["objectclass"][1]="organizationalPerson";
		$arr["objectclass"][2]="top";
		$arr["objectclass"][3]="inetOrgPerson";
		$arr["objectclass"][4]="posixAccount";
	
		//loop through our submitted data and assignt to the appropriate ldap field
		foreach ($this->map AS $key=>$field)
		{
			if (isset($option[$key])) $arr[$field] = $option[$key];
		}

		if ($option["first_name"] || $option["last_name"])
		{
			$fullName = trim($option["first_name"]." ".$option["last_name"]);
		}
		else
		{
			$fullName = $option["login"];
		}

		if ($fullName)
		{
			$arr[LDAP_CN] = $fullName;
			$arr[LDAP_GECOS] = $fullName;
		}
				
		//manadatory account info	
		$arr[LDAP_UID] = $option["login"];
		$arr[LDAP_UIDNUMBER] = $this->accountId;
		$arr[LDAP_GIDNUMBER] = DEFAULT_GID;

		$arr["homeDirectory"] = "/home/".$option["login"];
		$arr["loginShell"] = "/bin/bash";
		
		//what we'll store it as
		$dn = LDAP_UID."=".$option["login"].",".LDAP_CREATE_BASE;

		//insert the data
		if (!$this->addData($arr,$dn))
		{
			$this->accountId = null;
			$this->throwError("Error creating ldap account entry");
		} 

		return $this->accountId;
	
	}

	function update($option) 
	{

		$arr = array();
		$result = array();
	
		//loop through our submitted data and assignt to the appropriate ldap field
		foreach ($this->map AS $key=>$field)
		{
			if (isset($option[$key])) $arr[$field] = $option[$key];
		}

		if ($option["first_name"] || $option["last_name"])
		{
			$fullName = trim($option["first_name"]." ".$option["last_name"]);
		}
		else
		{
			$fullName = $option["login"];
		}

		if ($fullName)
		{
			$arr[LDAP_CN] = $fullName;
			$arr[LDAP_GECOS] = $fullName;
		}
		
		//manadatory account info	
		if ($option["login"]) $arr[LDAP_UID] = $option["login"];

		//update the account
		if (!$this->saveData($arr))
		{
			$this->accountId = null;
			$this->throwError("Error updating ldap account entry");
		} 

		return $this->accountId;
	
	}

	function delete() 
	{

		$info = $this->get();

		if (!ldap_delete($this->conn,$info["dn"])) 
		{
		
			$this->throwError("LDAP Account removal failed");
			$ret = false;
			
		}

		return $ret;

	}

	function addData($data,$dn)
	{

		//remove the nulls
		$data = arrayReduce($data);
		
		//dprint($dn."\n".print_r($data,1));
		//update the ldap database
		return ldap_add($this->conn,$dn,$data);
	
	}

	function saveData($data,$dn=null)
	{

		foreach ($data AS $key=>$val)
		{
			if (!$val) $data[$key] = array();
		}

		if (!$dn)
		{
			$info = $this->get();
			$dn = $info["dn"];
		}

		//update the ldap database
		return ldap_modify($this->conn,$dn,$data);
				 
	}

	function removeData($data)
	{

		$info = $this->get();
		$dn = $info["dn"];

		//update the ldap database
		return ldap_mod_del($this->conn,$dn,$data); 
	
	}

	function cryptPassword($password,$salt = null)
	{

		//sha1 hashing	
		if (LDAP_CRYPT=="{SHA}")
		{
			$crypt = base64_encode(pack("H*", sha1($password)));
		}
		//md5 hashing
		else if (LDAP_CRYPT=="{MD5}")
		{
			$crypt = base64_encode(pack("H*", md5($password)));
		}
		else if (LDAP_CRYPT=="{CLEARTEXT}")
		{
			$crypt = $password;
		}
		//passed a salt to compare our password against.  usually this usually a stored
		//copy of the password that crypt can pull the salt from
		else if ($salt)
		{
			$crypt = crypt($password,$salt);
		}
		else
		{

			//specified a salt config for new passwords
			if (defined("LDAP_CRYPT_SALT")) 
			{
				$salt = str_replace("-","",sprintf(LDAP_CRYPT_SALT,uuid()));
				$crypt = crypt($password,$salt);
			}
			else
			{
				$crypt = crypt($password);
			}
						
		}
	
		return $crypt;
	
	}
	
	function savePassword($password) 
	{

		$arr = array();
		$result = array();
	
		if ($this->accountId==NULL) 
		{
			$this->throwError("The account id must be passed to update the account");
			return false;
		}

		if (!$password) 
		{
			$this->throwError("You must specify a password");
			return false;
		}

		//crypt the password following our ldap settings
		$arr[LDAP_USERPASSWORD] = LDAP_CRYPT.$this->cryptPassword($password);

		//store the password in clear form if desired.  :(
		//legacy feature used in TEA system only
		if (defined("LDAP_CLEARPASSWORD")) $arr[LDAP_CLEARPASSWORD] = $password;

		//save the info
		$ret = $this->saveData($arr);

		if (!$ret)
		{		
			$this->throwError("Failed to update LDAP account password");
		}		

		return $ret;
		
	}

	function syncToDB($password=null)
	{

		$db = new ACCOUNT_DB($this->accountId);	

		$info = $this->get();
		$opt = null;

		//remap ldap fields to match our database fields
		foreach ($this->map AS $dbField=>$ldapField)
		{
			$opt[$dbField] = $info[strtolower($ldapField)][0];
		}

		//push our ldap info to the database
		$db->save($opt,1);

		//if passed a password, update it too	
		if ($password)
		{
			$db->savePassword($password);
		}

		if ($db->getError())
		{
			$this->throwError($db->getError());
		}
	
	}

	//compares the passed password to that in the ldap db
	function password_check($login,$password,$digestHash=null) 
	{

		$list = $this->search("(".LDAP_UID."=".$login.")");
	
		if (count($list) > 0)
		{

			//if passed the digest hash, just hit the database directly to fetch it
			if ($digestHash)
			{

				$sql = "SELECT id FROM auth.accounts WHERE login='".$login."' AND digest_hash='".$digestHash."'";
				$info = $this->DB->single($sql);

				if ($info)
				{
					$this->accountId = $info["id"];
					$this->syncToDB();
					return $this->accountId;
				}
				else
				{
					return false;
				}

			}
			//otherwise proceed as normal
			else
			{

				$hashes = array("{SSHA}","{SHA}","{SMD5}","{MD5}","{CRYPT}","{CLEARTEXT}");

				//fetch the password and remove any CRYPT ldap headers
				$storedPassword = $list[0][strtolower(LDAP_USERPASSWORD)][0];	
				$storedPassword = str_replace($hashes,"",$storedPassword);					

				//get the salt and encrypt the passed password
				$cryptpw = $this->cryptPassword($password,$storedPassword);

				//return info if we have a match
				if ($cryptpw == $storedPassword) 
				{
	
					$this->accountId = $list[0][strtolower(LDAP_UIDNUMBER)][0];
	
					//they entered a valid password, sync our ldap record to the local database
					$this->syncToDB($password);

					return $this->accountId;
	
				}
				else return false;

			}

		} 
		else return false;
	
	}

}


	