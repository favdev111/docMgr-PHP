<?php

//files common to this app
require_once(SITE_PATH."/app/common.php");
require_once(SITE_PATH."/app/common-docmgr.php");
require_once(SITE_PATH."/app/openoffice.php");

//docmgr only libs
require_once(SITE_PATH."/apilib/lib/docmgr/lib/common.php");

//set the execution time for uploading and file processing
if (defined("EXECUTION_TIME")) ini_set("max_execution_time",EXECUTION_TIME);

//setup which apps are available to docmgr
setExternalApps();


