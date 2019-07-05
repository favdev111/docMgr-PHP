<?php
/****************************************************************************
	xml.php
	
	Houses all our xml parsing functions which use simplexml when
	available.  This file can only be used with PHP 5
****************************************************************************/

class XML
{

	function __construct()
	{
	
	
	}

	public static function decode($str) 
	{

		$pos = strpos("<?xml",$str);
		
		//if no xml header, add one
		if ($pos!="0")
		{

			//submit the xml as whatever the browser's encoding should be as set in config file
			$str = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n".$str;
    
    }

		//decode, convert to an array    
		$str = urldecode($str);
		$xml = simplexml_load_string($str);
		$arr = XML::simpletoarray($xml);
		
		return $arr;

	}


	public static function encode($arr)
	{

		//make sure there's something there
		if (count($arr)==0) return false;
		if (!is_array($arr)) return false;
		
		$keys = array_keys($arr);

		foreach ($keys AS $key) 
		{

			if (is_array($arr[$key]))
			{

				$subkeys = array_keys($arr[$key]);
				
				//if the keys are numeric
				if (is_numeric($subkeys[0]))
				{

					foreach ($subkeys AS $sub)
					{

						if (is_array($arr[$key][$sub])) 
						{
							$xml .= "<".$key.">\n";
							$xml .= XML::encode($arr[$key][$sub]);
							$xml .= "</".$key.">\n";
						} 
						else 
						{
							$xml .= XML::entry($key,$arr[$key][$sub]);
						}

				
					}			

				//handle associative keys				
				} 
				else 
				{

					$xml .= "<".$key.">\n";

					foreach ($subkeys AS $sub)
					{
						if (is_array($arr[$key][$sub])) 
						{
							$xml .= "<".$sub.">\n";
							$xml .= XML::encode($arr[$key][$sub]);
							$xml .= "</".$sub.">\n";
						} 
						else 
						{
							$xml .= XML::entry($sub,$arr[$key][$sub]);
						}
				
					}			

					$xml .= "</".$key.">\n";
				
				
				}

			//handle regular xml strings				
			} else 
			{
			
				$xml .= XML::entry($key,$arr[$key]);
			
			}
	
		}


		return $xml;
	
	
	}

	public static function output($data)
	{

		file_put_contents("/tmp/xml",$data);

		XML::header();
		echo $data;
		XML::footer();

		die;
	
	}
	
	//this function creates an xml header with typeid so we can process and associate
	//the proper response handler with the returned data
	private static function header($type = null) {
	
		header("Cache-Control: no-cache, must-revalidate"); 
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Content-Type: text/xml");
	
		$str = "<?xml version=\"1.0\" encoding=\"utf-8\" standalone=\"yes\"?>\n";
		$str .= "<data>\n";
		if ($type) $str .= "\t<typeid>".$type."</typeid>\n";
	
		echo $str;
	
	}
	
	//puts a footer on the end of our xml data
	private static function footer() {
	
		echo "</data>\n";
	
	}
	
	//encases the data in its xml tags and CDATA declaration
	private static function entry($key,$data,$ignore = null) {

		if (is_numeric($key)) return false;
	
		$str = "<".$key.">";
		if ($data!=NULL) 
		{
	
			if ($ignore) $str .= $data;
			else $str .= "<![CDATA[".$data."]]>";

		}
	
		$str .= "</".$key.">\n";
	
		return $str;
	
	}
	
	//rewritten to handle nested xml tags by me!!!
	private static function simpletoarray($xml) {

		//$xml = get_object_vars($xml);
		$arr = array();
			
		//echo get_class($xml)."<hr>";
		$children = $xml->children();
	
		foreach ($children AS $k=>$v) {
	
			$childs = $v->children();
			if ($childs) {
	
				$ret = XML::simpletoarray($v);
	
				//if we have an entry, append, otherwise start new array
				if ($arr[$k]) $arr[$k][] = $ret;
				else $arr[$k] = array($ret);
				
			} else {
	
				//if a tag is already set, start an array for that series
				if (isset($arr[$k])) {
	
					if (is_array($arr[$k])) $arr[$k][] = (string)$v;
					else {
						$newarr = array($arr[$k]);
						$newarr[] = (string)$v;
						$arr[$k] = $newarr;
					}
					
				} else {
	
					$arr[$k] = (string)$v;
				}
	
			}
					
		}
	
		return $arr;
	
	}
	
}
