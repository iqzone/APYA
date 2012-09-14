<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Basic Blog Search
 * Last Updated: $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Blog
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
		if ( $this->request['cType'] == 'entry' AND !empty($this->request['cId']) )
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
		$sort_by     		= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order         = IPSSearchRegistry::get('in.search_sort_order');
		$search_term        = IPSSearchRegistry::get('in.clean_search_term');
		$rows    			= array();
		$c                  = 0;
		$got     			= 0;
		$sortKey			= 'comment_date';
		$sortType			= '';
		
		if ( IPSSearchRegistry::get('opt.noPostPreview') OR IPSSearchRegistry::get('opt.searchType') == 'titles' )
		{
			$group_by = 'c.comment_id';
		}
		
		$this->DB->build( array(
								'select'	=> 'c.comment_id, c.comment_date',
								'from'		=> array( 'blog_comments' => 'c' ),
 								'where'		=> $this->_buildWhereStatement( $search_term, 'comments' ),
								'group'		=> $group_by,
								'limit'		=> array(0, IPSSearchRegistry::get('set.hardLimit') + 1),
								'add_join'	=> array(
										 				array(
																'from'		=> array( 'blog_entries' => 'b' ),
																'where'		=> 'c.entry_id=b.entry_id',
																'type'		=> 'left'
										 					),
														array(
																'from'		=> array( 'blog_blogs' => 'bl' ),
																'where'		=> "bl.blog_id=b.blog_id",
																'type'		=> 'left',
															)
													)													
				)	);
		$this->DB->execute();
		
		/* Fetch count */
		$count = intval( $this->DB->getTotalRows() );
		
		if ( $count > IPSSearchRegistry::get('set.hardLimit') )
		{
			$count = IPSSearchRegistry::get('set.hardLimit');
			
			IPSSearchRegistry::set('set.resultsCutToLimit', true );
		}

		/* Fetch to sort */
		while ( $r = $this->DB->fetch() )
		{
			$_rows[ $r['comment_id'] ] = $r;
		}

		/* Set vars */
		IPSSearch::$ask = $sortKey;
		IPSSearch::$aso = strtolower( $sort_order );
		IPSSearch::$ast = 'numerical';
		
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

				$rows[ $got ] = $r['comment_id'];
							
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
		$sort_by     		= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order         = IPSSearchRegistry::get('in.search_sort_order');
		$search_term        = IPSSearchRegistry::get('in.clean_search_term');
		$search_tags        = IPSSearchRegistry::get('in.raw_search_tags');
		$rows    			= array();
		$c                  = 0;
		$got     			= 0;
		$sortKey			= '';
		$sortType			= '';
		
		/* If searching tags, we don't show a preview */
		if( $search_tags )
		{
			IPSSearchRegistry::set('opt.searchType', 'titles');
		}

		/* Sorting */
		switch( $sort_by )
		{
			default:
			case 'date':
				$sortKey	= 'entry_date';
				$sortType	= 'numerical';
			break;
			case 'title':
				$sortKey	= 'entry_name';
				$sortType	= 'string';
			break;
			case 'comments':
				$sortKey	= 'entry_num_comments';
				$sortType	= 'numerical';
			break;
		}
		
		$this->DB->build( array('select'   => "b.entry_id, b.entry_name, b.entry_date, b.entry_num_comments",
								'from'	   => array( 'blog_entries' => 'b' ),
 								'where'	   => $this->_buildWhereStatement( $search_term, 'entries', $search_tags ),
								'limit'    => array(0, IPSSearchRegistry::get('set.hardLimit') + 1),
								'add_join' => array( array( 'select'	=> 'bl.*',
															'from'		=> array( 'blog_blogs' => 'bl' ),
															'where'		=> "bl.blog_id=b.blog_id",
															'type'		=> 'left' ) )
						)		);
		$this->DB->execute();
		
		/* Fetch count */
		$count = intval( $this->DB->getTotalRows() );
		
		if ( $count > IPSSearchRegistry::get('set.hardLimit') )
		{
			$count = IPSSearchRegistry::get('set.hardLimit');
			
			IPSSearchRegistry::set('set.resultsCutToLimit', true );
		}

		/* Fetch to sort */
		while ( $r = $this->DB->fetch() )
		{
			$_rows[ $r['entry_id'] ] = $r;
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
				
				$rows[ $got ] = $r['entry_id'];
				
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
	 * Builds the where portion of a search string
	 *
	 * @access	protected
	 * @param	string	$search_term		The string to use in the search
	 * @return	string
	 */
	protected function _buildWhereStatement( $search_term, $type='entries', $search_tags=NULL )
	{	
		if( ! ipsRegistry::isClassLoaded( 'blogFunctions' ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'blog' ) . "/sources/classes/blogFunctions.php", 'blogFunctions', 'blog' );
			$this->registry->setClass('blogFunctions', new $classToLoad($this->registry));
		}
		
		$blogFunctions = $this->registry->getClass('blogFunctions');
		$_member       = $blogFunctions->buildPerms();
				
		/* INI */
		$where_clause = array();
				
		if( $search_term )
		{
			$search_term = str_replace( '&quot;', '"', $search_term );
			
			if( $type == 'entries' )
			{
				switch( IPSSearchRegistry::get('opt.searchType') )
				{
					case 'both':
					default:
						$where_clause[] = '(' . $this->DB->buildSearchStatement( 'b.entry_name', $search_term, true, false, ipsRegistry::$settings['use_fulltext'] ) . ' OR ' . $this->DB->buildSearchStatement( 'b.entry', $search_term, true, false, ipsRegistry::$settings['use_fulltext'] ) . ')';
					break;
					
					case 'titles':
						$where_clause[] = $this->DB->buildSearchStatement( 'b.entry_name', $search_term, true, false, ipsRegistry::$settings['use_fulltext'] );
					break;
					
					case 'content':
						$where_clause[] = $this->DB->buildSearchStatement( 'b.entry', $search_term, true, false, ipsRegistry::$settings['use_fulltext'] );
					break;
				}
			}
			else
			{
				$where_clause[] = $this->DB->buildSearchStatement( 'c.comment_text', $search_term, true, false, ipsRegistry::$settings['use_fulltext'] );
			}
		}
		
		if ( $search_tags )
		{
			$_tagIds = array();
			
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$tags = classes_tags_bootstrap::run( 'blog', 'entries' )->search( $search_tags, array() );
			
			if ( is_array($tags) && count($tags) )
			{
				foreach( $tags as $id => $data )
				{
					$_tagIds[] = $data['tag_meta_id'];
				}
				
				$where_clause[] = 'b.entry_id IN(' . implode( ',', $_tagIds ) . ')';
			}
			else
			{
				$where_clause[] = 'b.entry_id=0';
			}
		}
		
		/* Limit by blog */
		$type      = ipsRegistry::$request['type'];
		$type_id   = intval( ipsRegistry::$request['type_id'] );
		
		if( $type && $type_id )
		{
			$where_clause[] = "b.blog_id={$type_id}";
		}

		/* Exclude some items */
		if ( !$this->memberData['g_is_supmod'] )
		{
			if ( ! $_member['_blogmod']['moderate_can_disable'] )
			{
				/* Don't show disabled blogs */
				$where_clause[] = 'bl.blog_disabled=0';
			}
			
			if ( ! $_member['_blogmod']['moderate_can_view_draft'] )
			{
				/* Dont' show drafts */
				$where_clause[] = '(b.entry_status=\'published\' OR b.entry_author_id=' . $this->memberData['member_id'] . ')';
			}
			
			if ( ! $_member['_blogmod']['moderate_can_view_private'] )
			{
				/* Owner only */
				$where_clause[] = '(bl.blog_owner_only=0 OR b.entry_author_id=' . $this->memberData['member_id'] . ')';
				
				/* Authorized users only */
				$where_clause[] = '(bl.blog_authorized_users ' . $this->DB->buildIsNull() . " OR bl.blog_authorized_users='' OR b.entry_author_id=" . $this->memberData['member_id'] . " OR bl.blog_authorized_users LIKE '%," . $this->memberData['member_id'] . ",%')";
			}
		}
		
		/* Date Restrict */
		if( $this->search_begin_timestamp && $this->search_end_timestamp )
		{
			if( IPSSearchRegistry::get('blog.searchInKey') == 'entries' )
			{
				$where_clause[] = $this->DB->buildBetween( "b.entry_date", $this->search_begin_timestamp, $this->search_end_timestamp );
			}
			else
			{
				$where_clause[] = $this->DB->buildBetween( "c.comment_date", $this->search_begin_timestamp, $this->search_end_timestamp );
			}
		}
		else
		{
			if( IPSSearchRegistry::get('blog.searchInKey') == 'entries' )
			{
				if( $this->search_begin_timestamp )
				{
					$where_clause[] = "b.entry_date > {$this->search_begin_timestamp}";
				}
				
				if( $this->search_end_timestamp )
				{
					$where_clause[] = "b.entry_date < {$this->search_end_timestamp}";
				}
			}
			else
			{
				if( $this->search_begin_timestamp )
				{
					$where_clause[] = "c.comment_date > {$this->search_begin_timestamp}";
				}
				
				if( $this->search_end_timestamp )
				{
					$where_clause[] = "c.comment_date < {$this->search_end_timestamp}";
				}
			}
		}
		
		/* Context sensitive? */
		if ( $this->request['cType'] == 'entry' AND $this->request['cId'] )
		{
			$where_clause[] = "b.entry_id = " . intval($this->request['cId']);
		}
		
		/* Context sensitive? */
		if ( $this->request['cType'] == 'blog' AND $this->request['cId'] )
		{
			$where_clause[] = "b.blog_id = " . intval($this->request['cId']);
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
		
		return implode( " AND ", $where_clause );
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
		
		IPSSearchRegistry::set( 'opt.searchType' , 'titles' );

		/* Time limit */
		if ( IPSSearchRegistry::get('in.period_in_seconds') !== false )
		{
			$this->search_begin_timestamp	= ( IPS_UNIX_TIME_NOW - IPSSearchRegistry::get('in.period_in_seconds') );
		}
		else
		{
			/* Finalize times */
			if ( ! $oldStamp OR $oldStamp == IPS_UNIX_TIME_NOW )
			{
				$oldStamp = intval( $this->memberData['last_visit'] );
			}
			
			if ( $this->memberData['_cache']['gb_mark__blog'] && ( $this->memberData['_cache']['gb_mark__blog'] < $oldStamp ) )
			{
				$oldStamp = $this->memberData['_cache']['gb_mark__blog'];
			}

			/* Based on oldest timestamp */
			$this->search_begin_timestamp	= $oldStamp;
			
			IPSSearchRegistry::set('set.resultCutToDate', $oldStamp );

			/* Set read tids */
			if ( count( $entryIds ) )
			{
				$this->whereConditions['AND'][]	= "b.entry_id NOT IN (" . implode( ",", array_keys( $entryIds ) ) . ')';
			}
		}
		
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
				$this->whereConditions['AND'][]	= "b.entry_id IN(" . implode( ',', $followedEvents ) . ")";
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
						$this->whereConditions['AND'][]	= "(b.entry_author_id=" . $this->memberData['member_id'] . " OR b.entry_id IN(" . implode( ',', $_blogIds ) . "))";
					}
					else
					{
						$this->whereConditions['AND'][]	= "b.entry_author_id=" . $this->memberData['member_id'];
					}
				break;
				case 'title': 
					$this->whereConditions['AND'][]	= "b.entry_author_id=" . $this->memberData['member_id'];
				break;
			}
		}

		return $this->_getNonSearchData();
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
	 * @return	array
	 */
	public function viewUserContent( $member )
	{
		$sort_by		= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order		= IPSSearchRegistry::get('in.search_sort_order');
		
		/* Search by author */
		if ( IPSSearchRegistry::get('blog.searchInKey') == 'entries' )
		{
			$this->whereConditions['AND'][]	= "b.entry_author_id=" . intval( $member['member_id'] );
		}
		else
		{
			$this->whereConditions['AND'][]	= "c.member_id=" . intval( $member['member_id'] );
		}
		
		if ( $this->settings['search_ucontent_days'] )
		{
			$this->search_begin_timestamp	= ( time() - ( 86400 * intval( $this->settings['search_ucontent_days'] ) ) );
		}
		
		/* Get the results */
		return $this->_getNonSearchData();
	}
	
	/**
	 * Perform the search for viewUserContent, viewActiveContent, and viewNewContent
	 *
	 * @access	public
	 * @return	array
	 */
	public function _getNonSearchData()
	{
		/* Init */
		$start		= IPSSearchRegistry::get('in.start');
		$perPage	= IPSSearchRegistry::get('opt.search_per_page');
		
		/* Fetch the count */
		if ( IPSSearchRegistry::get('blog.searchInKey') == 'entries' )
		{
			$count = $this->DB->buildAndFetch( 
												array( 
														'select'	=> 'count(*) as count',
														'from'		=> array( 'blog_entries' => 'b' ),
														'where'		=> $this->_buildWhereStatement( '', IPSSearchRegistry::get('blog.searchInKey') ),
														'add_join'	=> array(
																				array(
																						'from'   => array( 'blog_blogs' => 'bl' ),
																						'where'  => 'bl.blog_id=b.blog_id',
																						'type'   => 'left',
																					),
																				)
													)	
											);
		}
		else
		{
			$count = $this->DB->buildAndFetch( 
												array( 
														'select'	=> 'count(*) as count',
														'from'		=> array( 'blog_comments' => 'c' ),
														'where'		=> $this->_buildWhereStatement( '', IPSSearchRegistry::get('blog.searchInKey') ),
														'add_join'	=> array(
																				array(
																						'from'   => array( 'blog_entries' => 'b' ),
																						'where'  => 'b.entry_id=c.entry_id',
																						'type'   => 'left',
																					),
																				array(
																						'from'   => array( 'blog_blogs' => 'bl' ),
																						'where'  => 'bl.blog_id=b.blog_id',
																						'type'   => 'left',
																					),
																				)
													)	
											);
		}

		/* Fetch the data */
		$resultIds = array();
		
		if ( $count['count'] )
		{
			if( IPSSearchRegistry::get('blog.searchInKey') == 'entries' )
			{
				$this->DB->build( array( 
											'select'	=> 'b.entry_id as id',
											'from'		=> array( 'blog_entries' => 'b' ),
										 	'where'		=> $this->_buildWhereStatement( '', IPSSearchRegistry::get('blog.searchInKey') ),
										 	'order'		=> 'b.entry_date DESC',
										 	'limit'		=> array( $start, $perPage ),
										 	'add_join'	=> array(
																	array(
																			'from'   => array( 'blog_blogs' => 'bl' ),
																			'where'  => 'bl.blog_id=b.blog_id',
																			'type'   => 'left',
																		),
																	) 
								)	);
			}
			else
			{
				$this->DB->build( array( 
											'select'	=> 'c.comment_id as id',
											'from'		=> array( 'blog_comments' => 'c' ),
										 	'where'		=> $this->_buildWhereStatement( '', IPSSearchRegistry::get('blog.searchInKey') ),
										 	'order'		=> 'c.comment_date DESC',
										 	'limit'		=> array( $start, $perPage ),
										 	'add_join'	=> array(
																	array(
																			'from'   => array( 'blog_entries' => 'b' ),
																			'where'  => 'b.entry_id=c.entry_id',
																			'type'   => 'left',
																		),
																	array(
																			'from'   => array( 'blog_blogs' => 'bl' ),
																			'where'  => 'bl.blog_id=b.blog_id',
																			'type'   => 'left',
																		),
																	)
								)	);
			}
			$this->DB->execute();

			while( $row = $this->DB->fetch() )
			{
				$resultIds[] = $row['id'];
			}
		}

		/* Return it */
		return array( 'count' => $count['count'], 'resultSet' => $resultIds );
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