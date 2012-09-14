<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Basic Gallery Search
 * Last Updated: $Date: 2011-11-04 09:28:03 -0400 (Fri, 04 Nov 2011) $
 * </pre>
 *
 * @author 		$Author: mmecham $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Rev: 9757 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class search_engine_gallery extends search_engine
{
	/**
	 * Constructor
	 */
	public function __construct( ipsRegistry $registry )
	{
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
		ipsRegistry::instance()->setClass( 'gallery', new $classToLoad( ipsRegistry::instance() ) );
		
		$this->_images = $registry->gallery->helper('image');
		$this->_albums = $registry->gallery->helper('albums');
		
		/* Hard limit */
		IPSSearchRegistry::set('set.hardLimit', ( ipsRegistry::$settings['search_hardlimit'] ) ? ipsRegistry::$settings['search_hardlimit'] : 200 );

		$searchIn	= ipsRegistry::$request['search_app_filters']['gallery']['searchInKey'];

		IPSSearchRegistry::set( 'gallery.searchInKey', in_array( $searchIn, array( 'images', 'comments', 'albums' ) ) ? $searchIn : 'images' );
		
		ipsRegistry::$request['search_app_filters']['gallery']['searchInKey'] = IPSSearchRegistry::get('gallery.searchInKey');
		
		parent::__construct( $registry );
	}
	
	/**
	 * Decide what type of search we're using
	 *
	 * @access	public
	 * @return	@e array
	 */
	public function search()
	{ 
		if ( IPSSearchRegistry::get('gallery.searchInKey') == 'images' )
		{
			return $this->_imagesSearch();
		}
		else if ( IPSSearchRegistry::get('gallery.searchInKey') == 'albums' )
		{
			return $this->_albumsSearch();
		}
		else
		{
			return $this->_commentsSearch();
		}
	}
	
	/**
	 * Perform a comment search.
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
		$count       		= 0;
		$results     		= array();
		$sort_by     		= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order         = IPSSearchRegistry::get('in.search_sort_order');
		$search_term        = IPSSearchRegistry::get('in.clean_search_term');
		$content_title_only = IPSSearchRegistry::get('opt.searchTitleOnly');
		$order_dir 			= ( $sort_order == 'asc' ) ? 'asc' : 'desc';
		$rows    			= array();
		$count   			= 0;
		$c                  = 0;
		$got     			= 0;
		$sortKey			= '';
		$sortType			= '';
		$group_by			= 'c.img_id';
		
		if ( IPSSearchRegistry::get('opt.noPostPreview') OR IPSSearchRegistry::get('opt.searchTitleOnly') )
		{
			$group_by = 'c.pid';
		}

		/* Sorting */
		switch( $sort_by )
		{
			default:
			case 'date':
				$sortKey  = 'post_date';
				$sortType = 'numerical';
			break;
		}

		/* Fetch data */		
		$this->DB->build( array( 
									'select'   => "c.*",
									'from'	   => array( 'gallery_comments' => 'c' ),
									'where'	   => $this->_buildWhereStatement( $search_term, $content_title_only, 'comments' ),
									'limit'    => array(0, IPSSearchRegistry::get('set.hardLimit') + 1),
									'group'    => $group_by,
									'add_join' => array( array( 'from'   => array( 'gallery_images' => 'g' ),
																'where'  => "c.img_id=g.id",
																'type'   => 'left' ),
														array(  'from'   => array( 'gallery_albums_main' => 'a' ),
																'where'  => 'g.img_album_id=a.album_id',
																'type'   => 'left' ) ) ) );
		$o = $this->DB->execute();
		
		/* Fetch count */
		$count = intval( $this->DB->getTotalRows( $o ) );
		
		if ( $count > IPSSearchRegistry::get('set.hardLimit') )
		{
			$count = IPSSearchRegistry::get('set.hardLimit');
			
			IPSSearchRegistry::set('set.resultsCutToLimit', true );
		}

		/* Fetch to sort */
		while ( $r = $this->DB->fetch() )
		{
			$_rows[ $r['pid'] ] = $r;
		}
		
		/* Set vars */
		IPSSearch::$ask = $sortKey;
		IPSSearch::$aso = strtolower( $order_dir );
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
				
				$rows[ $got ] = $r['pid'];
							
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
	 * Perform an image search.
	 * Returns an array of a total count (total number of matches)
	 * and an array of IDs ( 0 => 1203, 1 => 928, 2 => 2938 ).. matching the required number based on pagination. The ids returned would be based on the filters and type of search
	 *
	 * So if we had 1000 replies, and we are on page 2 of 25 per page, we'd return 25 items offset by 25
	 *
	 * @access public
	 * @return array
	 */
	public function _imagesSearch()
	{ 
		/* INIT */ 
		$count       		= 0;
		$results     		= array();
		$sort_by     		= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order         = IPSSearchRegistry::get('in.search_sort_order');
		$search_term        = IPSSearchRegistry::get('in.clean_search_term');
		$content_title_only = IPSSearchRegistry::get('opt.searchTitleOnly');
		$search_tags        = IPSSearchRegistry::get('in.raw_search_tags');
		$order_dir 			= ( $sort_order == 'asc' ) ? 'asc' : 'desc';
		$rows    			= array();
		$count   			= 0;
		$c                  = 0;
		$got     			= 0;
		$sortKey			= '';
		$sortType			= '';
		$_rows				= array();
		
		if ( IPSSearchRegistry::get('opt.noPostPreview') OR IPSSearchRegistry::get('opt.searchTitleOnly') )
		{
			$group_by = 'g.id';
		}

		/* Sorting */
		switch( $sort_by )
		{
			default:
			case 'date':
				$sortKey  = 'idate';
				$sortType = 'numerical';
			break;
			case 'title':
				$sortKey  = 'caption';
				$sortType = 'string';
			break;
			case 'views':
				$sortKey	= 'views';
				$sortType	= 'numerical';
			break;
			case 'comments':
				$sortKey	= 'comments';
				$sortType	= 'numerical';
			break;
		}
		
		/* Fetch data */
		if ( $search_term )
		{
			$this->DB->build( array('select'   => "g.*",
									'from'	   => array( 'gallery_images' => 'g' ),
									'where'	   => $this->_buildWhereStatement( $search_term, $content_title_only ),
									'limit'    => array(0, IPSSearchRegistry::get('set.hardLimit') + 1),
									'order'    => 'g.' . $sortKey . ' ' . $order_dir,
									'group'    => $group_by,
									'add_join' => array( array( 'from'   => array( 'gallery_albums_main' => 'a' ),
																'where'  => 'g.img_album_id=a.album_id',
																'type'   => 'left' ) ) ) );
			$o = $this->DB->execute();
			
			/* Fetch to sort */
			while ( $r = $this->DB->fetch() )
			{
				$_rows[ $r['id'] ] = $r;
			}
		}
		
		/* Check tags */
		if ( $search_tags && $this->settings['tags_enabled'] )
		{ 
			$tags = $this->registry->galleryTags->search( $search_tags, array( 'meta_app'		 => 'gallery',
																			   'meta_area'		 => 'images',
																			   'meta_id'		 => array_keys( $_rows ),
																			   'sortOrder'		 => $sort_order ) );
			
			if ( is_array( $tags ) And count( $tags ) )
			{
				$_tagIds = array();
				$_rows   = array();
				
				foreach( $tags as $id => $data )
				{
					$_tagIds[] = $data['tag_meta_id'];
				}
			
				/* Fetch data */
				$this->DB->build( array('select'   => "g.*",
										'from'	   => array( 'gallery_images' => 'g' ),
										'where'	   => 'g.id IN (' . implode( ",", $_tagIds ) . ')',
										'limit'    => array(0, IPSSearchRegistry::get('set.hardLimit') + 1),
										'order'    => 'g.' . $sortKey . ' ' . $order_dir,
										'add_join' => array( array( 'from'   => array( 'gallery_albums_main' => 'a' ),
																	'where'  => 'g.img_album_id=a.album_id',
																	'type'   => 'left' ) ) ) );
				$this->DB->execute();
				
				/* Fetch to sort */
				while ( $r = $this->DB->fetch() )
				{
					$_rows[ $r['id'] ] = $r;
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
		IPSSearch::$aso = strtolower( $order_dir );
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
	 * Perform an albums search.
	 * Returns an array of a total count (total number of matches)
	 * and an array of IDs ( 0 => 1203, 1 => 928, 2 => 2938 ).. matching the required number based on pagination. The ids returned would be based on the filters and type of search
	 *
	 * So if we had 1000 replies, and we are on page 2 of 25 per page, we'd return 25 items offset by 25
	 *
	 * @access public
	 * @return array
	 */
	public function _albumsSearch()
	{
		/* INIT */ 
		$count       		= 0;
		$results     		= array();
		$sort_by     		= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order         = IPSSearchRegistry::get('in.search_sort_order');
		$search_term        = IPSSearchRegistry::get('in.clean_search_term');
		$content_title_only = IPSSearchRegistry::get('opt.searchTitleOnly');
		$order_dir 			= ( $sort_order == 'asc' ) ? 'asc' : 'desc';
		$rows    			= array();
		$count   			= 0;
		$c                  = 0;
		$got     			= 0;
		$sortKey			= '';
		$sortType			= '';

		if ( IPSSearchRegistry::get('opt.noPostPreview') OR IPSSearchRegistry::get('opt.searchTitleOnly') )
		{
			$group_by = '';
		}

		/* Sorting */
		switch( $sort_by )
		{
			default:
			case 'date':
				$sortKey  = 'album_last_img_date';
				$sortType = 'numerical';
			break;
		}

		/* Fetch albums */
		$albums = $this->_albums->fetchAlbumsByFilters( array( 'isViewable'        => true,
													 		   'albumNameContains' => $search_term,
															   'sortKey'		   => $sortKey,
													 		   'sortOrder'		   => $sort_order,
													 		   'limit'			   => IPSSearchRegistry::get('set.hardLimit') + 1 ) );
	
		/* Fetch count */
		$count = count( $albums );
		
		if ( $count > IPSSearchRegistry::get('set.hardLimit') )
		{
			$count = IPSSearchRegistry::get('set.hardLimit');
			
			IPSSearchRegistry::set('set.resultsCutToLimit', true );
		}
				
		/* Set vars */
		IPSSearch::$ask = $sortKey;
		IPSSearch::$aso = strtolower( $order_dir );
		IPSSearch::$ast = $sortType;
		
		/* Sort */
		if ( count( $albums ) )
		{
			/* Build result array */
			foreach( $albums as $id => $r )
			{
				$c++;
				
				if ( IPSSearchRegistry::get('in.start') AND IPSSearchRegistry::get('in.start') >= $c )
				{
					continue;
				}
				
				$rows[ $got ] = $id;
							
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
	 * Perform the viewNewContent search
	 * Populates $this->_count and $this->_results
	 *
	 * @access	public
	 * @return	nothin'
	 */
	public function viewNewContent()
	{
		/* INIT */
		$imgIds		= array();
		$oldStamp	= 0;
		$start		= IPSSearchRegistry::get('in.start');
		$perPage	= IPSSearchRegistry::get('opt.search_per_page');
		$seconds	= IPSSearchRegistry::get('in.period_in_seconds');
		
		IPSSearchRegistry::set( 'opt.searchTitleOnly', true );
		
		/* Period filtering */
		if ( $seconds !== false )
		{
			$oldStamp = IPS_UNIX_TIME_NOW - $seconds;
		}
		else
		{
			/* More vars... */
			$check		= IPS_UNIX_TIME_NOW - ( 86400 * $this->settings['topic_marking_keep_days'] );
			$imgIds		= $this->registry->getClass('classItemMarking')->fetchCookieData( 'gallery', 'items' );
			$oldStamp	= $this->registry->getClass('classItemMarking')->fetchOldestUnreadTimestamp( array(), 'gallery' );
			
			/* Finalize times */
			if ( ! $oldStamp OR $oldStamp == IPS_UNIX_TIME_NOW )
			{
				$oldStamp = intval( $this->memberData['last_visit'] );
			}
		
			if ( $this->memberData['_cache']['gb_mark__gallery'] && ( $this->memberData['_cache']['gb_mark__gallery'] < $oldStamp ) )
			{
				$oldStamp = $this->memberData['_cache']['gb_mark__gallery'];
			}
			
			if ( ! $this->memberData['bw_vnc_type'] )
			{
				$oldStamp   = IPSLib::fetchHighestNumber( array( intval( $this->memberData['_cache']['gb_mark__gallery'] ), intval( $this->memberData['last_visit'] ) ) );
			}
			
			/* Older than 3 months.. then limit */
			if ( $oldStamp < $check )
			{
				$oldStamp = $check;
			}
		}

		/* Start Where */
		$where		= array();
		$where[]	= $this->_buildWhereStatement( '', false, IPSSearchRegistry::get('gallery.searchInKey') );

		/* Based on oldest timestamp */
		if ( IPSSearchRegistry::get('gallery.searchInKey') == 'comments' )
		{
			$where[] = "c.post_date > " . $oldStamp;
		}
		else
		{
			$where[] = "g.idate > " . $oldStamp;
		}
		
		/* Set read tids */
		if ( count( $imgIds ) )
		{
			$where[] = "g.id NOT IN (" . implode( ",", array_keys( $imgIds ) ) . ')';
		}

		$where = implode( " AND ", $where );

		/* Set up some vars */
		IPSSearchRegistry::set('set.resultCutToDate', $oldStamp );
		
		/* Get the results */
		return $this->_getNonSearchData( $where, $oldStamp );
	}
	
	/**
	 * Perform the viewUserContent search
	 * Populates $this->_count and $this->_results
	 *
	 * @access	public
	 * @return	@e array
	 */
	public function viewUserContent( $member )
	{
		/* Ensure we limit by date */
		$this->settings['search_ucontent_days'] = ( $this->settings['search_ucontent_days'] ) ? $this->settings['search_ucontent_days'] : 365;
		
		/* Start Where */
		$where		= array();
		$where[]	= $this->_buildWhereStatement( '', false, IPSSearchRegistry::get('gallery.searchInKey') );
		
		/* Search by author */
		$where[]    = "g.member_id=" . intval( $member['member_id'] );
	
		if ( $this->settings['search_ucontent_days'] )
		{
			$where[] = "g.idate > " . ( time() - ( 86400 * intval( $this->settings['search_ucontent_days'] ) ) );
		}
		
		$where = implode( " AND ", $where );
		
		/* Get the results */
		return $this->_getNonSearchData( $where );
	}
	
	/**
	 * Perform the search for viewUserContent, viewActiveContent, and viewNewContent
	 *
	 * @access	public
	 * @return	@e array
	 */
	public function _getNonSearchData( $where, $date=0 )
	{
		/* Init */
		$start		= IPSSearchRegistry::get('in.start');
		$perPage	= IPSSearchRegistry::get('opt.search_per_page');
		IPSSearchRegistry::set( 'in.search_sort_by'   , 'date' );
		//IPSSearchRegistry::set( 'in.search_sort_order', 'desc' );
		IPSSearchRegistry::set( 'gallery.searchInKey'	, in_array( $this->request['search_app_filters']['gallery']['searchInKey'], array( 'images', 'comments', 'albums' ) ) ? $this->request['search_app_filters']['gallery']['searchInKey'] : 'images' );
		
		if ( IPSSearchRegistry::get('gallery.searchInKey') == 'albums' )
		{
			$albums = $this->_albums->fetchAlbumsByFilters( array( 'isViewable'        => true,
													 		       'unixCutOff' 	   => $date,
																   'getTotalCount'	   => true,
															       'sortKey'		   => 'date',
													 		       'sortOrder'		   => 'desc',
																   'offset'			   => $start,
													 		       'limit'			   => $perPage ) );
	
			/* Fetch count */
			$count  = array( 'count' => $this->_albums->getCount() );
			$imgIds = array_keys( $albums );
		}
		else
		{
			/* Fetch the count */
			if( IPSSearchRegistry::get('gallery.searchInKey') == 'images' )
			{
				$count = $this->DB->buildAndFetch(  array(  'select'	=> 'count(*) as count',
															'from'		=> array( 'gallery_images' => 'g' ),
															'where'		=> $where,
															'add_join'	=> array( array( 'from'   => array( 'gallery_albums_main' => 'a' ),
																						 'where'  => 'g.img_album_id=a.album_id',
																						 'type'   => 'left' ) ) ) );
			}
			else
			{
				$count = $this->DB->buildAndFetch(  array(  'select'	=> 'count(*) as count',
															'from'		=> array( 'gallery_comments' => 'c' ),
															'where'		=> $where,
															'add_join'	=> array( array( 'from'   => array( 'gallery_images' => 'g' ),
																						 'where'  => 'c.img_id=g.id',
																						 'type'   => 'left' ),
																				  array( 'from'   => array( 'gallery_albums_main' => 'a' ),
																						 'where'  => 'g.img_album_id=a.album_id',
																						 'type'   => 'left') ) ) );
			}
	
			/* Fetch the data */
			$imgIds = array();
			
			if ( $count['count'] )
			{
				if( IPSSearchRegistry::get('gallery.searchInKey') == 'images' )
				{
					$this->DB->build( array(    'select'	=> 'g.id',
												'from'		=> array( 'gallery_images' => 'g' ),
											 	'where'		=> $where,
											 	'order'		=> 'g.idate DESC',
											 	'limit'		=> array( $start, $perPage ),
											 	'add_join'	=> array( array( 'from'   => array( 'gallery_albums_main' => 'a' ),
																			 'where'  => 'g.img_album_id=a.album_id',
																			 'type'   => 'left' ) ) ) );
				}
				else
				{
					$this->DB->build( array(    'select'	=> 'c.pid as id',
												'from'		=> array( 'gallery_comments' => 'c' ),
											 	'where'		=> $where,
											 	'order'		=> 'c.post_date DESC',
											 	'limit'		=> array( $start, $perPage ),
											 	'add_join'	=> array( array(   'from'   => array( 'gallery_images' => 'g' ),
																			   'where'  => 'g.id=c.img_id',
																			   'type'   => 'left' ),
																		array( 'from'   => array( 'gallery_albums_main' => 'a' ),
																			   'where'  => 'g.img_album_id=a.album_id',
																			   'type'   => 'left' ) ) ) );
				}
				$this->DB->execute();
	
				while( $row = $this->DB->fetch() )
				{
					$imgIds[] = $row['id'];
				}
			}
		}

		/* Return it */
		return array( 'count' => $count['count'], 'resultSet' => $imgIds );
	}
	
	/**
	 * Builds the where portion of a search string
	 *
	 * @access	private
	 * @param	string	$search_term		The string to use in the search
	 * @param	bool	$content_title_only	Search only title records
	 * @return	@e string
	 */
	private function _buildWhereStatement( $search_term, $content_title_only=false, $type='image' )
	{
		/* INI */
		$where_clause	= array();
		$searchInCats	= array();

		if( $search_term )
		{
			$search_term	= trim($search_term);
			
			if( $type == 'image' )
			{
				if( $content_title_only )
				{
					$where_clause[] = "g.caption LIKE '%{$search_term}%'";
				}
				else
				{
					$where_clause[] = "(g.caption LIKE '%{$search_term}%' OR g.description LIKE '%{$search_term}%')";
				}
			}
			else
			{
				$where_clause[] = "c.comment LIKE '%{$search_term}%'";
			}
		}
		
		/* Exclude some items */
		if ( ! $this->memberData['g_is_supmod'] )
		{
			if( $type == 'comments' )
			{
				$where_clause[] = 'c.approved=1';	
			}
			
			/* Approved only */
			$where_clause[]	= 'g.approved=1';	
		}
		
		/* Date Restrict */
		if( $this->search_begin_timestamp && $this->search_end_timestamp )
		{
			if ( $type == 'comments' )
			{
				$where_clause[] = $this->DB->buildBetween( "c.post_date", $this->search_begin_timestamp, $this->search_end_timestamp );
			}
			else
			{
				$where_clause[] = $this->DB->buildBetween( "g.idate", $this->search_begin_timestamp, $this->search_end_timestamp );
			}
		}
		else
		{
			if ( $type == 'comments' )
			{
				if ( $this->search_begin_timestamp )
				{
					$where_clause[] = "c.post_date > {$this->search_begin_timestamp}";
				}
				
				if ( $this->search_end_timestamp )
				{
					$where_clause[] = "c.post_date < {$this->search_end_timestamp}";
				}
			}
			else
			{
				if( $this->search_begin_timestamp )
				{
					$where_clause[] = "g.idate > {$this->search_begin_timestamp}";
				}
				
				if( $this->search_end_timestamp )
				{
					$where_clause[] = "g.idate < {$this->search_end_timestamp}";
				}
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
	
		/* Are we filtering categories? */
		if ( ! empty( ipsRegistry::$request['search_app_filters']['gallery']['albumids'] ) AND count( ipsRegistry::$request['search_app_filters']['gallery']['albumids'] ) )
		{
			foreach(  ipsRegistry::$request['search_app_filters']['gallery']['albumids'] as $albumId )
			{
				$albums[] = $albumId;
			}
			
			if ( count( $albums ) )
			{
				/* no need to do any further checks because we check image */
				$where_clause[] = "g.img_album_id IN(" . implode( ',', $albums ) . ')';
			}
		}
		
		if ( ipsRegistry::$request['search_app_filters']['gallery']['excludeAlbums'] )
		{
			$where_clause[] = 'a.album_is_global=1';
		}
			
		/* Add to where */
		if ( $this->memberData['member_id'] )
		{
			$_or[] = "( g." . $this->_images->sqlWherePrivacy( array( 'friend', 'public', 'private', 'galbum' ) ) . ' AND g.member_id=' . $this->memberData['member_id'] . ' )';
			$_or[] = "( g." . $this->_images->sqlWherePrivacy( array( 'public', 'galbum' ) ) . ' AND g.member_id !=' . $this->memberData['member_id'] . ' AND ( ' .  $this->DB->buildWherePermission( $this->member->perm_id_array, 'g.image_parent_permission', true ) . ') )';
		
			if ( is_array( $this->memberData['_cache']['friends'] ) AND count( $this->memberData['_cache']['friends'] ) )
			{
				$_or[] = "( g." . $this->_images->sqlWherePrivacy( array( 'public', 'friend' ) ) . ' AND g.member_id IN(' . implode( ",", array_slice( array_keys( $this->memberData['_cache']['friends'] ), 0, 150 ) ) . ') )';
			}
			
			/* add in */
			if ( count( $_or ) )
			{
				$where_clause[] = '( ' . implode( " OR ", $_or ) . ' )';
			}
		}
		else
		{
			$where_clause[] = "g." . $this->_images->sqlWherePrivacy( array( 'public', 'galbum' ) ) . ' AND ( ' .  $this->DB->buildWherePermission( $this->member->perm_id_array, 'g.image_parent_permission', true ) . ')';
		}
			
		/* Build and return the string */
		return implode( " AND ", $where_clause );
	}
	
	/**
	 * Remap standard columns (Apps can override )
	 *
	 * @access	public
	 * @param	string	$column		sql table column for this condition
	 * @return	@e string				column
	 * @return	@e void
	 */
	public function remapColumn( $column )
	{
		$column = $column == 'member_id' ? 'g.member_id' : $column;

		return $column;
	}
		
	/**
	 * Returns an array used in the searchplugin's setCondition method
	 *
	 * @access	public
	 * @param	array 	$data	Array of forums to view
	 * @return	@e array 	Array with column, operator, and value keys, for use in the setCondition call
	 */
	public function buildFilterSQL( $data )
	{
		/* INIT */
		$return		= array();
	
		/* Set up some defaults */
		IPSSearchRegistry::set( 'opt.noPostPreview'  , false );
		IPSSearchRegistry::set( 'opt.onlySearchPosts', false );	
		
		return array();
	}

	/**
	 * Can handle boolean searching
	 *
	 * @access	public
	 * @return	@e boolean
	 */
	public function isBoolean()
	{
		return false;
	}
}