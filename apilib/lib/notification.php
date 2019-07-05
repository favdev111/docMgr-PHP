<?php

//get subclass libraries
class NOTIFICATION
{

	//local vars
  protected $conn;
  protected $apidata;
  protected $errorMsg;
  protected $errorCode;
  protected $DB;
  protected $PROTO;
  protected $LOGGER;
        
  //this thing takes an id or an array, so an object id can be passed to init the object  
  function __construct($initdata=null) 
  {

    //db resources
    $this->conn = $GLOBALS["conn"];  
    $this->DB = $GLOBALS["DB"];
    $this->CDB = $GLOBALS["CDB"];
    $this->PROTO = $GLOBALS["PROTO"];
    $this->LOGGER = $GLOBALS["logger"];

    if ($initdata) $this->apidata = $initdata;
    
    //setup our constants
    $this->setup();
    
    //look for subclass constructor
    if (validMethod($this,"___construct")) $this->___construct();

  }  

	/**
		sets up all required constants.  only allowed to run once in case we are initted several times
		*/	
	function setup()
	{

		if (defined("NOTIFICATION_NOTIFICATION_SETUP")) return false;
		
		$sql = "SELECT * FROM notification.options ORDER BY id";
		$results = $this->DB->fetch($sql);

		for ($i=0;$i<$results["count"];$i++)
		{
			define($results[$i]["define_name"],$results[$i]["id"]);		
		}

		//just in case we get called again
		define("NOTIFICATION_NOTIFICATION_SETUP","1");
	
	}

  /*********************************************************************
    FUNCTION: getError
    PURPOSE:	returns any error or error code set by the api or
              its subclasses
  *********************************************************************/
  public function getError() 
  {

    //only return data if there is an error
    if ($this->errorMsg || $this->errorCode)
      return array($this->errorMsg,$this->errorCode); 
    else
      return false;

    }

  /*********************************************************************
    FUNCTION: throwError
    PURPOSE:	stores an error and optional code for this class 
              or any subclasses its subclasses
  *********************************************************************/
  protected function throwError($msg,$code=null) 
  {

    $this->errorMsg = $msg;
    $this->errorCode = $code;
 
  }

  public function showCommon()
  {

  }
   
}



