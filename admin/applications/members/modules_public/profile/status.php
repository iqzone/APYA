<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Allow user to change their status
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

class public_members_profile_status extends ipsCommand 
{
	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		$this->request['do'] = ( $this->request['do'] ) ? $this->request['do'] : 'list';
		
		//-----------------------------------------
		// Security check
		//-----------------------------------------
		
		if ( $this->request['do'] != 'list' AND ( $this->request['k'] != $this->member->form_hash ) )
		{
			$this->registry->getClass('output')->showError( 'no_permission', 20314, null, null, 403 );
		}
 				
		//-----------------------------------------
		// Get HTML and skin
		//-----------------------------------------

		$this->registry->class_localization->loadLanguageFile( array( 'public_profile' ), 'members' );
		
		/* Load status class */
		if ( ! $this->registry->isClassLoaded( 'memberStatus' ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/member/status.php', 'memberStatus' );
			$this->registry->setClass( 'memberStatus', new $classToLoad( ipsRegistry::instance() ) );
		}
		
		/* WHAT R WE DOING? */
		switch( $this->request['do'] )
		{
			default:
			case 'list':
				$this->_list();
			break;
			case 'new':
				$this->_new();
			break;
			case 'reply':
				$this->_reply();
			break;
			case 'deleteStatus':
				$this->_deleteStatus();
			break;
			case 'deleteReply':
				$this->_deleteReply();
			break;
			case 'lockStatus':
				$this->_lockStatus();
			break;
			case 'unlockStatus':
				$this->_unlockStatus();
			break;
		}
	}
	
	/**
	* Lock a status
	*
	*/
	protected function _lockStatus()
	{
		/* INIT */
		$status_id = intval( $this->request['status_id'] );
		
		/* Quick check? */
		if ( ! $status_id )
 		{
			$this->registry->output->showError( 'status_off', 10276, null, null, 404 );
		}

		/* Set Author */
		$this->registry->getClass('memberStatus')->setAuthor( $this->memberData );
		
		/* Set Data */
		$this->registry->getClass('memberStatus')->setStatusData( $status_id );
		
		/* Can we reply? */
		if ( ! $this->registry->getClass('memberStatus')->canLockStatus() )
 		{
			$this->registry->output->showError( 'status_off', 10277, null, null, 403 );
		}

		/* Update */
		$this->registry->getClass('memberStatus')->lockStatus();
		
		/* Got a return URL? */
		if ( $this->request['rurl'] )
		{
			$this->registry->output->redirectScreen( $this->lang->words['status_locked'], $this->settings['base_url'] . base64_decode( $this->request['rurl'] ) );
		}
		else
		{
			$this->registry->output->redirectScreen( $this->lang->words['status_locked'], $this->settings['base_url'] . 'showuser=' . $this->memberData['member_id'], $this->memberData['members_seo_name'] );
		}
	}
	
	/**
	* Lock a status
	*
	*/
	protected function _unlockStatus()
	{
		/* INIT */
		$status_id = intval( $this->request['status_id'] );
		
		/* Quick check? */
		if ( ! $status_id )
 		{
			$this->registry->output->showError( 'status_off', 10276, null, null, 404 );
		}

		/* Set Author */
		$this->registry->getClass('memberStatus')->setAuthor( $this->memberData );
		
		/* Set Data */
		$this->registry->getClass('memberStatus')->setStatusData( $status_id );
		
		/* Can we reply? */
		if ( ! $this->registry->getClass('memberStatus')->canUnlockStatus() )
 		{
			$this->registry->output->showError( 'status_off', 10277, null, null, 403 );
		}

		/* Update */
		$this->registry->getClass('memberStatus')->unlockStatus();
		
		/* Got a return URL? */
		if ( $this->request['rurl'] )
		{
			$this->registry->output->redirectScreen( $this->lang->words['status_unlocked'], $this->settings['base_url'] . base64_decode( $this->request['rurl'] ) );
		}
		else
		{
			$this->registry->output->redirectScreen( $this->lang->words['status_unlocked'], $this->settings['base_url'] . 'showuser=' . $this->memberData['member_id'], $this->memberData['members_seo_name'] );
		}
	}
	
	/**
	* Delete a status reply
	*
	*/
	protected function _list()
	{
		/* INIT */
		$filters = array( 'offset' => intval( $this->request['st'] ), 'limit' => 15, 'getCount' => true );
		
		/* Add to the filters */
		if ( $this->request['member_id'] )
		{
			$filters['relatedTo']  = intval( $this->request['member_id'] );
			$filters['isApproved'] = true;
		}
		else if ( $this->request['status_id'] )
		{
			$filters['status_id'] = intval( $this->request['status_id'] );
			$filters['type']      = 'all';
		}
		else if ( $this->request['type'] == 'friends' )
		{
			$filters['friends_only'] = 1;
			$filters['type']         = 'all';
		}
		
		/* Fetch last 20 */
		$this->registry->getClass('memberStatus')->setAuthor( $this->memberData );
		
		/* Fetch */
		$statuses = $this->registry->getClass('memberStatus')->fetch( $this->memberData, $filters );
		$count    = $this->registry->getClass('memberStatus')->getStatusCount();
		
		$pages    = $this->registry->getClass('output')->generatePagination( array( 'totalItems' 			=> $count,
																					'itemsPerPage'			=> 15,
																					'currentStartValue'   	=> $this->request['st'],
																					'seoTitle'				=> true,
																					'seoTemplate'			=> 'members_status_all',
																					'showNumbers'			=> false,
																					'baseUrl'				=> "app=members&amp;module=profile&amp;section=status&amp;type={$this->request['type']}&amp;member_id={$this->request['member_id']}" )	);
		
		$content = $this->registry->getClass('output')->getTemplate('profile')->statusUpdatesPage( $statuses, $pages );
		
		$this->registry->output->addContent( $content );
		$this->registry->output->setTitle( $this->lang->words['status_updates_title'] . ' - ' . ipsRegistry::$settings['board_name'] );
		$this->registry->output->addNavigation( $this->lang->words['status_updates_title'], '' );
		$this->registry->output->sendOutput();
		
	}
	
	/**
	* Delete a status reply
	*
	*/
	protected function _deleteReply()
	{
		/* INIT */
		$status_id = intval( $this->request['status_id'] );
		$reply_id  = intval( $this->request['reply_id'] );
		
		/* Quick check? */
		if ( ! $status_id OR ! $reply_id )
 		{
			$this->registry->output->showError( 'status_off', 10276, null, null, 404 );
		}

		/* Set Author */
		$this->registry->getClass('memberStatus')->setAuthor( $this->memberData );
		
		/* Set Data */
		$this->registry->getClass('memberStatus')->setStatusData( $status_id );
		$this->registry->getClass('memberStatus')->setReplyData( $reply_id );
		
		/* Can we reply? */
		if ( ! $this->registry->getClass('memberStatus')->canDeleteReply() )
 		{
			$this->registry->output->showError( 'status_off', 10277, null, null, 403 );
		}

		/* Update */
		$this->registry->getClass('memberStatus')->deleteReply();
		
		/* Got a return URL? */
		if ( $this->request['rurl'] )
		{
			$this->registry->output->redirectScreen( $this->lang->words['status_reply_deleted'], $this->settings['base_url'] . base64_decode( $this->request['rurl'] ) );
		}
		else
		{
			$this->registry->output->redirectScreen( $this->lang->words['status_reply_deleted'], $this->settings['base_url'] . 'app=members&amp;module=profile&amp;section=status&amp;status_id=' . $status_id, 'false', 'members_status_all' );
		}
	}
	
	/**
	* Delete a status
	*
	*/
	protected function _deleteStatus()
	{
		/* INIT */
		$status_id = intval( $this->request['status_id'] );
		
		/* Quick check? */
		if ( ! $status_id )
 		{
			$this->registry->output->showError( 'status_off', 10278, null, null, 404 );
		}

		/* Set Author */
		$this->registry->getClass('memberStatus')->setAuthor( $this->memberData );
		
		/* Set Data */
		$this->registry->getClass('memberStatus')->setStatusData( $status_id );
		
		/* Can we delete? */
		if ( ! $this->registry->getClass('memberStatus')->canDeleteStatus() )
 		{
			$this->registry->output->showError( 'status_off', 10279, null, null, 403 );
		}
		
		/* Get info */
		$status	= $this->registry->getClass('memberStatus')->getStatusData();

		/* Update */
		$this->registry->getClass('memberStatus')->deleteStatus();
		
		/* Got a return URL? */
		if ( $this->request['rurl'] )
		{
			$this->registry->output->redirectScreen( $this->lang->words['status_deleted'], $this->settings['base_url'] . base64_decode( $this->request['rurl'] ) );
		}
		else
		{
			$_text	= ( $status['status_member_id'] == $status['status_author_id'] ) ? $this->lang->words['status_deleted'] : $this->lang->words['comment_was_deleted'];
			
			$this->registry->output->redirectScreen( $_text, $this->settings['base_url'] . 'showuser=' . $status['status_member_id'] . '&amp;tab=status', $status['members_seo_name'] );
		}
	}
	
	/**
	* Add a reply statussesses
	*
	*/
	protected function _reply()
	{
		/* INIT */
		$status_id = intval( $this->request['status_id'] );
		$comment   = trim( $this->request['comment-' . $status_id ] );
		$id        = intval( $this->request['id'] );
		
		/* Quick check? */
		if ( ! $status_id OR ! $comment )
 		{
			$this->registry->output->showError( 'status_off', 10280, null, null, 404 );
		}

		/* Set Author */
		$this->registry->getClass('memberStatus')->setAuthor( $this->memberData );
		
		/* Set Content */
		$this->registry->getClass('memberStatus')->setContent( $comment );
		
		/* Set Data */
		$this->registry->getClass('memberStatus')->setStatusData( $status_id );
		
		/* Can we reply? */
		if ( ! $this->registry->getClass('memberStatus')->canReply() )
 		{
			$this->registry->output->showError( 'status_off', 10281, null, null, 403 );
		}

		/* Update */
		$this->registry->getClass('memberStatus')->reply();
		
		/* Got a return URL? */
		if ( $this->request['rurl'] )
		{
			$this->registry->output->redirectScreen( $this->lang->words['status_reply_done'], $this->settings['base_url'] . base64_decode( $this->request['rurl'] ) );
		}
		else
		{
			$this->registry->output->redirectScreen( $this->lang->words['status_reply_done'], $this->settings['base_url'] . 'showuser=' . $id, $this->memberData['members_seo_name'] );
		}
	}
	
	/**
	* Add a new statussesses
	*
	*/
	protected function _new()
	{
		$id   = intval( $this->memberData['member_id'] );
		$su_Twitter  = intval( $this->request['su_Twitter'] );
		$su_Facebook = intval( $this->request['su_Facebook'] );
		$forMemberId = intval( $this->request['forMemberId'] );
		
		/* Set Author */
		$this->registry->getClass('memberStatus')->setAuthor( $this->memberData );
		
		/* Set Content */
		$this->registry->getClass('memberStatus')->setContent( trim( $this->request['content'] ) );
		
		/* Can we reply? */
		if ( ! $this->registry->getClass('memberStatus')->canCreate() )
 		{
			$this->registry->output->showError( 'status_off', 10268, null, null, 403 );
		}
		
		/* Update or comment? */
		if ( $forMemberId && $forMemberId != $this->memberData['member_id'] )
		{
			$owner = IPSMember::load( $forMemberId );
			
	    	if ( ! $owner['pp_setting_count_comments'] )
	    	{
	    		$this->registry->output->showError( 'status_off', 10268, null, null, 403 );
	    	}
	
			/* Set owner */
			$this->registry->getClass('memberStatus')->setStatusOwner( $owner );
		}
		else
		{
			/* Set post outs */
			$this->registry->getClass('memberStatus')->setExternalUpdates( array( 'twitter' => $su_Twitter, 'facebook' => $su_Facebook ) );
		}

		/* Update */
		$this->registry->getClass('memberStatus')->create();
		
		/* Got a return URL? */
		if ( $this->request['rurl'] )
		{
			$this->registry->output->redirectScreen( $this->lang->words['status_was_changed'], $this->settings['base_url'] . base64_decode( $this->request['rurl'] ) );
		}
		else
		{
			$this->registry->output->redirectScreen( $this->lang->words['status_was_changed'], $this->settings['base_url'] . 'showuser=' . $id, $this->memberData['members_seo_name'] );
		}
	}
}