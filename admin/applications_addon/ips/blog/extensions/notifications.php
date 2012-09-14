<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Define the core notification types
 * Last Updated: $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Downloads
 * @link		http://www.invisionpower.com
 * @since		20th February 2002
 * @version		$Rev: 4 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * Notification types
 */
ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_blog' ), 'blog' );

class blog_notifications
{
	public function getConfiguration()
	{
		/**
		 * Notification types - Needs to be a method so when require_once is used, $_NOTIFY isn't empty
		 */
		$_NOTIFY	= array(
							array( 'key' => 'new_entry', 'default' => array( 'email' ), 'disabled' => array(), 'icon' => 'notify_newtopic' ),
							);/*noLibHook*/
							
		return $_NOTIFY;
	}
}

