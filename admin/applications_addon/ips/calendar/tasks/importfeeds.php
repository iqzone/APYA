<?php
/**
 * @file		importfeeds.php 	Task to reimport ical/webcal feeds
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		9th Feb 2011
 * $LastChangedDate: 2011-03-31 06:17:44 -0400 (Thu, 31 Mar 2011) $
 * @version		vVERSION_NUMBER
 * $Revision: 8229 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		task_item
 * @brief		Task to reimport ical/webcal feeds
 *
 */
class task_item
{
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
	 * @var		$lang
	 */
	protected $registry;
	protected $DB;
	protected $lang;
	
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
		$this->registry	= $registry;
		$this->DB		= $registry->DB();
		$this->lang		= $this->registry->getClass('class_localization');
		
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
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_calendar' ), 'calendar' );

		//-----------------------------------------
		// Load and recache info
		//-----------------------------------------
		
		$feed = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'cal_import_feeds', 'where' => "feed_next_run < " . time(), 'order' => 'feed_next_run ASC', 'limit' => array( 0, 1 ) ) );
		
		if( !$feed['feed_id'] )
		{
			$this->class->unlockTask( $this->task );
			return;
		}
		
		//-----------------------------------------
		// Fetch the feed
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classFileManagement.php', 'classFileManagement' );
		$fetcher	 = new $classToLoad();
		
		$content	 = $fetcher->getFileContents( str_replace( 'webcal://', 'http://', $feed['feed_url'] ) );

		if( !$content )
		{
			$this->class->unlockTask( $this->task );
			return;
		}
		
		//-----------------------------------------
		// Send content to be processed
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('calendar') . '/sources/icalendar.php', 'app_calendar_classes_icalendarOutput', 'calendar' );
		$ical		 = new $classToLoad( $this->registry, $feed['feed_calendar_id'] );
		
		$result	= $ical->import( $content, $feed['feed_member_id'], $feed['feed_id'] );

		//-----------------------------------------
		// Show error if any
		//-----------------------------------------
		
		if( $error = $ical->getError() )
		{
			$this->class->appendTaskLog( $this->task, $error );
			$this->class->unlockTask( $this->task );
			return;
		}
		
		//-----------------------------------------
		// Update last update date and next run date
		//-----------------------------------------
		
		$this->DB->update( 'cal_import_feeds', array( 'feed_lastupdated' => time(), 'feed_next_run' => time() + ( $feed['feed_recache_freq'] * 60 ) ), 'feed_id=' . $feed['feed_id'] );
		
		//-----------------------------------------
		// Log to log table - modify but dont delete
		//-----------------------------------------
		
		$this->class->appendTaskLog( $this->task, sprintf( $this->lang->words['feed_successwebcal'], $result['skipped'], $result['imported'] ) );
		
		//-----------------------------------------
		// Unlock Task: DO NOT MODIFY!
		//-----------------------------------------
		
		$this->class->unlockTask( $this->task );
	}
}