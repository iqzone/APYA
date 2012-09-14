<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board vVERSION_NUMBER
 * Calendar iCalendar/webcal output
 * Last Updated: $Date: 2012-02-27 16:50:51 -0500 (Mon, 27 Feb 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Calendar
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10365 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_calendar_feed_output extends ipsCommand
{
	/**
	 * Calendar data
	 *
	 * @var		array
	 */
	protected $calendar			= array();
	
	/**
	 * Calendar functions
	 *
	 * @var		object
	 */
	protected $functions;

	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$this->registry->class_localization->loadLanguageFile( array( 'public_calendar' ), 'calendar' );

		$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir( 'calendar' ) . "/sources/functions.php", 'app_calendar_classes_functions', 'calendar' );
		$this->functions	= new $classToLoad( $this->registry );
		$this->calendar		= $this->functions->getCalendar();

		//-----------------------------------------
		// Switch
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			default:
			case 'download':
				$this->downloadIcalFeed();
			break;
		}
	}
	
	/**
	 * Serve the iCalendar feed (valid for both download as .ics and webcal:// protocol)
	 *
	 * @return	@e void
	 */
	public function downloadIcalFeed()
	{
		//-----------------------------------------
		// Get main view class
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadActionOverloader( IPSLib::getAppDir( 'calendar' ) . '/modules_public/calendar/view.php', 'public_calendar_calendar_view' );
		$calendar		= new $classToLoad( $this->registry );
		$calendar->makeRegistryShortcuts( $this->registry );
		$calendar->initCalendar();
		
		//-----------------------------------------
		// Load (all) events
		//-----------------------------------------
		
		$calendar->calendarGetEventsSQL( gmstrftime( '%m' ), gmstrftime( '%Y' ), array( 'timenow' => '1', 'timethen' => '2000000000', 'cal_id' => $this->calendar['cal_id'], 'honor_permissions' => true ) );

		$events	= $calendar->calendarGetAllEvents();

		//-----------------------------------------
		// Load iCalendar class
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'calendar' ) . "/sources/icalendar.php", 'app_calendar_classes_icalendarOutput', 'calendar' );
		$iCalendar		= new $classToLoad( $this->registry, $this->calendar['cal_id'] );
		
		//-----------------------------------------
		// Send data to iCalendar class and get output
		//-----------------------------------------
		
		foreach( $events as $event )
		{
			$event	= $calendar->calendarMakeEventHTML( $event, true );

			$iCalendar->addEvent( $event );
		}
		
		$feed	= $iCalendar->buildICalendarFeed();

		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		@header( "Content-type: text/calendar; charset=" . IPS_DOC_CHAR_SET );
		@header( "Content-Disposition: inline; filename=calendarEvents.ics" );
		
		print $feed;
		exit;
	}
}