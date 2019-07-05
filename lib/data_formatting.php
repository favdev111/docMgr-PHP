<?php
/***************************************************************/
//        FILE: dataformatting.inc.php
//              (replaces date_function.inc.php and merges
//               functions from other include files into this one)
//
// DESCRIPTION: Contains functions that handle how information
//              is formatted for display on the web page 
//              or db inserting/updating
//
//              Includes functions for formatting the output of 
//              Time, Date, Phone Number,and Monetary values
//
//    CREATION
//        DATE: 04-19-2006
//
//     HISTORY:
//
/***************************************************************/


/*******************************************************************
  some new standard formatting functions.  
  I want to replace the 50 different ones I already have with
  a few functions that do more
*******************************************************************/

/******************************************************************
  FUNCTION: dateView
  PURPOSE:	main function for reformatting a date as passed from
            the database
  INPUTS:		date -> expected to be formatted as YYYY-MM-DD HH:II:SS
                    or YYYY-MM-DD
            opt  -> different output options:
                    "compact" -> small time formatting
******************************************************************/
function dateView($date,$dateOpt=null,$timeOpt=null) {

  //if there's a ":" in it, we've been passed a time and date.  otherwise, just a date
  if (!$date) return false;
  
  if (strstr($date,":")) {
  
    $arr = explode(" ",$date);
    $date = formatDate($arr[0],$dateOpt);
    $time = formatTime($arr[1],$timeOpt);

	  $str = $date." "._I18N_AT." ".$time;
	  return $str;

  } else {

    return formatDate($date,$dateOpt);
    
  }  
  
}

/******************************************************************
  FUNCTION: formatDate
  PURPOSE:	workhorse function for reformatting the date only
            as passed from a database
            the database
  INPUTS:		date -> expected to be formatted as YYYY-MM-DD HH:II:SS
                    or YYYY-MM-DD
            opt  -> different output options:
                    "compact" -> small time formatting
******************************************************************/

function formatDate($date,$dateOpt=null) {

	  if (defined("DATE_FORMAT")) $layout = DATE_FORMAT;
	  else $layout = "mm/dd/yyyy";

	  $layout = str_replace("mm","m",$layout);
	  $layout = str_replace("dd","d",$layout);
	  $layout = str_replace("yyyy","Y",$layout);

		$dateArray = explode("-",$date);

		if ($dateOpt=="full") date("M d, Y",mktime(0,0,0,$dateArray[1],$dateArray[2],$dateArray[0]));
		else $date = date("$layout",mktime(0,0,0,$dateArray[1],$dateArray[2],$dateArray[0]));
		
		return $date;
		
}

function formatTime($time,$timeOpt=null) {

	$arr = explode(":",$time);

	$hour = $arr[0];
	$min = $arr[1];
	$sec = $arr[2];

	if ($hour>="12") {

		$period="PM";
		if ($hour!=12) $hour=$hour-12;

	} else {
		if ($hour=="0" || $hour=="00") $hour = "12";
		$period="AM";
	}

	$time_show=$hour;
	$time_show.=":";
	$time_show.=$min;

	//add the am/pm
	if ($timeOpt=="compact") $time_show .= strtolower($period);
	else $time_show .= " ".$period;

	return $time_show;

}

function timeView($time,$timeOpt=null) {
  
  return formatTime($time,$timeOpt);
  
}

function dateProcess($date) {
 
  if ($date) return date("Y-m-d",strtotime($date));
  else return false;
  
}

function dateFormat($date) 
{
	if ($date) return date("c",strtotime($date));
	else return null;
}

function phoneProcess($num) {

    $num = preg_replace("/[^0-9]/","",$num);

    return $num;

}

function priceProcess($num) {

  //$num = number_format($num,2);

    $num = preg_replace("/[^0-9.]/","",$num);

    return $num;

}


function phoneView($num) 
{

	$num = preg_replace("/[^0-9]/","",$num);

	if ($num) 
	{

		if (strlen($num)==10) 
		{

			$area = substr($num,0,3);
			$prefix = substr($num,3,3);
			$number = substr($num,6);

			return "(".$area.") ".$prefix."-".$number;

		} 
		else if (strlen($num)==11) 
		{

			$ld = substr($num,0,1);
			$area = substr($num,1,3);
			$prefix = substr($num,4,3);
			$number = substr($num,7);

			return $ld." (".$area.") ".$prefix."-".$number;

		} 
		else if (strlen($num)>11) 
		{

			return $num;

		} 
		else 
		{

			$prefix = substr($num,0,3);
			$number = substr($num,3);

			return $prefix."-".$number;

		}

	} 
	else return false;
	
}


function formatPhone($num) {

	$num = trim($num);

	$len = strlen($num);

	if ($len==10) {

		$area = substr($num,0,3);
		$prefix = substr($num,3,3);
		$ident = substr($num,6,4);

		$number = "(".$area.") ".$prefix."-".$ident;

	} else {

		$prefix = substr($num,0,3);
		$ident = substr($num,3,4);

		$number = $prefix."-".$ident;
	
	}

	if ($number) return $number;
	else return false;


}

function priceView($num) {

	$num = (float)preg_replace("/[^0-9.]/","",$num);

  $num = "$".number_format($num,2);

  return $num;

}

// Takes string in currency format ($xxx,yyy.zz)
// and converts it into a floating point number
// so that it can be inserted into a db precision field.
function MoneyToFloat($zMoney) {

       /*--- Local Variables ---*/

        $laundermoney = null;
        $dollars = null;
        $cents = null;

       /*--- Start of Processing ---*/

        $zMoney = str_replace("$","",$zMoney);

        if(strrchr($zMoney,",") )
        {
            $gMoney = str_replace(",","",$zMoney); 
            if( strrchr($gMoney,".") )
            {
                $dollars = strtok($gMoney,".");
                $cents= strtok(".");
            }
            else
                $dollars = $gMoney;
        }
        else if( strrchr($zMoney,".") )
        {
            $dollars = strtok($zMoney,".");
            $cents= strtok(".");
        }
        else
        {
            $dollars = $zMoney;
            $cents = 0;
        }    

        $laundermoney = $dollars.".".$cents;

        (float)$laundermoney = $laundermoney;
 
        return($laundermoney);
}

function formatName($e) {

  if ($e["cb_first_name"]) {

    if ($e["cb_last_name"] == $e["last_name"] || !$e["cb_last_name"])
      $name = $e["first_name"]." & ".$e["cb_first_name"]." ".$e["last_name"];
    else
      $name = $e["first_name"]." ".$e["last_name"]." & ".$e["cb_first_name"]." ".$e["cb_last_name"];

  }
  else $name = $e["first_name"]." ".$e["last_name"];

  return $name;

}
/***********************************************************
************************************************************/
function organizeName($str) {

    $ret = array();
    $str = trim($str);
   
    //if it has a comma, assume it's ln,fn
    if (strstr($str,",")!=NULL) {
   
        $str = str_replace(",","",$str);
        $arr = explode(" ",$str);
        $num = count($arr);

        if ($num > 2) {
            $ret["ln"] = $arr[0];
            $ret["fn"] = $arr[1];
            $ret["mn"] = $arr[2];
        }
        else {
            $ret["ln"] = $arr[0];
            $ret["fn"] = $arr[1];
        }

    }
    else {

        $arr = explode(" ",$str);
        $num = count($arr);

        if ($num==1) {
            $ret["ln"] = $arr[0];
        }
        else if ($num > 2) {
            $ret["ln"] = $arr[2];
            $ret["fn"] = $arr[0];
            $ret["mn"] = $arr[1];
        }
        else {
            $ret["ln"] = $arr[1];
            $ret["fn"] = $arr[0];
        }

    }

    return $ret;

}
/***********************************************************
************************************************************/
function formatAddr($e) {

  $str = null;
  if ($e["address"]) $str .= $e["address"]."<br>";

  if ($e["city"] || $e["state"] || $e["zip"]) {
    if ($e["city"]) $str .= $e["city"].", ";
    if ($e["state"]) $str .= $e["state"]." ";
    if ($e["zip"]) $str .= $e["zip"];
    $str .= "<br>";
  }

  return $str;

}
/*********************************************************
*********************************************************/
function getmicrotime() {

    list($usec, $sec) = explode(" ",microtime());
    return ((float)$usec + (float)$sec);

}

/*********************************************************
  charConv	
  character encoding conversion.uses iconv
  if available.If not, returns the string
  unaltered
*********************************************************/
function charConv($string,$in,$out) {

  $str = null;

  //make them both lowercase
  $in = strtolower($in);
  $out = strtolower($out);

  //sanity checking
  if (!$in || !$out) return $string;

  if ($in==$out) return $string;

  //return string if we don't have this function
  if (!function_exists("iconv")) return $string;

  //this tells php to ignore characters it doesn't know
  $out .= "//IGNORE";

  return iconv($in,$out,$string);

}

if (!function_exists("quoted_printable_encode"))
{
	
	//direct from php website comments under quoted_printable_decode
	function quoted_printable_encode($str, $wrap=true)
	{
	  $return = '';
	  $iL = strlen($str);
	  for($i=0; $i<$iL; $i++)
	  {
	    $char = $str[$i];
	    if(ctype_print($char) && !ctype_punct($char)) $return .= $char;
	    else $return .= sprintf('=%02X', ord($char));
	  }
	  return ($wrap === true) ? wordwrap($return, 74, " =\n") : $return;
	
	}

}

function viewMonth($num) {

  $arr[1] = "January";
  $arr[2] = "February";
  $arr[3] = "March";
  $arr[4] = "April";
  $arr[5] = "May";
  $arr[6] = "June";
  $arr[7] = "July";
  $arr[8] = "August";
  $arr[9] = "September";
  $arr[10] = "October";
  $arr[11] = "November";
  $arr[12] = "December";
  
  return $arr[$num];

}

function timeProcess($time,$period) {

  $arr = explode(":",$time);
  $hour_value = $arr[0];
  $minute_value = $arr[1];

	if ($period) {

    //standardize the period
    $period = strtolower(str_replace(".","",$period));

		if ($period=="pm" && $hour_value!="12") $hour_value=12+$hour_value;

	}

	$time_enter=$hour_value;
	$time_enter.=":";
	$time_enter.=$minute_value;
	$time_enter.=":";
	$time_enter.="00";

	return $time_enter;


}

if (!function_exists("date_diff"))
{

	function date_diff($firstDate,$lastDate) {
	
		$fdArray = explode("-",$firstDate);
		$ldArray = explode("-",$lastDate);

		//get how many days the difference is
		$diff = mktime(0,0,0,$fdArray[1],$fdArray[2],$fdArray[0]) - mktime(0,0,0,$ldArray[1],$ldArray[2],$ldArray[0]);
		$diff = abs($diff / 86400);
	
		return $diff;
	}

}

//this function returns the hours and minutes difference between tw times
function time_diff($start_time,$end_time) {

	//subtract our times to get the difference
	$calcStArray = explode(":",$start_time);
	$calcEtArray = explode(":",$end_time);

	$start_hour = $calcStArray[0];
	$start_min = $calcStArray[1];

	$end_hour = $calcEtArray[0];
	$end_min = $calcEtArray[1];

	//reduce our dates to timestamps.  We use a random date here, since it does not matter
	$ts1 = mktime($start_hour,$start_min,"0",1,1,2000);
	$ts2 = mktime($end_hour,$end_min,"0",1,1,2000);

	//get the number of seconds
	$diff = $ts2 - $ts1;

	$temp = $diff/3600;

	//get the place of the decimal point
	$pos = strpos($temp,".");

	//if we have pos, there is a decimal point
	if ($pos) {

		$dur_hour = intval($temp);
		$min = substr($temp,$pos);

		//convert to clock nums
		if ($min==".5") $dur_min = "30";
		elseif ($min==".25") $dur_min = "15";
		elseif ($min==".75") $dur_min = "45";

	}
	else {
		$dur_hour = $temp;
		$dur_min = "00";
	}

	return array($dur_hour,$dur_min);

}

function notimeDateView($datestr) {

  $time = strtotime($datestr);
  return date("m/d/Y",$time);

}

/***********************************************************
  legacy functions
***********************************************************/

function date_view($date,$format = "slash") {

  return dateView($date,$format);

}


//takes dates in the XXXX-XX-XX format
function dateFix($date) {

     $dateArray = explode("-",$date);

    $year = $dateArray[0];
    $month = $dateArray[1];
    $day = $dateArray[2];

    return date("Y-m-d",mktime(0,0,0,$month,$day,$year));

}


//this makes dates viewable as passed from postgresql's date format
function date_time_view($datetime) {

	if (!$datetime) return true;

	$datetime = trim($datetime);

	$pos = strpos($datetime," ");

	//divide up our string into date and time
	$date_value=substr($datetime,0,$pos);
	$date_value = formatDate($date_value);

	$time_value = substr($datetime,$pos);
	$time_value = formatTime($time_value);

	$datetime=array($date_value,$time_value);
	return $datetime;
}

function boolView($data) {

	if ($data=="t" || $data=="1") $str = "Yes";
	else $str = "No";
	
	return $str;

}

