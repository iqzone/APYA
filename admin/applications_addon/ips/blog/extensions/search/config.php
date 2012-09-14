<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Config file
 * Last Updated: $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Blog
 * @link		http://www.invisionpower.com
 * @version		$Rev: 4 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/* Can search with this app */
$CONFIG['can_search']			= 1;

/* Can view new content with this app */
$CONFIG['can_viewNewContent']	= 1;
$CONFIG['can_vnc_unread_content']		= 1;
$CONFIG['can_vnc_filter_by_followed']	= 1;

/* Can fetch user generated content */
$CONFIG['can_userContent']		= 1;

/* Can search tags */
if ( !isset( $_REQUEST['search_app_filters']['blog']['searchInKey'] ) or $_REQUEST['search_app_filters']['blog']['searchInKey'] == 'entries' )
{
	$CONFIG['can_searchTags']		= 1;
}
else
{
	$CONFIG['can_searchTags']		= 0;
}

/* Content types, put the default one first */
$CONFIG['contentTypes']			= array( 'entries', 'comments' );

/* Content types for 'follow', default one first */
$CONFIG['followContentTypes']		= array( 'entries', 'blog' );