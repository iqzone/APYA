<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Generates a search and returns the results [ MySQL ]
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $ 
 */

class messengerSearch
{
	/**
	 * Results
	 *
	 * @var		array
	 */
	protected $_results = array();
	
	/**
	 * Total rows
	 *
	 * @var		int
	 */
	protected $_rows = 0;
	
	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
		$this->registry = $registry;
		$this->DB       = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang     = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache    = $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
	}
	
	/**
	 * Search
	 *
	 * @param	int			Member ID who is searching
	 * @param	string		Words to search (probably tainted at this point, so be careful!)
	 * @param	int			Offset start
	 * @param	int			Number of results to return
	 * @param	array 		Array of folders to search (send nothing to search all)
	 * @return 	boolean
	 */
	public function execute( $memberID, $words, $start=0, $end=50, $folders=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$ids      = array();
		$words    = addslashes( $words );
		$start    = intval( $start );
		$end      = intval( $end );
		$memberID = intval( $memberID );
		$results  = array();
		$dbpre    = ips_DBRegistry::getPrefix();
		
		/* Do it... */
		if ( $words )
		{
			$this->DB->allow_sub_select = 1;
			
			/* Type of search */
			if( $this->settings['use_fulltext'] AND strtolower($this->settings['mysql_tbl_type']) == 'myisam' )
			{
				$whereOne = "MATCH( t.mt_title ) AGAINST( '{$words}' IN BOOLEAN MODE )";
				$whereTwo = "MATCH( p.msg_post ) AGAINST( '{$words}' IN BOOLEAN MODE )";
			}
			else
			{
				$whereOne = "t.mt_title LIKE '%{$words}%'";
				$whereTwo = "p.msg_post LIKE '%{$words}%'";
			}
			
			$this->DB->query( "SELECT SQL_CALC_FOUND_ROWS mt_id, mt_first_msg_id FROM ( ( SELECT t.mt_id, t.mt_first_msg_id
									FROM {$dbpre}message_topics t, {$dbpre}message_topic_user_map m
									WHERE (t.mt_id=m.map_topic_id AND m.map_user_id=" . $memberID . " AND m.map_user_banned=0 AND m.map_user_active=1 AND t.mt_is_deleted=0) AND {$whereOne}
									ORDER BY t.mt_last_post_time DESC )
								UNION
								( SELECT p.msg_topic_id, p.msg_id
									FROM {$dbpre}message_posts p, {$dbpre}message_topic_user_map m
									WHERE (p.msg_topic_id=m.map_topic_id AND m.map_user_id=" . $memberID . " AND m.map_user_banned=0 AND m.map_user_active=1) AND {$whereTwo}
									ORDER BY p.msg_date DESC ) ) as tbl
								GROUP BY mt_id
								LIMIT $start, $end" );
								
			while( $row = $this->DB->fetch() )
			{
				$ids[] = $row['mt_id'];
			}
			
			$this->DB->query( "SELECT FOUND_ROWS() as row_your_boat" );
			$row = $this->DB->fetch();
			
			/* Set rows var */
			$this->_rows = intval( $row['row_your_boat'] ); // comic genius
			
			$this->DB->allow_sub_select = 0;
			
			/* Now fetch some actual data! */
			if ( count( $ids ) )
			{
				$this->DB->build( array( 'select'   => 't.*',
										 'from'     => array( 'message_topics' => 't' ),
										 'where'    => 'mt_id IN (' . implode( ",", $ids ) . ')',
										 'order'    => 't.mt_last_post_time DESC',
										 'add_join' => array( array( 'select' => 'map.*',
																	 'from'   => array( 'message_topic_user_map' => 'map' ),
																	 'where'  => 'map.map_topic_id=t.mt_id',
																	 'type'   => 'left' ),
															  array( 'select' => 'p.*',
																	 'from'   => array( 'message_posts' => 'p' ),
																	 'where'  => 'p.msg_id=t.mt_first_msg_id',
																	 'type'   => 'left' ) ) ) );
				$this->DB->execute();
				
				while ( $row = $this->DB->fetch() )
				{
					$results[ $row['mt_id'] ] = $row;
				}
				
				$this->_results = $results;
			}
		}
	}

	/**
	 * Fetch results
	 *
	 * @return	array
	 */
	public function fetchResults()
	{
		return ( is_array( $this->_results ) ) ? $this->_results : array();
	}
	
	/**
	 * Fetch total result row count
	 *
	 * @return	int
	 */
	public function fetchTotalRows()
	{
		return intval( $this->_rows );
	}
}