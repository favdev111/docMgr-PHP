<?php

$contactId = $_REQUEST["contactId"];
$email = $_REQUEST["email"];
$taskId = $_REQUEST["taskId"];
$objectPath = $_REQUEST["objectPath"];
$emailContent = $_REQUEST["editor_content"];

//make sure our temp folder exists, and make sure it's empty
$attachdir = TMP_DIR."/".USER_LOGIN."/email";
if (!is_dir($attachdir)) 
{
  recurmkdir(TMP_DIR."/".USER_LOGIN."/email");
} 
else 
{
  emptyDir($attachdir);
}

if ($_REQUEST["mode"]=="reply" || $_REQUEST["mode"]=="replyall") 
{

  require_once("app/imap.php");

  //establish connection to imap server
  $e = &$_SESSION["email"];
  $imap = new IMAP($e);

  if ($_REQUEST["mbox"]) $imap->openMbox($_REQUEST["mbox"]);
  else if ($_SESSION["api"]["imap_mbox"]) $imap->openMbox($_SESSION["api"]["imap_mbox"]);

  $ret = $imap -> getMsg($_REQUEST["uid"]);

  //setup content for the return.  append cc information if set
  if ($_REQUEST["mode"]=="replyall") {

    $email = $ret["from"].", ".$ret["to"];
    if ($ret["cc"]) $email .= ",".$ret["cc"];
    $email = extractSelfEmail($email);
    
  } else $email = $ret["from"];

  //setup our subject and email content
  if (!stristr($ret["subject"],"re:")) $subject = "RE: ".$ret["subject"];
  else $subject = $ret["subject"];
  
  $wrap = "<p>&nbsp;</p><p>&nbsp;</p>
                      <div style=\"margin-left:40px;\">
                      ------------------ Original Message -----------------
                      <br>
                      <b>Subject:</b> ".$ret["subject"]."<br>
                      <b>Date:</b> ".$ret["date"]."<br>
                      <b>From:</b> ".$ret["from"]."<br>
                      <b>To:</b> ".$ret["to"]."<br>
                      [[CONTENT]]
                    </div>";

  $emailContent = emailReformat($ret["content"],$wrap);
  
} 
else if ($_REQUEST["mode"]=="forward") 
{

  require_once("app/imap.php");

  //establish an imap connection and get our message info
  $e = &$_SESSION["email"];
  $imap = new IMAP($e);

  if ($_REQUEST["mbox"]) $imap->openMbox($_REQUEST["mbox"]);
  else if ($_SESSION["api"]["imap_mbox"]) $imap->openMbox($_SESSION["api"]["imap_mbox"]);

  $ret = $imap -> getMsg($_REQUEST["uid"]);

  //put the attachments where we can get to them
  if ($ret["attach"]) 
  {
  
    //loop through the attachments
    for ($i=0;$i<count($ret["attach"]);$i++) 
    {
    
      //write the file to our attachment directory so we can forward it
      $name = $ret["attach"][$i]["name"];
      $file = $attachdir."/".$name;
      $imap -> getAttachmentData($_REQUEST["uid"],$name,$file);
    
    }
  
  }

  //setup a subject and an email with the forwarded info
  $subject = "Fwd: ".$ret["subject"];

  $wrap = "<p>&nbsp;</p>
                      <div style=\"margin-left:0px;\">
                      ------------------ Original Message -----------------
                      <br>
                      <b>Subject:</b> ".$ret["subject"]."<br>
                      <b>Date:</b> ".$ret["date"]."<br>
                      <b>From:</b> ".str_replace("<","&lt;",$ret["from"])."<br>
                      <b>To:</b> ".str_replace("<","&lt;",$ret["to"])."<br>
                      <br>
                      [[CONTENT]]
                    </div>";

  $emailContent = emailReformat($ret["content"],$wrap);
  

} 
else if ($_REQUEST["mode"]=="draft") 
{

  require_once("app/imap.php");

  //establish an imap connection and get our message info
  //establish an imap connection and get our message info
  $e = &$_SESSION["email"];
  $imap = new IMAP($e);

  if ($_REQUEST["mbox"]) $imap->openMbox($_REQUEST["mbox"]);
  else if ($_SESSION["api"]["imap_mbox"]) $imap->openMbox($_SESSION["api"]["imap_mbox"]);

  $ret = $imap -> getMsg($_REQUEST["uid"]);

  //put the attachments where we can get to them
  if ($ret["attach"]) 
  {
  
    //loop through the attachments
    for ($i=0;$i<count($ret["attach"]);$i++) 
    {
    
      //write the file to our attachment directory so we can forward it
      $name = $ret["attach"][$i]["name"];
      $file = $attachdir."/".$name;
      $imap -> getAttachmentData($_REQUEST["uid"],$name,$file);
    
    }
  
  }

  //setup a subject and an email with the forwarded info
  $subject = $ret["subject"];
  $emailContent = emailReformat($ret["content"]);
  $email = $ret["to"];
  $cc = $ret["cc"];
  $bcc = $ret["bcc"];

}

if ($taskId) 
{

    $sql = "SELECT object_id FROM task.view_tea_task WHERE id='$taskId'";
    $info = $DB->single($sql);
    
    if ($info["object_id"]) $objectId = $info["object_id"];
    if ($info["contact_id"]) $contactId = $info["contact_id"];
    if ($info["contract_id"]) $contractId = $info["contract_id"];

}

if ($contactId) 
{

  if (!is_array($contactId)) $contactId = array($contactId);
  
  $sql = "SELECT first_name,last_name,email FROM contact WHERE id IN (".implode(",",$contactId).")";
  $list = list_result($conn,$sql);
  
  $arr = array();
  
  for ($i=0;$i<$list["count"];$i++) {

    if (!$list[$i]["email"]) $list[$i]["email"] = "UNKNOWN EMAIL ADDRESS";  
    $arr[] = $list[$i]["first_name"]." ".$list[$i]["last_name"]." <".$list[$i]["email"].">";  

  }

  $email = implode(",",$arr);
  
}


//get the type of object
if ($objectId || $objectPath) 
{

  if ($objectId)
  {
    $o = new DOCMGR_OBJECT($objectId);
    $objinfo = $o->getInfo();
  }
  else
  {
    $o = new DOCMGR_OBJECT($objectPath);
    $objinfo = $o->getInfo();
  }

  $objectType = $objinfo["object_type"];
  $objectId = $objinfo["id"];
  $objectPath = $objinfo["object_path"];
                 
}

//passed directly from url
if ($_REQUEST["editor_content"]) $emailContent = $_REQUEST["editor_content"];
                 
//if not content and there's a sig, set it to teh signature
if (!$emailContent && $_SESSION["accountSettings"]["email_sig"]) 
{
  $emailContent = $_SESSION["accountSettings"]["email_sig"];
  $emailContent = str_replace("<body>","<body><p>&nbsp;</p>",$emailContent);
}

//if there is editor content and we are on a mobile browser, switch to plain text mode
if ($emailContent && BROWSER_MOBILE=="t")
{
  $emailContent = strip_tags($emailContent);

  //remove leading spaces
  $arr = explode("\n",$emailContent);
  $num = count($arr);
  
  for ($i=0;$i<$num;$i++) 
  {
    if ($i > 6) $arr[$i] = "\t".trim($arr[$i]);
    else $arr[$i] = trim($arr[$i]);
  }
  
  $emailContent = implode("\n",$arr); 
  

}