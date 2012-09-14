<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * URL shortener
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

	 
/* Class name must match shortener directory name */
class topic extends urlShorten
{
	protected $_config = array();
	
	protected $_cfm;
	
	/**
	 * Method constructor
	 *
	 * If you pass false as the key, it will not save out the imported GUIDs
	 *
	 * @return	@e void
	 * 
	 */
	public function __construct( $config=array() )
	{
		$this->_config = $config;
		
		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classFileManagement.php', 'classFileManagement' );
		$this->_cfm          = new $classToLoad();
		$this->_cfm->timeout = 30;
	}
	
	/**
	 * Shorten URL
	 *
	 * @param	string	URL to shorten
	 * @return	array ( 'status' => [ok/failed], 'url' => [shortened url], 'method' => [api used], 'raw' => [any raw data] )
	 */
	public function apiShorten( $url )
	{
		/* Query the service */
		$response = $this->_cfm->getFileContents( "http://topic.to/api.php?action=shorturl&url=" . urlencode( $url ) . "&format=json" );
		
		if ( ! $response )
		{
			return array( 'status' => 'failed' );
		}
		
		$obj = json_decode( $response, TRUE );
		
		if ( $obj['statusCode'] != 200 )
		{
			return array( 'status' => 'failed' );
		}
		
		return array( 'status' => 'ok',
					  'url'    => $obj['shorturl'],
					  'method' => 'topic',
					  'raw'    => $obj['url'] );
	}
}