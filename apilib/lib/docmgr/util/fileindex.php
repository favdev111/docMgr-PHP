<?php

class DOCMGR_UTIL_FILEINDEX
{

	private $oomap;
	private $type;
	private $ext;
	private $filename;
	private $filepath;
	
	function __construct($filename,$filepath) 
	{

		$this->filename = $filename;
		$this->filepath = $filepath;
		
	}
	
	function getContent() 
	{

		$info = fileInfo($this->filename);
		$ext = fileExtension($this->filename);
		$content = null;
		
		if ($info["openoffice_index"]) 
		{

			//make our temp directory
			$tmp = TMP_DIR."/".USER_LOGIN;
			recurmkdir($tmp);
			$worker = $tmp."/worker.".$ext;

			//copy the file to there with the right extension
			copy($this->filepath,$worker);

			//start openoffice.  it will convert to oo if it's not an openoffice file
			$oo = new OPENOFFICE($worker);

			//return the file contents minus xml tags
			$content = DOCMGR_OBJINDEX::removeTags($oo->getFileContents());

			//remove temp directory
			$cmd = "rm -r ".$worker;
			`$cmd`;
			
		//the old way	
		} 
		else 
		{

			$fileType = return_file_type($this->filename); 	   

			if (!$fileType) return false;

			$function = "get".$fileType."content";

	    //extract content from the file by calling the associated
	    //function for that file type
	    if (method_exists("DOCMGR_UTIL_FILEINDEX",$function)) 
	    	$content = DOCMGR_UTIL_FILEINDEX::$function($this->filepath,$fileType);
	
		}

		return $content;
		                     	
	}	

	/******************************************************************
		Our content extraction functions
	******************************************************************/
	
	function getImageContent ($filepath,$ext) 
	{
	
		$multiOCR = null;
		$rs = null;

		if (!defined("OCR_SUPPORT")) return false;

		//check to see if this is a tiff image with multiple pages
		if ($ext=="tiff") 
		{

			//find out if multipage, use tiffinfo
			$app = APP_TIFFINFO;
			$numpages = `$app "$filepath"`;
			$numpages=substr_count($numpages,"TIFF Directory");
	
			$dir = substr($filepath,0,strrpos($filepath,"/") + 1);
	
			//if there is more than one tiff image, use the proccessing below
			if ($numpages>1) 
			{
	
				$multiOCR = 1;
	
				//we will ocr each page seperately, then form one string
				//and index that string
	
				//split the file and return the names
				$filePrefix = DOCMGR_UTIL_FILEINDEX::tiffSplit($numpages,"$filepath");
	
				$tempFile = createTempFile();
				$dirPrefix = $dir.$filePrefix;
				
				//get all files with this file prefix in the directory
				$tiffArray=listDirectory($dir,array(".tif"),$filePrefix);
				$pnmArray = array();
	
				$timeout = EXECUTION_TIME * $numpages;
				ini_set("max_execution_time","$timeout");    //putting this here is an experiment
	
				system(APP_MOGRIFY." -format pnm ".$dirPrefix."*.tif");
	
				if (defined("MAX_OCR")) $count = MAX_OCR;
				else $count="1";
				
				for ($row=0;$row<count($tiffArray);$row++) {
				
					$pnmArray[$row] = $dir.str_replace(".tif",".pnm",$tiffArray[$row]);
				
				}
				
				//run the ocr program
				$string1 = DOCMGR_UTIL_FILEINDEX::advOcr($pnmArray);

				//clean up the string
				$newstring = DOCMGR_OBJINDEX::clean($string1,null,null);
	
				//delete our temp files
				for ($row=0;$row<count($pnmArray);$row++) {
	
					$tiffFile = $dir.$tiffArray[$row];
					//delete the temp file
					@unlink("$tiffFile");
					@unlink("$pnmArray[$row]");
	
				}
	
			}
	
		}
	
		//use this if a single image, or a single page tiff
		if (!$multiOCR) 
		{
	
			/* OCR section, the file is converted from orig type to pnm, and
			   then scanned with the ocr software.  The only file type
		 	   limitations are any pic files not supported by imagemagick */
	
			$filepath1 = TMP_DIR."/".rand().".pnm";
	
			//convert to grayscale, pnm format
			system(APP_CONVERT." ".$rs." \"".$filepath."\" \"".$filepath1."\" 2>&1");

			if (defined("MAX_OCR")) $count = MAX_OCR;
			else $count="1";

			//ocr the image and return string as a variable
			$string1 = DOCMGR_UTIL_FILEINDEX::advOcr(array($filepath1));

			//the order of the steps below may need to change, we will see
			$newstring = DOCMGR_OBJINDEX::clean($string1,null,null);

			//delete the temp file
			@unlink("$filepath1");
	
		}
	
		return $newstring;
	
	}
	
	function getTiffContent($filepath) {

		return DOCMGR_UTIL_FILEINDEX::getImageContent($filepath,"tiff");
	
	}
	
	function getTxtContent($filepath) {

		return file_get_contents($filepath);
	
	}
	
	function getMarkupContent($filepath) {

		$str = file_get_contents($filepath);
		return DOCMGR_OBJINDEX::removeTags($str);	
	
	}
	
	function getPDFContent($filepath) {
	
		//one of these has to be defined
		if (!defined("PDF_SUPPORT")) return false;
		if (defined("DISABLE_PDF")) return false;
		$newstring = DOCMGR_UTIL_FILEINDEX::xpdfProcess($filepath);

		//clean it up
		return $newstring;
	
	}
	
	function getOtherContent($filepath) {
	
		return true;
		
	}
	
	//this way, we pull an text from the file and ocr any images
	function xpdfProcess($filepath) {

		//get any text that we can
		$pdftotext = APP_PDFTOTEXT;
		$newstring = `$pdftotext "$filepath" - 2>/dev/null`;

		//if we have disabled encapsulated pdf support, or have no ocr support, return here
		if (defined("DISABLE_ENCAP_OCR") || !defined("OCR_SUPPORT")) return $newstring;

		//extract all images to a directory with this prefix
		$dir = TMP_DIR."/".rand()."/";
		$prefix = $dir."files";
	
		mkdir($dir);

		//extract images from the pdf.  this will also handle encapsulated pdfs
		system(APP_PDFIMAGES." -q \"".$filepath."\" \"".$prefix."\" 2>/dev/null");	

		if (defined("MAX_OCR")) $count = MAX_OCR;
		else $count="1";
	
		$fileArray = listDir($dir);

		//append the ocr'd content from the extracted images
		$newstring .= DOCMGR_UTIL_FILEINDEX::advOcr($fileArray);

		//delete our tmp images
		for ($row=0;$row<count($fileArray);$row++) unlink($fileArray[$row]);
	
		//remove the temp directory
		rmdir($dir);

		return $newstring;
		
	}
	
	function advOcr($fileArray) {
	
		//reduce our file array so we know we don't have empty values
		$files = reduceArray($fileArray);
		$num = count($files);
		
		$str = null;

		for ($i=0;$i<$num;$i++) 
		{
	
			$cmd = APP_OCR." \"".$files[$i]."\"";
			$str .= `$cmd`;
			$str .= " ";
			
		}

		return $str;
	
	}
	
	
	//this function splits a tif file, and returns the names
	//of the files it creates as an array
	function tiffSplit($numpages,$userfile) {
	
		//split the tiff file
		//figure out what the names of the temp files will be.
		$pos = strrpos($userfile,"/");
		$dir_value = substr($userfile,0,$pos)."/";
	
		$firstloop = floor($numpages/26);
		$firstremainder = $numpages%26;
	
		//generate our file prefixes
		$file_prefix_num = rand(1,10000);
		$prefix_value = $dir_value.$file_prefix_num;
	
		//split the file
		exec(APP_TIFFSPLIT." ".$userfile." ".$prefix_value." 2>&1");
	
		return $file_prefix_num;
	
	}

	function getEmailContent($file)
	{

		$ei = new DOCMGR_UTIL_EMAILINDEX($file);
    return $ei->getIndex();

  }
	
	
}