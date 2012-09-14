<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board vVERSION_NUMBER
 * Last Updated: $Date: 2011-06-29 17:59:13 -0400 (Wed, 29 Jun 2011) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Downloads
 * @link		http://www.invisionpower.com
 * @version		$Rev: 9124 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class furlRedirect_calendar
{	
	/**
	 * Key type: Type of action
	 *
	 * @var		string
	 */
	protected $_type	= '';
	
	/**
	 * Key ID
	 *
	 * @var		int
	 */
	protected $_id		= 0;

	/**
	 * Calendar ID
	 *
	 * @var		int
	 */
	protected $_calId	= 0;
	
	/**
	 * Constructor
	 *
	 * @param	object	registry
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		$this->registry =  $registry;
		$this->DB       =  $registry->DB();
		$this->settings =& $registry->fetchSettings();
	}

	/**
	 * Set the key ID
	 * <code>furlRedirect_forums::setKey( 'topic', 12 );</code>
	 *
	 * @param	string	Type
	 * @param	mixed	Value
	 */
	public function setKey( $name, $value )
	{
		$this->_type = $name;
		$this->_id   = $value;
	}
	
	/**
	 * Set up the key by URI
	 *
	 * @param	string		URI (example: index.php?showtopic=5&view=getlastpost)
	 * @return	@e void
	 */
	public function setKeyByUri( $uri )
	{
		if( IN_ACP )
		{
			return FALSE;
		}
		
		$uri = str_replace( '&amp;', '&', $uri );

		if ( strstr( $uri, '?' ) )
		{
			list( $_chaff, $uri ) = explode( '?', $uri );
		}
		
		if( $uri == 'app=calendar' )
		{
			$this->setKey( 'app', 'calendar' );
			return TRUE;			
		}
		else
		{
			foreach( explode( '&', $uri ) as $bits )
			{
				list( $k, $v ) = explode( '=', $bits );
				
				if ( $k )
				{
					if( $k == 'week' )
					{
						$this->setKey( 'week', $v );
						$this->_calId	= intval(ipsRegistry::$request['cal_id']);
						return TRUE;
					}
					else if ( $k == 'event_id' )
					{
						$this->setKey( 'event', intval( $v ) );
						return TRUE;
					}
				}
			}
			
			if( $this->request['cal_id'] )
			{
				$this->setKey( 'cal_id', intval( ipsRegistry::$request['cal_id'] ) );
				return true;
			}
		}
		
		return FALSE;
	}
	
	/**
	 * Return the SEO title
	 *
	 * @access	public
	 * @return	string		The SEO friendly name
	 */
	public function fetchSeoTitle()
	{
		switch ( $this->_type )
		{
			default:
				return FALSE;
			break;
			case 'app':
				return $this->_fetchSeoTitle_app();
			break;
			case 'cal_id':
			case 'week':
				return $this->_fetchSeoTitle_calendar();
			break;
			case 'event':
				return $this->_fetchSeoTitle_event();
			break;
		}
	}

	/**
	 * Return the base SEO title
	 *
	 * @access	public
	 * @return	string
	 */
	public function _fetchSeoTitle_app()
	{
		if ( ipsRegistry::$request['request_method'] != 'post' )
		{
			/* Try to figure out what is used in furlTemplates.php */
			$_SEOTEMPLATES = array();
			@include( IPSLib::getAppDir( 'calendar' ) . '/extensions/furlTemplates.php' );/*noLibHook*/
			
			if( $_SEOTEMPLATES['app=calendar']['out'][1] )
			{
				return $_SEOTEMPLATES['app=calendar']['out'][1];
			}
			else
			{
				return 'calendar/';
			}
		}
	}
	
	/**
	 * Return the seo title for the calendar
	 *
	 * @access	public
	 * @return	string
	 */
	public function _fetchSeoTitle_calendar()
	{
		if ( ipsRegistry::$request['request_method'] != 'post' )
		{
			$calendars	= ipsRegistry::cache()->getCache('calendars');
			$calendar	= $calendars[ $this->_calId ? $this->_calId : $this->_id ];

			/* Make sure we have a title */
			if( $calendar['cal_id'] )
			{
				return $calendar['cal_title_seo'] ? $calendar['cal_title_seo'] : IPSText::makeSeoTitle( $calendar['cal_title'] );
			}
		}
	}
	
	/**
	 * Return the seo title for event
	 *
	 * @access	public
	 * @return	string
	 */
	public function _fetchSeoTitle_event()
	{
		if ( ipsRegistry::$request['request_method'] != 'post' )
		{
			/* Query the event */
			$event	= $this->DB->buildAndFetch( array( 'select' => 'event_id, event_title, event_title_seo', 'from' => 'cal_events', 'where' => "event_id={$this->_id}" ) );
	
			/* Make sure we have an event */
			if( $event['event_id'] )
			{
				return $event['event_title_seo'] ? $event['event_title_seo'] : IPSText::makeSeoTitle( $event['event_title'] );
			}
		}
	}
}