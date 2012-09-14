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

class classCacheMemcache implements interfaceCache
{
	/**
	 * Identifier
	 *
	 * @var		string
	 */
	protected $identifier	= '';
	
	/**
	 * Connection resource
	 *
	 * @var		resource
	 */
	protected $link		= null;
	
    /**
	 * Constructor
	 *
	 * @param	string 		Unique identifier
	 * @param	array 		Connection information
	 * @return	@e boolean
	 */
	public function __construct( $identifier='', $server_info=array() )
	{
		if( !function_exists('memcache_connect') )
		{
			$this->crashed = true;
			return false;
		}

		$this->identifier	= $identifier;
		
		return $this->_connect( $server_info );
	}
	
    /**
	 * Connect to memcache server
	 *
	 * @param	array 		Connection information
	 * @return	@e boolean
	 */
	protected function _connect( $server_info=array() )
	{
		if( !count($server_info) )
		{
			$this->crashed = true;
			return false;
		}
		
		if( !isset($server_info['memcache_server_1']) OR !isset($server_info['memcache_port_1']) )
		{
			$this->crashed = true;
			return false;
		}
		
		$this->link = memcache_connect( $server_info['memcache_server_1'], $server_info['memcache_port_1'] );
		
		if( !$this->link )
		{
			$this->crashed = true;
			return false;
		}
		
		if( isset($server_info['memcache_server_2']) AND isset($server_info['memcache_port_2']) )
		{
			memcache_add_server( $this->link, $server_info['memcache_server_2'], $server_info['memcache_port_2'] );
		}
		
		if( isset($server_info['memcache_server_3']) AND isset($server_info['memcache_port_3']) )
		{
			memcache_add_server( $this->link, $server_info['memcache_server_3'], $server_info['memcache_port_3'] );
		}
		
		if( isset($server_info['memcache_server_4']) AND isset($server_info['memcache_port_4']) )
		{
			memcache_add_server( $this->link, $server_info['memcache_server_4'], $server_info['memcache_port_4'] );
		}
		
		if( isset($server_info['memcache_server_5']) AND isset($server_info['memcache_port_5']) )
		{
			memcache_add_server( $this->link, $server_info['memcache_server_5'], $server_info['memcache_port_5'] );
		}
		
		if( function_exists('memcache_set_compress_threshold') )
		{
			memcache_set_compress_threshold( $this->link, 20000, 0.2 );
		}
		
		return true;
	}
	
    /**
	 * Disconnect from remote cache store
	 *
	 * @return	@e boolean
	 */
	public function disconnect()
	{
		if( $this->link )
		{
			return memcache_close( $this->link );
		}
		
		return false;
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
		return memcache_set( $this->link, md5( $this->identifier . $key ), $value, MEMCACHE_COMPRESSED, intval($ttl) );
	}
	
    /**
	 * Retrieve a value from remote cache store
	 *
	 * @param	string		Cache unique key
	 * @return	@e mixed
	 */
	public function getFromCache( $key )
	{
		return memcache_get( $this->link, md5( $this->identifier . $key ) );
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
		return memcache_delete( $this->link, md5( $this->identifier . $key ), 0 );
	}
}