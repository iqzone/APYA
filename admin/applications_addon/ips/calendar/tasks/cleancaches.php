<?php
/**
 * @file		cleancaches.php 	Remove minical caches
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
 * @brief		Task to clear out minical caches to prevent massive growth
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
	protected $cache;
	
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
		$this->cache	= $registry->cache();
		
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
		// First, we grab the minical cache keys.
		// We have to do this so we can call the proper
		// cache delete methods to ensure we clean up APC, etc.
		//-----------------------------------------

		$_oneWeek	= time() - ( 60 * 60 * 24 * 7 );
		$caches		= array();
		
		$this->DB->build( array( 'select' => 'cs_key', 'from' => 'cache_store', 'where' => "cs_updated < " . $_oneWeek . " AND cs_key LIKE 'minical_%'", 'limit' => array( 500 ) ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$caches[]	 = $r['cs_key'];
		}
		
		//-----------------------------------------
		// Delete the caches
		//-----------------------------------------
		
		if( count($caches) )
		{
			$this->cache->deleteCache( $caches );
		}

		//-----------------------------------------
		// Log to log table - modify but dont delete
		//-----------------------------------------
		
		$this->class->appendTaskLog( $this->task, sprintf( $this->lang->words['cal_clearedminicals'], count($caches) ) );
		
		//-----------------------------------------
		// Unlock Task: DO NOT MODIFY!
		//-----------------------------------------
		
		$this->class->unlockTask( $this->task );
	}
}