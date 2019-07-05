<?php

//get our main includes
require_once(SITE_PATH."/apilib/lib/docmgr/lib/setup.php");

//get subclass libraries
class DOCMGR
{

  public $path;
  public $objectId;
  public $objectInfo;

	//local vars
  protected $conn;
  protected $apidata;
  protected $parentId;
  protected $parentPath;
  protected $errorMsg;
  protected $errorCode;
  protected $DB;
  protected $objectBitset;
  protected $PROTO;
    
  //this thing takes an id or an array, so an object id can be passed to init the object  
  function __construct($initdata=null) 
  {

    //db resources
    $this->conn = $GLOBALS["conn"];  
    $this->DB = $GLOBALS["DB"];
    $this->PROTO = $GLOBALS["PROTO"];

    //called for logged in users
    if (defined("USER_ID")) $this->checkUserSetup();

    if ($initdata)
    {

	    //passed an object id, run with that
	    if (is_numeric($initdata)) 
	    {

	      $this->objectId = $initdata;
	      $this->setObjectInfo();

	      //set because some subclasses expect this
        $this->apidata["object_id"] = $this->objectId;	

			} 
			//passed the object path.  run with that
			else if ($initdata && !is_array($initdata)) 
			{
	
				$this->path = $initdata;
				$this->setObjectInfo();			

	      //set because some subclasses expect this
        $this->apidata["object_id"] = $this->objectId;	
	
	    } 
	    //passed an array of apidata
	    else 
	    { 
	
	      $this->apidata = $initdata;

		    //if passed multiple object ids, just store and move on
	      if (@is_array($this->apidata["object_id"])) 
	      {
	
	        $this->objectId = $this->apidata["object_id"];
					
	      //same with multiple paths
	      } 
	      else if (@is_array($this->apidata["path"])) 
	      {
	
	        //convert to an array of object ids
	        $this->objectId = array();
	 
	        foreach ($this->apidata["path"] AS $path) 
	        {
	          $info = $this->objectFromPath($path);
	          $this->objectId[] = $info["id"];
	        }
	
	      //passed an array of processing data to work with
	      } 
	      else 
	      {

	        if ($this->apidata["object_id"]!=null) 
	        {
	
	        	$this->objectId = $this->apidata["object_id"];
	          $this->setObjectInfo();
	
	        } 
	        else if ($this->apidata["path"]) 
	        {
	
	          $this->path = $this->apidata["path"];
	          $this->setObjectInfo();
	      
	        }
	
	      }
	
	    }

		}

		//look for subclass constructor
		if (validMethod($this,"___construct")) $this->___construct();

  }  

  /*********************************************************************
    FUNCTION:	setObjectInfo
    PURPOSE:	stores generic object info in our class variables
  *********************************************************************/
  protected function setObjectInfo() 
  {

    //get our object info from the id if available, otherwise pull from the path
    if ($this->objectId) 
    {

    	$sql = "SELECT dm_object.*,level1,level2 FROM docmgr.dm_object 
    					LEFT JOIN docmgr.dm_dirlevel ON dm_dirlevel.object_id=dm_object.id
    					WHERE id='".$this->objectId."'";
    	$this->objectInfo = $this->DB->single($sql);

    }
    //passed a path, get the info from that instead 
    else 
    {
    
    	if ($this->path=="/") $this->objectId = "0";
    	else 
    	{

     		$this->objectInfo = $this->objectFromPath($this->path);
     		if ($this->objectInfo) $this->objectId = $this->objectInfo["id"];

			}

		}

    //add the path to our file for later reference
    if ($this->objectInfo) 
    {

  		//now that we have our object id, we need to get permissions for it
	  	$this->objectBitset = DOCMGR_UTIL_OBJPERM::getUser($this->objectId);

	  	//if the user does not have at least view permissions for an object 
	  	//passed to the API, then they have no business going any further
	  	if (!$this->permCheck("view"))
	  	{
	  		$this->throwError(_I18N_PERMISSION_DENIED);
	  		return false;
	  	}

			//merge in permission values
			$this->objectInfo = array_merge($this->objectInfo,DOCMGR_UTIL_OBJPERM::getUserText($this->objectBitset,$this->objectInfo["object_owner"]));
				
			//set some id and display paths 
			$this->setObjectPath();

			//shortcuts
			$this->path = $this->objectInfo["object_path"];

		
		//otherwise objectInfo to prevent false returns							
    } 
    else if ($this->path=="/" || $this->objectId=="0")
    {
    
    	$cb = "00000000";
    	
			//setup some root level permissions
			if (PERM::check(ADMIN))								    
			{
				$this->objectBitset = PERM::bit_set($cb,OBJ_ADMIN);
			}
			else
			{
			
				//if they can create objects in root, give them edit.  if browse only, then view
				if (PERM::check(CREATE_ROOT)) $this->objectBitset = PERM::bit_set($cb,OBJ_EDIT);
				else if (PERM::check(BROWSE_ROOT)) $this->objectBitset = PERM::bit_set($cb,OBJ_VIEW);
				else
				{

					//otherwise they have no business being here
					$this->throwError(_I18N_PERMISSION_DENIED);
				
				}

			}
			
    }

  }      

  /*********************************************************************
    FUNCTION:	permCheck
    PURPOSE:	checks to see if the current user has passed permissions
    					on the current object
  *********************************************************************/
  function permCheck($perm,$bitset=null)
  {
  
  	if ($bitset) $check = $bitset;
  	else $check = $this->objectBitset;

  	return DOCMGR_UTIL_OBJPERM::check($check,$perm);
  
  }

  /*********************************************************************
    FUNCTION:	setObjectId
    PURPOSE:	stores generic object id in our class variables
  *********************************************************************/
  protected function setObjectId($id) {
    $this->objectId = $id;
  }

  /*********************************************************************
    FUNCTION:	getObjectId
    PURPOSE:	returns class objectid
  *********************************************************************/
  public function getObjectId() {
    return $this->objectId;
  }
  
  /*********************************************************************
    FUNCTION:	getObjectId
    PURPOSE:	returns class object path
  *********************************************************************/
  public function getObjectPath() {

  	return $this->path;

  }

  /*********************************************************************
    FUNCTION:	getObjectName
    PURPOSE:	returns class object name
  *********************************************************************/
  public function getObjectName() {

  	if ($this->objectId=="0") return ROOT_NAME;
    else return $this->objectInfo["name"];

  }

  /*********************************************************************
    FUNCTION:	getObjectInfo
    PURPOSE:	returns base object info
  *********************************************************************/
  public function getObjectInfo() {

  	return $this->objectInfo;

  }

  /*********************************************************************
    FUNCTION:	getObjectDir
    PURPOSE:	returns the filesystem directory for this object
  *********************************************************************/
  protected function getObjectDir($objId = null) {

  	$dir = null;

		if ($objId)
		{
		
			//get the values for this object
			$sql = "SELECT level1,level2 FROM docmgr.dm_dirlevel WHERE object_id='$objId'";
			$info = $this->DB->single($sql);

			//merge into a dir structure and return
			if ($info) $dir = $info["level1"]."/".$info["level2"];
		
		} else {
		
			//return the filesystem directory for this object  
	  	if ($this->objectInfo["level1"]) $dir = $this->objectInfo["level1"]."/".$this->objectInfo["level2"];
		
		}

		return $dir;

  }

  /*********************************************************************
    FUNCTION:	getError
    PURPOSE:	returns any error or error code set by the api or
              its subclasses
  *********************************************************************/
  public function getError() 
  {

  	//only return data if there is an error
		if ($this->errorMsg || $this->errorCode)  
 			return array($this->errorMsg,$this->errorCode); 
    else
    	return false;
    	
  }
  
  /*********************************************************************
    FUNCTION:	throwError
    PURPOSE:	stores an error and optional code for this class 
              or any subclasses its subclasses
  *********************************************************************/
  protected function throwError($msg,$code=null) 
  {
  
    $this->errorMsg = $msg;
   	$this->errorCode = $code;

  }

  /**********************************************************************
  	some utility functions for object manipulation
	**********************************************************************/

	/*********************************************************
		FUNCTION:	incrementName
		PURPOSE:	adds an extension to the end of a file name
							to prevent name duplication in a collection
	*********************************************************/

	protected function incrementName($name) 
	{
		
		$ext = return_file_extension($name);
		$pos = strrpos($name,".");
		
		//if no extension or marker, just add and get out
		if ($pos===FALSE) return $name.".1";
		
		//remove the extension
		$ext = substr($name,$pos+1);
		$basename = substr($name,0,$pos);
		
		//look for another period as we are storing in filename.1.ext
		$pos2 = strrpos($basename,".");
		if ($pos2===FALSE) 
		{
			$inc = 1;
			$core = $basename;
		} else {
			//extract the core and the marker, increment the marker by one
			$core = substr($basename,0,$pos2);
			$inc = substr($basename,$pos2+1,$pos-$pos2);
			$inc++;
		}
		
		//reincorporate everything
		$retname = $core.".".$inc.".".$ext;
		
		return $retname;
		
	}


	/*********************************************************
		FUNCTION:	objectFromPath
		PURPOSE:	returns the id of an object specified by
							it's docmgr path
	*********************************************************/
	function objectFromPath($path) 
	{

	  //sanity checking
	  if (!$path) return false;
	  if ($path=="/") return array("id"=>"0");

	  $sql = "SELECT dm_object.*,level1,level2 FROM docmgr.dm_object 
              		LEFT JOIN docmgr.dm_dirlevel ON dm_dirlevel.object_id=dm_object.id
									WHERE id=(SELECT docmgr.getobjfrompath('".$path."') AS objid);";
		return $this->DB->single($sql);

	}
	
	
	/*********************************************************
		FUNCTION:	objectPath
		PURPOSE:	returns an array of parent names of an object
							including the object itself
	*********************************************************/
	protected function objectPath($id) {

		if (!$id) $path = "/";
		else 
		{

			$sql = "SELECT docmgr.getobjpathname('".$id."','') AS objpath;";
			$info = $this->DB->single($sql);
			
			return $info["objpath"];

		}
		
		return $path;
	
	}

	/*********************************************************
		FUNCTION:	objectIdPath
		PURPOSE:	returns an array of parent ids of an object
							including the object itself
	*********************************************************/
	protected function objectIdPath($id) {

		if (!$id) $path = "0";
		else 
		{

			$sql = "SELECT docmgr.getobjpath('".$id."','') AS objpath;";
			$info = $this->DB->single($sql);
			
			return $info["objpath"];

		}
		
		return $path;
	
	}


	/*********************************************************
		FUNCTION:	getCurrentPath
		PURPOSE:	gets current path of the passed object in 
							relation to a parent we are currently
							browsing.
	*********************************************************/
	protected function getCurrentPath($id=null)
	{
	
		if (!$id) return $this->path;

		//get all paths to this object	
		$paths = $this->objectAllPaths($id);

		//default to the first one
		$ret = $paths[0];

		foreach ($paths AS $path)
		{

			//if it's in the path we are browsing, or there is no browse path, check it out
			if (strstr($path,$this->path) || !$this->path) 
			{

				//convert the path to ids and make sure we have access to all of them
				if (!PERM::check(ADMIN))
				{

					//we need to make sure we have at least view access to all members of one of the files hierarchies
					$idstr = $this->objectPathToId($path);

					//nothing found, bail
					if (!$idstr) continue;

					//convert to array and remove the root "0" folder
					$arr = explode(",",$idstr);
					array_shift($arr);

					//see how many of the folders in the hierarchy we can see				
					$sql = "SELECT DISTINCT object_id FROM docmgr.dm_view_perm WHERE object_id IN (".$idstr.") AND ".permString();
					$info = $this->DB->fetch($sql);

					//we can see all of them, good to go
					if ($info["count"]==count($arr))
					{
						$ret = $path;
						break;
					}
					//they don't.  show them nothing
          else
          {
            $ret = null;
          }
          
				}
				else
				{
					$ret = $path;
					break;
				}
				
			}
		
		}

		return $ret;	
	
	}

	/*********************************************************
		FUNCTION:	objectPathToId
		PURPOSE:	takes a name-based path and turns it into
							an id-based one
	*********************************************************/
	public function objectPathToId($path)
	{
    if (!$path) return false;
    	
		$sql = "SELECT docmgr.path_to_id('".sanitize($path)."') AS objpath;";
		$info = $this->DB->single($sql);

		return $info["objpath"];
	
	}

	
	/*********************************************************
		FUNCTION:	objectIdPath
		PURPOSE:	returns an array of parent ids of an object
							including the object itself
	*********************************************************/
	public function objectIdAllPaths($id=null,$list=null) 
	{

		if (!$id) $id = $this->objectId;

		if (!$id) $path = array("0");
		else 
		{

			//if not passed a list of stuff to query, do it ourselves
			if (!$list)
			{

				//get all related categories to this object
				$sql = "with recursive all_categories as (
									select * from docmgr.dm_view_parent where object_id='".$id."'
									union all
									select b.* from all_categories a, docmgr.dm_view_parent b where a.parent_id = b.object_id
								) select * from all_categories order by parent_id DESC;
								";
				$list = $this->DB->fetch($sql,1);

			}
				
			//get all paths to this object
			$ret =  $this->getObjIdPaths($id,$list);

			//algorithm isn't perfect, it returns dups, and the ids are backwards
			$path = array_values(array_unique($ret));

			for ($i=0;$i<count($path);$i++)
			{
				$arr = explode(",",$path[$i]);
				
				//another hack
				if (!in_array($id,$arr)) array_unshift($arr,$id);
				
				$path[$i] = implode(",",array_reverse($arr));

			}
			
		}
		
		return $path;
	
	}


	/*********************************************************
		FUNCTION:	objectAllPath
		PURPOSE:	returns an array of parent names of an object
							including the object itself
	*********************************************************/
	public function objectAllPaths($id=null) 
	{

		if (!$id) $id = $this->objectId;

		if (!$id) $path = array("/");
		else 
		{

			//get all related categories to this object.  we do this here instead of letting 
			//objectIdAllPaths do it, so we can reuse in the list for fetching object names
			$sql = "with recursive all_categories as (
									select * from docmgr.dm_view_parent where object_id='".$id."'
									union all
									select b.* from all_categories a, docmgr.dm_view_parent b where a.parent_id = b.object_id
								) select * from all_categories order by parent_id DESC;
								";
			$list = $this->DB->fetch($sql,1);

			//get our arrays of ids
			$idArr = $this->objectIdAllPaths($id,$list);
			$path = array();

			//now loop through and convert to pathnames
			foreach ($idArr AS $pathRow)
			{
			
				$arr = explode(",",$pathRow);
				$str = null;
				
				foreach ($arr AS $obj)
				{
				
					if ($obj!=0)
					{
						$key = array_search($obj,$list["object_id"]);					
						$str .= "/".$list["name"][$key];
					}

				}

				$path[] = $str;			
			
			}
				
		}
		
		return $path;
	
	}

	/*********************************************************
		FUNCTION:	getObjIdPaths
		PURPOSE:	loops through path results and links
							objects and parents together
	*********************************************************/
	protected function getObjIdPaths($id,$list)
	{

		$str = $id;
		$ret = array();

		if (count($list["object_id"]) > 0)
		{
		
			$keys = array_keys($list["object_id"],$id);
		
			foreach ($keys AS $key)
			{
		
				$pid = $list["parent_id"][$key];
		
				if ($pid==0) $ret[] = $str.",0";
				else
				{
					
					$arr = $this->getObjIdPaths($pid,$list);				

					foreach ($arr AS $e) $ret[] = $str.",".$e;
		
				}
			
			}

		}
			
		return $ret;

	}

	/*********************************************************
		FUNCTION: getChildObjects
		PURPOSE:returns all object children of the current object
	*********************************************************/ 
	function getChildObjects($objId=null)
	{
	 
		if (!$objId) $objId = $this->objectId;
	 
		$sql = "with recursive all_categories as (
							select * from docmgr.dm_object_parent where parent_id='".$objId."'
							union all
							select b.* from all_categories a, docmgr.dm_object_parent b where a.object_id = b.parent_id
						) select * from all_categories order by parent_id;
						";
		$list = $this->DB->fetch($sql,1);
	 
		return $list["object_id"];
	 
	}
	
	/*********************************************************
		FUNCTION: getChildCollections
		PURPOSE:returns all collection children of the current object
	*********************************************************/
	function getChildCollections($objId=null)
	{
	 
		if (!$objId) $objId = $this->objectId;
	 
		$sql = "with recursive all_categories as (
							select * from docmgr.dm_view_collections where parent_id='".$objId."'
							union all
							select b.* from all_categories a, docmgr.dm_view_collections b where a.id = b.parent_id
						) select * from all_categories order by parent_id;
						";
		$list = $this->DB->fetch($sql,1);
	 
		return $list["object_id"];
	 
	}

	/*********************************************************
		FUNCTION: getCollections
		PURPOSE:returns all collection children of the current object
	*********************************************************/
	function getCollections($objArr)
	{
	 
		$sql = "with recursive all_categories as (
							select * from docmgr.dm_view_collections where parent_id IN (".implode(",",$objArr).")
							union all
							select b.* from all_categories a, docmgr.dm_view_collections b where a.id = b.parent_id
						) select * from all_categories order by parent_id;
						";
		$list = $this->DB->fetch($sql);
		
		return $list;
	 
	}
	
	/*******************************************************************
		FUNCTION: setObjectPath
		PURPOSE:  some objects reside in hidden folders, like
							the .objectXXX_pages folders.  This returns a path
							that doesn't include those folders, along w/ corresponding
							object ids matched to each object in the path
	*******************************************************************/
	protected function setObjectPath()
	{

		$p = $this->getCurrentPath($this->objectId);
	
		//set the globals 
		$this->objectInfo["object_path"] = $p;
		$this->objectInfo["objectid_path"] = $this->objectPathToId($p);
		$this->objectInfo["display_path"] = $p;
		
	}

  /*********************************************************************
    FUNCTION:	showCommon
    PURPOSE:	outputs common data about the current object
    					on the reply to the client
  *********************************************************************/
	function showCommon()
	{

		if ($this->objectId==null || is_array($this->objectId)) return false;

		$this->PROTO->add("current_object_id",$this->objectId);
		$this->PROTO->add("current_object_path",$this->path);
		$this->PROTO->add("current_object_name",$this->objectInfo["name"]);
		$this->PROTO->add("current_object_allpaths",$this->objectAllPaths());

	}

  private function checkUserSetup()
  {

    if ($_SESSION["api"]["docmgr_setup_checked"]==1) return false;

    $_SESSION["api"]["docmgr_setup_checked"] = 1;

    //check Users folder and home folder setup
    $c = new DOCMGR_COLLECTION();
    $c->checkSetup();
    
    if ($c->getError())
    {
      $this->throwError($c->getError());
      return false;
    }

    //check bookmark setup
    $b = new DOCMGR_BOOKMARK();
    $b->checkSetup();

    if ($b->getError())
    {
      $this->throwError($b->getError());
      return false;
    }

    //toss any output from our setup calls
    $this->PROTO->clearData();
    
  }

}

