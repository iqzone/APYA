<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board vVERSION_NUMBER
 * Define the core notification types
 * Last Updated: $Date: 2011-09-19 20:56:02 -0400 (Mon, 19 Sep 2011) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Downloads
 * @link		http://www.invisionpower.com
 * @since		20th February 2002
 * @version		$Rev: 9511 $
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

ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_calendar' ), 'calendar' );

class calendar_notifications
{
	public function getConfiguration()
	{
		/**
		 * Notification types - Needs to be a method so when require_once is used, $_NOTIFY isn't empty
		 */
		$_NOTIFY	= array(
							array( 'key' => 'updated_event', 'default' => array( 'inline' ), 'disabled' => array(), 'icon' => 'notify_calendar' ),
							array( 'key' => 'new_event', 'default' => array( 'inline' ), 'disabled' => array(), 'icon' => 'notify_calendar' ),
							);/*noLibHook*/
							
		return $_NOTIFY;
	}
}

