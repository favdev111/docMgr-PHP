<?php

/**************************************************************************
	CLASS:	bookmark
	PURPOSE:	handle specific processing for bookmarks
**************************************************************************/

class DOCMGR_BOOKMARK extends DOCMGR 
{

  protected function checkPermissions()
  {

  	$check = true;

		//if passed a bookmark, make sure we own it or are an admin
		if ($this->apidata["bookmark_id"] && !PERM::check(ADMIN))
		{

			$sql = "SELECT account_id FROM docmgr.dm_bookmark WHERE id='".$this->apidata["bookmark_id"]."'";
			$info = $this->DB->single($sql);
			
			if ($info["account_id"]!=USER_ID) 
			{
				$check = false;
				$this->throwError(_I18N_BOOKMARK_EDIT_ERROR);
			}
					
		}  

		//passed an account id that's not ours and non-admin
		if (	$check==true && 
					$this->apidata["account_id"] && 
					$this->apidata["account_id"]!=USER_ID && 
					!PERM::check(ADMIN)
					)
		{
			$check = false;
			$this->throwError(_I18N_BOOKMARK_EDITOTHERUSER_ERROR);
		}

		return $check;
  
  }

	/***********************************************************************
		FUNCTION: get
		PURPOSE:	pulls a list of all bookmarks for this user
	***********************************************************************/
	public function search() 
	{

		//permissions checking
		if (!$this->checkPermissions()) return false;

		//allow admins to edit other user's bookmarks
		if ($this->apidata["account_id"]) $aid = $this->apidata["account_id"];
		else $aid = USER_ID;

		//get the children of the bookmark
		$sql = "SELECT docmgr.dm_bookmark.*,
									(SELECT count(id) FROM docmgr.dm_view_colsearch WHERE parent_id=docmgr.dm_bookmark.object_id AND hidden='f') AS child_count
									 FROM docmgr.dm_bookmark WHERE account_id='$aid' ORDER BY lower(name)";
		$list = $this->DB->fetch($sql);

		if ($list["count"] > 0)
		{

			for ($i=0;$i<$list["count"];$i++) 
			{
	
	      $pids = DOCMGR_UTIL_COMMON::resolvePathIds($list[$i]["object_id"]);
				$list[$i]["object_path"] = DOCMGR_UTIL_COMMON::idToPath($pids);
				$list[$i]["object_type"] = "collection";
					                  
			}

			unset($list["count"]);
			$this->PROTO->add("record",$list);

		}
		else
		{
			unset($list["count"]);
		}

		return $list;
		
	}

	/***********************************************************************
		FUNCTION: save
		PURPOSE:	pulls a list of all bookmarks for this user
	***********************************************************************/
	public function save() 
	{

		//permissions checking
		if (!$this->checkPermissions()) return false;

		//process and update from the manager		
		if ($this->apidata["bookmark_id"]) 
		{

			$opt = null;
			
			//user can set these options
			$opt["name"] = $this->apidata["name"];
			$opt["default_browse"] = $this->apidata["default_browse"];

			//this can only be passed by the admin utility
			if (isset($this->apidata["object_id"])) $opt["object_id"] = $this->apidata["object_id"];

			//run the query
			$opt["where"] = "id='".$this->apidata["bookmark_id"]."'";
			$this->DB->update("docmgr.dm_bookmark",$opt);

			//if passed a new default bookmark, set the rest of them to not default
			if ($this->apidata["default_browse"]=="t")
			{

				if ($this->apidata["account_id"]) $aid = $this->apidata["account_id"];
				else $aid = USER_ID;

				$sql = "UPDATE docmgr.dm_bookmark SET default_browse='f' WHERE account_id='".$aid."' AND id!='".$this->apidata["bookmark_id"]."'";
				$this->DB->query($sql);
				
			}
			
			$id = $this->apidata["bookmark_id"];

		//process a new bookmark
		} 
		else 
		{

			if ($this->apidata["account_id"]) $aid = $this->apidata["account_id"];
			else $aid = USER_ID;
			
			$sql = "SELECT id FROM docmgr.dm_bookmark WHERE account_id='".$aid."' AND name='".$this->apidata["name"]."'";
			$info = $this->DB->single($sql);
			
			if ($info)
			{
				$this->throwError(_I18N_BOOKMARK_EXISTS_ERROR);
				return false;
			}

			$opt = null;
			$opt["name"] = $this->apidata["name"];
			$opt["account_id"] = $aid;
			$opt["expandable"] = "t";
			$opt["protected"] = $this->apidata["protected"];
			$opt["object_id"] = $this->objectId;
			$id = $this->DB->insert("docmgr.dm_bookmark",$opt,"id");		
								
		}

		$this->PROTO->add("bookmark_id",$id);
	
	}

	public function getByName()
	{

		$sql = "SELECT * FROM docmgr.dm_bookmark WHERE account_id='".USER_ID."' AND name='".$this->apidata["name"]."'";
		$info = $this->DB->single($sql);

		if ($info)
		{

			$pids = DOCMGR_UTIL_COMMON::resolvePathIds($info["object_id"]);
			$info["object_path"] = DOCMGR_UTIL_COMMON::idToPath($pids);
			$info["object_type"] = "collection";

			$this->PROTO->add("record",$info);

		}
			
		return $info;	
			
	}

	/***********************************************************************
		FUNCTION: get
		PURPOSE:	removes the passed bookmark
	***********************************************************************/
	public function get() 
	{

		//permissions checking
		if (!$this->checkPermissions()) return false;

		//nothing passed, nothing to do
		if (!$this->apidata["bookmark_id"]) return false;
		
		$sql = "SELECT * FROM docmgr.dm_bookmark WHERE id='".$this->apidata["bookmark_id"]."'";
		$info = $this->DB->single($sql);
		
		$this->PROTO->add("record",$info);
				
	}

	/***********************************************************************
		FUNCTION: delete
		PURPOSE:	removes the passed bookmark
	***********************************************************************/
	public function delete() 
	{

		//permissions checking
		if (!$this->checkPermissions()) return false;

		$sql = "SELECT account_id,protected FROM docmgr.dm_bookmark WHERE id='".$this->apidata["bookmark_id"]."'";
		$info = $this->DB->single($sql);
		
		if ($info["protected"]=="t") 
		{
			$this->throwError(_I18N_BOOKMARK_PROTECT_ERROR);
		}
		else 
		{
		
			$sql = "DELETE FROM docmgr.dm_bookmark WHERE id='".$this->apidata["bookmark_id"]."'";
			$this->DB->query($sql);

		}
		
	}

	public function checkSetup()
	{

		//setup our bookmarks
		$b = new DOCMGR_BOOKMARK();
		$list = $b->search();
		
		$u = null;
		$r = null;
		
		//if there are some bookmarks for this user, make sure we have the required ones
		for ($i=0;$i<count($list);$i++)
		{

			$b = $list[$i];
			
			if ($b["object_path"]=="/Users/".USER_LOGIN) $u = 1;
			if ($b["object_path"]=="/") $r = 1;
		
		}
		
		//create the ones we are missing
		if (!$u)
		{
		
			$arr = null;
			$arr["path"] = "/Users/".USER_LOGIN;
			$arr["name"] = USER_LOGIN;
			$arr["expandable"] = "t";
			$arr["protected"] = "1";
		
			$b = new DOCMGR_BOOKMARK($arr);
			$b->save();
		
		}
		//if they can create or browse root, but there is no root, make one
		if (!$r && (PERM::check(CREATE_ROOT) || PERM::check(BROWSE_ROOT))) 
		{
		
			$arr = null;
			$arr["path"] = "/";
			$arr["name"] = ROOT_NAME;
			$arr["expandable"] = "t";
			$arr["protected"] = "1"; 
		
			$b = new DOCMGR_BOOKMARK($arr);
			$b->save();
		
		}
			
	}
	
	function getDefaultPath()
	{
		$sql = "SELECT object_id FROM docmgr.dm_bookmark WHERE account_id='".USER_ID."' AND default_browse='t'";
		$info = $this->DB->single($sql);
		
		if ($info)
		{
			$path = DOCMGR_UTIL_COMMON::getPath($info["object_id"]);
			$this->PROTO->add("default_path",$path);
		}
		
	}
					
}
		
		