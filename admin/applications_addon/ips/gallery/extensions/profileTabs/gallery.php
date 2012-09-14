<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Profile Plugin Library
 * Last Updated: $Date: 2011-11-04 12:50:51 -0400 (Fri, 04 Nov 2011) $
 * </pre>
 *
 * @author 		$Author: mmecham $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @version		$Rev: 9761 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class profile_gallery extends profile_plugin_parent
{
	/**
	 * return HTML block
	 *
	 * @access	public
	 * @param	array		Member information
	 * @return	@e string		HTML block
	 */
	public function return_html_block( $member=array() ) 
	{
		/* Can we use gallery? */
		if( ! $this->memberData['g_gallery_use'] )
		{
			return $this->lang->words['err_no_posts_to_show'];
		}
		
		/* Load Language */
		$this->lang->loadLanguageFile( array( 'public_gallery', 'public_gallery_four' ), 'gallery' );
		
		/* Gallery Object */
		if ( !ipsRegistry::isClassLoaded('gallery') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
			$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
		}
		
		/* Fetch 10 recently updated member albums */
		$albums  = $this->registry->gallery->helper('albums')->fetchAlbumsByFilters( array( 'getTotalCount'    => true,
																							'getChildrenCount' => true,
																							'album_owner_id'   => $member['member_id'],
																							'isViewable'       => true,
																							'album_is_global'  => 0,
																							'sortKey'          => 'date',
																							'sortOrder'        => 'desc',
																							'limit'            => 5,
																							'checkForMore' 	   => true,
																							'parseAlbumOwner'  => true,
																							'offset'           => 0
																					)		);
		
		$hasMore = $this->registry->gallery->helper('albums')->hasMore();
		
		
		/* Fetch 30 updated images */
		$images = $this->registry->gallery->helper('image')->fetchMembersImages( $member['member_id'], array( 'sortKey'          => 'date',
																								 'sortOrder'        => 'desc',
																								 'getTags'			=> true,
																								 'parseImageOwner'  => true,
																								 'limit'            => 30 ) );
																								 
		return $this->registry->getClass('output')->getTemplate('gallery_user')->profileBlock( $member, $albums, $images, $hasMore );
	}
	
}