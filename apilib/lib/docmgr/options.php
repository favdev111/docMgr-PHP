<?php

class DOCMGR_OPTIONS extends DOCMGR 
{

  function get() 
  {

    $sql = "SELECT * FROM docmgr.object_options WHERE object_id='".$this->objectId."'";
    $info = $this->DB->single($sql);
    
    if ($info)
    {
      $this->PROTO->add("record",$info);
    }

    if ($this->DB->error())
    {
      $this->throwError($this->DB->error());
    }
  
  }

  function save()
  {

    //see if we have a record to insert or update
    $sql = "SELECT * FROM docmgr.object_options WHERE object_id='".$this->objectId."'";
    $info = $this->DB->single($sql);

    //so we can easily add more fields later
    $keys = array("disable_content_index");
    
    foreach ($keys AS $key)
    {
      if (isset($this->apidata[$key])) $opt[$key] = $this->apidata[$key];
    }
    
    if ($info)
    {
      $opt["where"] = "object_id='".$this->apidata["object_id"]."'";
      $this->DB->update("docmgr.object_options",$opt);
    }
    else
    {
      $opt["object_id"] = $this->objectId;
      $this->DB->insert("docmgr.object_options",$opt);
    }

    if ($this->DB->error())
    {
      $this->throwError($this->DB->error());
    }
  
  }

  function delete()
  {
    $sql = "DELETE FROM docmgr.object_options WHERE object_id='".$this->objectId."'";
    $this->DB->query($sql);

    if ($this->DB->error())
    {
      $this->throwError($this->DB->error());
    }
  }

  function indexDisabled()
  {

    $paths = $this->objectIdAllPaths();

    //if one of these parents
    $pathString = implode(",",$paths);
    
    $sql = "SELECT disable_content_index FROM docmgr.object_options WHERE object_id IN (".$pathString.") AND disable_content_index='t'";
    $match = $this->DB->single($sql);
   
    if ($match) return true;
    else return false;
    
  }  

}
