<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * BBCode - determines which sections bbcode can be used in
 * Last Updated: $Date: 2011-05-18 11:41:44 -0400 (Wed, 18 May 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @since		6/24/2008
 * @version		$Revision: 8826 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/*
 * An array of key => value pairs
 * When going to parse, the key should be passed to the editor
 *  to determine which bbcodes should be parsed in the section
 *
 */
$BBCODE	= array( 'gallery_image' => ipsRegistry::getClass('class_localization')->words['ctype__galimg'] );