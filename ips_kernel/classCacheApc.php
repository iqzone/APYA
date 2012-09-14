<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * This class acts as a cache layer, allowing you to store and retrieve data in
 *	external cache sources such as memcache or APC
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Kernel
 * @link		http://www.invisionpower.com
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 10721 $
 *
 * Basic Usage Examples
 * <code>
 * $cache = new cache_lib( 'identifier' );
 * Update:
 * $cache->putInCache( 'key', 'value' [, 'ttl'] );
 * Remove
 * $cache->removeFromCache( 'key' );
 * Retrieve
 * $cache->getFromCache( 'key' );
 * </code>
 *
 */

class classCacheApc implements interfaceCache
{
	/**
	 * Identifier
	 *
	 * @var		string
	 */
	protected $identifier	= '';
	
	/**
	 * Constructor
	 *
	 * @param	string 		Unique identifier
	 * @return	@e boolean
	 */
	public function __construct( $identifier='' )
	{
		if( !function_exists('apc_fetch') )
		{
			$this->crashed = true;
			return false;
		}
		
		$this->identifier	= $identifier;
	}
	
    /**
	 * Put data into remote cache store
	 *
	 * @param	string		Cache unique key
	 * @param	string		Cache value to add
	 * @param	integer		[Optional] Time to live
	 * @return	@e boolean
	 */
	public function putInCache( $key, $value, $ttl=0 )
	{
		$ttl = $ttl > 0 ? intval($ttl) : 0;
		
		return apc_store( md5( $this->identifier . $key ), $value, $ttl );
	}
	
    /**
	 * Retrieve a value from remote cache store
	 *
	 * @param	string		Cache unique key
	 * @return	@e mixed
	 */
	public function getFromCache( $key )
	{
		return apc_fetch( md5( $this->identifier . $key ) );
	}
	
    /**
	 * Update value in remote cache store
	 *
	 * @param	string		Cache unique key
	 * @param	string		Cache value to set
	 * @param	integer		[Optional] Time to live
	 * @return	@e boolean
	 */
	public function updateInCache( $key, $value, $ttl=0 )
	{
		$this->removeFromCache( $key );
		return $this->putInCache( $key, $value, $ttl );
	}
	
    /**
	 * Remove a value in the remote cache store
	 *
	 * @param	string		Cache unique key
	 * @return	@e boolean
	 */
	public function removeFromCache( $key )
	{
		return apc_delete( md5( $this->identifier . $key ) );
	}
	
    /**
	 * Not used by this library
	 *
	 * @return	@e boolean
	 */
	public function disconnect()
	{
		return true;
	}
}