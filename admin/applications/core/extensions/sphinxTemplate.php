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

############################### --- TAGS --- ################################

source <!--SPHINX_CONF_PREFIX-->core_tags_search_main : <!--SPHINX_CONF_PREFIX-->ipb_source_config
{
	# Set our forum PID counter
	<!--SPHINX_DB_SET_NAMES-->
	sql_query_pre	= REPLACE INTO <!--SPHINX_DB_PREFIX-->cache_store VALUES( 'sphinx_core_tags_counter', (SELECT max(tag_id) FROM <!--SPHINX_DB_PREFIX-->core_tags), 0, UNIX_TIMESTAMP(), 0 )
	
	# Query posts for the main source
	sql_query		= SELECT t.tag_id, t.tag_id as search_id, t.tag_added, t.tag_member_id, t.tag_meta_id, t.tag_meta_parent_id, t.tag_text, t.tag_meta_app, REPLACE( t.tag_meta_area, '-', '_' ) as tag_meta_area,  \
							 CONCAT( ',', p.tag_perm_text, ',') as tag_perm_text ,p.tag_perm_visible \
					  FROM <!--SPHINX_DB_PREFIX-->core_tags t \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->core_tags_perms p ON ( t.tag_aai_lookup=p.tag_perm_aai_lookup )
	
	# Fields	
	sql_attr_uint	   = search_id
	sql_attr_timestamp = tag_added
	sql_attr_uint      = tag_member_id
	sql_attr_uint      = tag_meta_id
	sql_attr_uint      = tag_meta_parent_id
	sql_attr_uint	   = tag_perm_visible
	sql_ranged_throttle	= 0
}

source <!--SPHINX_CONF_PREFIX-->core_tags_search_delta : <!--SPHINX_CONF_PREFIX-->core_tags_search_main
{
	# Override the base sql_query_pre
	<!--SPHINX_DB_SET_NAMES-->
	sql_query_pre	= 
	
	# Query posts for the main source
	sql_query		= SELECT t.tag_id, t.tag_id as search_id, t.tag_added, t.tag_member_id, t.tag_meta_id, t.tag_meta_parent_id, t.tag_text, t.tag_meta_app, REPLACE( t.tag_meta_area, '-', '_' ) as tag_meta_area,  \
							 CONCAT( ',', p.tag_perm_text, ',') as tag_perm_text ,p.tag_perm_visible \
					  FROM <!--SPHINX_DB_PREFIX-->core_tags t \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->core_tags_perms p ON ( t.tag_aai_lookup=p.tag_perm_aai_lookup ) \
					  WHERE t.tag_id > ( SELECT cs_value FROM <!--SPHINX_DB_PREFIX-->cache_store WHERE cs_key='sphinx_core_tags_counter' )
}

index <!--SPHINX_CONF_PREFIX-->core_tags_search_main
{
	source			= <!--SPHINX_CONF_PREFIX-->core_tags_search_main
	path			= <!--SPHINX_BASE_PATH-->/<!--SPHINX_CONF_PREFIX-->core_tags_search_main
	
	docinfo			= extern
	mlock			= 0
	morphology		= none
	min_word_len	= 2
	charset_type	= sbcs
	html_strip		= 0
	#infix_fields    = tag_text
	#min_infix_len   = 3
	#enable_star     = 1
}

index <!--SPHINX_CONF_PREFIX-->core_tags_search_delta : <!--SPHINX_CONF_PREFIX-->core_tags_search_main
{
   source			= <!--SPHINX_CONF_PREFIX-->core_tags_search_delta
   path				= <!--SPHINX_BASE_PATH-->/<!--SPHINX_CONF_PREFIX-->core_tags_search_delta
}

EOF;

