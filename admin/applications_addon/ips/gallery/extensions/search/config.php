<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Config file
 * Last Updated: $Date: 2011-10-20 10:51:26 -0400 (Thu, 20 Oct 2011) $
 * </pre>
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Gallery
 * @link		http://www.invisionpower.com
 * @version		$Rev: 9643 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/* Can search with this app */
$CONFIG['can_search']					= 1;

/* Can view new content with this app */
$CONFIG['can_viewNewContent']			= 1;

/* Can further filter VNC by removing non-followed 'categories' */
$CONFIG['can_vnc_filter_by_followed']	= 1;
$CONFIG['can_vnc_unread_content']		= 1;

/* Can fetch user generated content */
$CONFIG['can_userContent']				= 1;

/* Can search tags */
$CONFIG['can_searchTags']		= 1;

/* Content types, put the default one first */
$CONFIG['contentTypes']					= array( 'images', 'albums', 'comments' );

/* Content types for 'follow', default one first */
$CONFIG['followContentTypes']			= array( 'images', 'albums' );