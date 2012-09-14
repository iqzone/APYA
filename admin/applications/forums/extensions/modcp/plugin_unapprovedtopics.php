<?php
/**
 * @file		plugin_unapprovedtopics.php 	Moderator control panel plugin: show topics pending approval
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: mmecham $
 * @since		2/15/2011
 * $LastChangedDate: 2012-04-26 10:09:11 -0400 (Thu, 26 Apr 2012) $
 * @version		v3.3.3
 * $Revision: 10645 $
 */


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		plugin_forums_unapprovedtopics
 * @brief		Moderator control panel plugin: show topics pending approval
 * 
 */
class plugin_forums_unapprovedtopics
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
	 * Forums library object
	 *
	 * @var		$forums
	 */
	protected $forums;

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
		$this->registry->class_localization->loadLanguageFile( array( 'public_forums' ), 'forums' );
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
		return 'unapprovedtopics';
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
		// Get forum class
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadActionOverloader( IPSLib::getAppDir('forums') . '/modules_public/forums/forums.php', 'public_forums_forums_forums' );
		$this->forums	= new $classToLoad( $this->registry );
		$this->forums->makeRegistryShortcuts( $this->registry );

		$st			= intval($this->request['st']);
		$_filters	= $this->_getFilters();
		$_filters	= array_merge( $_filters, array(
													'topicType'		=> array( 'hidden' ),
													'getCount'		=> true,
													'sortField'		=> 'tid',
													'sortOrder'		=> 'desc',
													'limit'			=> 10,
													'offset'		=> $st
								)					);

		$this->registry->getClass('topics')->setPermissionData();
		$topics	= $this->registry->getClass('topics')->getTopics( $_filters );
		$total	= $this->registry->getClass('topics')->getTopicsCount();
		$final	= array();
	
		//-----------------------------------------
		// Format data
		//-----------------------------------------

		if( count( $topics ) )
		{
			foreach( $topics as $tid => $topic )
			{
				$topic			= $this->_checkPermissions( $topic );
				$topic			= $this->forums->renderEntry( $topic );
				$topic['forum']	= $this->registry->class_forums->getForumById( $topic['forum_id'] );
				$final[ $tid ]	= $topic;
			}
		}
		
		$other_data	= array();
		
		if ( is_array( $topics ) AND count( $topics ) )
		{
			$other_data	= IPSDeleteLog::fetchEntries( array_keys($topics), 'topic', false );
		}

		//-----------------------------------------
		// Page links
		//-----------------------------------------
		
		$pages	= $this->registry->output->generatePagination( array(	'totalItems'		=> $total,
																		'itemsPerPage'		=> 10,
																		'currentStartValue'	=> $st,
																		'baseUrl'			=> "app=core&amp;module=modcp&amp;fromapp=forums&amp;tab=unapprovedtopics",
															)		);
		
		return $this->registry->output->getTemplate('modcp')->unapprovedTopics( $final, $pages );
	}
	
	/**
	 * Add permissions to the array
	 *
	 * @param	array 	$row	Array of topic data
	 * @return	@e array
	 */
	protected function _checkPermissions( $row )
	{
		$row['permissions']								= array();

		$row['permissions']['PostSoftDelete']			= $this->registry->getClass('class_forums')->canSoftDeletePosts( $row['forum_id'], array() );
		$row['permissions']['PostSoftDeleteRestore']	= $this->registry->getClass('class_forums')->can_Un_SoftDeletePosts( $row['forum_id'] );
		$row['permissions']['PostSoftDeleteSee']		= $this->registry->getClass('class_forums')->canSeeSoftDeletedPosts( $row['forum_id'] );
		$row['permissions']['SoftDeleteReason']			= $this->registry->getClass('class_forums')->canSeeSoftDeleteReason( $row['forum_id'] );
		$row['permissions']['SoftDeleteContent']		= $this->registry->getClass('class_forums')->canSeeSoftDeleteContent( $row['forum_id'] );
		$row['permissions']['TopicSoftDelete']			= $this->registry->getClass('class_forums')->canSoftDeleteTopics( $row['forum_id'], array() );
		$row['permissions']['TopicSoftDeleteRestore']	= $this->registry->getClass('class_forums')->can_Un_SoftDeleteTopics( $row['forum_id'] );
		$row['permissions']['TopicSoftDeleteSee']		= $this->registry->getClass('class_forums')->canSeeSoftDeletedTopics( $row['forum_id'] );
		$row['permissions']['canQueue']					= $this->registry->getClass('class_forums')->canQueuePosts( $row['forum_id'] );
		
		return $row;
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
 					if( $forum['topic_q'] )
 					{
 						$_return['forumId'][]	= $fid;
 					}
 				}
 			}
 		}
 		
 		return $_return;
 	}
}