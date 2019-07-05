<?php

spl_autoload_register('edev_autoload');

if (array_key_exists("viewobj",$_REQUEST))
{
  require_once("lang/".DEFAULT_LANGUAGE."/api.php");

  dmObjectView($_REQUEST["viewobj"]);

}

/***********************************************
  FUNCTION:	__autoload
  PURPOSE:  autoload classes for the api
***********************************************/
function edev_autoload($class_name)
{

  global $PROTO;

  //don't allow any non-alphanumeric info since class names can be passed from the url
  $class = preg_replace("/^a-z0-9_-/i","",$class_name);
 
  //if they don't match, let the user know so they don't get confused
  if ($class!=$class_name)
  {
    return false;
  }
  
  $cn = strtolower($class_name);
  $file = "apilib/lib/".str_replace("_","/",$cn.".php");

  if (!file_exists($file)) 
  {
    return false;
  }
  
  require_once($file);

}

function dmObjectView($link)
{
 
  global $DB,$PROTO;
  
  //first clear out all expired links
  $sql = "DELETE FROM docmgr.object_link WHERE expires < '".date("Y-m-d H:i:s")."'";
  $DB->query($sql);

  $sql = "SELECT object_id,account_id FROM docmgr.object_link WHERE link='$link'";
  $info = $DB->single($sql);

  if (!$info) die("This link is no longer valid");
  else
  { 

    //get user's account inf
    $a = new ACCOUNT($info["account_id"]);
    $ainfo = $a->getInfo();

    //for the api to use
    define("USER_ID",$info["account_id"]);
    define("USER_LOGIN",$ainfo["login"]); 

    $d = new DOCMGR_OBJECT(array("object_id"=>$info["object_id"]));
    $d->get();

    if ($d->getError()) die(print_r($d->getError()));

  } 

} 
