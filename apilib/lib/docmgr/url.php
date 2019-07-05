<?php

/**********************************************************************
	CLASS:	URL
	PURPOSE:	handles specific processing for document objects
**********************************************************************/
class DOCMGR_URL extends DOCMGR_AOBJECT
{

  /*******************************************************************************
  	called from DOCMGR class
	*******************************************************************************/

  /********************************************************************
  	FUNCTION: get
  	PURPOSE:  retrieves document from the system
	********************************************************************/
	public function get() 
	{

		$sql = "SELECT url FROM docmgr.dm_url WHERE object_id='".$this->objectId."'";
		$info = $this->DB->single($sql);
		
		if ($info) $this->PROTO->add("url",$info["url"]);
		else $this->throwError(_I18N_URL_NOTFOUND_ERROR);
	
	}

  /********************************************************************
  	FUNCTION: save
  	PURPOSE:  retrieves document from the system
	********************************************************************/
	public function save() 
	{

		$this->apidata["object_type"] = "url";
		
		$o = new DOCMGR_OBJECT($this->apidata);
		$o->save();

		//toss and error if we have one
		$err = $o->getError();    
		if ($err) $this->throwError($err[0],$err[1]);        

	}
	
  /*****************************************************************************************
  	FUNCTION: update
  	PURPOSE:  called from create method in object class
  	INPUT:		none
	*****************************************************************************************/
	protected function update($data) 
	{

	  //make sure the url has an http in it
	  if (!strstr($data["url"],"http://") && !strstr($data["url"],"https://"))
	  {
	    $data["url"] = "http://".$data["url"];
    }

    //add the url to the docmgr.dm_url table
    $sql = "DELETE FROM docmgr.dm_url WHERE object_id='".$this->objectId."';
            INSERT INTO docmgr.dm_url (object_id,url) VALUES ('".$this->objectId."','".$data["url"]."');";
    return $this->DB->query($sql);
                            	
	}

  /*******************************************************************************
  	end called from DOCMGR class
	*******************************************************************************/

	/****************************************************************************************
		called from the DOCMGR_OBJECT class
	****************************************************************************************/


  /*****************************************************************************************
  	FUNCTION: delete
  	PURPOSE:  additional processing function, called from DOCMGR_OBJECT class.  handles doc specific
							processing when deleting the object
	*****************************************************************************************/
	protected function remove() 
	{

		//delete the database entry
		$sql = "DELETE FROM docmgr.dm_url WHERE object_id='".$this->objectId."'";
		return $this->DB->query($sql);

	}


	/**************************************************************************************
		end DOCMGR_OBJECT class DOCMGR_calls
	**************************************************************************************/

  /*****************************************************************************************
  	FUNCTION: index
  	PURPOSE:  called from DOCMGR_OBJINDEX class.  Returns document content to be indexed
	*****************************************************************************************/
	protected function index() 
	{

		$sql = "SELECT url FROM docmgr.dm_url WHERE object_id='".$this->objectId."';";
		$info = $this->DB->single($sql);
		$url = $info["url"];
	
		//download the file
		$file = TMP_DIR."/".rand().".html";
		system(APP_WGET." \"".$file."\" \"".$url."\"");

		//return the contents of the file
		$str = file_get_contents($file);
		$str = DOCMGR_OBJINDEX::removeTags($str);
		
		@unlink($file);
		
		return $str;

	}

	/***************************************************************************
		common functions for this class
	***************************************************************************/

	/**************************************************************************
		end common
	**************************************************************************/	

	
	/***********************************************************************
	  Displaying:
	  This function returns the link and the icon to be displayed
	  in the finder in list view
	  return $arr("link" => $link, "icon" => $icon);
	***********************************************************************/
	protected function listDisplay($info) 
	{
	
	  $arr["icon"] = THEME_PATH."/images/fileicons/url.png";
	  $arr["link"] = "index.php?module=urlview&objectId=".$info["id"];
	  $arr["target"] = "_blank";	//open in a new window
	
	  return $arr;
	                                                                                                                
	}
	
}
