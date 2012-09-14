<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Core extensions
 * Last Updated: $Date: 2012-05-21 09:09:36 -0400 (Mon, 21 May 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @since		20th February 2002
 * @version		$Rev: 10771 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class publicSessions__members
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
		
		if( !empty(ipsRegistry::$request['id']) AND ipsRegistry::$request['section'] == 'view' AND ipsRegistry::$request['module'] == 'profile' )
		{
			$array['location_1_type']	= 'profile';
			$array['location_1_id']		= ipsRegistry::$request['id'];
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
		$profiles	= array();
		$members	= array();
		
		//-----------------------------------------
		// Extract the topic/forum data
		//-----------------------------------------

		foreach( $rows as $row )
		{
			if( $row['current_appcomponent'] != 'members' OR !$row['current_module'] )
			{
				continue;
			}

			if( $row['current_module'] == 'profile' )
			{
				$profiles[] = $row['location_1_id'];
			}
		}

		if( count($profiles) )
		{
			ipsRegistry::DB()->build( array( 'select' => 'member_id, members_display_name, members_seo_name, member_banned, members_bitoptions', 'from' => 'members', 'where' => 'member_id IN(' . implode( ',', $profiles ) . ')' ) );
			$pr = ipsRegistry::DB()->execute();
			
			while( $r = ipsRegistry::DB()->fetch($pr) )
			{
				/* Setup bitwise option to check for banned/spammer members */
				$r = IPSMember::buildBitWiseOptions( $r );
				
				if ( ! IPSMember::isInactive( $r ) || ipsRegistry::member()->getProperty('g_is_supmod') )
				{
					$members[ $r['member_id'] ] = array( 'members_display_name' => $r['members_display_name'], 'members_seo_name' => $r['members_seo_name'] );
				}
			}
		}

		foreach( $rows as $row )
		{
			if( $row['current_appcomponent'] == 'members' )
			{
				if( $row['current_module'] == 'online' )
				{
					$row['where_line'] = ipsRegistry::getClass( 'class_localization' )->words['WHERE_online'];
				}
				
				if( $row['current_module'] == 'list' )
				{
					$row['where_line'] = ipsRegistry::getClass( 'class_localization' )->words['WHERE_members'];
				}
				
				if( $row['current_module'] == 'messaging' )
				{
					$row['where_line'] = ipsRegistry::getClass( 'class_localization' )->words['WHERE_msg'];
				}
				
				if( $row['current_module'] == 'profile' )
				{
					if ( isset($members[ $row['location_1_id'] ]) )
					{
						$row['where_line']		= ipsRegistry::getClass( 'class_localization' )->words['WHERE_profile'];
						$row['where_line_more']	= $members[ $row['location_1_id'] ]['members_display_name'];
						$row['where_link']		= 'showuser=' . $row['location_1_id'];
						$row['_whereLinkSeo']   = ipsRegistry::getClass('output')->buildSEOUrl( $row['where_link'], 'public', $members[ $row['location_1_id'] ]['members_seo_name'], 'showuser' );
					}
				}
			}
			
			$final[ $row['id'] ] = $row;
		}
		
		return $final;
	}
}

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Item Marking
 * Last Updated: $Date: 2012-05-21 09:09:36 -0400 (Mon, 21 May 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10771 $
 */

class itemMarking__members
{
	/**
	 * Field Convert Data Remap Array
	 *
	 * This is where you can map your app_key_# numbers to application savvy fields
	 * 
	 * @var		array
	 */
	protected $_convertData = array();
	
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
	 * Determines whether to load all markers for this view or not
	 * 
	 * @return	bool
	 */
	public function loadAllMarkers()
	{
		/* don't need markers for this app */
		return false;
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
		return 0;
	}
}
