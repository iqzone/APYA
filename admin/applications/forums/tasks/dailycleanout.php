<?php
/**
 * @file		dailycleanout.php 	Task to prune daily old topic subscriptions
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: mark $
 * @since		-
 * $LastChangedDate: 2012-01-13 11:28:34 -0500 (Fri, 13 Jan 2012) $
 * @version		v3.3.3
 * $Revision: 10138 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		task_item
 * @brief		Task to prune daily old topic subscriptions
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
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_global' ), 'core' );
		
		//-----------------------------------------
		// Delete old subscriptions
		//-----------------------------------------
		
		$deleted	= 0;
		$trids		= array();
		
		if ( $this->settings['subs_autoprune'] > 0 )
 		{
			$time = time() - ($this->settings['subs_autoprune'] * 86400);

			$this->DB->build( array(
									'select'	=> 'l.like_lookup_id',
									'from'		=> array( 'core_like' => 'l' ),
									'where'		=> 't.last_post < ' . $time,
									'add_join'	=> array( 
														array(	'from'	=> array( 'topics' => 't' ),
																'where'	=> "t.tid=l.like_rel_id AND l.like_app='forums' AND l.like_area='topics'",
																'type'	=> 'left'
															)
														)
								)		);
			$this->DB->execute();
			
			while ( $r = $this->DB->fetch() )
			{
				$trids[] = $r['like_lookup_id'];
			}
			
			if (count($trids) > 0)
			{
				$this->DB->delete( 'core_like', "like_lookup_id IN ('" . implode( "','", $trids ) . "')" );
				$this->DB->delete( 'core_like_cache', "like_cache_id IN ('" . implode( "','", $trids ) . "')" );
			}
			
			$deleted	= intval( count($trids) );
 		}
 		
		//-----------------------------------------
		// Delete old unattached uploads
		//-----------------------------------------
		
		$time_cutoff	= time() - 7200;
		$deadid			= array();
		
		$this->DB->build( array( "select" => '*', 'from' => 'attachments',  'where' => "attach_rel_id=0 AND attach_date < {$time_cutoff}" ) );
		$this->DB->execute();
		
		while( $killmeh = $this->DB->fetch() )
		{
			if ( $killmeh['attach_location'] )
			{
				@unlink( $this->settings['upload_dir'] . "/" . $killmeh['attach_location'] );
			}
			if ( $killmeh['attach_thumb_location'] )
			{
				@unlink( $this->settings['upload_dir'] . "/" . $killmeh['attach_thumb_location'] );
			}
			
			$deadid[] = $killmeh['attach_id'];
		}
		
		$_attach_count	= count( $deadid );
		
		if ( $_attach_count )
		{
			$this->DB->delete( 'attachments', "attach_id IN(" . implode( ",", $deadid ) . ")" );
		}
		
		//-----------------------------------------
		// Delete old topic redirects
		//-----------------------------------------
		
		if ( intval( $this->settings['topic_redirect_prune'] ) > 0 )
		{
			$time = time() - ( $this->settings['topic_redirect_prune'] * 86400 );
			$tids = array();
			$fids = array();
			
			/* Grab topics ensuring we use the index */
			$this->DB->build( array( 'select' => 'tid, moved_to, forum_id',
									 'from'   => 'topics',
									 'where'  => 'pinned=0 AND moved_on < ' . $time . " AND state='link'",
									 'limit'  => array( 0, 150 ) ) );
									 
			$this->DB->execute();
			
			while ( $row = $this->DB->fetch() )
			{
				/* ensure it's a moved topic */
				if ( $row['moved_to'] )
				{
					$tids[] = $row['tid'];
					$fids[ $row['forum_id'] ]++;
				}
			}
			
			if ( count( $tids ) > 0 )
			{
				$this->DB->delete( 'topics', "tid IN (" . implode( ",", $tids ) . ")" );
				
				if ( count( $fids ) )
				{
					foreach( $fids as $f => $count )
					{
						ipsRegistry::getClass('class_forums')->forumRebuild( $f );
					}
				}
			}
											
			$redirectsDeleted = intval( count( $tids ) );
		}
		
		//-----------------------------------------
		// Remove old XML-RPC logs...
		//-----------------------------------------
		
		if ( $this->settings['xmlrpc_log_expire'] > 0 )
		{
			$time = time() - ( $this->settings['xmlrpc_log_expire'] * 86400 );
 			
 			$this->DB->delete( 'api_log', "api_log_date < {$time}" );
 			
 			$xmlrpc_logs_deleted = $this->DB->getAffectedRows();
		}
		
		//-----------------------------------------
		// Log to log table - modify but dont delete
		//-----------------------------------------
		
		$this->class->appendTaskLog( $this->task, sprintf( $this->lang->words['task_dailycleanout'], $xmlrpc_logs_deleted, $_attach_count, $deleted, $redirectsDeleted ) );
		
		//-----------------------------------------
		// Unlock Task: DO NOT MODIFY!
		//-----------------------------------------
		
		$this->class->unlockTask( $this->task );
	}
}