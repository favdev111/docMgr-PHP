<?php
/******************************************************************
	FILENAME:	arrays.php
	PURPOSE:	contains all special array handling functions

	Modified 04-18-2005.
		Added arrayStringSearch function

	Modified 04-20-2005
		added reduceArray function

	Modified 09-12-2005
		Modified reduceArray to handle associative arrays

	Modified 06-22-2007
		Cleaned up some old functions that aren't used anymore or
		are just plain stupid, and merged in arrayCombine from 
		the contract system		
******************************************************************/

/********************************************************************
	FUNCTION:	cs_in_array
	PURPOSE:	does a case insensitive search to see if the string
						exists in the array
	INPUTS:		string -> string we are searching for
						object -> array we are searching in
	RETURNS:	string on success, false on failure
********************************************************************/

function cs_in_array($string,$object) {

	$string = strtolower($string);

	$returnValue = null;

	for ($row=0;$row<count($object);$row++) {

		$tempValue = strtolower($object[$row]);

		if ($tempValue == $string) {
		  $returnValue = $tempValue;  
		  break;
    }
    
	}

	if ($returnValue) return $returnValue;
	else return false;

}

/********************************************************************
	FUNCTION:	multi_array_unique
	PURPOSE:	returns all unique values between two 2-dimensional arrays
	INPUTS:		array1 -> first array to check
						array2 -> second array to check
	RETURNS:	array
********************************************************************/

function multi_array_unique($array1,$array2) {

	$newArray1 = array();
	$newArray2 = array();

	for ($row=0;$row<count($array1);$row++) {

		if (!in_array($array1[$row],$newArray1)) {

			$newArray1[] = $array1[$row];
			$newArray2[] = $array2[$row];

		}

	}

	return array($newArray1,$newArray2);

}


//creates an array out of one array containing
//keys, and another containing array values.
//should be replaced by array_combine in php5

if (!function_exists("array_combine")) {

	function array_combine($keyArray,$valArray) {

		return arrayCombine($keyArray,$valArray);

	}
}

/**************************************************************************
	FUNCTION:  arrayCombine
	PURPOSE:   creates a new array using the passed
					   keys and their associated values
	RETURNS:   numerically keyed array
	INPUT:     keyArray -> array of keys we will be using to pull values
				     valueArray -> the array we're pulling our associated values from
	MODIFIED:  06/22/2007 -> merged in ArrayCombine from the contract system
**************************************************************************/
function arrayCombine($keyArray,$valArray) {

	//sanity checking
	if (!is_array($keyArray)) return null;
	if (!is_array($valArray)) return null;
	$keyArray = array_values($keyArray);
	$valArray = array_values($valArray);
		
	$arr = array();
	$num = count($keyArray);

	for ($row=0;$row<$num;$row++) {
		$key = $keyArray[$row];
		$arr[] = $valArray[$key];
	}

	return $arr;

}

/**************************************************************************
	FUNCTION:  transposeArray
	PURPOSE:   returns an array with the key/value pairing reversed
	RETURNS:   array
	INPUT:     array
**************************************************************************/

function transposeArray($arr) {

	if (!is_array($arr) || count($arr)==0) return array();

	foreach ($arr AS $keymaster => $value) {
                
		foreach($value AS $key => $element) {
			$returnArray[$key][$keymaster] = $element;
		}                 

	}

	return $returnArray;                                                        
}


/******************************************************
	FUNCTION: 	arrayMultiSort
	PURPOSE:		Sort an associative array by a key in that 
							array
	INPUTS:			modArray -> the array we are sorting
							sort -> the key we sort by
							dir -> direction we sort by (ASC or DESC)
	RETURNS:		sorted array
******************************************************/
function arrayMultiSort($modArray,$sort,$dir="ASC") {

	$newArray = array();

	//parameter sanity checking
	if (!$sort) return false;
	if (!is_array($modArray)) return false;

	//sort by our sort field
	$arr = $modArray[$sort];

	if (!is_array($arr)) return false;

	//get the keys in the array.  We will use these as field names later
	$fields = array_keys($modArray);

	//we will assume the first key in the array is the index key.  That means
	//there should be one of these in every array entry.  This way, if some of
	//our sort fields are empty, we can pad the sort array to the required length
	$idx = $fields[0];
	$realSize = count($modArray[$idx]);

	//sort our array 
	if ($dir=="DESC") arsort($arr);
	else asort($arr);

	//the size of our sorted field
	$sortSize = count($arr);

	//if our sort size is smaller than our actual size, pad to the real size so we don't lose any modules.
	//the value we pad it with should ensure it gets placed last whether it's a numeric or alpha sort
	if ($sortSize < $realSize) {

		//pad with something that should put the entry at the end whether it's number or alpha
		$pad = "zzzzzzzz";

		//loop through the array and pad the empty values.  We can't use array_pad because
		//we will lose our key order
		for ($row=0;$row<$realSize;$row++) if (!$arr[$row]) $arr[$row] = $pad;

	}

	//get all the keys from our sorted array.  We need to loop by these to maintain
	//index association with the rest of the array
	$sortArray = array_keys($arr);
	$fieldCount = count($fields);

	//resort our original array.  Map the new field->key to it's original index	
	foreach ($sortArray AS $key) {

		for ($i=0;$i<$fieldCount;$i++) {

			$field = $fields[$i];
			$newArray[$field][] = $modArray[$field][$key];
					
		}

	}

	return $newArray;

}

/*******************************************************************************
	FUNCTION: 	arrayStringSearch
	PURPOSE:		this function checks all values in an array
							to see if they contain a string.  The value
							does not have to = the string, just contain
							the string.  
	INPUTS:			str -> string we are searching for
							arr -> array we are searching in
							icase -> if set makes search case insensitive
	RETURNS: 		int or string -> key of array entry that matches
********************************************************************************/
function arrayStringSearch($str,$arr,$icase = null) {

	if (!is_array($arr)) return false;

	//we use array_keys so this works on associative arrays as well
	$keys = array_keys($arr);
	$ret = array();
	
	//loop through and search for a matching string	
	foreach ($keys AS $key) {

		if ($icase) $func = "stripos";
		else $func = "strpos";
		
		if ($func($arr[$key],$str)!==FALSE) return $key;

	}

	//if we get to here, return false;
	return false;

}

/**************************************************************
	FUNCTION:	reduceArray
	PURPOSE:	shrinks an array down by removing all null values in the array
						for some reason array_values misses certain entries
	INPUTS:		arr -> array we are reducing
	RETURNS:	array
***************************************************************/
function reduceArray($arr) {

	if (!is_array($arr)) return array();

	$newArr = array();
	$keys = array_keys($arr);

	foreach ($keys AS $key) {

		if ($arr[$key]!=NULL) {
			if (is_numeric($key)) $newArr[] = $arr[$key];
			else $newArr[$key] = $arr[$key];
		}

	}

	return $newArr;

}

/********************************************************************
	FUNCTION:	arrayReduce
	PURPOSE:	alias for reduceArray
********************************************************************/
function arrayReduce($arr) {
	return reduceArray($arr);
}

//find average of all values in an array
function arrayAverage($input) {

	$max = count($input);
	if ($max==0) return 0;

	return array_sum($input) / $max;

}

//takes a dash/comma delimited string and turns it into an array of valid extensions
function parseRange($str) {

	$ret = array();

	//first, get all our commas
	$arr = explode(",",$str);

	//loop through our comma entries
	foreach ($arr AS $entry) {

		//now delimit by dashes
		$range = explode("-",$entry);

		//if more than one entry, we have a range.otherwise we have a single entry
		if (count($range)==1) $ret[] = $range[0];
		else $ret = array_merge($ret,range($range[0],$range[1]));

	}


	return $ret;

}
 
function removeNumericKeys($arr) {
  
  $ret = array();
  $keys = array_keys($arr);
  
  if (count($keys) > 0) {
      
    foreach ($keys AS $key) {
  
      if (!is_numeric($key)) $ret[$key] = $arr[$key];
  
    }
    
  }

  return $ret;
}


/*********************************************************
//make the data safe to be inserted in the database.
//this will handle multilevel arrays
*********************************************************/
function urldecodeArray($obj,$es = null) 
{

  if (!$es) $es = array();
  if (count($obj)==0) return false;

  foreach ($obj AS $key=>$val) 
  {

    //skip if the variable is marked for exemption
    if (in_array($key,$es)) continue;

    //if an 
    if (is_array($val)) $obj[$key] = urldecodeArray($val,$es);
    else $obj[$key] = urldecode($val);
 
  }

  return $obj;

}


/******************************************************
	FUNCTION: 	arrayMSort
	PURPOSE:		Sort a multidimensional array by one 
	            of its columns  (arr[key][column])
	INPUTS:			$data -> array to sort
							$column -> field to sort by
							dir -> direction we sort by (ASC or DESC)
	RETURNS:		sorted array
******************************************************/
function arrayMSort($data,$column,$dir="ASC") {

  $temp = array();

  foreach ($data AS $key => $row) {

    $temp[$key] = $row[$column];
    
  }

  if ($dir=="DESC") $dir = SORT_DESC;
  else $dir = SORT_ASC;
  
  array_multisort($temp,$dir,$data);

  return $data;
    
}

/****************************************************
  FUNCTION:	is_assoc
  PURPOSE:	determines if an array is associative
  INPUTS:		$arr -> array to check
  RETURNS:	boolean
****************************************************/
function is_assoc($arr)
{

  $keys = array_keys($arr);
  $ret = false;
  
  foreach ($keys AS $key)
  {
  
    if (!is_numeric($key))
    {
      $ret = true;
      break;
    }
  
  }
  
  return $ret;

}
