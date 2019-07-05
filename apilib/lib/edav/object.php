<?php

/****************************************************************************
	CLASS:	OBJECT
	PURPOSE:	master function for managing docmgr objects.  this includes
				    creating, saving, update, moving, or deleting
****************************************************************************/

class EDAV_OBJECT extends EDAV
{

	public function get()
	{
		$opt = array("object_id"=>$this->objectId);
		$opt["stream"] = 1;

		if ($this->objectInfo["object_type"]=="document")
		{
			$d = new DOCMGR_DOCUMENT($opt);
			return $d->get();
		}
		else
		{
			$d = new DOCMGR_FILE($opt);
			return $d->get();
		}
	}	

	public function save()
	{

		unset($this->apidata["path"]);
		$this->apidata["object_id"] = $this->objectId;

		$d = new DOCMGR_OBJECT($this->apidata);
		$d->save();

	}

	
	public function put()
	{

		//if in root, we are not in a real collection, so bail
		if ($this->path=="/")
		{
			$this->throwError(_I18N_PERMISSION_DENIED);
			return false;
		}
	
		//making a new file
		if ($this->objectInfo["object_type"]=="collection")
		{
			$opt = null;
			$opt["parent_id"] = $this->objectId;
			$opt["filepath"] = $this->apidata["filepath"];
			$opt["name"] = $this->apidata["name"];
			
			//make it hidden if it starts with a "."
			if ($opt["name"][0]==".") $opt["hidden"] = "t";

			$d = new DOCMGR_FILE($opt);
			$d->save();
			
			if ($d->getError()) $this->throwError($d->getError());
			            		
		}
		else
		{
			$opt = null;
			$opt["object_id"] = $this->objectId;
			$opt["filepath"] = $this->apidata["filepath"];
			$opt["name"] = $this->apidata["name"];
			$opt["token"] = $this->apidata["token"];
			
			//make it hidden if it starts with a "."
			if ($opt["name"][0]==".") $opt["hidden"] = "t";

			$d = new DOCMGR_FILE($opt);
			$d->save();
			
			if ($d->getError()) $this->throwError($d->getError());
		
		}

	}

	public function move()
	{

		//if in root, we are not in a real collection, so bail
		if ($this->path=="/")
		{
			$this->throwError(_I18N_PERMISSION_DENIED);
			return false;
		}

		unset($this->apidata["path"]);
		$this->apidata["object_id"] = $this->objectId;

		$d = new DOCMGR_OBJECT($this->apidata);
		$d->move();

		if ($d->getError()) $this->throwError($d->getError());
		
	}

	public function delete()
	{

		//if in root, we are not in a real collection, so bail
		if ($this->path=="/")
		{
			$this->throwError(_I18N_PERMISSION_DENIED);
			return false;
		}

		$opt = array("object_id"=>$this->objectId);
		$d = new DOCMGR_OBJECT($opt);
		$d->delete();

		if ($d->getError()) $this->throwError($d->getError());

	}	

	public function mkdir()
	{

		//if in root, we are not in a real collection, so bail
		if ($this->path=="/")
		{
			$this->throwError(_I18N_PERMISSION_DENIED);
			return false;
		}

		//make the new collection
		$opt = null;
		$opt["parent_id"] = $this->objectId;
		$opt["object_type"] = "collection";
		$opt["name"] = $this->apidata["name"];
		
		$d = new DOCMGR_OBJECT($opt);
		$d->save();
		
		if ($d->getError()) $this->throwError($d->getError());
			            		

	}

	public function getProperties()
	{
		$d = new DOCMGR_OBJECT($this->objectId);
		$propData = $d->getProperties();
            
	}
	
	public function saveProperties()
	{
		unset($this->apidata["path"]);
		$this->apidata["object_id"] = $this->objectId;

		$d = new DOCMGR_OBJECT($this->objectId);
		$d->saveProperties();

	}
	

	public function getLocks()
	{
		$d = new DOCMGR_LOCK($this->objectId);
		return $d->get();
	}

	public function lock()
	{
		unset($this->apidata["path"]);
		$this->apidata["object_id"] = $this->objectId;
		
		$d = new DOCMGR_LOCK($this->apidata);
		$d->set();

		if ($d->getError()) $this->throwError($d->getError());

	}
	
	public function unlock()
	{

		unset($this->apidata["path"]);
		$this->apidata["object_id"] = $this->objectId;

		$d = new DOCMGR_LOCK($this->apidata);
		$d->clear();

		if ($d->getError()) $this->throwError($d->getError());

	}

	public function setName()
	{
		unset($this->apidata["path"]);
		$this->apidata["object_id"] = $this->objectId;

		$d = new DOCMGR_OBJECT($this->apidata);
		$d->save();
	                                    
	}
	
	
}
