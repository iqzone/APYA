<?php
/**
 * @file		blog_rssimport.php 	Task to import rss feeds as blog entries
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		-
 * $LastChangedDate: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * @version		v2.5.2
 * $Revision: 4 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		task_item
 * @brief		Task to import rss feeds as blog entries
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
		/* Enabled? */
		if ( $this->settings['blog_allow_rssimport'] )
		{
			$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_blog' ), 'blog' );
			
			/* Fetch Class */
			$classToLoad   = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/classes/post/blogRssImport.php', 'blogRssImport', 'blog' );
			$blogRssImport = new $classToLoad( $this->registry );
			
			/* Process */
			$processed = 0;
			$processed = $blogRssImport->runTask();
			
			if ( $processed == 0 )
			{
				/* All done. Turn me off (baby) */
				$this->DB->update( 'task_manager', array( 'task_enabled' => 0 ), "task_application='blog' AND task_key='blog_rssimport'" );
				
				/* Log to log table - modify but dont delete */
				$this->class->appendTaskLog( $this->task, $this->lang->words['task_blogrssimport_disable'] );
			}
			else
			{
				/* Log to log table - modify but dont delete */
				$this->class->appendTaskLog( $this->task, sprintf( $this->lang->words['task_blogrssimport'], $processed ) );
			}
		}

		//-----------------------------------------
		// Unlock Task: DO NOT MODIFY!
		//-----------------------------------------

		$this->class->unlockTask( $this->task );
	}
}