<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Define the core notification types
 * Last Updated: $Date: 2011-06-14 12:04:59 -0400 (Tue, 14 Jun 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		20th February 2002
 * @version		$Rev: 9039 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}



class gallery_notifications
{
	public function getConfiguration()
	{
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_gallery_four' ), 'gallery' );
		
		/**
		 * Notification types - Needs to be a method so when require_once is used, $_NOTIFY isn't empty
		 */
		$_NOTIFY	= array(
							// Future...
							// array( 'key' => 'reputation_received', 'default' => array( 'inline' ), 'disabled' => array() ),
							array( 'key' => 'new_image'  , 'default' => array( 'email' ), 'disabled' => array(), 'show_callback' => false, 'icon' => 'notify_profilecomment' ),
							);
		return $_NOTIFY;
	}
}