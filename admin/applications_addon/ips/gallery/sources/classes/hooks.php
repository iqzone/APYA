<?php
/**
 * @file		hooks.php 	Gallery hooks library
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: mmecham $
 * $LastChangedDate: 2011-12-02 06:11:30 -0500 (Fri, 02 Dec 2011) $
 * @version		v4.2.1
 * $Revision: 9935 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		app_gallery_classes_hooks
 * @brief		Gallery hooks library
 */
class app_gallery_classes_hooks
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
	 * @param	object		$registry		Registry object
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
		
		/* Load up the gallery */
		if ( !$this->registry->isClassLoaded('gallery') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
			$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
		}
	}
	
	/**
	 * Hook: Recent images on board index
	 *
	 * @return	@e string	HTML
	 */
	public function hookBoardIndexRecentImages()
	{
		/* Init vars */
		$recents = array();
		
		/* Fetch 20 recent images */
		if ( $this->memberData['g_gallery_use'] && IPSLib::appIsInstalled('gallery') )
		{
			$recents = $this->registry->gallery->helper('image')->fetchImages( $this->memberData['member_id'], array( 'limit' => 20, 'sortKey' => 'date', 'sortOrder' => 'desc', 'unixCutOff' => GALLERY_A_YEAR_AGO ) );
		}
		
		return count( $recents ) ? $this->registry->output->getTemplate( 'gallery_global' )->hookRecentGalleryImages( $recents ) : '';
	}
}