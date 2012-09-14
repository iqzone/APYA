<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Profile AJAX Comment Handler
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @since		Tuesday 1st March 2005 (11:52)
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_members_ajax_friends extends ipsAjaxCommand 
{
	/**
	 * Friends library
	 *
	 * @var		object
	 */
	protected $friends;

	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		/* Friends Enabled */
		if( ! $this->settings['friends_enabled'] )
		{
			$this->registry->getClass('output')->showError( 'friends_not_enabled', 10220 );
		}
				
		/* Friends Library */
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'members' ) . '/sources/friends.php', 'profileFriendsLib', 'members' );
		$this->friends = new $classToLoad( $registry );

		switch( $this->request['do'] )
		{
			case 'add':
				$this->_addFriend();
			break;
			
			case 'remove':
				$this->_removeFriend();
			break;
		}
	}

	/**
	 * Add a friend
	 *
	 * @return	@e void
	 */
 	protected function _addFriend()
 	{
		/* INIT */
		$member_id = intval( $this->request['member_id'] );

		/* Add friend */		
		$result		= $this->friends->addFriend( $member_id );
		
		/* Add to other user as well, but only if not pending */
		if( !$this->friends->pendingApproval )
		{
			$result2	= $this->friends->addFriend( $this->memberData['member_id'], $member_id, true );
		}
		
		/* Check for error */
		if( $result )
		{
			$this->returnString( $result );
		}
		else
		{
			$this->returnString( 'success' );
		}
	}
	
	/**
	 * Removes a friend
	 *
	 * @return	@e void
	 */
	protected function _removeFriend()
	{
		/* INIT */
		$member_id = intval( $this->request['member_id'] );

		/* Remove friend */		
		$result		= $this->friends->removeFriend( $member_id );
		
		/* Remove from other user as well */
		$result2	= $this->friends->removeFriend( $this->memberData['member_id'], $member_id );
		
		/* Check for error */
		if( $result )
		{
			$this->returnString( $result );
		}
		else
		{
			$this->returnString( 'success' );
		}		
	}
}