<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Basic Forum Search
 * Last Updated: $Date: 2012-05-16 07:04:05 -0400 (Wed, 16 May 2012) $
 * </pre>
 *
 * @author 		$Author: mmecham $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10759 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class search_engine_forums extends search_engine
{
	/**
	 * Constructor
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Hard limit */
		IPSSearchRegistry::set('set.hardLimit', ( ipsRegistry::$settings['search_hardlimit'] ) ? ipsRegistry::$settings['search_hardlimit'] : 200 );
		
		/* Get class forums, used for displaying forum names on results */
		if ( ipsRegistry::isClassLoaded('class_forums') !== TRUE )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php", 'class_forums', 'forums' );
			ipsRegistry::setClass( 'class_forums', new $classToLoad( ipsRegistry::instance() ) );
			ipsRegistry::getClass('class_forums')->strip_invisible = 1;
			ipsRegistry::getClass('class_forums')->forumsInit();
		}
		
		/* Load tagging stuff */
		if ( ! $registry->isClassLoaded('tags') )
		{
			
		}
		
		/* Get live or archive */
		$this->searchArchives = ( ipsRegistry::$request['search_app_filters']['forums']['liveOrArchive'] == 'archive' ) ? true : false;
		
		if ( $this->searchArchives )
		{
			/* Load up archive class */
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/archive/reader.php', 'classes_archive_reader' );
			$this->archiveReader = new $classToLoad();
		
			$this->archiveReader->setApp('forums');
			
			$this->table             = $this->archiveReader->getFields();
			$this->table['_table_']  = 'forums_archive_posts';
			$this->table['_prefix_'] = 'p.archive_';
			
			/* disable max days search */
			$this->settings['search_ucontent_days'] = 0;
		}
		else
		{
			$this->table = array( '_table_' 		 => 'posts',
								  '_prefix_'		 => 'p.',
								  'pid'           	 => 'pid',
								  'author_id'    	 => 'author_id',
								  'author_name'  	 => 'author_name',
							      'ip_address'   	 => 'ip_address',
								  'post_date' 	     => 'post_date',
								  'post'		 	 => 'post',
								  'queued'	 	     => 'queued',
								  'topic_id'     	 => 'topic_id',
								  'new_topic'     	 => 'new_topic',
								  'post_bwoptions'   => 'post_bwoptions',
								  'post_key'   	 	 => 'post_key',
								  'post_htmlstate'   => 'post_htmlstate',
								  'use_sig'   		 => 'use_sig',
								  'use_emo'   		 => 'use_emo',
								  'append_edit'   	 => 'append_edit',
								  'edit_time'   	 => 'edit_time',
								  'edit_name'   	 => 'edit_name',
								  'post_edit_reason' => 'post_edit_reason' );
		}
		
		parent::__construct( $registry );
	}
	
	/**
	 * Perform a search.
	 * Returns an array of a total count (total number of matches)
	 * and an array of IDs ( 0 => 1203, 1 => 928, 2 => 2938 ).. matching the required number based on pagination. The ids returned would be based on the filters and type of search
	 *
	 * So if we had 1000 replies, and we are on page 2 of 25 per page, we'd return 25 items offset by 25
	 *
	 * @return array
	 */
	public function search()
	{
		/* INIT */ 
		$sort_by     		= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order         = IPSSearchRegistry::get('in.search_sort_order');
		$search_term        = IPSSearchRegistry::get('in.clean_search_term');
		$search_tags        = IPSSearchRegistry::get('in.raw_search_tags');
		$rows    			= array();
		$c                  = 0;
		$got     			= 0;
		$sortKey			= '';
		$sortType			= '';
		$cType              = IPSSearchRegistry::get('contextual.type');
		$cId		        = IPSSearchRegistry::get('contextual.id' );
		$topicIds			= array();
		
		/* Contextual search */
		if ( $cType == 'topic' )
		{
			IPSSearchRegistry::set('opt.searchType', 'content');
			IPSSearchRegistry::set('opt.noPostPreview', false);
			IPSSearchRegistry::set('set.returnType', 'pids' );
		}
		
		/* If searching tags, we don't show a preview */
		if( $search_tags )
		{
			IPSSearchRegistry::set('opt.noPostPreview', true);
			IPSSearchRegistry::set('set.returnType', 'tids' );
		}
		
		/* Set up the flag for displaying results for posts and topics */
		if ( IPSSearchRegistry::get('opt.searchType') == 'both' || ( IPSSearchRegistry::get('opt.searchType') == 'content' && IPSSearchRegistry::get('opt.noPostPreview') ) )
		{
			IPSSearchRegistry::set('set.searchResultType', 'both' );
		}
		
		/* Sorting */
		switch( $sort_by )
		{
			default:
			case 'date':
				$sortKey  = ! IPSSearchRegistry::searchTitleOnly() ? $this->table['post_date'] : 'last_post';
				$sortType = 'numerical';
			break;
			case 'title':
				$sortKey  = 'title';
				$sortType = 'string';
			break;
			case 'posts':
				$sortKey  = 'posts';
				$sortType = 'numerical';
			break;
			case 'views':
				$sortKey  = 'views';
				$sortType = 'numerical';
			break;
		}

		/* Search in titles */
		/* Removed  OR !($search_tags && $this->settings['tags_enabled']) ) as it was allowing a blank search if tags disabled which makes no sense - Matt */
		if ( $search_term )
		{
			if ( ! IPSSearchRegistry::searchTitleOnly() )
			{
				/* Do the search */
				$this->DB->build( array( 
										'select'   => "p.{$this->table['pid']} as id, p.{$this->table['post_date']}, p.{$this->table['topic_id']}",
										'from'	   => array( $this->table['_table_'] => 'p' ),
		 								'where'	   => $this->_buildWhereStatement( $search_term, IPSSearchRegistry::searchTitleOnly(), '', null, ( IPSSearchRegistry::get('opt.searchType') == 'content' || IPSSearchRegistry::get('in.search_author') ) ? false : true ),
										'limit'    => array(0, IPSSearchRegistry::get('set.hardLimit') + 1),
										'order'    => $sortKey . ' ' . $sort_order,
										'add_join' => array( array( 'select' => 't.title, t.posts, t.views',
																	'from'	 => array( 'topics' => 't' ),
													 				'where'	 => 't.tid=p.'.$this->table['topic_id'],
													 				'type'	 => 'left' ) ) ) );
			}
			else
			{
				/* Do the search */
				$this->DB->build( array( 
										'select'   => "t.tid as id, t.tid as topic_id, t.title, t.posts, t.views, t.last_post",
										'from'	   => 'topics t',
		 								'where'	   => str_replace( 'p.' . $this->table['author_id'], 't.starter_id', $this->_buildWhereStatement( $search_term, IPSSearchRegistry::searchTitleOnly() ) ),
										'limit'    => array(0, IPSSearchRegistry::get('set.hardLimit') + 1),
										'order'    => 't.'.$sortKey . ' ' . $sort_order ) );
			}
	
			$DB = $this->DB->execute();
	
			/* Fetch all to sort */
			while( $r = $this->DB->fetch( $DB ) )
			{
				$topicIds[] = $r['topic_id'];
				
				if ( IPSSearchRegistry::get('opt.noPostPreview') )
				{
					$_rows[ $r['topic_id'] ] = $r;
				}
				else
				{
					$_rows[ $r['id'] ] = $r;
				}
			}
		}
		
		/* Check tags */
		if ( $search_tags && $this->settings['tags_enabled'] )
		{
			$tags = $this->registry->tags->search( $search_tags, array( 'meta_parent_id' => $this->request['search_app_filters']['forums']['forums'],
																		'meta_app'		 => 'forums',
																		'meta_area'		 => 'topics',
																		'meta_id'		 => $topicIds,
																		'sortOrder'		 => $sort_order,
																		'isViewable'     => true ) );
			
			/* Do the search */
			if ( is_array( $tags) && count( $tags ) )
			{
				$_tagIds = array();
				$_rows   = array();
				
				foreach( $tags as $id => $data )
				{
					$_tagIds[] = $data['tag_meta_id'];
				}
				
				$this->DB->build( array('select'	=> "t.tid as id, t.tid as topic_id, t.title, t.posts, t.views, t.last_post",
										'from'		=> array( 'topics' => 't' ),
		 								'where'		=> 't.tid IN (' . implode( ",", $_tagIds ) . ')',
										'limit'		=> array(0, IPSSearchRegistry::get('set.hardLimit') + 1),
										'order'		=> 't.'.str_replace( 'post_date', 'topic_firstpost', $sortKey ) . ' ' . $sort_order,
										'add_join'	=> array( array( 'select'	=> 'p.'.$this->table['pid'],
																	 'from'		=> array( $this->table['_table_'] => 'p' ),
																	 'where'	=> 'p.'.$this->table['pid'].'=t.topic_firstpost',
																	 'type'		=> 'left' ) ) ) );
				
		
				$DB = $this->DB->execute();
		
				/* Fetch all to merge and sort */
				while( $r = $this->DB->fetch( $DB ) )
				{ 
					if ( IPSSearchRegistry::get('set.returnType') != 'pids' )
					{ 
						$_rows[ $r['topic_id'] ] = $r;
					}
					else
					{
						$r['id'] = $r[$this->table['pid']];
						$_rows[ $r['id'] ] = $r;
					}
				}
			}
		}
		
		/* Fetch count */
		$count = count( $_rows );	
		
		if ( $count > IPSSearchRegistry::get('set.hardLimit') )
		{
			$count = IPSSearchRegistry::get('set.hardLimit');
			
			IPSSearchRegistry::set('set.resultsCutToLimit', true );
		}

		/* Set vars */
		IPSSearch::$ask = $sortKey;
		IPSSearch::$aso = strtolower( $sort_order );
		IPSSearch::$ast = $sortType;
		
		/* Sort */
		if ( count( $_rows ) )
		{
			usort( $_rows, array("IPSSearch", "usort") );
		
			/* Build result array */
			foreach( $_rows as $r )
			{
				$c++;
				
				if ( IPSSearchRegistry::get('in.start') AND IPSSearchRegistry::get('in.start') >= $c )
				{
					continue;
				}
				
				$rows[ $got ] = $r['id'];
							
				$got++;
				
				/* Done? */
				if ( IPSSearchRegistry::get('opt.search_per_page') AND $got >= IPSSearchRegistry::get('opt.search_per_page') )
				{
					break;
				}
			}
		}
		
		/* Return it */
		return array( 'count' => $count, 'resultSet' => $rows );
	}
	
	/**
	 * Perform the search
	 * Populates $this->_count and $this->_results
	 *
	 * @return	nothin'
	 */
	public function viewUserContent( $member )
	{
		$sort_by		= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order		= IPSSearchRegistry::get('in.search_sort_order');

		switch( IPSSearchRegistry::get('in.userMode') )
		{
			default:
			case 'all': 
				IPSSearchRegistry::set('opt.searchType', 'both' );
				IPSSearchRegistry::set('opt.noPostPreview'  , true );
				IPSSearchRegistry::set('set.returnType', 'tids' );
			break;
			case 'title': 
				IPSSearchRegistry::set('opt.searchType', 'titles' );
				IPSSearchRegistry::set('opt.noPostPreview'  , true );
				IPSSearchRegistry::set('set.returnType', 'tids' );
			break;	
			case 'content': 
				IPSSearchRegistry::set('opt.searchType', 'content' );
				IPSSearchRegistry::set('opt.noPostPreview'  , false );
				IPSSearchRegistry::set('set.returnType', 'pids' );
			break;	
		}

		/* Sorting */
		switch( $sort_by )
		{
			default:
			case 'date':
				$sortKey  = IPSSearchRegistry::get('opt.searchType') == 'content' ? 'p.post_date' : 't.last_post';
				$sortType = 'numerical';
			break;
			case 'title':
				$sortKey  = 't.title';
				$sortType = 'string';
			break;
			case 'posts':
				$sortKey  = 't.posts';
				$sortType = 'numerical';
			break;
			case 'views':
				$sortKey  = 't.views';
				$sortType = 'numerical';
			break;
		}
		
		/* Init */
		$start		= IPSSearchRegistry::get('in.start');
		$perPage	= IPSSearchRegistry::get('opt.search_per_page');

		/* Get list of good forum IDs */
		$forumIdsOk	= $this->registry->class_forums->fetchSearchableForumIds( $this->memberData['member_id'] );

		//-----------------------------------------
		// No forums?
		//-----------------------------------------
		
		if( !count( $forumIdsOk ) )
		{
			return array( 'count' => 0, 'resultSet' => array() );
		}

		$topic_where[]	= "t.forum_id IN (" . implode( ",", $forumIdsOk ) . ")";
		
		if ( IPSSearchRegistry::get('in.userMode') != 'title' )
		{
			$where[] = "p.author_id=" . intval( $member['member_id'] );
		}
		
		if ( $this->settings['search_ucontent_days'] )
		{
			if ( IPSSearchRegistry::get('in.userMode') != 'title' )
			{
				$where[]       = "p.post_date > " . ( ( $member['last_post'] ? $member['last_post'] : time() ) - ( 86400 * intval( $this->settings['search_ucontent_days'] ) ) );
			}
			
			$topic_where[] = "t.last_post > " . ( ( $member['last_post'] ? $member['last_post'] : time() ) - ( 86400 * intval( $this->settings['search_ucontent_days'] ) ) );
		}
		
		$where[]	= "state != 'link'";
		
		/* Set up perms */
		$permissions						= array();
		$permissions['TopicSoftDeleteSee']  = $this->registry->getClass('class_forums')->canSeeSoftDeletedTopics( 0 );
		$permissions['canQueue']			= $this->registry->getClass('class_forums')->canQueuePosts( 0 );
		$permissions['PostSoftDeleteSee']   = $this->registry->getClass('class_forums')->canSeeSoftDeletedPosts( 0 );
		$permissions['SoftDeleteReason']	= $this->registry->getClass('class_forums')->canSeeSoftDeleteReason( 0 );
		$permissions['SoftDeleteContent']	= $this->registry->getClass('class_forums')->canSeeSoftDeleteContent( 0 );
		
		/* Exclude some items */
		if ( IPSSearchRegistry::get('in.userMode') != 'title' )
		{
			if ( $permissions['SoftDeleteContent'] AND $permissions['PostSoftDeleteSee'] AND $permissions['canQueue'] )
			{
				$where[] = $this->registry->class_forums->fetchPostHiddenQuery(array('visible', 'hidden', 'sdeleted'), 'p.');
			}
			else if ( $permissions['SoftDeleteContent'] AND $permissions['PostSoftDeleteSee'] )
			{
				$where[] = $this->registry->class_forums->fetchPostHiddenQuery(array('visible', 'sdeleted'), 'p.');
			}
			else if ( $permissions['canQueue'] )
			{
				$where[] = $this->registry->class_forums->fetchPostHiddenQuery(array('visible', 'hidden'), 'p.');
			}
			else
			{
				$where[] = $this->registry->class_forums->fetchPostHiddenQuery(array('visible' ), 'p.' );
			}
		}
		
		if ( $permissions['TopicSoftDeleteSee'] AND $permissions['canQueue'] )
		{
			$topic_where[] = $this->registry->class_forums->fetchTopicHiddenQuery(array('visible', 'hidden', 'sdeleted'), 't.' );
		}
		else if ( $permissions['TopicSoftDeleteSee'] )
		{
			$topic_where[] = $this->registry->class_forums->fetchTopicHiddenQuery(array('visible', 'sdeleted'), 't.' );
		}
		else if ( $permissions['canQueue'] )
		{
			$topic_where[] = $this->registry->class_forums->fetchTopicHiddenQuery(array('visible', 'hidden'), 't.' );
		}
		else
		{
			$topic_where[] = $this->registry->class_forums->fetchTopicHiddenQuery(array('visible' ), 't.' );
		}
		
		/* Not archives */
		$topic_where[] = $this->registry->class_forums->fetchTopicArchiveQuery( array('not', 'exclude' ), 't.' );

		/* Manual fetch if user content all */
		if ( IPSSearchRegistry::get('in.userMode') == 'all' )
		{
			/* init */
			$pids = array();
			
			$this->DB->build( array( 'select'   => 't.tid, t.last_post, t.topic_firstpost',
									 'from'     => 'topics t',
									 'where'    => implode( ' AND ', $topic_where ) . " AND t.starter_id=" . intval( $member['member_id'] ),
									 'order'    => $sortKey . ' ' . $sort_order,
									 'limit'    => array(0, 1000 ) ) );
			$this->DB->execute();
			
			while( $t = $this->DB->fetch() )
			{
				$pids[ $t['tid'] ] = $t['last_post'];
			}
			
			$where	= ( array_merge( (array) $where, (array) $topic_where ) );
			$where	= implode( " AND ", $where );
			
			/* Now for posts */
			$this->DB->build( array( 'select'   => 'p.pid',
									 'from'     => array('posts' => 'p' ),
									 'where'    => $where,
									 'add_join' => array( array( 'select' => 't.tid, t.last_post, t.topic_firstpost',
									 							 'from'   => array( 'topics' => 't' ),
							 		  							 'where'  => 'p.topic_id=t.tid',
							 		  							 'type'   => 'left' ) ),
									 'order'    => 'p.pid DESC',
									 'limit'    => array( 0, 1000 ) ) );
									 
			$this->DB->execute();
			
			while( $t = $this->DB->fetch() )
			{
				$pids[ $t['tid'] ] = $t['last_post'];
			}
			
			$count = array( 'count' => count( $pids ) );
			
			if ( $count['count'] )
			{
				arsort( $pids, SORT_NUMERIC );
				$tids = array_slice( array_keys( $pids ), $start, $perPage, true );
			} 
		}
		else if ( IPSSearchRegistry::get('in.userMode') == 'title' )
		{
			$topic_where[] = "t.starter_id=" . intval( $member['member_id'] );
			
			$where = ( array_merge( (array) $where, (array) $topic_where ) );
			$where = implode( " AND ", $where );

			/* Fetch the count */
			$count = $this->DB->buildAndFetch( array( 'select'   => 'COUNT(tid) as count',
											  		  'from'     => 'topics t',
											 		  'where'    => $where ) );
									 
			/* Fetch the count */
			if ( $count['count'] )
			{
				$this->DB->build( array( 'select'   => 'tid',
										 'from'     => 'topics t',
										 'where'    => $where,
										 'order'    => $sortKey . ' ' . $sort_order,
										 'limit'    => array( $start, $perPage ) ) );
										
				$inner = $this->DB->execute();
			
				while( $row = $this->DB->fetch( $inner ) )
				{
					$tids[ $row['tid'] ] = $row['tid'];
				}
			}
		}
		else
		{
			$where = ( array_merge( (array) $where, (array) $topic_where ) );
			$where = implode( " AND ", $where );
			
			/* Fetch the count */
			$count = $this->DB->buildAndFetch( array( 'select'   => 'COUNT(tid) as count',
											  		  'from'     => array('topics' => 't' ),
											 		  'where'    => $where,
											 		  'add_join' => array( array( 'from'   => array( 'posts' => 'p' ),
											 		  							  'where'  => 'p.topic_id=t.tid',
											 		  							  'type'   => 'left' ) ) ) );

			/* Fetch the count */
			if ( $count['count'] )
			{
				$this->DB->build( array( 'select'   => 'tid',
										 'from'     => array('topics' => 't' ),
										 'where'    => $where,
										 'add_join' => array( array( 'select' => 'p.pid',
								 		  							 'from'   => array( 'posts' => 'p' ),
								 		  							 'where'  => 'p.topic_id=t.tid',
								 		  							 'type'   => 'left' ) ),
										 'order'    => $sortKey . ' ' . $sort_order,
										 'limit'    => array( $start, $perPage ) ) );
										
				$inner = $this->DB->execute();
			
				while( $row = $this->DB->fetch( $inner ) )
				{
					$tids[ $row['pid'] ] = $row['pid'];
				}
			}
		}
		
		/* Fix to 1000 results max */
		$count['count'] = ( $count['count'] > 1000 ) ? 1000 : $count['count'];
	
		/* Return it */
		return array( 'count' => $count['count'], 'resultSet' => $tids );
	}
	
	/**
	 * Perform the viewNewContent search
	 * Forum Version
	 * Populates $this->_count and $this->_results
	 *
	 * @return	nothin'
	 */
	public function viewNewContent()
	{
		$rtids		= array();
		$oldStamp	= $this->registry->getClass('classItemMarking')->fetchOldestUnreadTimestamp( array(), 'forums' );
		$check		= IPS_UNIX_TIME_NOW - ( 86400 * $this->settings['topic_marking_keep_days'] );
		$forumIdsOk	= array();
		$where		= array();
		$_tidsOnly	= array();
		
		/* Loop through the forums and build a list of forums we're allowed access to */
		$bvnp			= explode( ',', $this->settings['vnp_block_forums'] );
		$start			= IPSSearchRegistry::get('in.start');
		$perPage		= IPSSearchRegistry::get('opt.search_per_page');
		$seconds		= IPSSearchRegistry::get('in.period_in_seconds');
		$followedOnly	= $this->memberData['member_id'] ? IPSSearchRegistry::get('in.vncFollowFilterOn' ) : false;
		
		$followedForums	= array();
		$followedTopics	= array();
		
		IPSSearchRegistry::set('in.search_sort_by'		, 'date' );
		IPSSearchRegistry::set('in.search_sort_order'	, 'desc' );
		IPSSearchRegistry::set('opt.searchType'			, 'titles' );
		IPSSearchRegistry::set('opt.noPostPreview'		, true );
		
		//-----------------------------------------
		// Only content we have participated in?
		//-----------------------------------------

		if( IPSSearchRegistry::get('in.userMode') )
		{
			$_tempResults	= $this->viewUserContent( $this->memberData );
			
			if( $_tempResults['count'] )
			{
				$_tidsOnly	= array_merge( $_tidsOnly, $_tempResults['resultSet'] );
			}
			else
			{
				return array( 'count' => 0, 'resultSet' => array() );
			}

			switch( IPSSearchRegistry::get('in.userMode') )
			{
				default:
				case 'all':
				case 'content':
					IPSSearchRegistry::set('opt.searchType', 'both' );
					IPSSearchRegistry::set('opt.noPostPreview'  , true );
				break;
				case 'title': 
					IPSSearchRegistry::set('opt.searchType', 'titles' );
					IPSSearchRegistry::set('opt.noPostPreview'  , false );
				break;
			}
		}
		
		/* Set return type */
		IPSSearchRegistry::set('set.returnType', 'tids' );
		
		/* Get list of good forum IDs */
		$_forumIdsOk	= $this->registry->class_forums->fetchSearchableForumIds( $this->memberData['member_id'], ( $followedOnly ) ? array() : $bvnp );
		
		/* Fetch forum rel ids */
		if ( $followedOnly )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
			
			/* @link http://community.invisionpower.com/tracker/issue-33284-view-new-content-just-items-i-follow */
			//$like = classes_like::bootstrap( 'forums', 'forums' );
			
			//$followedForums = $like->getDataByMemberIdAndArea( $this->memberData['member_id'] );
			$followedForums = array(); //( $followedForums === null ) ? array() : array_keys( $followedForums );
			
			$like = classes_like::bootstrap( 'forums', 'topics' );
			
			$followedTopics = $like->getDataByMemberIdAndArea( $this->memberData['member_id'] );
			$followedTopics = ( $followedTopics === null ) ? array() : array_keys( $followedTopics );
		}

		/* Period filtering */
		if ( IPSSearchRegistry::get('in.period_in_seconds') !== false )
		{
			$where[]  = "last_post > " .  ( IPS_UNIX_TIME_NOW - $seconds );

			$forumIdsOk = $_forumIdsOk;
		}
		else
		{
			if ( intval( $this->memberData['_cache']['gb_mark__forums'] ) > 0 )
			{
				$oldStamp = $this->memberData['_cache']['gb_mark__forums'];
			}

			/* Finalize times */
			if ( ! $oldStamp OR $oldStamp == IPS_UNIX_TIME_NOW )
			{
				$oldStamp = intval( $this->memberData['last_visit'] );
			}
			
			/* Older than 3 months.. then limit */
			if ( $oldStamp < $check )
			{
				$oldStamp = $check;
			}
		
			foreach( $_forumIdsOk as $id )
			{
				/*if ( $followedOnly && ! in_array( $id, $followedForums ) )
				{
					// We don't want to skip followed topics just because we don't follow the forum
					continue;
				}*/
				
				$lMarked    = $this->registry->getClass('classItemMarking')->fetchTimeLastMarked( array( 'forumID' => $id ), 'forums' );
				$fData      = $this->registry->getClass('class_forums')->forumsFetchData( $id );
				
				if ( $fData['last_post'] > $lMarked )
				{
					$forumIdsOk[ $id ] = $id;
				}
			}
			
			/* If no forums, we're done */
			if ( ! count( $forumIdsOk ) )
			{
				/* Return it */
				return array( 'count' => 0, 'resultSet' => array() );
			}
			
			/* Based on oldest timestamp */
			$where[] = "last_post > " . $oldStamp;
		}

		$forumIdsOk	= ( count( $forumIdsOk ) ) ? $forumIdsOk : array( 0 => 0 );
		
		/* Only show VNC results from specified forums? */
		if( is_array(IPSSearchRegistry::get('forums.vncForumFilters')) AND count(IPSSearchRegistry::get('forums.vncForumFilters')) )
		{
			$_newIdsOk	= array();
			
			foreach( IPSSearchRegistry::get('forums.vncForumFilters') as $forumId )
			{
				if( in_array( $forumId, $forumIdsOk ) )
				{
					$_newIdsOk[]	= $forumId;
				}
			}
			
			$forumIdsOk	= $_newIdsOk;
		}

		//-----------------------------------------
		// No forums?
		//-----------------------------------------
		
		if( !count( $forumIdsOk ) )
		{
			return array( 'count' => 0, 'resultSet' => array() );
		}

		$where[]	= "forum_id IN (" . implode( ",", $forumIdsOk ) . ")";
		
		/* Add in last bits */
		$where[]	= "state != 'link'";
		
		/* Set up perms */
		$permissions['TopicSoftDeleteSee']  = $this->registry->getClass('class_forums')->canSeeSoftDeletedTopics( 0 );
		$permissions['canQueue']			= $this->registry->getClass('class_forums')->canQueuePosts( 0 );
		
		if ( $permissions['TopicSoftDeleteSee'] AND $permissions['canQueue'] )
		{
			$permWhere = $this->registry->class_forums->fetchTopicHiddenQuery(array('visible', 'hidden', 'sdeleted') );
		}
		else if ( $permissions['TopicSoftDeleteSee'] )
		{
			$permWhere = $this->registry->class_forums->fetchTopicHiddenQuery(array('visible', 'sdeleted') );
		}
		else if ( $permissions['canQueue'] )
		{
			$permWhere = $this->registry->class_forums->fetchTopicHiddenQuery(array('visible', 'hidden') );
		}
		else
		{
			$permWhere = $this->registry->class_forums->fetchTopicHiddenQuery(array('visible' )  );
		}
		
		$where[] = $this->registry->class_forums->fetchTopicArchiveQuery( array('not', 'exclude' ) );
		
		/* add perm */
		$where[]  = $permWhere;
		$_ffWhere = '';
		
		/* Followed topics only? */
		if ( $followedOnly )
		{
			if ( ! count( $followedTopics ) && ! count( $followedForums ) )
			{
				return array( 'count' => 0, 'resultSet' => array() );
			}

			$_tidsOnly	= array_merge( $_tidsOnly, $followedTopics );
			
			if ( count( $followedForums ) )
			{
				$_ffWhere = ' OR forum_id IN (' . implode( ',', $followedForums ) . ') ';
			}
		}

		/* This method allows us to have just one "tid IN()" clause, instead of two, depending on scenario */
		if( count($_tidsOnly) AND count($followedTopics) )
		{
			/* Only look for tids in both arrays */
			$_both	= array_intersect( $_tidsOnly, $followedTopics );
			
			if( count($_both) )
			{
				$where[]  = "( tid IN(" . implode( ',', $_both ) . ')' . $_ffWhere . ' )';
			}
		}
		else if( count($_tidsOnly) )
		{
			$where[]  = "( tid IN(" . implode( ',', $_tidsOnly ) . ')' . $_ffWhere . ' )';
		}
		else if( count($followedTopics) )
		{
			$where[]  = "( tid IN(" . implode( ',', $followedTopics ) . ')' . $_ffWhere . ' )';
		}

		$where = implode( " AND ", $where );

		/* Fetch the count */
		$count = $this->DB->buildAndFetch( array( 'select'   => 'count(*) as count',
										  		  'from'     => 'topics',
										 		  'where'    => $where ) );
								 
		/* Fetch the count */
		if ( $count['count'] )
		{
			$limit = ( IPSSearchRegistry::get('in.period') != 'unread' ) ? array( $start, $perPage ) : array( 0, 1200 );
		
			$this->DB->build( array( 'select'   => 'tid, forum_id, last_post',
									 'from'     => 'topics',
									 'where'    => $where,
									 'order'    => 'last_post DESC',
									 'limit'    => $limit ) );
									
			$inner  = $this->DB->execute();
			
			while( $row = $this->DB->fetch( $inner ) )
			{
				$rtids[ $row['tid'] ] = $row;
			}
		}
		
		/* Set up some vars */
		IPSSearchRegistry::set('set.resultCutToDate', $oldStamp );
		
		if( IPSSearchRegistry::get('in.period') == 'unread' )
		{
			$filter				= $this->registry->class_forums->postProcessVncTids( $rtids, array( $start, $perPage ) );
			$data['count']		= $filter['count'];
			$data['resultSet']	= $filter['tids'];
		}
		else
		{
			$data	= array( 'count' => $count['count'], 'resultSet' => array_keys( $rtids) );
		}
		
		/* Return it */
		return $data;
	}

	/**
	 * Builds the where portion of a search string
	 *
	 * @param	string	$search_term		The string to use in the search
	 * @param	bool	$content_title_only	Search only title records
	 * @param	string	$order				Order by data
	 * @param	bool	$onlyPosts			Enforce posts only
	 * @param	bool	$noForums			Don't check forums that posts are in
	 * @return	string
	 */
	protected function _buildWhereStatement( $search_term, $content_title_only=false, $order='', $onlyPosts=null, $noForums=false )
	{		
		/* INI */
		$where_clause	= array();
		$onlyPosts		= ( $onlyPosts !== null ) ? $onlyPosts : ( IPSSearchRegistry::get('opt.searchType') == 'content' );
		$sort_by		= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order		= IPSSearchRegistry::get('in.search_sort_order');
		$sortKey		= '';
		$sortType		= '';
		$cType			= IPSSearchRegistry::get('contextual.type');
		$cId			= IPSSearchRegistry::get('contextual.id' );
		$search_tags	= IPSSearchRegistry::get('in.raw_search_tags');
		
		/* Loop through the forums and build a list of forums we're allowed access to */
		$forumIdsOk		= array();
		$forumIdsBad	= array();
		
		if ( ! empty( ipsRegistry::$request['search_app_filters']['forums']['forums'] ) AND count( ipsRegistry::$request['search_app_filters']['forums']['forums'] ) )
		{
			foreach(  ipsRegistry::$request['search_app_filters']['forums']['forums'] as $forum_id )
			{
				if( $forum_id )
				{
					$data	= $this->registry->class_forums->forum_by_id[ $forum_id ];
					
					/* Check for sub forums */
					$children = ipsRegistry::getClass( 'class_forums' )->forumsGetChildren( $forum_id );
					
					foreach( $children as $kid )
					{
						if( ! in_array( $kid, ipsRegistry::$request['search_app_filters']['forums']['forums'] ) )
						{
							if( ! $this->registry->permissions->check( 'read', $this->registry->class_forums->forum_by_id[ $kid ] ) )
							{
								$forumIdsBad[] = $kid;
								continue;
							}
							
							/* Can read, but is it password protected, etc? */
							if ( ! $this->registry->class_forums->forumsCheckAccess( $kid, 0, 'forum', array(), true ) )
							{
								$forumIdsBad[] = $kid;
								continue;
							}

							if ( ! $this->registry->class_forums->forum_by_id[ $kid ]['sub_can_post'] OR ! $this->registry->class_forums->forum_by_id[ $kid ]['can_view_others'] )
							{
								$forumIdsBad[] = $kid;
								continue;
							}
							
							$forumIdsOk[] = $kid;
						}
					}

					/* Can we read? */
					if ( ! $this->registry->permissions->check( 'view', $data ) )
					{
						$forumIdsBad[] = $forum_id;
						continue;
					}

					/* Can read, but is it password protected, etc? */
					if ( ! $this->registry->class_forums->forumsCheckAccess( $forum_id, 0, 'forum', array(), true ) )
					{
						$forumIdsBad[] = $forum_id;
						continue;
					}

					if ( ( ! $data['sub_can_post'] OR ! $data['can_view_others'] ) AND !$this->memberData['g_access_cp'] )
					{
						$forumIdsBad[] = $forum_id;
						continue;
					}
				
					$forumIdsOk[] = $forum_id;
				}
			}
		}
		
		if ( ! count($forumIdsOk) )
		{
			/* Get list of good forum IDs */
			$forumIdsOk = $this->registry->class_forums->fetchSearchableForumIds();
		}

		/* Add allowed forums */
		if ( $noForums !== true )
		{
			$forumIdsOk = ( count( $forumIdsOk ) ) ? $forumIdsOk : array( 0 => 0 );
			
			/* Contextual */
			if ( $cType == 'forum' AND $cId AND in_array( $cId, $forumIdsOk ) )
			{
				$where_clause[] = "t.forum_id=" . $cId;
			}
			else
			{
				$where_clause[] = "t.forum_id IN (" . implode( ",", $forumIdsOk ) . ")";
			}
		}
		
		/* Topic contextual */
		if ( $cType == 'topic' AND $cId )
		{
			$where_clause[] = "t.tid=" . $cId;
		}
			
		/* Exclude some items */
		$permissions						= array();
		$permissions['TopicSoftDeleteSee']  = $this->registry->getClass('class_forums')->canSeeSoftDeletedTopics( 0 );
		$permissions['canQueue']			= $this->registry->getClass('class_forums')->canQueuePosts( 0 );
		$permissions['PostSoftDeleteSee']   = $this->registry->getClass('class_forums')->canSeeSoftDeletedPosts( 0 );
		$permissions['SoftDeleteReason']    = $this->registry->getClass('class_forums')->canSeeSoftDeleteReason( 0 );
		$permissions['SoftDeleteContent']   = $this->registry->getClass('class_forums')->canSeeSoftDeleteContent( 0 );

		if ( ! $content_title_only )
		{
			if ( $permissions['SoftDeleteContent'] AND $permissions['PostSoftDeleteSee'] AND $permissions['canQueue'] )
			{
				$where_clause[] = $this->registry->class_forums->fetchPostHiddenQuery(array('visible', 'hidden', 'sdeleted'), $this->table['_prefix_']);
			}
			else if ( $permissions['SoftDeleteContent'] AND $permissions['PostSoftDeleteSee'] )
			{
				$where_clause[] = $this->registry->class_forums->fetchPostHiddenQuery(array('visible', 'sdeleted'), $this->table['_prefix_']);
			}
			else if ( $permissions['canQueue'] )
			{
				$where_clause[] = $this->registry->class_forums->fetchPostHiddenQuery(array('visible', 'hidden'), $this->table['_prefix_']);
			}
			else
			{
				$where_clause[] = $this->registry->class_forums->fetchPostHiddenQuery(array('visible' ), $this->table['_prefix_'] );
			}
		}

		if ( $permissions['SoftDeleteContent'] AND $permissions['TopicSoftDeleteSee'] AND $permissions['canQueue'] )
		{
			$where_clause[] = $this->registry->class_forums->fetchTopicHiddenQuery(array('visible', 'hidden', 'sdeleted'), 't.');
		}
		else if ( $permissions['SoftDeleteContent'] AND $permissions['TopicSoftDeleteSee'] )
		{
			$where_clause[] = $this->registry->class_forums->fetchTopicHiddenQuery(array('visible', 'sdeleted'), 't.');
		}
		else if ( $permissions['canQueue'] )
		{
			$where_clause[] = $this->registry->class_forums->fetchTopicHiddenQuery(array('visible', 'hidden'), 't.');
		}
		else
		{
			$where_clause[] = $this->registry->class_forums->fetchTopicHiddenQuery(array('visible' ), 't.' );
		}
		
		/* Live or archived? */
		if ( $this->searchArchives )
		{
			$where_clause[] = $this->registry->class_forums->fetchTopicArchiveQuery( array('working', 'archived' ), 't.' );
		}
		else
		{
			$where_clause[] = $this->registry->class_forums->fetchTopicArchiveQuery( array('not', 'exclude' ), 't.' );
		}

		if( $search_term )
		{
			$search_term = str_replace( '&quot;', '"', $search_term );
			
			if( $content_title_only )
			{			
				$where_clause[] = $this->DB->buildSearchStatement( 't.title', $search_term, true, false, ipsRegistry::$settings['use_fulltext'] );

				IPSSearchRegistry::set('set.returnType', 'tids' );
			}
			else
			{
				if ( $onlyPosts )
				{
					$where_clause[] = $this->DB->buildSearchStatement( 'p.' . $this->table['post'], $search_term, true, false, ipsRegistry::$settings['use_fulltext'] );
					IPSSearchRegistry::set('set.returnType', 'pids' );
				}
				else
				{
					IPSSearchRegistry::set('set.returnType', 'pids' );
					
					/* Sorting */
					switch( $sort_by )
					{
						default:
						case 'date':
							$sortKey  = 'last_post';
							$sortType = 'numerical';
						break;
						case 'title':
							$sortKey  = 'title';
							$sortType = 'string';
						break;
						case 'posts':
							$sortKey  = 'posts';
							$sortType = 'numerical';
						break;
						case 'views':
							$sortKey  = 'views';
							$sortType = 'numerical';
						break;
					}

					/* Set vars */
					IPSSearch::$ask = $sortKey;
					IPSSearch::$aso = strtolower( $sort_order );
					IPSSearch::$ast = $sortType;
			
					/* Find topic ids that match */
					$tids = array( 0 => 0 );
					$pids = array( 0 => 0 );
					
					$this->DB->build( array('select'   => "t.tid, t.last_post, t.forum_id",
											'from'	   => 'topics t',
			 								'where'	   => str_replace( 'p.' . $this->table['author_id'], 't.starter_id', $this->_buildWhereStatement( $search_term, true, $order, null ) ),
			 								'order'    => 't.' . $sortKey . ' ' . $sort_order,
											'limit'    => array(0, IPSSearchRegistry::get('set.hardLimit')) ) );
								
					$i = $this->DB->execute();
					
					/* Grab the results */
					while( $row = $this->DB->fetch( $i ) )
					{
						$_rows[ $row['tid'] ] = $row;
					}
			
					/* Sort */
					if ( count( $_rows ) )
					{
						usort( $_rows, array("IPSSearch", "usort") );
				
						foreach( $_rows as $id => $row )
						{
							$tids[] = $row['tid'];
						}
					}
					
					/* Now get the Pids */
					if ( count( $tids ) > 1 )
					{
						$this->DB->build( array('select'  => $this->table['pid'],
												'from'	  => $this->table['_table_'],
												'where'   => $this->table['topic_id'] . ' IN ('. implode( ',', $tids ) . ') AND ' . $this->table['new_topic'] . '=1' ) );
						
						$i = $this->DB->execute();
						
						while( $row = $this->DB->fetch() )
						{
							$pids[ $row[ $this->table['pid'] ] ] = $row[ $this->table['pid'] ];
						}
					}
					
					/* Set vars */
					IPSSearch::$ask = ( $sortKey == 'last_post' ) ? $this->table['post_date'] : $sortKey;
					IPSSearch::$aso = strtolower( $sort_order );
					IPSSearch::$ast = $sortType;
					
					$this->DB->build( array( 
											'select'   => "p.{$this->table['pid']}, p.{$this->table['queued']}",
											'from'	   => array( $this->table['_table_'] => 'p' ),
			 								'where'	   => $this->_buildWhereStatement( $search_term, false, $order, true ),
			 								'order'    => IPSSearch::$ask . ' ' . IPSSearch::$aso,
											'limit'    => array(0, IPSSearchRegistry::get('set.hardLimit')),
											'add_join' => array( array( 'select' => 't.approved, t.forum_id',
																		'from'   => array( 'topics' => 't' ),
																		'where'  => 'p.'.$this->table['topic_id'].'=t.tid',
																		'type'   => 'left' ) ) ) );
								
					$i = $this->DB->execute();
					
					/* Grab the results */
					while( $row = $this->DB->fetch( $i ) )
					{
						$_prows[ $row[ $this->table['pid'] ] ] = $row;
					}
			
					/* Sort */
					if ( count( $_prows ) )
					{
						usort( $_prows, array("IPSSearch", "usort") );
						
						foreach( $_prows as $id => $row )
						{
							$pids[ $row[ $this->table['pid'] ] ] = $row[ $this->table['pid'] ];
						}
					}
					
					$where_clause[] = '( p.' . $this->table['pid'] .' IN (' . implode( ',', $pids ) .') )';
				}
			}
		}
		
		/* No moved topic links */
		$where_clause[] = "t.state != 'link'";
		
		/* Date Restrict */
		if( $this->search_begin_timestamp && $this->search_end_timestamp )
		{ 
			$where_clause[] = $this->DB->buildBetween( $content_title_only ? "t.last_post" : "p.".$this->table['post_date'], $this->search_begin_timestamp, $this->search_end_timestamp );
		}
		else
		{
			if( $this->search_begin_timestamp )
			{
				$where_clause[] = $content_title_only ? "t.last_post > {$this->search_begin_timestamp}" : "p.".$this->table['post_date'] . " > {$this->search_begin_timestamp}";
			}
			
			if( $this->search_end_timestamp )
			{
				$where_clause[] = $content_title_only ? "t.last_post < {$this->search_end_timestamp}" : "p.".$this->table['post_date'] . " < {$this->search_end_timestamp}";
			}
		}
		
		/* Add in AND where conditions */
		if( isset( $this->whereConditions['AND'] ) && count( $this->whereConditions['AND'] ) )
		{
			$where_clause = array_merge( $where_clause, $this->whereConditions['AND'] );
		}
		
		/* ADD in OR where conditions */
		if( isset( $this->whereConditions['OR'] ) && count( $this->whereConditions['OR'] ) )
		{
			$where_clause[] = '( ' . implode( ' OR ', $this->whereConditions['OR'] ) . ' )';
		}

		/* Build and return the string */
		return implode( " AND ", $where_clause );
	}

	/**
	 * Remap standard columns (Apps can override )
	 *
	 * @param	string	$column		sql table column for this condition
	 * @return	string				column
	 * @return	@e void
	 */
	public function remapColumn( $column )
	{
		$column = $column == 'member_id'     ? ( IPSSearchRegistry::get('opt.searchTitleOnly') ? 't.starter_id' : 'p.' . $this->table['author_id'] ) : $column;
		$column = $column == 'content_title' ? 't.title'     : $column;
		$column = $column == 'type_id'       ? 't.forum_id'  : $column;
		
		return $column;
	}
		
	/**
	 * Returns an array used in the searchplugin's setCondition method
	 *
	 * @param	array 	$data	Array of forums to view
	 * @return	array 	Array with column, operator, and value keys, for use in the setCondition call
	 */
	public function buildFilterSQL( $data )
	{
		/* INIT */
		$return = array();
		
		/* Set up some defaults */
		IPSSearchRegistry::set( 'opt.noPostPreview'  , true );
		//IPSSearchRegistry::set( 'opt.searchType', 'both' );
		
		/* Make default search type topics */
		if( isset( $data ) && is_array( $data ) && count( $data ) )
		{
			foreach( $data as $field => $_data )
			{
				/* CONTENT ONLY */
				if ( $field == 'noPreview' AND $_data == 0 )
				{
					IPSSearchRegistry::set( 'opt.noPostPreview', false );
				}

				/* POST COUNT */
				if ( $field == 'pCount' AND intval( $_data ) > 0 )
				{
					$return[] = array( 'column' => 't.posts', 'operator' => '>=', 'value' => intval( $_data ) );
				}

				/* TOPIC VIEWS */
				if ( $field == 'pViews' AND intval( $_data ) > 0 )
				{
					$return[] = array( 'column' => 't.views', 'operator' => '>=', 'value' => intval( $_data ) );
				}
			}

			return $return;
		}
		else
		{
			return '';
		}
	}
}