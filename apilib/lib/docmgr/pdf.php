<?php

/**************************************************************************
	CLASS:	pdf
	PURPOSE:	handle specific processing for pdf files
**************************************************************************/

class DOCMGR_PDF extends DOCMGR 
{

  /*****************************************************
  	Steps:
  	
  		merge physical files
  		delete all objects except first one
  		rename to new name
  		
	*****************************************************/

	private $tmpDir;

	function ___construct()
	{

    //setup our temp directory and make sure it exists
    $this->tmpDir = TMP_DIR."/".USER_LOGIN."/docmgradvedit";
    recurMkDir($this->tmpDir);
	
	}

	public function merge() 
	{

		$filepath = array();
		$obj = $this->objectId;

		if (count($obj)==0) 
		{
			$this->throwError(_I18N_NOMERGEFILE_ERROR);
			return false;
		}

		if (count($obj)==1) 
		{
			$this->throwError(_I18N_ONEMERGEFILE_ERROR);
			return false;
		}

		//make sure we have edit permissions for the first object
		$this->objectId = $obj[0];
		$bitset = DOCMGR_UTIL_OBJPERM::getUser($this->objectId);

 		//we have to have edit permissions on the destination file, and manage permissions on the others
 		//since they'll be deleted
	  if (!$this->permCheck("edit",$bitset))
	  {
 		  $this->throwError(_I18N_OBJECT_EDIT_ERROR);
 		  return false;
	  }

		for ($i=0;$i<count($obj);$i++) 
		{

			$sql = "SELECT id FROM docmgr.dm_file_history WHERE object_id='".$obj[$i]."' ORDER BY version DESC LIMIT 1";
			$info = $this->DB->single($sql);

			// get the filename
			$filepath[$i] = DATA_DIR."/".$this->getObjectDir($obj[$i])."/".$info["id"].".docmgr";
                                     		
		}

		//the output file is the first file
		$output = TMP_DIR."/merged-".USER_ID.".pdf";
		
		$cmd = APP_GS." -dNOPAUSE -sDEVICE=pdfwrite -sOUTPUTFILE=\"".$output."\" -dBATCH";
		for ($i=0;$i<count($obj);$i++) $cmd .= " \"".$filepath[$i]."\"";

		//run the merge
		`$cmd 1> /tmp/merge1 2>/tmp/merge2`;

		//get the name of the object we merged to
		$sql = "SELECT name FROM docmgr.dm_object WHERE id='".$obj[0]."'";
		$info = $this->DB->single($sql);
		
		//update our first file with the newly merged file
		$this->apidata["object_id"] = $obj[0];
		$this->apidata["filepath"] = $output;
		$this->apidata["name"] = $info["name"];

		//init our file object and update the first file in the list
		$f = new DOCMGR_FILE($this->apidata);
		$f->save();

		//now delete the other guys
		for ($i=1;$i<count($obj);$i++) 
		{
		
		  $this->objectId = $obj[$i];
		  $bitset = DOCMGR_UTIL_OBJPERM::getUser($this->objectId);
		  
  		//we have to have edit permissions on the destination file, and manage permissions on the others
  		//since they'll be deleted
		  if ($this->permCheck("admin",$bitset))
		  {
			  $o = new DOCMGR_OBJECT($obj[$i]);
			  $o->delete();
		  }

		}

	}


  public function advedit() 
  {

		//permissions check
		if (!$this->permCheck("edit"))
		{
		  $this->throwError(_I18N_OBJECT_EDIT_ERROR);
		  return false;
    }

    //setup our temp directory and make sure it's empty
    emptyDir($this->tmpDir);

    //we'll have several copies of files.  tiffs that we can actually work on, and pngs that we will see in the browser
    $tifftmp = $this->tmpDir."/tiff";
    $pngtmp = $this->tmpDir."/png";
    $hugepngtmp = $this->tmpDir."/hugepng";

    mkdir($tifftmp);
    
    //sanity checking  
    if (!is_dir($this->tmpDir)) 
    {
      $this->throwError(_I18N_TEMPDIRCREATE_ERROR);
      return false;
    }

    //get the file path for the file in question
    $sql = "SELECT id FROM docmgr.dm_file_history WHERE object_id='".$this->objectId."' ORDER BY version DESC LIMIT 1";
    $info = $this->DB->single($sql);

    // get the filename
    $filepath = DATA_DIR."/".$this->getObjectDir($this->objectId)."/".$info["id"].".docmgr";

    //split our pdf into individual files
    system(APP_PDFTOPPM." \"".$filepath."\" ".$tifftmp."/file 1>/tmp/file1 2>/tmp/file2");

    //copy our tiffs to the thumbnail dir
    copyDir($tifftmp,$pngtmp);
    copyDir($tifftmp,$hugepngtmp);

    //shrink the png files to something manageable for viewing
    $cmd = APP_MOGRIFY." -format png -geometry 75x100 ".$pngtmp."/*";
    `$cmd`;
    $cmd = APP_MOGRIFY." -format png -geometry 480x640 ".$hugepngtmp."/*";
    `$cmd`;
      
    $filearr = scandir($pngtmp);
     
    foreach ($filearr AS $file) 
    {
    
      //skip directory markers and directories
      if ($file=="." || $file==".." || strstr($file,".ppm")) continue;

      $arr = array();
      $arr["name"] = $file;
      $arr["path"] = $tifftmp."/".str_replace(".png",".ppm",$file);			//point to the real file
      $arr["small_thumb"] = $pngtmp."/".$file;			//point to the real file
      $arr["huge_thumb"] = $hugepngtmp."/".$file;			//point to the real file
    
      $this->PROTO->add("file",$arr);
    
    }
  
  }

  public function rotate() 
  {

    //setup our temp directory and make sure it's empty
    if (!is_array($this->apidata["file"])) $this->apidata["file"] = array($this->apidata["file"]);

    //we'll have several copies of files.  tiffs that we can actually work on, and pngs that we will see in the browser
    $tifftmp = $this->tmpDir."/tiff";
    $pngtmp = $this->tmpDir."/png";
    $hugepngtmp = $this->tmpDir."/hugepng";

    if ($this->apidata["direction"]=="right") $deg = "90";
    elseif ($this->apidata["direction"]=="flip") $deg = "180";
    else $deg = "270";

    foreach ($this->apidata["file"] AS $file) 
    {     

      if ($file=="." || $file=="..") continue;

      //rotate the real file
      $cmd = APP_MOGRIFY." -rotate $deg $file";
      `$cmd`;
      
      //rotate the thumbnail

      $thumbpath = $pngtmp."/".str_replace(".ppm",".png",$fn);
      $cmd = APP_MOGRIFY." -rotate $deg $thumbpath";
      `$cmd`;

      //rotate the large thumbnail
      $thumbpath = $hugepngtmp."/".str_replace(".ppm",".png",$fn);
      $cmd = APP_MOGRIFY." -rotate $deg $thumbpath";
      `$cmd`;
      
    }

    //if the order was changed, save that first
    if ($this->apidata["saveorder"]) $this->reorder();    

    //rescan and return the new directory structure
    $filearr = scandir($tifftmp);
    
    foreach ($filearr AS $file) 
    {

      if ($file=="." || $file=="..") continue;

      //spit out the new file list 
      $arr = array();
      $arr["name"] = $file;
      $arr["path"] = $tifftmp."/".$file;			//point to the real file
      $arr["small_thumb"] = $pngtmp."/".str_replace(".ppm",".png",$file);			//point to the real file
      $arr["huge_thumb"] = $hugepngtmp."/".str_replace(".ppm",".png",$file);			//point to the real file

      $this->PROTO->add("file",$arr);

    }
    
  }

  public function delete() 
  {

    //setup our temp directory and make sure it's empty
    if (!is_array($this->apidata["file"])) $this->apidata["file"] = array($this->apidata["file"]);

    //we'll have several copies of files.  tiffs that we can actually work on, and pngs that we will see in the browser
    $tifftmp = $this->tmpDir."/tiff";
    $pngtmp = $this->tmpDir."/png";
    $hugepngtmp = $this->tmpDir."/hugepng";

    foreach ($this->apidata["file"] AS $file) 
    {     

      if ($file=="." || $file=="..") continue;

      $fn = getFileName($file);

      $thumbpath = $pngtmp."/".str_replace(".ppm",".png",$fn);
      $hugethumbpath = $hugepngtmp."/".str_replace(".ppm",".png",$fn);

      //delete all necessary files
      unlink($file);
      unlink($thumbpath);
      unlink($hugethumbpath);

    }

    //if the order was changed, save that first
    if ($this->apidata["saveorder"]) $this->reorder();    

    //rescan and return the new directory structure
    $filearr = scandir($tifftmp);
    
    foreach ($filearr AS $file) 
    {

      if ($file=="." || $file=="..") continue;

      //spit out the new file list 
      $arr = array();
      $arr["name"] = $file;
      $arr["path"] = $tifftmp."/".$file;			//point to the real file
      $arr["small_thumb"] = $pngtmp."/".str_replace(".ppm",".png",$file);			//point to the real file
      $arr["huge_thumb"] = $hugepngtmp."/".str_replace(".ppm",".png",$file);			//point to the real file

      $this->PROTO->add("file",$arr);

    }
    
  }
  
  protected function reorder() 
  {

    //setup our temp directory and make sure it's empty

    //we'll have several copies of files.  tiffs that we can actually work on, and pngs that we will see in the browser
    $tifftmp = $this->tmpDir."/tiff";
    $pngtmp = $this->tmpDir."/png";

    //directories for putting renamed items in
    $tiffrename = $this->tmpDir."/tiffreorder";
    $pngrename = $this->tmpDir."/pngreorder";

    mkdir($tiffrename);
    mkdir($pngrename);
    
    $filearr = scandir($tifftmp);

    $c = 0;
    $filenum = 1;

    if (!is_array($this->apidata["reorderfile"])) 
      $this->apidata["reorderfile"] = array($this->apidata["reorderfile"]);
    
    foreach ($this->apidata["reorderfile"] AS $file) 
    {

      $newfile = "file-".str_pad($filenum,"6","0",STR_PAD_LEFT).".ppm";
      $filenum++;

      $new = $tiffrename."/".$newfile;
      rename($file,$new);

      //extract filename only to reference thumbs
      $fn = getFileName($file);
      
      //do again for the thumbnails
      $oldpng = $pngtmp."/".str_replace(".ppm",".png",$fn);
      $newpng = $pngrename."/".str_replace(".ppm",".png",$newfile);
      rename($oldpng,$newpng);

    }

    //remove old directories and replace with newly renamed files
    removeDir($tifftmp);
    removeDir($pngtmp);
    
    rename($tiffrename,$tifftmp);
    rename($pngrename,$pngtmp);

  }

  public function commit() 
  {

		//permissions check
		if (!$this->permCheck("edit"))
		{
		  $this->throwError(_I18N_OBJECT_EDIT_ERROR);
		  return false;
    }
  
    //setup our temp directory and make sure it's empty
    //we'll have several copies of files.  tiffs that we can actually work on, and pngs that we will see in the browser
    $tifftmp = $this->tmpDir."/tiff";
    $pngtmp = $this->tmpDir."/png";

    //if the order was changed, save that first
    if ($this->apidata["saveorder"]) $this->reorder();    

    //convert all to individual pdf files
    $cmd = APP_MOGRIFY." -format pdf ".$tifftmp."/*.ppm";
    `$cmd`;
      
    $filearr = scandir($tifftmp);

    //recreate the file in a temp directory
    $output = $this->tmpDir."/output.pdf";
    $cmd = APP_GS." -dNOPAUSE -sDEVICE=pdfwrite -sOUTPUTFILE=\"".$output."\" -dBATCH";

    foreach ($filearr AS $file) 
    {
      if (strstr($file,".pdf")) $cmd .= " \"".$tifftmp."/".$file."\"";
    }

    //remerge all files back over the original
    `$cmd`;  

		//get the name of the object we merged to
		$sql = "SELECT name FROM docmgr.dm_object WHERE id='".$this->objectId."'";
		$info = $this->DB->single($sql);

		//update our first file with the newly merged file
		$this->apidata["filepath"] = $output;
		$this->apidata["name"] = $info["name"];

		//recommit our file
		$f = new DOCMGR_FILE($this->apidata);
		$f->save();

  }

  public function optimize()
  {

		//permissions check
		if (!$this->permCheck("edit"))
		{
		  $this->throwError(_I18N_OBJECT_EDIT_ERROR);
		  return false;
    }

    //sanity checking  
    if (!is_dir($this->tmpDir)) 
    {
      $this->throwError(_I18N_TEMPDIRCREATE_ERROR);
      return false;
    }

    //get the file path for the file in question
    $sql = "SELECT id FROM docmgr.dm_file_history WHERE object_id='".$this->objectId."' ORDER BY version DESC LIMIT 1";
    $info = $this->DB->single($sql);

    // get the filename
    $filepath = DATA_DIR."/".$this->getObjectDir($this->objectId)."/".$info["id"].".docmgr";
    $output = $this->tmpDir."/output.pdf";
    
    //split our pdf into individual files
    $cmd = APP_GS." -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/".$this->apidata["optimize_level"]." -dNOPAUSE -dQUIET -dBATCH -sOutputFile=".$output." ".$filepath;
    `$cmd`;

    if ($this->apidata["destination"]=="download")
    {
      $url = $this->tmpDir."/output.pdf";
      $url = str_replace(SITE_PATH,SITE_URL,$url);
      
      $this->PROTO->add("url",$url);
    }
    else
    {
      $name = str_ireplace(".pdf"," ".ucfirst($this->apidata["optimize_level"]).".pdf",$this->objectInfo["name"]);
      
      $opt = null;
      $opt["filepath"] = $output;
      $opt["name"] = $name;
      $opt["parent_path"] = dirname($this->path);
      $opt["exist_rename"] = 1;
      
      $f = new DOCMGR_FILE($opt);
      $f->save();
     
      if ($f->getError()) $this->throwError($f->getError());
        
    }

  
  }

}


