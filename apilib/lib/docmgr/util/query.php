<?php

class DOCMGR_UTIL_QUERY
{

	private $DB;
	private $results;
	private $sqlFilter;
	private $searchLimit;
	private $searchOffset;
	private $sortField;
	private $sortDirection;
	private $sqlRank;

	function __construct()
	{
		$this->DB = $GLOBALS["DB"];
	}

	public function search($opt)
	{

		//get all our options
		extract($opt);

		//make available everywhere
		$this->sortField = $sort_field;
		$this->sortDirection = $sort_dir;
		$this->searchLimit = $search_limit;
		$this->searchOffset = $search_offset;
	
		//store our criteria in an array
		$sqlArr = array();
		
		/***********************************************************
			process our search filters
		***********************************************************/
		for ($i=0;$i<count($filter);$i++)
		{
	
			//passed a keyword filter
			if (strstr($filter[$i],"keyword_"))
			{
				$this->filterKeyword($filter[$i],$match[$i],$value[$i],$data_type[$i]);			
			}
			else
			{

				//figure out which filter to pass to
				if ($filter[$i]=="name") $func = "filterName";
				elseif ($filter[$i]=="summary") $func = "filterSummary";
				elseif ($filter[$i]=="content") $func = "filterContent";
				elseif ($filter[$i]=="date_created") $func = "filterDateCreated";
				elseif ($filter[$i]=="date_modified") $func = "filterDateModified";
				elseif ($filter[$i]=="account") $func = "filterAccount";
				elseif ($filter[$i]=="object_type") $func = "filterObjectType";
				elseif ($filter[$i]=="size") $func = "filterSize";

				//call it	
				$ret = $this->$func($match[$i],$value[$i]);
				if ($ret) $sqlArr[] = $ret;
			
			}
	
		}
	
		//our collection filter
		if ($colfilter) 		$sqlArr[] = $this->filterCollection($colfilter);
	
		//limit to certain set of object ids filter
		if ($object_filter) $sqlArr[] = $this->filterObject($opt);
	
		//shared w/ me filter
		if ($account_shared_with) 	$sqlArr[] = $this->accountSharedWith();

		if ($account_shared_by) 	$sqlArr[] = $this->accountSharedBy();

		//my subscriptions filter
		if ($account_subscribed) 	$sqlArr[] = $this->accountSubscribed();
	
		//sql filter
		if ($sql_filter) $sqlArr[] = $sql_filter;

		//perm string filter
		if (!PERM::check(ADMIN))
		{
			//limit only to objects they can see		
			$sqlArr[] = "SELECT object_id AS id FROM docmgr.dm_object_perm WHERE ".permString();
		}
	
		//dont' show hidden folders
		$sqlArr[] = "SELECT id FROM docmgr.dm_object WHERE hidden='f'";
		
		//setup for our query
		$runquery = true;
		$idArr = array();
		$time1 = getmicrotime();

		//create our sql filter and make available everywhere
		$this->sqlFilter = implode(" INTERSECT ",arrayReduce($sqlArr));
		$this->sqlRank = null;
		
		/************************************************
			put together our query string	
		************************************************/
		if ($search_string) 
		{
	
			//if using grouping and they haven't finished the last ")", bail
			$bcount = substr_count($search_string,"(");
			$ecount = substr_count($search_string,")");
			
			if ($bcount!=$ecount) $runquery = false;
			else
			{
			
				//setup our sql query.  this will add the tsearch2 piece to sqlFilter
				$this->tsearch2($opt);

				//some default sorting
				if (!$this->sortField) 
				{
					$this->sortField = "rank";
					$this->sortDirection = "DESC";
				}
				
				//setup our count sql statement for total results
				$countSql = "SELECT count(id) AS total_count FROM (".$this->sqlFilter.") AS id";
					
			}
			
		} 
		//no query string, just return all matches
		else 
		{

			//setup our count sql statement for total results
			$countSql = "SELECT count(id) AS total_count FROM (".$this->sqlFilter.") AS id";

			//setup a default sort pattern, and override rank because it won't work w/o a search string
			if (!$this->sortField || $this->sortField=="rank") 
			{
				$this->sortField = "name";
				$this->sortDirection = "ASC";
			}
			
		}

		//got the go ahead, run the search
		if ($runquery==true)
		{

			//are we paginating a search or starting over
			if ($use_last) 
				$reset = null;
			else 
				$reset = 1;

			//run our query to return ids only.  We'll query for details later
			if ($_SESSION["api"]["search_total_count"]==null || $reset) 
			{
				
				//finally, run the query		
				$total = $this->DB->single($countSql);

				$_SESSION["api"]["search_total_count"] = $total["total_count"];
		
			}

			//limit our array to the current page
			if ($_SESSION["api"]["search_total_count"] > 0)
			{
		
				//merge in our file info
				$this->mergeFile();
		
				//merge collection information
				$this->mergeCollection();
				
				//merge the permission values in for these
				if (!PERM::check(ADMIN)) $this->mergePerm();
		
				//merge in active discussion information
				$this->mergeDiscussion();
		
				//merge in related file information
				//$this->mergeRelated($curArr);
				
			} 

			//add permissions
			DOCMGR_UTIL_OBJPERM::addToObject($this->results);

			//add lock info
			$l = new DOCMGR_UTIL_LOCK();
			$l->addToObject($this->results);
	
		}

		//gracefully return "no results"
		if ($runquery==false)
		{
			$this->results = array("count"=>"0");
		}
	
		//how long did it take?		
		$time2 = getmicrotime();
		$diff = $time2 - $time1;
	
		$diff = floatValue($diff,2);
		$this->results["search_time"] = $diff;
	
		//store result count
		$this->results["total_count"] = $_SESSION["api"]["search_total_count"];

		//send it back	
		return $this->results;
	
	}

	/**
		results all objects under the passed parent_id value
		*/
	function browse($data)
	{

		if ($data["object_id"]==null) return false;
	
		$this->sortField = "name";
		$this->sortName = "ascending";
	
		//optional params
		if ($data["sort_field"]) 					$this->sortField = $data["sort_field"];
		if ($data["sort_dir"]) 						$this->sortDirection = $data["sort_dir"];
		if ($data["search_limit"]!=null) 	$this->searchLimit = $data["search_limit"];
		if ($data["search_offset"]!=null) $this->searchOffset = $data["search_offset"];
	
		//dont use values stored in session
		if ($data["use_last"]) $reset = null;
		else $reset = 1;
		
		//setup the sort field
		if ($sort_field == "edit") $sort_field = "last_modified";
		else if ($sort_field == "size") $sort_field = "size";
		else 
		{
	
			//if defined, sort by the defined browse group and name
			if (defined("BROWSE_GROUPBY")) 
				$sort_field = BROWSE_GROUPBY." ASC,name";
			else
				$sort_field = "name";
		}
		
		//we do this to make sure nothing funky can be passed by the url
		if ($sort_dir!="DESC" && $sort_dir!="ASC") $sort_dir = "ASC";
	
		//first get the count
		if (!$_SESSION["api"]["browse_total_count"] || $reset) 
		{

			//setup our base filter.  always used
			$this->sqlFilter = "SELECT id FROM docmgr.dm_object WHERE hidden='f'
													INTERSECT
													SELECT object_id FROM docmgr.dm_object_parent WHERE parent_id='".$data["object_id"]."'";

			//not admin, throw in permission limits
			if (!PERM::check(ADMIN))
			{
				$this->sqlFilter .= " INTERSECT SELECT object_id AS id FROM docmgr.dm_object_perm WHERE ".permString();
			}

			//there's an object_type filter			
			if ($filter) 
			{
				$this->sqlFilter .= " INTERSECT SELECT id FROM docmgr.dm_object WHERE object_type IN (".$filter.")";
			}
			
			//setup our count sql statement for total results
			$countSql = "SELECT count(id) AS total_count FROM (".$this->sqlFilter.") AS id";

			//run the query
			$results = $this->DB->single($countSql);

			//store our count
			$_SESSION["api"]["browse_total_count"] = $results["total_count"];

		}

		//if we have results, expand information for the desired subsection
		if ($_SESSION["api"]["browse_total_count"]) 
		{

			//merge in our file info
			$this->mergeFile();

			//merge in discussion counts for each object
			if ($this->results["count"] > 0) $this->mergeDiscussion();

			//add permissions
			DOCMGR_UTIL_OBJPERM::addToObject($this->results);

			//add lock info
			$l = new DOCMGR_UTIL_LOCK();
			$l->addToObject($this->results);

		} 
		else 
		{
			$this->results["count"] = "0";
		}

		$this->results["total_count"] = $_SESSION["api"]["browse_total_count"];

		return $this->results;
	
	}

	/*********************************
		private search string builders
	*********************************/
	
	private function tsearch2($opt) 
	{
	
		extract($opt);
	
		if (!$search_option || count($search_option)==0) $search_option = array("name","summary","content");
	
		//create our criteria for the query
		$criteria = null;
		$this->sqlRank = null;
	
		//setup lexem
		if (count($search_option)==3) $lexeme = "@@";
		else $lexeme = "@@@";
	
		/********************************************************************************
			create a search with wildcards.  the * is a trigger for promiscuous searching
		********************************************************************************/
		if ($this->isSuffixSearch($search_string))
		{
	
			if (in_array("name",$search_option)) 
				$nameCriteria = "(".$this->formatSqlString("name",$search_string).") OR ";
	
			if (in_array("summary",$search_option)) 
				$sumCriteria = "(".$this->formatSqlString("summary",$search_string).") OR ";
	
			if (in_array("content",$search_option)) 
			{
	
				//remove grouping if passed.  
				$search_string = str_replace(array("(",")"),"",$search_string);
	
				//translate our string into the corresponding word ids
				$wordString = $this->formatTSearchString($search_string,array("content"));
	
				$contCriteria = "(idxfti ".$lexeme." to_tsquery('".TSEARCH2_PROFILE."','$wordString')) OR ";
				$this->sqlRank = "ts_rank(idxfti,to_tsquery('".TSEARCH2_PROFILE."','$wordString'))";
			
			}
	
			$criteria = $nameCriteria.$sumCriteria.$contCriteria;
			$criteria = substr($criteria,0,strlen($criteria)-3);
	
			//if passed a number, maybe it's an object id
			if (is_numeric(trim($search_string))) 
			{
				$criteria .= " OR id='".$search_string."' ";
			}
	
			$filter = "SELECT id FROM docmgr.dm_view_search WHERE $criteria "; 
	
		}
		/******************************************
			process our regular search
		******************************************/
		else 
		{
	
			//translate our string into the corresponding word ids
			$wordString = $this->formatTSearchString($search_string,$search_option);
			$criteria = "idxfti ".$lexeme." to_tsquery('".TSEARCH2_PROFILE."','$wordString') ";
	
			//if passed a number, maybe it's an object id
			if (is_numeric(trim($search_string))) 
			{
				$criteria .= " OR object_id='".$search_string."' ";
			}
	
			$this->sqlRank = "ts_rank(idxfti,to_tsquery('".TSEARCH2_PROFILE."','$wordString'))";
			$filter = "SELECT object_id AS id FROM docmgr.dm_index WHERE $criteria "; 
	
		}
	
		$this->sqlFilter .= " INTERSECT ".$filter;

	}
	
	private function removeDoubleSpaces($string) 
	{
	
		while(strstr($string,"  ")) $string = str_replace("  "," ",$string);
	
		return $string;

	}
	
	private function isSuffixSearch($str)
	{
	
		$ret = false;
		$arr = explode(" ",trim($str));
		$num = count($arr);
		
		for ($i=0;$i<$num;$i++)
		{
	
			//if the word begins w/ a wildcard, it will be a suffix search
			if ($arr[$i][0]=="*")
			{
				$ret = true;
				break;
			}
			
		}
	
		return $ret;
		
	}
	
	private function formatTSearchString($string,$option) 
	{
	
		$wordString = trim(strtolower($string));
	
		$regstr = "[^".REGEXP_OPTION." ]";
	
		//remove anything not indexed, based on our config criteria
		$wordString = preg_replace("/".$regstr."\*/iu","",$wordString);
	
		//remove any more doublespaces left from invalid content removal
		$wordString = $this->removeDoubleSpaces($wordString);
		$saveString = $wordString;
	
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
			
			//$arr = explode(" ",trim(ereg_replace("[^".REGEXP_OPTION." ]","",$string)));
			$arr = explode(" ",$saveString);
			$lnum = count($arr);
			$skip = array("and","or","not");
	
			//loop through our search options to add the weight filters
			for ($row=0;$row<$num;$row++) 
			{
	
				$cur = $option[$row];
	
				if ($cur=="name") $weight = "A";
				elseif ($cur=="summary") $weight = "B";
				else $weight = "D";
	
				$tempString = "(".$wordString;
	
				//loop through our search terms
				for ($i=0;$i<$lnum;$i++) 
				{
	
					if (in_array($arr[$i],$skip)) continue;
	
					//if a wildcard, reformat and move the wildcard to the weighting
					if (strstr($arr[$i],"*"))
					{
						$repstring = str_replace("*","",$arr[$i]);
						$searchweight = $weight."*";
					}
					else 
					{
						$repstring = $arr[$i];
						$searchweight = $weight;
					}
					
					$tempString = str_replace($arr[$i],$repstring.":".$searchweight,$tempString);
	
				}
	
				$tempString .= ")|";
	
				$finalString .= $tempString;
	
			}
	
			$wordString = substr($finalString,0,strlen($finalString)-1);
	
		}
		//handle searching of all weights
		else
		{
	
			//a wildcard was passed
			if (strstr($saveString,"*"))
			{
	
				$arr = explode(" ",$saveString);
	
				foreach ($arr AS $word)
				{
					//handle wildcard searching
					if (strstr($word,"*")) 
					{
						//remove the asterisk and put it in proper tsearch2 prefix search format
						$newword = str_replace("*","",$word);
						$wordString = str_replace($word,$newword.":*",$wordString);
					}
					
				}
			
			}
	
		}
		
		return $wordString;
	
	}
	
	private function formatSqlString($field,$string) 
	{
	
		$wordString = trim(strtolower($string));
	
		$regstr = "[^".REGEXP_OPTION." ]";
	
		$wordString = preg_replace("/".$regstr."\*/iu","",$wordString);
	
		//remove any () in case they grouped thier search
		$wordString = str_replace(array("(",")"),"",$wordString);
		$wordString = str_replace("*","%",$wordString);
	
		//remove double spaces from our string cleanup
		$wordString = $this->removeDoubleSpaces($wordString);
	
		$skipArray = array("and","or","not");
		$arr = explode(" ",$wordString);
	
		$st = 1;
		$join = null;
		$num = count($arr);
		
		for ($i=0;$i<$num;$i++)
		{
		
			$term = &$arr[$i];
			
			if (in_array($term,$skipArray)) 
			{
	
				$st = null;
				$join = $term;
				if ($join=="not") {
					if ($arr[$i-1]=="or") $join = "or not";			
					else $join = "and not";
				}
	
			} else $st = 1;
	
			//we're on search terms now, add to the query using our predetermined join phrases
			if ($st) 
			{
	
				//if there is a wildcard, only place % where the * is, otherwise wrap the word with them
				if (!strstr($term,"%")) $term = "%".$term."%";
	
				if (!$join && $i>0) $join = " AND ";
			
				$str .= $join." lower(".$field.") LIKE '".strtolower($term)."' ";		
			
			}
	
		}
	
		return $str;
	
	}

	private function filterName($match,$value)
	{

		$sql = "SELECT id AS object_id FROM docmgr.dm_object WHERE ";

		if ($match=="contain")
		{
			$sql .= "lower(name) LIKE '%".$value."%'";
		}
		else if ($match=="notcontain")
		{
			$sql .= "lower(name) IS NOT LIKE '%".$value."%'";
		}
		else if ($match=="equal")
		{
			$sql .= "lower(name)='".$value."'";
		}
		else if ($match=="notequal")
		{
			$sql .= "lower(name)!='".$value."'";
		}

		return $sql;
		
	}

	private function filterSummary($match,$value)
	{

		$sql = "SELECT id AS object_id FROM docmgr.dm_object WHERE ";

		if ($match=="contain")
		{
			$sql .= "lower(summary) LIKE '%".$value."%'";
		}
		else if ($match=="notcontain")
		{
			$sql .= "lower(summary) IS NOT LIKE '%".$value."%'";
		}
		else if ($match=="equal")
		{
			$sql .= "lower(summary)='".$value."'";
		}
		else if ($match=="notequal")
		{
			$sql .= "lower(summary)!='".$value."'";
		}

		return $sql;
		
	}
	
	private function filterContent($match,$value)
	{

		//translate our string into the corresponding word ids
		$wordString = $this->formatTSearchString($value,array("content"));
	
		$criteria = "(idxfti @@ to_tsquery('".TSEARCH2_PROFILE."','$wordString'))";

		$sql = "SELECT object_id FROM docmgr.dm_index WHERE ".$criteria;

		return $sql;
		
	}

	private function filterKeyword($filter,$match,$value,$dataType) 
	{
	
		if (!$value) return false;

		//strip out "keyword_" from the filter name
		$filter = str_replace("keyword_","",$filter);
	
		$sql = "SELECT object_id AS id FROM docmgr.keyword_value WHERE ";
		
		$sql .= "(keyword_id='".$filter."' AND ";
			
		if ($dataType=="date")
		{

			$value = dateProcess($value);

			if ($match=="before")
			{
				$sql .= "keyword_value::date<='".$value." 23:59:59'";
			}
			else if ($match=="after")
			{
				$sql .= "keyword_value::date>='".$value." 00:00:00'";
			}
			else
			{
				$sql .= " keyword_value::date='".$value."'";
			}
		
		}
		else if ($dataType=="number")
		{

			if ($match=="greaterequal")
			{
				$sql .= "keyword_value::float>='".$value."' ";
			}
			else if ($match=="lesserequal")
			{
				$sql .= "keyword_value::float<='".$value."' ";
			}
			else
			{
				$sql .= " keyword_value='".$value."' ";
			}
		
		}
		else
		{

			if ($match=="contain")
			{
				$sql .= " lower(keyword_value) LIKE '%".strtolower($value)."%' ";		
			}
			else
			{
				$sql .= " keyword_value='".$value."' ";
			}
					
		}	
			
		$sql .= ")";
			
		return $sql;
		
	}
	
	//this function pulls the file information for our ids
	private function mergeFile()
	{

		//if there's a rank, tack it into our query	
		$fields = "id,name,summary,object_type,create_date,object_owner,status,last_modified,status_owner,size,last_modified,level1,level2";
		if ($this->sqlRank) $fields .= ",".$this->sqlRank;
		
		//run the query
		$sql = "SELECT $fields FROM docmgr.dm_view_full_search WHERE id IN (".$this->sqlFilter.")";

		/********************************************************
			layout our sorting
		********************************************************/
		$field = "name";
		$direction = "ASC";

		if ($this->sortField == "edit") 			$field = "last_modified";
		else if ($this->sortField == "size") 	$field = "size";
		else if ($this->sortField == "name")	$field = "lower(name)";
		else if ($this->sortField == "rank") 	$field = $this->sqlRank;
	
		//we do this to make sure nothing funky can be passed by the url
		if ($this->sortDirection) $direction = $this->sortDirection;

		//tack on our sorting and limiting to the end of the sql statement	
		$sql .= " ORDER BY ".$field." ".$direction;
		
		//throw in our limit if passed.  We may need to make it so there's always one
		if ($this->searchLimit) $sql .= " LIMIT ".$this->searchLimit." OFFSET ".$this->searchOffset;

		//run the query
		$this->results = $this->DB->fetch($sql);

	}
	
	//this pulls the owning collections for our ids
	private function mergeCollection() 
	{
	
		$curArr = array();
		for ($i=0;$i<$this->results["count"];$i++) $curArr[] = $this->results[$i]["id"];
	
		//merge the collection values in for these
		$sql = "SELECT object_id,parent_id FROM docmgr.dm_object_parent WHERE object_id IN (".implode(",",$curArr).")";
		$colarr = $this->DB->fetch($sql,1);
	
		//merge our collection values in
		for ($i=0;$i<$this->results["count"];$i++) 
		{
			$key = @array_search($this->results[$i]["id"],$colarr["object_id"]);
			$this->results[$i]["parent_id"] = $colarr["parent_id"][$key];
		}
	
	}
	
	//this pulls the owning collections for our ids
	private function mergeDiscussion() 
	{

		$curArr = array();
		for ($i=0;$i<$this->results["count"];$i++) $curArr[] = $this->results[$i]["id"];
	
		//merge the collection values in for these
		$sql = "SELECT object_id FROM docmgr.dm_discussion WHERE object_id IN (".implode(",",$curArr).")";
		$discArr = $this->DB->fetch($sql,1);
	
		//merge our collection values in
		for ($i=0;$i<$this->results["count"];$i++) 
		{
			$keys = @array_keys($discArr["object_id"],$this->results[$i]["id"]);
			$num = count($keys);
			if ($num > 0) $this->results[$i]["discussion"] = count($keys);
		}
	
	}
	
	//this pulls permission settings for our owning ids
	private function mergePerm() 
	{

		$curArr = array();
		for ($i=0;$i<$this->results["count"];$i++) $curArr[] = $this->results[$i]["id"];
	
		//the sort order will allow the highest permission to be set for an object and the user/group
		$sql = "SELECT object_id,bitmask FROM docmgr.dm_view_perm WHERE 
			object_id IN (".implode(",",$curArr).") AND (".permString().") ORDER BY object_id,bitset ASC";
		$permarr = $this->DB->fetch($sql,1);
	
		//merge our collection values in
		for ($i=0;$i<$this->results["count"];$i++) 
		{
			$key = @array_search($this->results[$i]["id"],$permarr["object_id"]);
			$this->results[$i]["bitmask"] = $permarr["bitmask"][$key];
		}
	
	}
	

	/**
		filter by the date the object was added to the system
		*/
	private function filterDateCreated($match,$value) 
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

	private function filterDateModified($match,$value) 
	{

		if (!$value) return false;
	
		if ($match=="on") 
		{
			$filter = "last_modified>='".dateProcess($value)." 00:00:00' AND last_modified<='".dateProcess($value)." 23:59:59'";
		}
		else if ($match=="before") 
		{
			$filter = "last_modified<='".dateProcess($value)." 23:59:59'";
		}
		else if ($match=="after") 
		{
			$filter = "last_modified>='".dateProcess($value)." 00:00:00'";
		}
		
		return " SELECT id FROM docmgr.dm_object WHERE ".$filter;
	
	}

	private function filterAccount($match,$value) 
	{
		return "SELECT id FROM docmgr.dm_object WHERE object_owner='$value'";
	}

	private function filterSize($match,$value) 
	{
		if ($match=="greaterthan") $op = ">=";
		else if ($match=="lessthan") $op = "<=";
		else $op = "=";

		return "SELECT id FROM docmgr.dm_object WHERE size ".$op." '".$value."'";

	}

	private function filterCollection($value)
	{

		//make an array if we only have a singular column value
		if (!is_array($value)) $value = explode(",",$value);

		//merge all selected collections and their children into one array
		$num = count($value);
		$cv = $value;
			
		for ($i=0;$i<$num;$i++) 
		{
			$subs = DOCMGR_UTIL_COMMON::getChildCollections($cv[$i]);
			if ($subs) $value = array_merge($value,$subs);
		}
	
		$value = array_values(array_unique($value));

		$sql = "SELECT object_id AS id FROM docmgr.dm_object_parent WHERE parent_id IN (".implode(",",$value).")";
		return $sql;

	}

	private function filterObject($opt)
	{

		//get all our options
		extract($opt);

		//make an array if we only have a singular column value
		if (is_array($object_filter)) $str = implode(",",$object_filter);
		else $str = $object_filter;

		$sql = "SELECT id FROM docmgr.dm_object WHERE id IN (".$str.")";

		return $sql;

	}

	private function filterObjectType($match,$value)
	{
		return "SELECT id FROM docmgr.dm_object WHERE object_type='".$value."'";
	}

	private function accountSharedWith() 
	{

		$sql = "SELECT object_id AS id FROM docmgr.dm_share WHERE share_account_id='".USER_ID."'";

		return $sql;

	}	

	private function accountSharedBy() 
	{

		$sql = "SELECT object_id AS id FROM docmgr.dm_share WHERE account_id='".USER_ID."'";

		return $sql;

	}	

	private function accountSubscribed()
	{

		$sql = "SELECT object_id FROM docmgr.subscriptions WHERE account_id='".USER_ID."' AND
										(	locked='t' OR 
											unlocked='t' OR 
											removed='t' OR 
											created='t' OR 
											comment_posted='t'
										)
										";

		return $sql;

	}	

}

