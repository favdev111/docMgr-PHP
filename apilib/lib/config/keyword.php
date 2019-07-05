<?php

/**
	admin class for managing keywords and their options
	*/

class CONFIG_KEYWORD extends CONFIG
{

	private $keywordId;

	public function ___construct()
	{

		//only admins can be here
		if (!PERM::check(ADMIN))
		{
			$this->throwError(_I18N_PERMISSION_DENIED);
			return false;
		}

		if ($this->apidata["keyword_id"]) $this->keywordId = $this->apidata["keyword_id"];
	
	}

	/**
		return all keywords in the system
		*/
	public function search()
	{

		$k = new DOCMGR_UTIL_KEYWORD();
		$list = $k->getAll();
		
		if ($list["count"]>0)
		{
			unset($list["count"]);
			$this->PROTO->add("record",$list);
		}

	}

  /**
  	get all info for the passed keyword_id
  	*/
  public function get()
  {

	  $sql = "SELECT * FROM docmgr.keyword WHERE id='".$this->keywordId."'";
	  $info = $this->DB->single($sql);

		//get any parents this may be limited to
	  $sql = "SELECT parent_id FROM docmgr.keyword_collection WHERE keyword_id='".$this->keywordId."'";
	  $parents = $this->DB->fetch($sql,1);
  
	  if ($parents["count"] > 0)
	  {
	  	unset($parents["count"]);
	  	$info["parent_id"] = $parents["parent_id"];
		}

		//get any select options that come w/ it
	  $sql = "SELECT id,name FROM docmgr.keyword_option WHERE keyword_id='".$this->keywordId."'";
	  $options = $this->DB->fetch($sql,1);
  
	  if ($options["count"] > 0)
	  {
	  	unset($options["count"]);
	  	$info["option"] = $options;
		}
		
  	$this->PROTO->add("record",$info);

  }

	/**
		saves core information for the keyword
		*/
	public function save()
	{

		$this->DB->begin();
		
  	$opt = null;
  	$opt["name"] = $this->apidata["name"];
  	$opt["type"] = $this->apidata["type"];
  	$opt["required"] = $this->apidata["required"];

  	if ($this->keywordId)
  	{
    	$opt["where"] = "id='".$this->keywordId."'";
    	$this->DB->update("docmgr.keyword",$opt);
		} 
		else 
		{
    	$keywordId = $this->DB->insert("docmgr.keyword",$opt,"id");
		}
		
		$this->DB->end();

		//return the keyword info
		$this->PROTO->add("keyword_id",$keywordId);

	}

	/**
		remove a keyword and all associated info from the db
		*/
	public function delete()
	{

  	$keywordId = $this->apidata["keyword_id"];
	
	  $sql = "DELETE FROM docmgr.keyword WHERE id='".$this->keywordId."';";
	  $sql .= "DELETE FROM docmgr.keyword_option WHERE keyword_id='".$this->keywordId."';";
	  $sql .= "DELETE FROM docmgr.keyword_collection WHERE keyword_id='".$this->keywordId."';";
	  $sql .= "DELETE FROM docmgr.keyword_value WHERE keyword_id='".$this->keywordId."';";
	  $this->DB->query($sql);

	}  

	public function saveParent()
	{
	
		//update which collections this keyword applies to
		$sql = "DELETE FROM docmgr.keyword_collection WHERE keyword_id='".$this->keywordId."';";
  
		$parents = $this->apidata["parent_id"];
		if ($parents && !is_array($parents)) $parents = array($parents);
		
		for ($i=0;$i<count($parents);$i++) 
		{
    	$sql .= "INSERT INTO docmgr.keyword_collection (keyword_id,parent_id) VALUES ('".$this->keywordId."','".$parents[$i]."');";
		}
  
		$this->DB->query($sql);

	}

	/****************************************************************************
		FUNCTION: saveOption
		PURPOSE:	add a select keyword option
	****************************************************************************/
	public function saveOption()
	{

	  $opt = null;
	  $opt["name"] = $this->apidata["name"];
	  $opt["sort_order"] = $this->apidata["sort_order"];

	  if ($this->apidata["option_id"]) 
	  {
	  	$opt["where"] = "id='".$this->apidata["option_id"]."'";
	  	$this->DB->update("docmgr.keyword_option",$opt);
	  	$optionId = $this->apidata["option_id"];
		}
		else
		{
		  if (!$opt["sort_order"])
		  {
		    $sql = "SELECT max(sort_order) AS max FROM docmgr.keyword_option WHERE keyword_id='".$this->apidata["keyword_id"]."'";
		    $info = $this->DB->single($sql);

		    $opt["sort_order"] = $info["max"] + 1;
      }
      
		  $opt["keyword_id"] = $this->apidata["keyword_id"];
		  $optionId = $this->DB->insert("docmgr.keyword_option",$opt,"id");
		}

		$this->PROTO->add("option_id",$optionId);
	
	}

	/****************************************************************************
		FUNCTION: deleteOption
		PURPOSE:	removes a select keyword option
	****************************************************************************/
	public function deleteOption()
	{

		$optionId = $this->apidata["option_id"];
		if (!is_array($optionId)) $optionId = array($optionId);
		
  	$sql = null;
  
  	for ($i=0;$i<count($optionId);$i++) 
  	{
	    $sql .= "DELETE FROM docmgr.keyword_option WHERE id='".$optionId[$i]."';";
		}
		
		if ($sql) $this->DB->query($sql);

  }


  /****************************************************************************
		FUNCTION: getoptions
		PURPOSE:	get all setup options for a select keyword
	****************************************************************************/
	public function searchOptions()
	{

  	$keywordId = $this->apidata["keyword_id"];
	
	  $sql = "SELECT * FROM docmgr.keyword_option WHERE keyword_id='".$this->keywordId."' ORDER BY sort_order,name";
	  $optionList = $this->DB->fetch($sql);

	  for ($i=0;$i<$optionList["count"];$i++)
	  {
	  	$this->PROTO->add("record",$optionList[$i]);
		}
		
	}  

  /****************************************************************************
		FUNCTION: getoptions
		PURPOSE:	get all setup options for a select keyword
	****************************************************************************/
	public function getOption()
	{

	  $sql = "SELECT * FROM docmgr.keyword_option WHERE id='".$this->apidata["option_id"]."'";
	  $info = $this->DB->single($sql);
	  
		$this->PROTO->add("record",$info);
		
	}  


}
