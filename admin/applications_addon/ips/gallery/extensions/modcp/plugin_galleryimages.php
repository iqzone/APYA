<?php
/**
 * Invision Power Services
 * IP.Gallery - Unapproved Comments Extension
 * Last Updated: $Date: 2011-10-21 06:20:03 -0400 (Fri, 21 Oct 2011) $
 *
 * @author 		$Author: bfarber $ (Orginal: Matt)
 * @copyright	© 2010 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Downloads
 * @link		http://www.invisionpower.com
 * @since		5th October 2011
 * @version		$Revision: 9660 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class plugin_gallery_galleryimages
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
	 * Cat permissions
	 *
	 * @var	string
	 */
	protected $_gotAlbums	= null;
	
	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Make shortcuts
		//-----------------------------------------

		$this->registry		= $registry;
		$this->DB			= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->member		= $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		$this->lang			= $this->registry->class_localization;
		
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
	 * Determine if we can view tab
	 *
	 * @param	array 	$permissions	Moderator permissions
	 * @return	@e bool
	 */
	public function canView( $permissions )
	{
		if ( $this->_getAlbums( $permissions ) )
		{ 
			return true;
		}
		
		return false;
	}
	
	/**
	 * Returns the primary tab key for the navigation bar
	 * 
	 * @return	@e string
	 */
	public function getPrimaryTab()
	{
		return 'unapproved_content';
	}
	
	/**
	 * Returns the secondary tab key for the navigation bar
	 * 
	 * @return	@e string
	 */
	public function getSecondaryTab()
	{ 
		return 'galleryimages';
	}
	
	/**
	 * Execute plugin
	 *
	 * @param	array 	$permissions	Moderator permissions
	 * @return	@e string
	 */
	public function executePlugin( $permissions )
	{
		//-----------------------------------------
		// Check permissions
		//-----------------------------------------
		
		if ( ! $this->_getAlbums( $permissions ) )
		{
			return '';
		}
				
		//----------------------------------
		// Get Images Pending Approval
		//----------------------------------
		
		$limiter	= ( $this->_getAlbums() ) == '*' ? '' : ( is_array( $this->_getAlbums() ) ? " AND i.img_album_id IN ( " . implode( ',', array_keys( $this->_getAlbums() ) ) . ")" : ' AND 1=2' );

		$this->DB->build( array(
								'select'	=> 'i.*',
								'from'		=> array( 'gallery_images' => 'i' ),
								'where'		=> "i.approved=0" . $limiter,
								'add_join'	=> array(
													array(
															'select'	=> 'm.*',
															'from'		=> array( 'members' => 'm' ),
															'where'		=> 'm.member_id=i.member_id',
															'type'		=> 'left',
														),
													array(
															'select'	=> 'pp.*',
															'from'		=> array( 'profile_portal' => 'pp' ),
															'where'		=> 'm.member_id=pp.pp_member_id',
															'type'		=> 'left',
														),
													array(
															'select'	=> 'a.*',
															'from'		=> array( 'gallery_albums_main' => 'a' ),
															'where'		=> 'i.img_album_id=a.album_id',
															'type'		=> 'left',
														),
													)
							)		);
		$e = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $e ) )
		{
			$row['tag']     = $this->_images->makeImageTag( $row, array( 'type' => 'small' ) );
		
			$results[] = IPSMember::buildDisplayData( $row );
		}
						
		return $this->registry->getClass('output')->getTemplate('gallery_user')->unapprovedImages( $results );
	}

	/**
	 * Get albums we can approve comments in
	 *
	 * @return	@e string
	 */
	protected function _getAlbums( $permissions='' )
	{
		if ( $this->_gotAlbums !== null )
		{
			return $this->_gotAlbums;
		}
		
		if ( $this->memberData['g_access_cp'] )
		{
			$this->_gotAlbums = '*';
		}
		else
		{
			$this->_gotAlbums = $this->_albums->fetchAlbumsByFilters( array( 'isViewable' => 1, 'getFields' => array( 'album_id' ) ) );
		}
		
		return $this->_gotAlbums;
	}
}