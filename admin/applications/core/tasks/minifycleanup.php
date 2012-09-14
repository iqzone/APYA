<?php
/**
 * @file		minifycleanup.php 	Task to cleanup old minify files
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		-
 * $LastChangedDate: 2012-04-02 16:01:35 -0400 (Mon, 02 Apr 2012) $
 * @version		v3.3.3
 * $Revision: 10543 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * @class		task_item
 * @brief		Task to cleanup old minify files
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
		$this->settings	= $registry->fetchSettings();
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
		// This is only relevant if we use minify
		//-----------------------------------------

		if( !$this->settings['use_minify'] )
		{
			$this->class->unlockTask( $this->task );
			return;
		}

		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_global' ), 'core' );
		
		//-----------------------------------------
		// Clean out the files minify creates once a week,
		// just to ensure old unused files don't eat up disk space
		//-----------------------------------------
		
		try
		{
			if( is_dir( DOC_IPS_ROOT_PATH . 'cache/tmp' ) )
			{
				foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . 'cache/tmp' ) as $cache )
				{
					if( $cache->getMTime() < ( time() - ( 60 * 60 * 24 * 7 ) ) AND $cache->getFilename() != 'index.html' )
					{
						@unlink( $cache->getPathname() );
					}
				}
			}
		} catch ( Exception $e ) {}

		//-----------------------------------------
		// Log to log table - modify but dont delete
		//-----------------------------------------
		
		$this->class->appendTaskLog( $this->task, $this->lang->words['task_minifycleanup'] );
		
		//-----------------------------------------
		// Unlock Task: DO NOT MODIFY!
		//-----------------------------------------
		
		$this->class->unlockTask( $this->task );
	}
}