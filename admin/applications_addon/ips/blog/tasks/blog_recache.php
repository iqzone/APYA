<?php
/**
 * @file		blog_recache.php 	Task to recache member blogs cached values
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		-
 * $LastChangedDate: 2012-03-23 15:07:02 -0400 (Fri, 23 Mar 2012) $
 * @version		v2.5.2
 * $Revision: 10481 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		task_item
 * @brief		Task to recache member blogs cached values
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
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_blog' ), 'blog' );
		
		/* Load any members pending a cache rebuild */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'members',
								 'where'  => 'blogs_recache=1',
								 'limit'  => array( 0, 25 )
						 )		);
		$outer = $this->DB->execute();
		
		/* Had any results? */
		$found   = intval( $this->DB->getTotalRows($outer) );
		$disable = false;
		
		if ( $found )
		{
			if ( ! $this->registry->isClassLoaded('blogFunctions') )
			{
				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'blog' ) . '/sources/classes/blogFunctions.php', 'blogFunctions', 'blog' );
				$this->registry->setClass( 'blogFunctions', new $classToLoad( $this->registry ) );
			}
			
			while( $row = $this->DB->fetch($outer) )
			{
				$this->registry->getClass('blogFunctions')->rebuildMyBlogsCache( $row );
			}
			
			/* Less than 25 results? There's no more to rebuild then */
			if ( $found < 25 )
			{
				$disable = true;
			}
			
			$this->class->appendTaskLog( $this->task, sprintf( $this->lang->words['task_members_caches_x'], $found ) );
		}
		/* No more cached to rebuild, disable the task then */
		else
		{
			$disable = true;
			
			$this->class->appendTaskLog( $this->task, $this->lang->words['task_members_caches_none'] );
		}
		
		/* Need to disable it? */
		if ( $disable )
		{
			$this->DB->update( 'task_manager', array( 'task_enabled' => 0 ), "task_application='blog' AND task_key='blog_recache'" );
		}
		
		//-----------------------------------------
		// Unlock Task: DO NOT MODIFY!
		//-----------------------------------------

		$this->class->unlockTask( $this->task );
	}
}