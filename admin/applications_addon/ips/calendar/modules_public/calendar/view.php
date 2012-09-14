<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board vVERSION_NUMBER
 * Calendar View
 * Last Updated: $Date: 2012-03-29 15:02:31 -0400 (Thu, 29 Mar 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Calendar
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10518 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_calendar_calendar_view extends ipsCommand
{
	/**
	 * Calendar data
	 *
	 * @var		array
	 */
	protected $calendar			= array();

	/**
	 * Cache of events we've pulled from DB
	 *
	 * @var		array
	 */
	protected $events			= array();

	/**
	 * Chosen date bits
	 *
	 * @var		array
	 */
	protected $chosen_date		= array();

	/**
	 * Calendar functions
	 *
	 * @var		object
	 */
	protected $functions;
	
	/**
	 * Like object
	 *
	 * @var	object
	 */
	protected $_like;
	
	/**
	 * Flag to indicate if we can report events or not
	 *
	 * @var	bool
	 */
	protected $canReport	= 'notinit';

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
		
		$this->initCalendar();
		
		//-----------------------------------------
		// Start navigation now - other functions append
		//-----------------------------------------
		
		$this->registry->output->addNavigation( $this->lang->words['cal_page_title'], 'app=calendar', 'false', 'app=calendar' );
		
		if( $this->request['do'] != 'showevent' )
		{
			$this->registry->output->addNavigation( $this->calendar['cal_title'], "app=calendar&amp;module=calendar&amp;section=view&amp;cal_id={$this->calendar['cal_id']}", $this->calendar['cal_title_seo'], 'cal_calendar' );
		}

		//-----------------------------------------
		// What are we doing
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'showday':
				$this->calendarShowDay();
			break;

			case 'showevent':
				$this->calendarShowEvent();
			break;

			case 'showweek':
				$this->calendarShowWeek();
			break;

			default:
				$this->calendarShowMonth();
			break;
		}

		//-----------------------------------------
		// Output
		//-----------------------------------------

		$this->registry->output->setTitle( $this->page_title ? $this->page_title . ' - ' . $this->settings['board_name'] : $this->lang->words['cal_page_title'] . ' - ' . $this->settings['board_name'] );
		$this->registry->output->addContent( $this->output );
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Initialize calendar
	 *
	 * @param	bool	Return instead of throw error
	 * @return	@e void
	 */
	public function initCalendar( $return=false )
	{
		//-----------------------------------------
		// Language files
		//-----------------------------------------
		
		$this->registry->class_localization->loadLanguageFile( array( 'public_calendar' ), 'calendar' );

		//-----------------------------------------
		// Functions class
		//-----------------------------------------
		
		$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir( 'calendar' ) . "/sources/functions.php", 'app_calendar_classes_functions', 'calendar' );
		$this->functions	= new $classToLoad( $this->registry, false, $return );

		if( $this->functions === false )
		{
			if( $return )
			{
				return false;
			}
		}
		
		//-----------------------------------------
		// Get our calendar
		//-----------------------------------------
		
		$this->calendar		= $this->functions->getCalendar();

		if( !$this->calendar['cal_id'] )
		{
			if( $return )
			{
				return false;
			}

			$this->registry->output->showError( 'no_calendar_permission', 104211.1, null, null, 403 );
		}
		
		//-----------------------------------------
		// Figure out requested date
		//-----------------------------------------
		
		$this->chosen_date	= array(
									'month'			=> empty($this->request['m']) ? gmstrftime( '%m' ) : str_pad( intval($this->request['m']), 2, '0', STR_PAD_LEFT ),
									'year'			=> empty($this->request['y']) ? gmstrftime( '%Y' ) : intval($this->request['y']),
									);

		if( ! checkdate( $this->chosen_date['month'], 1 , $this->chosen_date['year'] ) )
		{
			$this->chosen_date	= array(
										'month'			=> gmstrftime( '%m' ),
										'year'			=> gmstrftime( '%Y' ),
										);
		}

		$this->chosen_date['month_name']	= gmstrftime( '%B', gmmktime( 0, 0, 0, $this->chosen_date['month'], 15, $this->chosen_date['year'] ) );
		
		$this->registry->output->addToDocumentHead( 'raw', "<link rel='profile' href='http://microformats.org/profile/hcalendar' />" );
		
		return true;
	}
	
	/**
	 * Return the words for the days of the week
	 *
	 * @return	array
	 */
	protected function _getDayWords()
	{
		if( !$this->settings['ipb_calendar_mon'] )
		{
			return array( $this->lang->words['D_0'], $this->lang->words['D_1'], $this->lang->words['D_2'],
							$this->lang->words['D_3'], $this->lang->words['D_4'], $this->lang->words['D_5'],
							$this->lang->words['D_6'] );
		}
		else
		{
			return array( $this->lang->words['D_1'], $this->lang->words['D_2'], $this->lang->words['D_3'],
							$this->lang->words['D_4'], $this->lang->words['D_5'], $this->lang->words['D_6'],
							$this->lang->words['D_0'] );
		}
	}
	
	/**
	 * Returns birthdays based on requested month
	 *
	 * @param	integer	$month	Month
	 * @return	@e void
	 */
	protected function _getBirthdaysForMonth( $month )
	{
		static $birthdayCache	= array();
		
		if( ! isset( $birthdayCache[ $month ] ) )
		{
			$prev_month	= $this->calendarGetPreviousMonth( $month, gmstrftime( '%Y' ) );
			$next_month	= $this->calendarGetNextMonth( $month, gmstrftime( '%Y' ) );

			$birthdayCache[ $month ] 					= array();
			$birthdayCache[ $next_month['month_id'] ]	= array();
			$birthdayCache[ $prev_month['month_id'] ]	= array();

			$this->DB->build( array( 'select' => 'bday_year, bday_day, bday_month, member_id, member_group_id, members_display_name, members_seo_name', 'from' => 'members', 'where' => 'member_banned=0 AND bday_month IN(' . $prev_month['month_id'] . ',' . $month . ',' . $next_month['month_id'] . ')' ) );
			$this->DB->execute();

			while ($r = $this->DB->fetch())
			{
				$birthdayCache[ str_pad( $r['bday_month'], 2, '0', STR_PAD_LEFT ) ][ str_pad( $r['bday_day'], 2, '0', STR_PAD_LEFT ) ][] = $r;
			}
		}

		return $birthdayCache[ $month ];
    }

	/**
	 * Make Birthday HTML (return HTML for bdays)
	 *
	 * @param	integer	$month	Month
	 * @param	integer	$day	Day
	 * @param	integer	$year	Year
	 * @return	string	HTML output
	 */
	public function calendarMakeBirthdayHTML( $month, $day, $year=NULL )
	{
		//-----------------------------------------
		// Grab leap year babbies on Feb 28 if needed
		//-----------------------------------------
		
		if( ! date( "L" ) AND $month == "2" AND $day == "28" )
		{
			$where_string = "bday_month=2 AND bday_day IN(28,29)";
		}
		else
		{
			$where_string = 'bday_month=' . $month . ' AND bday_day=' . $day;
		}

		//-----------------------------------------
		// Get birthdays
		//-----------------------------------------
		
		$birthdays	= array();

		$this->DB->build( array( 'select' => 'member_id', 'from' => 'members', 'where' => 'member_banned=0 AND ' . $where_string ) );
		$this->DB->execute();

		if( $this->DB->getTotalRows() )
		{
			$_memberIds	= array();
			
			while( $r = $this->DB->fetch() )
			{
				$_memberIds[]	= $r['member_id'];
			}
			
			$_members	= IPSMember::load( $_memberIds, 'all' );
			
			if( count($_members) )
			{
				foreach( $_members as $_member )
				{
					$_member['age']	= $this->_getAge( $_member['bday_month'], $_member['bday_day'], $_member['bday_year'], ( $year ? gmmktime( 0, 0, 0, $month, $day, $year ) : NULL ) );
					
					$birthdays[]	= IPSMember::buildDisplayData( $_member );
				}
			}
		}

		return $this->registry->output->getTemplate( 'calendar' )->calendarBirthdayList( $birthdays );
	}
	
	/**
	 * Builds the html for a mini calendar
	 *
	 * @param	int		[$month]	Numeric value of month to build
	 * @param	int		[$year]		Year to build
	 * @return	string	Mini-cal HTML
	 */
	public function getMiniCalendar( $month=0, $year=0 )
	{
		$month	= $month ? $month : $this->chosen_date['month'];
		$year	= $year ? $year : $this->chosen_date['year'];
		
		/* One cache per month, year and skin */
		$_cal	= IN_DEV ? '' : $this->cache->getCache( 'minical_' . $month . '_' . $year . '_' . $this->registry->output->skin['_skincacheid'] );

		/* If cache wasn't built today, rebuild.  We use a diff skin template for today vs other days. */
		if( !$_cal OR !is_array($_cal) OR !$_cal['built'] OR gmdate( 'Ymj', $_cal['built'] ) != gmdate('Ymj') )
		{
			$_cal	= $this->registry->output->getTemplate('calendar')->miniCalendarWrapper( array(
																						'month_title'	=> gmstrftime( '%B', gmmktime( 0, 0, 0, $month, 15 ) ), 
																						'month'			=> $month, 
																						'year'			=> $year, 
																						'events'		=> $this->getMonthEvents( $month, $year, true ), 
																						'day_words'		=> $this->_getDayWords(),
																						'calendar'		=> $this->calendar,
																				)		);

			$_cal	= array( 'html' => $_cal, 'built' => time() );
			
			if( !IN_DEV )
			{
				$this->cache->setCache( 'minical_' . $month . '_' . $year . '_' . $this->registry->output->skin['_skincacheid'], $_cal, array( 'array' => 1 ) );
			}
		}

		return $_cal['html'];
	}

	/**
	 * Figure out age
	 *
	 * @param	int		$month	Month
	 * @param	int		$day	Day
	 * @param	int		$year	Year
	 * @param	int		$date	The date this applies to
	 * @return	int		Number of years old
	 */
	protected function _getAge( $month, $day, $year, $date=NULL )
	{
		if( !$year OR !$month OR !$day )
		{
			return 0;
		}
		
		$_birthday	= gmmktime( 0, 0, 0, $month, $day, $year );
		$_current	= $date ? $date : gmmktime( 0 );
		
		return floor( ( $_current - $_birthday ) / 31536000 );
	}

	/**
	 * Figures out what the next month on the calendar is
	 *
	 * @param	integer	$month	Current Month
	 * @param	integer	$year	Current Year
	 * @return	array 	Month data
	 */
	protected function calendarGetNextMonth( $month, $year )
	{
		return array(
					'year_id'		=> gmstrftime( '%Y', gmmktime( 0, 0, 0, $month + 1, 15, $year ) ),
					'month_name'	=> gmstrftime( '%B', gmmktime( 0, 0, 0, $month + 1, 15, $year ) ),
					'month_id'		=> gmstrftime( '%m', gmmktime( 0, 0, 0, $month + 1, 15, $year ) ),
					);
	}

	/**
	 * Figures out what the previous month on the calendar is
	 *
	 * @param	integer	$month	Current Month
	 * @param	integer	$year	Current Year
	 * @return	array 	Month data
	 */
	protected function calendarGetPreviousMonth( $month, $year )
	{
		return array(
					'year_id'		=> gmstrftime( '%Y', gmmktime( 0, 0, 0, $month - 1, 15, $year ) ),
					'month_name'	=> gmstrftime( '%B', gmmktime( 0, 0, 0, $month - 1, 15, $year ) ),
					'month_id'		=> gmstrftime( '%m', gmmktime( 0, 0, 0, $month - 1, 15, $year ) ),
					);
	}
	
	/**
	 * Build the ID for the current day
	 *
	 * @param	int		$year	The year
	 * @param	int		$month	The month
	 * @param	int		$day	The day
	 * @return 	string
	 */
	protected function _buildDayID( $year, $month, $day )
	{
		return 'day-' . $year . '-' . $month . '-' . $day;
	}

	/**
	 * Displays the specified month
	 *
	 * @return	@e void
	 */
	public function calendarShowMonth()
	{
		//-----------------------------------------
		// Get data for the template
		//-----------------------------------------
		
		$_previous	= $this->calendarGetPreviousMonth( $this->chosen_date['month'], $this->chosen_date['year'] );
		$_next		= $this->calendarGetNextMonth( $this->chosen_date['month'], $this->chosen_date['year'] );
		
		//-----------------------------------------
		// Like strip
		//-----------------------------------------
		
		if( !$this->_like )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
			$this->_like	= classes_like::bootstrap( 'calendar', 'calendars' );
		}
		
		$_likeStrip	= $this->_like->render( 'summary', $this->calendar['cal_id'] );

		//-----------------------------------------
		// Set output
		//-----------------------------------------
		
		$this->output .= $this->registry->output->getTemplate('calendar')->calendarMainContent(
																								array(
																									'calendars'		=> $this->functions->getCalendars(),
																									'calendar'		=> $this->calendar,
																									'events'		=> $this->getMonthEvents( $this->chosen_date['month'], $this->chosen_date['year'] ),
																									'minical_next'	=> $this->getMiniCalendar( $_next['month_id'], $_next['year_id'] ),
																									'minical_prev'	=> $this->getMiniCalendar( $_previous['month_id'], $_previous['year_id'] ),
																									'calendar_jump'	=> $this->functions->getCalendarJump(),
																									'prev_month'	=> $_previous,
																									'next_month'	=> $_next,
																									'month_title'	=> $this->chosen_date['month_name'],
																									'day_words'		=> $this->_getDayWords(),
																									'chosen_date'	=> $this->chosen_date,
																									'month_box'		=> $this->functions->returnMonthDropdown( $this->chosen_date['month'] ),
																									'year_box'		=> $this->functions->returnYearDropdown( $this->chosen_date['year'] ),
																									'navigation'	=> array( 'this_week' => gmmktime( 0 ), 'this_month' => array( 'm' => gmstrftime( '%m' ), 'y' => gmstrftime( '%Y' ) ) ),
																									'_like_strip'	=> $_likeStrip,
																									'_can_add'		=> ( $this->memberData['member_id'] AND $this->registry->permissions->check( 'start', $this->calendar ) ),
																									)
																								);

		//-----------------------------------------
		// Set page title and navigation
		//-----------------------------------------

		$this->registry->output->addNavigation( $this->chosen_date['month_name'] . ' ' . $this->chosen_date['year'] );
		$this->registry->output->addMetaTag( 'keywords', $this->chosen_date['month_name'], TRUE );
		$this->registry->output->addMetaTag( 'description', str_replace( "\n", " ", str_replace( "\r", "", sprintf( $this->lang->words['month_meta_description'], $this->chosen_date['month_name'], $this->chosen_date['year'] ) ) ), FALSE, 155 );
		
		if( $this->request['m'] )
		{
			$this->registry->getClass('output')->addCanonicalTag( 'app=calendar&amp;module=calendar&amp;section=view&amp;cal_id=' . $this->calendar['cal_id'] . '&amp;m=' . $this->chosen_date['month'] . '&amp;y=' . $this->chosen_date['year'], $this->calendar['cal_title_seo'], 'cal_month' );
		}
		else if( $this->request['cal_id'] )
		{
			$this->registry->getClass('output')->addCanonicalTag( 'app=calendar&amp;module=calendar&amp;section=view&amp;cal_id=' . $this->calendar['cal_id'], $this->calendar['cal_title_seo'], 'cal_calendar' );
		}
		else
		{
			$this->registry->getClass('output')->addCanonicalTag( 'app=calendar', 'false', 'app=calendar' );
		}
		
		$this->page_title	= $this->chosen_date['month_name'] . ' ' . $this->chosen_date['year'];
	}
	
	/**
	 * Generates the sql to query events
	 *
	 * @param	integer	$month		Month to get events from
	 * @param	integer	$year		Year to get events from
	 * @param	array	[$presets]	Load based on specific timestamps
	 * @return	@e void
	 */
	public function calendarGetEventsSQL( $month=0, $year=0, $presets=array() )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------

		$minimal	= ( isset($presets['minimal']) AND $presets['minimal'] ) ? true : false;

		if( isset($presets['timenow']) )
		{
			$prev_month	= $this->calendarGetPreviousMonth( gmstrftime( "%m", $presets['timenow'] ), gmstrftime( "%Y", $presets['timenow'] ) );
		}
		else
		{
			$prev_month	= $this->calendarGetPreviousMonth( $month, $year );
		}

		if( isset($presets['timethen']) )
		{
			$next_month	= $this->calendarGetNextMonth(  gmstrftime( "%m", $presets['timethen'] ), gmstrftime( "%Y", $presets['timethen'] ) );
		}
		else
		{
			$next_month	= $this->calendarGetNextMonth( $month, $year );
		}
		
		$start_date	= gmstrftime( "%Y-%m-%d", isset($presets['timenow']) ? $presets['timenow'] : gmmktime( 0, 0, 1, $prev_month['month_id'], 1, $prev_month['year_id'] ) );
		$end_date	= gmstrftime( "%Y-%m-%d", isset($presets['timethen']) ? $presets['timethen'] : gmmktime( 0, 0, 1, $next_month['month_id'], date( 't', mktime( 0, 0, 0, $next_month['month_id'], 1, $next_month['year_id'] ) ), $next_month['year_id'] ) );
		$getcached	= ( count($presets) AND ( !isset($presets['honor_permissions']) OR !$presets['honor_permissions'] ) ) ? 1 : 0;

		//-----------------------------------------
		// Get the events (if we haven't already)
		//-----------------------------------------
		
		if( !array_key_exists( $month, $this->events ) )
		{
			$this->events[ $month ]	= array();

			if ( $getcached )
			{
				$where	= ( !empty( $presets['cal_id'] ) ? "e.event_calendar_id=" . intval( $presets['cal_id'] ) . " AND " : '' ) . "e.event_approved=1";
			}
			else
			{
				$where	= "e.event_calendar_id={$this->calendar['cal_id']} AND " . ( $this->memberData['g_is_supmod'] ? "e.event_approved IN (0,1)" : "e.event_approved=1" );
			}
			
			//-----------------------------------------
			// Query DB for events and loop
			//-----------------------------------------

			if( !$minimal )
			{
				$_joins	= array(
								array(
									'select'	=> 'm.*',
									'from'		=> array( 'members' => 'm' ),
									'where'		=> 'm.member_id=e.event_member_id',
									'type'		=> 'left',
									),
								array(
									'select'	=> 'pp.*',
									'from'		=> array( 'profile_portal' => 'pp' ),
									'where'		=> 'm.member_id=pp.pp_member_id',
									'type'		=> 'left',
									),
								);
							
				if ( $this->settings['reputation_enabled'] )
				{
					$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_reputation_cache.php', 'classReputationCache' );
					$this->registry->setClass( 'repCache', new $classToLoad() );
				
					$_joins[]	= $this->registry->getClass('repCache')->getTotalRatingJoin( 'event_id', 'e.event_id', 'calendar' );
					$_joins[]	= $this->registry->getClass('repCache')->getUserHasRatedJoin( 'event_id', 'e.event_id', 'calendar' );
				}
			}
		
			$this->DB->build( array(
									'select'	=> 'e.*',
									'from'		=> $minimal ? 'cal_events e' : array( 'cal_events' => 'e' ),
									'add_join'	=> $_joins,
									'where'		=> $where . " AND ( 
														( e.event_start_date <= '{$end_date}' AND e.event_end_date >= '{$start_date}' ) OR
														( ( e.event_end_date " . $this->DB->buildIsNull(true) . " OR e.event_end_date='0000-00-00 00:00:00' ) AND e.event_start_date >= '{$start_date}' AND e.event_start_date <= '{$end_date}' ) OR 
														( e.event_recurring=3 AND " . $this->DB->buildDateFormat( 'event_start_date', '%c' ) . "={$month} AND e.event_end_date <= '{$end_date}' ) )"
							) 		);
			$outer = $this->DB->execute();

			while( $r = $this->DB->fetch($outer) )
			{
				//-----------------------------------------
				// Exclude private events
				//-----------------------------------------

				if( $r['event_private'] AND !$getcached )
				{
					if( ! $this->memberData['member_id'] OR $this->memberData['member_id'] != $r['event_member_id'] )
					{
						continue;
					}
				}

				//-----------------------------------------
				// Check event permissions
				//-----------------------------------------
				
				if( $r['event_perms'] != '*' AND !$getcached )
				{
					$permissionGroups	= explode( ',', IPSText::cleanPermString( $r['event_perms'] ) );
					
					if( !IPSMember::isInGroup( $this->memberData, $permissionGroups ) )
					{
						continue;
					}
				}

				//-----------------------------------------
				// Dynamically fix missing SEO titles
				//-----------------------------------------
				
				if( !$r['event_title_seo'] )
				{
					$r['event_title_seo']	= IPSText::makeSeoTitle( $r['event_title'] );
					
					$this->DB->update( 'cal_events', array( 'event_title_seo' => $r['event_title_seo'] ), 'event_id=' . $r['event_id'] );
				}
				
				//-----------------------------------------
				// Create time info for PHP date functions
				//-----------------------------------------
				
				$_startTime	= strtotime( $r['event_start_date'] );
				$_endTime	= ( $r['event_end_date'] AND $r['event_end_date'] != '0000-00-00 00:00:00' ) ? strtotime( $r['event_end_date'] ) : 0;
				
				if( !$r['event_all_day'] )
				{
					if( $this->memberData['member_id'] AND $this->memberData['time_offset'] )
					{
						$_startTime	= $_startTime + ( $this->memberData['time_offset'] * 3600 );
					}
					else if( !$this->memberData['member_id'] AND $this->settings['time_offset'] )
					{
						$_startTime	= $_startTime + ( $this->settings['time_offset'] * 3600 );
					}

					if( $this->memberData['member_id'] AND $this->memberData['time_offset'] )
					{
						$_endTime	= $_endTime ? ( $_endTime + ( $this->memberData['time_offset'] * 3600 ) ) : 0;
					}
					else if( !$this->memberData['member_id'] AND $this->settings['time_offset'] )
					{
						$_endTime	= $_endTime ? ( $_endTime + ( $this->settings['time_offset'] * 3600 ) ) : 0;
					}
				}

				$r['event_start_date']	= gmstrftime( "%Y-%m-%d %H:%M:%S", $_startTime );
				$r['event_end_date']	= gmstrftime( "%Y-%m-%d %H:%M:%S", $_endTime );
				
				list( $_year, $_month, $_day )	= explode( '-', gmdate( 'Y-m-d', $_startTime ) );

				//-----------------------------------------
				// Recurring events
				//-----------------------------------------
				
				if( $r['event_recurring'] > 0 )
				{
					$r['recurring'] = 1;

					if( $_month == $month AND ( $_year == $year OR $r['event_recurring'] == 3 ) )
					{
						$this->events[ $_month ]['recurring'][ $r['event_id'] ] = $r;
					}

					//-----------------------------------------
					// Loop to add to other months as needed
					// Don't need to add yearly as we only check month
					//-----------------------------------------
					
					if( $r['event_recurring'] < 3 )
					{
						while( $_startTime < $_endTime )
						{
							//-----------------------------------------
							// Only add if not already present
							//-----------------------------------------
							
							if( !isset( $this->events[ $_month ]['recurring'][ $r['event_id'] ] ) )
							{
								if( ( ( $_month == $month OR $_month == $next_month['month_id'] OR $_month == $prev_month['month_id'] ) AND $_year == $year ) OR $getcached )
								{
									$this->events[ $_month ]['recurring'][ $r['event_id'] ] = $r;
								}
							}
	
							//-----------------------------------------
							// Increment timestamps and reset month/year
							//-----------------------------------------
							
							if( $r['event_recurring'] == 1 )
							{
								$_startTime = strtotime( gmstrftime( "%Y-%m-%d", $_startTime ) . " +1 week" );
							}
							elseif( $r['event_recurring'] == 2 )
							{
								$_startTime = strtotime( gmstrftime( "%Y-%m-%d", $_startTime ) . " +1 month" );
							}
	
							$_month	= gmstrftime( '%m', $_startTime );
							$_year	= gmstrftime( '%Y', $_startTime );
						}
					}
				}

				//-----------------------------------------
				// Ranged event?
				//-----------------------------------------

				else if( !$r['event_recurring'] AND $_endTime )
				{
					$r['ranged']	= 1;

					//-----------------------------------------
					// Daily loop
					//-----------------------------------------
					
					while( $_startTime <= $_endTime  )
					{
						if( $_startTime < gmmktime( 0, 0, 1, $prev_month['month_id'], 1, $prev_month['year_id'] ) OR $_startTime > gmmktime( 0, 0, 1, $next_month['month_id'], 31, $next_month['year_id'] ) )
						{
							$_startTime += 86400;
							continue;
						}

						$_dayOfMonth	= gmstrftime( "%d", $_startTime );
						$_month			= gmstrftime( "%m", $_startTime );

						$this->events[ $_month ]['ranged'][ $_dayOfMonth ][ $r['event_id'] ] = $r;

						$_startTime += 86400;
					}
				}
				
				//-----------------------------------------
				// Single event
				//-----------------------------------------
				
				else
				{
					$r['single'] = 1;

					$this->events[ $_month ]['single'][ $_day ][ $r['event_id'] ] = $r;
				}
			}
		}
    }

	/**
	 * Builds the html for the monthly events
	 *
	 * @param	int		$month		Numeric value of the month to get events from
	 * @param	int		$year		Year to get events from
	 * @param	bool	$minical	Set to 1 if this is for the minicalendar
	 * @return	string	Calendar HTML output
	 */
    public function getMonthEvents( $month, $year, $minical=false )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------

		$birthdays		= array();

		//-----------------------------------------
		// Work out timestamps
		//-----------------------------------------

		$our_datestamp	= gmmktime( 12,0,0, $month, 1, $year);
		$_firstDay		= gmdate( 'w', $our_datestamp );

		if( $this->settings['ipb_calendar_mon'] )
		{
			$_firstDay	= $_firstDay == 0 ? 7 : $_firstDay;
		}

		//-----------------------------------------
		// Get the birthdays from the database
		//-----------------------------------------

		if ( $this->settings['show_bday_calendar'] AND !$minical )
		{
			$birthdays		= $this->_getBirthdaysForMonth( $month );
		}

		//-----------------------------------------
		// Get the events
		//-----------------------------------------

		if( !$minical )
		{
			$this->calendarGetEventsSQL( $month, $year );
		}

		//-----------------------------------------
		// Get events
		//-----------------------------------------

		$seen_days		= array();
		$seen_events	= array();
		$output			= "";

		//-----------------------------------------
		// Loop (42 is 7 days x 6 "weeks" potential)
		//-----------------------------------------
		
		for( $c = 0 ; $c < 42; $c++ )
		{
			//-----------------------------------------
			// Get data for each day
			//-----------------------------------------

			list( $_year, $_month, $_day )	= explode( '-', gmstrftime( '%Y-%m-%d', $our_datestamp ) );
			$day_array						= IPSTime::date_getgmdate( $our_datestamp );

			//-----------------------------------------
			// If this is a new week, reset row
			//-----------------------------------------
			
			if( ( ( $c ) % 7 ) == 0 )
			{
				//-----------------------------------------
				// Kill the loop if we are no longer on our month
				//-----------------------------------------

				if( $day_array['mon'] != $month )
				{
					break;
				}

				$output .= $minical ? $this->registry->output->getTemplate('calendar')->mini_cal_new_row( $our_datestamp, $this->calendar ) : $this->registry->output->getTemplate('calendar')->cal_new_row( $our_datestamp, $this->calendar );
			}
			
			$_c	= $this->settings['ipb_calendar_mon'] ? $c + 1 : $c;

			//-----------------------------------------
			// Run out of legal days for this month?
			// Or have we yet to get to the first day?
			//-----------------------------------------

			if ( ( $_c < $_firstDay ) or ( $day_array['mon'] != $month ) )
			{
				$output .= $minical ? $this->registry->output->getTemplate('calendar')->mini_cal_blank_cell()
										: $this->registry->output->getTemplate('calendar')->cal_blank_cell();
			}
			else
			{
				if ( isset($seen_days[ $day_array['yday'] ]) )
				{
					$our_datestamp += 86400;
					
					continue;
				}

				$seen_days[ $day_array['yday'] ]	= true;
				$tmp_cevents						= array();
				$this_day_events					= "";
				$_hasQueued							= false;

				//-----------------------------------------
				// Get events
				//-----------------------------------------

				if( !$minical )
				{
					$events	= $this->calendarGetDayEvents( $_month, $_day, $_year );
	
					if ( is_array( $events ) AND count( $events ) )
					{
						foreach( $events as $event )
						{
							if ( empty( $seen_events[ $_month . '-' . $_day . '-' . $_year ][ $event['event_id'] ] ) )
							{
								//$event	= $this->calendarMakeEventHTML( $event, true );
	
								if ( isset($event['recurring']) )
								{
									$tmp_cevents[ $event['event_id'] ] = $this->registry->output->getTemplate('calendar')->cal_events_wrap_recurring( $event, 'month' );
								}
								else if ( isset($event['ranged']) )
								{
									$tmp_cevents[ $event['event_id'] ] = $this->registry->output->getTemplate('calendar')->cal_events_wrap_range( $event, 'month' );
								}
								else
								{
									$tmp_cevents[ $event['event_id'] ] = $this->registry->output->getTemplate('calendar')->cal_events_wrap( $event, 'month' );
								}
	
								$seen_events[ $_month . '-' . $_day . '-' . $_year ][ $event['event_id'] ]	= 1;
	
								//-----------------------------------------
								// Queued events?
								//-----------------------------------------
	
								if ( ! $event['event_approved'] AND $this->memberData['g_is_supmod'] )
								{
									$_hasQueued = true;
								}
							}
						}
	
						//-----------------------------------------
						// How many events?
						//-----------------------------------------
	
						if ( count($tmp_cevents) > $this->calendar['cal_event_limit'] )
						{
							$this_day_events	= $this->registry->output->getTemplate('calendar')->cal_events_wrap_manual(
																													  		array( 'url' => "cal_id={$this->calendar['cal_id']}&amp;do=showday&amp;y=" . $_year . "&amp;m=" . $_month . "&amp;d=" . $_day, 'template' => 'cal_day', 'title' => $this->calendar['cal_title_seo'] ),
																													  		sprintf( $this->lang->words['show_n_events'], intval(count($tmp_cevents)) ) 
																													  		);
						}
						else if ( count( $tmp_cevents ) )
						{
							$this_day_events	= implode( "\n", $tmp_cevents );
						}
	        		}
	
					//-----------------------------------------
					// Birthdays
					//-----------------------------------------
	
					if ( $this->calendar['cal_bday_limit'] )
					{
						if ( isset($birthdays[ $_day ]) and count( $birthdays[ $_day ] ) > 0 )
						{
							$no_bdays = count($birthdays[ $_day ]);
	
							if ( $no_bdays <= $this->calendar['cal_bday_limit'] )
							{
								foreach( $birthdays[ $_day ] as $user )
								{
									$this_day_events .= $this->registry->output->getTemplate('calendar')->cal_events_wrap_manual(
																																array( 'url' => "cal_id={$this->calendar['cal_id']}&amp;do=showday&amp;y=" . $_year . "&amp;m=" . $_month . "&amp;d=" . $_day, 'template' => 'cal_day', 'title' => $this->calendar['cal_title_seo'] ),
																																$user['members_display_name'] . $this->lang->words['bd_birthday'] 
																																);
								}
	
							}
							else
							{
								$this_day_events .= $this->registry->output->getTemplate('calendar')->cal_events_wrap_manual(
																															 array( 'url' => "cal_id={$this->calendar['cal_id']}&amp;do=showday&amp;y=" . $_year . "&amp;m=" . $_month . "&amp;d=" . $_day, 'template' => 'cal_day', 'title' => $this->calendar['cal_title_seo'] ),
																															 sprintf( $this->lang->words['entry_birthdays'], $no_bdays ) 
																															 );
							}
						}
	        		}
    			}

        		//-----------------------------------------
        		// If we have events, show them
        		//-----------------------------------------

				$_dateLink	= '';
				$_queueLink	= '';
				
				$_dateLink			= array(
											'url'		=> "app=calendar&amp;module=calendar&amp;section=view&amp;cal_id={$this->calendar['cal_id']}&amp;do=showday&amp;y=" . $year . "&amp;m=" . $month . "&amp;d=" . $day_array['mday'],
											'seotitle'	=> $this->calendar['cal_title_seo'],
											'template'	=> 'cal_day',
											'day'		=> $day_array['mday'],
											);
        										
				if( $this_day_events AND !$minical )
				{
					$_queueLink			= $this->registry->getClass('output')->buildSEOUrl( "app=calendar&amp;module=calendar&amp;section=view&amp;cal_id={$this->calendar['cal_id']}&amp;do=showday&amp;y=" . $year . "&amp;m=" . $month . "&amp;d=" . $day_array['mday'] . "&amp;modfilter=queued", 'public', $this->calendar['cal_title_seo'], 'cal_day' );
					$this_day_events	= $this->registry->output->getTemplate('calendar')->eventsWrapper( $this_day_events );
				}

				/* Apply TZ offset so that we show the correct 'today' */
        		if ( $day_array['mday'] == strftime( '%d', time() + $this->lang->getTimeOffset() ) AND $day_array['mon'] == strftime( '%m', time() + $this->lang->getTimeOffset() ) AND $day_array['year'] == strftime( '%Y', time() + $this->lang->getTimeOffset() ) )
        		{
        			$output .= $minical ? $this->registry->output->getTemplate('calendar')->mini_cal_date_cell_today($_dateLink) : $this->registry->output->getTemplate('calendar')->cal_date_cell_today($_dateLink, $this_day_events, $_queueLink, $_hasQueued, $this->_buildDayID( $this->chosen_date['year'], $this->chosen_date['month'], $_day ) );
        		}
        		else
        		{
        			$output .= $minical ? $this->registry->output->getTemplate('calendar')->mini_cal_date_cell($_dateLink) : $this->registry->output->getTemplate('calendar')->cal_date_cell($_dateLink, $this_day_events, $_queueLink, $_hasQueued, $this->_buildDayID( $this->chosen_date['year'], $this->chosen_date['month'], $_day ) );
        		}

        		$our_datestamp += 86400;
        	}
        }

    	return $output;
    }

	/**
	 * Display the events for a calendar date
	 *
	 * @return	@e void
	 */
	public function calendarShowDay()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$day		= str_pad( intval( $this->request['d'] ), 2, '0', STR_PAD_LEFT );
		$month		= $this->chosen_date['month'];
		$year		= $this->chosen_date['year'];
		$seen_ids	= array();
		$output		= '';

		if ( ! checkdate( $month, $day , $year ) )
		{
			$this->registry->output->showError( 'calendar_invalid_date_view', 10428, null, null, 404 );
		}
		
		$_previous	= $this->calendarGetPreviousMonth( $this->chosen_date['month'], $this->chosen_date['year'] );
		$_next		= $this->calendarGetNextMonth( $this->chosen_date['month'], $this->chosen_date['year'] );

		//-----------------------------------------
		// Load the events
		//-----------------------------------------
		
		$this->calendarGetEventsSQL( $month, $year );

		//-----------------------------------------
		// Get today's events from loaded events
		//-----------------------------------------
		
		$events	= $this->calendarGetDayEvents( $month, $day, $year );

		//-----------------------------------------
		// Loop over today's events
		//-----------------------------------------
		
		if ( is_array( $events ) AND count( $events ) )
		{
			foreach( $events as $event )
			{
				if ( empty( $seen_ids[ $event['eventid'] ] ) )
				{
					$seen_ids[ $event['event_id'] ]	= true;

					//-----------------------------------------
					// Exclude private events
					//-----------------------------------------
					
					if( $event['event_private'] AND ( !$this->memberData['member_id'] OR $this->memberData['member_id'] != $event['event_member_id'] ) )
					{
						continue;
					}
	
					//-----------------------------------------
					// Check event permissions
					//-----------------------------------------
					
					if( $event['event_perms'] != '*' )
					{
						$permissionGroups	= explode( ',', IPSText::cleanPermString( $event['event_perms'] ) );
						
						if( !IPSMember::isInGroup( $this->memberData, $permissionGroups ) )
						{
							continue;
						}
					}

					//-----------------------------------------
					// Output
					//-----------------------------------------
					
					$_data	= $this->calendarMakeEventHTML( $event, true );
					$output	.= $this->registry->output->getTemplate( 'calendar' )->showEventSimple( array_merge( $_data['member'], $_data['event'] ) );
				}
			}
		}
		
		//-----------------------------------------
		// Show birthdays
		//-----------------------------------------
		
		$birthdays	= $this->calendar['cal_bday_limit'] ? $this->calendarMakeBirthdayHTML( $month, $day, $year ) : '';

		//-----------------------------------------
		// Output
		//-----------------------------------------

		$this->registry->output->addNavigation( $this->chosen_date['month_name'] . " " . $this->chosen_date['year'], "app=calendar&amp;module=calendar&amp;section=view&amp;cal_id={$this->calendar['cal_id']}&amp;m={$this->chosen_date['month']}&amp;y={$this->chosen_date['year']}", $this->calendar['cal_title_seo'], 'cal_month' );
		$this->registry->output->addNavigation( $this->chosen_date['month_name'] . ' ' . $day . ', ' . $this->chosen_date['year'] );
		$this->registry->output->addToDocumentHead( 'raw', "<link rel='up' href='" . $this->registry->output->buildSEOUrl( 'app=calendar&amp;module=calendar&amp;section=view&amp;cal_id=' . $this->calendar['cal_id'], 'publicNoSession', $this->calendar['cal_title_seo'], 'cal_calendar' ) . "' />" );
		$this->registry->output->addMetaTag( 'keywords', $this->chosen_date['month_name'] . ' ' . $day . ' events event calendar', TRUE );
		$this->registry->output->addMetaTag( 'description', str_replace( "\n", " ", str_replace( "\r", "", sprintf( $this->lang->words['day_meta_description'], $this->chosen_date['month_name'], $day, $this->chosen_date['year'] ) ) ), FALSE, 155 );
		$this->registry->getClass('output')->addCanonicalTag( 'app=calendar&amp;module=calendar&amp;section=view&amp;cal_id=' . $this->calendar['cal_id'] . '&amp;do=showday&amp;y=' . $this->chosen_date['year'] . '&amp;m=' . $this->chosen_date['month'] . '&amp;d=' . $day, $this->calendar['cal_title_seo'], 'cal_day' );

		$this->page_title	= $this->chosen_date['month_name'] . ' ' . $day . ', ' . $this->chosen_date['year'];
		$this->output		.= $this->registry->output->getTemplate( 'calendar' )->calendarEventsList( array(
																									'calendars'		=> $this->functions->getCalendars(),
																									'calendar'		=> $this->calendar,
																									'prev_minical'	=> $this->getMiniCalendar( $_previous['month_id'], $_previous['year_id'] ),
																									'cur_minical'	=> $this->getMiniCalendar( $this->chosen_date['month'], $this->chosen_date['year'] ),
																									'next_minical'	=> $this->getMiniCalendar( $_next['month_id'], $_next['year_id'] ),
																									'month_box'		=> $this->functions->returnMonthDropdown( $this->chosen_date['month'] ),
																									'year_box'		=> $this->functions->returnYearDropdown( $this->chosen_date['year'] ),
																									'events'		=> $output,
																									'birthdays'		=> $birthdays,
																									'chosen_date'	=> $this->chosen_date,
																									'prev_month'	=> $_previous,
																									'next_month'	=> $_next,
																									'start_date'	=> $this->chosen_date['month_name'] . ' ' . $day . ', ' . $this->chosen_date['year'],
																									'prev_day'		=> explode( '-', gmstrftime( '%Y-%m-%d', gmmktime( 0, 0, 0, $this->chosen_date['month'], $day - 1, $this->chosen_date['year'] ) ) ),
																									'next_day'		=> explode( '-', gmstrftime( '%Y-%m-%d', gmmktime( 0, 0, 0, $this->chosen_date['month'], $day + 1, $this->chosen_date['year'] ) ) ),
																									'this_week'		=> gmmktime( 0, 0, 0, $this->chosen_date['month'], $day, $this->chosen_date['year'] ),
																									'_can_add'		=> ( $this->memberData['member_id'] AND $this->registry->permissions->check( 'start', $this->calendar ) ),
																							) );
	}

	/**
	 * Gets the days events
	 *
	 * @param	string	$month	Month
	 * @param	string	$day	Day
	 * @param	string	$year	Year
	 * @return	array 	Events for specified day
	 */
	public function calendarGetDayEvents( $month, $day, $year )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------

		$return	= array();

		//-----------------------------------------
		// Check ranged
		//-----------------------------------------
		
		if( isset( $this->events[ $month ]['ranged'][ $day ] ) AND is_array( $this->events[ $month ]['ranged'][ $day ] ) and count( $this->events[ $month ]['ranged'][ $day ] ) )
		{
			foreach( $this->events[ $month ]['ranged'][ $day ] as $idx => $data )
			{
				$return[]	= $data;
			}
		}

		//-----------------------------------------
		// Recurring
		//-----------------------------------------

		if( isset( $this->events[ $month ]['recurring'] ) AND is_array( $this->events[ $month ]['recurring'] ) and count( $this->events[ $month ]['recurring'] ) )
		{
			foreach( $this->events[ $month ]['recurring'] as $idx => $data )
			{
				if ( $this->checkRecurring( $data, $month, $day, $year ) )
				{
					$return[]	= $data;
				}
			}
		}

		//-----------------------------------------
		// Single day
		//-----------------------------------------
		
		if( isset( $this->events[ $month ]['single'][ $day ] ) AND is_array( $this->events[ $month ]['single'][ $day ] ) and count( $this->events[ $month ]['single'][ $day ] ) )
		{
			foreach( $this->events[ $month ]['single'][ $day ] as $idx => $data )
			{
				$return[]	= $data;
			}
		}

		return $return;
	}
	
	/**
	 * Return all cached events
	 *
	 * @return	array
	 */
	public function calendarGetAllEvents()
	{
		$events	= array();
		
		foreach( $this->events as $month => $type )
		{
			foreach( $type as $_type => $info )
			{
				if( $_type == 'recurring' )
				{
					foreach( $info as $event )
					{
						$events[ $event['event_id'] ]	= $event;
					}
				}
				else
				{
					foreach( $info as $day => $_dayEvents )
					{
						foreach( $_dayEvents as $event )
						{
							$events[ $event['event_id'] ]	= $event;
						}
					}
				}
			}
		}
		
		return $events;
	}

	/**
	 * Verify if a recurring event should be shown on a given day
	 *
	 * @param	array	$event	Event data
	 * @param	int		$month	Month
	 * @param	int		$day	Day
	 * @param	int		$year	Year
	 * @return	bool
	 */
	public function checkRecurring( $event, $month, $day, $year  )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		if( !$event['event_id'] )
		{
			return false;
		}
		
		if ( !$event['event_recurring'] )
		{
			return false;
		}

		static $_shownEvents	= array();
		
		/**
		 * We set start time to end of day and end time to beginning of day, because our "in range" check below
		 * checks if the event start time is greater than the day time and end time is less than the day time.  Thus
		 * an event on August 10 2011 4pm (recurring) will not show if we check if it's greater than the beginning of the day
		 * but will if we check if it's greater than the end of day (i.e. it occurs on that day).  I know this sounds confusing,
		 * but I'm just pointing out that start is intended to be END of day and end is BEGINNING of day on purpose.
		 * @link	http://community.invisionpower.com/tracker/issue-32575-calendar-recurring-events
		 */
		$_start			= gmmktime( 23, 59, 59, $month, $day, $year );
		$_lunch			= gmmktime( 12, 0 , 0 , $month, $day, $year );
		$_end			= gmmktime( 0 , 0 , 0 , $month, $day, $year );
		$_eventStart	= strtotime( $event['event_start_date'] );
		$_eventEnd		= strtotime( $event['event_end_date'] );

		//-----------------------------------------
		// Already seen it?
		//-----------------------------------------

		if ( !empty( $_shownEvents[ $month . '-' . $day . '-' . $year ][ $event['event_id'] ] ) )
		{
			return false;
		}

		//-----------------------------------------
		// Check we're in range
		//-----------------------------------------

		if ( $_eventStart > $_start OR $_eventEnd < $_end )
		{
			return false;
		}

		//-----------------------------------------
		// Check recurring
		//-----------------------------------------

		if ( $event['event_recurring'] )
		{
			//-----------------------------------------
			// Weekly
			//-----------------------------------------

			if ( $event['event_recurring'] == 1 )
			{
				return ( gmstrftime( '%w', $_eventStart ) == gmstrftime( '%w', $_lunch ) ) ? true : false;
			}

			//-----------------------------------------
			// Monthly
			//-----------------------------------------

			else if ( $event['event_recurring'] == 2 )
			{
				return ( gmstrftime( '%d', $_eventStart ) == gmstrftime( '%d', $_lunch ) ) ? true : false;
			}

			//-----------------------------------------
			// Yearly
			//-----------------------------------------

			else if ( $event['event_recurring'] == 3 )
			{
				return ( gmstrftime( '%d', $_eventStart ) == gmstrftime( '%d', $_lunch ) AND gmstrftime( '%m', $_eventStart ) == gmstrftime( '%m', $_lunch ) ) ? true : false;
			}
		}

		return false;
	}

	/**
	 * Builds the html for an event
	 *
	 * @param	array	Array of event data
	 * @param	bool	Parse the data and return the parsed array, instead of the formatted HTML
	 * @return	string	Parsed event HTML
	 */
	public function calendarMakeEventHTML( $event, $returnAsArray=false )
	{
		//-----------------------------------------
		// Caching
		//-----------------------------------------
		
		static $cachedEvents	= array();
		
		if( isset($cachedEvents[ $event['event_id'] ]) )
		{
			if( $returnAsArray )
			{
				return $cachedEvents[ $event['event_id'] ];
			}
			else
			{
				return $this->registry->output->getTemplate('calendar')->showEvent( $cachedEvents[ $event['event_id'] ]['event'], $cachedEvents[ $event['event_id'] ]['member'], array( 'type' => $cachedEvents[ $event['event_id'] ]['info']['type'], 'ends' => $cachedEvents[ $event['event_id'] ]['info']['ends'], 'calendars' => $this->functions->getCalendars(), 'calendar' => $this->calendar, 'chosen_date' => $this->chosen_date ) );
			}
		}

		if( $this->canReport === 'notinit' )
		{
			$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir('core') . '/sources/classes/reportLibrary.php', 'reportLibrary' );
			$reports			= new $classToLoad( $this->registry );
			
			$this->canReport	= $reports->canReport( 'calendar' );
		}

		$event['_canReport']	= $this->canReport;

		//-----------------------------------------
		// Get member details
		//-----------------------------------------

		$member	= IPSMember::buildDisplayData( $event );
		
		//-----------------------------------------
		// Like strip
		//-----------------------------------------
		
		if( !$this->_like )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
			$this->_like = classes_like::bootstrap( 'calendar', 'events' );
		}
		
		$event['_like_strip']	= $this->_like->render( 'summary', $event['event_id'] );
		$event['_like_count']	= $this->_like->getCount( $event['event_id'] );

		//-----------------------------------------
		// Reputation
		//-----------------------------------------
		
		if( is_null($event['has_given_rep']) )
		{
			$event['has_given_rep']	= 0;
		}
		
		if( is_null($event['rep_points']) )
		{
			$event['rep_points']	= 0;
		}
		
		if ( $this->settings['reputation_enabled'] )
		{
			$event['like']	= $this->registry->repCache->getLikeFormatted( array( 'app' => 'calendar', 'type' => 'event_id', 'id' => $event['event_id'], 'rep_like_cache' => $event['rep_like_cache'] ) );
		}
				
		//-----------------------------------------
		// Times and dates
		//-----------------------------------------
		
		$event['_start_time']	= strtotime( $event['event_start_date'] );
		$event['_end_time']		= ( $event['event_end_date'] AND $event['event_end_date'] != '0000-00-00 00:00:00' ) ? strtotime( $event['event_end_date'] ) : 0;

		if( !$event['event_all_day'] )
		{
			if( $this->memberData['member_id'] AND $this->memberData['time_offset'] )
			{
				$event['_start_time']	= $event['_start_time'] + ( $this->memberData['time_offset'] * 3600 );
			}
			else if( !$this->memberData['member_id'] AND $this->settings['time_offset'] )
			{
				$event['_start_time']	= $event['_start_time'] + ( $this->settings['time_offset'] * 3600 );
			}

			if( $this->memberData['member_id'] AND $this->memberData['time_offset'] )
			{
				$event['_end_time']		= $event['_end_time'] ? ( $event['_end_time'] + ( $this->memberData['time_offset'] * 3600 ) ) : 0;
			}
			else if( !$this->memberData['member_id'] AND $this->settings['time_offset'] )
			{
				$event['_end_time']		= $event['_end_time'] ? ( $event['_end_time'] + ( $this->settings['time_offset'] * 3600 ) ) : 0;
			}
		}

		$event['_start_date']	= gmstrftime( $this->settings['clock_date'], $event['_start_time'] );
		$event['_event_time']	= '';
		$event['_event_etime']	= '';
		
		if( !$event['event_all_day'] )
		{
			if( $this->settings['cal_time_format'] == 'standard' )
			{
				$event['_event_time']	= gmstrftime( '%I:%M %p', $event['_start_time'] );
				$event['_event_etime']	= $event['_end_time'] ? gmstrftime( '%I:%M %p', $event['_end_time'] ) : '';
			}
			else
			{
				$event['_event_time']	= gmstrftime( '%H:%M', $event['_start_time'] );
				$event['_event_etime']	= $event['_end_time'] ? gmstrftime( '%H:%M', $event['_end_time'] ) : '';
			}
		}

		//-----------------------------------------
		// Event type
		//-----------------------------------------
		
		$type	= $this->lang->words['se_normal'];
		$ends	= '';
	
		if( !$event['event_recurring'] AND $event['_end_time'] AND gmstrftime( $this->settings['clock_date'], $event['_end_time'] ) != $event['_start_date'] )
		{
			$type	= $this->lang->words['se_range'];
			$ends	= sprintf( $this->lang->words['se_ends'], gmstrftime( $this->settings['clock_date'], $event['_end_time'] ) );
		}
		else if ( $event['event_recurring'] )
		{
			$type	= $this->lang->words['se_recur'];
			$ends	= sprintf( $this->lang->words['se_ends'], gmstrftime( $this->settings['clock_date'], $event['_end_time'] ) );
		}

		//-----------------------------------------
		// Event content
		//-----------------------------------------
		
		IPSText::getTextClass( 'bbcode' )->parse_html				= 0;
		IPSText::getTextClass( 'bbcode' )->parse_smilies			= intval( $event['event_smilies'] );
		IPSText::getTextClass( 'bbcode' )->parse_bbcode				= 1;
		IPSText::getTextClass( 'bbcode' )->parsing_section			= 'calendar';
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $member['member_group_id'];
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $member['mgroup_others'];
	
		$event['event_content']			= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $event['event_content'] );
		$event['event_attach_content']	= '';

		//-----------------------------------------
		// Parse attachments
		//-----------------------------------------

		static $attachments		= null;

		if( $event['event_attachments'] )
		{
			//-----------------------------------------
			// Get attachments class
			//-----------------------------------------
			
			if( !$attachments )
			{
				$this->lang->loadLanguageFile( array( 'public_topic' ), 'forums' );
				$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
				$attachments		=  new $classToLoad( $this->registry );
				$attachments->type	= 'event';
				$attachments->init();
			}
		
			$attachHTML	= $attachments->renderAttachments( array( $event['event_id'] => $event['event_content'] ), array( $event['event_id'] ) );
			
			if( is_array($attachHTML) )
			{
				$event['event_content']			= $attachHTML[ $event['event_id'] ]['html'];
				$event['event_attach_content']	= $attachHTML[ $event['event_id'] ]['attachmentHtml'];
			}
		}
		
		//-----------------------------------------
		// Rating
		//-----------------------------------------
		
		$event['_can_rate']		= ( $this->memberData['member_id'] AND $this->registry->permissions->check( 'rate', $this->calendar ) ) ? 1 : 0;
		$event['_rating_value']	= -1;
		
		if( $event['_can_rate'] )
		{
			$rating	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'cal_event_ratings', 'where' => "rating_eid={$event['event_id']} and rating_member_id=" . $this->memberData['member_id'] ) );
			
			$event['_rating_value']	= $rating['rating_value'] ? $rating['rating_value'] : -1;
		}
		
		//-----------------------------------------
		// RSVP attendees
		//-----------------------------------------
		
		$event['_rsvp_attendees']		= array();
		$event['_rsvp_attendees_short']	= array();
		$event['_rsvp_count']			= 0;
		$event['_can_rsvp']				= false;
		$event['_can_delete_rsvp']		= false;
		
		if( $event['event_rsvp'] )
		{
			$this->DB->build( array(
									'select'	=> 'a.*',
									'from'		=> array( 'cal_event_rsvp' => 'a' ),
									'where'		=> 'a.rsvp_event_id=' . $event['event_id'],
									'order'		=> 'm.members_display_name ASC',
									'add_join'	=> array(
														array(
															'select'	=> 'm.*',
															'from'		=> array( 'members' => 'm' ),
															'where'		=> 'm.member_id=a.rsvp_member_id',
															'type'		=> 'left',
															),
														array(
															'select'	=> 'pp.*',
															'from'		=> array( 'profile_portal' => 'pp' ),
															'where'		=> 'm.member_id=pp.pp_member_id',
															'type'		=> 'left',
															),
														)
							)		);
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$event['_rsvp_attendees'][ $r['member_id'] ]	= IPSMember::buildDisplayData( array_merge( $r, $this->caches['group_cache'][ $r['member_group_id'] ] ) );
				
				$event['_rsvp_attendees'][ $r['member_id'] ]['_can_delete_rsvp']	= false;
				
				if( $this->calendar['cal_rsvp_owner'] AND $this->memberData['member_id'] AND $this->memberData['member_id'] == $r['member_id'] )
				{
					$event['_rsvp_attendees'][ $r['member_id'] ]['_can_delete_rsvp']	= true;
				}
				
				if( $event['_rsvp_count'] < 5 )
				{
					$event['_rsvp_attendees_short'][ $r['member_id'] ] = $event['_rsvp_attendees'][ $r['member_id'] ];
				}
				
				$event['_rsvp_count']++;
			}
			
			if( $this->memberData['member_id'] AND $this->registry->permissions->check( 'rsvp', $this->calendar ) )
			{
				if( !isset( $event['_rsvp_attendees'][ $this->memberData['member_id'] ] ) )
				{
					$event['_can_rsvp']		= true;
				}
				else
				{
					$event['_have_rsvp']	= true;
				}
			}
			
			if( $this->calendar['cal_rsvp_owner'] AND $this->memberData['member_id'] AND $this->memberData['member_id'] == $event['event_member_id'] )
			{
				$event['_can_delete_rsvp']	= true;
			}
		}
	
		//-----------------------------------------
		// Return formatted HTML
		//-----------------------------------------
		
		$cachedEvents[ $event['event_id'] ]	= array( 'member' => $member, 'event' => $event, 'info' => array( 'type' => $type, 'ends' => $ends ) );
		
		if( $returnAsArray )
		{
			return $cachedEvents[ $event['event_id'] ];
		}
		else
		{
			return $this->registry->output->getTemplate('calendar')->showEvent( $event, $member, array( 'type' => $type, 'ends' => $ends, 'calendars' => $this->functions->getCalendars(), 'calendar' => $this->calendar, 'chosen_date' => $this->chosen_date ) );
		}
	}

	/**
	 * Show a single event based on eventid
	 *
	 * @return	@e void
	 */
	public function calendarShowEvent()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$event_id	= intval($this->request['event_id']);

		if( !$event_id )
		{
			$this->registry->output->showError( 'calendar_event_not_found', 10429, null, null, 404 );
		}
		
		//-----------------------------------------
		// Get the event data
		//-----------------------------------------
		
		$_joins	= array(
						array(
							'select'	=> 'm.*',
							'from'		=> array( 'members' => 'm' ),
							'where'		=> 'm.member_id=e.event_member_id',
							'type'		=> 'left',
							),
						array(
							'select'	=> 'pp.*',
							'from'		=> array( 'profile_portal' => 'pp' ),
							'where'		=> 'm.member_id=pp.pp_member_id',
							'type'		=> 'left',
							),
						);
					
		if ( $this->settings['reputation_enabled'] )
		{
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_reputation_cache.php', 'classReputationCache' );
			$this->registry->setClass( 'repCache', new $classToLoad() );
			
			$_joins[]	= $this->registry->getClass('repCache')->getTotalRatingJoin( 'event_id', $event_id, 'calendar' );
			$_joins[]	= $this->registry->getClass('repCache')->getUserHasRatedJoin( 'event_id', $event_id, 'calendar' );
		}

		$event	= $this->DB->buildAndFetch( array( 'select' => 'e.*', 'from' => array( 'cal_events' => 'e' ), 'where' => 'e.event_id=' . $event_id, 'add_join' => $_joins ) );

		if ( !$event['event_id'] )
		{
			$this->registry->output->showError( 'calendar_event_not_found', 10430, null, null, 404 );
		}
		
		//-----------------------------------------
		// Reset calendar
		//-----------------------------------------
		
		$this->calendar	= $this->functions->getCalendar( $event['event_calendar_id'] );
		
		if( $this->calendar['cal_id'] != $event['event_calendar_id'] )
		{
			$this->registry->output->showError( 'cal_no_perm', 1040.22, null, null, 403 );
		}

		//-----------------------------------------
		// Check permissions
		//-----------------------------------------
		
		if ( $event['event_private'] AND $this->memberData['member_id'] != $event['event_member_id'] )
		{
			$this->registry->output->showError( 'calendar_event_not_found', 10431, null, null, 403 );
		}

		if( !$this->memberData['g_is_supmod'] AND !$event['event_approved'] )
		{
			$this->registry->output->showError( 'calendar_event_not_found', 10432.1, null, null, 404 );
		}

		if( $event['event_perms'] != '*' )
		{
			$permissionGroups	= explode( ',', IPSText::cleanPermString( $event['event_perms'] ) );
			
			if( !IPSMember::isInGroup( $this->memberData, $permissionGroups ) )
			{
				$this->registry->output->showError( 'calendar_event_not_found', 10432, null, null, 404 );
			}
		}
		
		//-----------------------------------------
		// Are we RSVPing?
		//-----------------------------------------

		if( $this->request['_rsvp'] AND $event['event_rsvp'] )
		{
			if( $this->registry->permissions->check( 'rsvp', $this->calendar ) AND $this->memberData['member_id'] )
			{
				//-----------------------------------------
				// Make sure we aren't already RSVPed
				//-----------------------------------------
				
				$_check	= $this->DB->buildAndFetch( array( 'select' => 'rsvp_id', 'from' => 'cal_event_rsvp', 'where' => 'rsvp_event_id=' . $event['event_id'] . ' AND rsvp_member_id=' . $this->memberData['member_id'] ) );
				
				if( !$_check['rsvp_id'] )
				{
					$_insert	= array(
										'rsvp_event_id'		=> $event['event_id'],
										'rsvp_member_id'	=> $this->memberData['member_id'],
										'rsvp_date'			=> time(),
										);

					$this->DB->insert( 'cal_event_rsvp', $_insert );
					
					$this->registry->output->redirectScreen( $this->lang->words['rsvp_saved_im'], $this->settings['base_url'] . "app=calendar&amp;module=calendar&amp;section=view&amp;do=showevent&amp;event_id=" . $event['event_id'], $event['event_title_seo'], 'cal_event' );
				}
			}
		}

		//-----------------------------------------
		// Comments class
		//-----------------------------------------
		
		require_once( IPS_ROOT_PATH . 'sources/classes/comments/bootstrap.php' );/*noLibHook*/
		$this->_comments = classes_comments_bootstrap::controller( 'calendar-events' );
		
		$comments = array( 'html'  => $this->_comments->fetchFormatted( $event, array( 'offset' => intval( $this->request['st'] ) ) ),
						   'count' => $this->_comments->count( $event ),
						  );

		//-----------------------------------------
		// Highlight...
		//-----------------------------------------

		if ( $this->request['hl'] )
		{
			$event['event_content']	= IPSText::searchHighlight( $event['event_content'], $this->request['hl'] );
			$event['event_title']	= IPSText::searchHighlight( $event['event_title'], $this->request['hl'] );
		}

		//-----------------------------------------
		// Can we report?
		//-----------------------------------------

		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('core') . '/sources/classes/reportLibrary.php', 'reportLibrary', 'core' );
		$reports		= new $classToLoad( $this->registry );
		
		$event['_canReport']	= $reports->canReport( 'calendar' );
		
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$member	= IPSMember::load( $event['event_member_id'], 'all' );

		$this->registry->output->addNavigation( $this->calendar['cal_title']    , "app=calendar&amp;module=calendar&amp;section=view&amp;cal_id={$this->calendar['cal_id']}", $this->calendar['cal_title_seo'], 'cal_calendar' );

		//-----------------------------------------
		// Try to match out and improve navigation
		//-----------------------------------------

		$_referrer	= $_SERVER['HTTP_REFERER'];

		if( $_referrer )
		{
			//-----------------------------------------
			// Came from add form?
			//-----------------------------------------
			
			if( preg_match( "#/add$#", $_referrer ) )
			{
				$_data		= $this->calendarMakeEventHTML( $event, true );
				$_dateBits	= explode( '-', gmstrftime( '%Y-%m-%d-%B', $_data['event']['_start_time'] ) );
				$this->registry->output->addNavigation( $_dateBits[3] . ' ' . $_dateBits[0], "app=calendar&amp;module=calendar&amp;section=view&amp;cal_id={$this->calendar['cal_id']}&amp;m={$_dateBits[1]}&amp;y={$_dateBits[0]}", $this->calendar['cal_title_seo'], 'cal_month' );
				$this->registry->output->addNavigation( $_dateBits[3] . ' ' . $_dateBits[2] . ', ' . $_dateBits[0], "app=calendar&amp;module=calendar&amp;section=view&amp;cal_id={$this->calendar['cal_id']}&amp;do=showday&amp;y={$_dateBits[0]}&amp;m={$_dateBits[1]}&amp;d={$_dateBits[2]}", $this->calendar['cal_title_seo'], 'cal_day' );
			}
			
			//-----------------------------------------
			// Came from a day view?
			//-----------------------------------------
			
			else if( preg_match( "#/day\-(\d{4})\-(\d{1,2})\-(\d{1,2})$#i", $_referrer, $matches ) )
			{
				$_dateBits	= explode( '-', gmstrftime( '%Y-%m-%d-%B', gmmktime( 0, 0, 0, $matches[2], $matches[3], $matches[1] ) ) );
				$this->registry->output->addNavigation( $_dateBits[3] . ' ' . $_dateBits[0], "app=calendar&amp;module=calendar&amp;section=view&amp;cal_id={$this->calendar['cal_id']}&amp;m={$_dateBits[1]}&amp;y={$_dateBits[0]}", $this->calendar['cal_title_seo'], 'cal_month' );
				$this->registry->output->addNavigation( $_dateBits[3] . ' ' . $_dateBits[2] . ', ' . $_dateBits[0], "app=calendar&amp;module=calendar&amp;section=view&amp;cal_id={$this->calendar['cal_id']}&amp;do=showday&amp;y={$_dateBits[0]}&amp;m={$_dateBits[1]}&amp;d={$_dateBits[2]}", $this->calendar['cal_title_seo'], 'cal_day' );
			}
			
			//-----------------------------------------
			// How about a week view?
			//-----------------------------------------
			
			else if( preg_match( "#/week\-(\d+?)$#i", $_referrer, $matches ) )
			{
				$_dateBits	= explode( '-', gmstrftime( '%Y-%m-%d-%B', $matches[1] ) );
				$this->registry->output->addNavigation( $_dateBits[3] . ' ' . $_dateBits[0], "app=calendar&amp;module=calendar&amp;section=view&amp;cal_id={$this->calendar['cal_id']}&amp;m={$_dateBits[1]}&amp;y={$_dateBits[0]}", $this->calendar['cal_title_seo'], 'cal_month' );
				$this->registry->output->addNavigation( "{$this->lang->words['week_beginning']} " . gmstrftime( '%B %d, %Y', $matches[1] ), "app=calendar&amp;module=calendar&amp;section=view&amp;cal_id={$this->calendar['cal_id']}&amp;do=showweek&amp;week={$matches[1]}", $this->calendar['cal_title_seo'], 'cal_week' );
			}

			//-----------------------------------------
			// Or a month view?
			//-----------------------------------------

			else if( preg_match( "#/(\d{1,2})\-(\d{4})$#i", $_referrer, $matches ) )
			{
				$_dateBits	= explode( '-', gmstrftime( '%Y-%m-%d-%B', gmmktime( 0, 0, 0, $matches[1], 15, $matches[2] ) ) );
				$this->registry->output->addNavigation( $_dateBits[3] . " " . $_dateBits[0], "app=calendar&amp;module=calendar&amp;section=view&amp;cal_id={$this->calendar['cal_id']}&amp;m={$_dateBits[1]}&amp;y={$_dateBits[0]}", $this->calendar['cal_title_seo'], 'cal_month' );
			}
			else if( preg_match( "#/(\d+?)\-(.+?)$#i", $_referrer, $matches ) )
			{
				$_data		= $this->calendarMakeEventHTML( $event, true );
				$_dateBits	= explode( '-', gmstrftime( '%Y-%m-%d-%B', $_data['event']['_start_time'] ) );
				$this->registry->output->addNavigation( $_dateBits[3] . " " . $_dateBits[0], "app=calendar&amp;module=calendar&amp;section=view&amp;cal_id={$this->calendar['cal_id']}&amp;m={$_dateBits[1]}&amp;y={$_dateBits[0]}", $this->calendar['cal_title_seo'], 'cal_month' );
			}
		}
		
		//-----------------------------------------
		// Finish output
		//-----------------------------------------
		
		$this->registry->output->addNavigation( $event['event_title'] );
		$this->registry->output->addMetaTag( 'keywords', $this->chosen_date['month_name'] . ' ' . $_dateBits[2] . ' events event calendar ' . $event['event_title'] . ' ' . IPSText::getTextClass( 'bbcode' )->stripAllTags( $event['event_content'] ), TRUE );
		$this->registry->output->addMetaTag( 'description', str_replace( "\n", " ", str_replace( "\r", "", IPSText::getTextClass( 'bbcode' )->stripAllTags( $event['event_content'] ) ) ), FALSE, 155 );
		$this->registry->output->addToDocumentHead( 'raw', "<link rel='up' href='" . $this->registry->output->buildSEOUrl( 'app=calendar&amp;module=calendar&amp;section=view&amp;cal_id=' . $event['event_calendar_id'], 'publicNoSession', $this->calendar['cal_title_seo'], 'cal_calendar' ) . "' />" );
		$this->registry->output->addToDocumentHead( 'raw', "<link rel='author' href='" . $this->registry->output->buildSEOUrl( 'showuser=' . $event['event_member_id'], 'publicNoSession', $member['members_seo_name'], 'showuser' ) . "' />" );
		$this->registry->getClass('output')->addCanonicalTag( 'app=calendar&amp;module=calendar&amp;section=view&amp;do=showevent&amp;event_id=' . $event['event_id'], $event['event_title_seo'], 'cal_event' );

		$this->page_title	= $event['event_title'];
		$this->output		.= $this->registry->output->getTemplate( 'calendar' )->calendarShowEvent( $this->calendarMakeEventHTML( $event ), $comments );
    }

	/**
	 * Shows the events for a week
	 *
	 * @return	@e void
	 */
	public function calendarShowWeek()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$in_week		= intval( $this->request['week'] );
		$_dayOfWeek		= gmdate( 'w', $in_week );
		$last_month_id	= -1;
		$output			= '';
		$seenIds		= array();

		//-----------------------------------------
		// Figure out date stuff
		//-----------------------------------------
		
		if( !$this->settings['ipb_calendar_mon'] )
		{
			if( $_dayOfWeek > 0 )
			{
				while( $_dayOfWeek > 0 )
				{
					$_dayOfWeek--;
					$in_week	-= 86400;
				}
			}
		}
		else
		{
			if( $_dayOfWeek != 1 )
			{
				$_dayOfWeek	= !$_dayOfWeek ? 6 : $_dayOfWeek;

				while( $_dayOfWeek != 1 )
				{
					$_dayOfWeek--;
					$in_week	-= 86400;
				}
			}
		}

		$our_datestamp	= gmmktime( 12, 0, 0, gmstrftime( '%m', $in_week ), gmstrftime( '%d', $in_week ), gmstrftime( '%Y', $in_week ) );

		$this->chosen_date	= array(
									'month'			=> gmstrftime( '%m', $in_week ),
									'year'			=> gmstrftime( '%Y', $in_week ),
									'month_name'	=> gmstrftime( '%B', $in_week )
									);

		//-----------------------------------------
		// Get last/next month info
		//-----------------------------------------
		
		$_previous	= $this->calendarGetPreviousMonth( $this->chosen_date['month'], $this->chosen_date['year'] );
		$_next		= $this->calendarGetNextMonth( $this->chosen_date['month'], $this->chosen_date['year'] );

		//-----------------------------------------
		// Get events
		//-----------------------------------------
		
		$this->calendarGetEventsSQL( $this->chosen_date['month'], $this->chosen_date['year'] );

		//-----------------------------------------
		// Start looping and outputting
		//-----------------------------------------

		for( $i = 0 ; $i <= 6 ; $i++ )
		{
			//-----------------------------------------
			// Init
			//-----------------------------------------
			
			$year				= gmstrftime( '%Y', $our_datestamp );
			$month				= gmstrftime( '%m', $our_datestamp );
			$day				= gmstrftime( '%d', $our_datestamp );
			$events				= $this->calendarGetDayEvents( $month, $day, $year );
			$_hasQueued			= 0;
			$this_day_events	= "";

			//-----------------------------------------
			// Show month bar?
			//-----------------------------------------
			
			if ( $last_month_id != $month )
			{
				$last_month_id	= $month;
				$output			.= $this->registry->output->getTemplate('calendar')->cal_week_monthbar( gmstrftime( '%B', $our_datestamp ), $year );

				//-----------------------------------------
				// Get birthdays
				//-----------------------------------------
				
				if ( $this->settings['show_bday_calendar'] )
				{
					$birthdays	= $this->_getBirthdaysForMonth( $month );
				}
			}

			//-----------------------------------------
			// Have events?
			//-----------------------------------------

			if ( is_array( $events ) AND count( $events ) )
			{
				foreach( $events as $event )
				{
					if ( empty($seenIds[ $month . '-' . $day . '-' . $year ][ $event['event_id'] ]) )
					{
						$event	= $this->calendarMakeEventHTML( $event, true );
						$event	= $event['event'];

						if ( $event['recurring'] )
						{
							$this_day_events .= $this->registry->output->getTemplate('calendar')->cal_events_wrap_recurring( $event, 'week' );
						}
						else if ( $event['single'] )
						{
							$this_day_events .= $this->registry->output->getTemplate('calendar')->cal_events_wrap( $event, 'week' );
						}
						else
						{
							$this_day_events .= $this->registry->output->getTemplate('calendar')->cal_events_wrap_range( $event, 'week' );
						}

						$seenIds[ $month . '-' . $day . '-' . $year ][ $event['event_id'] ]	= true;
					}

					//-----------------------------------------
					// Set queued flag
					//-----------------------------------------
					
					if ( !$event['event_approved'] AND $this->memberData['g_is_supmod'] )
					{
						$_hasQueued = 1;
					}
				}
			}

			//-----------------------------------------
			// Have birthdays?
			//-----------------------------------------
			
			if( $this->calendar['cal_bday_limit'] )
			{
				if( isset( $birthdays[ $day ] ) and count( $birthdays[ $day ] ) > 0 )
				{
					$no_bdays	= count( $birthdays[ $day ] );

					if ( $no_bdays <= $this->calendar['cal_bday_limit'] )
					{
						foreach( $birthdays[ $day ] as $user )
						{
							$this_day_events .= $this->registry->output->getTemplate('calendar')->cal_week_events_wrap(
																														array( 'cal_id' => $this->calendar['cal_id'], 'y' => $year, 'm' => $month, 'd' => $day, 'seotitle' => $this->calendar['cal_title_seo'] ),
																														$user['members_display_name'] . $this->lang->words['bd_birthday']
																														);
						}

					}
					else
					{
						$this_day_events .= $this->registry->output->getTemplate('calendar')->cal_week_events_wrap(
																													array( 'cal_id' => $this->calendar['cal_id'], 'y' => $year, 'm' => $month, 'd' => $day, 'seotitle' => $this->calendar['cal_title_seo'] ),
																													sprintf( $this->lang->words['entry_birthdays'], $no_bdays )
																													);
					}
				}
			}

			$this_day_events	= $this_day_events ? $this_day_events : '&nbsp;';

			$output	.= $this->registry->output->getTemplate('calendar')->cal_week_dayentry( gmstrftime( '%a', $our_datestamp ), $day, gmstrftime( '%B', gmmktime( 0, 0, 0, $month, 15, $year ) ), $month, $year, $this_day_events, $_hasQueued );

			$our_datestamp	+= 86400;
		}

		//-----------------------------------------
		// Output
		//-----------------------------------------

		$this->registry->output->addNavigation( $this->chosen_date['month_name'] . " " . $this->chosen_date['year'], "app=calendar&amp;module=calendar&amp;section=view&amp;cal_id={$this->calendar['cal_id']}&amp;m={$this->chosen_date['month']}&amp;y={$this->chosen_date['year']}", $this->calendar['cal_title_seo'], 'cal_month' );
		$this->registry->output->addNavigation( "{$this->lang->words['week_beginning']} " . gmstrftime( '%B %d, %Y', $in_week ) );
		$this->registry->output->addMetaTag( 'keywords', $this->chosen_date['month_name'] . ' events event calendar week', TRUE );
		$this->registry->output->addMetaTag( 'description', str_replace( "\n", " ", str_replace( "\r", "", sprintf( $this->lang->words['week_meta_description'], gmstrftime( '%U', $in_week ), $this->chosen_date['year'] ) ) ), FALSE, 155 );
		$this->registry->output->addToDocumentHead( 'raw', "<link rel='up' href='" . $this->registry->output->buildSEOUrl( 'app=calendar&amp;module=calendar&amp;section=view&amp;cal_id=' . $this->calendar['cal_id'], 'publicNoSession', $this->calendar['cal_title_seo'], 'cal_calendar' ) . "' />" );
		$this->registry->getClass('output')->addCanonicalTag( 'app=calendar&amp;module=calendar&amp;section=view&amp;cal_id=' . $this->calendar['cal_id'] . '&amp;do=showweek&amp;week=' . $in_week, $this->calendar['cal_title_seo'], 'cal_week' );
		
		$this->page_title	= "{$this->lang->words['week_beginning']} " . gmstrftime( '%B %d, %Y', $in_week );
		
		$this->output .= $this->registry->output->getTemplate('calendar')->calendarWeekView( array(
																									'calendars'		=> $this->functions->getCalendars(),
																									'calendar'		=> $this->calendar,
																									'prev_minical'	=> $this->getMiniCalendar( $_previous['month_id'], $_previous['year_id'] ),
																									'cur_minical'	=> $this->getMiniCalendar( $this->chosen_date['month'], $this->chosen_date['year'] ),
																									'next_minical'	=> $this->getMiniCalendar( $_next['month_id'], $_next['year_id'] ),
																									'month_box'		=> $this->functions->returnMonthDropdown( $this->chosen_date['month'] ),
																									'year_box'		=> $this->functions->returnYearDropdown( $this->chosen_date['year'] ),
																									'events'		=> $output,
																									'chosen_date'	=> $this->chosen_date,
																									'prev_month'	=> $_previous,
																									'next_month'	=> $_next,
																									'start_date'	=> gmstrftime( '%B %d, %Y', $in_week ),
																									'prev_week'		=> $in_week - 604800,
																									'next_week'		=> $in_week + 604800,
																									'this_week'		=> $in_week,
																									'_can_add'		=> ( $this->memberData['member_id'] AND $this->registry->permissions->check( 'start', $this->calendar ) ),
																							)		);
	}
}