<?php
/**
 * Library/Moderate
 *
 * Helper functions for moderation
 *
 * @author 		$Author: mmecham $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link			http://www.invisionpower.com
 * @version		$Rev: 9982 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * Enter description here ...
 * @author matt
 *
 */
class gallery_moderate
{
	/**#@+
	 * Image, cat and album arrays
	 * 
	 * @var		array
	 */
	protected $img;
	protected $cat;
	protected $album;
	/**#@-*/
	
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
	
	private $_node;
	
	const DEBUG = false;
	
	private $_dbCount = 0;
	
	/**
	 * Constructor
	 *
	 * @param	ipsRegistry  $registry
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

		$this->_albums   = $this->registry->gallery->helper('albums');
		$this->_images   = $this->registry->gallery->helper('image');
	}	
	
	/**
	 * Adds album.
	 * Warning: Does not perform any permission checks. You must do this in your code
	 * or face the Wrath of Khan.
	 * 
	 * @param	array		$album			Data for the new album
	 * @return	@e array	Album data
	 * 
	 * @throws
	 * 	@li	MEMBER_ALBUM_NO_PARENT:			A member album requires always a parent album ID
	 * 	@li	USER_ALBUM_ERROR_CANNOT_NEST:	Means that you're trying to nest a user album inside a user album inside a global album
	 */
	public function createAlbum( array $album )
	{
		/* Lets guess some data! Yay! */
		$album['album_parent_id']		= intval( $album['album_parent_id'] );
		$album['album_owner_id']		= ( isset( $album['album_owner_id'] ) AND $album['album_owner_id'] ) ? $album['album_owner_id'] : $this->memberData['member_id'];
		$album['album_name']			= empty($album['album_name']) ? ( empty($this->lang->words['untitled_album']) ? 'Untitled Album' : $this->lang->words['untitled_album'] ) : $album['album_name'];
		$album['album_name_seo']		= IPSText::makeSeoTitle( $album['album_name'] );
		$album['album_description']		= empty( $album['album_description'] ) ? '' : $album['album_description'];
		$album['album_is_public']		= intval( $album['album_is_public'] );
		$album['album_is_global']		= intval( $album['album_is_global'] );
		$album['album_is_profile']		= intval( $album['album_is_profile'] );
		$album['album_watermark']		= intval( $album['album_watermark'] );
		$album['album_allow_comments']	= ( isset( $album['album_allow_comments'] ) ) ? $album['album_allow_comments'] : 1;
		$album['album_g_container_only']= ( isset( $album['album_g_container_only'] ) AND $album['album_g_container_only'] ) ? $album['album_g_container_only'] : 0;
		$album['album_g_approve_img']	= ( isset( $album['album_g_approve_img'] ) 	AND $album['album_g_approve_img'] )    ? $album['album_g_approve_img'] : 0;
		$album['album_g_approve_com']	= ( isset( $album['album_g_approve_com'] ) 	AND $album['album_g_approve_com'] )    ? $album['album_g_approve_com'] : 0;
		
		/* Make text fields happy :) */
		$album['album_sort_options']      = ( isset( $album['album_sort_options'] )     AND $album['album_sort_options'] )     ? $album['album_sort_options'] : '';
		$album['album_cache']             = ( isset( $album['album_cache'] )            AND $album['album_cache'] )            ? $album['album_cache'] : '';
		$album['album_position']		  = ( intval( $album['album_position'] ) ) ? $album['album_position'] : $this->_getNewAlbumPosition( $album['album_parent_id'] );
		$album['album_g_rules']           = ( isset( $album['album_g_rules'] )          AND $album['album_g_rules'] )          ? $album['album_g_rules'] : '';
		$album['album_g_perms_thumbs']    = ( isset( $album['album_g_perms_thumbs'] )   AND $album['album_g_perms_thumbs'] )   ? $album['album_description'] : '';
		$album['album_g_perms_view']      = ( isset( $album['album_g_perms_view'] )     AND $album['album_g_perms_view'] )     ? $album['album_g_perms_view'] : '';
		$album['album_g_perms_images']    = ( isset( $album['album_g_perms_images'] )   AND $album['album_g_perms_images'] )   ? $album['album_g_perms_images'] : '';
		$album['album_g_perms_comments']  = ( isset( $album['album_g_perms_comments'] ) AND $album['album_g_perms_comments'] ) ? $album['album_g_perms_comments'] : '';
		$album['album_g_perms_moderate']  = ( isset( $album['album_g_perms_moderate'] ) AND $album['album_g_perms_moderate'] ) ? $album['album_g_perms_moderate'] : '';
		
		/* Attempt permission update */
		if ( ! $album['album_is_global'] )
		{
			/* Make list last */
			$album['album_position'] = time();
			
			if ( $album['album_parent_id'] )
			{
				$album['album_g_perms_view'] = $this->registry->gallery->helper('albums')->getFirstGlobalViewMask( $album['album_parent_id'] );
				
				/* No global parent */
				if ( empty( $album['album_g_perms_view'] ) )
				{
					$album['album_g_perms_view'] = '*';
				}
			}
			else
			{
				throw new Exception('MEMBER_ALBUM_NO_PARENT');
			}
			
			/* Is parent moderating images? */
			$globalParent = $this->registry->gallery->helper('albums')->getFirstGlobalParent( $album['album_parent_id'] );
			
			if ( $globalParent['album_id'] )
			{
				$album['album_g_approve_img'] = intval( $globalParent['album_g_approve_img'] );
				$album['album_g_approve_com'] = intval( $globalParent['album_g_approve_com'] );
			} 
		}
		else
		{
			$album['album_owner_id']  = 0;
			
			if ( $album['album_parent_id'] )
			{
				$album = $this->setPermsAgainstParent( $album['album_parent_id'], $album );
			}
		}
		
		/* Be a bit forceful about some basic rules
		 * USER ALBUM: If the immediate parent is a global album then it must be public. User albums cannot be nested inside a global album
		 * GLOBAL ALBUM: Children cannot have more permissive settings than the parent. So if user Y cannot view images in parent, then all children must inherit this permission. 
		 */
		try
		{
			$result = $this->isOkToBeAChildOf( $album['album_parent_id'], $album );
		}
		catch ( Exception $e )
		{
			throw new Exception( $e->getMessage() );
		}
		
		/* Insert */
		$album['album_id'] = $this->_node()->addNode( $album['album_parent_id'], $album );
		
		/* Update */
		$this->registry->gallery->helper('tools')->rebuildTree( $album['album_id'] );
		
		/* Rebuild stats */
		$this->registry->gallery->rebuildStatsCache();
		
		/* Rebuild has_gallery */
		$this->registry->gallery->resetHasGallery( $album['album_owner_id'] );
		
		/* Send it all back */
		return $album;
	}
	
	/**
	 * Checks to see if album is OK to add as actual album creation will just be a dictator and make the changes for you
	 * 
	 * @throws
	 * 	@li	USER_ALBUM_ERROR_CANNOT_NEST	Means that you're trying to nest a user album inside a user album inside a global album
	 */
	public function isOkToBeAChildOf( $parentId, array $album )
	{
		/* do we have permission to create album here? */
		if ( $album['album_parent_id'] )
		{
			 if ( ! $this->registry->gallery->helper('albums')->canCreateSubAlbumInside( $parentId ) )
			 {
			 	throw new Exception('NO_PERMISSION_TO_CREATE_IN_ALBUM');
			 }
		}
		
		if ( ! $album['album_is_global'] )
		{
			if ( $parentId )
			{
				$parents     = $this->_albums->fetchAlbumParents( $parentId );
				$foundGlobal = 0;
				
				if ( count( $parents ) )
				{
					foreach( $parents as $id => $al )
					{
						if ( $this->_albums->isGlobal( $al ) )
						{
							$foundGlobal++;
						}
					}					
				}
				
				$parent = $this->_albums->fetchAlbumsById( $album['album_parent_id'] );
				
				if ( ! $parent['album_is_global'] && ( $parent['album_owner_id'] != $album['album_owner_id'] ) )
				{ 
					throw new Exception('MEMBER_ALBUM_NOT_YOUR_PARENT');
				}
							
				/* If we've found global then it has to be under 2 or we're nesting too deep (0, 1 ok) */
				if ( $foundGlobal >= 2 )
				{
					//throw new Exception('USER_ALBUM_ERROR_CANNOT_NEST');
				}
			}
		}
		
		return true;
	}
	
	/**
	 * "heal" permissions
	 * Give it a node ID and it'll go through all the children and reset their permissions
	 * To make sure public albums have inherited permissions correctly and are not more permissiable than their parents
	 * 
	 * @param	int		Node ID of branch
	 * @return  pint of beer for your old dad
	 */
	public function resetPermissions( $nodeId )
	{
		if ( self::DEBUG )
		{
			IPSDebug::addLogMessage( "1: resetInheritedPermissions called, node ID " . $nodeId, 'gallery_albums_permissions', false, self::DEBUG, true );
		}
		
		if ( ! $nodeId )
		{
			/* Generically heal like JESUS */
			$this->DB->update( 'gallery_albums_main', array( 'album_g_perms_view' => '*' ), 'album_is_global=0 AND album_is_public=1 AND album_parent_id=0' );
			$this->_dbCount++;
			
			$album = array( 'album_id' => 0, 'album_node_left' => null, 'album_node_right' => null );
		}
		else
		{
			$album = $this->_albums->fetchAlbumsById( $nodeId );
		}
		
		/* Fetch all root global albums */
		$children = $this->_albums->fetchGlobalAlbums( array( 'parentId' => $nodeId, 'isViewable' => null ) );
		
		if ( count( $children ) )
		{
			foreach( $children as $id => $luke )
			{
				IPSDebug::addLogMessage( "2: Calling setGlobalInheritedPermission from resetInheritedPermissions, child node ID " . $id, 'gallery_albums_permissions', false, self::DEBUG );
				
				$this->setGlobalInheritedPermission( $luke, $album );		
			}
		}
		
		/* Finish up by doing user albums */
		IPSDebug::addLogMessage( "PENULTIMATE: Calling gallery_update_user_albums", 'gallery_albums_permissions', false, self::DEBUG );
		
		$data = array();
		ips_DBRegistry::loadQueryFile( 'public', 'gallery' );
		$this->DB->buildFromCache( 'gallery_update_user_albums', $album, 'public_gallery_sql_queries' );
		$this->DB->execute();
		$this->_dbCount += 5;
		
		/* Lastly Update images */
		IPSDebug::addLogMessage( "END: Calling updatePermissionFromParent", 'gallery_albums_permissions', false, self::DEBUG );
		
		$this->_images->updatePermissionFromParent( $album );
		$this->_dbCount++;
	}
	
	/**
	 * Resets inherited permissions for an album
	 * @param mixed (int - album ID, array $album )
	 */
	public function setGlobalInheritedPermission( $album, $parentAlbum=array() )
	{
		/* init (yes ) */
		static $_seenParentIds = array();
		
		if ( is_numeric( $album ) )
		{
			$album = $this->_albums->fetchAlbumsById( $album );
		}
		
		/* Do we have a parent album? */
		if ( isset( $parentAlbum['album_id'] ) && isset( $parentAlbum['album_g_perms_view'] ) )
		{
			IPSDebug::addLogMessage( "3: Inside global album " .  $album['album_id'] . " and album_g_perms_view => " . $album['album_g_perms_view'], 'gallery_albums_permissions', false, self::DEBUG );
			
			$childAlbum = $this->setPermsAgainstParent( $parentAlbum, $album );
						
			/* Save */
			$this->DB->update( 'gallery_albums_main', array( 'album_g_perms_view'   => IPSText::cleanPermString( $childAlbum['album_g_perms_view'] ),
															 'album_g_perms_images' => IPSText::cleanPermString( $childAlbum['album_g_perms_images'] ) ), 'album_is_global=1 AND album_id=' . $childAlbum['album_id'] );
			$this->_dbCount++;
		}
		
		/* Now fetch any child albums */
		$children = $this->_albums->fetchAlbumsByFilters( array( 'album_parent_id' => $album['album_id'], 'isViewable' => null, 'album_is_global' => 1 ) );
		$this->_dbCount++;
		
		if ( count( $children ) )
		{			
			foreach( $children as $cid => $childAlbum )
			{
				IPSDebug::addLogMessage( "4: ***** Child iteration - GLOBAL " . $cid . '' , 'gallery_albums_permissions', false, self::DEBUG );				
				
				if ( ( $album['album_g_perms_view'] != $childAlbum['album_g_perms_view'] ) OR ( $album['album_g_perms_images'] != $childAlbum['album_g_perms_images'] ) )
				{
					$childAlbum = $this->setPermsAgainstParent( $album, $childAlbum );
					
					/* Save */
					$this->DB->update( 'gallery_albums_main', array( 'album_g_perms_view'   => IPSText::cleanPermString( $childAlbum['album_g_perms_view'] ),
																	 'album_g_perms_images' => IPSText::cleanPermString( $childAlbum['album_g_perms_images'] ) ), 'album_is_global=1 AND album_id=' . $childAlbum['album_id'] );
					$this->_dbCount++;
				}
				
				/* Go again */
				$this->setGlobalInheritedPermission( $childAlbum );
			}			
		} 
	}
	
	/**
	 * Makes sure the child isn't > than parent
	 * @param mixed $album
	 * @param mixed $childAlbum
	 */
	public function setPermsAgainstParent( $album, $childAlbum )
	{
		IPSDebug::addLogMessage( "5: Inside setPermsAgainstParent, album ID " . $album['album_id'] . " child ID " . $childAlbum['album_id'] , 'gallery_albums_permissions', false, self::DEBUG );	
						
		if ( is_numeric( $album ) )
		{
			$album = $this->_albums->fetchAlbumsById( $album );
		}
		
		if ( is_numeric( $childAlbum ) )
		{
			$childAlbum = $this->_albums->fetchAlbumsById( $childAlbum );
		}
		
		foreach( array( 'album_g_perms_view', 'album_g_perms_images' ) as $mask )
		{
			if ( $album[ $mask ] != '*' )
			{
				if ( $childAlbum[ $mask ] == '*' )
				{
					$childAlbum[ $mask ] = $album[ $mask ];
				}
				else
				{
					$albumIds = explode( ',', IPSText::cleanPermString( $album[ $mask ] ) );
					$childIds = explode( ',', IPSText::cleanPermString( $childAlbum[ $mask ] ) );
					$finalIds = array();
					
					foreach( $childIds as $id )
					{
						if ( in_array( $id, $albumIds ) )
						{
							$finalIds[] = $id;
						}
					}
					
					$childAlbum[ $mask ] = IPSText::cleanPermString( implode( ',', $finalIds ) );
				}
			}
		}
		
		return $childAlbum;
	}
	
	/**
	 * Deletes picture(s)
	 * 
	 * @param	array 	Array of images: array( id => data )
	 * @return	@e bool
	 */	
	public function deleteImages( array $images=array() )
	{
		/* Init */
		$final   = array();
		$uploads = array();
		$albums  = array();
		$_names  = array();
		$_ids    = array();
		
		if ( ! count( $images ) )
		{
			return false;
		}
		
		/* is this a simple array */
		if ( is_numeric( $images[0] ) AND ! is_array( $images[0] ) )
		{
			$images = $this->_images->fetchImages( null, array( 'imageIds' => $images ) );
		}
		
		/* Fetch like class */
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$this->_like = classes_like::bootstrap( 'gallery', 'images' );
		
		/* Loop through */
		foreach( $images as $id => $data )
		{
			if ( ! is_numeric( $data['id'] ) AND strlen( $data['id'] ) == 32 )
			{
				$uploads[ $id ] = $data;
			}
			else if ( is_numeric( $data['id'] ) )
			{
				$final[ $id ] = $data;
				
				if ( $data['img_album_id'] )
				{
					$albums[ $data['img_album_id'] ] = $data['img_album_id'];
				}
			}
		}
		
		/* Any natives to delete? */
		if ( count( $final ) )
		{
			foreach( $final as $id => $data )
			{
				$_ids[]   = intval( $data['id'] );
				$_names[] = "'" . $this->DB->addSlashes( $data['masked_file_name'] ) . "'";
				
				/* Delete images */
				$this->removeImageFiles( $data );
				
				/* Remove like */
				$this->_like->remove( $data['id'] );
			}
			
			/* Remove from DB */
			$_idIn = ' IN (' . implode( ',',  $_ids )  . ')';
			$_nIn  = ' IN (' . implode( ',', $_names ) . ')';
			
			$this->DB->delete( 'gallery_comments' , "img_id" . $_idIn );
			$this->DB->delete( 'gallery_bandwidth', "file_name" . $_nIn );
			$this->DB->delete( 'gallery_ratings'  , "rating_foreign_id" . $_idIn . " AND rating_where='image'");
			$this->DB->delete( 'gallery_images'   , "id" . $_idIn );
			
			/* Tags */
			$this->registry->galleryTags->deleteByMetaId( $_ids );
		}
		
		/* Now check uploads */
		if ( count( $uploads ) )
		{
			$this->registry->gallery->helper('upload')->deleteSessionImages( $uploads );
		}
		
		/* Resync albums? */
		if ( count( $albums ) )
		{
			foreach( $albums as $id => $i )
			{
				$this->registry->gallery->helper('albums')->resync( $id );
			}
		}
		
		/* Rebuild stats */
		$this->registry->gallery->rebuildStatsCache();
		
		/* Log action */
		if ( count($_ids) )
		{
			$this->addModLog( sprintf( $this->lang->words['modlog_delete_images'], implode( ',',  $_ids ) ) );
		}
		
		return true;
	}
	
	/**
	 * Toggle image visibility
	 * 
	 * @param	array	$images		Array of images: array( id => data )
	 * @param	bool	$visible	True to approve the images, defaults to FALSE
	 * @return	@e bool	True if update is successful
	 * 
	 * @throws
	 * 	@li	NO_IMAGES	No image IDs passed or image don't exist anymore
	 */	
	public function toggleVisibility( array $images=array(), $visible=false )
	{
		$imageIds      = IPSLib::cleanIntArray( $images );
		$imagesByAlbum = array();
		
		/* Fetch images */
		$images = $this->_images->fetchImages( null, array( 'imageIds' => $imageIds ) );
		
		if ( !count($images) )
		{
			throw new Exception('NO_IMAGES');
		}
		
		/* Sort into albums */
		foreach( $images as $id => $data )
		{
			if ( !isset($imagesByAlbum[ $data['img_album_id'] ]) )
			{
				$imagesByAlbum[ $data['img_album_id'] ] = array();
			}
			
			$imagesByAlbum[ $data['img_album_id'] ][ $data['id'] ] = $data;
		}
		
		/* Update image album id */
		$_imagesIn = implode( ',', array_keys( $images ) );
		$this->DB->update( 'gallery_images', array( 'approved' => ( ( $visible === true ) ? 1 : 0 ) ), 'id IN (' . $_imagesIn . ')' );
		
		/* Tags */
		$this->registry->galleryTags->updateVisibilityByMetaId( array_keys( $images ), $visible );
		
		/* rebuild albums */
		foreach( $imagesByAlbum as $albumId => $images )
		{
			$this->_albums->resync( $albumId );
		}
		
		/* Rebuild stats */
		$this->registry->gallery->rebuildStatsCache();
		
		/* Log action */
		if ( $visible === true )
		{
			$this->addModLog( sprintf( $this->lang->words['modlog_approve_images'], $_imagesIn ) );
		}
		else
		{
			$this->addModLog( sprintf( $this->lang->words['modlog_unapprove_images'], $_imagesIn ) );
		}
		
		return true;
	}
	
	/**
	 * Move images to another album
	 * 
	 * @param	array		$imageIds		Image ids to move
	 * @param	int			$toAlbumId		New album ID
	 * @return	@e bool	TRUE if all went fine
	 * 
	 * @throws
	 * 	@li	NO_ALBUM	The album chosen to move the data to doesn't exist
	 * 	@li	NO_IMAGE	None of the image IDs passed exist
	 */
	public function moveImages( array $imageIds, $toAlbumId )
	{
		/* Hold onto images like they're our very best friend */
		$imagesByAlbum = array();
		$imageIds      = IPSLib::cleanIntArray( $imageIds );
		
		/* Is the other album valid? */
		$toAlbumData   = $this->_albums->fetchAlbumsById( $toAlbumId );
		
		if ( empty( $toAlbumData['album_id'] ) )
		{
			throw new Exception('NO_ALBUM');
		}
		
		/* Fetch images */
		$images = $this->_images->fetchImages( null, array( 'imageIds' => $imageIds ) );
		
		if ( ! count( $images ) )
		{
			throw new Exception('NO_IMAGES');
		}
		
		/* Sort into albums */
		foreach( $images as $id => $data )
		{
			if ( !isset($imagesByAlbum[ $data['img_album_id'] ]) )
			{
				$imagesByAlbum[ $data['img_album_id'] ] = array();
			}
			
			$imagesByAlbum[ $data['img_album_id'] ][ $data['id'] ] = $data;
		}
		
		/* Update image album id */
		$this->DB->update( 'gallery_images', array( 'img_album_id' => $toAlbumId ), 'id IN (' . implode( ',', $imageIds ) . ')' );
		
		/* Move tags */
		$this->registry->galleryTags->moveTagsToParentId( $imageIds, $toAlbumId );
		
		/* rebuild albums */
		foreach( $imagesByAlbum as $albumId => $images )
		{
			$this->_albums->resync( $albumId );
		}
		
		$this->_albums->resync( $toAlbumId );
		
		/* Reset image permission flags */
		$this->_images->updatePermissionFromParent( $toAlbumId );
		
		/* Rebuild stats */
		$this->registry->gallery->rebuildStatsCache();
		
		/* Log action */
		$this->addModLog( sprintf( $this->lang->words['modlog_move_images'], $toAlbumData['album_name'], $toAlbumData['album_id'], implode( ',', $imageIds ) ) );
		
		return true;
	}

	/**
	 * Removes the specified album and then deletes the physical files
	 * or moves them to another specified album
	 * 
	 * @param	integer		$album			Data of the album to delete
	 * @param	mixed		$moveToAlbum	Data or ID of the album to move images to (optional)
	 * @return	@e bool
	 */		
	public function deleteAlbum( $album, $moveToAlbum=array() )
	{
		/* this has to be in albums class due to nodes class */
		$return = $this->_albums->remove( $album, $moveToAlbum );
		
		/* Log action */
		if ( $return )
		{
			if ( is_numeric($moveToAlbum) && $moveToAlbum )
			{
				$moveToAlbum = $this->_albums->fetchAlbumsById( $moveToAlbum );
			}
			
			if ( $moveToAlbum['album_id'] )
			{
				$this->addModLog( sprintf( $this->lang->words['modlog_delete_album_move'], $album['album_name'], $album['album_id'], $moveToAlbum['album_name'], $moveToAlbum['album_id'] ) );
			}
			else
			{
				$this->addModLog( sprintf( $this->lang->words['modlog_delete_album'], $album['album_name'], $album['album_id'] ) );
			}
		}
		
		return $return;
	}
	
	/**
	 * Remove media thumb ...
	 * 
	 * @param	array $image
	 * @return	@e boolean
	 */
	public function removeMediaThumb( $image )
	{
		if ( ! $image['id'] )
		{
			return false;
		}
		
		$this->_images->save( array( $image['id'] => array( 'medium_file_name' => '', 'media_thumb' => '', 'thumbnail' => 0 ) ) );
		
		/* remove the files */
		$dir = $image['directory'] ? $image['directory'] . "/" : "";
		
		if ( is_file( $this->settings['gallery_images_path'] . '/' . $dir . $image['medium_file_name'] ) )
		{
			@unlink( $this->settings['gallery_images_path'] . '/' . $dir . $image['medium_file_name'] );
		}	
		
		if ( $image['media_thumb'] )
		{
			if( is_file( $this->settings['gallery_images_path'] . '/' . $dir . $image['media_thumb'] ) )
			{
				@unlink( $this->settings['gallery_images_path'] . '/' . $dir . $image['media_thumb'] );
			}
		}
		
		return true;
	}
	
	/**
	 * Remove image files from disk
	 *
	 * @param	array		$image				Image data
	 * @param	boolean		$checkDirectory		Checks if the album folder is empty and deletes it
	 * @return	@e boolean	False if the array is empty or there is no ID otherwise true
	 */
	public function removeImageFiles( $image=array(), $checkDirectory=true )
	{
		if ( ! count($image) )
		{
			return false;
		}
		
		if ( ! $image['id'] )
		{
			return false;
		}
		
		$dir = $image['directory'] ? $image['directory'] . "/" : "";
		
		@unlink( $this->settings['gallery_images_path'] . '/' . $dir . $image['masked_file_name'] );
		
		if ( is_file( $this->settings['gallery_images_path'] . '/' . $dir . 'tn_' . $image['masked_file_name'] ) )
		{
			@unlink( $this->settings['gallery_images_path'] . '/' . $dir . 'tn_' . $image['masked_file_name'] );
		}
		
		if ( is_file( $this->settings['gallery_images_path'] . '/' . $dir . 'sml_' . $image['masked_file_name'] ) )
		{
			@unlink( $this->settings['gallery_images_path'] . '/' . $dir . 'sml_' . $image['masked_file_name'] );
		}
		
		if ( is_file( $this->settings['gallery_images_path'] . '/' . $dir . $image['medium_file_name'] ) )
		{
			@unlink( $this->settings['gallery_images_path'] . '/' . $dir . $image['medium_file_name'] );
		}
		
		if ( is_file( $this->settings['gallery_images_path'] . '/' . $dir . $image['original_file_name'] ) )
		{
			@unlink( $this->settings['gallery_images_path'] . '/' . $dir . $image['original_file_name'] );
		}
		
		if ( $image['media_thumb'] )
		{
			if ( is_file( $this->settings['gallery_images_path'] . '/' . $dir . $image['media_thumb'] ) )
			{
				@unlink( $this->settings['gallery_images_path'] . '/' . $dir . $image['media_thumb'] );
			}
		}
		
		if ( $checkDirectory && $image['directory'] )
		{
			// is this file(s) the last ones in the dir?
			
			$files = array();
			
			$dh = @opendir(  $this->settings['gallery_images_path'] . '/' . $dir );
			
			if( $dh )
			{
				while( false !== ($file = readdir($dh))) 
				{
					if( $file != "." AND $file != ".." )
					{
						$files[] = $file;
					}
				}
				
				closedir($dh);
			}
			
			if ( ( count($files) == 1 AND $files[0] == "index.html" ) OR count($files) == 0 )
			{
				if( $files[0] )
				{
					@unlink( $this->settings['gallery_images_path'] . '/' . $dir . $files[0] );
				}

				@rmdir( $this->settings['gallery_images_path'] . '/' . $dir );
			}
		}
		
		return true;
	}
	
	/**
	 * Add an entry to the moderator logs
	 *
	 * @param	string		$action
	 * @return	@e void
	 */
	public function addModLog( $action )
	{
		$this->DB->insert( 'moderator_logs', array( 'member_id'   => $this->memberData['member_id'],
													'member_name' => $this->memberData['members_display_name'],
													'ip_address'  => $this->member->ip_address,
													'http_referer'=> htmlspecialchars( my_getenv( 'HTTP_REFERER' ) ),
													'ctime'       => time(),
													'action'      => $action,
													'query_string'=> htmlspecialchars( my_getenv('QUERY_STRING') ),
												), true );
	}
	
	/**
	 * Returns higher perm id
	 * @param int $parentId
	 */
	private function _getNewAlbumPosition( $parentId )
	{
		$pos = $this->DB->buildAndFetch( array( 'select' => 'MAX(album_position) as ition',
												'from'   => 'gallery_albums_main',
												'where'  => 'album_parent_id=' . intval( $parentId ) ) );
		
		return intval( $pos['ition'] ) + 1;
	}
	
	/**
	 * Set up and return a new node class handler
	 *
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
}