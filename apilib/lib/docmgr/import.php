<?php

class DOCMGR_IMPORT extends DOCMGR 
{

  private $browsepath;
  private $hugethumb;
	private $tmpDir;

  function ___construct()
	{

  	if ($this->apidata["mode"]=="user")
  	{
  		$this->browsepath = HOME_DIR."/".USER_LOGIN;
		}
		else
		{
			$this->browsepath = IMPORT_DIR;
		}
		
    $this->hugethumb = $this->browsepath."/.hugethumb";
    $this->tmpDir = TMP_DIR."/".USER_LOGIN;
    
    recurMkDir($this->tmpDir);
    recurMkDir($this->hugethumb);
    
  }


  function browse() 
  {

  	$this->setBrowsePath();

    //sanity checking  
    if (!is_dir($this->browsepath)) recurmkdir($this->browsepath);

    $filearr = scandir($this->browsepath);

    foreach ($filearr AS $file) 
    {

      //skip directory markers and directories
      if ($file[0]==".") continue;
      if ($file=="hugethumb") continue;
      
      //get our thumbnail
      $thumb = $this->getThumbName($file);

      //init our array for storing output
			$ret = array();

     	if (is_dir($this->browsepath."/".$file))
     	{
				$ret["icon"] = THEME_PATH."/images/object-icons/folder.png";
				$ret["type"] = "collection";
     	}
     	else
     	{
				$info = fileInfo($file);
				$ret["icon"] = THEME_PATH."/images/object-icons/".$info["custom_type"].".png";
				$ret["type"] = "file";
			}

			$name = getFileName($file);
			
      $ret["name"] = $name;
      $ret["path"] = $this->browsepath."/".$file;
      $ret["size"] = displayFileSize(filesize($this->browsepath."/".$file));

			$this->PROTO->add("record",$ret);
    
    }
  
    //output our browse path also for the interface to show
    $this->PROTO->add("browse_path",$this->browsepath);
  
  }

  private function setBrowsePath()
  {

    //if passed a relative browse_path, tack it on
    if ($this->apidata["browse_path"])
    {
    
    	//sanity check
    	if (strstr($this->apidata["browse_path"],"..") || $this->apidata["browse_path"][0]=="/")
    	{
    		$this->throwError(_I18N_IMPORT_BROWSE_PATH_ERROR);
    		return false;
			}

			//tack it on
			$this->browsepath .= "/".$this->apidata["browse_path"];
    
    }
  
  }

  function run()
  {

  	$this->setBrowsePath();
  
		$fileArr = $this->apidata["file"];		  
  
		if (count($fileArr) == 0)
		{
			$this->throwError(_I18N_FILES_PASSED_ERROR);
			return false;
		}
		
		foreach ($fileArr AS $file)
		{
			$this->import($file);
		}
		
  }

  private function import($file)
  {

  	//we've been passed a directory
  	if (is_dir($this->browsepath."/".$file))
  	{
	
  		//get all files 
			$arr = scandir($this->browsepath."/".$file);

			//loop through and import them			
			foreach ($arr AS $f)
			{
			
				if ($f[0]==".") continue;
				
				//recursive call to work our way down
				$this->import($file."/".$f);
			
			}  	

			//if asked, remove the directory
		  if ($this->apidata["delete"])
		  {
			  @rmdir($this->browsepath."/".$file);
      }
  	
  	}
  	else
  	{

  	  $name = $file;
  	  $parentPath = $this->apidata["parent_path"];

  		//if we are currently in a subdirectory of the root parent directory, make it
  		if (strstr($file,"/"))
  		{

  			//keep the file name
  			$name = basename($file);

  			//tack on our directory name to the parent
				$parentPath .= "/".dirname($file);

  		}
			
			//we have a file, import it  	
 			$opt = null;
			$opt["parent_path"] = $parentPath;
			$opt["name"] = $name;
			$opt["filepath"] = $this->browsepath."/".$file;
			$opt["mkdir"] = 1;

			//save the file in our destination
			$f = new DOCMGR_FILE($opt);
			$f->save();

			if ($f->getError())
			{
				$this->throwError($f->getError());
			}
			else
			{
			  if ($this->apidata["delete"])
			  {
				  @unlink($this->browsepath."/".$file);
        }
        
			}
 	
  	}
  
  }

  function preview()
  {

    if (!$file) $file = $this->apidata["file_path"];


    if (!$file) 
    {
      $this->throwError(_I18N_FILES_PASSED_THUMB_ERROR);
      return false;
    }

    $file = $this->browsepath."/".$file;
		$filename = getFileName($file);

    $thumbname = $this->getThumbName($file);
    $thumb = $this->tmpDir."/".$thumbname;

    //create a thumbnail
    $d = new DOCMGR_UTIL_FILETHUMB("preview",$file,$filename,$thumb);

    $hugethumb = $this->hugethumb."/".$thumbname;

    //resize it and move it
    $cmd = APP_CONVERT." -resize 480x640 \"".$thumb."\" \"".$hugethumb."\"";
    `$cmd`;
 
    //return the thumbnail path
    $this->PROTO->add("preview",str_replace(SITE_PATH,SITE_URL,$hugethumb));
 
  }

  function merge() 
  {

  	$files = $this->apidata["file"];
  
    if (count($files)==0) 
    {
      $this->throwError(_I18N_FILES_SPECIFY_MERGE_ERROR);
      return false;
    }

    if (count($files)==1) 
    {
      $this->throwError(_I18N_FILES_SPECIFY_MULTIPLE_ERROR);
      return false;
    }

    $this->setBrowsePath();

    //convert our array to a full filepath
    for ($i=0;$i<count($files);$i++)
    {
    	$files[$i] = $this->browsepath."/".$files[$i];
    }

    //the output file is the first file
    $arr = explode("/",$files[0]);
    $arr[count($arr)-1] = "merged-".$arr[count($arr)-1];
    $output = implode("/",$arr);

    //use ghostscript to merge the files
    $cmd = APP_GS." -dNOPAUSE -sDEVICE=pdfwrite -sOUTPUTFILE=\"".$output."\" -dBATCH";
    for ($i=0;$i<count($files);$i++)  $cmd .= " \"".$files[$i]."\" ";

    `$cmd`;
    
    //if the output file exists, delete the other files, otherwise throw an error
    if (!file_exists($output)) 
    {
      $this->throwError(_I18N_MERGE_FILE_CREATE_ERROR);
    }
    else
    {
      //it does exist, remove the originals
      for ($i=0;$i<count($files);$i++)  unlink($files[$i]);
    }
          
  }  

  function delete() 
  {

    if (!$this->apidata["file"]) 
    {
      $this->throwError(_I18N_FILES_SPECIFY_DELETE_ERROR);
      return false;
    }

    $error = null;
  	$this->setBrowsePath();
		$filepath = $this->browsepath."/".$this->apidata["file"];  

		if (is_dir($filepath))
		{

		  $ret = removeDir($filepath);
		  
      if (!$ret) $error = _I18N_DIR_DELETE_ERROR;
		
		}
		else
		{

      if (unlink($filepath)) 
      {

        //delete the thumbnails as well
        $tn = $this->getThumbName($filepath);
        @unlink($this->hugethumb."/".$tn);
     
      }
      else $error = _I18N_FILE_DELETE_ERROR;
       
    } 

    if ($error) $this->throwError($error);
      
  }  



  function thumb($file=null) 
  {

    if (!$file) $file = $this->apidata["file_path"];

    //sanity checking
    if (!$file) 
    {
      $this->throwError(_I18N_FILES_PASSED_THUMB_ERROR);
      return false;
    }

		$filename = getFileName($file);
    $thumbname = $this->getThumbName($file);
    $thumb = $$this->tmpDir."/".$thumbname;

    $d = new DOCMGR_UTIL_FILETHUMB("preview",$file,$filename,$thumb);

    $hugethumb = $this->hugethumb."/".$thumbname;

    system(APP_CONVERT." -resize 480x640 \"".$thumb."\" \"".$hugethumb."\"");
 
  }

  function getThumbName($file) 
  {
  
    //get the name with the new extension
    $arr = explode("/",$file);
    $name = $arr[count($arr)-1];
    $ext = return_file_extension($name);
    $name = str_replace(".".$ext,".png",$name);
    return $name;
  
  }

  function rename() 
  {

  	$this->setBrowsePath();
  
    if (!$this->apidata["file"])
    {
      $this->throwError(_I18N_FILES_SPECIFY_RENAME_ERROR);
      return false;
    }
    
    if (!$this->apidata["name"])
    {
      $this->throwError(_I18N_FILES_SPECIFY_NAME_ERROR);
      return false;
    }

    $filepath = $this->browsepath."/".$this->apidata["file"];
		$newname = $this->apidata["name"];
		
    //get the path of the file
    $arr = explode("/",$filepath);
    array_pop($arr);
    $dest = implode("/",$arr)."/".$newname;    

    //thumbnail names
    $thumb = $this->getThumbName($filepath);
    $newthumb = $this->getThumbName($newname);
    
    if (rename($filepath,$dest)) 
    {

      //rename the thumbnails as well
      @rename($this->hugethumb."/".$thumb,$this->hugethumb."/".$newthumb);

    } 
    else $this->throwError(_I18N_FILES_REMOVE_ERROR);

  }  

}
