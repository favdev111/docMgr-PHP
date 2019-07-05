<?php

//call this file to get our path to the thumbnails
include("../config/config.php");

//make sure someone isn't pulling a fast one with the objDir
if (strstr($_REQUEST["image"],"..")) return false;
if (strpos($_REQUEST["image"],TMP_DIR)!=0) return false;

$pos = strpos($_REQUEST["image"],"?");
if ($pos!==FALSE) $img = substr($_REQUEST["image"],0,$pos);
else $img = $_REQUEST["image"];

header("Content-Type: image/png"); 
readfile($img);
die;
