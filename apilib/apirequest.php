<?php

/**
	parse the api request out of the posted data, and hands off the request to the appropriate class
	*/
class APIREQUEST
{
	
	private $apidata;
	private $PROTO;
	private $logger;
	private $header;
	private $body;
	private $errorMessage;
  private $langLoaded;
  		
	/**
		build it
		*/
	function __construct($data)
	{
		$this->PROTO = &$GLOBALS["PROTO"];		
		$this->logger = &$GLOBALS["logger"];
		$this->apidata = $data;
		$this->langLoaded = false;
	}

	/**
		return any error messages thrown by us or subclasses
		*/
	function getError()
	{
		return $this->errorMessage;
	}

	/**
		throws an error message and stores for public access
		*/	
	function throwError($err)
	{
		$this->errorMessage = $err;
	}

	private function includeLanguageFile()
	{

	  //already been here once
	  if ($this->langLoaded==true) return false;
	
		//load the appropriate server-side language file
		if (isset($_SESSION["api"]["authorize"]))
		{
		  if (isset($_SESSION["api"]["accountInfo"]["language"])) $lang = $_SESSION["api"]["accountInfo"]["language"];
		  else $lang = DEFAULT_LANGUAGE;
    }
    else
    {
      $lang = DEFAULT_LANGUAGE;
    }

		//if the language file doesn't exist, fall back on english
		$langFile = "lang/".$lang."/api.php";
		if (!file_exists($langFile)) $langFile = "lang/en/api.php";

		require_once($langFile);

	
	}

	/**
		main processor.  parses the data and authorizes user if necessary
		*/
	function process()
	{

		$this->setupData();
		$this->getParts();

		//so we can use translactions during authentication
		if (!isset($_SESSION["api"]["authorize"])) $this->includeLanguageFile();

		//process authentication
		$this->auth();

		//load our language file if not done yet
		$this->includeLanguageFile();
  		
		//if no error so far, keep going
		if ($this->getError())
		{
			$this->PROTO->add("error",$this->getError());
		}
		else
		{
			$this->handleBody();
		}

		$this->close();
		
	}

	/**
		converts our request string into an array
		*/	
	function setupData()
	{
	
		if (!is_array($this->apidata))
		{
		
		  $pos = stripos($apidata,"<data>");
  
		  if ($pos!==FALSE && $pos=="0")
		  {
		    $this->PROTO->setProtocol("XML");
		  }

		  $this->apidata = $this->PROTO->decode($this->apidata);

		}
			
	}	
   
  /**
  	extracts the header and body from our message
  	*/
  function getParts()
  {

  	//file_put_contents("/tmp/apipot.log",print_r($this->apidata,1),FILE_APPEND);

  	//new way, we are passed a header and a body
    if (isset($this->apidata["header"]))
    {
  		$this->header = &$this->apidata["header"];
  		$this->body = &$this->apidata["body"];
    }
    //old way, no head/body pieces.  Just assume we are passed the body
    else
    {
      $this->body = &$this->apidata;
    }  

  } 

	/**
		sets our session id if passed in the header, then hands off for authorization
		*/ 
  function auth()
  {

		//try to set the session if passed
		if ($this->header["session_id"]) session_id($this->header["session_id"]);

		//start the session
		session_start();
		
		//called if a username or password is set, or if the client is looking for a valid cookie 
		if (		isset($_SESSION["api"]["authorize"]) || $this->body["command"]=="login" ||
		      (	isset($this->header["login"]) && isset($this->header["password"])	) ||
		      ( isset($_REQUEST["login"]) && isset($_REQUEST["password"])	 				)
          )
    {  
                                
			//now go through authentication.  the AUTH class will just return
			//true if we are already using a valid session
			$a = new AUTH($this->header["login"],$this->header["password"]);

			//if passed a save_cookie parameter, set class to write a cookie on auth
			if ($this->header["save_cookie"]) $a->writeCookie = 1;

			$ret = $a->authorize();

			if (!$ret) 
			{
				//if there's an error, bail
				$err = $a->getError();
				if (!$err) $err = "Not Authorized";
				$this->throwError($err);

				//username or password was invalid
        $this->PROTO->addHeader("authentication_error","login_failed");

			}
      
		}
		else
		{
		  $this->throwError("Not Authorized");
		  $this->PROTO->addHeader("authentication_error","session_timeout");
		}
				  
  }

	/**
		looks for a passed command request, then passes it off to the appropriate
		class to be handled, if said class exists
		*/
  function handleBody()
  {

		if ($this->body["command"])
		{
		
		  //parse out the cmdarr to its separate components
		  $cmdarr = explode("_",$this->body["command"]);
	
		  //continue only if we have the proper app_object_method structure
		  if (count($cmdarr) == 3)
		  {
	
		    //subclass name
		    $class = $cmdarr[0]."_".$cmdarr[1];

		    //if the class doesn't exist, let them know
		    if (!class_exists($class)) 
		    {
		    	$this->PROTO->add("error","Class ".$class." does not exist");
					return false;
			}
				
		    //method
				$mn = $cmdarr[2];
				
	    	//keep going
		    $sub = new $class($this->body);
		    
		    //call our requested method
				if (!$sub->getError())
				{		
			    $sub->$mn();
				}	        

				//our called class threw an error, output to the client	
				if ($err = $sub->getError())
				{
	
					$this->PROTO->add("error",$err);
	
					//record the error along with passed apiBody
					$this->logger->log($err[0],LOGGER_ERROR,"API_ERROR",print_r($this->body,1));
	    
				}
				//check for common output
				else 
				{
					$sub->showCommon();
				}
	
		  }
		  //kick back all the account information 
		  else if ($this->body["command"]=="login")
		  {

		  	if ($_SESSION["api"]["accountInfo"])
		  	{
			  	//set our user information from that which is returned from the function
			  	$this->PROTO->add("account_id",$_SESSION["api"]["accountInfo"]["id"]);
			  	$this->PROTO->add("login",$_SESSION["api"]["accountInfo"]["login"]);
			  	$this->PROTO->add("password",$_SESSION["api"]["accountInfo"]["password"]);
			  	$this->PROTO->add("email",$_SESSION["api"]["accountInfo"]["email"]);
			  	$this->PROTO->add("first_name",$_SESSION["api"]["accountInfo"]["first_name"]);
			  	$this->PROTO->add("last_name",$_SESSION["api"]["accountInfo"]["last_name"]);
			  	$this->PROTO->add("groups",$_SESSION["api"]["user_groups"]);
			  	$this->PROTO->add("bitmask",$_SESSION["api"]["bitmask"]);
			  	$this->PROTO->add("location_id",$_SESSION["api"]["location_id"]);

			  	//get our account settings
			  	$a = new CONFIG_ACCOUNT(array("account_id"=>$_SESSION["api"]["accountInfo"]["id"]));
			  	$a->getSettings();
			  	
		  	}

		  }
		  else if ($this->body["command"]=="keepalive")
		  {
		    //keep the dream alive.  dummy entry to keep session from expiring
		  } 
		  //didn't use the proper command structure  
		  else
		  {
		    $this->PROTO->add("error","Error.  Invalid command structure used.  Must follow \"app_object_method\" structure\"");;
		  }
		
		} 
		//no command found, bail
		else 
		{
		  $this->PROTO->add("error","API Command not specified -> ".print_r($this->body,1));
		}
									
	}

	/**
		wrap it up
		*/
	function close()
	{
		$this->PROTO->addHeader("session_id",session_id());
	}
		
}


