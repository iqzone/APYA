<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Sphinx template file
 * Last Updated: $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Blog
 * @link		http://www.invisionpower.com
 * @version		$Rev: 4 $
 * @since		3.0.0
 *
 */

$appSphinxTemplate	= <<<EOF

################################# --- BLOG --- ##############################

source <!--SPHINX_CONF_PREFIX-->blog_search_main : <!--SPHINX_CONF_PREFIX-->ipb_source_config
{
	# Set our forum PID counter
	sql_query_pre	= REPLACE INTO <!--SPHINX_DB_PREFIX-->cache_store VALUES( 'sphinx_blog_counter', (SELECT max(entry_id) FROM <!--SPHINX_DB_PREFIX-->blog_entries), 0, UNIX_TIMESTAMP(), 0 )
	
	# Query posts for the main source
	sql_query		= SELECT e.entry_id, e.entry_id as search_id, e.entry_name, e.entry_name as tordinal, e.entry, e.entry_date, e.entry_author_id, e.entry_num_comments, e.blog_id, \
							 b.blog_owner_only, b.blog_private, b.blog_disabled, \
					  		CASE WHEN e.entry_status='published' THEN 0 ELSE e.entry_author_id END AS entry_not_published, \
							CASE WHEN b.blog_owner_only=0 THEN 0 ELSE b.member_id END AS blog_owner_id, \
							CASE WHEN b.blog_authorized_users IS NULL THEN 0 ELSE 1 END AS authorized_users \
						FROM <!--SPHINX_DB_PREFIX-->blog_entries e \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->blog_blogs b ON ( b.blog_id=e.blog_id )
	
	# Fields	
	sql_attr_uint			= search_id
	sql_attr_uint			= blog_owner_only
	sql_attr_timestamp		= entry_date
	sql_attr_str2ordinal	= tordinal
	sql_attr_uint			= entry_author_id
	sql_attr_uint			= entry_num_comments
	sql_attr_uint		    = blog_id
	sql_attr_uint			= blog_private
	sql_attr_uint			= blog_disabled
	sql_attr_uint			= entry_not_published
	sql_attr_uint			= blog_owner_id
	sql_attr_uint			= authorized_users
	sql_attr_multi			= uint tag_id from query; SELECT tag_meta_id, tag_id FROM <!--SPHINX_DB_PREFIX-->core_tags WHERE tag_meta_app='blog' AND tag_meta_area='entries'
	sql_ranged_throttle	= 0
}

source <!--SPHINX_CONF_PREFIX-->blog_search_delta : <!--SPHINX_CONF_PREFIX-->blog_search_main
{
	# Override the base sql_query_pre
	sql_query_pre = 
	
	# Query posts for the delta source
	sql_query		= SELECT e.entry_id, e.entry_id as search_id, e.entry_name, e.entry_name as tordinal, e.entry, e.entry_date, e.entry_author_id, e.entry_num_comments, e.blog_id, b.blog_owner_only, b.blog_private, b.blog_disabled, \
					  		CASE WHEN e.entry_status='published' THEN 0 ELSE e.entry_author_id END AS entry_not_published, \
							CASE WHEN b.blog_owner_only=0 THEN 0 ELSE b.member_id END as blog_owner_id, \
							CASE WHEN b.blog_authorized_users IS NULL THEN 0 ELSE 1 END AS authorized_users \
						FROM <!--SPHINX_DB_PREFIX-->blog_entries e \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->blog_blogs b ON ( b.blog_id=e.blog_id ) \
					  WHERE e.entry_id > ( SELECT cs_value FROM <!--SPHINX_DB_PREFIX-->cache_store WHERE cs_key='sphinx_blog_counter' )	
}

index <!--SPHINX_CONF_PREFIX-->blog_search_main
{
	source			= <!--SPHINX_CONF_PREFIX-->blog_search_main
	path			= <!--SPHINX_BASE_PATH-->/<!--SPHINX_CONF_PREFIX-->blog_search_main
	
	docinfo			= extern
	mlock			= 0
	morphology		= none
	min_word_len	= 2
	charset_type	= sbcs
	html_strip		= 0
	#infix_fields    = entry_name, entry
	#min_infix_len   = 3
	#enable_star     = 1
}

index <!--SPHINX_CONF_PREFIX-->blog_search_delta : <!--SPHINX_CONF_PREFIX-->blog_search_main
{
   source			= <!--SPHINX_CONF_PREFIX-->blog_search_delta
   path				= <!--SPHINX_BASE_PATH-->/<!--SPHINX_CONF_PREFIX-->blog_search_delta
}

source <!--SPHINX_CONF_PREFIX-->blog_comments_main : <!--SPHINX_CONF_PREFIX-->ipb_source_config
{
	# Set our forum PID counter
	sql_query_pre	= REPLACE INTO <!--SPHINX_DB_PREFIX-->cache_store VALUES( 'sphinx_blog_comments_counter', (SELECT max(comment_id) FROM <!--SPHINX_DB_PREFIX-->blog_comments), 0, UNIX_TIMESTAMP(), 0 )
	
	# Query posts for the main source
	sql_query		= SELECT c.comment_id, c.comment_id as search_id, c.member_id as comment_member_id, c.comment_date, c.comment_approved, c.comment_text, \
	 						 e.entry_id, e.entry_name as tordinal, e.entry_date, e.entry_author_id, e.entry_num_comments, e.blog_id, \
							 b.blog_owner_only, b.blog_private, b.blog_disabled, \
					  		CASE WHEN e.entry_status='published' THEN 0 ELSE e.entry_author_id END AS entry_not_published, \
							CASE WHEN b.blog_owner_only=0 THEN 0 ELSE b.member_id END AS blog_owner_id, \
							CASE WHEN b.blog_authorized_users IS NULL THEN 0 ELSE 1 END AS authorized_users, \
							CONCAT(e.entry_last_comment_date, '.', e.entry_id ) as last_post_group \
						FROM <!--SPHINX_DB_PREFIX-->blog_comments c \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->blog_entries e ON ( c.entry_id=e.entry_id ) \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->blog_blogs b ON ( b.blog_id=e.blog_id )
	
	# Fields	
	sql_attr_uint			= search_id
	sql_attr_uint			= entry_id
	sql_attr_uint			= blog_owner_only
	sql_attr_timestamp		= entry_date
	sql_attr_str2ordinal	= tordinal
	sql_attr_uint			= entry_author_id
	sql_attr_uint			= entry_num_comments
	sql_attr_uint		    = blog_id
	sql_attr_uint			= blog_private
	sql_attr_uint			= blog_disabled
	sql_attr_uint			= entry_not_published
	sql_attr_uint			= blog_owner_id
	sql_attr_uint			= authorized_users
	sql_attr_uint			= last_post_group
	sql_attr_timestamp		= comment_date
	sql_attr_uint			= comment_member_id
	sql_attr_uint			= comment_approved
	sql_ranged_throttle	= 0
}

source <!--SPHINX_CONF_PREFIX-->blog_comments_delta : <!--SPHINX_CONF_PREFIX-->blog_comments_main
{
	# Override the base sql_query_pre
	sql_query_pre = 
	
	# Query posts for the delta source
	sql_query		= SELECT c.comment_id, c.comment_id as search_id, c.member_id as comment_member_id, c.comment_date, c.comment_approved, c.comment_text, \
	 						 e.entry_id, e.entry_name as tordinal, e.entry_date, e.entry_author_id, e.entry_num_comments, e.blog_id, \
							 b.blog_owner_only, b.blog_private, b.blog_disabled, \
					  		CASE WHEN e.entry_status='published' THEN 0 ELSE e.entry_author_id END AS entry_not_published, \
							CASE WHEN b.blog_owner_only=0 THEN 0 ELSE b.member_id END AS blog_owner_id, \
							CASE WHEN b.blog_authorized_users IS NULL THEN 0 ELSE 1 END AS authorized_users, \
							CONCAT(e.entry_last_comment_date, '.', e.entry_id ) as last_post_group \
						FROM <!--SPHINX_DB_PREFIX-->blog_comments c \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->blog_entries e ON ( c.entry_id=e.entry_id ) \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->blog_blogs b ON ( b.blog_id=e.blog_id ) \
					  WHERE c.comment_id <= ( SELECT cs_value FROM <!--SPHINX_DB_PREFIX-->cache_store WHERE cs_key='sphinx_blog_comments_counter' )	
}

index <!--SPHINX_CONF_PREFIX-->blog_comments_main
{
	source			= <!--SPHINX_CONF_PREFIX-->blog_comments_main
	path			= <!--SPHINX_BASE_PATH-->/<!--SPHINX_CONF_PREFIX-->blog_comments_main
	
	docinfo			= extern
	mlock			= 0
	morphology		= none
	min_word_len	= 2
	charset_type	= sbcs
	html_strip		= 0
	#infix_fields    = comment_text
	#min_infix_len   = 3
	#enable_star     = 1
}

index <!--SPHINX_CONF_PREFIX-->blog_comments_delta : <!--SPHINX_CONF_PREFIX-->blog_comments_main
{
   source			= <!--SPHINX_CONF_PREFIX-->blog_comments_delta
   path				= <!--SPHINX_BASE_PATH-->/<!--SPHINX_CONF_PREFIX-->blog_comments_delta
}


EOF;
