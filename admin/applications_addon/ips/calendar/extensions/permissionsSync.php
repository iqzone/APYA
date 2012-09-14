<?php
/**
 * @file		permissionsSync.php 	Update calendar caches when permissions are updated from central manager
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		3/18/2011
 * $LastChangedDate: 2011-03-31 06:17:44 -0400 (Thu, 31 Mar 2011) $
 * @version		vVERSION_NUMBER
 * $Revision: 8229 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		calendarPermissionsSync
 * @brief		Recaches calendars when permissions are updated
 */
class calendarPermissionsSync
{
	/**
	 * Cache Shortcut
	 *
	 * @var		$cache
	 */
	protected $cache;

	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Make shortcuts
		//-----------------------------------------

		$this->cache		= $registry->cache();
	}
	
	/**
	 * Callback when permissions have been updated
	 *
	 * @return	@e string
	 */
	public function updatePermissions()
	{
		$this->cache->rebuildCache( 'calendars', 'calendar' );
	}
}