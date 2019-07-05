<?php
/***********************************************************************
 FILE: error.php

 PURPOSE: File contains functions used for displaying error
          returned by processing in Tea

/***********************************************************************/

//constants for the log levels
define("LOGGER_DEBUG","5");
define("LOGGER_WARNING","3");
define("LOGGER_ERROR","1");
define("LOGGER_MODE","db");

/************************************************************
  categories:
  AUTH
  IMAP
  COMPANY

************************************************************/

class LOGGER {

  private $DB;
  private $logData;
  private $mode;
  private $errorStack = array();
        
  /******************************************************************************
    FUNCTION: construct
    PURPOSE:	our constructor.  inits all required variables and sets up object
  ******************************************************************************/
  public function __construct($dbref) 
  {

    $this->DB = $dbref;
    $this->mode = LOGGER_MODE;
    
  }

  /********************************************************
    FUNCTION: logerror
    PURPOSE:	log sql errors
  ********************************************************/
  public function logerror($sql) 
  {

    //get the last error message from sql  
    $msg = $this->DB->last_error();

    $this->log($msg,LOGGER_ERROR,"DB_ERROR",$sql);

  }

  /******************************************************************************
    FUNCTION: log
    PURPOSE:	logs the specified message
  ******************************************************************************/
  public function log($msg,$level=null,$category=null,$data=null) 
  {

    //store it in our array for access
    if (defined("USER_ID")) $this->logData["user_id"] = sanitize(USER_ID);
    if (defined("USER_LOGIN")) $this->logData["user_login"] = sanitize(USER_LOGIN);
    $this->logData["log_timestamp"] = date("Y-m-d H:i:s");
    $this->logData["ip_address"] = $_SERVER['REMOTE_ADDR'];    
    $this->logData["message"] = sanitize($msg);
    if ($level) $this->logData["level"] = $level;
    if ($category) $this->logData["category"] = $category;
    if ($data) $this->logData["data"] = sanitize($data);

    if ($category=="DB_ERROR") $this->errorStack[] = $this->logData;
    
    $this->logToDB();

  }

  /**********************************************************
    write our log into the database
  **********************************************************/
  private function logToDB()
  {

    //insert the info into the database, don't allow logging of any error
    $this->DB->insert("logger.logs",$this->logData,null,1);
    
  }

  /******************************************************************************
    FUNCTION: getLastError
    PURPOSE:returns error data on the last db error.returns false if there
    is no error to return
  ******************************************************************************/
  public function getLastError() {		
    $num = count($this->errorStack);
    if ($num=="0") return false;
    else return $this->errorStack[$num-1];
  }

  /******************************************************************************
    FUNCTION: getLastErrorMsg
    PURPOSE:returns error data on the last db error.returns false if there
          is no error to return
  ******************************************************************************/
  public function getLastErrorMsg($sep = "html") {

    $num = count($this->errorStack);
    if ($num=="0") return false;
    else {

      if ($sep=="html") $div = "<br>\n";
      else $div = "\n";

      $msg = $this->errorStack[$num-1]["msg"];

      //add the query to the message if in dev mode
      if (defined("DEV_MODE")) $msg .= $div.$this->errorStack[$num-1]["query"];

      return $msg;

    }

  }

  /******************************************************************************
    FUNCTION: getAllErrorMsgs
    PURPOSE:returns error data in the error stack
            is no error to return
  ******************************************************************************/
  public function getAllErrorMsgs($sep = "html") {

    $num = count($this->errorStack);

    if ($sep=="html") $div = "<br>\n";
    else $div = "\n";

    if ($num > 0) {

      $str = null;	

      for ($i=0;$i<$num;$i++) {

        //append our error message and teh query as well if in dev mode
        $msg = $this->errorStack[$i]["msg"];
        if (defined("DEV_MODE")) $msg .= $div.$this->errorStack[$i]["query"];
        $msg .= $div;

        $str .= $msg;

      }
      return $str;

    } else return false;

  }

}

