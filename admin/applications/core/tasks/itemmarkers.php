<?php
/**
 * @file		itemmarkers.php 	Task to prune dead item markers
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: mmecham $
 * @since		27th January 2004
 * $LastChangedDate: 2011-12-16 06:13:08 -0500 (Fri, 16 Dec 2011) $
 * @version		v3.3.3
 * $Revision: 10009 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		task_item
 * @brief		Task to prune dead item markers
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
		$this->DB		= $this->registry->DB();
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
		/* INIT */
		if ( ! $this->registry->isClassLoaded( 'classItemMarking' ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/itemmarking/classItemMarking.php', 'classItemMarking' );
			$this->registry->setClass( 'classItemMarking', new $classToLoad( $this->registry ) );
		}
		
		$time         = time() - ( 86400 * ipsRegistry::$settings['topic_marking_keep_days'] );
		$itemsRemoved = 0;
		
		/* Remove 'deleted' items */
		$this->DB->delete( 'core_item_markers', 'item_is_deleted=1' );
		$c = $this->DB->getAffectedRows();
		
		/* Now delete old markers - we use a separate query because there are separate indexes */
		$this->DB->delete( 'core_item_markers', 'item_last_saved < ' . $time );
		$c += $this->DB->getAffectedRows();
		
		IPSDebug::addLogMessage( "$c item markers removed", 'markers_cleanout' );
		
		/* Log task */
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_global' ), 'core' );
		$this->class->appendTaskLog( $this->task, sprintf( $this->lang->words['itemmarkers_task_log'], $itemsRemoved, $c ) );
		
		/* UNLOCK TASK */
		$this->class->unlockTask( $this->task );
	}
}