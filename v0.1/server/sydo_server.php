<?php

class SYDO_Server
{
	private $version  = '0.1';
	private $cversion = '';
	private $server   = 'localhost';
	private $port 	  = '3306';
	private $username = 'root';
	private $password = '';
	private $database = 'sydo';	
	private $handler  = NULL;
	private $defaults = array('validate' => 0,
	 						  'set'      => 1,
							  'get'      => 2,
							  'update'   => 3,
							  'delete'   => 4,
							  'flush'    => 5,
							  );
	private $nversion = array('__construct' => '0.1',
							  'init'        => '0.1',
							  'validate'    => '0.1',
							  'can'         => '0.1',
							  'request'     => '0.1',
							 );
	
	public function __construct($handler = NULL)
	{
		//is client compatible
		$agent 		     = @explode('|', $_SERVER['HTTP_USER_AGENT']);
		if(!is_array($agent) || sizeof($agent) != 3) $this->cversion = '0';
		preg_match('/SYDO Client v([0-9]+\.[0-9])+/', trim($agent[0]),$this->cversion);
		$this->cversion = $this->cversion[1];
		
		$this->can($this->cversion, __FUNCTION__); 
		
		//init
		if($handler != NULL)
			$this->handler = $handler;
		else
		{
			$handler = mysql_connect($this->server.":".$this->port, $this->username, $this->password) or eval("throw new Exception('Datacenter busy.',0);");
			mysql_select_db($this->database, $handler) or eval("throw new Exception('Datacenter busy.',0);");
		}
		return TRUE;
	}
	
	public function init()
	{
		$this->can($this->cversion, __FUNCTION__); //is client compatible
		
		$auth  		     = array('ip' => FALSE, 'auth' => FALSE, 'token' => FALSE, 'version' => FALSE, 'domain' => FALSE);
		$params  		 = array();
		$action 		 = '';
		$agent 		     = @explode('|', $_SERVER['HTTP_USER_AGENT']);
		if(is_array($agent) && sizeof($agent) != 3) return array('action' => '', 'auth' => $auth, 'params' => $params);
		
		$auth['ip']      = $_SERVER['REMOTE_ADDR'];
		$auth['auth']    = trim($agent[1]);
		$auth['token']   = trim($agent[2]);
		$auth['version'] = preg_match('/SYDO Client v([0-9]+\.[0-9])+/', trim($agent[0]));
		$auth['version'] = $auth['version'][1];
		
		if(isset($_POST) && is_array($_POST))
		{
			foreach($_POST as $key => $value)
				if(!is_array($_POST[$key]))
					$params[htmlentities($key, ENT_QUOTES)] = htmlentities($value, ENT_QUOTES);
		}
		if(isset($_GET['e']) && is_numeric($_GET['e']))
			$action = $_GET['e'];
		
		return array('action' => $action, 'auth' => $auth, 'params' => $params);
	}
	
	public function validate(&$auth)
	{	
		$this->can($this->cversion, __FUNCTION__); //is client compatible
		
		$query = mysql_query('SELECT domain FROM `sydo_clients` WHERE sydo_clients.ip="'.$auth['ip'].'" AND sydo_clients.auth="'.$auth['auth'].'" AND sydo_clients.token="'.$auth['token'].'"', $this->handler);
		if(mysql_num_rows($query))
		{
			$row = mysql_fetch_assoc($query);
			$auth['domain'] = $row['domain'];
			return TRUE;
		}
		return FALSE;
	}
	
	public function can($version, $function)
	{  
		if((float)$this->nversion[$function] > (float)$version)
			throw new Exception('Your SYDO Client is not updated. Please consider updating to the latest version from <a href="https://github.com/AndreiAvadanei/SYDO" target="_blank">GitHub</a> to support all features.', 1);
	}
	
	public function request($action, $params, &$auth, $login = TRUE)
	{
		$this->can($this->cversion, __FUNCTION__); //is client compatible	
		if(!is_numeric($action)) 
			$action = array_search($action, $this->defaults);

		switch($action)
		{
			case 0:
				if(!$login || $this->validate($auth))
					$response = array('status' => 1, 'message' => 'This request is valid.');
			break;
			case 1:
				if(!$login || $this->validate($auth))
				{
					if(isset($params['hash'], $params['key']) || !ctype_alnum($params['hash']) || !ctype_alnum($params['key']))
					{
						$obj = mysql_query('INSERT INTO `sydo_hash` (`hash`,`key`,`domain`,`time`) VALUES ("'.$params['hash'].'","'.$params['key'].'","'.$auth['domain'].'","'.time().'")',$this->handler);
						if($obj)
							$response = array('status' => 1, 'message' => 'Succesfully added.');
						else
							$response = array('status' => 0, 'error' => 'SQL Server busy.', 'code' => 2);
					}
					if(!isset($response))
						$response = array('status' => 0, 'error' => 'Invalid parameters. Hash and key should be alfanumeric only.', 'code' => 1);
				} 
			break;
			case 2:
				if(!$login || $this->validate($auth))
				{
					if(isset($params['key']) || !ctype_alnum($params['key']))
					{ 
						$obj = mysql_query('SELECT hash FROM `sydo_hash` WHERE sydo_hash.key="'.$params['key'].'" AND sydo_hash.domain="'.$auth['domain'].'"',$this->handler);
						
						if($obj)
						{
							if(mysql_num_rows($obj))
							{
								$obj = mysql_fetch_array($obj);
								$response = array('status' => 1, 'message' => $obj['hash']);	
							}
							else
							{
								$response = array('status' => 0, 'error' => 'The key provided was invalid.', 'code' => 3);
							}
						}
						else
						{
							$response = array('status' => 0, 'error' => 'SQL Server busy.', 'code' => 4);
						}
					}
					if(!isset($response))
						$response = array('status' => 0, 'error' => 'Invalid parameters.', 'code' => 1);
				}
			break;
			case 3:
				//nothing to do here
			break;
			case 4:
				if(!$login || $this->validate($auth))
				{
					if(isset($params['key']) || !ctype_alnum($params['key']))
					{
						$obj = mysql_query('DELETE FROM `sydo_hash` WHERE key="'.$params['key'].'" AND domain="'.$auth['domain'].'"',$this->handler);
						if($obj)
							$response = array('status' => 1, 'message' => 'Removed succesfully.');	
						else
							$response = array('status' => 0, 'error' => 'SQL Server busy.', 'code' => 5);
					}
					if(!isset($response))
						$response = array('status' => 0, 'error' => 'Invalid parameters.', 'code' => 1);
				}
			break;
			case 5:
				if(!$login || $this->validate($auth))
				{
					$answers = array();
				    
					$actions = json_decode(urldecode($params['cache']), TRUE);
					 
					foreach($actions as $action => $value)
						if(is_numeric($action))
						{
							foreach($value as $data)
								$answers[] = $this->request($action, $data, $auth, FALSE);
						}
							
					//todo get a better method for answers
					$response = array('status' => 1, 'message' => 'Successfully flushed.');
				}
			break;
			default:
				
			break;
		}
		if(!isset($response))
			$response = array('status' => 0, 'error' => 'Invalid authentication details.', 'code' => 0);
		
		echo json_encode($response);	
	}
} 
?>