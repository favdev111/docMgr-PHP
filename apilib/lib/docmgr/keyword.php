<?php

/****************************************************************************
	CLASS:	KEYWORD
	PURPOSE:	master class DOCMGR_for dealing with object keywords
****************************************************************************/

class DOCMGR_KEYWORD extends DOCMGR 
{

	/**
		builds and returns an eform compatible array for filtering by keywords
		*/
	public function buildfilters()
	{

		$k = new DOCMGR_UTIL_KEYWORD($_SESSION["api"]["current_object_id"]);
		$list = $k->getlist();

		
		$results = array();
			 
		for ($i=0;$i<$list["count"];$i++)
		{

			$opt = null;

			//setup our basic form options
			$opt["title"] = $list[$i]["name"];
			$opt["data"] = "keyword_".$list[$i]["id"];
			$opt["type"] = "textbox";
			$opt["data_type"] = $list[$i]["type"];
			
			//setup our match options			
			if ($opt["data_type"]=="date")
			{

				$opt["match"] = array();
				$opt["match"][0]["title"] = "on";
				$opt["match"][0]["data"] = "on";
				$opt["match"][1]["title"] = "before";
				$opt["match"][1]["data"] = "before";
				$opt["match"][2]["title"] = "after";
				$opt["match"][2]["data"] = "after";
			}
			else if ($opt["data_type"]=="number")
			{

				$opt["match"] = array();
				$opt["match"][0]["title"] = "equals";
				$opt["match"][0]["data"] = "equal";
				$opt["match"][1]["title"] = "greater than or equal";
				$opt["match"][1]["data"] = "greaterequal";
				$opt["match"][2]["title"] = "lesser than or equal";
				$opt["match"][2]["data"] = "lesserequal";
			}
			else
			{
				$opt["match"] = array();
				$opt["match"][0]["title"] = "equals";
				$opt["match"][0]["data"] = "equal";
				$opt["match"][1]["title"] = "does not equal";
				$opt["match"][1]["data"] = "notequal";
				$opt["match"][2]["title"] = "contains";
				$opt["match"][2]["data"] = "contain";
				$opt["match"][3]["title"] = "does not contain";
				$opt["match"][3]["data"] = "notcontain";
			}


			$results[] = $opt;			
		
		}

		return $results;

	}

	/****************************************************************************
		FUNCTION: saveValues
		PURPOSE:	get all the keywords for the current parent if passed, also
						return the current object's keyword data if there is a current obj
	****************************************************************************/
	public function search() 
	{

		$k = new DOCMGR_UTIL_KEYWORD($this->objectId);
		$list = $k->getlist();
		
		if ($list["count"]>0)
		{
			unset($list["count"]);
			$this->PROTO->add("record",$list);
		}
  
	}

	/**
		saves the passed keyword value for the specified object
		*/
	function save()
	{

		//make sure we have permissions to manage this object
		if (!$this->permCheck("edit"))
		{
			$this->throwError(_I18N_OBJECT_MANAGE_ERROR);
			return false;
		}
                                  
		//shortcuts
		$ids = &$this->apidata["keyword_id"];
		$values = &$this->apidata["keyword_value"];

		$sql = "DELETE FROM docmgr.keyword_value WHERE object_id='".$this->objectId."';";
		
		for ($i=0;$i<count($ids);$i++)
		{
		
			//don't save noentries
			if ($ids[$i]=="[[docmgr_noentry]]") continue;
		
			//get the type of keyword to see if we need to do any special formatting
			$infosql = "SELECT type FROM docmgr.keyword WHERE id='".$ids[$i]."'";
			$info = $this->DB->single($infosql);
		
			//do formatting
			if ($info["type"]=="date") $values[$i] = dateProcess($values[$i]);
			else if ($info["type"]=="number") $values[$i] = priceProcess($values[$i]);		//will only leave numbers and decimals
					
			//save the value
			$opt = null;
			$opt["object_id"] = $this->objectId;
			$opt["keyword_id"] = $ids[$i];
			$opt["keyword_value"] = $values[$i];
			$opt["query"] = 1;
			$sql .= $this->DB->insert("docmgr.keyword_value",$opt).";";
		
		}
		
		$this->DB->query($sql);
			
	}
		
}
