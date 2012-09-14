<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board vVERSION_NUMBER
 * Basic Calendar Search
 * Last Updated: $Date: 2011-11-03 16:44:24 -0400 (Thu, 03 Nov 2011) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Calendar
 * @link		http://www.invisionpower.com
 * @version		$Rev: 9751 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class search_engine_calendar extends search_engine
{
	/**
	 * Calendars we have access to
	 * 
	 * @var	array
	 */
 	protected $calendars	= array();

	/**
	 * Constructor
	 */
	public function __construct( ipsRegistry $registry )
	{		
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
		//-----------------------------------------
		// Init
		//-----------------------------------------

		$sort_by			= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order			= IPSSearchRegistry::get('in.search_sort_order');
		$search_term		= IPSSearchRegistry::get('in.clean_search_term');
		$sortKey			= '';
		$rows				= array();

		switch( $sort_by )
		{
			default:
			case 'date':
				$sortKey	= 'c.event_start_date';
			break;
			case 'title':
				$sortKey	= 'c.event_title';
			break;
		}
		
		//-----------------------------------------
		// Get calendars
		//-----------------------------------------
		
		if( !count($this->calendars) )
		{
			$this->DB->build( array( 'select' => 'perm_type_id as calendar_id', 'from' => 'permission_index', 'where' => "app='calendar' AND " . $this->DB->buildRegexp( "perm_view", $this->member->perm_id_array ) ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$this->calendars[]	= $r['calendar_id'];
			}
		}
		
		//-----------------------------------------
		// Get count
		//-----------------------------------------
		
		$where	= $this->_buildWhereStatement( $search_term );
		
		$count	= $this->DB->buildAndFetch(
											array( 
													'select'	=> 'COUNT(*) as total_results',
													'from'		=> 'cal_events',
 													'where'		=> str_replace( 'c.', '', $where ),													
										)  );

		if( $count['total_results'] )
		{
			//-----------------------------------------
			// Perform search
			//-----------------------------------------
			
			$this->DB->build( array( 
									'select'	=> "c.*",
									'from'		=> array( 'cal_events' => 'c' ),
									'where'		=> $where,
									'order'		=> $sortKey . ' ' . $sort_order,
									'limit'		=> array( IPSSearchRegistry::get('in.start'), IPSSearchRegistry::get('opt.search_per_page') ),
									'add_join'	=> array(
														array(
																'select'	=> 'mem.members_display_name, mem.member_group_id, mem.mgroup_others',
																'from'		=> array( 'members' => 'mem' ),
																'where'		=> "mem.member_id=c.event_member_id",
																'type'		=> 'left',
															),
														)
										)		);
			$this->DB->execute();
	
			while( $r = $this->DB->fetch() )
			{
				$rows[] = $r;
			}
		}
	
		//-----------------------------------------
		// Return results
		//-----------------------------------------
		
		return array( 'count' => $count['total_results'], 'resultSet' => $rows );
	}
	
	/**
	 * Perform the viewNewContent search
	 * Populates $this->_count and $this->_results
	 *
	 * @access	public
	 * @return	array
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

		//-----------------------------------------
		// Only content we are following?
		//-----------------------------------------
		
		if ( IPSSearchRegistry::get('in.vncFollowFilterOn' ) AND $this->memberData['member_id'] )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
			$like = classes_like::bootstrap( 'calendar', 'events' );
			
			$followedEvents	= $like->getDataByMemberIdAndArea( $this->memberData['member_id'] );
			$followedEvents = ( $followedEvents === null ) ? array() : array_keys( $followedEvents );
			
			if( !count($followedEvents) )
			{
				return array( 'count' => 0, 'resultSet' => array() );
			}
			else
			{
				$this->whereConditions['AND'][]	= "c.event_id IN(" . implode( ',', $followedEvents ) . ")";
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
					$_eventIds	= $this->_getEventIdsFromComments();
					
					if( count($_eventIds) )
					{
						$this->whereConditions['AND'][]	= "(c.event_member_id=" . $this->memberData['member_id'] . " OR c.event_id IN(" . implode( ',', $_eventIds ) . "))";
					}
					else
					{
						$this->whereConditions['AND'][]	= "c.event_member_id=" . $this->memberData['member_id'];
					}
				break;
				case 'title': 
					$this->whereConditions['AND'][]	= "c.event_member_id=" . $this->memberData['member_id'];
				break;
			}
		}

		return $this->search();
	}
	
	/**
	 * Find events we have commented on
	 *
	 * @return	array
	 */
	protected function _getEventIdsFromComments()
	{
		$ids	= array();
		
		$this->DB->build( array(
								'select'	=> $this->DB->buildDistinct('comment_eid'),
								'from'		=> 'cal_event_comments',
								'where'		=> 'comment_approved=1 AND comment_mid=' . $this->memberData['member_id'],
								'limit'		=> array( 0, 200 )
						)		);
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$ids[]	= $r['comment_eid'];
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
		//-----------------------------------------
		// Set limits
		//-----------------------------------------

		$this->search_begin_timestamp	= time() - ( 86400 * intval( $this->settings['search_ucontent_days'] ) );
		$this->whereConditions['AND'][]	= "c.event_member_id=" . intval( $member['member_id'] );
		
		//-----------------------------------------
		// Get count
		//-----------------------------------------
		
		return $this->search();
	}
		
	/**
	 * Builds the where portion of a search string
	 *
	 * @param	string	$search_term		The string to use in the search
	 * @return	string
	 */
	protected function _buildWhereStatement( $search_term )
	{		
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$where_clause	= array();

		//-----------------------------------------
		// Search term
		//-----------------------------------------
		
		if( $search_term )
		{
			$search_term	= str_replace( '&quot;', '"', $search_term );
			
			switch( IPSSearchRegistry::get('opt.searchType') )
			{
				case 'both':
				default:
					$where_clause[]	= '(' . $this->DB->buildSearchStatement( 'c.event_title', $search_term, true, false, ipsRegistry::$settings['use_fulltext'] ) . ' OR ' . $this->DB->buildSearchStatement( 'c.event_content', $search_term, true, false, ipsRegistry::$settings['use_fulltext'] ) . ')';
				break;
				
				case 'titles':
					$where_clause[]	= $this->DB->buildSearchStatement( 'c.event_title', $search_term, true, false, ipsRegistry::$settings['use_fulltext'] );
				break;
				
				case 'content':
					$where_clause[]	= $this->DB->buildSearchStatement( 'c.event_content', $search_term, true, false, ipsRegistry::$settings['use_fulltext'] );
				break;
			}
		}
		
		//-----------------------------------------
		// Date restriction
		//-----------------------------------------
		
		if( $this->search_begin_timestamp && $this->search_end_timestamp )
		{
			$where_clause[]	= $this->DB->buildBetween( $this->DB->buildUnixTimestamp( "c.event_start_date" ), $this->search_begin_timestamp, $this->search_end_timestamp );
		}
		else
		{
			if( $this->search_begin_timestamp )
			{
				$where_clause[]	= $this->DB->buildUnixTimestamp( "c.event_start_date" ) . " > {$this->search_begin_timestamp}";
			}
			
			if( $this->search_end_timestamp )
			{
				$where_clause[]	= $this->DB->buildUnixTimestamp( "c.event_start_date" ) . " < {$this->search_end_timestamp}";
			}
		}
		
		//-----------------------------------------
		// Other conditions
		//-----------------------------------------
		
		if( isset( $this->whereConditions['AND'] ) && count( $this->whereConditions['AND'] ) )
		{
			$where_clause	= array_merge( $where_clause, $this->whereConditions['AND'] );
		}

		if( isset( $this->whereConditions['OR'] ) && count( $this->whereConditions['OR'] ) )
		{
			$where_clause[]	= '( ' . implode( ' OR ', $this->whereConditions['OR'] ) . ' )';
		}

		//-----------------------------------------
		// Permissions
		//-----------------------------------------
		
		if( !$this->memberData['g_is_supmod'] )
		{
			$where_clause[]	= 'c.event_approved=1';
		}
		else
		{
			$where_clause[]	= 'c.event_approved IN (0,1)';
		}

		$where_clause[]	= "c.event_calendar_id IN(" . ( count($this->calendars) ? implode( ',', $this->calendars ) : 0 ) . ")";
		$where_clause[]	= "((c.event_private=1 AND c.event_member_id=" . $this->memberData['member_id'] . ") OR (c.event_private=0 AND " . $this->DB->buildRegexp( "c.event_perms", $this->member->perm_id_array ) . "))";
			
		//-----------------------------------------
		// Return
		//-----------------------------------------
		
		return implode( " AND ", $where_clause );
	}
	
	/**
	 * Remap standard columns (Apps can override )
	 *
	 * @param	string	$column		sql table column for this condition
	 * @return	string				column
	 */
	public function remapColumn( $column )
	{
		$column = $column == 'member_id' ? 'c.event_member_id' : $column;

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
	 * @access	public
	 * @return	boolean
	 */
	public function isBoolean()
	{
		return true;
	}
}