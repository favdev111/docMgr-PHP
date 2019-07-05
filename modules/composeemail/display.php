<?php

if ($_REQUEST["hideHeader"]) $template = "solo";

$modJs .= "ckeditor/ckeditor.js;";
$modJs .= "ckeditor/config.js;";  
$modCss .= "ckeditor/stylesheet.css;";
$modCss .= THEME_PATH."/css/minib.css;";
      
$modJs .= "modules/composeemail/js/template.js;";
$modJs .= "modules/composeemail/js/attach.js;";
$modJs .= "modules/composeemail/js/addressbook.js;";
$modJs .= "modules/composeemail/js/suggest.js;";
$modJs .= "javascript/minib.js;";

$onPageLoad = "loadPage()";

$siteContent = "
<div id=\"templatewin\" style=\"visibility:hidden;position:absolute\"></div>
<div id=\"addrwin\" style=\"visibility:hidden;position:absolute\"></div>

<iframe name=\"uploadframe\" id=\"uploadframe\"  style=\"display:none;width:300px;height:50px;\"></iframe>
                                       
<form name=\"pageForm\" method=\"post\">
<input type=hidden name=\"uid\" id=\"uid\" value=\"".$_REQUEST["uid"]."\">
<input type=hidden name=\"mode\" id=\"mode\" value=\"".$_REQUEST["mode"]."\">
<input type=hidden name=\"task_id\" id=\"task_id\" value=\"".$taskId."\">
<input type=hidden name=\"objectPath\" id=\"objectPath\" value=\"".$objectPath."\">
<input type=hidden name=\"objectType\" id=\"objectType\" value=\"".$objectType."\">
<input type=hidden name=\"objectId\" id=\"objectId\" value=\"".$objectId."\">
<input type=hidden name=\"contact_id\" id=\"contact_id\" value=\"".@implode(",",$contactId)."\">
<input type=hidden name=\"docmgrAttachments\" id=\"docmgrAttachments\" value=\"".$_REQUEST["docmgrAttachments"]."\">
<textarea style=\"display:none\" id=\"notes\" name=\"notes\">".$_REQUEST["notes"]."</textarea>

<div class=\"container\" id=\"container\">

  <div id=\"emailHeader\">
    <div class=\"emailHeaderCell\">
      <div class=\"emailHeaderTitle\">To</div>
      <div class=\"emailHeaderContent\">
        <textarea name=\"to\" id=\"to\" onBlur=\"hideAllSuggest()\" onFocus=\"setFocus('to');\" onKeyUp=\"suggestAddress(event)\">".$email."</textarea>
        <div id=\"tosuggest\" class=\"suggestdiv\"></div>
      </div>
    </div>
    <div class=\"cleaner\">&nbsp;</div>
    <div class=\"emailHeaderCell\" style=\"visibility:hidden;position:absolute;\" id=\"ccCell\">
      <div class=\"emailHeaderTitle\">CC</div>
      <div class=\"emailHeaderContent\">
        <textarea name=\"cc\" id=\"cc\" onBlur=\"hideAllSuggest()\"  onFocus=\"setFocus('cc');\" onKeyUp=\"suggestAddress(event)\">".$cc."</textarea>
        <div id=\"ccsuggest\" class=\"suggestdiv\"></div>
      </div>
    </div>
    <div class=\"cleaner\">&nbsp;</div>
    <div class=\"emailHeaderCell\" style=\"visibility:hidden;position:absolute;\" id=\"bccCell\">
      <div class=\"emailHeaderTitle\">BCC</div>
      <div class=\"emailHeaderContent\">
        <textarea name=\"bcc\" id=\"bcc\" onBlur=\"hideAllSuggest()\"  onFocus=\"setFocus('bcc');\" onKeyUp=\"suggestAddress(event)\">".$bcc."</textarea>
        <div id=\"bccsuggest\" class=\"suggestdiv\"></div>
      </div>
    </div>
    <div class=\"cleaner\">&nbsp;</div>
    <div class=\"emailHeaderCell\" id=\"subjectCell\">
      <div class=\"emailHeaderTitle\">Subject</div>
      <div class=\"emailHeaderContent\">
        <input type=text name=\"subject\" id=\"subject\" value=\"".$subject."\" autocomplete=\"off\">
        <input type=button value=\"CC\" onClick=\"cycleObject('ccCell');setFrameSize();\" class=\"".$btnclass."\">
        <input type=button value=\"BCC\" onClick=\"cycleObject('bccCell');setFrameSize();\" class=\"".$btnclass."\">
      </div>
    </div>
    <div class=\"cleaner\">&nbsp;</div>
    <div class=\"emailHeaderCell\" id=\"attachCell\"></div>
    <div class=\"cleaner\">&nbsp;</div>
  </div>
  <textarea  name=\"editor_content\" id=\"editor_content\">".$emailContent."</textarea>

  <!--  <div id=\"editorDiv\"></div> -->

</div>
</form>
";

