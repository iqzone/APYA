<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Basic Forum Search
 * Last Updated: $Date: 2012-06-12 08:43:54 -0400 (Tue, 12 Jun 2012) $
 * </pre>
 *
 * @author 		$Author: mmecham $
 * @copyright	© 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10912 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class search_engine_forums extends search_engine
{
	/**
	 * Forum ids to search - set in secondary methods
	 * 
	 * @var	array
	 */
 	protected $searchForumIds	= array();
 	
	/**
	 * Constructor
	 * 
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Hard limit - not used in Sphinx but may need to revisit if we bust IN()s */
		//IPSSearchRegistry::set('set.hardLimit', ( ipsRegistry::$settings['search_hardlimit'] ) ? ipsRegistry::$settings['search_hardlimit'] : 200 );
		
		/* Get class forums, used for displaying forum names on results */
		if ( ipsRegistry::isClassLoaded('class_forums') !== TRUE )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php", 'class_forums', 'forums' );
			ipsRegistry::setClass( 'class_forums', new $classToLoad( ipsRegistry::instance() ) );
			ipsRegistry::getClass('class_forums')->strip_invisible = 1;
			ipsRegistry::getClass('class_forums')->forumsInit();
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
			$this->table['forums_search_posts_main']  = 'forums_search_archive_main';
			$this->table['forums_search_posts_delta'] = 'forums_search_archive_delta';
			
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
								  'post_edit_reason' => 'post_edit_reason',
								  'forums_search_posts_main'  => 'forums_search_posts_main',
								  'forums_search_posts_delta' => 'forums_search_posts_delta' );
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
		$start		        = intval( IPSSearchRegistry::get('in.start') );
		$perPage            = IPSSearchRegistry::get('opt.search_per_page');
		$sort_by            = IPSSearchRegistry::get('in.search_sort_by');
		$sort_order         = IPSSearchRegistry::get('in.search_sort_order');
		$search_term        = IPSSearchRegistry::get('in.clean_search_term');
		$search_type		= IPSSearchRegistry::get('opt.searchType');
		$search_tags        = IPSSearchRegistry::get('in.raw_search_tags');
		$search_ids		    = array();
		$groupby			= false;
		$cType              = IPSSearchRegistry::get('contextual.type');
		$cId		        = IPSSearchRegistry::get('contextual.id' );
		
		/* Contextual search */
		if ( $cType == 'topic' )
		{
			$search_type	= 'content';
			IPSSearchRegistry::set('opt.searchType', 'content');
			IPSSearchRegistry::set('opt.noPostPreview', false);
		}
		
		/* If searching tags, we don't show a preview */
		if( $search_tags )
		{
			IPSSearchRegistry::set('opt.noPostPreview', true);
			IPSSearchRegistry::set('opt.searchType', 'titles');
			$search_type = 'titles';
		}
		
		/* Permissions */
		$permissions						= array();
		$permissions['TopicSoftDeleteSee']  = $this->registry->getClass('class_forums')->canSeeSoftDeletedTopics( 0 );
		$permissions['canQueue']			= $this->registry->getClass('class_forums')->canQueuePosts( 0 );
		$permissions['PostSoftDeleteSee']   = $this->registry->getClass('class_forums')->canSeeSoftDeletedPosts( 0 );
		$permissions['SoftDeleteReason']    = $this->registry->getClass('class_forums')->canSeeSoftDeleteReason( 0 );
		$permissions['SoftDeleteContent']   = $this->registry->getClass('class_forums')->canSeeSoftDeleteContent( 0 );
		
		/* Sorting */
		switch( $sort_by )
		{
			default:
			case 'date':
				$sortKey     = 'last_post';
				$sortKeyPost = 'post_date';
			break;
			case 'title':
				$sortKeyPost = $sortKey  = 'tordinal';
			break;
			case 'posts':
				$sortKeyPost = $sortKey  = 'posts';
			break;
			case 'views':
				$sortKeyPost = $sortKey  = 'views';
			break;
		}
					
		/* Limit Results */
		$this->sphinxClient->SetLimits( intval($start), $perPage );
		
		/* Loop through the forums and build a list of forums we're allowed access to */
		if( is_array($this->searchForumIds) AND count($this->searchForumIds) )
		{
			$forumIdsOk	= $this->searchForumIds;
		}
		else
		{
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
	
						if ( ! $data['sub_can_post'] OR ! $data['can_view_others'] AND !$this->memberData['g_access_cp'] )
						{
							$forumIdsBad[] = $forum_id;
							continue;
						}
					
						$forumIdsOk[] = $forum_id;
					}
				}
			}
		}
		
		if ( ! count($forumIdsOk) )
		{
			/* Get list of good forum IDs */
			$forumIdsOk = $this->registry->class_forums->fetchSearchableForumIds();
		}
		
		/* Add allowed forums */
		$forumIdsOk = ( count( $forumIdsOk ) ) ? $forumIdsOk : array( 0 => 0 );
		
		/* Contextual */
		if ( $cType == 'forum' AND $cId AND in_array( $cId, $forumIdsOk ) )
		{
			$this->sphinxClient->SetFilter( 'forum_id', array( $cId ) );
		}
		else
		{
			$this->sphinxClient->SetFilter( 'forum_id', $forumIdsOk );
		}
		
		/* Topic contextual */
		if ( $cType == 'topic' AND $cId )
		{
			$this->sphinxClient->SetFilter( 'tid', array( $cId ) );
		}
		
		/* Just show topics started by a member if we're search titles + member (#35289) */
		if ( $search_type == 'titles' && IPSSearchRegistry::get('in.search_author_id') )
		{
			$this->sphinxClient->SetFilter( 'starter_id', array( IPSSearchRegistry::get('in.search_author_id') ) );
		}
		
		/* Exclude some items */
		if ( $search_type != 'titles' )
		{
			if ( $permissions['SoftDeleteContent'] AND $permissions['PostSoftDeleteSee'] AND $permissions['canQueue'] )
			{
				$this->sphinxClient->SetFilter( 'queued', array( 0,1,2 ) );
			}
			else if ( $permissions['SoftDeleteContent'] AND $permissions['PostSoftDeleteSee'] )
			{
				$this->sphinxClient->SetFilter( 'queued', array( 0,2 ) );
			}
			else if ( $permissions['canQueue'] )
			{
				$this->sphinxClient->SetFilter( 'queued', array( 0,1 ) );
			}
			else
			{
				$this->sphinxClient->SetFilter( 'queued', array( 0 ) );
			}
		}
		
		if ( $permissions['SoftDeleteContent'] AND $permissions['TopicSoftDeleteSee'] AND $permissions['canQueue'] )
		{
			$this->sphinxClient->SetFilter( 'approved'    , array( 0,1 ) );
			$this->sphinxClient->SetFilter( 'soft_deleted', array( 0,1 ) );
		}
		else if ( $permissions['SoftDeleteContent'] AND $permissions['TopicSoftDeleteSee'] )
		{
			$this->sphinxClient->SetFilter( 'approved', array( 1 ) );
			$this->sphinxClient->SetFilter( 'soft_deleted', array( 0,1 ) );
		}
		else if ( $permissions['canQueue'] )
		{
			$this->sphinxClient->SetFilter( 'soft_deleted', array( 0 ) );
			$this->sphinxClient->SetFilter( 'approved', array( 0,1 ) );
		}
		else
		{
			$this->sphinxClient->SetFilter( 'soft_deleted', array( 0 ) );
			$this->sphinxClient->SetFilter( 'approved', array( 1 ) );
		}
		
		/* Archive search? */
		if ( $this->searchArchives )
		{
			$this->sphinxClient->SetFilter( 'archive_status', array( 1 ) );
		}
		else
		{
			$this->sphinxClient->SetFilter( 'archive_status', array( 0 ) );
		}

		/* Additional filters */
		if ( IPSSearchRegistry::get('opt.pCount') )
		{
			$this->sphinxClient->SetFilterRange( 'posts', intval( IPSSearchRegistry::get('opt.pCount') ), 1500000000 );
		}
		
		if ( IPSSearchRegistry::get('opt.pViews') )
		{
			$this->sphinxClient->SetFilterRange( 'views', intval( IPSSearchRegistry::get('opt.pViews') ), 1500000000 );
		}
		
		/* Date limit */
		if ( $this->search_begin_timestamp )
		{
			if ( ! $this->search_end_timestamp )
			{
				$this->search_end_timestamp = time() + 100;
			}
			
			if ( $search_type == 'titles' )
			{
				$this->sphinxClient->SetFilterRange( 'start_date', $this->search_begin_timestamp, $this->search_end_timestamp );
			}
			else
			{
				$this->sphinxClient->SetFilterRange( 'post_date', $this->search_begin_timestamp, $this->search_end_timestamp );
			}
		}
	
		if ( IPSSearchRegistry::get('opt.noPostPreview') OR $search_type == 'titles' )
		{
			$groupby = true;
		}
		
		/* Check tags */
		$tagIds = array();
		
		if ( $search_tags && $this->settings['tags_enabled'] )
		{
			$tags = $this->registry->tags->search( $search_tags, array( 'meta_parent_id' => $this->request['search_app_filters']['forums']['forums'],
																		'meta_app'		 => 'forums',
																		'meta_area'		 => 'topics',
																		'isViewable'	 => true,
																		'sortOrder'		 => $sort_order ) );
			
			foreach( $tags as $id => $data )
			{
				$tagIds[] = $data['tag_id'];
			}
			
			if ( $search_term )
			{
				if ( count( $tagIds ) )
				{
					$this->sphinxClient->SetFilter( 'tag_id', $tagIds );
				}
			}
			else
			{
				if ( ! $tagIds )
				{
					return array( 'count' => 0, 'resultSet' => array() );
				}
				else
				{
					/* We have tags but no search term, so just return the tids now */
					IPSSearchRegistry::set('set.returnType', 'tids' );
					$tids = array();
					
					$this->DB->build( array( 'select'   => 'c.tag_meta_id',
											 'from'     => array( 'core_tags' => 'c' ),
											 'where'    => 'c.tag_id IN (' . implode( ',', $tagIds ) . ')',
											 'order'    => 'c.tag_added DESC',
											 'limit'    => array( 0, 1000 ),
											 'add_join' => array( array( "select" => 't.title, t.posts, t.views, t.last_post', 
																		 "from"   => array( 'topics' => 't' ), 
																		 "where"  => "c.tag_meta_id=t.tid", 
																		 'type'   => 'left' ) ) ) );

					$this->DB->execute();
										
					$rows = array();

					while( $row = $this->DB->fetch() )
					{
						$rows[] = $row;
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
					
					IPSSearch::$ask = 'last_post';
					IPSSearch::$aso = strtolower( $sort_order );
					IPSSearch::$ast = 'numerical';
					usort( $rows, array("IPSSearch", "usort") );

					$c   = 0;
					$got = 0;
					
					foreach ($rows as $row) {
						$c++;
						
						if ( IPSSearchRegistry::get('in.start') AND IPSSearchRegistry::get('in.start') >= $c )
						{
							continue;
						}
					
						$tids[] = $row['tag_meta_id'];	
									
						$got++;
						
						/* Done? */
						if ( IPSSearchRegistry::get('opt.search_per_page') AND $got >= IPSSearchRegistry::get('opt.search_per_page') )
						{
							break;
						}
					}
					
					return array( 'count' => count( $tids ), 'resultSet' => $tids );
				}
			}
		}

		/* Set sort order */
		if ( $sort_order == 'asc' )
		{
			$this->sphinxClient->SetSortMode( SPH_SORT_ATTR_ASC, $sortKey );
		}
		else
		{
			$this->sphinxClient->SetSortMode( SPH_SORT_ATTR_DESC, $sortKey );
		}

		/* Generate sphinx search query */
		switch( $search_type )
		{
			case 'titles':
				/* Group by */
				if ( $groupby )
				{
					/* @link http://community.invisionpower.com/topic/335036-sphinx-vncactive-content-sorting/ 
					   @link http://community.invisionpower.com/tracker/issue-34481-sphinx-sorting-broken/*/
					$this->sphinxClient->SetGroupDistinct ( "tid" );
					$this->sphinxClient->SetGroupBy( 'tid', SPH_GROUPBY_ATTR, $sortKey . ' ' . $sort_order );				
				}
				
				$_s = ( $search_term ) ? '@title ' . $search_term : '';
			break;
			
			case 'content':
				/* Group by */
				if ( $groupby )
				{
					$this->sphinxClient->SetSortMode( SPH_SORT_EXTENDED, $sortKeyPost . ' DESC' );
					$this->sphinxClient->SetGroupBy( 'tid', SPH_GROUPBY_ATTR, $sortKey . ' ' . $sort_order );
					
					IPSSearchRegistry::set('set.searchResultType', 'both' );
				}
				
			
				$_s = ( $search_term ) ? '@'. $this->table['post'] . ' ' . $search_term : '';
			break;
			
			case 'both':
			default:
				/* Group by */
				if ( $groupby )
				{
					$this->sphinxClient->SetSortMode( SPH_SORT_EXTENDED, $sortKeyPost . ' DESC' );
					$this->sphinxClient->SetGroupBy( 'tid', SPH_GROUPBY_ATTR, $sortKey . ' ' . $sort_order );
				}
				
				IPSSearchRegistry::set('set.searchResultType', 'both' );
				
				$_s = ( $search_term AND strstr( $search_term, '"' ) ) ? '@'. $this->table['post'] . ' ' . $search_term . ' | @title ' . $search_term : ( $search_term ? '@('.$this->table['post'].',title) ' . $search_term : '' );
			break;
		}
				
		/* Perform search */
		$result = $this->sphinxClient->Query( $_s, $this->settings['sphinx_prefix'] . $this->table['forums_search_posts_main']. ', ' . $this->settings['sphinx_prefix'] . $this->table['forums_search_posts_delta'] );
		
		/* Log errors */
		$this->logSphinxWarnings();
		
		/* Get result ids */
		if ( is_array( $result['matches'] ) && count( $result['matches'] ) )
		{
			$c = 0;
			
			foreach( $result['matches'] as $res )
			{
				$search_ids[] = ( $search_type == 'titles' && IPSSearchRegistry::get('set.searchResultType') != 'both' ) ? $res['attrs']['tid'] : $res['attrs']['search_id'];
			}
		}
		
		/* Set return type */
		if ( $search_type == 'titles' && IPSSearchRegistry::get('set.searchResultType') != 'both' )
		{ 
			IPSSearchRegistry::set('set.returnType', 'tids' );
		}
		else
		{
			IPSSearchRegistry::set('set.returnType', 'pids' );
		}

		/* Return it */
		return array( 'count' => intval( $result['total_found'] ) > 1000 ? 1000 : $result['total_found'], 'resultSet' => $search_ids );
	}
	
	/**
	 * Perform the search
	 * Populates $this->_count and $this->_results
	 *
	 * @return	array
	 */
	public function viewUserContent( $member )
	{
		/* Bit of init */
		$time			= ( $member['last_post'] ? $member['last_post'] : time() ) - ( 86400 * intval( $this->settings['search_ucontent_days'] ) );
		$sort_by		= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order		= IPSSearchRegistry::get('in.search_sort_order');

		switch( IPSSearchRegistry::get('in.userMode') )
		{
			default:
			case 'all': 
				IPSSearchRegistry::set('opt.searchType', 'both');
				IPSSearchRegistry::set('opt.noPostPreview'  , true );
			break;
			case 'title': 
				IPSSearchRegistry::set('opt.searchType', 'titles');
				IPSSearchRegistry::set('opt.noPostPreview'  , true );
			break;	
			case 'content': 
				IPSSearchRegistry::set('opt.searchType', 'content');
				IPSSearchRegistry::set('opt.noPostPreview'  , false );
			break;	
		}
		
		/* Sorting */
		switch( $sort_by )
		{
			default:
			case 'date':
				$sortKey     = 'last_post';
				$sortKeyPost = 'post_date';
			break;
			case 'title':
				$sortKeyPost = $sortKey  = 'tordinal';
			break;
			case 'posts':
				$sortKeyPost = $sortKey  = 'posts';
			break;
			case 'views':
				$sortKeyPost = $sortKey  = 'views';
			break;
		}
		
		/* Init */
		$start		= IPSSearchRegistry::get('in.start');
		$perPage    = IPSSearchRegistry::get('opt.search_per_page');
			
		//IPSSearchRegistry::set('in.search_sort_by'   , 'date' );
		//IPSSearchRegistry::set('in.search_sort_order', 'desc' );
		
		/* Limit Results */
		$this->sphinxClient->SetLimits( intval($start), intval($perPage) );
		
		/* Get list of good forum IDs */
		$forumIdsOk	= $this->registry->class_forums->fetchSearchableForumIds( $this->memberData['member_id'] );
		
		$this->sphinxClient->SetFilter( 'forum_id', $forumIdsOk );
		
		/* what we doing? */
		if ( IPSSearchRegistry::get('in.userMode') != 'title' )
		{
			$this->sphinxClient->SetFilter( 'author_id', array( intval( $member['member_id'] ) ) );
		}
		else
		{
			$this->sphinxClient->SetFilter( 'starter_id', array( intval( $member['member_id'] ) ) );
		}
		
		if ( $this->settings['search_ucontent_days'] )
		{
			$this->sphinxClient->SetFilterRange( 'post_date', $time, time() );
		}
				
		/* Set up perms */
		$permissions						= array();
		$permissions['TopicSoftDeleteSee']  = $this->registry->getClass('class_forums')->canSeeSoftDeletedTopics( 0 );
		$permissions['canQueue']			= $this->registry->getClass('class_forums')->canQueuePosts( 0 );
		$permissions['PostSoftDeleteSee']   = $this->registry->getClass('class_forums')->canSeeSoftDeletedPosts( 0 );
		$permissions['SoftDeleteReason']    = $this->registry->getClass('class_forums')->canSeeSoftDeleteReason( 0 );
		$permissions['SoftDeleteContent']   = $this->registry->getClass('class_forums')->canSeeSoftDeleteContent( 0 );
		
		/* Exclude some items */
		if ( IPSSearchRegistry::get('in.userMode') != 'title' )
		{
			if ( $permissions['SoftDeleteContent'] AND $permissions['PostSoftDeleteSee'] AND $permissions['canQueue'] )
			{
				$this->sphinxClient->SetFilter( 'queued', array( 0,1,2 ) );
			}
			else if ( $permissions['SoftDeleteContent'] AND $permissions['PostSoftDeleteSee'] )
			{
				$this->sphinxClient->SetFilter( 'queued', array( 0,2 ) );
			}
			else if ( $permissions['canQueue'] )
			{
				$this->sphinxClient->SetFilter( 'queued', array( 0,1 ) );
			}
			else
			{
				$this->sphinxClient->SetFilter( 'queued', array( 0 ) );
			}
		}
		else
		{
			if ( $permissions['SoftDeleteContent'] AND $permissions['TopicSoftDeleteSee'] AND $permissions['canQueue'] )
			{
				$this->sphinxClient->SetFilter( 'approved'    , array( 0,1 ) );
				$this->sphinxClient->SetFilter( 'soft_deleted', array( 0,1 ) );
			}
			else if ( $permissions['SoftDeleteContent'] AND $permissions['TopicSoftDeleteSee'] )
			{
				$this->sphinxClient->SetFilter( 'approved', array( 1 ) );
				$this->sphinxClient->SetFilter( 'soft_deleted', array( 0,1 ) );
			}
			else if ( $permissions['canQueue'] )
			{
				$this->sphinxClient->SetFilter( 'soft_deleted', array( 0 ) );
				$this->sphinxClient->SetFilter( 'approved', array( 0,1 ) );
			}
			else
			{
				$this->sphinxClient->SetFilter( 'soft_deleted', array( 0 ) );
				$this->sphinxClient->SetFilter( 'approved', array( 1 ) );
			}
		}
		
		/* Perform 'search' */
		/* We have to sort by topic last post date for 'both' otherwise the topics will appear in random order.  Even though it's correctly ordering by YOUR post
			date, the user just sees the list of topics with random last post date ordering.  In a future version it would be nice to change the last post column
			on 'view user content' to show something like "you last posted on x date" instead of showing the topic last poster and last post date.
			@link	http://community.invisionpower.com/tracker/issue-33692-my-content-second-page */
		if ( IPSSearchRegistry::get('in.userMode') == 'title' OR IPSSearchRegistry::get('in.userMode') == 'all' )
		{
			/* Set return type */
			IPSSearchRegistry::set('set.returnType', 'tids' );
		
			$this->sphinxClient->SetGroupDistinct ( "tid" );
			$this->sphinxClient->SetGroupBy( 'tid', SPH_GROUPBY_ATTR, $sortKey . ' ' . $sort_order );
			
			if ( $sort_order == 'asc' )
			{
				$this->sphinxClient->SetSortMode( SPH_SORT_ATTR_ASC, $sortKey );
			}
			else
			{
				$this->sphinxClient->SetSortMode( SPH_SORT_ATTR_DESC, $sortKey );
			}
			
			$result = $this->sphinxClient->Query( '', $this->settings['sphinx_prefix'] . 'forums_search_posts_main,' . $this->settings['sphinx_prefix'] . 'forums_search_posts_delta' );
		}
		else
		{
			/* Set return type */
			if( IPSSearchRegistry::get('in.userMode') == 'all' )
			{
				IPSSearchRegistry::set('set.returnType', 'tids' );
			}
			else
			{
				IPSSearchRegistry::set('set.returnType', 'pids' );
			}
			
			if ( $sort_order == 'asc' )
			{
				$this->sphinxClient->SetSortMode( SPH_SORT_ATTR_ASC, $sortKeyPost );
			}
			else
			{
				$this->sphinxClient->SetSortMode( SPH_SORT_ATTR_DESC, $sortKeyPost );
			}
			
			if ( IPSSearchRegistry::get('in.userMode') != 'content' )
			{
				$this->sphinxClient->SetGroupBy( 'tid', SPH_GROUPBY_ATTR, $sortKeyPost . ' ' . $sort_order );
			}
			
			$result = $this->sphinxClient->Query(  '', $this->settings['sphinx_prefix'] . 'forums_search_posts_main,' . $this->settings['sphinx_prefix'] . 'forums_search_posts_delta' );
		}
		
		/* Log warnings */
		$this->logSphinxWarnings();

		/* Get result ids */
		if ( is_array( $result['matches'] ) && count( $result['matches'] ) )
		{
			foreach( $result['matches'] as $res )
			{
				$search_ids[] = ( IPSSearchRegistry::get('set.returnType') == 'tids' ) ? $res['attrs']['tid'] : $res['attrs']['search_id'];
			}
		}
		
		/* Return results */
		return array( 'count' => intval( $result['total_found'] ) > 1000 ? 1000 : $result['total_found'], 'resultSet' => $search_ids );
	}

	/**
	 * Perform the viewNewContent search
	 * Forum Version
	 * Populates $this->_count and $this->_results
	 *
	 * @return	array
	 */
	public function viewNewContent()
	{
		$oldStamp		= $this->registry->getClass('classItemMarking')->fetchOldestUnreadTimestamp( array(), 'forums' );
		$check		    = IPS_UNIX_TIME_NOW - ( 86400 * $this->settings['topic_marking_keep_days'] );
		$forumIdsOk		= array();
		$start			= IPSSearchRegistry::get('in.start');
		$perPage		= IPSSearchRegistry::get('opt.search_per_page');
		$seconds		= IPSSearchRegistry::get('in.period_in_seconds');
		$followedOnly	= $this->memberData['member_id'] ? IPSSearchRegistry::get('in.vncFollowFilterOn' ) : false;
		$permissions	= array();
		
		/* Loop through the forums and build a list of forums we're allowed access to */
		$bvnp = explode( ',', $this->settings['vnp_block_forums'] );
		
		IPSSearchRegistry::set('in.search_sort_by'   , 'date' );
		IPSSearchRegistry::set('in.search_sort_order', 'desc' );
		IPSSearchRegistry::set('opt.noPostPreview'   , true );
		IPSSearchRegistry::set('opt.searchType'      , 'titles' );
		
		$followedForums = array();
		$followedTopics = array();

		//-----------------------------------------
		// Only content we have participated in?
		//-----------------------------------------

		if( IPSSearchRegistry::get('in.userMode') )
		{
			IPSSearchRegistry::set('in.start', 0);
			IPSSearchRegistry::set('opt.search_per_page', 1000);
		
			$_tempResults	= $this->viewUserContent( $this->memberData );
			
			IPSSearchRegistry::set('in.start', $start);
			IPSSearchRegistry::set('opt.search_per_page', $perPage);
			
			if( $_tempResults['count'] )
			{
				$this->sphinxClient->SetFilter( 'tid', $_tempResults['resultSet'] );
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
		
		/* What type of date restriction? */
		if ( IPSSearchRegistry::get('in.period_in_seconds') !== false )
		{
			$oldStamp	= ( time() - $seconds );
			$forumIdsOk	= $_forumIdsOk;
		}
		else
		{
			foreach( $_forumIdsOk as $id )
			{
				if ( $followedOnly && ! in_array( $id, $followedForums ) )
				{
					// We don't want to skip followed topics just because we don't follow the forum
					//continue;
				}
				
				$lMarked    = $this->registry->getClass('classItemMarking')->fetchTimeLastMarked( array( 'forumID' => $id ), 'forums' );
				$fData      = $this->registry->getClass('class_forums')->forumsFetchData( $id );
			
				if ( $fData['last_post'] > $lMarked )
				{
					$forumIdsOk[ $id ] = $id;
				}
			}
			
			if ( intval( $this->memberData['_cache']['gb_mark__forums'] ) > 0  )
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
			
			/* If no forums, we're done */
			if ( ! count( $forumIdsOk ) )
			{
				/* Return it */
				return array( 'count' => 0, 'resultSet' => array() );
			}
		}
		
		/* Only show VNC results from specified forums? */
		if ( is_array(IPSSearchRegistry::get('forums.vncForumFilters')) AND count(IPSSearchRegistry::get('forums.vncForumFilters')) )
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
		
		/* Set the timestamps */
		$this->sphinxClient->SetFilterRange( 'last_post', $oldStamp, time() );
		$this->setDateRange( 0, 0 );
		
		/* Force it into filter so that search can pick it up */
		$this->searchForumIds	= $forumIdsOk;
		
		/* Set up some vars */
		IPSSearchRegistry::set('set.resultCutToDate', $oldStamp );
		
		/* Fetch first 300 results */
		IPSSearchRegistry::set('opt.search_per_page', 300);
		IPSSearchRegistry::set('in.start', 0);
			
		/* Run search */
		$data = $this->search();
	
		/* Reset for display function */
		IPSSearchRegistry::set('in.start', $start);
		IPSSearchRegistry::set('opt.search_per_page', $perPage);
		
		$_ffWhere = '';
		
		/* Followed topics only */
		if ( $followedOnly )
		{
			if ( ! count( $followedTopics ) && ! count( $followedForums ) )
			{
				return array( 'count' => 0, 'resultSet' => array() );
			}
			
			if ( count( $followedForums ) )
			{
				/* @link http://community.invisionpower.com/tracker/issue-33284-view-new-content-just-items-i-follow */
				//$_ffWhere = ' OR forum_id IN (' . implode( ',', $followedForums ) . ') ';
			}
		}
		
		/* This method allows us to have just one "tid IN()" clause, instead of two, depending on scenario */
		if ( count($followedTopics) )
		{
			$where[]  = "( tid IN(" . implode( ',', $followedTopics ) . ')' . $_ffWhere . ' )';
		}
		else
		{
			if ( !count($data['resultSet']) )
			{
				return array( 'count' => 0, 'resultSet' => array() );
			}
			
			$this->DB->build( array( 'select' => 'topic_id',
									 'from'   => 'posts',
									 'where'  => 'pid IN(' . implode( ',', $data['resultSet'] ) . ')' ) );
									 
			$this->DB->execute();
			
			$_tmpTids = array();
			
			while( $row = $this->DB->fetch() )
			{
				$_tmpTids[] = $row['topic_id'];
			}
		
			if ( count( $_tmpTids ) )
			{
				$where[] = "( tid IN(" . implode( ',', $_tmpTids ) . ')' . $_ffWhere . ' )';
			}
		}
		
		$permissions['TopicSoftDeleteSee']  = $this->registry->getClass('class_forums')->canSeeSoftDeletedTopics( 0 );
		$permissions['canQueue']			= $this->registry->getClass('class_forums')->canQueuePosts( 0 );
		
		if ( $permissions['TopicSoftDeleteSee'] AND $permissions['canQueue'] )
		{
			$where[] = $this->registry->class_forums->fetchTopicHiddenQuery( array( 'visible', 'hidden', 'sdeleted' ) );
		}
		else if ( $permissions['TopicSoftDeleteSee'] )
		{
			$where[] = $this->registry->class_forums->fetchTopicHiddenQuery( array( 'visible', 'sdeleted' ) );
		}
		else if ( $permissions['canQueue'] )
		{
			$where[] = $this->registry->class_forums->fetchTopicHiddenQuery( array( 'visible', 'hidden' ) );
		}
		else
		{
			$where[] = $this->registry->class_forums->fetchTopicHiddenQuery( array( 'visible' )  );
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
		
		if ( IPSSearchRegistry::get('in.period') == 'unread' )
		{
			$filter				= $this->registry->class_forums->postProcessVncTids( $rtids, array( $start, $perPage ) );
			$data['count']		= $filter['count'];
			$data['resultSet']	= $filter['tids'];
		}
		else
		{
			$data	= array( 'count' => $count['count'], 'resultSet' => ( is_array( $rtids ) ) ? array_keys( $rtids ) : array() );
		}
		
		/* ensure we check TIDS */
		IPSSearchRegistry::set('set.returnType', 'tids' );
		
		/* Return it */
		return $data;
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
		$column = $column == 'member_id'     ? ( IPSSearchRegistry::get('opt.searchTitleOnly') ? 'starter_id' : 'author_id' ) : $column;
		$column = $column == 'content_title' ? 'title'     : $column;
		$column = $column == 'type_id'       ? 'forum_id'  : $column;
		
		return $column;
	}
	
	/**
	 * Returns an array used in the searchplugin's setCondition method
	 *
	 * @param	array 	$data	Array of filters to apply
	 * @return	array 	Array with column, operator, and value keys, for use in the setCondition call
	 */
	public function buildFilterSQL( $data )
	{
		/* INIT */
		$return = array();
		
		/* Set up some defaults */
		IPSSearchRegistry::set( 'opt.noPostPreview'  , true );
		
		if ( ! IPSSearchRegistry::get('opt.searchType' ) )
		{
			IPSSearchRegistry::set( 'opt.searchType', 'both' );
		}
		
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
					IPSSearchRegistry::set( 'opt.pCount', intval( $_data ) );
				}

				/* TOPIC VIEWS */
				if ( $field == 'pViews' AND intval( $_data ) > 0 )
				{
					IPSSearchRegistry::set( 'opt.pViews', intval( $_data ) );
				}
			}

			return $return;
		}
		else
		{
			return array();
		}
	}
}