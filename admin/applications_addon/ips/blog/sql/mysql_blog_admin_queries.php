<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Last Updated: $LastChangedDate: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Blog
 * @link		http://www.invisionpower.com
 * @since		27th January 2004
 * @version		$Rev: 4 $
 *
 */



class sql_blog_admin_queries extends db_driver_mysql
{
     protected $db  = "";
     protected $tbl = "";

    /*========================================================================*/
    // Set up...
    /*========================================================================*/

    function sql_blog_admin_queries( &$obj )
    {
    	$this->db	= &$obj;
		$this->tbl	= ips_DBRegistry::getPrefix();
    }

    /*========================================================================*/


    function blog_stats( $a )
    {
    	return "SELECT COUNT(*) AS num_blogs, SUM(CASE WHEN blog_view_level='private' THEN 1 ELSE 0 END) AS num_private, SUM(CASE WHEN blog_type='local' THEN 1 ELSE 0 END) AS num_local,
					   SUM(CASE WHEN blog_type='external' THEN 1 ELSE 0 END) AS num_external, SUM(blog_num_entries) AS total_entries, SUM(blog_num_drafts) AS total_drafts,
       				   SUM(blog_num_comments) AS total_comments
				FROM {$this->tbl}blog_blogs b LEFT JOIN {$this->tbl}blog_lastinfo li ON b.blog_id=li.blog_id";
    }

    function blog_groups( $a )
    {
      return "SELECT a.g_id, a.g_title, COUNT(b.id) as num_members, COUNT(c.blog_id) as num_blogs
      		  FROM {$this->tbl}groups a
      		  LEFT JOIN {$this->tbl}members b ON (b.member_group_id = a.g_id)
      		  LEFT JOIN {$this->tbl}blog_blogs c ON (b.id = c.member_id)
		      GROUP BY a.g_id, a.g_title";
    }

} // end class


?>