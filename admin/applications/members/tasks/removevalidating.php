<?php
/**
 * @file		removevalidating.php 	Task to remove validating members over configured time
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
 * @brief		Task to remove validating members over configured time
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
		// If enabled, remove validating new_reg members & entries from members table
		if ( intval($this->settings['validate_day_prune']) > 0 )
		{
			/* Init vars */
			$mids	= array();
			$vids	= array();
			$emails	= array();
			$less_than = time() - $this->settings['validate_day_prune'] * 86400;
			
			/* Get members from DB */
			$this->DB->build( array( 'select'	=> 'v.vid, v.member_id',
									 'from'		=> array( 'validating' => 'v' ),
									 'where'	=> 'v.new_reg=1 AND v.coppa_user<>1 AND v.entry_date < '.$less_than.' AND v.lost_pass<>1 AND v.user_verified=0',
									 'add_join' => array( array( 'select' 	=> 'm.posts, m.member_group_id, m.email',
									 							 'from'		=> array( 'members' => 'm' ),
									 							 'where'	=> 'm.member_id=v.member_id',
									 							 'type'		=> 'left' ) )
							)		);
			$outer = $this->DB->execute();
		
			while( $i = $this->DB->fetch($outer) )
			{
				if( $i['member_group_id'] != $this->settings['auth_group'] )
				{
					// No longer validating?
					
					$this->DB->delete( 'validating', "vid='{$i['vid']}'" );
					continue;
				}
				
				if ( intval($i['posts']) < 1 )
				{
					$mids[] 					= $i['member_id'];
				}
			}
			
			// Remove non-posted validating members
			
			if ( count($mids) > 0 )
			{
				IPSMember::remove( $mids );
			}		
		
			//-----------------------------------------
			// Log to log table - modify but dont delete
			//-----------------------------------------
			
			$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_global' ), 'core' );
			$this->class->appendTaskLog( $this->task, sprintf( $this->lang->words['task_removevalidating'], count($mids) ) );
		}
		
		//-----------------------------------------
		// Unlock Task: DO NOT MODIFY!
		//-----------------------------------------
		
		$this->class->unlockTask( $this->task );
	}
}