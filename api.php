<?php

//get header file
require_once("apilib/header.php");

//get header file
require_once("apilib/preauth.php");

//not sure if we still need this or not
define("THEME_PATH","themes/".SITE_THEME);

//for handling requests
require_once("apilib/request.php");

$PROTO->output();

