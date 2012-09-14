<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * This class can act as an API server, handling API requests
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Kernel
 * @link		http://www.invisionpower.com
 * @since		Friday 6th January 2006 (12:24)
 * @version		$Revision: 10721 $
 *
 * Examples of sending and receiving API data
 * <code>
 * # APPLICATION: SEND AN API REQUEST AND PARSE DATA
 * $api_server->apiSendRequest( 'http://www.domain.com/xmlrpc.php', 'get_members', array( 'name' => 'matt', 'email' => 'matt@email.com' ) );
 * # APPLICATION: PICK UP REPLY AND PARSE
 * print_r( $api_server->params );
 *
 * # SERVER: PARSE DATA, MAKE DATA AND RETURN
 * $api_server->apiDecodeRequest( $_SERVER['RAW_HTTP_POST_DATA'] );
 * print $api_server->method_name;
 * print_r( $api_server->params );
 * # SERVER: SEND DATA BACK
 * # Is complex array, so we choose to encode and send with the , 1 flag
 * $api_server->apiSendReply( array( 'matt' => array( 'email' => 'matt@email.com', 'joined' => '01-01-2005' ) ), 1 );
 * </code>
 *
 */

if ( ! defined( 'IPS_CLASSES_PATH' ) )
{
	/**
	* Define classes path
	*/
	define( 'IPS_CLASSES_PATH', dirname(__FILE__) );
}

class classApiServer
{
	/**
	 * XML-RPC class
	 *
	 * @var		object
	 */
	public $xmlrpc;
	
	/**
	 * Raw incoming data
	 *
	 * @var		string
	 */
	public $raw_request;
	
	/**
	 * Requested method name
	 *
	 * @var		string
	 */
	protected $method_name;
	
	/**
	 * Raw data incoming
	 *
	 * @var		string
	 */
	protected $_raw_in_data;
		
	/**
	 * Raw data output
	 *
	 * @var		string
	 */
	protected $_raw_out_data;
	
	/**
	 * Method params
	 *
	 * @var		array
	 */
	public $params			= array();
	
	/**
	 * XML-RPC serialized 64 key
	 *
	 * @var		string
	 */
	protected $serialized_key = '__serialized64__';
	
	/**
	 * Server function object
	 *
	 * @var		object
	 */
	protected $xml_server;
	
	/**
	 * Return cookie information
	 *
	 * @var		array
	 */
	public $cookies = array();
	
	/**
	 * XML-RPC cookie serialized 64 key
	 *
	 * @var		string
	 */
	protected $cookie_serialized_key = '__cookie__serialized64__';
	
	/**#@+
	 * HTTP authentication credentials
	 *
	 * @var		string
	 */
	public $auth_user = '';
	public $auth_pass = '';
	/*#@-*/
	
	/**
	 * Errors array
	 *
	 * @var		array
	 */
	public $errors = array();
	
    /**
	 * Constructor
	 *
	 * @return	@e void
	 */
	public function __construct()
	{
		if ( ! is_object( $this->xmlrpc ) )
		{
			require_once( IPS_KERNEL_PATH . '/classXmlRpc.php' );/*noLibHook*/
			$this->xmlrpc = new classXmlRpc();
		}
		
		$this->cookies = array();
	}

	/**
	 * Decoding an incoming request.  If incoming data is not passed to this method, we will check the standard php://input stream.  Returns the method name to call.
	 *
	 * @param    string    Incoming data
	 * @return   @e string
	 */
	public function decodeRequest( $incoming='' )
    {
        if ( ! $incoming )
        {
            $incoming = file_get_contents( "php://input" );
        }

        //-----------------------------------------
        // Get data and dispatch
        //-----------------------------------------

        $this->apiDecodeRequest( $incoming );

		$this->raw_request = $incoming;
		
        $api_call = explode( ".", $this->method_name );
        
		if ( count($api_call) > 1 )
        {
            $this->method_name = $api_call[1];

            return $api_call[0];
        }
        else
        {
            return 'default';
        }
    }

	/**
	 * Add object map to this class
	 *
	 * @param	object		Server class object
	 * @param	string		Document charset
	 * @return	@e boolean
	 */
	public function addObjectMap( $server_class, $doc_type='UTF-8' )
	{
		$this->xmlrpc->doc_type = $doc_type;
		
		if ( is_object( $server_class ) )
		{
			$this->xml_server =& $server_class;

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Get the XML-RPC data.  If the XML server is not available, returns false.  Otherwise, if method name is valid, the defined method is called.  If method name is 
	 *	invalid, an error is sent back to the requester and the script exits.
	 *
	 * @param	string		Incoming data
	 * @return	@e mixed
	 */
	public function getXmlRpc( $incoming='' )
	{
		if ( ! $this->xml_server )
		{
			return false;
		}
		
		//-----------------------------------------
		// Got function?
		//-----------------------------------------
		
		if ( $this->method_name AND is_array( $this->xml_server->__dispatch_map[ $this->method_name ] ) )
		{
			$func    = $this->method_name;
			$_params = array();
			
			//-----------------------------------------
			// Figure out params to use...
			//-----------------------------------------
			
			if ( is_array( $this->params ) and is_array( $this->xml_server->__dispatch_map[ $func ]['in'] ) )
			{
				foreach( $this->xml_server->__dispatch_map[ $func ]['in'] as $field => $type )
				{
					$_var = $this->params[ $field ];
					
					switch ($type)
					{
						default:
						case 'string':
							$_var = (string) $_var;
							break;
		                case 'int':
		 				case 'i4':
							$_var = (int)    $_var;
							break;
		                case 'double':
							$_var = (double) $_var; 
							break;
		                case 'boolean':
							$_var = (bool)   $_var;
							break;
						case 'base64':
							$_var = trim($_var);
							break;
						case 'struct':
							$_var = is_array($_var) ? $_var : (string) $_var;
							break;
		            }
		
					$_params[ $field ] = $_var;
				}
			}
			
			if ( is_array( $_params ) )
			{
				@call_user_func_array( array( &$this->xml_server, $func), $_params );
			}
			else
			{
				@call_user_func( array( &$this->xml_server, $func), $_params );
			}
		}
		else
		{
			//-----------------------------------------
			// Return false
			//-----------------------------------------
			
			$this->apiSendError( 100, 'No methodRequest function -' . htmlspecialchars( $this->method_name ) . ' defined / found' );
			exit();
		}
	}

	/**
	 * Set a cookie for the API request
	 *
	 * @param	array		Array of cookie params to send ('name' is required)
	 * @return	@e void
	 */
	public function apiAddCookieData( $data )
	{
		if ( $data['name'] )
		{
			$this->cookies[ $data['name'] ] = $data;
		}
	}

	/**
	 * Send an API reply back to the client
	 *
	 * @param	array		Array of params to send
	 * @param	int  		1 = Complex data: Encode before sending, 0 = Send as-is
	 * @param	array 		Forced data type mapping
	 * @return	@e void
	 */
	public function apiSendReply( $data=array(), $complex_data=0, $force=array() )
	{
		//-----------------------------------------
		// Cookies?
		//-----------------------------------------
		
		if ( is_array( $this->cookies ) AND count( $this->cookies ) )
		{
			$data[ $this->cookie_serialized_key ] = $this->_encodeBase64Array( $this->cookies );
			$this->xmlrpc->map_type_to_key[ $this->cookie_serialized_key ] = 'base64';
		}
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! is_array( $data ) )
		{
			$this->xmlrpc->returnValue( $data );
		}
		elseif ( ! count( $data ) )
		{
			# No data? Just return true
			$this->xmlrpc->returnTrue();
		}
		
		//-----------------------------------------
		// Complex data?
		//-----------------------------------------
		
		if ( $complex_data )
		{
			$_tmp = $data;
			$data = array();
			$data[ $this->serialized_key ] = $this->_encodeBase64Array( $_tmp );
			$this->xmlrpc->map_type_to_key[ $this->serialized_key ] = 'base64';
		}
		
		//-----------------------------------------
		// Force type?
		//-----------------------------------------

		if ( is_array($force) AND count($force) > 0 )
		{
			foreach ( $force as $key => $type )
			{
				$this->xmlrpc->map_type_to_key[ $key ] = $type;
			}
		}
		
		//-----------------------------------------
		// Send...
		//-----------------------------------------
		
		$this->xmlrpc->returnParams( $data );
	}

	/**
	 * Reply to API request with an error code and error message
	 *
	 * @param	int 		Error Code
	 * @param	string  	Error message
	 * @return	@e void
	 */
	public function apiSendError( $error_code, $error_msg )
	{
		$this->xmlrpc->returnError( $error_code, $error_msg );
	}
	
	/**
	 * Decode an API Request
	 *
	 * @param	string		Raw data picked up
	 * @return	@e void
	 */
	public function apiDecodeRequest( $raw_data )
	{
		//-----------------------------------------
		// Get data...
		//-----------------------------------------
		
		$raw = $this->xmlrpc->decodeXmlRpc( $raw_data );
		
		//-----------------------------------------
		// Process return data
		//-----------------------------------------
		
		$this->apiProcessData( $raw );
	}

	/**
	 * Send an API Request
	 *
	 * @param	string		URL to send request to
	 * @param	string		Method name for API to pick up
	 * @param	array		Data to send
	 * @param	int  		1 = Complex data: Encode before sending, 0 = Send as-is
	 * @return	@e boolean
	 */
	public function apiSendRequest( $url, $method_name, $data=array(), $complex_data=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$return_data             = array();
		$raw                     = array();
		$this->xmlrpc->errors    = array();
		$this->xmlrpc->auth_user = $this->auth_user;
		$this->xmlrpc->auth_pass = $this->auth_pass;
		
		//-----------------------------------------
		// Cookies?
		//-----------------------------------------
		
		if ( is_array( $this->cookies ) AND count( $this->cookies ) )
		{
			$data[ $this->cookie_serialized_key ] = $this->_encodeBase64Array( $this->cookies );
			$this->xmlrpc->map_type_to_key[ $this->cookie_serialized_key ] = 'base64';
		}
		
		//-----------------------------------------
		// Complex data?
		//-----------------------------------------
		
		if ( $complex_data )
		{
			$_tmp = $data;
			$data = array();
			$data[ $this->serialized_key ] = $this->_encodeBase64Array( $_tmp );
			$this->xmlrpc->map_type_to_key[ $this->serialized_key ] = 'base64';
		}
		
		//-----------------------------------------
		// Get data...
		//-----------------------------------------
		
		$return_data = $this->xmlrpc->sendXmlRpc( $url, $method_name, $data );
		
		if ( count( $this->xmlrpc->errors ) )
		{
			$this->errors = $this->xmlrpc->errors;
			return false;
		}
		
		//-----------------------------------------
		// Process return data
		//-----------------------------------------
	
		$this->apiProcessData( $return_data );
		
		return true;
	}

	/**
	 * Process response from an API request
	 *
	 * @param	array	Raw array
	 * @return	@e array
	 */
	public function apiProcessData( $raw=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$_params              = $this->xmlrpc->getParams( $raw );
		$this->method_name    = $this->xmlrpc->getMethodName( $raw );
		$this->params         = array();
		$this->_raw_in_data   = var_export( $raw, TRUE );
		$this->_raw_out_data  = var_export( $_params, TRUE );
		
		//-----------------------------------------
		// Debug?
		//-----------------------------------------
		
		if ( IPS_XML_RPC_DEBUG_ON )
		{
			$this->xmlrpc->addDebug( "API_PROCESS_DECODE: IN PARAMS:  " . var_export( $raw, TRUE ) );
			$this->xmlrpc->addDebug( "API_PROCESS_DECODE: OUT PARAMS: " . var_export( $_params, TRUE ) );
		}
		
		//-----------------------------------------
		// Fix up params
		//-----------------------------------------
		
		if ( isset($_params[0]) AND is_array( $_params[0] ) )
		{
			foreach( $_params[0] as $k => $v )
			{
				if ( $k != '' && $k == $this->serialized_key )
				{
					$_tmp = $this->_decodeBase64Array( $v );
					
					if ( is_array( $_tmp ) and count( $_tmp ) )
					{
						$this->params = array_merge( $this->params, $_tmp );
					}
				}
				else if ( $k != '' && $k == $this->cookie_serialized_key )
				{
					$_cookies = $this->_decodeBase64Array( $v );
					
					if ( is_array( $_cookies ) and count( $_cookies ) )
					{
						foreach( $_cookies as $cookie_data )
						{
							if ( $cookie_data['sticky'] == 1 )
					        {
					        	$cookie_data['expires'] = time() + 60*60*24*365;
					        }
							
							$cookie_data['path'] = $cookie_data['path'] ? $cookie_data['path'] : '/';
							
					        @setcookie( $cookie_data['name'], $cookie_data['value'], $cookie_data['expires'], $cookie_data['path'], $cookie_data['domain'] );
					
							if ( IPS_XML_RPC_DEBUG_ON )
							{
								$this->xmlrpc->addDebug( "API_PROCESS_DECODE: SETTING COOKIE:  " . var_export( $cookie_data, TRUE ) );
							}
						}
					}
				}
				else
				{
					$this->params[ $k ] = $v;
				}
			}
		}
		else if ( is_array( $_params ) )
		{
			$i = 0;

			foreach( $_params as $v )
			{
				$this->params['param'.$i] = $v;
				$i++;
			}
		}
	}

	/**
	 * Serialize and base64-encode array
	 *
	 * @param	array	Raw array
	 * @return	@e string
	 */
	protected function _encodeBase64Array( $array )
	{
		return base64_encode( serialize( $array ) );
	}

	/**
	 * Unserialize an array of data.  Note that base64 decoding already occurs in the XML-RPC library
	 *
	 * @param	string  Serialized string
	 * @return	@e array
	 * @link	http://community.invisionpower.com/tracker/issue-18676-xml-rpcapiserver-params-not-unserialized/
	 * @see		classXmlRpc::adjustValue()
	 */
	protected function _decodeBase64Array( $data )
	{
		if ( ! is_array( $data ) )
		{
			//return unserialize( base64_decode( $data ) );
			/**
			 * @link	http://community.invisionpower.com/tracker/issue-18676-xml-rpcapiserver-params-not-unserialized/
			 * base64 data types are properly base-64 decoded in the XML-RPC library now, so it is redundant to do so here now
			 */
			return unserialize( $data );
		}
		else
		{
			return $data;
		}
	}
}
