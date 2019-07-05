<?php

/****************************************************************************
	CLASS:	KEYWORD
	PURPOSE:	master class DOCMGR_for dealing with object keywords
****************************************************************************/

class DOCMGR_UTIL_KEYWORD
{

	private $objectId;
	private $DB;
		
	function __construct($objId=null)
	{

		$this->DB = $GLOBALS["DB"];
	
		if ($objId) $this->objectId = $objId;
			
	}

	public function getAll()
	{
	
		//get all global keywords or ones belonging to this collection  
  	$sql = "SELECT * FROM docmgr.keyword ORDER BY name";
  	$list = $this->DB->fetch($sql);

  	return $list;

	}

	/****************************************************************************
		FUNCTION: saveValues
		PURPOSE:	get all the keywords for the current parent if passed, also
						return the current object's keyword data if there is a current obj
	****************************************************************************/
	public function getlist() 
	{

		$parentId = array();

		if ($this->objectId)
		{

			$parentId[] = $this->objectId;
		
			$sql = "SELECT parent_id FROM docmgr.dm_object_parent WHERE object_id='".$this->objectId."'";
			$list = $this->DB->fetch($sql);

			//recurse all the way up to get parents also
			for ($i=0;$i<$list["count"];$i++)
			{

				$p = DOCMGR_UTIL_COMMON::objectIdPath($list[$i]["parent_id"]);
				
				$parentId = array_merge($parentId,explode(",",$p));

			}
			
			$parentId = arrayReduce(array_values(array_unique($parentId)));
					
		}

		//get all global keywords or ones belonging to this collection  
  	$sql = "SELECT * FROM docmgr.view_keyword_collection WHERE parent_id IS NULL";
  	if ($parentId) $sql .= " OR parent_id IN (".implode(",",$parentId).")";
  	$sql .= " ORDER BY name";
  	$list = $this->DB->fetch($sql);

  	//get all the keyword data for this object
  	if ($this->objectId)
  	{
  		$sql = "SELECT * FROM docmgr.keyword_value WHERE object_id='".$this->objectId."'";
  		$data = $this->DB->fetch($sql,1);
		}
		
		//now create data entries
		for ($i=0;$i<$list["count"];$i++)
		{

			//if it's a dropdown, get the options
			if ($list[$i]["type"]=="select")
			{

				//add a "select one" option if it's not required
				if ($list[$i]["required"]=="f")
				{

					$arr = array();
					$arr["id"] = "[[docmgr_noentry]]";
					$arr["name"] = "Select...";
					$list[$i]["option"][count($list[$i]["option"])] = $arr;

				}
				
				$sql = "SELECT id,name FROM docmgr.keyword_option WHERE keyword_id='".$list[$i]["id"]."' ORDER BY sort_order,name";			
				$options = $this->DB->fetch($sql);
				
				for ($c=0;$c<$options["count"];$c++)
				{

					$arr = array();
					$arr["id"] = "[[docmgr_noentry]]";
					$arr["name"] = "Select...";
					$list[$i]["option"][count($list[$i]["option"])] = $options[$c];
				
				}
				
			}

			//add the object value for this if there is one
			if ($data["count"] > 0)
			{
			
				$key = @array_search($list[$i]["id"],$data["keyword_id"]);
				if ($key!==FALSE)
				{

					//set the value
					$list[$i]["object_value"] = $data["keyword_value"][$key];

					//format if necessary
					if ($list[$i]["type"]=="date") $list[$i]["object_value"] = dateView($list[$i]["object_value"]);

				}

			}

		}  

		return $list;
  
	}

}

