<?php
/********************************************************/
//        FILE: sanitize.php
// DESCRIPTION: Contains functions that handle
//              the preprocessing of the form
//              submitted data so that information
//              may be safely stored in the database.
//
//     HISTORY:
//              04-19-2006
//                  -File created.
//							04-07-2010
//                  -sanitze updated to use sanitizeArray
//                   to handle multi-dimensional arrays
/********************************************************/
/*********************************************************
//make the data safe to be inserted in the database.
//this will handle strings or multi-level arrays
*********************************************************/
function sanitize($obj,$es=null) {

    if (!$es) $es = array();

    if (is_array($obj)) 
      $obj = sanitizeArray($obj,$es);
    else 
      $obj = sanitizeString($obj);

    return $obj;

}

/*********************************************************
//actually perform the sanitation
*********************************************************/
function sanitizeString($str) {

    return pg_escape_string(trim(strip_tags($str)));

}
/*********************************************************
//cleans sanitize string for display
*********************************************************/
function stripsan($str) {

    return stripslashes(str_replace("''","'",$str));

}

/*********************************************************
//sanitizes all get,post,request, and cookie variables
*********************************************************/
function sanitizeRequest($es = null) {

    if (!$es) $es = array();

    //the request sg
    $keys = array_keys($_REQUEST);
    foreach ($keys AS $key) {
        //skip if the variable is marked for exemption
        if (in_array($key,$es)) continue;
        $_REQUEST[$key] = sanitize($_REQUEST[$key]);
    }

    //the post sg
    $keys = array_keys($_POST);
    foreach ($keys AS $key) {
        //skip if the variable is marked for exemption
        if (in_array($key,$es)) continue;
        $_POST[$key] = sanitize($_POST[$key]);
    }

    //the get sg
    $keys = array_keys($_GET);
    foreach ($keys AS $key) {
        //skip if the variable is marked for exemption
        if (in_array($key,$es)) continue;
        $_GET[$key] = sanitize($_GET[$key]);

    }

    //the cookie sg
    $keys = array_keys($_COOKIE);
    foreach ($keys AS $key) {

        //skip if the variable is marked for exemption
        if (in_array($key,$es)) continue;
        $_COOKIE[$key] = sanitize($_COOKIE[$key]);

    }

}


/*********************************************************
//make the data safe to be inserted in the database.
//this will handle multilevel arrays
*********************************************************/
function sanitizeArray($obj,$es=null) 
{

	if (!$es) $es = array();

	//nothing to do, bail
	if (!$obj || count($obj)==0) return array();

  //loop through and process	
	foreach ($obj AS $key=>$val)
  {
  	
	  //skip if the variable is marked for exemption
	  if (!is_numeric($key) && in_array($key,$es)) continue;

	  //if an array, resubmit for recursive processing 
	  if (is_array($val)) 
	    $obj[$key] = sanitizeArray($val,$es);

	  //sanitize the string
	  else 
	    $obj[$key] = sanitizeString($val);
 
	}

	return $obj;

}
