<?php
include_once('../server/sydo_server.php');

/* Connect to MySQL Server - this is the place where are stored all hashes and keys */
$handler = mysql_connect('localhost', 'root','') or die('Could not connect to server.');
mysql_select_db('teste', $handler);


try 
{
	/* Init API */
	$sydo = new SYDO_Server($handler);
	$init = $sydo->init();
	/* Catch init input */
	extract($init);
	/* Parse request */
	$sydo->request($action, $params, $auth);
}
catch(Exception $e) /* Catch exceptions */
{
	$response = array('status' => 0, 'error' => $e->getMessage(), 'code' => -1);
	echo json_encode($response);
}
?>