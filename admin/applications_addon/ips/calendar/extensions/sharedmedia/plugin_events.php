<?php
/**
 * @file		plugin_events.php 	Shared media plugin: calendar events
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		3/8/2011
 * $LastChangedDate: 2011-09-08 15:22:26 -0400 (Thu, 08 Sep 2011) $
 * @version		vVERSION_NUMBER
 * $Revision: 9469 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		plugin_calendar_events
 * @brief		Provide ability to share calendar events via editor
 */
class plugin_calendar_events
{
	/**
	 * Registry Object Shortcuts
	 *
	 * @var		$registry
	 * @var		$DB
	 * @var		$settings
	 * @var		$request
	 * @var		$lang
	 * @var		$member
	 * @var		$memberData
	 * @var		$cache
	 * @var		$caches
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

		$this->registry		= $registry;
		$this->DB			= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->member		= $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		$this->lang			= $this->registry->class_localization;
		
		$this->lang->loadLanguageFile( array( 'public_calendar' ), 'calendar' );
	}
	
	/**
	 * Return the tab title
	 *
	 * @return	@e string
	 */
	public function getTab()
	{
		if( $this->memberData['member_id'] )
		{
			return $this->lang->words['sharedmedia_events'];
		}
	}
	
	/**
	 * Return the HTML to display the tab
	 *
	 * @return	@e string
	 */
	public function showTab( $string )
	{
		//-----------------------------------------
		// Are we a member?
		//-----------------------------------------
		
		if( !$this->memberData['member_id'] )
		{
			return '';
		}

		//-----------------------------------------
		// How many approved events do we have?
		//-----------------------------------------
		
		$st		= intval($this->request['st']);
		$each	= 30;
		$where	= '';
		
		if( $string )
		{
			$where	= " AND ( event_title LIKE '%{$string}%' OR event_content LIKE '%{$string}%' )";
		}
		
		$count	= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'cal_events', 'where' => "event_approved=1 AND event_private=0 AND event_member_id={$this->memberData['member_id']}" . $where ) );
		$rows	= array();
		
		$pages	= $this->registry->output->generatePagination( array(	'totalItems'		=> $count['total'],
																		'itemsPerPage'		=> $each,
																		'currentStartValue'	=> $st,
																		'seoTitle'			=> '',
																		'method'			=> 'nextPrevious',
																		'noDropdown'		=> true,
																		'ajaxLoad'			=> 'mymedia_content',
																		'baseUrl'			=> "app=core&amp;module=ajax&amp;section=media&amp;do=loadtab&amp;tabapp=calendar&amp;tabplugin=events&amp;search=" . urlencode($string) ) );

		$this->DB->build( array( 'select' => '*', 'from' => 'cal_events', 'where' => "event_approved=1 AND event_private=0 AND event_member_id={$this->memberData['member_id']}" . $where, 'order' => 'event_lastupdated DESC', 'limit' => array( $st, $each ) ) );
		$outer	= $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			$rows[]	= array(
							'image'		=> $this->settings['img_url'] . '/sharedmedia/events.png',
							'width'		=> 0,
							'height'	=> 0,
							'title'		=> IPSText::truncate( $r['event_title'], 25 ),
							'desc'		=> IPSText::truncate( strip_tags( IPSText::stripAttachTag( IPSText::getTextClass('bbcode')->stripAllTags( $r['event_content'] ) ), '<br>' ), 100 ),
							'insert'	=> "calendar:events:" . $r['event_id'],
							);
		}

		return $this->registry->output->getTemplate('editors')->mediaGenericWrapper( $rows, $pages, 'calendar', 'events' );
	}

	/**
	 * Return the HTML output to display
	 *
	 * @param	int		$eventId	Event ID to show
	 * @return	@e string
	 */
	public function getOutput( $eventId=0 )
	{
		$eventId	= intval($eventId);
		
		if( !$eventId )
		{
			return '';
		}

		$event	= $this->DB->buildAndFetch( array(
												'select'	=> 'e.*',
												'from'		=> array( 'cal_events' => 'e' ),
												'where'		=> 'e.event_approved=1 AND e.event_private=0 AND e.event_id=' . $eventId,
												'add_join'	=> array(
																	array(
																		'select'	=> 'c.*',
																		'from'		=> array( 'cal_calendars' => 'c' ),
																		'where'		=> 'c.cal_id=e.event_calendar_id',
																		'type'		=> 'left',
																		)
																	)
										)		);

		//-----------------------------------------
		// Times and dates
		//-----------------------------------------
		
		$event['_start_time']	= strtotime( $event['event_start_date'] );
		$event['_end_time']		= ( $event['event_end_date'] AND $event['event_end_date'] != '0000-00-00 00:00:00' ) ? strtotime( $event['event_end_date'] ) : 0;
		$event['_event_time']	= '';
		$event['_event_etime']	= '';

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
		}
		
		if( !$event['event_all_day'] )
		{
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
		
		if( !$event['event_all_day'] )
		{
			if( $this->settings['cal_time_format'] == 'standard' )
			{
				$event['_event_time']	= ltrim( gmstrftime( '%I:%M %p', $event['_start_time'] ), '0' );
				$event['_event_etime']	= $event['_end_time'] ? ltrim( gmstrftime( '%I:%M %p', $event['_end_time'] ), '0' ) : ':00';
			}
			else
			{
				$event['_event_time']	= ltrim( gmstrftime( '%H:%M', $event['_start_time'] ), '0' );
				$event['_event_etime']	= $event['_end_time'] ? ltrim( gmstrftime( '%H:%M', $event['_end_time'] ), '0' ) : ':00';
			}
			
			if( strpos( ':00', $event['_event_time'] ) === 0 OR gmstrftime( '%H:%M', $event['_start_time'] ) == '00:00' )
			{
				$event['_event_time']	= '';
			}

			if( strpos( ':00', $event['_event_etime'] ) === 0 OR gmstrftime( '%H:%M', $event['_end_time'] ) == '00:00' )
			{
				$event['_event_etime']	= '';
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

		return $this->registry->output->getTemplate('calendar')->bbCodeEvent( $event, $type, $ends );
	}
	
	/**
	 * Verify current user has permission to post this
	 *
	 * @param	int		$eventId	Event ID to show
	 * @return	@e bool
	 */
	public function checkPostPermission( $eventId )
	{
		$eventId	= intval($eventId);

		if( !$eventId )
		{
			return '';
		}
		
		if( $this->memberData['g_is_supmod'] OR $this->memberData['is_mod'] )
		{
			return '';
		}
		
		$event	= $this->DB->buildAndFetch( array(
												'select'	=> 'e.*',
												'from'		=> array( 'cal_events' => 'e' ),
												'where'		=> 'e.event_approved=1 AND e.event_private=0 AND e.event_id=' . $eventId,
												'add_join'	=> array(
																	array(
																		'select'	=> 'c.*',
																		'from'		=> array( 'cal_calendars' => 'c' ),
																		'where'		=> 'c.cal_id=e.event_calendar_id',
																		'type'		=> 'left',
																		)
																	)
										)		);
		
		if( $this->memberData['member_id'] AND $event['event_member_id'] == $this->memberData['member_id'] )
		{
			return '';
		}
		
		return 'no_permission_shared';
	}
}