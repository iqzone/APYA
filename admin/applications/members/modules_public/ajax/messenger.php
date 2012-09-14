<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Profile AJAX Ignored User methods
 * Last Updated: $Date: 2012-05-29 13:17:47 -0400 (Tue, 29 May 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @since		Thursday 26th June 2008
 * @version		$Revision: 10813 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_members_ajax_messenger extends ipsAjaxCommand 
{
	/**
	 * Messenger library
	 *
	 * @var		object
	 */
	protected $messengerFunctions;

	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Grab class
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'members' ) . '/sources/classes/messaging/messengerFunctions.php', 'messengerFunctions', 'members' );
		$this->messengerFunctions = new $classToLoad( $registry );
		
		switch( $this->request[ 'do' ] )
		{
			case 'addFolder':
				$this->_addFolder();
			break;
			case 'removeFolder':
				$this->_removeFolder();
			break;
			case 'renameFolder':
				$this->_renameFolder();
			break;
			case 'emptyFolder':
				$this->_emptyFolder();
			break;
			case 'getPMNotification':
				$this->_getPMNotification();
			break;
			case 'showQuickForm':
				$this->_showQuickForm();
			break;
			case 'PMSend':
				$this->_PMSend();
			break;
			case 'archiveConversation':
				$this->_archiveConversation();
			break;
			case 'getInboxDropDown':
				$this->_getInboxDropDown();
			break;
			case 'sigCloseMenu':
				$this->_sigCloseMenu();
			break;
			default:
			break;
		}
	}
	
	
	protected function _sigCloseMenu()
	{
		$this->lang->loadLanguageFile( array( 'public_topic', 'public_mod' ), 'forums' );
		
		$msgid = intval( $this->request['msgid'] );
		
		/* Fetch post */
		$msg  = $this->messengerFunctions->fetchMessageDataById( $msgid, true );
		
		if ( $this->messengerFunctions->canAccessTopic( $this->memberData['member_id'], $msg['msg_topic_id'] ) )
		{
			return $this->returnHtml( $this->registry->output->getTemplate('topic')->ajaxSigCloseMenu( $msg ) );
		}
		else
		{
			$this->returnJsonError( 'nopermission' );
		}
	}
	
	/**
	 * Shows the last 10 Pms or whatever.
	 *
	 * @return	string		HTML to be returned via ajax
	 * @since	IPB 3.1.0.2011-03-24
	 */
 	protected function _getInboxDropDown()
 	{
 		if ( ! $this->messengerFunctions->canUsePMSystem( $this->memberData ) )
 		{
 			$this->returnJsonError( 'cannotUsePMSystem' );
 		}
 		
 		$this->lang->loadLanguageFile( array( 'public_messaging' ), 'members' );
 		
 		/* Alright. Fetch the last 10 */
 		//$this->messengerFunctions->addFolderFilter('myconvo');
 		
 		$topics = $this->messengerFunctions->getPersonalTopicsList( $this->memberData['member_id'], 'myconvo', array( 'sort' => 'date', 'offsetStart' => 0, 'offsetEnd' => 10 ) );
 		$topics = ( ! is_array( $topics ) ) ? array() : $topics;
 		
 		/* reset */
 		$this->messengerFunctions->resetMembersAlertCounts( $this->memberData );
 		
 		$this->returnJsonArray( array( 'html' => $this->cleanOutput( $this->registry->output->getTemplate('global_other')->inboxList( $topics ) ) ) );
 	}
 	
	/**
	 * Archives the conversation
	 *
	 * @return	string		HTML to be returned via ajax
	 * @since	IPB 3.1.2.2010-07-07
	 */
 	protected function _archiveConversation()
 	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$memberID            = intval( $this->request['memberID'] );
		$topicID             = intval( $this->request['topicID'] );
		
		/* Load lang */
		$this->registry->class_localization->loadLanguageFile( array( 'public_messaging' ), 'members' );
		
		/* Basic checks */
		if ( ! $this->memberData['member_id'] )
		{
			$this->returnJsonError( 'cannotUsePMSystem' );
		}
		
		if ( ! $topicID )
		{
			$this->returnJsonError( $this->lang->words['ajax_no_topic_id'] );
		}
		
		/* Attempt to fetch the conversation */
		try
		{
			$conversationData = $this->messengerFunctions->fetchConversation( $topicID, $this->memberData['member_id'], array( 'offsetStart' => 0, 'offsetEnd' => 150 ) );
			
			$html = $this->registry->getClass('output')->getTemplate('messaging')->showConversationForArchive( $conversationData['topicData'], $conversationData['replyData'], $conversationData['memberData'] );
			
			if ( ! $html )
			{
				return $this->returnJsonError( $this->lang->words['ajax_no_permission'] );
			}
			
			/* Email it */
			IPSText::getTextClass('email')->getTemplate( "email_convo" );
			
			IPSText::getTextClass('email')->buildMessage( array( 'TITLE'	=> $conversationData['topicData']['mt_title'],
																 'LINK'		=> $this->registry->output->buildUrl( "app=members&amp;module=messaging&amp;section=view&amp;do=showConversation&amp;topicID=" . $topicID, 'public' ),
																 'DATE'		=> $this->lang->getDate( $conversationData['topicData']['mt_start_time'], 'SHORT' ),
																 'NAME'		=> $this->memberData['members_display_name'] ) );
										
			IPSText::getTextClass('email')->to		= $this->memberData['email'];
			IPSText::getTextClass('email')->addAttachment( $html, IPSText::makeSeoTitle( gmdate( 'Y-m-d', $conversationData['topicData']['mt_start_time'] ) . '-' . $conversationData['topicData']['mt_title'] ) . '.html' , 'text/html' );
			IPSText::getTextClass('email')->sendMail();
			
			$this->returnJsonArray( array( 'status' =>  'sent', 'msg' => sprintf( $this->lang->words['email_has_been_sent'], $this->memberData['email'] ) ) );
 		}
		catch( Exception $error )
		{
			$_msg = $error->getMessage();
			
			if ( $_msg == 'NO_READ_PERMISSION' )
			{
				$this->returnJsonError( $this->lang->words['ajax_no_permission'] );
			}
			else if ( $_msg == 'YOU_ARE_BANNED' )
			{
				$this->returnJsonError( $this->lang->words['ajax_no_permission'] );
			}
			
			print_r( $error );
 		}
	}
	
	/**
	 * Sends the PM
	 *
	 * @return	string		HTML to be returned via ajax
	 * @since	IPB 3.0.0.2008-06-25
	 */
 	protected function _PMSend()
 	{
		/* Check permissions */
 		if ( empty($this->memberData['member_id']) )
		{
			$this->returnJsonError( 'cannotUsePMSystem' );
		}
		
		if ( ! $this->memberData['g_use_pm'] )
		{
			$this->returnJsonError( 'cannotUsePMSystem' );
		}
		
		if ( $this->memberData['members_disable_pm'] )
		{
			$this->returnJsonError( 'cannotUsePMSystem' );
		}
		
 		if( $this->messengerFunctions->checkHasHitMax() )
 		{
 			$this->returnJsonError( 'cannotUsePMSystem' );
 		}
 		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$toMemberID            = intval( $this->request['toMemberID'] );
		$msgContent            = $this->convertHtmlEntities( $this->convertUnicode( $_POST['Post'] ) );
		$msgTitle			   = IPSText::parseCleanValue( $this->convertHtmlEntities( $this->convertUnicode( $_POST['subject'] ) ) );
    	$this->request['Post'] = IPSText::parseCleanValue( $_POST['Post'] );
		
		//-----------------------------------------
		// Load lang file
		//-----------------------------------------
		
		$this->registry->getClass( 'class_localization')->loadLanguageFile( array( "public_error" ), 'core' );
		
		//-----------------------------------------
    	// Language
    	//-----------------------------------------
    	
		$this->registry->class_localization->loadLanguageFile( array( 'public_messaging' ), 'members' );
		
		if ( IPSText::mbstrlen( trim( IPSText::br2nl( $msgContent ) ) ) < 3 )
 		{
			$this->returnJsonError( $this->lang->words['err_no_msg'] );
 		}
    	
 		//-----------------------------------------
 		// Send .. or.. save...
 		//-----------------------------------------
 		
		try
		{
 			$this->messengerFunctions->sendNewPersonalTopic( $toMemberID, $this->memberData['member_id'], array(), $msgTitle, $msgContent );
																												
			return $this->returnJsonArray( array( 'status' => 'sent' ) );
 		}
		catch( Exception $error )
		{
			$msg      = $error->getMessage();
			$toMember = IPSMember::load( $toMemberID, 'core' );
			
			if ( strstr( $msg, 'BBCODE_' ) )
			{
				$msg = str_replace( 'BBCODE_', '', $msg );
				
				return $this->returnJsonArray( array( 'inlineError' => $this->lang->words[ $msg ] ) );
			}
			else if ( isset($this->lang->words[ 'err_' . $msg ]) )
			{
				$_msgString = $this->lang->words[ 'err_' . $msg ];
				$_msgString = str_replace( '#NAMES#'   , implode( ",", $this->messengerFunctions->exceptionData ), $_msgString );
				$_msgString = str_replace( '#TONAME#'  , $toMember['members_display_name']    , $_msgString );
				$_msgString = str_replace( '#FROMNAME#', $this->memberData['members_display_name'], $_msgString );
				$_msgString = str_replace( '#DATE#'    , $this->messengerFunctions->exceptionData[0], $_msgString );
			}
			else
			{
				$_msgString = $this->lang->words['err_UNKNOWN'] . ' ' . $msg;
			}
			
			return $this->returnJsonArray( array( 'inlineError' => $_msgString ) );
		}
	}
	
	/**
	 * Shows the quick PM form
	 *
	 * @return	string		HTML to be returned via ajax
	 * @since	IPB 3.0.0.2008-06-25
	 */
 	protected function _showQuickForm()
 	{
 		if( $this->messengerFunctions->checkHasHitMax() )
 		{
 			$this->returnJsonError( 'cannotUsePMSystem' );
 		}
 		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$toMemberID = intval( $this->request['toMemberID'] );
		
		//-----------------------------------------
		// Load...
		//-----------------------------------------
		
		$toMemberData = IPSMember::load( $toMemberID, 'all' );
		
		if ( ! $toMemberData['member_id'] )
		{
			$this->returnJsonError( 'noSuchToMember' );
		}
		
		//-----------------------------------------
    	// Check viewing permissions, etc
		//-----------------------------------------
		
		if ( ! $this->memberData['g_use_pm'] )
		{
			$this->returnJsonError( 'cannotUsePMSystem' );
		}
		
		if ( $this->memberData['members_disable_pm'] )
		{
			$this->returnJsonError( 'cannotUsePMSystem' );
		}
		
		if ( ! $this->memberData['member_id'] )
		{
			$this->returnJsonError( 'cannotUsePMSystem' );
		}
		
		//-----------------------------------------
		// Stil here?
		//-----------------------------------------
		
		$this->registry->getClass( 'class_localization')->loadLanguageFile( array( 'public_editors' ), 'core' );
		
		return $this->returnJsonArray( array( 'success' => $this->registry->getClass('output')->getTemplate('messaging')->PMQuickForm( $toMemberData ) ) );
	}
	
	/**
	 * Returns PM notification
	 *
	 * @return	string		JSON either error or status
	 * @since	IPB 3.0.0.2008-06-25
	 */
 	protected function _getPMNotification()
 	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$limit = intval( $this->request['limit'] );
		
		//-----------------------------------------
		// INIT the parser
		//-----------------------------------------
		
		IPSText::resetTextClass('bbcode');
        IPSText::getTextClass('bbcode')->allow_update_caches = 0;
        IPSText::getTextClass('bbcode')->parse_bbcode		 = 1;
        IPSText::getTextClass('bbcode')->parse_smilies		 = 1;
        IPSText::getTextClass('bbcode')->parse_html		 	 = 0;
        IPSText::getTextClass('bbcode')->parsing_section	 = 'pms';
		
		//-----------------------------------------
		// Get last PM details
		//-----------------------------------------
		
		$msg = $this->DB->buildAndFetch( array( 'select'	=> 'mt.*',
														'from'	=> array( 'message_topics' => 'mt' ),
														'where'	=> "mt.mt_owner_id=" . $this->memberData['member_id'] . " AND mt.mt_vid_folder='in'",
														'order'	=> 'mt.mt_date DESC',
														'limit'	=> array( intval($limit), 1 ),
														'add_join'	=> array(
																			array( 'select'	=> 'msg.*',
																					'from'	=> array( 'message_text' => 'msg' ),
																					'where'	=> 'msg.msg_id=mt.mt_msg_id',
																					'type'	=> 'left'
																				),
																			array( 'select'	=> 'm.member_id,m.name,m.member_group_id,m.mgroup_others,m.email,m.joined,m.posts, m.last_visit, m.last_activity, m.warn_level, m.warn_lastwarn, m.members_display_name',
																					'from'	=> array( 'members' => 'm' ),
																					'where'	=> 'm.member_id=mt.mt_from_id',
																					'type'	=> 'left'
																				),
																			array( 'select'	=> 'pp.*',
																					'from'	=> array( 'profile_portal' => 'pp' ),
																					'where'	=> 'pp.pp_member_id=mt.mt_from_id',
																					'type'	=> 'left'
																				),
																			array( 'select'	=> 'g.g_id, g.g_title, g.g_icon, g.g_dohtml',
																					'from'	=> array( 'groups' => 'g' ),
																					'where'	=> 'g.g_id=m.member_group_id',
																					'type'	=> 'left'
																				) ) ) );
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( ! $msg['msg_id'] and ! $msg['mt_id'] and ! $msg['id'] )
		{
			$this->returnJsonError( 'noMsg' );
		}
		
		//-----------------------------------------
		// Strip and wrap
		//-----------------------------------------
		
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $msg['member_group_id'];
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $msg['mgroup_others'];
		
		$msg['msg_post'] = IPSText::getTextClass('bbcode')->stripAllTags( strip_tags( str_replace( '<br />', "\n", $msg['msg_post'] ) ) );
		$msg['msg_post'] = wordwrap( $msg['msg_post'], 50, "\n" );
		
		if ( IPSText::mbstrlen( $msg['msg_post'] ) > 300 )
		{
			$msg['msg_post'] = IPSText::truncate( $msg['msg_post'], 350 ) ;
		}
		
		$msg['msg_post'] = nl2br($msg['msg_post']);
		
		//-----------------------------------------
		// Add attach icon
		//-----------------------------------------
		
		if ( $msg['mt_hasattach'] )
		{
			$msg['attach_img'] = '<{ATTACH_ICON}>&nbsp;';
		}
		
		//-----------------------------------------
		// Date
		//-----------------------------------------
		
		$msg['msg_date'] = $this->registry->getClass('class_localization')->getDate( $msg['msg_date'], 'TINY' );
		
		//-----------------------------------------
		// Next / Total links
		//-----------------------------------------
		
		$msg['_cur_num']   = intval($limit) + 1;
		$msg['_msg_count_total'] = intval($this->memberData['msg_count_new']) ? intval($this->memberData['msg_count_new']) : 1;
		
		//-----------------------------------------
		// Return loverly HTML
		//-----------------------------------------
		
		return $this->returnHtml( $this->registry->getClass('output')->getTemplate('messaging')->PMNotificationBox( $msg ) );
	}
	
	/**
	 * Empties a folder
	 *
	 * @return	string		JSON either error or status
	 * @since	IPB 3.0.0.2008-06-25
	 */
 	protected function _emptyFolder()
 	{
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------
		
		$folderID     = IPSText::alphanumericalClean( $this->request['folderID'] );
		$memberID     = intval( $this->request['memberID'] );
		$memberData   = IPSMember::load( $memberID, 'extendedProfile' );
		$status	      = 'ok';
 		$mtids        = array();
 		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! $memberData['member_id'] OR ! $folderID )
		{
			$this->returnJsonError( 'noSuchFolder' );
		}
		
		//-----------------------------------------
		// First off, get dir data
		//-----------------------------------------
		
		$folders = $this->messengerFunctions->explodeFolderData( $memberData['pconversation_filters'] );
		
		//-----------------------------------------
		// "New" folder?
		//-----------------------------------------
		
		if ( $folderID == 'new' )
		{
			/* Just mark them as read */
			$this->DB->update( 'message_topic_user_map', array( 'map_has_unread' => 0 ), 'map_user_id=' . $memberID . " AND map_user_banned=0 AND map_user_active=1" );
		}
		else
		{
			/* Delete all PMs -you- sent regardless of which folder they're in */
			$messages = $this->messengerFunctions->getPersonalTopicsList( $memberID, $folderID, array( 'offsetStart' => 0, 'offsetEnd' => 100000 ) );
		
 			/* Just grab IDs */
			$mtids = array_keys( $messages );
 		
			//-----------------------------------------
			// Got anything?
			//-----------------------------------------
		
			if ( ! count( $mtids ) )
			{
				$this->returnJsonError( 'nothingToRemove' );
			}
		
			//-----------------------------------------
			// Delete the messages
			//-----------------------------------------
		
			try
			{
				$this->messengerFunctions->deleteTopics( $memberData['member_id'], $mtids );
			}
			catch( Exception $error )
			{
				$this->returnJsonError( $error->getMessage() );
			}
		}
		
		//-----------------------------------------
		// Reset total message count
		//-----------------------------------------
		
		$totalMsgs = $this->messengerFunctions->resetMembersTotalTopicCount( $memberData['member_id'] );
 		$this->messengerFunctions->resetMembersNewTopicCount( $memberData['member_id'] );
 		
		//-----------------------------------------
 		// Update directory counts
 		//-----------------------------------------
		
		$newDirs = $this->messengerFunctions->resetMembersFolderCounts( $memberData['member_id'] );
		$folders = $this->messengerFunctions->explodeFolderData( $newDirs );
		
		$this->returnJsonArray( array( 'status' =>  $status, 'totalMsgs' => $totalMsgs, 'newDirs' => $newDirs, 'affectedDirCount' => $folders[ $folderID ]['count'] ) );
	}
	
	/**
	 * Renames a folder
	 *
	 * @return	string		JSON either error or status
	 * @since	IPB 3.0.0.2008-06-25
	 */
 	protected function _renameFolder()
 	{
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------
		
		$folderID     = IPSText::alphanumericalClean( $this->request['folderID'] );
		$memberID     = intval( $this->request['memberID'] );
		$memberData   = IPSMember::load( $memberID, 'extendedProfile' );
		
		// If we run through alpha clean, chars in other langs don't work properly of course
		$name         = IPSText::truncate( $this->convertAndMakeSafe($_POST['name']), 50 );	//IPSText::alphanumericalClean( $this->request['name'], ' ' );
		$status	      = 'ok';
		
		//-----------------------------------------
		// First off, get dir data
		//-----------------------------------------
		
		$folders = $this->messengerFunctions->explodeFolderData( $memberData['pconversation_filters'] );
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! $memberData['member_id'] OR ! $folderID )
		{
			$this->returnJsonError( 'noSuchFolder' );
		}
		
		//-----------------------------------------
		// Now ensure we actually have that folder
		//-----------------------------------------
		
		if ( ! $folders[ $folderID ] )
		{
			$this->returnJsonError( 'noSuchFolder' );
		}
		
		//-----------------------------------------
		// ..and it is not a 'set' folder..
		// 8.25.2008 - We should be able to rename these
		// 10.10.2008 - No, we shouldn't now they are "magic"
		//-----------------------------------------
		
		/* Protected? */
		if ( $folders[ $folderID ]['protected'] )
		{
			$this->returnJsonError( 'cannotDeleteUndeletable' );
		}

		//-----------------------------------------
		// OK, rename it.
		//-----------------------------------------
		
		$folders[ $folderID ]['real'] = $name;
		
		//-----------------------------------------
		// Collapse
		//-----------------------------------------
		
		$newDirs = $this->messengerFunctions->implodeFolderData( $folders );
		
		//-----------------------------------------
		// Save...
		//-----------------------------------------
		
		IPSMember::save( $memberID, array( 'extendedProfile' => array( 'pconversation_filters' => $newDirs ) ) );
		
		//-----------------------------------------
		// Return...
		//-----------------------------------------
		
		$this->returnJsonArray( array( 'status' =>  $status, 'newDirs' => $newDirs, 'name' => $name ) );
	}
	
	/**
	 * Removes a folder
	 *
	 * @return	string		JSON either error or status
	 * @since	IPB 3.0.0.2008-06-25
	 */
 	protected function _removeFolder()
 	{
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------
		
		$folderID     = IPSText::alphanumericalClean( $this->request['folderID'] );
		$memberID     = intval( $this->request['memberID'] );
		$memberData   = IPSMember::load( $memberID, 'extendedProfile' );
		$status	      = 'ok';
		
		IPSDebug::fireBug( 'info', array( 'Received folder id:' . $folderID ) );
		IPSDebug::fireBug( 'info', array( 'Received member id:' . $memberID ) );

		//-----------------------------------------
		// First off, get dir data
		//-----------------------------------------
		
		$folders = $this->messengerFunctions->explodeFolderData( $memberData['pconversation_filters'] );
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! $memberData['member_id'] OR ! $folderID )
		{
			IPSDebug::fireBug( 'error', array( 'Missing member id or folder id' ) );
			
			$this->returnJsonError( 'noSuchFolder' );
		}
		
		//-----------------------------------------
		// Now ensure we actually have that folder
		//-----------------------------------------
		
		if ( ! $folders[ $folderID ] )
		{
			IPSDebug::fireBug( 'error', array( 'Specified folder does not exist' ) );
			
			$this->returnJsonError( 'noSuchFolder' );
		}
		
		//-----------------------------------------
		// Protected folder?
		//-----------------------------------------
		
		/* Protected? */
		if ( $folders[ $folderID ]['protected'] )
		{
			$this->returnJsonError( 'cannotDeleteUndeletable' );
		}
		
		//-----------------------------------------
		// .. and it has no messages
		// Change May 9 2011 - JS alert already warns that
		// all messages in folder will be deleted, so just empty and delete
		// @link http://community.invisionpower.com/tracker/issue-29857-cannot-delete-pm-folder
		//-----------------------------------------
		
		//if ( $folders[ $folderID ]['count'] > 0 )
		//{
		//	$this->returnJsonError( 'cannotDeleteHasMessages' );
		//}

		$messages	= $this->messengerFunctions->getPersonalTopicsList( $memberID, $folderID, array( 'offsetStart' => 0, 'offsetEnd' => 100000 ) );
	
		/* Just grab IDs */
		$mtids		= array_keys( $messages );

		try
		{
			$this->messengerFunctions->deleteTopics( $memberData['member_id'], $mtids );
		}
		catch( Exception $error )
		{
			if( $error->getMessage() != 'NO_IDS_TO_DELETE' )
			{
				$this->returnJsonError( $error->getMessage() );
			}
		}
		
		//-----------------------------------------
		// OK, remove it.
		//-----------------------------------------
		
		unset( $folders[ $folderID ] );
		
		///-----------------------------------------
		// Collapse
		//-----------------------------------------
		
		$newDirs = $this->messengerFunctions->implodeFolderData( $folders );
		
		//-----------------------------------------
		// Save...
		//-----------------------------------------
		
		IPSMember::save( $memberID, array( 'extendedProfile' => array( 'pconversation_filters' => $newDirs ) ) );
		
		//-----------------------------------------
		// Return...
		//-----------------------------------------
		
		$this->returnJsonArray( array( 'status' =>  $status, 'newDirs' => $newDirs ) );
	}
	
 	/**
	 * Adds a folder
	 *
	 * @return	string		JSON either error or status
	 * @since	IPB 3.0.0.2008-06-25
	 */
 	protected function _addFolder()
 	{
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------
		
		$name         = IPSText::truncate( $this->convertAndMakeSafe($_POST['name']), 50 );
		$memberID     = intval( $this->request['memberID'] );
		$memberData   = IPSMember::load( $memberID, 'extendedProfile' );
		$status	      = 'ok';
		$maxID        = 0;

		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! $memberData['member_id'] OR ! $name )
		{
			$this->returnJsonError( 'invalidName' );
		}
		
		//-----------------------------------------
		// Get vdir information
		//-----------------------------------------
		
		$folders = $this->messengerFunctions->explodeFolderData( $memberData['pconversation_filters'] );
		
		foreach( $folders as $id => $folder )
		{
			if ( stristr( $folder['id'], 'dir_' ) )
 			{
 				$maxID = intval( str_replace( 'dir_', "", $folder['id'] ) ) + 1;
 			}
		}
		
		//-----------------------------------------
		// Add a folder
		//-----------------------------------------
		
		$folders[ 'dir_' . $maxID ] = array( 'id'        => 'dir_' . $maxID,
											 'real'      => $name,
											 'protected' => 0,
											 'count'     => 0 );
		
		//-----------------------------------------
		// If we have more than 50 folders, error
		//-----------------------------------------

		if ( count( $folders ) > 50 )
		{
			$this->returnJsonError( 'tooManyFolders' );
		}
										
		//-----------------------------------------
		// Collapse
		//-----------------------------------------
		
		$newDirs = $this->messengerFunctions->implodeFolderData( $folders );
		
		//-----------------------------------------
		// Save...
		//-----------------------------------------
		
		IPSMember::save( $memberID, array( 'extendedProfile' => array( 'pconversation_filters' => $newDirs ) ) );
		
		//-----------------------------------------
		// Return...
		//-----------------------------------------
		
		$this->returnJsonArray( array( 'status' =>  $status, 'newDirs' => $newDirs, 'newID' => 'dir_' . $maxID ) );
	}

}