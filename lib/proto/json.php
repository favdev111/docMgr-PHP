<?php

class JSON
{

  private $M;				//class we call for data handling
  private $data;		//where we store the manipulated info
  
  function __construct()
  {
  
  }

  /*********************************************************
    FUNCTION:	encode
    PURPOSE:	converts an array of data into transmission
              ready text
  *********************************************************/  
  function encode($data)
  {

    return json_encode($data);
  
  }
  
  /*********************************************************
    FUNCTION:	decode
    PURPOSE:	decodes transmission data into an array for
              access
  *********************************************************/  
  function decode($data)
  {

    return json_decode($data,true);
        
  }

  /*********************************************************
    FUNCTION:	output
    PURPOSE:	outputs encoded information to the browser
  *********************************************************/  
  function output($data)
  {

    header("Cache-Control: no-cache, must-revalidate");	
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Content-Type: application/json; charset=utf-8");
    die($data);
  
  }

  //$string = iconv("UTF-8","UTF-8//IGNORE",$string);
  function checkUTF8(&$arr)
  {

    $keys = array_keys($arr);
    
    foreach ($keys AS $key)
    {
    
      if (is_array($arr[$key])) 
      {
        $this->checkUTF8($arr[$key]);
      }
      else
      {
        //remove non-utf8
        $arr[$key] = iconv("UTF-8","UTF-8//IGNORE",$arr[$key]);
      }
      
    }
  
  }

}

