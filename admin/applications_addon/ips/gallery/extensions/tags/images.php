<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Tagging: Forums/Topics Class
 * Matt Mecham
 * Last Updated: $Date: 2011-09-13 01:59:05 +0100 (Tue, 13 Sep 2011) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2011 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		25 Feb 2011
 * @version		$Revision: 9483 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class tags_gallery_images extends classes_tag_abstract
{
	protected $imageCache = array();
		
	/**
	 * CONSTRUCTOR
	 *
	 * @return	@e void
	 */
	public function __construct()
	{
		/* Make registry objects */
		$this->registry   =  ipsRegistry::instance();
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
	}
	
	/**
	 * Init
	 *
	 * @return	@e void
	 */
	public function init()
	{
		/* Gallery Object */
		if ( ! ipsRegistry::isClassLoaded('gallery') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
			$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
		}
		
		/* Short cuts */
		$this->_albums   = $this->registry->gallery->helper('albums');
		$this->_images   = $this->registry->gallery->helper('image');
		$this->_media    = $this->registry->gallery->helper('media');
		
		parent::init();
	}
	
	/**
	 * Little 'trick' to force preset tags
	 *
	 * @param	string	view to show
	 * @param	array	Where data to show
	 */
	public function render( $what, $where )
	{
		if ( ! empty( $where['meta_parent_id'] ) )
		{
			$album = $this->_albums->fetchAlbumsById( $where['meta_parent_id'] );
		}
		
		if ( ! empty( $album['album_preset_tags'] ) )
		{
			/* Turn off open system */
			$this->settings['tags_open_system'] = false;
		}
		
		return parent::render( $what, $where );
	}
	
	/**
	 * Fetches parent ID
	 * @param 	array	Where Data
	 * @return	int		Id of parent if one exists or 0
	 */
	public function getParentId( $where )
	{
		$image = $this->_getImage( $where['meta_id'] );
		
		return intval( $image['img_album_id'] );
	}
	
	/**
	 * Fetches permission data
	 * @param 	array	Where Data
	 * @return	string	Comma delimiter or *
	 */
	public function getPermissionData( $where )
	{
		if ( isset( $where['meta_id'] ) )
		{
			$image = $this->_getImage( $where['meta_id'] );
			$album = $this->_albums->fetchAlbumsById( $image['id'] );
		}
		else if ( isset( $where['meta_parent_id'] ) )
		{
			$album = $this->_albums->fetchAlbumsById( $where['meta_parent_id'] );
		}
		
		return $album['album_g_perms_view'];
	}
	
	/**
	 * Basic permission check
	 * @param	string	$what (add/remove/edit/create/prefix) [ add = add new tags to items, create = create unique tags, use a tag as a prefix for an item ]
	 * @param	array	$where data
	 */
	public function can( $what, $where )
	{
		$topic = array();
		$forum = array();
		
		if ( ! empty( $where['meta_id'] ) )
		{
			$image = $this->_getImage( $where['meta_id'] );
			$album = $this->_albums->fetchAlbumsById( $image['img_album_id'] );
		}
		else if ( ! empty( $where['meta_parent_id'] ) )
		{
			$album = $this->_albums->fetchAlbumsById( $where['meta_parent_id'] );
		}
		
		/* Check parent */
		$return = parent::can( $what, $where );

		if ( $return === false  )
		{
			return $return;
		}
		
		/* Album disabled */
		if ( ! $album['album_can_tag'] )
		{
			return false;
		}

		switch ( $what )
		{
			case 'create':
				if ( ! $this->_isOpenSystem() )
				{
					return false;
				}
				
				return true;
			break;
			case 'add':
			 
				if ( $this->_albums->isUploadable( $album['album_id'] ) || $this->_albums->canModerate( $album ) )
				{
					return true;
				}
			break;
			case 'edit':
			case 'remove':
				if ( $this->memberData['member_id'] == $album['album_owner_id'] || $this->_albums->canModerate( $album ) )
				{
					return true;
				}
			break;
			case 'prefix':
				return false;
				/*if ( $this->_albums->isUploadable( $album['album_id'] ) )
				{
					return true;
				}*/
			break;
		}
		
		return false;
	}
	
	/**
	 * DEFAULT: returns true and should be defined in your own class
	 * @param 	array	Where Data
	 * @return	int		If meta item is visible (not unapproved, etc)
	 */
	public function getIsVisible( $where )
	{
		$image = $this->_getImage( $where['meta_id'] );
		
		return intval( $image['approved'] );
	}
	
	/**
	 * Search for tags
	 * @param mixed $tags	Array or string
	 * @param array $options	Array( 'meta_id' (array), 'meta_parent_id' (array), 'olderThan' (int), 'youngerThan' (int), 'limit' (int), 'sortKey' (string) 'sortOrder' (string) )
	 */
	public function search( $tags, $options )
	{
		$ok = array();
		
		/* Fix up forum IDs */
		if ( isset( $options['meta_parent_id'] ) )
		{
			if ( is_array( $options['meta_parent_id'] ) )
			{
				foreach( $options['meta_parent_id'] as $id )
				{
					if ( $this->_canSearchAlbum( $id ) === true )
					{
						$ok[] = $id;
					}
				}
			}
			else
			{
				if ( $this->_canSearchAlbum( $options['meta_parent_id'] ) === true )
				{
					$ok[] = $options['meta_parent_id'];
				}
			}
		}
		else
		{
			/* Fetch forum IDs */
			//$ok = $this->registry->class_forums->fetchSearchableForumIds();
		}
		
		$options['meta_parent_id'] = $ok;
		
		return parent::search( $tags, $options );
	}
	
	/**
	 * Get text field name (future expansion)
	 * @param 	array	Where Data
	 * @return 	Booyaleean
	 */
	protected function _getFieldId( $where )
	{
		return 'ipsTags_' . ( ( ! empty( $where['fake_meta_id'] ) ) ? $where['fake_meta_id'] : $where['meta_id']);
	}
	
	/**
	 * Fetch a list of pre-defined tags
	 * 
	 * @param 	array	Where Data
	 * @return	Array of pre-defined tags or null
	 */
	protected function _getPreDefinedTags( $where=array() )
	{
		if ( ! empty( $where['meta_parent_id'] ) )
		{
			$album = $this->_albums->fetchAlbumsById( $where['meta_parent_id'] );
		}
		
		$this->settings['tags_predefined'] = ( ! empty( $album['album_preset_tags'] ) ) ? $album['album_preset_tags'] : $this->settings['tags_predefined'];
		
		return parent::_getPreDefinedTags( $where );
	}
	
	/**
	 * Can set an item as a topic prefix
	 * 
	 * @param 	array		$where		Where Data
	 * @return 	@e boolean
	 */
	protected function _prefixesEnabled( $where )
	{
		return false;
	}
	
	/**
	 * Check an album for tag searching
	 * 
	 * @param	id		$id		Forum ID
	 * @return	@e boolean	True if it can be searched
	 */
	protected function _canSearchAlbum( $id )
	{
		return $this->_albums->isViewable( $id );
	}
	
	/**
	 * Fetch an image
	 * 
	 * @param	integer		$imageId	Image ID
	 * @return	@e array	Image data
	 */
	protected function _getImage( $imageId )
	{
		if ( ! isset( $this->imageCache[ $imageId ] ) )
		{
			$this->imageCache[ $imageId ] = $this->_images->fetchImage( $imageId, true, false );
		}
		
		return $this->imageCache[ $imageId ];
	}
}