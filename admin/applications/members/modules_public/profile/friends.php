<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Profile View
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @since		20th February 2002
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_members_profile_friends extends ipsCommand
{
	/**
	 * Friend's library
	 *
	 * @var		object
	 */
	protected $friend_lib;
	
	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Friends enabled? */
		if( ! $this->settings['friends_enabled'] )
		{
			$this->registry->getClass('output')->showError( 'friends_not_enabled', 10236, null, null, 403 );
		}		
		
		/* Friend Library */
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'members' ) . '/sources/friends.php', 'profileFriendsLib', 'members' );
		$this->friend_lib = new $classToLoad( $this->registry );
				
		//-----------------------------------------
		// Get HTML and skin
		//-----------------------------------------

		$this->registry->class_localization->loadLanguageFile( array( 'public_profile' ), 'members' );

		switch( $this->request['do'] )
		{
			case 'list':
			default:
				$this->_viewList();
			break;

			case 'add':
				$this->_addFriend();
			break;
			
			case 'remove':
				$this->_removeFriend();
			break;
			
			case 'moderate':
				$this->_moderation();
			break;

		}
	}

 	/**
	 * Remove a friend
	 *
	 * @return	@e void		[Prints to screen]
	 * @since	IPB 2.2.0.2006-08-09
	 */
 	protected function _removeFriend()
 	{
		/* INIT */
		$friend_id	= intval( $this->request['member_id'] );

		/* Check the secure key */
		$this->request['secure_key']	= $this->request['secure_key'] ? $this->request['secure_key'] : $this->request['md5check'];

		if( $this->request['secure_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'no_permission', 10274, null, null, 403 );
		}
		
		/* Remove the friend */
		$result		= $this->friend_lib->removeFriend( $friend_id );
		
		/* Remove from other user as well */
		$result2	= $this->friend_lib->removeFriend( $this->memberData['member_id'], $friend_id );

		if( $result )
		{
			$this->registry->output->showError( $result, 10237 );
		}
		else
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . '&app=members&section=friends&module=profile&do=list&___msg=removed' );
		}
	}
	
 	/**
	 * Moderate pending friends
	 *
	 * @return	@e void		[Prints to screen]
	 * @since	IPB 2.2.0.2006-08-09
	 */
 	protected function _moderation()
 	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$md5check			= IPSText::md5Clean( $this->request['md5check'] );
		$friends			= array();
		$friend_ids			= array();
		$friend_member_ids	= array();
		$_friend_ids		= array();
		$friends_already	= array();
		$friends_update		= array();
		$member				= array();
		$pp_option			= $this->request['pp_option'] == 'delete' ? 'delete' : 'add';
		$message			= '';
		$subject			= '';
		$msg				= 'pp_friend_approved';
		
		//-----------------------------------------
		// MD5 check
		//-----------------------------------------
		
		if ( $md5check != $this->member->form_hash )
    	{
    		$this->registry->output->silentRedirect( $this->settings['base_url'] . '&app=members&section=friends&module=profile&do=list&___msg=error&tab=pending' );
    	}

		//-----------------------------------------
		// Get friends...
		//-----------------------------------------
		
		if ( ! is_array( $this->request['pp_friend_id'] ) OR ! count( $this->request['pp_friend_id'] ) )
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . '&app=members&section=friends&module=profile&do=list&___msg=error&tab=pending' );
		}
		
		//-----------------------------------------
		// Figure IDs
		//-----------------------------------------
		
		foreach( $this->request['pp_friend_id'] as $key => $value )
		{
			$_key = intval( $key );
			
			if ( $_key )
			{
				$_friend_ids[ $_key ] = $_key;
			}
		}
		
		if ( ! is_array( $_friend_ids ) OR ! count( $_friend_ids ) )
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . '&app=members&section=friends&module=profile&do=list&___msg=error&tab=pending' );
		}
		
		//-----------------------------------------
		// Check our friends are OK
		//-----------------------------------------

		$this->DB->build( array( 'select'	=> '*',
								 'from'		=> 'profile_friends',
								 'where'	=> 'friends_friend_id=' . $this->memberData['member_id'] . ' AND friends_approved=0 AND friends_member_id IN (' . implode( ',', $_friend_ids ) . ')' ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$friend_ids[ $row['friends_id'] ]				= $row['friends_id'];
			$friend_member_ids[ $row['friends_member_id'] ]	= $row['friends_member_id'];
		}	
		
		if ( ! is_array( $friend_ids ) OR ! count( $friend_ids ) )
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . '&app=members&section=friends&module=profile&do=list&___msg=error&tab=pending' );
		}

		//-----------------------------------------
		// Load friends...
		//-----------------------------------------
		
		$friends	= IPSMember::load( $friend_member_ids );

		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( ! is_array( $friends ) OR ! count( $friends ) OR ! $this->memberData['member_id'] )
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . '&app=members&section=friends&module=profile&do=list&___msg=error&tab=pending' );
		}
		
		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		if ( $pp_option == 'delete' )
		{
			//-----------------------------------------
			// Delete friends records
			//-----------------------------------------

			foreach( $friend_member_ids as $friend_id )
			{
				$this->friend_lib->removeFriend( $this->memberData['member_id'], $friend_id );
				$this->friend_lib->removeFriend( $friend_id, $this->memberData['member_id'] );
			}
			
			$msg = 'pp_friend_removed';
		}
		else
		{
			//-----------------------------------------
			// Ok.. approve them in the DB.
			//-----------------------------------------
		
			$this->DB->update( 'profile_friends', array( 'friends_approved' => 1 ), 'friends_id IN(' . implode( ',', $friend_ids ) . ')' );
			
			//-----------------------------------------
			// And make sure they're added in reverse
			//-----------------------------------------
			
			foreach( $friend_member_ids as $friend_id )
			{
				$this->friend_lib->addFriend( $friend_id, $this->memberData['member_id'], true, false );
			}

			//-----------------------------------------
			// Catch all (should find any missing friends)
			//-----------------------------------------
			
			if ( $pp_option == 'add' )
			{
				//-----------------------------------------
				// Find out who isn't already on your list...
				//-----------------------------------------
				
				$this->DB->build( array( 'select'	=> '*',
										 'from'	    => 'profile_friends',
										 'where'	=> 'friends_friend_id=' . $this->memberData['member_id'] . ' AND friends_approved=1 AND friends_member_id IN (' . implode( ',', $_friend_ids ) . ')' ) );

				$this->DB->execute();

				while( $row = $this->DB->fetch() )
				{
					$friends_already[ $row['friends_member_id'] ] = $row['friends_member_id'];
				}

				//-----------------------------------------
				// Check which aren't already members...	
				//-----------------------------------------
				
				foreach( $friend_member_ids as $id => $_id )
				{
					if ( in_array( $id, $friends_already ) )
					{
						continue;
					}
					
					$friends_update[ $id ] = $id;
				}
				
				//-----------------------------------------
				// Gonna do it?
				//-----------------------------------------
				
				if ( is_array( $friends_update ) AND count( $friends_update ) )
				{
					foreach( $friends_update as $id )
					{
						$this->DB->insert( 'profile_friends', array( 'friends_member_id'	=> $id,
																	 'friends_friend_id'	=> $this->memberData['member_id'],
																	 'friends_approved'		=> 1,
																	 'friends_added'		=> time() ) );
					}
				}
			}
			
			//-----------------------------------------
			// Send out message...
			//-----------------------------------------
			
			foreach( $friends as $friend )
			{
				//-----------------------------------------
				// Notifications library
				//-----------------------------------------
				
				$classToLoad		= IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
				$notifyLibrary		= new $classToLoad( $this->registry );
	
				IPSText::getTextClass( 'email' )->getTemplate( "new_friend_approved" );
			
				IPSText::getTextClass( 'email' )->buildMessage( array( 'MEMBERS_DISPLAY_NAME' => $friend['members_display_name'],
											  'FRIEND_NAME'          => $this->memberData['members_display_name'],
											  'LINK'				 => $this->settings['board_url'] . '/index.' . $this->settings['php_ext'] . '?app=members&amp;module=profile&amp;section=friends&amp;do=list' ) );

				IPSText::getTextClass('email')->subject	= sprintf( 
																	IPSText::getTextClass('email')->subject, 
																	$this->registry->output->buildSEOUrl( 'showuser=' . $this->memberData['member_id'], 'public', $this->memberData['members_seo_name'], 'showuser' ), 
																	$this->memberData['members_display_name']
																);
	
				$notifyLibrary->setMember( $friend );
				$notifyLibrary->setFrom( $this->memberData );
				$notifyLibrary->setNotificationKey( 'friend_request_approve' );
				$notifyLibrary->setNotificationUrl( $this->registry->output->buildSEOUrl( 'showuser=' . $this->memberData['member_id'], 'public', $this->memberData['members_seo_name'], 'showuser' ) );
				$notifyLibrary->setNotificationText( IPSText::getTextClass('email')->message );
				$notifyLibrary->setNotificationTitle( IPSText::getTextClass('email')->subject );
				try
				{
					$notifyLibrary->sendNotification();
				}
				catch( Exception $e ){}
			}
			
			$this->friend_lib->recacheFriends( $friend );
		}
		
		//-----------------------------------------
		// Recache..
		//-----------------------------------------
		
		$this->friend_lib->recacheFriends( $this->memberData );

		//-----------------------------------------
		// Return
		//-----------------------------------------
		
		$this->registry->output->silentRedirect( $this->settings['base_url'] . '&app=members&section=friends&module=profile&do=list&___msg=' . $msg . '&tab=pending' );
	}
	
 	/**
	 * Add a friend
	 *
	 * @return	@e void		[Prints to screen]
	 * @since	IPB 2.2.0.2006-08-09
	 */
 	protected function _addFriend()
 	{
		/* INIT */
		$friend_id	= intval( $this->request['member_id'] );
		$_friend	= IPSMember::load( $friend_id, 'core' );
		
		/* Check the secure key */
		$this->request['secure_key']	= $this->request['secure_key'] ? $this->request['secure_key'] : $this->request['md5check'];

		if( $this->request['secure_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'no_permission', 10273, null, null, 403 );
		}
		
		/* Add the friend */
		$result		= $this->friend_lib->addFriend( $friend_id );
		
		/* Add to other user as well, but only if not pending */
		if( !$this->friend_lib->pendingApproval )
		{
			$result2	= $this->friend_lib->addFriend( $this->memberData['member_id'], $friend_id, true );
		}

		if( $result )
		{
			$this->registry->output->showError( $result, 10241 );
		}
		else
		{
			$this->registry->output->redirectScreen( $this->lang->words['friend_added_ok'], $this->settings['base_url'] . 'showuser=' . $friend_id, 'showuser', $_friend['members_seo_name'] );
		}
	}
	
 	/**
	 * List all current friends.
	 *
	 * @return	@e void		[Prints to screen]
	 * @since	IPB 2.2.0.2006-08-08
	 */
 	protected function _viewList()
 	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$friends		= array();
		$tab			= substr( IPSText::alphanumericalClean( $this->request['tab'] ), 0, 20 );
		$per_page		= 25;
		$start			= intval( $this->request['st'] );
		
		//-----------------------------------------
		// Check we're a member
		//-----------------------------------------
		
		if ( !$this->memberData['member_id'] )
		{
			$this->registry->output->showError( 'no_friend_mid', 10267, null, null, 404 );
		}
		
		//-----------------------------------------
		// To what are we doing to whom?
		//-----------------------------------------
		
		if ( $tab == 'pending' )
		{
			$query		= 'f.friends_approved=0 AND f.friends_friend_id=' . $this->memberData['member_id'];
			$joinKey	= 'f.friends_member_id';
		}
		else
		{
			$query		= 'f.friends_approved=1 AND f.friends_member_id=' . $this->memberData['member_id'];
			$joinKey	= 'f.friends_friend_id';
		}
		
		/* Not banned or spammed */
		$query .= ' AND m.member_banned=0 AND ( ! ' . IPSBWOptions::sql( 'bw_is_spammer', 'members_bitoptions', 'members', 'global', 'has' ) . ')';
		
		//-----------------------------------------
		// Get count...
		//-----------------------------------------
		
		$count	= $this->DB->buildAndFetch( array( 
													'select'	=> 'COUNT(*) as count',
													'from'		=> array( 'profile_friends' => 'f' ),
													'where'		=> $query,
													'add_join'	=> array( array( 
																					'select'	=> '',
																					'from'		=> array( 'members' => 'm' ),
																					'where'		=> 'm.member_id=' . $joinKey,
																					'type'		=> 'inner' 
																		)	) 
										)	);

		//-----------------------------------------
		// Pages...
		//----------------------------------------- 
		
		$pages	= $this->registry->output->generatePagination( array(	
																	'totalItems'		=> intval( $count['count'] ),
																	'noDropdown'		=> 1,
												   	 				'itemsPerPage'		=> $per_page,
																	'currentStartValue'	=> $start,
																	'baseUrl'			=> 'app=members&amp;module=profile&amp;section=friends&amp;do=list&amp;tab=' . $tab,
														 	)	);

		//-----------------------------------------
		// Get current friends...	
		//-----------------------------------------
		
		$this->DB->build( array( 
								'select'	=> 'f.*',
								'from'		=> array( 'profile_friends' => 'f' ),
								'where'		=> $query,
								'order'		=> 'm.members_l_display_name ASC',
								'limit'		=> array( $start, $per_page ),
								'add_join'	=> array(
													  array( 
															'select' => 'pp.*',
															'from'   => array( 'profile_portal' => 'pp' ),
															'where'  => 'pp.pp_member_id=' . $joinKey,
															'type'   => 'left' 
															),
												 	  array( 
															'select' => 'm.*',
															'from'   => array( 'members' => 'm' ),
															'where'  => 'm.member_id=' . $joinKey,
															'type'   => 'left' 
															) 
														) 
						)	);
		$q	= $this->DB->execute();
		
		//-----------------------------------------
		// Get and store...
		//-----------------------------------------
		
		while( $row = $this->DB->fetch( $q ) )
		{
			$row = IPSMember::buildDisplayData( $row, array( 'warn' => 0 ) );

			$friends[] = $row;
		}
		
		//-----------------------------------------
		// Show...
		//-----------------------------------------
		
		$content	= $this->registry->getClass('output')->getTemplate('profile')->friendsList( $friends, $pages );
		
		$this->registry->output->setTitle( $this->lang->words['m_title_friends'] . ' - ' . ipsRegistry::$settings['board_name'] );
		$this->registry->output->addNavigation( $this->lang->words['m_title_friends'], '' );

		$this->registry->getClass('output')->addContent( $content );
		$this->registry->getClass('output')->sendOutput();
	}
}