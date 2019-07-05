<?php

//make sure we are setup
if (file_exists("install/install.php")) include("install/install.php");

//call our main header file
include("apilib/header.php");

//setup our module and default theme
include("lib/setup.php");

//run our pre-authorization custom processes
if (file_exists("app/preauth.inc.php")) include("app/preauth.inc.php");

//do we authorize people in this site to access any module
if (defined("PROCESS_AUTH")) include("lib/auth.inc.php");

//for storing our main site content output
$siteContent = null;
$permError = null;

if (defined("USER_ID"))
{

	/****************************************************************************************
		make sure this user can access this module, then extract error message 
		if there is any. 
	*****************************************************************************************/

	$arr = null;
	
	//process our module permissions
	$arr = checkModPerm($module,BITSET);
	if (is_array($arr)) extract($arr);

	//run our post-authorization custom processing for this application
	if (file_exists("app/postauth.inc.php")) include("app/postauth.inc.php");

	//start processing our module if all is well permission-wise
	if ($permError) 
	{
	  //if this is an xml module, show xml with an error
	  die("You do not have permissions to access the module ".$module);
	} 

}

//get our path where everything for the module is stored	
$modPath = $siteModInfo[$module]["module_path"];
$modJs = null;
$modCss = null;
$siteTemplate = null;
$template = null;

//determine our process file and our display file
$process_path = $modPath."process.php";
$style_path = $modPath."stylesheet.css";
$js_path = $modPath."javascript.js";
$display_path = $modPath."display.php";
$function_path = $modPath."function.php";
$ie_style_path = $modPath."stylesheet-ie.css";
$moz_style_path = $modPath."stylesheet-moz.css";

//load any optional function files in the module directory
if (file_exists($function_path)) include($function_path);
if (file_exists($process_path)) include($process_path);

//these get called by our templated display file
if (file_exists($style_path)) $modCss .= $style_path.";";

//allow ie-specific stylesheets for overriding the standard module stylesheet
if (BROWSER=="ie" && floatval(BROWSER_VERSION) < 10 && file_exists($ie_style_path)) $modCss .= $ie_style_path.";";

//stock module javascript file
if (file_exists($js_path)) $modJs .= $js_path.";";

//define our display module if there is one
if (file_exists($display_path)) include($display_path);

//call our navbar utility if it exists
if (file_exists(THEME_PATH."/layout/navbar.inc.php")) include(THEME_PATH."/layout/navbar.inc.php");

//get our module template for display
if ($siteTemplate) 
{
  $template = $siteTemplate;
}
else if (isset($siteModInfo[$module]["template"]))
{
  $template = $siteModInfo[$module]["template"];
}
else
{
  $template = "normal";
}

//call logo file if available for login, otherwise call the template
$templateFile = THEME_PATH."/layout/".$template.".php";

//load the template file
if (file_exists($templateFile)) include($templateFile);
else die("Template $template does not exist");

