<?php
/**
 * @file		mysql_topics_queries.php 	Topics queries
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		13 Gen 2012
 * $LastChangedDate: 2012-01-23 05:10:45 -0500 (Mon, 23 Jan 2012) $
 * @version		v3.3.3
 * $Revision: 10165 $
 */

/**
 * @class		topics_sql_queries
 * @brief		Topics queries
 */
class topics_sql_queries extends db_driver_mysql
{	
	/**
	 * Database object handle
	 *
	 * @var		$db
	 */
	private	$db;
	
	/**
	 * Constructor
	 *
	 * @param	object		$obj		Database reference
	 * @return	@e void
	 */
	public function __construct( &$obj )
	{
		$reg          = ipsRegistry::instance();
    	$this->DB     = $reg->DB();
    	$this->tbl	  = ips_DBRegistry::getPrefix();
	}
	
	/**
     * Restores a recent post from the posts table
     * 
     * @param	array		$data		Query arguments
     * @return	@e string
     */
	public function restoreRecentPost( $data )
    {
    	$this->DB->allow_sub_select = true;
		
		$query ="REPLACE INTO {$this->tbl}forums_recent_posts (post_id, post_topic_id, post_forum_id, post_author_id, post_date)
								( SELECT p.pid, p.topic_id, ( SELECT t.forum_id FROM {$this->tbl}topics t where t.tid=p.topic_id), p.author_id, p.post_date
								FROM {$this->tbl}posts p 
								WHERE p." . implode ( 'AND p.', $data['query'] ) . " AND p.author_id > 0 AND p.post_date > " . $data['date'] .")";
		
		return $query;
	}
}