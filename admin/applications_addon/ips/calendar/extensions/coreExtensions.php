<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board vVERSION_NUMBER
 * Calendar core extensions
 * Last Updated: $LastChangedDate: 2012-03-15 17:19:52 -0400 (Thu, 15 Mar 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Calendar
 * @link		http://www.invisionpower.com
 * @since		27th January 2004
 * @version		$Rev: 10431 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

$_PERM_CONFIG = array( 'Calendar' );

class calendarPermMappingCalendar
{
	/**
	 * Mapping of keys to columns
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $mapping = array(
								'view'		=> 'perm_view',
								'start'		=> 'perm_2',
								'nomod'		=> 'perm_3',
								'rate'		=> 'perm_4',
								'comment'	=> 'perm_5',
								'askrsvp'	=> 'perm_6',
								'rsvp'		=> 'perm_7',
							);

	/**
	 * Mapping of keys to names
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $perm_names = array(
								'view'		=> 'Show Calendar',
								'start'		=> 'Create Events',
								'nomod'		=> 'Bypass Moderation',
								'rate'		=> 'Rate Events',
								'comment'	=> 'Comment Events',
								'askrsvp'	=> 'Request RSVP',
								'rsvp'		=> 'RSVP Event',
							);

	/**
	 * Mapping of keys to background colors for the form
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $perm_colors = array(
								'view'		=> '#fff0f2',
								'start'		=> '#effff6',
								'nomod'		=> '#edfaff',
								'rate'		=> '#f0f1ff',
								'comment'	=> '#fffaee',
								'askrsvp'	=> '#ffeef9',
								'rsvp'		=> '#fff5ec',
							);

	/**
	 * Method to pull the key/column mapping
	 *
	 * @access	public
	 * @return	array
	 */
	public function getMapping()
	{
		return $this->mapping;
	}

	/**
	 * Method to pull the key/name mapping
	 *
	 * @access	public
	 * @return	array
	 */
	public function getPermNames()
	{
		return $this->perm_names;
	}

	/**
	 * Method to pull the key/color mapping
	 *
	 * @access	public
	 * @return	array
	 */
	public function getPermColors()
	{
		return $this->perm_colors;
	}

	/**
	 * Retrieve the items that support permission mapping
	 *
	 * @access	public
	 * @return	array
	 */
	public function getPermItems()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$rows	= array();
		
		//-----------------------------------------
		// Get calendars and loop
		//-----------------------------------------
		
		ipsRegistry::DB()->build( array(
								'select'	=> 'c.*', 
								'from'		=> array( 'cal_calendars' => 'c' ), 
								'add_join'	=> array(
													array(
														'select'	=> 'p.*',
														'from'		=> array( 'permission_index' => 'p' ),
														'where'		=> "p.perm_type='calendar' AND perm_type_id=c.cal_id",
														'type'		=> 'left'
														)
													)	
						 )		);
		$outer	= ipsRegistry::DB()->execute();

		while( $row = ipsRegistry::DB()->fetch( $outer ) )
		{
			$rows[ $row['cal_id'] ]	= array(
										'title'		=> $row['cal_title'],
										'perm_view'	=> $row['perm_view'],
										'perm_2'	=> $row['perm_2'],
										'perm_3'	=> $row['perm_3'],
										'perm_4'	=> $row['perm_4'],
										'perm_5'	=> $row['perm_5'],
										'perm_6'	=> $row['perm_6'],
										'perm_7'	=> $row['perm_7'],										
									);
		}

		return $rows;
	}
}

/**
 * <pre>
 * Invision Power Services
 * IP.Board vVERSION_NUMBER
 * Library: Handle public session data
 * Last Updated: $Date: 2012-03-15 17:19:52 -0400 (Thu, 15 Mar 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Calendar
 * @link		http://www.invisionpower.com
 * @since		12th March 2002
 * @version		$Revision: 10431 $
 *
 */

class publicSessions__calendar
{
	/**
	* Return session variables for this application
	*
	* current_appcomponent, current_module and current_section are automatically
	* stored. This function allows you to add specific variables in.
	*
	* @access	public
	* @author	Matt Mecham
	* @return   array
	*/
	public function getSessionVariables()
	{
		$array = array( 'location_1_type'	=> '',
						'location_1_id'		=> 0,
						'location_2_type'	=> '',
						'location_2_id'		=> 0 );

		//-----------------------------------------
		// Viewing event?
		//-----------------------------------------
		
		if ( ipsRegistry::$request['do'] == 'showevent' )
		{
			$array = array( 
							'location_1_type'	=> 'event',
							'location_1_id'		=> intval(ipsRegistry::$request['event_id']),
						);
		}
		else if( ipsRegistry::$request['cal_id'] )
		{
			$array = array( 
							'location_1_type'	=> 'calendar',
							'location_1_id'		=> intval(ipsRegistry::$request['cal_id']),
						);
		}

		return $array;
	}

	/**
	* Parse/format the online list data for the records
	*
	* @access	public
	* @author	Brandon Farber
	* @param	array 			Online list rows to check against
	* @return   array 			Online list rows parsed
	*/
	public function parseOnlineEntries( $rows )
	{
		if( !is_array($rows) OR !count($rows) )
		{
			return $rows;
		}
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_calendar' ), 'calendar' );

		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$cals_raw	= array();
		$cals		= array();
		$events_raw	= array();
		$events		= array();
		$final		= array();
		
		//-----------------------------------------
		// Extract the data
		//-----------------------------------------

		foreach( $rows as $row )
		{
			if( $row['current_appcomponent'] != 'calendar' OR !$row['current_module'] )
			{
				continue;
			}

			if( $row['location_1_type'] == 'event' )
			{
				$events_raw[ $row['location_1_id'] ]	= $row['location_1_id'];
			}
			else if( $row['location_1_type'] == 'calendar' )
			{
				$cals_raw[ $row['location_1_id'] ]		= $row['location_1_id'];
			}
		}
		
		//-----------------------------------------
		// Get calendars
		//-----------------------------------------
		
		$calendars	= ipsRegistry::cache()->getCache('calendars');
		
		if( count($cals_raw) )
		{
			foreach( $calendars as $cid => $calendar )
			{
				if( isset($cals_raw[ $cid ]) )
				{
					if( ipsRegistry::getClass('permissions')->check( 'view', $calendar ) )
					{
						$cals[ $cid ]	= $calendar;
					}
				}
			}
		}

		//-----------------------------------------
		// And events
		//-----------------------------------------
		
		if( count($events_raw) )
		{
			ipsRegistry::DB()->build( array( 'select' => 'event_id, event_title, event_title_seo, event_calendar_id, event_approved, event_private, event_perms', 'from' => 'cal_events', 'where' => 'event_id IN(' . implode( ',', $events_raw ) . ')' ) );
			$tr = ipsRegistry::DB()->execute();
			
			while( $r = ipsRegistry::DB()->fetch($tr) )
			{
				if( ipsRegistry::getClass('permissions')->check( 'view', $calendars[ $r['event_calendar_id'] ] ) )
				{
					if ( $r['event_private'] AND $this->memberData['member_id'] != $r['event_member_id'] )
					{
						continue;
					}
			
					if( !$this->memberData['g_is_supmod'] AND !$r['event_approved'] )
					{
						continue;
					}
			
					if( $r['event_perms'] != '*' )
					{
						$permissionGroups	= explode( ',', IPSText::cleanPermString( $r['event_perms'] ) );
						
						if( !IPSMember::isInGroup( $this->memberData, $permissionGroups ) )
						{
							continue;
						}
					}

					$events[ $r['event_id'] ]	= $r;
				}
			}
		}
		
		//-----------------------------------------
		// Extract the topic/forum data
		//-----------------------------------------
		
		foreach( $rows as $row )
		{
			if( $row['current_appcomponent'] == 'calendar' )
			{
				if( $row['location_1_type'] == 'event' AND isset($events[ $row['location_1_id'] ]) )
				{
					$row['where_line']		= ipsRegistry::getClass( 'class_localization' )->words['cal_event_ol'];
					$row['where_line_more']	= $events[ $row['location_1_id'] ]['event_title'];
					$row['where_link']		= "app=calendar&amp;module=calendar&amp;section=view&amp;do=showevent&amp;event_id={$events[ $row['location_1_id'] ]['event_id']}";
					$row['_whereLinkSeo']	= ipsRegistry::getClass('output')->buildSEOUrl( $row['where_link'], 'public', $events[ $row['location_1_id'] ]['event_title_seo'], 'cal_event' );
				}
				else if( $row['location_1_type'] == 'calendar' AND isset($cals[ $row['location_1_id'] ]) )
				{
					$row['where_line']		= ipsRegistry::getClass( 'class_localization' )->words['cal_calendar_ol'];
					$row['where_line_more']	= $cals[ $row['location_1_id'] ]['cal_title'];
					$row['where_link']		= "app=calendar&amp;module=calendar&amp;section=view&amp;cal_id={$cals[ $row['location_1_id'] ]['cal_id']}";
					$row['_whereLinkSeo']	= ipsRegistry::getClass('output')->buildSEOUrl( $row['where_link'], 'public', $cals[ $row['location_1_id'] ]['cal_title_seo'], 'cal_calendar' );
				}
				else
				{
					$row['where_line']		= ipsRegistry::getClass( 'class_localization' )->words['WHERE_calendar'];
					$row['where_link']		= 'app=calendar';
					$row['_whereLinkSeo']	= ipsRegistry::getClass('output')->buildSEOUrl( 'app=calendar', 'public', 'false', 'app=calendar' );
				}
			}
			
			$final[ $row['id'] ] = $row;
		}

		return $final;
	}
}

/**
 * Find ip address extension
 *
 */
class calendar_findIpAddress
{
	/**
	 * Return ip address lookup tables
	 *
	 * @access	public
	 * @return	array 	Table lookups
	 */
	public function getTables()
	{
		return array(
					'cal_event_comments'	=> array( 'comment_mid', 'ip_address', 'comment_date' ),
					'cal_event_ratings'		=> array( 'rating_member_id', 'rating_ip_address', '' ),
					);
	}
}