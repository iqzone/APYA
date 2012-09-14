<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Archive: Writer
 * By Matt Mecham
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2010 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		17th February 2010
 * @version		$Revision: 8644 $
 */

class classes_archive_writer
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
	 * Archive 'do' allowed fields
	 */
	private $_archiveOnFields = array( 'state'		=> 'state',
									   'pinned'		=> 'pinned',
									   'approved'	=> 'approved',
									   'poll'		=> 'poll_state',
									   'post'		=> 'posts',
									   'view'		=> 'views',
									   'rating'		=> 'topic_rating_total',
									   'forum'		=> 'forum_id',
									   'member'		=> 'starter_id',
									   'lastpost' 	=> 'last_post' );
	
	/**
	 * Archive 'skip' allowed fields
	 */
	private $_archiveSkipFields = array( 'post'		=> 'posts',
									     'view'		=> 'views',
									     'rating'	=> 'topic_rating_total',
									     'forum'	=> 'forum_id',
									     'member'	=> 'starter_id',
									     'lastpost' => 'last_post');
	
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
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/archive/writer/' . $this->settings['archive_engine'] . '.php', 'classes_archive_writer_' . $this->settings['archive_engine'] );
		$this->engine = new $classToLoad();
		
		if ( $this->engine->test() !== true )
		{
			$this->error( $this->lang->words['archive_fail_engine'] );
		}
	}

	/**
	 * It's magic!
	 * @param string $method
	 * @param array $arguments
	 */
	public function __call( $method, $arguments )
	{
		switch ( $method )
		{
			case 'testConnection':
				return $this->engine->test();
			break;
			case 'createTable':
				return $this->engine->createTable();
			break;
			case 'flush':
				return $this->engine->flush();
			break;
		}
	}
	
	/**
	 * Updates posts
	 *
	 * @param	$what		Array of fields with vals to update
	 * @param	$where		String of what to update
	 */
	public function update( array $what, $where )
	{
		return $this->engine->update( $what, $where );
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
	 * Return archive on fields
	 * @return array
	 */
	public function getArchiveOnFields()
	{
		return array_keys( $this->_archiveOnFields );
	}
	
	/**
	 * Return archive skip fields
	 * @return array
	 */
	public function getArchiveSkipFields()
	{
		return array_keys( $this->_archiveSkipFields );
	}
	
	/**
	 * Write topics to the archive
	 * @param	array	INTS
	 */
	public function write( $pids=array() )
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
		
		/* Fetch data based on PIDs */
		$this->DB->build( array( 'select' => 'p.*',
								 'from'   => array( 'posts' => 'p' ),
								 'where'  => 'p.pid IN (' . implode( ',', $pids ) . ')',
								 'order'  => 'p.topic_id ASC, p.pid ASC',
								 'add_join' => array( array( 'select' => 't.*',
															 'from'   => array( 'topics' => 't' ),
															 'where'  => 'p.topic_id=t.tid' ) ) ) );
		
		$o = $this->DB->execute();
		
		while( $post = $this->DB->fetch( $o ) )
		{
			/* Write it */
			$this->engine->set( $post );
			
			/* Collect some data */
			if ( ! in_array( $post['tid'], $topics ) )
			{
				$post['_pids']          = 1;
				$post['_lowPid']		= $post['pid'];
				$post['_maxPid']		= $post['pid'];
				$post['_topicCount']    = ( intval( $post['posts'] ) + intval( $post['topic_deleted_posts'] ) + intval( $post['topic_queuedposts'] ) + 1 );
				$topics[ $post['tid'] ] = $post;
			}
			else
			{
				$topics[ $post['tid'] ]['_maxPid'] = $post['pid'];
				$topics[ $post['tid'] ]['_pids']++;
			}
				
			/* Collect fids */
			$forums[ $post['forum_id'] ] = $post['forum_id'];
		}
		
		/* Got any topics? */
		if ( ! count( $topics ) )
		{
			return;
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
		
		/* Update working */
		if ( count( $workingTopics ) )
		{
			$this->DB->update( 'topics', array( 'topic_archive_status' => $this->registry->class_forums->fetchTopicArchiveFlag( 'working' ) ),'tid IN(' . implode( ',', $workingTopics ) . ')' );
		}
		
		/* Update completed */
		if ( count( $completedTopics ) )
		{
			$this->DB->update( 'topics', array( 'topic_archive_status' => $this->registry->class_forums->fetchTopicArchiveFlag( 'archived' ) ), 'tid IN(' . implode( ',', $completedTopics ) . ')' );
			
			/* Archive attachments */
			$class_attach->bulkArchive( $completedTopics, 'attach_parent_id' );
			
			/* Remove posts */
			$this->DB->delete( 'posts', 'topic_id IN(' . implode( ',', $completedTopics ) . ')' );		
		}
		
		/* Do forums */
		foreach( $forums as $id )
		{
			$this->registry->class_forums->forumRebuild( $id );
		}
		
		/* Log it */
		$this->log( $pids );
	}
	
	/**
	 * Process a batch. Genius.
	 * $options is an array of keys and values:
	 * process - INT - number of items to process in this batch
	 * @return int count of topics archived
	 */
	public function processBatch( $options=array() )
	{
		/* Archiver running? */
		if ( ! $this->settings['archive_on'] )
		{
			return false;
		}
		
		/* Init vars */
		$query           = $this->getArchiveWhereQuery();
		$topics          = array();
		$forums          = array();
		$pids            = array();
		
		/* Fix up options */
		$options['process'] = ( is_numeric( $options['process'] ) && $options['process'] > 0 ) ? $options['process'] : 250;
		
		if ( $query === null )
		{
			return null;
		}
		
		/* First see if we have a topic in progress */
		$working = $this->DB->buildAndFetch( array( 'select' => '*',
													'from'   => 'topics',
													'where'  => 'topic_archive_status=' . $this->registry->class_forums->fetchTopicArchiveFlag( 'working' ),
													'order'  => 'tid ASC',
													'limit'  => array( 0, 1 ) ) );
		
		/* Got one? */
		if ( $working['tid'] )
		{
			$doneSoFar = $this->engine->getDoneSoFarByTid( $working['tid'] );
			
			$postTable = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count, MAX(pid) as max',
														  'from'   => 'posts',
														  'where'  => 'pid > ' . intval( $doneSoFar['max'] ) . ' AND topic_id=' . $working['tid'] ) );
			
			
			/* Select remaining Pids */
			$this->DB->build( array( 'select' => 'pid',
									 'from'   => 'posts',
									 'where'  => 'pid > ' . intval( $doneSoFar['max'] ) . ' AND topic_id=' . $working['tid'],
									 'order'  => 'pid ASC',
									 'limit'  => array( 0, $options['process'] ) ) );
			
			$o = $this->DB->execute();
			
			while( $post = $this->DB->fetch( $o ) )
			{
				$pids[ $post['pid'] ] = $post['pid'];
			}
			
			/* Update process for below */
			$options['process'] -= count( $pids );
		}
		
		/* Carry on? */
		if ( $options['process'] > 0 )
		{
			/* Collect topic IDs first */
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'topics',
									 'where'  => $query . ' AND (posts + topic_deleted_posts + topic_queuedposts ) >= 0',
									 /**
									  * We can't check for posts > 0 because if the topic has only the first post it will skip them
									  * 
									  * @link	http://community.invisionpower.com/tracker/issue-37131-archive-posts-missing-old-topics-with-post-count-0/
									  */
									 //'where'  => $query . ' AND (posts + topic_deleted_posts + topic_queuedposts ) > 0',
									 'order'  => 'tid ASC',
									 'limit'  => array( 0, 250 ) ) );
			
			$o = $this->DB->execute();
			
			$counter = 0;
			
			while( $topic = $this->DB->fetch( $o ) )
			{
				$topics[] = $topic['tid'];
				
				$counter += ( $topic['posts'] + $topic['topic_deleted_posts'] + $topic['topic_queuedposts'] ) + 1;
				
				if ( $counter >= $options['process'] )
				{
					break;
				}
			}
			
			/* Got anything? */
			if ( ! count( $topics ) )
			{
				return 0;
			}
			
			/* Now fetch the PIDS */
			$this->DB->build( array( 'select' => 'pid',
									 'from'   => 'posts',
									 'where'  => 'topic_id IN (' . implode( ',', $topics ) . ')',
									 'order'  => 'topic_id ASC, pid ASC',
									 'limit'  => array( 0, $options['process'] ) ) );
			
			$o = $this->DB->execute();
			
			while( $post = $this->DB->fetch( $o ) )
			{
				$pids[ $post['pid'] ] = $post['pid'];
			}
		}
		
		/* Process */
		$this->write( $pids );
		
		return count( $pids );
	}
	
	/**
	 * Give me a key and I'll give you a DB field you lucky beasts
	 * @param string $key
	 */
	public function getDbFieldFromKey( $key )
	{
		return ( isset( $this->_archiveOnFields[ $key ] ) ) ? $this->_archiveOnFields[ $key ] : null;
	}
	
	/**
	 * Get a count of how many topics the current settings would archive off
	 * if performed in one go
	 * @param array $rules [Optional]
	 * @return array	array( count, total, percentage )
	 */
	public function getArchivePossibleCount( $rules=array() )
	{
		$query  = $this->getArchiveWhereQuery( $rules, false, true );	

		$return = array( 'count' => null, 'percentage' => 0, 'total' => 0 );
		 
		if ( $query !== null )
		{
			$total = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count',
													  'from'   => 'topics',
			 										  'where'  => $query  . ' AND state != \'link\'' ) );
			
			$all   = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count',
												 	  'from'   => 'topics' ) );
			
			$return['count'] = intval( $total['count'] );
			$return['total'] = intval( $all['count'] );
			
			if ( $return['count'] && $return['total'] )
			{
				$return['percentage'] = round( ( $return['count'] / $return['total'] ) * 100, 2 );
			}
		}
		
		return $return;
	}
	
	/**
	 * Returns the WHERE portion of a query string required to fetch topics to archive
	 * @param array $rules [Optional]
	 */
	public function getArchiveWhereQuery( $rules=array(), $onlyGetUnarchived=true, $excludeExcluded=false )
	{
		$where    = ( $onlyGetUnarchived ) ? array( 'topic_archive_status=' . $this->registry->class_forums->fetchTopicArchiveFlag( 'not' ) ) : array();
		$whereNot = ( $excludeExcluded ) ? array( 'topic_archive_status=' . $this->registry->class_forums->fetchTopicArchiveFlag( 'exclude' ) ) : array();
		
		/* If no input, fetch from deebee */
		if ( ! count( $rules ) )
		{
			$rules = $this->getRulesFromDb();
		}
		
		/* Hello mum! */
		if ( is_array( $rules ) && count( $rules ) )
		{
			if ( is_array( $rules['archive'] ) )
			{
				foreach( $rules['archive'] as $key => $data )
				{
					$result = $this->_buildQueryBit( $key, $data, true );
					
					if ( $result !== null )
					{
						$where[] = $result;
					}
				}
			}
			
			if ( is_array( $rules['skip'] ) )
			{
				foreach( $rules['skip'] as $key => $data )
				{
					$result = $this->_buildQueryBit( $key, $data, false );
					
					if ( $result !== null )
					{
						$where[] = $result;
					}
				}
			}
		}
		
		foreach ( $whereNot as $w )
		{
			$where[] = "!({$w})";
		}
		
		if ( count( $where ) )
		{
			/* Only fetch non-deleted topics */
			$where[] = $this->registry->class_forums->fetchTopicHiddenQuery( array( 'hidden', 'visible' ) );
			
			return implode( ' AND ', $where );
		}
		else
		{
			return null;
		}
	}
	
	/**
	 * Fetch the rules form the database
	 */
	public function getRulesFromDb()
	{
		$rules = array();
		
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'core_archive_rules',
								 'where'  => 'archive_app=\'' . $this->DB->addSlashes( $this->getApp() ) . '\'' ) );
		
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$whut = ( $row['archive_skip'] ) ? 'skip' : 'archive';
			
			$rules[ $whut ][ $row['archive_field'] ] = array( 'value' => $row['archive_value'],
															  'text'  => $row['archive_text'],
															  'unit'  => $row['archive_unit'] );
		}
		
		return $rules;
	}
	
	/**
	 * Take a post and returned archive friendly array
	 * @param array $post
	 * @return array
	 */
	public function nativeToArchiveFields( $post )
	{
		$archive = array( 'archive_id'           	 => intval( $post['pid'] ),
						  'archive_author_id'    	 => intval( $post['author_id'] ),
						  'archive_author_name'  	 => $post['author_name'],
					      'archive_ip_address'   	 => $post['ip_address'],
						  'archive_content_date' 	 => intval( $post['post_date'] ),
						  'archive_content'		 	 => $post['post'],
						  'archive_queued'	 	     => $post['queued'],
						  'archive_topic_id'     	 => intval( $post['topic_id'] ),
						  'archive_is_first'     	 => intval( $post['new_topic'] ),
						  'archive_bwoptions'    	 => $post['post_bwoptions'],
						  'archive_added'		 	 => IPS_UNIX_TIME_NOW,
						  'archive_attach_key'   	 => $post['post_key'],
						  'archive_html_mode'   	 => $post['post_htmlstate'],
						  'archive_show_signature'   => $post['use_sig'],
						  'archive_show_emoticons'   => $post['use_emo'],
						  'archive_show_edited_by'   => $post['append_edit'],
						  'archive_edit_time'   	 => intval( $post['edit_time'] ),
						  'archive_edit_name'   	 => $post['edit_name'],
						  'archive_edit_reason'      => $post['post_edit_reason'],
						  'archive_forum_id'		 => intval( $post['forum_id'] ) );
		
		return $archive;
						
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
													  'archlog_is_restore' => 0,
													  'archlog_count'      => count( $ids ) ) );	
	}
	
	/**
	 * Logs archive action
	 * @param array $ids
	 * @param boolean $isRestore
	 */
	public function error( $msg )
	{
		$this->DB->insert( 'core_archive_log', array( 'archlog_date'       => IPS_UNIX_TIME_NOW,
													  'archlog_app'        => $this->getApp(),
													  'archlog_msg'        => $msg,
													  'archlog_is_error'   => 1 ) );	
	}
	
	/**
	 * Build a WHERE segment
	 * @param string $key
	 * @param data $data
	 * @param boolean $archiveOn Archive = true, skip = false
	 */
	protected function _buildQueryBit( $key, $data, $archiveOn )
	{
		$strOp = ( $archiveOn ) ? '='  : '!=';
		$inOp  = ( $archiveOn ) ? 'IN' : 'NOT IN';
		
		switch( $key )
		{
			case 'state':
				if ( $data['value'] != '' && $data['value'] != '-' )
				{
					return $this->getDbFieldFromKey( $key ) . $strOp . "'" . $data['value'] . "'"; 
				}
 			break;
			case 'pinned':
			case 'approved':
			case 'poll':
				if ( $data['value'] != '' && $data['value'] != '-' )
				{
					return $this->getDbFieldFromKey( $key ) . $strOp . intval( $data['value'] ); 
				}
			break;
			case 'post':
			case 'view':
			case 'rating':
				if ( is_numeric( $data['text'] ) && $data['text'] > 0 )
				{
					return $this->getDbFieldFromKey( $key ) . $data['value'] . intval( $data['text'] ); 
				}
			break;
			case 'lastpost':
				if ( is_numeric( $data['text'] ) && $data['text'] > 0 )
				{
					switch( $data['unit'] )
					{
						case 'd':
							$seconds = 86400;
						break;
						case 'm':
							$seconds = 2592000;
						break;
						case 'y':
							$seconds = 31536000;
						break;
					}
					
					$time = IPS_UNIX_TIME_NOW - ( $seconds * intval( $data['text'] ) );
					
					/* Switch around for skipping */
					$data['value'] = ( $archiveOn ) ? $data['value'] : ( $data['value'] == '>' ? '<' : '>' );
					
					return $this->getDbFieldFromKey( $key ) . ' ' . $data['value'] . ' ' . $time;
				}
			break;
			case 'forum':
			case 'member':
				if ( $data['text'] )
				{
					if ( IPSLib::isSerialized( $data['text'] ) )
					{
						$ids = unserialize( $data['text'] );
					}
					else
					{
						$ids = array();
					}
					
					$inOp = ( $data['value'] == '+' ) ? 'IN' : 'NOT IN';
					
					if ( is_array( $ids ) && count( $ids ) )
					{
						return $this->getDbFieldFromKey( $key ) . ' ' . $inOp . '(' . implode(',', $ids ) . ')';
					}
				}
			break;
		}
		
		return null;
	}
}
