<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Simple caching class
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * Original Author: Matt Mecham
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2011 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}


class classes_cache_simple
{
	/**#@+
	 * Registry objects
	 *
	 * @access	protected
	 * @var		object
	 */
	public $registry;
	public $DB;
	public $settings;
	public $request;
	public $lang;
	public $member;
	public $memberData	= array( 'member_id' => 0 );
	/**#@-*/
	
	protected $app          = '';
	protected $me           = array();
	protected $meCache      = array();
	protected $cacheForMins = array();
	
	
	/**
	 * Constructer
	 *
	 * @access	public
	 * @param	object 		ipsRegistry $registry
	 * @return	@e void
	 */
	public function __construct()
	{
		$this->registry   =  ipsRegistry::instance();
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();	
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		
		/* Set the default */
		$this->setApp( IPS_APP_COMPONENT );
		
		/* Set ME */
		$this->setMe( $this->memberData );
		
		/* Set default cache for mins */
		$this->setCacheForMins( 5 );
	}	

	/**
	 * @return the $app
	 */
	public function getApp()
	{
		return $this->app;
	}

	/**
	 * @param field_type $app
	 */
	public function setApp( $app )
	{
		$this->app = $app;
	}

	/**
	 * @return the $me
	 */
	public function getMe()
	{
		return $this->me;
	}

	/**
	 * @param array $me
	 */
	public function setMe( array $me )
	{
		$this->me = $me;
	}

	/**
	 * @return the $meCache
	 */
	public function getMeCache( $id )
	{
		return ( ! empty( $this->meCache[ $id ] ) ) ? $this->meCache[ $id ] : null;
	}

	/**
	 * @param string $meCache
	 */
	public function setMeCache( $meCache )
	{
		$me = $this->getMe();
		$this->meCache[ $me['member_id'] ] = $meCache;
	}
	
	/**
	 * @return the $cacheForMins
	 */
	public function getCacheForMins()
	{
		return $this->cacheForMins;
	}

	/**
	 * @param field_type $cacheForMins
	 */
	public function setCacheForMins( $cacheForMins )
	{
		$this->cacheForMins = intval( $cacheForMins );
	}
	
	/**
	 * Return a cached item
	 * @param string $key
	 */
	public function get( $key )
	{
		$data = $this->DB->buildAndFetch( array( 'select' => '*',
												 'from'   => 'cache_simple',
												 'where'  => 'cache_id=\'' . $this->DB->addSlashes( $this->_makeId( $key ) ) . '\' AND cache_perm_key=\'' . $this->DB->addSlashes( $this->_makePermKey() ) . '\'' ) );
		
		if ( ! empty( $data['cache_id'] ) )
		{
			if ( ! $this->_isStale( $data ) )
			{
				/* Do we need to unserialize this? */
				$data['cache_data'] = ( IPSLib::isSerialized( $data['cache_data'] ) ) ? unserialize( $data['cache_data'] ) : $data['cache_data'];
				
				return array( 'data' => $data['cache_data'],
							  'time' => $data['cache_time'] );
			}
		}
		
		return null;
	}
	
	/**
	 * Set a cached item
	 * @param string $key
	 * @param mixed  $data If array, it will be serialized
	 */
	public function set( $key, $data )
	{
		$this->DB->replace( 'cache_simple', array( 'cache_id' 		=> $this->_makeId( $key ),
												   'cache_perm_key' => $this->_makePermKey(),
												   'cache_time'		=> IPS_UNIX_TIME_NOW,
												   'cache_data'		=> is_array( $data ) ? serialize( $data ) : $data ), array( 'cache_id' ) );
		
		return true;
	}
	
	/**
	 * Tests to see if a cached item is stale
	 * @param array $data
	 * @return boolean
	 */
	private function _isStale( array $data )
	{
		if ( empty( $data['cache_time'] ) )
		{
			return true;
		}	
		else if ( ( IPS_UNIX_TIME_NOW - $data['cache_time'] ) > ( $this->getCacheForMins() * 60 ) )
		{
			return true;
		}
		
		return false;
	}
	
	/**
	 * Make an ID key for the DeeBee
	 * @param string $key
	 */
	private function _makeId( $key )
	{
		return md5( $this->getApp() . '-' . $key );
	}
	
	/**
	 * Make a perm key for the DeeBee
	 * @param string $key
	 */
	private function _makePermKey()
	{
		$me     = $this->getMe();
		$cached = $this->getMeCache( $me['member_id'] );
		$key    = ( $cached !== null ) ? $cached : $this->_makePermOrdered( $me );
		
		if ( $cached === null && $key )
		{
			$this->setMeCache( $key );
		}
		
		return md5( $key );
	}
	
	/**
	 * Fetch all relevant Perm IDs and make them ordered
	 * @param array $member
	 */
	private function _makePermOrdered( $member )
	{
		$perms = array();
		
		if ( ! empty( $member['org_perm_id'] ) )
		{
			$perms = explode( ',', IPSText::cleanPermString( $member['org_perm_id'] ) );
		}
		
		if ( ! count( $perms ) )
		{
			$groups = array( $member['member_group_id'] );
			
			if ( ! empty( $member['mgroup_others'] ) )
			{
				$others = explode( ',', IPSText::cleanPermString( $member['mgroup_others'] ) );
				
				if ( is_array( $others ) )
				{
					$groups = array_merge( $groups, $others );
				}
			}
			
			foreach( $groups as $gid )
			{
				$_perms = IPSText::cleanPermString( $this->caches['group_cache'][ $gid ]['g_perm_id'] );
				
				if ( ! empty( $_perms ) )
				{
					$__perms = explode( ',', $_perms );
					
					if ( is_array( $__perms ) )
					{
						$perms = array_merge( $perms, $__perms );
					}
				}
			}
			
		}
		
		if ( is_array( $perms ) AND count( $perms ) )
		{
			sort( $perms, SORT_NUMERIC );
			$perms = array_unique( $perms );
		
			return implode( ',', $perms );
		}
		else
		{
			return '';
		}
		
	}
	
}