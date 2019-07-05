<?php

/******************************************************************************
  Indexer script.  It will only work if called from the root docmgr directory
******************************************************************************/

//set which DocMGR user id the script should run as.  defaults to 
//"admin" user
//define("USER_ID","1");
//define("USER_LOGIN","elawman");
//define("BITSET","1");

/*****************************************************************************
  end configurable options
*****************************************************************************/

/******************************************************************************
    preliminary configuration and variable setting
******************************************************************************/

//get our includes
//first call the config file to get our settings, call our base functions, and get our wrapper
require_once("apilib/header.php");
require_once("app/common.php");
require_once("app/common-docmgr.php");
require_once("app/openoffice.php");
require_once("app/client.php");

//so API knows we are called from a script
define("DOCMGR_SCRIPT","1");

$accountId = null;
$bitmask = null;

//find an administrative user to run as
$sql = "SELECT account_id,bitmask FROM auth.account_permissions WHERE enable='t' AND bitmask IS NOT NULL";
$results = $DB->fetch($sql);

for ($i=0;$i<$results["count"];$i++)
{
  if (PERM::is_set($results[$i]["bitmask"],ADMIN))
  {
    $accountId = $results[$i]["account_id"];
    $bitmask = $results[$i]["bitmask"];
    break;
  }
}

if ($accountId!=null)
{
  define("USER_ID",$accountId);
  define("BITSET",$bitmask);
  define("USER_LOGIN","docmgr-indexer");
}
else
{
  die("Could not find an administrative user to run as\n");
}

//setup which apps are available to docmgr
setExternalApps();

//register our autoloader so we can call
//api functions direct from the client
spl_autoload_register('client_autoload');

//allow indexing of a certain objectId.  This is for debugging only
if (in_array("--convert",$argv)) 
{

  //we are looking for the id passed after our parameter
  $key = array_search("--convert",$argv) + 1;
  $obj = $argv[$key];

  $content = file_get_contents("files/document/5/5/5.docmgr");
  
  $worker = "/www/docbeta/file.html";
  file_put_contents($worker,$content);
  
  //fire up openoffice
  $oo = new OPENOFFICE($worker,$obj);
  $newfile = $oo->convert("pdf");
 
  echo "Done => ".$newfile."\n";
               
  die;
  
}

