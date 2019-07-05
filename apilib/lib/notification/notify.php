<?php

/**
	manages and fetches subscription information for the current object and account
	*/
	
class NOTIFICATION_NOTIFY extends NOTIFICATION
{

	/**
		still playing around with this implementation.  Here's an alternate method to post a notification
		that simply requires the type and account
		*/
	public function post($notifType,$accountId,$link=null,$message = null)
	{
	
	  //convert to the id if necessary
	  if (is_numeric($notifType)) $notifOption = $notifType;
	  else $notifOption = constant($notifType);
	
	  //go ahead and insert the notification
	  $opt = null;
	  $opt["option_id"] = $notifOption;
	  $opt["account_id"] = $accountId;
	  $opt["record_id"] = "0";

	  if ($link) $opt["link"] = $link;
	  if ($message) $opt["message"] = sanitize($message);

	  $notifId = $this->DB->insert("notification.notifications",$opt,"id");

		//see if this account wants email notifications
		$sql = "SELECT email_notifications FROM auth.account_config WHERE account_id='".$accountId."'";
		$info = $this->DB->single($sql);
		
		//they do, build an email message and send it off
		if ($info["email_notifications"]=="t")
		{
			$this->email($notifId);
		}
		
	
	}

	/**
		inserts a notification into the database, and emails if configured to do so
		*/
	public function send($notifType,$accountId,$recordId,$recordName,$link,$message = null,$attach = null)
	{

	  //convert to the id if necessary
	  if (is_numeric($notifType)) $notifOption = $notifType;
	  else $notifOption = constant($notifType);
	
	  //get all info for this notification type
	  $sql = "SELECT * FROM notification.options WHERE id='".$notifOption."'";
	  $option = $this->DB->single($sql);

	  //get the name of the record we are dealing with for storage
	  if (!$recordName) $recordName = $this->getRecordName($recordId,$option["record_type"]);

	  //go ahead and insert the notification
	  $opt = null;
	  $opt["record_id"] = $recordId;
	  $opt["record_name"] = sanitize($recordName);
	  $opt["option_id"] = $notifOption;
	  $opt["account_id"] = $accountId;
	  $opt["link"] = $link;
	  $opt["message"] = sanitize($message);

	  $notifId = $this->DB->insert("notification.notifications",$opt,"id");

		//see if this account wants email notifications
		$sql = "SELECT email_notifications FROM auth.account_config WHERE account_id='".$accountId."'";
		$info = $this->DB->single($sql);
		
		//they do, build an email message and send it off
		if ($info["email_notifications"]=="t")
		{
			$this->email($notifId);
		}
		
	}

	/**
		emails the notification to the passed acount.  If an attachment is passed,
		that is sent as well
		*/
	private function email($notifId)
	{

		//I know it's one more query, but did it this way so I don't have to pass 50 arguments anymore
		$sql = "SELECT * FROM notification.view_notifications WHERE id='".$notifId."'";
		$info = $this->DB->single($sql);

		//get the user's email address
		$a = new ACCOUNT();
		$aInfo = $a->get($info["account_id"]);

		//nothing to do		
		if (!$aInfo["email"]) return false;

		//get our translation of what happened
		$el = "_I18N_".$info["define_name"];
	
		//convert to a description and id of our notification type
		$notifString = constant($el);
		$notifOption = constant($info["define_name"]);
	
		//setup the message subject		    
		$subject = _I18N_EVENT_NOTIFICATION." \"".$info["record_name"]."\"";	
		
		//if no message, create a default one
		if ($info["message"]) $message = $info["message"];
		else
		{
		
		  //build our email message
		  $message = _I18N_EVENT_OCCURRED.": \"".$info["record_name"]."\"<br><br><b>".$notifString."</b>\n";
	
			$message .= "<br>"._I18N_CLICK_TO_VIEW;
			$message .= "<br><br><a href=\"".$info["link"]."\">".$info["link"]."</a>\n";
		
    }

    $attach = null;
    
		if ($info["attach"])
		{

			$attach = array();

			//assemble our attachment array
			$attach[0]["name"] = $info["record_name"];
			$attach[0]["path"] = $info["attach"];
		
		}

		//fire off the email
		send_email($aInfo["email"],ADMIN_EMAIL,$subject,$message,$attach);
	
	}

	/**
		DocMGR-specific.  Notifies all subscribers of an object of the desired notification
		*/
	public function notifySubscribers($objectId,$notifType,$parent = null)
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

			$a = new ACCOUNT($list[$i]["account_id"]);
			$aInfo = $a->get();

			//if the user wants the attachment, send the file with the attachment
			if ($list[$i]["notify_send_file"]=="t" && $oInfo["object_type"]=="file" && 
				($notifOption==OBJ_LOCK_NOTIFICATION || $notifOption==OBJ_CREATE_NOTIFICATION)) 
			{
				
				$sql = "SELECT id FROM docmgr.dm_file_history WHERE object_id='$objectId' AND version='".$oInfo["version"]."'";
				$fileinfo = $this->DB->single($sql);
				    
				$filePath = DATA_DIR."/".returnObjPath($objectId)."/".$fileinfo["id"].".docmgr";
		
				//assemble our attachment array
				$attach[0]["name"] = $oInfo["name"];
				$attach[0]["path"] = $filePath;
		                         			
			} 
			else
			{
				$attach = null;
			}
			
			//insert the notification record
			$this->notify($notifType,$objectId,$list[$i]["account_id"],$objName,$attach);
	
		}
	
		return true;
	
	}

}


	
	