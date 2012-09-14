<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Basic Forum Search
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class search_engine_members extends search_engine
{
	/**
	 * Constructor
	 */
	public function __construct( ipsRegistry $registry )
	{
		parent::__construct( $registry );
	}
	
	/**
	 * Decide what type of search we're using
	 *
	 * @return	array
	 */
	public function search()
	{
		/* Hard limit */
		IPSSearchRegistry::set('set.hardLimit', ( ipsRegistry::$settings['search_hardlimit'] ) ? ipsRegistry::$settings['search_hardlimit'] : 200 );
		
		/* Ok, now because the top bar search box defaults to members default search, which is profile comments, we need
		   to convince it to default to members if the user used the top search bar with members selected */
		if ( $this->request['fromMainBar'] )
		{
			IPSSearchRegistry::set('members.searchInKey', 'members');
		}
		
		if ( IPSSearchRegistry::get('members.searchInKey') == 'comments' )
		{
			$this->request['search_app_filters']['members']['searchInKey']	= 'comments';
			
			return $this->_commentsSearch();
		}
		else
		{
			$this->request['search_app_filters']['members']['searchInKey']	= 'members';
			
			return $this->_membersSearch();
		}
	}
	
	/**
	 * Perform a comment search.
	 * Returns an array of a total count (total number of matches)
	 * and an array of IDs ( 0 => 1203, 1 => 928, 2 => 2938 ).. matching the required number based on pagination. The ids returned would be based on the filters and type of search
	 *
	 * So if we had 1000 replies, and we are on page 2 of 25 per page, we'd return 25 items offset by 25
	 *
	 * @return array
	 */
	public function _commentsSearch()
	{
		/* Not allowed to see profile information */
		if ( ! $this->memberData['g_mem_info'] )
		{
			return array( 'count' => 0, 'resultSet' => array() );
		}

		/* INIT */
		$sort_order		= IPSSearchRegistry::get('in.search_sort_order');
		$search_term	= IPSSearchRegistry::get('in.clean_search_term');
		$rows			= array();
		
		$this->DB->build( array( 
									'select'   => "s.status_id, s.status_date",
									'from'	   => 'member_status_updates s',
	 								'where'	   => $this->_buildCommentsWhereStatement( $search_term ),
									'limit'    => array(0, IPSSearchRegistry::get('set.hardLimit') + 1),
									'order'    => 's.status_date ' . $sort_order ) );

		$DB = $this->DB->execute();

		/* Fetch all to sort */
		while( $r = $this->DB->fetch( $DB ) )
		{		
			if ( IPSSearchRegistry::get('opt.noPostPreview') )
			{
				$_rows[ $r['status_id'] ] = $r;
			}
			else
			{
				$_rows[ $r['status_id'] ] = $r;
			}
		}
		
		/* Got any replies? */
		if ( count( IPSSearchRegistry::get('_internal.replyIds') ) )
		{			
			$this->DB->build( array( 
								'select'   => "r.*, r.reply_date as status_date",
								'from'	   => array( 'member_status_replies' => 'r' ),
	 							'where'	   => 'r.reply_id IN( ' . implode( ',', IPSSearchRegistry::get('_internal.replyIds') ) . ')',
								'add_join' => array_merge( array( array( 'select'	=> 's.status_id, s.status_member_id, s.status_author_id',
																		 'from'		=> array( 'member_status_updates' => 's' ),
											 							 'where'	=> 's.status_id=r.reply_status_id',
											 							 'type'		=> 'left' ),
																  array( 'select'	=> 'm.member_id as owner_id, m.members_display_name as owner_display_name, m.members_seo_name as owner_seo_name',
																		 'from'		=> array( 'members' => 'm' ),
														 				 'where'	=> 'm.member_id=s.status_member_id',
														 				 'type'		=> 'left' ),
																  array( 'select'	=> 'mem.member_id as author_id, mem.members_display_name as author_display_name, mem.members_seo_name as author_seo_name',
																		 'from'		=> array( 'members' => 'mem' ),
														 				 'where'	=> 'mem.member_id=s.status_author_id',
														 				 'type'		=> 'left' ) ) ) ) );
			/* Grab data */
			$this->DB->execute();
		
			/* Grab the results */
			while( $row = $this->DB->fetch() )
			{
				$_rows[ $row['reply_status_id'] ]    = $row;
				$_replies[ $row['reply_status_id'] ] = $row;
			}
		}
		
		/* Store */
		IPSSearchRegistry::set('_internal.replyData', $_replies );
		
		/* Fetch count */
		$count = count( $_rows );	
		$c     = 0;
		$rows  = array();
		$got   = 0;
		
		if ( $count > IPSSearchRegistry::get('set.hardLimit') )
		{
			$count = IPSSearchRegistry::get('set.hardLimit');
			
			IPSSearchRegistry::set('set.resultsCutToLimit', true );
		}

		/* Set vars */
		IPSSearch::$ask = 'status_date';
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
				
				$rows[ $got ] = $r['status_id'];
							
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
	 * Perform a MEMBER search.
	 * Returns an array of a total count (total number of matches)
	 * and an array of IDs ( 0 => 1203, 1 => 928, 2 => 2938 ).. matching the required number based on pagination. The ids returned would be based on the filters and type of search
	 *
	 * So if we had 1000 replies, and we are on page 2 of 25 per page, we'd return 25 items offset by 25
	 *
	 * @return array
	 */
	public function _membersSearch()
	{
		/* Not allowed to see profile information */
		if ( ! $this->memberData['g_mem_info'] )
		{
			return array( 'count' => 0, 'resultSet' => array() );
		}
		
		/* INIT */
		$sort_by		= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order		= IPSSearchRegistry::get('in.search_sort_order');
		$search_term	= IPSSearchRegistry::get('in.clean_search_term');
		$sortKey		= '';
		$rows			= array();

		/* Sorting */
		switch( $sort_by )
		{
			default:
			case 'date':
				$sortKey  = 'member_id';
			break;
			case 'title':
				$sortKey  = 'members_l_display_name';
			break;
		}
		
		/* Query the count */	
		$count = $this->DB->buildAndFetch( array('select'   => 'COUNT(*) as total_results',
												 'from'	    => array( 'members' => 'm' ),
				 								 'where'	=> $this->_buildWhereStatement( $search_term ),
												 'add_join' => array( array( 'from'   => array( 'profile_portal' => 'p' ),
																			'where'  => "p.pp_member_id=m.member_id",
																			'type'   => 'left' ) ) ) );
		
		
		
		/* Fetch data */
		$this->DB->build( array('select'   => 'm.*',
								'from'	   => array( 'members' => 'm' ),
								'where'	   => $this->_buildWhereStatement( $search_term ),
								'order'    => $sortKey . ' ' . $sort_order,
								'limit'    => array( IPSSearchRegistry::get('in.start'), IPSSearchRegistry::get('opt.search_per_page') ),
								'add_join' => array( array( 'select' => 'p.*',
															'from'   => array( 'profile_portal' => 'p' ),
															'where'  => "p.pp_member_id=m.member_id",
															'type'   => 'left' ) ) ) );
		$this->DB->execute();

		/* Get results */
		while ( $_row = $this->DB->fetch() )
		{
			$rows[]	= IPSMember::buildProfilePhoto( $_row );
		}
	
		/* Return it */
		return array( 'count' => $count['total_results'], 'resultSet' => $rows );
	}

	/**
	 * Perform the viewNewContent search
	 * Populates $this->_count and $this->_results
	 *
	 * @access	public
	 * @return	array
	 * @note	Does not support 'Content I have not read' because events are not tracked through topic marker class
	 * @note	Does not support 'Content I am following' because you cannot 'follow' members at this point in time
	 */
	public function viewNewContent()
	{	
		//-----------------------------------------
		// Init
		//-----------------------------------------

		IPSSearchRegistry::set('in.search_sort_by', 'date' );
		IPSSearchRegistry::set('in.search_sort_order', 'desc' );
		IPSSearchRegistry::set('opt.searchType', 'titles' );
		IPSSearchRegistry::set('opt.noPostPreview', true );
		
		//-----------------------------------------
		// Set time limit
		//-----------------------------------------
		
		if ( IPSSearchRegistry::get('in.period_in_seconds') !== false )
		{
			$this->search_begin_timestamp	= ( IPS_UNIX_TIME_NOW - IPSSearchRegistry::get('in.period_in_seconds') );
		}
		else
		{
			$this->search_begin_timestamp	= intval( $this->memberData['last_visit'] ) ? intval( $this->memberData['last_visit'] ) : IPS_UNIX_TIME_NOW;
		}

		return $this->search();
	}

	/**
	 * Builds the where portion of a search string
	 *
	 * @param	string	$search_term			The string to use in the search
	 * @param	bool	$searchType		Search only title records
	 * @return	string
	 */
	protected function _buildCommentsWhereStatement( $search_term, $searchType=null )
	{		
		/* INI */
		$where_clause	= array();
		$searchType     = ( $searchType === null ) ? IPSSearchRegistry::get('opt.searchType') : $searchType;
		$sort_order     = IPSSearchRegistry::get('in.search_sort_order');
		
		if ( $search_term )
		{
			if ( $searchType == 'titles' )
			{			
				$where_clause[] = $this->DB->buildSearchStatement( 's.status_content', $search_term, true, false, false );
			}
			else
			{
				if ( $searchType == 'content' )
				{
					$where_clause[] = $this->DB->buildSearchStatement( 'r.reply_content', $search_term, true, false, false );
				}
				else
				{
					/* Set vars */
					IPSSearch::$ask = 'status_date';
					IPSSearch::$aso = strtolower( $sort_order );
					IPSSearch::$ast = 'numerical';
			
					/* Find topic ids that match */
					$tids = array();
					$pids = array();
					
					$this->DB->build( array('select'   => "s.status_id, s.status_date",
											'from'	   => 'member_status_updates s',
			 								'where'	   => $this->_buildCommentsWhereStatement( $search_term, 'titles' ),
			 								'order'    => 's.status_date ' . $sort_order,
											'limit'    => array(0, IPSSearchRegistry::get('set.hardLimit')) ) );
								
					$i = $this->DB->execute();
					
					/* Grab the results */
					while( $row = $this->DB->fetch( $i ) )
					{
						$_rows[ $row['status_id'] ] = $row;
					}
			
					/* Sort */
					if ( count( $_rows ) )
					{
						usort( $_rows, array("IPSSearch", "usort") );
				
						foreach( $_rows as $id => $row )
						{
							$tids[] = $row['status_id'];
						}
					}
				
					/* Set vars */
					IPSSearch::$ask = 'reply_date';
					IPSSearch::$aso = strtolower( $sort_order );
					IPSSearch::$ast = 'numerical';
					
					$this->DB->build( array( 'select'   => "r.reply_id, r.reply_date, r.reply_status_id as status_id",
											 'from'	    => 'member_status_replies r',
			 								 'where'	=> $this->_buildCommentsWhereStatement( $search_term, 'content' ),
			 								 'order'    => IPSSearch::$ask . ' ' . IPSSearch::$aso,
											 'limit'    => array(0, IPSSearchRegistry::get('set.hardLimit') ) ) );
								
					$i = $this->DB->execute();
					
					/* Grab the results */
					while( $row = $this->DB->fetch( $i ) )
					{
						$tids[] = $row['status_id'];
						$pids[] = $row['reply_id'];
					}
			
					if ( count( $pids ) )
					{
						IPSSearchRegistry::set('_internal.replyIds', $pids );
					}
					
					$where_clause[] = '( s.status_id IN (' . ( count($tids) ? implode( ',', $tids ) : 0 ) .') )';
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
		
		/* Date Restrict */
		if( $this->search_begin_timestamp && $this->search_end_timestamp )
		{ 
			$where_clause[] = $this->DB->buildBetween( $searchType == 'content' ? "r.reply_date" : "s.status_date", $this->search_begin_timestamp, $this->search_end_timestamp );
		}
		else
		{
			if( $this->search_begin_timestamp )
			{
				$where_clause[] = $searchType == 'content' ? "r.reply_date > {$this->search_begin_timestamp}" : "s.status_date > {$this->search_begin_timestamp}";
			}
			
			if( $this->search_end_timestamp )
			{
				$where_clause[] = $searchType == 'content' ? "r.reply_date < {$this->search_end_timestamp}" : "s.status_date < {$this->search_end_timestamp}";
			}
		}
			
		/* Build and return the string */
		return implode( " AND ", $where_clause );
	}

	/**
	 * Builds the where portion of a search string
	 *
	 * @param	string	$search_term		The string to use in the search
	 * @return	string
	 */
	protected function _buildWhereStatement( $search_term )
	{		
		/* INI */
		$where_clause = array();
				
		if( $search_term )
		{
			switch( IPSSearchRegistry::get('opt.searchType') )
			{
				case 'both':
				default:
					$where_clause[] = "(m.members_l_display_name LIKE '%" . strtolower( $search_term ) . "%' OR p.signature LIKE '%{$search_term}%' OR p.pp_about_me LIKE '%{$search_term}%')";
				break;
				
				case 'titles':
					$where_clause[] = "m.members_l_display_name LIKE '%" . strtolower( $search_term ) . "%'";
				break;
				
				case 'content':
					$where_clause[] = "(p.signature LIKE '%{$search_term}%' OR p.pp_about_me LIKE '%{$search_term}%')";
				break;
					
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
		
		/* Date Restrict */
		if( $this->search_begin_timestamp && $this->search_end_timestamp )
		{
			$where_clause[] = $this->DB->buildBetween( "m.joined", $this->search_begin_timestamp, $this->search_end_timestamp );
		}
		else
		{
			if( $this->search_begin_timestamp )
			{
				$where_clause[] = "m.joined > {$this->search_begin_timestamp}";
			}
			
			if( $this->search_end_timestamp )
			{
				$where_clause[] = "m.joined < {$this->search_end_timestamp}";
			}
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
		if ( IPSSearchRegistry::get( 'members.searchInKey') == 'comments' )
		{
			$column = ( $column == 'member_id' ) ? 'status_author_id' : '';
		}
		
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
		return array();
	}
	
	/**
	 * Can handle boolean searching
	 *
	 * @return	boolean
	 */
	public function isBoolean()
	{
		return false;
	}
}