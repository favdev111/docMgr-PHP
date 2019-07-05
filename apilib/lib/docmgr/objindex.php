<?php

//we're extending AOBJECT here so we have access to the objects' index methods
class DOCMGR_OBJINDEX extends DOCMGR_AOBJECT
{

  public $objectId;
  protected $DB;
  protected $content;
  
  function __construct($objId) 
  {
  
    $this->objectId = $objId;
    $this->DB = $GLOBALS["DB"];
    
  }

	/**********************************************************************
	  main function to call when you want to index an object
  **********************************************************************/

	function run($accountId,$notifyUser="f",$propOnly=null) {

	  //if updating properties only, just update name & summary if using tsearch2 and exit.
	  //in this case, obj will always be a non-array
	  if ($propsOnly) return $this->index(1);

    $name = sanitizeString($propsOnly["name"]);
    $summary = sanitizeString($propsOnly["summary"]);

	  $this->addQueue($accountId,$notifyUser);
	
	  //we're all set, hand this off to our indexing program
	  //to run in the background
	  $prog = "cd ".SITE_PATH."; ".APP_PHP." bin/docmgr-indexer.php";
	
	  //check to see if our app is running.If it is, we can exit
	  //otherwise, we have to start it.
	  if (!checkIsRunning("bin/docmgr-indexer.php")) 
	  {
	  	runProgInBack("$prog");
		}
		
	  return true;
	
	}

  /*****************************************************************
    does the legwork for object indexing
  *****************************************************************/	
	function index($propOnly=null) {

	  //first, get our object's name and summary
	  $sql = "SELECT id,name,summary,object_type FROM docmgr.dm_object WHERE id='".$this->objectId."'";
	  $objInfo = $this->DB->single($sql);

	  //check to see if content indexing is disabled in this collection
	  if (!$propOnly)
	  {
	  
		  $a = new DOCMGR_OPTIONS($this->objectId);
		  $disabled = $a->indexDisabled();

		  //it is disabled, only index the properties
		  if ($disabled==true) $propOnly = true;

		}
			
	  $name = &$objInfo["name"];
	  $summary = &$objInfo["summary"];
	  $objectType = $objInfo["object_type"];

    //remove the file extension from the name before we index
    $pos = strrpos($name,".");
    $noext = substr($name,0,$pos);
	 
    //throw these into any array and spit out a unique version of the file name.Since we
    //are indexing the name twice, technically, this prevents the same word from being added
    //twice and falsely increasing the weight
    $idxName = $this->unique($name." ".$noext);
    //$idxName = $noext;

    //setup our indexing string with weighting. 'A' for the filename, 'B' for
    //the summary, and the default 'D' for the content
    $indexString = "setweight(to_tsvector('".TSEARCH2_PROFILE."','".sanitize($idxName)."'),'A')||
	                      setweight(to_tsvector('".TSEARCH2_PROFILE."','".sanitize($summary)."'),'B') ";

    //if propOnly is passed, index the objetc's properties and exit
    //we'll only handle tsearch2 here.
    if ($propOnly) 
    {

	      //pull the existing content from the index so we can re-create our tsearch2 idx data
	      $sql = "SELECT setweight(idxfti,'D') FROM docmgr.dm_index WHERE object_id='".$this->objectId."';";
	      $temp = $this->DB->single($sql);
	      $content = sanitize($temp[0]);
	      
	      $indexString .= " || setweight('".$content."','D') ";
	      
	      $sql = "DELETE FROM docmgr.dm_index WHERE object_id='".$this->objectId."';";
	      $sql .= "INSERT INTO docmgr.dm_index (object_id,idxfti) VALUES ('".$this->objectId."',$indexString);";

	      if ($this->DB->query($sql)) return true;
	      else return false;
	
    }

    $content = null;

    $cn = "DOCMGR_".$objectType;
    
    //if we have a class DOCMGR_for the object type, try to call it's index method    
    if (class_exists($cn))
    {

      $c = new $cn($this->objectId);

      if (method_exists($c,"index")) 
      {
      	echo "Running object-specific method for indexing\n";
      	$content = $c->index();
			}
			
    }

    //if we have content from the object indexer method, clean it up
    if ($content) 
    {
        
	    //shorten it to the word limit if set
	    if (defined("INDEX_WORD_LIMIT") && $content) 
	    {
	      $arr = explode(" ",$content);
	      $arr = array_slice($arr,0,INDEX_WORD_LIMIT);
	      $content = implode(" ",$arr);
      }
	
      //prepare the content for indexing
	    $content = $this->clean($content);
	    $indexString .= " || setweight(to_tsvector('".TSEARCH2_PROFILE."','".sanitize($content)."'),'D') ";

    }
    
    $sql = "DELETE FROM docmgr.dm_index WHERE object_id='".$this->objectId."';";
    $sql .= "INSERT INTO docmgr.dm_index (object_id,idxfti) VALUES ('".$this->objectId."',$indexString);";

	  if ($this->DB->query($sql)) return true;
	  else return false;
	  
	}

	/*************************************************************
	  createIndexQueue
	  This adds all our objects to the queue to be indexed
	*************************************************************/
	
	function addQueue($accountId,$notifyUser) {
	
	  $opt = null;
	  $opt["object_id"] = $this->objectId;
	  $opt["account_id"] = $accountId;
	  $opt["notify_user"] = $notifyUser;
	  $opt["create_date"] = date("Y-m-d H:i:s");
	  if ($this->DB->insert("docmgr.dm_index_queue",$opt)) return true;
	  else return false;
	
	}
	
	/******************************************************************
		Our string manipulation and cleanup functions.
		These functions are available to any 
		object specific indexing functions
	******************************************************************/
	
	function removeTags($string) {
	
		return strip_tags($string);

	}
	
	
	function clean($passString) {
	
		$keepIndex = REGEXP_OPTION;
		
		//pass our text (or file) to tr to clean the text
		$exp = "/[^" . $keepIndex . "\\r\\n\\t ]/i";
		$string =  preg_replace($exp," ",$passString);
	
		//replace our tabs with spaces
		$string = str_replace("\t"," ",$string);
		$string = str_replace("\r"," ",$string);
		$string = str_replace("\n"," ",$string);
	
		//make all lowercase
		$string = mb_strtolower($string,'UTF-8');
	
		//clean out anything between a tag
		if ($cleanTags) $string = $this->removeTags($string);
	
		//replace any 10 consecutive blank spaces with 1 space.  This should help keep tsearch2 under the 2k limit
		$string = str_replace("          "," ",$string);
		
		return $string;
	
	}
	
	//this strips all duplicate words from our string	
	function unique($string) {
	
		$wordArray = explode(" ",$string);
		$wordArray = array_values(array_unique($wordArray));
	
		return implode(" ",$wordArray);
	
	}
	
	
}
