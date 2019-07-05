<?php

function createDateAddFilter($match,$value) 
{

	if (!$value) return false;
	
	if ($match=="on") {
		$filter = "create_date>='".dateProcess($value)." 00:00:00' AND create_date<='".dateProcess($value)." 23:59:59'";
	}
	else if ($match=="before") {
		$filter = "create_date<='".dateProcess($value)." 23:59:59'";
	}
	else if ($match=="after") {
		$filter = "create_date>='".dateProcess($value)." 00:00:00'";
	}
		
	return " SELECT id FROM docmgr.dm_object WHERE ".$filter;
	
}

function createDateModFilter($match,$value) 
{

	if (!$value) return false;
	
	if ($match=="on") {
		$filter = "status_date>='".dateProcess($value)." 00:00:00' AND status_date<='".dateProcess($value)." 23:59:59'";
	}
	else if ($match=="before") {
		$filter = "status_date<='".dateProcess($value)." 23:59:59'";
	}
	else if ($match=="after") {
		$filter = "status_date>='".dateProcess($value)." 00:00:00'";
	}
		
	return " SELECT id FROM docmgr.dm_object WHERE ".$filter;
	
}

function createAccountFilter($match,$value) 
{

	return "SELECT id FROM docmgr.dm_object WHERE object_owner='$value'";

}


function createCollectionFilter($colVal) 
{

	//make an array if we only have a singular column value
	if (is_array($colVal)) 
		$colArr = $colVal;
	else 
		$colArr = explode(",",$colVal);

	//merge all selected collections and their children into one array
	$num = count($colArr);
	$cv = $colArr;
	
	for ($i=0;$i<$num;$i++) 
	{
		$subs = DOCMGR_UTIL_COMMON::getChildCollections($cv[$i]);
		if ($subs) $colArr = array_merge($colArr,$subs);
	}
	
	$colArr = array_values(array_unique($colArr));

	$sql = "SELECT object_id AS id FROM docmgr.dm_object_parent WHERE parent_id IN (".implode(",",$colArr).")";
	return $sql;

}

function createObjectFilter($opt) {

	//get all our options
	extract($opt);

	//make an array if we only have a singular column value
	if (is_array($object_filter)) $str = implode(",",$object_filter);
	else $str = $object_filter;

	$sql = "SELECT id FROM docmgr.dm_object WHERE id IN (".$str.")";

	return $sql;

}

function createShareFilter($val)
{

	if ($val=="t")
	{
		$sql = "SELECT object_id AS id FROM docmgr.dm_share WHERE account_id='".USER_ID."'";
	}
	else if ($val=="f")
	{
		$sql = "SELECT id FROM docmgr.dm_object WHERE id NOT IN (SELECT object_id FROM docmgr.dm_share WHERE account_id='".USER_ID."')";
	}
	else
	{
		$sql = null;
	}

	return $sql;

}

function createTypeSql($string) {

	$arr = array();

	$pos = strpos($string,"objtype:");
	if ($pos!==FALSE) {
	
		$pos+=8;	//get past "objtype:"
		$str = substr($string,$pos);
		$pos2 = strpos($str," ");	//find the space after the type

		if ($pos2===FALSE) $type = $str;
		else $type = substr($str,0,$pos2);

		$arr["sql"] = "SELECT id FROM docmgr.dm_object WHERE object_type='$type';";

		//remove our object type from what we search for
		$string = str_replace("objtype:".$type,"",$string);
	
	}

	$arr["string"] = $string;

	return $arr;

}

function createObjectTypeFilter($match,$value) {

	if (!is_array($value)) $value = array($value);

	$sqlarr = array();

	foreach ($value AS $so) 
	{

		$sqlarr[] = " SELECT id FROM docmgr.dm_object WHERE object_type='".$so."'";
		
	}
	
	if (count($sqlarr)>0) {
	
		$sqlarr = array_values(array_unique($sqlarr));
		
		//if we have 4 different types, no filter
		if (count($sqlarr)==4) return false;
		else return "(".implode(" UNION ",$sqlarr).")";
	
	} else return false;

}

function execSearch($opt) {

	global $DB;

	//get all our options
	extract($opt);

	//store our criteria in an array
	$sqlArr = array();
	
	/***********************************************************
		process our search filters
	***********************************************************/
	for ($i=0;$i<count($filter);$i++)
	{

		if ($filter[$i]=="keyword") continue;
	
		if ($filter[$i]=="date_add") $func = "createDateAddFilter";
		elseif ($filter[$i]=="date_mod") $func = "createDateModFilter";
		elseif ($filter[$i]=="account") $func = "createAccountFilter";
		elseif ($filter[$i]=="object_type") $func = "createObjectTypeFilter";

		$ret = $func($match[$i],$value[$i]);
		if ($ret) $sqlArr[] = $ret;
		
	}

	//our keyword filters
	for ($i=0;$i<count($keywordFilter);$i++)
	{
		$ret = createKeywordFilter($keywordFilter[$i],$keywordMatch[$i],$keywordValue[$i]);
		if ($ret) $sqlArr[] = $ret;
	}


	//our collection filter
	if ($colfilter) 		$sqlArr[] = createCollectionFilter($colfilter);

	//limit to certain set of object ids filter
	if ($object_filter) $sqlArr[] = createObjectFilter($opt);

	//file shared w/ others filter
	if ($share_filter) 	$sqlArr[] = createShareFilter($share_filter);	

	//sql filter
	if ($sql_filter) $sqlArr[] = $sql_filter;
    
	//perm string filter
	if (!PERM::check(ADMIN))
	{

		$permStr = permString();

		//limit only to objects they can see		
		$sqlArr[] = "SELECT object_id AS id FROM docmgr.dm_object_perm WHERE ".$permStr;

		//limit the results to objects only in collections they can see
		$sqlArr[] = "SELECT object_id AS id FROM docmgr.dm_object_parent WHERE parent_id IN 
									(SELECT object_id FROM docmgr.dm_object_perm WHERE ".$permStr.")";

	}

	//dont' show hidden folders
	$sqlArr[] = "SELECT id FROM docmgr.dm_object WHERE hidden='f'";

	/************************************************
		put together our query string	
	************************************************/
	if ($string) 
	{
		$arr = createTsearch2Sql($opt);
		$sql = $arr["sql"];
		$rank = $arr["rank"];
	} else {
		$sql = "SELECT id FROM docmgr.dm_object";
		$sortField = "edit";
	}

	$sqlArr = arrayReduce($sqlArr);

	//merge our query arrays	
	if (count($sqlArr) > 0) {
		if (strstr($sql,"WHERE")) $sql .= " AND id IN (".implode(" INTERSECT ",$sqlArr).")";
		else $sql .= " WHERE id IN (".implode(" INTERSECT ",$sqlArr).")";
	}

	if (!$sortField) $sortField = "ts_rank";

	/********************************************************
		layout our sorting
	********************************************************/
		if ($sortField == "edit") $sortField = "last_modified";
		else if ($sortField == "size") $sortField = "filesize::numeric";
		else if ($sortField == "rank") $sortField = "ts_rank";
		
		//we do this to make sure nothing funky can be passed by the url
		if ($sortDir!="ASC" && $sortDir!="DESC") $sortDir = "ASC";

		if ($sortField=="rank") $sql .= " ORDER BY ".$rank." ".$sortDir;
		else $sql .= " ORDER BY ".$sortField." ".$sortDir;


	//are we paginating a search or starting over
	if ($use_last) 
		$reset = null;
	else 
		$reset = 1;

	$time1 = getmicrotime();

	//run our query to return ids only.  We'll query for details later
	if ($_SESSION["api"]["searchCount"]==null || $reset) 
	{

		$list = $DB->fetch($sql,1);
		$_SESSION["api"]["searchCount"] = $list["count"];

		$idArr = $list["id"];
		$_SESSION["api"]["searchResultArray"] = $idArr;

	}
	else $idArr = $_SESSION["api"]["searchResultArray"];

	//limit our array to the current page
	if (count($idArr) > 0) 
	{

		//merge in our file info
		$results = mergeFileInfo($idArr,$limit,$offset,$rank);

		//transpose our array to have only the currently displayed ids
		for ($i=0;$i<$results["count"];$i++) $curArr[] = $results[$i]["id"];

		//merge collection information
		$results = mergeCollectionInfo($results,$curArr);
		
		//merge the permission values in for these
		if (!PERM::check(ADMIN))
			$results = mergePermInfo($results,$curArr,$permStr);

		//merge in active discussion information
		$results = mergeDiscussionInfo($results,$curArr);

		//merge in related file information
		$results = mergeRelatedInfo($results,$curArr);
		
	} else 
	{
		$results = array();
		$results["count"] = 0;
	}
	
	$time2 = getmicrotime();
	$diff = $time2 - $time1;

	$diff = floatValue($diff,2);
	$results["timeCount"] = $diff;
	$results["searchCount"] = $_SESSION["api"]["searchCount"];
	
	return $results;

}

function createKeywordFilter($filter,$match,$value) 
{

	if (!$value) return false;

	$sql = "SELECT object_id AS id FROM docmgr.keyword_value WHERE ";
	
	$sql .= "(keyword_id='".$filter."' AND ";
		
	if ($match=="matches") $sql .= " keyword_value='".$value."' ";
	else if ($match=="contains") $sql .= " keyword_value LIKE '%".$value."%' ";		

	$sql .= ")";
		
	return $sql;
	
}

//this function pulls the file information for our ids
function mergeFileInfo($idArr,$limit,$offset,$rank) {

	global $DB;

	$fields = "id,name,summary,object_type,create_date,object_owner,status,status_date,status_owner,filesize,last_modified,level1,level2";
	if ($rank) $fields .= ",".$rank;
	
	//if limit and offset
	if ($limit != NULL && !$offset) $offset = "0";
	if ($limit !== NULL && $offset !== NULL) $idArr = array_slice($idArr,$offset,$limit);

	//now get the info for these files
	$sql = "SELECT DISTINCT $fields FROM docmgr.dm_view_full_search WHERE id IN (".implode(",",$idArr).")";
	$list = $DB->fetch($sql);

	//resort our results to match the original order of idArr if not using rank
	return sortSearchResults($idArr,$list);
		
}

//this pulls the owning collections for our ids
function mergeCollectionInfo($results,$curArr) 
{

	global $DB;
	
	//merge the collection values in for these
	$sql = "SELECT object_id,parent_id FROM docmgr.dm_object_parent WHERE object_id IN (".implode(",",$curArr).")";
	$colarr = $DB->fetch($sql,1);

	//merge our collection values in
	for ($i=0;$i<$results["count"];$i++) {
		$key = @array_search($results[$i]["id"],$colarr["object_id"]);
		$results[$i]["parent_id"] = $colarr["parent_id"][$key];
	}

	return $results;

}

//this pulls the owning collections for our ids
function mergeRelatedInfo($results,$curArr) 
{

	global $DB;
	
	//merge the collection values in for these
	$sql = "SELECT object_id,related_id,name,object_type FROM docmgr.dm_view_related WHERE object_id IN (".implode(",",$curArr).")";
	$colarr = $DB->fetch($sql,1);

	//merge our collection values in
	for ($i=0;$i<$results["count"];$i++) {
		$keys = @array_keys($colarr["object_id"],$results[$i]["id"]);
		if (count($keys) > 0) {
			$c = 0;
			foreach ($keys AS $key) {
				$results[$i]["related"][$c]["id"] = $colarr["related_id"][$key];			
				$results[$i]["related"][$c]["name"] = $colarr["name"][$key];			
				$results[$i]["related"][$c]["object_type"] = $colarr["object_type"][$key];			
				$c++;
			}
		}
	}

	return $results;

}

//this pulls the owning collections for our ids
function mergeDiscussionInfo($results,$curArr) 
{

	global $DB;
	
	//merge the collection values in for these
	$sql = "SELECT object_id FROM docmgr.dm_discussion WHERE object_id IN (".implode(",",$curArr).")";
	$discArr = $DB->fetch($sql,1);

	//merge our collection values in
	for ($i=0;$i<$results["count"];$i++) {
		$keys = @array_keys($discArr["object_id"],$results[$i]["id"]);
		$num = count($keys);
		if ($num > 0) $results[$i]["discussion"] = count($keys);
	}

	return $results;

}

//this pulls permission settings for our owning ids
function mergePermInfo($results,$curArr,$permStr) 
{

	global $DB;
	
	//the sort order will allow the highest permission to be set for an object and the user/group
	$sql = "SELECT object_id,bitmask FROM docmgr.dm_view_perm WHERE 
		object_id IN (".implode(",",$curArr).") AND (".$permStr.") ORDER BY object_id,bitset ASC";
	$permarr = $DB->fetch($sql,1);

	//merge our collection values in
	for ($i=0;$i<$results["count"];$i++) {
		$key = @array_search($results[$i]["id"],$permarr["object_id"]);
		$results[$i]["bitmask"] = $permarr["bitmask"][$key];
	}

	return $results;

}


function createTsearch2Sql($opt) {

	extract($opt);

	if (!$search_option || count($search_option)==0) $search_option = array("name","summary","content");

	//show ranks in the table
	$_SESSION["api"]["showRank"] = 1;

	//create our criteria for the query
	$criteria = null;
	$rank = null;

	//setup lexem
	if (count($search_option)==3) $lexeme = "@@";
	else $lexeme = "@@@";

	/********************************************
		create a search with wildcards
	*********************************************/
	if (strstr($string,"*")) {

		if (in_array("name",$search_option)) 
		$nameCriteria = "(".formatSqlString("name",$string).") OR ";

		if (in_array("summary",$search_option)) 
		$sumCriteria = "(".formatSqlString("summary",$string).") OR ";

		if (in_array("content",$search_option)) {

			$string = str_replace("%","",$string);

			//translate our string into the corresponding word ids
			$wordString = formatTsearch2String($string,array("content"));

			//there was an error processing the string, exit
			//if (!$wordString) return false;

			$contCriteria = "(idxfti ".$lexeme." to_tsquery('".TSEARCH2_PROFILE."','$wordString')) OR ";
			$rank = "ts_rank(idxfti,to_tsquery('".TSEARCH2_PROFILE."','$wordString'))";
		
		}

		$criteria = $nameCriteria.$sumCriteria.$contCriteria;
		$criteria = substr($criteria,0,strlen($criteria)-3);

		if ($rank) $fields = "id,$rank";
		else $fields = "id";

		$sql = "SELECT $fields FROM docmgr.dm_view_search WHERE $criteria "; 
		
	}
	/******************************************
		process our regular search
	******************************************/
	else 
	{

		//translate our string into the corresponding word ids
		$wordString = formatTsearch2String($string,$search_option);
		$criteria = "idxfti ".$lexeme." to_tsquery('".TSEARCH2_PROFILE."','$wordString') ";

		$rank = "ts_rank(idxfti,to_tsquery('".TSEARCH2_PROFILE."','$wordString'))";
		$sql = "SELECT id,$rank FROM docmgr.dm_view_search WHERE $criteria "; 

	}

	$arr = array();
	$arr["sql"] = $sql;
	$arr["rank"] = $rank;

	return $arr;

}

function removeDoubleSpaces($string) {

	while(strstr($string,"  ")) $string = str_replace("  "," ",$string);

	return $string;
}


function formatTsearch2String($string,$option) {

	$wordString = str_replace("*","",trim(strtolower($string)));

	$wordString = removeDoubleSpaces($wordString);

	$str = "[^".REGEXP_OPTION." ]";

	//remove anything not indexed, based on our config criteria
	$wordString = preg_replace("/".$str."/i","",$wordString);

	//remove any more doublespaces left from invalid content removal
	$wordString = removeDoubleSpaces($wordString);

	$wordString = str_replace(" and not ","&!",$wordString);
	$wordString = str_replace(" or not ","|!",$wordString);
	$wordString = str_replace(" and ","&",$wordString);
	$wordString = str_replace(" not ","&!",$wordString);	//we use AND NOT by default.  The user can always use OR NOT
	$wordString = str_replace(" or ","|",$wordString);
	$wordString = str_replace(" ","&",$wordString);

	//if our string is reduced to nothing, get out
	if (!strlen($wordString)) return false;

	//if file_name,summary, or content are checked, this appends weights to the end of our strings
	if (count($option)<3) 
	{

		$num = count($option);
		
		$arr = explode(" ",$wordString);
		$lnum = count($arr);
		$skip = array("and","or","not");

		for ($row=0;$row<$num;$row++) {

			$tempString = $wordString;

			$cur = $option[$row];

			if ($cur=="name") $weight = "A";
			elseif ($cur=="summary") $weight = "B";
			else $weight = "D";

			$tempString = "(".$tempString;

			for ($i=0;$i<$lnum;$i++) {

				if (in_array($arr[$i],$skip)) continue;

				$tempString = str_replace($arr[$i],$arr[$i].":".$weight,$tempString);

			}

			$tempString .= ")|";

			$finalString .= $tempString;
		}

		$wordString = substr($finalString,0,strlen($finalString)-1);

	}

	return $wordString;

}

function formatSqlString($field,$string) {

	$skipArray = array("and","or","not");

	$string = removeDoubleSpaces($string);

	$arr = explode(" ",$string);

	$st = 1;
	$join = null;
	
	for ($row=0;$row<count($arr);$row++) {

		if (!$arr[$row]) continue;

		if ($field=="idxtext") $comp = " LIKE ";
		else $comp = " ILIKE ";

		//if there is a wildcard, only place % where the * is, otherwise wrap the word with them
		if (strstr($arr[$row],"*")) $term = str_replace("*","%",$arr[$row]);
		else $term = "%".$arr[$row]."%";
		
		if (in_array($arr[$row],$skipArray)) {

			$st = null;
			$join = $arr[$row];
			if ($join=="not") {
				if ($arr[$row-1]=="or") $join = "or not";			
				else $join = "and not";
			}

		} else $st = 1;

		if ($st) {

			if ($st && (!$join && $row>0)) $join = " AND ";
		
			$str .= $join." ".$field." ".$comp." '".$term."' ";		
		
		}

	}

	return $str;

}


function sortSearchResults($idArr,$resArr) {

	$c = 0;
	$results = array();
	$results["count"] = $resArr["count"];
	
	foreach ($idArr AS $id) {

		for ($i=0;$i<$resArr["count"];$i++) {

			if ($resArr[$i]["id"]==$id) {
				$results[$c] = $resArr[$i];
				$c++;
				break;
			}
		}		
	
	}

	return $results;

}

function execCategory($data)
{

	global $conn,$DB;

	$sortField = "name";
	$sortDir = "ascending";

	$category_value = $data["parent_id"];

	//optional params
	if ($data["sort_field"]) $sortField = $data["sort_field"];
	if ($data["sort_dir"]) $sortDir = $data["sort_dir"];
	if ($data["limit"]) $limit = $data["limit"];
	if ($data["offset"]) $offset = $data["offset"];

	//dont use values stored in session
	if ($data["use_last"]) $reset = null;
	else $reset = 1;
	
	//setup the sort field
	if ($sortField == "edit") $sortField = "status_date";
	else if ($sortField == "size") $sortField = "filesize::numeric";
	else 
	{

		//if defined, sort by the defined browse group and name
		if (defined("BROWSE_GROUPBY")) 
			$sortField = BROWSE_GROUPBY." ASC,name";
		else
			$sortField = "name";
	}
	
	//we do this to make sure nothing funky can be passed by the url
	if ($sortDir!="DESC" && $sortDir!="ASC") $sortDir = "ASC";

	//make sure we have an offset
	if ($limit && !$offset) $offset = 0;

	if ($category_value!=null) 
	{

		//first get the count
		if (!$_SESSION["api"]["browseCount"] || $reset) 
		{

			$subquery = "SELECT id FROM docmgr.dm_view_objects WHERE hidden='f' AND parent_id='$category_value'";
			if (!PERM::check(ADMIN)) $subquery .= " AND ".permString();
			if ($filter) $subquery .= " AND object_type IN (".$filter.")";

			$sql = "SELECT id FROM docmgr.dm_object WHERE id IN (".$subquery.") ORDER BY ".$sortField." ".$sortDir;
			$ids = $DB->fetch($sql,1);
			$_SESSION["api"]["browseCount"] = $ids["count"];
			$_SESSION["api"]["browseId"] = $ids["id"];

		}

		if ($_SESSION["api"]["browseCount"]) 
		{

			//merge in our file info
			$results = mergeFileInfo($_SESSION["api"]["browseId"],$limit,$offset,null);

			//transpose our array to have only the currently displayed ids
			for ($i=0;$i<$results["count"];$i++) $curArr[] = $results[$i]["id"];

			//merge in discussion counts for each object
			$results = mergeDiscussionInfo($results,$curArr);

			//merge in related file information
			$results = mergeRelatedInfo($results,$curArr);

		} else 
		{
		
			$results["count"] = "0";
			
		}

		$results["searchCount"] = $_SESSION["api"]["browseCount"];

		return $results;

	}

	return false;

}

