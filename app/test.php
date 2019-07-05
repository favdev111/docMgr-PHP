<?php

function fixHtmlImages($file)
{

  $data = file_get_contents($file);
  
  //logged in as normal, just use the easy way of plugging in a valid session id into the url
  if (isset($_SESSION["api"]["authorize"]))
  {
    $session = "sessionId=".session_id();
    $data = str_replace("[DOCMGR_SESSION_MARKER]",$session,$data);  
  }
  else
  {

	  //going to try something a bit different here, due to my loathing of regex
	  $dom = new DOMDocument;
	  @$dom->loadHTML($data);
	
	  //get all images
	  $images = $dom->getElementsByTagName('img');
    $usedObjects = array();
    	
	  foreach($images as $im)
	  {
	
	    $src = $im->attributes->getNamedItem('src')->nodeValue;
	
	    //no need to do this if there's no session marker
	    if (!strstr($src,"[DOCMGR_SESSION_MARKER]")) continue;
	
	    $origSrc = substr($src,strpos($src,"?") + 1);

      //get our objectId and a uuid to use	
	    parse_str($origSrc);
	    $uuid = uuid();

	    //store our key so it can be used by the script
      $sql = "INSERT INTO docmgr.object_convert_keys (object_id,convert_key) VALUES ('".$objectId."','".$uuid."')";
      $GLOBALS["DB"]->query($sql);
      
      //fix our link
      $newSrc = str_replace("[DOCMGR_SESSION_MARKER]","convertKey=".$uuid,$src);      
	    $im->setAttribute("src",$newSrc);  

	  }

    //save our changes
    $data = $dom->saveHTML();
      
  }

  file_put_contents($file,$data);

}
