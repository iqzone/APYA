<?php
/**
 * @file		cleanout.php 	Task to clean out 'dead' sessions, validations, registration image entires, etc
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: mark $
 * @since		-
 * $LastChangedDate: 2012-02-22 12:07:55 -0500 (Wed, 22 Feb 2012) $
 * @version		v3.3.3
 * $Revision: 10349 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		task_item
 * @brief		Task to clean out 'dead' sessions, validations, registration image entires, etc
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
		$this->caches	=& ipsRegistry::cache()->fetchCaches();
		$this->cache	= ipsRegistry::cache();
		
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
		
		//-----------------------------------------
		// Delete reg_anti_spam
		//-----------------------------------------

		$this->DB->delete( 'captcha', 'captcha_date < ' . (time() - (60*60*6)) );
		
		//-----------------------------------------
		// Delete sessions
		//-----------------------------------------
		
		$this->DB->delete( 'sessions', 'running_time < ' . ( IPS_UNIX_TIME_NOW - $this->settings['session_expiration'] ) );
		
		//-----------------------------------------
		// Lost password requests
		//-----------------------------------------
		
		$_cutoff	= time() - ( $this->settings['lost_pw_prune'] * 60 * 60 * 24 );
		
		$this->DB->delete( 'validating', 'lost_pass=1 AND entry_date < ' . $_cutoff );
		
		/* Delete old saved content */
		$this->DB->delete( 'core_editor_autosave', 'eas_updated < '  . ( IPS_UNIX_TIME_NOW - 86400 ) );
		
		/* Delete old cached content */
		$this->DB->delete( 'cache_simple', 'cache_time < '  . ( IPS_UNIX_TIME_NOW - 86400 ) );
		
		/* Remove rep caches (3 days old) */
		$this->DB->delete( 'reputation_cache', 'cache_date < '  . ( IPS_UNIX_TIME_NOW - ( 86400 * 3 ) ) );
		
		/* Remove recent topics (3 days worth ) */
		$this->DB->delete( 'forums_recent_posts', 'post_date < '  . ( IPS_UNIX_TIME_NOW - ( 86400 * 3 ) ) );
		
		//-----------------------------------------
		// Delete core_incoming_email_logs
		//-----------------------------------------
		
		if ( $this->DB->checkForTable('core_incoming_email_log') )
		{
			$this->DB->delete( 'core_incoming_email_log', 'log_time < ' . ( IPS_UNIX_TIME_NOW - 60 ) );
		}
		
		/* Delete old inline messages (5 mins) */
		$this->DB->delete( 'core_inline_messages', 'inline_msg_date < ' . ( IPS_UNIX_TIME_NOW - 300 ) );
				
		//-----------------------------------------
		// Log to log table - modify but dont delete
		//-----------------------------------------
		
		$this->class->appendTaskLog( $this->task, $this->lang->words['task_cleanout'] );
		
		//-----------------------------------------
		// Unlock Task: DO NOT MODIFY!
		//-----------------------------------------
		
		$this->class->unlockTask( $this->task );
	}
}