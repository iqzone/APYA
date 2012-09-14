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

class search_engine_core extends search_engine
{
	/**
	 * Constructor
	 *
	 * @param	object	ipsRegistry reference
	 * @return	@e void
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
				$sortKey  = 'id';
			break;
			case 'title':
				$sortKey  = 'title';
			break;
		}
		
		/* Query the count */	
		$count = $this->DB->buildAndFetch( array('select'   => 'COUNT(*) as total_results',
												 'from'	    => 'faq',
		 										 'where'	=> $this->_buildWhereStatement( $search_term ),
										)		);
		
		
		
		/* Fetch data */
		$this->DB->build( array( 'select'   => '*',
							     'from'	    => 'faq',
								 'where'	=> $this->_buildWhereStatement( $search_term ),
								 'order'    => $sortKey . ' ' . $sort_order,
								 'limit'    => array( IPSSearchRegistry::get('in.start'), IPSSearchRegistry::get('opt.search_per_page') ),
						)		);
		$this->DB->execute();

		/* Get results */
		while ( $_row = $this->DB->fetch() )
		{
			$rows[] = $_row;
		}
	
		/* Return it */
		return array( 'count' => $count['total_results'], 'resultSet' => $rows );
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
					$where_clause[] = "(title LIKE '%{$search_term}%' OR text LIKE '%{$search_term}%' OR description LIKE '%{$search_term}%')";
				break;
				
				case 'titles':
					$where_clause[] = "title LIKE '%{$search_term}%'";
				break;
				
				case 'content':
					$where_clause[] = "(text LIKE '%{$search_term}%' OR description LIKE '%{$search_term}%')";
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
		if ( $column == 'member_id' )
		{
			return '';
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