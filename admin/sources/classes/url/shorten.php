<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * URL Shortener
 * Owner: Matt Mecham
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		Matt Mecham
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		24th November 2009
 * @version		$Revision: 10721 $
 */

/*
# Example: Shorten URL
# require_once( IPS_ROOT_PATH . 'sources/classes/url/shorten.php' );/*noLibHook*/
# $shorten = new urlShorten();
# $data    = $shorten->shorten( 'http://www.invisionpower.com', 'bitly' );
# 
# print $data['url'];
# 
# Example: List available shorteners
# require_once( IPS_ROOT_PATH . 'sources/classes/url/shorten.php' );/*noLibHook*/
# $shorten = new urlShorten();
# 
# print_r( $shorten->fetchAvailableApis() );
#

class urlShorten
{	
	/**
	 * Main API classes directory
	 *
	 * @var		$_mainDir
	 */
	protected $_mainDir;
	
	/**
	 * API object
	 *
	 * @var		$_api
	 */
	protected $_api;
	
	/**
	 * Array of additional error messages
	 *
	 * @var		$errors
	 */
	public $errors = array();
	
	/**
	 * Method constructor
	 *
	 * @return	@e void
	 *
	 * @throws Exception
	 * 	@li NO_METHOD (API class could not be found)
	 */
	public function __construct()
	{
		$this->_mainDir = IPS_ROOT_PATH . 'sources/classes/url/apis';
	}
	
	/**
	 * Shorten URL
	 *
	 * @param	string		$url		URL to shorten
	 * @param	string		$apiKey		Method key to use (If left blank, we'll just grab the first one)
	 * @return	@e array	Shortened url data
	 *
	 * @throws Exception
	 * 	@li NO_METHOD (API class could not be found)
	 * 	@li BAD_FORMAT (URL is not in the correct format)
	 * 	@li FAILED (URL shorten failed)
	 */
	public function shorten( $url, $apiKey )
	{
		/* Check URL */
		if ( ! strstr( $url, 'http://' ) AND ! strstr( $url, 'https://' ) )
		{
			throw new Exception( 'BAD_FORMAT' );
		}
		
		if ( $apiKey )
		{
			$_thisDir = $this->_mainDir . '/' . $apiKey;
			
			if ( @is_file( $_thisDir . '/api.php' ) )
			{
				$this->_setMethod( $apiKey );
			}
		}
		else
		{
			/* We don't care */
			$apis = $this->fetchAvailableApis();
			
			if ( is_array( $apis ) AND count( $apis ) )
			{
				$apiKey = array_shift( $apis );
				
				if ( $apiKey )
				{
					$this->_setMethod( $apiKey );
				}
			}
		}
		
		if ( ! is_object( $this->_api ) )
		{
			throw new Exception( 'NO_METHOD' );
		}
		
		/* still here? */
		$data = $this->_api->apiShorten( $url );
		
		if ( $data['status'] == 'ok' )
		{
			return $data;
		}
		else
		{
			/* could do something more useful here */
			throw new Exception( 'FAILED' );
		}
	}
	
	/**
	 * Set method
	 * Assumes that the folder and files exist
	 *
	 * @param	string		$apiKey		API key
	 * @return	@e void
	 */
	protected function _setMethod( $apiKey )
	{
		$config = array();
		
		if ( is_file( $this->_mainDir . '/' . $apiKey . '/conf.php' ) )
		{
			require( $this->_mainDir . '/' . $apiKey . '/conf.php' );/*noLibHook*/
		}
		
		$classToLoad = IPSLib::loadLibrary( $this->_mainDir . '/' . $apiKey . '/api.php', $apiKey );
		$this->_api = new $classToLoad( $config );
	}
	
	/**
	 * Fetch all available APIs
	 *
	 * @return	@e array	Available APIs
	 */
	public function fetchAvailableApis()
	{
		$apis = array();
		
		try
		{
			foreach( new DirectoryIterator( $this->_mainDir ) as $file )
			{
				if ( ! $file->isDot() AND $file->isDir() )
				{
					$_name = $file->getFileName();
					
					if ( substr( $_name, 0, 1 ) != '.' )
					{
						$apis[] = $_name;
					}
				}
			}
		} catch ( Exception $e ) {}
		
		return $apis;
	}
}