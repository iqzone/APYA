<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board vVERSION_NUMBER
 * Config file
 * Last Updated: $Date: 2011-04-18 21:34:01 -0400 (Mon, 18 Apr 2011) $
 * </pre>
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Calendar
 * @link		http://www.invisionpower.com
 * @version		$Rev: 8370 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/* Can search with this app */
$CONFIG['can_search']			= 1;

/* Can view new content with this app */
$CONFIG['can_viewNewContent']			= 1;
$CONFIG['can_vnc_unread_content']		= 0;
$CONFIG['can_vnc_filter_by_followed']	= 1;

/* Can fetch user generated content */
$CONFIG['can_userContent']		= 1;

/* Content types for 'follow', default one first */
$CONFIG['followContentTypes']		= array( 'events', 'calendars' );