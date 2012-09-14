<?php
/**
 * @file		weeklydigest.php 	Task to send out the weekly digest emails
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		-
 * $LastChangedDate: 2012-03-19 16:54:21 -0400 (Mon, 19 Mar 2012) $
 * @version		v3.3.3
 * $Revision: 10447 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		task_item
 * @brief		Task to send out the weekly digest emails
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
		$this->registry	= $registry;
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
		// Send daily digests on a Sunday...
		//-----------------------------------------
		
		if ( strtolower( date('D') ) == 'sun')
		{
			$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_global' ), 'core' );
			
			require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
			
			$_like = classes_like::bootstrap( 'forums','topics' );
			$_like->sendDigestNotifications( 'weekly', 500 );	
			
			$_like = classes_like::bootstrap( 'forums','forums' );
			$_like->sendDigestNotifications( 'weekly', 500 );	
			
			//-----------------------------------------
			// Log to log table - modify but dont delete
			//-----------------------------------------
			
			$this->class->appendTaskLog( $this->task, $this->lang->words['task_weeklydigest'] );
		}
		
		//-----------------------------------------
		// Unlock Task: DO NOT MODIFY!
		//-----------------------------------------
		
		$this->class->unlockTask( $this->task );
	}
}