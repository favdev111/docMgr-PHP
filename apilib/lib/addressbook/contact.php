<?php

class ADDRESSBOOK_CONTACT extends ADDRESSBOOK
{

  private $contactId;
  
  function ___construct()
  {

    if ($this->apidata["contact_id"])
    {

    	if (is_array($this->apidata["contact_id"])) $ids = $this->apidata["contact_id"];
    	else $ids = array($this->apidata["contact_id"]);

      $sql = "SELECT contact_id FROM addressbook.contact_account WHERE contact_id IN (".implode(",",$ids).") AND account_id='".USER_ID."'";
      $results = $this->DB->fetch($sql);
      
			//we have permission to access all of them
      if ($results["count"] >= count($ids)) $this->contactId = $this->apidata["contact_id"];
      else $this->throwError(_I18N_PERMISSION_DENIED);
    
    }
  
  }

  function search()
  {

    $sql = "SELECT * FROM addressbook.view_contact WHERE account_id='".USER_ID."'";

    //keep it simple for now    
    if ($this->apidata["search_string"]) $sql .= " AND ".$this->nameFilter($this->apidata["search_string"]);

    $results = $this->DB->fetch($sql);

    if ($results["count"] > 0)
    {
      unset($results["count"]);
      $this->PROTO->add("record",$results);    
    }

    if ($this->DB->error()) $this->throwError($this->DB->error());
        
  }

	private function nameFilter($ss)
	{
	
	  $ss = strtolower($ss);
	  
	  $filter = array();
	
	  $filter[] = "lower(email) LIKE '".$ss."%'";
	
	  //name searching
	  $arr = organizeName($ss);
	 
	  if (count($arr)==1)
	  {
	    $filter[] = "(lower(first_name) ILIKE '".$arr["ln"]."%' OR lower(last_name) ILIKE '".$arr["ln"]."%')";
	  }
	  else
	  {
	    $filter[] = "(lower(first_name) ILIKE '".$arr["fn"]."%' AND lower(last_name) ILIKE '".$arr["ln"]."%')";
	  }
	 
	  return "(".implode(" OR ",$filter).")";
	 
	}
	 
  function get()
  {
    $sql = "SELECT * FROM addressbook.contact WHERE id='".$this->contactId."'";
    $info = $this->DB->single($sql);
    $this->PROTO->add("record",$info);

    if ($this->DB->error()) $this->throwError($this->DB->error());
  }

  function delete()
  {

  	if (!is_array($this->contactId)) $this->contactId = array($this->contactId);

    $sql = "DELETE FROM addressbook.contact WHERE id IN (".implode(",",$this->contactId).");";
    $sql .= "DELETE FROM addressbook.contact_account WHERE contact_id IN (".implode(",",$this->contactId).");";
    $this->DB->query($sql);

    if ($this->DB->error()) $this->throwError($this->DB->error());
  }
                                                                                                        
  function save()
  {

    $fields = array("first_name","middle_name","last_name","address","address2","city",
                    "state","zip","country","home_phone","work_phone","work_fax","mobile",
                    "pager","email","prefix","suffix","letter_salutation","envelope_salutation",
                    "website","company_name","work_ext");

    $opt = null;

    foreach ($fields AS $field)
    {
      if (isset($this->apidata[$field])) $opt[$field] = $this->apidata[$field];
    }

    $opt["work_phone"] = phoneProcess($opt["work_phone"]);
    $opt["home_phone"] = phoneProcess($opt["home_phone"]);
    $opt["work_fax"] = phoneProcess($opt["work_fax"]);
    $opt["mobile"] = phoneProcess($opt["mobile"]);
    $opt["pager"] = phoneProcess($opt["pager"]);

    $this->DB->begin();
    
    if ($this->contactId)
    {
      $opt["where"] = "id='".$this->contactId."'";
      $this->DB->update("addressbook.contact",$opt);
    }
    else
    {
      $this->contactId = $this->DB->insert("addressbook.contact",$opt,"id");    

      $opt = null;
      $opt["account_id"] = USER_ID;
      $opt["contact_id"] = $this->contactId;
      $this->DB->insert("addressbook.contact_account",$opt);
      
    }

    $this->DB->end();

    if ($this->DB->error()) $this->throwError($this->DB->error());

    $this->PROTO->add("contact_id",$this->contactId);
    
  }

}


	 