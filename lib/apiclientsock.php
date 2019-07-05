<?php

//already loaded, bail 
if (class_exists("APICLIENTSOCK")) return false;

/**********************************************************************
  CLASS: APICLIENTSOCK
  PURPOSE:	A standalone library that code that exists outside
                of the tea module system (like scripts) can use to
                access the api.  uses http sockets via 
                file_get_contents to call the api
**********************************************************************/

class APICLIENTSOCK
{

  private $login;
  private $password;
  private $sessid;
  private $url;
  private $errorMessage;

  /******************************************************************
    FUNCTION:	__construct
    PURPOSE:	class constructor
    INPUTS:	url -> url to the api, including api script name
                login -> api login
                password -> api password
  ******************************************************************/    
  function __construct($url,$login,$password)
  {

    $this->url = $url;
    $this->login = $login;
    $this->password = $password;

  }

  private function buildPacket($params)
  {

    $packet = array();

    $header = array();
    $header["login"] = $this->login;
    $header["password"] = $this->password;

    if ($_SESSION["api_session_id"]) $header["session_id"] = $_SESSION["api_session_id"];  

    $packet["header"] = $header;
    $packet["body"] = $params;
    print_r($packet);    
    return $packet;

  }

  /******************************************************************
    FUNCTION:	call
    PURPOSE:	calls the API, passes array as json command
    INPUTS:	opt (array) -> array of command info to pass to api
                  - command (string) -> class to execute in API,
                                        like tea_contact_get
                  - all others depend on class called
    RETURNS:	data (array) -> array of returned info from API
  ******************************************************************/    
  function call($opt)
  {

    $packet = $this->buildPacket($opt);

    //convert to json string
    $encoded = json_encode($packet);

    //setup our url, use the session from our login to bypass auth        
    $url = $this->url."?apidata=".urlencode($encoded);
    echo $url."\n";
    $str = file_get_contents($url);

    echo $str."\n";
    //decode response to array
    $response = json_decode($str,true);

    print_r($response);
    
    $output = array();
    
    //throw a parse error if response isn't formatted correctly
    if (!is_array($response)) $this->throwError($response);
    else
    {

      //store for later
      $_SESSION["api_session_id"] = $response["header"]["session_id"];
      $data = $response["body"];

      //api returned an error
      if ($data["error"]) $this->throwError($data["error"]);
      
      //everything worked fine
      else $output = $data;

    }

    return $data;
  
  }

  /******************************************************************
    FUNCTION:	throwError
    PURPOSE:	stores errors thrown by this class
    INPUTS:	err (string) -> error message
    RETURNS:	none
  ******************************************************************/    
  function throwError($err)
  {
    $this->errorMessage = $err;
  }
  
  /******************************************************************
    FUNCTION:	error
    PURPOSE:	returns stored class error messages
    INPUTS:	none
    RETURNS:	error (string)
  ******************************************************************/    
  function error()
  {
    return $this->errorMessage;
  }

}

