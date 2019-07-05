<?php
/*****************************************************************************************
  Fileame: postgresql.php

  Purpose: Contains the functions required for the wrapper to access a PostgreSQL Database

  Created: 11-01-00
  Updated: 05-02-2005 - added db_close function
  Updated: 09-01-2005 - added db_escape_string function

******************************************************************************************/

CLASS POSTGRESQL 
{

	private $conn;
	private $logger;

	/***********************************************************
		FUNCTION: construct
		PURPOSE:  initializes the class
		INPUTS:		dbhost -> ip address of the database host
							dbuser -> database user
							dbpassword -> database password
							dbport -> port of database
							dbname -> name of database
	***********************************************************/
	function __construct($dbhost,$dbuser,$dbpassword,$dbport,$dbname) 
	{
		$this->conn = pg_connect("host=$dbhost port=$dbport user=$dbuser password=$dbpassword dbname=$dbname");

		if (class_exists("logger")) $this->logger = new logger($this);
	}

	/****************************************************************
		FUNCTION: getLogger()
		PURPOSE:  returns a reference to the error handler
	****************************************************************/
	function getLogger() 
	{
		return $this->logger;
	}

	/****************************************************************
		FUNCTION: getConn()
		PURPOSE:  returns a reference to database connection
	****************************************************************/
	function getConn() {
		return $this->conn;
	}

	/****************************************************************
		FUNCTION: close()
		PURPOSE:  closes the database
	****************************************************************/
	function close() {
		pg_close($this->conn);
	}

	/****************************************************************
		FUNCTION: query()
		PURPOSE:  executes a basic sql query
		INPUTS:		sql -> string we are executing as a sql statement
		RETURNS:	pg_query result
	****************************************************************/
	function query($sql,$nolog=null) {

		if (!$sql) return false;
		
		if (!$result = pg_query($this->conn,$sql)) 
		{
			if (defined("DEBUG")) debug_print_backtrace();
		}

		return $result;
	
	}

	/****************************************************************
		FUNCTION: fetch()
		PURPOSE:  executes a query and returns results as an associative
							array with record[$row][$column] as format
		INPUTS:		sql -> string we are executing as a sql statement
							transpose -> causes it to return results as 
							record[$column][$row] format
		RETURNS:	array
	****************************************************************/
	function fetch($sql,$transpose=null) {

		if (!$sql) return false;

		if (!$result = pg_query($this->conn,$sql)) 
		{
			if (defined("DEBUG")) debug_print_backtrace();
			return false;
		}
	
		$num = pg_numrows($result);

		if ($num!=0) $arr = pg_fetch_all($result);

		//flip results if asked
		if ($transpose) $arr = transposeArray($arr);

		$arr["count"] = $num;
		return $arr;

	}

	/****************************************************************
		FUNCTION: single()
		PURPOSE:  executes a query and returns results as single row
							associative array
		INPUTS:		sql -> string we are executing as a sql statement
		RETURNS:	array
	****************************************************************/
	function single($sql) {

		if (!$sql) return false;
	
		if (!$result = pg_query($this->conn,$sql)) 
		{
			if (defined("DEBUG")) debug_print_backtrace();
			return false;
		}
	
		$num = pg_numrows($result);

		if ($num != 0) 
		{

			$value = @pg_fetch_assoc($result,0);
			return $value;

		}
		else return false;
	
	}

	/****************************************************************
		FUNCTION: error()
		PURPOSE:  returns last error from error handler stack
		RETURNS:	string
	****************************************************************/
	function error() {
		return pg_last_error($this->conn);
	}

	/****************************************************************
		FUNCTION: getAllErrors()
		PURPOSE:  returns all errors in error stack of error handler.
		INPUT:		sep -> change error delimiter for formatting
		RETURNS:	string
	****************************************************************/
	function getAllErrors($sep="html") {
	}

	/****************************************************************
		FUNCTION: count()
		PURPOSE:  returns number of matches of query
		INPUT:		sql -> sql query string to run
		RETURNS:	integer
	****************************************************************/
	function count($sql) {

		if (!$result = pg_query($this->conn,$sql)) 
		{
			return false;
		}
	
		return pg_numrows($result);
	
	}

	/*********************************************
			some fun aliases
	*********************************************/
	function escort($sql,$transpose=null) {
		return $this->fetch($sql,$transpose);
	}
	
	function solo($sql) {
		return $this->single($sql);
	}

	/****************************************************************
		FUNCTION: getId()
		PURPOSE:  get the id of the primary key of the last inserted
							record 
		INPUT:		$table -> table of record
							$id -> name of column of primary key
							$result -> result from query run
		RETURNS:	integer
	****************************************************************/
	function getId($table,$id,$result) {

		//use oids to return the last inserted id for older versions of postgresql (pre 8.1)
		if (defined("USE_OID")) {

			//it didn't work, try again with the old method
			if (!$return_id) {
				$pgoid=@pg_last_oid($result);
				$result1=@pg_exec($this->conn,"SELECT $id FROM $table WHERE oid='$pgoid'");
				$query_myrow=@pg_fetch_array($result1,0);
				$return_id=$query_myrow[$id];
			}
	

		} else {
	
			$sql = "SELECT LASTVAL()";
			if ($res = @pg_exec($this->conn,$sql)) {
				$query_myrow=@pg_fetch_array($res,0);
				$return_id=$query_myrow[0];
			} 

		}

		return $return_id;
	
	}

	/*************************************************
		FUNCTION: begin()
		PURPOSE:  begins transaction instance
	*************************************************/
	function begin() {
	
		$sql = "BEGIN WORK";
		if ($this->query($sql)) return true;
		else return false;
	}
	
	/*************************************************
		FUNCTION: end()
		PURPOSE:  ends transaction instance
	*************************************************/
	function end() {
	
		$sql = "END WORK";
		if ($this->query($sql)) return true;
		else return false;

	}

	function rollback() {
	
		$sql = "ROLLBACK WORK";
		if ($this->query($sql)) return true;
		else return false;

	}
	
	/*************************************************
		FUNCTION: vacuum()
		PURPOSE:  vacuums the database
	*************************************************/
	function vacuum() {
	
		$sql = "VACUUM FULL ANALYZE";
	
		if ($this->query($sql)) $message = "Database Vacuumed Successfully";
		else $message = "Database Vacuum Failed";
	
		return $message;
	
	}

	/**
		FUNCTION: sanitize
		PURPSE: because I can remember this easier
		*/
	function sanitize($str)
	{
		return pg_escape_string($str);
	}
	
	/*************************************************
		FUNCTION: escape_string()
		PURPOSE:  make string safe for db entry
	*************************************************/
	function escape_string($str) {
		return pg_escape_string($str);
	}
	
	/*************************************************
		FUNCTION: unescape_string()
		PURPOSE:  undo escape_string
	*************************************************/
	function unescape_string($str) {
	
		return str_replace("''","'",$str);
	
	}
	
	/*************************************************
		FUNCTION: increment_seq
		PURPOSE:  increments sequence and returns new value
		INPUTS:		seq -> sequence name
	*************************************************/

	function next_seq($seq) {
	
		$sql = "SELECT NEXTVAL('".$seq."');";
		$info = $this->single($sql);
		return $info["nextval"];
	
	}

	/*************************************************
		FUNCTION: increment_seq
		PURPOSE:  increments sequence and returns new value
		INPUTS:		seq -> sequence name
	*************************************************/

	function set_seq($seq,$val) {
	
		$sql = "SELECT SETVAL('".$seq."','".$val."');";
		$info = $this->single($sql);
		return $info["nextval"];
	
	}
	
	/*************************************************
		FUNCTION: get_seq
		PURPOSE:  returns current value of sequence
		INPUTS:		seq -> sequence name
	*************************************************/
	function get_seq($seq) {
	
		$sql = "SELECT CURRVAL('".$seq."');";
		$info = $this->single($sql);
		return $info["curval"];
	
	}
	
	/*************************************************
		FUNCTION: last_error
		PURPOSE:  returns last error of db connection
	*************************************************/
	function last_error() {
	
		return pg_last_error($this->conn);
	
	}


	/*******************************************************************
		FUNCTION: insert
		PURPOSE:  creates and executes an insert on the database
		INPUTS:		table -> table to run query on
							option -> associative array with $option[$columnname] = $newvalue setup
									query -> return the query w/o executing
									debug -> echo the query to stdout
							idField -> primary key of database.  use to return newly inserted id
	********************************************************************/
	function insert($table,$option,$idField = null,$nolog=null) {
	
		$ignoreArray = array("conn","table","debug","query","_showquery");
		$fieldArr = array();
		$valueArr = array();	

		foreach ($option AS $field=>$value) {
		
			if (in_array($field,$ignoreArray)) continue;
			if (is_numeric($field)) continue;
			if ($value==null) continue;
			
			$fieldArr[] = $field;
			$valueArr[] = "'".$value."'";
			
		}

		if (count($fieldArr) > 0 && count($valueArr) > 0) {
		
			$fieldString = implode(",",$fieldArr);
			$valueString = implode(",",$valueArr);
		
			$sql = "INSERT INTO $table (".$fieldString.") VALUES (".$valueString.");";
			if ($option["debug"]) dprint($sql."\n");
			if ($option["query"]) return $sql;
			if ($option["_showquery"]) file_put_contents("/tmp/query.sql",$sql);
			
			if ($result = $this->query($sql,$nolog)) {
	
	      if ($idField) {		
	
	  			$returnId = $this->getId($table,$idField,$result);
	  			if ($returnId) return $returnId;
	  			else return true;
	
	      } else {
	        return true;
	      }
	
			} else return false;
	
		} else return false;
	
	}
	
	/*******************************************************************
		FUNCTION: update
		PURPOSE:  creates and executes an update on the database
		INPUTS:		table -> table to run query on
							option -> associative array with $option[$columnname] = $newvalue setup
									where -> which record to base your update on ($option["where"] = "id='5'")
									query -> return the query w/o executing
									debug -> echo the query to stdout
							idField -> primary key of database.  use to return newly inserted id
	********************************************************************/
	function update($table,$option,$sanitize = null) {
	
		$ignoreArray = array("conn","table","where","debug","query","_showquery");

		$queryString = null;

		foreach ($option AS $field=>$value) {
		
			if (in_array($field,$ignoreArray)) continue;
			if (is_numeric($field)) continue;
		
			if ($value==null) $queryString .= $field."=NULL,";
			else {
				if ($sanitize) 
					$queryString .= $field."='".sanitize($value)."',";
				else
					$queryString .= $field."='".$value."',";
			
			}

		}
	
		if ($queryString) {
	
			$queryString = substr($queryString,0,strlen($queryString) - 1);
				
			$sql = "UPDATE $table SET ".$queryString." WHERE ".$option["where"];
	
			if ($option["debug"]) dprint($sql."\n");
			if ($option["query"]) return $sql;
			if ($option["_showquery"]) file_put_contents("/tmp/query.sql",$sql);
			
			if ($this->query($sql)) return true;
			else return false;
	
		} else return false;
	
	}

	function version()
	{
		$arr = pg_version();
		return $arr["server"];
	}		
}
		