<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Archive: Reader
 * By Matt Mecham
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2010 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		18th November 2011
 * @version		$Revision: 8644 $
 */

/**
 * Restores topics that are either manually flagged or via settings
 * from the Admin CP.
 * 
 * Manually flagged topics are always restored first.
 * @author matt
 *
 */
class classes_archive_restore
{
	/**#@+
	 * Registry objects
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	/**#@-*/
	
	/**
	 * Set current app
	 */
	private $_app = '';
	
	/**
	 * Constructor
	 *
	 */
	public function __construct()
	{
		/* Make registry objects */
		$this->registry		=  ipsRegistry::instance();
		$this->DB			=  $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->lang			=  $this->registry->getClass('class_localization');
		$this->member		=  $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		=  $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		
		/* Check for class_forums */
		if ( ipsRegistry::isClassLoaded('class_forums') !== TRUE )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php", 'class_forums', 'forums' );
			$this->registry->setClass( 'class_forums', new $classToLoad( $this->registry ) );
			$this->registry->class_forums->forumsInit();
		}
		
		/* Language class */
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_global' ), 'core' );
		
		/* Fetch engine class */
		$this->settings['archive_engine'] = ( $this->settings['archive_engine'] ) ? $this->settings['archive_engine'] : 'sql';
		
		/* Load up archive class */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/archive/restore/' . $this->settings['archive_engine'] . '.php', 'classes_archive_restore_' . $this->settings['archive_engine'] );
		$this->engine = new $classToLoad();
	}

	/**
	 * Set the current application
	 * @param string $app
	 */
	public function setApp( $app )
	{
		$this->_app = $app;
	}
	
	/**
	 * Get the application
	 * @return string
	 */
	public function getApp()
	{
		return $this->_app;
	}
	
	/**
	 * Restore posts to the topic
	 * @param	array	INTS
	 */
	public function restore( $pids=array() )
	{
		if ( ! count( $pids ) )
		{
			return null;
		}
		
		$topics          = array();
		$forumIds        = array();
		$completedTopics = array();
		$workingTopics   = array();
		$maxes			 = array();
		
		/* Load the attachments stuff */
		$classToLoad  = IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
		$class_attach = new $classToLoad( $this->registry );
		$class_attach->type = 'post';
		$class_attach->init();
		
		/* Load the attachments stuff */
		$classToLoad  = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/topics.php', 'app_forums_classes_topics' );
		$topicClass   = new $classToLoad( $this->registry );
	
		/* Fetch data based on PIDs */
		$posts = $topicClass->getPosts( array( 'postId'          => $pids,
											   'sortField'		 => 'pid',
											   'sortOrder'		 => 'asc',
											   'isArchivedTopic' => true ) );
		
		foreach( $posts as $pid => $post )
		{
			/* Write it */
			$this->engine->set( $post );
			
			/* Collect some data */
			if ( ! in_array( $post['tid'], $topics ) )
			{
				$post['_pids']          = 1;
				$post['_lowPid']		= $post['archive_id'];
				$post['_maxPid']		= $post['archive_id'];
				$post['_topicCount']    = ( intval( $post['posts'] ) + intval( $post['topic_deleted_posts'] ) + intval( $post['topic_queuedposts'] ) + 1 );
				$topics[ $post['tid'] ] = $post;
			}
			else
			{
				$topics[ $post['tid'] ]['_maxPid'] = $post['archive_id'];
				$topics[ $post['tid'] ]['_pids']++;
			}
				
			/* Collect fids */
			$forums[ $post['forum_id'] ] = $post['forum_id'];
		}
		
		/* Get the maxes */
		$this->DB->build( array( 'select' => 'topic_id, MAX(pid) as i',
								 'from'   => 'posts',
								 'where'  => 'topic_id IN (' . implode( ',', array_keys( $topics ) ) . ')',
								 'group'  => 'topic_id' ) );
		
		$o = $this->DB->execute();
		
		while( $t = $this->DB->fetch( $o ) )
		{
			$maxes[ $t['topic_id'] ] = intval( $t['i'] );
		}
		
		/* Figure out how many topics have been completed */
		foreach( $topics as $tid => $data )
		{
			/* First pid matches topic first pid, so we have the first post in the topic
			 * but have we got all the posts in this topic?
			 */
			if ( $maxes[ $tid ] == $data['_maxPid'] )
			{
				$completedTopics[] = $data['tid'];
			}
			else
			{
				$workingTopics[] = $data['tid'];
			}
		}
		
		/* Update completed */
		if ( count( $completedTopics ) )
		{
			$this->DB->update( 'topics', array( 'topic_archive_status' => $this->registry->class_forums->fetchTopicArchiveFlag( 'exclude' ) ), 'tid IN(' . implode( ',', $completedTopics ) . ')' );
			
			/* Archive attachments */
			$class_attach->bulkUnarchive( $completedTopics, 'attach_parent_id' );
			
			/* Remove posts */
			$this->engine->removePostsByTids( $completedTopics );
		}
		
		/* Flag as archived */
		$this->engine->setAsRestoredByPids( $pids );
		
		/* Do forums */
		foreach( $forums as $id )
		{
			$this->registry->class_forums->forumRebuild( $id );
		}
		
		/* Log it */
		$this->log( $pids, true );
	}
	
	/**
	 * Process a batch. Genius.
	 * $options is an array of keys and values:
	 * process - INT - number of items to process in this batch
	 * @return int count of topics archived
	 */
	public function processBatch( $options=array() )
	{
		$restoreData     = $this->getRestoreData();
		$topics          = array();
		$forums          = array();
		$pids            = array();
		
		/* Fix up options */
		$options['process'] = ( is_numeric( $options['process'] ) && $options['process'] > 0 ) ? $options['process'] : 250;
		
		if ( ! $restoreData['restore_min_tid'] && ! $restoreData['restore_max_tid'] && ! count( $restoreData['restore_manual_tids'] ) )
		{
			return false;
		}
		
		/* Any manually flagged? */
		if ( count( $restoreData['restore_manual_tids'] ) )
		{ 
			/* Select remaining Pids */
			$pids = $this->engine->getPidsInTids( IPSLib::cleanIntArray( array_keys( $restoreData['restore_manual_tids'] ) ), $options['process'] );
			
			/* Did we complete topics? */
			foreach( $restoreData['restore_manual_tids'] as $tid => $maxPid )
			{
				if ( in_array( $maxPid, array_keys( $pids ) ) )
				{
					$this->deleteManualId( $tid );
				}
			}
			
			/* All done? */
			if ( ! count( $pids ) )
			{
				$restoreData['restore_manual_tids'] = array();
				
				$this->setRestoreData( $restoreData );
			}
			
			/* Update process for below */
			$options['process'] -= count( $pids );
		}
		
		/* Now fetch the max/min if archiver is on */
		if ( $this->settings['archive_on'] && $options['process'] > 0 && $restoreData['restore_min_tid'] && $restoreData['restore_max_tid'] )
		{
			$date  = IPS_UNIX_TIME_NOW - ( 86400 * intval( $this->settings['archive_restore_days'] ) );
			
			$pids = $this->engine->getPidsBetweenTidsAndDate( $restoreData['restore_min_tid'], $restoreData['restore_max_tid'], $date, $options['process'] );
			
			/* All done? */
			if ( ! count( $pids ) )
			{
				$restoreData['restore_min_tid'] = 0;
				$restoreData['restore_max_tid'] = 0;
				
				$this->setRestoreData( $restoreData );
			}
		}
		
		/* Process */
		$this->restore( $pids );
		
		return count( $pids );
	}
		
	/**
	 * Flag an item for manual unarchive
	 * @param int $id
	 */
	public function setManualUnarchive( $id )
	{
		/* Init */
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/topics.php", 'app_forums_classes_topics', 'forums' );
		$topicClass = new $classToLoad( $this->registry );
		
		$topic = $topicClass->getTopicById( $id );
		
		if ( $topic['tid'] && $this->registry->class_forums->fetchArchiveTopicType( $topic ) == 'archived' )
		{
			$topicClass->updateTopic( $topic['tid'], array( 'topic_archive_status' => $this->registry->class_forums->fetchTopicArchiveFlag('restore') ) );
			
			$this->insertManualId( $id );
		}
	}
	
	/**
	 * Add an ID to the manual restore filter
	 */
	public function insertManualId( $id )
	{
		$data = $this->getRestoreData();
		$max  = $this->engine->getMaxPidInTid( $id );
		
		$data['restore_manual_tids'][ $id ] = $max;
		
		$this->setRestoreData( $data );
	}
	
	/**
	 * Delete an ID from the manual restore filter
	 */
	public function deleteManualId( $id )
	{
		$data = $this->getRestoreData();
		
		if ( in_array( $id, array_keys( $data['restore_manual_tids'] ) ) )
		{
			unset( $data['restore_manual_tids'][ $id ] );
		
			$this->setRestoreData( $data );
		}
	}
	
	
	/**
	 * Get restore data
	 */
	public function getRestoreData()
	{
		$data = $this->DB->buildAndFetch( array( 'select' => '*',
												 'from'   => 'core_archive_restore' ) );
		
		if ( IPSLib::isSerialized( $data['restore_manual_tids'] ) )
		{
			$data['restore_manual_tids'] = unserialize( $data['restore_manual_tids'] );
		}
		else
		{
			$data['restore_manual_tids'] = array();
		}
		
		return $data;
	}
	
	/**
	 * Set restore data
	 * @param	array
	 */
	public function setRestoreData( $data )
	{
		if ( is_array( $data['restore_manual_tids'] ) )
		{
			$data['restore_manual_tids'] = serialize( $data['restore_manual_tids'] );
		}
		
		$data['restore_min_tid'] = intval( $data['restore_min_tid'] );
		$data['restore_max_tid'] = intval( $data['restore_max_tid'] );
		
		$this->DB->delete( 'core_archive_restore');
		$this->DB->insert( 'core_archive_restore', $data );
	}
	
	/**
	 * Take an archive row and returned native friendly array
	 * @param array $post
	 * @return array
	 */
	public function archiveToNativeFields( $post )
	{
		$native  = array( 'pid'           	 => intval( $post['archive_id'] ),
						  'author_id'    	 => intval( $post['archive_author_id'] ),
						  'author_name'  	 => $post['archive_author_name'],
					      'ip_address'   	 => $post['archive_ip_address'],
						  'post_date' 	     => intval( $post['archive_content_date'] ),
						  'post'		 	 => $post['archive_content'],
						  'queued'	 	     => $post['archive_queued'],
						  'topic_id'     	 => intval( $post['archive_topic_id'] ),
						  'new_topic'     	 => intval( $post['archive_is_first'] ),
						  'post_bwoptions'   => $post['archive_bwoptions'],
						  'post_key'   	 	 => $post['archive_attach_key'],
						  'post_htmlstate'   => $post['archive_html_mode'],
						  'use_sig'   		 => $post['archive_show_signature'],
						  'use_emo'   		 => $post['archive_show_emoticons'],
						  'append_edit'   	 => $post['archive_show_edited_by'],
						  'edit_time'   	 => $post['archive_edit_time'],
						  'edit_name'   	 => $post['archive_edit_name'],
						  'post_edit_reason' => $post['archive_edit_reason'] );
		
		return $native;
	}
	
	/**
	 * Logs archive action
	 * @param array $ids
	 */
	public function log( $ids )
	{
		$this->DB->insert( 'core_archive_log', array( 'archlog_date'       => IPS_UNIX_TIME_NOW,
													  'archlog_app'        => $this->getApp(),
													  'archlog_ids'        => serialize( $ids ),
													  'archlog_is_restore' => 1,
													  'archlog_count'      => count( $ids ) ) );	
	}
	
}
