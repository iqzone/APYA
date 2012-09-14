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

class members_notifications
{
	public function getConfiguration()
	{
		/**
		 * Notification types - Needs to be a method so when require_once is used, $_NOTIFY isn't empty
		 */
		$_NOTIFY	= array(
							array( 'key' => 'profile_comment', 'default' => array( 'inline' ), 'disabled' => array(), 'icon' => 'notify_profilecomment' ),
							array( 'key' => 'friend_request', 'default' => array( 'inline' ), 'disabled' => array(), 'icon' => 'notify_friendrequest' ),
							array( 'key' => 'friend_request_approve', 'default' => array( 'inline' ), 'disabled' => array(), 'icon' => 'notify_friendrequest' ),
							array( 'key' => 'new_private_message', 'default' => array( 'email' ), 'disabled' => array( 'inline' ), 'icon' => 'notify_pm' ),
							array( 'key' => 'reply_private_message', 'default' => array( 'email' ), 'disabled' => array( 'inline' ), 'icon' => 'notify_pm' ),
							array( 'key' => 'invite_private_message', 'default' => array( 'email' ), 'disabled' => array( 'inline' ), 'icon' => 'notify_pm' ),
							array( 'key' => 'reply_your_status', 'default' => array(), 'disabled' => array(), 'icon' => 'notify_statusreply' ),
							array( 'key' => 'reply_any_status', 'default' => array(), 'disabled' => array(), 'icon' => 'notify_statusreply' ),
							array( 'key' => 'friend_status_update', 'default' => array(), 'disabled' => array(), 'icon' => 'notify_statusreply' ),
							array( 'key' => 'warning', 'default' => array( 'email' ), 'disabled' => array(), 'show_callback' => true, 'icon' => 'notify_warning' ),
							array( 'key' => 'warning_mods', 'default' => array( 'inline' ), 'disabled' => array(), 'show_callback' => true, 'icon' => 'notify_warning' ),
							);/*noLibHook*/
							
		return $_NOTIFY;
	}
	
	public function warning( $member )
	{
		if ( !ipsRegistry::$settings['warn_on'] )
		{
			return false;
		}
		
		if ( ipsRegistry::$settings['warn_protected'] )
		{
			if ( IPSMember::isInGroup( $member, explode( ',', ipsRegistry::$settings['warn_protected'] ) ) )
			{
				return false;
			}
		}
		
		return true;
	}
	
	public function warning_mods( $member )
	{
		if ( !ipsRegistry::$settings['warn_on'] )
		{
			return FALSE;
		}
	
		if ( $member['g_is_supmod'] )
		{
			return TRUE;
		}
		elseif ( $member['is_mod'] )
		{
			$other_mgroups	= array();
			$_other_mgroups	= IPSText::cleanPermString( $member['mgroup_others'] );
			
			if( $_other_mgroups )
			{
				$other_mgroups	= explode( ",", $_other_mgroups );
			}
			
			$other_mgroups[] = $member['member_group_id'];

			ipsRegistry::DB()->build( array( 
									'select' => '*',
									'from'   => 'moderators',
									'where'  => "(member_id='" . $member['member_id'] . "' OR (is_group=1 AND group_id IN(" . implode( ",", $other_mgroups ) . ")))" 
							)	);
										  
			ipsRegistry::DB()->execute();
			
			while ( $this->moderator = ipsRegistry::DB()->fetch() )
			{
				if ( $this->moderator['allow_warn'] )
				{
					return TRUE;
				}
			}
		}
		
		return FALSE;
	}
}