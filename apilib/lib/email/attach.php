<?php

class EMAIL_ATTACH extends EMAIL
{

	function add()
	{

	  $dir = TMP_DIR."/".USER_LOGIN."/email";
	  recurmkdir($dir);
	  
		$headers = getallheaders();

		//print_r($headers);
		if (!isset($headers['Content-Type'])) exit('Content-Type header not set');
		if (!isset($headers['Content-Length'])) exit('Content-Length header not set');
		if (!isset($headers['X-File-Size'])) exit('X-File-Size header not set');
		if (!isset($headers['X-File-Name'])) exit('X-File-Name header not set');

		if (!stristr($headers['Content-Type'],"multipart/form-data")) exit('Content-Type not multipart/form-data');
		if ($headers['Content-Length'] != $headers['X-File-Size']) exit('Content-Length is incorrect');

		// create the object and assign property
		$file = new stdClass;
		$file->name = basename($headers['X-File-Name']);
		$file->size = $headers['X-File-Size'];

		$filepath = $dir."/".$file->name;

		$fp = fopen("php://input","r");
		file_put_contents($filepath,$fp);
		fclose($fp);		

	}

	/**
		this adds an attachment using the old $_FILES array for browsers that 
		don't support the new Files API
		*/
	function addfile()
	{

			$this->PROTO->clearData();

	  	$dir = TMP_DIR."/".USER_LOGIN."/email";
	  	recurmkdir($dir);
	
	  	//if passed file data, handle it
	  	$source = $_FILES["attach"]["tmp_name"];
			$destination = $dir."/".$_FILES["attach"]["name"];

			//copy the temp file to its destination
	  	copy($source,$destination);
	
	  	//uploading a file.this means iframes.this also means we can't 
	  	//return messages as anything but XML
	  	$this->PROTO->setProtocol("XML");

	  	if (!file_exists($destination))
	  	{
	  		$this->PROTO->add("error","Error uploading file");
			}
			else
			{ 
				$this->PROTO->add("success","success");
			}
                                         	                                        
	}
	
	
	function remove()
	{
	
	  $path = TMP_DIR."/".USER_LOGIN."/email";
	
	  if ($this->apidata["filename"])
	  {
	
	  	$file = $path."/".stripsan($this->apidata["filename"]);
	  	if (file_exists($file)) unlink($file);
	  	else $errorMessage = "Error removing file";
	  
	  } 
	  else 
	  {
	  	$this->throwError(_I18N_EMAIL_ATTACH_ERROR);
	  } 
	
	}

	function get()
	{
	
		//attachments are in the user's temp directory
		$files = @scandir(TMP_DIR."/".USER_LOGIN."/email");
		
		//remove directory markers
		array_shift($files);
		array_shift($files);
		$num = count($files);

		$attach = array();
			
		//prepend the directory name to the file
		for ($i=0;$i<$num;$i++)
		{

			//setup our attachment array using the files in our temp directory
			$attach[$i]["path"] = TMP_DIR."/".USER_LOGIN."/email/".$files[$i];
			$attach[$i]["name"] = $files[$i];
			$attach[$i]["size"] = displayFileSize(filesize(TMP_DIR."/".USER_LOGIN."/email/".$files[$i]));

		}

		if ($num > 0) $this->PROTO->add("attach",$attach);

		return $attach;
	
	}

}
	
	