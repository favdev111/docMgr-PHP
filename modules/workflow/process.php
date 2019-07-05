<?php

$objectId = $_REQUEST["objectId"];
$workflowId = $_REQUEST["workflowId"];
$routeId = $_REQUEST["routeId"];
$action = $_REQUEST["action"];

//if passed an objectId, use it to make a new workflow
if ($_REQUEST["objectId"] && $_REQUEST["action"]=="newWorkflow")
{

  $objects = explode(",",$_REQUEST["objectId"]);
  $name = null;

  //get the name for each object  
  foreach ($objects AS $object)
  {

    //get the general object info
    $o = new DOCMGR_OBJECT($object);
    $info = $o->getInfo();

    //add the name  
    $name .= $info["name"].", ";
  
  }

  //remove hte trailing comma
  $name = substr($name,0,strlen($name)-2);

  $opt = null;
  $opt["name"] = $name;
  $opt["object_id"] = $objects;
  
  $o = new DOCMGR_WORKFLOW($opt);
  $workflowId = $o->save();

}

