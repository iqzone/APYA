<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * BBCode - determines which sections bbcode can be used in
 * Last Updated: $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Blog
 * @link		http://www.invisionpower.com
 * @since		6/24/2008
 * @version		$Revision: 4 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/*
 * An array of key => value pairs
 * When going to parse, the key should be passed to the editor
 *  to determine which bbcodes should be parsed in the section
 *
 */
$BBCODE	= array(
				'blog_entry'		=> ipsRegistry::getClass('class_localization')->words['ctype__blogent'],
				'blog_cblock'		=> ipsRegistry::getClass('class_localization')->words['ctype__blogblc'],
				);