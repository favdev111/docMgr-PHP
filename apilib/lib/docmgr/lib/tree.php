<?php

function showColData($catInfo,$keys,$showarr,$parentPath,$permInfo=null)
{

  global $PROTO;
  $arr = array();
  
  if (!$keys) return false;

  foreach ($keys AS $childKey) 
  {

    $subkeys = array_keys($catInfo["parent_id"],$catInfo["id"][$childKey]);
    $childnum = count($subkeys);
    $data = array();
    
    //if passed permInfo, make sure this is a collection the user can see
    if ($permInfo)
    {

      $key = array_search($catInfo["id"][$childKey],$permInfo["id"]);

      if ($key!==FALSE)
      {
        $data["id"] = $catInfo["id"][$childKey];
        $data["name"] = $catInfo["name"][$childKey];
        $data["object_type"] = $catInfo["object_type"][$childKey];
      }
      
    }
    else
    {
    
      $data["id"] = $catInfo["id"][$childKey];
      $data["name"] = $catInfo["name"][$childKey];
      $data["object_type"] = $catInfo["object_type"][$childKey];
    
    }

    //user doesn't have permissions to see anything, carry on
    if (count($data)==0) continue;

    //append the name to the parent path to store later
    if ($parentPath=="/") $path = "/".$data["name"];
    else $path = $parentPath."/".$data["name"];

    //first, get the info for this file
    $data["child_count"] = $childnum;
    $data["path"] = $path;

    //if in the expansion array show the children
    if (@in_array($catInfo["id"][$childKey],$showarr)) 
    {

      $children = reduceArray(showColData($catInfo,$subkeys,$showarr,$path,$permInfo));
      if (count($children) > 0) $data["collection"] = $children;

    }
    
    $arr[] = $data;

  }

  return $arr;

}


function loadBaseCollections($curValue,$root=0,$path="/",$showSearch=null) 
{

  global $DB,$PROTO;

  if (!PERM::check(ADMIN)) $ps = " AND ".permString();
  else $ps = null;

  if ($showSearch) $table = "dm_view_colsearch";
  else $table = "dm_view_collections";

  //get all collections that need to be displayed
  $sql = "SELECT DISTINCT id,name,parent_id,object_type FROM docmgr.".$table."
                    WHERE hidden='f' ORDER BY name ";
  $catInfo = $DB->fetch($sql,1);

  //get the collections we are allowed to see
  if (!PERM::check(ADMIN))
  {
  
    //get all collections that need to be displayed
    $sql = "SELECT DISTINCT id,name,parent_id,object_type FROM docmgr.".$table."
                    WHERE hidden='f' AND ".permString()." ORDER BY name ";
    $permInfo = $DB->fetch($sql,1);
    
  } else $permInfo = null;

  $arr = array();

  if ($curValue) 
  {

    if (!is_array($curValue)) $curValue = explode(",",$curValue);

    $num = count($curValue);

    for ($i=0;$i<$num;$i++) 
    {
      if ($curValue[$i]!=0) $arr = array_merge($arr,returnCatOwner($catInfo,$curValue[$i],null));
    }
    
    $arr = array_values(array_unique($arr));

  }

  //get the parent of the current, then pass it on to return info for all files
  //which are on the same level.  we also pass our value array so we can 
  //decent to the next level at a hit
  if ($catInfo["count"] > 0) 
  {

    $keys = array_keys($catInfo["parent_id"],$root);

    $data = arrayReduce(showColData($catInfo,$keys,$arr,$path,$permInfo));

    if (count($data) > 0)
      foreach ($data AS $col) $PROTO->add("collection",$col);

  }

}

