<?php
/**
 * @file		archive.php 	Task to check and write incremental archives - Matt Mecham
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		-
 * $LastChangedDate: 2011-05-13 03:28:10 +0100 (Fri, 13 May 2011) $
 * @version		v3.3.3
 * $Revision: 8754 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		task_item
 * @brief		Task to update the topic views from the temporary table
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
	 * @var		$settings
	 * @var		$lang
	 */
	protected $registry;
	protected $DB;
	protected $settings;
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
		$this->settings	=& $this->registry->fetchSettings();
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
		/* Language class */
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_global' ), 'core' );
		
		/* Load up archive class */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/archive/writer.php', 'classes_archive_writer' );
		$archiveWriter = new $classToLoad();
		
		$archiveWriter->setApp('forums');
		
		/* Do it! DO IT */
		$count = $archiveWriter->processBatch();
		
		$this->class->appendTaskLog( $this->task, sprintf( $this->lang->words['task_archiveprocess'], intval($count) ) );
		
		/* Load up restore class */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/archive/restore.php', 'classes_archive_restore' );
		$archiveRestore = new $classToLoad();
		
		$archiveRestore->setApp('forums');
		
		/* Do it! DO IT */
		$count = $archiveRestore->processBatch();
		
		if ( $count )
		{
			$this->class->appendTaskLog( $this->task, sprintf( $this->lang->words['task_unarchiveprocess'], $count ) );
		}
		
		//-----------------------------------------
		// Unlock Task: DO NOT MODIFY!
		//-----------------------------------------
		
		$this->class->unlockTask( $this->task );
	}
}