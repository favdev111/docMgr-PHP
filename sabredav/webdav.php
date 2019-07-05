<?php

/******************************************
  BEGIN CONFIGURABLE OPTIONS
******************************************/


//snag our docmgr options
include("../config/config.php");
include("../apilib/header.php");

//for later
$GLOBALS["DOCMGR"] = null;

//load our class register
spl_autoload_register('edev_autoload_sabre');

$baseURI = "/";
$tmpDir = '/tmp/tmpdata';

// Files we need
require_once 'vendor/autoload.php';

//file_put_contents("/tmp/header",print_r($_SERVER,1)."==============\n",FILE_APPEND);

// Create the root node
$root = new \Sabre\DAV\Tree\DOCMGR($baseURI);

// The rootnode needs in turn to be passed to the server class
$server = new \Sabre\DAV\Server($root);

if (isset($baseURI)) $server->setBaseUri($baseURI);

// Support for LOCK and UNLOCK
$lockBackend = new \Sabre\DAV\Locks\Backend\DOCMGR($tmpDir);
$lockPlugin = new \Sabre\DAV\Locks\Plugin($lockBackend);
$server->addPlugin($lockPlugin);

// Support for html frontend
$browser = new \Sabre\DAV\Browser\Plugin();
$server->addPlugin($browser);

// Automatically guess (some) contenttypes, based on extesion
$server->addPlugin(new \Sabre\DAV\Browser\GuessContentType());

// Authentication backend
$authBackend = new \Sabre\DAV\Auth\Backend\DOCMGR(null);
$auth = new \Sabre\DAV\Auth\Plugin($authBackend,'SabreDAV');
$server->addPlugin($auth);

// Temporary file filter
$tempFF = new \Sabre\DAV\TemporaryFileFilterPlugin($tmpDir);
$server->addPlugin($tempFF);

// And off we go!
$server->exec();




// Make sure this setting is turned on and reflect the root url for your WebDAV server.
// This can be for example the root / or a complete path to your server script.  you probably
//don't need to change this
//$baseUri = '/';

/******************************************
  END CONFIGURABLE OPTIONS
******************************************/

/*
//docmgr classes need this
define("BASE_URI",$baseUri);

//sabre needs this
set_include_path(SITE_PATH."/sabredav/lib/Sabre" . PATH_SEPARATOR . get_include_path()); 


// Files we need
require_once 'autoload.php';
require_once 'apilib/auth.php';

// Create the parent node
$publicDirObj = new Sabre_DAV_DOCMGR_Directory($baseUri);

// Now we create an ObjectTree, which dispatches all requests to your newly created file system
$objectTree = new Sabre_DAV_ObjectTree($publicDirObj);

// The object tree needs in turn to be passed to the server class
$server = new Sabre_DAV_Server($objectTree);
$server->setBaseUri($baseUri);

// Support for LOCK and UNLOCK 
$lockBackend = new Sabre_DAV_Locks_Backend_DOCMGR($tmpDir);
$lockPlugin = new Sabre_DAV_Locks_Plugin();
$server->addPlugin($lockPlugin);

// Support for html frontend
$browser = new Sabre_DAV_Browser_Plugin();
$server->addPlugin($browser);


// Authentication backend
$authBackend = new Sabre_DAV_Auth_Backend_DOCMGR();
$auth = new Sabre_DAV_Auth_Plugin($authBackend,'SabreDAV');
$server->addPlugin($auth);

// And off we go!
$server->exec();

*/


/***********************************************
  FUNCTION:	__autoload
  PURPOSE:  autoload classes for the api
***********************************************/
function edev_autoload_sabre($class_name)
{

  //bail on Sabre's class calls
  if (strstr($class_name,"\\")) return false;

  //don't allow any non-alphanumeric info since class names can be passed from the url
  $class = preg_replace("/^a-z0-9_-/i","",$class_name);
 
  //if they don't match, let the user know so they don't get confused
  if ($class!=$class_name)
  {
    throw new Exception('Class '.$class_name.' not exists');
  }
  
  $cn = strtolower($class_name);
  $file = SITE_PATH."/apilib/lib/".str_replace("_","/",$cn.".php");

  if (!file_exists($file)) 
  {
    throw new Exception('Class '.$class_name.' not exists');
  }
  
  require_once($file);

}

