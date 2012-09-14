<?php
/**
 * @file		calendarUpcomingBirthdays.php 	Hook to display upcoming birthdays on the board index
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		11th Feb 2011
 * $LastChangedDate: 2011-02-16 11:59:53 -0500 (Wed, 16 Feb 2011) $
 * @version		vVERSION_NUMBER
 * $Revision: 7806 $
 */

/**
 *
 * @class		calendarUpcomingBirthdays
 * @brief		Hook to display upcoming birthdays on the board index
 *
 */
class calendarUpcomingBirthdays
{
	/**
	 * Constructor
	 *
	 * @return	@e void
	 */
	public function __construct() {}
	
	/**
	 * Execute data hook
	 *
	 * @return	@e void
	 */
	public function getOutput()
	{
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'calendar' ) . '/sources/hooks.php', 'app_calendar_classes_hooks', 'calendar' );
		$gateway	 = new $classToLoad( ipsRegistry::instance() );
		
		return $gateway->getUpcomingBirthdays();
	}
}