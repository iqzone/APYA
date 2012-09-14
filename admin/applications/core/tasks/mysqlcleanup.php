<?php
/**
 * @file		mysqlcleanup.php 	Task to prune dead item markers
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		-
 * $LastChangedDate: 2011-02-08 17:20:18 -0500 (Tue, 08 Feb 2011) $
 * @version		v3.3.3
 * $Revision: 7750 $
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
		//-----------------------------------------
		// This is mysql only
		//-----------------------------------------
		
		if ( strtolower( $this->settings['sql_driver'] ) != 'mysql' )
		{
			$this->class->unlockTask( $this->task );
			return;
		}
		
		//-----------------------------------------
		// Clean out sleeping mysql processes
		//-----------------------------------------

		$this->DB->return_die	= true;
		$resource	= $this->DB->query( "SHOW PROCESSLIST", true );
		
		while( $r = $this->DB->fetch($resource) )
		{
			//-----------------------------------------
			// Make sure we're only killing stuff on our db
			//-----------------------------------------
			
			if( $r['db'] == $this->settings['sql_database'] AND $r['Command'] == 'Sleep' AND $r['Time'] > 60 )
			{
				$this->DB->return_die = true;
				$this->DB->query( "KILL {$r['Id']}" );
				
				/* Log */
				IPSDebug::addLogMessage( 'Task - MySQL Clean Up Performed. Killed id ' . $r['Id'], 'mysqlCleanUp', $r );
			}
		}
		
		$this->DB->return_die = false;

		//-----------------------------------------
		// Log to log table - modify but dont delete
		//-----------------------------------------
		
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_global' ), 'core' );
		
		$this->class->appendTaskLog( $this->task, $this->lang->words['task_mysqlcleanup'] );
		
		//-----------------------------------------
		// Unlock Task: DO NOT MODIFY!
		//-----------------------------------------
		
		$this->class->unlockTask( $this->task );
	}
}