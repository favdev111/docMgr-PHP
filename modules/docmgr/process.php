<?php

$objectId = null;
$objectPath = null;

//handle objects passed from the URL
if (isset($_REQUEST["objectId"])) $objectId = $_REQUEST["objectId"];
else if (isset($_REQUEST["recordId"])) $objectId = $_REQUEST["recordId"];

//if we have an object, get its parent path to be browsed
if ($objectId!=null)
{

  $d = new DOCMGR_OBJECT($objectId);
  $info = $d->getInfo();
  
  if (isset($info["object_path"])) $objectPath = $info["object_path"];
  
}
