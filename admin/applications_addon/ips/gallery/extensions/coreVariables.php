<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Core variables
 * Last Updated: $Date: 2011-07-18 09:26:24 -0400 (Mon, 18 Jul 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @since		1st march 2002
 * @version		$Revision: 9266 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

$_LOAD = array( 'emoticons' => 1 );

$_GROUP = array( 'neg1_is_best'	=> array( 'g_max_diskspace', 'g_max_upload', 'g_max_transfer', 'g_max_views', 'g_movie_size', 'g_album_limit', 'g_img_album_limit' ),
				);

$valid_reqs = array('albums' => array( 'gallery_stats' => 1, 'sharelinks' => 1 ),
					'images' => array( 'badwords' => 1, 'bbcode' => 1, 'profilefields' => 1, 'ranks' => 1, 'sharelinks' => 1 ),
					'post'   => array( 'badwords' => 1, 'bbcode' => 1, 'ranks' => 1 ),
					);

$req = ( isset( $_GET['module'] ) && isset( $valid_reqs[ $_GET['module'] ] ) ? strtolower($_GET['module']) : 'albums' );

if ( isset( $valid_reqs[ $req ] ) )
{
	$_LOAD = array_merge( $_LOAD, $valid_reqs[ $req ] );
}

/* Caches */
$CACHE['gallery_stats']		= array(
								'array'				=> 1,
								'allow_unload'		=> 0,
								'default_load'		=> 1,
								'recache_file'		=> IPSLib::getAppDir( 'gallery' ) . '/sources/classes/gallery.php',
								'recache_class'		=> 'ipsGallery',
								'recache_function'	=> 'rebuildStatsCache'
							);

$CACHE['gallery_fattach'] = array(
								'array'				=> 1,
								'allow_unload'		=> 0,
								'default_load'		=> 1,
								'recache_file'		=> IPSLib::getAppDir( 'gallery' ) . '/modules_admin/albums/manage.php',
								'recache_class'		=> 'admin_gallery_albums_manage',
								'recache_function'	=> 'cacheAttachToForum'
							);
							
/* Load some caches for buildDisplayData */
if ( isset( $_REQUEST['image'] ) && $_REQUEST['image'] )
{
	$_LOAD['ranks']				= 1;
	$_LOAD['reputation_levels']	= 1;
	$_LOAD['moderators']		= 1;
}

/* Redirect old links to Gallery index */
if ( isset( $_REQUEST['module'] ) )
{
	if ( ( $_REQUEST['module'] == 'images' && !empty($_REQUEST['section']) && in_array( $_REQUEST['section'], array('ecard','avatar','cover','fav','find') ) ) OR
		 ( $_REQUEST['module'] == 'post' && !empty($_REQUEST['section']) && in_array( $_REQUEST['section'], array('mod','comment') ) ) OR
		 ( in_array( $_REQUEST['module'], array('cats','stats','subscribe','user') ) )
		)
	{
		$_RESET['module']	= 'albums';
		$_RESET['section']	= 'home';
	}
}

//-----------------------------------------
// Request resets
//-----------------------------------------

/* Capture 'old' user gallery */
if ( ( isset( $_REQUEST['app'] ) && $_REQUEST['app'] == 'gallery' ) && ( ( isset( $_REQUEST['module'] ) && $_REQUEST['module'] == 'user' ) or isset( $_REQUEST['user'] ) ) )
{
	$_RESET['module']	= 'albums';
	$_RESET['section']	= 'user';
	$_RESET['user']		= intval( $_REQUEST['user'] );
}

if ( ( isset( $_REQUEST['app'] ) && $_REQUEST['app'] == 'gallery' ) && !empty( $_REQUEST['album'] ) && ! isset( $_REQUEST['section'] )  )
{
	$_RESET['module']	= 'albums';
	$_RESET['section']	= 'album';
	$_RESET['album']	= $_REQUEST['album'];
}

if ( ( isset( $_REQUEST['app'] ) && $_REQUEST['app'] == 'gallery' ) && ( isset( $_REQUEST['browseAlbum'] ) ) )
{
	$_RESET['module']	= 'albums';
	$_RESET['section']	= 'browse';
	$_RESET['albumId']	= $_REQUEST['browseAlbum'];
}

if ( ( isset( $_REQUEST['app'] ) && $_REQUEST['app'] == 'gallery' ) && !empty( $_REQUEST['find'] ) && ! isset( $_REQUEST['section'] )  )
{
	$_RESET['module']	= 'images';
	$_RESET['section']	= 'find';
}																							

if ( ( isset( $_REQUEST['app'] ) && $_REQUEST['app'] == 'gallery' ) && !empty( $_REQUEST['image'] ) && ! isset( $_REQUEST['section'] ) )
{
	$_RESET['module']	= 'images';
	
	if ( ! empty( $_REQUEST['size'] ) )
	{
		$_RESET['section']	= 'sizes';
	}
	else
	{
		$_RESET['section']	= 'viewimage';
	}
}

if ( ( isset( $_REQUEST['app'] ) && $_REQUEST['app'] == 'gallery' ) && ! empty( $_REQUEST['albumedit'] ) && ! isset( $_REQUEST['section'] ) )
{
	$_RESET['module']	= 'images';
	$_RESET['section']	= 'review';
	$_RESET['album_id']	= $_REQUEST['albumedit'];
}

/** LEGACY **/
if ( ( isset( $_REQUEST['autocom'] ) && $_REQUEST['autocom'] == 'gallery' ) && ( isset( $_REQUEST['img'] ) && $_REQUEST['img'] > 0 ) )
{
	$_RESET['module']	= 'images';
	$_RESET['section']	= 'viewimage';
	$_RESET['img']		= $_REQUEST['img'];
}

// Fix to handle deprecated user links
if ( ( isset( $_REQUEST['autocom'] ) && $_REQUEST['autocom'] == 'gallery' ) && ( isset( $_REQUEST['req'] ) && $_REQUEST['req'] == 'user' ) )
{
	$_RESET['module']	= 'albums';
	$_RESET['section']	= 'user';
}

// Fix to handle deprecated category links
if ( ( isset( $_REQUEST['autocom'] ) && $_REQUEST['autocom'] == 'gallery' ) && ( isset( $_REQUEST['req'] ) && $_REQUEST['req'] == 'sc' ) )
{
	$_RESET['module']	= 'albums';
	$_RESET['section']	= 'home';
}
