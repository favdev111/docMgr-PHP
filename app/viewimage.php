<?php

$thumb = "../images/thumbnails/file.png";
 
//call this file to get our path to the thumbnails
require_once("../config/config.php");

require_once(SITE_PATH."/config/ldap-config.php");
require_once(SITE_PATH."/lib/filefunctions.php");
require_once(SITE_PATH."/lib/sanitize.php");
require_once(SITE_PATH."/lib/pgsql.php");
require_once(SITE_PATH."/lib/logger.php");
require_once(SITE_PATH."/lib/proto/xml.php");
require_once(SITE_PATH."/lib/xml.php");
require_once(SITE_PATH."/app/common.php");
require_once(SITE_PATH."/lib/account/account.php");

$objectId = sanitize($_REQUEST["objectId"]);
$sessionId = sanitize($_REQUEST["sessionId"]);

$DB = new POSTGRESQL(DBHOST,DBUSER,DBPASSWORD,DBPORT,DBNAME);
$GLOBALS["DB"] = $DB;

if ($sessionId && $sessionId!="[DOCMGR_SESSION_MARKER]") 
{
  session_id($sessionId);
  session_start();

  //stop here if not authorized by session id or username/password
  if (!$_SESSION["api"]["authorize"]) die("Invalid session paramaters set");

} 
//the only thing that should use this is openoffice during conversions
else 
{
  //delete any keys more than 5 minutes old, just in case we end up with some stale ones
  $time = date("c",time() - (60 * 5));

  $sql = "DELETE FROM docmgr.object_convert_keys WHERE date_created < '".$time."'";
  $DB->query($sql);

  $convertKey = sanitize($_REQUEST["convertKey"]);
  
  $sql = "SELECT object_id FROM docmgr.object_convert_keys WHERE object_id='".$objectId."' AND convert_key='".$convertKey."'";
  $info = $DB->single($sql);
  
  if (!$info)
  {
    die("Invalid key specified for this object");
  }
  else
  {
    //remove the auth key so it can't be used again
    $sql = "DELETE FROM docmgr.object_convert_keys WHERE object_id='".$objectId."' AND convert_key='".$convertKey."'";
    $info = $DB->query($sql);
  }

}

//if we made it to here, assume they are allowed to be here
$sql = "SELECT DISTINCT id,name,(level1 || '/' || level2) AS file_path,
        (SELECT id FROM docmgr.dm_file_history WHERE dm_file_history.object_id=dm_view_objects.id ORDER BY version DESC LIMIT 1) AS file_id
        FROM docmgr.dm_view_objects
        WHERE id='$objectId'";
$list = $DB->fetch($sql);

for ($i=0;$i<$list["count"];$i++) {

  $key = $list[$i]["id"];
  $fileid = $list[$i]["file_id"];
  $filepath = $list[$i]["file_path"];
  $filename = $list[$i]["name"];
 
} 

$DB->close();

//put our path in a variableË
$d = DATA_DIR."/".$filepath;

//if the thumb_dir is an absolute path, point directly to it.
//if it's relative, move up a directory to get to the file
if ($d[0]=="/") $filepath = $d."/".$fileid.".docmgr";
else $filepath = "../".$d."/".$fileid.".docmgr";

if (!file_exists($filepath)) 
{
  $filepath = "../themes/".SITE_THEME."/images/thumbnails/file.png";
  $filename = "file.png";
}

$fileinfo = fileInfo($filename);
$mime = $fileinfo["mime_type"];

header("Content-Type: $mime");
readfile($filepath);
