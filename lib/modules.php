<?php
/*********************************************************/
//         FILE: module.php
//  DESCRIPTION: Contains functions that handle the
//               processing of the information in the
//               module.xml files and the execution/display
//               for the site.
//                      
//     CREATION
//         DATE: 04-19-2006
//
//      HISTORY:
//
//
/*********************************************************/

/*********************************************************
*********************************************************/
function returnModuleOwner($id,$ownerArray) {

    $siteModSettings = $_SESSION["siteModSettings"];

    $key = array_search($id,$siteModSettings["modId"]);
    $owner = $siteModSettings["modOwner"][$key];

    //if 0, we have reached the top.  Just return the array as is.
    if ($owner=="0") return $ownerArray;
    else {

        //this one also is owned by another module.  Add to the array and check again
        $ownerArray[] = $owner;
        $ownerArray = returnModuleOwner($owner,$ownerArray);

        //return the new ownerArray
        return $ownerArray;

    }

}
/*********************************************************
*********************************************************/
function getPath($modArray,$id,$path) {

    //do a search for our id to get the key
    $key = array_search($id,$modArray["modId"]);

    $path = $modArray["modDirectory"][$key]."/".$path;

    //is their an owner
    if ($modArray["modOwner"][$key]!="0") {

        $id = $modArray["modOwner"][$key];

        $path = getPath($modArray,$id,$path);

    }

    return $path;

}
/*********************************************************
*********************************************************/
function getGroupPath($conn,$id,$path) {

    //get all groups and store them in an array
    $sql = "SELECT owner,path FROM in_groups WHERE id='$id'";
    $info = single_result($conn,$sql);

    if ($info) {

        $owner = $info["owner"];
        $newPath = $info["path"];

        $path = $newPath."/".$path;

        if ($owner!="0") $path = getGroupPath($conn,$owner,$path);

    }

    return $path;

}
/*********************************************************
*********************************************************/
function getOwner($bitpos,$ownerArray,$bitposArray,$string) {

    $string .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>";

    $key = array_search($bitpos,$bitposArray);

    if ($ownerArray[$key]!="0") $string = getOwner($bitpos,$ownerArray,$bitposArray,$string);
    else return $string;

}
/*********************************************************
*********************************************************/
function getOwnerPath($str) {

	$len = strlen($str);

	//remove trailing slash
	if (substr($str,$len-1)=="/") $str = substr($str,0,$len-1);

	$pos = strrpos($str,"/") + 1;
	
	return substr($str,0,$pos);

}


/**************************************************************
	this function creates links to all modules below
	it in a tabular format.  It still needs some love
**************************************************************/

function showModTabs($path,$module=null) 
{

	if (!$path) return false;

	$siteModList = showModLevel($path,"sort_order");

	$string = null;
	$counter = "0";

	$num = count($siteModList["owner_path"]);

	for ($row=0;$row<$num;$row++) {

		$hide = null;
		
		if ($siteModList["permissions"][$row])
		{

		  $allow = false;
		  
		  foreach ($siteModList["permissions"][$row] AS $perm)
		  {
		    $allow = PERM::check(constant($perm));
		    if ($allow==true) break;
      }
      
      if ($allow==false) $hide = 1;

    }
    
		if ($siteModList["hidden"][$row]==1) $hide=1;

		if (!$hide) 
		{

			//alter the tab class if it's current
			if ($module==$siteModList["link_name"][$row]) $class = "modTabSelected";
			else $class = "modTab";

			//show the translation for our module name if it exists
			$langmod = "_MT_".strtoupper($siteModList["link_name"][$row]);

			if (defined($langmod)) $modName = constant($langmod);
			else $modName = $siteModList["module_name"][$row];

      $id = $siteModList["link_name"][$row]."ModTab";
			
			$string .= "	
			    <div>
          <div class=\"".$class."\"
					  onclick=\"location.href = 'index.php?module=".$siteModList["link_name"][$row]."'\"
					  id=\"".$id."\"
          >
					".$modName."
					</div>
          <div id=\"".$siteModList["link_name"][$row]."ModuleCtrl\" class=\"siteModCtrl\" style=\"display:none\"></div>
          <div id=\"".$siteModList["link_name"][$row]."ModuleNav\" class=\"siteModNav\" style=\"display:none\"></div>
          <div id=\"".$siteModList["link_name"][$row]."ModuleFooter\" class=\"siteModFooter\" style=\"display:none\"></div>
					</div>\n";

		}

	}

	return $string;
	
}

function getModules($path,$module=null) 
{

	if (!$path) return false;

	$siteModList = showModLevel($path,"sort_order");

	$string = null;
	$counter = "0";

	$num = count($siteModList["owner_path"]);
	$ret = array();
	
	for ($row=0;$row<$num;$row++) {

		$hide = null;
		
		if ($siteModList["permissions"][$row])
		{

		  $allow = false;
		  
		  foreach ($siteModList["permissions"][$row] AS $perm)
		  {
		    $allow = PERM::check(constant($perm));
		    if ($allow==true) break;
      }
      
      if ($allow==false) $hide = 1;

    }
    
		if ($siteModList["hidden"][$row]==1) $hide=1;

		if (!$hide) 
		{

		  $arr = array();
		  $arr["module_name"] = $siteModList["link_name"][$row];
		  $arr["module_title"] = $siteModList["module_name"][$row];
		  $ret[] = $arr;

		}

	}

	return $ret;
	
}


/************************************************************
	This function generates a page with all sub
	modules and their descriptions.
************************************************************/

function showModTable($path,$sort = null) {

	if (!$path) return false;

	$siteModList = showModLevel($path,$sort);

	$string = "<table border=0 width=100%>
			<tr>\n";

	$counter = "0";

	$num = count($siteModList["module_name"]);
	$cell = "0";
	
	for ($row=0;$row<$num;$row++) {

		if ($cell=="2") {
			$string .= "</tr>\n<tr>";
			$cell = "0";
		}
		
		$hide = null;

		if ($siteModList["permissions"][$row])
		{

		  $allow = false;
		  
		  foreach ($siteModList["permissions"][$row] AS $perm)
		  {
		    $allow = PERM::check(constant($perm));
		    if ($allow==true) break;
      }
      
      if ($allow==false) $hide = 1;

    }

		if ($siteModList["custom_perm"][$row])
		{

		  $allow = false;
		  
		  foreach ($siteModList["custom_perm"][$row] AS $perm)
		  {
		    $allow = PERM::check(constant($perm));
		    if ($allow==true) break;
      }
      
      if ($allow==false) $hide = 1;

    }

		if ($siteModList["hidden"][$row]==1) $hide=1;

		if (!$hide) {

			//show the translation for our module name if it exists
			$langmod = "_MT_".strtoupper($siteModList["link_name"][$row]);
			$langdesc = "_MTDESC_".strtoupper($siteModList["link_name"][$row]);

			//check for translations of the module name
			if (defined($langmod)) $modName = constant($langmod);
			else $modName = $siteModList["module_name"][$row];

			//check for translations of the module description
			if (defined($langdesc)) $modDesc = constant($langdesc);
			else $modDesc = $siteModList["module_description"][$row];

			$string .= "	<td width=50% valign=top style=\"padding-bottom:10px;\">
					<div>
					<a class=\"moduleLink\" 
					href=\"index.php?module=".$siteModList["link_name"][$row]."\"
					>
					".$modName."
					</a>
					</div>
					<div>
					".$modDesc."
					</div>
					</td>\n";

			$cell++;

		}

		
	}

	$string .= "</tr></table>";

	return $string;
	
}

function returnModImage($linkName) {

	if (!$linkName) return false;
	
	$siteModList = $_SESSION["siteModList"];
	
	$key = array_search($linkName,$siteModList["link_name"]);
	
	$baseImg = THEME_PATH."/images/modules/module.png";

	//the image order priority goes image in module directory, current theme image,
	//default theme image, and then no image
	$themeImg = THEME_PATH."/images/modules/".$siteModList["link_name"][$key].".png";
	$defaultImg = THEME_PATH."/images/modules/".$siteModList["link_name"][$key].".png";
	$modImg = $siteModList["module_path"][$key].$siteModList["link_name"][$key].".png";

	if (file_exists($modImg)) $liImage = $modImg;
	elseif (file_exists($themeImg)) $liImage = $themeImg;
	elseif (file_exists($defaultImg)) $liImage = $defaultImg;
	else $liImage = $baseImg;

	return $liImage;

}

/**************************************************************
	this function creates links to all modules below
	it in a tabular format.  It still needs some love
**************************************************************/
//filter is looking for a tag to exist.  if it does, we can show
function showModLinks($path,$module,$filter=null)
{

	if (!$path) return false;

	$siteModList = showModLevel($path,"sort_order");

	$string = "<ul style=\"list-style:none;margin-left:0px;padding-left:0px;\">\n";

	$counter = "0";

	$num = count($siteModList["owner_path"]);

	$baseImg = THEME_PATH."/images/modules/module.png";

	for ($row=0;$row<$num;$row++) {

		$hide = null;

		if ($filter && !$siteModList[$filter][$row]) continue;

		if ($siteModList["permissions"][$row])
		{

		  $allow = false;
		  
		  foreach ($siteModList["permissions"][$row] AS $perm)
		  {
		    $allow = PERM::check(constant($perm));
		    if ($allow==true) break;
      }
      
      if ($allow==false) $hide = 1;

    }

		if ($siteModList["custom_perm"][$row])
		{

		  $allow = false;
		  
		  foreach ($siteModList["custom_perm"][$row] AS $perm)
		  {
		    $allow = PERM::check(constant($perm));
		    if ($allow==true) break;
      }
      
      if ($allow==false) $hide = 1;

    }

    if ($siteModList["hidden"][$row]) $hide = 1;

		if (!$hide) 
		{

			//show the translation for our module name if it exists
			$langmod = "_MT_".strtoupper($siteModList["link_name"][$row]);
			$langdesc = "_MTDESC_".strtoupper($siteModList["link_name"][$row]);

			//check for translations of the module name
			if (defined($langmod)) $modName = constant($langmod);
			else $modName = $siteModList["module_name"][$row];

			//check for translations of the module description
			if (defined($langdesc)) $modDesc = constant($langdesc);
			else $modDesc = $siteModList["module_description"][$row];

			$liImage = returnModImage($siteModList["link_name"][$row]);

			$string .= "<li>\n";
			$string .= "<table>";
			$string .= "<tr><td valign=top>\n";
			$string .= "<img alt=\"\" src=\"".$liImage."\" style=\"vertical-align:bottom\">\n";
			$string .= "</td><td>\n";
			$string .= "<a 	class=\"moduleLink\" 
					href=\"index.php?module=".$module."&includeModule=".$siteModList["link_name"][$row]."\"
					>
					".$modName."
					</a>\n
					<br>
					".$modDesc."\n";
			$string .= "</td></tr>\n";
			$string .= "</table>\n";
			$string .= "</li>\n";

		}

	}

	$string .= "</ul>";

	return $string;
	
}

function includeModuleProcess($path) {

	if (!$path) return false;
	
	//determine our process file and our display file
	$process_path = $path."process.php";
	$function_path = $path."function.php";

	//load any optional function files in the module directory
	if (file_exists("$function_path")) include("$function_path");
	if (file_exists("$process_path")) include("$process_path");

}

function includeModuleDisplay($path) {

	if (!$path) return false;
	
	//determine our process file and our display file
	$style_path = $path."stylesheet.css";
	$js_path = $path."javascript.js";
	$display_path = $path."display.php";

	//these get called by our body.inc.php file
	if (file_exists("$style_path")) includeStylesheet("$style_path");
	if (file_exists("$js_path")) includeJavascript("$js_path");

	//define our display module if there is one
	if (file_exists("$display_path")) include("$display_path");;

}

                
                
function getCustomModPerms($module,$recursive="yes") 
{

  //just return here if it's not a recursive entry
  if ($recursive=="no") return $_SESSION["siteModInfo"][$module]["custom_perm"];

	//for custom permissions, we get the owning permissions as well
	$tmp = $_SESSION["siteModInfo"][$module];

	//extract our parent module names from our current module path
	$arr = explode("/",$tmp["module_path"]);

	//remove module/center/ and the trailing slash from the array
	array_shift($arr);
	array_pop($arr);

	//get the permissions for each module
	for ($row=0;$row<count($arr);$row++) {

		$mod = $arr[$row];

		if (is_array($customPermArr)) $customPermArr = array_merge($customPermArr,$_SESSION["siteModInfo"][$mod]["custom_perm"]);
		else $customPermArr = $_SESSION["siteModInfo"][$mod]["custom_perm"];

	}

	//get rid of duplicates
	if (is_array($customPermArr)) return array_values(array_unique($customPermArr));
	else return false;
	
}

//this function currently isn't used
function getRecursiveModInfo($module) 
{

	//for custom permissions, we get the owning permissions as well
	$tmp = $_SESSION["siteModInfo"][$module];

	//extract our parent module names from our current module path
	$arr = explode("/",$tmp["module_path"]);

	//remove module/center/ and the trailing slash from the array
	array_shift($arr);
	array_pop($arr);

	return $arr;

	
}


function checkModPerm($module) 
{

  $permArr = null;
  $authOnly = null;

	if (isset($_SESSION["siteModInfo"][$module]["permissions"])) $permArr = $_SESSION["siteModInfo"][$module]["permissions"];
  if (isset($_SESSION["siteModInfo"][$module]["authOnly"])) $authOnly = $_SESSION["siteModInfo"][$module]["auth_only"];
        
  $ret = array();

  //the user should be logged in if auth_only is checked
  if ($authOnly!=null && !isset($_SESSION["api"]["authorize"])) 
  {
 	  $ret["errorMessage"] = "You must be logged in to view this section";
 	  $ret["permError"] = 1;

 	  return $ret;
	
	}

	//run our perm check if there are perm requirements	
	if (is_array($permArr)) 
	{

	  $error = true;

	  foreach ($permArr AS $perm)
	  {

	    $p = constant($perm);

	    if (PERM::check($p))
	    {
	      $error = false;
        break;
      }
      
    }
    
    if ($error)
    {
    
  		if (isset($_SESSION["siteModInfo"][$module]["perm_message"]))
  			$msg = $_SESSION["siteModInfo"][$module]["perm_message"];
  		else
  			$msg = "You are not allowed to access this section";
  				
      $ret["errorMessage"] = $msg;
      $ret["permError"] = 1;
	    
	  }
		
		return $ret;	

	} else {
	  return false;
  }

}


function checkCustomModPerm($module) 
{

	$permArr = $_SESSION["siteModInfo"][$module]["custom_perm"];
        
  $ret = array();

	//run our perm check if there are perm requirements	
	if (is_array($permArr)) 
	{

	  $error = true;

	  foreach ($permArr AS $perm)
	  {

	    $p = constant($perm);

      //check against custom_bitset value	    
	    if (PERM::checkCustom($p))
	    {
	      $error = false;
        break;
      }

    }
    
    if ($error)
    {
    
  		if ($_SESSION["siteModInfo"][$module]["perm_message"])
  			$msg = $_SESSION["siteModInfo"][$module]["perm_message"];
  		else
  			$msg = "You are not allowed to access this section";
  				
      $ret["errorMessage"] = $msg;
      $ret["permError"] = 1;
	    
	  }
		
		return $ret;	

	} else return false;

}


function modTreeMenu($module) {

	$arr = getRecursiveModInfo($module);

	$num = count($arr);

	for ($row=0;$row<$num;$row++) 
	{
	
		$mod = &$arr[$row];

		//show the translation for our module name if it exists
		$langmod = "_MT_".strtoupper($_SESSION["siteModInfo"][$mod]["link_name"]);

		if (defined($langmod)) $modName = constant($langmod);
		else $modName = $_SESSION["siteModInfo"][$mod]["module_name"];

		$str .= "<a href=\"index.php?module=".$mod."\">".$modName."</a>";
		if ($row != ($num -1)) $str .= " --> ";	

	}

	return $str;

}

function showModLevel($path,$sort) 
{

	$newArray = array();

	if (!is_array($_SESSION["siteModList"]["owner_path"])) return false;
	
	if (!$sort) $sort = "module_name";

	//get the keys of all modules at this level
	$keys = array_keys($_SESSION["siteModList"]["owner_path"],$path);

	$fields = array_keys($_SESSION["siteModList"]);
	
	$arr = $_SESSION["siteModList"][$sort];
	asort($arr);
	
	$sortArray = array_keys($arr);
	$count = count($sortArray);
	$fieldCount = count($fields);
	
	for ($row=0;$row<$count;$row++) 
	{
	
		$key = $sortArray[$row];
		
		if (in_array($key,$keys)) 
		{
		
			for ($i=0;$i<$fieldCount;$i++) 
			{
				
				$field = $fields[$i];
				$newArray[$field][] = $_SESSION["siteModList"][$field][$key];
					
			}		
		
		}
		
	}

	return $newArray;

}

/************************************************************
	This function generates a page with all sub
	modules and their descriptions.
************************************************************/

function showModTableAlt($path,$sort = null,$altStyle,$decorStyle) {

	if (!$path) return false;

	$siteModList = showModLevel($path,$sort);

	$string = "<table border=0 width=100%>
			<tr>\n";

	$counter = "0";

	$num = count($siteModList["module_name"]);
	$cell = "0";
	
	for ($row=0;$row<$num;$row++) {

		if ($cell=="2") {
			$string .= "</tr>\n<tr>";
			$cell = "0";
		}
		
		$hide = null;

		if ($siteModList["permissions"][$row])
		{

		  $allow = false;
		  
		  foreach ($siteModList["permissions"][$row] AS $perm)
		  {
		    $allow = PERM::check(constant($perm));
		    if ($allow==true) break;
      }
      
      if ($allow==false) $hide = 1;

    }

		if ($siteModList["custom_perm"][$row])
		{

		  $allow = false;
		  
		  foreach ($siteModList["custom_perm"][$row] AS $perm)
		  {
		    $allow = PERM::check(constant($perm));
		    if ($allow==true) break;
      }
      
      if ($allow==false) $hide = 1;

    }

		if ($siteModList["hidden"][$row]==1) $hide=1;

		if (!$hide) {

			//show the translation for our module name if it exists
			$langmod = "_MT_".strtoupper($siteModList["link_name"][$row]);
			$langdesc = "_MTDESC_".strtoupper($siteModList["link_name"][$row]);

			//check for translations of the module name
			if (defined($langmod)) $modName = constant($langmod);
			else $modName = $siteModList["module_name"][$row];

			//check for translations of the module description
			if (defined($langdesc)) $modDesc = constant($langdesc);
			else $modDesc = $siteModList["module_description"][$row];

			$string .= "	<td width=50% valign=top style=\"padding-bottom:10px\">
					<div>
					<a $altStyle  
					href=\"index.php?module=".$siteModList["link_name"][$row]."\"
					>$decorStyle
					".$modName."
					</a>
					</div>
					<div style=\"width:80%;font: 1em Georgia,Arial,sans-serif;\">
					".$modDesc."
					</div>
					</td>\n";

			$cell++;

		}

		
	}

	$string .= "</tr></table>";

	return $string;
	
}

/**********************************************
	this function gets the top
	level parent of the current module
**********************************************/
function getTopLevelParent($module) {

	//for custom permissions, we get the owning permissions as well
	$tmp = $_SESSION["siteModInfo"][$module];

	//extract our parent module names from our current module path
	$arr = explode("/",$tmp["module_path"]);

	//get the 3rd entry in the array (so we skip "modules");
	return $arr[1];

}



//loads the module and returns the filenames used to 
//load the module itself
function loadModule($module) 
{

  //process our module permissions
  $arr = checkModPerm($module,BITSET);
  if (is_array($arr)) extract($arr);

  //process our custom permissions
  if (defined("CUSTOM_BITSET")) {
    $arr = checkCustomModPerm($module,CUSTOM_BITSET);
    if (is_array($arr)) extract($arr);
  }

  //start processing our module if all is well permission-wise
  if (!$permError) 
  {

    $modPath = $_SESSION["siteModInfo"][$module]["module_path"];
    $modStylesheet = null;
    $modJs = null;
    $modCss = null;

    //determine our process file and our display file
    $process_path = $modPath."process.php";
    $style_path = $modPath."stylesheet.css";
    $js_path = $modPath."javascript.js";
    $display_path = $modPath."display.php";
    $function_path = $modPath."function.php";
    $css_path = THEME_PATH."/modcss/".$module.".css";

    //return our above info for later processing
    $ret = array();
    $ret["module_name"] = $_SESSION["siteModInfo"][$module]["module_name"];
    $ret["link_name"] = $module;	//save this for later
    if (file_exists($process_path)) 	$ret["process"] = $process_path;
    if (file_exists($style_path)) 	$ret["style"] = $style_path;
    if (file_exists($js_path))		$ret["js"] = $js_path;
    if (file_exists($display_path))	$ret["display"] = $display_path;
    if (file_exists($function_path))	$ret["function"] = $function_path;
    if (file_exists($css_path))		$ret["css"] = $css_path;
    return $ret;

  } else return false;

}

function getTopModule($module)
{

  $modPath = $_SESSION["siteModInfo"][$module]["module_path"];

  $arr = explode("/",$modPath);
  
  //now remove the directory prefix ("/modules/center")
  $arr = array_slice($arr,1);

  return $arr[0];

}
