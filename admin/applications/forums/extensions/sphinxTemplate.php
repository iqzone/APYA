<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Sphinx template file
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
 * @since		3.0.0
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

$appSphinxTemplate	= <<<EOF

################################# --- FORUM --- ##############################
source <!--SPHINX_CONF_PREFIX-->forums_search_posts_main : <!--SPHINX_CONF_PREFIX-->ipb_source_config
{
	# Set our forum PID counter
	<!--SPHINX_DB_SET_NAMES-->
	sql_query_pre	= REPLACE INTO <!--SPHINX_DB_PREFIX-->cache_store VALUES( 'sphinx_forums_counter_posts', (SELECT max(pid) FROM <!--SPHINX_DB_PREFIX-->posts), 0, UNIX_TIMESTAMP(), 0 )
	
	# Query posts for the main source
	sql_query		= SELECT p.pid, p.pid as search_id, p.author_id, p.post_date, REPLACE( p.post, '-', '&\#8208') as post, p.topic_id, p.queued, \
							 t.tid, LOWER(t.title) as tordinal, REPLACE( t.title, '-', '&\#8208') as title, t.views, t.posts, t.forum_id, t.last_post, t.state, t.start_date, t.starter_id, t.last_poster_id, t.topic_firstpost, \
							CASE WHEN t.approved = -1 THEN 1 ELSE 0 END AS soft_deleted, \
							CASE WHEN t.approved = -1 THEN 0 ELSE t.approved END AS approved, \
							CASE WHEN t.topic_archive_status IN (0,3) THEN 0 ELSE 1 END AS archive_status, \
							CONCAT( SUBSTRING( t.last_post, 2, 8 ), '0', LPAD( t.tid, 10, 0 ) ) as last_post_group \
					  FROM <!--SPHINX_DB_PREFIX-->posts p \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->topics t ON ( p.topic_id=t.tid )
	
	# Fields	
	sql_attr_uint			= queued
	sql_attr_uint			= approved
	sql_attr_uint			= soft_deleted
	sql_attr_uint			= archive_status
	sql_attr_uint			= search_id
	sql_attr_uint			= forum_id
	sql_attr_timestamp	    = post_date
	sql_attr_timestamp	    = last_post
	sql_attr_timestamp	    = start_date
	sql_attr_uint			= author_id
	sql_attr_uint			= starter_id
	sql_attr_uint			= tid
	sql_attr_uint			= posts
	sql_attr_uint			= views
	sql_attr_str2ordinal	= tordinal
	sql_attr_bigint			= last_post_group
	sql_attr_multi			= uint tag_id from query; SELECT t.topic_firstpost, c.tag_id FROM <!--SPHINX_DB_PREFIX-->core_tags c LEFT JOIN <!--SPHINX_DB_PREFIX-->topics t ON (t.tid=c.tag_meta_id) WHERE c.tag_meta_app='forums' AND c.tag_meta_area='topics'
	
	sql_ranged_throttle	= 0
}

source <!--SPHINX_CONF_PREFIX-->forums_search_posts_delta : <!--SPHINX_CONF_PREFIX-->forums_search_posts_main
{
	# Override the base sql_query_pre
	<!--SPHINX_DB_SET_NAMES-->
	sql_query_pre = 
	
	# Query posts for the delta source
	sql_query		= SELECT p.pid, p.pid as search_id, p.author_id, p.post_date, REPLACE( p.post, '-', '&\#8208') as post, p.topic_id, p.queued, \
							 t.tid, LOWER(t.title) as tordinal, REPLACE( t.title, '-', '&\#8208') as title, t.views, t.posts, t.forum_id, t.last_post, t.state, t.start_date, t.starter_id, t.last_poster_id, t.topic_firstpost, \
							 CASE WHEN t.approved = -1 THEN 1 ELSE 0 END AS soft_deleted, \
						 	 CASE WHEN t.approved = -1 THEN 0 ELSE t.approved END AS approved, \
						 	 CASE WHEN t.topic_archive_status IN (0,3) THEN 0 ELSE 1 END AS archive_status, \
							 CONCAT( SUBSTRING( t.last_post, 2, 8 ), '0', LPAD( t.tid, 10, 0 ) ) as last_post_group \
					  FROM <!--SPHINX_DB_PREFIX-->posts p \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->topics t ON ( p.topic_id=t.tid ) \
					  WHERE p.pid > ( SELECT cs_value FROM <!--SPHINX_DB_PREFIX-->cache_store WHERE cs_key='sphinx_forums_counter_posts' )
					  
	sql_query_killlist = SELECT pid FROM <!--SPHINX_DB_PREFIX-->posts WHERE pid > ( SELECT cs_value FROM <!--SPHINX_DB_PREFIX-->cache_store WHERE cs_key='sphinx_forums_counter_posts' )
}

index <!--SPHINX_CONF_PREFIX-->forums_search_posts_main
{
	source			= <!--SPHINX_CONF_PREFIX-->forums_search_posts_main
	path			= <!--SPHINX_BASE_PATH-->/<!--SPHINX_CONF_PREFIX-->forums_search_posts_main
	
	docinfo			= extern
	mlock			= 0
	morphology		= none
	min_word_len	= 2
	charset_type	= sbcs
	html_strip		= 0
	#infix_fields    = post, title
	#min_infix_len   = 3
	#enable_star     = 1
}

index <!--SPHINX_CONF_PREFIX-->forums_search_posts_delta : <!--SPHINX_CONF_PREFIX-->forums_search_posts_main
{
   source			= <!--SPHINX_CONF_PREFIX-->forums_search_posts_delta
   path				= <!--SPHINX_BASE_PATH-->/<!--SPHINX_CONF_PREFIX-->forums_search_posts_delta
}

source <!--SPHINX_CONF_PREFIX-->forums_search_archive_main : <!--SPHINX_CONF_PREFIX-->ipb_source_config
{
	# Set our forum PID counter
	<!--SPHINX_DB_SET_NAMES-->
	sql_query_pre	= REPLACE INTO <!--SPHINX_DB_PREFIX-->cache_store VALUES( 'sphinx_forums_counter_archives', (SELECT max(archive_id) FROM <!--SPHINX_DB_PREFIX-->forums_archive_posts), 0, UNIX_TIMESTAMP(), 0 )
	
	# Query posts for the main source
	sql_query		= SELECT p.archive_id, p.archive_id as search_id, p.archive_author_id as author_id, p.archive_content_date as post_date, REPLACE( p.archive_content, '-', '&\#8208') as archive_content, p.archive_topic_id, p.archive_queued as queued, \
							 t.tid, LOWER(t.title) as tordinal, REPLACE( t.title, '-', '&\#8208') as title, t.views, t.posts, t.forum_id, t.last_post, t.state, t.start_date, t.starter_id, t.last_poster_id, t.topic_firstpost, \
							CASE WHEN t.approved = -1 THEN 1 ELSE 0 END AS soft_deleted, \
							CASE WHEN t.approved = -1 THEN 0 ELSE t.approved END AS approved, \
							CASE WHEN t.topic_archive_status IN (0,3) THEN 0 ELSE 1 END AS archive_status, \
							CONCAT( SUBSTRING( t.last_post, 2, 8 ), '0', LPAD( t.tid, 10, 0 ) ) as last_post_group \
					  FROM <!--SPHINX_DB_PREFIX-->forums_archive_posts p \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->topics t ON ( p.archive_topic_id=t.tid )
	
	# Fields	
	sql_attr_uint			= queued
	sql_attr_uint			= approved
	sql_attr_uint			= soft_deleted
	sql_attr_uint			= archive_status
	sql_attr_uint			= search_id
	sql_attr_uint			= forum_id
	sql_attr_timestamp	    = post_date
	sql_attr_timestamp	    = last_post
	sql_attr_timestamp	    = start_date
	sql_attr_uint			= author_id
	sql_attr_uint			= starter_id
	sql_attr_uint			= tid
	sql_attr_uint			= posts
	sql_attr_uint			= views
	sql_attr_str2ordinal	= tordinal
	sql_attr_bigint			= last_post_group
	sql_attr_multi			= uint tag_id from query; SELECT t.topic_firstpost, c.tag_id FROM <!--SPHINX_DB_PREFIX-->core_tags c LEFT JOIN <!--SPHINX_DB_PREFIX-->topics t ON (t.tid=c.tag_meta_id) WHERE c.tag_meta_app='forums' AND c.tag_meta_area='topics'
	
	sql_ranged_throttle	= 0
}

source <!--SPHINX_CONF_PREFIX-->forums_search_archive_delta : <!--SPHINX_CONF_PREFIX-->forums_search_archive_main
{
	# Override the base sql_query_pre
	<!--SPHINX_DB_SET_NAMES-->
	sql_query_pre = 
	
	# Query posts for the delta source
	sql_query		= SELECT p.archive_id, p.archive_id as search_id, p.archive_author_id as author_id, p.archive_content_date as post_date, REPLACE( p.archive_content, '-', '&\#8208') as archive_content, p.archive_topic_id, p.archive_queued as queued, \
							 t.tid, LOWER(t.title) as tordinal, REPLACE( t.title, '-', '&\#8208') as title, t.views, t.posts, t.forum_id, t.last_post, t.state, t.start_date, t.starter_id, t.last_poster_id, t.topic_firstpost, \
							 CASE WHEN t.approved = -1 THEN 1 ELSE 0 END AS soft_deleted, \
						 	 CASE WHEN t.approved = -1 THEN 0 ELSE t.approved END AS approved, \
							 CASE WHEN t.topic_archive_status IN (0,3) THEN 0 ELSE 1 END AS archive_status, \
							 CONCAT( SUBSTRING( t.last_post, 2, 8 ), '0', LPAD( t.tid, 10, 0 ) ) as last_post_group \
					  FROM <!--SPHINX_DB_PREFIX-->forums_archive_posts p \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->topics t ON ( p.archive_topic_id=t.tid ) \
					  WHERE p.archive_id > ( SELECT cs_value FROM <!--SPHINX_DB_PREFIX-->cache_store WHERE cs_key='sphinx_forums_counter_archives' )
	
	sql_query_killlist = SELECT archive_id FROM <!--SPHINX_DB_PREFIX-->forums_archive_posts WHERE archive_id > ( SELECT cs_value FROM <!--SPHINX_DB_PREFIX-->cache_store WHERE cs_key='sphinx_forums_counter_archives' )
}

index <!--SPHINX_CONF_PREFIX-->forums_search_archive_main
{
	source			= <!--SPHINX_CONF_PREFIX-->forums_search_archive_main
	path			= <!--SPHINX_BASE_PATH-->/<!--SPHINX_CONF_PREFIX-->forums_search_archive_main
	
	docinfo			= extern
	mlock			= 0
	morphology		= none
	min_word_len	= 2
	charset_type	= sbcs
	html_strip		= 0
	#infix_fields    = archive_content, title
	#min_infix_len   = 3
	#enable_star     = 1
}

index <!--SPHINX_CONF_PREFIX-->forums_search_archive_delta : <!--SPHINX_CONF_PREFIX-->forums_search_archive_main
{
   source			= <!--SPHINX_CONF_PREFIX-->forums_search_archive_delta
   path				= <!--SPHINX_BASE_PATH-->/<!--SPHINX_CONF_PREFIX-->forums_search_archive_delta
}

EOF;
