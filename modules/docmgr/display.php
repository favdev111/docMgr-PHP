<?php
$modJs .= "modules/docmgr/js/nav.js;";
$modJs .= "modules/docmgr/js/browse.js;";
$modJs .= "modules/docmgr/js/search.js;";
$modJs .= "modules/docmgr/js/bookmarks.js;";
$modJs .= "modules/docmgr/js/view.js;";
$modJs .= "modules/docmgr/js/actions.js;";
$modJs .= "modules/docmgr/js/convert.js;";
$modJs .= "modules/docmgr/js/object.js;";
$modJs .= "modules/docmgr/js/properties.js;";
$modJs .= "modules/docmgr/js/permissions.js;";
$modJs .= "modules/docmgr/js/subscriptions.js;";
$modJs .= "modules/docmgr/js/logs.js;";
$modJs .= "modules/docmgr/js/history.js;";
$modJs .= "modules/docmgr/js/keywords.js;";
$modJs .= "modules/docmgr/js/treeform.js;";
$modJs .= "modules/docmgr/js/parents.js;";
$modJs .= "modules/docmgr/js/upload.js;";
$modJs .= "modules/docmgr/js/checkin.js;";
$modJs .= "modules/docmgr/js/pdfedit.js;";
$modJs .= "modules/docmgr/js/share.js;";
$modJs .= "modules/docmgr/js/savedsearches.js;";
$modJs .= "modules/docmgr/js/discussion.js;";
$modJs .= "modules/docmgr/js/options.js;";
$modJs .= "javascript/minib.js;";

if (BROWSER=="ie") $modJs .= "modules/docmgr/js/ieupload.js;";


$modCss .= "modules/docmgr/css/upload.css;";
$modCss .= "modules/docmgr/css/pdfedit.css;";
$modCss .= "modules/docmgr/css/discussion.css;";
$modCss .= THEME_PATH."/css/minib.css;";

//ckeditor stuffs
$modCss .= "ckeditor/stylesheet.css;";
$modJs .= "ckeditor/ckeditor.js;";
$modJs .= "ckeditor/config.js;";

$onPageLoad = "loadPage();";

$siteContent = "
<input type=\"hidden\" name=\"objectId\" id=\"objectId\" value=\"".$objectId."\">
<input type=\"hidden\" name=\"objectPath\" id=\"objectPath\" value=\"".$objectPath."\">
<div id=\"container\"></div>
";

//for older file uploading
if (BROWSER=="ie")
{

  $siteContent .= "
  <iframe id=\"uploadframe\" name=\"uploadframe\" style=\"display:none;width:200px;height:200px\"></iframe>
  <form name=\"pageForm\" id=\"pageForm\" method=\"post\" enctype=\"multipart/form-data\"></form>
  ";
        
}

