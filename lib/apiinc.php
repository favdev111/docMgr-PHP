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
include(ALT_FILE_PATH."/apilib/header.php");

//get header file
include(ALT_FILE_PATH."/apilib/preauth.php");
	
//do we authorize people in this site to access any module
include(ALT_FILE_PATH."/apilib/auth.php");


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


