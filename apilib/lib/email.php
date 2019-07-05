<?php

require_once("app/common.php");

//get subclass libraries
class EMAIL
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
  function __construct($initdata) 
  {

    //db resources
    $this->conn = $GLOBALS["conn"];  
    $this->DB = $GLOBALS["DB"];
    $this->PROTO = $GLOBALS["PROTO"];
    $this->LOGGER = $GLOBALS["logger"];
    
    $this->apidata = $initdata;

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
  public function throwError($msg,$code=null) 
  {

    $this->errorMsg = $msg;
    $this->errorCode = $code;
 
  }

  function showCommon()
  {

    //we will be in xml mode on this, don't want to return anything else
    if ($this->apidata["command"]!="email_attach_addfile") $this->PROTO->add("server",$_SESSION["api"]["email"]);  

  }

}

