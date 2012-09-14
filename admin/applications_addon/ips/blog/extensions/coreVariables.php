<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Core variables
 * Last Updated: $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Blog
 * @link		http://www.invisionpower.com
 * @version		$Rev: 4 $
 *
 */

/* Caches to load */
$_LOAD = array(
			'emoticons'		=> 1,
			'bbcode'		=> 1,
			'badwords'		=> 1,
			'attachtypes'	=> 1,
			'ranks'			=> 1,
			'moderators'    => 1,
			'reputation_levels' => 1
			);

$_GROUP = array( 'zero_is_best'	=> array( 'g_blog_attach_max', 'g_blog_attach_per_entry', 'g_blog_preventpublish', 'g_blog_maxblogs' ),
				 'exclude'		=> array(),
				 'less_is_more'	=> array(),
				);

$_RESET = array();

###### Redirect requests... ######
if ( (isset($_REQUEST['autocom']) && $_REQUEST['autocom'] == 'blog') || (isset($_REQUEST['automodule']) && $_REQUEST['automodule'] == 'blog') )
{
	$_RESET['app'] = 'blog';
}

if( empty( $_REQUEST['module'] ) && ( isset( $_REQUEST['app'] ) && $_REQUEST['app'] == 'blog' ) )
{
	$_RESET['module'] = 'display';
}

if( isset( $_REQUEST['showentry'] ) )
{
	$_RESET['app']		= 'blog';
	$_RESET['module']	= 'display';
	$_RESET['section']	= 'entry';
	$_RESET['eid']		= intval( $_REQUEST['showentry'] );
	
	$_LOAD['sharelinks'] = 1;
}
else if( isset( $_REQUEST['showblog'] ) OR ( isset( $_REQUEST['blogid'] ) AND ! $_REQUEST['module'] ) )
{
	$_RESET['module']	= 'display';
	$_RESET['section']	= 'blog';
	$_RESET['blogid']	= intval( $_REQUEST['showblog'] ? $_REQUEST['showblog'] : $_REQUEST['blogid'] );
}
else if( isset( $_GET['blogid'] ) AND ! $_REQUEST['blogid'] )
{
	$_RESET['module']	= $_REQUEST['module'] ? $_REQUEST['module'] : 'display';
	$_RESET['section']	= $_REQUEST['section'] ? $_REQUEST['section'] : 'blog';
	$_RESET['blogid']	= intval( $_GET['blogid'] );
}
else if ( !empty( $_REQUEST['show_members_blogs'] ) )
{
	$_RESET['module']	= 'display';
	$_RESET['section']	= 'list';
	$_RESET['type']     = 'all';
	$_RESET['member_id'] = intval( $_GET['show_members_blogs'] );
}
else if ( !empty( $_REQUEST['blog_this'] ) )
{
	$_RESET['module']	 = 'actions';
	$_RESET['section']	 = 'blogthis';
	$_RESET['btapp']     = $_GET['blog_this'];
	$_RESET['member_id'] = intval( $_GET['show_members_blogs'] );
}

$CACHE					= array();

$CACHE['blogmods']		= array( 
								'array'				=> 1,
								'allow_unload'		=> 0,
								'default_load'		=> 1,
								'recache_file'		=> IPSLib::getAppDir( 'blog' ) . '/modules_admin/blogs/manage.php',
								'recache_class'		=> 'admin_blog_blogs_manage',
								'recache_function'	=> 'rebuildModeratorCache' 
							);

$CACHE['cblocks']		= array( 
								'array'				=> 1,
								'allow_unload'		=> 0,
								'default_load'		=> 1,
								'recache_file'		=> IPSLib::getAppDir( 'blog' ) . '/modules_admin/cblocks/manage.php',
								'recache_class'		=> 'admin_blog_cblocks_manage',
								'recache_function'	=> 'reCacheCBlocks' 
							);
							
$CACHE['blog_stats']		= array( 
								'array'				=> 1,
								'allow_unload'		=> 0,
								'default_load'		=> 1,
								'recache_file'		=> IPSLib::getAppDir( 'blog' ) . '/sources/classes/blogFunctions.php',
								'recache_class'		=> 'blogFunctions',
								'recache_function'	=> 'rebuildStats' 
							);
							
$CACHE['blog_themes']		= array( 
								'array'				=> 1,
								'allow_unload'		=> 0,
								'default_load'		=> 1,
								'recache_file'		=> IPSLib::getAppDir( 'blog' ) . '/modules_admin/customize/custom.php',
								'recache_class'		=> 'admin_blog_customize_custom',
								'recache_function'	=> 'reCacheThemes' 
							);

$CACHE['blog_gblogs']		= array( 
								'array'				=> 1,
								'allow_unload'		=> 0,
								'default_load'		=> 1,
								'recache_file'		=> IPSLib::getAppDir( 'blog' ) . '/modules_admin/blogs/manage.php',
								'recache_class'		=> 'admin_blog_blogs_manage',
								'recache_function'	=> 'recacheGroupBlogs' 
							);
