<?php

class CONFIG_LOGS extends CONFIG
{

  function ___construct()
  {

    //only admins can be here
    if (!PERM::check(ADMIN))
    {
      $this->throwError(_I18N_PERMISSION_DENIED);
      return false;
    }
                         
  }
  
  function search()
  {

    $searchLimit = "50";
    $searchOffset = "0";

    if ($this->apidata["search_limit"]) $searchLimit = $this->apidata["search_limit"];
    if ($this->apidata["search_offset"]) $searchOffset = $this->apidata["search_offset"];

    $filterArr = $this->handleFilters();

    //get the count first
    $sql = "SELECT count(id) AS total_count FROM logger.logs";
    if (count($filterArr) > 0) $sql .= " WHERE id IN (".implode(" INTERSECT ",$filterArr).") ";
    $info = $this->DB->single($sql);
    
    $this->PROTO->add("total_count",$info["total_count"]);

    //now run the limited query
    $sql = "SELECT id,message,level,category,log_timestamp,ip_address,user_id,user_login,data FROM logger.logs ";
    if (count($filterArr) > 0) $sql .= " WHERE id IN (".implode(" INTERSECT ",$filterArr).") ";
    $sql .= " ORDER BY log_timestamp DESC LIMIT ".$searchLimit." OFFSET ".$searchOffset;

    $results = $this->DB->fetch($sql);

    $this->PROTO->add("current_count",$results["count"]);

    for ($i=0;$i<$results["count"];$i++)
    {
      $results[$i]["log_timestamp"] = dateFormat($results[$i]["log_timestamp"]);
      $this->PROTO->add("record",$results[$i]);
    }
   
  }

  private function handleFilters()
  {

    $filterArr = array();
    $filters = $this->apidata["filters"];
    $matches = $this->apidata["matches"];
    $values = $this->apidata["values"];
    $dataTypes = $this->apidata["data_types"];;
       
    //loop through our optional filters
    for ($i=0;$i<count($filters);$i++)
    {
      if ($filters[$i]=="category") $filterArr[] = $this->categoryFilter($matches[$i],$values[$i]);    
      else if ($filters[$i]=="log_timestamp") $filterArr[] = $this->timestampFilter($matches[$i],$values[$i]);    
      else if ($filters[$i]=="account_id") $filterArr[] = $this->accountFilter($matches[$i],$values[$i]);    
    }

    //remove null entries
    return arrayReduce($filterArr);
  
  }

  private function categoryFilter($match,$value)
  {

    if (!$value) return false;

    if ($match=="notequal") $op = "!=";
    else $op = "=";

    $sql = "SELECT id FROM logger.logs WHERE category ".$op." '".$value."'";

    return $sql;  

  }

  private function accountFilter($match,$value)
  {
    if (!$value) return false;

    $sql = "SELECT id FROM logger.logs WHERE account_id='".$value."'";
    return $sql;  
  }

  private function timestampFilter($match,$value)
  {

		if (!$value) return false;
		
		if ($match=="on")
		{
  		$filter = "log_timestamp>='".dateProcess($value)." 00:00:00' AND log_timestamp<='".dateProcess($value)." 23:59:59'";
		}
		else if ($match=="before")
		{
  		$filter = "log_timestamp<='".dateProcess($value)." 23:59:59'";
		}
		else if ($match=="after") 
		{
  		$filter = "log_timestamp>='".dateProcess($value)." 00:00:00'";
		}
		
		return " SELECT id FROM logger.logs WHERE ".$filter;
		
		
  }

}
		