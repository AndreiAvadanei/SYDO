<?php

class mysql 
{
	private $handler    = NULL;
	private $database   = NULL;
	
	/* ~~ Constructor ~~ */
	public function __construct($handler)
	{
		$this->handler = $handler;	
	}
	
	/**
	  * A hooked version of mysql_fetch_array/mysql_fetch_assoc.
	  * TODO : table_columns, table_row_columns, database, "the special character ^"
	  *
	  * @param resource $resource
	  * @param string $key
	  * @param string $key_type
	  * @param string $fetch_type : array (fetch array), assoc(fetch assoc)
	  * @param int    $result_type
	  * @access public
	  *
	  * @return a fetched array
	  *
	  *
	  * @explain : $key should look like this : 
	  *				 - database   	 	 : TODO
	  * 			 - tables 	  	 	 : DATABASE^TABLE
	  * 			 - table_rows 	 	 : DATABASE^TABLE^ROW_HANDLER (ROW_HANDLER will be replaced with value of row value)
	  *				 - table_columns 	 : TODO
	  *				 - table_row_columns : TODO
	  **/ 	
	public function sql_fetch($resource, $key, $key_type = 'tables', $fetch_type = 'array', $result_type = MYSQL_BOTH)
	{
		if($fetch_type == 'array') $__row = mysql_fetch_array($resource, $result_type);
		else 					   $__row = mysql_fetch_assoc($resource);
		
		if($__row == FALSE) return FALSE;
		switch($key_type)
		{
			case 'database':
			
			break;
			case 'tables':  
				$key = explode('^', $key);
				if(sizeof($key) != 2) 		return FALSE;    //DATABASE^TABLE
				$key = implode('^',$key);
				foreach($__row as $__key => $__value)
					$__row[$__key] = SYDO_Client::decrypt($__value, FALSE, sha1($key));	
				return $__row; 
			break;
			case 'table_rows': 
				$key = explode('^', $key);
				if(sizeof($key) != 3)		return FALSE; 
				if(!isset($__row[$key[2]])) return FALSE; 
				$key[2] = $__row[$key[2]];
				
				foreach($__row as $__key => $__value)
					$__row[$__key] = SYDO_Client::decrypt($__value, FALSE, sha1(implode('^',$key)));			
				return $__row;
			break;
			case 'table_columns':
						
			break;
			case 'table_row_columns':
			
			break; 
		}
		return FALSE;
	}
	
	/**
	  * One function that belongs to SYDO SQL Interface (MySQL) that helps you to insert allready encrypted data 
	  * TODO : table_rows, table_columns, table_row_columns, database
	  *
	  * @param string $table
	  * @param array  $columns
	  * @param array  $dump
	  * @param string $key_type : database, tables, table_rows, table_columns, table_row_columns
	  * @param string $key
	  * @access public
	  *
	  * @return resource on success, FALSE otherwise
	  *
	  *
	  * @explain : $key should look like this : 
	  *				 - database   	 	 : TODO
	  * 			 - tables 	  	 	 : DATABASE^TABLE
	  * 			 - table_rows 	 	 : DATABASE^TABLE^ROW_HANDLER (ROW_HANDLER will be replaced with value of row value)
	  *				 - table_columns 	 : TODO
	  *				 - table_row_columns : TODO
	  **/ 	
	
	public function sql_insert($table, $columns, $dump, $key_type = 'tables', $key = FALSE)
	{
		if($this->database == NULL && $key == FALSE) 
			$this->database = mysql_result(mysql_query("SELECT DATABASE()"),0);
		
		$query = 'INSERT INTO `'.$table.'`';
		switch($key_type)
		{
			case 'database':
			break;
			case 'tables':						
				if($key == FALSE)      $key = $this->database.'^'.$table;
				$key = explode('^', $key);
				if(sizeof($key) != 2) 		return FALSE; //DATABASE^TABLE
				 
				$key = implode('^', $key);
				if(strlen($key) != 40) $key = sha1($key); //key control
				 
				$hash = SYDO_Client::encrypt('test', $key);
				$hash = $hash['hash'];
				 
				foreach($dump as $k => $__value)
				{ 
					foreach($__value as $__key => $__col)
					{
						$__value[$__key] = SYDO_Client::encrypt($__col, $key, $hash);
						$__value[$__key] = '"'.$__value[$__key]['encrypted'].'"';
					}
					$__value = implode(',',$__value); 
					$dump[$k] = '('.$__value.')';
				}
			break;
			case 'table_rows':
				
			break;
			case 'table_columns':
			break;
			case 'table_row_columns':
			break;
		}
		
		$query .= ' ('.implode(',',$columns).') ';
		$query .= ' VALUES '.implode(',',$dump).' ';
		
		return mysql_query($query);
	}
}

?>