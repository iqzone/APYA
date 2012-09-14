<?php
/**
 * @file		generate.php 	Navigation plugin: Gallery
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $ < I wrote this actually you glory stealer (-Matt x)
 * @since		3/8/2011
 * $LastChangedDate: 2011-03-31 11:17:44 +0100 (Thu, 31 Mar 2011) $
 * @version		v4.2.1
 * $Revision: 8229 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		navigation_gallery
 * @brief		Provide ability to share attachments via editor
 */
class navigation_gallery
{
	/**
	 * Registry Object Shortcuts
	 *
	 * @var		$registry
	 * @var		$DB
	 * @var		$settings
	 * @var		$request
	 * @var		$lang
	 * @var		$member
	 * @var		$memberData
	 * @var		$cache
	 * @var		$caches
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
	

	/**
	 * Constructor
	 *
	 * @return	@e void
	 */
	public function __construct() 
	{
		$this->registry		=  ipsRegistry::instance();
		$this->DB			=  $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->member		=  $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		=  $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		$this->lang			=  $this->registry->class_localization;
		
		if ( ! ipsRegistry::instance()->isClassLoaded( 'gallery') )
		{ 
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
			ipsRegistry::instance()->setClass( 'gallery', new $classToLoad( ipsRegistry::instance() ) );
		}
		
		/* Load Language */
		$this->lang->loadLanguageFile( array( 'public_gallery_four' ), 'gallery' );
		
		$this->_images = $this->registry->gallery->helper('image');
		$this->_albums = $this->registry->gallery->helper('albums');
	}
	
	/**
	 * Return the tab title
	 *
	 * @return	@e string
	 */
	public function getTabName()
	{ 
		return IPSLib::getAppTitle( 'gallery' );
	}
	
	/**
	 * Returns navigation data
	 * @return	array	array( array( 0 => array( 'title' => 'x', 'url' => 'x' ) ) );
	 */
	public function getNavigationData()
	{
		$blocks = array();
		$global = $this->_getGlobalAlbumData();
		$yours  = $this->_getMemberAlbums();
			
		/* Add to blocks */
		$blocks[] = array( 'title' => $this->lang->words['g_nav_global'], 'links' => $global );
		
		if ( is_array( $yours ) && count( $yours ) )
		{
			$blocks[] = array( 'title' => $this->lang->words['g_nav_your'], 'links' => $yours );
		}
		
		return $blocks;
	}
	
	/**
	 * Fetches global albums in a lovely nested format.
	 *
	 * @return	string
	 */
	private function _getMemberAlbums()
	{
		$links = array();
		
		if ( $this->memberData['member_id'] )
		{
			$mine = $this->_albums->fetchAlbumsByFilters( array('album_owner_id' => $this->memberData['member_id'], 'getParents' => true, 'album_is_global' => 0, 'limit' => 150, 'isViewable' => 1 ) );
			
			if ( count( $mine ) )
			{
				foreach( $mine as $id => $album )
				{
					$title = '';
					
					if ( $album['_parents'] )
					{
						foreach( $album['_parents'] as $id => $data )
						{
							$title = $data['album_name'] . " <span class='ipsType_small desc'>&rarr; </span>";
						}
					}
				
					$title .= '<strong>' . $album['album_name'] . '</strong>';
					
					$links[] = array( 'depth' => 0, 'title' => $title, 'url' => $this->registry->output->buildSEOUrl( 'app=gallery&amp;album=' . $album['album_id'], 'public', $album['album_name_seo'], 'viewalbum' ) );
				}
			}
		}
		
		return $links;
	}
	
	/**
	 * Fetches global albums in a lovely nested format.
	 *
	 * @return	string
	 */
	private function _getGlobalAlbumData()
	{
		$depth_guide = 0;
		$links		 = array();
		
		/* Fetch entire tree of global albums */
		$albums = $this->_albums->fetchAlbumChildren( 0, array( 'album_is_global' => 1, 'isViewable' => 1, 'nestDescendants' => true ) );
		
		if ( is_array( $albums ) AND count( $albums ) )
		{
			foreach( $albums as $id => $data )
			{
				$links[] = array( 'important' => true, 'depth' => $depth_guide, 'title' => $data['album_name'], 'url' => $this->registry->output->buildSEOUrl( 'app=gallery&amp;album=' . $data['album_id'], 'public', $data['album_name_seo'], 'viewalbum' ) );
				
				if ( isset( $data['_children_'] ) AND is_array( $data['_children_'] ) )
				{
					$depth_guide++;
					
					foreach( $data['_children_'] as $sid => $subAlbum )
					{
						$links[] = array( 'depth' => $depth_guide, 'title' => $subAlbum['album_name'], 'url' => $this->registry->output->buildSEOUrl( 'app=gallery&amp;album=' . $subAlbum['album_id'], 'public', $subAlbum['album_name_seo'], 'viewalbum' ) );
						
						if ( isset( $data['_children_'] ) AND is_array( $data['_children_'] ) )
						{
							$links = $this->_getDataRecursively( $subAlbum['_children_'], $links, $depth_guide );
						}	
					}
					
					$depth_guide--;
				}
			
			}
		}
		
		return $links;
	}
	
	/**
	 * Internal helper function for forumsForumJump
	 *
	 * @param	integer	$root_id
	 * @param	array	$links
	 * @param	string	$depth_guide
	 * @return	string
	 */
	private function _getDataRecursively( $subSubAlbums, $links=array(), $depth_guide=0 )
	{
		if ( isset( $subSubAlbums ) AND is_array( $subSubAlbums ) )
		{
			$depth_guide++;
			
			foreach( $subSubAlbums as $id => $data )
			{
				$links[] = array( 'depth' => $depth_guide, 'title' => $subSubAlbums['album_name'], 'url' => $this->registry->output->buildSEOUrl( 'app=gallery&amp;album=' . $subSubAlbums['album_id'], 'public', $subSubAlbums['album_name_seo'], 'showforum' ) );
								
				$links = $this->_getDataRecursively( $subSubAlbums['_children_'], $links, $depth_guide );
			}
		}
		
		return $links;
	}
	
}