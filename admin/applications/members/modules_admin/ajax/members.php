<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Member list AJAX handler
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @version		$Revision: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class admin_members_ajax_members extends ipsAjaxCommand 
{
	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		$this->registry->class_localization->loadLanguageFile( array( 'admin_member' ), 'members' );
		
    	switch( $this->request['do'] )
    	{
			default:
			case 'show':
				$this->show();
			break;

			case 'do_validating':
				$this->registry->getClass('class_permissions')->return	= true;
				
				if( ! $this->registry->getClass('class_permissions')->checkPermission( 'membertools_validating', 'members', 'members' ) )
				{
					$this->returnJsonError( $this->lang->words['no_permission'] );
				}

				$this->_manageValidating();
			break;
			
			case 'unappemail':
				$this->registry->getClass('class_permissions')->return	= true;
				
				if( ! $this->registry->getClass('class_permissions')->checkPermission( 'membertools_validating', 'members', 'members' ) )
				{
					$this->returnJsonError( $this->lang->words['no_permission'] );
				}

				$this->_emailUnapprove();
			break;

			case 'do_locked':
				$this->registry->getClass('class_permissions')->return	= true;
				
				if( ! $this->registry->getClass('class_permissions')->checkPermission( 'membertools_locked', 'members', 'members' ) )
				{
					$this->returnJsonError( $this->lang->words['no_permission'] );
				}

				$this->_unlock();
			break;

			case 'do_spam':
				$this->registry->getClass('class_permissions')->return	= true;
				
				if( ! $this->registry->getClass('class_permissions')->checkPermission( 'membertools_spam', 'members', 'members' ) )
				{
					$this->returnJsonError( $this->lang->words['no_permission'] );
				}

				$this->_unSpam();
			break;

			case 'do_banned':
				$this->registry->getClass('class_permissions')->return	= true;
				
				if( ! $this->registry->getClass('class_permissions')->checkPermission( 'membertools_banned', 'members', 'members' ) )
				{
					$this->returnJsonError( $this->lang->words['no_permission'] );
				}

				$this->_unban();
			break;
			
			case 'do_incomplete':
				$this->registry->getClass('class_permissions')->return	= true;
				
				if( ! $this->registry->getClass('class_permissions')->checkPermission( 'membertools_incomplete', 'members', 'members' ) )
				{
					$this->returnJsonError( $this->lang->words['no_permission'] );
				}

				$this->_doIncomplete();
			break;
			
			case 'do_delete':
				$this->registry->getClass('class_permissions')->return	= true;
				
				if( ! $this->registry->getClass('class_permissions')->checkPermission( 'member_delete', 'members', 'members' ) )
				{
					$this->returnJsonError( $this->lang->words['no_permission'] );
				}

				$this->_doDelete();
			break;
    	}
	}
	
	/**
	 * Delete members [form+process]
	 *
	 * @return	@e void
	 */
	protected function _doDelete()
	{
		//-----------------------------------------
		// Check input
		//-----------------------------------------
		
		$ids = IPSLib::fetchInputAsArray( 'mid_' );
		
		if ( !count($ids) )
		{
			$this->returnJsonError( $this->lang->words['m_nomember'] );
		}

		/* Don't delete our selves */
		if( in_array( $this->memberData['member_id'], $ids ) )
		{
			$this->returnJsonError( $this->lang->words['m_nodeleteslefr'] );
		}

		//-----------------------------------------
		// Get accounts
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => 'member_id, name, member_group_id, mgroup_others', 'from' => 'members', 'where' => 'member_id IN(' . implode( ',', $ids ) . ')' ) );
		$this->DB->execute();
		
		$names	= array();
		$newIds	= array();
		
		while ( $r = $this->DB->fetch() )
		{
			//-----------------------------------------
			// r u trying to kill teh admin?
			//-----------------------------------------

			if( ! $this->registry->getClass('class_permissions')->checkPermission( 'member_delete_admin' ) )
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
			
			$names[]	= $r['name'];
			$newIds[]	= $r['member_id'];
		}
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! count( $names ) )
		{
			$this->returnJsonError( $this->lang->words['m_nomember'] );
		}
		
		//-----------------------------------------
		// Delete
		//-----------------------------------------
		
		IPSMember::remove( $newIds, true );
		
		ipsRegistry::getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['m_deletedlog'], implode( ",", $names ) ) );

		//-----------------------------------------
		// Respond
		//-----------------------------------------
		
		$this->returnJsonArray( array( 'ok' => 1, 'msg' => sprintf( $this->lang->words['m_deletedlog'], implode( ",", $names ) ) ) );
	}
	
	/**
	 * Show the results
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function show()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('members') . '/sources/adminSearch.php', 'adminMemberSearch', 'members' );
		$searchHelper	= new $classToLoad( $this->registry );
		$html			= $this->registry->output->loadTemplate('cp_skin_member');

		//-----------------------------------------
		// Get the number of results
		//-----------------------------------------
		
		$count	= $searchHelper->getSearchResultsCount();
		
		IPSDebug::fireBug( 'info', array( 'Total results:' . $count ) );
		
		//-----------------------------------------
		// Generate pagination
		//-----------------------------------------
		
		$st			= intval($this->request['st']);
		$perpage	= 20;

		$pages		= $this->registry->output->generatePagination( array(
																		'totalItems'		=> $count,
																		'itemsPerPage'		=> $perpage,
																		'currentStartValue'	=> $st,
																		'baseUrl'			=> $this->settings['base_url'] . 'module=members&amp;section=members',
																)		);
		
		//-----------------------------------------
		// Run the query
		//-----------------------------------------
		
		$members	= $searchHelper->getSearchResults( $st, $perpage );
		
		IPSDebug::fireBug( 'info', array( 'Total results (2):' . count($members) ) );
		
		//-----------------------------------------
		// Format results
		//-----------------------------------------

		$_memberOutput	= '';
		
		if( count($members) )
		{
			foreach( $members as $member )
			{
				/* Ensure encoding is safe */
				//$member['members_display_name'] = IPSText::encodeForXml( $member['members_display_name'] );
				//$member['name'] 			    = IPSText::encodeForXml( $member['name'] );
				/* The above causes strings returned on utf-8 sites to be entirely corrupted
					@link http://community.invisionpower.com/tracker/issue-32444-ajax-for-text-in-acp */
				
				
				IPSDebug::fireBug( 'info', array( 'Showing member:' . $member['members_display_name'] . ' (' . $member['email'] . ' - ' . $member['member_id'] . ')' ) );
				
				switch( $searchHelper->getMemberType() )
				{
					case 'all':
					default:
						$_memberOutput .= $html->memberListRow( $member );
					break;
					
					case 'spam':
						$_memberOutput .= $html->memberListRow_spam( $member );
					break;
		
					case 'banned':
						$_memberOutput .= $html->memberListRow_banned( $member );
					break;
		
					case 'locked':
						$_memberOutput .= $html->memberListRow_locked( $member );
					break;
		
					case 'validating':
						$_memberOutput .= $html->memberListRow_validating( $member );
					break;
		
					case 'incomplete':
						$_memberOutput .= $html->memberListRow_incomplete( $member );
					break;
				}
			}
		}
		else
		{
			$_memberOutput = $html->memberListRow_empty();
		}
		
		//-----------------------------------------
		// Return as JSON
		//-----------------------------------------
		
		$this->returnJsonArray( array(
									'count'		=> $count,
									'pages'		=> $pages,
									'members'	=> $_memberOutput ) );
	}

	/**
	 * Manage incomplete members
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _doIncomplete()
	{
		//-----------------------------------------
		// Check
		//-----------------------------------------

		$ids = IPSLib::fetchInputAsArray( 'mid_' );
		
		if ( count($ids) < 1 )
		{
			$this->returnJsonError( $this->lang->words['t_nomemsel'] );
		}

		//-----------------------------------------
		// DELETE
		//-----------------------------------------

		if ( $this->request['type'] == 'delete' )
		{
			$this->registry->getClass('class_permissions')->return	= true;
			
			if( ! $this->registry->getClass('class_permissions')->checkPermission( 'member_delete', 'members', 'members' ) )
			{
				$this->returnJsonError( $this->lang->words['no_permission'] );
			}
				
			try
			{
				$message	= $this->_getManagementClass()->deleteMembers( $ids, 't_inc_removed' );
			}
			catch( Exception $error )
			{
				$this->returnJsonError( $error->getMessage() );
			}

			$this->returnJsonArray( array( 'ok' => 1, 'msg' => $message ) );
		}
		else if( $this->request['type'] == 'finalize' )
		{
			try
			{
				$message	= $this->_getManagementClass()->finalizeMembers( $ids );
			}
			catch( Exception $error )
			{
				$this->returnJsonError( $error->getMessage() );
			}

			$this->returnJsonArray( array( 'ok' => 1, 'msg' => $message ) );
		}
	}
	
	/**
	 * Manage validating members
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _manageValidating()
	{
		//-----------------------------------------
		// Check
		//-----------------------------------------

		$ids = IPSLib::fetchInputAsArray( 'mid_' );
		
		if ( count($ids) < 1 )
		{
			$this->returnJsonError( $this->lang->words['t_nomemsel'] );
		}

		//-----------------------------------------
		// APPROVE
		//-----------------------------------------

		if ( $this->request['type'] == 'approve' )
		{
			try
			{
				$message	= $this->_getManagementClass()->approveMembers( $ids );
			}
			catch( Exception $error )
			{
				$this->returnJsonError( $error->getMessage() );
			}

			$this->returnJsonArray( array( 'ok' => 1, 'msg' => $message ) );
		}

		//-----------------------------------------
		// Resend validation email
		//-----------------------------------------

		else if ( $this->request['type'] == 'resend' )
		{
			try
			{
				$message	= $this->_getManagementClass()->resendValidationEmails( $ids );
			}
			catch( Exception $error )
			{
				$this->returnJsonError( $error->getMessage() );
			}

			$this->returnJsonArray( array( 'ok' => 1, 'msg' => $message ) );
		}

		//-----------------------------------------
		// Ban
		//-----------------------------------------

		else if( $this->request['type'] == 'ban' )
		{
			$this->registry->getClass('class_permissions')->return	= true;
			
			if( ! $this->registry->getClass('class_permissions')->checkPermission( 'member_ban', 'members', 'members' ) )
			{
				$this->returnJsonError( $this->lang->words['no_permission'] );
			}

			try
			{
				$message	= $this->_getManagementClass()->banMembers( $ids );
			}
			catch( Exception $error )
			{
				$this->returnJsonError( $error->getMessage() );
			}

			$this->returnJsonArray( array( 'ok' => 1, 'msg' => $message ) );
		}

		//-----------------------------------------
		// SPAMMER
		//-----------------------------------------

		else if ( $this->request['type'] == 'spam' )
		{
			try
			{
				$message	= $this->_getManagementClass()->markMembersAsSpam( $ids );
			}
			catch( Exception $error )
			{
				$this->returnJsonError( $error->getMessage() );
			}

			$this->returnJsonArray( array( 'ok' => 1, 'msg' => $message ) );
		}

		//-----------------------------------------
		// DELETE
		//-----------------------------------------

		else
		{
			$this->registry->getClass('class_permissions')->return	= true;
			
			if( ! $this->registry->getClass('class_permissions')->checkPermission( 'member_delete', 'members', 'members' ) )
			{
				$this->returnJsonError( $this->lang->words['no_permission'] );
			}

			try
			{
				$message	= $this->_getManagementClass()->denyMembers( $ids );
			}
			catch( Exception $error )
			{
				$this->returnJsonError( $error->getMessage() );
			}

			$this->returnJsonArray( array( 'ok' => 1, 'msg' => $message ) );
		}
	}

	/**
	 * Manage spam requests
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _unSpam()
	{
		//-----------------------------------------
		// Check
		//-----------------------------------------

		$ids = IPSLib::fetchInputAsArray( 'mid_' );
		
		if ( count($ids) < 1 )
		{
			$this->returnJsonError( $this->lang->words['t_nomemunspammed'] );
		}

		//-----------------------------------------
		// Unspam
		//-----------------------------------------

		if ( $this->request['type'] == 'unspam' OR $this->request['type'] == 'unspam_posts' )
		{
			try
			{
				$message	= $this->_getManagementClass()->unmarkMembersAsSpam( $ids, $this->request['type'] == 'unspam_posts' ? true : false );
			}
			catch( Exception $error )
			{
				$this->returnJsonError( $error->getMessage() );
			}

			$this->returnJsonArray( array( 'ok' => 1, 'msg' => $message ) );
		}

		//-----------------------------------------
		// Ban
		//-----------------------------------------

		else if ( $this->request['type'] == 'ban' OR $this->request['type'] == 'ban_blacklist' )
		{
			$this->registry->getClass('class_permissions')->return	= true;
			
			if( ! $this->registry->getClass('class_permissions')->checkPermission( 'member_ban', 'members', 'members' ) )
			{
				$this->returnJsonError( $this->lang->words['no_permission'] );
			}

			try
			{
				$message	= $this->_getManagementClass()->banSpammers( $ids, $this->request['type'] == 'ban_blacklist' ? true : false );
			}
			catch( Exception $error )
			{
				$this->returnJsonError( $error->getMessage() );
			}

			$this->returnJsonArray( array( 'ok' => 1, 'msg' => $message ) );
		}
		else if( $this->request['type'] == 'delete' )
		{
			$this->registry->getClass('class_permissions')->return	= true;
			
			if( ! $this->registry->getClass('class_permissions')->checkPermission( 'member_delete', 'members', 'members' ) )
			{
				$this->returnJsonError( $this->lang->words['no_permission'] );
			}

			$message	= $this->_getManagementClass()->deleteMembers( $ids );
			
			$this->returnJsonArray( array( 'ok' => 1, 'msg' => $message ) );
		}
	}

	/**
	 * Manage banned requests
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _unban()
	{
		//-----------------------------------------
		// Check
		//-----------------------------------------

		$ids = IPSLib::fetchInputAsArray( 'mid_' );
		
		if ( count($ids) < 1 )
		{
			$this->returnJsonError( $this->lang->words['t_nomemunban'] );
		}

		//-----------------------------------------
		// Unlock
		//-----------------------------------------

		if ( $this->request['type'] == 'unban' )
		{
			try
			{
				$message	= $this->_getManagementClass()->unbanMembers( $ids );
			}
			catch( Exception $error )
			{
				$this->returnJsonError( $error->getMessage() );
			}
	
			$this->returnJsonArray( array( 'ok' => 1, 'msg' => $message ) );
		}

		//-----------------------------------------
		// Delete
		//-----------------------------------------

		else if ( $this->request['type'] == 'delete' )
		{
			$this->registry->getClass('class_permissions')->return	= true;
			
			if( ! $this->registry->getClass('class_permissions')->checkPermission( 'member_delete', 'members', 'members' ) )
			{
				$this->returnJsonError( $this->lang->words['no_permission'] );
			}

			$message	= $this->_getManagementClass()->deleteMembers( $ids );
			
			$this->returnJsonArray( array( 'ok' => 1, 'msg' => $message ) );
		}
	}

	/**
	 * Unapprove email change request
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _emailUnapprove()
	{
		//-----------------------------------------
		// GET member
		//-----------------------------------------

		if( !$this->request['mid'] )
		{
			$this->returnJsonError( $this->lang->words['t_noemailloc'] );
		}

		try
		{
			$message	= $this->_getManagementClass()->unapproveEmailChange( $this->request['mid'] );
		}
		catch( Exception $error )
		{
			$this->returnJsonError( $error->getMessage() );
		}

		$this->returnJsonArray( array( 'ok' => 1, 'msg' => $message ) );
	}


	/**
	 * Unlock selected accounts
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _unlock()
	{
		//-----------------------------------------
		// Check
		//-----------------------------------------

		$ids = IPSLib::fetchInputAsArray( 'mid_' );
		
		if ( count($ids) < 1 )
		{
			$this->returnJsonError( $this->lang->words['t_nolockloc'], 11251 );
		}

		//-----------------------------------------
		// Unlock
		//-----------------------------------------

		if ( $this->request['type'] == 'unlock' )
		{
			try
			{
				$message	= $this->_getManagementClass()->unlockMembers( $ids );
			}
			catch( Exception $error )
			{
				$this->returnJsonError( $error->getMessage() );
			}

			$this->returnJsonArray( array( 'ok' => 1, 'msg' => $message ) );
		}

		//-----------------------------------------
		// Ban
		//-----------------------------------------

		else if ( $this->request['type'] == 'ban' )
		{
			$this->registry->getClass('class_permissions')->return	= true;
			
			if( ! $this->registry->getClass('class_permissions')->checkPermission( 'member_ban', 'members', 'members' ) )
			{
				$this->returnJsonError( $this->lang->words['no_permission'] );
			}

			try
			{
				$message	= $this->_getManagementClass()->banMembers( $ids );
			}
			catch( Exception $error )
			{
				$this->returnJsonError( $error->getMessage() );
			}

			$this->returnJsonArray( array( 'ok' => 1, 'msg' => $message ) );
		}

		//-----------------------------------------
		// Delete
		//-----------------------------------------

		else if ( $this->request['type'] == 'delete' )
		{
			$this->registry->getClass('class_permissions')->return	= true;
			
			if( ! $this->registry->getClass('class_permissions')->checkPermission( 'member_delete', 'members', 'members' ) )
			{
				$this->returnJsonError( $this->lang->words['no_permission'] );
			}

			$message	= $this->_getManagementClass()->deleteMembers( $ids );

			$this->returnJsonArray( array( 'ok' => 1, 'msg' => $message ) );
		}
	}
	
	/**
	 * Get the member management class
	 *
	 * @return	object
	 */
	protected function _getManagementClass()
	{
		static $_object = null;
		
		if ( $_object === null )
		{
			$_class  = IPSLib::loadLibrary( IPSLib::getAppDir( 'members' ) . '/sources/adminManage.php', 'adminMemberManagement', 'members' );
			$_object = new $_class( $this->registry );
		}
		
		return $_object;
	}
}