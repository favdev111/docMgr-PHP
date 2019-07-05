<?php

class EMAIL_SEND extends EMAIL
{

	private $trackerId;
	private $sendemail;
			
  function send()
  {

  	if (!$this->sanityCheck()) return false;

		//send a regular email with no extra logging
    $this->sendMessage();  
		
  }

  /**
  	everything okay to proceed?
  	*/
  private function sanityCheck()
  {
  
  	if (!$this->apidata["to"])
  	{
  		$this->throwError(_I18N_EMAIL_RECIP_ERROR);
  		return false;
		}

		return true;
				
  }

	/**
		formats a regular message and sends it to our smtp client
		*/
	function sendMessage()
	{

	  $attach = array();
	  $msg = fixDoctype($this->apidata["editor_content"]);
	  $this->trackerId = null;

	  //default from
	  if (!$this->apidata["from"]) 
	  {
	  	$this->apidata["from"] = USER_FN." ".USER_LN." <".USER_EMAIL.">";
		}
	
		//get our uploaded attachments
	  $a = new EMAIL_ATTACH($this->apidata);
	  $attach = $a->get();

		//add our inline and docmgr attachments
	  $this->handleEmailAttachments($attach,$msg);
	  $this->handleDocmgrAttachments($attach,$msg);

		//setup our parameters	
    $this->sendemail = new SENDEMAIL($this->apidata["to"],
    																$this->apidata["from"],
    																$this->apidata["subject"],
    																$msg);
    $this->sendemail->setAttach($attach);
		$this->sendemail->setCC($this->apidata["cc"]);
		$this->sendemail->setBCC($this->apidata["bcc"]);

		//send the email
		$this->sendemail->send();

		//oops            
    if ($this->sendemail->getError())
    {
    	$this->throwError("Error sending email\n".$this->sendemail->getError());
    	return false;
		}

	}

	protected function getEmailImages($msg) 
	{
	
	  $imgs = array();
	
	  preg_match_all('/\<img.+?src="(.+?)".+?\/>/', $msg, $matches);
	  $n = 0;
	  $matcharr = $matches[1];
	    
	  for ($i=0;$i<count($matcharr);$i++) 
	  {
	
	    //skip regular links
	    if (!strstr($matcharr[$i],"objectId=")) continue;
	
	    //remove the server and everything else before "?"
	    $pos = strpos($matcharr[$i],"?");
	    if ($pos===FALSE) continue;
	    $str = substr($matcharr[$i],$pos+1);
	
	    //extract the objectid from the source    
	    $objectId = null;
	    parse_str($str);
	
	    //if there was an objectid in the string, save it
	    if ($objectId) 
	    {
	      $imgs[$n] = array();
	      $imgs[$n]["src"] = $matcharr[$i];
	      $imgs[$n]["object_id"] = $objectId;
	      $n++;
	    }
	
	  }
	
	  return $imgs;
	
	}
	
	protected function handleEmailAttachments(&$attach,&$msg) 
	{
	
	  //first we need to pull all images and objects from this email
	  $imgarr = $this->getEmailImages($msg);
	
	  if (count($imgarr)==0) return false;
	
	  //our directory for storing the attachments
	  $dir = TMP_DIR."/".USER_LOGIN."/email";
	  $c = count($attach);
	    
	  foreach ($imgarr AS $img) 
	  {
	
	    $d = new DOCMGR_OBJECT($img["object_id"]);
	    $info = $d->getInfo();
	    $data = $d->getContent();
	
	    //make sure it's not already attached
	    if (!$this->checkAttached($attach,$dir."/".$info["name"])) continue;
	
	    //write the file to our temp directory
	    file_put_contents($dir."/".$info["name"],$data);
	
	    $ext = fileExtension($info["name"]);
	    $cid = md5(uniqid()).".".$ext;
	
	    //add to the attachment array
	    $attach[$c]["path"] = $dir."/".$info["name"];
	    $attach[$c]["name"] = $info["name"];
	    $attach[$c]["cid"] = $cid;
	    $c++;
	
	    //now replace the original source in the message with the inline cid source
	    $msg = str_replace($img["src"],"cid:".$cid,$msg);
	            
	  }
	
	}
	
	protected function handleDocmgrAttachments(&$attach,&$msg) 
	{

	  //stop here if nothing to do
	  if (!$this->apidata["docmgr_attach"]) return false;
	
	  //our directory for storing the attachments
	  $dir = TMP_DIR."/".USER_LOGIN."/email";
	
	  //loop through and get our files
	  $docarr = $this->apidata["docmgr_attach"];
	  $c = count($attach);

	  foreach ($docarr AS $docId) 
	  {
	
	    $d = new DOCMGR_OBJECT($docId);
	    $info = $d->getInfo();
	    $data = $d->getContent();
	
	    //log that each one was sent
	    logEvent(OBJ_EMAILED,$docId);
	   
	    //make sure it's not already attached
	    if (!$this->checkAttached($attach,$dir."/".$info["name"])) continue;
	
	    //if a document, extract content from the xml
	    if ($info["object_type"]=="document") $info["name"] .= ".html";
	
	    //write the file to our temp directory
	    file_put_contents($dir."/".$info["name"],$data);
	
	    //add to the attachment array
	    $attach[$c]["path"] = $dir."/".$info["name"];
	    $attach[$c]["name"] = $info["name"];
	    $c++;
	
	  }
	
	}
	
	protected function checkAttached($attach,$path) 
	{
	
	  $num = count($attach);
	  $ret = true;
	  
	  for ($i=0;$i<$num;$i++) {
	    if ($attach[$i]["path"]==$path) {
	      $ret = false;
	      break;
	    }
	    
	  }
	  
	  return $ret;
	}

}
