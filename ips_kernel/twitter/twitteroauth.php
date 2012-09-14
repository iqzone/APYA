<?php
/*
 * Abraham Williams (abraham@abrah.am) http://abrah.am
 *
 * Basic lib to work with Twitter's OAuth beta. This is untested and should not
 * be used in production code. Twitter's beta could change at anytime.
 *
 * Code based on:
 * Fire Eagle code - http://github.com/myelin/fireeagle-php-lib
 * twitterlibphp - http://github.com/jdp/twitterlibphp
 *
 * Modified by Matt Mecham (Invision Power Services)
 * - Option to return json_decode in ARRAY not OBJ
 * - Added POST / sockets fall back if curl disabled
 */

/* Load OAuth lib. You can find it at http://oauth.net */
require_once('OAuth.php');/*noLibHook*/

/**
 * Twitter OAuth class
 */
class TwitterOAuth {
  /* Contains the last HTTP status code returned */
  public $http_code;
  /* Contains the last API call */
  public $last_api_call;
  /* Set up the API root URL */
  public $host = "https://api.twitter.com/1/";
  /* Set timeout default */
  public $timeout = 30;
  /* Set connect timeout */
  public $connecttimeout = 30; 
  /* Verify SSL Cert */
  public $ssl_verifypeer = FALSE;
  /* Respons format */
  public $format = 'json';
  /* Decode returne json data */
  public $decode_json = TRUE;
  /* Allow decoded JSON to be return as an array */
  public $json_as_array = TRUE;
  /* Immediately retry the API call if the response was not successful. */
  //public $retry = TRUE;




  /**
   * Set API URLS
   */
  function accessTokenURL()  { return 'https://twitter.com/oauth/access_token'; }
  function authenticateURL() { return 'https://twitter.com/oauth/authenticate'; }
  function authorizeURL()    { return 'https://twitter.com/oauth/authorize'; }
  function requestTokenURL() { return 'https://twitter.com/oauth/request_token'; }

  /**
   * Debug helpers
   */
  function lastStatusCode() { return $this->http_status; }
  function lastAPICall() { return $this->last_api_call; }

  /**
   * construct TwitterOAuth object
   */
  function __construct($consumer_key, $consumer_secret, $oauth_token = NULL, $oauth_token_secret = NULL) {
    $this->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
    $this->consumer = new OAuthConsumer($consumer_key, $consumer_secret);
    if (!empty($oauth_token) && !empty($oauth_token_secret)) {
      $this->token = new OAuthConsumer($oauth_token, $oauth_token_secret);
    } else {
      $this->token = NULL;
    }
  }


  /**
   * Get a request_token from Twitter
   *
   * @returns a key/value array containing oauth_token and oauth_token_secret
   */
  function getRequestToken($oauth_callback = NULL) {
    $parameters = array();
    if (!empty($oauth_callback)) {
      $parameters['oauth_callback'] = $oauth_callback;
    } 
    $request = $this->oAuthRequest($this->requestTokenURL(), 'GET', $parameters);
    $token = OAuthUtil::parse_parameters($request);
    $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
    return $token;
  }

  /**
   * Get the authorize URL
   *
   * @returns a string
   */
  function getAuthorizeURL($token, $sign_in_with_twitter = TRUE) {
    if (is_array($token)) {
      $token = $token['oauth_token'];
    }
    if (empty($sign_in_with_twitter)) {
      return $this->authorizeURL() . "?oauth_token={$token}";
    } else {
       return $this->authenticateURL() . "?oauth_token={$token}";
    }
  }

  /**
   * Exchange the request token and secret for an access token and
   * secret, to sign API calls.
   *
   * @returns array("oauth_token" => the access token,
   *                "oauth_token_secret" => the access secret)
   */
  function getAccessToken($oauth_verifier = FALSE) {
    $parameters = array();
    if (!empty($oauth_verifier)) {
      $parameters['oauth_verifier'] = $oauth_verifier;
    }
    $request = $this->oAuthRequest($this->accessTokenURL(), 'GET', $parameters);
    $token = OAuthUtil::parse_parameters($request);
    $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
    return $token;
  }

  /**
   * GET wrappwer for oAuthRequest.
   */
  function get($url, $parameters = array()) {
    $response = $this->oAuthRequest($url, 'GET', $parameters);
    if ($this->format === 'json' && $this->decode_json) {
      return json_decode($response, $this->json_as_array);
    }
    return $response;
  }
  
  /**
   * POST wreapper for oAuthRequest.
   */
  function post($url, $parameters = array()) {
    $response = $this->oAuthRequest($url, 'POST', $parameters);
    if ($this->format === 'json' && $this->decode_json) {
      return json_decode($response, $this->json_as_array);
    }
    return $response;
  }

  /**
   * DELTE wrapper for oAuthReqeust.
   */
  function delete($url, $parameters = array()) {
    $response = $this->oAuthRequest($url, 'DELETE', $parameters);
    if ($this->format === 'json' && $this->decode_json) {
      return json_decode($response, $this->json_as_array);
    }
    return $response;
  }

  /**
   * Format and sign an OAuth / API request
   */
  function oAuthRequest($url, $method, $parameters) {
    if (strrpos($url, 'https://') !== 0 && strrpos($url, 'http://') !== 0) {
      $url = "{$this->host}{$url}.{$this->format}";
    }
    $request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $parameters);
    $request->sign_request($this->sha1_method, $this->consumer, $this->token);
    switch ($method) {
    case 'GET':
      return $this->http($request->to_url(), 'GET');
    default:
      return $this->http($request->get_normalized_http_url(), $method, $request->to_postdata());
    }
  }

  /**
   * Make an HTTP request
   *
   * @return API results
   */
  function http($url, $method, $postfields = NULL) {
  
  	if ( function_exists( 'curl_init' ) AND function_exists("curl_exec") )
	{
	    $ci = curl_init();
	    /* Curl settings */
	    curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout);
	    curl_setopt($ci, CURLOPT_TIMEOUT, $this->timeout);
	    curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
	    curl_setopt($ci, CURLOPT_HTTPHEADER, array('Expect:'));
	    curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $this->ssl_verifypeer);
	
	    switch ($method) {
	      case 'POST':
	        curl_setopt($ci, CURLOPT_POST, TRUE);
	        if (!empty($postfields)) {
	          curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
	        }
	        break;
	      case 'DELETE':
	        curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
	        if (!empty($postfields)) {
	          $url = "{$url}?{$postfields}";
	        }
	    }
	
	    curl_setopt($ci, CURLOPT_URL, $url);
	    $response = curl_exec($ci);
	    $this->http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
	    $this->last_api_call = $url;
	    curl_close ($ci);
	    
	    if( $response )
	    {
	    	return $response;
    	}
	}

	$url_parts  = @parse_url($url);
	
	if ( ! $postfields )
	{
		$_c = str_replace( '&amp;', '&', $url_parts['query'] );
		$_a = array();
		
		foreach( explode( '&', $_c ) as $meh )
		{
			list( $k, $v ) = explode( '=', $meh );
			
			$_a[ $k ] = urldecode( $v );
		}
		
		$postfields = http_build_query( $_a );
	}
	
	if ( $method != 'POST' )
	{
		$data = @file_get_contents( $url );
		
		$this->http_code =  ( strstr( $data, 'oauth_token' ) ) ? 200 : 401;
		$this->last_api_call = $url;
		
		return trim( $data );
	}
	else
	{	
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
		
		$header  = "$method {$path} HTTP/1.0\r\n";
		$header .= "User-Agent: Twitter\r\n";
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
		$this->http_code     = substr( $data, 9, 3 );
		$this->last_api_call = $url;
		
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
}
