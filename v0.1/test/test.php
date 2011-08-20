<?php
include_once('../client/sydo_client.php');
include_once('../client/dbs/mysql.php');
/* Connect to MySQL Server - this is the place where are stored all hashes and keys */
$handler = mysql_connect('localhost', 'root','') or die('Could not connect to server.');
mysql_select_db('test2', $handler);

/* This is an example, we can validate any type of auth + token. Soon will get a special register for new clients of SYDO Hash Center */
$auth  = sha1('test');
$token = sha1('token');

/* Init SYDO Client */  
SYDO_Client::init($auth, $token, 'mysql', $handler);


/* Insert protected data by table key encryption */
$columns = array('column1', 'column2', 'column3');
$dump    = array(array('row1_column1', 'row2_column2', 'row3_column3'),
				 array('row2_column1', 'row2_column2', 'row2_column3')
				 );
/* Perform insert, this function is an example of function that can be used from MySQL Interface special crafted for SYDO */
SYDO_Client::sql_insert('table_test', $columns, $dump, 'tables');
/* Perform a usual select */
echo '<h1>Select using mysql_fetch_array</h1>';
$query = mysql_query('SELECT * FROM table_test');
while($row = mysql_fetch_array($query))
	echo $row['column1'].' - '.$row['column2'].' - '.$row['column3'].'<br />';

/* Perform a SYDO select */
echo '<h1>Select using sql_fetch from SYDO MySQL Interface</h1>';
$query = mysql_query('SELECT * FROM table_test');
while($row = SYDO_Client::sql_fetch($query, 'test2^table_test'))
	echo $row['column1'].' - '.$row['column2'].' - '.$row['column3'].'<br />';
	
	
SYDO_Client::flush(); /* flush cache to store keys in SYDO Hash Center */
?>