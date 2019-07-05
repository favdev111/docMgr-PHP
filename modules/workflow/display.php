<?php
//css files
$modCss .= "modules/workflow/css/recipients.css;";
//$modCss .= "modules/workflow/css/workflow.css;";
$modCss .= THEME_PATH."/css/minib.css;";

//calendar date picker requirements
$modJs .= "jslib/datepicker/Source/Picker.js;";
$modJs .= "jslib/datepicker/Source/Picker.Attach.js;";
$modJs .= "jslib/datepicker/Source/Picker.Date.js;";
$modCss .= SITE_URL."jslib/datepicker/Source/datepicker_vista/datepicker_vista.css;";

//js files
$modJs .= "modules/workflow/js/tasks.js;";
$modJs .= "modules/workflow/js/workflow.js;";
$modJs .= "modules/workflow/js/recipients.js;";
$modJs .= "modules/docmgr/js/view.js;";
$modJs .= "javascript/minib.js;";

$onPageLoad = "loadPage();";
$siteContent = "
<input type=\"hidden\" name=\"object_id\" id=\"object_id\" value=\"".$objectId."\">
<input type=\"hidden\" name=\"route_id\" id=\"route_id\" value=\"".$routeId."\">
<input type=\"hidden\" name=\"workflow_id\" id=\"workflow_id\" value=\"".$workflowId."\">
<input type=\"hidden\" name=\"action\" id=\"action\" value=\"".$action."\">
<div id=\"container\"></div>
";

