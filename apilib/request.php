<?php

/*****************************************************************************************************
  get our passed data.
  data can either be passed in xml via the apidata= parameter, or as a request variable.
  To pass arrays of data, either use field[] notation for request vars, or use the
  variable name twice in xml (i.e: <data><object_id>1</object_id><object_id>2</object_id></data>)
  All xml data must be encompassed in a root tag, like "<data>" in the above example
******************************************************************************************************/

require_once("apilib/apirequest.php");

//make sure magic quotes is disabled
if (get_magic_quotes_gpc()==1)
{
	$PROTO->add("error","Magic quotes is enabled.  It must be disabled in your php.ini file");
  return false;
}

//try to pull from apidata.  If nothing, default to request (for socket connections)
if ($_REQUEST["apidata"]) $apidata = $_REQUEST["apidata"];

//init our class and process the data
$ap = new APIREQUEST($apidata);

//make available for other classes
$GLOBALS["APIREQUEST"] = $ap;

$ap->process();

