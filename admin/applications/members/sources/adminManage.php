<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Library to facilitate ACP member management
 * Last Updated: $Date: 2012-05-22 13:10:11 -0400 (Tue, 22 May 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @since		20th February 2002
 * @version		$Rev: 10783 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class adminMemberManagement
{
	/**#@+
	 * Registry objects
	 *
	 * @var		object
	 */	
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	protected $lang;
	/**#@-*/

	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
		$this->registry		= $registry;
		$this->DB			= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->member		= $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->lang			= $this->registry->getClass('class_localization');
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
	}
	
	/**
	 * Delete member(s)
	 *
	 * @param	array 	Array of member ids
	 * @param	string	Alternate language key to use
	 * @return	string	Confirmation message
	 */
	public function deleteMembers( $ids, $lang='' )
	{
		//-----------------------------------------
		// Filter if we cannot delete admins
		//-----------------------------------------
		
		if( ! $this->registry->getClass('class_permissions')->checkPermission( 'member_delete_admin', 'members', 'members' ) )
		{
			$newIds	= array();
	
			$this->DB->build( array( 'select' => 'member_id, member_group_id, mgroup_others', 'from' => 'members', 'where' => "member_id IN(" . implode( ",", $ids ) . ")" ) );
			$this->DB->execute();
	
			while( $r = $this->DB->fetch() )
			{
				if( $this->caches['group_cache'][ $r['member_group_id'] ]['g_access_cp'] )
				{
					continue;
				}
				else
				{
					$other_mgroups = explode( ',', IPSText::cleanPermString( $r['mgroup_others'] ) );
					
					if( count($other_mgroups) )
					{
						foreach( $other_mgroups as $other_mgroup )
						{
							if( $this->caches['group_cache'][ $other_mgroup ]['g_access_cp'] )
							{
								continue 2;
							}
						}
					}
				}
				
				$newIds[]	= $r['member_id'];
			}
			
			$ids	= $newIds;
		}
		
		if ( count($ids) )
		{
			IPSMember::remove( $ids );
		}
		
		$message = sprintf( $lang ? $this->lang->words[ $lang ] : $this->lang->words['t_memdeleted'], count($ids) );
		
		ipsRegistry::getClass('adminFunctions')->saveAdminLog( $message );
		
		return $message;
	}

	/**
	 * Deny member(s) registrations
	 *
	 * @param	array 	Array of member ids
	 * @return	string	Confirmation message
	 */
	public function denyMembers( $ids )
	{
		//-----------------------------------------
		// Get names for log, and filter out admins if
		// we do not have permission to delete them
		//-----------------------------------------
		
		$denied	= array();
		$newIds	= array();

		$this->DB->build( array( 'select' => 'member_id, member_group_id, mgroup_others, members_display_name', 'from' => 'members', 'where' => "member_id IN(" . implode( ",", $ids ) . ")" ) );
		$this->DB->execute();

		while( $r = $this->DB->fetch() )
		{
			if( ! $this->registry->getClass('class_permissions')->checkPermission( 'member_delete_admin', 'members', 'members' ) )
			{
				if( $this->caches['group_cache'][ $r['member_group_id'] ]['g_access_cp'] )
				{
					continue;
				}
				else
				{
					$other_mgroups = explode( ',', IPSText::cleanPermString( $r['mgroup_others'] ) );
					
					if( count($other_mgroups) )
					{
						foreach( $other_mgroups as $other_mgroup )
						{
							if( $this->caches['group_cache'][ $other_mgroup ]['g_access_cp'] )
							{
								continue 2;
							}
						}
					}
				}
			}
			
			$denied[]	= $r['members_display_name'];
			$newIds[]	= $r['member_id'];
		}
		
		if ( count($newIds) )
		{
			IPSMember::remove( $newIds );
		}
		
		$message = sprintf( $this->lang->words['t_regdenied'], count($newIds), implode( ", ", $denied ) );
		
		ipsRegistry::getClass('adminFunctions')->saveAdminLog( $message );
		
		return $message;
	}

	/**
	 * Ban member(s)
	 *
	 * @note	Exceptions CAN bubble up, so you should still capture exceptions from calls to this method
	 * @param	array 	Array of member ids
	 * @return	string	Confirmation message
	 */
	public function banMembers( $ids )
	{
		//-----------------------------------------
		// Filter if we cannot delete admins
		//-----------------------------------------
		
		if( ! $this->registry->getClass('class_permissions')->checkPermission( 'member_ban_admin', 'members', 'members' ) )
		{
			$newIds	= array();
			
			$this->DB->build( array( 'select' => 'member_id, member_group_id, mgroup_others', 'from' => 'members', 'where' => "member_id IN(" . implode( ",", $ids ) . ")" ) );
			$this->DB->execute();
	
			while( $r = $this->DB->fetch() )
			{
				if( $this->caches['group_cache'][ $r['member_group_id'] ]['g_access_cp'] )
				{
					continue;
				}
				else
				{
					$other_mgroups = explode( ',', IPSText::cleanPermString( $r['mgroup_others'] ) );
					
					if( count($other_mgroups) )
					{
						foreach( $other_mgroups as $other_mgroup )
						{
							if( $this->caches['group_cache'][ $other_mgroup ]['g_access_cp'] )
							{
								continue 2;
							}
						}
					}
				}
				
				$newIds[] = $r['member_id'];
			}
			
			$ids = $newIds;
		}
		
		if( count($ids) )
		{
			$this->DB->update( 'members', array( 'failed_logins' => '', 'failed_login_count' => 0, 'member_banned' => 1 ), "member_id IN (" . implode( ",", $ids ) . ")" );
			
			$this->DB->delete( 'validating', "member_id IN(" . implode( ",", $ids ) . ")" );
			
			/* Reset last member */
			IPSMember::resetLastRegisteredMember();
		}

		$message = sprintf( $this->lang->words['t_membanned'], count($ids) );

		ipsRegistry::getClass('adminFunctions')->saveAdminLog( $message );
		
		return $message;
	}

	/**
	 * Unban member(s)
	 *
	 * @note	Exceptions CAN bubble up, so you should still capture exceptions from calls to this method
	 * @param	array 	Array of member ids
	 * @return	string	Confirmation message
	 */
	public function unbanMembers( $ids )
	{
		$members = IPSMember::load( $ids );

		foreach( $members as $id => $data )
		{
			/* @todo / @deprecated - banned_group has been removed from new installs but needed here for existing boards */
			if( ! empty( $this->settings['banned_group'] ) && $data['member_group_id'] == $this->settings['banned_group'] )
			{
				$group	= $this->settings['member_group'];
			}
			else
			{
				$group	= $data['member_group_id'];
			}

			IPSMember::save( $id, array( 'core' => array( 'member_banned' => 0, 'member_group_id' => $group ) ) );
		}
		
		/* Reset last member */
		IPSMember::resetLastRegisteredMember();

		$message = sprintf( $this->lang->words['t_memunbanned'], count($ids) );

		ipsRegistry::getClass('adminFunctions')->saveAdminLog( $message );
		
		return $message;
	}
	
	/**
	 * Marks member(s) as spam
	 *
	 * @note	Exceptions CAN bubble up, so you should still capture exceptions from calls to this method
	 * @param	array 	Array of member ids
	 * @return	string	Confirmation message
	 */
	public function markMembersAsSpam( $ids )
	{
		/* Grab members */
		$members = IPSMember::load( $ids );

		/* Load moderator's library */
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/moderate.php', 'moderatorLibrary', 'forums' );
		$modLibrary	=  new $classToLoad( $this->registry );

		/* Load custom profile fields class */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/customfields/profileFields.php', 'customProfileFields' );
		$fields      = new $classToLoad();

		/* Load language file */
		$this->registry->getClass( 'class_localization')->loadLanguageFile( array( 'public_mod' ), 'forums' );
		
		/* Cycle all members */
		foreach( $members as $member_id => $member )
		{
			/* Protected group? */
			if ( IPSMember::isInGroup( $member, explode( ',', ipsRegistry::$settings['warn_protected'] ) ) )
			{
				continue;
			}
			
			/**
			 * Update member group and then flag as spammer,
			 * we're removing them from the validating queue anyway
			 * 
			 * We must run this query here before they're flagged as spammer because
			 * the 'onProfileUpdate' member sync call could edit further their group
			 */
			$this->DB->update( 'members', array( 'member_group_id' => $this->settings['member_group'] ), 'member_id=' . $member['member_id'] );
			
			$member['member_group_id'] = $this->settings['member_group']; # Change group here too to reflect the update just in case
			
			IPSMember::flagMemberAsSpammer( $member, $this->memberData, FALSE );
		}
		
		/* Remove validating rows */
		$this->DB->delete( 'validating', "member_id IN (" . implode( ",", $ids ) . ")" );
		
		/* Reset last member */
		IPSMember::resetLastRegisteredMember();
		
		$message	= sprintf( $this->lang->words['t_setasspammers'], count($ids) );
		
		ipsRegistry::getClass('adminFunctions')->saveAdminLog( $message );
		
		return $message;
	}
	
	/**
	 * Unmarks member(s) as spam
	 *
	 * @note	Exceptions CAN bubble up, so you should still capture exceptions from calls to this method
	 * @param	array 	Array of member ids
	 * @param	bool	Unmark posts additionally
	 * @return	string	Confirmation message
	 */
	public function unmarkMembersAsSpam( $ids, $unmarkPosts=false )
	{
		/* Load Member Data */
		$members = IPSMember::load( $ids );

		foreach( $ids as $i )
		{
			IPSMember::save( $i, array( 'core' => array( 'bw_is_spammer' => 0, 'restrict_post' => 0, 'members_disable_pm' => 0 ) ) );
			
			if( $this->settings['spam_service_send_to_ips'] )
			{
				IPSMember::querySpamService( $members[$i]['email'], $members[$i]['ip_address'], 'notspam' );
			}
			
			$this->DB->update( 'validating', array( 'spam_flag' => 0 ), 'member_id=' . $i );
		}
		
		/* Reset last member */
		IPSMember::resetLastRegisteredMember();
		
		if ( $unmarkPosts )
		{
			/* Toggle their content */
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/moderate.php', 'moderatorLibrary', 'forums' );
			$modLibrary	=  new $classToLoad( $this->registry );

			foreach( $ids as $id )
			{
				$modLibrary->toggleApproveMemberContent( $id, TRUE, 'all', intval( $this->settings['spm_post_days'] ) * 24 );
				
				/* Run member sync */
				IPSLib::runMemberSync( 'onUnSetAsSpammer', array( 'member_id' => $id ) );
			}
		}

		$message	= sprintf( $this->lang->words['t_memunspammed'], count($ids) );
		
		ipsRegistry::getClass('adminFunctions')->saveAdminLog( $message );
		
		return $message;
	}
	
	/**
	 * Bans spammers and optionally blacklists their data
	 *
	 * @note	Exceptions CAN bubble up, so you should still capture exceptions from calls to this method
	 * @param	array 	Array of member ids
	 * @param	bool	Blacklist additionally
	 * @return	string	Confirmation message
	 */
	public function banSpammers( $ids, $blacklist=false )
	{
		//-----------------------------------------
		// Load members
		//-----------------------------------------
		
		$members	= IPSMember::load( $ids );
			
		foreach( $members as $i => $data )
		{
			//-----------------------------------------
			// Filter if we cannot delete admins
			//-----------------------------------------
			
			if( ! $this->registry->getClass('class_permissions')->checkPermission( 'member_ban_admin', 'members', 'members' ) )
			{
				if( $this->caches['group_cache'][ $data['member_group_id'] ]['g_access_cp'] )
				{
					continue;
				}
				else
				{
					$other_mgroups = explode( ',', IPSText::cleanPermString( $data['mgroup_others'] ) );
					
					if( count($other_mgroups) )
					{
						foreach( $other_mgroups as $other_mgroup )
						{
							if( $this->caches['group_cache'][ $other_mgroup ]['g_access_cp'] )
							{
								continue 2;
							}
						}
					}
				}
			}
			
			IPSMember::save( $i, array( 'core' => array( 'bw_is_spammer' => 0, 'member_banned' => 1 ) ) );
			
			//-----------------------------------------
			// Delete from validating if necessary
			//-----------------------------------------
			
			$this->DB->delete( 'validating', "member_id={$data['member_id']}" );
		}
		
		/* Reset last member */	
		IPSMember::resetLastRegisteredMember();

		//-----------------------------------------
		// Are we blacklisting too?
		//-----------------------------------------
		
		if ( $blacklist )
		{
			$ips		= array();
			$email		= array();
			$ban		= array( 'ip' => array(), 'email' => array() );

			if ( is_array( $members ) AND count( $members ) )
			{
				foreach( $members as $id => $data )
				{
					//-----------------------------------------
					// Filter if we cannot delete admins
					//-----------------------------------------
					
					if( ! $this->registry->getClass('class_permissions')->checkPermission( 'member_ban_admin', 'members', 'members' ) )
					{
						if( $this->caches['group_cache'][ $data['member_group_id'] ]['g_access_cp'] )
						{
							continue;
						}
						else
						{
							$other_mgroups = explode( ',', IPSText::cleanPermString( $data['mgroup_others'] ) );
							
							if( count($other_mgroups) )
							{
								foreach( $other_mgroups as $other_mgroup )
								{
									if( $this->caches['group_cache'][ $other_mgroup ]['g_access_cp'] )
									{
										continue 2;
									}
								}
							}
						}
					}
		
					$ips[ $data['ip_address'] ]	= $data['ip_address'];
					$email[ $data['email'] ]	= $data['email'];
				}

				if ( count( $ips ) )
				{
					/* IPS: Check for duplicate */
					$this->DB->build( array(	'select'	=> '*',
												'from'		=> 'banfilters',
												'where'		=> "ban_content IN ('" . implode( "','", $ips ) . "') and ban_type='ip'" ) );
					$this->DB->execute();

					while( $row = $this->DB->fetch() )
					{
						$ban['ip'][] = $row['ban_content'];
					}

					/* Now insert.. */
					foreach( $ips as $i )
					{
						if ( ! in_array( $i, $ban['ip'] ) )
						{
							/* Insert the new ban filter */
							$this->DB->insert( 'banfilters', array( 'ban_type' => 'ip', 'ban_content' => $i, 'ban_date' => time() ) );
							
							/* Prevent it adding a second time */
							$ban['ip'][]	= $i;
						}
					}
				}

				if ( count( $email ) )
				{
					/* IPS: Check for duplicate */
					$this->DB->build( array(	'select'	=> '*',
												'from'		=> 'banfilters',
												'where'		=> "ban_content IN ('" . implode( "','", $email ) . "') and ban_type='email'" ) );
					$this->DB->execute();

					while( $row = $this->DB->fetch() )
					{
						$ban['email'][] = $row['ban_content'];
					}

					/* Now insert.. */
					foreach( $email as $e )
					{
						if ( ! in_array( $e, $ban['email'] ) )
						{
							/* Insert the new ban filter */
							$this->DB->insert( 'banfilters', array( 'ban_type' => 'email', 'ban_content' => $e, 'ban_date' => time() ) );
						}
					}
				}
			}
		}

		$message	= sprintf( $this->lang->words['t_membanned'], count($ids) );
		
		ipsRegistry::getClass('adminFunctions')->saveAdminLog( $message );
		
		return $message;
	}

	/**
	 * Unlock member(s)
	 *
	 * @note	Exceptions CAN bubble up, so you should still capture exceptions from calls to this method
	 * @param	array 	Array of member ids
	 * @return	string	Confirmation message
	 */
	public function unlockMembers( $ids )
	{
		foreach( $ids as $_id )
		{
			IPSMember::save( $_id, array( 'core' => array( 'failed_logins' => '', 'failed_login_count' => 0 ) ) );
		}
		
		$message	= sprintf( $this->lang->words['t_memunlocked'], count($ids) );
		
		ipsRegistry::getClass('adminFunctions')->saveAdminLog( $message );
		
		return $message;
	}
	
	/**
	 * Finalize member(s)
	 *
	 * @note	Exceptions CAN bubble up, so you should still capture exceptions from calls to this method
	 * @param	array 	Array of member ids
	 * @return	string	Confirmation message
	 */
	public function finalizeMembers( $ids )
	{
		$members	= IPSMember::load( $ids, 'core' );
		$_total		= 0;
		
		foreach( $members as $member )
		{
			if( $member['name'] AND $member['members_display_name'] AND $member['email'] )
			{
				$this->DB->delete( 'members_partial', 'partial_member_id=' . $member['member_id'] );
				$_total++;
			}
		}
		
		/* Reset last member */
		IPSMember::resetLastRegisteredMember();
		
		$message	= sprintf( $this->lang->words['t_inc_finalized'], $_total );
		
		ipsRegistry::getClass('adminFunctions')->saveAdminLog( $message );
		
		return $message;
	}

	/**
	 * Unapprove member email change
	 *
	 * @param	int 	Member ID
	 * @return	string	Confirmation message
	 * @throws	NO_VALIDATING_MEMBER
	 */
	public function unapproveEmailChange( $id )
	{
		$member	= $this->DB->buildAndFetch( array( 'select'		=> 'v.*',
												   'from'		=> array( 'validating' => 'v' ),
												   'where'		=> 'v.email_chg=1 AND v.member_id=' . intval($id),
												   'add_join'	=> array( array( 'select' => 'm.member_group_id AS old_member_group',
																				 'from'   => array( 'members' => 'm' ),
																				 'where'  => 'm.member_id=v.member_id',
																				 'type'   => 'left' ) )
										   )	  );
		
		if( !$member['vid'] )
		{
			throw new Exception( "NO_VALIDATING_MEMBER" );
		}

		$this->DB->delete( "validating", "vid='{$member['vid']}'" );

		IPSMember::save( $member['member_id'], array( 'core' => array( 'email' => $member['prev_email'], 'member_group_id' => $member['real_group'] ) ) );

		IPSLib::runMemberSync( 'onGroupChange', $member['member_id'], $member['real_group'], $member['old_member_group'] );
		
		/* Reset last member */
		IPSMember::resetLastRegisteredMember();

		$message	= sprintf( $this->lang->words['t_emailchangeun'], $member['member_id'] );
		
		ipsRegistry::getClass('adminFunctions')->saveAdminLog( $message );
		
		return $message;
	}
	
	/**
	 * Approve member(s)
	 *
	 * @note	Exceptions CAN bubble up, so you should still capture exceptions from calls to this method
	 * @param	array 	Array of member ids
	 * @return	string	Confirmation message
	 */
	public function approveMembers( $ids )
	{
		$approved = array();

		//-----------------------------------------
		// Get members
		//-----------------------------------------

		$this->DB->build( array( 'select'	=> 'v.*',
										'from'	=> array( 'validating' => 'v' ),
										'where'	=> "m.member_id IN(" . implode( ",", $ids ) . ")",
										'add_join'	=> array(
															array( 'select'	=> 'm.*',
																	'from'	=> array( 'members' => 'm' ),
																	'where'	=> 'm.member_id=v.member_id',
																	'type'	=> 'left'
																)
															)
							)		);
		$main = $this->DB->execute();

		while( $row = $this->DB->fetch( $main ) )
		{
			$approved[]	= $row['name'];

			//-----------------------------------------
			// Only approve if the user is validating
			//-----------------------------------------

			if ( $row['member_group_id'] != $this->settings['auth_group'] )
			{
				$row['real_group'] = $row['member_group_id'];
				// Let's still "validate", but leave them in the group they're in
				//continue;
			}

			//-----------------------------------------
			// Don't approve if no real_group set
			//-----------------------------------------

			if ( !$row['real_group'] )
			{
				//$row['real_group'] = $this->settings['member_group'];
				continue;
			}

			//-----------------------------------------
			// We don't approve lost pass requests
			//-----------------------------------------

			if( $row['lost_pass'] == 1 )
			{
				continue;
			}

			if( $row['real_group'] != $row['member_group_id'] )
			{
				IPSMember::save( $row['member_id'], array( 'core' => array( 'member_group_id' => $row['real_group'] ) ) );
			}

			IPSText::getTextClass('email')->buildMessage( array() );

			//-----------------------------------------
			// Using 'name' on purpose
			// @link http://forums.invisionpower.com/index.php?autocom=tracker&showissue=11564&view=findpost&p=45269
			//-----------------------------------------
			
			IPSText::getTextClass('email')->getTemplate( 'complete_reg', $row['language'] );
					
			IPSText::getTextClass('email')->buildMessage( array( 'NAME'	=> $row['name'] ) );
														 
			IPSText::getTextClass('email')->subject = sprintf( $this->lang->words['subject__complete_reg'], $row['name'], $this->settings['board_name'] );
			IPSText::getTextClass('email')->to      = $row['email'];
			IPSText::getTextClass('email')->sendMail();
	
			IPSLib::runMemberSync( 'onCompleteAccount', $row );
			IPSLib::runMemberSync( 'onGroupChange', $row['member_id'], $row['real_group'], $row['member_group_id'] );
		}

		$this->DB->delete( 'validating', "member_id IN(" . implode( ",", $ids ) . ")" );

		//-----------------------------------------
		// Stats to Update?
		//-----------------------------------------

		$this->cache->rebuildCache( 'stats', 'global' );

		$message	= sprintf( $this->lang->words['t_memregapp2'], count($ids), implode( ", ", $approved ) );

		ipsRegistry::getClass('adminFunctions')->saveAdminLog( $message );
		
		return $message;
	}

	/**
	 * Resend member validation emails
	 *
	 * @note	Exceptions CAN bubble up, so you should still capture exceptions from calls to this method
	 * @param	array 	Array of member ids
	 * @return	string	Confirmation message
	 */
	public function resendValidationEmails( $ids )
	{
		$reset		= array();
		$cant		= array();
		$main_msgs	= array();

		//-----------------------------------------
		// Get members
		//-----------------------------------------

		$this->DB->build( array( 'select'	=> 'v.*',
								 'from'		=> array( 'validating' => 'v' ),
								 'where'	=> "m.member_id IN(" . implode( ",", $ids ) . ")",
								 'add_join'	=> array( array( 'select' => 'm.member_id, m.name, m.members_display_name, m.email, m.member_group_id, m.language',
															 'from'   => array( 'members' => 'm' ),
															 'where'  => 'm.member_id=v.member_id',
															 'type'   => 'left' ) )
						)		);
		$main = $this->DB->execute();

		while( $row = $this->DB->fetch( $main ) )
		{
			if ( $row['member_group_id'] != $this->settings['auth_group'] )
			{
				continue;
			}

			if ( $row['lost_pass'] )
			{
				IPSText::getTextClass('email')->getTemplate( 'lost_pass', $row['language'] );

				IPSText::getTextClass('email')->buildMessage( array(
																'USERNAME'	=> $row['name'],
																'NAME'		=> $row['members_display_name'],
																'THE_LINK'	=> $this->registry->getClass('output')->buildUrl( "app=core&module=global&section=lostpass&do=sendform&uid={$row['member_id']}&aid={$row['vid']}", 'publicNoSession' ),
																'MAN_LINK'	=> $this->registry->getClass('output')->buildUrl( 'app=core&module=global&section=lostpass&do=sendform', 'publicNoSession' ),
																'EMAIL'		=> $row['email'],
																'ID'		=> $row['member_id'],
																'CODE'		=> $row['vid'],
																'IP_ADDRESS'=> $row['ip_address'],
															)		);

				IPSText::getTextClass('email')->subject	= $this->lang->words['t_passwordrec'] . $this->settings['board_name'];
				IPSText::getTextClass('email')->to		= $row['email'];
				IPSText::getTextClass('email')->sendMail();
			}
			else if ( $row['new_reg'] )
			{
				if( $row['user_verified'] )
				{
					$cant[]	= $row['members_display_name'];
					continue;
				}

				IPSText::getTextClass('email')->getTemplate( 'reg_validate', $row['language'] );

				IPSText::getTextClass('email')->buildMessage( array(
																'THE_LINK'	=> $this->registry->getClass('output')->buildUrl( "app=core&module=global&section=register&do=auto_validate&uid={$row['member_id']}&aid={$row['vid']}", 'publicNoSession' ),
																'NAME'		=> $row['members_display_name'],
																'MAN_LINK'	=> $this->registry->getClass('output')->buildUrl( "app=core&module=global&section=register&do=05", 'publicNoSession' ),
																'EMAIL'		=> $row['email'],
																'ID'		=> $row['member_id'],
																'CODE'		=> $row['vid'],
															)		);

				IPSText::getTextClass('email')->subject	= sprintf( $this->lang->words['t_regat'], $this->settings['board_name'] );
				IPSText::getTextClass('email')->to		= $row['email'];
				IPSText::getTextClass('email')->sendMail();
			}
			else if ( $row['email_chg'] )
			{
				IPSText::getTextClass('email')->getTemplate( 'newemail', $row['language'] );

				IPSText::getTextClass('email')->buildMessage( array(
																'NAME'		=> $row['members_display_name'],
																'THE_LINK'	=> $this->registry->getClass('output')->buildUrl( "app=core&module=global&section=register&do=auto_validate&type=newemail&uid={$row['member_id']}&aid={$row['vid']}", 'publicNoSession' ),
																'ID'		=> $row['member_id'],
																'MAN_LINK'	=> $this->registry->getClass('output')->buildUrl( "app=core&module=global&section=register&do=user_validate", 'publicNoSession' ),
																'CODE'		=> $row['vid'],
															)		);

				IPSText::getTextClass('email')->subject	= sprintf( $this->lang->words['t_emailchange'], $this->settings['board_name'] );
				IPSText::getTextClass('email')->to		= $row['email'];
				IPSText::getTextClass('email')->sendMail();
			}

			$resent[]	= $row['members_display_name'];
		}

		if( count($resent) )
		{
			$message		= sprintf( $this->lang->words['tools_val_resent_log'], count($resent), implode( ", ", $resent ) );
			$main_msgs[]	= $message;

			ipsRegistry::getClass('adminFunctions')->saveAdminLog( $message );
		}

		if( count($cant) )
		{
			$main_msgs[]	= sprintf( $this->lang->words['t_valcannot'], implode( ", ", $cant ) );
		}

		return count($main_msgs) ? implode( "\n", $main_msgs ) : '';
	}
}