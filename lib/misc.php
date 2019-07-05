<?php
/********************************************************************************************/
//
//	Filename:
//		misc.php
//      
//	Summary:
//		this file contains functions common to both prospect and contract applications
//		They should still be somewhat generic
//           
//	Modified:
//             
//		09-02-2004
//			Code cleanup.  Moved functions that don't belong out
//
//       04-19-2006
//          -More consolidation of functions.
//          -merged function.inc.php into file
//          -Created new files for removed functions
//              *file_functions.inc.php
//              *sanitize.inc.php
//              *calc_functions.inc.php                    
//          -Renamed file from common.inc.php to misc.php
//
//
/*********************************************************************************************/
/*********************************************************
//  returns the type of browser the user is using.
//  this function must be passed $HTTP_USER_AGENT.
*********************************************************/
function set_browser_info() {

  //Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.5; en-US; rv:1.9.0.6) Gecko/2009011912 Firefox/3.0.6 
  //Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_5_6; en-us) AppleWebKit/525.27.1 (KHTML, like Gecko) Version/3.2.1 Safari/525.27.1
  //Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; SLCC1; .NET CLR 2.0.50727; .NET CLR 3.0.04506; .NET CLR 3.5.21022) 

  $browser = $_SERVER["HTTP_USER_AGENT"];

  if (stristr($browser,"MSIE")) {

    $arr = explode(";",$browser);
    $version = trim(str_replace(" MSIE ","",$arr[1]));

    define("BROWSER","ie");
    define("BROWSER_VERSION",$version);
      
  } else if (stristr($browser,"WEBKIT")) {

    $str = substr($browser,strpos($browser,"Version/")+8);
    $version = trim(substr($str,0,strpos($str," ")));

    define("BROWSER","webkit");
    define("BROWSER_VERSION",$version);
      
  } else {

    $version = trim(substr($browser,strrpos($browser,"/")+1));
  
    define("BROWSER","mozilla");
    define("BROWSER_VERSION",$version);
      
  } 

  if (stristr($browser,"MOBILE")) define("BROWSER_MOBILE","t");
  else define("BROWSER_MOBILE","f");

}

/*********************************************************
*********************************************************/
function includeStylesheet($path) 
{

  global $module;

  //if it has a semicolon in it, it may contain multiples
  $arr = cacheCSSFiles($path);

  foreach ($arr AS $css) 
  {

    if (!$css) continue;

    //look for a cached copy, otherwise use the passed one
    if (defined("CSS_COMPRESS") && file_exists("cache/".$css)) $css = "cache/".$css;

    $css .= "?v=".JS_VERSION;

    echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$css."\">\n";

  }


}
/*********************************************************
*********************************************************/
function includeJavascript($path) 
{

  global $module;

  $arr = cacheJSFiles($path);
  
  foreach ($arr AS $js) 
  {

    if (!$js) continue;

    //look for a cached copy, otherwise use the passed one
    if (defined("JS_COMPRESS") && file_exists("cache/".$js)) $js = "cache/".$js;

    $js .= "?v=".JS_VERSION;

	  echo "<script type=\"text/javascript\" src=\"".$js."\"></script>\n";

  }

}

/*********************************************************
*********************************************************/
function includeVBS($path) {

    //if it has a semicolon in it, it may contain multiples
    $arr = explode(";",$path);

    foreach ($arr AS $vbs) {

      if (!$vbs) continue;
  	  echo "<script type=\"text/vbscript\" src=\"".$vbs."\"></script>\n";

    }
  
}

function returnAccountInfo($aid) {

  $a = new ACCOUNT($aid);
  return $a->getInfo();

}

function returnAccountList($opt) {

  $sort = $opt["sort"];
  $filter = $opt["search_filter"];

  $a = new ACCOUNT();
  $ret = $a->getList($filter,$sort);
  
  $num = count($ret);

  if ($ret["count"] > 0)
  {

    //for backwards compatibility
    unset($ret["count"]);

    $ret = transposeArray($ret);

    $ret["count"] = $num;

  }

  return $ret;

}

function validMethod($class,$method)
{

  //make sure the method is public
  $valids = get_class_methods($class);
  $num = count($valids);

  for ($i=0;$i<$num;$i++) $valids[$i] = strtolower($valids[$i]);

  if (@in_array($method,$valids)) return true;
  else return false;
 
}

function uuid($prefix = '')
{  

  $chars = md5(uniqid(mt_rand(), true));
  $uuid = substr($chars,0,8) . '-';
  $uuid .= substr($chars,8,4) . '-';
  $uuid .= substr($chars,12,4) . '-';
  $uuid .= substr($chars,16,4) . '-';
  $uuid .= substr($chars,20,12);

  return strtoupper($prefix . $uuid);

}

function short_uuid($prefix = '')
{  

  $chars = md5(uniqid(mt_rand(), true));
  $uuid = substr($chars,0,8) . '-';
  $uuid .= substr($chars,20,12);

  return strtoupper($prefix . $uuid);

}

function packFile($file) 
{

  $ext = fileExtension($file);
  $file = SITE_PATH."/".$file;
  
  if ($ext=="js") 
  {
    $cmd = "jspack \"".$file."\"";
  } 
  else if ($ext=="css") 
  {
    $cmd = "csspack \"".$file."\"";
  }

  $ret = `$cmd`;

  return $ret;

}

function cacheJSFiles($path)
{

  global $module;
  
  $arr = explode(";",$path);
  $ret = array();
      
  //can't cache the home module because of the modlets
  if (defined("DEV_MODE") ||
      !defined("JS_CACHE") || 
      $_SESSION["siteModInfo"][$module]["nocache"]) return $arr;

  $directory = "cache/".JS_VERSION;
  if (!is_dir($directory)) mkdir($directory);

  //the files we need to skip because they don't play well.  this does
  //a pattern match check.  So, I could use "mootools" to skip all mootools files
  $skip = array("ckeditor.js");

  //split into the ones we need to skip and the ones we need to cache
  $toCache = array();
  
  foreach ($arr AS $js)
  {
  
    if (!$js) continue;
    $filename = array_pop(explode("/",$js));
    $ignore = false;

    for ($i=0;$i<count($skip);$i++)
    {
      if (strstr($js,$skip[$i]))
      {
        $ignore = true;
        break;
      }
    }

    if ($ignore==true) $ret[] = $js;
    else $toCache[] = $js;
    
  }

  $cacheFile = $directory."/".$module.".js";

  if (!file_exists($cacheFile))
  {

    $str = null;

    foreach ($toCache AS $js) 
    {
      if (!$js) continue; 
      $str .= file_get_contents($js)."\n";
    }

    file_put_contents($cacheFile,$str);

    if (USER_ID==1000)
    {
      //$compressed = packFile($cacheFile);
      //file_put_contents($cacheFile,$compressed); 
    }
  }
  
  $ret[] = $cacheFile;
    
  return $ret;

}

function cacheCSSFiles($path)
{

  global $module;
  
  $arr = explode(";",$path);
  $ret = array();
      
  //can't cache the home module because of the modlets
  if (defined("DEV_MODE") ||
      !defined("CSS_CACHE") || 
      $_SESSION["siteModInfo"][$module]["nocache"]) return $arr;

  $directory = "cache/".JS_VERSION;
  if (!is_dir($directory)) mkdir($directory);

  //the files we need to skip because they don't play well.  this does
  //a pattern match check.  So, I could use "mootools" to skip all mootools files
  //$skip = array("ckeditor.js");

  //split into the ones we need to skip and the ones we need to cache
  $toCache = array();
  
  foreach ($arr AS $css)
  {
  
    if (!$css) continue;
    $filename = array_pop(explode("/",$css));
    $ignore = false;

    for ($i=0;$i<count($skip);$i++)
    {
      if (strstr($css,$skip[$i]))
      {
        $ignore = true;
        break;
      }
    }

    if ($ignore==true) $ret[] = $css;
    else $toCache[] = $css;
    
  }

  $cacheFile = $directory."/".$module.".css";

  if (!file_exists($cacheFile))
  {

    $str = null;

    foreach ($toCache AS $css) 
    {
      if (!$css) continue; 
      $str .= file_get_contents($css)."\n";
    }

    //rewrite the url so relative paths from theme directory works
    $str = str_replace('url("../','url("'.SITE_URL.THEME_PATH.'/',$str);

    file_put_contents($cacheFile,$str);

    if (USER_ID==1000)
    {
      //$compressed = packFile($cacheFile);
      //file_put_contents($cacheFile,$compressed); 
    }
    
  }
  
  $ret[] = $cacheFile;
    
  return $ret;

}

function dprint($str)
{

  //throw our field to trip the proto error
  if (!$GLOBALS["echoDebug"])
  {
    echo "DEBUG:\n";
    $GLOBALS["echoDebug"] = 1;
  }

  echo $str;

}

//puts a doctype declaration at the top of page if none is there
function fixDoctype($content) 
{
   
  //trim it down
  $content = trim($content);
  $doctype = "<!DOCTYPE HTML>\n";

  //make sure we have html and body tags in the email.We are assuming here that if there
  //is no body tag, it's missing all the main tags
  if (!stristr($content,"</body>"))
  {
    $content = "<html><head></head><body>".$content."</body></html>\n";
  }

  //get the first line of the document (everything before first newline)
  $line = substr($content,0,strpos($content,"\n")); 

  //doctype should be there 
  $test = stripos($line,"<!DOCTYPE");

  //if not, add it
  if ($test===FALSE) $content = $doctype.$content;

  return $content;

}

/**
  apparently this only exists with mod_php
  */
if (!function_exists('getallheaders'))
{

  function getallheaders() 
  {

    foreach ($_SERVER as $name => $value)
    {
      if (substr($name, 0, 5) == 'HTTP_')
      {	
        $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
        $headers = $value;
      } 
      else if ($name == "CONTENT_TYPE") 
      {
        $headers = $value;
      } 
      else if ($name == "CONTENT_LENGTH") 
      {
        $headers = $value;
      }
    }

    return $headers;

  }

}
