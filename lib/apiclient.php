<?php

/**********************************************************************
  CLASS: API
  PURPOSE:	A standalone library that code that exists outside
            of the tea module system (like scripts) can use to
            access the api
  INPUTS:		login -> api login
            password -> api password
**********************************************************************/

//get our core includes
ini_set("include_path",".:".ALT_FILE_PATH);

spl_autoload_register('apiclient_autoload');

//get header file
include("apilib/header.php");

class APICLIENT
{

  private $login;
  private $password;
  private $sessid;
  
  function __construct($login,$password)
  {

  	global $DB,$CDB,$PROTO,$logger;
  
    $_REQUEST["login"] = $login;
    $_REQUEST["password"] = $password;

    //get header file
    include("apilib/preauth.php");
	
    //do we authorize people in this site to access any module
    include("apilib/auth.php");
    $a = new AUTH();
		
		//stop here if login error
		if ($err = $a->getError()) 
		{
			$this->throwError($err);
		} 
		else if (!$_SESSION["api"]["authorize"]) 
		{
			
			//if not authorized but don't have an error message, show a generic error 
			$this->throwError("Could not log into api");
		
		} 
		else
		{

			//not sure if we still need this or not
			define("THEME_PATH","themes/".SITE_THEME);

		}

  }

  function throwError($err)
  {
  	$this->errorMessage = $err;
	}
	
	function getError()
	{
		return $this->errorMessage;
	}

  function call($opt)
  {

  	global $DB,$CDB,$PROTO,$logger;

  	$PROTO->setData(null);
  	$_REQUEST = null;
  	
  	if ($opt)
  	{
  	
  	  $keys = array_keys($opt);
  	
  	  foreach ($keys AS $key)
  	  {
  	
  	    //convert our html entities to proper characters, and then sanitize them
  	    $opt[$key] = html_entity_decode($opt[$key],ENT_QUOTES);
  	    $_REQUEST[$key] = sanitize($opt[$key]);
  	  
      }

    }
    
		//for handling requests
		include("apilib/request.php");

		$data = $PROTO->getData();
		
		return $data;
		
	}

}

/***********************************************
  FUNCTION:	__autoload
  PURPOSE:  autoload classes for the api
***********************************************/
function apiclient_autoload($class_name)
{

  //don't allow any non-alphanumeric info since class names can be passed from the url
  $class = preg_replace("/^a-z0-9_-/i","",$class_name);
 
  //if they don't match, let the user know so they don't get confused
  if ($class!=$class_name)
  {
    throw new Exception('Class '.$class_name.' not exists');
  }
  
  $cn = strtolower($class_name);
  $file = ALT_FILE_PATH."/apilib/lib/".str_replace("_","/",$cn.".php");

  if (!file_exists($file)) 
  {
    throw new Exception('Class '.$class_name.' not exists');
  }
  
  require_once($file);

}


