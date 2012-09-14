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

class classes_archive_reader_sql extends classes_archive_reader
{
	
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
		
		/* Do we have a remote DB? */
		if ( $this->settings['archive_remote_sql_database'] && $this->settings['archive_remote_sql_user'] )
		{
			if ( ! is_object( $this->registry->dbFunctions()->getDB('remoteArchive') ) )
			{	
				
				$this->registry->dbFunctions()->setDB( 'mysql', 'remoteArchive', array(  'sql_database'	        => $this->settings['archive_remote_sql_database'],
																						 'sql_user'		        => $this->settings['archive_remote_sql_user'],
																						 'sql_pass'		        => $this->settings['archive_remote_sql_pass'],
																						 'sql_host'		        => $this->settings['archive_remote_sql_host'],
																						 'sql_charset'	        => $this->settings['archive_remote_sql_charset'],
																						 'sql_tbl_prefix'       => $this->settings['sql_tbl_prefix'],
																						 'catchConnectionError' => true ) );
				
				
				$this->remoteDB = $this->registry->dbFunctions()->getDB('remoteArchive');
				
				/* Check for connection issue */
				if ( $this->remoteDB->error )
				{
					$this->connectError = $this->remoteDB->error;
					$this->remoteDB     = null;
					
					$this->registry->dbFunctions()->unsetDB('remoteArchive');
				}
			}
			else
			{
				$this->remoteDB = $this->registry->dbFunctions()->getDB('remoteArchive');
				
				/* Check for connection issue */
				if ( $this->remoteDB->error )
				{
					$this->connectError = $this->remoteDB->error;
					$this->remoteDB     = null;
					
					$this->registry->dbFunctions()->unsetDB('remoteArchive');
				}
			}
		}
		else
		{
			$this->remoteDB = $this->DB;
		}
	}
	
	/**
	 * Fetch a topic's post count
	 * @param	int		Topic ID
	 * @param	array	Masks [visible, hidden, sdelete]
	 * @return  int
	 */
	public function getPostCount( $tid, $masks )
	{
		/* Fetch replies */
		$_queued	= $this->registry->class_forums->fetchPostHiddenQuery( $masks, 'archive_' );
		$posts		= $this->remoteDB->buildAndFetch( array( 'select' => 'COUNT(*) as posts', 'from' => 'forums_archive_posts', 'where' => "archive_topic_id={$tid} and {$_queued}" ) );
	
		return intval( $posts['posts'] );
	}
	
	/**
	 * getPosts
	 * Fetches posts based on different critera
	 * @param	array	Filters (see below for specifics)
	 * @return	array
	 *
	 * FILTERS:
	 * topicId			Get posts matching the (array) topic ids, (int) topic ID
	 * forumId			Get posts matching the (array) forum ids, (int) forum ID
	 * notForumId		Get posts NOT matching the (array) forum ids, (int) forum ID
	 * postId			Get posts matching the (array) post ids, (int) post id
	 * memberData		Set memberData (this->memberData is used otherwise)
	 * onlyViewable		Set whether this member can view them or not. (default is true ) NOTE: Will not check to see if parent topic is viewable!
	 * onlyVisible 		Set whether to skip unapproved posts where permission allows (default is true)
	 * postType			array of 'sdelete', 'visible', 'hidden', 'pdeleted' (if you specify these, permission checks are NOT performed)
	 * sortField		Sort key (date, pid, etc)
	 * sortOrder		asc/desc
	 * pidIsGreater		Where PID is greater than x
	 * dateIsGreater	Where DATE is greater than UNIX
	 * skipForumCheck	Skips the forum ID IN list check to ensure you have access to view (good for when using perms elsewhere)
	 * parse			Parses post content
	 * limit, offset	Limit the amount of results in the returned query
	 * getCount			fetch count without limit
	 * getCountOnly	    fetch count and return only
	 *
	 */
	public function getPosts( $filters )
	{
		/* init */
		$filters	= $this->_setPostFilters( $filters );
		$limit	    = null;
		$posts	    = array();
		$where		= array();
		$memberData	= ( ! empty( $filters['memberData'] ) && is_array( $filters['memberData'] ) ) ? $filters['memberData'] : $this->memberData;
	
		/* Posts */
		if ( ! empty( $filters['postId'] ) )
		{
			$filters['postId'] = ( ! is_array( $filters['postId'] ) ) ? array( $filters['postId'] ) : $filters['postId'];
			$where[] = "p.archive_id IN (" . implode( ',',$filters['postId'] ) . ")";
		}
	
		/* Topics */
		if ( ! empty( $filters['topicId'] ) )
		{
			$filters['topicId'] = ( ! is_array( $filters['topicId'] ) ) ? array( $filters['topicId'] ) : $filters['topicId'];
			$where[] = "p.archive_topic_id IN (" . implode( ',',$filters['topicId'] ) . ")";
		}
		
		/* Author Ids */
		if ( ! empty( $filters['authorId'] ) )
		{
			$filters['authorId'] = ( ! is_array( $filters['authorId'] ) ) ? array( $filters['authorId'] ) : $filters['authorId'];
			$where[] = "p.archive_author_id IN (" . implode( ',',$filters['authorId'] ) . ")";
		}
		
		/* PID is greater */
		if ( ! empty( $filters['pidIsGreater'] ) )
		{
			$where[] = "p.archive_id > " . intval( $filters['pidIsGreater'] );
		}
	
		/* PID is less */
		if ( ! empty( $filters['pidIsLess'] ) )
		{
			$where[] = "p.archive_id < " . intval( $filters['pidIsLess'] );
		}
	
		/* Date is greater */
		if ( ! empty( $filters['dateIsGreater'] ) )
		{
			$where[] = "p.archive_content_date > " . intval( $filters['dateIsGreater'] );
		}
		
		/* Forum Ids */
		if ( ! empty( $filters['forumId'] ) )
		{
			$filters['forumId'] = ( ! is_array( $filters['forumId'] ) ) ? array( $filters['forumId'] ) : $filters['forumId'];
			$where[] = "archive_forum_id IN (" . implode( ',', $filters['forumId'] ) . ")";
		}
			
		/* Not Forum Ids */
		if ( ! empty( $filters['notForumId'] ) )
		{
			$filters['notForumId'] = ( ! is_array( $filters['notForumId'] ) ) ? array( $filters['notForumId'] ) : $filters['notForumId'];
			$where[] = "archive_forum_id NOT IN (" . implode( ',', $filters['notForumId'] ) . ")";
		}
		
		/* Visible / specific filters */
		if ( ! empty( $filters['postType'] ) && is_array( $filters['postType'] ) )
		{
			$where[] = $this->registry->class_forums->fetchPostHiddenQuery( $filters['postType'], 'p.archive_' );
		}
		else
		{
			if ( isset( $filters['onlyViewable'] ) && $filters['onlyViewable'] === true && empty( $filters['onlyVisible'] ) )
			{
				$_perms = array( 'visible' );
	
				if ( $this->registry->getClass('class_forums')->canSeeSoftDeletedPosts( false ) )
				{
					$_perms[] = 'sdelete';
				}
	
				if ( $this->registry->getClass('class_forums')->canQueuePosts( false ) )
				{
					$_perms[] = 'hidden';
				}
	
				$where[] = $this->registry->class_forums->fetchPostHiddenQuery( $_perms, 'p.archive_' );
			}
			else
			{
				/* Show visible only */
				$where[] = $this->registry->class_forums->fetchPostHiddenQuery( array( 'visible' ), 'p.archive_' );
			}
		}
	
		/* Forum ID check? */
		if ( ( isset( $filters['onlyViewable'] ) && $filters['onlyViewable'] === true ) && ( empty($filters['skipForumCheck']) OR $filters['skipForumCheck'] === false ) )
		{
			if ( empty( $filters['forumId'] ) && empty( $filters['notForumId'] ) )
			{
				$forumIds = $this->registry->class_forums->fetchSearchableForumIds( $memberData['member_id'] );
	
				if ( ! count( $forumIds ) )
				{
					return $where;
				}
	
				$where[] = "archive_forum_id IN (" . implode( ",", $forumIds ) . ")";
			}
		}
	
		/* Did we want a count also? */
		if ( ! empty( $filters['getCount'] ) || ! empty( $filters['getCountOnly'] ) )
		{
			$count	= $this->remoteDB->buildAndFetch( array( 'select'	=> 'COUNT(*) as posts',
															 'from'		=> 'forums_archive_posts p',
															 'where'	=> implode( ' AND ', $where ) ) );
	
			$this->_countPosts = $count['posts'];
			
			if ( $filters['getCountOnly'] )
			{
				return $this->_countPosts;
			}
		}
	
		/* Offset, limit */
		if ( isset( $filters['offset'] ) OR isset( $filters['limit'] ) )
		{
			if ( $filters['offset'] > 0 || $filters['limit'] > 0 )
			{
				$limit = array( intval( $filters['offset'] ), intval( $filters['limit'] ) );
			}
		}
	
		/* Order */
		if ( ! empty( $filters['sortField'] ) )
		{
			if ( strstr( $filters['sortField'], '.' ) )
			{
				$order = $filters['sortField'];
			}
			else
			{
				$order = 'p.' . $filters['sortField'];
			}
				
			if ( isset( $filters['sortOrder'] ) )
			{
				$order .= ' ' . $filters['sortOrder'];
			}
		}
	
		/* Fetch them */
		$this->remoteDB->build( array(  'select'   => 'p.*, p.archive_ip_address as post_ip',
										'from'     => 'forums_archive_posts p',
										'where'    => implode( ' AND ', $where ),
										'limit'	  => $limit ? $limit : '',
										'order'	  => $order ? $order : 'p.archive_id asc' ) );
	
		$o = $this->remoteDB->execute();
	
		while( $post = $this->remoteDB->fetch( $o ) )
		{
			/* Post IP overrides member */
			$post['ip_address'] = $post['post_ip'];
				
			$post = $this->registry->topics->archivePostToNativeFields( $post );
				
			$posts[ $post['pid'] ] = $post;
		}
	
		return $posts;
	}
	
	/**
	 * Write single entry to DB
	 * @param	array	INTS
	 */
	public function getData( $data=array() )
	{
		if ( ! $this->remoteDB )
		{
			return;
		}
		
		/* Init */
		$topicData      = $data['parentData'];
		$forumData      = $this->registry->getClass('class_forums')->getForumById( $topicData['forum_id'] );
		$permissionData = $this->registry->getClass('topics')->getPermissionData();
		$first          = $data['offset'];
		$end			= $data['limit'];
		$fields			= $this->getFields();
		
		/* Default - just see all visible posts */
		$queued_query_bit = ' AND ' . $this->registry->class_forums->fetchPostHiddenQuery('visible', 'archive_');

		/* Can we deal with hidden posts? */
		if ( $this->registry->class_forums->canQueuePosts( $topicData['forum_id'] ) )
		{
			if ( $permissionData['softDeleteSee'] )
			{
				/* See queued and soft deleted */
				$queued_query_bit = ' AND ' . $this->registry->class_forums->fetchPostHiddenQuery( array( 'visible', 'hidden', 'sdeleted' ), 'archive_' );
			}
			else
			{
				/* Otherwise, see queued and approved */
				$queued_query_bit = ' AND ' . $this->registry->class_forums->fetchPostHiddenQuery( array( 'visible', 'hidden' ), 'archive_' );
			}
		}
		else
		{
			/* We cannot see hidden posts */
			if ( $permissionData['softDeleteSee'] )
			{
				/* See queued and soft deleted */
				$queued_query_bit = ' AND ' . $this->registry->class_forums->fetchPostHiddenQuery( array('approved', 'sdeleted'), 'archive_' );
			}
		}
		
		/* Set up */
		/* Ignored Users */
		$ignored_users = array();
		
		foreach( $this->member->ignored_users as $_i )
		{
			if ( $_i['ignore_topics'] )
			{
				$ignored_users[] = $_i['ignore_ignore_id'];
			}
		}
		
		/* Format */
		$this->registry->getClass('topics')->setTopicData('adCodeSet'   , false );
		$this->registry->getClass('topics')->setTopicData('ignoredUsers', $ignored_users );
		$posts  = array();
		$cached = array();
		
		/* Get posts separately */
		if ( IPSLib::isUsingRemoteArchiveDB() )
		{
			/* Get posts */
			$this->remoteDB->build( array( 'select'   => '*',
									 	   'from'	  => 'forums_archive_posts',
									 	   'where'    => 'archive_topic_id='.$topicData['tid'] . $queued_query_bit,
									 	   'order'    => $fields[ $data['sortKey'] ] . ' ' . $data['sortOrder'],
									 	   'limit'    => array( $first, $end ) ) );
	
			$ab = $this->remoteDB->execute();
			
			$mids  = array();
			while( $p = $this->remoteDB->fetch( $ab ) )
			{
				$posts[ $p['archive_id'] ]       = $p;
				$mids[ $p['archive_author_id'] ] = $p['archive_author_id'];
			}
			
			if ( count( $posts ) )
			{
				/* Get cached posts */
				$this->DB->build( array( 'select' => 'cache_content_id, cache_content',
										 'from'   => 'content_cache_posts',
										 'where'  => 'cache_content_id IN ('. implode( ',', array_keys( $posts ) ) . ')' ) );
				
				$this->DB->execute();
				
				while( $row = $this->DB->fetch() )
				{
					$cached[ $row['cache_content_id'] ] = $row['cache_content'];
				}
				
				/* Get members */
				$members = IPSMember::load( $mids, 'all' );
					
				foreach( $posts as $pid => $pdata )
				{
					$pdata['member_id'] = $pdata['archive_author_id'];
					
					if ( $data['goNative'] )
					{
						$pdata = $this->archiveToNativeFields( $pdata );
					}
					
					if ( $pdata['author_id'] )
					{
						$members[ $pdata['author_id'] ]['cache_content_sig'] = $members[ $pdata['author_id'] ]['cache_content'];
						unset( $members[ $pdata['author_id'] ]['cache_content'] );
						$posts[ $pid ] = array_merge( $members[ $pdata['author_id'] ], $pdata );
					}
					else
					{
						$posts[ $pid ] = $pdata;
					}
					
					/* Cached */
					if ( isset( $cached[ $pid ] ) )
					{
						$posts[ $pid ]['cache_content'] = $cached[ $pid ];
					}
				}
			}
		}
		else
		{
			/* Joins */
			$_post_joins = array( array( 'select' => 'm.member_id as mid,m.name,m.member_group_id,m.email,m.joined,m.posts, m.last_visit, m.last_activity,m.login_anonymous,m.title as member_title, m.warn_level, m.warn_lastwarn, m.members_display_name, m.members_seo_name, m.has_gallery, m.has_blog, m.members_bitoptions,m.mgroup_others',
										 'from'   => array( 'members' => 'm' ),
										 'where'  => 'm.member_id=a.archive_author_id',
										 'type'   => 'left' ),
								  array( 'select' => 'pp.*',
										 'from'   => array( 'profile_portal' => 'pp' ),
										 'where'  => 'm.member_id=pp.pp_member_id',
										 'type'   => 'left' ) );
			
			/* Cache? */
			if ( IPSContentCache::isEnabled() )
			{
				if ( IPSContentCache::fetchSettingValue('post') )
				{
					$_post_joins[] = IPSContentCache::join( 'post', 'a.archive_id' );
				}
				
				if ( IPSContentCache::fetchSettingValue('sig') )
				{
					$_post_joins[] = IPSContentCache::join( 'sig' , 'm.member_id', 'ccb', 'left', 'ccb.cache_content as cache_content_sig, ccb.cache_updated as cache_updated_sig' );
				}
			}
			
			/* Get posts */
			$this->remoteDB->build( array( 'select'   => 'a.*',
									 	   'from'	  => array( 'forums_archive_posts' => 'a' ),
									 	   'where'    => 'archive_topic_id='.$topicData['tid'] . $queued_query_bit,
									 	   'order'    => $fields[ $data['sortKey'] ] . ' ' . $data['sortOrder'],
									 	   'limit'    => array( $first, $end ),
									 	   'add_join' => $_post_joins ) );
	
			$oq = $this->remoteDB->execute();
			
			while ( $row = $this->remoteDB->fetch( $oq ) )
			{
				$row['member_id'] = $row['archive_author_id'];
				
				if ( $data['goNative'] )
				{
					$row = $this->archiveToNativeFields( $row );
				}
				
				$posts[ $row['pid'] ] = $row;
			}
		}	
		
		/* Return */
		return $posts;
	}
	
	/**
	 * Set post filters
	 * Takes user input and cleans it up a bit
	 *
	 * @param	array		Incoming filters
	 * @return	array
	 */
	protected function _setPostFilters( $filters )
	{
		$filters['sortOrder']		= ( isset( $filters['sortOrder'] ) )	? $filters['sortOrder']	: '';
		$filters['sortField']		= ( isset( $filters['sortField'] ) )	? $filters['sortField']	: '';
		$filters['offset']			= ( isset( $filters['offset'] ) )		? $filters['offset']	: '';
		$filters['limit']			= ( isset( $filters['limit'] ) )		? $filters['limit']		: '';
		$filters['isVisible']		= ( isset( $filters['isVisible'] ) )	? $filters['isVisible']	: '';
	
		switch( $filters['sortOrder'] )
		{
			default:
			case 'desc':
			case 'descending':
			case 'z-a':
				$filters['sortOrder'] = 'desc';
				break;
			case 'asc':
			case 'ascending':
			case 'a-z':
				$filters['sortOrder'] = 'asc';
				break;
		}
	
		/* Do some set up */
		switch( $filters['sortField'] )
		{
			case 'date':
			case 'time':
			case 'post_date':
				$filters['sortField']  = 'archive_content_date';
				break;
			case 'pid':
			case 'id':
				$filters['sortField']  = 'archive_id';
				break;
		}
	
		/* Others */
		$filters['offset']       = intval( $filters['offset'] );
		$filters['limit']        = intval( $filters['limit'] );
		$filters['unixCutOff']   = ( ! empty( $filters['unixCutOff'] ) ) ? intval( $filters['unixCutOff'] ) : 0;
	
		/* So we don't have to do this twice */
		$filters['_cleaned']   = true;
	
		return $filters;
	}
	
}
