<?php

require_once(SITE_PATH."/apilib/lib/docmgr/lib/tree.php");

class DOCMGR_QUERY extends DOCMGR_AOBJECT
{

  public function browse() 
  {

  	if ($this->objectId!=null) $parent = $this->objectId;
  	else if ($this->objectPath=="/") $parent = "0";
  	else 
  	{

  		//if passed to make this collection. go for it!
  		if ($this->apidata["mkdir"]) 
  		{
  			
  			//make the folder we are browsing
  			$parent = DOCMGR_UTIL_COMMON::recurMkParent($this->apidata["path"]);

  			//set the perms for our new parent for the permcheck that happens later
				$this->objectBitset = DOCMGR_UTIL_OBJPERM::getUser($parent);

			} 
			else 
			{
	  		$this->throwError("\"".$this->apidata["path"]."\" "._I18N_OBJECT_NOTFOUND_ERROR);
	  		return false;
			}
			
		}

  	//do we have permissions to view this
  	if ($parent!=null && !$this->permCheck("view"))
  	{
  		$this->throwError("\"".$this->path."\" "._I18N_OBJECT_VIEW_ERROR);
  		return false;
		}

		//set the object_id we will be browsing
		$this->apidata["object_id"] = $parent;

		//run the search
		$s = new DOCMGR_UTIL_QUERY();
		$data = $s->browse($this->apidata);

		//output our result counts
    $this->PROTO->add("current_count",$data["count"]);
    $this->PROTO->add("total_count",$data["total_count"]);

    //now, add in additional information for our results
    for ($i=0;$i<$data["count"];$i++) 
    {

      if ($this->path=="/") $data[$i]["object_path"] = "/".$data[$i]["name"];
      else $data[$i]["object_path"] = $this->path."/".$data[$i]["name"];

      //make some pretty fields for viewing
      $data[$i]["last_modified_view"] = dateView($data[$i]["last_modified"]);
      $data[$i]["object_directory"] = $data[$i]["level1"]."/".$data[$i]["level2"];

      //populate with some data from our extensions.xml file
      $info = fileInfo($data[$i]["name"]);
      $data[$i] = array_merge($info,$data[$i]);
            
      //return the parent we were browsing under
			$data[$i]["parent_id"] = $parent;

      //return the object type and load
      $objectClass = "DOCMGR_".$data[$i]["object_type"];

			//call the owning class for the object type and get any relevant info
      $o = new $objectClass();
      $data[$i] = array_merge($data[$i],$o->listDisplay($data[$i]));

      //fix the icon link to only have the name
      $arr = explode("/",$data[$i]["icon"]);
      $data[$i]["icon"] = $arr[count($arr)-1];

      //add file extension
      if ($data[$i]["object_type"]=="file") 
      {

          $data[$i]["type"] =  $type = return_file_type($data[$i]["name"]);
          $data[$i]["extension"] =  $type = return_file_extension($data[$i]["name"]);

          //if asked, return image size also
          if ($this->apidata["show_image_size"] && $data[$i]["type"]=="image")
          {
          	$arr = $this->getImageSize($data[$i]["id"]);
          	$data[$i]["image_width"] = $arr["width"];
          	$data[$i]["image_height"] = $arr["height"];
					}

      }

			$this->PROTO->add("record",$data[$i]);
  
    }

    //get default view for this collection
		$o = new DOCMGR_OBJECT($parent);
		$view = $o->getView();
		$this->PROTO->add("default_view",$view["default_view"]);
		$this->PROTO->add("account_view",$view["account_view"]);

		//store the object we are browsing in a session to be used by other api calls
		$_SESSION["api"]["current_object_id"] = $this->objectId;

		return $data;

  }

  private function getImageSize($id)
  {
  
  	$sql = "SELECT level1,level2,
  					(SELECT id FROM docmgr.dm_file_history WHERE 
  						dm_file_history.object_id=dm_view_objects.id AND 
  						dm_file_history.version=dm_view_objects.version) AS file_id
						FROM docmgr.dm_view_objects WHERE id='$id'";
		$info = $this->DB->single($sql);
		
		$file = DATA_DIR."/".$info["level1"]."/".$info["level2"]."/".$info["file_id"].".docmgr";
		
		$ret = array();
		
		//get our file  
		if (file_exists($file))
		{  
			$arr = getImageSize($file);
			$ret["width"] = $arr[0];
			$ret["height"] = $arr[1];
		}
		
		return $ret;
		
  }

	/****************************************************************
	  data parameters (keys)
	  search_string -> string of text search db with
	  search_options -> file_name,summary,file_contents: comma delimited
	  search_objects -> file,collection,url,document...: comma delimited
	  limit -> how many results to return
	  offset -> where to start results
	  begin_date -> start date filter from
	  end_date -> end date filter on
	  sort_field -> sort by field	('edit','size','rank','name')
	  sort_dir -> sort in direction ('ASC' or 'DESC')
	****************************************************************/
	public function search($sqlFilter=null) 
	{

	  //passed a path to limit collections to
	  if ($this->apidata["colfilter"]) 
	  	$colfilter = $this->apidata["colfilter"];
	  else if ($this->path && $this->path!="/") 
	  	$colfilter = $this->objectId;
	  else 
	  	$colfilter = null;

		//if outright passed and objectId, use it for our collection filter
	  if ($this->objectId) $colfilter = $this->objectId;

	  //load our search options 
	  $opt = null;
	  $opt["search_string"] = $this->apidata["search_string"];				//string to search for

	  //optional parameters
	  if ($this->apidata["search_option"]) 				$opt["search_option"] = $this->apidata["search_option"];	//search in name, summary or content
	  if ($this->apidata["search_limit"]!=null) 	$opt["search_limit"] = $this->apidata["search_limit"];
	  if ($this->apidata["search_offset"]!=null)	$opt["search_offset"] = $this->apidata["search_offset"];
	  if ($this->apidata["sort_field"])						$opt["sort_field"] = $this->apidata["sort_field"];			//field to sort by
	  if ($this->apidata["sort_dir"])							$opt["sort_dir"] = $this->apidata["sort_dir"];			//sort direction
	  if ($this->apidata["reset"])								$opt["reset"] = $this->apidata["reset"];

	  //if passed the sql filter, add it to the mix.  note we don't allow this to be passed from the API, 
	  //only in a direct function call.  We never want anyone passing sql statements directly into the API
	  //from outside the system
	  if ($sqlFilter)                       $opt["sql_filter"] = $sqlFilter;

	  //restrict to files within current column
	  if ($colfilter) $opt["colfilter"] = $colfilter;
	
	  //restrict responds to certain object ids.  This would be if we want info on a set of objects
		if ($this->apidata["object_filter"]) $opt["object_filter"] = $this->apidata["object_filter"];

		//show only shared files
		if ($this->apidata["account_shared_with"]) $opt["account_shared_with"] = $this->apidata["account_shared_with"];
		if ($this->apidata["account_shared_by"]) $opt["account_shared_by"] = $this->apidata["account_shared_by"];
		if ($this->apidata["account_subscribed"]) $opt["account_subscribed"] = $this->apidata["account_subscribed"];

		//setup the rest of our filters    
		$opt["filter"] = array();
		$opt["match"] = array();
		$opt["value"] = array();
		$opt["data_type"] = array();
		    
    //passed keyword filters
		for ($i=0;$i<count($this->apidata["keyword_filters"]);$i++)
		{
			//only proceed if we have a value
			if ($this->apidata["keyword_values"][$i]!=null)
			{
				$opt["filter"][] = $this->apidata["keyword_filters"][$i];
				$opt["match"][] = $this->apidata["keyword_matches"][$i];
				$opt["value"][] = $this->apidata["keyword_values"][$i];
				$opt["data_type"][] = $this->apidata["keyword_data_types"][$i];
			}
		}

		//passed standard filters       
		for ($i=0;$i<count($this->apidata["filters"]);$i++)
		{
			//only proceed if we have a value
			if ($this->apidata["values"][$i]!=null)
			{
				$opt["filter"][] = $this->apidata["filters"][$i];
				$opt["match"][] = $this->apidata["matches"][$i];
				$opt["value"][] = $this->apidata["values"][$i];
				$opt["data_type"][] = $this->apidata["data_types"][$i];
			}
	  	}

		//run the search
		$s = new DOCMGR_UTIL_QUERY();
		$data = $s->search($opt);

		//output our result counts
	  $this->PROTO->add("current_count",$data["count"]);
    $this->PROTO->add("total_count",$data["total_count"]);
    $this->PROTO->add("search_time",$data["search_time"]);
    
    //the path we were searching in
    if ($this->path) $path = $this->path;
    else $path = "/";
    
    $this->PROTO->add("path",$path);

	  //get all collections that need to be displayed
	  $sql = "SELECT DISTINCT id,name,parent_id,object_type FROM docmgr.dm_view_collections ORDER BY name";
	  $catInfo = $this->DB->fetch($sql,1);
	                                
	  //now, convert it all to data
	  for ($i=0;$i<$data["count"];$i++) 
	  {
	
      //if we have a path filter, make sure we have the version of the file that's under that path
			$data[$i]["object_path"] = $this->getCurrentPath($data[$i]["id"]);

      //make some pretty fields for viewing
      $data[$i]["last_modified_view"] = dateView($data[$i]["last_modified"]);
      $data[$i]["object_directory"] = $data[$i]["level1"]."/".$data[$i]["level2"];

      //where or not it's openoffice compatible
      $info = fileInfo($data[$i]["name"]);
      $data[$i]["openoffice"] = $info["openoffice"];
      $data[$i]["openoffice_edit"] = $info["openoffice_edit"];

      //return the object type and load
      $objectClass = "DOCMGR_".$data[$i]["object_type"];

      $o = new $objectClass();
      $data[$i] = array_merge($data[$i],$o->listDisplay($data[$i]));

			//fix the icon link to only have the name
			$arr = explode("/",$data[$i]["icon"]);
			$data[$i]["icon"] = $arr[count($arr)-1];

      //add file extension
      if ($data[$i]["object_type"]=="file") 
      {
          $data[$i]["type"] =  $type = return_file_type($data[$i]["name"]);
          $data[$i]["extension"] =  $type = return_file_extension($data[$i]["name"]);
      }

      //convert rank to something viewable
      if ($data[$i]["ts_rank"]) $data[$i]["rank"] = $data[$i]["ts_rank"]*100;
      else $data[$i]["rank"] = "100";
      
      $this->PROTO->add("record",$data[$i]);
	  
	  }

	}

	/**
		tree browsing functions
		*/

	public function browsecol() 
	{

		//setup curval

		//if initializing a tree
		if ($this->apidata["init"])
		{
		
			//if passed a ceiling, use that as our root level
			if ($this->apidata["ceiling"]!=null) 
			{
	
				//if passed an object id, use that.  Otherwise get the id from the path
				if (is_numeric($this->apidata["ceiling"])) 
				{
					$ceiling = $this->apidata["ceiling"];
					$ceilPath =  DOCMGR_UTIL_COMMON::getPath($ceiling);
				}
				else 
				{
					$info = $this->objectFromPath($this->apidata["ceiling"]);
					$ceiling = $info["id"];
					$ceilPath = $this->apidata["ceiling"];
				}
				
			}
			else 
			{
				$ceiling = "0";
				$ceilPath = "/";
			}

			if ($this->objectId) $val = $this->objectId;
			else $val = array("0");

			//now expand our column
			loadBaseCollections($val,$ceiling,$ceilPath,1);
		
		}
		else
		{

			$this->expandSingleCol($this->objectId,$this->path,1);
			
		}

	}

	//just show a single level of collections
	protected function expandSingleCol($curValue,$curPath,$showSearch=null) 
	{
	
	  if (!PERM::check(ADMIN)) $ps = " AND ".permString();
	  else $ps = null;

	  if ($showSearch) $table = "dm_view_colsearch";
	  else $table = "dm_view_collections";
	  
	  //somehow, this is faster than the two table query method
	  $sql = "SELECT DISTINCT id,name,parent_id,object_type,
	           (SELECT count(id) FROM 
	             (SELECT id,parent_id FROM docmgr.".$table." WHERE hidden='f') AS mytable
	             WHERE parent_id=".$table.".id ".$ps.") AS child_count
	             FROM docmgr.".$table." WHERE parent_id='$curValue' ".$ps." AND hidden='f' ORDER BY name
	              ";
	  $list = $this->DB->fetch($sql);
	
	  for ($i=0;$i<$list["count"];$i++) 
	  {

	  	if ($curPath=="/") $path = "/".$list[$i]["name"];
	  	else $path = $curPath."/".$list[$i]["name"];
	
	    //first, get the info for this file
	    $arr = array();
	    $arr["id"] = $list[$i]["id"];
	    $arr["name"] = $list[$i]["name"];
	    $arr["child_count"] = $list[$i]["child_count"];
	    $arr["path"] = $path;
	    $arr["object_type"] = $list[$i]["object_type"];
	    $this->PROTO->add("collection",$arr);
	
	  }
	
	}

}
