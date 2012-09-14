<?php
/**
 * @file		warnings.php 	Task to optimize database tables daily
 * $Copyright: © 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: mark $
 * @since		-
 * $LastChangedDate: 2011-11-18 06:19:03 -0500 (Fri, 18 Nov 2011) $
 * @version		v3.3.3
 * $Revision: 9838 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		task_item
 * @brief		Task to optimize database tables daily
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
		//-----------------------------------------
		// Do it
		//-----------------------------------------
		
		$this->DB->build( array(
			'select'	=> '*',
			'from'		=> 'members_warn_logs',
			'where'		=> 'wl_expire>0 AND wl_expire_date>0 AND wl_expire_date<' . time(),
			'order'		=> 'wl_date ASC',
			'limit'		=> array( 0, 25 )
			) );
		$e = $this->DB->execute();
		while ( $r = $this->DB->fetch( $e ) )
		{
			$this->DB->update( 'members', "warn_level=warn_level-{$r['wl_points']}", "member_id={$r['wl_member']}", FALSE, TRUE );
			$this->DB->update( 'members_warn_logs', array( 'wl_expire_date' => 0 ), "wl_id={$r['wl_id']}" );
		}
		
		//-----------------------------------------
		// Finish
		//-----------------------------------------
		
		/* Log to log table - modify but dont delete */
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_global' ), 'core' );
		$this->class->appendTaskLog( $this->task, $this->lang->words['task__warnings'] );
		
		/* Unlock Task: DO NOT MODIFY! */
		$this->class->unlockTask( $this->task );
	}
}