<?php

require_once("app/common.php");
require_once("app/client.php");

//make sure we are accessing the site correctly
checkSiteURL();

//register our autoloader so we can call
//api functions direct from the client
spl_autoload_register('client_autoload');

