<?php

/**************************************************************************

	config.php

	This file was automatically created by the installer.  You may 
	edit it at any time.  The installer will migrate your settings 
	into the new config file at upgrade time.  Be sure to backup   
	this file before attempting upgrades.  Non-standard config     
	options should go in the custom-config.php file.               

**************************************************************************/


/**********************************************************
	Required
***********************************************************/

//Database Host
define("DBHOST","localhost");

//Database User
define("DBUSER","postgres");

//Database Password
define("DBPASSWORD","x4%tez912ZH09p");

//Database Port
define("DBPORT","5432");

//Database Name
define("DBNAME","docmgr");

//Full site URL.  Must have trailing slash!
define("SITE_URL","https://docs.cuenta.red/");

//Full site path.  No trailing slash required
define("SITE_PATH","/var/www/dms");

//Absolute path to DocMGR files directory
define("FILE_DIR",SITE_PATH."/files");

//Path to DocMGR tmp folder
define("TMP_DIR",FILE_DIR."/tmp");

//Path to DocMGR tmp folder
define("DATA_DIR",FILE_DIR."/data");

//Path to DocMGR thumbnail folder
define("THUMB_DIR",FILE_DIR."/thumbnails");

//Path to DocMGR preview folder
define("PREVIEW_DIR",FILE_DIR."/preview");

//Path to DocMGR documents folder
define("DOC_DIR",FILE_DIR."/document");

//Path to DocMGR home folder
define("HOME_DIR",FILE_DIR."/home");

//Path to DocMGR Import Folder
define("IMPORT_DIR",FILE_DIR."/import");

//Enable LDAP for accounts
//define("USE_LDAP","1");

/**********************************************************
	Email
***********************************************************/

//Admin email set as return address for system emails
define("ADMIN_EMAIL","admin@mydomain.com");

//Address of your SMTP server
define("SMTP_HOST","localhost");

//Port of your SMTP server
define("SMTP_PORT","25");

//Use SMTP Authentication
//define("SMTP_AUTH","1");

//SMTP Auth Username.
//define("SMTP_AUTH_LOGIN","mailuser");

//SMTP Auth Password.
//define("SMTP_AUTH_PASSWORD","secret");

/**********************************************************
	Indexing
***********************************************************/

//Regular expression used to determine what characters to index
define("REGEXP_OPTION","-a-z0-9_");

//Limit index to this many words
//define("INDEX_WORD_LIMIT","1000");

/**********************************************************
	Permissions
***********************************************************/

//Allow automated logins with cookies
define("USE_COOKIES","1");

//Number of days until cookie expires
define("COOKIE_TIMEOUT","14");

//Allow removal of past file revisions
define("FILE_REVISION_REMOVE","yes");

//Allow removal of past document revisions
define("DOC_REVISION_REMOVE","yes");

/**********************************************************
	Optional
***********************************************************/

//Default language for application
define("DEFAULT_LANGUAGE","es");

//Default search results per page
define("RESULTS_PER_PAGE","20");

//Number of pages of results to show at once
define("PAGE_RESULT_LIMIT","20");

//Max number of seconds of processing per page per file
define("EXECUTION_TIME","60");

//Date format for entering and viewing dates (either mm/dd/yyyy or dd/mm/yyyy)
define("DATE_FORMAT","dd/mm/yyyy");

//Number of file histories to keep.  O for unlimited
define("FILE_REVISION_LIMIT","0");

//Number of document histories to keep.  O for unlimited
define("DOC_REVISION_LIMIT","0");

//Send md5 checksum file w/ all email attachments
//define("SEND_MD5_CHECKSUM","1");

//Allow file to be viewed even md5 check fails (after warning displayed)
//define("BYPASS_MD5CHECK","1");

//Use trash can instead of direct delete
define("USE_TRASH","1");

//Tsearch2 profile to use for indexing
define("TSEARCH2_PROFILE","english");

//Name for the top level bookmark
define("ROOT_NAME","Inicio");

//default permissions.  user can alter own profile, insert objects into system, and create in the root collection
define("DEFAULT_PERMISSIONS","00000000000000000000110000001000");

//group browse results by object type
//define("BROWSE_GROUPBY","object_type");

//Change default module to display after login
define("DEFAULT_MODULE","workflow");

//Default theme for DocMGR
define("SITE_THEME","default");

//Default file type for DocMGR's built-in editor to save as.  Options are 'docmgr','odt','doc'... or whatever you set allow_dmsave tag to in extensions.xml file
define("DMEDITOR_DEFAULT_SAVE","docmgr");

/**********************************************************
	Security
***********************************************************/

//Login banner displayed on login page
//define("WARNING_BANNER","Warning!!!!");

//Enable account lockout feature - affects all users but admins
define("ENABLE_ACCOUNT_LOCKOUT","1");

//Number of minutes to lock out account. 0 = forever
define("ACCOUNT_LOCKOUT_TIME","5");

//Number of failed login attempts for an account is locked
define("ACCOUNT_LOCKOUT_ATTEMPTS","5");

//Select whether cookies should only be sent over secure connections
//define("SECURE_COOKIES","1");

//Characters we disallow in a filename
define("DISALLOW_CHARS","\"/*");

//Set this if nobody can delete objects except administrators
define("RESTRICTED_DELETE","1");

/**********************************************************
	Unchangeable
***********************************************************/

//url to docmgr api
define("API_URL","api.php");

//used for digest authentication on webdav
define("DIGEST_REALM","SabreDAV");

//Our proto transfer protocol
define("PROTO_DEFAULT","JSON");

//length of our permissions bitmask
define("PERM_BITLEN","32");

//Process authentications on this site
define("PROCESS_AUTH","1");

//Reload modules every time for development
//define("DEV_MODE","1");

//Debugging level
//define("DEBUG","5");

//Top directory level (DO NOT CHANGE)
define("LEVEL1_NUM","16");

//Second directory level (DO NOT CHANGE)
define("LEVEL2_NUM","256");



//set error reporting to not show notices
error_reporting(E_ALL ^ E_NOTICE ^ E_STRICT);

//turn on error reporting
ini_set("display_error","1");

$exemptRequest = array();
$exemptRequest[] = "editor_content";
$exemptRequest[] = "apidata";
$exemptRequest[] = "to"; 
$exemptRequest[] = "from"; 
$exemptRequest[] = "cc";
$exemptRequest[] = "bcc";

include("config-custom.php");

