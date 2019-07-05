<?php

//get subclass libraries
class CONFIG
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

    //look for subclass constructor
    if (validMethod($this,"___construct")) $this->___construct();

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

