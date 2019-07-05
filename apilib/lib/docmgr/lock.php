<?php

/****************************************************************************
	CLASS:	LOCK
	PURPOSE:	master function for managing docmgr objects.  this includes
				    creating, saving, update, moving, or deleting
****************************************************************************/

class DOCMGR_LOCK extends DOCMGR 
{

	/************************************************************
		FUNCTION:	get
		PURPOSE:	pulls all current lock info for a file
		INPUTS:		internal -> if set, returns an array of info
													instead of outputting it
		RETURNS:	array -> array of db lock info
		OUTPUTS:	array["lock"] -> array of db lock info
	************************************************************/
	public function get()
	{

		$l = new DOCMGR_UTIL_LOCK($this->objectId);
		$list = $l->get($this->objectId,$this->apidata["child_locks"]);

		if ($list["count"] > 0)
		{

			unset($list["count"]);
			$this->PROTO->add("lock",$list);			

			return $list;		

		}
		else return array();

	}

	/************************************************************
		FUNCTION:	set
		PURPOSE:	locks an object so it can only be edited
							by the locking user
		INPUTS:		apidata or data
								timeout -> lock timeout
								scope -> 1 or 2 (shared or exclusive)
								depth -> -1 or 0 (indirect or direct)
								uri -> path of file to lock
								owner -> owner of lock creation
								token -> lock token
		RETURNS:	none
		OUTPUTS:	none
	************************************************************/
	public function set()
	{

		//must have at least edit permissions
		if (!$this->permCheck("edit"))
		{
			$this->throwError(_I18N_OBJECT_EDIT_ERROR);
			return false;
		}

		$LOCK = new DOCMGR_UTIL_LOCK($this->objectId);
		$LOCK->set($this->apidata);
		
		if ($LOCK->error()) $this->throwError($LOCK->error());
		
	}


	/************************************************************
		FUNCTION:	clear
		PURPOSE:	unlocks an object so it can be edited by anyone
		INPUTS:		apidata or data
								token -> lock token
		RETURNS:	none
		OUTPUTS:	none
	************************************************************/
	function clear()
	{

		//must have at least edit permissions
		if (!$this->permCheck("edit"))
		{
			$this->throwError(_I18N_OBJECT_EDIT_ERROR);
			return false;
		}

		$LOCK = new DOCMGR_UTIL_LOCK($this->objectId);
		$LOCK->clear($this->apidata["token"]);
		
		if ($LOCK->error()) $this->throwError($LOCK->error());

	}

	public function validate()
	{

		$LOCK = new DOCMGR_UTIL_LOCK($this->objectId);
		$LOCK->validate($this->apidata["token"]);
		
		if ($LOCK->error()) $this->throwError($LOCK->error());
	
	}
	

	/************************************************************
		FUNCTION: clearAll
		PURPOSE:	clears all locks on an object
		INPUTS:		none
		RETURNS:	none
		OUTPUTS:	none
	************************************************************/
	function clearAll()
	{

		//must be an object admin
		if (!$this->permCheck("admin"))
		{
			$this->throwError(_I18N_OBJECT_EDIT_ERROR);
			return false;
		}

		$LOCK = new DOCMGR_UTIL_LOCK($this->objectId);
		$LOCK->clearAll();
		
		if ($LOCK->error()) $this->throwError($LOCK->error());

	}

}
