<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Wrapper for retrieving file contents.  Methods available for removing files and directories as well.
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Kernel
 * @link		http://www.invisionpower.com
 * @since		Tuesday 22nd February 2005 (16:55)
 * @version		$Revision: 10721 $
 */
 
if ( ! defined( 'IPS_CLASSES_PATH' ) )
{
	/**
	 * Define classes path
	 */
	define( 'IPS_CLASSES_PATH', dirname(__FILE__) );
}

class classFileManagement
{
	/**
	 * Use sockets flag
	 *
	 * @var		integer
	 */
	public $use_sockets	= 1;
	
	/**
	 * Error array
	 *
	 * @var		array
	 */
	public $errors		= array();
	
	/**
	 * Key prefix to identify communication strings
	 *
	 * @var		string
	 */
	public $key_prefix	= '';

	/**
	 * HTTP Status Code
	 *
	 * @var		integer
	 */	
	public $http_status_code	= 0;
	
	/**
	 * HTTP Status Text
	 *
	 * @var		string
	 */	
	public $http_status_text	= "";
	
	/**
	 * Raw HTTP header output
	 * 
	 * @var		string
	 */
	public $raw_headers			= '';
	
	/**#@+
	 * Set HTTP authentication parameters
	 *
	 * @var 	string
	 */
	public $auth_req		= 0;
	public $auth_user		= '';
	public $auth_pass		= '';
	public $auth_raw		= '';
	public $userAgent		= '';
	/**#@-*/
	
	/**
	 * Timeout setting
	 *
	 * @var		int
	 */
	public $timeout			= 15;
	
	/**
	 * Retrieve file contents from either a local file or URL, returning those contents
	 *
	 * @param	string		URI / File path
	 * @param	string		HTTP User
	 * @param	string		HTTP Pass
	 * @return	@e string
	 */
	public function getFileContents( $file_location, $http_user='', $http_pass='' )
	{
		//-------------------------------
		// INIT
		//-------------------------------
		
		$contents		= "";
		$file_location	= str_replace( '&amp;', '&', $file_location );
		
		//-----------------------------------------
		// Inline user/pass?
		//-----------------------------------------
		
		if ( $http_user and $http_pass )
		{
			$this->auth_req  = 1;
			$this->auth_user = $http_user;
			$this->auth_pass = $http_pass;
		}
		
		//-------------------------------
		// Hello
		//-------------------------------
		
		if ( ! $file_location )
		{
			return FALSE;
		}
		
		if ( ! stristr( $file_location, 'http://' ) AND ! stristr( $file_location, 'https://' ) )
		{
			//-------------------------------
			// It's a path!
			//-------------------------------
			
			if ( ! file_exists( $file_location ) )
			{
				$this->errors[] = "File '{$file_location}' does not exist, please check the path.";
				return;
			}
			
			$contents = $this->_getContentsWithFopen( $file_location );
		}
		else
		{
			//-------------------------------
			// Is URL, try curl and then fall back
			//-------------------------------
			
			if( ( $contents = $this->_getContentsWithCurl( $file_location ) ) === false )
			{
				if ( $this->use_sockets )
				{
					$contents = $this->_getContentsWithSocket( $file_location );
				}
				else
				{
					$contents = $this->_getContentsWithFopen( $file_location );
				}
			}
		}
		
		return $contents;
	}
	
	/**
	 * Sends a POST request to specified URL and returns the result
	 *
	 * @param	string		URI to post to
	 * @param	array   	Arry of post fields (key => value)
	 * @param	string		HTTP User
	 * @param	string		HTTP Pass
	 * @return	@e string
	 */
	public function postFileContents( $file_location='', $post_array=array(), $http_user='', $http_pass='' )
	{
		//-------------------------------
		// INIT
		//-------------------------------
		
		$contents		= "";
		$file_location	= str_replace( '&amp;', '&', $file_location );

		if ( ! is_array( $post_array ) OR ! count( $post_array ) )
		{
			return false;
		}
		
		if ( ! $file_location )
		{
			return false;
		}

		if( ( $contents = $this->_getContentsWithCurl( $file_location, $post_array ) ) === false )
		{
			$contents = $this->_getContentsWithSocket( $file_location, $post_array );
		}

		return $contents;
	}
	
	/**
	 * Get file contents (with PHP's fopen)
	 *
	 * @param	string		File location
	 * @return	@e string
	 */
	protected function _getContentsWithFopen( $file_location )
	{
		//-------------------------------
		// INIT
		//-------------------------------
		
		$buffer = "";
		
		@clearstatcache();
			
		if ( $FILE = fopen( $file_location, "r" ) )
		{
			@stream_set_timeout( $FILE, $this->timeout );
			$status = @stream_get_meta_data($FILE);
			
			while ( ! feof( $FILE ) && ! $status['timed_out'] )
			{
			   $buffer .= fgets( $FILE, 4096 );
			   
			   $status = stream_get_meta_data($FILE);
			}

			fclose($FILE);
		}
		
		if ( $buffer )
		{
			$this->http_status_code = 200;
		}
		
		return $buffer;
	}
	
	/**
	 * Get file contents (with sockets)
	 *
	 * @param	string		File location
	 * @param	array 		Data to post (automatically converts to POST request)
	 * @return	@e string
	 */
	protected function _getContentsWithSocket( $file_location, $post_array=array() )
	{
		//-------------------------------
		// INIT
		//-------------------------------
		
		$data				= null;
		
		//-------------------------------
		// Parse URL
		//-------------------------------
		
		$url_parts = @parse_url($file_location);
		
		if ( ! $url_parts['host'] )
		{
			$this->errors[] = "No host found in the URL '{$file_location}'!";
			return FALSE;
		}
		
		//-------------------------------
		// Finalize
		//-------------------------------
		
		$host = $url_parts['host'];
	 	$port = ( isset($url_parts['port']) ) ? $url_parts['port'] : ( $url_parts['scheme'] == 'https' ? 443 : 80 );

	 	//-------------------------------
	 	// Tidy up path
	 	//-------------------------------
	 	
	 	if ( !empty( $url_parts["path"] ) )
		{
			$path = $url_parts["path"];
		}
		else
		{
			$path = "/";
		}
 
		if ( !empty( $url_parts["query"] ) )
		{
			$path .= "?" . $url_parts["query"];
		}
	 	
	 	//-------------------------------
	 	// Open connection
	 	//-------------------------------
	 	
	 	if ( ! $fp = @fsockopen( $url_parts['scheme'] == 'https' ? "ssl://" . $host : $host, $port, $errno, $errstr, $this->timeout ) )
	 	{
			$this->errors[] = "Could not establish a connection with {$host}";
			return FALSE;
		
		}
		else
		{
			$final_carriage	= ( $this->auth_req or $this->auth_raw ) ? "" : "\r\n";
			
			$userAgent = ( $this->userAgent ) ? "\r\nUser-Agent: " . $this->userAgent : '';
			
			//-----------------------------------------
			// Are we posting?
			//-----------------------------------------
			
			if( is_array($post_array) AND count($post_array) )
			{
				$post_back	= array();
				
				foreach ( $post_array as $key => $val )
				{
					$post_back[] = $this->key_prefix . $key . '=' . urlencode($val);
				}
				
				$post_back_str	= implode( '&', $post_back);
				
				$header	= "POST {$path} HTTP/1.0\r\nHost:{$host}\r\nContent-Type: application/x-www-form-urlencoded\r\nConnection: Keep-Alive{$userAgent}\r\nContent-Length: " . strlen($post_back_str) . "\r\n{$final_carriage}{$post_back_str}";
			}
			else
			{
				$header	= "GET {$path} HTTP/1.0\r\nHost:{$host}\r\nConnection: Keep-Alive{$userAgent}\r\n{$final_carriage}";
			}

			if ( ! fputs( $fp, $header ) )
			{
				$this->errors[] = "Unable to send request to {$host}!";
				return FALSE;
			}
			
			if ( $this->auth_req )
			{
				if ( $this->auth_user && $this->auth_pass )
				{
					$header = "Authorization: Basic ".base64_encode("{$this->auth_user}:{$this->auth_pass}")."\r\n\r\n";
					
					if ( ! fputs( $fp, $header ) )
					{
						$this->errors[] = "Authorization Failed!";
						return FALSE;
					}
				}
			}
			elseif ( $this->auth_raw )
			{
				$header = $this->auth_raw."\r\n\r\n";
					
				if ( ! fputs( $fp, $header ) )
				{
					$this->errors[] = "Authorization Failed!";
					return FALSE;
				}
			}
		}

		@stream_set_timeout( $fp, $this->timeout );
		
		$status = @stream_get_meta_data($fp);
		
		while( ! feof($fp) && ! $status['timed_out'] )		
		{
			$data	.= fgets( $fp, 8192 );
			$status	= stream_get_meta_data($fp);
		}
		
		fclose ($fp);
		
		//-------------------------------
		// Strip headers
		//-------------------------------
		
		// HTTP/1.1 ### ABCD
		$this->http_status_code = substr( $data, 9, 3 );
		$this->http_status_text = substr( $data, 13, ( strpos( $data, "\r\n" ) - 13 ) );

		//-----------------------------------------
		// Try to deal with chunked..
		//-----------------------------------------
		
		$_chunked	= false;
		
		if( preg_match( '/Transfer\-Encoding:\s*chunked/i', $data ) )
		{
			$_chunked	= true;
		}

		$tmp	= preg_split("/\r\n\r\n/", $data, 2);
		$data	= trim($tmp[1]);
		
		$this->raw_headers	= trim($tmp[0]);
		
		//-----------------------------------------
		// Easy way out :P
		//-----------------------------------------
		
		if( $_chunked )
		{
			$lines	= explode( "\n", $data );
			array_pop($lines);
			array_shift($lines);
			$data	= implode( "\n", $lines );
		}

 		return $data;
	}
	
	/**
	 * Get file contents (with cURL)
	 *
	 * @param	string		File location
	 * @param	array 		Data to post (automatically converts to POST request)
	 * @return	@e string
	 */
	protected function _getContentsWithCurl( $file_location, $post_array=array() )
	{
		if ( function_exists( 'curl_init' ) AND function_exists("curl_exec") )
		{
			//-----------------------------------------
			// Are we posting?
			//-----------------------------------------

			$ch = curl_init( $file_location );
			
			curl_setopt( $ch, CURLOPT_HEADER			, 1 );
			curl_setopt( $ch, CURLOPT_TIMEOUT			, $this->timeout );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER	, 1 ); 
			curl_setopt( $ch, CURLOPT_FAILONERROR		, 1 ); 
			curl_setopt( $ch, CURLOPT_MAXREDIRS			, 10 );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER	, false );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST	, 1 );
			
			if ( $this->userAgent )
			{
				curl_setopt( $ch, CURLOPT_USERAGENT, $this->userAgent );
			}
			
			/**
			 * Cannot set this when safe_mode or open_basedir is enabled
			 * @link http://forums.invisionpower.com/index.php?autocom=tracker&showissue=11334
			 */
			if ( ! ini_get('open_basedir') AND ! ini_get('safe_mode') )
			{
				curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 ); 
			}
			
			if( $this->auth_req )
			{
				curl_setopt( $ch, CURLOPT_USERPWD	, "{$this->auth_user}:{$this->auth_pass}" );
			}
			elseif ( $this->auth_raw )
			{
				curl_setopt( $ch, CURLOPT_HTTPHEADER, array( $this->auth_raw ) );
			}
			
			//-----------------------------------------
			// Are we posting?
			//-----------------------------------------
			
			if( is_array($post_array) AND count($post_array) )
			{
				$post_back	= array();
				
				foreach ( $post_array as $key => $val )
				{
					$post_back[] = $this->key_prefix . $key . '=' . urlencode($val);
				}
				
				$post_back_str	= implode( '&', $post_back);
				
				curl_setopt( $ch, CURLOPT_POST			, true );
				curl_setopt( $ch, CURLOPT_POSTFIELDS	, $post_back_str );
			}
			else
			{
				curl_setopt( $ch, CURLOPT_POST			, false );
			}
			
			$data = curl_exec($ch);
			
			//-----------------------------------------
			// Handle some errors we can handle
			//-----------------------------------------
			
			if ( curl_errno($ch) == 60 ||  curl_errno($ch) == 77 )
			{
				// CURL_SSL_CACERT
				if ( defined('CA_BUNDLE_PATH') )
				{
					curl_setopt( $ch, CURLOPT_CAINFO, CA_BUNDLE_PATH );
				}
				else
				{
					curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
				}
				
				$data = curl_exec($ch);
			}
			else if ( curl_errno($ch) == 6 )
			{
				// Name lookup failed / DNS issues
				$url_parts	= @parse_url( $file_location );
				$ip			= @gethostbyname( $url_parts['host'] );
			
				if ( $ip and preg_match( '#^\d{1,3}\.#', $ip ) )
				{
					// Turn of verify SSL as the IP will not match domain of cert
					curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
					curl_setopt( $ch, CURLOPT_URL, $url_parts['scheme'] . '://' . $ip . '/' . $url_parts['path'] );
					$data = curl_exec($ch);
				}
			}
		
			curl_close($ch);

			if( $data )
			{
				$tmp  = preg_split("/\r\n\r\n/", $data, 2);
				$data = trim($tmp[1]);

				$this->raw_headers	= trim($tmp[0]);
			}
			
			$this->http_status_code = substr( $this->raw_headers, 9, 3 );
			$this->http_status_text = substr( $this->raw_headers, 13, ( strpos( $this->raw_headers, "\r\n" ) - 13 ) );

			if ( $data AND !$this->http_status_code )
			{
				$this->http_status_code = 200;
			}
			
			return $data;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Tail a file
	 *
	 * @param	string		Full path to file
	 * @param	int			No. lines to tail
	 * @return	@e string
	 */
	public function tailFile( $file, $lines=100 )
	{
		/* INIT */
		$content	= '';
		$t			= array();
		
		if ( is_file( $file ) )
		{
			$handle = @fopen( $file, 'r' );
			
			if ( $handle )
			{
			    $l   = $lines;
			    $pos = -2;
			    $beg = false;
			   
				while ( $l > 0 )
			    {
					$_t = " ";
					
					while( $_t != "\n" )
					{
			            if ( @fseek( $handle, $pos, SEEK_END ) == -1 )
			            {
			                $beg = true; 
			                break; 
			            }
			            
			            $_t = @fgetc( $handle );
			            $pos--;
			        }
			        
			        $l--;
			        
			        if ( $beg )
			        {
			            rewind( $handle );
			        }
			        
			        $t[ $lines - $l - 1 ] = @fgets( $handle );
			        
			        if ( $beg )
			        {
			        	break;
			        }
			    }
			    
			    @fclose ($handle);
			    
			    $content = trim( implode( "", array_reverse( $t ) ) );
			}
		}
		
	    return $content;
	}
	
	/**
	 * Copies contents of one directory to another, creating if necessary.  Returns true on success or false on failure.
	 *
	 * @param	string		File location [from]
	 * @param	string		File location [destination]
	 * @param	string		[Optional] CHMOD mode to set (WARNING: CHMOD modes should be specified in octal notation, e.g., 0777 and not 777)
	 * @return	@e boolean
	 */
	public function copyDirectory($from_path, $to_path, $mode = 0 )
	{
		$this->errors	= array();
		$mode			= $mode ? $mode : IPS_FOLDER_PERMISSION;
		
		//-----------------------------------------
		// Strip off trailing slashes...
		//-----------------------------------------
		
		$from_path	= rtrim( $from_path, '/' );
		$to_path	= rtrim( $to_path, '/' );
	
		if ( ! is_dir( $from_path ) )
		{
			$this->errors[] = "Could not locate directory '{$from_path}'";
			return false;
		}
	
		if ( ! is_dir( $to_path ) )
		{
			if ( ! @mkdir( $to_path, $mode ) )
			{
				$this->errors[] = "Could not create directory '{$to_path}' please check the CHMOD permissions and re-try";
				return FALSE;
			}
			else
			{
				@chmod( $to_path, $mode );
			}
		}
		
		if ( is_dir( $from_path ) )
		{
			$handle = opendir($from_path);
			
			while ( ($file = readdir($handle)) !== false )
			{
				if ( ($file != ".") && ($file != "..") )
				{
					if ( is_dir( $from_path."/".$file ) )
					{
						$this->copyDirectory( $from_path."/".$file, $to_path."/".$file );
					}
					
					if ( is_file( $from_path."/".$file ) )
					{
						copy( $from_path."/".$file, $to_path."/".$file );
						@chmod( $to_path."/".$file, IPS_FILE_PERMISSION );
					} 
				}
			}

			closedir($handle); 
		}
		
		if ( ! count( $this->errors ) )
		{
			return true;
		}
	}
	
	/**
	 * Removes a directory.  Returns true on success or false on failure.
	 *
	 * @param	string		File location [from]
	 * @return	@e boolean
	 */
	public function removeDirectory($file)
	{
		$errors = 0;
		
		//-----------------------------------------
		// Remove trailing slashes..
		//-----------------------------------------
		
		$file = rtrim( $file, '/' );
		
		if ( file_exists($file) )
		{
			//-----------------------------------------
			// Attempt CHMOD
			//-----------------------------------------
			
			@chmod( $file, IPS_FOLDER_PERMISSION );
			
			if ( is_dir( $file ) )
			{
				$handle = opendir( $file );
				
				while ( ($filename = readdir($handle)) !== false )
				{
					if ( ($filename != ".") && ($filename != "..") )
					{
						$this->removeDirectory( $file . "/" . $filename );
					}
				}
				
				closedir($handle);
				
				if ( ! @rmdir($file) )
				{
					$errors++;
				}
			}
			else
			{
				if ( ! @unlink($file) )
				{
					$errors++;
				}
			}
		}
		
		if( $errors == 0 )
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Empties a directory.  Returns true on success or false on failure.
	 *
	 * @param	string		File location [from]
	 * @param	int			In a loop (not root folder)
	 * @return	@e boolean
	 */
	public function emptyDirectory($file, $inLoop=0)
	{
		$errors = 0;
		
		//-----------------------------------------
		// Remove trailing slashes..
		//-----------------------------------------
		
		$file = rtrim( $file, '/' );
		
		if ( file_exists($file) )
		{
			//-----------------------------------------
			// Attempt CHMOD
			//-----------------------------------------
			
			@chmod( $file, IPS_FOLDER_PERMISSION );
			
			if ( is_dir( $file ) )
			{
				$handle = opendir( $file );
				
				while ( ($filename = readdir($handle)) !== false )
				{
					if ( ($filename != ".") && ($filename != "..") )
					{
						$this->emptyDirectory( $file."/".$filename, 1 );
					}
				}
				
				closedir($handle);
				
				if ( $inLoop )
				{
					if ( ! @rmdir($file) )
					{
						$errors++;
					}
				}
			}
			else
			{
				if ( ! @unlink($file) )
				{
					$errors++;
				}
			}
		}
		
		if( $errors == 0 )
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Receive data from the "send" function, returning an array of field data
	 *
	 * @param	array	Array of fields to return
	 * @return	@e array
	 */
	public function communicationReceiveData( $return_fields=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$return_array = array();
		
		//-----------------------------------------
		// Get data...
		//-----------------------------------------
		
		foreach( $_REQUEST as $k => $v )
		{
			if ( strstr( $k, $this->key_prefix ) )
			{
				$k = str_replace( $this->key_prefix, '', $k );
				
				$return_array[ $k ] = $v;
			}
		}
		
		return $this->_filterFields( $return_array );
	}

	/**
	 * Filter out fields (optional)
	 *
	 * @param	array	Array of field data
	 * @param	array 	Array of fields to look for
	 * @return	@e array
	 */
	protected function _filterFields( $in_fields=array(), $out_fields=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$return_array = array();
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! is_array( $in_fields ) or ! count( $in_fields ) )
		{
			return false;
		}
		
		if ( ! is_array( $out_fields ) or ! count( $out_fields ) )
		{
			return $in_fields;
		}
		
		//-----------------------------------------
		// Get data...
		//-----------------------------------------
		
		foreach( $out_fields as $k => $type )
		{
			if ( $in_fields[ $k ] )
			{
				switch ( $type )
				{
					default:
					case 'string':
					case 'text':
						$return_array[ $k ] = trim( $in_fields[ $k ] );
						break;
					case 'int':
					case 'integar':
						$return_array[ $k ] = intval( $in_fields[ $k ] );
						break;
					case 'float':
					case 'floatval':
						$return_array[ $k ] = floatval( $in_fields[ $k ] );
						break;
				}
			}
		}
		
		return $return_array;
	}
}