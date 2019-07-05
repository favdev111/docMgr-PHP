<?php
/****************************************************************************
	libxml.php

	not really used.  I was just playing	
****************************************************************************/

class XML {

	protected $xmlarr;
	protected $xmlstr;
	
	function __construct($init=null) {
	
		//if init is string, store it as string
		if (is_array($init)) $this->xmlarr = $init;
		else $this->xmlstr = $init;
		
	}

	function toArray() {
	
		$this->xmlarr = $this->convertToArray($this->xmlstr);
		return $this->xmlarr;
		
	}
	
	function toXml() {
	
		$this->xmlstr = $this->convertToXml($this->xmlarr);
		return $this->xmlstr;
			
	}

	
	function convertToArray($str) {
	
		$xml = simplexml_load_string($str);
		return $this->simplexml2array($xml);	
	
	}
	
	//rewritten to handle nested xml tags by me!!!
	function simplexml2array($xml) {
	
		//$xml = get_object_vars($xml);
		$arr = array();
			
		//echo get_class($xml)."<hr>";
		$children = $xml->children();
	
		foreach ($children AS $k=>$v) {
	
			$childs = $v->children();
			if ($childs) {
	
				$ret = $this->simplexml2array($v);
	
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

	//taken directly from the php website.  needs interoperability testing
	function phpConvertToArray($str) {

		$xml = simplexml_load_string($str);

		if (get_class($xml) == 'SimpleXMLElement') {
			$attributes = $xml->attributes();
			foreach($attributes as $k=>$v) {
				if ($v) $a[$k] = (string) $v;
			}
			$x = $xml;
			$xml = get_object_vars($xml);
		}
		if (is_array($xml)) {
			if (count($xml) == 0) return (string) $x; // for CDATA
			foreach($xml as $key=>$value) {
				$r[$key] = $this->convertToArray($value);
			}
			if (isset($a)) $r['@'] = $a;// Attributes
			return $r;
		}
		return (string) $xml;

	}

	//encases the data in its xml tags and CDATA declaration
	function entry($key,$data,$ignore = null) {

		$str = "<".$key.">";
		if ($data!=NULL) {

			//convert our db data to the proper encoding if able
			if (defined("DB_CHARSET") && defined("VIEW_CHARSET")) $data = charConv($data,DB_CHARSET,VIEW_CHARSET);

			if ($ignore) $str .= $data;
			else $str .= "<![CDATA[".$data."]]>";
		}

		$str .= "</".$key.">\n";

		return $str;

	}

	//convert an array back to xml
	function convertToXml($arr,$passKey = null) {

		//make sure there's something there
		if (count($arr)==0) return false;
		if (!is_array($arr)) return false;
	
		$keys = array_keys($arr);
		foreach ($keys AS $key) {

			if (!$key || $key=="comment") continue;

			if ($passKey && !is_numeric($passKey)) $xml .= "<".$passKey.">\n";

			//if our value is an array, create a new child and reprocess
			if (is_array($arr[$key])) {

				$xml .= $this->convertToXml($arr[$key],$key);

			} else if (is_numeric($key)) {
		
				//use passkey instead of key since it's numeric
				$xml .= $this->entry($arr[$key],$passKey);
		
			}	
			//otherwise just add the node
			else {
				$xml .= $this->entry($key,$arr[$key]);
			}

			if ($passKey && !is_numeric($passKey)) $xml .= "</".$passKey.">\n";

		}

		return $xml;

	}

	//this function creates an xml header with typeid so we can process and associate
	//the proper response handler with the returned data
	function header($type=null) {

		header("Content-Type: text/xml");

		//use a default dbencoding
		if (!defined("VIEW_CHARSET")) define("VIEW_CHARSET","ISO-8859-1"); 

		$str = "<?xml version=\"1.0\" encoding=\"".VIEW_CHARSET."\" standalone=\"yes\"?>\n";
		$str .= "<data>\n";
		if ($type) $str .= "\t<typeid>".$type."</typeid>\n";

		return $str;

	}

	//puts a footer on the end of our xml data
	function footer() {

		return "</data>\n";

	}

	//convert the table entry into xml data.this should be fed a row at a time
	function convertTable($data) {

		$keys = array_keys($data);
		$num = count($keys);
		$str = null;

		//get all fields and convert data to xml
		for ($c=0;$c<$num;$c++){

			$key = &$keys[$c];

			if (is_numeric($key)) continue;

			if (is_array($data[$key])) $str .= $this->convertTable($data[$key]);
			else $str .= $this->entry($key,$data[$key]);

		}

		return $str;

	}

}


	
	