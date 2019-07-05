#!/usr/bin/php

<?php

/**
  simple utility for reporting which variables in the second file are missing from the first file.
  works for the javascript and php files
  
  Usage: "php lang-diff.php en/client.js old-en/client.js"
  */
  
//sanity check
if (!$argv[1] || !$argv[2])
{
  echo "Usage: \"php lang-diff.php en/client.js old-en/client.js\"\n";
  die();
}


//make sure they are the same type
$ext1 = fileExtension($argv[1]);
$ext2 = fileExtension($argv[2]);

if ($ext1 != $ext2)
{
  die("Both files must be of the same file type\n");
}

//process js files
if ($ext1=="js") processJS($argv[1],$argv[2]);

//process php files
else if ($ext1=="php") processPHP($argv[1],$argv[2]);

//oops
else die("Unrecognized file type passed\n");

/********************************
  our functions
********************************/

function processPHP($newFile,$oldFile)
{

  $newVars = getPHPVars($newFile);
  $oldVars = getPHPVars($oldFile);

  $diff = array_values(array_diff($newVars,$oldVars));
  
  for ($i=0;$i<count($diff);$i++)
  {
    echo "New Variable => ".$diff[$i]."\n";
  }

}

function processJS($newFile,$oldFile)
{

  $newVars = getJSVars($newFile);
  $oldVars = getJSVars($oldFile);
  
  $diff = array_values(array_diff($newVars,$oldVars));
  
  for ($i=0;$i<count($diff);$i++)
  {
    echo "New Variable => ".$diff[$i]."\n";
  }

}

function getPHPVars($file)
{
  $str = file_get_contents($file);
  $arr = explode("\n",$str);
  $vars = array();
  
  foreach ($arr AS $line)
  {
    if (!strstr($line,"_I18N_")) continue;

    $pos1 = strpos($line,"_I18N");
    $line = substr($line,$pos1);

    $pos2 = strpos($line,"\"");
    $var = substr($line,0,$pos2);

    $vars[] = $var;
    
  }

  return $vars;
    
}

function getJSVars($file)
{
  $str = file_get_contents($file);
  $arr = explode("\n",$str);
  $vars = array();
  
  foreach ($arr AS $line)
  {
    if (!strstr($line,"_I18N_")) continue;

    $pos1 = strpos($line,"_I18N");
    $line = substr($line,$pos11);
    
    $pos2 = strpos($line,"=");
    $var = trim(substr($line,0,$pos2));

    $vars[] = $var;
    
  }

  return $vars;
    
}


function fileExtension($file) 
{
  $file = basename($file);
  
  $pos = strrpos($file,".");
  if ($pos == "0") return false;
  else 
  {
    $ext = strtolower(substr($file,$pos+1));
    return $ext;
  }
}
