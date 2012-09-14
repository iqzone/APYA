<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Core extensions
 * Last Updated: $Date: 2011-07-26 09:45:56 -0400 (Tue, 26 Jul 2011) $
 * </pre>
 *
 * @author 		$Author: mark $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @since		1st march 2002
 * @version		$Revision: 9322 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

$_PERM_CONFIG = array( 'Albums' );

class galleryPermMappingAlbums
{
	/**
	 * Mapping array of keys to columns
	 *
	 * @var		$mapping
	 */
	private $mapping = array( 'images'   => 'album_g_perms_view',
							  'post'     => 'album_g_perms_images',
							  'comments' => 'album_g_perms_comments',
							  'moderate' => 'album_g_perms_moderate'
							 );

	/**
	 * Mapping array of keys to names
	 *
	 * @var		$perm_names
	 */
	private $perm_names = array( 'images'   => 'View Images',
								 'post'     => 'Post Images',
								 'comments' => 'Post Comments',
								 'moderate' => 'Moderate'
								);

	/**
	 * Mapping array of keys to background colors for the form
	 *
	 * @var		$perm_colors
	 */
	private $perm_colors = array( 'images'   => '#effff6',
								  'post'     => '#edfaff',
								  'comments' => '#f0f1ff',
								  'moderate' => '#fffaee'
								 );

	/**
	 * If you do not wish to use the main perm table, specify the table here
	 * Please note that you must use the correct 'custom table' fields in $mapping, etc
	 * 
	 * @return	@e array	array( 'table' => $table, 'primaryKey' => $pkey );
	 */
	public function getCustomTable()
	{
		return array( 'table' => 'gallery_albums_main', 'primaryKey' => 'album_id' );
	}
	
	/**
	 * Method to pull the key/column mapping
	 *
	 * @return	@e array
	 */
	public function getMapping()
	{
		return $this->mapping;
	}

	/**
	 * Method to pull the key/name mapping
	 *
	 * @return	@e array
	 */
	public function getPermNames()
	{
		return $this->perm_names;
	}

	/**
	 * Method to pull the key/color mapping
	 *
	 * @return	@e array
	 */
	public function getPermColors()
	{
		return $this->perm_colors;
	}

	/**
	 * Retrieve the items that support permission mapping
	 *
	 * @return	@e array
	 * @todo	[Future 3.2] Expand this function to show the data for global albums editing a permission mask as the callback is implemented
	 */
	public function getPermItems()
	{
		
	}
}

/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Item Marking
 * Last Updated: $Date: 2011-07-26 09:45:56 -0400 (Tue, 26 Jul 2011) $
 * </pre>
 *
 * @author 		$Author: mark $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @version		$Revision: 9322 $
 *
 */

class itemMarking__gallery
{
	/**
	 * Field Convert Data Remap Array
	 * This is where you can map your app_key_# numbers to application savvy fields
	 * 
	 * @var		array
	 */
	private $_convertData = array( 'categoryID' => 'item_app_key_1',
								   'albumID'    => 'item_app_key_2'
								  );
	
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
	protected $cache;
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
		$this->registry   =  $registry;
		$this->DB	      =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang	      =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	  =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
	}

	/**
	 * Convert Data
	 * Takes an array of app specific data and remaps it to the DB table fields
	 *
	 * @param	array
	 * @return	@e array
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
	 * @return	@e integer	Last unread count
	 */
	public function fetchUnreadCount( $data, $readItems, $lastReset )
	{	
		/* Setup */
		$count     = 0;
		$lastItem  = 0;
		$readItems = is_array( $readItems ) ? $readItems : array( 0 );
		
		/* Permissions */
		$approved = ( $this->registry->gallery->helper('albums')->canModerate( $data['albumID'] )  ) ? ' AND approved=1' : '';

		if ( $data['albumID'] )
		{
			$_count = $this->DB->buildAndFetch( array( 
														'select' => 'COUNT(*) as cnt, MIN(idate) as lastItem',
														'from'   => 'gallery_images',
														'where'  => "img_album_id=" . intval( $data['albumID'] ) . " AND id NOT IN(" . implode( ",", array_keys( $readItems ) ) . ") AND idate > " . intval( $lastReset )
												)	);
													
			$count    = intval( $_count['cnt'] );
			$lastItem = intval( $_count['lastItem'] );		
		}

		return array( 'count'    => $count,
					  'lastItem' => $lastItem );
	}
}


/**
 *
 * @class		publicSessions__gallery
 * @brief		Handles public session data for the online list
 */
class publicSessions__gallery
{
	/**
	 * Registry Object Shortcuts
	 *
	 * @var		$registry
	 * @var		$DB
	 * @var		$settings
	 * @var		$request
	 * @var		$lang
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;

	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function __construct()
	{
		/* Make registry shortcuts */
		$this->registry   =  ipsRegistry::instance();
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
	}
						 	
	/**
	 * Return session variables for this application
	 *
	 * current_appcomponent, current_module and current_section are automatically
	 * stored. This function allows you to add specific variables in.
	 *
	 * @return	@e array	Parsed sessions data
	 */
	public function getSessionVariables()
	{
		/* INIT */
		$return_array = array();
		
		if( $this->request['section'] == 'img_ctrl' )
		{
			define( 'NO_SESSION_UPDATE', true );
		}
		else if( $this->request['module'] == 'images' && $this->request['section'] == 'viewimage' )
		{
			$return_array['location_1_type'] = 'image';
			$return_array['location_1_id']   = intval($this->request['image']);
			$return_array['location_2_type'] = 'album';
			$return_array['location_2_id']   = intval($this->request['album']);
		}
		else if( $this->request['module'] == 'albums' )
		{
			$return_array['location_2_type'] = 'album';
			$return_array['location_2_id']   = intval($this->request['album']);
		}

		return $return_array;
	}

	/**
	 * Parse/format the online list data for the records
	 *
	 * @param	array	$rows		Online list rows to check against
	 * @return	@e array	Online list rows parsed
	 */
	public function parseOnlineEntries( $rows )
	{
		/* Load language strings */
		$this->lang = $this->registry->getClass('class_localization');
		$this->lang->loadLanguageFile( array( 'public_location' ), 'gallery' );
		
		/* Let's cache some data... */
		$sessionAlbums = array();
		$sessionImages = array();
		
		foreach( $rows as $session_id => $row )
		{
			if( $row['current_appcomponent'] != 'gallery' )
			{
				continue;
			}
			
			/* Got an image? */
			if( $row['location_1_type'] == 'image' && intval($row['location_1_id']) )
			{
				$_img = intval($row['location_1_id']);
				
				$sessionImages[ $_img ] = $_img;
			}
			/* Got an album! - Skip adding album value for the image as it's already loaded by fetchImages */
			elseif ( $row['location_2_type'] == 'album' && intval($row['location_2_id']) )
			{
				$_alb = intval($row['location_2_id']);
				
				$sessionAlbums[ $_alb ] = $_alb;
			}
		}
		
		/* Load further data from Db */
		$albums = array();
		$images = array();
		
		if ( count($sessionAlbums) OR count($sessionImages) )
		{
			/* Got our main class? */
			if ( !ipsRegistry::isClassLoaded('gallery') )
			{
				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
				$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
			}
						
			$albums = $this->registry->gallery->helper('albums')->fetchAlbumsById( $sessionAlbums );
			$images = $this->registry->gallery->helper('image')->fetchImages( ips_MemberRegistry::instance()->getProperty('member_id'), array( 'imageIds' => $sessionImages ) );			
		}
		
		/* Loop through the session data and set gallery info */
		foreach( $rows as $session_id => $row )
		{
			/* Make sure this is gallery */
			if( $row['current_appcomponent'] == 'gallery' )
			{
				/* Viewing an Image */
				if( $row['location_1_type'] == 'image' && isset($images[ $row['location_1_id'] ]) && $this->registry->getClass('gallery')->helper('image')->validateAccess( $images[ $row['location_1_id'] ], true ) )
				{
					$_img = $images[ $row['location_1_id'] ];
					
					$row['where_line']		= $this->lang->words['gallery_loci_si'];
					$row['where_line_more']	= $_img['caption'];
					$row['where_link']		= 'app=gallery&amp;image='.$_img['id'];
					$row['_whereLinkSeo']	= ipsRegistry::getClass('output')->formatUrl( ipsRegistry::getClass('output')->buildUrl( $row['where_link'], 'public' ), $_img['caption_seo'], 'viewimage' );
				}
				/* Viewing an album */
				elseif ( $row['location_2_type'] == 'album' && isset($albums[ $row['location_2_id'] ]) && $this->registry->getClass('gallery')->helper('albums')->isViewable( $albums[ $row['location_2_id'] ], true ) )
				{
					$_album = $albums[ $row['location_2_id'] ];
					
					$row['where_line']		= $this->lang->words['gallery_loci_album'];
					$row['where_line_more']	= $_album['album_name'];
					$row['where_link']		= 'app=gallery&amp;album=' . $_album['album_id'];
					$row['_whereLinkSeo']	= ipsRegistry::getClass('output')->formatUrl( ipsRegistry::getClass('output')->buildUrl( $row['where_link'], 'public' ), $_album['album_name_seo'], 'viewalbum' );
				}
				/* Viewing the index */
				else
				{
					$row['where_line']		= $this->lang->words['gallery_loci_idx'];
					$row['where_link']		= 'app=gallery';
					$row['_whereLinkSeo']	= ipsRegistry::getClass('output')->formatUrl( ipsRegistry::getClass('output')->buildUrl( $row['where_link'], 'public' ), 'false', 'app=gallery' );
				}
			}
						
			$rows[ $session_id ] = $row;
		}

		return $rows;
	}
}


/**
 * Find ip address extension
 *
 */
class gallery_findIpAddress
{
	/**
	 * Registry
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $registry;
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object	Registry instance
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		$this->registry	= $registry;
	}
	
	/**
	 * Return ip address lookup tables
	 *
	 * @access	public
	 * @return	@e array 	Table lookups
	 */
	public function getTables()
	{
		return array( 'gallery_comments'	=> array( 'author_id', 'ip_address', 'post_date' ) );
	}
}