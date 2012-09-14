<?php
/**
 * @file		rssimport.php 	Task to import RSS feeds defined in the ACP
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		-
 * $LastChangedDate: 2011-05-20 00:17:42 -0400 (Fri, 20 May 2011) $
 * @version		v3.3.3
 * $Revision: 8847 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		task_item
 * @brief		Task to import RSS feeds defined in the ACP
 *
 */
class task_item
{
	/**
	 * Integer used to limit the number
	 * of RSS feeds parsed per cycle
	 *
	 * @var		$limit
	 */
	protected $limit = 10;
	
	/**
	 * Object that stores the parent task manager class
	 *
	 * @var		$class
	 */
	protected $class;
	
	/**
	 * Array that stores the task data
	 *
	 * @var		$task
	 */
	protected $task = array();
	
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
	 * @param	object		$class			Task manager class object
	 * @param	array		$task			Array with the task data
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $class, $task )
	{
		/* Make registry objects */
		$this->registry		= $registry;
		$this->DB			= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->lang			= $this->registry->getClass('class_localization');
		$this->member		= $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		
		$this->class	= $class;
		$this->task		= $task;
	}
	
	/**
	 * Run this task
	 *
	 * @return	@e void
	 */
	public function runTask()
	{
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_global' ), 'core' );
		
		/* Init vars */
		$feeds_to_update	= array();
		$time				= time();
		$t_minus_30			= time() - ( 30 * 60 );
		
		//-----------------------------------------
		// Got any to update?
		// 30 mins is RSS friendly.
		//-----------------------------------------
		
		$this->DB->build( array( 'select'	=> '*', 
								 'from'		=> 'rss_import', 
								 'where'	=> 'rss_import_enabled=1 AND rss_import_last_import <= '.$t_minus_30,
								 'order'	=> 'rss_import_last_import ASC',
								 'limit'	=> array( $this->limit )
						 )		);
		$rss_main_query = $this->DB->execute();
		
		if ( $this->DB->getTotalRows( $rss_main_query ) )
		{
			$classToLoad = IPSLib::loadActionOverloader( IPSLib::getAppDir( 'forums' ) . '/modules_admin/rss/import.php', 'admin_forums_rss_import' );
			$rss = new $classToLoad();
			$rss->makeRegistryShortcuts( $this->registry );

			while( $rss_feed = $this->DB->fetch( $rss_main_query ) )
			{
				$this_check = time() - ( $rss_feed['rss_import_time'] * 60 );
				
				if ( $rss_feed['rss_import_last_import'] <= $this_check )
				{
					//-----------------------------------------
					// Set the feeds we need to update...
					//-----------------------------------------
					
					$feeds_to_update[] = $rss_feed['rss_import_id'];
				}
			}

			//-----------------------------------------
			// Do the update now...
			//-----------------------------------------

			if ( count($feeds_to_update) )
			{
				$rss->rssImportRebuildCache( implode( ",", $feeds_to_update), 0, 1 );
			}
		}

		//-----------------------------------------
		// Log to log table - modify but dont delete
		//-----------------------------------------

		$this->class->appendTaskLog( $this->task, sprintf( $this->lang->words['task_rssimport'], count($feeds_to_update) ) );
		
		//-----------------------------------------
		// Unlock Task: DO NOT MODIFY!
		//-----------------------------------------
		
		$this->class->unlockTask( $this->task );
	}
}