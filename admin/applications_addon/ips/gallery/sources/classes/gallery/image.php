<?php
/**
* Image Listing Class
*
* @author 		Matt Mecham
* @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
* @license		http://www.invisionpower.com/community/board/license.html
* @package		IP.Gallery
* @link			http://www.invisionpower.com
* @version		$Rev: 9999 $
*/

class gallery_image
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
	 * Next/previous cache
	 * We use virtually the same query to generate the photostrip as to generate the next/prev links so
	 * we cache the result to save another query.
	 * 
	 * @var		$_navCache
	 */
	protected $_navCache = array( 'prev' => null, 'now' => null, 'next' => null );
	
	/**
	 * Image count
	 *
	 * @var		$_imageCount
	 */
	protected $_imageCount = null;
	
	/**
	 * Image file extensions
	 * 
	 * @var		$_ext
	 */
	protected $_ext = array( 'gif', 'png', 'jpg', 'jpeg', 'tiff' );
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	ipsRegistry	$registry
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make registry objects */
		$this->registry   = $registry;
		$this->DB         = $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       = $this->registry->getClass('class_localization');
		$this->member     = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      = $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		
		define( 'GALLERY_IMAGES_FORCE_LOAD', true );
		
		/* Load tagging stuff */
		if ( ! $this->registry->isClassLoaded('galleryTags') )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$this->registry->setClass( 'galleryTags', classes_tags_bootstrap::run( 'gallery', 'images' ) );
		}
		
		if ( ! ipsRegistry::isClassLoaded('gallery') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
			$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
		}
		
		if ( ! ipsRegistry::isClassLoaded('classItemMarking') && ( ! defined('IPS_IS_UPGRADER') OR ! IPS_IS_UPGRADER ) )
		{
			$this->registry->getClass('classItemMarking');
		}
		
		$this->gallery = $this->registry->gallery;
	}
	
	/**
	 * Return an array of allowed extensions
	 * 
	 * @return	@e array
	 */
	public function allowedExtensions()
	{
		return $this->_ext;
	}
	
	/**
	 * Returns the image count from a query which asks for it.
	 * Which is anything that passes via _fetchImages
	 * @access	public
	 * @return	@e int
	 */
	public function getCount()
	{
		return $this->_imageCount;
	}
	
	/**
	 * Return the correct mime type for an image
	 *
	 * @access	public
	 * @since	2.0.4
	 * @param	string	$img
	 * @return	@e string	
	 */
	public function getImageType( $img )
	{
		$exploded_array = explode( ".", $img );

		$ext = strtolower( array_pop( $exploded_array ) );
		
		switch( $ext )
		{
			case 'gif':
				$file_type = 'image/gif';
			break;
			
			case 'jpg':
			case 'jpeg':
			case 'jpe':
				$file_type = 'image/jpeg';
			break;
			
			case 'png':
				$file_type = 'image/png';
			break;
		}
		
		return $file_type;
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
				case 'cat':
				case 'category':
				case 'global_album':
				case 'galbum':
					$privacy[] = 3;
				break;
			}
		}
		
		if ( count( $privacy ) == 1 )
		{
			return 'image_privacy=' . array_shift( $privacy ) . '';
		}
		else
		{
			return 'image_privacy IN (' . implode( ",", $privacy ) . ')';
		}
	}
	
	/**
	 * Return a human flag for image privacy setting
	 *
	 * @access	public
	 * @param	mixed		Int, image id. Array, image array data
	 * @return	@e string		public, private, friend
	 */
	public function fetchPrivacyFlag( $image )
	{
		$flag = 'private';
		
		if ( is_numeric( $image ) )
		{
			$image = $this->fetchImage( $image );
		}
		
		if ( ! isset( $image['id'] ) OR ! isset( $image['image_privacy'] ) )
		{
			trigger_error( 'Incomplete data passed to fetchPrivacyFlag', E_USER_WARNING );
		}
		else
		{
			switch( $image['image_privacy'] )
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
				case 3: 
					$flag = 'galbum';
				break;
			}
			
			return $flag;
		}
	}
	
	/**
	 * Save imarges. Give it an array, and it'll update the fields in the array.
	 * It's WELL good like that
	 *
	 * @access	public
	 * @param	array	array( id => array( ... ) )
	 * @return	@e boolean
	 */
	public function save( array $array )
	{
		$albums  = array();
		$uploads = array();
		
		foreach( $array as $id => $data )
		{
			/* Is it noomeric? */
			if ( ! is_numeric( $id ) AND strlen( $id ) == 32 )
			{
				$uploads[ $id ] = $data;
			}
			else
			{
				/* Flag album for resync */
				if ( ! array_key_exists( $data['img_album_id'], $albums ) )
				{
					$albums[ $data['img_album_id'] ] = array();
				}
				
				/* Remove special items */
				if ( isset( $data['_isCover'] ) )
				{
					if ( $data['_isCover'] )
					{
						$albums[ $data['img_album_id'] ] = array( 'album_cover_img_id' => $id );
					}
					
					unset( $data['_isCover'] );
				}

				/* It's an imarge! */
				$this->DB->update( 'gallery_images', $data, 'id=' . intval( $id ) );
				
				if ( ! empty( $_POST['ipsTags_' . $id] ) )
				{
					$this->registry->galleryTags->replace( $_POST['ipsTags_' . $id], array( 'meta_id'		 => $id,
																	      					'meta_parent_id' => $data['img_album_id'],
																	      					'member_id'	     => $this->memberData['member_id'],
																	      					'meta_visible'   => $data['approved'] ) );
				}
				
				/* Rebuild images? */
				if ( isset( $data['masked_file_name'] ) AND isset( $data['directory'] ) )
				{
					$this->rebuildSizedCopies( $data );
				}
			}
		}
		
		/* Got uploads stuff? */
		if ( count( $uploads ) )
		{
			$this->registry->gallery->helper('upload')->saveSessionImages( $uploads );
		}
		
		/* Update albums? */
		if ( count( $albums ) )
		{
			$this->registry->gallery->helper('albums')->save( $albums );
		}
	}
	
	/**
	 * Updates images in this album with the appropriate permission string
	 * @param mixed $album
	 */
	public function updatePermissionFromParent( $album )
	{
		if ( is_numeric( $album ) )
		{
			if ( $album > 0 )
			{
				$album = $this->registry->gallery->helper('albums')->fetchAlbumsById( $album );
			}
		}
		else if ( is_array( $album ) AND isset( $album['album_node_left'] ) )
		{
			/* Do all */
			$this->DB->allow_sub_select = true;
			ips_DBRegistry::loadQueryFile( 'public', 'gallery' );
			$this->DB->buildFromCache( 'gallery_update_all_image_permission', $album, 'public_gallery_sql_queries' );
			$this->DB->execute();
		}
		else
		{
			foreach( $album as $id => $al )
			{
				if ( ! isset( $al['album_node_left'] ) )
				{
					$al = $this->registry->gallery->helper('albums')->fetchAlbumsById( $al );
					
					$this->DB->allow_sub_select = true;
					ips_DBRegistry::loadQueryFile( 'public', 'gallery' );
					$this->DB->buildFromCache( 'gallery_update_all_image_permission', $al, 'public_gallery_sql_queries' );
					$this->DB->execute();
				}
			}
		}
	}
	
	/**
	 * Deletes images
	 *
	 * @access	public
	 * @param	string		Session key
	 * @param	string
	 *
	 */
	public function delete( $images )
	{
		/* Nice to keep a handler here for consistency */
		$this->registry->gallery->helper('moderate')->deleteImages( $images );
	}
	
	/**
	 * Generic method for getting an image record
	 * 
	 * @since	2.0
	 * @access	public
	 * @param	int	$id
	 * @param	bool		If we're loading lots of images in the same view and not displaying descriptions, pass false to save some resources
	 * @return	@e array
	 */
	public function fetchImage( $id, $force=false, $parseDescription=true )
	{
		$id = trim($id);
		
		if ( !$id )
		{
			return array();
		}
		
		static $imagesCache = array();
		
		if ( isset($imagesCache[ $id ]) && $force === false )
		{
			return $imagesCache[ $id ];
		}
		
		/* Main table */
		if ( is_numeric($id) && $id > 0 )
		{
			$_img = $this->DB->buildAndFetch( array( 'select'	=>	'i.*',
													 'from'		=>	array( 'gallery_images' =>	'i' ),
													 'where'	=>	'i.id=' . intval( $id ),
													 'add_join'	=>	array( array( 'select'  => 'a.*',
							 					  		 						  'from'    => array( 'gallery_albums_main' => 'a' ),
							 					  	 							  'where'   => 'a.album_id=i.img_album_id',
							 					  		 						  'type'    => 'left' ),
																		   array( 'select'	=>	'mem.members_display_name',
																				  'from'	=>	array( 'members' => 'mem' ),
																				  'where'	=>	'mem.member_id=i.member_id',
																				  'type'	=>	'left' ) )
											 )		);
		}
		/* Fallback on upload session */
		elseif ( $id )
		{
			$_img = $this->registry->gallery->helper('upload')->fetchImage( $id );
		}
		
		$imagesCache[ $id ] = $this->_setUpImage( $_img, $parseDescription );
		
		return $imagesCache[ $id ];
	}
	
	/**
	 * Fetch images: Generic class
	 *
	 * Fetches a members' images. It's good like that.
	 * Filters:
	 * sortKey				Sort results key
	 * sortOrder			Sort results asc/desc
	 * limit				Limit to X results
	 * offset				Fetch from X rows
	 * imageIds				Array of image ids to fetch
	 * albumId				Array of album Ids to fetch from
	 * getTotalCount		Fetch total count of WHERE before limit is added
	 * featured				WHERE on the featured field
	 * getLatestComment		Fetch latest comment with data
	 * hasComments			Only return images with comments
	 *
	 * @access	public
	 * @param	int		Member ID (the person viewing)
	 * @param	array	Sort options (sortKey, sortOrder, offset, limit, unixCutOff)
	 * @return	@e array	Array of images and album information
	 */
	public function fetchImages( $memberId, $filters=array(), $byPassPerms=false )
	{
		/* Set filters */
		$memberId = ( $memberId !== null ) ? intval( $memberId ) : null;
		$filters  = $this->_setFilters( $filters );
		$where    = array();
		$_or      = array();
		$_masks   = array( 'public', 'galbum' );
		
		/* Looking for specific images without perm checks? */
		if ( ! empty( $filters['imageIds'] ) && is_array( $filters['imageIds'] ) )
		{
			$where[] = "i.id IN (" . implode( ",", $filters['imageIds'] ) . ')';
		}
			
		if ( $memberId !== null || ( $memberId !== null && $byPassPerms === false ) )
		{
			/* Set up permission masks if we're a member */
			if ( $memberId )
			{
				if ( $memberId == $this->memberData['member_id'] )
				{
					$_masks = array( 'friend', 'public', 'private', 'galbum' );
				}
				elseif ( IPSMember::checkFriendStatus( $memberId, 0, true ) )
				{
					$_masks = array( 'friend', 'public', 'galbum' );
				}
			}
			
			/* If we specify album IDs, then fetch galbum ones also */
			if ( isset( $filters['albumId'] ) )
			{
				$_masks[] = 'galbum';
			}
			
			/* Add to where */
			if ( $memberId )
			{
				$_or[] = "( i." . $this->sqlWherePrivacy( array( 'friend', 'public', 'private', 'galbum' ) ) . ' AND i.member_id=' . $memberId . ' )';
				$_or[] = "( i." . $this->sqlWherePrivacy( array( 'public', 'galbum' ) ) . ' AND ( ' .  $this->DB->buildWherePermission( $this->member->perm_id_array, 'i.image_parent_permission', true ) . ') )';
			
				if ( $memberId == $this->memberData['member_id'] AND is_array( $this->memberData['_cache']['friends'] ) AND count( $this->memberData['_cache']['friends'] ) )
				{
					$_or[] = "( i." . $this->sqlWherePrivacy( array( 'public', 'friend' ) ) . ' AND i.member_id IN(' . implode( ",", array_slice( array_keys( $this->memberData['_cache']['friends'] ), 0, 300 ) ) . ') )';
				}
			}
			else
			{
				$where[] = "i." . $this->sqlWherePrivacy( $_masks ) . ' AND ( ' .  $this->DB->buildWherePermission( $this->member->perm_id_array, 'i.image_parent_permission', true ) . ')';
			}
		}
		
		/* add in */
		if ( count( $_or ) )
		{
			$where[] = '( ' . implode( " OR ", $_or ) . ' )';
		}

		/* Finish the set up off */
		if ( ! $this->registry->gallery->helper('albums')->canModerate() )
		{
			$where[] = "i.approved=1";
		}
		else
		{
			$where[] = "i.approved IN(0,1)";
		}
		
			
		/* Central processing function. CPF for short. */
		return $this->_fetchImages( $where, $filters );
	}
	
	/**
	 * Fetch images: Generic class
	 *
	 * Fetches an albums' images. It's good like that.
	 *
	 * @access	public
	 * @param	int		Album ID
	 * @param	array	Sort options (sortKey, sortOrder, offset, limit, unixCutOff)
	 * @return	@e array	Array of images and album information
	 */
	public function fetchAlbumImages( $albumId, $filters=array() )
	{	
		/* Set filters */
		$filters  = $this->_setFilters( $filters );
		$where    = array();
				
		/* Make sure we have a filter */
		$filters['albumId'] = $albumId;
		
		/* Finish the set up off */
		if ( ! $this->registry->gallery->helper('albums')->canModerate( $albumId ) )
		{
			$where[] = "i.approved=1";
		}
				
		/* Central processing function. CPF for short. */
		return $this->_fetchImages( $where, $filters );
	}
	
	/**
	 * Fetch images: Members
	 *
	 * Fetches a members' images. It's good like that.
	 *
	 * @access	public
	 * @param	mixed	Member ID of owner or array of owners
	 * @param	array	Sort options (sortKey, sortOrder, offset, limit, unixCutOff)
	 * @return	@e array	Array of images and album information
	 */
	public function fetchMembersImages( $member, $filters=array() )
	{
		/* Set filters */
		$filters = $this->_setFilters( $filters );
		$where   = array();
		$_or    = array();
		
		/* Set up member */
		if ( is_numeric( $member ) )
		{
			$member = array( $member );
		}
		
		if ( ! count( $member ) )
		{
			return array();
		}
		
		/* Go! */
		foreach( $member as $memberId )
		{
			$memberId = intval( $memberId );
			$_masks   = array( 'public' );
			
			/* Set up permission masks */
			if ( $memberId == $this->memberData['member_id'] )
			{
				$_masks = array( 'friend', 'public', 'private', 'galbum' );
			}
			elseif ( IPSMember::checkFriendStatus( $memberId, 0, true ) )
			{
				$_masks = array( 'friend', 'public' );
			}
			
			/* Add to where */
			$_or[] = "( i." . $this->sqlWherePrivacy( $_masks ) . ' AND i.member_id=' . $memberId . ' )';
		}
		
		/* add in */
		if ( count( $_or ) )
		{
			$where[] = implode( " OR ", $_or );
		}
		
		/* Finish the set up off */
		if ( ! $this->registry->gallery->helper('albums')->canModerate() )
		{
			$where[] = "i.approved=1";
		}
		
		/* Central processing function. CPF for short. */
		return $this->_fetchImages( $where, $filters );
	}
	
	/**
	 * Fetch images: Friends
	 *
	 * Fetches your friends images. It's good like that.
	 *
	 * @access	public
	 * @param	int		Member ID (the person viewing)
	 * @param	array	Sort options (sortKey, sortOrder, offset, limit, unixCutOff)
	 * @return	@e array	Array of images and album information
	 */
	public function fetchFriendsImages( $memberId, $filters=array() )
	{
		/* Set filters */
		$memberId = intval( $memberId );
		$filters  = $this->_setFilters( $filters );
		$where    = array();
		$_fids    = array();
		
		/* No member ID? No friends! */
		if ( ! $memberId )
		{
			return array();
		}
		
		/* Finish the set up off */
		if ( ! $this->registry->gallery->helper('albums')->canModerate() )
		{
			$where[] = "i.approved=1";
		}
		
		$where[] = "i." . $this->sqlWherePrivacy( array( 'friend', 'public' ) );
		
		/* Fetch friend IDs */
		$this->DB->build( array( 'select'		=> '*',
								 'from'			=> 'profile_friends',
								 'where'		=> 'friends_member_id=' . $memberId . ' AND friends_approved=1',
								 'limit'		=> array(0, 250) ) );
								 
		$outer	= $this->DB->execute();
		
		while( $row = $this->DB->fetch( $outer ) )
		{
			$_fids[] = intval( $row['friends_friend_id'] );
		}
		
		if ( count( $_fids ) )
		{
			$where[] = "i.member_id IN (" . implode( ",", $_fids ) . ")";
		}
		
		/* Central processing function. CPF for short. */
		return $this->_fetchImages( $where, $filters );
	}
	
	/**
	 * Fetch featured image
	 * 
	 * @param	array	Array of album IDs to select from
	 */
	public function fetchFeatured( $albumIds=null )
	{
		/* Switched off */
		if ( $this->settings['gallery_feature_image'] == 'none' )
		{
			return null;
		}
		
		if ( $albumIds !== null )
		{
			if ( is_numeric( $albumIds ) )
			{
				$albumIds = array( $albumIds );
			}
			else if ( is_array( $albumIds ) && isset( $albumIds['album_id'] ) )
			{
				$albumIds = array( $albumIds['album_id'] );
			}
		}
		
		/* Auto */
		if ( $this->settings['gallery_feature_image'] == 'auto' )
		{
			$feature = $this->fetchImages( $this->memberData['member_id'], array( 'albumId' => $albumIds, 'featured' => true, 'limit' => 1, 'sortKey' => 'random', 'unixCutOff' => GALLERY_A_YEAR_AGO ) );
			return array_pop( $feature );
		}
		else
		{
			/* Cover images */
			if ( $albumIds )
			{
				$albums = $this->gallery->helper('albums')->fetchAlbumsByFilters( array( 'album_parent_id' => $albumIds, 'isViewable' => true, 'limit' => 250, 'sortKey' => 'date', 'sortOrder' => 'desc' ) );
			}
			else
			{
				$albums = $this->gallery->helper('albums')->fetchAlbumsByFilters( array( 'isViewable' => true, 'limit' => 250, 'sortKey' => 'date', 'sortOrder' => 'desc' ) );
			}
			
			if ( count( $albums ) )
			{
				$ids = array();
				
				foreach( $albums as $id => $data )
				{
					if ( $data['album_cover_img_id'] )
					{
						$ids[] = $data['album_cover_img_id'];
					}
				}
			}
			
			if ( count( $ids ) )
			{
				$feature = $this->fetchImages( null, array( 'imageIds' => $ids, 'limit' => 1, 'sortKey' => 'random', 'featured' => true ) );
				
				if ( count( $feature ) )
				{
					return array_pop( $feature );
				}
			}
		}
		
		return null;
	}
	
	/**
	 * Generate photostrip
	 *
	 * @access	public
	 * @param	array  	Image array
	 * @return	@e string	Photostrip html
	 */
	public function fetchPhotoStrip( $image, $jumpDirection=null, $directionPos=null )
	{
		/* Init */
		$images       = array();
		$directionPos = ( $directionPos == null ) ? 0 : $directionPos;
		
		/* Set up */
		if ( is_numeric( $image ) )
		{
			$image = $this->fetchImage( $image );
		}
		
		/* Got anything? */
		if ( ! count( $image ) OR ! isset( $image['id'] ) )
		{
			return array();
		}
		
		/* Fetch album */
		$album = $this->registry->gallery->helper('albums')->fetchAlbumsById( $image['img_album_id'] ); 
		
		//-------------------------------------------------------
		// Category or album?
		//-------------------------------------------------------
		
		$where = 'img_album_id=' . $image['img_album_id'];
		$order = $album['album_sort_options__key'] ? $album['album_sort_options__key'] : 'idate';
		$dir   = strtolower( $album['album_sort_options__dir'] );

		$name  = $this->album['name'];
		$where = $this->registry->gallery->helper('albums')->canModerate( $album['album_id'] ) ? $where : $where . ' AND approved=1';
		$_jd   = '';
		
		/* Switch jumps if we need to */
		if ( $jumpDirection )
		{
			if ( $dir != 'asc' )
			{
				$_jd = ( $jumpDirection == 'left' ) ? 'right' : 'left';
			}
			else
			{
				$_jd = $jumpDirection;
			}
		}
		
		/* If we jump left, we want 4 more images AFTER the current one */
		if ( $_jd == 'left' )
		{
			$where .= " AND " . $order . " < " . intval( $image[ $order ] );
			$dir    = 'desc';
		}
		/* If we jump right, we want 4 more images BEFORE the current one */
		else if ( $_jd == 'right' )
		{
			$where .= " AND " . $order . " > " . intval( $image[ $order ] );
			$dir    = 'asc';
		}
		
		/* Select images */
		$this->DB->build( array( 'select' 	=> "id, caption, caption_seo, masked_file_name, directory, media, media_thumb, thumbnail",
								 'from'		=> 'gallery_images',
								 'where' 	=> $where,
								 'order' 	=> $order . ' ' . $dir ) );
										
		$res = $this->DB->execute();

		$total = $this->DB->getTotalRows( $res );
		$cache = array();
		$seen  = 0;
		
		/* Loop and go nuts */
		while( $row = $this->DB->fetch( $res ) )
		{
			/* Increment seen flag */
			$seen++;
			
			/* A JUMP TO THE RIGHT */
			if ( $jumpDirection == 'right' )
			{
				$directionPos++;
				$images[ $directionPos ] = $row;
			}
			/* A JUMP TO THE LEFT */
			else if ( $jumpDirection == 'left' )
			{
				$directionPos--;
				$images[ $directionPos ] = $row;
			}
			/* PUT YOUR HANDS ON YOUR HIPS */
			else
			{
				if ( $row['id'] == $image['id'] )
				{
					/* This is the 'center' image, so store it at the center position */
					$images[ 0 ] = $row;
					
					/* Store nav cache */
					$this->_navCache['now'] = $image;
					
					/* Populate previous two ids */
					if ( array_key_exists( ($seen - 1), $cache ) )
					{
						$images[ -1 ] = $cache[ $seen - 1 ];
						
						/* Store nav cache */
						$this->_navCache['prev'] = $cache[ $seen - 1 ];
					}
					
					if ( array_key_exists( ($seen - 2), $cache ) )
					{
						$images[ -2 ] = $cache[ $seen - 2 ];
					}
				}
				else
				{
					if ( isset( $images[0]['id'] ) AND ! isset( $images[1]['id'] ) )
					{
						/* This must be the next one */
						$images[1] = $row;
						
						/* Store nav cache */
						$this->_navCache['next'] = $row;
					}
					/* Do we have the next image? */
					else if ( isset( $images[1]['id'] ) AND ! isset( $images[2]['id'] ) )
					{
						/* This must be the next one */
						$images[2] = $row;
					}
				}
			}
			
			/* Add to the cache */
			$cache[ $seen ] = $row;
		
			/* Only keep last 5 seen 'cos that's all we care about */
			if ( count( $cache ) > 5 )
			{ 
				/* Delete first entry without re-indexing keys */
				unset( $cache[ $seen - 5 ] );
			}
			
			/* Are we done? */
			if ( count( $images ) == 5 )
			{
				/* We're all done */
				break;
			}
		}
		
		/* Sort, add thumbies and return */
		ksort( $images );
		
		foreach( $images as $id => $im )
		{
			$im['thumb'] = $this->makeImageLink( $im, array( 'type' => 'thumb' ) );
			
			$images[ $id ] = $im;
		}
		
		$return = array( 'total' => $total, 'photos' => $images );
		
		return ( $jumpDirection ) ? $return : $this->registry->output->getTemplate( 'gallery_img' )->photostrip( $album, $return );
	}
	
	/**
	 * Fetch next/previous links
	 * Returns the image that is next and previous. If you are at left or right of stream then that element will be null
	 * 
	 * @access	public
	 * @param	int		Image id
	 * 
	 */
	public function fetchNextPrevImages( $imageId )
	{
		if ( ! isset( $this->_navCache['now'] ) OR $imageId != $this->_navCache['now']['id'] )
		{
			$this->fetchPhotoStrip( $imageId );
		}
		
		return $this->_navCache;
	}

	/**
	 * Is this image viewable by the current user?
	 * 
	 * @param mixed $image
	 * @param mixed $album
	 * @return boolean
	 */
	public function isViewable( $image, $album=null )
	{
		if ( is_numeric( $image ) )
		{
			$image = $this->fetchImage( $image );
		}
		
		if ( $album === null )
		{
			$album = $this->registry->gallery->helper('albums')->fetchAlbumsById( $image['img_album_id'] );
		}
		else if ( is_numeric( $album ) )
		{
			$album = $this->registry->gallery->helper('albums')->fetchAlbumsById( $album );
		}
		
		/* Unapproved? */
		if ( ! $this->isOwner( $image ) )
		{
			if ( ! $image['approved'] AND ! $this->registry->gallery->helper('albums')->canModerate( $album ) )
			{
				return false;
			}
		}
		
		return $this->registry->gallery->helper('albums')->isViewable( $album );
	}
	
	/**
	 * Is this image owned by the current user?
	 * 
	 * @param mixed $image
	 * @param mixed $member
	 * @return boolean
	 */
	public function isOwner( $image, $member=null )
	{
		if ( is_numeric( $image ) )
		{
			$image = $this->fetchImage( $image );
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
		
		return ( $member['member_id'] == $image['member_id'] ) ? true : false;
	}
	
	/**
	 * Process image
	 * 
	 * @access	public
	 * @param	array	Of Image Data
	 * @return	@e array	Of ProcessEd ImAaeg DatA
	 */
	public function processImage( $image )
	{
		/* Item marking */
		return $image;
	}
	
	/**
	 * Resync image
	 *
	 * @access	public
	 * @param	mixed		Either image ID or image array
	 * @return	@e void
	 */
	public function resync( $image )
	{
		/* Set up */
		if ( is_numeric( $image ) )
		{
			$image = $this->fetchImage( $image );
		}
		
		/* Fetch queued comments */
		$que = $this->DB->buildAndFetch( array( 'select' => 'count(*) as ued',
												'from'   => 'gallery_comments',
												'where'  => 'img_id=' . intval( $image['id'] ) . ' AND approved=0' ) );
												
		$tot = $this->DB->buildAndFetch( array( 'select' => 'count(*) as al',
												'from'   => 'gallery_comments',
												'where'  => 'img_id=' . intval( $image['id'] ) . ' AND approved=1' ) );
		
		$las = $this->DB->buildAndFetch( array( 'select' => 'MAX(post_date) as t',
												'from'   => 'gallery_comments',
												'where'  => 'img_id=' . intval( $image['id'] ) . ' AND approved=1' ) );
												
		/* Oopdate */
		$this->DB->update( 'gallery_images', array( 'comments'        => intval( $tot['al']  ),
													'lastcomment'	  => intval( $las['t'] ),
													'comments_queued' => intval( $que['ued'] ) ), 'id=' . intval( $image['id'] ) );
	}
	
	/**
	 * Generates the tag for an image. If $tn is set, then it will
	 * generate a tag for the image thumbnail
	 * 
	 * @since	1.0
	 * @access	public
	 * @param	array 	Image data
	 * @param	array	options:   type => (thumb, medium, full)
	 * @param	bool	Create medium link
	 * @return	@e string
	 */
	public function makeImageTag( $image, $opts=array() )
	{
		if ( ! $image['id'] )
		{
			return $this->makeNoPhotoTag( $opts );
		}
		
		if ( $opts['type'] != 'thumb' AND $opts['type'] != 'small' )
		{
			if ( ! $this->checkBandwidth() )
			{
				return $this->lang->words['bwlimit'];
			}
		}
		
		if ( $opts['h1image'] )
		{
			$opts['thumbClass'] = 'ipsUserPhoto ipsUserPhoto_medium left';
			$opts['type'] = 'thumb';
		}
		
		/* Class */
		$opts['thumbClass'] = ( ! empty( $opts['thumbClass'] ) ) ? $opts['thumbClass'] : 'galattach';
		
		/* Is a cover image for an album? */
		if ( isset( $opts['coverImg'] ) AND $opts['coverImg'] === true )
		{
			$opts['thumbClass'] .= ' cover_img___xx___';
			$opts['link-type']   = 'album';
		}
		
		$i_am_new    = ( isset( $image['_isRead'] ) && ! $image['_isRead'] ) ? ' hello_i_am_new' : '';
		
		$i_am_hidden = ( isset( $image['approved'] ) && ! $image['approved'] ) ? ' hello_i_am_hidden' : '';
		
		//------------------------
		// Thumbnail?
		//------------------------
		
		$tn		= $opts['type'] == 'thumb' 	? '&amp;tn=1' 	: '';
		$thumb  = $opts['type'] == 'thumb' 	? 'tn_' 		: '';
		$small  = $opts['type'] == 'small'  ? 'sml_'		: '';
		
		//------------------------
		// Directory?
		//------------------------
		
		$dir 		= $image['directory'] ? "&amp;dir={$image['directory']}/" 	: "";
		$directory 	= $image['directory'] ? "{$image['directory']}/" 			: "";

		if ( ! file_exists( $this->settings['gallery_images_path'].'/'.$directory.$thumb.$image['masked_file_name'] ) )
		{
			$tn 	      = '';
			$thumb	      = '';
			$small        = '';
			$opts['type'] ='full';
		}		
		
		//------------------------
		// Update bandwidth, if required
		//------------------------
		
		if ( $this->settings['gallery_detailed_bandwidth'] && $this->request['section'] == 'viewimage' )
		{
		  	if ( $opts['type'] != 'thumb' AND $opts['type'] != 'small' AND $opts['type'] != 'medium' AND empty( $opts['coverImg'] ) )
		  	{
		 		$filename = ( $tn == 0 AND $opts['type'] == 'medium' ) ? ( $image['medium_file_name'] ? $image['medium_file_name'] : $image['masked_file_name'] ) : $image['masked_file_name'];

				$imagensert = array( 'member_id' 	=> $this->memberData['member_id'], 
								 	 'file_name' 	=> $image['masked_file_name'],
								 	 'bdate'	 	=> time(),
								 	 'bsize' 		=> $image['file_size'],
								);
				
				$this->DB->insert( 'gallery_bandwidth', $imagensert, true );
		  	}
		}
		
		//------------------------
		// Is this media?
		//------------------------
		
		if ( $image['media'] ) 
		{
			return $this->gallery->helper('media')->getThumb( $image, $opts );
		}
		else 
		{
			if( $this->settings['gallery_web_accessible'] == 'yes' )
			{
				$imagemg_url = "{$this->settings['gallery_images_url']}/{$directory}{$thumb}";
							
				if ( $opts['type'] == 'medium' )
				{
					$size = '';
					
					if ( isset( $image['_data'] ) && is_array( $image['_data']['sizes']['medium'] ) AND $image['_data']['sizes']['medium'][0] > 0 )
					{
						$size = " width='" . intval( $image['_data']['sizes']['medium'][0] ). "' height='" . intval( $image['_data']['sizes']['medium'][1] ) . "' ";
					}
					
					$imagemg_tag = "<img src='{$imagemg_url}{$image['medium_file_name']}' class='galmedium{$i_am_hidden}' title='{$image['caption']}' {$size} alt='{$image['caption']}' id='image_view_{$image['id']}' />";
					
					if ( ! empty( $opts['link-type'] ) && $opts['link-type'] == 'src' )
					{
						return "{$imagemg_url}{$image['medium_file_name']}";
					}
					else if ( ! empty( $opts['link-type'] ) AND ( $opts['link-type'] == 'none' OR $opts['link-type'] == 'page' OR $opts['link-type'] == 'album' ) )
					{
						return $imagemg_tag;
					}
					else
					{
						return "<a href='{$imagemg_url}{$image['masked_file_name']}' class='gal' title='{$image['caption']}' alt='{$image['caption']}'>{$imagemg_tag}</a>";
					}
				}
				else if ( $opts['type'] == 'small' )
				{
					$size = '';
					
					if ( isset( $image['_data'] ) && is_array( $image['_data']['sizes']['small'] ) AND $image['_data']['sizes']['small'][0] > 0 )
					{
						$size = " width='" . intval( $image['_data']['sizes']['small'][0] ). "' height='" . intval( $image['_data']['sizes']['small'][1] ) . "' ";
					}
					
					$imagemg_tag = "<img src='{$this->settings['gallery_images_url']}/{$directory}{$small}{$image['masked_file_name']}' {$size} class='galsmall{$i_am_new}{$i_am_hidden}' title='{$image['caption']}' alt='{$image['caption']}' id='image_view_{$image['id']}' />";
					
					if ( ! empty( $opts['link-type'] ) && $opts['link-type'] == 'src' )
					{
						return "{$this->settings['gallery_images_url']}/{$directory}{$small}{$image['masked_file_name']}";
					}
					else if ( ! empty( $opts['link-type'] ) AND ( $opts['link-type'] == 'none' OR $opts['link-type'] == 'page' OR $opts['link-type'] == 'album' ) )
					{
						return $imagemg_tag;
					}
					else
					{
						return "<a href='{$imagemg_url}{$image['masked_file_name']}' class='gal' title='{$image['caption']}' alt='{$image['caption']}'>{$imagemg_tag}</a>";
					}
				}
				else
				{
					if ( $this->settings['gallery_use_square_thumbnails'] )
					{
						$size = "width='100' height='100'";
					}
					else
					{
						$imageData = unserialize( $image['image_data'] );
						$size = "width='{$imageData['sizes']['thumb'][0]}' height='{$imageData['sizes']['thumb'][1]}'";
					}
					
					if ( $thumb )
					{
						if ( isset( $image['_data'] ) && is_array( $image['_data']['sizes']['thumb'] ) AND $image['_data']['sizes']['thumb'][0] > 0 )
						{
							$size = " width='" . intval( $image['_data']['sizes']['thumb'][0] ). "' height='" . intval( $image['_data']['sizes']['thumb'][1] ) . "' ";
						}
					}
					else
					{
						if ( isset( $image['_data'] ) && is_array( $image['_data']['sizes']['max'] ) AND $image['_data']['sizes']['max'][0] > 0 )
						{
							$size = " width='" . intval( $image['_data']['sizes']['max'][0] ). "' height='" . intval( $image['_data']['sizes']['max'][1] ) . "' ";
						}
					}
					
					if ( ! empty( $opts['link-type'] ) && $opts['link-type'] == 'src' )
					{
						return "{$imagemg_url}{$image['masked_file_name']}";
					}
					return "<img src='{$imagemg_url}{$image['masked_file_name']}' {$size} class='{$opts['thumbClass']} {$i_am_new}{$i_am_hidden}' title='{$image['caption']}' alt='{$image['caption']}' id='{$thumb}image_view_{$image['id']}' />";		
				}
			}
			/* MASKED URLS */
			else
			{
				$imagemg_url = "{$this->settings['board_url']}/index.php?app=gallery&amp;module=images&amp;section=img_ctrl&amp;img={$image['id']}{$tn}";
									
				if ( $opts['type'] == 'medium' )
				{
					$size = '';
					
					if ( isset( $image['_data'] ) && is_array( $image['_data']['sizes']['medium'] ) AND $image['_data']['sizes']['medium'][0] > 0 )
					{
						$size = " width='" . intval( $image['_data']['sizes']['medium'][0] ). "' height='" . intval( $image['_data']['sizes']['medium'][1] ) . "' ";
					}

					$imagemg_tag = "<img src='{$imagemg_url}&amp;file=med' class='{$opts['thumbClass']}' title='{$image['caption']}' {$size} alt='{$image['caption']}' id='{$thumb}image_view_{$image['id']}' />";
					
 					if ( ! empty( $opts['link-type'] ) AND $opts['link-type'] == 'src' )
					{
						return $imagemg_tag;
					}
					else if ( ! empty( $opts['link-type'] ) AND ( $opts['link-type'] == 'none' OR $opts['link-type'] == 'page' OR $opts['link-type'] == 'album' ) )
					{
						return $imagemg_tag;
					}
					else
					{
						return "<a href='{$imagemg_url}' class='gal' title='{$image['caption']}' alt='{$image['caption']}'>{$imagemg_tag}</a>";
					}
				}
				else if ( $opts['type'] == 'small' )
				{
					$size = '';
					
					if ( isset( $image['_data'] ) && is_array( $image['_data']['sizes']['small'] ) AND $image['_data']['sizes']['small'][0] > 0 )
					{
						$size = " width='" . intval( $image['_data']['sizes']['small'][0] ). "' height='" . intval( $image['_data']['sizes']['small'][1] ) . "' ";
					}
					
					$imagemg_tag = "<img src='{$imagemg_url}&amp;file=small' class='{$opts['thumbClass']}' title='{$image['caption']}' {$size} alt='{$image['caption']}' id='{$small}image_view_{$image['id']}' />";
					
 					if ( ! empty( $opts['link-type'] ) AND $opts['link-type'] == 'src' )
					{
						return $imagemg_tag;
					}
					else
					{
						return "<a href='{$imagemg_url}' class='galsmall {$i_am_new}{$i_am_hidden}' title='{$image['caption']}' alt='{$image['caption']}'>{$imagemg_tag}</a>";
					}
				}
				else
				{
					if ( ! empty( $opts['link-type'] ) && $opts['link-type'] == 'src' )
					{
						return "{$imagemg_url}";
					}
					
					$size = "width='100' height='100'";
					
					if ( $thumb )
					{
						if ( isset( $image['_data'] ) && is_array( $image['_data']['sizes']['thumb'] ) AND $image['_data']['sizes']['thumb'][0] > 0 )
						{
							$size = " width='" . intval( $image['_data']['sizes']['thumb'][0] ). "' height='" . intval( $image['_data']['sizes']['thumb'][1] ) . "' ";
						}
					}
					else
					{
						if ( isset( $image['_data'] ) && is_array( $image['_data']['sizes']['max'] ) AND $image['_data']['sizes']['max'][0] > 0 )
						{
							$size = " width='" . intval( $image['_data']['sizes']['max'][0] ). "' height='" . intval( $image['_data']['sizes']['max'][1] ) . "' ";
						}
					}

					return "<img src='{$imagemg_url}' class='{$opts['thumbClass']}{$i_am_new}{$i_am_hidden}' {$size} title='{$image['caption']}' id='{$thumb}image_view_{$image['id']}' alt='{$image['caption']}' />";
 				}
 			}
		}
	}
	
	/**
	 * Make no photo image tag
	 *
	 * @access	public
	 * @param	array 		Options
	 * @return	@e string
	 */
	public function makeNoPhotoTag( $opts=array() )
	{
		$img   = '';
		$class = 'galattach';
		
		if ( $opts['h1image'] )
		{
			$class = 'ipsUserPhoto ipsUserPhoto_medium left';
			$opts['type'] = 'thumb';
		}
		
	    /* Is a cover image for an album? */
		if ( isset( $opts['coverImg'] ) AND $opts['coverImg'] === true )
		{
			$class .= ' cover_img___xx___';
		}
		
		switch( $opts['type'] )
		{
			default:
			case 'thumb':
				$img = 'nophotothumb.png';
			break;
			case 'strip':
				$img = 'nopicstrip.png';
			break;
		}
		
		if ( ! empty( $opts['link-type'] ) && $opts['link-type'] == 'src' )
		{
			return "{$this->settings['img_url']}/gallery/{$img}";
		}
		
		return "<img src='{$this->settings['img_url']}/gallery/{$img}' width='100' height='100' class='{$class}' />";
	
	}
	/**
	 * Check bandwidth usage
	 *
	 * @access	protected
	 * @return	@e bool		Show image or not
	 */
	public function checkBandwidth()
	{
		$show	= true;
		
		if ( $this->settings['gallery_detailed_bandwidth'] )
		{  		
			$q = array();
			
	  		if ( $this->memberData['g_max_transfer'] != -1 )
	  		{
		 		$q[] = " SUM( bsize ) AS transfer ";
	  		}

	  		if ( $this->memberData['g_max_views'] != -1 )
	  		{
		 		$q[] = " COUNT( bsize ) AS views ";
	  		}

	  		if( $q )
	  		{
		 		if( ! $this->memberData['bandwidth'] )
		 		{
					$q		= implode( ",", $q );
					$time	= time() - ( 60 * 60 * 24 );

					$this->memberData['bandwidth'] = $this->DB->buildAndFetch( array( 'select' => $q, 'from' => 'gallery_bandwidth', 'where' => "member_id={$this->memberData['member_id']} AND bdate > {$time}" ) );
				}

		 		if( ! empty( $this->memberData['g_max_transfer'] ) && 
		 			$this->memberData['bandwidth']['transfer'] > $this->memberData['g_max_transfer']*1024 
		 		  )
		 		{
					$show = false;
		 		}

		 		if( ! empty( $this->memberData['g_max_views'] ) && 
		 			$this->memberData['bandwidth']['views'] > $this->memberData['g_max_views'] 
		 		  )
		 		{
					$show = false;
		 		}
	  		}
		}
		
		return $show;
	}

	/**
	 * Generates a link to an image.  If $tn is set, then
	 * it will generate a thumbnail tag.  Checks bandwith too
	 * 
	 * @since	1.0
	 * @access	public
	 * @param	array 	Image data
	 * @param	bool	Use thumbnail
	 * @param	int		Override image id
	 * @param	bool	Don't check bandwidth
	 * @param	bool	Don't wrap
	 * @param	string	Calling location
	 * @param	bool	Set to true if this is category/album cover link
	 * @return	@e string
	 */
	public function makeImageLink( $image, $opts=array() )
	{
		$show   = true;
		$album  = array();
		$target = ( IN_ACP ) ? ' target="_blank" ' : '';
		
		if ( ! $image['id'] )
		{
			return $this->makeNoPhotoTag( $opts );
		}
		
		/* Is this from an upload session? */
		if ( ! is_numeric( $image['id'] ) AND strlen( $image['id'] ) == 32 )
		{
			return $this->makeImageTag( $image, $opts );
		}
		
		/* Is a cover image for an album? */
		if ( isset( $opts['coverImg'] ) AND $opts['coverImg'] === true )
		{
			$opts['link-type']   = 'album';
		}
		
		if ( $opts['type'] != 'thumb' AND $opts['type'] != 'small' )
		{
			if ( ! IN_ACP ) 
			{
				$show = $this->checkBandwidth();
			}
		}
		
		/* Check some stuffs */
		if ( ! empty( $opts['link-type'] ) && $opts['link-type'] == 'album' )
		{
			/* try and avoid a new query */
			if ( ! empty( $image['album_id'] ) AND ! empty( $image['album_name'] ) )
			{
				$album = array( 'album_id'       => $image['album_id'],
								'album_name_seo' => ( ! empty( $image['album_name_seo'] ) ) ? $image['album_name_seo'] : IPSText::makeSeoTitle( $image['album_name'] ) );
			}
			else
			{
				$album = $this->gallery->helper('albums')->fetchAlbumsById( $image['img_album_id'] );
			}
		}
		
		if ( $show )
		{
		  	if ( $image['media'] )
		  	{
				$thumbnail = $this->makeImageTag( $image, $opts );
			 	
		  		if ( ! empty( $opts['link-type'] ) &&  $opts['link-type'] == 'album' )
			 	{
			 		$url = $this->registry->output->buildSEOUrl( "app=gallery&amp;album={$album['album_id']}", 'public', $album['album_name_seo'], 'viewalbum' );
			 	}
				else
				{
					$url = $this->registry->output->buildSEOUrl( "app=gallery&amp;image={$image['id']}", 'public', $image['caption_seo'], 'viewimage' );
				}
			 		
			 	return "<a href='{$url}' {$target} title='{$image['caption']}'>{$thumbnail}</a>";
		  	}
			else
		  	{
			 	if ( ! empty( $opts['link-type'] ) && $opts['link-type'] == 'album' )
			 	{
			 		$url = $this->registry->output->buildSEOUrl( "app=gallery&amp;album={$album['album_id']}", 'public', $album['album_name_seo'], 'viewalbum' );
			 	}
				else
				{
					$url = $this->registry->output->buildSEOUrl( "app=gallery&amp;image={$image['id']}", 'public', $image['caption_seo'], 'viewimage' );
				}
				
				if ( $opts['h1image'] )
				{
					return "<a href='{$url}' {$target} title='{$image['caption']}' class='ipsUserPhotoLink'>" . $this->makeImageTag( $image, $opts ) . "</a>";
				}
				else
				{			 	
			 		return "<a href='{$url}' {$target} title='{$image['caption']}'>" . $this->makeImageTag( $image, $opts ) . "</a>";
			 	}
		  	}
		}
		else
		{
		  	return $this->lang->words['bwlimit'];
		}
	}

	/**
	 * Generic method for validating access to an image
	 * 
	 * @since	2.2
	 * @access	public
	 * @param	mixed	Int for image ID, or array for image data
	 * @param	boolean	Return instead of outputting error
	 * @param	mixed	Int for member ID, array for member data or null for $this->memberData
	 * @return	mixed	image data / print error
	 */
	public function validateAccess( $image, $return=false, $member=null )
	{
		if ( is_numeric( $member ) )
		{
			$member = IPSMember::load( $member, 'all' );
		}
		elseif ( ! is_array( $member ) OR ! isset( $member['member_id'] ) )
		{
			$member = $this->memberData;
		}
		
		if ( is_numeric( $image ) )
		{
			$image = $this->fetchImage( $image );
		}

		if ( $member['member_id'] )
		{
			$perms = explode( ':', $member['gallery_perms'] );

			if( ! $perms[0] )
			{
				if( $return )
				{
					return false;
				}

			 	$this->registry->output->showError( 'no_permission', 107161, null, null, 403 );
			}
		}
		
		if ( ! $image['id'] )
		{
			if ( $return )
			{
				return false;
			}

			$this->registry->output->showError( 'img_not_found', 107163, null, null, 404 );
		}
		
		$album = $this->registry->getClass('gallery')->helper('albums')->fetchAlbum( $image['album_id'] );
	
		if ( ! $album['album_id'] )
		{
			if ( $return )
			{
				return false;
			}

			$this->registry->output->showError( 'no_permission', 107166, null, null, 404 );
		}
		
		if ( $this->fetchPrivacyFlag( $image ) == 'galbum' )
		{
			if ( ! $member['g_mod_albums'] AND ( $member['member_id'] != $image['member_id'] ) )
			{ 
				if ( $return )
				{
					return false;
				}
			
				$this->registry->output->showError( 'no_permission', 107166.1, null, null, 403 );
			}
		}
		else
		{
			if ( $this->fetchPrivacyFlag( $image ) != 'public' OR ! $image['approved'] )
			{
				if ( $album['album_owner_id'] != $member['member_id'] AND ! $member['g_mod_albums'] )
				{
					if ( $return )
					{
						return false;
					}
			
					$this->registry->output->showError( 'no_permission', 107167, null, null, 403 );
				}
			}
		}
		
		return $image;
	}

	/**
	 * Get the lattitude and longtitude of an image
	 * @param mixed $image
	 */
	public function getLatLon( $image )
	{
		if ( is_numeric( $image ) )
		{
			$image = $this->fetchImage( $image );
		}
		
		if ( $image['image_gps_lat'] && $image['image_gps_lon'] )
		{
			return array( $image['image_gps_lat'], $image['image_gps_lon'] );
		}
		else
		{
			return array( false, false );
		}
	}

	/**
	 * Updates the image with reverse geo look up data
	 * @param mixed $image
	 */
	public function setReverseGeoData( $image )
	{
		require_once( IPS_ROOT_PATH . 'sources/classes/mapping/bootstrap.php' );/*noLibHook*/
		$this->_mapping = classes_mapping::bootstrap( IPS_MAPPING_SERVICE );
		
		if ( is_numeric( $image ) )
		{
			$image = $this->fetchImage( $image );
		}
		
		if ( empty( $image['id'] ) )
		{
			trigger_error("Image data missing in setReverseGeoData");
			return false;
		}
		
		$latLon = $this->getLatLon( $image );
		
		if ( $latLon[0] !== false )
		{
			if ( empty( $image['image_loc_short'] ) )
			{
				$_data           = $this->_mapping->reverseGeoCodeLookUp( $latLon[0], $latLon[1] );
				$image_loc_short = $_data['geocache_short'];
			}
			else
			{
				$image_loc_short = $image['image_loc_short'];
			}
			
			if ( $image_loc_short && $image_loc_short !== false )
			{ 
				$this->DB->update( 'gallery_images', array( 'image_loc_short' => $image_loc_short ), 'id=' . intval( $image['id'] ) );
			}
		}
	}
	
	/**
	 * Extracts exif data
	 * 
	 * @access	public
	 * @param	string	path to file
	 * @return	@e array	exif data
	 * @since	2.1
	 */  
	public function extractExif( $file )
	{
		if( !$this->lang->words['check_key'] )
		{
			$this->lang->loadLanguageFile( array( 'public_meta' ), 'gallery' );
		}
		
		$return = array();
		$gps    = array();
		
		if( !extension_loaded('exif') )
		{
			return $return;
		}
		
		if( !file_exists( $file ) )
		{
			return $return;
		}
		
		if( @exif_imagetype( $file ) )
		{
			$data = array();
			$data = @exif_read_data( $file, 0, true );

			if( is_array($data) AND count($data) > 0 )
			{
				foreach( $data as $k => $v )
				{
					if ( $k == 'GPS' )
					{
						$gps = $data['GPS'];
						continue;
					}				

					foreach( $v as $k2 => $v2 )
					{
						if( (is_string($v2) OR is_numeric($v2)) AND !is_null($v2) )
						{
							$key = $k.'.'.$k2;
							
							if ( strstr( $key, 'UndefinedTag' ) )
							{
								/* Often has special chars that break serialized */
								continue;
							}

							if( array_key_exists( $key, $this->lang->words ) )
							{
								// Key exists
								
								if( !$this->lang->words[ $key ] )
								{
									// Set blank, let's skip this data
									continue;
								}
								else
								{
									if( array_key_exists( $key . '_map_' . $v2, $this->lang->words ) )
									{
										// This key has a mapping...
										$return[ $this->lang->words[ $key ] ] = $this->lang->words[ $key . '_map_' . $v2 ] ? $this->lang->words[ $key . '_map_' . $v2 ] : htmlspecialchars($v2);
									}
									else
									{
										$return[ $this->lang->words[ $key ] ] = htmlspecialchars($v2);
										
									}
								}
							}
							else
							{
								$return[ $key ] = htmlspecialchars($v2);
							}
						}
					}
				}
			}
		}
		
		if ( is_array( $gps ) AND count( $gps ) )
		{
			/* Can screw up serialized string */
			unset( $gps['GPSAltitudeRef'] );
			
			$return['GPS'] = $gps;
		}
		
		return $return;
	}
	
	/**
	 * Rebuilds exif data
	 * 
	 * @param	mixed		$image		Image Data
	 * @return	@e boolean
	 * 
	 * @todo	This function isn't used anywhere apparently, do we need it at all?
	 */
	public function rebuildExif( $image )
	{
		if ( is_numeric( $image ) )
		{
			$image = $this->fetchImage( $image );
		}
		
		if ( empty( $image['id'] ) )
		{
			return false;
		}
		
		/* Init */
		$dir   = $image['directory'] ? $image['directory'] . "/" : '';
		$large = $this->settings['gallery_images_path'] . '/' . $dir . $image['masked_file_name'];
		$orig  = $this->settings['gallery_images_path'] . '/' . $dir . $image['original_file_name'];
		
		$file  = ( file_exists( $orig ) ) ? $orig : ( file_exists( $large ) ? $large : null );
		
		if ( $file !== null )
		{
			$exif = $this->extractExif( $file );
			
			if ( is_array( $exif ) )
			{
				$this->DB->update( 'gallery_images', array( 'metadata' => serialize( $exif ) ), 'id=' . intval( $image['id'] ) );
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Converts EXIF GPS data to lat/lon
	 * 
	 * @param	array	$exif	Exif Data
	 * @return	@e string
	 */
	public function convertExifGpsToLatLon( array $exif )
	{
		if ( isset( $exif['GPSLatitudeRef'] ) && isset( $exif['GPSLatitude'] ) && isset( $exif['GPSLongitudeRef'] ) && isset( $exif['GPSLongitude'] ) )
		{
			return array( $this->_exifToCoords( $exif['GPSLatitudeRef'], $exif['GPSLatitude'] ), $this->_exifToCoords( $exif['GPSLongitudeRef'], $exif['GPSLongitude'] ) );
		}
		else
		{
			return array( 0, 0 );
		}
	}
	
	/**
	 * Converts degrees to coordinates
	 * @param string $ref
	 * @param array $coord
	 * @return string
	 */
	private function _exifToCoords( $ref, $coord )
	{
		$prefix = ( $ref == 'S' || $ref == 'W' ) ? '-' : '';
		
		return $prefix . sprintf( '%.6F', $this->_exifToNumber( $coord[0], '%.6F' ) + ( ( ( $this->_exifToNumber( $coord[1], '%.6F' ) * 60 ) + ( $this->_exifToNumber( $coord[2], '%.6F' ) ) ) / 3600 ) );
	}
	
	/**
	 * Converts degrees into int
	 * @param string $v
	 * @param formatting key $f
	 * @return string
	 */
	private function _exifToNumber( $v, $f )
	{		
		if ( strpos( $v, '/' ) === false )
		{
			return sprintf( $f, $v );
		}
		else
		{
			list( $base, $divider ) = split( "/", $v, 2 );
			
			if ( $divider == 0 )
			{
				return sprintf( $f, 0 );
			}
			else
			{
				return sprintf( $f, ( $base / $divider ) );
			}
		}
	}
	
	/**
	 * Extracts iptc data
	 *
	 * @access	public
	 * @param	string	path to file
	 * @return	@e array	exif data
	 * @since	2.1
	 */
	public function extractIptc( $file )
	{
		if( !$this->lang->words['check_key'] )
		{
			$this->lang->loadLanguageFile( array( 'public_meta' ), 'gallery' );
		}
				
		$return = array();
		
		if( !file_exists( $file ) )
		{
			return $return;
		}
		
		$size = @getimagesize( $file, $info);	  

		if( is_array($info) ) 
		{	
	  		$iptc = iptcparse( $info["APP13"] );
	  		
	  		if( is_array($iptc) AND count($iptc) )
	  		{
		  		foreach( array_keys($iptc) as $s ) 
		  		{		
			  		if( $s == '2#000' )
			  		{
				  		continue;
			  		}
			  		
			  		$key = $this->getIptcKey( $s );
			  		
			 		$c = count( $iptc[$s] );
			 
			 		for ($i=0; $i < $c; $i++)
			 		{
						$return[$key] = htmlspecialchars($iptc[$s][$i]);
			 		}
		  		}
	  		}
		}
			
		return $return;
	}
	
	/**
	 * Returns IPTC Name
	 *
	 * @access	public
	 * @param	string	$key	Key to get the IPTC name for
	 * @return	@e string
	 */
	private function getIptcKey( $key )
	{
		$keys	= array(	'2#003'	=> 'IPTC.ObjectTypeReference',
							'2#010'	=> 'IPTC.Urgency',
							'2#005'	=> 'IPTC.ObjectName',
							'2#120'	=> 'IPTC.Caption',
							'2#110'	=> 'IPTC.Credit',
							'2#015'	=> 'IPTC.Category',
							'2#020'	=> 'IPTC.SupplementalCategories',
							'2#040'	=> 'IPTC.ActionAdvised',
							'2#055'	=> 'IPTC.DateCreated',
							'2#060'	=> 'IPTC.TimeCreated',
							'2#025'	=> 'IPTC.Keywords',
							'2#080'	=> 'IPTC.By-line',
							'2#085'	=> 'IPTC.By-LineTitle',
							'2#090'	=> 'IPTC.City',
							'2#095'	=> 'IPTC.State',
							'2#101'	=> 'IPTC.Country',
							'2#103'	=> 'IPTC.OTR',
							'2#105'	=> 'IPTC.Headline',
							'2#115'	=> 'IPTC.Source',
							'2#116'	=> 'IPTC.CopyrightNotice',
							'2#118'	=> 'IPTC.Contact',
							'2#122'	=> 'IPTC.CaptionWriter',
						);
		
		if( $this->lang->words[ 'IPTC' . str_replace( "#", "", $key ) ] )
		{
			return $this->lang->words[ 'IPTC' . str_replace( "#", "", $key ) ];
		}
		else if( array_key_exists( $key, $keys ) )
		{
			return $keys[$key];
		}
		else
		{
			return $key;
		}
	}
	
	/**
	 * Returns true if the image should get a watermark applied
	 * 
	 * @param	mixed		$album		Album ID or array
	 * @return	@e bool
	 */
	public function applyWatermark( $album )
	{
		if ( is_numeric($album) )
		{
			$album = $this->registry->gallery->helper('albums')->fetchAlbumsById( $album );
		}
		
		if ( ! $album['album_id'] )
		{
			//trigger_error( 'No album ID/data passed to applyWatermark', E_USER_WARNING );
			# Bug 29584
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
		
		/* First let's check parent albums */
		$parents = $this->registry->gallery->helper('albums')->fetchAlbumParents( $album['album_id'] );
		
		if ( count($parents) )
		{
			foreach( $parents as $_album )
			{
				/* Not a global one? */
				if ( !$_album['album_is_global'] )
				{
					continue;
				}
				
				/* Watermark disabled or enforced? */
				switch( $_album['album_watermark'] )
				{
					default:
					case 0:
						return false;
						break;
					case 1:
						// Skip this, we always assume true by default but leave it for break:
						#$canWater = true;
						break;
					case 2:
						return true;
						break;
					case 3:
						// Skip this, we always assume true by default
						/*if ( $this->registry->gallery->helper('albums')->isContainerOnly( $album ) )
						{
							return true;
						}
						else
						{
							$canWater = true;
						}*/
						break;
				}
			}
		}
		
		/* Not a container? */
		if ( $this->registry->gallery->helper('albums')->isGlobal( $album ) && !$this->registry->gallery->helper('albums')->isContainerOnly( $album ) )
		{
			if ( $album['album_watermark'] == 1 )
			{
				return false;
			}
		}
		
		/* Time to return home.. */
		return ( $album['album_watermark'] != 0 ) ? true : false;
	}

	/**
	 * Builds an image sized copies based on the settings
	 * 
	 * @param	array		$image		Image data
	 * @param	array		$opts		Build options (album_id|destination|watermark)
	 * @return	@e bool
	 */
	public function buildSizedCopies( $image=array(), $opts=array() )
	{
		/* Init */
		$dir       = $image['directory'] ? $image['directory'] . "/" : '';
		$_return   = array();
		$imData    = array();
		$_save     = array();
		
		/* Settings setup */
		$settings  = array( 'image_path'	=> $this->settings['gallery_images_path'] . '/' . $dir, 
						    'image_file'	=> $image['masked_file_name'],
						    'im_path'		=> $this->settings['gallery_im_path'],
						    'temp_path'		=> DOC_IPS_ROOT_PATH . '/cache/tmp',
						    'jpg_quality'	=> GALLERY_JPG_QUALITY,
						    'png_quality'	=> GALLERY_PNG_QUALITY );
		
		/* Images setup */
		$_default  = $settings['image_path'] . $image['masked_file_name'];
		$thumb     = $settings['image_path'] . 'tn_' . $image['masked_file_name'];
		$medium    = $settings['image_path'] . 'med_' . $image['masked_file_name'];
		$small     = $settings['image_path'] . 'sml_' . $image['masked_file_name'];
		$watermark = ( empty($this->settings['gallery_watermark_path']) || !is_file($this->settings['gallery_watermark_path']) ) ? false : (empty($opts['watermark']) ? $this->applyWatermark($image['img_album_id']) : $opts['watermark']);
		
		
		/* @todo Move into settings */
		$this->settings['gallery_size_thumb']  = 100;
		$this->settings['gallery_size_small']  = 240;
		
		/* Ensure we have options */
		$_table     = 'gallery_images';
		$_field     = 'medium_file_name';
		$_original  = 'original_file_name';
		$_feature   = 'image_feature_flag';
		$_thumb     = '';
		$_dataField = 'image_data';
		$_where     = 'id=' . $image['id'];
		
		/* Auto majestically assign the table */
		if ( empty($opts['destination']) AND !is_numeric($image['id']) AND strlen($image['id']) == 32 )
		{
			$opts['destination'] = 'uploads';
		}
		
		/* Tmp upload table */
		if ( isset( $opts['destination'] ) and $opts['destination'] == 'uploads' )
		{
			$_table     = 'gallery_images_uploads';
			$_field     = 'upload_medium_name';
			$_original  = 'upload_file_name_original';
			$_feature   = 'upload_feature_flag';
			$_thumb     = 'upload_thumb_name';
			$_dataField = 'upload_data';
			$_where     = "upload_key='{$image['id']}'";
		}
		
		/* A little set up here */
		$_save[ $_dataField ] = ( IPSLib::isSerialized( $image[ $_dataField ] ) ) ? unserialize( $image[ $_dataField ] ) : '';
		$_save[ $_field ]     = '';
		
		if ( ! empty( $_thumb  ) )
		{
			$_save[ $_thumb ] = '';
		}
		
		/* Basic checks */
		if ( ! count($image) )
		{
			return false;
		}
		
		/* Not a media file */
		if ( $image['media'] )
		{
			return false;
		}
		
		/* Ensure we have a file on disk */
		if ( ! file_exists( $_default ) )
		{
			return false;
		}
		else
		{
			@chmod( $_default, IPS_FILE_PERMISSION );
		}
		
		/* Get kernel image library */
		require_once( IPS_KERNEL_PATH . 'classImage.php' );/*noLibHook*/
		
		/* Unlink the images */
		if ( file_exists( $thumb ) )
		{
			@unlink( $thumb );
		}
		
		if ( file_exists( $medium ) )
		{
			@unlink( $medium );
		}
		
		$img = ips_kernel_image::bootstrap( $this->settings['gallery_img_suite'] );
		
		/* Prep Thumbnail */
		if ( $img->init( $settings ) )
		{
			/* Set size */
			if ( $this->settings['gallery_use_square_thumbnails'] )
			{
				$return = $img->croppedResize( $this->settings['gallery_size_thumb'], $this->settings['gallery_size_thumb'] );
				$_save[ $_dataField ]['sizes']['thumb']    = array( $return['newWidth'], $return['newHeight'] );
				$_save[ $_dataField ]['sizes']['original'] = array( $return['originalWidth'], $return['originalHeight'] );
			}
			else
			{
				$return = $img->resizeImage( $this->settings['gallery_size_thumb'], $this->settings['gallery_size_thumb'], false, false, array( $this->settings['gallery_size_thumb'], $this->settings['gallery_size_thumb'] ) );
				$_save[ $_dataField ]['sizes']['thumb'] = array( $return['newWidth'], $return['newHeight'] );
				$_save[ $_dataField ]['sizes']['original'] = array( $return['originalWidth'], $return['originalHeight'] );
			}
			
			if ( $img->writeImage( $thumb ) )
			{
				@chmod( $thumb, IPS_FILE_PERMISSION );
				
				if ( ! empty( $_thumb  ) )
				{
					$_save[ $_thumb ] = 'tn_'  . $image['masked_file_name'];
				}			
			}
		}
		
		unset( $img );
		
		/* Prep medium size */
		if( $this->settings['gallery_medium_width'] || $this->settings['gallery_medium_height'] )
		{
			/* Init Lib */
			$img = ips_kernel_image::bootstrap( $this->settings['gallery_img_suite'] );
			
			if ( $img->init( $settings ) )
			{
				$return = $img->resizeImage( $this->settings['gallery_medium_width'], $this->settings['gallery_medium_width'] );
				
				/* Set size */
				$_save[ $_dataField ]['sizes']['medium'] = array( $return['newWidth'], $return['newHeight'] );
			
				/* Water mark image */
				if ( $watermark )
				{
					$img->addWatermark( $this->settings['gallery_watermark_path'], $this->settings['gallery_watermark_opacity'] );
				}

				if ( $watermark || ( count($return) && empty($return['noResize']) ) )
				{
					if ( $img->writeImage( $medium ) )
					{
						@chmod( $medium, IPS_FILE_PERMISSION );
						
						$_save[ $_field ] = 'med_' . $image['masked_file_name'];
					}
					else
					{
						$_save[ $_field ] = $image['masked_file_name'];
					}
				}
				else
				{
					$_save[ $_field ] = $image['masked_file_name'];
				}
			}
			
			unset( $img );
		}
		
		/* Prep small size */
		if( $this->settings['gallery_size_small'] )
		{
			/* Init Lib */
			$img = ips_kernel_image::bootstrap( $this->settings['gallery_img_suite'] );
			
			if ( $img->init( $settings ) )
			{
				$return = $img->resizeImage( $this->settings['gallery_size_small'], $this->settings['gallery_size_small'] );
				
				/* Set size */
				$_save[ $_dataField ]['sizes']['small'] = array( $return['newWidth'], $return['newHeight'] );
				
				/* Small image is too 'small' for a watermark
				$img->addWatermark( $this->settings['gallery_watermark_path'], $this->settings['gallery_watermark_opacity'] );*/
				
				if ( $img->writeImage( $small ) )
				{
					@chmod( $small, IPS_FILE_PERMISSION );
				}
			}
			
			unset( $img );
		}
		
		/* Init Lib */
		$img = ips_kernel_image::bootstrap( $this->settings['gallery_img_suite'] );
		
		/* Prep max */
		if ( $img->init( $settings ) )
		{
			$return = $img->resizeImage( $this->settings['gallery_max_img_width'], $this->settings['gallery_max_img_height'] );
			
			/* Set size */
			$_save[ $_dataField ]['sizes']['max'] = array( $return['newWidth'], $return['newHeight'] );
			
			/* Can we feature? Yes we can! */
			if ( empty( $image[ $_feature ] ) AND ( $return['newWidth'] >= 480 OR $return['newHeight'] >= 480 ) )
			{
				$_save[ $_feature ] = 1;
			}
			
			/* Watermark image */
			if( $watermark )
			{
				/* Create unique name for original image */
				$_save[ $_original ] = $image['original_file_name'] ? $image['original_file_name'] : md5( IPS_UNIX_TIME_NOW . $image['masked_file_name'] . $this->settings['gallery_watermark_path'] . $this->settings['gallery_watermark_opacity'] ) . '.' . IPSText::getFileExtension($image['masked_file_name']);
				
				/* copy original image while avoiding copy() */
				if ( ! file_exists( $settings['image_path'] . $_save[ $_original ] ) )
				{
					$_orig = @file_get_contents( $_default );
					
					if ( $_orig )
					{
						@file_put_contents( $settings['image_path'] . $_save[ $_original ], $_orig );
					}
				}
				else
				{
					/* We already have an original, no need to update the table :) */
					unset($_save[ $_original ]);
				}
				
				$img->addWatermark( $this->settings['gallery_watermark_path'], $this->settings['gallery_watermark_opacity'] );
			}

			if ( $watermark || ( count($return) && empty($return['noResize']) ) )
			{
				$img->writeImage( $_default );
			}
			
			@chmod( $_default, IPS_FILE_PERMISSION );
		}
		
		unset( $img );
		
		/* serialize cornflakes */
		if ( ! empty( $_save[ $_dataField ] ) )
		{
			$_save[ $_dataField ] = serialize( $_save[ $_dataField ] );
		}
		
		$this->DB->update( $_table, $_save, $_where );
		
		return $_return;
	}
	
	/**
	 * Rotates an image using GD, function will eventually be moved into the kernel image class
	 *
	 * @param	array		$imgData		Array of image data
	 * @param	integer		$angle			Angle to rotate the image
	 * @return	@e bool
	 */
	public function rotateImage( $imgData, $angle=90 )
	{
		/* Init vars */
		$hasOriginal = false;
		$fullImgPath = $this->settings['gallery_images_path'] . '/' . $imgData['directory'] . '/' . $imgData['masked_file_name'];
		
		/* Setup */
		if ( $imgData['original_file_name'] && is_file($this->settings['gallery_images_path'] . '/' . $imgData['directory'] . '/' . $imgData['original_file_name']) )
		{
			$hasOriginal = true;
			$fullImgPath = $this->settings['gallery_images_path'] . '/' . $imgData['directory'] . '/' . $imgData['original_file_name'];
		}
		
		/* Save the old dimension, for figuring out new note position after rotate */
		/*if( file_exists( $this->settings['gallery_images_path'] . '/' . $imgData['directory'] . '/' . $imgData['medium_file_name'] ) )
		{
			$oldImageSize = getimagesize( $this->settings['gallery_images_path'] . '/' . $imgData['directory'] . '/' . $imgData['medium_file_name'] );
		}
		else
		{
			$oldImageSize = getimagesize( $this->settings['gallery_images_path'] . '/' . $imgData['directory'] . '/' . $imgData['masked_file_name'] );
		}*/
		
		/* Image Magick */
		if( $this->settings['gallery_img_suite'] == 'im' )
		{
			$angle = ($angle == "-90") ? "90" : "-90";
			system( "{$this->settings['gallery_im_path']}/convert -rotate \"{$angle}\" {$fullImgPath} {$fullImgPath}" );
		}
		/* GD */
		else
		{
			/* Extension */
			$imgExtension = IPSText::getFileExtension( $imgData['masked_file_name'] );
        	
			/* Create the image */
			switch( $imgExtension )
			{
				case 'gif':
					$image = imagecreatefromgif( $fullImgPath );
				break;
				
				case 'jpeg':
				case 'jpg':
				case 'jpe':
					$image = imagecreatefromjpeg( $fullImgPath );
				break;
				
				case 'png':
					$image = imagecreatefrompng( $fullImgPath );
				break;
			}

			if( ! $image )
			{
				return false;
			}

			/* Rotate */
			$rotatedImg = imagerotate( $image, $angle, 0 );
			
			if( ! $rotatedImg )
			{
				return false;
			}
			
			/* Save the Image */
			switch( $imgExtension )
			{
				case 'gif':
					$image = imagegif( $rotatedImg, $fullImgPath );
				break;
				
				case 'jpeg':
				case 'jpg':
				case 'jpe':
					$image = imagejpeg( $rotatedImg, $fullImgPath );
				break;
				
				case 'png':
					$image = imagepng( $rotatedImg, $fullImgPath );
				break;
			}

			/* Cleanup */
			imagedestroy( $rotatedImg );
			
			if( ! $image )
			{
				return false;
			}
		}
		
		if ( $hasOriginal )
		{
			@unlink($this->settings['gallery_images_path'] . '/' . $imgData['directory'] . '/' . $imgData['masked_file_name']);
			@copy( $this->settings['gallery_images_path'] . '/' . $imgData['directory'] . '/' . $imgData['original_file_name'], $this->settings['gallery_images_path'] . '/' . $imgData['directory'] . '/' . $imgData['masked_file_name']);
		}
		
		/* Rebuild the file */
		$this->buildSizedCopies( $imgData );
		
		/* Save the old dimension, for figuring out new note position after rotate */
		/*if( file_exists( $this->settings['gallery_images_path'] . '/' . $imgData['directory'] . '/' . $imgData['medium_file_name'] ) )
		{
			$newImageSize = getimagesize( $this->settings['gallery_images_path'] . '/' . $imgData['directory'] . '/' . $imgData['medium_file_name'] );
		}
		else
		{
			$newImageSize = getimagesize( $this->settings['gallery_images_path'] . '/' . $imgData['directory'] . '/' . $imgData['masked_file_name'] );
		}*/

		/* Notes 
		if( is_array( $imgData['image_notes'] ) && count( $imgData['image_notes'] ) )
		{
			foreach( $imgData['image_notes'] as $k => $v )
			{
				$newValues = $this->moveNotes( 
												$oldImageSize[0], 
												$oldImageSize[1],
												$imgData['image_notes'][$k]['left'],
												$imgData['image_notes'][$k]['top'],
												$imgData['image_notes'][$k]['width'],
												$imgData['image_notes'][$k]['height'],
												$newImageSize[0], 
												$newImageSize[1] 
											);
				$imgData['image_notes'][$k]['left'] = $newValues['left'];
				$imgData['image_notes'][$k]['top'] = $newValues['top'];
				$imgData['image_notes'][$k]['width'] = $newValues['width'];
				$imgData['image_notes'][$k]['height'] = $newValues['height'];
			}
			
			$this->DB->update( 'gallery_images', array( 'image_notes' => serialize( $imgData['image_notes'] ) ), "id={$imgData['id']}" );
		}
		*/
		return true;
	}
	
	public function moveNotes( $oldImgWidth, $oldImgHeight, $oldNoteLeft, $oldNoteTop, $oldNoteWidth, $oldNoteHeight, $newImgWidth, $newImgHeight )
	{
		$oldWidthDiff	= abs( $oldImgWidth - $oldNoteLeft );
		$oldHeightDiff	= abs( $oldImgHeight - $oldNoteTop );
		
		$newNoteLeft	= ceil( ( $oldWidthDiff * $newImgWidth ) / $oldImgWidth );
		$newNoteTop		= ceil( ( $oldHeightDiff * $newImgHeight ) / $oldImgHeight );
		
		//echo "\$oldWidthDiff = $oldWidthDiff<BR>";
		//echo "\$oldHeightDiff = $oldHeightDiff<BR><BR>";
		//
		//echo "( $oldWidthDiff * $newImgWidth ) / $oldImgWidth = $newNoteLeft<BR>";
		//echo "( $oldHeightDiff * $newImgHeight ) / $oldImgHeight = $newNoteTop<BR>";
		//echo "<BR>\$newNoteLeft = $newNoteLeft<BR>";
		//echo "\$newNoteTop = $newNoteTop<BR>";
		
		return array( 'left' => $newNoteLeft, 'top' => $newNoteTop, 'width' => $oldNoteHeight, 'height' => $oldNoteWidth );
	}
	
	/**
	 * Fetch images: Central function.
	 *
	 * @access	private
	 * @param	array		Array of 'where' information. Where? THERE!
	 * @param	array		Array of 'filter' information. Filter? Water!
	 * @return	@e array		Array of 'IDs'. Can't think of anything funny to do with IDs.
	 */
	private function _fetchImages( $where, $filters )
	{
		/* Imarjes */
		$images = array();
		
		/* Have we cleaned filters? */
		if ( ! isset( $filters['_cleaned'] ) )
		{
			$filters = $this->_setFilters( $filters );
		}
		
		/* Finish off */
		if ( $filters['unixCutOff'] )
		{
			$where[] = "i.idate > " . $filters['unixCutOff'];
		}
		
		/* Limit to album? */
		if ( isset( $filters['albumId'] ) )
		{
			if ( ! $filters['albumId'] )
			{
				/* We have specified an album ID but nothing is there, so we return nothing */
				return array();
			}
			
			$where[] = ( is_numeric( $filters['albumId'] ) ) ? "i.img_album_id=" . $filters['albumId'] : "i.img_album_id IN(" . implode( ",", $filters['albumId'] ) . ')';
		}
		
		/* Looking for member only? */
		if ( ! empty( $filters['ownerId'] ) )
		{
			$where[] = 'i.member_id=' . intval( $filters['ownerId'] );
		}
		
		/* Looking for featured? */
		if ( $filters['featured'] )
		{
			$where[] = ( $filters['featured'] === true ) ? "i.image_feature_flag > 0" : "i.image_feature_flag = " . intval( $filters['featured'] );
		}
		
		/* Looking for (non)media items? */
		if ( isset( $filters['media'] ) )
		{
			$where[] = ( $filters['media'] === true ) ? 'i.media=1' : 'i.media=0';
		}
		
		/* Looking for commented items? */
		if ( ! empty( $filters['hasComments'] ) )
		{
			$where[] = 'i.lastcomment > 0';
		}
		
		/* Are we fetching a single random image? */
		if ( $filters['limit'] == 1 && isset( $filters['_sortKey'] ) && $filters['_sortKey'] == 'rand' )
		{
			/* Specifying album IDs? */
			if ( ! empty( $filters['albumId'] ) )
			{
				/* Do all */
				$this->DB->allow_sub_select = true;
				ips_DBRegistry::loadQueryFile( 'public', 'gallery' );
				$this->DB->buildFromCache( 'gallery_fetch_feature_image_php_rand', $where, 'public_gallery_sql_queries' );
				$o = $this->DB->execute();
			}
			else
			{
				/* Do all */
				$this->DB->allow_sub_select = true;
				ips_DBRegistry::loadQueryFile( 'public', 'gallery' );
				$this->DB->buildFromCache( 'gallery_fetch_feature_image', $where, 'public_gallery_sql_queries' );
				$o = $this->DB->execute();
			}
			
			$res = $this->DB->fetch( $o );
			
			if ( count( $res ) )
			{
				$row = $this->_setUpImage( $res );
				
				$images[ $row['id'] ] = $row;
			}
				
			return $images;
		}
		
		/* Build joins */
		$joins = array( array( 'select' => 'a.*',
 					  		   'from'   => array( 'gallery_albums_main' => 'a' ),
 					  	 	   'where'  => 'a.album_id=i.img_album_id',
 					  		   'type'   => 'left' ),
 					    array( 'select' => 'm.members_display_name, m.members_seo_name, m.member_id',
 					           'from'	  => array( 'members' => 'm' ),
 					           'where'  => 'm.member_id=i.member_id',
 					           'type'   => 'left' ) );
		
		/* get latest comment */
		if ( ! empty( $filters['getLatestComment'] ) )
		{	
			/* Ensure an index is used */
			if ( ! isset( $filters['albumId'] ) )
			{
				$where[] = "i.img_album_id != 0";
			}
			
			$joins[] = array( 'select' => 'c.*',
							  'from'   => array( 'gallery_comments' => 'c' ),
							  'where'  => 'c.post_date=i.lastcomment AND c.img_id=i.id',
							  'type'   => 'left' );
		}
		
		/* Get tags */
		if ( ! empty( $filters['getTags'] ) )
		{
			$joins[] = $this->registry->galleryTags->getCacheJoin( array( 'meta_id_field' => 'i.id' ) );	
		}
		
		/* Calc image count */
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
									 'from'     => array( 'gallery_images' => 'i' ),
									 'where'    => ( count( $where ) ) ? implode( ' AND ', $where ) : '',
									 'add_join' => $_joins ) );
			
			$o   = $this->DB->execute();
			$row = $this->DB->fetch( $o );
			
			$this->_imageCount = intval( $row['count'] );
		}
		
		/* Perform the query to fetch the images. Not that you needed this comment to understand that */
		$this->DB->build( array( 'select'   => 'i.*',
								 'from'     => array( 'gallery_images' => 'i' ),
								 'where'    => ( count( $where ) ) ? implode( ' AND ', $where ) : '',
								 'order'    => $filters['sortKey'] . ' ' . $filters['sortOrder'],
								 'limit'    => ( $filters['offset'] || $filters['limit'] ) ? array( $filters['offset'], $filters['limit'] ) : null,
								 'add_join' => $joins ) );
		
		$o = $this->DB->execute();	
		
		$commentAuthorIds = array();
		$ownerIds		  = array();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			$ownerIds[] = $row['member_id'];
			
			if ( isset( $row['comment'] ) )
			{
				$row['_commentShort'] = IPSText::truncate( IPSText::getTextClass('bbcode')->stripAllTags( IPSText::unconvertSmilies( $row['comment'] ) ), 200 );
				
				if ( ! empty( $row['author_id'] ) )
				{
					$commentAuthorIds[ $row['author_id'] ] = $row['author_id'];
				}
			}
			
			/* Tags */
			if ( ! empty( $row['tag_cache_key'] ) )
			{
				$row['tags'] = $this->registry->galleryTags->formatCacheJoinData( $row );
			}
			
			$row = $this->_setUpImage( $row, $filters['parseDescription'] );
			$images[ $row['id'] ] = $row;		
		}
		
		/* parse image owner? */
		if ( ! empty( $filters['parseImageOwner'] ) )
		{
			/* Need to load members? */
			if ( count( $ownerIds ) )
			{
				$mems = IPSMember::load( $ownerIds, 'all' );
				
				foreach( $images as $id => $r )
				{
					if ( ! empty( $r['member_id'] ) AND isset( $mems[ $r['member_id'] ] ) )
					{
						$mems[ $r['member_id'] ]['m_posts'] = $mems[ $r['member_id'] ]['posts'];
						
						$_mem = IPSMember::buildDisplayData( $mems[ $r['member_id'] ], array( 'reputation' => 0, 'warn' => 0 ) );

						$images[ $id ] = array_merge( $images[ $id ], $_mem );
					}
				}
			}
		}
		
		/* Got comment authors to put together? */
		if ( count( $commentAuthorIds ) )
		{
			$members = IPSMember::load( $commentAuthorIds, 'all' );
			
			foreach( $images as $id => $data )
			{
				if ( ! empty( $data['author_id'] ) )
				{
					if ( $members[ $data['author_id'] ] )
					{
						$images[ $id ]['_commentAuthor'] = IPSMember::buildProfilePhoto( $members[ $data['author_id'] ] );
					}
				}
			}
		}
		
		return $images;
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
		$filters['sortOrder'] = ( isset( $filters['sortOrder'] ) ) ? strtolower( $filters['sortOrder'] ) : '';
		$filters['sortKey']   = ( isset( $filters['sortKey'] ) )   ? $filters['sortKey'] : '';
		$filters['featured']  = ( isset( $filters['featured'] ) )  ? $filters['featured'] : '';
		$filters['offset']    = ( isset( $filters['offset'] ) )    ? $filters['offset'] : '';
		$filters['limit']     = ( isset( $filters['limit'] ) )     ? $filters['limit'] : '';

		/* Do some set up */
		switch( $filters['sortKey'] )
		{
			default:
			case 'idate':
			case 'date':
			case 'time':
				$filters['sortKey']  = 'i.idate';
			break;
			case 'name':
				$filters['sortKey']  = 'i.caption_seo';
			break;
			case 'file_name':
				$filters['sortKey']  = 'i.file_name';
			break;
			case 'size':
			case 'file_size':
				$filters['sortKey']  = 'i.file_size';
			break;
			case 'rand':
			case 'random':
				$filters['sortKey']  = $this->DB->buildRandomOrder();
				$filters['_sortKey'] = 'rand';
			break;
			case 'lastcomment':
			case 'commentdate':
				$filters['sortKey'] = 'i.lastcomment';
			break;
			case 'views':
				$filters['sortKey'] = 'i.views';
			break;
			case 'comments':
				$filters['sortKey'] = 'i.comments';
			break;
			case 'rating':
				$filters['sortKey'] = 'i.rating';
			break;
		}
		
		switch( $filters['sortOrder'] )
		{
			case 'desc':
			case 'descending':
			case 'z-a':
				$filters['sortOrder'] = 'desc';
			break;
			default:
			case 'asc':
			case 'ascending':
			case 'a-z':
				$filters['sortOrder'] = 'asc';
			break;
		}
		
		/* Others */
		$filters['offset']    		 = intval( $filters['offset'] );
		$filters['limit']      		 = intval( $filters['limit'] );
		$filters['unixCutOff'] 		 = intval( $filters['unixCutOff'] );
		$filters['albumId']    		 = ( isset( $filters['albumId'] ) && is_numeric( $filters['albumId'] ) ) ? intval( $filters['albumId'] ) : $filters['albumId'];
		$filters['parseDescription'] = ( isset( $filters['parseDescription'] ) ) ? $filters['parseDescription'] : false;
		
		/* So we don't have to do this twice */
		$filters['_cleaned']   = true;
		
		return $filters;
	}
	
	/**
	 * Set up image
	 *
	 * @access	protected
	 * @param	array
	 * @param	bool		If we're loading lots of images in the same view and not displaying descriptions, pass false to save some resources
	 * @return	@e array
	 */
	protected function _setUpImage( $image, $parseDescription=TRUE )
	{
		if ( ! is_array( $image ) || ! count( $image ) )
		{
			return array();
		}
		
		/* Fix seo names 1 by 1 */
		if ( !$image['caption_seo'] && is_numeric($image['id']) && $image['id'] )
		{
			$image['caption_seo'] = IPSText::makeSeoTitle( $image['caption'] );
			
			$this->DB->update( 'gallery_images', array( 'caption_seo' => $image['caption_seo'] ), 'id='.$image['id'] );
		}
		
		/* Legacy variable */
		$image['_seo_name'] = $image['caption_seo'];
		
		/* Fix guest names */
		if ( empty($image['member_id']) || empty($image['members_display_name']) )
		{
			$image['members_display_name'] = $this->lang->words['global_guestname'];
		}
		
		$image['_isRead'] = ( !isset($image['_isRead']) && $this->registry->isClassLoaded('classItemMarking') ) ? $this->registry->classItemMarking->isRead( array( 'albumID' => $image['img_album_id'], 'itemID' => $image['id'], 'itemLastUpdate' => $image['idate'] ), 'gallery' ) : $image['_isRead'];
		
		$image['thumb'] = $this->makeImageLink( $image, array( 'type' => 'thumb' ) );
		
		if ( IPSLib::isSerialized( $image['image_data'] ) )
		{
			$image['_data'] = unserialize( $image['image_data'] );
		}
	
		if ( $parseDescription and ! empty( $image['description'] ) )
		{ 
			if ( class_exists('ipsCommand') )
			{ 
				$image['_descriptionParsed'] = IPSText::getTextClass('bbcode')->preDisplayParse( $image['description'] );
			}
		}

		return $image;
	}
}