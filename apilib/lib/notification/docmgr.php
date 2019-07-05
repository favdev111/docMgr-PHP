<?php

/**
	manages and fetches subscription information for the current object and account
	*/
	
class NOTIFICATION_DOCMGR extends NOTIFICATION
{

	/**
		DocMGR-specific.  Notifies all subscribers of an object of the desired notification
		*/
	public function send($objectId,$notifType,$parent = null)
	{

		$notifOption = constant($notifType);
	
	  $sql = "SELECT subscription_field FROM notification.options WHERE id='".$notifOption."'";
	  $info = $this->DB->single($sql);
		$notifField = $info["subscription_field"];

		//bail if not set
		if (!$notifField) return false;

	  //get all names
	  $sql = "SELECT id,name,parent_id FROM docmgr.dm_view_objects WHERE id='$objectId' OR object_type='collection'";
	  $catInfo = $this->DB->fetch($sql,1);
	
	  //get our array of category owners
	  $objArr = array_reverse(returnCatOwner($catInfo,$objectId,null));
			        
		//get all users that are subscribed to this file and arent the current user
		$sql = "SELECT * FROM docmgr.subscriptions WHERE object_id IN (".implode(",",$objArr).") AND ".$notifField."='t'"; // AND account_id!='".USER_ID."';";
		$list = $this->DB->fetch($sql);
	
		//get out if there's no subscribers to this object and event
		if (!$list["count"]) return false;
	
		//get the object's information
		$sql = "SELECT name,object_type,version FROM docmgr.dm_object WHERE id='$objectId';";
		$oInfo = $this->DB->single($sql);
	
		$sql = "SELECT id FROM docmgr.dm_file_history WHERE object_id='$objectId' ORDER BY version DESC LIMIT 1";
		$fileinfo = $this->DB->single($sql);
	
		//if a collection is passed, we need to recognize this
		if ($parent) 
		{
	    $sql = "SELECT name FROM docmgr.dm_object WHERE id='$parent'";
	    $pInfo = $this->DB->single($sql);
	    $objName = $pInfo["name"];
	  } 
	  else 
	  {
	  	$objName = $oInfo["name"];
		}

		//send an email to each account on the notification list
		for ($i=0;$i<$list["count"];$i++) 
		{

			//init for later
			$message = null;
			$attach = null;

			$a = new ACCOUNT($list[$i]["account_id"]);
			$aInfo = $a->get();
			
			//if the user wants the attachment, send the file with the attachment
			if ($list[$i]["notify_send_file"]=="t" && $oInfo["object_type"]=="file" && 
				($notifOption==OBJ_LOCK_NOTIFICATION || $notifOption==OBJ_CREATE_NOTIFICATION)) 
			{
				
				$sql = "SELECT id FROM docmgr.dm_file_history WHERE object_id='$objectId' AND version='".$oInfo["version"]."'";
				$fileinfo = $this->DB->single($sql);
				    
				$attach = DATA_DIR."/".returnObjPath($objectId)."/".$fileinfo["id"].".docmgr";
		
			} 

			//setup a message for removal.  otherwise the default message is fine
			if ($notifOption==OBJ_REMOVAL_NOTIFICATION)
			{

				$notifString = constant("_I18N_".$notifType);

				//build our email message
        $message = _I18N_EVENT_OCCURRED.": \"".$objName."\"<br><br><b>".$notifString."</b>\n";			

			}

			$link = "index.php?module=docmgr&objectId=".$objectId;
			
			//insert the notification record
			$n = new NOTIFICATION_NOTIFY();
			$n->send($notifType,$list[$i]["account_id"],$objectId,$objName,$link,$message,$attach);
	
		}
	
		return true;
	
	}

}


	
	