<?php

class EDAV_QUERY extends EDAV
{

  public function browse() 
  {
		$results = array();

  	//if no bookmark id set, then just return a list of bookmarks
  	if ($this->path=="/")
  	{
			$b = new DOCMGR_BOOKMARK();
			$results = $b->search();
  	}
  	//otherwise return everything under that object
  	else
  	{

  	  //make sure they have access to this object
  	  if (!$this->permCheck())
  	  {
          $this->throwError(_I18N_PERMISSION_DENIED);
          return false;
  	  }
                                  
  		$sql = "SELECT id,name,create_date,size,object_type,token,last_modified,modified_by 
  						FROM docmgr.dm_object
  						LEFT JOIN docmgr.dm_object_parent ON dm_object.id = dm_object_parent.object_id
  						WHERE dm_object_parent.parent_id='".$this->objectId."' AND hidden='f'
  						ORDER BY lower(name)";
			$results = $this->DB->fetch($sql);

			for ($i=0;$i<$results["count"];$i++)
			{
				//create a path so we don't ahve to query for it
				$results[$i]["object_path"] = $this->path."/".$results[$i]["name"];
			}

			unset($results["count"]);
			
			return $results;
  	
  	}

  	$this->PROTO->clearData();

  	//clean up our results
  	unset($results["count"]);
  	unset($results["total_count"]);
  	unset($results["current_count"]);
	
  	return $results;

	}

	/**
	  make sure the user has view access to the current location
	  */
  private function permCheck()
  {

    $ret = true;
      
 	  //make sure they have access to Root Level
	  if ($this->objectId=="0")
	  {
	    if (!PERM::check(CREATE_ROOT) && !PERM::check(BROWSE_ROOT))
	    {
	      $ret = false;
	    }
	  }
	  //make sure they have access to any other level
	  else
	  {

  	  $bitset = DOCMGR_UTIL_OBJPERM::getUser($this->objectId);
	  
  	  if (!DOCMGR_UTIL_OBJPERM::check($bitset,"view"))
  	  {
  	    $ret = false;
      }
    
    }

    return $ret;
     
  }	


}	


