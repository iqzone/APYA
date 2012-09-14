<?php
/**
 * Library/Comment View
 *
 * Handles comment display
 *
 * @author 		$Author: mmecham $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @version		$Rev: 9792 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class gallery_comments
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
	 * Constructor
	 *
	 * @access	public
	 * @param	ipsRegistry	$registry
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $type='images' )
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
	}
	
	/**
	 * Fetch a comment by ID (no perm checks)
	 *
	 * @param	int			$id			Comment ID
	 * @param	string		$type		Comment type
	 * @param	boolean		$force		Force to reload a comment from the DB instead of using the cache
	 * @return	@e array	Comment data
	 */
	public function fetchById( $id, $type='image', $force=false )
	{
		$id = intval($id);
		
		static $commentsCache = array();
		
		/* Got something cached? */
		if ( !isset($commentsCache[ $type ][ $id ]) || $force === true )
		{
			$commentsCache[ $type ][ $id ] = array();
			
			if ( $type == 'image' )
			{
				$commentsCache[ $type ][ $id ] = $this->DB->buildAndFetch( array( 'select' => '*',
																 		 		  'from'   => 'gallery_comments',
																 				  'where'  => 'pid=' . intval($id)
																		  )		 );
			}
		}

		return $commentsCache[ $type ][ $id ];
	}

	/**
	 * Can comment on this image
	 *
	 * @access	public
	 * @param	array		Image Data 
	 */
	public function canComment( $parent, $type='image' )
	{
		if ( $type == 'image' )
		{
			$album = $this->registry->gallery->helper('albums')->fetchAlbumsById( $parent['img_album_id'] );
			
			/* Test permission */
			if ( ! $this->registry->gallery->helper('albums')->isViewable( $album ) )
			{
				return false;
			}
			
			/* Can post comments? */
			if ( ! $album['album_allow_comments'] )
			{
				return false;
			}
			
			return ( $this->memberData['g_comment'] ) ? true : false;
		}
	}
	
	/**
	 * Returns edit button status
	 *
	 * @access	public
	 * @param	array		Comment
	 * @param	array		Image
	 * @param	array		Album
	 * @return	@e boolean
	 */
	public function canDelete( $comment, $parent, $type='image' )
	{
		if ( $type == 'image' )
		{
			/* Init */
			if ( ! $this->memberData['member_id'] )
			{
				return false;
			}
			
			if ( $this->memberData['g_is_supmod'] )
			{
				return true;
			}
			
			/* Can delete ones own */
			if ( $comment['author_id'] == $this->memberData['member_id'] && $this->memberData['g_del_own'] )
			{
				return true;
			}

			$album = $this->registry->gallery->helper('albums')->fetchAlbumsById( $parent['img_album_id'] );
			
			/* Moderator */
			if ( $this->registry->gallery->helper('albums')->canModerate( $album ) )
			{
				return true;
			}
		}
		
		return false;
	}

	/**
	 * Returns edit button status
	 *
	 * @access	public
	 * @param	array		Comment
	 * @param	array		Image
	 * @param	array		Album
	 * @return	@e boolean
	 */
	public function canEdit( $comment, $parent, $type='image' )
	{
		/* Init */
		if ( ! $this->memberData['member_id'] )
		{
			return false;
		}
		
		if ( $this->memberData['g_is_supmod'] )
		{
			return true;
		}
		
		if ( $type == 'image' )
		{
			$album = $this->registry->gallery->helper('albums')->fetchAlbumsById( $parent['img_album_id'] );
			
			/* Moderator */
			if ( $this->registry->gallery->helper('albums')->canModerate( $album ) )
			{
				return true;
			}
			
			/* Edit ones own? */
			if ( $comment['author_id'] == $this->memberData['member_id'] && $this->memberData['g_edit_own'] )
			{
				/* Have we set a time limit? */
				if ( $this->memberData['g_edit_cutoff'] > 0 )
				{
					if ( $comment['post_date'] > ( time() - ( intval( $this->memberData['g_edit_cutoff'] ) * 60 ) ) )
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
					return true;
				}
			}
		}
		
		return false;
	}
}