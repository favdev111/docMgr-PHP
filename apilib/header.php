<?php

//set the include path to work with relative paths
//ini_set("include_path",".");

//first call the config file to get our settings, call our base functions, and get our wrapper
if (!defined("SITE_PATH")) include("config/config.php");

include(SITE_PATH."/config/app-config.php");
include(SITE_PATH."/config/version.php");

//include our ldap file if set
if (defined("USE_LDAP")) include(SITE_PATH."/config/ldap-config.php");

//the rest of our includes with our base functions
include(SITE_PATH."/apilib/includes.php");
include(SITE_PATH."/apilib/constants.php");

//this gets rid of a hidden session form field which we do not want
ini_set("session.use_trans_sid","0");

//whether cookies should only be sent over secure connections.
if (defined("SECURE_COOKIES")) ini_set("session.cookie_secure",SECURE_COOKIES);

//set to use short tags
ini_set("short_open_tag","1");

//make our request variables safe
sanitizeRequest($exemptRequest);

//start our session
session_start();

//connect
$DB = new POSTGRESQL(DBHOST,DBUSER,DBPASSWORD,DBPORT,DBNAME);
$GLOBALS["DB"] = $DB;

//we now do our authentications on the local database
$GLOBALS["conn"] = $conn;

//init out logger
$GLOBALS["logger"] = $DB->getLogger();
$logger = $GLOBALS["logger"];

//init our communications protocol
$PROTO = new PROTO();
$GLOBALS["PROTO"] = $PROTO;

//set our permission defines
PERM::setDefines(SITE_PATH."/config/permissions.xml");
PERM::setDefines(SITE_PATH."/config/customperm.xml");
