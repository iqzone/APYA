<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Basic Calendar Search
 * Last Updated: $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Blog
 * @link		http://www.invisionpower.com
 * @version		$Rev: 4 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class search_engine_blog extends search_engine
{
	/**
	 * Constructor
	 *
	 * @param	object	ipsRegistry
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Hard limit */
		IPSSearchRegistry::set('set.hardLimit', ( ipsRegistry::$settings['search_hardlimit'] ) ? ipsRegistry::$settings['search_hardlimit'] : 200 );
		
		$searchIn	= ipsRegistry::$request['search_app_filters']['blog']['searchInKey'];

		IPSSearchRegistry::set( 'blog.searchInKey', in_array( $searchIn, array( 'entries', 'comments' ) ) ? $searchIn : 'entries' );
		
		ipsRegistry::$request['search_app_filters']['blog']['searchInKey'] = IPSSearchRegistry::get( 'blog.searchInKey' );
		
		parent::__construct( $registry );
	}
	
	/**
	 * Decide what type of search we're using
	 *
	 * @access	public
	 * @return	array
	 */
	public function search()
	{
		/* Context sensitive? */
		if ( $this->request['cType'] == 'entry' AND $this->request['cId'] )
		{
			IPSSearchRegistry::set('blog.searchInKey', 'comments' );
		}
		
		if ( IPSSearchRegistry::get('blog.searchInKey') == 'entries' )
		{
			return $this->_entriesSearch();
		}
		else
		{
			return $this->_commentsSearch();
		}
	}
	
	/**
	 * Perform a search.
	 * Returns an array of a total count (total number of matches)
	 * and an array of IDs ( 0 => 1203, 1 => 928, 2 => 2938 ).. matching the required number based on pagination. The ids returned would be based on the filters and type of search
	 *
	 * So if we had 1000 replies, and we are on page 2 of 25 per page, we'd return 25 items offset by 25
	 *
	 * @access public
	 * @return array
	 */
	public function _commentsSearch()
	{
		/* INIT */
		$start		        = IPSSearchRegistry::get('in.start');
		$sort_by     		= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order         = IPSSearchRegistry::get('in.search_sort_order');
		$search_term        = IPSSearchRegistry::get('in.clean_search_term');
		$perPage            = IPSSearchRegistry::get('opt.search_per_page');
		$sortKey			= 'comment_date';
		$groupBy			= false;
		
		if ( IPSSearchRegistry::get('opt.noPostPreview') OR IPSSearchRegistry::get('opt.searchType') == 'titles' )
		{
			$groupBy = true;
		}

		/* Limit Results */
		$this->sphinxClient->SetLimits( intval($start), intval($perPage) );
		
		/* Exclude some items */
		if ( ! $this->memberData['g_is_supmod'] )
		{
			/* Don't show disabled blogs */
			$this->sphinxClient->SetFilter( 'blog_disabled', array( 0 ) );
			
			/* Dont' show drafts */
			$this->sphinxClient->SetFilter( 'entry_not_published', array( 0, $this->memberData['member_id'] ) );
			$this->sphinxClient->SetFilter( 'comment_approved', array( 1 ) );
			
			/* Owner only */
			$this->sphinxClient->SetFilter( 'blog_owner_id', array( 0, $this->memberData['member_id'] ) );
			
			
			/* Authorized users only */
			$this->sphinxClient->SetFilter( 'blog_private'    , array( 0 ) );
			$this->sphinxClient->SetFilter( 'authorized_users', array( 0 ) );
		}
		
		/* Context sensitive? */
		if ( $this->request['cType'] == 'entry' AND $this->request['cId'] )
		{
			$this->sphinxClient->SetFilter( 'entry_id', array( $this->request['cId'] ) );
		}
		
		/* Date Restrict */
		if ( $this->search_begin_timestamp )
		{
			if ( ! $this->search_end_timestamp )
			{
				$this->search_end_timestamp = time() + 100;
			}
			
			$this->sphinxClient->SetFilterRange( 'comment_date', $this->search_begin_timestamp, $this->search_end_timestamp );
		}

		/* Sort ordering */
		if ( $sort_order == 'asc' )
		{
			$this->sphinxClient->SetSortMode( SPH_SORT_ATTR_ASC, $sortKey );
		}
		else
		{
			$this->sphinxClient->SetSortMode( SPH_SORT_ATTR_DESC, $sortKey );
		}
		
		/* Group by */
		if ( $groupBy )
		{
			$this->sphinxClient->SetGroupBy( 'last_post_group', SPH_GROUPBY_ATTR, '@group ' . $sort_order );
		}

		/* Search */
		$_s = ( $search_term ) ? '@comment_text ' . $search_term : '';
		
		$result = $this->sphinxClient->Query( $_s, $this->settings['sphinx_prefix'] . 'blog_comments_main,' . $this->settings['sphinx_prefix'] . 'blog_comments_delta' );
		
		$this->logSphinxWarnings();

		/* Get matches */
		if ( is_array( $result['matches'] ) && count( $result['matches'] ) )
		{
			foreach( $result['matches'] as $res )
			{
				$search_ids[] = $res['attrs']['search_id'];
			}
		}

		/* Return it */
		return array( 'count' => intval( $result['total_found'] ) > 1000 ? 1000 : $result['total_found'], 'resultSet' => $search_ids );
	}
	
	/**
	 * Perform a search.
	 * Returns an array of a total count (total number of matches)
	 * and an array of IDs ( 0 => 1203, 1 => 928, 2 => 2938 ).. matching the required number based on pagination. The ids returned would be based on the filters and type of search
	 *
	 * So if we had 1000 replies, and we are on page 2 of 25 per page, we'd return 25 items offset by 25
	 *
	 * @access public
	 * @return array
	 */
	public function _entriesSearch()
	{
		/* INIT */ 
		$start		        = IPSSearchRegistry::get('in.start');
		$sort_by     		= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order         = IPSSearchRegistry::get('in.search_sort_order');
		$search_term        = IPSSearchRegistry::get('in.clean_search_term');
		$content_title_only = IPSSearchRegistry::get('opt.searchTitleOnly');
		$perPage            = IPSSearchRegistry::get('opt.search_per_page');
		$search_tags        = IPSSearchRegistry::get('in.raw_search_tags');
		$sortKey			= '';
				
		/* Sorting */
		switch( $sort_by )
		{
			default:
			case 'date':
				$sortKey	= 'entry_date';
			break;
			case 'title':
				$sortKey	= 'entry_name';
			break;
			case 'comments':
				$sortKey	= 'entry_num_comments';
			break;
		}
		
		/* Limit Results */
		$this->sphinxClient->SetLimits( intval($start), intval($perPage) );
		
		/* Exclude some items */
		if ( ! $this->memberData['g_is_supmod'] )
		{
			/* Don't show disabled blogs */
			$this->sphinxClient->SetFilter( 'blog_disabled', array( 0 ) );
			
			/* Dont' show drafts */
			$this->sphinxClient->SetFilter( 'entry_not_published', array( 0, $this->memberData['member_id'] ) );
			
			/* Owner only */
			$this->sphinxClient->SetFilter( 'blog_owner_id', array( 0, $this->memberData['member_id'] ) );
			
			
			/* Authorized users only */
			$this->sphinxClient->SetFilter( 'blog_private'    , array( 0 ) );
			$this->sphinxClient->SetFilter( 'authorized_users', array( 0 ) );
		}
		
		/* Tags */
		if ( $search_tags && $this->settings['tags_enabled'] )
		{
			IPSSearchRegistry::set('opt.searchType', 'titles');
			
			$search_tags = explode( ",", $search_tags );
			
			$this->DB->build( array( 'select' => 'tag_id',
									 'from'   => 'core_tags',
									 'where'  => "tag_meta_app='blog' AND tag_meta_area='entries' AND (" . $this->DB->buildLikeChain( 'tag_text', $search_tags, false ) . ")",
									 'limit'  => array( 0, 500 ) ) );
			
			$o = $this->DB->execute();
			
			while( $row = $this->DB->fetch( $o ) )
			{
				$tagIds[] = $row['tag_id'];
			}
			
			if ( count( $tagIds ) )
			{
				$this->sphinxClient->SetFilter( 'tag_id', $tagIds );
			}
		}
		
		/* Context sensitive? */
		if ( $this->request['cType'] == 'blog' AND $this->request['cId'] )
		{
			$this->sphinxClient->SetFilter( 'blog_id', array( $this->request['cId'] ) );
		}
		
		/* Date Restrict */
		if ( $this->search_begin_timestamp )
		{
			if ( ! $this->search_end_timestamp )
			{
				$this->search_end_timestamp = time() + 100;
			}
			
			$this->sphinxClient->SetFilterRange( 'entry_date', $this->search_begin_timestamp, $this->search_end_timestamp );
		}
		
		/* Sorting */
		if ( $sort_order == 'asc' )
		{
			$this->sphinxClient->SetSortMode( SPH_SORT_ATTR_ASC, $sortKey );
		}
		else
		{
			$this->sphinxClient->SetSortMode( SPH_SORT_ATTR_DESC, $sortKey );
		}

		/* Search */
		switch( IPSSearchRegistry::get('opt.searchType') )
		{
			case 'both':
			default:
				$_s = ( $search_term AND strstr( $search_term, '"' ) ) ? '@entry  ' . $search_term . ' | @entry_name ' . $search_term : ( $search_term ? '@(entry,entry_name) ' . $search_term : '' );
			break;
			
			case 'titles':
				$_s = ( $search_term ) ? '@entry_name ' . $search_term : '';
			break;
			
			case 'content':
				$_s = ( $search_term ) ? '@entry ' . $search_term : '';
			break;
		}

		$result = $this->sphinxClient->Query( $_s, $this->settings['sphinx_prefix'] . 'blog_search_main,' . $this->settings['sphinx_prefix'] . 'blog_search_delta' );
		
		$this->logSphinxWarnings();

		/* Get ids */
		if ( is_array( $result['matches'] ) && count( $result['matches'] ) )
		{
			foreach( $result['matches'] as $res )
			{
				$search_ids[] = ( $content_title_only ) ? $res['attrs']['search_id'] : $res['attrs']['search_id'];
			}
		}

		/* Return it */
		return array( 'count' => intval( $result['total_found'] ) > 1000 ? 1000 : $result['total_found'], 'resultSet' => $search_ids );
	}
		
    /**
	 * Perform the viewNewContent search
	 * Populates $this->_count and $this->_results
	 *
	 * @access	public
	 * @return	nothin'
	 */
	public function viewNewContent()
	{
		$entryIds		= $this->registry->getClass('classItemMarking')->fetchCookieData( 'blog', 'items' );
		$oldStamp		= $this->registry->getClass('classItemMarking')->fetchOldestUnreadTimestamp( array(), 'blog' );
		$followedOnly	= $this->memberData['member_id'] ? IPSSearchRegistry::get('in.vncFollowFilterOn' ) : false;
	
		/* Loop through the forums and build a list of forums we're allowed access to */
		$start		= IPSSearchRegistry::get('in.start');
		$perPage	= IPSSearchRegistry::get('opt.search_per_page');
		
		IPSSearchRegistry::set('in.search_sort_by'   , 'date' );
		IPSSearchRegistry::set('in.search_sort_order', 'desc' );
		IPSSearchRegistry::set('opt.searchType' , 'titles' );
		IPSSearchRegistry::set('opt.noPostPreview'   , true );
		
		/* Finalize times */
		if ( ! $oldStamp OR $oldStamp == IPS_UNIX_TIME_NOW )
		{
			$oldStamp = intval( $this->memberData['last_visit'] );
		}
		
		if ( $this->memberData['_cache']['gb_mark__blog'] && ( $this->memberData['_cache']['gb_mark__blog'] < $oldStamp ) )
		{
			$oldStamp = $this->memberData['_cache']['gb_mark__blog'];
		}
		
		/* Finalize times */
		if ( ! $oldStamp OR $oldStamp == IPS_UNIX_TIME_NOW )
		{
			$oldStamp = intval( $this->memberData['last_visit'] );
		}
		
		/* Set the timestamps */
		$this->sphinxClient->SetFilterRange( 'entry_date', $oldStamp, time() );
		$this->setDateRange( 0, 0 );
		
		/* Set up some vars */
		IPSSearchRegistry::set('set.resultCutToDate', $oldStamp );
		
		//-----------------------------------------
		// Only content we are following?
		//-----------------------------------------
		
		if ( $followedOnly )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
			$like = classes_like::bootstrap( 'blog', 'entries' );
			
			$followedEvents	= $like->getDataByMemberIdAndArea( $this->memberData['member_id'] );
			$followedEvents = ( $followedEvents === null ) ? array() : array_keys( $followedEvents );
			
			if( !count($followedEvents) )
			{
				return array( 'count' => 0, 'resultSet' => array() );
			}
			else
			{
				$this->sphinxClient->SetFilter( 'search_id', $followedEvents );
			}
		}

		//-----------------------------------------
		// Only content we have participated in?
		//-----------------------------------------
		
		if( IPSSearchRegistry::get('in.userMode') )
		{
			switch( IPSSearchRegistry::get('in.userMode') )
			{
				default:
				case 'all': 
					$_blogIds	= $this->_getBlogIdsFromComments();
					
					if( count($_blogIds) )
					{
						$this->sphinxClient->SetFilter( 'entry_author_id', $this->memberData['member_id'] );
						$this->sphinxClient->SetFilter( 'search_id', $_blogIds );
					}
					else
					{
						$this->sphinxClient->SetFilter( 'entry_author_id', $this->memberData['member_id'] );
					}
				break;
				case 'title':
					$this->sphinxClient->SetFilter( 'entry_author_id', $this->memberData['member_id'] );
				break;
			}
		}

		if ( IPSSearchRegistry::get('blog.searchInKey') == 'entries' )
		{
			return $this->_entriesSearch();
		}
		else
		{
			return $this->_commentsSearch();
		}
	}

	/**
	 * Find blogs we have commented on
	 *
	 * @return	array
	 */
	protected function _getBlogIdsFromComments()
	{
		$ids	= array();
		
		$this->DB->build( array(
								'select'	=> $this->DB->buildDistinct('entry_id'),
								'from'		=> 'blog_comments',
								'where'		=> 'comment_approved=1 AND member_id=' . $this->memberData['member_id'],
								'limit'		=> array( 0, 200 )
						)		);
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$ids[]	= $r['entry_id'];
		}
		
		return $ids;
	}

	/**
	 * Perform the viewUserContent search
	 * Populates $this->_count and $this->_results
	 *
	 * @access	public
	 * @return	nothin'
	 */
	public function viewUserContent( $member )
	{
		/* Init */
		$start		= IPSSearchRegistry::get('in.start');
		$perPage	= IPSSearchRegistry::get('opt.search_per_page');
		$time       = time() - ( 86400 * intval( $this->settings['search_ucontent_days'] ) );
		
		switch( IPSSearchRegistry::get('in.userMode') )
		{
			default:
			case 'all': 
				IPSSearchRegistry::set('opt.searchType', 'both' );
				IPSSearchRegistry::set('opt.noPostPreview'  , false );
			break;
			case 'title': 
				IPSSearchRegistry::set('opt.searchType', 'titles' );
				IPSSearchRegistry::set('opt.noPostPreview'  , false );
			break;	
			case 'content': 
				IPSSearchRegistry::set('opt.searchType', 'both' );
				IPSSearchRegistry::set('opt.noPostPreview'  , false );
			break;	
		}
		
		/* Set author ID */
		if ( IPSSearchRegistry::get('blog.searchInKey') == 'entries' )
		{
			$this->sphinxClient->SetFilter( 'entry_author_id', array( $member['member_id'] ) );
			$this->sphinxClient->SetFilterRange( 'entry_date', $time, time() );
		}
		else
		{
			$this->sphinxClient->SetFilter( 'comment_member_id', array( $member['member_id'] ) );
			$this->sphinxClient->SetFilterRange( 'comment_date', $time, time() );
		}

		/* Set the timestamps */
		$this->setDateRange( 0, 0 );
		
		if ( IPSSearchRegistry::get('blog.searchInKey') == 'entries' )
		{
			return $this->_entriesSearch();
		}
		else
		{
			return $this->_commentsSearch();
		}
	}
	
	/**
	 * Remap standard columns (Apps can override )
	 *
	 * @access	public
	 * @param	string	$column		sql table column for this condition
	 * @return	string				column
	 * @return	@e void
	 */
	public function remapColumn( $column )
	{
		$column = $column == 'member_id' ? 'b.entry_author_id' : $column;

		return $column;
	}
		
	/**
	 * Returns an array used in the searchplugin's setCondition method
	 *
	 * @access	public
	 * @param	array 	$data	Array of forums to view
	 * @return	array 	Array with column, operator, and value keys, for use in the setCondition call
	 */
	public function buildFilterSQL( $data )
	{
		return array();
	}
}