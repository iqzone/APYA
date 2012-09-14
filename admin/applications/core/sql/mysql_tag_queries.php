<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Tagging Queries
 * Last Updated: $LastChangedDate: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @copyright	(c) 2001 - 20011 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 *
 */

class public_tag_sql_queries extends db_driver_mysql
{	
	/**
	 * Database object handle
	 *
	 * @var		object
	 */
	private	$db;
	
	/**
	 * Constructor
	 *
	 * @param	object	Database reference
	 * @return	@e void
	 */
	public function __construct( &$obj )
	{
		$reg          = ipsRegistry::instance();
    	$this->member = $reg->member();
    	$this->DB     = $reg->DB();
    	$this->tbl	  = ips_DBRegistry::getPrefix();
	}

	/*========================================================================*/

	/**
     * Fetches a single random image (few holes)
     * @param array $album
     * @return string
     */
	public function getCloudData( $data )
    {
    	$where  = $data['where'];
    	$limit  = $data['limit'];
    	
    	$where  = ( count( $where ) ) ? ' ' . implode( ' AND ', $where )  : ' 1=1 ';
  		$prefix = $this->tbl;
  		
  		$this->DB->allow_sub_select = true;
		
		$query = "SELECT t.tag_text, COUNT(t.tag_text) as times, t.tag_meta_app, t.tag_meta_area
					FROM {$prefix}core_tags t WHERE " . $where . "
					AND t.tag_aai_lookup IN( SELECT p.tag_perm_aai_lookup FROM {$prefix}core_tags_perms p WHERE
				 " . $this->DB->buildWherePermission( $this->member->perm_id_array, 'p.tag_perm_text', true ) . " AND p.tag_perm_visible=1 ) GROUP BY t.tag_text";
				 
		if ( ! empty( $limit[0] ) || ! empty( $limit[1] ) )
		{
			$query .= "\n ORDER BY times DESC\nLIMIT " . $limit[0] . ", " . $limit[1];
		}
		else
		{
			$query .= "\n ORDER BY NULL";
		}
		
    	return $query;
	}
}