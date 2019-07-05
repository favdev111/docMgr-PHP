<?php

/****************************************************************************
  setup.php
  
  called by the main index.php file, sets up our browser information
  and loads our modules
****************************************************************************/

//get the users browser type
set_browser_info();

//Get our site layout if we have not already
if ($_SESSION["siteModList"] && $_SESSION["siteModInfo"] && !defined("DEV_MODE")) 
{
  $siteModList = &$_SESSION["siteModList"];
  $siteModInfo = &$_SESSION["siteModInfo"];
}
else 
{
  $siteModArr = loadSiteStructure("modules/");
  $_SESSION["siteModList"] = $siteModArr["list"];
  $_SESSION["siteModInfo"] = $siteModArr["info"];
  $siteModList = &$_SESSION["siteModList"];
  $siteModInfo = &$_SESSION["siteModInfo"];
}

//get our module information for our file includes 
$module = null;

if (isset($_POST["module"])) $module = $_POST["module"];  
elseif (isset($_GET["module"])) $module = $_GET["module"];
$GLOBALS["module"] = $module;

//here we will call the default module 
if (!$module) $module = DEFAULT_MODULE;

//setup our theme.look to see if this module is using a different theme first
if ($siteModInfo[$module]["theme"])
{
  define("THEME_PATH","themes/".$siteModInfo[$module]["theme"]);
} 
else
{

  if (!defined("SITE_THEME")) die("No theme is defined for the site");

  //create a define for referencing all our theme objects (css,layout,images)
  define("THEME_PATH","themes/".SITE_THEME);

}
