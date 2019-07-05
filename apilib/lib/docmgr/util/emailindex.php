<?php


class DOCMGR_UTIL_EMAILINDEX
{

	private $content;
	private $boundary;
		
	function __construct($file)
	{
	
		$this->content = file_get_contents($file);
		//$this->content = str_replace("\r\n","\n",$this->content);
	
	}
	
	function getIndex()
	{
	
		$sub = $this->getSubject();
		$body = $this->getContent("text");

		return $sub." ".$body."\n";
	
	}

	function getThumb()
	{
	
		$body = $this->getContent("html");

		$diff = "10";
		$test = strip_tags($body);

		if ((strlen($body)-strlen($test)) > $diff)
			$file = TMP_DIR."/".USER_LOGIN."/worker.html";
		else 
			$file = TMP_DIR."/".USER_LOGIN."/worker.txt";
		
		file_put_contents($file,$body);

		//start openoffice.it will convert to oo if it's not an openoffice file
		$oo = new OPENOFFICE($file);
		$thumb = $oo->getThumbnail();

		@unlink($file);

		return $thumb;
		
	}

	function getContent($mode)
	{
	
	  $this->boundary = $this->getBoundary();
	  
	  if ($this->boundary) 
	  {

		  $arr = explode($this->boundary,$this->content);

		  $body = null;
		  $text = null;
		  $html = null;
			$textEnc = null;
			$htmlEnc = null;
			  
		  for ($i=1;$i<count($arr);$i++)
		  {
		
		    $type = $this->getContentType($arr[$i]);
		    $encoding = $this->getContentEncoding($arr[$i]);  
	
		  	if ($type=="text/plain" && !$text) 
		  	{
		  		$text = substr($arr[$i],strpos($arr[$i],"\n\n"));
		  		$textEnc = $encoding;
				}
				else if ($type=="text/html" && !$html)
				{
					$html = substr($arr[$i],strpos($arr[$i],"\n\n"));
					$htmlEnc = $encoding;
				}
		    
		  }

		  if ($mode=="html")
		  {

			  if ($html) 
			  	$ret = $this->decodePart($htmlEnc,$html);
			  else if ($text) 
			  	$ret = $this->decodePart($textEnc,$text);
		  
		  }
		  else
		  {

			  if ($text) 
			  	$ret = $this->decodePart($textEnc,$text);
			  else if ($html) 
			  	$ret = strip_tags($this->decodePart($htmlEnc,$html));

			}
			
		}
		else
		{
		
			$arr = explode("\r\n\r\n",$this->content);
			array_shift($arr);
			$ret = implode("\r\n\r\n",$arr);

			$type = $this->getContentType($this->content);
			$encoding = $this->getContentEncoding($this->content);
	
			$ret = $this->decodePart($encoding,$ret);
			
		}
			  
	  return $ret;
	
	}
	
	function getSubject()
	{
	
	  $arr = explode("\n",$this->content);
	
	  foreach ($arr AS $line)
	  {
	  
	  	$pos = strpos($line,"Subject:");
	
	  	if ($pos!==FALSE)
	  	{
	
				$sub = trim(substr($line,$pos+strlen("Subject:")));
	
				return $sub;
					
			}
			    	
	  }
	
	}
	
	function getContentType($str)
	{
	
	  $arr = explode("\n",$str);
	
	  foreach ($arr AS $line)
	  {
	  
	  	$pos = strpos($line,"Content-Type:");
	
	  	if ($pos!==FALSE)
	  	{
	
				$tmp = substr($line,$pos+strlen("Content-Type:"));
				$pos2 = strpos($tmp,";");
				
				$ct = trim(substr($tmp,0,$pos2));
			
				return $ct;
					
			}
			    	
	  }
	
	  if (!$ct) return "text/plain";
	
	}
	
	function getContentEncoding($str)
	{
	
		//Content-Transfer-Encoding: quoted-printable    
	  $arr = explode("\n",$str);
	
	  foreach ($arr AS $line)
	  {
	  
	  	$pos = strpos($line,"Content-Transfer-Encoding:");
	
	  	if ($pos!==FALSE)
	  	{
	
				$ct = trim(substr($line,$pos+strlen("Content-Transfer-Encoding:")));
				
				return $ct;
					
			}
			    	
	  }
	
	  if (!$ct) return "other";
	  
	}
	
	/*****************************************************************************
	FUNCTION: getBoundary
	PURPOSE:gets mixed part boundary for email message
	INPUTS: $header (string) email header
	*****************************************************************************/
	function getBoundary()
	{
	
	  $arr = explode("\n",$this->content);
	  $num = count($arr);
	
	  for ($i=0;$i<$num;$i++) {
	    $str = substr($arr[$i],0,2);
	    if ($str=="--") return $arr[$i];
	  }
	
	}
	
	/*****************************************************************************
	FUNCTION: decodePart
	PURPOSE:decodes part of the message based on passed encoding
	INPUTS: $coding (string) -> encoding of the message
	$body (string) -> part to decode
	*****************************************************************************/
	function decodePart($coding,$body)
	{
	
	  $coding = strtoupper($coding);
	
	  if ($coding == "7BIT")	$body = $body;
	  elseif ($coding == "8BIT")	$body = $body;
	  elseif ($coding == "BINARY")	$body = base64_decode(imap_binary($body));
	  elseif ($coding == "BASE64")	$body = base64_decode($body);
	  elseif ($coding == "QUOTED-PRINTABLE")	$body = quoted_printable_decode($body);
	  elseif ($coding == "OTHER") $body = $body;
	
	  return $body;
	
	}
	
}
	