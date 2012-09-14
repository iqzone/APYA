<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Define the core notification types
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @since		20th February 2002
 * @version		$Rev: 10721 $
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

class forums_notifications
{
	public function getConfiguration()
	{
		/**
		 * Notification types - Needs to be a method so when require_once is used, $_NOTIFY isn't empty
		 */
		$_NOTIFY	= array(
							array( 'key' => 'post_quoted', 'default' => array(), 'disabled' => array(), 'icon' => 'notify_quoted' ),
							array( 'key' => 'new_likes', 'default' => array( 'inline' ), 'disabled' => array(), 'icon' => 'notify_newreply', 'show_callback' => TRUE ),
							array( 'key' => 'followed_topics', 'default' => array( 'email' ), 'disabled' => array(), 'icon' => 'notify_newreply' ),
							array( 'key' => 'followed_forums', 'default' => array( 'email' ), 'disabled' => array(), 'icon' => 'notify_newreply' ),
							array( 'key' => 'followed_topics_digest', 'default' => array( 'email' ), 'disabled' => array('inline'), 'icon' => 'notify_newreply' ),
							array( 'key' => 'followed_forums_digest', 'default' => array( 'email' ), 'disabled' => array('inline'), 'icon' => 'notify_newreply' ),
							// This is defined in ACP forum settings as an email, so it's not mapped to a specific member
							//array( 'key' => 'new_topic_queue', 'default' => array( 'email' ), 'disabled' => array() ),
							);/*noLibHook*/
		return $_NOTIFY;
	}
	
	function new_likes()
	{
		return (bool) (
			ipsRegistry::$settings['reputation_enabled'] and
			!in_array( ipsRegistry::member()->getProperty('member_group_id'), explode( ',', ipsRegistry::$settings['reputation_protected_groups'] ) ) and
			ipsRegistry::$settings['reputation_point_types'] == 'like'
			);
	}
}