<?php

$modCss .= THEME_PATH."/css/minib.css;";
$modCss .= "ckeditor/stylesheet.css;";

//javascript fiels
$modJs .= "ckeditor/ckeditor.js;";
$modJs .= "ckeditor/config.js;";
$modJs .= "javascript/minib.js;";
$modJs .= "modules/docmgr/editor/js/editor.js;";
$modJs .= "modules/docmgr/editor/js/dmeditor.js;";
$modJs .= "modules/docmgr/editor/js/textedit.js;";

//load the page
$onPageLoad = "loadPage()";

$siteContent ="
<form name=\"pageForm\" method=\"post\">
<input type=\"hidden\" name=\"editor\" id=\"editor\" value=\"".$_REQUEST["editor"]."\">
<input type=\"hidden\" name=\"objectId\" id=\"objectId\" value=\"".$objectId."\">
<input type=\"hidden\" name=\"directPath\" id=\"directPath\" value=\"".$directPath."\">
<input type=\"hidden\" name=\"parentPath\" id=\"parentPath\" value=\"".$parentPath."\">
<div id=\"container\"></div>
</form>

";

