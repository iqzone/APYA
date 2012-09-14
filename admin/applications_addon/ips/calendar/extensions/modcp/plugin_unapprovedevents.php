<?php
/**
 * @file		plugin_unapprovedevents.php 	Moderator control panel plugin: show events pending approval
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		2/23/2011
 * $LastChangedDate: 2012-03-21 12:54:20 -0400 (Wed, 21 Mar 2012) $
 * @version		vVERSION_NUMBER
 * $Revision: 10464 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		plugin_calendar_unapprovedevents
 * @brief		Moderator control panel plugin: show events pending approval
 */
class plugin_calendar_unapprovedevents
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
	 * Calendar functions
	 *
	 * @var		object
	 */
	protected $functions;

	/**
	 * Calendar view class
	 *
	 * @var		object
	 */
	protected $view;
	
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
		
		/* Load language strings.. */
		$this->lang->loadLanguageFile( array( 'public_calendar' ), 'calendar' );
	}
	
	/**
	 * Returns the primary tab key for the navigation bar
	 * 
	 * @return	@e string
	 */
	public function getPrimaryTab()
	{
		return 'unapproved_content';
	}
	
	/**
	 * Returns the secondary tab key for the navigation bar
	 * 
	 * @return	@e string
	 */
	public function getSecondaryTab()
	{
		return 'unapprovedevents';
	}
	
	/**
	 * Determine if we can view tab
	 *
	 * @param	array 	$permissions	Moderator permissions
	 * @return	@e bool
	 */
	public function canView( $permissions )
	{
		if( $this->memberData['g_is_supmod'] )
		{
			return true;
		}
		
		return false;
	}

	/**
	 * Execute plugin
	 *
	 * @param	array 	$permissions	Moderator permissions
	 * @return	@e string
	 */
	public function executePlugin( $permissions )
	{
		//-----------------------------------------
		// Check permissions
		//-----------------------------------------

		if( !$this->canView( $permissions ) )
		{
			return '';
		}
		
		//-----------------------------------------
		// Functions class
		//-----------------------------------------
		
		$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir( 'calendar' ) . "/sources/functions.php", 'app_calendar_classes_functions', 'calendar' );
		$this->functions	= new $classToLoad( $this->registry );

		$classToLoad		= IPSLib::loadActionOverloader( IPSLib::getAppDir( 'calendar' ) . "/modules_public/calendar/view.php", 'public_calendar_calendar_view' );
		$this->view			= new $classToLoad( $this->registry );
		$this->view->makeRegistryShortcuts( $this->registry );
		$this->view->initCalendar();
		
		/* Add some CSS.. */
		$this->registry->output->addToDocumentHead( 'importcss', "{$this->settings['css_base_url']}style_css/{$this->registry->output->skin['_csscacheid']}/ipb_calendar.css" );
		
		//-----------------------------------------
		// Get 10 events
		//-----------------------------------------
		
		$calendars	= $this->functions->getCalendars();
		$events		= array();
		$total		= array( 'events' => 0 );

		if( count($calendars) )
		{
			$_calIds	= array_keys( $calendars );
			$st			= intval($this->request['st']);
			$total		= $this->DB->buildAndFetch( array( 'select' => 'count(*) as events', 'from' => 'cal_events', 'where' => "event_calendar_id IN(" . implode( ',', $_calIds ) . ') AND event_approved=0' ) );
			
			//-----------------------------------------
			// Page links
			//-----------------------------------------
			
			$pages	= $this->registry->output->generatePagination( array(	'totalItems'		=> $total['events'],
																			'itemsPerPage'		=> 10,
																			'currentStartValue'	=> $st,
																			'baseUrl'			=> "app=core&amp;module=modcp&amp;fromapp=calendar&amp;tab=unapprovedevents",
																)		);
			
			/* Got events to load? */
			if ( $total['events'] )
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
				
				$this->DB->build( array(
										'select'	=> 'e.*',
										'from'		=> array( 'cal_events' => 'e' ),
										'order'		=> 'e.event_start_date ASC',
										'limit'		=> array( $st, 10 ),
										'where'		=> "e.event_calendar_id IN(" . implode( ',', $_calIds ) . ') AND e.event_approved=0',
										'add_join'	=> $_joins,
								)		);
				$outer	= $this->DB->execute();
				
				while( $r = $this->DB->fetch($outer) )
				{
					$events[] = $this->view->calendarMakeEventHTML( IPSMember::buildDisplayData( $r ), true );
				}
			}
		}
		
		return $this->registry->output->getTemplate('calendar')->modEventsWrapper( $events, $pages );
	}
}