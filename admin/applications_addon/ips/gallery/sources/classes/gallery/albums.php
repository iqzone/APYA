<?php
/**
 * Library/Album Control
 *
 * Class for handling album stuff
 *
 * @author 		$Author: mmecham $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link			http://www.invisionpower.com
 * @version		$Rev: 10044 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class gallery_albums
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
	protected $cache;
	/**#@-*/
	
	/**
	 * Root node class
	 * Used for fetching single items and what not. Not what. What not.
	 *
	 * @access	private
	 * @var		object of my affection
	 */
	private $_node;
	
	/**
	 * Keep count
	 * 
	 */
	private $_albumCount = 0;
	
	/**
	 * Anymore?
	 * 
	 */
	private $_hasMore = false;
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{ 
		/* Make registry objects */
		$this->registry   =  $registry;
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		
		/* Make sure perm class is ok */
		$this->registry->permissions->setMemberData( $this->memberData );
		
		/* Set up some readable constants */
		define( 'GALLERY_ALBUM_FORCE_LOAD'  , true );
		define( 'GALLERY_ALBUM_BYPASS_PERMS', true );
	}
	
	/**
	 * Returns the album count from a query which asks for it.
	 * Which is anything that passes via _fetchAlbums
	 * @access	public
	 * @return	@e int
	 */
	public function getCount()
	{
		return $this->_albumCount;
	}
	
	/**
	 * Returns the designated member's album
	 * 
	 */
	public function getMembersAlbumId()
	{
		return intval( $this->settings['gallery_members_album'] );
	}
	
	/**
	 * Returns if there were more rows matched. Set in _fetchAlbums
	 * @access	public
	 * @return	@e int
	 */
	public function hasMore()
	{
		return $this->_hasMore;
	}	
	
	/**
	 * Save Damon Albums. Give it an array, and it'll update the fields in the array.
	 * It's WELL good like that
	 *
	 * @access	public
	 * @param	array	array( id => array( ... ) )
	 * @return	@e boolean
	 */
	public function save( array $array )
	{
		$albums  = array();
		$return  = true;
		
		if ( isset( $array['album_id'] ) )
		{
			$array = array( $array['album_id'] => $array );	
		}
		
		foreach( $array as $id => $data )
		{
			/* Got stuff? */
			if ( $id )
			{
				if ( count( $data ) )
				{
					$parentChanged = false;
					
					if ( ! empty( $data['album_name'] ) )
					{
						$data['album_name_seo'] = IPSText::makeSeoTitle( $data['album_name'] );
					}
					
					/* Check to make sure the parents are ok */
					if ( isset( $data['album_parent_id'] ) )
					{
						/* Fetch album */
						$_album = $this->fetchAlbumsById( $id );
						
						/* Changed? */
						if ( $data['album_parent_id'] != $_album['album_parent_id'] )
						{
							try
							{
								$return = $this->registry->gallery->helper('moderate')->isOkToBeAChildOf( $data['album_parent_id'], $data );
								
								if ( $return )
								{
									/* Appropriate checks are performed in the node class */
									$result = $this->_node()->moveNode( $id, $data['album_parent_id'] );
									
									/* Flag as changed for post save update */
									$parentChanged = true;
								}
								
								if ( ! $result || ! $return )
								{
									unset( $data['album_parent_id'] );
								}	
							}
							catch( Exception $error )
							{
								unset( $data['album_parent_id'] );
							}
						}
						
						/* Check we have a proper parent for members */
						if ( !$_album['album_is_global'] && !$data['album_parent_id'] )
						{
							$return = false;
						}
					}
					
					/* All ok? */
					if ( $return )
					{
						/* It's an album! */
						$this->DB->update( 'gallery_albums_main', $data, 'album_id=' . intval( $id ) );
						
						/* reset parents? */
						if ( $parentChanged )
						{
							/* Update */
							$this->registry->gallery->helper('tools')->rebuildTree( $id );
						}
						 
						/* rebuild image permissions */
						if ( isset( $data['album_is_public'] ) OR isset( $data['album_g_perms_view'] ) )
						{
							$this->registry->gallery->helper('moderate')->resetPermissions( $id );
						}
						
						/* We have changed data */
						$this->_node()->dropCache();
					
						/* Re-kitchen-sync */
						$this->resync( $id );
					}
				}
			}
		}
		
		return $return;
	}

	/**
	 * Remove album
	 * @param mixed $album
	 * @return boolean
	 */
	public function remove( $album, $moveToAlbum=array() )
	{
		if ( is_numeric( $album ) )
		{
			$album = $this->fetchAlbum( $album, true );
		}
		
		if ( empty( $album['album_id'] ) )
		{
			return false;
		}
		
		/* Move to album */
		if ( $moveToAlbum )
		{
			if ( is_numeric( $moveToAlbum ) )
			{
				$moveToAlbum = $this->fetchAlbum( $moveToAlbum );
			}
		}
		
        /* Fetch Images */
		$images   = $this->registry->gallery->helper('image')->fetchAlbumImages( $album['album_id'] );
		$children = $this->fetchAlbumsByFilters( array( 'album_parent_id' => $album['album_id'], 'bypassPermissionChecks' => true ) );
		$parents  = $this->fetchAlbumParents( $album['album_id'] );
		
		if ( ! empty( $moveToAlbum['album_id'] ) )
		{		
			/* Move children albums */
			if ( count( $children ) )
			{
				/* Update childrens */
				$this->DB->update( 'gallery_albums_main', array( 'album_parent_id' => $moveToAlbum['album_id'] ), 'album_parent_id=' . $album['album_id'] );
			
				foreach( $children as $age => $shoesize )
				{
					$this->_node()->moveNode( $shoesize['album_id'], $moveToAlbum['album_id'] );		
				}
			}
			
			/* Move the imagiz */
			$images = $this->registry->gallery->helper('image')->fetchAlbumImages( $album['album_id'] );
			
			try
			{
				if ( count( $images ) )
				{
					$result = $this->registry->gallery->helper('moderate')->moveImages( array_keys( $images ), $moveToAlbum['album_id'] );
				}
			}
			catch( Exception $error )
			{
				throw new Exception( $error->getMessage() );
			}
		}
		else
		{
			/* Remove the images */
			$this->registry->gallery->helper('moderate')->deleteImages( $images );
		}
		
		/* Remove album */
        $this->_node()->deleteNode( $album['album_id'] );
        
		/* Rebuild tree */
		if ( count( $children ) )
		{
			foreach( $children as $age => $shoesize )
			{
				$this->registry->gallery->helper('tools')->rebuildTree( $shoesize['album_id'] );			
			}
		}
		
		
		/* Sort tree */
		if ( count( $parents ) )
		{
			foreach( $parents as $bed => $now )
			{
				/* Update */
				$this->registry->gallery->helper('tools')->rebuildTree( $now['album_id'] );
			}
		}
			
        /* See if we have a directory for this album specifically */
        $dir = $this->settings['gallery_images_path'] . '/gallery/album_' . $album['album_id'];
        
        if ( is_dir( $dir ) )
        {
        	$files = @scandir( $dir );

        	/* Only remove if empty. When we move images we don't always move physical files */
        	if ( count( $files ) <= 2 )
        	{
        		@unlink( $dir );
        	}
        }
        
        /* Rebuild stats */
		$this->registry->gallery->rebuildStatsCache();
		
		/* Rebuild has_gallery */
		$this->registry->gallery->resetHasGallery( $album['album_owner_id'] );
		
		return true;
	}
	
	/**
	 * Basic accessor methods: Fetch album by ID
	 *
	 * @access	public
	 * @param	mixed		INT = Album ID, array = many IDs
	 * @param	boolean		Force fresh load or not
	 * @return	@e array
	 */
	public function fetchAlbumsById( $id, $forceLoad=false )
	{
		$singleOrder = is_numeric( $id ) ? true : false;
		$_returnData = array();
		
		/* Got valid IDs? */
		if ( ! is_numeric( $id ) AND ( ! is_array( $id ) OR ! count( $id ) ) )
		{
			return array();
		}
		
		static $albumsCache = array();
		
		/* Check our cache */
		if ( ! $forceLoad )
		{
			if ( $singleOrder )
			{
				/* Found it? */
				if ( isset($albumsCache[ $id ]) )
				{
					return $albumsCache[ $id ];
				}
			}
			else
			{
				$_new_ids =array();
				
				foreach( $id as $_ci )
				{
					/* Found it? */
					if ( isset($albumsCache[ $_ci ]) )
					{
						$_returnData[ $_ci ] = $albumsCache[ $_ci ];
					}
					else
					{
						/* Need to load that one */
						$_new_ids[] = $_ci;
					}
				}
				
				if ( count($_new_ids) )
				{
					$id = $_new_ids;
				}
				else
				{
					return $_returnData;
				}
			}
		}
		
		/* Init */
		$joins  = array( array( 'select' => 'i.*',
							    'from'   => array( 'gallery_images' => 'i' ),
							    'where'  => 'i.id=a.album_cover_img_id',
							    'type'   => 'left' ),
						 array( 'select' => 'm.members_display_name, m.members_seo_name',
							    'from'   => array( 'members' => 'm' ),
							    'where'  => 'i.member_id=m.member_id',
							    'type'   => 'left' ),
						 array( 'select' => 'mx.members_display_name as owners_members_display_name, mx.members_seo_name as owners_members_seo_name',
							    'from'   => array( 'members' => 'mx' ),
							    'where'  => 'a.album_owner_id=mx.member_id',
							    'type'   => 'left' ) );
		
		/* Rating */
		$join = $this->registry->gallery->helper('rate')->getTableJoins( 'a.album_id', 'album', $this->memberData['member_id'] );
		
		if ( $join !== false && is_array( $join ) )
		{
			array_push( $joins, $join );
		}
	
		/* Set joins */
		$this->_node()->setJoins( $joins );
											    
		/* Set where */
		$this->_node()->setWhere();
		
		/* Set where */
		$this->_node()->setTblPrefix( 'a' );
		
		if ( $forceLoad !== false )
		{
			$this->_node()->dropCache();
		}
		
		$albums = $this->_node()->fetchNodeContent( $id );
		
		if ( $singleOrder )
		{
			/* Cache time */
			$albumsCache[ $id ] = $this->_setUpAlbum( $albums );
			return $albumsCache[ $id ];
		}
		else
		{
			if ( !count($albums) )
			{
				return $_returnData;
			}
			
			foreach( $albums as $id => $album )
			{
				/* Cache time */
				$albumsCache[ $id ] = $this->_setUpAlbum( $album );
				$_returnData[ $id ] = $albumsCache[ $id ];
			}
			
			return $_returnData;
		}
	}
	
	/**
	 * Gets all the albums for the specified member id
	 * 
	 * @access	public
	 * @param	integer	$memberId
	 * @return	@e void
	 */		
	public function fetchAlbumsByOwner( $memberId, $filters=array() )
	{
		$memberId = intval( $memberId );
											    
		/* Set where */
		$filters['album_owner_id'] = $memberId;

		if ( ! isset( $filters['isViewable'] ) AND ! isset( $filters['isUploadable'] ) )
		{
			$filters['isViewable'] = 1;
		}
		
		return $this->fetchAlbumsByFilters( $filters );
	}
	
	/**
	 * Gets all the global albums 'you' have permission to view
	 * 
	 * @access	public
	 * @param	array	Filters
	 * @return	@e void
	 */		
	public function fetchGlobalAlbums( $filters=array() )
	{
		$filters['album_is_global'] = 1;
		
		if ( ! isset( $filters['isViewable'] ) AND ! isset( $filters['isUploadable'] ) )
		{
			$filters['isViewable'] = 1;
		}
		
		if ( ! isset( $filters['parentId'] ) )
		{
			$filters['parentId'] = 0;
		}
		
		/* Central processing function. CPF for short. */
		return $this->fetchAlbumsByFilters( $filters );
	}
	
	/**
	 * Fetches albums by filters
	 * Options options:
	 * album_id				- INT or ARRAY matches these album ids
	 * album_parent_id		- INT or ARRAY of parent IDS
	 * album_is_global		- 0 or 1 
	 * sortKey				- DB row to sort on
	 * sortOrder			- ASC OR DESC but never ASK
	 * isUploadable			- Returns only items that you have permission to upload into
	 * isViewable			- Returns only items that you have permission to view
	 * limit				- Return X matches (SQL limit)
	 * offset				- Offset by X (SQL offset)
	 * checkForMore			- Works in conjunction with LIMIT, check with (this)->hasMore() [boolean]
	 * unixCutOff			- Returns albums last updated > unixtime
	 * albumNameContains	- Returns albums that contain STRING
	 * album_owner_id		- Returns albums owned by INT
	 * getTotalCount		- BOOLEAN - performs a COUNT(*) first and stores in $this->getCount()
	 * skip					- INT or ARRAY of album_ids to skip
	 * getChildrenCount		- BOOLEAN - returns a count of children of each returned match
	 * nestDescendants		- BOOLEAN - nests data into array ['__children__'] before returning
	 * getAlbumTableResultsOnly - BOOLEAN - fetches results from gallery_albums_main only
	 * getFields				- Specify which fields to select (use a.* when getAlbumTableResultsOnly is TRUE, otherwise use plain table fields)
	 * getParents				- Returns parents in the data set also.
	 * moderatingData (array)   - array including 'action', 'owner_id', 'moderator', 'album_id'
	 * hasSamePermsAs			- INT or ARRAY matches all albums with at least the passed in album array
	 * getGlobalAlbumLatestImgs - INT - Returns imgs from the stored album_g_latest_imgs array (true returns all, numeric returns the numeric)
	 * 
	 * @access	public
	 * @param	array	Filters ('memberData', 'returnTree', 'isViewable', 'isUploadable' are custom for this function, 'skip' will skip that node and all children)
	 * @return	@e void
	 */		
	public function fetchAlbumsByFilters( $filters=array(), $byPassPermissionChecks=false )
	{
		/* Set filters */
		$filters = $this->_setFilters( $filters );
		$where   = array();
		
		/* Album id? */
		if ( isset( $filters['album_id'] ) )
		{
			$_as = ( ! is_array( $filters['album_id'] ) ) ? array( $filters['album_id'] ) : $filters['album_id'];
			
			$where[] = "a.album_id IN (" . implode( ',', IPSLib::cleanIntArray( $_as ) ) . ")";
		}
		
		/* Album id? */
		if ( isset( $filters['album_parent_id'] ) )
		{
			$_as = ( ! is_array( $filters['album_parent_id'] ) ) ? array( $filters['album_parent_id'] ) : $filters['album_parent_id'];
			
			$where[] = "a.album_parent_id IN (" . implode( ',', IPSLib::cleanIntArray( $_as ) ) . ")";
		}
		
		/* Central processing function. CPF for short. */
		return $this->_fetchAlbums( $where, $filters, $byPassPermissionChecks );
	}
	
	/**
	 * Basic accessor methods: Fetch album's parents
	 *
	 * @param	mixed		$id			Album ID or array
	 * @param	boolean		$forceLoad	Skips loading the data from the cache
	 * @param	boolean		$skipSetUp  Skips calling _setUpAlbum()
	 * @return	@e array
	 */
	public function fetchAlbumParents( $id, $forceLoad=false, $skipSetUp=false )
	{
		/* Retrieve ID from an array */
		if ( ! is_numeric( $id ) AND is_array( $id ) )
		{
			if ( empty($id['album_parent_id']) )
			{
				return array();
			}
			else
			{
				$id = intval( $id['album_id'] );
			}
			
		}
		
		static $parentsCache = array();
		
		/* Got it cached? */
		if ( ! isset( $parentsCache[ $id ] ) || $forceLoad === true )
		{
			/* Init */
			$parents = array();
			
			/* Prep */
			$this->_node()->fetchParents( $id );
			
			/* Pull */
			while( $p = $this->_node()->nextRow() )
			{
				if ( $p['album_id'] == $id )
				{
					continue;
				}
				
				$parents[ $p['album_id'] ] = ( $skipSetUp === true ) ? $p : $this->_setUpAlbum( $p );
			}
			
			/* Self heal */
			foreach( $parents as $p => $data )
			{
				if ( $data['album_parent_id'] && ( ! in_array( $data['album_parent_id'], array_keys( $parents ) ) || ( $data['album_parent_id'] == $data['album_id'] ) || ! $data['album_id'] ) )
				{
					$newParent = ( $this->isGlobal( $data ) ? 0 : $this->getMembersAlbumId() );
					
					$this->DB->update( 'gallery_albums_main', array( 'album_parent_id' => $newParent, 'album_node_left' => 0, 'album_node_right' => 0, 'album_node_level' => 0 ), 'album_id=' . $data['album_id'] );
					
					$this->registry->gallery->helper('tools')->rebuildTree( $data['album_id'] );
					$this->rebuildNodeTree();
					
					unset( $parents[ $p ] );
				}
			}
			
			$parentsCache[ $id ] = array_reverse( $parents, true );
		}
		
		/* Return */
		return $parentsCache[ $id ];
	}

	
	/**
	 * Basic accessor methods: Fetch album's children
	 * Options options:
	 * immediateDescendants - only returns immediate descendants but you could have guessed that
	 * firstLevel			- only returns (all|global|members) for the root node
	 * nestDescendants		- Nests the childrens into ['_children_'] array of parent
	 * limitDescendants		- Limits the nestDescendant matches to x items
	 * sortKey				- DB row to sort on
	 * sortOrder			- ASC OR DESC but never ASK
	 * isUploadable			- Returns only items that you have permission to upload into
	 * isViewable			- Returns only items that you have permission to view
	 * limit				- Return X matches (SQL limit)
	 * offset				- Offset by X (SQL offset)
	 * isUsed				- Has images inside or sub-albums
	 *
	 * @access	public
	 * @param	mixed		INT = Album ID or array of album data
	 * @param	array		Options array: 'immediateDescendants' only returns immediate children, 'firstLevel' (all|global|members) 'nestDescendants', nests descendants (true, unlimited, numeric to that level ) into '__children__' => array( ... )
	 * @return	@e array
	 */
	public function fetchAlbumChildren( $album, $options=array() )
	{
		/* init */
		$warray = array();
		$where  = null;
		
		/* Test */
		if ( is_numeric( $album ) )
		{
			if ( $album >= 1 )
			{
				$album = $this->fetchAlbumsById( $album );
			}
			else
			{
				/* We want children of 'root' */
				$album = array( 'album_node_level' => -1,
								'album_id'         => 0 );
			}
		}
	
		/* Init */
		$children         = array();
		$nested           = array();
		$level            = $album['album_node_level'] + 1;
		$nestPlease 	  = isset($options['nestDescendants']) ? $options['nestDescendants'] : false;
		$firstLevel 	  = ( isset($options['firstLevel']) AND $album['album_id'] == 0 ) ? $options['firstLevel'] : false;
		$limitDescendants = isset($options['limitDescendants']) ? $options['limitDescendants'] : false;
		$isUsed           = isset($options['isUsed']) ? $options['isUsed'] : false;
		$counter		  = array();
		$seen             = 0;
		
		/* Custom where? */
		if ( $firstLevel == 'global' )
		{
			$warray[] = "( (a.album_is_global=1 AND a.album_parent_id=0) OR (a.album_parent_id > 0) )";
		}
		
		/* Only get up to X deep? */
		if ( $nestPlease AND is_numeric( $nestPlease ) )
		{
			$warray[] = "(a.album_node_level <= " . ( $level + $nestPlease ) . ")";
		}
		
		if ( ! $nestPlease && !empty( $options['immediateDescendants'] ) )
		{
			$warray[] = "a.album_parent_id=" . $album['album_id'];			
		}
		
		/* Set limit */
		if ( ! $nestPlease AND ( ! empty( $options['offset'] ) OR ! empty( $options['limit'] ) ) )
		{
			$this->_node()->setLimit( array( intval($options['offset']), intval($options['limit'] ) ) );
		}
		else
		{
			/* Reset */
			$this->_node()->setLimit( null );
		}
		
		if ( ! $nestPlease && $isUsed )
		{
			$warray[] = "(a.album_count_imgs > 0)";
		}
		
		/* Permission filters */
		$_w = $this->_processPermissionFilters( $options );
		
		if ( $_w )
		{
			$warray[] = $_w;
		}
		
		if ( count( $warray ) )
		{
			$where = implode( ' AND ', $warray );
		}
		
		/* Flush any node sorting */
    	$this->_node()->setOrder(null);
    	$this->_node()->setWhere( $where );
    	
    	/* Set table prefix */
		$this->_node()->setTblPrefix( 'a' );
		
		$joins  = array( array( 'select' => 'i.*',
							    'from'   => array( 'gallery_images' => 'i' ),
							    'where'  => 'i.id=a.album_cover_img_id',
							    'type'   => 'left' ),
						 array( 'select' => 'm.member_id as owner_id, m.members_display_name, m.members_seo_name, m.member_group_id',
							    'from'   => array( 'members' => 'm' ),
							    'where'  => 'a.album_owner_id=m.member_id',
							    'type'   => 'left' ) );
										 
		/* Rating */
		$join = $this->registry->gallery->helper('rate')->getTableJoins( 'a.album_id', 'album', $this->memberData['member_id'] );
		
		if ( $join !== false && is_array( $join ) )
		{
			array_push( $joins, $join );
		}
		
    	/* Set joins */
		$this->_node()->setJoins( $joins );
		
		/* Prep */
		$this->_node()->fetchChildren( $album['album_id'] );
		
		/* Pull */
		while( $p = $this->_node()->nextRow() )
		{
			if ( $p['album_id'] == $album['album_id'] )
			{
				continue;
			}
			
			/* Empty album? Fix the member ID */
			if ( empty($p['member_id']) && !empty($p['owner_id']) )
			{
				$p['member_id'] = $p['owner_id'];
			}
			
			/* Level up? */
			if ( $firstLevel && $firstLevel != 'all' )
			{
				if ( $p['album_node_level'] - $level == 0 )
				{
					if ( $firstLevel == 'global' )
					{
						if ( ! $this->isGlobal( $p ) )
						{
							continue;
						}
					}
					else if ( $firstLevel == 'members' )
					{
						if ( $this->isGlobal( $p ) )
						{
							continue;
						}
					}
				}	
			}
			
			/* re-test permissions to be sure */
			if ( ! empty( $options['isViewable'] ) && empty( $options['bypassPermissionChecks'] ) )
			{
				if ( ! $this->isViewable( $p ) )
				{
					continue;
				}
			}
			
			/* re-test permissions to be sure */
			if ( ! empty( $options['isUploadable'] ) && empty( $options['bypassPermissionChecks'] ) )
			{
				if ( ! $this->isUploadable( $p ) )
				{
					continue;
				}
			}			
			
			/* Nesting with offset or limit ? */
			if ( ( $p['album_node_level'] - $level == 0 ) AND $nestPlease AND ( ! empty( $options['offset'] ) OR ! empty( $options['limit'] ) ) )
			{
				$seen++;
				 
				if ( ! empty( $options['offset'] ) AND $seen <= $options['offset'] )
				{
					continue;
				}
				else if ( ! empty( $options['limit'] ) AND ( ( $seen - 1 ) - $options['offset'] ) >= $options['limit'] )
				{
					if ( isset( $options['getTotalCount'] ) )
					{
						continue;
					}
					else
					{
						break;
					}
				}
			}
			
			/* Set up thumbies and that */
			$p = $this->_setUpAlbum( $p );
			
			$children[ $p['album_id'] ] = $p;		
		}
		
		/* Get total count */
		if ( ! empty( $options['getTotalCount'] ) )
		{
			$this->_albumCount = $seen;
		}
		
		/* Post process */
		if ( $nestPlease )
		{
			$children = $this->_nestDescendants( $children, $album, $options );
		}
		
		/* Did we want to sort? if so, then we do it after everything else */
		if ( ! empty( $options['sortKey'] ) )
		{
			$options   = $this->_setFilters( $options );
			$_children = $children;
			$_foster   = array();
			$children  = array();
			
			/* Do stuff that doesn't deserve a comment */
			foreach( $_children as $id => $data )
			{
				$_foster[ $data['album_last_img_date'] . '.' . $id ] = $data;
			}
			
			if ( $options['sortOrder'] == 'desc' )
			{
				krsort( $_foster );
			}
			else
			{
				ksort( $_foster );
			}
		
			/*  Now replace */
			foreach( $_foster as $tmp => $data )
			{
				$children[ $data['album_id'] ] = $data;
			}
		}
			
		return $children;
	}
	
	/**
	 * Returns an array of album data
	 *
	 * @access	public
	 * @param	integer	$id
	 * @param	bool	[$bypass]
	 * @return	@e array
	 */
	public function fetchAlbum( $albumId, $bypass=false )
	{
		$albumId = intval($albumId);
		$album   = $this->fetchAlbumsById( $albumId );
		
		/* Bypass permission checks */
		if ( ! $bypass )
		{
			/* Album exists? */
			if ( ! $album['album_id'] )
			{
				$this->registry->output->showError( 'gallery_404', 107141.1, null, null, 404 );
			}
			
			if ( $this->isGlobal( $album ) === true )
			{
				if ( ! $this->isViewable( $album ) )
				{
					$this->registry->output->showError( 'no_permission', 107141, null, null, 403 );
				}
			}
			else if ( $this->isPublic( $album ) !== true )
			{
				if ( ! ( $this->isOwner( $album ) OR $this->canModerate() ) )
				{
					/* Friend Only Check */
					if ( $this->isFriends( $album ) )
					{
						if ( ! IPSMember::checkFriendStatus( $album['album_owner_id'], 0, true ) )
						{
							$this->registry->output->showError( 'no_permission', 107140, null, null, 403 );
						}
					}
					else
					{
						$this->registry->output->showError( 'no_permission', 107140, null, null, 403 );
					}
				}
			}
		}
		
		return $album;
	}
	
	/**
	 * Method to get parents as nav string This > That > Me
	 * @param	Mixed	Either array (album array) or int (album id)
	 * @param	Boolean	If no parent, return < ROOT > false, return nothing
	 * @param	String	Separator
	 */
	public function getParentsAsTextString( $album, $addCurrentAlbum=true, $sep=' &rarr; '  )
	{
		if ( is_numeric( $album ) )
		{
			$album = $this->fetchAlbumsById( $album );
		}
		
		$parentText = '';
		
		if ( $album['album_parent_id'] )
    	{
    		$parents = $this->fetchAlbumParents( $album );
    			
    		$parents = array_reverse( $parents, true );
    			
    		foreach( $parents as $id => $parent )
    		{
    			$parentText .= $parent['album_name'] . $sep;
    		}
    		
    		if ( $addCurrentAlbum )
    		{
    			$parentText .= $album['album_name'];
    		}
    	}
    	else
    	{
    		if ( $addCurrentAlbum )
    		{
    			$parentText .= $album['album_name'];
    		}
    	}
    	
    	return $parentText;
	}
	
	/**
	 * Determines if the user owns the album
	 *
	 * @access	public
	 * @param	Mixed	Either array (album array) or int (album id)
	 * @param	Mixed	Either array (memberData array) or int (member id)
	 * @return	@e bool
	 */
	public function isOwner( $album, $member=null )
	{
		if ( is_numeric( $album ) )
		{
			$album = $this->fetchAlbumsById( $album );
		}
		
		if ( $member !== null )
		{
			if ( is_numeric( $member ) )
			{
				$member = IPSMember::load( $member, 'core' );
			}
		}
		else
		{
			$member = $this->memberData;
		}
		
		if ( isset( $member['member_id'] ) AND isset( $album['album_id'] ) AND isset( $album['album_owner_id'] ) )
		{
			if ( $member['member_id'] and $member['member_id'] == $album['album_owner_id'] )
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			trigger_error( "Incorrect data sent to gallery_albums::isOwner" );
		}
	}
	
	/**
	 * Determines if the album is global
	 *
	 * @access	public
	 * @param	Mixed	Either array (album array) or int (album id)
	 * @return	@e bool
	 */
	public function isGlobal( $album )
	{
		if ( is_numeric( $album ) )
		{
			$album = $this->fetchAlbumsById( $album );
		}
			
		if ( is_array( $album ) AND isset( $album['album_is_global'] ) )
		{
			return ( $album['album_is_global'] ) ? true : false;
		}
		else
		{
			return null;
		}
	}
	
	/**
	 * Determines if the album is empty not
	 *
	 * @access	public
	 * @param	Mixed	Either array (album array) or int (album id)
	 * @return	@e bool
	 */
	public function isEmpty( $album )
	{
		if ( is_numeric( $album ) )
		{
			$album = $this->fetchAlbumsById( $album );
		}
			
		if ( is_array( $album ) AND isset( $album['album_is_global'] ) )
		{
			return ( ( $album['album_count_imgs'] + $album['album_count_imgs_hidden'] ) < 1 ) ? true : false;
		}
		else
		{
			return null;
		}
	}
	
	/**
	 * Determines if the album is private
	 *
	 * @access	public
	 * @param	Mixed	Either array (album array) or int (album id)
	 * @return	@e bool
	 */
	public function isPrivate( $album )
	{
		if ( is_numeric( $album ) )
		{
			$album = $this->fetchAlbumsById( $album );
		}

		if ( $this->isGlobal( $album ) )
		{
			return false;
		}
		
		if ( is_array( $album ) AND isset( $album['album_is_public'] ) )
		{
			return ( $this->fetchPrivacyFlag( $album ) == 'private' ) ? true : false;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Determines if the album is public
	 *
	 * @access	public
	 * @param	Mixed	Either array (album array) or int (album id)
	 * @return	@e bool
	 */
	public function isPublic( $album )
	{
		if ( is_numeric( $album ) )
		{
			$album = $this->fetchAlbumsById( $album );
		}
			
		if ( is_array( $album ) AND isset( $album['album_is_public'] ) )
		{
			return ( $this->fetchPrivacyFlag( $album ) == 'public' ) ? true : false;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Determines if the album is uploadble by $this->memberData
	 *
	 * @access	public
	 * @param	Mixed	Either array (album array) or int (album id)
	 * @return	@e bool
	 */
	public function isUploadable( $album )
	{
		/* Can we upload at all? */
		if ( ! $this->registry->gallery->getCanUpload() )
		{
			return false;
		}
		
		if ( is_numeric( $album ) )
		{
			$album = $this->fetchAlbumsById( $album );
		}
		
		/* Got an album? */
		if ( ! $album['album_id'] )
		{
			return false;
		}
		
		/* Global album? */
		if ( $this->isGlobal( $album ) )
		{
			if ( $this->fetchAlbumContainerFlag( $album ) == 'albums' )
			{
				return false;
			}
			
			if ( $this->registry->permissions->check( 'post', $this->_permify( $album ) ) )
			{
				return true;
			}
		}
		else
		{
			if ( $this->isOwner( $album ) )
			{
				return true;
			}
		}
		
		return false;
	}
		
	/**
	 * Determines if the album is friends only
	 *
	 * @access	public
	 * @param	Mixed	Either array (album array) or int (album id)
	 * @return	@e bool
	 */
	public function isFriends( $album )
	{
		if ( is_numeric( $album ) )
		{
			$album = $this->fetchAlbumsById( $album );
		}
			
		if ( is_array( $album ) AND isset( $album['album_is_public'] ) )
		{
			return ( $this->fetchPrivacyFlag( $album ) == 'friend' ) ? true : false;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Checks to see if this is a container only
	 *
	 * @return	@e bool
	 */
	public function isContainerOnly( $album )
	{
		/* Check album */
		if ( is_numeric( $album ) )
		{
			$album = $this->fetchAlbumsById( $album );
		}
		
		if ( $this->isGlobal( $album ) )
		{
			return $album['album_g_container_only'] ? true : false;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Determines if the album is viewable by parents
	 *
	 * @param	Mixed	Either array (album array) or int (album id)
	 * @param	Boolean	Return true/false or throw output error
	 * @param	Mixed	Either array (member data ) or int (member id ) or nothing (memberData)
	 * @return	@e bool
	 */
	public function isViewable( $album, $inlineError=false, $member=null )
	{
		/* Got a member to load? */
		if ( is_numeric( $member ) )
		{
			$member = IPSMember::load( $member, 'all' );
		}
		/* No member data? Fallback on logged in user */
		elseif ( ! is_array($member) OR ! isset($member['member_id']) )
		{
			$member = $this->memberData;
		}
		
		if ( is_numeric( $album ) )
		{
			$album = $this->fetchAlbumsById( $album );
		}
		
		/* got an album at all? */
		if ( ! $album['album_id'] )
		{
			if ( $inlineError )
			{
		  		$this->registry->output->showError( $this->lang->words['4_no_album'], 'class-albums-viewable-0', null, null, 403 );
			}
			else
			{
				return false;
			}
		}
		
		/* Moderator? */
		if ( $this->canModerate( $album, $member ) )
		{
			return true;
		}
		
		/* Non global album */
		if ( $this->isGlobal( $album ) !== true )
		{
			/* Our album? */
			if ( $this->isOwner( $album, $member ) )
			{
				return true;
			}
			else if ( $this->isPublic( $album ) )
			{ 
				/* Could be a child of an album */
				if ( empty( $album['album_g_perms_view'] ) OR $this->registry->permissions->check( 'view', $this->_permify( $album ) ) )
				{
					return true;
				}
				else
				{
					if ( $inlineError )
					{
						$this->registry->output->showError( 'no_permission', 107142, null, null, 403 );
					}
					else
					{
						return false;
					}
				}
			}
			else if ( $this->isPrivate( $album ) )
			{
				if ( ! $this->isOwner( $album, $member ) )
				{
					if ( $inlineError )
					{
						$this->registry->output->showError( 'no_permission', 107142, null, null, 403 );
					}
					else
					{
						return false;
					}
				}
				else
				{
					return true;
				}
			}
			else if ( $this->isFriends( $album ) AND IPSMember::checkFriendStatus( $album['album_owner_id'], 0, true ) )
			{
				return true;
			}
		}
		else
		{
			if ( $this->registry->permissions->check( 'view', $this->_permify( $album ) ) )
			{
				return true;
			}
			else
			{
				if ( $inlineError )
				{
					$this->registry->output->showError( 'no_permission', 107142, null, null, 403 );
				}
				else
				{
					return false;
				}
			}
		}
			
		if ( $inlineError )
		{
			$this->registry->output->showError( 'no_permission', 107142, null, null, 403 );
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Rebuilds the album data (counts, last img, etc)
	 *
	 * @param	Mixed	Either array (album array) or int (album id)
	 * @return	@e array
	 */
	public function resync( $album )
	{
		$image   	 = array();
		$saveIds 	 = '';
		$rebuildTree = false;
		
		if ( is_numeric( $album ) )
		{
			$album = $this->fetchAlbumsById( $album, GALLERY_ALBUM_FORCE_LOAD );
		}		
		
		/* Global counts: Approved */
		$stats	= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as images, SUM(comments) as comments, SUM(comments_queued) as comments_queued',
												   'from'   => 'gallery_images',
												   'where'  => "img_album_id=" . $album['album_id'] . " AND approved=1" ) );
		
		/* Global counts: Unapproved */
		$modque	= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as modimages, SUM(comments_queued) as comments_queued',
												   'from'   => 'gallery_images',
												   'where'  => "img_album_id=" . $album['album_id'] . " AND approved=0" ) );
												   
		/* Last image data */
		$last	= $this->DB->buildAndFetch( array( 'select' => 'id, idate',
												   'from'   => 'gallery_images',
												   'where'  => "img_album_id=" . $album['album_id'] . " AND approved=1",
												   'order'  => 'idate DESC',
												   'limit'  => array(1) ) );
		
		/* Make sure cover image is still OK */
		if ( $album['album_cover_img_id'] && ! $this->isGlobal( $album ) )
		{
			$img = $this->registry->gallery->helper('image')->fetchImage( $album['album_cover_img_id'], true );
			
			if ( ! $img['id'] OR ( $img['img_album_id'] != $album['album_id'] ) )
			{
				/* Image moved or deleted */
				$album['album_cover_img_id'] = 0;
			}
		}
		
		/* If no cover image attached, grab the first one */
		if ( ! $album['album_cover_img_id'] OR $this->isGlobal( $album ) )
		{
			$_albums = array( $album['album_id'] );
			$_i      = array();
				
			if ( ( $this->isGlobal( $album ) && in_array( $this->fetchAlbumContainerFlag( $album ), array( 'albums', 'all', 'images' ) ) ) || ( $this->isPublic( $album ) AND ! $album['album_count_imgs'] AND count( $album['album_child_tree'] ) ) )
			{
				$_albums = array_merge( $_albums, array_keys( $this->fetchAlbumChildren( $album['album_id'], array( 'limit' => 250, 'hasSamePermsAs' => $album ) ) ) );
			}
			
			$_i = $this->registry->gallery->helper('image')->fetchImages( 0, array( 'albumId' => $_albums, 'limit' => 10, 'sortKey' => $album['album_sort_options__key'], 'sortOrder' => $album['album_sort_options__dir'] ), GALLERY_IMAGE_BYPASS_PERMS );
			
			$saveIds = serialize( array_keys( $_i ) );
			$image   = array_shift( $_i );
			
			if ( ! isset( $image['id'] ) )
			{
				$image['id'] = 0;
			}
		}
		
		
		/* No images? reset cover id */
		//if ( ( ! $stats['images'] AND ! $this->isGlobal( $album ) && ! $this->isContainerOnly( $album ) )
		//{ 
		//	$album['album_cover_img_id'] = 0;
		//	$image['id']				 = 0;
		//}
		
		$save   = array( 'album_count_imgs'		 		 => intval( $stats['images'] ),
						 'album_count_comments'		 	 => intval( $stats['comments'] ),
						 'album_count_imgs_hidden'	 	 => intval( $modque['modimages'] ),
						 'album_count_comments_hidden'	 => intval( $stats['comments_queued'] ) + intval( $modque['comments_queued'] ),
		 				 'album_name_seo'				 => ( $album['album_name_seo'] ) ? $album['album_name_seo'] : IPSText::makeSeoTitle( $album['album_name'] ),
						 'album_last_img_id'		     => intval( $last['id'] ),
						 'album_cover_img_id'			 => ( isset( $image['id'] ) ) ? intval( $image['id'] ) : $album['album_cover_img_id'],
						 'album_last_img_date'	 		 => $last['idate'] ? $last['idate'] : 0,
						 'album_g_latest_imgs'			 => $saveIds );
	 	
	 	/* Resync up the chain */
	 	$parents = $this->fetchAlbumParents( $album, true, true );
	 	
	 	$this->DB->update( 'gallery_albums_main', $save, 'album_id=' . $album['album_id'] );
	 	
	 	if ( is_array( $parents ) AND count( $parents ) )
	 	{
	 		foreach( $parents as $id => $data )
	 		{
	 			if ( $this->isGlobal( $data ) )
	 			{
	 				$this->resync( $data );
	 			}
	 		}
	 	}
	 	
	 	return $save;
	}

	/**
	 * Checks the parent tree for global album permissions
	 *
	 * @access	public
	 * @return	@e bool
	 */
	public function isGlobalParentViewable( $album )
	{
		/* Check album */
		if ( is_numeric( $album ) )
		{
			$album = $this->fetchAlbumsById( $album );
		}
		
		if ( ! $this->registry->permissions->check( 'view', $this->_permify( $album ) ) )
		{
			return false;
		}
					
		/* Fetch parents */
		$parents = $this->fetchAlbumParents( $album['album_id'] );
		
		/* Loop through and check for global albums */
		if ( is_array( $parents ) AND count( $parents ) )
		{
			foreach( $parents as $pid => $_album )
			{
				if ( $this->isGlobal( $_album ) )
				{
					if ( ! $this->registry->permissions->check( 'view', $this->_permify( $_album ) ) )
					{
						return false;
					}
				}
			}
		}
		
		return true;
	}
	
	/**
	 * Determines if the album is creatable by $this->memberData
	 *
	 * @access	public
	 * @param	Mixed	Either array (album array) or int (album id)
	 * @return	@e bool
	 */
	public function canCreateSubAlbumInside( $album )
	{
		/* Can we upload at all? */
		if ( ! $this->registry->gallery->getCanUpload() )
		{
			return false;
		}
		
		if ( is_numeric( $album ) )
		{
			if ( $album == 0 )
			{
				return true;
			}
			
			$album = $this->fetchAlbumsById( $album );
		}
		
		/* Got an album? */
		if ( ! $album['album_id'] )
		{
			return false;
		}
				
		/* Global album? */
		if ( $this->isGlobal( $album ) )
		{
			if ( $this->registry->permissions->check( 'post', $this->_permify( $album ) ) )
			{
				return true;
			}
		}
		else
		{
			if ( $this->isOwner( $album ) )
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Can create a new album?
	 * That is the question we ask and we expect a reply
	 * 
	 * @param	mixed		$memberId		Either member ID, memberData or null (will use $this->memberData)
	 * @param	boolean		$checkLimit		Checks if the member has already more albums than the limit (returns false if limit is hit)
	 * @return	@e boolean	True YES, False, no
	 */
	public function canCreate( $memberId=null, $checkLimit=true )
	{
		$memberData = array();	
	
		/* Fetch member */
		if ( $memberId !== null )
		{
			if ( is_numeric( $memberId ) )
			{
				$memberData = IPSMember::load( $memberId );
			}
			else
			{
				$memberData = $memberId;
			}
		}
		else
		{
			$memberData = $this->memberData;
		}
		
		/* Global check */
		if ( ! $memberData['g_create_albums'] )
		{
			return false;
		}
		
		/* Can we create any more albums? */
		if( $checkLimit && $memberData['g_album_limit'] > 0 )
		{
		 	$total = $this->DB->buildAndFetch( array( 'select' => 'count(album_id) AS total',
		 											  'from'   => 'gallery_albums_main',
		 											  'where'  => "album_owner_id=" . intval( $memberData['member_id'] ) ) );
			
		 	if( $total['total'] < $memberData['g_album_limit'] )
		 	{
				return true;
		 	}
		}
		elseif( $memberData['g_album_limit'] == -1 )
		{
			/* There's no, there's no, there's no LIMITS! */
			return true;
		}

		return false;
	}
	
	/**
	 * Can delete an album
	 * That is the question we ask and we expect a reply
	 * 
	 * @access	public
	 * @param	Mixed		Album ID or array
	 * @param	mixed	Either member ID, memberData or null (will use $this->memberData)
	 * @return	@e boolean	True YES, False, no
	 */
	public function canDelete( $album=0, $member=null )
	{
		$memberData = ( is_array( $member ) ) ? $member : $this->memberData;
		
		if ( ! $memberData['member_id'] )
		{
			return false;
		}
		
		if ( $this->isOwner( $album, $member ) || $this->canModerate( $album, $member ) )
		{
			return true;
		}
		
		return false;
	}
	
	/**
	 * Can edit an album
	 * That is the question we ask and we expect a reply
	 * 
	 * @access	public
	 * @param	Mixed	Album ID or array
	 * @param	mixed	Either member ID, memberData or null (will use $this->memberData)
	 * @return	@e boolean	True YES, False, no
	 */
	public function canEdit( $album=0, $member=null )
	{
		$memberData = ( is_array( $member ) ) ? $member : $this->memberData;
		
		if ( ! $memberData['member_id'] )
		{
			return false;
		}
		
		if ( $this->isOwner( $album, $member ) || $this->canModerate( $album, $member ) )
		{
			return true;
		}
		
		return false;
	}
	
	/**
	 * Returns true if the logged in user can moderate
	 *
	 * @param	mixed		$album		Album ID or array
	 * @param	array		$member		Member data, if null it uses the current logged in member data
	 * @return	@e boolean	False if no member ID or the member can't moderate
	 */
	public function canModerate( $album=0, $member=null )
	{
		$memberData = ( is_array( $member ) ) ? $member : $this->memberData;
		
		if ( ! $memberData['member_id'] )
		{
			return false;
		}
		
		/* Is Admin? */
		if ( IN_ACP || $this->memberData['g_access_cp'] )
		{
			return true;
		}
		
		return $memberData['g_mod_albums'] ? true : false;
	}
	
	/**
	 * Returns true if the logged in user can edit the watermark option
	 *
	 * @param	mixed		$album		Album ID or array
	 * @return	@e bool
	 */
	public function canWatermark( $album )
	{
		if ( is_numeric($album) )
		{
			$album = $this->fetchAlbumsById( intval($album) );
		}
		
		/* No album? */
		if ( !$album['album_id'] )
		{
			return false;
		}
		
		/**
		 * GLOBAL:
		 * 0: disabled
		 * 1: allow members to choose
		 * 2: force watermark
		 * 3: force watermark for direct images but members can choose
		 * 
		 * MEMBER:
		 * 0: disabled
		 * 1: apply watermark
		 * 2: force watermark
		 */
		
		/* Watermark is forced? */
		if ( $album['album_watermark'] == 2 )
		{
			return false;
		}
		
		/* Get any parents */
		$parents = $this->fetchAlbumParents( $album['album_id'] );
		
		if ( count($parents) )
		{
			foreach( $parents as $_album )
			{
				/* Not a global one? */
				if ( !$_album['album_is_global'] )
				{
					continue;
				}
				
				/* Watermark disabled */
				if ( in_array( $_album['album_watermark'], array(0,2) ) )
				{
					return false;
				}
			}
		}
		
		/* Stillllll here? It's TRUE! */
		return true;
	}
	
	/**
	 * 
	 * Returns children for a parent or array of parent ids
	 * @param mixed $album
	 * @param	array	Filters
	 */
	public function getChildrenCount( $album, $filters=array() )
	{
		/* Init */
		$albumIds = array();
		$return   = array();
		
		if ( is_numeric( $album ) )
		{
			$albumIds = array( $album );
		}
		else if ( is_array( $album ) && ! isset( $album['album_id'] ) )
		{
			$albumIds = $album;
		}
		else
		{
			$albumIds = array( $album['album_id'] );
		}
		
		if ( count( $albumIds) )
		{
			/* Did we specify any permissions? */
			$_w = $this->_processPermissionFilters( $filters );
			
			if ( $_w )
			{
				$_w = " AND " . $_w;
			}
		
			$this->DB->build( array( 'select' => 'a.album_parent_id, COUNT(*) as count',
									 'from'   => 'gallery_albums_main a',
									 'where'  => 'a.album_parent_id IN(' . implode( ',', $albumIds ) . ')' . $_w,
									 'group'  => 'a.album_parent_id' ) );
			$o = $this->DB->execute();
			
			while( $row = $this->DB->fetch( $o ) )
			{
				$return[ $row['album_parent_id'] ] = $row['count'];
			}
		}
		
		return ( is_numeric( $album ) ) ? $return[ $album ] : $return;
	}
	
	/**
	 * Get global parent mask
	 * Essentially iterates over an album parent until it hits a global album and then returns the view mask
	 * 
	 * @access	public
	 * @param	int		Album ID
	 * @return	@e string	Permission mask (5,1,4) 
	 */
	public function getFirstGlobalViewMask( $album )
	{
		$mask = null;
		
		$parent = $this->getFirstGlobalParent( $album );
		
		if ( $parent !== null )
		{
			$mask = $parent['album_g_perms_view'];
		}
		
		return $mask;
	}
	
	/**
	 * Get global parent album
	 * Essentially iterates over an album parent until it hits a global album and then returns the album
	 * 
	 * @access	public
	 * @param	int		Album ID
	 * @return	string	Album data; 
	 */
	public function getFirstGlobalParent( $album )
	{
		$mask = null;
		
		if ( is_numeric( $album ) )
		{
			$album = $this->fetchAlbumsById( $album );
		}
		
		/* Is the current album a global parent? */
		if ( $album['album_is_global'] )
		{
			return $album;
		}
		
		/* Root album */
		if ( ! $album['album_parent_id'] )
		{
			return null;
		}
		
		/* Check parents */
		$parents = $this->fetchAlbumParents( $album );
		
		if ( count( $parents ) )
		{
			foreach( $parents as $id => $al )
			{
				if ( $al['album_is_global'] )
				{
					return $al;
					break;
				}
			}
		}
		
		return null;
	}
	
	/**
	 * Get drop down data for albums. Can be a root node or a child node
	 * 
	 * @param int	Parent (0 by default)	$rootNode
	 * @param array Standard filters 		$filters
	 */
	public function getOptionTags( $rootNode=0, array $filters=array() )
	{
		$return      = array();
		$skipParents = array();
		$lowestLevel = null;
		
		if ( is_numeric( $rootNode ) and $rootNode > 0 )
		{
			$filters['album_parent_id'] = $rootNode;
		}
		
		/* Add a root option? */
		if ( ! empty( $filters['skip'] ) )
		{
			$_sa = $this->fetchAlbumsById( $filters['skip'] );
			
			if ( $this->isGlobal( $_sa ) )
			{
				$return[] = '<option value="0">&lt; ' . $this->lang->words['root_album'] . ' &gt;</option>';
			}
		}
		
		/* Are we adding a new root album? */
		if ( ! count( $return ) AND ! empty( $filters['forceRootOption'] ) )
		{
			$return[] = '<option value="0">&lt; ' . $this->lang->words['root_album'] . ' &gt;</option>';
		}
		
		if ( empty( $filters['isViewable'] ) && empty( $filters['isUploadable'] ) && empty( $filters['isCreatable'] ) )
		{
			$filters['isCreatable'] = 1;
		}
		
		$albums = $this->populateDepthString( $this->fetchAlbumsByFilters( $filters ), '--' );
		
		foreach( $albums as $id => $data )
		{
			$checked = ( ! empty( $filters['selected'] ) && $filters['selected'] == $id ) ? ' selected="selected" ' : ''; 
			$lowestLevel = ( $lowestLevel === null ) ? $data['album_node_level'] : $lowestLevel;
			
			if ( ! empty( $filters['isCreatable'] ) AND $this->isPublic( $data ) )
			{
				/* public albums can only be embedded 1 deep */
				if ( isset( $albums[ $data['album_parent_id'] ] ) && empty( $albums[ $data['album_parent_id'] ]['album_is_global'] ) )
				{
					//continue;
				}
			}
			
			/**
			 * @todo	 Is this code still used? referenced in gallery_imagelisting::review()
			if ( ! empty( $filters['skipChildrenOfSelected'] ) )
			{
				if ( $filters['selected'] == $id )
				{
					$skipParents[ $id ] = 1;
				}
				else
				{
					if ( isset($skipParents[ $data['album_parent_id'] ]) )
					{
						continue;
					}
				}
			}*/
			
			$return[] = '<option value="' . $data['album_id'] . '"' . $checked . '>' . $data['depthString'] . $data['album_name'] . '</option>';		
		}
		
		return count( $return ) ? implode( "\n", $return ) : false;
	}
	
	/**
	 * Populates depth string
	 *
	 * @access	public
	 * @param	array	Albums
	 * @param	string	Depth marker chars to use and that
	 * @return	@e array
	 */	
	public function populateDepthString( $albums, $depthMarker='&nbsp;&nbsp;' )
	{
		if ( ! is_array( $albums ) OR ! count( $albums ) )
		{
			return array();
		}
		
		/* First depth */
		$firstDepth = null;

		if ( count( $albums ) > 0 )
		{
			foreach( $albums as $id => $album_data )
			{
				if ( ! $album_data['album_id'] )
				{
					continue;
				}
				
				if ( $firstDepth === null )
				{
					$firstDepth = intval( $album_data['album_node_level'] );
				}
				
				$val = $album_data['album_node_level'] - $firstDepth;
				
				/* Do it. Simple */
				$albums[ $id ]['depthString'] = str_repeat( $depthMarker, $val >= 0 ? $val : 0 );
			}
		}
		
		return $albums;
	}
	
	/**
	 * Flatten and extract album IDs
	 * Takes an nested array of albums, flattens them and returns the album IDs
	 *
	 * @param	array	Nest array of albums with [_children]
	 * @param	array	Array of IDs collected
	 * @return	@e array
	 */
	public function flattenAndExtractAlbumIds( $albums, $ids=array() )
	{
		foreach( $albums as $id => $album )
		{
			$ids[] = $album['album_id'];
			
			if ( ! empty( $album['_children_'] ) )
			{
				foreach( $album['_children_'] as $cid => $cdata )
				{
					$ids  = $this->flattenAndExtractAlbumIds( $album['_children_'], $ids );
				}
			}
		}
		
		return $ids;
	}
	
	/**
	 * Rebuild parent tree
	 *
	 * @access	public
	 * @return
	 */
	public function rebuildNodeTree()
	{
		$this->_node()->setOrder('album_position');
		$this->_node()->rebuildTree();
	}

	/**
	 * Reposition within branch
	 * 
	 */
	public function movePosition( $albumId, $nearId, $position='before' )
	{
		/* Clean and check */
		$position = ( $position == 'after' ) ? 'after' : 'before';
		
		return $this->_node()->movePosition( intval( $albumId ), intval( $nearId ), $position );
	}
	
	/**
	 * Set up and return a new node class handler
	 *
	 * @access	private
	 * @param	string		Key identifier
	 * @param	string		Optional where params during set up
	 * @param	array		Optional IPB formatted add_join array for DB selection
	 * @return	object
	 */
	private function _node()
	{
		if ( ! is_object( $this->_node ) )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/nodes/classNodes.php' );/*noLibHook*/
			$this->_node = new classNodes();
			
			$this->_node->init( array( 'sqlPrimaryID' 		    => 'album_id',
									   'sqlParentID'  		    => 'album_parent_id',
									   'sqlNodeLeft'			=> 'album_node_left',
									   'sqlNodeRight'			=> 'album_node_right',
									   'sqlNodeLevel'    	    => 'album_node_level',
									   'sqlOrder'		    	=> 'album_id',
									   'sqlTitle'				=> 'album_name',
									   'sqlSelect'              => '*',
									   'sqlWhere'			    => '',
									   'sqlJoins'				=> '',
									   'sqlTblPrefix'		    => '',
									   'sqlTable'				=> 'gallery_albums_main' ) );
		}
		
		return $this->_node;
	}
	
	/**
	 * Return part of an SQL 'where' statement. Abstracted in case we change it later
	 *
	 * @access	public
	 * @params	mixed		Array or string of (private, public, friend)
	 * @return	@e string
	 */
	public function sqlWherePrivacy( $mask )
	{
		$privacy = array();
		
		if ( ! is_array( $mask ) )
		{
			$mask = array( $mask );
		}
		
		/* Loop through */
		foreach( $mask as $m )
		{
			switch ( strtolower( $m ) )
			{
				case 'private':
				case 'none':
					$privacy[] = 0;
				break;
				case 'public':
					$privacy[] = 1;
				break;
				case 'friend':
				case 'friends':
					$privacy[] = 2;
				break;
			}
		}
		
		if ( count( $privacy ) == 1 )
		{
			return 'album_is_public=' . array_shift( $privacy ) . '';
		}
		else
		{
			return 'album_is_public IN (' . implode( ",", $privacy ) . ')';
		}
	}
	
	/**
	 * Return part of an SQL 'where' statement. Abstracted in case we change it later
	 *
	 * @access	public
	 * @params	mixed		Array or string of (all,images,albums)
	 * @return	@e string
	 */
	public function sqlWhereAlbumContainerType( $option )
	{
		$mode = array();
		
		if ( ! is_array( $option ) )
		{
			$option = array( $option );
		}
		
		/* Loop through */
		foreach( $option as $m )
		{
			switch ( strtolower( $m ) )
			{
				case 'all':
				case 'both':
					$mode[] = 0;
				break;
				case 'albums':
				case 'album':
					$mode[] = 1;
				break;
				case 'images':
				case 'image':
					$mode[] = 2;
				break;
			}
		}
		
		if ( count( $mode ) == 1 )
		{
			return 'album_g_container_only=' . array_shift( $mode ) . '';
		}
		else
		{
			return 'album_g_container_only IN (' . implode( ",", $mode ) . ')';
		}
	}
	
	/**
	 * Return a human flag for album container setting
	 *
	 * @access	public
	 * @param	mixed		Int, album id. Array, album array data
	 * @return	@e string		public, private, friend
	 */
	public function fetchAlbumContainerFlag( $album )
	{
		$flag = 'all';
		
		if ( is_numeric( $album ) )
		{
			$album = $this->fetchAlbumById( $album );
		}
		
		if ( ! isset( $album['album_id'] ) OR ! isset( $album['album_is_global'] ) )
		{
			trigger_error( 'Incomplete data passed to albums.fetchPrivacyFlag', E_USER_WARNING );
		}
		else
		{
			switch( $album['album_g_container_only'] )
			{
				case 0:
					$flag = 'all';
				break;
				case 1:
					$flag = 'albums';
				break;
				case 2: 
					$flag = 'images';
				break;
			}
			
			return $flag;
		}
	}
	/**
	 * Return a human flag for album privacy setting
	 *
	 * @access	public
	 * @param	mixed		Int, album id. Array, album array data
	 * @return	@e string		public, private, friend
	 */
	public function fetchPrivacyFlag( $album )
	{
		$flag = 'private';
		
		if ( is_numeric( $album ) )
		{
			$album = $this->fetchAlbumById( $album );
		}
		
		if ( ! isset( $album['album_id'] ) OR ! isset( $album['album_is_public'] ) )
		{
			trigger_error( 'Incomplete data passed to albums.fetchPrivacyFlag', E_USER_WARNING );
		}
		else
		{
			switch( $album['album_is_public'] )
			{
				case 0:
					$flag = 'private';
				break;
				case 1:
					$flag = 'public';
				break;
				case 2: 
					$flag = 'friend';
				break;
			}
			
			return $flag;
		}
	}
	
	/**
	 * Fetch images: Central function.
	 *
	 * Custom filter attributes:
	 * isViewable: Only select items the viewer has permission to see
	 * isUploadable: Only select items the viewer has permission to upload into
	 * memberData: Pass data for the viewer else $this->memberData will be used
	 * returnTree: Will return a nested array
	 *
	 * @access	private
	 * @param	array		Array of 'where' information. Where? THERE!
	 * @param	array		Array of 'filter' information. Filter? Water!
	 * @Param 	bool		Skip any permission checks
	 * @return	@e array		Array of 'IDs'. Can't think of anything funny to do with IDs.
	 */
	private function _fetchAlbums( $where, $filters, $byPassPermissionChecks=false )
	{
		/* Al Bums */
		$albums   = array();
		$joins    = null;
		$_q       = array();
		
		/* Have we cleaned filters? */
		if ( ! isset( $filters['_cleaned'] ) )
		{
			$filters = $this->_setFilters( $filters );
		}
		
		if ( ! empty( $filters['skip'] ) )
		{
			$skipAlbum = $this->fetchAlbumsById( $filters['skip'] );
			$_skip     = array();
			
			if ( count( $skipAlbum ) AND is_array( $skipAlbum ) )
			{
				if ( $skipAlbum['album_id'] )
				{
					$skipAlbum = array( $skipAlbum['album_id'] => $skipAlbum );
				}
				
				foreach( $skipAlbum as $id => $_alsbum )
				{
					$_skip[]   = '( a.album_node_left >= ' . intval( $_alsbum['album_node_left'] ) . ' AND a.album_node_right <= ' . intval( $_alsbum['album_node_right'] ) . ')';
				}
				
				$where[] = '( ! ' . implode( " AND ", $_skip ) . ')';
			}
		}
		
		/* Date? */
		if ( $filters['unixCutOff'] )
		{
			$where[] = "a.album_last_img_date > " . intval( $filters['unixCutOff'] );
		}
		
		/* Node level */
		if ( isset( $filters['album_node_level'] ) )
		{
			$where[] = "a.album_node_level=" . intval( $filters['album_node_level'] );
		}
		
		/* Undo some filters and that like */
		if ( $byPassPermissionChecks === true )
		{
			if ( isset( $filters['isViewable'] ) )
			{
				unset( $filters['isViewable'] );
			}
			
			if ( isset( $filters['isUploadable'] ) )
			{
				unset( $filters['isUploadable'] );
			}
		}
		
		/* Did we specify any permissions? */
		$_w = $this->_processPermissionFilters( $filters );
		
		if ( $_w )
		{
			$where[] = $_w;
		}
		
		/* Name like */
		if ( isset( $filters['albumNameContains'] ) )
		{
			$where[] = "a.album_name LIKE '%" . $this->DB->addSlashes( $filters['albumNameContains'] ) . "%'";
		}
		else if ( isset( $filters['albumNameIs'] ) )
		{
			$where[] = "a.album_name='" . $this->DB->addSlashes( $filters['albumNameIs'] ) . "'";
		}
		
		/* Owner like */
		if ( isset( $filters['albumOwnerNameContains'] ) OR isset( $filters['albumOwnerNameIs'] ) )
		{
			/* Could consider lightweight search routine in ipsMembers for 3.3 */
			$_w   = ( ! empty( $filters['albumOwnerNameIs'] ) ) ? "='" . $this->DB->addSlashes( strtolower( $filters['albumOwnerNameIs'] ) ) . "'" : "LIKE '%" . $this->DB->addSlashes( strtolower( $filters['albumOwnerNameContains'] ) ) . "%'";
			$_ids = array();
			
			$this->DB->build( array( 'select' => 'member_id',
									 'from'   => 'members',
									 'where'  => 'members_l_display_name ' . $_w,
									 'limit'  => array(0, 250) ) );
			
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$_ids[] = $row['member_id'];
			}
			
			if ( count( $_ids ) )
			{
				$where[] = 'a.album_owner_id IN (' . implode( ',', $_ids ) . ')';
			}
			else
			{
				/* Return nothing */
				return array();
			}
		} 
		
		/* Filters */
		if ( ! empty( $filters['parentId'] ) )
		{
			$where[] = "a.album_parent_id=" . intval( $filters['parentId'] );
		}
		
		/* More custom where? */
		if ( ! empty( $filters['album_owner_id'] ) )
		{
			if ( ! is_array( $filters['album_owner_id'] ) )
			{
				$filters['album_owner_id'] = array( $filters['album_owner_id'] );
			}
			
			$where[] = "( a.album_owner_id IN (" . implode( ",", $filters['album_owner_id'] ) . ") )";
		}

		/* Wrap up definite elements */
		$query  = ( is_array( $where ) AND count( $where ) ) ? implode( ' AND ', $where ) : '1=1';
		
		/* Do we need joins? */
		if ( empty( $filters['getAlbumTableResultsOnly'] ) )
		{
			/* joins */
			$joins  = array( array( 'select' => 'i.*',
								    'from'   => array( 'gallery_images' => 'i' ),
								    'where'  => 'i.id=a.' . $filters['coverImg'],
								    'type'   => 'left' ),
							 array( 'select' => 'm.members_display_name, m.members_seo_name',
								    'from'   => array( 'members' => 'm' ),
								    'where'  => 'i.member_id=m.member_id',
								    'type'   => 'left' ),
							 array( 'select' => 'mx.members_display_name as owners_members_display_name, mx.members_seo_name as owners_members_seo_name',
								    'from'   => array( 'members' => 'mx' ),
								    'where'  => 'a.album_owner_id=mx.member_id',
								    'type'   => 'left' ) );
			
			/* Rating */
			$join = $this->registry->gallery->helper('rate')->getTableJoins( 'a.album_id', 'album', $this->memberData['member_id'] );
			
			if ( $join !== false && is_array( $join ) )
			{
				array_push( $joins, $join );
			}
		}
		else
		{
			$joins = array();
		}
									 
		/* Did we want a count first? */
		if ( ! empty( $filters['getTotalCount'] ) )
		{
			$_joins = array();
			
			/* Remove the selects from the joins */
			foreach( $joins as $id => $join )
			{
				$join['select'] = '';
				$_joins[] = $join;	
			}
			
			/* Perform the query to fetch the images. Not that you needed this comment to understand that */
			$this->DB->build( array( 'select'   => 'count(*) as count',
									 'from'     => array( 'gallery_albums_main' => 'a' ),
									 'where'    => $query,
									 'add_join' => $_joins ) );
									 
			$o   = $this->DB->execute();
			$row = $this->DB->fetch( $o );
			
			$this->_albumCount = intval( $row['count'] );
		}
		
		/* Set select */
		if ( ! empty( $filters['getFields'] ) && is_array( $filters['getFields'] ) && count( $filters['getFields'] ) )
		{
			if ( empty( $filters['getAlbumTableResultsOnly'] ) )
			{
				$select = preg_replace( '#^,a\.#', '', implode( ',a.', $filters['getFields'] ) );
			}
			else
			{
				$select = implode( ',', $filters['getFields'] );
			}
			
			if ( $select )
			{
				$this->_node()->setSelect( $select );
			}
		}
		
		/* Set joins */
		$this->_node()->setJoins( $joins );
		
		/* Set where */
		$this->_node()->setWhere( $query );
		
		/* Set table prefix */
		$this->_node()->setTblPrefix( 'a' );
		
		/* Set sort */
		if ( ! empty( $filters['sortKey'] ) )
		{
			$this->_node()->setOrder( $filters['sortKey'] . ' ' . $filters['sortOrder'] );
		}
		else
		{
			/* Reset */
			$this->_node()->setOrder( null );
		}
		
		/* Set limit */
		if ( ! empty( $filters['offset'] ) OR ! empty( $filters['limit'] ) )
		{
			/* Are we checking for more? */
			if ( $filters['checkForMore'] )
			{
				$filters['limit']++;
			}
			
			$this->_node()->setLimit( array( $filters['offset'], $filters['limit'] ) );
		}
		else
		{
			/* Reset */
			$this->_node()->setLimit( null );
		}
		
		/* Query */
		$this->_node()->fetchTree();
		
		/* Reset a few items */
		$seen = 0;
		$this->_hasMore = false;
		$ownerIds       = array();
		
		/* Fetch */
		while( $_a = $this->_node()->nextRow() )
		{
			/* re-test permissions to be sure */
			if ( $filters['isViewable'] && ! isset( $filters['moderatingData']['action'] ) )
			{
				if ( ! $this->isViewable( $_a ) )
				{
					continue;
				}
			}
			
			if ( $filters['isUploadable'] && ! isset( $filters['moderatingData']['action'] ) )
			{
				if ( ! $this->isUploadable( $_a ) )
				{
					continue;
				}
			}
			
			/* Add check? */
			if ( $filters['addUploadableFlag'] )
			{
				$_a['_canUpload'] = ( $this->isUploadable( $_a ) ) ? 1: 0;
			}
			
			if ( $filters['checkForMore'] && $filters['limit'] )
			{
				$seen++;
				
				if ( $seen + 1 > $filters['limit'] )
				{
					$this->_hasMore = true;
					continue;
				}
			}
			
			$ownerIds[] = $_a['album_owner_id'];
			
			$albums[ $_a['album_id'] ] = $this->_setUpAlbum( $_a );
		}
		
		/* parse image owner? */
		if ( ! empty( $filters['parseAlbumOwner'] ) )
		{
			/* Need to load members? */
			if ( count( $ownerIds ) )
			{
				$mems = IPSMember::load( $ownerIds, 'all' );
				
				foreach( $albums as $id => $r )
				{
					if ( ! empty( $r['member_id'] ) AND isset( $mems[ $r['member_id'] ] ) )
					{
						$mems[ $r['member_id'] ]['m_posts'] = $mems[ $r['member_id'] ]['posts'];
						
						$_mem = IPSMember::buildDisplayData( $mems[ $r['member_id'] ], array( 'reputation' => 0, 'warn' => 0 ) );

						$albums[ $id ] = array_merge( $albums[ $id ], $_mem );
					}
				}
			}
		}
		
		/* Did we vant ze kiddie vinkies? */
		if ( ! empty( $filters['getChildrenCount'] ) AND count( $albums ) )
		{
			$_zeKIDS = $this->getChildrenCount( array_keys( $albums ), $filters );
			
			foreach( $_zeKIDS as $r => $allVIGHT )
			{
				$albums[ $r ]['_childrenCount'] = intval( $allVIGHT );
			}
		}
		
		/* Did we want to parse the latest IMGS or WHAT? */
		if ( ! empty( $filters['getGlobalAlbumLatestImgs'] ) )
		{
			$imgIds = array();
			
			foreach( $albums as $key => $data )
			{
				if ( is_array( $data['album_g_latest_imgs'] ) )
				{
					$imgIds = array_unique( array_merge( $imgIds, $data['album_g_latest_imgs'] ) );
				}
			}
			
			if ( count( $imgIds ) )
			{
				$imageData = $this->registry->gallery->helper('image')->fetchImages( null, array( 'imageIds' => $imgIds ) );
				
				foreach( $albums as $key => $data )
				{
					if ( is_array( $data['album_g_latest_imgs'] ) )
					{
						$count = 0;
						
						foreach( $data['album_g_latest_imgs'] as $id )
						{
							if ( $imageData[ $id ] )
							{
								$count++;
								
								if ( is_numeric( $filters['getGlobalAlbumLatestImgs'] ) )
								{
									if ( $count > $filters['getGlobalAlbumLatestImgs'] )
									{
										continue;
									}
								}
								
								$albums[ $key ]['_latestImages'][ $id ] = $imageData[ $id ];
							}
						}
					}
				}
			}
		}
		
		/* Nestle the array */
		if ( $filters['returnTree'] )
		{
			return nodeFunctions::nestle( $albums, 'album_node_level' ); 
		}
		else if ( $filters['nestDescendants'] )
		{
			return $this->_nestDescendants( $albums, 0, $filters );
		}
		else
		{
			if ( ! empty( $filters['getParents'] ) )
			{
				$parents = array();
				
				foreach( $albums as $key => $data )
				{ 
					if ( is_array( $data['album_parent_tree'] ) )
					{
						$parents = array_unique( array_merge( $parents, $data['album_parent_tree'] ) );
					}
				}
				
				if ( count( $parents ) )
				{
					$subFilters = array( 'album_id' => $parents );
					
					/* Don't need permission filters here because we assume we have
					 * permission to see them if we can see child
					 */
					
					/* Figure out filter */
					if ( is_string( $filters['getParents'] ) )
					{
						switch ( $filters['getParents'] )
						{
							case 'global':
								$subFilters['album_is_global'] = 1;
							break;
							case 'members':
							case 'member':
								$subFilters['album_is_global'] = 0;
							break;
						}
					}
					
					/* Got anything? */
					$parentData = $this->fetchAlbumsByFilters( $subFilters );
					
					foreach( $albums as $key => $data )
					{
						if ( is_array( $data['album_parent_tree'] ) )
						{
							foreach( $data['album_parent_tree'] as $id )
							{
								if ( $parentData[ $id ] )
								{
									$albums[ $key ]['_parents'][ $id ] = $parentData[ $id ];
								}
							}
						}
					}
				}
			}
			
			if ( ! empty( $filters['getChildren'] ) )
			{
				$children   = array();
				
				foreach( $albums as $key => $data )
				{				
					if ( is_array( $data['album_child_tree'] ) )
					{
						$children = array_unique( array_merge( $children, $data['album_child_tree'] ) );
					}
				}
				
				if ( count( $children ) )
				{
					$subFilters = array( 'album_id' => $children, 'sortKey' => 'position', 'sortOrder' => 'asc' );
					
					/* Add in permission filters */
					$perms = array( 'isUploadable', 'isCreatable', 'isViewable' );
					
					foreach( $perms as $p )
					{
						if ( isset( $filters[ $p ] ) )
						{
							$subFilters[ $p ] = $filters[ $p ];
						}
					}
					
					/* Figure out filter */
					switch ( $filters['getChildren'] )
					{
						case 'global':
							$subFilters['album_is_global'] = 1;
						break;
						case 'members':
						case 'member':
							$subFilters['album_is_global'] = 0;
						break;
					}
					
					/* Got anything? */
					$childData = $this->fetchAlbumsByFilters( $subFilters );
					
					foreach( $albums as $key => $data )
					{
						if ( is_array( $data['album_child_tree'] ) )
						{
							$tmp = array();
							
							foreach( $data['album_child_tree'] as $id )
							{
								if ( $childData[ $id ] )
								{ 
									if ( ! $childData[ $id ]['album_is_global'] )
									{
										$childData[ $id ]['album_position'] += 100000000;
									}
									
									$tmp[ $childData[ $id ]['album_position'] . '.' . $id ] = $childData[ $id ];
								}
							}
							
							if ( count( $tmp ) )
							{
								ksort( $tmp );
								
								foreach( $tmp as $pos => $_data )
								{
									$albums[ $key ]['_children'][ $_data['album_id'] ] = $_data;
								}
							}
						}
					}
				}
			}
			
			return $albums;
		}
	}
	
	/**
	 * Sets up some album data and that.
	 *
	 * @access	private
	 * @param	array		Album
	 * @return	@e array
	 */
	private function _setUpAlbum( $album )
	{
		if ( ! is_array( $album ) )
		{
			return array();
		}
		
		/* SEO Name - Auto fix */
		if ( !$album['album_name_seo'] && $album['album_id'] && $album['album_name'] )
		{
			$album['album_name_seo'] = IPSText::makeSeoTitle( $album['album_name'] );
			
			$this->DB->update( 'gallery_albums_main', array( 'album_name_seo' => $album['album_name_seo'] ), 'album_id='.$album['album_id'] );
		}
		
		/* Legacy */
		$album['_seo_name'] = $album['album_name_seo'];
		
		/* Sort cover image */
		if ( $album['album_cover_img_id'] && ! empty( $album['id'] ) and $album['approved'] ) 
		{
			$album['thumb'] = $this->registry->gallery->helper('image')->makeImageLink( $album, array( 'type' => 'thumb', 'coverImg' => true ) );
			$album['small'] = $this->registry->gallery->helper('image')->makeImageLink( $album, array( 'type' => 'small', 'coverImg' => true ) );
		}
		else
		{
			$album['album_cover_img_id'] = 0;
			if ( ( isset( $album['album_count_imgs'] ) && $album['album_count_imgs'] ) && ( isset( $album['album_is_global'] ) && ! $album['album_is_global'] ) )
			{
				/* No longer needed and causes infinite loops */
				//$album['album_cover_img_id'] = 0;
				//$this->resync( $album );
				
				//$album = $this->fetchAlbumsById( $album['album_id'], GALLERY_ALBUM_FORCE_LOAD );
			}
			else
			{
				$album['thumb'] = $this->registry->gallery->helper('image')->makeNoPhotoTag( array( 'type' => 'thumb', 'coverImg' => true ) );
			}
		}
		
		$album['selfSeoUrl']     = $this->registry->output->buildSEOUrl( "app=gallery&amp;album={$album['album_id']}", 'public', $album['album_name_seo'], 'viewalbum' );
		$album['_totalImages']   = intval( $album['album_count_imgs'] );
		$album['_totalComments'] = intval( $album['album_count_comments'] );
		
		if ( $album['album_count_imgs_hidden'] && $this->canModerate( $album ) )
		{
			$album['_totalImages']   += intval( $album['album_count_imgs_hidden'] );
			$album['_totalComments'] += intval( $album['album_count_comments_hidden'] );
		}
		
		/* Got rules? */
		if ( !empty($album['album_g_rules']) && IPSLib::isSerialized( $album['album_g_rules'] ) )
		{
			$rules = unserialize( $album['album_g_rules'] );
			
			if ( $rules['title'] && $rules['text'] )
			{
				if ( strstr( $rules['text'], '[' ) )
				{
					IPSText::getTextClass('bbcode')->parse_html			= 1;
					IPSText::getTextClass('bbcode')->parse_nl2br		= 1;
					IPSText::getTextClass('bbcode')->parse_smilies		= 1;
					IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
					IPSText::getTextClass('bbcode')->parsing_section	= 'global';
		
					$rules['text'] = IPSText::getTextClass('bbcode')->preDisplayParse( $rules['text'] );
				}
				
				$album['album_g_rules_expanded'] = $rules;
			}
		}
		
		/* Preset sort order */
		$album['album_sort_options__key'] = 'idate';
		$album['album_sort_options__dir'] = 'asc';
		
		/* Got sort order? */
		if ( $album['album_sort_options'] )
		{
			if ( IPSLib::isSerialized( $album['album_sort_options'] ) )
			{
				$order = unserialize( $album['album_sort_options'] );
				
				$album['album_sort_options__key'] = ( ! empty( $album['album_sort_options__key'] ) ) ? $order['key'] : 'idate';
				$album['album_sort_options__dir'] = ( ! empty( $album['album_sort_options__dir'] ) ) ? $order['dir'] : 'asc';
			}
		}
		
		/* Parent n' kids */
		if ( IPSLib::isSerialized( $album['album_parent_tree'] ) )
		{
			$album['album_parent_tree'] = unserialize( $album['album_parent_tree'] );
		}
		
		if ( IPSLib::isSerialized( $album['album_child_tree'] ) )
		{
			$album['album_child_tree'] = unserialize( $album['album_child_tree'] );
		}
		
		if ( IPSLib::isSerialized( $album['album_g_latest_imgs'] ) )
		{
			$album['album_g_latest_imgs'] = unserialize( $album['album_g_latest_imgs'] );
		}
				
		/* Got rating? */
		if ( isset( $album['rate'] ) AND isset( $album['rdate'] ) )
		{
			$album['_youRated'] = $album['rate'];
		}
		
		return $album;
	}
	
	/**
	 * Set filters
	 * Takes user input and cleans it up a bit
	 *
	 * @access	private
	 * @param	array		Incoming filters
	 * @return	@e array
	 */
	private function _setFilters( $filters )
	{
		$filters['sortOrder']         = ( isset( $filters['sortOrder'] ) )         ? $filters['sortOrder'] : '';
		$filters['sortKey']           = ( isset( $filters['sortKey'] ) )           ? $filters['sortKey'] : '';
		$filters['offset']            = ( isset( $filters['offset'] ) )            ? $filters['offset'] : '';
		$filters['limit']             = ( isset( $filters['limit'] ) )             ? $filters['limit'] : '';
		$filters['isUploadable']      = ( isset( $filters['isUploadable'] ) )      ? $filters['isUploadable'] : '';
		$filters['isViewable']        = ( isset( $filters['isViewable'] ) )        ? $filters['isViewable'] : '';
		$filters['returnTree']        = ( isset( $filters['returnTree'] ) )        ? $filters['returnTree'] : '';
		$filters['nestDescendants']   = ( isset( $filters['nestDescendants'] ) )   ? $filters['nestDescendants'] : '';
		
		switch( $filters['sortOrder'] )
		{
			default:
			case 'desc':
			case 'descending':
			case 'z-a':
				$filters['sortOrder'] = 'desc';
			break;
			case 'asc':
			case 'ascending':
			case 'a-z':
				$filters['sortOrder'] = 'asc';
			break;
		}
		
		/* Do some set up */
		switch( $filters['sortKey'] )
		{
			case 'position':
				$filters['sortKey']  = 'a.album_position';
			break;
			case 'album_last_img_date':
			case 'date':
			case 'time':
				$filters['sortKey']  = 'a.album_last_img_date';
			break;
			case 'name':
				$filters['sortKey']  = 'a.album_name_seo';
			break;
			case 'album_count_total_imgs':
			case 'images':
				$filters['sortKey']  = 'a.album_count_imgs';
			break;
			case 'album_count_comments':
			case 'comments':
				$filters['sortKey']  = 'a.album_count_comments';
			break;
			case 'album_rating_aggregate':
			case 'rating':
			case 'rated':
				$filters['sortKey']  = 'a.album_rating_aggregate ' . $filters['sortOrder'] . ', a.album_rating_count';
			break;
			case 'rand':
			case 'random':
				$filters['sortKey']  = $this->DB->buildRandomOrder();
			break;
		}
	
		
		/* Others */
		$filters['offset']       = intval( $filters['offset'] );
		$filters['limit']        = intval( $filters['limit'] );
		$filters['unixCutOff']   = ( ! empty( $filters['unixCutOff'] ) ) ? intval( $filters['unixCutOff'] ) : 0;
		$filters['checkForMore'] = ( ! empty( $filters['checkForMore'] ) ) ? true : false;
		$filters['coverImg']     = ( ! empty( $filters['unixCutOff'] ) && $filters['coverImg'] == 'latest' ) ? 'album_last_img_id' : 'album_cover_img_id';
		
		/* So we don't have to do this twice */
		$filters['_cleaned']   = true;
		
		return $filters;
	}
	
	/**
	 * Process permission filters
	 * @param array $filters
	 * @return string
	 */
	private function _processPermissionFilters( $filters )
	{
		$where         = array();
		$memberId      = isset( $filters['memberData']['member_id'] ) ? $filters['memberData']['member_id'] : $this->memberData['member_id'];
		$_q            = array();
		$_or	       = '';
		$perm_id_array = $this->member->perm_id_array;
		
		/* Have we cleaned filters? */
		if ( ! isset( $filters['_cleaned'] ) )
		{
			$filters = $this->_setFilters( $filters );
		}
		
		if ( ! empty( $filters['bypassPermissionChecks'] ) )
		{
			if ( ! empty( $filters['album_is_global'] ) )
			{
				$where[] = '(a.album_is_global=1)';
			}
			
			if ( isset( $filters['album_is_global'] ) && $filters['album_is_global'] == 0 )
			{
				$where[] = '(a.album_is_global=0)';
			}
			
			return ( count($where) ) ? '( ' . implode( ' OR ', $where ) . ' )' : '';
		}
		
		if ( $memberId != $this->memberData['member_id'] )
		{
			/* Force to guest for now */
			$perm_id_array = explode( ",", $this->caches[ $this->settings['guest_group'] ]['g_perm_id'] );
		}
		
		/* Same perms as... */
		if ( ! empty( $filters['hasSamePermsAs'] ) )
		{
			if ( is_numeric( $filters['hasSamePermsAs'] ) )
			{
				$filters['hasSamePermsAs'] = $this->fetchAlbum( $filters['hasSamePermsAs'], true );
			}
			
			$perm_id_array = explode( ",", $filters['hasSamePermsAs']['album_g_perms_view'] );
		}
		
		/* Ensure perms are ok */
		foreach( $perm_id_array as $i => $v )
		{
			if ( empty( $v ) )
			{
				unset( $perm_id_array[ $i ] );
			}
		}
		
		if ( $filters['isViewable'] )
		{
			if ( ! isset( $filters['album_is_global'] ) OR ! $filters['album_is_global'] )
			{
				if ( ! $this->canModerate( 0, $this->memberData ) )
				{
					if ( $memberId == $this->memberData['member_id'] AND is_array( $this->memberData['_cache']['friends'] ) AND count( $this->memberData['_cache']['friends'] ) )
					{
						/**
						 * Friends are limit to the first 300 to prevent HUGE 'IN queries'
						 * which cause slow loading. Need to revisit this for a future version
						 */
						$_or = " OR ( a.album_owner_id IN(" . implode( ",", array_slice( array_keys( $this->memberData['_cache']['friends'] ), 0, 300 ) ) . ') )';
					}
					
					$_q[] = "(a.album_is_global=0 AND (
								( a.album_is_public=1 AND " . $this->DB->buildWherePermission( $perm_id_array, 'a.album_g_perms_view', true ) . ")
									OR ( a.album_is_public=0 AND a.album_owner_id=" . intval( $memberId ) . " )
									OR ( a.album_is_public=2 AND ( a.album_owner_id=" . intval( $memberId ) . " ) " . $_or . ")
								) )";
				}
				else
				{
					$_q[] = "(a.album_is_global=0 AND (
								( a.album_is_public=1 AND " . $this->DB->buildWherePermission( $perm_id_array, 'a.album_g_perms_view', true ) . ")
									OR ( a.album_is_public=0 )
									OR ( a.album_is_public=2 )
								) )";
				}
			}
			
			if ( ! isset( $filters['album_is_global'] ) OR $filters['album_is_global'] )
			{
				$_q[] = "(a.album_is_global=1 AND " . $this->DB->buildWherePermission( $perm_id_array, 'a.album_g_perms_view', true ) . ')';
			}
		}
		else if ( $filters['isCreatable'] )
		{
			if ( ! isset( $filters['album_is_global'] ) OR ! $filters['album_is_global'] )
			{
				$_q[] = '(a.album_is_global=0 AND a.album_owner_id=' . intval( $memberId ) . ')';
			}
			
			if ( ! isset( $filters['album_is_global'] ) OR $filters['album_is_global'] )
			{
				/**
				 * Checking for a.album_g_container_only=1 prevents members from creating an album
				 * in global albums that are not setup as 'album mode' only
				 */
				$_q[] = '(a.album_is_global=1 AND a.' . $this->sqlWhereAlbumContainerType( array('all', 'albums' ) ) . ' AND ' . $this->DB->buildWherePermission( $perm_id_array, 'a.album_g_perms_images', true ) . ')';
			}
		}
		else if ( $filters['isUploadable'] )
		{
			if ( ! isset( $filters['album_is_global'] ) OR ! $filters['album_is_global'] )
			{
				if ( isset( $filters['moderatingData']['moderator'] ) AND $this->canModerate( 0, $filters['moderatingData']['moderator'] ) )
				{
					if ( $filters['moderatingData']['action'] != 'moveImages' && $filters['moderatingData']['owner_id'] )
					{
						$_q[] = '(a.album_is_global=0 AND a.album_owner_id=' . intval($filters['moderatingData']['owner_id']). ')';
					}
					else
					{
						$_q[] = '(a.album_is_global=0)';
					}
				}
				else
				{
					$_q[] = '(a.album_is_global=0 AND a.album_owner_id=' . intval( $memberId ) . ')';
				}
			}
			
			if ( ! isset( $filters['album_is_global'] ) OR $filters['album_is_global'] )
			{
				$_q[] = '(a.album_is_global=1 AND a.' . $this->sqlWhereAlbumContainerType( array('all', 'images' ) ) . ' AND ' . $this->DB->buildWherePermission( $perm_id_array, 'a.album_g_perms_images', true ) . ')';
			}		
		}
		else if ( ! isset( $filters['isUploadable'] ) AND ! isset( $filters['isViewable'] ) AND isset( $filters['album_is_global'] ) AND $filters['album_is_global'] !== null AND $filters['album_is_global'] )
		{
			$where[] = 'a.album_is_global=1 AND a.' . $this->sqlWhereAlbumContainerType( array('all', 'images' ) );
		}
		else if ( ! isset( $filters['isUploadable'] ) AND ! isset( $filters['isViewable'] ) AND isset( $filters['album_is_global'] ) AND $filters['album_is_global'] !== null AND ! $filters['album_is_global'] )
		{
			$where[] = 'a.album_is_global=0';
		}
		else if ( ! empty( $filters['album_is_global'] ) )
		{
			$where[] = "a.album_is_global=1";
		}
		else if ( isset($filters['album_is_global']) && $filters['album_is_global'] == 0 )
		{
			$where[] = "a.album_is_global=0";
		}
		
		/* Add in OR items */
		if ( count( $_q ) )
		{
			$where[] = '( ' . implode( ' OR ', $_q ) . ' )';
		}
		
		return ( count( $where ) ) ? '( ' . implode( ' AND ', $where ) . ' )' : '';
	}
	
	/**
	 * Post process to figure out descendant nesting
	 * @param unknown_type $albums
	 * @param unknown_type $level
	 * @param unknown_type $filters
	 */
	private function _nestDescendants( $albums, $album, $filters=array() )
	{
		if ( ! is_array( $album ) or $album == 0 )
		{
			$album = array( 'album_node_level' => 0, 'album_parent_id' => 0 );
		}
		
		$level            = $album['album_node_level'] + 1;
		$nestPlease 	  = ( array_key_exists( 'nestDescendants', $filters ) ) ? $filters['nestDescendants'] : false;
		$limitDescendants = ( array_key_exists( 'limitDescendants', $filters ) ) ? $filters['limitDescendants'] : false;
		$isUsed           = ( array_key_exists( 'isUsed', $filters ) ) ? $filters['isUsed'] : false;
		$parentMap        = array();
		$children         = array();
		$counter          = array();
			
		foreach( $albums as $id => $p )
		{
			if ( $p['album_node_level'] > $level )
			{
				if ( $nestPlease === true OR $p['album_node_level'] - $level <= $nestPlease )
				{
					/* Count */
					if ( ! isset( $counter[ $p['album_parent_id'] ] ) )
					{
						$counter[ $p['album_parent_id'] ] = 0;
					}
					
					if ( $limitDescendants )
					{
						if ( $p['album_parent_id'] AND ( ! empty( $parentMap[ $p['album_parent_id'] ] ) ) AND $counter[ $p['album_parent_id'] ] + 1 > $limitDescendants )
						{
							continue;
						}
					}			
					
					$counter[ $p['album_parent_id'] ]++;
					
					$parentMap[ $p['album_parent_id'] ][ $p['album_id'] ] = $p;
				}
				else
				{
					if ( $nestPlease !== true && is_numeric( $nestPlease ) )
					{ 
						/* Only want X deep */
						$counter[ $p['album_parent_id'] ]--;
						continue;
					}
				}
			}
			
			if ( array_key_exists( 'immediateDescendants', $filters ) AND $filters['immediateDescendants'] )
			{
				if ( $p['album_parent_id'] != $album['album_id'] )
				{
					continue;
				}			
			}
			
			$children[ $p['album_id'] ] = $p;
		}
		
		/* Post process */
		if ( count( $parentMap ) )
		{
			foreach( $parentMap as $pid => $pdata )
			{
				$children[ $pid ]['_children_']       = $pdata;
				$children[ $pid ]['_children_count_'] = ( ! empty( $counter[ $pid ] ) ) ? $counter[ $pid ] : 0;
				
				foreach( $parentMap[ $pid ] as $aid => $adata )
				{
					if ( empty( $children[ $pid ]['album_id'] ) )
					{
						unset( $children[ $pid ] );
					}
					
					if ( $adata['album_node_level'] > $level )
					{
						unset( $children[ $aid ] );
					}
				}
			}
		}
	
		/* Remove ones not used */
		if ( $isUsed && count( $children ) )
		{
			foreach( $children as $id => $data )
			{
				if ( ! $data['album_count_imgs'] && ! isset( $children[ $id ]['_children_'] ) )
				{
					unset( $children[ $id ] );
				}
			}	
		}
		
		return $children;
	}
	
	/**
	 * Ensures data is correct format
	 *
	 * @access	private
	 * @param	array
	 * @return	@e array
	 */
	private function _permify( $album )
	{
		$album['app']          = 'gallery';
		$album['perm_type_id'] = $album['album_id'];
		$album['perm_type']    = 'albums';
		$album['perm_view']    = $album['album_g_perms_view'];
		
		return $album; 
	}
	
}