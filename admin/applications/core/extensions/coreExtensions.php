<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Core Sessions
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		20th February 2002
 * @version		$Rev: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Item Marking
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Downloads
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 *
 */

class itemMarking__core
{
	/**#@+
	 * Registry Object Shortcuts
	 *
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
	 * I'm a constructor, twisted constructor
	 *
	 * @param	object	ipsRegistry reference
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make objects */
		$this->registry = $registry;
		$this->DB	    = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang	    = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	= $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
	}

	/**
	 * Convert Data
	 * Takes an array of app specific data and remaps it to the DB table fields
	 *
	 * @param	array
	 * @return	array
	 */
	public function convertData( $data )
	{
		return $data;
	}
	
	/**
	 * Fetch unread count
	 *
	 * Grab the number of items truly unread
	 * This is called upon by 'markRead' when the number of items
	 * left hits zero (or less).
	 * 
	 *
	 * @param	array 	Array of data
	 * @param	array 	Array of read itemIDs
	 * @param	int 	Last global reset
	 * @return	integer	Last unread count
	 */
	public function fetchUnreadCount( $data, $readItems, $lastReset )
	{
		$readItems	= is_array( $readItems ) ? $readItems : array( 0 );

		$_count		= $this->DB->buildAndFetch( array( 
														'select'	=> 'COUNT(*) as cnt, MIN(date_updated) AS lastItem',
														'from'		=> 'rc_reports_index',
														'where'		=> "id NOT IN(" . implode( ",", array_keys( $readItems ) ) . ") AND date_updated > " . intval($lastReset)
												)	);

		return array( 'count'    => intval( $_count['cnt'] ),
					  'lastItem' => intval( $_count['lastItem'] ) );
	}
}

/**
* Main loader class
*/
class publicSessions__core
{
	/**
	 * Return session variables for this application
	 *
	 * current_appcomponent, current_module and current_section are automatically
	 * stored. This function allows you to add specific variables in.
	 *
	 * @author	Matt Mecham
	 * @return	array
	 */
	public function getSessionVariables()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$array = array( 'location_1_type'   => '',
						'location_1_id'     => 0,
						'location_2_type'   => '',
						'location_2_id'     => 0 );

		/* @link http://community.invisionpower.com/tracker/issue-33345-start-new-topic-reply-to-this-topic-online-list-location */
		if( ipsRegistry::$request['module'] == 'attach' )
		{
			define( 'NO_SESSION_UPDATE', true );
		}
		
		return $array;
	}
	
	/**
	 * Parse/format the online list data for the records
	 *
	 * @author	Brandon Farber
	 * @param	array 			Online list rows to check against
	 * @return	array 			Online list rows parsed
	 */
	public function parseOnlineEntries( $rows )
	{
		if( !is_array($rows) OR !count($rows) )
		{
			return $rows;
		}
		
		$final		= array();	

		foreach( $rows as $row )
		{
			if( $row['current_appcomponent'] == 'core' )
			{
				if( $row['current_module'] == 'global' )
				{
					if( $row['current_section'] == 'login' )
					{
						$row['where_line']		= ipsRegistry::getClass( 'class_localization' )->words['WHERE_login'];
					}
				}
				else if( $row['current_module'] == 'search' )
				{
					$row['where_line']		= ipsRegistry::getClass( 'class_localization' )->words['WHERE_search'];
				}
				else if( $row['current_module'] == 'reports' )
				{
					$rcCache = ipsRegistry::cache()->getCache('report_cache');
					
					if( is_array( $rcCache ) )
					{
						if( $rcCache['group_access'][ ipsRegistry::member()->getProperty('member_group_id') ] == true )
						{
							$row['where_line'] = ipsRegistry::getClass( 'class_localization' )->words['WHERE_reports'];
						}
					}
				}
			}
			
			$final[ $row['id'] ]	= $row;
		}
		
		return $final;
	}
}
