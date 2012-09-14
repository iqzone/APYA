<?php
/**
 * @file		events.php 	Events like class (calendar application)
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * $LastChangedDate: 2011-04-15 11:55:29 -0400 (Fri, 15 Apr 2011) $
 * @version		vVERSION_NUMBER
 * $Revision: 8355 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		like_calendar_events_composite
 * @brief		Events like class (calendar application)
 */
class like_calendar_events_composite extends classes_like_composite
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
	 * @return	@e void
	 */
	public function __construct()
	{
		/* Make registry objects */
		$this->registry   =  ipsRegistry::instance();
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		
		$this->lang->loadLanguageFile( array( 'public_calendar' ), 'calendar' );
	}
	
	/**
	 * Fetch the template group
	 * 
	 * @return	@e string
	 */
	public function skin()
	{
		return 'calendar';
	}
	
	/**
	 * Fetch the template prefix.  This allows you to have two follow implementations in
	 * one skin file (i.e. skin_calendars -> eventLikeMoreDialog() and skin_calendars -> calendarLikeMoreDialog())
	 * 
	 * @return	@e string
	 */
	public function templatePrefix() 
	{ 
	    return 'event_'; 
	}
	
	/**
	 * Gets the vernacular (like or follow)
	 *
	 * @return	@e string
	 */
	public function getVernacular()
	{
		return 'follow_e';
	}
	
	/**
	 * Return an array of acceptable frequencies
	 * Possible: immediate, offline, daily, weekly
	 * 
	 * @return	@e array
	 */
	public function allowedFrequencies()
	{
		return array( 'immediate', 'offline' );
	}
	
	/**
	 * Return types of notification available for this item
	 * 
	 * @return	@e array	array( key, human readable )
	 */
	public function getNotifyType()
	{
		return array( 'comments', ipsRegistry::getClass('class_localization')->words['like__updatesandcomments'] );
	}
	
	/**
	 * Returns the type of item
	 * 
	 * @param	mixed		$relId			Relationship ID or array of IDs
	 * @param	array		$selectType		Array of meta to select (title, url, type, parentTitle, parentUrl, parentType) null fetches all
	 * @return	@e array	Meta data
	 */
	public function getMeta( $relId, $selectType=null )
	{
		$return    = array();
		$isNumeric = false;
		
		if ( is_numeric( $relId ) )
		{
			$relId     = array( intval($relId) );
			$isNumeric = true;
		}
		
		$this->DB->build( array( 'select' => 'e.*',
								 'from'   => array( 'cal_events' => 'e' ),
								 'where'  => 'e.event_id IN (' . implode( ',', $relId ) . ')',
								 'add_join' => array( array( 'select' => 'c.*',
															 'from'   => array( 'cal_calendars' => 'c' ),
															 'where'  => 'e.event_calendar_id=c.cal_id',
															 'type'   => 'left'  ) ) ) );
		
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			/* Title */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'title', $selectType ) ) )
			{
				$return[ $row['event_id'] ]['like.title'] = $row['event_title'];
			} 
			
			/* URL */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'url', $selectType ) ) )
			{
				$return[ $row['event_id'] ]['like.url'] = $this->registry->output->buildSEOUrl( "app=calendar&amp;module=calendar&amp;section=view&amp;do=showevent&amp;event_id=" . $row['event_id'], "public", $row['event_title_seo'], "cal_event" );
			}
			
			/* Type */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'type', $selectType ) ) )
			{
				$return[ $row['event_id'] ]['like.type'] = $this->lang->words['like__event'];
			} 
			
			/* Parent title */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'parentTitle', $selectType ) ) )
			{
				$return[ $row['event_id'] ]['like.parentTitle'] = $row['cal_title'];
			} 
			
			/* Parent url */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'parentTitle', $selectType ) ) )
			{
				$return[ $row['event_id'] ]['like.parentUrl'] = $this->registry->output->buildSEOUrl( "app=calendar&amp;module=calendar&amp;section=view&amp;cal_id=" . $row['cal_id'], "public", $row['cal_title_seo'], "cal_calendar" );
			} 
			
			/* Parent Type */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'parentType', $selectType ) ) )
			{
				$return[ $row['event_id'] ]['like.parentType'] = $this->lang->words['like__calendar'];
			} 
		}
		
		return ( $isNumeric === true ) ? array_pop( $return ) : $return;
	}
}