<?php
/**
 * @file		generate.php 	Navigation plugin: attachments
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		3/8/2011
 * $LastChangedDate: 2011-05-12 22:28:10 -0400 (Thu, 12 May 2011) $
 * @version		v3.3.3
 * $Revision: 8754 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		navigation_core
 * @brief		Provide ability to share attachments via editor
 */
class navigation_core
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
	}
	
	/**
	 * Return the tab title
	 *
	 * @return	@e string
	 */
	public function getTabName()
	{ 
		return $this->lang->words['quicknav_comm_link'];
	}
	
	/**
	 * Returns navigation data
	 * 
	 * @return	@e array	array( array( 0 => array( 'title' => 'x', 'url' => 'x' ) ) );
	 */
	public function getNavigationData()
	{
		$blocks = array();
		$links  = array();
		
		$links[] = array( 'title' => $this->lang->words['view_new_posts']       , 'url' => $this->registry->output->buildUrl( 'app=core&amp;module=search&amp;do=viewNewContent&amp;search_app=forums', 'public') );
		$links[] = array( 'title' => $this->lang->words['online_user_link']     , 'url' => $this->registry->output->buildUrl( 'app=members&amp;module=online&amp;sort_order=desc', 'public') );
		$links[] = array( 'title' => $this->lang->words['registered_users_link'], 'url' => $this->registry->output->buildSEOUrl( 'app=members&amp;module=list', 'public', 'false', 'members_list' ) );
		$links[] = array( 'title' => $this->lang->words['status_updates_link']  , 'url' => $this->registry->output->buildSEOUrl( 'app=members&amp;module=profile&amp;section=status', 'public', 'false', 'members_status_all' ) );
		
		/* Add to blocks */
		$blocks[] = array( 'title' => '', 'links' => $links );
		
		return $blocks;
	}
}