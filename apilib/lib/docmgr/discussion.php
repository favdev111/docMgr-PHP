<?php

/**********************************************************************
	CLASS:	DISCUSSION
	PURPOSE:	handles specific processing for document objects
**********************************************************************/
class DOCMGR_DISCUSSION extends DOCMGR 
{

	public function search() 
	{

		$sql = "SELECT dm_discussion.* FROM docmgr.dm_discussion WHERE object_id='".$this->objectId."'";

		if ($htis->apidata["owner"] != "0") 
			$sql .= " AND (owner='".$this->apidata["owner"]."' OR id='".$this->apidata["owner"]."')";
		else 
			$sql .= " AND owner='0'";

		$sql .= " ORDER BY time_stamp DESC";

		$list = $this->DB->fetch($sql);

		if ($this->apidata["owner"])
		{

			$sql = "SELECT header FROM docmgr.dm_discussion WHERE id='".$this->apidata["owner"]."'";
			$info = $this->DB->single($sql);

			$this->PROTO->add("thread_name",$info["header"]);

		}
		
		for ($i=0;$i<$list["count"];$i++) 
		{

			$sql = "SELECT max(time_stamp) FROM docmgr.dm_discussion WHERE owner='".$list[$i]["id"]."'";
			$info = $this->DB->single($sql);
		
			$list[$i]["time_stamp_view"] = dateView($list[$i]["time_stamp"]);
			$list[$i]["reply_time_stamp"] = $info["max"];
			$list[$i]["reply_time_stamp_view"] = dateView($info["max"]);
			$list[$i]["account_name"] = returnAccountName($list[$i]["account_id"]);

			$this->PROTO->add("record",$list[$i]);
		
		}
	
	}

  /********************************************************************
  	FUNCTION: replyTopic
  	PURPOSE:  post reply to topic
	********************************************************************/
	public function save() 
	{
	
		$opt = null;

		if ($this->apidata["record_id"])
		{

			if (!$this->checkPermissions())
			{
				$this->throwError(_I18N_TOPIC_EDITPERM_ERROR);
			}

			$recordId = $this->apidata["record_id"];
			$opt["content"] = $this->apidata["editor_content"];
			$opt["where"] = "id='".$this->apidata["record_id"]."'";
			$this->DB->update("docmgr.dm_discussion",$opt);

		}
		else
		{

			$opt["object_id"] = $this->objectId;
			$opt["header"] = $this->apidata["message_subject"];
			$opt["account_id"] = USER_ID;
			$opt["content"] = $this->apidata["editor_content"];
			$opt["owner"] = $this->apidata["owner"];
			$opt["time_stamp"] = date("Y-m-d H:i:s");

			$recordId = $this->DB->insert("docmgr.dm_discussion",$opt,"id");
		
		}

		//check for errors			
		if ($this->DB->error())
		{
			$this->throwError($this->DB->error());
		}
		else
		{
			$n = new NOTIFICATION_DOCMGR();
			$n->send($this->objectId,"OBJ_COMMENT_POST_NOTIFICATION");
			$this->PROTO->add("record_id",$recordId);
		}		
		
	}

  /********************************************************************
  	FUNCTION: deleteTopic
  	PURPOSE:  delete the current topic
	********************************************************************/
	public function delete() 
	{

		if (!$this->checkPermissions())
		{
			$this->throwError(_I18N_TOPIC_DELETEPERM_ERROR);
		}
		else
		{

			//delete topic and all its children.  	
			$sql = "DELETE FROM docmgr.dm_discussion WHERE id='".$this->apidata["record_id"]."' OR owner='".$this->apidata["record_id"]."'";
			$this->DB->query($sql);
		
			$err = $this->DB->error();
			if ($err) $this->throwError($err);

		}
			
	}

	protected function checkPermissions()
	{

		$check = true;
	
		//make sure we own this topic that we are editing
		if (!PERM::check(ADMIN) && $this->apidata["record_id"])
		{
		
			$sql = "SELECT id FROM docmgr.dm_discussion WHERE id='".$this->apidata["record_id"]."' AND account_id='".USER_ID."'";
			$info = $this->DB->single($sql);
			
			if (!$info) $check = false;
		
		}
	
		return $check;	

	}
			
}
	
