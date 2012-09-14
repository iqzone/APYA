<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board vVERSION_NUMBER
 * Calendar functions
 * Last Updated: $Date: 2012-03-26 20:09:40 -0400 (Mon, 26 Mar 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Calendar
 * @link		http://www.invisionpower.com
 * @since		5th January 2005
 * @version		$Revision: 10493 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class app_calendar_classes_functions
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
	 * Current calendar ID
	 *
	 * @var	int
	 */
	protected $calendarId;
	
	/**
	 * Calendar arrays (permissions checked)
	 *
	 * @var	array
	 */
	protected $calendars;
	
	/**
	 * Constructor
	 *
	 * @param	object		Registry object
	 * @param	bool		Ignore permission check
	 * @param	bool		Return instead of throwing an error
	 * @return	@e bool		Initiated successfully
	 */
	public function __construct( ipsRegistry $registry, $ignorePermissions=false, $return=false )
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

		//-----------------------------------------
		// Make sure calendar app is installed
		// (this is primarily here for API methods)
		//-----------------------------------------
		
		if( ! IPSLib::appIsInstalled( 'calendar' ) AND ( !defined('IPS_IS_INSTALLER') OR !IPS_IS_INSTALLER ) AND ( !defined('IPS_IS_UPGRADER') OR !IPS_IS_UPGRADER ) )
		{
			if( $return )
			{
				return false;
			}

			$this->registry->output->showError( 'no_permission', 1076, null, null, 404 );
		}

		//-----------------------------------------
		// Check cache
		//-----------------------------------------
		
		if( ! count( $this->caches['calendars'] ) )
		{
			$this->cache->rebuildCache( 'calendars', 'calendar' );
		}

		//-----------------------------------------
		// Loop through, check perms, etc.
		//-----------------------------------------

		$_noPermission	= array();
		
		if ( count( $this->caches['calendars'] ) AND is_array( $this->caches['calendars'] ) )
		{
			foreach( $this->caches['calendars'] as $cal_id => $cal )
			{
				//-----------------------------------------
				// Automatic FURL fix
				//-----------------------------------------
				
				if( !$cal['cal_title_seo'] )
				{
					$cal['cal_title_seo']	= IPSText::makeSeoTitle( $cal['cal_title'] );
					
					$this->DB->update( 'cal_calendars', array( 'cal_title_seo' => $cal['cal_title_seo'] ), 'cal_id=' . $cal_id );
				}
				
				//-----------------------------------------
				// Check permissions
				//-----------------------------------------
				
				if( !$this->registry->permissions->check( 'view', $cal ) AND !$ignorePermissions )
				{
					$_noPermission[]	= $cal['cal_id'];

					continue;
				}

				$this->calendars[ $cal['cal_id'] ]	= $cal;
			}
		}
		
		//-----------------------------------------
		// What calendar are we requesting?
		//-----------------------------------------

		if( !empty($this->request['cal_id']) AND isset( $this->calendars[ $this->request['cal_id'] ] ) )
		{
			$this->calendarId	= intval($this->request['cal_id']);
		}
		else if( !empty($this->request['cal_id']) AND !IN_ACP )
		{
			//-----------------------------------------
			// Calendar requested but it's not in $this->calendars
			// Either the cal_id doesn't exist, or user does not have view permissions
			//-----------------------------------------

			if( $return )
			{
				return false;
			}

			$_header	= ( !empty($this->request['cal_id']) AND in_array( $this->request['cal_id'], $_noPermission ) ) ? 403 : 404;
			
			$this->registry->output->showError( 'cal_no_perm', 1040, null, null, $_header );
		}
		else
		{
			$this->calendarId	= ( is_array( $this->calendars ) ) ? key( $this->calendars ) : 0;
		}

		return true;
	}
	
	/**
	 * Get calendar array
	 *
	 * @param	integer	[$calendar_id]	Optional calendar ID to retrieve, otherwise defaults to calendar id in request, or default calendar set by admin
	 * @return	array
	 */
	public function getCalendar( $calendar_id=0 )
	{
		return $this->calendars[ !empty($calendar_id) ? ( isset( $this->calendars[ $calendar_id ] ) ? $calendar_id : $this->calendarId ) : $this->calendarId ];
	}
	
	/**
	 * Get all calendars
	 *
	 * @return	array
	 */
	public function getCalendars()
	{
		return $this->calendars;
	}
	
	/**
	 * Get calendar jump menu
	 *
	 * @param	int		[$cal_id]	Default calendar ID to select
	 * @param	array 	[$extra]	Additional permission bits to check
	 * @return	string
	 */
	public function getCalendarJump( $selected=0, $extra=array() )
	{
		$return	= "";
		
		foreach( $this->calendars as $calendar )
		{
			//-----------------------------------------
			// Check any extra permissions
			//-----------------------------------------
			
			if( is_array($extra) AND count($extra) )
			{
				foreach( $extra as $_bit )
				{
					if( !$this->registry->permissions->check( $_bit, $calendar ) )
					{
						continue 2;
					}
				}
			}

			$return	.= "<option value='{$calendar['cal_id']}'" . ( $selected == $calendar['cal_id'] ? " selected='selected'" : '' ) . ">{$calendar['cal_title']}</option>\n";
		}
		
		return $return;
	}
	
	/**
	 * Return an HTML options list of days
	 *
	 * @param	integer	$day	Default selected day
	 * @return	string			HTML options
	 */
	public function returnDayDropdown( $day=0 )
	{
		$return	= "";
		$day	= intval($day);

		for( $x = 1 ; $x <= 31 ; $x++ )
		{
			$return	.= "\t<option value='" . $x . "'" . ( $x == $day ? " selected='selected'" : "" ) . ">" . $x . "</option>\n";
		}

		return $return;
	}
	
	/**
	 * Returns an HTML options list of months
	 *
	 * @param	integer	$month	Month to select by default
	 * @return	string			HTML options
	 */
	public function returnMonthDropdown( $month=0 )
	{
		$return	= "";
		$month	= intval($month);

		for( $x = 1 ; $x <= 12 ; $x++ )
		{
			$return	.= "\t<option value='" . $x . "'" . ( $x == $month ? " selected='selected'" : "" ) . ">" . gmstrftime( '%B', gmmktime( 0, 0, 0, $x, 15 ) ) . "</option>\n";
		}

		return $return;
	}


	/**
	 * Builds a year dropdown
	 *
	 * @param	integer	$year	Year to select by default
	 * @return	string	HTML dropdown
	 */
	public function returnYearDropdown( $year=0 )
	{
		$return	= "";
		$year	= intval($year);

		$this->settings['start_year']	= $this->settings['start_year'] ? ( $this->settings['start_year'] <= gmstrftime('%Y') ? $this->settings['start_year'] : gmstrftime('%Y') - 2 ) : gmstrftime('%Y') - 2;
		$this->settings['year_limit']	= $this->settings['year_limit'] ? $this->settings['year_limit'] : 5;
		
		if( $this->settings['start_year'] + $this->settings['year_limit'] < gmstrftime('%Y') )
		{
			$this->settings['year_limit']	= gmstrftime('%Y') - $this->settings['start_year'];
		}

		for( $x = $this->settings['start_year'], $xy = $this->settings['start_year'] + $this->settings['year_limit'] ; $x <= $xy ; $x++ )
		{
			$return	.= "\t<option value='" . $x . "'" . ( $x == $year ? " selected='selected'" : "" ) . ">" . $x . "</option>\n";
		}

		return $return;
	}
}