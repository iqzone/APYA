<?php

/**
 * Member Synchronization extensions
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @version		$Rev: 8824 $ 
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class galleryMemberSync
{
	/**
	 * Registry reference
	 *
	 * @access	public
	 * @var		object
	 */
	public $registry;
	
	/**
	 * CONSTRUCTOR
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function __construct()
	{
		$this->registry = ipsRegistry::instance();
		$this->DB       = $this->registry->DB();
	}
	
	/**
	 * This method is run when a member is flagged as a spammer
	 *
	 * @access	public
	 * @param	array 	$member	Array of member data
	 * @return	@e void
	 */
	public function onSetAsSpammer( $member )
	{
		/* Check */
		if( $member['member_id'] )
		{
			/* Unapprove */
			$this->DB->update( 'gallery_images',	array( 'approved' => 0 ), "member_id={$member['member_id']}" );
			$this->DB->update( 'gallery_comments',	array( 'approved' => 0 ), "author_id={$member['member_id']}" );
			
			$this->_recountImages( $member );
		}
	}
	
	/**
	 * This method is run when a member is un-flagged as a spammer
	 *
	 * @access	public
	 * @param	array 	$member	Array of member data
	 * @return	@e void
	 */
	public function onUnSetAsSpammer( $member )
	{
		/* Should really track what was disabled  - @todo */
		if ( $member['member_id'] )
		{
			$this->DB->update( 'gallery_images',	array( 'approved' => 1 ), "member_id={$member['member_id']}" );
			$this->DB->update( 'gallery_comments',	array( 'approved' => 1 ), "author_id={$member['member_id']}" );
			
			$this->_recountImages( $member );
		}
	}
	
	private function _recountImages( $member )
	{
		/* Gallery Library */
		if ( !ipsRegistry::isClassLoaded('gallery') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
			$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
		}
		
		/* Get unique image ids */
		$this->DB->build( array( 'select'	=> 'DISTINCT(img_id)',
								 'from'		=> 'gallery_comments',
								 'where'	=> 'author_id=' . intval( $member['member_id'] ) ) );
		
		$this->DB->execute();
		
		$imgs		= array();
		$imgIdQuery	= '';
		$albums     = array();
		
		while( $row = $this->DB->fetch() )
		{
			$imgs[] = $row['img_id'];
		}
		
		if( is_array( $imgs ) && count( $imgs ) )
		{
			$imgIdQuery = ' OR id IN( ' . implode( ',', $imgs ) . ' )';
		}
		
		/* Get unique category ids */
		$this->DB->build( array( 'select'	=> 'DISTINCT(img_album_id)',
								 'from'		=> 'gallery_images',
								 'where'	=> 'member_id=' . intval( $member['member_id'] ) . $imgIdQuery ) );
		
		$this->DB->execute();
		
		
		while( $row = $this->DB->fetch() )
		{
			$albums[] = $row['img_album_id'];
		}
		
		/* Caches */
		if ( is_array( $albums ) && count( $albums ) )
		{
			foreach( $albums as $id )
			{
				$this->registry->gallery->helper('albums')->resync( $id );
			}
			
			$this->registry->gallery->rebuildStatsCache();
		}
	}
	
	/**
	 * This method is called after a member account has been removed
	 *
	 * @access	public
	 * @param	string	$ids	SQL IN() clause
	 * @return	@e void
	 */
	public function onDelete( $mids )
	{
		/* Gallery Library */
		if ( !ipsRegistry::isClassLoaded('gallery') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
			$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
		}
		
		//-----------------------------------------
		// Delete images
		//-----------------------------------------
		$images = array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'gallery_images', 'where' => 'member_id' . $mids ) );
		$outer = $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			$images[] = $r;
		}
		
		$this->registry->gallery->helper('moderate')->deleteImages( $images );
		
		//-----------------------------------------
		// Update to guest
		//-----------------------------------------
		
		$this->DB->update( 'gallery_comments', array( 'author_id' => 0 ), 'author_id' . $mids );
		
		//-----------------------------------------
		// Just delete
		//-----------------------------------------
		
		$this->DB->delete( 'gallery_albums_main', 'album_owner_id' . $mids );
		$this->DB->delete( 'gallery_bandwidth'  , 'member_id' . $mids );
		$this->DB->delete( 'gallery_ratings'    , 'member_id' . $mids );
		
		//-----------------------------------------
		// caches
		//-----------------------------------------
		
		$this->registry->gallery->rebuildStatsCache();
	}
	
	/**
	 * This method is called after a member's account has been merged into another member's account
	 *
	 * @access	public
	 * @param	array	$member		Member account being kept
	 * @param	array	$member2	Member account being removed
	 * @return	@e void
	 */
	public function onMerge( $member, $member2 )
	{
		//-----------------------------------------
		// Update to guest
		//-----------------------------------------
		
		$this->DB->update( 'gallery_albums_main', array( 'album_owner_id' => $member['member_id'] ), 'album_owner_id=' . $member2['member_id'] );
		$this->DB->update( 'gallery_bandwidth', array( 'member_id' => $member['member_id'] ), 'member_id=' . $member2['member_id'] );
		$this->DB->update( 'gallery_comments', array( 'author_id' => $member['member_id'], 'author_name' => $member['members_display_name'] ), 'author_id=' . $member2['member_id'] );
		$this->DB->update( 'gallery_images', array( 'member_id' => $member['member_id'] ), 'member_id=' . $member2['member_id'] );
		$this->DB->update( 'gallery_ratings', array( 'member_id' => $member['member_id'] ), 'member_id=' . $member2['member_id'] );
	}

	/**
	 * This method is run after a users display name is successfully changed
	 *
	 * @access	public
	 * @param	integer	$id			Member ID
	 * @param	string	$new_name	New display name
	 * @return	@e void
	 */
	public function onNameChange( $id, $new_name )
	{
		//-----------------------------------------
		// Fix comments
		//-----------------------------------------
		
		$this->DB->update( 'gallery_comments', array( 'author_name' => $new_name ), 'author_id=' . $id );
	}
}