<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board vVERSION_NUMBER
 * Core variables for calendar
 * Last Updated: $Date: 2011-04-12 17:15:39 -0400 (Tue, 12 Apr 2011) $
 * </pre>
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Calendar
 * @link		http://www.invisionpower.com
 * @version		$Rev: 8310 $ 
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

$_LOAD	= array(
				'calendars'			=> 1,
				'reputation_levels'	=> 1,
				'ranks'				=> 1,
				'emoticons'			=> 1,
				'bbcode'			=> 1,
				'badwords'			=> 1,
				'moderators'		=> 1,
				'attachtypes'		=> 1,
				'sharelinks'		=> 1,
				);

$CACHE['calendars']			= array( 
									'array'				=> 1,
									'allow_unload'		=> 0,
									'default_load'		=> 1,
									'recache_file'		=> IPSLib::getAppDir( 'calendar' ) . '/modules_admin/calendar/calendars.php',
									'recache_class'		=> 'admin_calendar_calendar_calendars',
									'recache_function'	=> 'calendarsRebuildCache' 
									);

$CACHE['birthdays']			= array( 
									'array'				=> 1,
									'allow_unload'		=> 0,
									'default_load'		=> 1,
									'recache_file'		=> IPSLib::getAppDir( 'calendar' ) . '/sources/cache.php',
									'recache_class'		=> 'calendar_cache',
									'recache_function'	=> 'rebuildCalendarEventsCache' 
								);

$CACHE['calendar_events']	= array( 
									'array'				=> 1,
									'allow_unload'		=> 0,
									'default_load'		=> 1,
									'recache_file'		=> IPSLib::getAppDir( 'calendar' ) . '/sources/cache.php',
									'recache_class'		=> 'calendar_cache',
									'recache_function'	=> 'rebuildCalendarEventsCache' 
								);

$CACHE['rss_calendar']		= array(
									'array'				=> 1,
									'allow_unload'		=> 0,
									'default_load'		=> 1,
									'recache_file'		=> IPSLib::getAppDir( 'calendar' ) . '/sources/cache.php',
									'recache_class'		=> 'calendar_cache',
									'recache_function'	=> 'rebuildCalendarRSSCache',
									'skip_rebuild_when_upgrading' => 1,
								);
