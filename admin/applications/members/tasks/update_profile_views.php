<?php
/**
 * @file		update_profile_views.php 	Task to update the profile views from the temporary table
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
 * @brief		Task to update the profile views from the temporary table
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
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_global' ), 'core' );
		
		$this->DB->build( array( 'select' => 'views_member_id, COUNT(*) as profile_views',
								 'from'   => 'profile_portal_views',
								 'group'  => 'views_member_id'
						)		);

		$o = $this->DB->execute();
		
		while( $r = $this->DB->fetch( $o ) )
		{
			//-----------------------------------------
			// Update...
			//-----------------------------------------
			
			$this->DB->update( 'members', 'members_profile_views=members_profile_views+' . intval( $r['profile_views'] ), "member_id=" . intval($r['views_member_id']), false, true ); 
		}

		//-----------------------------------------
		// Delete from table
		//-----------------------------------------
		
		$this->DB->delete( 'profile_portal_views' );
		
		//-----------------------------------------
		// Log to log table - modify but dont delete
		//-----------------------------------------
		
		$this->class->appendTaskLog( $this->task, $this->lang->words['task_profileviews'] );
		
		//-----------------------------------------
		// Unlock Task: DO NOT MODIFY!
		//-----------------------------------------
		
		$this->class->unlockTask( $this->task );
	}
}