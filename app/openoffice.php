<?php
class OPENOFFICE 
{

  private $origFile;
  private $ooFile;
  private $ooname;
  private $fileName;
  private $tmpdir;
  private $prefix;
  private $suffix;
  private $content;
  private $errorMessage;
  private $objectId;
  private $zip;
    
  function __construct($path,$objId=null) 
  {

    $this->origFile = $path;
    $this->fileName = getFileName($path);
    $this->objectId = $objId;
    $this->zip = new ZipArchive;
      
    //make our temp directories
    $this->makeTemp();    
    $info = fileInfo($this->origFile);
    $ooext = $info["openoffice"];

    //convert to an openoffice file for processing    
    if ($ooext) $this->ooFile = $this->convert($ooext,$this->origFile);

    //die if we had a problem
    if (!$this->ooFile || !file_exists($this->ooFile)) 
    {
      $this->throwError("Could not create openoffice file from source");
      return false;
    }
    else 
    {
      $this->ooName = getFileName($this->ooFile);
    }
    
  }

  function throwError($err) 
  {
  
    $this->errorMessage = $err;
    
  }

  function getError()
  {
    return $this->errorMessage;
  }
  
  function error() {
  
    return $this->errorMessage;
    
  }

  function getExtension($filename) 
  {

    $convname = getFileName($filename);
  
    //get our file's current extension
    $pos = strrpos($convname,".");  
    $ext = substr($convname,$pos+1);

    return strtolower($ext);  

  }


  /***************************************************
    make a temp directory for working with the files
  ***************************************************/
  function makeTemp() {

    //make a temp directory for workign with files
    $this->tmpdir = TMP_DIR."/".USER_LOGIN."/openoffice";
    recurmkdir($this->tmpdir);

    //clear it out
    $cmd = "rm ".$this->tmpdir."/*";
    `$cmd`;
    
  }

  /*************************************************************
    get the contents of the file
  *************************************************************/
  function getFileContents() {
  
    //unzip it
    $this->zip->open($this->ooFile);
    $data = $this->zip->getFromName("content.xml");
    $this->zip->close();          
  
    return $data;
  
  }
  
  /*************************************************************
    set the contents of the file
  *************************************************************/
  function setFileContents($data = null) {

    //if not passed data, used the file data stored in the object
    if (!$data) $data = $this->prefix.$this->content.$this->suffix;

    //update the archive with the new contents
    $this->zip->open($this->ooFile);
    $data = $this->zip->addFromString("content.xml",$data);
    $this->zip->close();          

  }

  /*************************************************************
    convert a file from one file type to another
  *************************************************************/
  function convert($newext,$convfile=null) {

    $newext = strtolower($newext);

    if (!$convfile) $convfile = $this->ooFile;
    
    //get our file's current name and extension
    $convname = getFileName($convfile);
    $ext = fileExtension($convname);

    //if our old extension equals our new extension, stop here
    if ($newext==$ext) $dest = $convfile;
    else 
    {

      //error obtaining source file
      if (!$convfile || !file_exists($convfile))
      {
        $this->throwError("Could not find file to convert");
        return false;
      }

      //if the source is html and the destination is something else, rewrite
      //the images so we can still view them
      if ($ext=="html" && $newext!="html") $this->fixHtmlImages($convfile);

      //add the new extension and put it in our oo temp directory
      $dest = str_replace(".".$ext,".".$newext,$convname);
      $dest = $this->tmpdir."/".$dest;

      //convert the file to the new extension  
      $cmd = "php bin/fileconvert.php \"".$convfile."\" \"".$dest."\"";
      `$cmd`;

      //conversion wasn't completed
      if (!file_exists($dest))
      {
        $this->throwError("File conversion failed");
        return false;
      }

      //if the new file is html, we have to do some special stuff
      if ($newext=="html") 	
        $this->imgLocalToRemote($convfile,$dest);

      else if ($ext=="html") 	
        $this->imgRemoteToLocal($dest);

    }
    
    //if destination exists, return it's path, otherwise return false
    if (file_exists($dest)) return $dest;
    else return false;
  
  }

  /***********************************************************
    split our file content into header,content,and footer
  ***********************************************************/
  function parseFile() {
  
    $data = $this->getFileContents();

    //extract the content
    $begin = "</text:sequence-decls>";
    $end = "</office:text>";

    //get the position for the start and end of our content
    $pos1 = stripos($data,$begin) + strlen($begin);					//content starts after this
    $pos2 = stripos($data,$end);														//content ends after this
    
    //split into sections
    $this->prefix = substr($data,0,$pos1);
    $this->suffix = substr($data,$pos2);
    $this->content = substr($data,$pos1,$pos2-$pos1);
  
  }

  /**********************************************************	
    return contents of the file
  **********************************************************/
  function getContent() {
    return $this->content;
  }

  /**********************************************************
    duplicates a page of the file within the file on a 
    new page
  **********************************************************/
  function duplicate($data) {
  
    //this makes our page break at the page tag
    $break = "<style:style style:name=\"MANUALPAGE\" style:family=\"paragraph\" style:parent-style-name=\"Standard\">";
    $break .= "<style:paragraph-properties fo:break-before=\"page\"/>";
    $break .= "</style:style>";
    $break .= "</office:automatic-styles>";  

    $search = "</office:automatic-styles>";  
    //$pb = "<text:p text:style-name=\"MANUALPAGE\"></text:p>";		removing this shouldn't work, but it does
    $pb = null;
    
    //if our pagebreak style isn't in there, add it
    if (!stristr($this->prefix,$break)) $this->prefix = str_ireplace($search,$break,$this->prefix);

    $this->content = $this->content.$pb.$data;

  }

  /*******************************************************
    set the content of the file
  *******************************************************/  
  function setContent($data) {
    $this->content = $data;
  }


  /********************************************************************
    convert html to non-html images
  ********************************************************************/
	private function fixHtmlImages($file)
	{
	
	  $data = file_get_contents($file);
	  
	  //logged in as normal, just use the easy way of plugging in a valid session id into the url
	  if (!defined("DOCMGR_SCRIPT"))
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

  /********************************************
    return the thumbnail for our document
  ********************************************/
  function getThumbnail() {

    //unzip it
    $this->zip->open($this->ooFile);
    $data = $this->zip->getFromName("Thumbnails/thumbnail.png");
    $this->zip->close();          
    
    //if no thumbnail, convert our odt file to another odt file to get the thumbnail
    //this is a workaround for an openoffice bug where oo doesn't create the thumbnail file
    //when converting html to odt
    if (!$data)
    {

      //just gonna make a new file w/ the same extension
      //$newfile = str_replace("worker.","worker1.",$this->ooFile);    
      $thumbfile = str_replace(".odt",".png",$this->ooFile);
      $newfile = $this->convert("pdf");
            
      //try to extract thumbnail again from new file
      if (file_exists($newfile))
      {

        system(APP_CONVERT." -resize 100x100 \"".$newfile."[0]\" \"".$thumbfile."\"");        
        $data = file_get_contents($thumbfile);
                
      }

    }

    return $data;

  }

  function imgRemoteToLocal($file)
  {

    //open our oo file
    $this->zip->open($file);

    //get our content
    $dom = new DOMDocument();
    $dom->preserveWhitespace = false;
    $dom->loadXML($this->zip->getFromName("content.xml"));    

    //extract pictures
    $imgs = $dom->getElementsByTagNameNS("urn:oasis:names:tc:opendocument:xmlns:drawing:1.0","image");

    //we found some images, handle them
    if ($imgs->length > 0)
    {

      //for storing the pics we worked on                   
      $addPics = array();

      //we are converting from html to non-html, so we can assume there is no pics diretory
      $this->zip->addEmptyDir("Pictures");

      for ($i=0;$i<$imgs->length;$i++)
      {
      
        $src = $imgs->item($i)->getAttribute("xlink:href");

        //if it's a docmgr object, pull the name
        if (strstr($src,"objectId="))
        {

          //extract objectId from string 
          $query = substr($src,strpos($src,"?")+1);
          parse_str($query);

          //snag the pic from the api
          $d = new DOCMGR_FILE($objectId);
          $name = $d->getObjectName();
          $ext = fileExtension($name);
          $pic = $d->get("contentonly");
                
        }
        else
        {
        
          //setup the new name for it       
          $ext = fileExtension($src);

          //get the pic from the url
          $pic = file_get_contents($src);

        }

        //uniquely named pic
        $newname = "Pictures/".short_uuid().".".$ext; 

        //copy to Pics directory
        $this->zip->addFromString($newname,$pic);

        //now update the image source to the new one
        $imgs->item($i)->setAttribute("xlink:href",$newname);

        //add to our metafile
        $addPics[] = $newname;

      }

      //save our content.xml file with the new urls      
      $this->zip->addFromString("content.xml",$dom->saveXML());

      //now update our file manifest
      $dom = new DOMDocument();
      $dom->preserveWhitespace = false;
      $dom->loadXML($this->zip->getFromName("META-INF/manifest.xml"));

      //first pics directory
      $e = $dom->createElement("manifest:file-entry");
      $e->setAttribute("manifest:media-type","");
      $e->setAttribute("manifest:full-path","Pictures/");
      $dom->documentElement->appendChild($e);
      
      //now the images
      foreach ($addPics AS $pic)
      {

        $info = fileInfo($pic);
        
        $e = $dom->createElement("manifest:file-entry");
        $e->setAttribute("manifest:media-type",$info["mime_type"]);
        $e->setAttribute("manifest:full-path",$pic);
        $dom->documentElement->appendChild($e);
         
      }

      //write the manifest file
      $this->zip->addFromString("META-INF/manifest.xml",$dom->saveXML());

    }
    
    //close her up
    $this->zip->close();


  }

  /***********************************************************************
    FUNCTION:	fixWebImg
    PURPOSE:	extracts images in an oo document and stores them in
              docmgr, the links the img tags to them
  ***********************************************************************/  
  function imgLocalToRemote($src,$dest)
  {

    global $DB;


    $this->zip->open($src);
    
    //now update our file manifest
    $dom = new DOMDocument();
    $dom->preserveWhitespace = false;
    $dom->loadXML($this->zip->getFromName("META-INF/manifest.xml"));

    $files = $dom->getElementsByTagNameNS("urn:oasis:names:tc:opendocument:xmlns:manifest:1.0","file-entry");
    $pics = array();
    
    for ($i=0;$i<$files->length;$i++)
    {
    
      $e = $files->item($i);
      $path = $e->getAttribute("manifest:full-path");

      //if it's in pictures, but isn't "Pictures/" get it
      if (strstr($path,"Pictures/") && $path!="Pictures/")
      {
      
        $picdata = $this->zip->getFromName($path);
        $tmpname = TMP_DIR."/".USER_LOGIN."/".short_uuid();
        $name = str_replace("Pictures/","",$path);
                
        file_put_contents($tmpname,$picdata);
        $pics[] = array($name,$tmpname);

      }

    }

    //if we don't have any pictures, we can stop here
    if (count($pics) > 0)
    {
	
	    if ($this->objectId) 
	    {
	
	      //we are converting, so clear out any existing documents and/or create a storage folder
	      $opt = null;
	      $opt["object_id"] = $this->objectId;
	      $opt["clearall"] = "1";

	      $d = new DOCMGR_OBJECT($opt);
	      $storageId = $d->createStorage();

	    } 
	    else 
	    {

	      $d = new DOCMGR_OBJECT();
	      $storageId = $d->createTemp();
        $d->emptyTemp();

	    }

	    //pictures we can keep
	    $sessionId = session_id();

      //load our html into a file
      $dom = new DOMDocument();
      $dom->preserveWhitespace = true;
      $dom->formatOutput = true;
      $dom->loadHTML(file_get_contents($dest));
      $imgs = $dom->getElementsByTagName("img");

      for ($i=0;$i<count($pics);$i++)
      {

        //easy access, baby      
        $file = &$pics[$i];
        $picname = $file[0];
        $picfile = $file[1];
        	      
        $opt = null;
        $opt["parent_id"] = $storageId;
        $opt["name"] = $picname;
	      $opt["filepath"] = $picfile;
	      $opt["overwrite"] = 1;
	      
        //save the image to docmgr
        $d = new DOCMGR_FILE($opt);
        $objId = $d->save();

        //store the image view url in the docmgr file
        $path = SITE_URL."app/viewimage.php?objectId=".$objId."&[DOCMGR_SESSION_MARKER]";
        $imgs->item($i)->setAttribute("src",$path);

        //remove the temporary file
        @unlink($picfile);
	      
	    }

	    //write back our updated content
	    file_put_contents($dest,$dom->saveHTML());

    }

  }


}
