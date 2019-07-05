<?php

/**************************************************************************
	CLASS:	collection
	PURPOSE:	handle specific processing for the collection object
**************************************************************************/

class DOCMGR_COLLECTION extends DOCMGR_AOBJECT
{

	function get()
	{
		return false;
	}
	
	function save()
	{

		//make sure it's saved as a document
		$this->apidata["object_type"] = "collection";

		$o = new DOCMGR_OBJECT($this->apidata);
		$objId = $o->save();	

		//toss and error if we have one
		$err = $o->getError();

		if ($err)
		{ 
			$this->throwError($err);
		}
		else 
		{
			return $objId;
		}
		
	}

	function saveOptions()
	{

		$sql = "SELECT object_id FROM docmgr.object_view WHERE object_id='".$this->objectId."' AND account_id='".USER_ID."'";
		$info = $this->DB->single($sql);
		
		if ($info)	
		{
			$sql = "UPDATE docmgr.object_view SET view='".$this->apidata["account_view"]."' WHERE object_id='".$this->objectId."' AND account_id='".USER_ID."'";
		}
		else
		{
			$sql = "INSERT INTO docmgr.object_view (object_id,account_id,view) VALUES ('".$this->objectId."','".USER_ID."','".$this->apidata["account_view"]."');";
		}

		$this->DB->query($sql);
		
	}

	/***********************************************************************
		FUNCTION: update
		PURPOSE:	called by DOCMGR_OBJECT class DOCMGR_to perform additional processing
							for updating a collection
	***********************************************************************/
	protected function update($data) 
	{

		if ($data["default_view"]) $dv = $data["default_view"];

		$sql = null;
		
		//delete current values for default browsing view, and add new oens
		if ($dv)
		{
		 	$sql .= "DELETE FROM docmgr.object_view WHERE object_id='".$this->objectId."' AND account_id='0';";
			$sql .= "INSERT INTO docmgr.object_view (object_id,account_id,view) VALUES ('".$this->objectId."','0','".$dv."');";
		}

		$this->DB->query($sql);

	}

	/***********************************************************************
		FUNCTION: remove
		PURPOSE:	called by DOCMGR_OBJECT class DOCMGR_to perform additional processing
							for removing a collection
	***********************************************************************/
	protected function remove() 
	{

		//reset everyone's home directory if they were using this one
		$sql = "UPDATE auth.account_config SET home_directory='0' WHERE home_directory='".$this->objectId."'";
		if (!$this->DB->query($sql)) return false;

		//return true if we make it to here
		return true;

	}

	
	/***********************************************************************
	  Displaying:
	  This private function returns the link and the icon to be displayed
	  in the finder in list view
	  return $arr("link" => $link, "icon" => $icon);
	***********************************************************************/
	protected function listDisplay($info) 
	{
	
	  $arr["icon"] = THEME_PATH."/images/fileicons/folder.png";
	  $arr["link"] = "javascript:browseCollection('".$info["id"]."');";
	  return $arr;
	
	}

	/***********************************************************************
		FUNCTION: zip
		PURPOSE:	gets all children of the current collection and 
							zips them up and pushes to them to the browser for download
	***********************************************************************/
	function zip()
	{
	
		$dir = TMP_DIR."/".USER_LOGIN;
	
		//create the temp directory. otherwise empty any previous contents in that dir
		emptyDir($dir);
		recurMkdir($dir);
	
		$sql = "SELECT * FROM docmgr.dm_view_collections WHERE id='".$this->objectId."'";
		$info = $this->DB->single($sql);
		
		//create a folder which is a mirror of our collection
		$arcDir = $this->zipProcessCol($info,$dir);	
	
		if (is_dir($arcDir)) 
		{
	
			$arr = explode("/",$arcDir);
			$arcsrc = array_pop($arr);
	
			$path = $dir."/".$info["name"];
			$path = $this->zipDir($path);
			
			//handle everything else
			header ("Content-Type: application/zip");
			header ("Content-Type: application/force-download");
			header ("Content-Length: ".filesize($path));
			header ("Content-Disposition: attachment; filename=\"".$info["name"].".zip\"");
			header ("Content-Transfer-Encoding:binary");
			header ("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header ("Pragma: public");

			//chunked handles bigger files well 
			readfile_chunked($path);

		} else return false;
	
	}

	/***********************************************************************
		FUNCTION: zipDir
		PURPOSE:	creates a zip archive from all the files in the passed
							directory
	***********************************************************************/
	protected function zipDir($dir)
	{
	
		$zipfile = $dir.".zip";
		$zip = new ZipArchive();
	
		$zip->open($zipfile,ZIPARCHIVE::CREATE);
			
		$arr = listDir($dir);
		
		foreach ($arr AS $file)
		{

			$fileName = str_replace(TMP_DIR."/".USER_LOGIN,"",$file);
			
			if (is_dir($file)) $zip->addEmptyDir($fileName);
			else $zip->addFile($file,$fileName);
		
		}				
	
		return $zipfile;
	
	}

	/***********************************************************************
		FUNCTION: zipProcessFile
		PURPOSE:	copies the called file into the collection for zipping
	***********************************************************************/
	protected function zipProcessFile($obj,$dir) {
	
		$sql = "SELECT name,version,level1,level2 FROM docmgr.dm_view_objects WHERE id='".$obj["id"]."'";
		$objInfo = $this->DB->single($sql);
		$version = $objInfo["version"];
	
		$sql = "SELECT id FROM docmgr.dm_file_history WHERE object_id='".$obj["id"]."' AND version='$version'";
		$info = $this->DB->single($sql);
	
		//copy the file to the temp directory with the correct name
		$filename = $dir."/".$obj["name"];
		$source = DATA_DIR."/".$objInfo["level1"]."/".$objInfo["level2"]."/".$info["id"].".docmgr";

		if (file_exists("$source")) copy("$source","$filename");
	
	}
	
	/***********************************************************************
		FUNCTION: zipProcessCollection
		PURPOSE:	makes a collection and gets all children of it and copies
							them in for download
	***********************************************************************/
	protected function zipProcessCol($obj,$passDir) {
	
		$sql = "SELECT * FROM docmgr.dm_view_objects WHERE parent_id='".$obj["id"]."'";
	
		//add perm string filter if not admin
		if (!PERM::check(ADMIN)) $sql .= " AND ".permString();
	
		$list = $this->DB->fetch($sql);
		
		//first, create a directory with this column.
		$dir = $passDir."/".$obj["name"];
	
		//remove the directory if it is there
		if (is_dir("$dir")) `rm -r "$dir"`;
		mkdir("$dir");
	
		for ($i=0;$i<$list["count"];$i++) 
		{
	
			//only add files and collections to the archive	
			if ($list[$i]["object_type"]=="collection") $this->zipProcessCol($list[$i],$dir);
			else if ($list[$i]["object_type"]=="file") $this->zipProcessFile($list[$i],$dir);
		
		}
	
		//return the directory we created
		return $dir;

	}


	function checkSetup()
	{

		$this->DB->begin();

		//look for a Users collection.  Create if it doesn't exist
		$sql = "SELECT object_id FROM docmgr.dm_view_parent WHERE name='Users' AND parent_id='0'";
		$info = $this->DB->single($sql);
	
		if (!$info) $objId = $this->createUsersCollection();
		else $objId = $info["object_id"];
		
		//now check for a home directory for this account
		$sql = "SELECT object_id FROM docmgr.dm_view_parent WHERE name='".sanitize(USER_LOGIN)."' AND parent_id='$objId'";
		$info = $this->DB->single($sql);
		
		if (!$info) $this->createHomeCollection($objId);

		$this->DB->end();
		
		//check for errors
		if ($this->DB->error()) $this->throwError($this->DB->error());

	}
	
	private function createUsersCollection()
	{
		
		$date = date("Y-m-d H:i:s");
			
		//we do this with raw SQL instead of API calls to bypass permission checks
		$opt = null;
		$opt["name"] = "Users";
		$opt["create_date"] = $date;
		$opt["object_owner"] = 1;
		$opt["version"] = 1;
		$opt["object_type"] = "collection";
		$opt["last_modified"] = $date;
		$opt["modified_by"] = 1;
		$opt["hidden"] = "f";
		$opt["protected"] = "t"; 
		$id = $this->DB->insert("docmgr.dm_object",$opt,"id");
		
		if ($id)
		{
		
			$opt = null;
			$opt["object_id"] = $id;
			$opt["parent_id"] = "0";
			$opt["account_id"] = 1;
			$this->DB->insert("docmgr.dm_object_parent",$opt);
		
			$opt = null;
			$opt["object_id"] = $id;
			$opt["group_id"] = "0";
			$opt["bitmask"] = "00000100";
			$this->DB->insert("docmgr.dm_object_perm",$opt);

			//queue it for indexing
			$opt = null;
			$opt["object_id"] = $id;
			$opt["account_id"] = 1;
			$opt["notify_user"] = "f";
			$opt["create_date"] = $date;
			$this->DB->insert("docmgr.dm_index_queue",$opt);
		
		}

		return $id;		
			
	}

	private function createHomeCollection($parent)
	{

		$date = date("Y-m-d H:i:s");
			
		//we do this with raw SQL instead of API calls to bypass permission checks
		$opt = null;
		$opt["name"] = USER_LOGIN;
		$opt["create_date"] = $date;
		$opt["object_owner"] = USER_ID;
		$opt["version"] = 1;
		$opt["object_type"] = "collection";
		$opt["last_modified"] = $date;
		$opt["modified_by"] = USER_ID;
		$opt["hidden"] = "f";
		$opt["protected"] = "t"; 
		$id = $this->DB->insert("docmgr.dm_object",$opt,"id");
		
		if ($id)
		{
		
			$opt = null;
			$opt["object_id"] = $id;
			$opt["parent_id"] = $parent;
			$opt["account_id"] = USER_ID;
			$this->DB->insert("docmgr.dm_object_parent",$opt);

			//owner gets admin rights to their home directory		
			$opt = null;
			$opt["object_id"] = $id;
			$opt["account_id"] = USER_ID;
			$opt["bitmask"] = "00000001";
			$this->DB->insert("docmgr.dm_object_perm",$opt);

			//queue it for indexing
			$opt = null;
			$opt["object_id"] = $id;
			$opt["account_id"] = USER_ID;
			$opt["notify_user"] = "f";
			$opt["create_date"] = $date;
			$this->DB->insert("docmgr.dm_index_queue",$opt);
		
		}

		return $id;		
			
	}

	function createUserHome()
	{

		//perm check
		if (!PERM::check(ADMIN))
		{
			$this->throwError(_I18N_PERMISSION_DENIED);
			return false;
		}

		//sanity check
		if (!$this->apidata["account_id"])
		{
			$this->throwError(_I18N_ACCOUNTID_NOTPASSED);
			return false;
		}

		$accountId = $this->apidata["account_id"];

		$a = new ACCOUNT($accountId);
		$info = $a->getInfo();
		$login = $info["login"];
		
		$date = date("Y-m-d H:i:s");

		//look for a Users collection.  Create if it doesn't exist
		$sql = "SELECT object_id FROM docmgr.dm_view_parent WHERE name='Users' AND parent_id='0'";
		$info = $this->DB->single($sql);

		$parent = $info["object_id"];

		//bail if we couldn't find the Users collection
		if (!$parent)
		{
			$this->throwError(_I18N_PARENT_NOTFOUND);
			return false;
		}
		
		//make the collection if it doesn't exist
		$sql = "SELECT object_id FROM docmgr.dm_view_parent WHERE name='$login' AND parent_id='$parent'";
		$info = $this->DB->single($sql);
		
		if (!$info)
		{
				
			//we do this with raw SQL instead of API calls to bypass permission checks
			$opt = null;
			$opt["name"] = $login;
			$opt["create_date"] = $date;
			$opt["object_owner"] = $accountId;
			$opt["version"] = 1;
			$opt["object_type"] = "collection";
			$opt["last_modified"] = $date;
			$opt["modified_by"] = $accountId;
			$opt["hidden"] = "f";
			$opt["protected"] = "t"; 
			$id = $this->DB->insert("docmgr.dm_object",$opt,"id");
			
			if ($id)
			{
			
				$opt = null;
				$opt["object_id"] = $id;
				$opt["parent_id"] = $parent;
				$opt["account_id"] = $accountId;
				$this->DB->insert("docmgr.dm_object_parent",$opt);
	
				//owner gets admin rights to their home directory		
				$opt = null;
				$opt["object_id"] = $id;
				$opt["account_id"] = $accountId;
				$opt["bitmask"] = "00000001";
				$this->DB->insert("docmgr.dm_object_perm",$opt);
	
				//queue it for indexing
				$opt = null;
				$opt["object_id"] = $id;
				$opt["account_id"] = $accountId;
				$opt["notify_user"] = "f";
				$opt["create_date"] = $date;
				$this->DB->insert("docmgr.dm_index_queue",$opt);
			
			}
	
		}

	}

	function getChildren()
	{
 
		$sql = "SELECT object_id FROM docmgr.dm_object_parent WHERE parent_id='".$this->objectId."'";
		$list = $this->DB->fetch($sql,1);
 
		return $list["object_id"];
 
	}					
}
	