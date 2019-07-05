<?php


/*********************************************************************************
  FILE:     common-docmgr.php
  PURPOSE:  contains common functions for docmgr interface
*********************************************************************************/

function checkAppAvail($app) 
{

  //if the app is an absolute path, just return true
  if ($app[0]=="/") return true;

  //extract the app from it's command line args
  $app = extractApp($app);

	$str = `which "$app" 2>/dev/null`;

	//if which returns nothing, it couldn't find the app
	if (!$str) return false;

	$pos = strrpos($str,"/");
	$str = trim(substr($str,0,$pos));

	//make sure the app's path is in apache's path
	$pathArr = explode(":",$_SERVER["PATH"]);

	if (in_array($str,$pathArr)) return true;
	else return false;

}

function checkRequiredApp($app) {

        //if the app is an absolute path, just return true
        if ($app[0]=="/") return true;

        //extract the app from it's command line args
        $app = extractApp($app);

	$str = `which "$app" 2>/dev/null`;
	$error = null;

	//if which returns nothing, it couldn't find the app
  	if (!$str) $error = "1";
  	else {
  	  $pos = strrpos($str,"/");
  	  $str = trim(substr($str,0,$pos));

  	  //make sure the app's path is in apache's path
  	  $pathArr = explode(":",$_SERVER["PATH"]);

  	  if (!in_array($str,$pathArr)) $error = "1";;
        }

	if ($error) {
	  $message = "Error!  The application <b>$app</b> could not be found in ".$_SERVER["PATH"]."<br>
	              This application is required by DocMGR to run.<br><br>
	              ";
          die($message);
        }
}

//this function extracts the core app name from an absolute or relative path, and 
//the parameters pass to the app
function extractApp($app) {

  $arr = explode(" ",$app);
  return $arr[0];
  

}

//this function determines if our optional applications are available to docmgr
function getExternalApps() {

  $arr = array();

  //figure out which of our external progs exist
  if (checkAppAvail(APP_OCR)) $arr["ocr"] = 1;
  if (checkAppAvail(APP_WGET)) $arr["wget"] = 1;
  if (class_exists("ZipArchive")) $arr["zip"] = 1;

  if (checkAppAvail(APP_MOGRIFY)) $arr["mogrify"] = 1;
  if (checkAppAvail(APP_CONVERT)) $arr["convert"] = 1;
  if (checkAppAvail(APP_MONTAGE)) $arr["montage"] = 1;
  if ($arr["mogrify"] && $arr["convert"] && $arr["montage"]) $arr["imagemagick"] = 1;

  if (checkAppAvail(APP_PDFTOTEXT)) $arr["pdftotext"] = 1;
  if (checkAppAvail(APP_PDFIMAGES)) $arr["pdfimages"] = 1;
  if ($arr["pdftotext"] && $arr["pdfimages"]) $arr["xpdf"] = 1;  

  if (checkAppAvail(APP_TIFFINFO)) $arr["tiffinfo"] = 1;
  if (checkAppAvail(APP_TIFFSPLIT)) $arr["tiffsplit"] = 1;
  if ($arr["tiffinfo"] && $arr["tiffsplit"]) $arr["libtiff"] = 1;

  if (checkAppAvail(APP_SENDMAIL) || function_exists("imap_8bit")) $arr["email"] = 1;

  if (checkAppAvail(APP_CLAMAV)) $arr["clamav"] = 1;

  return $arr;

}

function setExternalApps() {

	if (!isset($_SESSION["api"]["setApps"])) {
	
     //check to make sure if we have these required programs.  If not, die
     checkRequiredApp(APP_PHP);

     //make sure they are not using the cgi version of php
     $app = APP_PHP." -v";
     $str = `$app`;
     if (!strstr($str,"(cli)")) die("You are not using the cli version of php.  Please either install php-cli");

     $_SESSION["api"]["setApps"] = getExternalApps();	

	}

	//url download support
	if ($_SESSION["api"]["setApps"]["wget"]) define("URL_SUPPORT","1");

	//zip archive support
	if ($_SESSION["api"]["setApps"]["zip"]) define("ZIP_SUPPORT","1");

	//ocr support
	if (($_SESSION["api"]["setApps"]["ocr"] && 
	  $_SESSION["api"]["setApps"]["libtiff"] && 
	  $_SESSION["api"]["setApps"]["imagemagick"])) define("OCR_SUPPORT","1");

  if ($_SESSION["api"]["setApps"]["xpdf"]) define("PDF_SUPPORT","1");

	//thumbnail support
	if ($_SESSION["api"]["setApps"]["imagemagick"]) define("THUMB_SUPPORT","1");

	//tiff handling support
	if ($_SESSION["api"]["setApps"]["libtiff"]) define("TIFF_SUPPORT","1");
	
	//antivirus support
	if ($_SESSION["api"]["setApps"]["clamav"]) define("CLAMAV_SUPPORT","1");

	if ($_SESSION["api"]["setApps"]["email"]) define("EMAIL_SUPPORT","1");

	return true;
	
}
