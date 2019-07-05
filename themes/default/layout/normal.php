<?php
/*****************************************************************************************************

	body.inc.php

	This file displays the site including all side columns and logos

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

*****************************************************************************************************/
?>
<!DOCTYPE HTML>
<html>
<head>

<title>
<?php
if ($siteTitle) echo $siteTitle;
else echo $siteModInfo[$module]["module_name"]." - ".SITE_TITLE;
?>
</title>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<?php
/****************************************************
	our stylesheets and javascript files
****************************************************/

if (BROWSER=="ie" && floatval(BROWSER_VERSION) < 10)
{
  $css = THEME_PATH."/css-ie/core.css;";
  $css .= THEME_PATH."/css-ie/sidebar.css;";
  $css .= THEME_PATH."/css-ie/modal.css;";
  $css .= THEME_PATH."/css-ie/toolbar.css;";
  $css .= THEME_PATH."/css-ie/records.css;";
  $css .= THEME_PATH."/css-ie/eform.css;";
}
else
{
  $css = THEME_PATH."/css/core.css;";
  $css .= THEME_PATH."/css/sidebar.css;";
  $css .= THEME_PATH."/css/modal.css;";
  $css .= THEME_PATH."/css/toolbar.css;";
  $css .= THEME_PATH."/css/records.css;";
  $css .= THEME_PATH."/css/eform.css;";
  $css .= THEME_PATH."/css/misc.css;";
}

        
if ($modCss) $css .= $modCss;

includeStylesheet($css);

//our globals
include("jslib/globals.php");

$js = null;

//always include the english file.  That way if a variable gets missed in a translation
//during an upgrade, DocMGR will load w/o javascript errors
$js .= "lang/en/client.js;";

//load the appropriate client side language file
if ($_SESSION["api"]["accountInfo"]["language"]) $lang = $_SESSION["api"]["accountInfo"]["language"];
else $lang = DEFAULT_LANGUAGE;

//make sure the file exists, or throw an error
if ($lang!="en") $js .= "lang/".$lang."/client.js;";

//get the rest of them
$js .= "jslib/mootools-core-1.4.5.js;";
$js .= "jslib/mootools-more-1.4.0.1.js;";
$js .= "jslib/core.js;";
$js .= "jslib/xdate.js;";
$js .= "jslib/sidebar.js;";
$js .= "jslib/toolbar.js;";
$js .= "jslib/pulldown.js;";
$js .= "jslib/modal.js;";
$js .= "jslib/eform.js;";
$js .= "jslib/records.js;";
$js .= "jslib/record_filters.js;";
$js .= "jslib/notifications.js;";
$js .= "jslib/pager.js;";
$js .= "jslib/xml.js;";
$js .= "jslib/query.js;";
$js .= "jslib/proto.js;";
$js .= "jslib/string.js;";
$js .= "jslib/sitemenu.js;";
$js .= "javascript/common.js;";

if ($modJs) $js .= $modJs;
includeJavascript($js);

$onPageLoad = "SIDEBAR.setSizes();NOTIFICATIONS.load();".$onPageLoad;

//used by a module to directly inject something into the page header
if ($siteHeadStr) echo $siteHeadStr;

?>
</head>

<body onLoad="<?php echo $onPageLoad;?>">

<div id="siteModal"></div>

<div id="siteStatus">
  <img alt="" src="<?php echo THEME_PATH;?>/images/icons/loading.gif">
  <div id="siteStatusMessage"></div>
</div>
    
<div id="sitePage">

  <div id="siteHeader">
      <div id="siteHeaderImageContainer">
        <img onClick="SITEMENU.load()" id="siteHeaderImage" src="<?php echo THEME_PATH;?>/images/logo.png" border="0"/>
      </div>
      <div id="siteToolbar"></div>
      <div id="notificationsDiv" onClick="NOTIFICATIONS.open()"></div>
  </div>

  <div id="siteBody">

    <div id="siteSidebar"></div>
    <div id="siteContent">
      <?php echo $siteContent;?>
    </div>
    
  </div>
                                                                
</div>


</body>
</html>
