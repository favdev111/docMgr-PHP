<?php

/**********************************************************************
	CLASS:	DOCUMENT
	PURPOSE:	handles specific processing for document objects
**********************************************************************/
class DOCMGR_DOCUMENT extends DOCMGR_AOBJECT 
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

		$realname = $this->objectInfo["name"];
		$version = $this->objectInfo["version"];
		
		if ($this->apidata["document_id"]) $documentId = $this->apidata["document_id"];
		else 
		{
		
			$sql = "SELECT id FROM docmgr.dm_document WHERE object_id='".$this->objectId."' AND version='$version'";
			$info = $this->DB->single($sql);
			$documentId = $info["id"];

		}

		// get the filename
		$filename = DOC_DIR."/".$this->getObjectDir()."/".$documentId.".docmgr";

		//lock if necessary
		if ($this->apidata["lock"] && $this->permCheck("edit")) 
		{

			$l = new DOCMGR_UTIL_LOCK($this->objectId);
			
			//lock with standard timeout
			if (!$l->isLocked()) $l->set();

		}
	
		//log the view
		logEvent(OBJ_VIEWED,$this->objectId);

		//if direct, output directly to the browser, otherwise return the content
		if ($this->apidata["direct"]) 
		{

			// send headers to browser to initiate file download
			header ("Content-Type: text/html");
			header ("Content-Type: application/force-download");
			header ("Content-Length: ".filesize($filename));
			header ("Content-Disposition: attachment; filename=\"".$realname.".html\"");
			header ("Content-Transfer-Encoding:binary");
			header ("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header ("Pragma: public");

			//get the contents
			$xhtml = formatEditorStr(file_get_contents($filename));

			//readfile_chunked($filename);
			die($xhtml);
			
		} 
		else if ($this->apidata["stream"])
		{
			
			$stream = fopen($filename,"r");

			$this->PROTO->add("stream",$stream);
			return $stream;

		} 
		else 
		{

			if (file_exists($filename))
			{

				//get the contents
				$xhtml = formatEditorStr(file_get_contents($filename));

				//are we locked
				$l = new DOCMGR_UTIL_LOCK($this->objectId);
				if ($l->isLocked())
				{
					$this->PROTO->add("locked","t");
				}
			
			} else $xhtml = null;
		
			//add our user's permissions
			$this->PROTO->add("bitmask_text",$this->objectInfo["bitmask_text"]);	

			//return our data in a <content> tag	
			$this->PROTO->add("content",$xhtml);

			//return the content for internal functions
			return $xhtml;
						
		}	

	}

	public function getHistory() 
	{

		$sql = "SELECT * FROM docmgr.dm_document WHERE object_id='".$this->objectId."' ORDER BY version DESC";	
		$list = $this->DB->fetch($sql);

		// get the filename
		$filedir = DOC_DIR."/".$this->getObjectDir();
		
		//convert to data and return
		for ($i=0;$i<$list["count"];$i++) 
		{

			$info = returnAccountInfo($list[$i]["object_owner"]);
			$name = $info["first_name"]." ".$info["last_name"];

			//add formated modified info
			$list[$i]["view_modified_date"] = dateView($list[$i]["modify"]);
			$list[$i]["view_modified_by"] = $name;
			$list[$i]["size"] = displayFileSize(filesize($filedir."/".$list[$i]["id"].".docmgr"));

			//add to the output stream
			$this->PROTO->add("history",$list[$i]);
		
		}
	
	}
	
	/*****************************************************************************************
  		FUNCTION: save
  		PURPOSE:  saves the content for the document
  		INPUT:		editor_content -> html content of the document
  						path -> full path including document name to save (only required for new document)
	*****************************************************************************************/
	public function save() 
	{

		//make sure it's saved as a document
		$this->apidata["object_type"] = "document";

		$o = new DOCMGR_OBJECT($this->apidata);
		$objId = $o->save();

		//toss and error if we have one
		$err = $o->getError();
    if ($err) $this->throwError($err[0],$err[1]);

    return $objId;

	}

	protected function update($data)
	{

		//if not passed editor content, we're not saving the file itself
		if (!$data["editor_content"]) return false;

		$o = new DOCMGR_OBJECT($this->objectId);
		$o->tempToStorage();

		//get the next version
		$data["version"] = $this->objectInfo["version"]+1;

		//make sure it's a properly formated html document
		$data["editor_content"] = cleanupEditorStr($data["editor_content"]);

		//pass it off to the content function for file writing
		$this->saveContent($data);
                                                            
		//handle some updating for non-new documents (handled by object->create method 
		//if it was a new document)
		if ($data["version"]>1) 
		{

			//if there is a file revision limit, delete the previous file if necessary
			$this->removeRevision("earliest",1);

		}

	}

	/***************************************************************************
		FUNCTION:	saveContent
		PURPOSE:	workhorse for saving actual document content
	***************************************************************************/
	protected function saveContent($data)
	{

		$content = $data["editor_content"];
		$version = $data["version"];
	
		//make sure we can write to the data directory
		if (!is_writable(DOC_DIR)) 
		{
			define("ERROR_MESSAGE","Documents directory is not writable");
			return false;
		}

		$documentPath = DOC_DIR."/".$this->getObjectDir();

		//create our document directory if it doesn't exist
		recurMkDir($documentPath);

		//this is a new document entry, so we will get a new unique id for it and store it in the filesystem
		$opt = null;
		$opt["object_id"] = $this->objectId;
		$opt["version"] = $version;
		$opt["modify"] = date("Y-m-d h:i:s");
		$opt["object_owner"] = USER_ID;
		$opt["notes"] = $data["revision_notes"];
		$documentId = $this->DB->insert("docmgr.dm_document",$opt,"id");

		if (!$documentId) 
		{
			$this->throwError(_I18N_DOCRECORDCREATE_ERROR);
			return false;
		}
		
		$file = $documentPath."/".$documentId.".docmgr";

		if ($fp = fopen($file,"w")) 
		{

			fwrite($fp,$content);
			fclose($fp);	

			//update the filesize for this object
			$size = strlen($content);
			$opt = null;
			$opt["size"] = $size;
			$opt["last_modified"] = date("Y-m-d H:i:s");
			$opt["modified_by"] = USER_ID;
			$opt["version"] = $version;
			$opt["where"] = "id='".$this->objectId."'";
			$this->DB->update("docmgr.dm_object",$opt);

			return true;	

		} 
		else 
		{
			$this->throwError(_I18N_DOCFILEOPEN_ERROR);
			return false;		
		}
		
	}


	/*****************************************************************************************
  		FUNCTION: delete
  		PURPOSE:  additional processing function, called from DOCMGR_OBJECT class.  handles doc specific
							processing when deleting the object
	*****************************************************************************************/
	protected function remove() 
	{

		//get the ids of our documents belonging to this object
		$sql = "SELECT id FROM docmgr.dm_document WHERE object_id='".$this->objectId."'";
		$info = $this->DB->fetch($sql,1);

		$objPath = $this->getObjectDir($this->objectId);

		//delete them
		$sql = "DELETE FROM docmgr.dm_document WHERE object_id='".$this->objectId."'";
		if ($this->DB->query($sql))
		 {

			//delete any physical files associated with our revisions
			if ($info["id"]) 
			{
				foreach ($info["id"] AS $id) @unlink(DOC_DIR."/".$objPath."/".$id.".docmgr");
			}

			return true;

		}
		else return false;
	
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

		//get the current version so we can increment
		$sql = "SELECT version FROM docmgr.dm_object WHERE id='".$this->objectId."'";
		$info = $this->DB->single($sql);

		//find the id of our most recent entry
		$sql = "SELECT id FROM docmgr.dm_document WHERE object_id='".$this->objectId."' AND version='".$info["version"]."' ORDER BY version DESC LIMIT 1";
		$winfo = $this->DB->single($sql);
		
		$file = DOC_DIR."/".$this->getObjectDir()."/".$winfo["id"].".docmgr";

		//extract the contents of our file
		if (file_exists($file)) return DOCMGR_OBJINDEX::removeTags(file_get_contents($file));
	
	}
	
	/*****************************************************************************************
  		FUNCTION: thumb
  		PURPOSE:  called from THUMB class.  Creates thumbnail for document
	*****************************************************************************************/
	protected function thumb() 
	{
	
		//make sure thumbnail support is enabled.Also make sure imagemagick is enabled
		if (!defined("THUMB_SUPPORT")) return false;

		//find the id of our most recent entry
		$sql = "SELECT id FROM docmgr.dm_document WHERE object_id='".$this->objectId."' ORDER BY version DESC LIMIT 1";
		$info = $this->DB->single($sql);

		$dirPath = $this->getObjectDir();
		$filename = "file.html";
		$filepath = DOC_DIR."/".$dirPath."/".$info["id"].".docmgr";

		recurMkdir(THUMB_DIR."/".$dirPath);

		$thumb = THUMB_DIR."/".$dirPath."/".$this->objectId.".png";

		$finalThumb = str_replace(".png",".docmgr",$thumb);

		$str = $filepath."\n".$filename."\n".$thumb."\n";
		
		//init our thumbnail creator in thumb node
		$t = new DOCMGR_UTIL_FILETHUMB("thumb",$filepath,$filename,$thumb);

		//rename the file to a docmgr extension for security
		if (file_exists($thumb)) rename($thumb,$finalThumb);
		
	}
	
	/***************************************************************************
		common functions for this class
	***************************************************************************/

	/***************************************************************
		remove a revision for a document.  if file_id = earliest,
		remove the earliest available version.  Otherwise
		remove the passed id
	***************************************************************/
	public function removeRevision($docArr=null,$bypassPerm = null) 
	{
	
		if (!$docArr) $docArr = $this->apidata["document_id"];

		if (!is_array($docArr)) $docArr = array($docArr);

		//make sure we have perms to edit this thing. bypass only happens when auto called
		//from a document save
		if (!$bypassPerm && !$this->permCheck("admin"))
		{
			$this->throwError(_I18N_OBJECT_MANAGE_ERROR);
			return false;
		}

		foreach ($docArr AS $docId)
		{
		
			//remove our earliest revision to keep the limit where it should be
			if ($docId=="earliest") 
			{
		
				//config check
				if (!defined("DOC_REVISION_LIMIT") || DOC_REVISION_LIMIT=="0") continue;
		
				$sql = "SELECT id FROM docmgr.dm_document WHERE object_id='".$this->objectId."' ORDER BY id";	
				$info = $this->DB->fetch($sql,1);
		
				if ($info["count"] > DOC_REVISION_LIMIT) {
		
					//delete all entries that are less then our current count
					$diff = $info["count"] - DOC_REVISION_LIMIT;
		
					for ($i=0;$i<$diff;$i++) {
					  
						$docId = $info["id"][$i];
					
						$sql = "DELETE FROM docmgr.dm_document WHERE id='$docId'";
						if ($this->DB->query($sql)) {
						
							$file = DOC_DIR."/".$this->getObjectDir()."/".$docId.".docmgr";
							@unlink($file);
	
						}
						
					}
		
				}      		
		
			//this portion deletes the specified revision as determined by the docId	
			} 
			else 
			{
		
				//config check
				if (!defined("DOC_REVISION_REMOVE") || DOC_REVISION_REMOVE=="no") continue;
								
				//get the latest version of this file
				$sql = "SELECT version FROM docmgr.dm_document WHERE id='".$docId."'";
				$hInfo = $this->DB->single($sql);
		
				//get the current version
				$sql = "SELECT version FROM docmgr.dm_object WHERE id='".$this->objectId."'";
				$oInfo = $this->DB->single($sql);
		
				$sql = "DELETE FROM docmgr.dm_document WHERE id='".$docId."'";
				if ($this->DB->query($sql)) {
		
			 		$file = DOC_DIR."/".$this->getObjectDir()."/".$docId.".docmgr";
			 		@unlink($file);
		
			 		//if the file was the latest revision, promote the next in line
			 		if ($hInfo["version"]==$oInfo["version"]) {
		
						//get the next latest version of this file
						$sql = "SELECT id FROM docmgr.dm_document WHERE object_id='".$this->objectId."' ORDER BY version DESC LIMIT 1";
						$info = $this->DB->single($sql);
		
						//promote our file, then reindex and rethumb
						$this->promote($info["id"]);
						$i = new DOCMGR_OBJINDEX($this->objectId);
						$i->run(USER_ID,null);
		
					} 
		
				} 
		
			}

		}	

		//since it's possible we removed teh latest one, reset version in the object file
		$sql = "UPDATE docmgr.dm_object SET version=(SELECT max(version) FROM docmgr.dm_document WHERE object_id='".$this->objectId."')
                                                WHERE id='".$this->objectId."'";	
		$this->DB->query($sql);
                                                    
	
	}
	
	//promotes a document to the latest revision for the object
	public function promote($documentId=null) 
	{
	
		if (!$documentId) $documentId = $this->apidata["document_id"];

		//make sure we have perms to edit this thing. 
		if (!$this->permCheck("admin"))
		{
			$this->throwError(_I18N_OBJECT_MANAGE_ERROR);
			return false;
		}
	
		/* with this, we will pretty much have to put the ids in an array, and alter them accordingly */
		$sql = "SELECT * FROM docmgr.dm_document WHERE object_id='".$this->objectId."' ORDER BY version DESC";
		$info = $this->DB->fetch($sql,1);
	
		$id_array = &$info["id"];
		$version_array = &$info["version"];
	
		/* figure out which number in the array our orignal belongs to */
		$orig_num=array_search($documentId,$id_array);
	
		/* set the new file to the highest version number */
		$sql = null;
	
		//create a new array with the swapped orders
		$temp = array($documentId);
		foreach ($info["id"] AS $id) if ($id!=$documentId) $temp[] = $id;
		$num = count($temp);
	
		for ($row=0;$row<$num;$row++) 
		{
	
			//a hack to fix the promote errors some people are getting
			if (!$temp[$row]) continue;
	
			$version = $num - $row;
			$sql .= "UPDATE docmgr.dm_document SET version='$version' WHERE id='".$temp[$row]."';";
	
		}
	
		//update the database to use the highest one if there is a name field in the database
		$sql .= "UPDATE docmgr.dm_object SET version='".max($version_array)."' WHERE id='".$this->objectId."';";
		
		//run our query
		if ($this->DB->query($sql)) 
		{
			logEvent(OBJ_VERSION_PROMOTE,$this->objectId);
			return true;
		}
		else return false;
	
	}

	
	/***********************************************************************
		Displaying:
		This function returns the link and the icon to be displayed
		in the finder in list view
		return $arr("link" => $link, "icon" => $icon);
	***********************************************************************/
	protected function listDisplay($info) 
	{
	
		$arr["icon"] = THEME_PATH."/images/fileicons/document.png";
		$arr["link"] = "index.php?module=docview&objectId=".$info["id"];
		return $arr;
	
	}
	
}
