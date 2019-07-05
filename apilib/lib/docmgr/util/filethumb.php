<?php

class DOCMGR_UTIL_FILETHUMB
{

	private $oomap;
	private $filename;
	private $filepath;
	private $thumb;		//path of the file we create
	private $mode;		//our mode, (thumb or preview)
	private $imageSize;	//the size of the image we'll create
	
	//constructor	
	public function __construct($mode,$filepath,$filename,$thumb) 
	{
		$this->filename = $filename;
		$this->filepath = $filepath;
		$this->thumb = $thumb;
		$this->setMode($mode);	
		$this->makeThumbnail();
	}
	
	//change the current mode of this class DOCMGR_(preview or thumbnail)	
	public function setMode($newMode) 
	{

		$this->mode = $newMode;
		
		if ($newMode=="preview") $this->imageSize = "1200x1200";
		else $this->imageSize = "100x100";
				
	}
	
	//master function for thumbnailing the passed file	
	function makeThumbnail() {

		$ext = fileExtension($this->filename);
		$info = fileInfo($this->filename);

		if (!file_exists($this->filepath)) return false;

		//new way.  if openoffice supports it, go that route
		if ($info["openoffice_thumb"]) 
		{

			//make our temp directory
			$tmp = TMP_DIR."/".USER_LOGIN;
			recurmkdir($tmp);
			$worker = $tmp."/worker.".$ext;

			//copy the file to there with the right extension
			copy($this->filepath,$worker);

			//start openoffice.  it will convert to oo if it's not an openoffice file
			$oo = new OPENOFFICE($worker);

			//convert to a pdf
			$pdf = $oo->convert("pdf");
			
			if ($pdf)
			{
			
				//thumbnail the pdf
				system(APP_CONVERT." -resize ".$this->imageSize." \"".$pdf."[0]\" \"".$this->thumb."\"");

			}
			
			//remove worker file
			@unlink($worker);

		//the old way	
		} 
		else 
		{

			$fileType = return_file_type($this->filename); 	   
			$function = "create".$fileType."thumb";
        
			//extract content from the file by calling the associated
			//function for that file type
			if (method_exists($this,$function)) return DOCMGR_UTIL_FILETHUMB::$function($this->filepath,$fileType);
	
		}
	
	}	

	//get the current dimensions for this image	
	private function getImageDimensions($file = null) {

		//default to the passed file if not set	
		if (!$file) $file = $this->filepath;
		$arr = @getImageSize($file);
	
		if (!$arr[1]) return;

		$ratio = floatValue($arr[0] / $arr[1],"2");

		//get our size for the end ratio
		$finalArr = explode("x",$this->imageSize);
		$fw = $finalArr[0];
		$fh = $finalArr[1];

		//if the width and height is smaller, just return the image height
		if ($arr[0] <= $fw && $arr[1] <= $fh) {
			$dim = $arr[0]."x".$arr[1];
		}
		else if ($arr[0] <= $fw && $arr[1] > $fh) {
			$dim = intValue($fw/$ratio)."x".$fh;
		}
		else if ($arr[0] > $fw && $arr[1] <= $fh) {
			$dim = $fw."x".intValue($fh/$ratio);
		} 
		else {
			$dim = $this->imageSize;
		}

		return $dim;
	
	}

	//make a thumbnail for a regular image	
	public function createImageThumb() {

		$dim = $this->getImageDimensions();

		if (!$dim) return;

		//do not alter the image if it's too small, or shrink if it's too long
		if ($this->mode=="thumb") {
			system(APP_MONTAGE." -size ".$dim." -gravity center \"".$this->filepath."\" \"".$this->thumb."\"");
		} else {
			system(APP_CONVERT." -size \"".$dim."\" -thumbnail \"".$dim."\" \"".$this->filepath."\" \"".$this->thumb."\"");
		}
		
	}

	//make a thumbnail for tiff images	
	public function createTiffThumb() {

		//do we support tiff indexing
		if (!defined("TIFF_SUPPORT")) return false;
	
		//find out if multipage, use tiffinfo
		$app = APP_TIFFINFO;
		$numpages=`$app "$this->filepath"`;
		$numpages=substr_count($numpages,"TIFF Directory");

		$dim = $this->getImageDimensions();

		if (!$dim) return;
	
		//only one page, just resize it and be on our way
		if ($numpages=="1") {
		
			system(APP_CONVERT ." -resize ".$dim." \"".$this->filepath."\" \"".$this->thumb."\"");
		
		} else {
	
			$dir = TMP_DIR."/".rand();
			$tifprefix = $dir."/tiff";
	
			mkdir($dir);
			system(APP_TIFFSPLIT." \"".$this->filepath."\" $tifprefix");
			
			$fileArr = listDirectory($dir,null,null);
	
			$file = $dir."/".$fileArr[0];	
	
			system(APP_CONVERT." -resize ".$dim." \"".$file."\" \"".$this->thumb."\"");
					
			//todo: replace this with php function	
			if (is_dir($dir)) `rm -r $dir`;
	
		}
	
	}
	
	//handle pdfs
	public function createPdfThumb() {

		//now resize it
		//$cmd = APP_CONVERT." -resize ".$this->imageSize." \"".$this->filepath."[0]\" \"".$this->thumb."\"\n";
		system(APP_CONVERT." -resize ".$this->imageSize." \"".$this->filepath."[0]\" \"".$this->thumb."\"");
					
	}

	public function createEmailThumb()
	{

		$ei = new DOCMGR_UTIL_EMAILINDEX($this->filepath);
    $thumb = $ei->getThumb();	

    //write to our destination
    file_put_contents($this->thumb,$thumb);
	     
	}

}
