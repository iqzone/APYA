<?php

/**
 * Simple oAuth wrapper for Facebook
 * Designed to stream line log in  and token exchange
 * By Matt Mecham (Invision Power Services, Inc)
 * http://www.invisionpower.com
 * April 2010
 *
 * Version 0.1
 */

class FacebookOAuth
{
	/**
	 * Facebook Application ID
	 * @var		int
	 * @access	protected
	 */
	protected $_appId;
	 
	 /**
	  * Facebook Application Secret 
	  * @var	string
	  * @access	protected
	  */
	protected $_appSecret;
	 
	/**
	 * Call back URL
	 * URL to redirect oAuth requests to after processing at Facebook
	 * @var	string
	 * @access	protected
	 */
	protected $_callBackUrl;
	
	/**
	 * Permissions scope
	 * Array containing required permissions
	 * @var		array
	 * @access	protected
	 */
	protected $_permsScope = array();
	
	/**
	 * Form Factor
	 * @link http://developers.facebook.com/docs/authentication/
	 * @var		string
	 * @access	protected
	 */
	protected $_formFactor = '';
	
	/**
	 * Last http code (200, 403, etc)
	 * @var	string
	 * @access	public
	 */
	public $httpCode;
	
	/**
	 * Last API call
	 * @var	string
	 * @access	public
	 */
	public $lastApiCall;
	
	/**
	 * Timeout
	 * @var	int
	 * @access	public
	 */
	public $timeout = 30;
	
	/**
	 * Connect timeout
	 * @var	int
	 * @access	public
	 */
	public $connectTimeout = 30; 
	
	/**
	 * SSL verify peer
	 * @var	string
	 * @access	public
	 */
	public $sslVerifyPeer = FALSE;
  
	/**
	 * Constant for authorize URL
	 */
	const AUTHORIZE_URL = 'https://graph.facebook.com/oauth/authorize';
	 
	/**
	 * Constant for access token
	 */
	const ACCESS_TOKEN_URL = 'https://graph.facebook.com/oauth/access_token';
	 
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	int		application id
	 * @param	string	application secret
	 * @param	string	callback URL
	 * @param	string	Form factor
	 * @param	array	Array of permissions array( 'email', 'publish_stream' ), etc
	 */
	public function __construct( $appId, $appSecret, $callBackUrl, $permsScope, $formFactor='page' )
	 {
	 	$this->_appId       = $appId;
	 	$this->_appSecret   = $appSecret;
	 	
	 	$this->setCallBackUrl( $callBackUrl );
	 	$this->setPermissionsScope( $permsScope );
	 	$this->setFormFactor( $formFactor );
	 }
	 
	/**
	 * Set FormFactor
	 * Although we require this in the contructor, the calling class may wish
	 * to change it to add other parameters
	 *
	 * @access	public
	 * @param	string
	 */
	public function setFormFactor( $formFactor )
	{
		if ( $formFactor )
		{
	 		$this->_formFactor = $formFactor;
	 	}
	}
	
	/**
	 * Set Call back URL
	 * Although we require this in the contructor, the calling class may wish
	 * to change it to add other parameters
	 *
	 * @access	public
	 * @param	string
	 */
	public function setCallBackUrl( $url )
	{
		if ( $url )
		{
	 		$this->_callBackUrl = $url;
	 	}
	}
	
	/**
	 * Set Permissions required for auth token
	 * Although we require this in the contructor, the calling class may wish
	 * to change it to add other parameters
	 *
	 * @access	public
	 * @param	string
	 */
	public function setPermissionsScope( $permsScope )
	{
		if ( is_array( $permsScope ) )
		{
	 		$this->_permsScope = $permsScope;
	 	}
	 	else if ( strstr( $permsScope, ',' ) )
	 	{
	 		$this->_permsScope = explode( ',', str_replace( ' ', '', $permsScope ) );
	 	}
	}
	 	 
	/**
	 * Get Authorize URL
	 * Returns a full authorize URL
	 *
	 * @access	public
	 * @return	string		URL
	 */
	public function getAuthorizeUrl()
	{
		$_e = ( count( $this->_permsScope ) ) ? '&scope=' . implode( ',', $this->_permsScope ) : '';
		$_f = ( $this->_formFactor ) ? '&display=' . $this->_formFactor : '';
		
	 	return self::AUTHORIZE_URL . '?client_id=' . $this->_appId . '&redirect_uri=' . urlencode( $this->_callBackUrl ) . $_e . $_f;
	}
	
	/**
	 * Authorize
	 * Accepts a code, uses CURL to return a final access token
	 *
	 * @access	public
	 * @param	string		Code from authorize
	 * @return	mixed		Access token or JSON error
	 */
	public function getAccessToken( $code )
	{
	 	$url = self::ACCESS_TOKEN_URL . '?client_id=' . $this->_appId . '&redirect_uri=' . urlencode( $this->_callBackUrl ) . '&client_secret=' . $this->_appSecret . '&code=' . rawurlencode( $code );
	 	
	 	/* Send it and hope to receive access_token=blah */
	 	$result = $this->_send( $url, 'GET' );
	 	
	 	$params = null;
	 	
    	parse_str($result, $params);

	 	if ( $params['access_token'] )
	 	{
	 		return $params['access_token'];
	 	}
	 	else
	 	{
	 		/* Likely to be an error in json */
	 		return json_decode( $result );
	 	}
	}
	
	/**
	 * Make an HTTP request
 	 *
 	 * @param		protected
 	 * @param		string		URL
 	 * @param		string		Method (post or get)
 	 * @param		array		Post data to send
	 * @return API results
	*/
	protected function _send($url, $method='POST', $postfields=NULL)
	{
		/* Compress post fields */
		if ( is_array( $postfields ) AND count( $postfields ) )
		{
			$_postfields = $postfields;
			$postfields  = '';
			
			foreach( $_postfields as $k => $v )
			{
				$postfields .= $k . '=' . $v . '&';
			}
			
			$postfields = trim( $postfields, '&' );
		}
		
		/* Attempt CURL */
		if ( function_exists( 'curl_init' ) AND function_exists("curl_exec") )
		{
		    $ci = curl_init();
		    /* Curl settings */
		    curl_setopt( $ci, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout );
		    curl_setopt( $ci, CURLOPT_TIMEOUT, $this->timeout );
		    curl_setopt( $ci, CURLOPT_RETURNTRANSFER, TRUE );
		    curl_setopt( $ci, CURLOPT_HTTPHEADER, array('Expect:') );
		    curl_setopt( $ci, CURLOPT_SSL_VERIFYPEER, $this->sslVerifyPeer );
		
		    switch ($method)
		    {
				case 'POST':
		        	curl_setopt($ci, CURLOPT_POST, TRUE);
		        	if ( ! empty( $postfields) )
		        	{
		          		curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
		       		}
		        break;
				case 'DELETE':
		     		curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
		        	if ( ! empty( $postfields ) )
		        	{
						$url = "{$url}?{$postfields}";
		        	}
		        break;
		    }
		
		    curl_setopt( $ci, CURLOPT_URL, $url );
		    $response          = curl_exec( $ci );
		    $this->httpCode    = curl_getinfo( $ci, CURLINFO_HTTP_CODE );
		    $this->lastApiCall = $url;
		    curl_close ($ci);
		    
		    if( $response )
		    {
		    	return $response;
	    	}
		}

		$url_parts = @parse_url($url);
		
		if ( ! $postfields )
		{
			$postfields = str_replace( '&amp;', '&', $url_parts['query'] );
		}
		
		/* Use SSL rather than https */
		$url_parts['scheme'] = ( $url_parts['scheme'] == 'https' ) ? 'ssl' : $url_parts['scheme'];
		
		$host = $url_parts['scheme'] . '://' . $url_parts['host'];
	 	$port = ( isset($url_parts['port']) ) ? $url_parts['port'] : ( $url_parts['scheme'] == 'https' || $url_parts['scheme'] == 'ssl' ? 443 : 80 );
	 	
	 	if ( !empty( $url_parts["path"] ) )
		{
			$path = $url_parts["path"];
		}
		else
		{
			$path = "/";
		}
		
		$header  = "POST {$path} HTTP/1.0\r\n";
		$header .= "Host: " . str_replace( array( 'http://', 'https://', 'ssl://' ), '', $host ) . "\r\n";
		$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$header .= "Content-Length: " . strlen($postfields) . "\r\n\r\n";
		
		if ( $fp = fsockopen( $host, $port, $errno, $errstr, $this->timeout ) )
		{
			socket_set_timeout($fp, $this->timeout);
			
			fwrite($fp, $header . $postfields);
			
			while( ! feof($fp) && ! $status['timed_out'] )		
			{
		  		$data .= fgets ($fp,8192);
		  		$status  = stream_get_meta_data($fp);
			}
			
			fclose($fp);
	 	}		
		
		/* Strip headers HTTP/1.1 ### ABCD */
		$this->httpCode     = substr( $data, 9, 3 );
		$this->lastApiCall = $url;
		
		/* Chuncked? */
		
		$_chunked	= false;
		
		if( preg_match( "/Transfer\-Encoding:\s*chunked/i", $data ) )
		{
			$_chunked	= true;
		}
		
		$tmp	= explode("\r\n\r\n", $data, 2);
		$data	= trim($tmp[1]);
		
		if( $_chunked )
		{
			$lines	= explode( "\n", $data );
			array_pop($lines);
			array_shift($lines);
			$data	= implode( "\n", $lines );
		}
		
		return $data;
	}


}