<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board vVERSION_NUMBER
 * Calendar cache functions
 * Last Updated: $Date: 2012-03-29 15:02:31 -0400 (Thu, 29 Mar 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Calendar
 * @link		http://www.invisionpower.com
 * @since		5th January 2005
 * @version		$Revision: 10518 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class calendar_cache
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
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
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
	}
	
	/**
	 * Rebuild calendar RSS cache
	 *
	 * @param	mixed	[$calendar_id]	"all", or a specific calendar ID
	 * @return	string	RSS document
	 */
	public function rebuildCalendarRSSCache( $calendar_id='all' )
	{
		//-----------------------------------------
		// Ensure calendar is installed
		//-----------------------------------------
		
		if( !$this->DB->checkForTable('cal_calendars') )
		{
			return '';
		}

		if( ! IPSLib::appIsInstalled( 'calendar' ) )
		{
			$this->cache->setCache( 'rss_calendar', array(), array( 'donow' => 1, 'array' => 1 ) );
			
			return '';
		}

		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$seenids	= array();
		$cache		= array();
		
		//--------------------------------------------
		// Get classes
		//--------------------------------------------
		
		$classToLoad	= IPSLib::loadActionOverloader( IPSLib::getAppDir( 'calendar' ) . '/modules_public/calendar/view.php', 'public_calendar_calendar_view' );
		$calendar		= new $classToLoad( $this->registry );
		$calendar->makeRegistryShortcuts( $this->registry );
		
		if( !$calendar->initCalendar( true ) )
		{
			return '';
		}


		$classToLoad			= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classRss.php', 'classRss' );
		$class_rss				= new $classToLoad();
		$class_rss->doc_type	= IPS_DOC_CHAR_SET;

		//-----------------------------------------
		// Get calendars that support RSS and loop
		//-----------------------------------------
		
		$this->DB->build( array(
								'select'	=> 'c.*', 
								'from'		=> array( 'cal_calendars' => 'c' ), 
								'where'		=> 'c.cal_rss_export_days > 0 AND c.cal_rss_export_max > 0 AND c.cal_rss_export=1',
								'add_join'	=> array(
													array(
														'select'	=> 'p.*',
														'from'		=> array( 'permission_index' => 'p' ),
														'where'		=> "p.perm_type='calendar' AND perm_type_id=c.cal_id",
														'type'		=> 'left'
														)
													)	
						 )		);
		$outer	= $this->DB->execute();

		while( $row = $this->DB->fetch( $outer ) )
		{
			$row['cal_rss_export_max']	= $row['cal_rss_export_max'] ? $row['cal_rss_export_max'] : 10;

			if ( $row['cal_rss_export'] )
			{
				$cache[]	= array( 'url' => $this->settings['board_url'] . '/index.php?app=core&amp;module=global&amp;section=rss&amp;type=calendar&amp;id=' . $row['cal_id'], 'title' => $row['cal_title'] );
			}
			
			//-----------------------------------------
			// Are we including events from this calendar?
			//-----------------------------------------
			
			if( $calendar_id == $row['cal_id'] OR $calendar_id == 'all' )
			{
				//-----------------------------------------
				// Start channel
				//-----------------------------------------
				
				$channel_id	= $class_rss->createNewChannel( array(
																'title'			=> $row['cal_title'],
																'link'			=> $this->registry->output->buildSEOUrl( 'app=calendar&amp;module=calendar&amp;section=view&amp;cal_id=' . $row['cal_id'], 'publicNoSession', $row['cal_title_seo'], 'cal_calendar' ),
																'pubDate'		=> $class_rss->formatDate( time() ),
																'ttl'			=> $row['cal_rss_update'] * 60,
																'description'	=> $row['cal_title']
														)		);
				
				//-----------------------------------------
				// Verify if guests can view
				//-----------------------------------------
				
				if( !$this->registry->permissions->check( 'view', $row, explode( ',', IPSText::cleanPermString( $this->caches['group_cache'][ $this->settings['guest_group'] ]['g_perm_id'] ) ) ) )
				{
					continue;
				}

				//-----------------------------------------
				// Sort out date stuff
				//-----------------------------------------
				
				$row['cal_rss_export_days']	= intval($row['cal_rss_export_days']) + 1;
				$startTime					= gmmktime( 0,0,0, gmstrftime( '%m' ), 1 );
				$endTime					= gmmktime( 0,0,0, gmstrftime( '%m' ), gmstrftime( '%d' ) + $row['cal_rss_export_days'] );
				$nowTime					= gmmktime( 0,0,0, gmstrftime( '%m' ), gmstrftime( '%d' ) - 1 );
				$items						= 0;
				
				list( $month, $day, $year )	= explode( '-', gmstrftime( '%m-%d-%Y' ) );
				
				//-----------------------------------------
				// Get the events
				//-----------------------------------------
				
				$calendar->calendarGetEventsSQL( $month, $year, array( 'timenow' => $startTime, 'timethen' => $endTime, 'cal_id' => $row['cal_id'] ) );
				
				//--------------------------------------------
				// Loop through events and cache
				//--------------------------------------------

				for( $i = 0 ; $i <= $row['cal_rss_export_days'] ; $i++ )
				{
					list( $_month, $_day, $_year )	= explode( '-', gmstrftime( '%m-%d-%Y', $nowTime ) );
		
					$eventcache	= $calendar->calendarGetDayEvents( $_month, $_day, $_year );
		
					foreach( $eventcache as $event )
					{
						//-----------------------------------------
						// Hit our max?
						//-----------------------------------------
						
						if ( $row['cal_rss_export_max'] <= $items )
						{
							break;
						}

						//-----------------------------------------
						// Can guests access?
						//-----------------------------------------
						
						$permissionGroups	= explode( ',', IPSText::cleanPermString( $event['event_perms'] ) );
						
						if ( !$event['event_approved'] OR $event['event_private'] OR ( $event['event_perms'] != '*' AND !IPSMember::isInGroup( array( 'member_group_id' => $this->settings['guest_group'] ), $permissionGroups ) ) )
						{
							continue;
						}

						//-----------------------------------------
						// Check the event and add it
						//-----------------------------------------
						
						if ( !in_array( $event['event_id'], $seenids ) )
						{ 
							if ( !$event['event_recurring'] OR $calendar->checkRecurring( $event, $_month, $_day, $_year ) )
							{
								$event	= $calendar->calendarMakeEventHTML( $event, true );

								if ( $event['event']['recurring'] )
								{
									$event['event']['event_content']	= $this->registry->output->getTemplate('calendar')->calendar_rss_recurring( $event['event'] );
								}
								else if ( $event['event']['single'] )
								{
									$event['event']['event_content']	= $this->registry->output->getTemplate('calendar')->calendar_rss_single( $event['event'] );
								}
								else
								{
									$event['event']['event_content']	= $this->registry->output->getTemplate('calendar')->calendar_rss_range( $event['event'] );
								}
								
								//-----------------------------------------
								// Re-adjust the event time for the RSS feed
								// to represent the next occurrence of recurring
								// and ranged events
								//-----------------------------------------
								
								if( $event['event']['event_recurring'] )
								{
									$_incrementor	= 0;
									
									switch( $event['event']['event_recurring'] )
									{
										case 1:
											$_incrementor	= 604800;
										break;
										
										case 2:
											$_incrementor	= 2592000;
										break;
										
										case 3:
											$_incrementor	= 31536000;
										break;
									}
									
									if( $event['event']['_start_time'] < gmmktime( 0 ) )
									{
										while( $event['event']['_start_time'] < gmmktime( 0 ) )
										{
											$event['event']['_start_time'] += $_incrementor;
										}
									}
								}
								
								//-----------------------------------------
								// Ranged events
								//-----------------------------------------
								
								else if( $event['event']['_end_time'] )
								{
									if( $event['event']['_start_time'] < gmmktime( 0 ) AND $event['event']['_end_time'] > gmmktime( 0 ) )
									{
										while( $event['event']['_start_time'] < gmmktime( 0 ) )
										{
											$event['event']['_start_time'] += 86400;
										}
									}
								}								

								//-----------------------------------------
								// Add to the channel
								//-----------------------------------------

								$class_rss->addItemToChannel( $channel_id, array(
																				'title'			=> $event['event']['event_title'],
																				'link'			=> $this->registry->output->buildSEOUrl( 'app=calendar&amp;module=calendar&amp;section=view&amp;do=showevent&amp;event_id=' . $event['event']['event_id'], 'publicNoSession', $event['event']['event_title_seo'], 'cal_event' ),
																				'description'	=> $event['event']['event_content'],
																				'pubDate'		=> $class_rss->formatDate( $event['event']['_start_time'] ),
																				'guid'			=> $event['event']['event_id']
														  )                    );
											
							}
							
							//-----------------------------------------
							// Increment counters and cache event
							//-----------------------------------------
							
							$seenids[ $event['event']['event_id'] ]	= $event['event']['event_id'];
							$items++;
						}
					}
					
					$nowTime	+= 86400;
				}

				//-----------------------------------------
				// Create RSS document
				//-----------------------------------------
				
				$class_rss->createRssDocument();
			
				//-----------------------------------------
				// Update calendar RSS cache
				//-----------------------------------------
				
				$this->DB->update( 'cal_calendars', array( 'cal_rss_update_last' => time(), 'cal_rss_cache' => $class_rss->rss_document ), 'cal_id=' . $row['cal_id'] );
			}
		}
		
		//-----------------------------------------
		// Update cache and return RSS document
		//-----------------------------------------
		
		$this->cache->setCache( 'rss_calendar', $cache, array( 'donow' => 1, 'array' => 1 ) );
		
		return $class_rss->rss_document;
	}
	
	/**
	 * Rebuild upcoming events cache
	 *
	 * @return	@e void
	 */
	public function rebuildCalendarEventsCache()
	{
		//-----------------------------------------
		// Ensure calendar is installed
		//-----------------------------------------
		
		if( !$this->DB->checkForTable('cal_calendars') )
		{
			return;
		}
		
		if( ! IPSLib::appIsInstalled( 'calendar' ) )
		{
			$this->cache->setCache( 'calendar_events', array(), array( 'array' => 1 ) );
			$this->cache->setCache( 'birthdays', array(), array( 'array' => 1 ) );
			
			return;
		}

		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$this->settings['calendar_limit']	= ( intval( $this->settings['calendar_limit'] ) < 2 ? 1 : intval( $this->settings['calendar_limit'] ) ) + 1;
		
		$birthdays	= array();
		$events		= array();
		$calendars	= $this->cache->getCache('calendars');
		$seenids	= array();

		//--------------------------------------------
		// Set time stuff
		//--------------------------------------------
	
		$startTime	= gmmktime( 0,0,0, gmstrftime( '%m' ), 1 );
		$endTime	= gmmktime( 0,0,0, gmstrftime( '%m' ), gmstrftime( '%d' ) + $this->settings['calendar_limit'] );
		$nowTime	= gmmktime( 0,0,0, gmstrftime( '%m' ), gmstrftime( '%d' ) - 1 );
		
		list( $month, $day, $year )					= explode( '-', gmstrftime( '%m-%d-%Y' ) );
		list( $last_month, $last_day, $last_year )	= explode( '-', gmstrftime( '%m-%d-%Y', $nowTime ) );
		list( $next_month, $next_day, $next_year )	= explode( '-', gmstrftime( '%m-%d-%Y', gmmktime( 0,0,0, gmstrftime( '%m' ), gmstrftime( '%d' ) + 1 ) ) );

		//--------------------------------------------
		// Get classes
		//--------------------------------------------
		
		$classToLoad	= IPSLib::loadActionOverloader( IPSLib::getAppDir( 'calendar' ) . '/modules_public/calendar/view.php', 'public_calendar_calendar_view' );
		$calendar		= new $classToLoad( $this->registry );
		$calendar->makeRegistryShortcuts( $this->registry );
		
		if( !$calendar->initCalendar( true ) )
		{
			$this->cache->setCache( 'calendar_events' , array(), array( 'array' => 1 ) );
			$this->cache->setCache( 'birthdays', array(), array( 'array' => 1 ) );
			return;
		}
				
		//--------------------------------------------
		// Load the events
		//--------------------------------------------

		$calendar->calendarGetEventsSQL( $month, $year, array( 'timenow' => $startTime, 'timethen' => $endTime, 'minimal' => true ) );

		//--------------------------------------------
		// Loop through events and cache
		//--------------------------------------------
		
		for( $i = 0 ; $i <= $this->settings['calendar_limit'] ; $i++ )
		{
			list( $_month, $_day, $_year )	= explode( '-', gmstrftime( '%m-%d-%Y', $nowTime ) );

			$eventcache	= $calendar->calendarGetDayEvents( $_month, $_day, $_year );

			foreach( $eventcache as $event )
			{
				if ( $event['event_approved'] AND !in_array( $event['event_id'], $seenids ) )
				{ 
					if ( !$event['event_recurring'] OR $calendar->checkRecurring( $event, $_month, $_day, $_year ) )
					{
						unset( $event['event_content'], $event['event_smilies'] );
						
						$event['perm_view']				= $calendars[ $event['event_calendar_id'] ]['perm_view'];
						$events[ $event['event_id'] ]	= $event;
					}
					
					$seenids[ $event['event_id'] ]		= $event['event_id'];
				}
			}
			
			$nowTime	+= 86400;
		}

		//-----------------------------------------
		// Grab birthdays
		//-----------------------------------------
		
		$append_string	= "";
		
        if( !gmdate("L") )
        {
	        if( $month == 2 AND $day > 26 )
	        {
		        $append_string	= " OR ( bday_month=2 AND bday_day=29 )";
	        }
		}

		$this->DB->build( array( 
								'select'	=> 'member_id, members_seo_name, members_display_name, member_group_id, bday_day, bday_month, bday_year',
								'from'		=> 'members',
								'where'		=> "member_banned=0 AND ( !" . IPSBWOptions::sql( 'bw_is_spammer', 'members_bitoptions', 'members', 'global', 'has' ) . ")
												AND ( ( bday_day={$last_day} AND bday_month={$last_month} )
												OR ( bday_day={$day} AND bday_month={$month} )
												OR ( bday_day={$next_day} AND bday_month={$next_month} ) {$append_string} )"
							)	);
		$this->DB->execute();

		while( $r = $this->DB->fetch() )
		{
			$birthdays[ $r['member_id'] ]	= $r;
		}

		//--------------------------------------------
		// Update calendar array
		//--------------------------------------------

		$this->cache->setCache( 'calendar_events' , $events, array( 'array' => 1 ) );
		$this->cache->setCache( 'birthdays', $birthdays, array( 'array' => 1 ) );
	}
}