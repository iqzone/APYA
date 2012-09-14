<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board vVERSION_NUMBER
 * Calendar hook function gateway
 * Last Updated: $Date: 2012-03-19 15:00:59 -0400 (Mon, 19 Mar 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Calendar
 * @link		http://www.invisionpower.com
 * @since		5th January 2005
 * @version		$Revision: 10446 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class app_calendar_classes_hooks
{
	/**#@+
	 * Registry objects
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	/**#@-*/

	/**
	 * Constructor
	 *
	 * @param	object		Registry object
	 * @param	bool		Ignore permission check
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $ignorePermissions=false )
	{
		/* Make object */
		$this->registry = $registry;
		$this->DB       = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang     = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache    = $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		
		$this->lang->loadLanguageFile( array( 'public_calendar' ), 'calendar' );
	}
	
	/**
	 * Retrieve upcoming birthdays
	 *
	 * @return	@e string
	 * 
	 * @todo	[Future] Optimize the function to load the member data ONLY for 5 members, no need to load all of them if we have 300+ [for the total count we can just run a count() on the cache array]
	 *	We can't just run a count on the cache array, because it contains birthday data for 3 days (when task runs, it contains yesterday, today and tomorrow)
	 */
	public function getUpcomingBirthdays()
	{
		if ( !$this->settings['show_birthdays'] OR !IPSLib::appIsInstalled( 'calendar' ) )
		{
			return '';
		}

		$a				= explode( ',', gmdate( 'Y,n,j,G,i,s', time() + $this->registry->getClass('class_localization')->getTimeOffset() ) );
		$day			= $a[2];
		$month			= $a[1];
		$year			= $a[0];
		
		$birthstring	= "";
		$count			= 0;
		$lang 			= '';
		$users			= array();
		$fetch			= 5;

		if ( is_array($this->caches['birthdays']) AND count( $this->caches['birthdays'] ) )
		{
			$_users	= IPSMember::load( array_keys( $this->caches['birthdays'] ) );

			foreach( $this->caches['birthdays'] as $u )
			{
				/* Age */
				$pyear = 0;
				
				$u	= array_merge( $_users[ $u['member_id'] ], $u );
				$u	= IPSMember::buildDisplayData( $u );
				
				if( $u['bday_year'] && $u['bday_year'] > 0 )
				{
					$pyear = $year - $u['bday_year'];
				}
				
				$u['_pyear']	= $pyear;
			
				if ( $u['bday_day'] == $day and $u['bday_month'] == $month )
				{
					if( $count < $fetch )
					{
						$users[] = $u;
					}

					$count++;
				}
				else if( $day == 28 && $month == 2 && !date("L") )
				{
					if ( $u['bday_day'] == "29" and $u['bday_month'] == $month )
					{
						if( $count < $fetch )
						{
							$users[] = $u;
						}

						$count++;
					}
				}
			}
		}

		//-----------------------------------------
		// Get calendar info, but only if we need it
		//-----------------------------------------
		
		$data	= array();
		
		if( $count >= count($users) )
		{
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'calendar' ) . "/sources/functions.php", 'app_calendar_classes_functions', 'calendar' );
			$functions		= new $classToLoad( $this->registry );

			$calendars		= $functions->getCalendars();
			
			foreach( $calendars as $calendar )
			{
				/* We need to grab first calendar that allows birthdays */
				if( !$calendar['cal_bday_limit'] )
				{
					continue;
				}

				$data			= array(
										'id'	=> $calendar['cal_id'],
										'title'	=> $calendar['cal_title_seo'],
										'year'	=> $year,
										'month'	=> $month,
										'day'	=> $day,
										);

				break;
			}
		}
		
		if( $count >= count($users) AND !count($data) )
		{
			return '';
		}

		//-----------------------------------------
		// Spin and print...
		//-----------------------------------------

		return $this->registry->output->getTemplate('calendar')->boardIndexBirthdays( $count, $users, $data );
	}
	
	/**
	 * Retrieve upcoming events
	 *
	 * @return	string
	 */
	public function getUpcomingEvents()
	{
		//-----------------------------------------
		// Make sure calendar app is installed
		//-----------------------------------------
		
		if( !$this->settings['show_calendar'] OR !IPSLib::appIsInstalled( 'calendar' ) )
		{
			return '';
		}

		//-----------------------------------------
		// Get current date data
		//-----------------------------------------
		
		$a		= explode( ',', gmdate( 'Y,n,j,G,i,s', time() + $this->registry->class_localization->getTimeOffset() ) );
		$day	= $a[2];
		$month	= $a[1];
		$year	= $a[0];
		
		//-----------------------------------------
		// Check calendar limit
		//-----------------------------------------
		
		$this->settings['calendar_limit'] = intval( $this->settings['calendar_limit'] ) < 2 ? 1 : intval( $this->settings['calendar_limit'] );
		
		$our_unix		= gmmktime( 0, 0, 0, $month, $day, $year);
		$max_date		= $our_unix + ($this->settings['calendar_limit'] * 86400);
		$events			= array();
		$show_events	= array();

		//-----------------------------------------
		// Loop over the cache
		//-----------------------------------------

		if( is_array( $this->caches['calendar_events'] ) AND count( $this->caches['calendar_events'] ) )
		{
			foreach( $this->caches['calendar_events'] as $u )
			{
				//-----------------------------------------
				// Private?
				//-----------------------------------------
				
				if ( $u['event_private'] == 1 and $this->memberData['member_id'] != $u['event_member_id'] )
				{
					continue;
				}
				
				//-----------------------------------------
				// Got perms?
				//-----------------------------------------
				
				if( $u['event_perms'] != '*' )
				{
					$permissionGroups	= explode( ',', IPSText::cleanPermString( $u['event_perms'] ) );
					
					if( !IPSMember::isInGroup( $this->memberData, $permissionGroups ) )
					{
						continue;
					}
				}

				//-----------------------------------------
				// Got calendar perms?
				//-----------------------------------------

				if( !$this->registry->permissions->check( 'view', $u ) )
				{
					continue;
				}
				
				//-----------------------------------------
				// Times and dates
				//-----------------------------------------
				
				$u['isoDate']       = gmdate( 'c', strtotime( $u['event_start_date'] ) );
				$u['_start_time']	= strtotime( $u['event_start_date'] );
				$u['_end_time']		= ( $u['event_end_date'] AND $u['event_end_date'] != '0000-00-00 00:00:00' ) ? strtotime( $u['event_end_date'] ) : 0;
			
				if( !$u['event_all_day'] )
				{
					if( $this->memberData['member_id'] AND $this->memberData['time_offset'] )
					{
						$u['_start_time']	= $u['_start_time'] + ( $this->memberData['time_offset'] * 3600 );
					}
					else if( !$this->memberData['member_id'] AND $this->settings['time_offset'] )
					{
						$u['_start_time']	= $u['_start_time'] + ( $this->settings['time_offset'] * 3600 );
					}

					if( $this->memberData['member_id'] AND $this->memberData['time_offset'] )
					{
						$u['_end_time']		= $u['_end_time'] ? ( $u['_end_time'] + ( $this->memberData['time_offset'] * 3600 ) ) : 0;
					}
					else if( !$this->memberData['member_id'] AND $this->settings['time_offset'] )
					{
						$u['_end_time']		= $u['_end_time'] ? ( $u['_end_time'] + ( $this->settings['time_offset'] * 3600 ) ) : 0;
					}
				}

				//-----------------------------------------
				// In range?
				//-----------------------------------------
			
				if ( !$u['event_recurring'] AND ( ( $u['_end_time'] >= $our_unix AND $u['_start_time'] <= $max_date )
					OR ( !$u['_end_time'] AND $u['_start_time'] >= $our_unix AND $u['_start_time'] <= $max_date ) ) )
				{
					if( $u['_end_time'] )
					{
						if( $u['_start_time'] < gmmktime( 0 ) )
						{
							$u['_start_time']	= gmmktime( 0 );
						}
					}

					$events[ str_pad( $u['_start_time'].$u['event_id'], 15, "0" ) ]	= $u;
				}
				elseif( $u['event_recurring'] > 0 )
				{
					$cust_range_s	= $u['_start_time'];

					while( $cust_range_s <= $u['_end_time'] )
					{
						if( $cust_range_s >= $our_unix AND $cust_range_s <= $max_date )
						{
							//-----------------------------------------
							// Special case for "monthly" to ensure it lands on the same day
							//-----------------------------------------
							
							if ( $u['event_recurring'] != 1 )
							{
								$u['_start_time']	= gmmktime( 1, 1, 1, gmdate( 'n', $cust_range_s ), gmdate( 'j', $u['_start_time'] ), gmdate( 'Y', $cust_range_s ) );
							}
							else
							{
								$u['_start_time']	= $cust_range_s;
							}

							$events[ str_pad( $u['_start_time'] . $u['event_id'], 15, "0" ) ] = $u;
						}

						if( $u['event_recurring'] == 1 )
						{
							$cust_range_s	+= 604800;
						}
						elseif ( $u['event_recurring'] == 2 )
						{
							$cust_range_s	+= 2628000;
						}
						else
						{
							$cust_range_s	+= 31536000;
						}
					}								
				}
			}
		}
		
		//-----------------------------------------
		// Sort and format
		//-----------------------------------------
		
		ksort($events);
		
		foreach( $events as $event )
		{
			//-----------------------------------------
			// Recurring?
			//-----------------------------------------

			$c_time	= gmstrftime( '%x', $event['_start_time'] );
			$url	= $this->registry->output->buildSEOUrl( "app=calendar&amp;module=calendar&amp;section=view&amp;do=showevent&amp;event_id={$event['event_id']}", 'public', $event['event_title_seo'], 'cal_event' );
			
			$show_events[]	= array( 'url' => $url, 'isoDate' => $event['isoDate'], 'date' => $c_time, 'title' => $event['event_title'], 'calendar' => $this->caches['calendars'][ $event['event_calendar_id'] ] );
		}
		
		//-----------------------------------------
		// Send output to template and return HTML
		//-----------------------------------------
		
		$this->lang->words['calender_f_title']	= sprintf( $this->lang->words['calender_f_title'], $this->settings['calendar_limit'] );
		
		if ( count($show_events) > 0 )
		{
			$event_string	= $show_events;
		}
		else
		{
			if ( ! $this->settings['autohide_calendar'] )
			{
				$event_string	= $this->lang->words['no_calendar_events'];
			}
		}
		
		return $this->registry->output->getTemplate('calendar')->boardIndexCalEvents( $event_string );
	}

}