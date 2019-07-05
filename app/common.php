<?php

/*********************************************************************************
  FILE:			common.php
  PURPOSE:	contains functions common across the application interface
*********************************************************************************/


/*********************************************************
  FUNCTION: getFieldInfo
  PURPOSE:  returns all info from our xml file for
            the current forms
  RETURNS:  associative array with form html name as key
*********************************************************/

function getFieldInfo($file=null,$deffile=null) 
{

  if (!$file) 		$file = $_REQUEST["file"];
  if (!$deffile) 	$deffile = $_REQUEST["deffile"];

  $formArr = array();

  //sanity checking
  if (!$file) die("No file template specified");
  if ($file[0]=="/") die("Cannot use absolute paths in the filename");
  if (strstr($file,"..")!=null) die("Cannot use \"../\" in the filename");
  if (!file_exists($file)) die("File \"".$file."\" does not exist");
  $str = file_get_contents("$file");

  //load our master file
  if ($deffile) 
  {

    if ($deffile[0]=="/") die("Cannot use absolute paths in the definition filename");
    if (strstr($deffile,"..")!=null) die("Cannot use \"../\" in the definition filename");
    $defstr = file_get_contents($deffile);

    //create defforms
    $defdom = new DOMDocument();
    $defdom->loadXML($defstr);

    $defforms = $defdom -> getElementsByTagName("form");
    $defarr = array();

    //populate our defform array
    foreach ($defforms AS $defform) 
    {

      $defchildren = $defform -> childNodes;
      $arr = array();

      //store in an array for later access
      foreach ($defchildren AS $defchild) 
      {
        if ($defchild->nodeType!=1) continue;
        $arr[$defchild->nodeName] = $defchild->textContent;
      }

      //the key for looking this up later
      $key = &$arr["name"];
      $defarr[$key] = $arr;

    }  

  }

  //convert simple to dom so we can work with it
  //$dom = dom_import_simplexml($xml);
  $dom = new DOMDocument();
  $dom->loadXML($str);

  //get all forms
  $forms = $dom -> getElementsByTagName("form");

  $temparr = array();

  //populate our form array
  foreach ($forms AS $form) 
  {

    $children = $form -> childNodes;
    $arr = array();

    //store in an array for later access
    foreach ($children AS $child) 
    {

			//if we have a deffile and it's a single node, then it's a linker to a deffile definition
	    if ($deffile && $child->nodeType==3 && $child->textContent) 
	    {

	    	$temparr[] = trim($child->textContent);
				break;
				
	    } else 
	    {

      	//process regular nodes here
      	if ($child->nodeType!=1) continue;
      	$arr[$child->nodeName] = $child->textContent;
      	
			}

    }

    //no deffile, store the form information in a keyed array using the form's name as the key
    if (!$deffile) 
    {

	    //the key for looking this up later
	    $key = &$arr["name"];
	    $temparr[$key] = $arr;

		}
		
  }  

  //now if there's a deffile, loop through and merge form information from the definition file
  //into the forms listed in our form file
  if ($deffile) 
  {

  	//store the forms info from its def using the form name as the key
  	foreach ($temparr AS $fn) $formArr[$fn] = $defarr[$fn];

	//otherwise return as is  
  } else $formArr = $temparr;
	
	return $formArr;

}

/*********************************************************
	FUNCTION: simpleXMLQuery
	PURPOSE:	returns posted data which matches specified
						forms in our xml file in sql query form
	INPUT:		valid output from getFieldInfo
	RETURNS:	associative array ready for dbInsertQuery
*********************************************************/

function simpleXmlQuery($formArr,$data=null) 
{

	if (!$data) $data = $_REQUEST;

	//first save all the simple data.text fields and single linked forms
	$keys = array_keys($formArr);
	
	//form types that do not require us to query a database for their possible values
	$dataforms = array("checkbox","age","cobroker","pricerange");
	
	$opt = array();
	
	foreach ($formArr AS $form) 
	{
	
		if (in_array($form["type"],$dataforms) || $form["save_table"]) continue;
	
		//data is the field name, while $_REQUEST[html_name] is our submitted data
		$datakey = $form["data"];
		$namekey = $form["name"];
		$opt[$datakey] = $data[$namekey];
	
	}
	
	return $opt;
	
}

function returnAccountName($id) 
{

  $info = returnAccountInfo($id);
  if ($info) return $info["first_name"]." ".$info["last_name"];
  else return false;
      
}

/*********************************************************************
  FUNCTION: outputFile
  PURPOSE:outputs passed file to the browser
*********************************************************************/

function outputFile($path,$filename=null) {

  if (!$filename) 
  {
  	$arr = explode("/",$path);
  	$filename = array_pop($arr);
  }
  
  // send headers to browser to initiate file download
  header ("Content-Type: application/octet-stream");
  header ("Content-Type: application/force-download");
  header ("Content-Length: ".filesize($path));
  header ("Content-Disposition: attachment; filename=\"$filename\"");
  header ("Content-Transfer-Encoding:binary");
  header ("Cache-Control: must-revalidate, post-check=0, pre-check=0");
  header ("Pragma: public");

  readfile($path);
  die;

}


/**************************************************************
  FUNCTION: outputPDF
  PURPOSE:converts letter html to pdf and outputs to the browser
  INPUT:str -> letter html
**************************************************************/
function outputPDF($str,$name=null) {

	//create a temp file
	$rand = rand();
	$randhtml = TMP_DIR."/".$rand.".html";
	$randpdf = TMP_DIR."/".$rand.".pdf";
	file_put_contents($randhtml,$str);
	
	//init oo and extract our content
	$oo = new OPENOFFICE($randhtml);
	
	//convert the file to pdf and get the content
	$pdffile = $oo->convert("pdf");
	
	if ($name) $realname = $name;
	else $realname = "Letter_".date("m-d-Y").".pdf";
	
	// send headers to browser to initiate file download
	header ("Content-Type: application/pdf");
	header ("Content-Type: application/force-download");
	header ("Content-Length: ".filesize($pdffile));
	header ("Content-Disposition: attachment; filename=\"$realname\"");
	header ("Content-Transfer-Encoding:binary");
	header ("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header ("Pragma: public");
	
	readfile($pdffile);
	die;
	
}

/*******************************************************************************
	makes sure we are accessing the site through the URL we used in the config.php
********************************************************************************/
function checkSiteURL()
{

	//redirect if we aren't at the right URL
	if (isset($_SERVER["HTTPS"])) $prefix = "https://";
	else $prefix = "http://";

	//not accessing correctly, redirect them
	if (!strstr(SITE_URL,$prefix.$_SERVER["SERVER_NAME"]))
	{
		header("Location: ".SITE_URL);
	}
  
}

