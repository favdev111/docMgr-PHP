<?php

/********************************************
  configurable options
********************************************/
$ooPort = "8100";
$ooHome = "/tmp";

/*********************************************
  end configurable options
*********************************************/

include("config/app-config.php");

//try to get to openoffice
$fp = @fsockopen("localhost",$ooPort);

if (!$fp)
{

  echo "Server down.  Starting...";

  //run the server
  $cmd = "export HOME='".$ooHome."'; soffice -norestore -nologo -headless -nofirststartwizard -accept='socket,port=".$ooPort.";urp;StarOffice.Service' &";
  
  system($cmd);

  //wait a few seconds for the server to start
  sleep(4);

} 
else
{

  //go ahead the resource since we don't need it
  fclose($fp);

}

//run the convertor
$cmd = APP_PYTHON." bin/DocumentConverter.py \"".$argv[1]."\" \"".$argv[2]."\"";

//run it
$res = `$cmd`;

echo $res;
