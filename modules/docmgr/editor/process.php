<?php

$objectId = $_REQUEST["objectId"];
$parentPath = $_REQUEST["parentPath"];

//make sure there's temp storage available for uploaded files
if ($objectId) 
{

  //make sure this file is a storage folder
  $d = new DOCMGR_OBJECT();
  $d->createStorage($objectId);

} 
else 
{

  //make sure this file is a storage folder
  $d = new DOCMGR_OBJECT();
  $d->createTemp();

}

