<?php

//transfer some PHP-side variables into javascript variables
echo "
<script type=\"text/javascript\">
var THEME_PATH = \"".THEME_PATH."\";
var SITE_URL = \"".SITE_URL."\";
var API_URL = \"".API_URL."\";
var USE_COOKIES = \"".USE_COOKIES."\";
var MODULE = \"".$module."\";
var RESULTS_PER_PAGE = \"".RESULTS_PER_PAGE."\";
var PAGE_RESULT_LIMIT = \"".PAGE_RESULT_LIMIT."\";
var ROOT_NAME = \"".ROOT_NAME."\";
var USE_TRASH = \"".USE_TRASH."\";
var DMEDITOR_DEFAULT_SAVE = \"".DMEDITOR_DEFAULT_SAVE."\";
var DATE_FORMAT = \"".DATE_FORMAT."\";
var USER_ID = \"".USER_ID."\";
var USER_FN = \"".USER_FN."\";
var USER_LN = \"".USER_LN."\";
var USER_EMAIL = \"".USER_EMAIL."\";
var USER_LOGIN = \"".USER_LOGIN."\";
var BITSET = \"".BITSET."\";
var SESSION_ID = \"".session_id()."\";
";

//optionally set
if (defined("USE_LDAP")) echo "var USE_LDAP = \"".USE_LDAP."\"\n";

//move over our permissions so we can do client-side permission checking for the inteface  
$str = file_get_contents("config/permissions.xml");
$arr = xml2array($str);
  
for ($i=0;$i<count($arr["perm"]);$i++) 
{
  echo "var ".$arr["perm"][$i]["define_name"]." = \"".$arr["perm"][$i]["bitpos"]."\";\n";
}
     
echo "
</script>
";

