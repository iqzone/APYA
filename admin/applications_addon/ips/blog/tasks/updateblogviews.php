<?php
/**
 * @file		updateblogviews.php 	Task to update the blog views from the temporary table
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
 * @brief		Task to update the blog views from the temporary table
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
		if ( ! $this->settings['blog_update_views_immediately'] )
		{
			//-----------------------------------------
			// Attempt to prevent timeout...
			//-----------------------------------------
			
			$timeStart	= time();
			$ids		= array();
			$complete	= true;
			
			//-----------------------------------------
			// Load DB file
			//-----------------------------------------
			
			$this->DB->loadCacheFile( IPS_ROOT_PATH . 'applications_addon/ips/blog/sql/' . ips_DBRegistry::getDriverType() . '_blog_queries.php', 'sql_blog_queries' );
			
			//-----------------------------------------
			// Get SQL query
			//-----------------------------------------
			
			$this->DB->buildFromCache( 'updateblogviews_get', array(), 'sql_blog_queries' );
			$o = $this->DB->execute();
			
			while( $r = $this->DB->fetch( $o ) )
			{
            	$this->DB->update( 'blog_blogs', 'blog_num_views=blog_num_views+'.intval( $r['blogviews'] ), 'blog_id=' . intval( $r['blog_id'] ), false, true );
            	$this->DB->update( 'blog_entries', 'entry_views=entry_views+'.intval( $r['blogviews'] ), 'entry_id=' . intval( $r['entry_id'] ), false, true );

				$ids[ $r['blog_id'] ]	= $r['blog_id'];
				
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
					$this->DB->delete( 'blog_views', 'blog_id IN(' . implode( ',', $ids ) . ')' );
				}
			}
			else
			{
				$this->DB->delete( 'blog_views' );
			}
			
			//-----------------------------------------
			// Log to log table - modify but dont delete
			//-----------------------------------------
			
			$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_blog' ), 'blog' );
			$this->class->appendTaskLog( $this->task, $this->lang->words['task_updateblogviews'] );
		}
		
		//-----------------------------------------
		// Unlock Task: DO NOT MODIFY!
		//-----------------------------------------
		
		$this->class->unlockTask( $this->task );
	}
}