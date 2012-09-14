<?php
/**
 * @file		updateviews.php 	Task to update the topic views from the temporary table
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		-
 * $LastChangedDate: 2011-05-12 22:28:10 -0400 (Thu, 12 May 2011) $
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
		/* Update delayed? */
		if ( ! $this->settings['update_topic_views_immediately'] )
		{
			//-----------------------------------------
			// Attempt to prevent timeout...
			//-----------------------------------------
			
			$timeStart	= time();
			$ids		= array();
			$complete	= true;
			
			//-----------------------------------------
			// Get SQL query
			//-----------------------------------------
			
			$this->DB->build( array( 'select'	=> 'views_tid, COUNT(*) as topicviews',
											'from'	=> 'topic_views',
											'group'	=> 'views_tid',
								)		);
			$o = $this->DB->execute();
			
			while( $r = $this->DB->fetch( $o ) )
			{
				//-----------------------------------------
				// Update...
				//-----------------------------------------
				
				$this->DB->update( 'topics', 'views=views+' . intval( $r['topicviews'] ), "tid=" . intval($r['views_tid']), false, true );
				
				$ids[ $r['views_tid'] ]	= $r['views_tid'];
				
				//-----------------------------------------
				// Running longer than 30 seconds?
				//-----------------------------------------
				
				if( time() - $timeStart > 30 )
				{
					$complete	= false;
					break;
				}
			}
			
			//-----------------------------------------
			// Delete from table
			//-----------------------------------------
			
			if( !$complete )
			{
				if( count($ids) )
				{
					$this->DB->delete( 'topic_views', 'views_tid IN(' . implode( ',', $ids ) . ')' );
				}
			}
			else
			{
				$this->DB->delete( 'topic_views' );
			}
			
			//-----------------------------------------
			// Log to log table - modify but dont delete
			//-----------------------------------------
			
			$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_global' ), 'core' );
			$this->class->appendTaskLog( $this->task, $this->lang->words['task_updateviews'] );
		}
		
		//-----------------------------------------
		// Unlock Task: DO NOT MODIFY!
		//-----------------------------------------
		
		$this->class->unlockTask( $this->task );
	}
}