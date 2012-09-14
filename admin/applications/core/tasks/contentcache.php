<?php
/**
 * @file		contentcache.php 	Task to prune the content cache (posts & signatures)
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		27th January 2004
 * $LastChangedDate: 2011-03-18 21:17:06 -0400 (Fri, 18 Mar 2011) $
 * @version		v3.3.3
 * $Revision: 8131 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		task_item
 * @brief		Task to prune the content cache (posts & signatures)
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
	 * @var		$lang
	 */
	protected $registry;
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
		$this->registry		= $registry;
		$this->lang			= $this->registry->getClass('class_localization');
		
		$this->class	= $class;
		$this->task		= $task;
		
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_global' ), 'core' );
	}
	
	/**
	 * Run this task
	 * 
	 * @return	@e void
	 */
	public function runTask()
	{
		/* Run it */
		$itemsRemoved = IPSContentCache::prune();
		
		$log = ( $itemsRemoved === FALSE ) ? $this->lang->words['errorcleaningcontentcache'] : sprintf( $this->lang->words['contentcache_cleaned'], $itemsRemoved );
		
		$this->class->appendTaskLog( $this->task, $log );
	
		$this->class->unlockTask( $this->task );
	}
}