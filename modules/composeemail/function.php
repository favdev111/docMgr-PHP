<?php

function extractSelfEmail($email) {

	if (strstr($email,";")) $sep = ";";
	else $sep = ",";
	
	$arr = explode($sep,$email);
	$ret = array();
	
	foreach ($arr AS $recip)
	{
	
		//skip us
		if (strstr($recip,USER_EMAIL)) continue;
		$ret[] = $recip;
	
	}

	$email = implode(",",$ret);
	
	return $email;

}


function emailReformat($emailContent,$wrap = null)
{

  //things we'll be searching for
  $linkstr = "<link[^>]*?>";
  $metastr = "<meta[^>]*?>";  
  $xmlstr = "<xml[^>]*?>.*?</xml>";
  $headstr = "<head[^>]*?>.*?</head>";
  $bodystr = "<body[^>]*?>.*?</body>";
  $mailscannerstr = "<mailscanner[^>]*?>.*?</mailscanner[^>]*?>";

  //places to store search results    
  $bodytags = array();
  $headertags = array();
  $linktags = array();
  $mailscannertags = array();
        
  //header and body extracted
  preg_match_all("@".$headstr."@si",$emailContent,$headertags);
  preg_match_all("@".$bodystr."@si",$emailContent,$bodytags);

  //save the body out if found, otherwise add body tags to what we have
  if ($bodytags[0][0]) 
    $body = $bodytags[0][0];
  else 
    $body = "<body>\n".$emailContent."</body>\n";
 
  //pull the external stylesheet links out because ckeditor hates them, and msword
  //likes to embed them all over the damn place
  preg_match_all("@".$linkstr."@si",$body,$linktags);

  //reassemble the header with body embeded links in it
  if ($headertags[0][0]) 
  {
    $header = str_replace("</head>",@implode("\n",$linktags[0])."\n</head>\n",$headertags[0][0]);
  }
  else 
  {
    $header = "<head>\n".@implode("\n",$linktags[0])."\n</head>\n";
  }

  //we have to strip external stylesheets when forwarding because it flips
  //ckeditor out.  we could also move them all to the <head> tags.  That would be better
  $body = preg_replace("@".$linkstr."@si","",$body);

  //no embeded meta or xml tags in the email (MS Word, I'm still looking at you)
  $body = preg_replace("@".$metastr."@si","",$body);
  $body = preg_replace("@".$xmlstr."@si","",$body);

  //strip out mailscanner sanitizng tags
  $body = preg_replace("@".$mailscannerstr."@si","",$body);

  //wrap the content in a text container if required
  if ($wrap)
  {
  
    $pos1 = strpos($body,">") + 1;
    $pos2 = stripos($body,"</body>");
  
    $c = substr($body,$pos1,$pos2-$pos1);
    $prefix = substr($body,0,$pos1);
    $suffix = "</body>";
        
    $body = $prefix.str_replace("[[CONTENT]]",$c,$wrap).$suffix;
  
  }

  //reassemble the thing.  for now we aren't including doctype even if it was in the original 
  $emailContent = "<html>\n".$header."\n".$body."\n</html>\n";

  return $emailContent;
  
} 

