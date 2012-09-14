<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Item Marking
 * Last Updated: $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Blog
 * @link		http://www.invisionpower.com
 * @version		$Rev: 4 $
 *
 */
 
class itemMarking__blog
{
	/**
	 * Field Convert Data Remap Array
	 *
	 * This is where you can map your app_key_# numbers to application savvy fields
	 * 
	 * @access	protected
	 * @var		array
	 */
	protected $_convertData = array( 'blogID' => 'item_app_key_1' );
	
	/**#@+
	 * Registry Object Shortcuts
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
	 * I'm a constructor, twisted constructor
	 *
	 * @access	public
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
	 * @access	public
	 * @param	array
	 * @return	array
	 */
	public function convertData( $data )
	{
		$_data = array();
		
		foreach( $data as $k => $v )
		{
			if ( isset($this->_convertData[$k]) )
			{
				# Make sure we use intval here as all 'forum' app fields
				# are integers.
				$_data[ $this->_convertData[ $k ] ] = intval( $v );
			}
			else
			{
				$_data[ $k ] = $v;
			}
		}
		
		return $_data;
	}
		
	/**
	 * Fetch unread count
	 *
	 * Grab the number of items truly unread
	 * This is called upon by 'markRead' when the number of items
	 * left hits zero (or less).
	 * 
	 *
	 * @access	public
	 * @param	array 	Array of data
	 * @param	array 	Array of read itemIDs
	 * @param	int 	Last global reset
	 * @return	integer	Last unread count
	 */
	public function fetchUnreadCount( $data, $readItems, $lastReset )
	{
		$count     = 0;
		$lastItem  = 0;
		$approved  = ' AND entry_status != \'draft\'';
		$readItems = is_array( $readItems ) ? $readItems : array( 0 );
		
		/* Fix up approved properly now */
		if ( $this->registry->isClassLoaded('blogFunctions') )
		{
			$blog = $this->registry->getClass('blogFunctions')->getActiveBlog();
			
			if ( $blog['blog_id'] AND $this->memberData['member_id'] )
			{
				if ( $this->registry->getClass('blogFunctions')->ownsBlog( $blog, $this->memberData ) )
				{
					$approved = '';
				}
				else if ( $this->memberData['g_is_supmod'] OR $this->memberData['_blogmod']['moderate_can_view_draft'] )
				{
					$approved = '';
				}
			}
		}
		
		if ( $data['blogID'] )
		{
			$_count = $this->DB->buildAndFetch( array( 
															'select' => 'COUNT(*) as cnt, MIN(entry_last_update) as lastItem',
															'from'   => 'blog_entries',
															'where'  => "blog_id=" . intval( $data['blogID'] ) . " {$approved} AND entry_id NOT IN(".implode(",",array_keys($readItems)).") AND entry_last_update > ".intval($lastReset)
													)	);
													
			$count 	  = intval( $_count['cnt'] );
			$lastItem = intval( $_count['lastItem'] );
		}

		return array( 'count'    => $count,
					  'lastItem' => $lastItem );
	}
}

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Blog Extensions
 * Last Updated: $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Blog
 * @link		http://www.invisionpower.com
 * @since		12th March 2002
 * @version		$Revision: 4 $
 *
 */


class publicSessions__blog
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
		/* INIT */
		$array = array( 'location_1_type'   => '',
						'location_1_id'	 	=> 0,
						'location_2_type'   => '',
						'location_2_id'	 	=> 0 );
						
		
		/* Store */
		if ( ipsRegistry::$request['module'] == 'display' )
		{
			$array = array( 'location_1_type' => ( ipsRegistry::$request['eid'] ) ? 'blog' : ipsRegistry::$request['section'],
							'location_1_id'   => intval(ipsRegistry::$request['blogid']),
							'location_2_type' => ipsRegistry::$request['eid'] ? 'entry' : substr( ipsRegistry::$request['do'], 0, 10 ),
							'location_2_id'   => intval(ipsRegistry::$request['eid'])
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
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$return			= array();
		$blog_cache		= array();
		$entry_cache	= array();
		$member_id		= intval( ipsRegistry::member()->getProperty('member_id') );

		//-----------------------------------------
		// Load language file
		//-----------------------------------------

		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_location' ), 'blog' );

		//-----------------------------------------
		// LOOP
		//-----------------------------------------

		if ( is_array( $rows ) and count( $rows ) )
		{
			foreach( $rows as $row )
			{
				if( $row['current_appcomponent'] != 'blog' OR !$row['current_module'] )
				{
					continue;
				}
				
				if ( $row['location_1_type'] == 'blog' && intval($row['location_1_id']) )
				{
					$blog_ids[] = intval($row['location_1_id']);
				}

				if ( $row['location_2_type'] == 'entry' && intval($row['location_2_id']) )
				{
					$entry_ids[] = intval($row['location_2_id']);
				}
			}

			// load blogs
			if ( count( $blog_ids ) > 0 )
			{
				$query[]	= "blog_id in(" . implode( ",", $blog_ids ) . ")";
				$blogmod	= ipsRegistry::member()->getProperty('_blogmod');
				
				if ( !ipsRegistry::member()->getProperty('g_is_supmod') and !$blogmod['moderate_can_disable'])
				{
					$query[] = "blog_disabled = 0";
				}

				if ( !ipsRegistry::member()->getProperty('member_id') )
				{
					$query[] = "blog_allowguests = 1";
				}

				if( ! $blogmod['moderate_can_view_private'] )
				{
					$query[] = "( ( blog_owner_only=1 AND member_id={$member_id} ) OR blog_owner_only=0 ) AND ( blog_authorized_users LIKE '%,{$member_id},%' OR blog_authorized_users IS NULL )";
				}

		    	if ( count($query) )
		    	{
		    		$query_string = "AND " . implode( " AND ", $query );
		    	}

				ipsRegistry::DB()->build( array( 
												'select'	=> 'blog_id, blog_name, blog_seo_name', 
												'from'		=> 'blog_blogs', 
												'where'		=> "blog_name IS NOT NULL {$query_string}") );
				ipsRegistry::DB()->execute();

				while ( $row = ipsRegistry::DB()->fetch() )
				{
					$blog_cache[$row['blog_id']] = $row;
				}
			}

			// load entries
			if ( count( $entry_ids ) > 0 )
			{
				$extra = " AND e.entry_id in(" . implode( ",", $entry_ids ) . ")";

				/* INIT */
				$limit       = ipsRegistry::$settings['blogs_portal_lastx'] ? ipsRegistry::$settings['blogs_portal_lastx'] : 5;
				$allowguests = '';
				
				/* Allow Guests */
				if( ! ipsRegistry::member()->getProperty('member_id') )
				{
					$allowguests .= " AND b.blog_allowguests = 1";
				}
	
				ipsRegistry::DB()->build( array( 
												'select'   => 'e.*',
												'from'     => array( 'blog_entries' => 'e' ),
												'add_join' => array(
																	array( 
																			'select' => 'b.blog_name, b.blog_seo_name',
																			'from'   => array( 'blog_blogs' => 'b' ),
																			'where'  => "e.blog_id=b.blog_id",
																			'type'   => 'left'
																		) ),
												'where'    => "e.entry_status='published' AND 
												              ( ( b.blog_owner_only=1 AND b.member_id={$member_id} ) OR b.blog_owner_only=0 ) AND 
	                                                          ( b.blog_authorized_users LIKE '%,{$member_id},%' OR b.blog_authorized_users IS NULL ) {$allowguests} {$extra}",
												'order'    => 'e.entry_date DESC',
												'limit'    => array( 0, $limit )
								     )		);
				ipsRegistry::DB()->execute();

				while ( $row = ipsRegistry::DB()->fetch() )
				{
					$blog_cache[ $row['blog_id']]  = $row;
					$entry_cache[$row['entry_id']] = $row;
				}
			}

			foreach( $rows as $row )
			{
				if( $row['current_appcomponent'] != 'blog' )
				{
					$final[ $row['id'] ] = $row;
					
					continue;
				}
				
				$g_blog_settings = IPSLib::isSerialized(ipsRegistry::member()->getProperty('g_blog_settings')) ? unserialize( ipsRegistry::member()->getProperty('g_blog_settings') ) : array();
				
				if( $g_blog_settings['g_blog_allowview'] )
				{
					if ( $row['location_2_type'] == 'entry' && isset($entry_cache[$row['location_2_id']]) )
					{
						$row['where_line']		= ipsRegistry::getClass('class_localization')->words['blog_loc_entry'];
						$row['where_line_more']	= $entry_cache[$row['location_2_id']]['entry_name'];
						$row['where_link']		= 'app=blog&amp;blogid=' . $row['location_1_id'] . '&amp;showentry=' . $row['location_2_id'];
						$row['_whereLinkSeo']   = ipsRegistry::getClass('output')->formatUrl( ipsRegistry::getClass('output')->buildUrl( $row['where_link'], 'public' ), $entry_cache[ $row['location_2_id'] ]['entry_name_seo'], 'showentry' );

					}
					else if ( $row['location_1_type'] == 'blog' && isset($blog_cache[$row['location_1_id']]) )
					{
					
						$row['where_line']		= ipsRegistry::getClass( 'class_localization' )->words['blog_loc_blog'];
						$row['where_line_more']	= $blog_cache[$row['location_1_id']]['blog_name'];
						$row['where_link']		= 'app=blog&amp;blogid=' . $row['location_1_id'];
						$row['_whereLinkSeo']   = ipsRegistry::getClass('output')->formatUrl( ipsRegistry::getClass('output')->buildUrl( $row['where_link'], 'public' ), $blog_cache[ $row['location_1_id'] ]['blog_seo_name'], 'showblog' );
					}
					else
					{
						$row['where_line']		= $row['location_1_type'] ? ipsRegistry::getClass( 'class_localization' )->words['blog_loc_'.$row['location_1_type']] : ipsRegistry::getClass( 'class_localization' )->words['blog_loc_list'];
						$row['where_line_more']	= $blog_cache[$row['location_1_id']]['blog_name'];
						$row['where_link']		= 'app=blog';
						$row['_whereLinkSeo']   = ipsRegistry::getClass('output')->formatUrl( ipsRegistry::getClass('output')->buildUrl( $row['where_link'], 'public' ), 'false', 'app=blog' );
					}
				}
				
				$final[ $row['id'] ]	= $row;
			}
		}

		return $final;
	}

}

/**
 * Handles custom skins per blog
 * @package		IP.Blog
 */
class fetchSkin__blog
{
	/**#@+
	 * Registry Object Shortcuts
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
	 * I'm a constructor, twisted constructor
	 *
	 * @access	public
	 * @param	object	ipsRegistry reference
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make objects */
		$this->registry		=  $registry;
		$this->DB			=  $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->lang			=  $this->registry->getClass('class_localization');
		$this->member		=  $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		=  $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
	}
	
	/**
	* Returns a skin ID or FALSE
	*
	* @access	public
	* @return   mixed			INT or FALSE if no skin found / required
	*/
	public function fetchSkin()
	{
		/* INIT */
		$blogid    = intval( $this->request['blogid'] );

		if( ! $blogid )
		{
			return FALSE;
		}

		/* Query skin */
		$skin = $this->DB->buildAndFetch( array( 'select' => 'blog_skin_id', 'from' => 'blog_blogs', 'where' => "blog_id={$blogid}") );
		
		if( ! $skin['blog_skin_id'] )
		{
			return FALSE;
		}

		/* Make sure it's legal */
		$_test = $this->allSkins[ $skin['blog_skin_id'] ];

		if ( $_test['_youCanUse'] !== TRUE )
		{
			return FALSE;
		}	

		return $skin['blog_skin_id'];
	}
}


/**
 * Find ip address extension
 * @package		IP.Blog
 */
class blog_findIpAddress
{
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object	Registry instance
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		
	}
	
	/**
	 * Return ip address lookup tables
	 *
	 * @access	public
	 * @return	array 	Table lookups
	 */
	public function getTables()
	{
		return array(
					'blog_comments'	=> array( 'member_id', 'ip_address', 'comment_date' ),
					'blog_voters'	=> array( 'member_id', 'ip_address', 'vote_date' ),
					);
	}
}
