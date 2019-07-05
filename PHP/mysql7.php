<?php

class db
{
	var $db_id = false;
	var $mysqli_error = '';
	var $mysqli_error_num = 0;
	
	function connect($db_user='DBUSER', $db_pass='DBPASS', $db_name='DBNAME', $db_location = 'DBHOST', $show_error=1)
	{
		if(!$this->db_id = @mysqli_connect($db_location, $db_user, $db_pass, $db_name)) {
			if($show_error == 1) {
				$this->display_error(mysqli_error(), mysqli_errno());
			} else {
				return false;
			}
		} 
		return true;
	}
	
	function query($query, $show_error=false)
	{
		if(!$this->db_id) $this->connect(DBUSER, DBPASS, DBNAME, DBHOST);
		
		if(!($result = mysqli_query($this->db_id, $query) )) {

			$this->mysqli_error = mysqli_error($this->db_id);
			$this->mysqli_error_num = mysqli_errno($this->db_id);

			if($show_error) {
				$this->display_error($this->mysqli_error, $this->mysqli_error_num, $query);
			}
		}
		return $result;
	}
	
	function get_row($query_result)
	{
		return mysqli_fetch_assoc($query_result);
	}

	function get_array($query_result)
	{
		return mysqli_fetch_array($query_result);
	}
	
	function get_object($query_result)
	{
		return mysqli_fetch_object($query_result);
	}
	
	function super_query($query, $multi = false)
	{
		if(!$this->db_id) $this->connect(DBUSER, DBPASS, DBNAME, DBHOST);

		if(!$multi) {
			return $this->get_row($this->query($query));
		} else {
			$query_result = $this->query($query);
			
			$rows = array();
			while($row = $this->get_row($query_result)) {
				$rows[] = $row;
			}
			return $rows;
		}
	}
	
	function num_rows($query_result)
	{
		return mysqli_num_rows($query_result);
	}
	
	function insert_id()
	{
		return mysqli_insert_id($this->db_id);
	}

	function get_result_fields($result) {

		while ($field = mysqli_fetch_field($result))
		{
            $fields[] = $field;
		}
		return $fields;
   	}

	function close()
	{
		@mysqli_close($this->db_id);
	}
	
	function display_error($error, $error_num, $query = '')
	{
		if($query) {
			$query = preg_replace("/([0-9a-f]){32}/", "********************************", $query);
			$query_str = "$query";
		}
		
		echo '<?xml version="1.0" encoding="iso-8859-1"?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml">
		<head><meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
		<title>MySQL Fatal Error</title>
		
		<style type="text/css">
		<!--
		body {
			font-family: Verdana, Arial, Helvetica, sans-serif;
			font-size: 10px;
			font-style: normal;
			color: #000000;
		}
		-->
		</style>
		</head>
		<body>
			<font size="4">MySQL Error!</font> 
			<br />------------------------<br />
			<br />
			
			<u>The Error returned was:</u> 
			<br />
				<strong>'.$error.'</strong>

			<br /><br />
			</strong><u>Error Number:</u> 
			<br />
				<strong>'.$error_num.'</strong>
			<br />
				<br />
			
			<textarea name="" rows="10" cols="52" wrap="virtual">'.$query_str.'</textarea><br />

		</body>
		</html>';
		
		exit();
	}

}

?>
