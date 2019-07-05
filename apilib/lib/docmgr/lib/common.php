<?php

/********************************************************************************************

	Filename:
		common.inc.php
      
	Summary:
		this file contains functions common to all modules in this application.
		They should still be somewhat generic
            
	Modified:
              
		09-02-2004
			Code cleanup.  Moved functions that don't belong out
                        
*********************************************************************************************/

function returnCatOwner($info,$id,$pass_array,$owner = null) {

	if (!$pass_array) $pass_array[] = $id;

	//see if there is an owner for this key
	$key = array_search($id,$info["id"]);

	if (!$owner) $owner = $info["parent_id"][$key];

	//this exits if we are at the top.  
	//it now also exits if a category owns itself.  This should not happen, and will 
	//crash the webserver in a neverending loop if not checked here
	if ($owner!=0 && $owner!=$id) 
	{

	  //if the owner is already in there, we're looping back on our self.  bail
	  if (!in_array($owner,$pass_array)) 
	  {
  		$pass_array[] = $owner;
  		$pass_array = returnCatOwner($info,$owner,$pass_array);
    }
    
	}
	return $pass_array;
		
}

//log an event for an object in the database
function logEvent($logType,$objectId,$data = null, $accountId = null) {

  global $DB;
  
	if (defined("USER_ID")) $accountId = USER_ID;
	else $accountId = $accountId;

	if (!$accountId) $accountId = "0";
	
	$opt = null;
	$opt["object_id"] = $objectId;
	$opt["log_type"] = $logType;
	$opt["account_id"] = $accountId;
	$opt["log_time"] = date("c");
	$opt["ip_address"] = $_SERVER["REMOTE_ADDR"];
	
	//optional data for the log
	if ($data) $opt["log_data"] = $data;
	$DB->insert("docmgr.dm_object_log",$opt);

}

function returnLoglist() {

	$data = file_get_contents("config/logtypes.xml");
	return parseGenericXml("log_type",$data);

}

function returnLogType($logArr,$logType) {

	//get out if we don't have an array of possible logs
	if (!$logArr) return false;
	
	if (!in_array($logType,$logArr["link_name"])) return false;

	$langtext = "_LT_".$logType;
	
	if (defined($langtext)) $text = constant($langtext);
	else {
	
		$key = array_search($logType,$logArr["link_name"]);
		$text = $logArr["name"][$key];

	}

	return $text;	

}

//return a query to filter our objects to only allow those a non-admin can see
function permString() 
{

	$sql = "(";

	//if there is an entry for a group this user belongs to, they can see the object.
	if (defined("USER_GROUPS") && strlen(USER_GROUPS)>0)
		$sql .= " group_id IN (".USER_GROUPS.") OR ";

	$sql .= " account_id='".USER_ID."' OR ";

	//set default permissions for a file if no perms are set
	if (DOCMGR_UTIL_OBJPERM_LEVEL=="strict" || PERM::check(GUEST_ACCOUNT,1)) 
		$sql .= " object_owner='".USER_ID."')";
	else
		$sql .= " bitmask ISNULL)";

	return $sql;

}

/******************************************************************************
	scan a file for a virus.  this returns "clean" if nothing was found.
	it returns the name of the virus if an infection is found.  If there
	is a scan error, it returns false
*******************************************************************************/
function clamAvScan($filepath) {

	if (!defined("CLAMAV_SUPPORT")) return false;

	$app = APP_CLAMAV;
	$str = `$app --infected "$filepath"`;
	
	//return false if there is a scanning error
	if (strstr($str,"Scanned files: 0")) return false;

	//if no infected files are found, return true;
	if (strstr($str,"Infected files: 0")) return "clean";
	else 
	{
	
		//viruses were found, display the found virus information
		$pos = strpos($str,"----------- SCAN SUMMARY -----------");
		$vf = trim(substr($str,0,$pos));

		$pos = strpos($vf,":") + 1;
		$vf = _VIRUS_WARNING."! ".substr($vf,$pos);					

		return $vf;
	
	}
}

/****************************************************************************
	this function compares the md5 sum of the file we're accessing
	to the stored value created at the time of file upload.  If
	the values do not match, we return false.
****************************************************************************/
function fileChecksum($id,$filepath) {

  global $DB;
  
	//sanity checking
	if (!$id) return false;
	if (!$filepath) return false;
	if (!is_file($filepath)) return false;

	//get the stored md5sum
	$sql = "SELECT md5sum FROM docmgr.dm_file_history WHERE id='$id';";
	$info = $DB->single($sql);

	//get the md5sum for the file we're trying to access
	$md5sum = md5_file($filepath);

	//make sure values exist for both
	if (!$md5sum || !$info["md5sum"]) return false;

	//return true if they match
	if ($md5sum==$info["md5sum"]) return true;
	else return false;
	
}

/**************************************************************************
	This function creates a checksum.md5 file with the path
	of the file and its checksum.  it returns the path
	to the checksum file if successful, false on failure
**************************************************************************/
function createChecksum($id,$filename) {

  global $DB;

	//sanity checking
	if (!$id) return false;
	if (!$filename) return false;

	//get the stored md5sum
	$sql = "SELECT md5sum FROM docmgr.dm_file_history WHERE id='$id';";
	$info = $DB->single($sql);

	$md5sum = $info["md5sum"];

	//create a temp directory for our user
	$dir = TMP_DIR."/".USER_LOGIN;
	$file = $dir."/checksum.md5";
	
	if (!is_dir("$dir")) mkdir("$dir");

	$str = $md5sum."  ./".$filename."\n";

	//make sure the file doesn't already exist
	@unlink($file);

	$fp = fopen("$file",w);
	fwrite($fp,$str);
	fclose($fp);

	return "$file";
	
}

//checks to see if a program with the passed pid is running
function isPidRunning($pid) {

    if (!$pid) return false;

    $str = `ps --no-headers --pid $pid`;
     
    if (strstr($str,$pid)) return true;
    else return false;
       
}

//checks to see if a program of the passed name is running       
function checkIsRunning($app) {

  $cmd = "ps aux | grep \"".$app."\" | grep -c -v grep";
  $num = `$cmd`;

  if ($num > 0) return true;
  else return false;

}

//runs a program in the background
function runProgInBack($prog,$file = null) {

  //if no file, create an output file
  if (!$file) $file = "/dev/null";

  //$pid = exec("$prog 1>/tmp/prog1 2>/tmp/prog2");

  //output errors to the console if debug is turned on
  if (defined("DEBUG") && DEBUG > 0) $pid = exec("$prog >> $file & echo \$!");
  else $pid = exec("$prog >> $file 2>/dev/null & echo \$!");

  return $pid;

}

function createTempFile($ext = null) {

  if (!$ext) $ext = "txt";

  if (defined("USER_ID")) $fn = TMP_DIR."/".USER_ID."_".rand().".".$ext;
  else  $fn = TMP_DIR."/".rand().".".$ext;

  //if the file exists, remove it and create a new one with open permissions
  if (file_exists($fn)) unlink($fn);
  
  //create our empty file
  $fp = fopen($fn,"w");
  fclose($fp);

  //set the permissions as open as possible.  This way if an external script
  //is run as root, we can remove it as the webuser later
  chmod($fn,0777);

  return $fn;

}

//reformats our inline document for proper display
function formatEditorStr($str) {
  
  //re-add session id.  also replace the "&" w/ an html entity
  $sess = "sessionId=".session_id();
  $str = str_replace("&[DOCMGR_SESSION_MARKER]","&amp;".$sess,$str);

  //just in case it was encoded
  $str = str_replace("%5BDOCMGR_SESSION_MARKER%5D",$sess,$str);
  
  //make sure we have a doctype
  $str = fixDoctype($str);

  return $str;

}

//removes the session id and cleans up other items for document saving
function cleanupEditorStr($str) {

  //make sure we have a doctype
  $str = fixDoctype($str);

  //remove the current session id
  $sess = "sessionId=".session_id();
  $str = str_replace($sess,"[DOCMGR_SESSION_MARKER]",$str);

  //fckeditor removes our & signs
  $str = str_replace("&amp;","&",$str);

  return $str;
  
}

//converts a string to an array that we can run php array functions on
function strtoarray($str) {

  if (!$str) return false;

  $arr = array();
  $len = strlen($str);

  for ($i=0;$i<$len;$i++) $arr[] = $str[$i];

  return $arr;

}


//check for an existing object with the new object's name
function checkObjName($name,$parentId,$objectId = null) {

  global $DB;  

  //first check to see if all our characters are valid
  if (defined("DISALLOW_CHARS")) 
  {
  
    //treat both strings as arrays, make sure no characters in name are in our checkstr array  
    //yes, I know strings are arrays.  I did it this way for cleaner code
    $checkArr = strtoarray(DISALLOW_CHARS);
    $nameArr = strtoarray($name);

    $len = strlen($name);
    for ($i=0;$i<$len;$i++) {
      if (in_array($nameArr[$i],$checkArr)) {
        define("ERROR_MESSAGE",_INVALID_CHAR_IN_NAME." ".DISALLOW_CHARS);
        define("ERROR_CODE","OBJECT_EXISTS");
        return false;
      }
    }
    
  }  

  //if we have an object with no parents, get the parents
  if ($parentId==NULL && $objectId) 
  {
  
    $sql = "SELECT parent_id FROM docmgr.dm_view_objects WHERE id='$objectId'";
    $list = $DB->fetch($sql,1);
    
    $parentId = $list["parent_id"];
  
  }

  //make sure parentId is an array before we continue
  if ($parentId==NULL) $parentId = "0";
  if (!is_array($parentId)) $parentId = array($parentId);

  $sql = "SELECT id FROM docmgr.dm_view_objects WHERE name='".$name."' AND parent_id IN (".implode(",",$parentId).")";
  
  //if objectId is passed, we are doing an update and want to make sure the updated name doesn't
  //exist with another object
  if ($objectId) $sql .= " AND id!='$objectId'";

  $info = $DB->single($sql);
  
  if ($info)
  {
    //get the name of the parents for the error message
    if (!$parentId[0])
      $parentName = _HOME;
    else 
    {
      
      $sql = "SELECT name FROM docmgr.dm_object WHERE id IN (".implode(",",$parentId).")";
      $info = $DB->fetch($sql,1);
      $parentName = implode("\" "._OR." \"",$info["name"]);

    }
    
    $msg = _OBJ_WITH_NAME." \"".$name."\" "._ALREADY_EXISTS_IN." \"".$parentName."\"";
    define("ERROR_MESSAGE",$msg);
    define("ERROR_CODE","OBJECT_EXISTS");

    return false;
       
  }

  return true;
       
}


//storeObjLevel inserts a record in the database with the two level ids
//the object will use when writing files to the filesystem
function storeObjLevel($objId,$level1,$level2) 
{

  global $DB;

  //this should never change for an object, but we'll pass a delete query just to be safe"
  $sql = "DELETE FROM docmgr.dm_dirlevel WHERE object_id='$objId';
          INSERT INTO docmgr.dm_dirlevel (object_id,level1,level2) VALUES ('$objId','$level1','$level2');";
  if ($DB->query($sql)) return true;
  else return false;
  
}

function getObjectDir($objId) 
{

  global $DB;

  //get the values for this object
  $sql = "SELECT level1,level2 FROM docmgr.dm_dirlevel WHERE object_id='$objId'";
  $info = $DB->single($sql);

  //get out if nothings found
  if (!$info) return false;

  //merge into a dir structure and return
  return $info["level1"]."/".$info["level2"];

}
 
//legacy function
function returnObjPath($objId) {
 
  return getObjectDir($objId);
 
}
 
function extractObjectsFromText($haystack) {

  $needle = "sessionId=[DOCMGR_SESSION_MARKER]&objectId=";
  $objects = array();
  
  while ( ($pos=strpos($haystack,$needle))!==FALSE )  {
  
    //cut off after this find
    $haystack = substr($haystack,$pos + strlen($needle));  

    //find next quote
    $endpos = strpos($haystack,"\"");  
    $object = substr($haystack,0,$endpos);
    $objects[] = $object;

  }

  return $objects;
}

