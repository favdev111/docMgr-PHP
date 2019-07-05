<?php

//get subclass libraries
class EDAV
{

	//local vars
  protected $conn;
  protected $apidata;
  protected $errorMsg;
  protected $errorCode;
  protected $DB;
  protected $PROTO;
  protected $LOGGER;

  //object specific things we need to track
  protected $path;
  protected $bookmarkId;
  public $objectId;
  public $objectInfo;
            
  //this thing takes an id or an array, so an object id can be passed to init the object  
  function __construct($initdata=null) 
  {

    //db resources
    $this->conn = $GLOBALS["conn"];  
    $this->DB = $GLOBALS["DB"];
    $this->PROTO = $GLOBALS["PROTO"];
    $this->LOGGER = $GLOBALS["logger"];
    $this->path = null;
    
    if ($initdata) 
    {

      if (is_array($initdata))
      {
        $this->apidata = sanitize($initdata);
        $this->path = $this->apidata["path"];      
      }
      else
      {
        $this->path = sanitize($initdata);      
      }

    }

    //init some stuff
    $this->bookmarkId = null;
    $this->objectId = null;

    $this->parsePath();

    //so far so good, call our desired class to do some work    
    if (!$this->getError())
    {
      //look for subclass constructor
      if (validMethod($this,"___construct")) $this->___construct();
    }	

  }  

  private function parsePath()
  {

    //do nothing if we are in root
    if ($this->path=="/") 
    {

      $this->objectId = "0";

      $this->objectInfo = array();
      $this->objectInfo["name"] = ROOT_LEVEL;
      $this->objectInfo["object_path"] = "/";
      $this->objectInfo["object_id"] = "0";
      $this->objectInfo["object_type"] = "collection";

    }
    else
    {
  
	    $arr = explode("/",$this->path);
	    array_shift($arr);
	
	    //go ahead and get our bookmark info
	    $b = new DOCMGR_BOOKMARK(array("name"=>$arr[0]));
	    $info = $b->getByName();

	    //if only one deep, we only have a bookmark name
	    if (count($arr) > 1)
	    {
	      //remove the bookmark
	      array_shift($arr);
	
	      //remove the bookmark from our path
	      if ($info["object_path"]=="/") $objectPath = "/".implode("/",$arr);
	      else $objectPath = $info["object_path"]."/".implode("/",$arr);
	
	      //create a new docmgr object to fetch the id we'll be working with
	      $d = new DOCMGR($objectPath);
	      
	      $this->objectId = $d->objectId;
	      $this->objectInfo = $d->objectInfo;
	          
	    }
	    //just use the object id from the bookmark
	    else
	    {
	      if ($info["object_id"]=="0")
	      {
          $opt = array();
          $opt["name"] = ROOT_LEVEL;
          $opt["object_path"] = "/";
          $opt["object_id"] = "0";
          $opt["object_type"] = "collection";

          $this->objectId = "0";
          $this->objectInfo = $opt;       
	      }
	      else
	      {
  	      $this->objectId = $info["object_id"];
  	      $d = new DOCMGR($this->objectId);
  	      $this->objectInfo = $d->objectInfo;
	      }

	    }
	
	    $this->PROTO->clearData();

    }
      
  }

  /*********************************************************************
    FUNCTION: getError
    PURPOSE:	returns any error or error code set by the api or
              its subclasses
  *********************************************************************/
  public function getError() 
  {

    //only return data if there is an error
    if ($this->errorMsg)
      return $this->errorMsg;
    else
      return false;

    }

  /*********************************************************************
    FUNCTION: throwError
    PURPOSE:	stores an error and optional code for this class 
              or any subclasses its subclasses
  *********************************************************************/
  protected function throwError($msg) 
  {
    if (is_array($msg)) $this->errorMsg = $msg[0];
    else $this->errorMsg = $msg;
  }

  public function showCommon()
  {

  }
   
}

