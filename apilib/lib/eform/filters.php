<?php

class EFORM_FILTERS extends EFORM
{

  private $file;
  
  function ___construct()
  {

    $this->file = $this->apidata["file"];
    
    if (!$this->file)
    {
      $this->throwError("No xml definition file specified");
      return false;
    }

    if (!file_exists($this->file))
    {
      $this->throwError("Could not find xml defintion file");
      return false;
    }
  
  }

  /**
    load the xml file and calls the api as needed to add any options
    */
  function load()
  {

		//convert to an array
		$arr = XML::decode(file_get_contents($this->file));
		
		//get all filters
		$filters = $arr["filter"];
		
		//bail if nothing found
		if (count($filters)==0)
		{
			$this->throwError("No filters defined");
			return false;
		}
		
		//filter types that require us to query a database for their possible values
		$dataFilters = array("select");

		$records = array();
		
		//loop through and see if we have to add any options
		foreach ($filters AS $filter) 
		{
		
		  //is it defined correctly
		  if (!$filter["type"]) continue;
		
		  //if it's not a data filter, we don't need to pull additional data for it
		  if (in_array($filter["type"],$dataFilters)) $filter = $this->addOptions($filter);

			if (!$filter["match"]) continue;

		  $records[] = $filter;

		  //output to the client
		  $this->PROTO->add("record",$filter);
		
		}

		//clear any incidental output from api methods we may have called
		$this->PROTO->clearData();
		$this->PROTO->add("record",$records);
			  
  }

  private function addOptions($filter)
  {
  
		  //get our class parameters
		  $title_field = $filter["title_field"];
		  $data_field = $filter["data_field"];
		  $api_command = $filter["api_command"];
		  $api_param = $filter["api_param"];

		  if ($this->apidata["api_parameters"]) $api_parameters = $this->apidata["api_parameters"][0];
      else $api_parameters = null;
            
			//no class or method specified to call		  
		  if (!$api_command) return $filter;
		
		  //fallback
		  if (!$title_field) $title_field = "name";
		  if (!$data_field) $data_field = "id";
		
		  //split the api into it's parts
		  $tmp = explode("_",$api_command);

		  if (count($tmp)!=3) 
		  {
		  	$this->throwError("API command must have 3 segments");
		  	return false;
			}

			//fetch the parts
		  $api_method = array_pop($tmp);
		  $api_class = implode("_",$tmp);
		  
		  //run the query
		  $API = new $api_class($this->apidata["api_parameters"]);
		  $list = $API->$api_method();
		  unset($list["count"]);
		  
		  $num = count($list);
		  
		  for ($i=0;$i<$num;$i++)
		  {

				$option = array();
				$option["title"] = $list[$i][$title_field];
				$option["data"] = $list[$i][$data_field];

				//create new or add existing array				
				if ($filter["option"]) $filter["option"][] = $option;
				else $filter["option"] = array($option);
		
		  } 
  
		  return $filter;
		  
  }
		
}
	