<?php
/**
 * @file		logprune.php 	Task to prune old logs from the cache folder
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: mmecham $
 * @since		-
 * $LastChangedDate: 2011-04-19 11:20:55 -0400 (Tue, 19 Apr 2011) $
 * @version		v3.3.3
 * $Revision: 8383 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		task_item
 * @brief		Task to prune old logs from the cache folder
 *
 */
class task_item
{
	/**
	 * Integer value (seconds) used to delete old records
	 * By default 1 month: 2592000 (60*60*24*30)
	 * 
	 * @var		$deleteTime
	 */
	protected $deleteTime = 2592000;
	
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
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_global' ), 'core' );
		
		//-----------------------------------------
		// Spider Logs
		//-----------------------------------------
		
		if( $this->settings['ipb_prune_spider'] )
		{
			$this->DB->delete( "spider_logs", "entry_date < " . (time() - $this->deleteTime) );
		}
		
		//-----------------------------------------
		// Spam Logs
		//-----------------------------------------
		
		if( $this->settings['ipb_prune_spam'] )
		{
			$this->DB->delete( "spam_service_log", "log_date < " . (time() - $this->deleteTime) );
		}
		
		//-----------------------------------------
		// Admin Login Logs
		//-----------------------------------------
		
		if( $this->settings['prune_admin_login_logs'] )
		{
			$this->DB->delete( "admin_login_logs", "admin_time < " . (time() - $this->deleteTime) );
		}
		
		//-----------------------------------------
		// Task Logs
		//-----------------------------------------
		
		if( $this->settings['ipb_prune_task'] )
		{
			$this->DB->delete( "task_logs", "log_date < " . (time() - $this->deleteTime) );
		}
		
		//-----------------------------------------
		// Admin Logs
		//-----------------------------------------
		
		if( $this->settings['ipb_prune_admin'] )
		{
			$this->DB->delete( "admin_logs", "ctime < " . (time() - $this->deleteTime) );
		}
		
		//-----------------------------------------
		// Mod Logs
		//-----------------------------------------
		
		if ( $this->settings['ipb_prune_mod'] )
		{
			$this->DB->delete( "moderator_logs", "ctime < " . (time() - $this->deleteTime) );
		}
		
		//-----------------------------------------
		// Email Error Logs
		//-----------------------------------------
		
		if ( $this->settings['ipb_prune_emailerror'] )
		{
			$this->DB->delete( "mail_error_logs", "mlog_date < " . (time() - $this->deleteTime) );
		}
		
		//-----------------------------------------
		// Error Logs
		//-----------------------------------------
		
		if ( $this->settings['prune_error_logs'] )
		{
			$this->DB->delete( "error_logs", "log_date < " . (time() - $this->deleteTime) );
		}
		
		//-----------------------------------------
		// SQL Logs
		//-----------------------------------------
		
		if ( $this->settings['ipb_prune_sql'] )
		{
			try
			{
				foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . 'cache' ) as $file )
				{
					if( $file->isDot() OR !$file->isFile() )
					{
						continue;
					}
            	
					if( preg_match( "#^sql_(error|debug|upgrade)_log_(\d+)_(\d+)_(\d+).cgi$#", $file->getFilename(), $matches ) )
					{
						if( $file->getMTime() < (time() - $this->deleteTime) )
						{
							@unlink( $file->getPathname() );
						}
					}
				}
			} catch ( Exception $e ) {}
		}	
		
		//-----------------------------------------
		// Log to log table - modify but dont delete
		//-----------------------------------------
		
		$this->class->appendTaskLog( $this->task, $this->lang->words['task_logprune'] );
		
		//-----------------------------------------
		// Unlock Task: DO NOT MODIFY!
		//-----------------------------------------
		
		$this->class->unlockTask( $this->task );
	}
}