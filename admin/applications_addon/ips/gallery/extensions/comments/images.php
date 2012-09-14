<?php
/**
 * Gallery Comments class
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @version		$Rev: 9979 $ 
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class comments_gallery_images extends classes_comments_renderer
{
	/**
	 * Registry reference
	 *
	 * @var		object
	 */
	protected $registry;
	
	/**
	 * Internal remap array
	 *
	 * @param	array
	 */
	private $_remap = array( 'comment_id'			=> 'pid',
							 'comment_author_id'	=> 'author_id',
							 'comment_author_name'  => 'author_name',
							 'comment_text'			=> 'comment',
							 'comment_ip_address'   => 'ip_address',
							 'comment_edit_date'	=> 'edit_time',
							 'comment_date'			=> 'post_date',
							 'comment_approved'		=> 'approved',
							 'comment_parent_id'	=> 'img_id' );
					 
	/**
	 * Internal parent remap array
	 *
	 * @param	array
	 */
	private $_parentRemap = array( 'parent_id'			=> 'id',
							 	   'parent_owner_id'	=> 'member_id',
							       'parent_parent_id'   => 'img_album_id',
							       'parent_title'	    => 'caption',
							       'parent_seo_title'   => 'caption_seo',
							       'parent_date'	    => 'idate' );
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
		
		if ( ! $this->registry->isClassLoaded('gallery') )
		{
			/* Gallery Object */
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
			$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
		}
	}
	
	/**
	 * Who am I?
	 * 
	 * @return	@e string
	 */
	public function whoAmI()
	{
		return 'gallery-images';
	}
	
	/**
	 * Who am I?
	 * 
	 * @return	@e string
	 */
	public function seoTemplate()
	{
		return 'viewimage';
	}
	
	/**
	 * Comment table
	 *
	 * @return	@e string
	 */
	public function table()
	{
		return 'gallery_comments';
	}
    
	/**
	 * Fetch parent
	 *
	 * @return	@e array
	 */
	public function fetchParent( $id )
	{
		return $this->registry->gallery->helper('image')->fetchImage( $id );
	}
	
	/**
	 * Fetch settings
	 *
	 * @return	@e array
	 */
	public function settings()
	{
		return array( 'urls-showParent' => "app=gallery&amp;image=%s",
					  'urls-report'		=> $this->getReportLibrary()->canReport('gallery') ? "app=core&amp;module=reports&amp;rcom=gallery&amp;commentId=%s&amp;ctyp=comment" : '',
					 );
	}

	/**
	 * Pre save
	 * Accepts an array of GENERIC data and allows manipulation before it's added to DB
	 *
	 * @param	string	Type of save (edit/add)
	 * @param	array	Array of GENERIC data (comment_xxxx)
	 * @return 	array	Array of GENERIC data
	 */
	public function preSave( $type, array $array )
	{
		if ( $type == 'add' )
		{
			/* Load image and album */
			$parent  = $this->registry->gallery->helper('image')->fetchImage( $array['comment_parent_id'] );
			$album   = $this->registry->gallery->helper('albums')->fetchAlbumsById( $parent['img_album_id'] );
						
			if ( $array['comment_approved'] and $album['album_g_approve_com'] )
			{
				$array['comment_approved'] = $this->registry->gallery->helper('albums')->canModerate( $album ) ? 1 : 0;
			}
			
			/* Data Hook Location */
			IPSLib::doDataHooks( $array, 'galleryAddImageComment' );
		}
		else
		{
			/* Data Hook Location */
			IPSLib::doDataHooks( $array, 'galleryEditImageComment' );
		}
		
		return $array;
	}
	
	/**
	 * Post save
	 * Accepts an array of GENERIC data and allows manipulation after it's added to DB
	 *
	 * @param	string	Type of action (edit/add)
	 * @param	array	Array of GENERIC data (comment_xxxx)
	 * @return 	array	Array of GENERIC data
	 */
	public function postSave( $type, array $array )
	{
		if ( $type == 'add' )
		{
			/* Load image and album */
			$parent  = $this->registry->gallery->helper('image')->fetchImage( $array['comment_parent_id'] );
			$album   = $this->registry->gallery->helper('albums')->fetchAlbumsById( $parent['img_album_id'] );
		
			/* rebuild stats */
			$this->registry->gallery->helper('image')->resync( $parent['id'] );
			$this->registry->gallery->helper('albums')->resync( $album );
			$this->registry->gallery->rebuildStatsCache();
			
			/* Data Hook Location */
			IPSLib::doDataHooks( $array, 'galleryCommentAddPostSave' );
		}
		else
		{
			/* Data Hook Location */
			IPSLib::doDataHooks( $array, 'galleryCommentEditPostSave' );
		}
		
		return $array;
	}
	
	/**
	 * Post delete. Can do stuff and that
	 *
	 * @param	array	Array of comment IDs to be deleted
	 * @param	int		Parent ID
	 * @return 	void
	 */
	public function postDelete( $commentIds, $parentId )
	{
		/* Load image and album */
		$parent  = $this->registry->gallery->helper('image')->fetchImage( $parentId );
		$album   = $this->registry->gallery->helper('albums')->fetchAlbumsById( $parent['img_album_id'] );
		
		/* rebuild stats */
		$this->registry->gallery->helper('image')->resync( $parentId );
		$this->registry->gallery->helper('albums')->resync( $album );
		$this->registry->gallery->rebuildStatsCache();
		
		/* Data Hook Location */
		$_dataHook	= array( 'commentIds'	=> $commentIds,
							 'parentId'		=> $parentId );
		
		IPSLib::doDataHooks( $_dataHook, 'galleryCommentPostDelete' );
	}
	
	/**
	 * Toggles visibility
	 * 
	 * @param	string	on/off
	 * @param	array	Array of comment IDs to be deleted
	 * @param	int		Parent ID
	 * @return 	void
	 */
	public function postVisibility( $toggle, $commentIds, $parentId )
	{
		/* Load image and album */
		$parent  = $this->registry->gallery->helper('image')->fetchImage( $parentId );
		$album   = $this->registry->gallery->helper('albums')->fetchAlbumsById( $parent['img_album_id'] );
		
		/* rebuild stats */
		$this->registry->gallery->helper('image')->resync( $parentId );
		$this->registry->gallery->helper('albums')->resync( $album );
		$this->registry->gallery->rebuildStatsCache();
		
		/* Data Hook Location */
		$_dataHook	= array( 'toggle'		=> $toggle,
							 'commentIds'	=> $commentIds,
							 'parentId'		=> $parentId );
		
		IPSLib::doDataHooks( $_dataHook, 'galleryCommentToggleVisibility' );
	}
	
	/**
	 * Fetch a total count of comments we can see
	 *
	 * @param	mixed	parent Id or parent array
	 * @return	@e int
	 */
	public function count( $parent )
	{
		/* Check parent */
		if ( is_numeric( $parent ) )
		{
			$parent = $this->fetchParent( $parent );
		}
		
		/* Guarantee the data */
		$parent = $this->remapToLocal( $parent, 'parent' );
		$total  = $parent['comments'];
		
		/* more? */
		$total += ( $this->registry->gallery->helper('albums')->canModerate( $parent ) ) ? $parent['comments_queued'] : 0;
	
		return $total;
	}
	
	/**
	 * Perform a permission check
	 *
	 * @param	string	Type of check (add/edit/delete/editall/deleteall/approve all)
	 * @param	array 	Array of GENERIC data
	 * @return	true or string to be used in exception
	 */
	public function can( $type, array $array )
	{ 
		/* Init */
		$comment = array();
		
		/* Got data? */
		if ( empty( $array['comment_parent_id'] ) )
		{
			trigger_error( "No parent ID passed to " . __FILE__, E_USER_WARNING );
		}
		
		/* Fetch and check image */
		$parent  = $this->registry->gallery->helper('image')->fetchImage( $array['comment_parent_id'], FALSE, FALSE );
		$album   = $this->registry->gallery->helper('albums')->fetchAlbumsById( $parent['img_album_id'] );
		
		/* Fetch comment */
		if ( $array['comment_id'] )
		{ 
			$comment = $this->fetchById( $array['comment_id'] );
			$comment = $this->remapToLocal( $comment, 'comment' );
		}
		
		/* Check permissions */
		switch( $type )
		{
			case 'view':
				if ( ! $this->registry->gallery->helper('albums')->isViewable( $album ) )
				{
					return 'NO_PERMISSION';
				}
			break;
			case 'edit':
				if ( ! $this->registry->gallery->helper('comments')->canEdit( $comment, $parent, 'image' ) )
				{
					return 'NO_PERMISSION';
				}
			break;
			case 'add':
				if ( ! $this->registry->gallery->helper('comments')->canComment( $parent, 'image' ) )
				{
					return 'NO_PERMISSION';
				}
			break;
			case 'delete':
				if ( ! $this->registry->gallery->helper('comments')->canDelete( $comment, $parent, 'image' ) )
				{
					return 'NO_PERMISSION';
				}
			break;
			case 'visibility':
			case 'moderate':
				if ( ! $this->registry->gallery->helper('albums')->canModerate( $album ) )
				{
					return 'NO_PERMISSION';
				}
			break;
			case 'hide':
				return IPSMember::canModerateContent( $this->memberData, IPSMember::CONTENT_HIDE, $comment['comment_author_id'] ) ? TRUE : 'NO_PERMISSION';
				break;
			case 'unhide':
				return IPSMember::canModerateContent( $this->memberData, IPSMember::CONTENT_UNHIDE, $comment['comment_author_id'] ) ? TRUE : 'NO_PERMISSION';
				break;
		}
		
		/* Still here? We're not telling lies then */
		return true;
	}
	
	
	/**
	 * Returns remap keys (generic => local)
	 *
	 * @return	@e array
	 */
	public function remapKeys($type='comment')
	{
		return ( $type == 'comment' ) ? $this->_remap : $this->_parentRemap;
	}
}