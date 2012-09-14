<?php
/**
 * @file		permadelete.php 	Deletes posts and topics marked for deletion after specified number of days
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: mmecham $
 * @since		-
 * $LastChangedDate: 2012-01-24 07:52:19 -0500 (Tue, 24 Jan 2012) $
 * @version		v3.3.3
 * $Revision: 10172 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		task_item
 * @brief		Task to send out the daily digest emails
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
		$this->cache	= $this->registry->cache();
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
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_mod' ), 'forums' );
		
		//-----------------------------------------
		// Figure out how long
		//-----------------------------------------
		
		$_days	= $this->settings['days_to_keep_deletions'] > 0 ? intval($this->settings['days_to_keep_deletions']) : 365;
		$_ts	= time() - ( $_days * 86400 );
		$posts	= 0;		// Number of removed posts
		$topics	= 0;		// Number of removed topics
		$forums	= array();
		
		//-----------------------------------------
		// Moderator library
		//-----------------------------------------
		
		$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/moderate.php', 'moderatorLibrary', 'forums' );
		$moderatorLibrary	= new $classToLoad( $this->registry );
		
		//-----------------------------------------
		// Get topics 
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'topics',
								 'where'  => $this->registry->getClass('class_forums')->fetchTopicHiddenQuery( array( 'pdelete', 'oktoremove' ) ) . ' AND tdelete_time < ' . $_ts,
								 'limit'  => array( 0, 500 ) ) );
								 
		$outer	= $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			$topics++;
			$forums[ $r['forum_id'] ]	= $r['forum_id'];

			$moderatorLibrary->init( $this->registry->getClass('class_forums')->allForums[ $r['forum_id'] ], $r );
			$moderatorLibrary->topicDeleteFromDB( $r['tid'], true );
			$moderatorLibrary->clearModQueueTable( 'topic', $r['tid'] );
			$moderatorLibrary->addModerateLog( $r['forum_id'], $r['tid'], '', $r['title'], $this->lang->words['acp_deleted_a_topic'] );
		}
		
		//-----------------------------------------
		// Get posts
		//-----------------------------------------
		
		$_ids	= array();
		
		$this->DB->build( array(
								'select'	=> 'p.*', 
								'from'		=> array( 'posts' => 'p' ),
								'where'		=> $this->registry->getClass('class_forums')->fetchPostHiddenQuery( array( 'pdelete', 'oktoremove' ), 'p.' ) . ' AND pdelete_time < ' . $_ts,
								'limit'     => array( 0, 500 ),
								'add_join'	=> array( array( 'select'	=> 't.*',
															 'from'		=> array( 'topics' => 't' ),
															 'where'	=> 't.tid=p.topic_id',
															 'type'		=> 'left' ) ) ) );
		$outer	= $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			$posts++;
			$forums[ $r['forum_id'] ]	= $r['forum_id'];
			
			$moderatorLibrary->init( $this->registry->getClass('class_forums')->allForums[ $r['forum_id'] ], $r );
			$moderatorLibrary->postDeleteFromDb( $r['pid'], true );
			$moderatorLibrary->clearModQueueTable( 'post', $r['pid'] );
			
			$_ids[]	= $r['pid'];
		}
		
		//-----------------------------------------
		// Rebuild forums and stats
		//-----------------------------------------
		
		if( count($forums) )
		{
			foreach( $forums as $forum )
			{
				$moderatorLibrary->forumRecount( $forum );
			}
			
			$this->cache->rebuildCache( 'stats', 'global' );
		}
		
		$moderatorLibrary->addModerateLog( "", "", "", implode( ',', $_ids ), sprintf( $this->lang->words['multi_post_delete'], implode( ', ', $_ids ) ) );
		
		//-----------------------------------------
		// Log to log table - modify but dont delete
		//-----------------------------------------
		
		$this->class->appendTaskLog( $this->task, sprintf( $this->lang->words['task_permadelete'], $posts, $topics ) );
		
		//-----------------------------------------
		// Unlock Task: DO NOT MODIFY!
		//-----------------------------------------
		
		$this->class->unlockTask( $this->task );
	}
}