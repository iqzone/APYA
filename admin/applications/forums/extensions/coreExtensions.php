<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Forum permissions mappings
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $ 
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * Class name must be (app)ShareLinks
 */
class forumsShareLinks
{
	/**
	 * Shortcuts
	 *
	 * @var	object
	 */
	protected $DB;
	protected $registry;
	
	/**
	 * Constructor
	 *
	 * @return	@e void
	 */
	public function __construct()
	{
		$this->DB 		= ipsRegistry::DB();
		$this->registry = ipsRegistry::instance();
	}
	
	/**
	 * Deconstruct the URL
	 *
	 * An array of URL bits are passed ( array( 'showtopic' => 100, 'pid' => '123' ) ).
	 * This function should attempt to return formatted data:
	 *
	 *
	 * array( 'data_app'          => '',
	 *		  'data_type'		  => '',
	 *		  'data_primary_id'	  => 0,
	 *		  'data_secondary_id' => 0 )
	 *
	 * log_data_app MUST be set if you find a match. Leave blank to confirm no match
	 *
	 * @param	array
	 * @return	array
	 */
	 public function deconstructUrl( $url )
	 {
	 	$ret = array( 'data_app'          => '',
	 				  'data_type'		  => '',
	 				  'data_primary_id'	  => 0,
	 				  'data_secondary_id' => 0 );
	 				  
	 	if ( is_array( $url ) AND count( $url ) )
	 	{
	 		if ( isset( $url['showtopic'] ) )
	 		{
	 			$ret['data_app']        = 'forums';
	 			$ret['data_type']       = 'topic';
	 			$ret['data_primary_id'] = $url['showtopic'];
	 		}
	 		else if (  $url['module'] == 'forums' AND $url['section'] == 'topics' )
	 		{
	 			$ret['data_app']        = 'forums';
	 			$ret['data_type']       = 'topic';
	 			$ret['data_primary_id'] = $url['tid'];
	 		}
	 	}

	 	return $ret;
	 }
	 
	 /**
	  * Process links data
	  *
	  * Array will be $key => array( log_data_app =>,  log_data_type =>,  log_data_primary_id =>, )
	  *
	  * MUST be returned:
	  * $key => array( 'title'     => 'My Great Topic',
	  *				   'url'   	   => URL, SEO where appropriate,
	  *				   'published' => unix time stamp,
	  *				   'icon'      => icon relative to /style_images/master (16x16 icon)
	  *
	  * @param	array
	  * @return array
	  */
	 public function processData( $array )
	 {
	 	$topics = array();
	 	$final  = array();
	 	$return = array();
	 	
	 	/* Ensure forums class is loaded */
	 	if ( ! $this->registry->isClassLoaded('class_forums') )
	 	{
		 	try
			{
				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php", 'class_forums', 'forums' );
				$this->registry->setClass( 'class_forums', new $classToLoad( $this->registry ) );
				$this->registry->getClass('class_forums')->strip_invisible = 1;
				
				$this->registry->getClass('class_forums')->forumsInit();
			}
			catch( Exception $error )
			{
				IPS_exception_error( $error );
			}
		}
			
	 	/* Here we go again */
	 	if ( is_array( $array ) and count( $array ) )
	 	{
	 		foreach( $array as $key => $data )
	 		{
	 			if ( $data['log_data_type'] == 'topic' )
	 			{
	 				$topics[] = intval( $data['log_data_primary_id'] );
	 			}
	 		}
	 		
	 		if ( count( $topics ) )
	 		{
	 			$this->DB->build( array( 'select' => 'tid, title, title_seo, start_date, forum_id',
	 									 'from'   => 'topics',
	 									 'where'  => 'tid IN (' . implode( ',', $topics ) . ')' ) );
	 									 
	 			$this->DB->execute();
	 			
	 			while( $row = $this->DB->fetch() )
	 			{
	 				/* Quick permission check */
	 				if ( ! $this->registry->getClass('class_forums')->forumsCheckAccess( $row['forum_id'], 0, 'forum', $row, true ) )
	 				{
	 					$url   = $this->registry->output->buildUrl( 'showtopic='. $row['tid'], 'publicNoSession' );
	 					$title = 'Protected Topic';
	 				}
	 				else
	 				{
	 					$url   = $this->registry->output->buildSEOUrl( 'showtopic='. $row['tid'], 'publicNoSession', $row['title_seo'], 'showtopic' );
	 					$title = $row['title'];
	 				}
	 				
	 				$final[ $row['tid'] ] = array( 'title'     => $title,
	 											   'url'       => $url,
	 											   'published' => $row['start_date'],
	 											   'icon'	   => 'page_topic.png' );
	 			}
	 		}
	 		
	 		/* Sigh */
	 		if ( is_array( $final ) )
	 		{
	 			foreach( $array as $key => $data )
	 			{
	 				$return[ $key ] = isset($final[ $data['log_data_primary_id'] ]) ? $final[ $data['log_data_primary_id'] ] : array();
	 			}
	 			
	 			return $return;
	 		}
	 		
	 		return array();
	 	}
	 	
	 	return array();
	 }
	 
	 /**
	  * Process links data
	  *
	  * Array will be array( data_app =>,  data_type =>,  data_primary_id =>, )
	  *
	  * Return TRUE for permission OK, FALSE for no permission
	  *
	  * @param	array
	  * @return array
	  */
	 public function permissionCheck( $array )
	 {
	 	/* Ensure forums class is loaded */
	 	if ( ! $this->registry->isClassLoaded('class_forums') )
	 	{
		 	try
			{
				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php", 'class_forums', 'forums' );
				$this->registry->setClass( 'class_forums', new $classToLoad( $this->registry ) );
				$this->registry->getClass('class_forums')->strip_invisible = 1;
				
				$this->registry->getClass('class_forums')->forumsInit();
			}
			catch( Exception $error )
			{
				IPS_exception_error( $error );
			}
		}
		
		/* Quick permission check */
		if ( $array['data_type'] == 'topic' )
		{
			$topic = $this->DB->buildAndFetch( array( 'select' => '*',
			 									 	  'from'   => 'topics',
			 									 	  'where'  => 'tid=' . intval( $array['data_primary_id'] ) ) );
		
		
			if ( $this->registry->getClass('class_forums')->forumsCheckAccess( $topic['forum_id'], 0, 'forum', $topic, true ) )
			{
				return TRUE;
			}
		}
		
		return FALSE;
	 }
    
}

$_PERM_CONFIG = array( 'Forum' );

class forumsPermMappingForum
{
	/**
	 * Mapping of keys to columns
	 *
	 * @var		array
	 */
	protected $mapping = array(
								'view'     => 'perm_view',
								'read'     => 'perm_2',
								'reply'    => 'perm_3',
								'start'    => 'perm_4',
								'upload'   => 'perm_5',
								'download' => 'perm_6'
							);

	/**
	 * Mapping of keys to names
	 *
	 * @var		array
	 */
	protected $perm_names = array(
								'view'     => 'Show Forum',
								'read'     => 'Read Topics',
								'reply'    => 'Reply Topics',
								'start'    => 'Start Topics',
								'upload'   => 'Upload',
								'download' => 'Download',
							);

	/**
	 * Mapping of keys to background colors for the form
	 *
	 * @var		array
	 */
	protected $perm_colors = array(
								'view'     => '#fff0f2',
								'read'     => '#effff6',
								'reply'    => '#edfaff',
								'start'    => '#f0f1ff',
								'upload'   => '#fffaee',
								'download' => '#ffeef9',
							);

	/**
	 * Method to pull the key/column mapping
	 *
	 * @return	array
	 */
	public function getMapping()
	{
		return $this->mapping;
	}

	/**
	 * Method to pull the key/name mapping
	 *
	 * @return	array
	 */
	public function getPermNames()
	{
		return $this->perm_names;
	}

	/**
	 * Method to pull the key/color mapping
	 *
	 * @return	array
	 */
	public function getPermColors()
	{
		return $this->perm_colors;
	}

	/**
	 * Retrieve the permission items
	 *
	 * @return	array
	 */
	public function getPermItems()
	{
		require_once( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php" );/*noLibHook*/
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/admin_forum_functions.php", 'admin_forum_functions', 'forums' );
		
		$forumfunc = new $classToLoad( ipsRegistry::instance() );
		$forumfunc->forumsInit();
				
		$forum_data = $forumfunc->adForumsForumData();

		$return_arr = array();
		foreach( $forum_data as $r )
		{
			$return_arr[$r['id']] = array(
												'title'     => $r['depthed_name'],
												'perm_view' => $r['perm_view'],
												'perm_2'    => $r['perm_2'],
												'perm_3'    => $r['perm_3'],
												'perm_4'    => $r['perm_4'],
												'perm_5'    => $r['perm_5'],
												'perm_6'    => $r['perm_6'],
												'perm_7'    => $r['perm_7'],
												'restrict'  => $r['parent_id'] == 'root' ? 'perm_view' : '',
											);
		}
		
		return $return_arr;
		
	}
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
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 */

class itemMarking__forums
{
	/**
	 * Field Convert Data Remap Array
	 *
	 * This is where you can map your app_key_# numbers to application savvy fields
	 * 
	 * @var		array
	 */
	protected $_convertData = array( 'forumID' => 'item_app_key_1' );
	
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
	 * Determines whether to load all markers for this view or not
	 * 
	 * @return	bool
	 */
	public function loadAllMarkers()
	{
		if ( ! empty( $this->request['showtopic'] ) )
		{
			return false;
		}
		
		if ( ! empty( $this->request['showforum'] ) )
		{
			return false;
		}
		
		return true;
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
		$count     = 0;
		$lastItem  = 0;
		$approved  = $this->memberData['is_mod'] ? ' AND ' . $this->registry->getClass('class_forums')->fetchTopicHiddenQuery( array( 'visible', 'hidden' ), '' ) . ' ' : ' AND ' . $this->registry->getClass('class_forums')->fetchTopicHiddenQuery( array( 'visible' ), '' ) . ' ';
		$readItems = is_array( $readItems ) ? $readItems : array( 0 );

		if ( $data['forumID'] )
		{
			$_count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as cnt, MIN(last_post) as lastItem',
													   'from'   => 'topics',
													   'where'  => "forum_id=" . intval( $data['forumID'] ) . " {$approved} AND tid NOT IN(".implode(",",array_keys($readItems)).") AND last_post > ".intval($lastReset) . " AND state != 'link'" )	);
													
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
 * IP.Board v3.3.3
 * Fetch Skin
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 */

class fetchSkin__forums
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
		
		/* Check for class_forums */
		if( ipsRegistry::isClassLoaded('class_forums') !== TRUE )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php", 'class_forums', 'forums' );
			$this->registry->setClass( 'class_forums', new $classToLoad( $registry ) );
			$this->registry->class_forums->forumsInit();
		}
	}
	
	/**
	 * Returns a skin ID or FALSE
	 *
	 * @author	Matt Mecham
	 * @return	mixed			INT or FALSE if no skin found / required
	 */
	public function fetchSkin()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$useSkinID = FALSE;
		$_keys     = array_keys( $_REQUEST );
	
		//-----------------------------------------
		// OK, viewing a forum or WHAT?
		//-----------------------------------------
	
		if ( count( array_intersect( array( 'showtopic', 'showforum' ), $_keys ) ) OR ( $this->request['module'] == 'post' ) )
		{
			$cache = $this->registry->class_forums->forum_by_id;
			$eff   = intval( $this->request['f'] );
			
			//-----------------------------------------
			// Do we have a skin for a particular forum?
			//-----------------------------------------

			if ( $eff )
			{
				if ( !empty( $cache[ $eff ]['skin_id'] ) )
				{
					$useSkinID = $cache[ $eff ]['skin_id'];
				}
			}
		}
		
		return $useSkinID;
	}
}

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Library: Handle public session data
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @since		12th March 2002
 * @version		$Revision: 10721 $
 */

class publicSessions__forums
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
						
		//-----------------------------------------
    	// FORUM
    	//-----------------------------------------

    	if ( ipsRegistry::$request['module'] == 'forums' AND !empty( ipsRegistry::$request['f'] ) )
    	{
    		$array = array( 
    						 'location_2_type'   => 'forum',
    						 'location_2_id'     => intval(ipsRegistry::$request['f']) );
    	}
    	
    	//-----------------------------------------
    	// TOPIC
    	//-----------------------------------------
    	
    	else if ( ipsRegistry::$request['module'] == 'forums' AND ipsRegistry::$request['section'] == 'topics' AND !empty( ipsRegistry::$request['t'] ) )
    	{
    		$array = array( 
    						 'location_1_type'   => 'topic',
    						 'location_1_id'     => intval(ipsRegistry::$request['t']),
    						 'location_2_type'   => 'forum',
    						 'location_2_id'     => intval(ipsRegistry::$request['f']) );
    	}
    	
    	//-----------------------------------------
    	// POST
    	//-----------------------------------------
    	
    	else if ( ipsRegistry::$request['module'] == 'post' AND !empty( ipsRegistry::$request['f'] ) )
    	{
    		$array = array( 
    			 			 'location_1_type'   => 'topic',
    						 'location_1_id'     => intval(ipsRegistry::$request['t']),
    						 'location_2_type'   => 'forum',
    						 'location_2_id'     => intval(ipsRegistry::$request['f']) );
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
		
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$forums_raw	= array();
		$forums		= array();
		$topics_raw	= array();
		$topics		= array();
		$posts_raw	= array();
		$posts		= array();
		$final		= array();
		
		//-----------------------------------------
		// Extract the topic/forum data
		//-----------------------------------------
		
		foreach( $rows as $row )
		{
			if( $row['current_appcomponent'] != 'forums' OR !$row['current_module'] )
			{
				continue;
			}
			
			if( $row['current_section'] == 'forums' )
			{
				$forums_raw[ $row['location_2_id'] ]	= '';
			}
			else if( $row['current_section'] == 'topics' )
			{
				$topics_raw[ $row['location_1_id'] ]	= $row['location_1_id'];
			}
			else if( $row['current_section'] == 'post' )
			{
				$forums_raw[ $row['location_2_id'] ]	= '';
				$topics_raw[ $row['location_1_id'] ]	= $row['location_1_id'];
			}
		}

		//-----------------------------------------
		// Get the forums, if you dare
		//-----------------------------------------

		ipsRegistry::getAppClass('forums');
		
		if( count($forums_raw) )
		{
			foreach( ipsRegistry::getClass('class_forums')->forum_by_id as $fid => $forum )
			{
				if( isset($forums_raw[$fid]) )
				{
					if( ipsRegistry::getClass( 'permissions' )->check( 'view', $forum ) !== false )
					{
						$forums[ $fid ] = $forum['name'];
					}
				}
			}
		}

		//-----------------------------------------
		// Get the topics, if you care
		//-----------------------------------------
		
		if( count($topics_raw) )
		{
			$_approved	= ipsRegistry::getClass('class_forums')->fetchTopicHiddenQuery( array( 'visible' ), 't.' );

			ipsRegistry::DB()->build( array( 'select'	=> 't.*', 
											 'from'		=> array( 'topics' => 't' ), 
											 'where'	=> $_approved . ' AND t.tid IN(' . implode( ',', $topics_raw ) . ') AND ' . ipsRegistry::getClass('permissions')->buildPermQuery('p'),
											 'add_join'	=> array(
																array(
																		'from'   => array( 'permission_index' => 'p' ),
																		'where'  => "p.perm_type_id=t.forum_id AND p.app='forums' AND p.perm_type='forum'",
																		'type'   => 'left',
																	),
											 					)
									)		);
			$tr = ipsRegistry::DB()->execute();
			
			while( $r = ipsRegistry::DB()->fetch($tr) )
			{
				$_forum	= ipsRegistry::getClass('class_forums')->getForumByid( $r['forum_id'] );
				
				if( count( $_forum ) AND ipsRegistry::getClass('class_forums')->forumsCheckAccess( $_forum['id'], false, 'topic', $r, true ) )
				{
					if( ipsRegistry::getClass( 'permissions' )->check( 'read', $_forum ) !== false )
					{
						if ( $_forum['password'] == '' or IPSMember::isInGroup( ipsRegistry::member()->fetchMemberData(), explode( ',', $_forum['password_override'] ) ) or IPSCookie::get( "ipbforumpass_{$_forum['id']}" ) == md5( $_forum['password'] ) )
						{
							$topics[ $r['tid'] ] = $r['title'];
						}
					}
				}
			}
		}

		//-----------------------------------------
		// Put humpty dumpty together again
		//-----------------------------------------
		
		foreach( $rows as $row )
		{
			if( $row['current_appcomponent'] != 'forums' )
			{
				$final[ $row['id'] ]	= $row;
				
				continue;
			}
		
			if( !$row['current_module'] )
			{
				$row['where_line']		= ipsRegistry::getClass( 'class_localization' )->words['board_index'];
				$final[ $row['id'] ] = $row;
				
				continue;
			}
			
			if( $row['current_section'] == 'forums' )
			{
				if( isset($forums[ $row['location_2_id'] ]) )
				{
					$row['where_line']		= ipsRegistry::getClass( 'class_localization' )->words['WHERE_sf'];
					$row['where_line_more']	= $forums[ $row['location_2_id'] ];
					$row['where_link']		= 'showforum=' . $row['location_2_id'];
					$row['_whereLinkSeo']  = ipsRegistry::getClass('output')->formatUrl( ipsRegistry::getClass('output')->buildUrl( "showforum=".$row['location_2_id'], 'public' ), IPSText::makeSeoTitle( $forums[ $row['location_2_id'] ] ), 'showforum' );
				}
			}
			else if( $row['current_section'] == 'topics' )
			{
				if( isset($topics[ $row['location_1_id'] ]) )
				{
					$row['where_line']		= ipsRegistry::getClass( 'class_localization' )->words['WHERE_st'];
					$row['where_line_more']	= $topics[ $row['location_1_id'] ];
					$row['where_link']		= 'showtopic=' . $row['location_1_id'];
					$row['_whereLinkSeo']  = ipsRegistry::getClass('output')->formatUrl( ipsRegistry::getClass('output')->buildUrl( "showtopic=".$row['location_1_id'], 'public' ), IPSText::makeSeoTitle( $topics[ $row['location_1_id'] ] ), 'showtopic' );
				}
			}
			else if( $row['current_section'] == 'post' )
			{
				if( $row['location_1_id'] )
				{
					if( isset($topics[ $row['location_1_id'] ]) )
					{
						$row['where_line']		= ipsRegistry::getClass( 'class_localization' )->words['WHERE_postrep'];
						$row['where_line_more']	= $topics[ $row['location_1_id'] ];
						$row['where_link']		= 'showtopic=' . $row['location_1_id'];
						$row['_whereLinkSeo']  = ipsRegistry::getClass('output')->formatUrl( ipsRegistry::getClass('output')->buildUrl( "showtopic=".$row['location_1_id'], 'public' ), IPSText::makeSeoTitle( $topics[ $row['location_1_id'] ] ), 'showtopic' );
					}
				}
				else if( $row['location_2_id'] )
				{
					if( isset($forums[ $row['location_2_id'] ]) )
					{
						$row['where_line']		= ipsRegistry::getClass( 'class_localization' )->words['WHERE_postnew'];
						$row['where_line_more']	= $forums[ $row['location_2_id'] ];
						$row['where_link']		= 'showforum=' . $row['location_2_id'];
						$row['_whereLinkSeo']  = ipsRegistry::getClass('output')->formatUrl( ipsRegistry::getClass('output')->buildUrl( "showforum=".$row['location_2_id'], 'public' ), IPSText::makeSeoTitle( $forums[ $row['location_2_id'] ] ), 'showforum' );
					}
				}
			}

			$final[ $row['id'] ]	= $row;
		}
		
		return $final;
	}
}