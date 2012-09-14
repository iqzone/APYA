<?php
/**
 * @file		plugin_unapprovedposts.php 	Moderator control panel plugin: show posts pending approval
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		2/15/2011
 * $LastChangedDate: 2011-05-31 14:07:00 -0400 (Tue, 31 May 2011) $
 * @version		v3.3.3
 * $Revision: 8930 $
 */


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		plugin_forums_unapprovedposts
 * @brief		Moderator control panel plugin: show posts pending approval
 * 
 */
class plugin_forums_unapprovedposts
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
	 * Execute selected method
	 *
	 * @param	object		Registry object
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
		
		/* Load language strings.. */
		$this->registry->class_localization->loadLanguageFile( array( 'public_topic' ), 'forums' );
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
		return 'unapprovedposts';
	}
	
	/**
	 * Determine if we can view tab
	 *
	 * @param	array 	$permissions	Moderator permissions
	 * @return	@e bool
	 */
	public function canView( $permissions )
	{
		if( $this->memberData['g_is_supmod'] OR $this->memberData['is_mod'] )
		{
			return true;
		}
		
		return false;
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

		if( !$this->canView( $permissions ) )
		{
			return '';
		}

		//-----------------------------------------
		// Get forum class
		//-----------------------------------------
		
		if ( ! $this->registry->isClassLoaded('topics') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/topics.php", 'app_forums_classes_topics', 'forums' );
			$this->registry->setClass( 'topics', new $classToLoad( $this->registry ) );
		}

		//-----------------------------------------
		// Get results
		//-----------------------------------------

		$st		= intval($this->request['st']);
		$_filters	= $this->_getFilters();
		$_filters	= array_merge( $_filters, array(
													'postType'		=> array( 'hidden' ),
													'getCount'		=> true,
													'sortField'		=> 'pid',
													'sortOrder'		=> 'desc',
													'parse'			=> true,
													'limit'			=> 10,
													'offset'		=> $st
								)					);
		
		$this->registry->getClass('topics')->setPermissionData();
		$posts	= $this->registry->getClass('topics')->getPosts( $_filters );
		$total	= $this->registry->getClass('topics')->getPostsCount();
		
		//-----------------------------------------
		// Page links
		//-----------------------------------------
		
		$pages	= $this->registry->output->generatePagination( array(	'totalItems'		=> $total,
																		'itemsPerPage'		=> 10,
																		'currentStartValue'	=> $st,
																		'baseUrl'			=> "app=core&amp;module=modcp&amp;fromapp=forums&amp;tab=unapprovedposts",
															)		);

		return $this->registry->output->getTemplate('modcp')->unapprovedPosts( $posts, $pages );
	}

	/**
	 * Retrieve forum ids we can moderate in for getTopics() call
	 * 
	 * @return	@e array
	 */
 	protected function _getFilters()
 	{
 		$_return	= array();
 		
 		if( $this->memberData['g_is_supmod'] )
 		{
 			$_return['skipForumCheck']	= true;
 		}
 		else
 		{
 			$_return['forumId']			= array( 0 );
 			
 			if( count($this->memberData['forumsModeratorData']) )
 			{
 				foreach( $this->memberData['forumsModeratorData'] as $fid => $forum )
 				{
 					if( $forum['post_q'] )
 					{
 						$_return['forumId'][]	= $fid;
 					}
 				}
 			}
 		}
 		
 		return $_return;
 	}
}