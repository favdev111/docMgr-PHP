<?php

//call this file to get our path to the thumbnails
include("../config/config.php");
session_id($_REQUEST["sessionId"]);
session_start();

define("THUMB_DIR",FILE_DIR."/thumbnails");

//don't go any farther if there is no session.  Someone is getting here by cheating
if (!$_SESSION["api"]["authorize"]) return false;

//make sure someone isn't pulling a fast one with the objDir
if (strstr($_REQUEST["objDir"],"..")) return false;
if ($_REQUEST["objDir"][0]=="/") return false;

displayThumbnail($_REQUEST["objectId"],$_REQUEST["objDir"]);

function displayThumbnail($objectId,$objDir) {

    //put our path in a variable
    $t = THUMB_DIR."/".$objDir;

    //if the thumb_dir is an absolute path, point directly to it.
    //if it's relative, move up a directory to get to the file
    if ($t[0]=="/") $thumb = $t."/".$objectId.".docmgr";
    else $thumb = "../".$t."/".$objectId.".docmgr";

    if (!file_exists($thumb) || filesize($thumb)=="0") $thumb = SITE_URL."themes/".SITE_THEME."/images/thumbnails/file.png";

    header("Content-Type: image/png");
    readfile($thumb);

}
