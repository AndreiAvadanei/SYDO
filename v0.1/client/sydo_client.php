<?php

/**** 
	* Name        : SYDO - Secure Your Data by Obscurity
	* Version     : 0.1 Alpha
	* Author      : Andrei Avadanei
	* Website     : http://www.worldit.info
	* Contact     : andrei@worldit.info
	* Description : SYDO aims to protect your data stored in SQL Database. This class has hooked functions for MySQL (for now) queries that are encrypted based on some settings. How many times you have made compromises with your hosting provider because you don't trust him in his privacy rules? How many times you don't know what is hosting provider aim? This tool encrypt your data before encrypting it with AES, based on random keys. Then, the service create special hashes (if needed) that are sended to a safe webserver (with SYDO Hash Center installed and configured) which stores only the real keys (used for decryption) and the special keys used for identification. Thus, your data's are a litle bit safer because the hosting provider should decrypt your tables first to see what you store there.
	* Features    : - column encryption based on special key manufactured on the fly
	*			 	- table encryption based on special key manufactured on the fly
	* 				- Special token + IP authentication
	* TODO        : - support for various SQL interfaces
	*				- support for encrypting whole tables/databases (encrypt even tables name and table columns)
	*				- support data statistics, request information
	*				- multiple website management for SYDO Hash Center
	* 				- encache-ing (support for set/get data even if they weren't stored/removed by SYDO Hash Center)
	*				- encrypted communication
	*				- P2P Hash Server Service
	*				- PGP Authentication/encryption
	*				- RESTful API Communication
	*				- understanding sql queries
	*				- Anti-DOS for SYDO Hash Center
	****/

include_once('aes.php');
class SYDO_Client
{
	static private $version  = '0.1';
	static private $dbs   	 = array('mysql' => 'dbs/mysql.php');
	static private $auth  	 = NULL;
	static private $token 	 = NULL;
	static private $valid 	 = FALSE;
	static private $db    	 = NULL;
	static private $acache   = array();
	static private $kcache   = array();
	static private $aes 	 = NULL;
	static private $defaults = array('validate' => 0,
									 'set'      => 1,
									 'get'      => 2,
									 'update'   => 3,
									 'delete'   => 4,
									 'flush'    => 5,
									 );
	static private $interface= NULL;
	static private $url      = 'http://localhost/SYDO%20-%20Secure%20Your%20Data%20by%20Obscurity/test/api.php';
	
	
   /** Init authentication details + database interface.
	 *
	 * @param string $auth authentication username
	 * @param string $token authentication token
	 * @param string $db database interface handler 
	 * @return bool FALSE on failure, TRUE on success
	 * @access public static
	 **/
	
	public static function init($auth, $token, $db = 'mysql', $handler)
	{
		$response = self::request(self::$defaults['validate'], array('auth' => $auth, 'token' => $token));
		try
		{
			if((bool)$response['status'] === TRUE)
			{
				if(in_array($db,array_keys(self::$dbs)))
				{
					self::$auth      = $auth;
					self::$token     = $token;
					self::$db        = $db;
					self::$valid     = TRUE;
					self::$aes       = new AES();
					self::$interface = new $db($handler); 
					return TRUE;
				}
				throw new Exception('Invalid database interface.', 1);
			} 
			throw new Exception('Invalid authentication details.',0);
		}
		catch(Exception $e)
		{
			echo 'Exception rised: '.$e->getMessage().'<br />';
			return FALSE;
		}
	}
	
	
   /** Encrypt input. If $key is FALSE a random key will be created.
	 *
	 * @param string $data the data that will be encrypted 
	 * @param string $key the key that will be used to find out the encryption hash for a specific data row, table and so on 
	 * @param string $hash  the hash key that will be used for encrypting $data dump with AES 256
	 * @return mixed FALSE on failure, array on success : array('encrypted' => data, 'hash' => data, 'key' => data)
	 * @access public static
	 **/
	public static function encrypt($data, $key = FALSE, $hash = FALSE)			
	{
		try
		{
			if(self::$valid == TRUE)
			{ 
				if($key == FALSE)
					$key  = sha1(mt_rand().time());	
				
				if(strlen($key) != 40) $key = sha1($key); //key control
					$hash = self::remote('get', $key);
				  	
				if($hash == FALSE)
				{
					$hash      = sha1(mt_rand().time());
					self::remote('set', $key, NULL, $hash);			
				}
				if(!isset(self::$kcache[$key])) self::$kcache[$key] = $hash; //cache control
					
				$encrypted = self::$aes->encrypt($data, $hash, 256);
				return array('encrypted' => $encrypted, 'hash' => $hash, 'key' => $key);
			}
			throw new Exception('Please provide authentication details with init() function.',2);
		}
		catch(Exception $e)
		{			
			echo 'Exception rised: '.$e->getMessage().'<br />';
			return FALSE;
		} 
	}
	
	
   /** Decrypt input. If $key is FALSE a remote request will be performed to get the hash key.
	 *
	 * @param string $data the data that will be decrypted (if needed, see $key/$hash)
	 * @param string $hash the key used for finding out the encryption hash for current data, if needed
	 * @param string $key the key that will be used to find out the encryption hash for a specific data row, table and so on
	 * @param string $hash the hash key that will be used for decrypting $data dump with AES 256
	 * @return mixed FALSE on failure, string on success
	 * @access public static
	 **/
	public static function decrypt($data, $hash, $key = FALSE)
	{
		try
		{
			if(self::$valid == TRUE)
			{
				if($key != FALSE)
				{
					if(strlen($key) != 40) $key = sha1($key); //key control
					$hash = self::remote('get', $key);	
				}
				
				if($hash !== FALSE)
				{
					$decrypted = self::$aes->decrypt($data, $hash, 256);
					return $decrypted;	
				}
				throw new Exception('Invalid key.',9);
			}
			throw new Exception('Please provide authentication details with init() function.',2);
		}
		catch(Exception $e)
		{			
			echo 'Exception rised: '.$e->getMessage().'<br />';
			return FALSE;
		} 
	}
	
	
   /** Negociate with SYDO Hash Center to perform some actions : set, get, update, delete keys.
	 *
	 * @param string $action the action handler, but the user friendly version, that will be transformed into a SYDO Hash Center understandable version
	 * @param string $key the key that will be used to find out the encryption hash for a specific data row, table and so on
	 * @param string $data the data that will be encrypted (if needed, see $encrypt)
	 * @param string $hash the hash key that will be used for encrypting $data dump with AES 256
	 * @param bool   $encrypt data must be encrypted or not
	 * @param bool   $cache store in cache and perform advanced requests that will negociate with SYDO Hash Center one time
	 * @return mixed FALSE on failure, string/object on success
	 * @access public static
	 **/
	public static function remote($action = 'set', $key = FALSE, $data = NULL, $hash = FALSE, $encrypt = FALSE, $cache = TRUE)
	{
		try
		{			
			if(self::$valid == TRUE)
			{
				if(strlen($key) != 40) 
					$key = sha1($key); //key control
									
				if($encrypt == TRUE && $data != NULL && ($key !== FALSE || $hash !== FALSE))
				{ 					
					$data = self::encrypt($data, $key, $hash);
					$hash = $data['hash'];
					$key  = $data['key'];
					$data = $data['encrypted'];
				}
				
				switch($action)
				{
					//have temporary cache support for actions and for keys
					case 'set': 
						if($cache == TRUE)
						{
							self::$acache[self::$defaults[$action]][] = array('key'  => $key,
														    				  'hash' => $hash);
						}
						else
						{
							$response = self::request(self::$defaults[$action], array('auth' => self::$auth, 'token' => self::$token, 'hash' => $hash, 'key' => $key));	
							if((bool)$response['status'] != TRUE)
								throw new Exception($response['error'], $response['code']);
						}
						self::$kcache[$key] = $hash;
						return array('encrypted' => $data, 'hash' => $hash, 'key' => $key);
					break;
					case 'get':
					//have temporary cache support for keys
						if($key != FALSE)
						{
							if(!isset(self::$kcache[$key]))
							{
								$response = self::request(self::$defaults[$action], array('auth' => self::$auth, 'token' => self::$token, 'key' => $key));
								if((bool)$response['status'] == TRUE)
									$hash = $response['message'];
								else
									throw new Exception($response['error'], $response['code']);
							}
							else
							{
								$hash = self::$kcache[$key];
							} 
							
							if($data != NULL)
								return self::decrypt($data, $hash);
							else
								return $hash;
						} 
						throw new Exception('Invalid input.', 5);
					break;
					case 'update':
						//nothing to do, your data are locally, you can use the same hash password
					break;
					//have cache support
					case 'delete':
						if($key != FALSE)
						{
							if($cache == TRUE)
							{
								self::$acache[self::$defaults[$action]][] = array('key' => $key);
							}
							else
							{							
								$response = self::request(self::$defaults[$action], array('auth' => self::$auth, 'token' => self::$token, 'key' => $key));
								if((bool)$response['status'] != TRUE) 
									throw new Exception($response['error'], $response['code']);
							}
							unset(self::$kcache[$key]);
							return TRUE;
						}
						throw new Exception('Invalid input.', 6);
					break;
				}
			}
			throw new Exception('Please provide authentication details with init() function.',7);
		}
		catch(Exception $e)
		{
			echo 'Exception rised: '.$e->getMessage().'<br />';
			return FALSE;
		}
	}
	
	
   /** Flush SYDO Cache Client. Perform a dump request to SYDO Hash Center that will perform all updates within a single request.
	 *
	 * @return bool FALSE on failure, TRUE on success
	 * @access public static
	 **/
	public static function flush()
	{
		try
		{ 
			$response = self::request(self::$defaults['flush'], array('auth' => self::$auth, 'token' => self::$token, 'cache' => urlencode(json_encode(self::$acache))));
			if((bool)$response['status'] == TRUE)
			{
				self::$acache = array();
				return TRUE;
			}
			throw new Exception($status['error'], $status['code']);
		}
		catch(Exception $e)
		{
			echo 'Exception rised: '.$e->getMessage().'<br />';
			return FALSE;	
		}
	}
	
   /** Perform a request to SYDO Hash Center
	 *
	 * @param int $type the action handler that will be understandable for the SYDO Hash Center
	 * @param array $params are the data identification/dependencies for current action
	 *
	 * @return mixed FALSE on failure, decoded JSON on success
	 * @access private static
	 **/
	private static function request($type, $params = array())
	{
		$ch = curl_init();
		if(!isset($params['auth'], $params['token'])) return array('status' => FALSE, 'error' => 'Invalid request params.', 'code' => -1);
		$agent = 'SYDO Client v'.self::$version.' | '.$params['auth'].' | '.$params['token'];
		unset($params['auth'], $params['token']);
		
		curl_setopt ($ch, CURLOPT_URL, self::$url.'?e='.$type);		
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt ($ch, CURLOPT_USERAGENT, $agent);
		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_REFERER, '');
		if(is_array($params))
		{
			curl_setopt ($ch, CURLOPT_POST, 1);
			curl_setopt ($ch, CURLOPT_POSTFIELDS, implode('&',array_map(create_function('$a,$b','return $a."=".urlencode($b);'),array_keys($params),array_values($params))));
		}	
		
		$result = curl_exec ($ch); 
				
		curl_close($ch);  
		return json_decode($result, TRUE);
	}
	
	/**
	  * Hook for static function to call even if they belongs to SQL interface class
	  **/
	public static function __callStatic($method, $args)
	{
		if(method_exists(__CLASS__, $method)) 
        	return call_user_func_array(array(__CLASS__, $method), $args); 
        elseif(method_exists(self::$interface, $method))
			return call_user_func_array(array(self::$interface, $method), $args);
		return FALSE;
	}
}

?>