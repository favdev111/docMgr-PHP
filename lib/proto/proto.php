<?php

require_once("xml.php");
require_once("json.php");

class PROTO
{

  private $M;				//class we call for data handling
  private $data;		//where we store the manipulated info
  private $header;	//where we store the data we want to send in the output header
    
  /*********************************************************
    FUNCTION:	__construct
    PURPOSE:	inits class.  sets protocol mode
    INPUTS:		mode -> protocol mode (JSON or XML);
  *********************************************************/  
  function __construct($mode=null)
  {

    if (!$mode && defined("PROTO_DEFAULT")) $mode = PROTO_DEFAULT;

    $this->setProtocol($mode);  
    $this->data = array();
    $this->header = array();
    
  }


  /*********************************************************
    FUNCTION:	setProtocol
    PURPOSE:	changes our output format to JSON or XML
    INPUTS:		mode => mode we are changing to
  *********************************************************/  
  function setProtocol($mode)
  {

    if ($mode=="JSON") $this->M = new JSON();
    else $this->M = new XML();
  
  }


  /*********************************************************
    FUNCTION:	add
    PURPOSE:	adds the key/value pair to the queue for 
              outputing to the browser later
    INPUTS:		key => name of value to output
              val => value to output.  Can be string/number
                     or array
  *********************************************************/  
  function add($key,$val,$rep = null)
  {

    //make sure it's stored as UTF-8 with valid characters
    $this->checkUTF8($val);

    //if passing an array, store as an an array in the data key
    if (is_array($val))
    {

      //fix db result arrays
      $val = $this->fixQueryArray($val);
      
      //if no entry, make an new array for this key    
      if ($this->data[$key] && !$rep) 
      {
        
        //if associated, add as a new object.  otherwise just merge into the array
        if (is_assoc($val)) $this->data[$key][] = $val;
        else $this->data[$key] = array_merge($this->data[$key],$val);
        
      }
      else 
      {
      
        //if associative, add as new object, otherwise create new indexed array
        if (is_assoc($val)) $this->data[$key] = array($val);
        else $this->data[$key] = $val;
        
      }
      
    } 
    else 
    {

      //if it already exists, convert into an array with the original value
      //then add the new value
      if ($this->data[$key] && !$rep)
      {

        //not already an array, convert
        if (!is_array($this->data[$key])) 
        {
        
          $arr = array($this->data[$key]);
          $this->data[$key] = $arr;      

        }

        //add the new value        
        $this->data[$key][] = $val;

      //otherwise just store normally                
      } 
      else 
      {

        $this->data[$key] = $val;

      }
 
    }
     
  }

  /*********************************************************
    FUNCTION:	addHeader
    PURPOSE:	adds the key/value pair response header
    INPUTS:		key => name of value to output
              val => value to output.  Can be string/number
  *********************************************************/  
  function addHeader($key,$val,$rep = null)
  {

    //make sure it's stored as UTF-8 with valid characters
    $this->checkUTF8($val);

    $this->header[$key] = $val;
     
  }

  /*********************************************************
    FUNCTION:	encode
    PURPOSE:	converts the queued data into transmission
              ready text in XML or JSON format
  *********************************************************/  
  function encode()
  {

    //encode using the appropriate protocol handler
    return $this->M->encode($this->data);
  
  }
  
  /*********************************************************
    FUNCTION:	decode
    PURPOSE:	decodes transmission data into an array for
              access.  assumes it's dealing with request
              variables, because they get sanitized
  *********************************************************/  
  function decode($str)
  {

    global $exemptRequest;

    //decode using the appropriate protocol handler
    $arr = $this->M->decode($str);

    //sanitize all data from the request
    $arr = sanitize($arr,$exemptRequest);
    
    return $arr;      

  }

  /*********************************************************
    FUNCTION:	output
    PURPOSE:	outputs encoded information to the browser
  *********************************************************/  
  function output()
  {

    $arr = array("header"=>$this->header,"body"=>$this->data);

    $str = $this->M->encode($arr);
    $this->M->output($str);
  
  }

  /*********************************************************
    FUNCTION:	getEncoded
    PURPOSE:	gets encoded queue data w/o outputing to to
              the browser
  *********************************************************/  
  function getEncoded()
  {
  
    $str = $this->M->encode($this->data);
    return $str;  

  }

  /*********************************************************
    FUNCTION:	getData
    PURPOSE:	returns the data array we have queued for 
              later output
  *********************************************************/  
  function getData()
  {
  
    return $this->data;
  
  }


  /*********************************************************
    FUNCTION:	setData
    PURPOSE:	set the data array to the passed value
  *********************************************************/  
  function setData($data)
  {
  
    $this->data = $data;
  
  }

  /*********************************************************
    FUNCTION:	clearData
    PURPOSE:	clears the output queue
  *********************************************************/  
  function clearData()
  {
  
    $this->data = null;
  
  }

  /*********************************************************
    FUNCTION:	fixQueryArray
    PURPOSE:	this little gem removes the "count" key added
              by our db wrapper for query results.  it upsets
              the way of things in javascript world because
              it will turn a numerically keyed array into
              an associative one
    INPUTS:		arr => array to check
    RETURNS:	arr => fixed array
  *********************************************************/  
  protected function fixQueryArray(&$arr)
  {
  
    if ($arr[0] && $arr["count"]) unset($arr["count"]);
    
    $keys = array_keys($arr);

    foreach ($keys AS $key)
    {
    
      if (is_array($arr[$key])) 
      {
        $this->fixQueryArray($arr[$key]);
      }
      
    }  

    return $arr;
      
  }

  /*********************************************************
    FUNCTION:	checkUTF8
    PURPOSE:	makes sure we don't have any non-utf8
              characters in our data. those cause problem
              for json_encode down the line
    INPUTS:		val -> data to check.  array, string, numeric
    RETURNS:	none
  *********************************************************/  
  function checkUTF8(&$val)
  {

    if (is_array($val))
    {

      if (count($val) > 0)
      {
      
        foreach ($val AS $key=>$data)	$this->checkUTF8($val[$key]);
 
      }
           
    }
    else
    {
    
      //remove non-utf8
       if (!is_resource($val)) $val = iconv("UTF-8","UTF-8//IGNORE",$val);

    }

  }

}
