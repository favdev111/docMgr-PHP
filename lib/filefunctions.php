<?php

/*******************************************************************
  FILENAME: filefunctions.php
  PURPOSE:	holds custom file management functions
*******************************************************************/

function listDir($path,$rec=1,$filter=null) {

  $ret = array();
  
  $arr = scandir($path);
  $num = count($arr);
  
  for ($i=0;$i<$num;$i++) {

    if ($arr[$i]=="." || $arr[$i]=="..") continue;

    $obj = $path."/".$arr[$i];

    //if we have a filter, make sure the file type is in it (don't process directoreis)
    if (!is_dir($obj) && $filter) {
    
      $ext = fileExtension($obj);
      if ($ext) $ext = substr($ext,1);	//remove lead "."
      
      if (!in_array($ext,$filter)) continue;

    }

    //if we have a filter and it's not set to directory, then don't return directories
    if ($filter && is_dir($obj) && !in_array("directory",$filter)) {
      //do nothing
    } else {
      //add to the return array
      $ret[] = $obj;
    }

    //if recursive, look for more files       
    if (is_dir($obj) && $rec) $ret = array_merge($ret,listDir($obj,$rec,$filter));


  }

  return $ret;

}

/*******************************************************************
  FILENAME: emptyDir
  PURPOSE:	removes all files in a directory
*******************************************************************/
function emptyDir($path)
{

  if (!is_dir($path)) return false;

  $files = listDir($path);
  
  if (count($files)==0) return false;

  //this makes sure the files in a directory are gone before we
  //try to delete the actual directory
  $files = array_reverse($files);
    
  foreach ($files AS $file) 
  {
    if (is_dir($file)) rmdir($file);
    else unlink($file);
  }

}

/*********************************************************
//this function figures out the path to our files, based on whether or
//not the filePath is relative
*********************************************************/
function getFilePath($filePath,$altFilePath) {

    //there is a leading slash, it's an absolute path
    if ($filePath[0]=="/") return $filePath;
    else {

        //return our relative path appended to the path
        //of the docmgr installation
        return $altFilePath."/".$filePath;

    }

}
/*******************************************************************
//  this function spits out the files in chunks to 
//  allow for larger files to be downloaded even though
//  memory_limit is low.  I took this directly from
//  the php website.
*******************************************************************/

function readfile_chunked ($filename) {

    $chunksize = 1*(1024*1024); // how many bytes per chunk (this is 1 mb)

    $buffer = null;

    if (!$handle = fopen($filename, 'rb')) return false;

    while (!feof($handle)) {
        $buffer = fread($handle, $chunksize);
        print $buffer;
    }

    return fclose($handle);

}
/*********************************************************
*********************************************************/
function fileExtension($file) {

    $pos = strrpos($file,".");
    if ($pos == "0") return false;
    else {

        $ext = strtolower(substr($file,$pos+1));

        return $ext;

    }
}
/*********************************************************
*********************************************************/
function fileIncludeType($ext) {

    $imageArray = array(    ".jpg",
                            ".png",
                            ".bmp",
                            ".gif",
                            ".tif",
                            ".tiff",
                            ".jpeg"
                            );

    $embedArray = array(    ".avi",
                            ".pdf",
                            ".mov",
                            ".doc"
                            );



    if (in_array($ext,$embedArray)) return "embed";
    elseif (in_array($ext,$imageArray)) return "image";
    else return "include";

}


/****************************************************************************
	return an array with all possible information regarding this extension
****************************************************************************/

function return_file_type($filename) {

  $info = fileInfo($filename);
  return $info["custom_type"];
}

function return_file_mime($filename) {

  $info = fileInfo($filename);
  return $info["mime_type"];

}

//returns true if we can index the file, false if we can't
function return_file_idxopt($filename) {

  $info = fileInfo($filename);
  return $info["prevent_index"];
	
}


function return_file_proper_name($filename) {

  $info = fileInfo($filename);
  return $info["proper_name"];

}


function return_file_info($filename,$filepath = null) {

  return fileInfo($filename);

}

					
function return_file_extension($filename) {

  return fileExtension($filename);

}

/********************************
	new file info functions
********************************/
function fileInfo($file) {

	if (!$_SESSION["extensions"]) {
	
		//get our extension config
		$xml = file_get_contents(SITE_PATH."/config/extensions.xml");
	
		$arr = XML::decode($xml);
		$_SESSION["extensions"] = $arr["object"];

  }
  
  $ext = fileExtension($file);
  $num = count($_SESSION["extensions"]);

  $fileinfo = array();
  
  for ($i=0;$i<$num;$i++) {

    //if this extension matches the passed one, stop
    if ($_SESSION["extensions"][$i]["extension"]==$ext) {
      $fileinfo = $_SESSION["extensions"][$i];
      break;
    }
    
  }

  return $fileinfo;
  
}

function displayFileSize($size,$floatsize = 2) {

  $kbTest = 1024;
  $mbTest = 1024*1024;
  $gbTest = 1024*1024*1024;

  //if greater than this size, return in kilobytes
  if ($size>=$gbTest) $size = number_format($size/$gbTest,$floatsize)." GB";
  elseif ($size>=$mbTest) $size = number_format($size/$mbTest,$floatsize)." MB";
  else $size = number_format($size/$kbTest,$floatsize)." KB";
  //else $size = $size." B";

  return $size;

}

/*********************************************************************
  FUNCTION:	extractFileName
  PURPOSE:	pulls a file's name from a full path
*********************************************************************/
function extractFileName($path) {

  return array_pop(explode("/",$path));

} 

/*******************************************************
  FUNCTION:	recurmkdir
  PURPOSE:	recursively makes the desired directory
            on the filesystem
*******************************************************/
function recurMkDir($path) 
{

  $arr = explode("/",$path);
  $cur = null;

  foreach ($arr AS $dir)
  {

    $cur .= $dir."/";
    if (!file_exists("$cur")) mkdir("$cur");

  }

}


function listDirectory($directory,$extFilter=null,$nameFilter=null) 
{

  $resultArray = array();

  $handle=@opendir($directory);

  while ($file = @readdir($handle)) 
  {

    //skip directory markers, hidden directorys, and joe leftovers
    if ($file!="." && $file!=".." && $file[0]!="." && substr($file,-1)!="~" && !strstr($file,"THUMB_")) 
    {

      //skip this one if the name filter isn't matched
      if ($nameFilter) 
      {

        $pos = strpos($file,trim($nameFilter));

        if ($pos===FALSE) continue;

      }

      //is our filter an array o
      if (is_array($extFilter)) 
      {

        //get the extension;
        $pos = strrpos($file,".");
        $ext = strtolower(substr($file,$pos));

        if (in_array($ext,$extFilter)) $resultArray[] = $file;

      }
      else 
      {
        $resultArray[] = $file;
      }

    }

  }

  @closedir($handle);

  return $resultArray;

}

function copyDir($src,$dest)
{

	$list = listDir($src);
	
	//make our destination
	recurMkDir($dest);
	
	if (count($list) > 0)
	{
	
		foreach ($list AS $path)
		{
		
			//replace the path part of our object with the destination
			$destpath = str_replace($src,$dest,$path);
			
			//it's a directory
			if (is_dir($path))
			{
			
				//now copy the contents of the directory
				copyDir($path,$destpath);
			
			}
			//it's a file, just copy it
			else
			{
	  		copy($path,$destpath);
			}
		
		}
	
	}

}

/**
  because I'm stupid and didn't realize there was already a function that did this
  */
function getFileName($path)
{
  return basename($path);
}
