<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Online list
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @since		12th March 2002
 * @version		$Revision: 10721 $
 *
 */


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
} 

class public_members_messaging_send extends ipsCommand
{
	/**
	 * Page title
	 *
	 * @var		string
	 */
	protected $_title;
	
	/**
	 * Navigation
	 *
	 * @var		array[ 0 => [ title, url ] ]
	 */
	protected $_navigation;
	
	/**
	 * Post Key
	 *
	 * @var		string		Md5 key
	 */
	protected $_postKey;
	
	/**
	 * Flag: Can we upload?
	 *
	 * @var		int		1 or 0
	 */
	protected $_canUpload;
	
	/**
	 * Error string
	 *
	 * @var		string
	 */
	protected $_errorString = '';
	
	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry )
    {
		//-----------------------------------------
    	// Check viewing permissions, etc
		//-----------------------------------------
		
		if ( ! $this->memberData['g_use_pm'] )
		{
			$this->registry->getClass('output')->showError( 'messenger_disabled', 10222, null, null, 403 );
		}
		
		if ( $this->memberData['members_disable_pm'] )
		{
			$this->registry->getClass('output')->showError( 'messenger_disabled', 10223, null, null, 403 );
		}
		
		if ( ! $this->memberData['member_id'] )
		{
			$this->registry->getClass('output')->showError( 'messenger_no_guests', 10224, null, null, 403 );
		}
		
		if( ! IPSLib::moduleIsEnabled( 'messaging', 'members' ) )
		{
			$this->registry->getClass('output')->showError( 'messenger_disabled', 10227, null, null, 404 );
		}		
		
		//-----------------------------------------
		// Reset Classes
		//-----------------------------------------
		
		IPSText::resetTextClass('bbcode');
		
		//-----------------------------------------
		// Load lang file
		//-----------------------------------------
		
		$this->registry->getClass( 'class_localization')->loadLanguageFile( array( "public_error" ), 'core' );
		
		//-----------------------------------------
		// Post Key
		//-----------------------------------------
		
		$this->_postKey = ( $this->request['postKey'] AND $this->request['postKey'] != '' ) ? $this->request['postKey'] : md5(microtime()); 
		
		//-----------------------------------------
		// Can we upload?
		//-----------------------------------------
		
		if ( $this->memberData['g_attach_max'] != -1 and $this->memberData['g_can_msg_attach'] )
		{
			$this->_canUpload   = 1;
		}
		
		$this->lang->words['the_max_length'] = $this->settings['max_post_length'] * 1024;
		
    	//-----------------------------------------
    	// Language
    	//-----------------------------------------
		
		/* Load post lang file for attachments stuff */
		$this->registry->class_localization->loadLanguageFile( array( 'public_post' ), 'forums' );
		$this->registry->class_localization->loadLanguageFile( array( 'public_messaging' ), 'members' );
		
		//-----------------------------------------
		// Grab class
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'members' ) . '/sources/classes/messaging/messengerFunctions.php', 'messengerFunctions', 'members' );
		$this->messengerFunctions = new $classToLoad( $registry );
		
		/* Messenger Totals */
		$totals = $this->messengerFunctions->buildMessageTotals();
		
		//-----------------------------------------
		// Did we preview new topic?
		//-----------------------------------------
		
		if ( $this->request['preview'] )
 		{
 			$this->request['do'] = 'form';
 		}

		//-----------------------------------------
		// Or a reply?
		//-----------------------------------------
		
		if ( $this->request['previewReply'] )
 		{
 			if( $this->request['do'] == 'sendEdit' )
 			{
 				$this->request['do'] = 'editMessage';
 			}
 			else
 			{
 				$this->request['do'] = 'replyForm';
 			}
 		}

    	//-----------------------------------------
    	// What to do?
    	//-----------------------------------------

    	switch( $this->request['do'] )
    	{
			default:
    		case 'form':
				$html = $this->_showNewTopicForm();
			break;
			case 'replyForm':
				$html = $this->_showForm( 'reply' );
			break;
    		case 'send':
    			$html = $this->_sendNewPersonalTopic();
    		break;
			case 'sendReply':
				$html = $this->_sendReply();
			break;
			case 'editMessage':
				$html = $this->_showForm( 'edit' );
			break;
			case 'sendEdit':
				$html = $this->_sendEdit();
			break;
			case 'deleteReply':
				$html = $this->_deleteReply();
			break;
    	}
    	
    	//-----------------------------------------
    	// If we have any HTML to print, do so...
    	//-----------------------------------------
    	
    	$this->registry->output->addContent( $this->registry->getClass('output')->getTemplate('messaging')->messengerTemplate( $html, $this->messengerFunctions->_jumpMenu, $this->messengerFunctions->_dirData, $totals, array(), $this->_errorString ) );
    	$this->registry->output->setTitle( $this->_title  . ' - ' . ipsRegistry::$settings['board_name']);
		
		$this->registry->output->addNavigation( $this->lang->words['messenger__nav'], 'app=members&amp;module=messaging' );
		
		if ( is_array( $this->_navigation ) AND count( $this->_navigation ) )
		{
			foreach( $this->_navigation as $idx => $data )
			{
				$this->registry->output->addNavigation( $data[0], $data[1] );
			}
    	}

        $this->registry->output->sendOutput();
 	}
	
	/**
	 * Deletes a reply
	 *
	 * @return	@e void
	 */
	protected function _deleteReply()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$authKey    = $this->request['authKey'];
		$topicID	= intval( $this->request['topicID'] );
 		$msgID 	    = intval( $this->request['msgID'] );
		
		//-----------------------------------------
		// Auth check
		//-----------------------------------------
		
		if ( $this->request['authKey'] != $this->member->form_hash )
		{
			$this->registry->getClass('output')->showError( 'messenger_bad_key', 2024, null, null, 403 );
		}
		
		//-----------------------------------------
		// Can access this topic?
		//-----------------------------------------
		
		if ( $this->messengerFunctions->canAccessTopic( $this->memberData['member_id'], $topicID ) !== TRUE )
		{
			$this->registry->getClass('output')->showError( 'messenger_no_msgid', 10229, null, null, 403 );
		}
		
		//-----------------------------------------
		// Delete 'em
		//-----------------------------------------
		
		if ( $this->messengerFunctions->deleteMessages( array( $msgID ), $this->memberData['member_id'] ) !== TRUE )
		{
			$this->registry->getClass('output')->showError( 'messenger_no_delete_permission', 10264, null, null, 403 );
		}
		
		//-----------------------------------------
		// Go back to the topic at the correct post ID
		//-----------------------------------------
		
		$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . 'app=members&amp;module=messaging&amp;section=view&amp;do=findMessage&amp;topicID=' . $topicID . '&amp;msgID=' . $this->messengerFunctions->fetchPreviousMsgID( $msgID ) );
	}

	/**
	 * Sends a reply
	 *
	 * @return	mixed	void, or HTML form
	 */
	protected function _sendReply()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$authKey    = $this->request['authKey'];
		$topicID	= intval( $this->request['topicID'] );
 		$msgContent = $_POST['msgContent'];
		
 		//-----------------------------------------
 		// Error checking
 		//-----------------------------------------

 		if ( IPSText::mbstrlen( trim( IPSText::br2nl( $_POST['msgContent'] ) ) ) < 2 )
 		{
 			return $this->_showForm( 'reply', $this->lang->words['err_no_msg'] );
 		}
 		
 		if ( $this->request['authKey'] != $this->member->form_hash )
		{
			$this->registry->getClass('output')->showError( 'messenger_bad_key', 2024, null, null, 403 );
		}
		
 		//-----------------------------------------
 		// Add reply
 		//-----------------------------------------
 		
		try
		{
 			$msgID = $this->messengerFunctions->sendReply( $this->memberData['member_id'], $topicID, $msgContent, array( 'postKey' => $this->_postKey ) );
 		}
		catch( Exception $error )
		{
			$msg = $error->getMessage();
			
			if ( strstr( $msg, 'BBCODE_' ) )
			{
				$msg = str_replace( 'BBCODE_', '', $msg );
				
				return $this->_showForm( $this->lang->words[ $msg ] );
			}
			else if ( isset($this->lang->words[ 'err_' . $msg ]) )
			{
				$_msgString = $this->lang->words[ 'err_' . $msg ];
				$_msgString = str_replace( '#NAMES#'   , implode( ",", $this->messengerFunctions->exceptionData ), $_msgString );
				$_msgString = str_replace( '#LIMIT#'   , $this->memberData['g_max_mass_pm'], $_msgString );
				$_msgString = str_replace( '#TONAME#'  , $this->messengerFunctions->exceptionData[0], $_msgString );
				$_msgString = str_replace( '#FROMNAME#', $this->memberData['members_display_name'], $_msgString );
			}
			else
			{
				$_msgString = $this->lang->words['err_UNKNOWN'] . ' ' . $msg;
			}

			return $this->_showForm( 'reply', $_msgString );
		}
		
		//-----------------------------------------
		// Which page are we on, then?...
		//-----------------------------------------
		
		$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . 'app=members&amp;module=messaging&amp;section=view&amp;do=findMessage&amp;topicID=' . $topicID . '&amp;msgID=' . $msgID );
	}
 	
	/**
	 * Sends a reply
	 *
	 * @return	mixed	void, or HTML form
	 */
	protected function _sendEdit()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$authKey    = $this->request['authKey'];
		$topicID	= intval( $this->request['topicID'] );
		$msgID   	= intval( $this->request['msgID'] );
 		$msgContent = $_POST['msgContent'];
		
 		//-----------------------------------------
 		// Error checking
 		//-----------------------------------------
 		
 		if ( IPSText::mbstrlen( trim( IPSText::br2nl( $_POST['msgContent'] ) ) ) < 2 )
 		{
 			return $this->_showForm( 'edit', $this->lang->words['err_no_msg'] );
 		}
 		
 		if ( $this->request['authKey'] != $this->member->form_hash )
		{
			$this->registry->getClass('output')->showError( 'messenger_bad_key', 2024, null, null, 403 );
		}
		
 		//-----------------------------------------
 		// Add reply
 		//-----------------------------------------
 		
		try
		{
 			$this->messengerFunctions->sendEdit( $this->memberData['member_id'], $topicID, $msgID, $msgContent );
 		}
		catch( Exception $error )
		{
			$msg = $error->getMessage();
			
			if ( strstr( $msg, 'BBCODE_' ) )
			{
				$msg = str_replace( 'BBCODE_', '', $msg );
				
				return $this->_showForm( $this->lang->words[ $msg ] );
			}
			else if ( isset($this->lang->words[ 'err_' . $msg ]) )
			{
				$_msgString = $this->lang->words[ 'err_' . $msg ];
				$_msgString = str_replace( '#NAMES#'   , implode( ",", $this->messengerFunctions->exceptionData ), $_msgString );
				$_msgString = str_replace( '#LIMIT#'   , $this->memberData['g_max_mass_pm'], $_msgString );
				$_msgString = str_replace( '#TONAME#'  , $this->messengerFunctions->exceptionData[0], $_msgString );
				$_msgString = str_replace( '#FROMNAME#', $this->memberData['members_display_name'], $_msgString );
			}
			else
			{
				$_msgString = $this->lang->words['err_UNKNOWN'] . ' ' . $msg;
			}
		
			return $this->_showForm( 'reply', $_msgString );
		}
		
		//-----------------------------------------
		// Which page are we on, then?...
		//-----------------------------------------
		
		$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . 'app=members&amp;module=messaging&amp;section=view&amp;do=findMessage&amp;topicID=' . $topicID . '&amp;msgID=' . $msgID );
	}
	
 	/**
	 * Sends the PM
	 *
	 * @return	@e void, or HTML form
	 */
	protected function _sendNewPersonalTopic()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		if( $this->messengerFunctions->checkHasHitMax() )
		{
			$this->registry->getClass('output')->showError( 'maxperday_hit', 10272 );
		}
		if ( !$this->messengerFunctions->withinPMQuota( $this->memberData ) )
		{
			$this->registry->getClass('output')->showError( 'cannot_send_new_limit', 10274, FALSE, '', 40 );
		}
		
		$msgTitle     = IPSText::getTextClass('bbcode')->stripBadWords( trim( IPSText::parseCleanValue( $_POST['msg_title'] ) ) );
		$authKey      = $this->request['auth_key'];
		$sendToName   = $this->request['entered_name'];
		$sendToID	  = intval( $this->request['toMemberID'] );
		$sendType     = trim( $this->request['sendType'] );
		$_inviteUsers = trim( $this->request['inviteUsers'] );
 		$msgContent   = $_POST['Post'];
		$topicID      = $this->request['topicID'];
		$inviteUsers  = array();
		$draft        = ( $this->request['save'] ) ? TRUE : FALSE;
		
 		//-----------------------------------------
 		// Error checking
 		//-----------------------------------------
 		
 		if ( IPSText::mbstrlen( trim( $msgTitle ) ) < 2 )
 		{
 			return $this->_showNewTopicForm( $this->lang->words['err_no_title'] );
 		}
 		
 		if ( IPSText::mbstrlen( trim( IPSText::br2nl( $msgContent ) ) ) < 3 )
 		{
 			return $this->_showNewTopicForm( $this->lang->words['err_no_msg'] );
 		}
 		
 		if ( $this->request['auth_key'] != $this->member->form_hash )
		{
			$this->registry->getClass('output')->showError( 'messenger_bad_key', 2024 );
		}
 		
 		if ( !$sendToID AND !$sendToName )
 		{
 			return $this->_showNewTopicForm( $this->lang->words['err_no_chosen_member'] );
 		}

		//-----------------------------------------
		// Invite Users
		//-----------------------------------------
		
		if ( $this->memberData['g_max_mass_pm'] AND $_inviteUsers )
		{
			$_tmp = array();
			
			foreach( explode( ',', $_inviteUsers ) as $name )
			{
				$name = trim( $name );
				
				if ( $name )
				{
					$inviteUsers[] = $name;
				}
			}
		}

		//-----------------------------------------
		// Grab member ID
		//-----------------------------------------
		
		$toMember = ( $sendToID ) ? IPSMember::load( $sendToID, 'core' ) :  IPSMember::load( $sendToName, 'core', 'displayname' );
 		
		if ( ! $toMember['member_id'] )
		{
			return $this->_showNewTopicForm( $this->lang->words['err_no_chosen_member'] );
		}
		
 		//-----------------------------------------
 		// Send .. or.. save...
 		//-----------------------------------------

		try
		{
 			$this->messengerFunctions->sendNewPersonalTopic( $toMember['member_id'], $this->memberData['member_id'], $inviteUsers, $msgTitle, $msgContent, array( 'isDraft'  => $draft,
																																							  'topicID'  => $topicID,
																																							  'sendMode' => $sendType,
																																			        		  'postKey'  => $this->_postKey ) );
 		}
		catch( Exception $error )
		{
			$msg = $error->getMessage();

			if ( strstr( $msg, 'BBCODE_' ) )
			{
				$msg = str_replace( 'BBCODE_', '', $msg );
				
				return $this->_showNewTopicForm( $this->lang->words[ $msg ] );
			}
			else if ( isset($this->lang->words[ 'err_' . $msg ]) )
			{
				$_msgString = $this->lang->words[ 'err_' . $msg ];
				$_msgString = str_replace( '#NAMES#'   , implode( ",", $this->messengerFunctions->exceptionData ), $_msgString );
				$_msgString = str_replace( '#LIMIT#'   , $this->memberData['g_max_mass_pm'], $_msgString );
				$_msgString = str_replace( '#TONAME#'  , $toMember['members_display_name']    , $_msgString );
				$_msgString = str_replace( '#FROMNAME#', $this->memberData['members_display_name'], $_msgString );
				$_msgString = str_replace( '#DATE#'    , $this->messengerFunctions->exceptionData[0], $_msgString );
			}
			else
			{
				$_msgString = $this->lang->words['err_UNKNOWN'] . ' ' . $msg;
			}

			return $this->_showNewTopicForm( $_msgString );
		}

		//-----------------------------------------
		// Swap and serve...
		//-----------------------------------------
		
		if ( $draft !== TRUE )
		{
			$text = str_replace( "<#FROM_MEMBER#>"   , $this->memberData['members_display_name'] , $this->lang->words['sent_text'] );
			$text = str_replace( "<#MESSAGE_TITLE#>" , $msgTitle, $text );
		}
		else
		{
			$text = $this->lang->words['saved_as_a_draft'];
		}
		
		$this->registry->getClass('output')->redirectScreen( $text , $this->settings['base_url'] . 'app=members&amp;module=messaging&amp;section=view&amp;do=inbox' );
	}
	
	/**
	 * Show PM form
	 *
	 * @param	string 		Type of form (edit / reply)
	 * @param	string 		Error message
	 * @return	string		returns HTML
	 */
	protected function _showForm( $type, $errors='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$topicID		  = intval( $this->request['topicID'] );
		$msgID			  = intval( $this->request['msgID'] );
		$preview          = isset( $this->request['previewReply'] ) ? 1 : 0;
		$displayData	  = array( 'errors'      => $errors ? array( $errors ) : array(),
								   'topicID'	 => $topicID,
								   'msgID'       => $msgID,
								   'type'        => $type,
								   'message'     => '' );
		
 		$_POST['Post-NS']    = isset($_POST['msgContent']) ? $_POST['msgContent'] : '';
 		$_POST['msgContent'] = IPSText::raw2form( isset($_POST['msgContent']) ? $_POST['msgContent'] : '' );
 		
		//-----------------------------------------
		// Fetch topic Data
		//-----------------------------------------
		
		$topicData = $this->messengerFunctions->fetchTopicData( $topicID, FALSE );
		
		//-----------------------------------------
		// Fetch topic participants
		//-----------------------------------------
		
		$topicParticipants = $this->messengerFunctions->fetchTopicParticipants( $topicID, FALSE );
		
		//-----------------------------------------
		// Are we allowed in here?
		//-----------------------------------------
		
		if ( $this->messengerFunctions->canAccessTopic( $this->memberData['member_id'], $topicData, $topicParticipants ) !== TRUE )
		{
			$this->registry->getClass('output')->showError( 'messenger_no_msgid', 10229, null, null, 403 );
		}
		
		//-----------------------------------------
		// Load editor
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$_editor = new $classToLoad();
		$_editor->setAllowHtml( false );
		
		//-----------------------------------------
		// Got a message ID?
		//-----------------------------------------
		
		if ( $msgID AND ! $_POST['Post-NS'] )
		{
			$msgData = $this->messengerFunctions->fetchMessageData( $topicID, $msgID, TRUE );
			
			if ( $msgData['msg_post'] )
			{
				if ( $type == 'reply' )
				{
					/* We're quoting a post... */
					if ( $_editor->getRteEnabled() )
					{
						$displayData['message'] = "[quote name='".IPSText::getTextClass( 'bbcode' )->makeQuoteSafe($msgData['members_display_name'])."' timestamp='" . $msgData['msg_date'] . "']<br />{$msgData['msg_post']}".'[/quote]'."<br />";
					}
					else
					{
						IPSText::getTextClass('bbcode')->parse_html		= 0;
						IPSText::getTextClass('bbcode')->parse_nl2br	= 1;
						IPSText::getTextClass('bbcode')->parse_smilies	= 1;
						IPSText::getTextClass('bbcode')->parse_bbcode	= 1;
						IPSText::getTextClass('bbcode')->parsing_section = 'pms';

						$msgData['msg_post'] = IPSText::getTextClass('bbcode')->preEditParse( $msgData['msg_post'] );
						
						$displayData['message'] = "[quote name='".IPSText::getTextClass( 'bbcode' )->makeQuoteSafe($msgData['members_display_name'])."' timestamp='" . $msgData['msg_date'] . "']\n{$msgData['msg_post']}".'[/quote]'."\n";
					}
				}
				else if ( $type == 'edit' )
				{
					/* Reset post key */
					$this->_postKey = $msgData['msg_post_key'];
						
					$displayData['message'] = $msgData['msg_post'];
				}
			}
		}
	
    	//-----------------------------------------
    	// Previewing...
    	//-----------------------------------------

    	if ( $preview )
    	{
			/* Grab language for attachment previews */
			$this->registry->getClass( 'class_localization')->loadLanguageFile( array( "public_topic" ), 'forums' );
			
    		IPSText::getTextClass('bbcode')->parse_html					= 0;
			IPSText::getTextClass('bbcode')->parse_nl2br				= 1;
			IPSText::getTextClass('bbcode')->parse_smilies				= 1;
			IPSText::getTextClass('bbcode')->parse_bbcode				= 1;
			IPSText::getTextClass('bbcode')->parsing_section			= 'pms';
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $this->memberData['member_group_id'];
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $this->memberData['mgroup_others'];
			
			$this->settings['max_emos'] = 0;
			
			$msg = $_editor->process( $_POST['Post-NS'] );
			$msg = IPSText::getTextClass('bbcode')->preDisplayParse( IPSText::getTextClass('bbcode')->preDbParse( $msg ) );
			
			if ( ! is_object( $this->class_attach ) )
			{
				//-----------------------------------------
				// Grab render attach class
				//-----------------------------------------

				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
				$this->class_attach = new $classToLoad( $this->registry );
			}

			//-----------------------------------------
			// Continue...
			//-----------------------------------------

			$this->class_attach->type  = 'msg';
			$this->class_attach->attach_post_key = $this->_postKey;
			$this->class_attach->init();

			$attachData = $this->class_attach->renderAttachments( array( 0 => $msg ) );			
			
			$displayData['preview'] = $attachData[0]['html'] . $attachData[0]['attachmentHtml'];
    	}
    	
		//-----------------------------------------
		// Errors
		//-----------------------------------------

		if( is_array($errors) AND count($errors) )
		{
			$preview	= 1;
		}
		
    	if ( IPSText::getTextClass('bbcode')->error != "" )
    	{
			if ( IPSText::getTextClass('bbcode')->error )
			{
	    		$displayData['errors'][] = $this->lang->words[ IPSText::getTextClass('bbcode')->error ];
	    	}
    		
    		$preview = 1;
    	}
 		
 		//-----------------------------------------
 		// Are we quoting an old message?
 		//-----------------------------------------
 		
 		if ( $preview )
 		{ 
 			$displayData['message'] = ( $displayData['message'] ) ? $displayData['message'] : $_POST['Post-NS'];
 		}	
 		
 		$_editor->setContent( $displayData['message'] );
 		$displayData['editor'] = $_editor->show( 'msgContent', array( 'height' => 350 ) );
		
		//-----------------------------------------
		// More Data...
		//-----------------------------------------
		
		$displayData['uploadData'] = ( $this->_canUpload ) ? array( 'canUpload' => 1 ) : array( 'canUpload' => 0 );
		$displayData['postKey']    = $this->_postKey;
		
		//-----------------------------------------
		// Load attachments so we get some stats
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
		$class_attach = new $classToLoad( $this->registry );
		$class_attach->type = 'msg';
		$class_attach->init();
		$class_attach->getUploadFormSettings();
		
		$displayData['uploadData']['attach_stats']	= $class_attach->attach_stats;
		
 		//-----------------------------------------
 		// Build up the HTML for the send form
 		//-----------------------------------------
		
		$_folder             = $topicParticipants[ $this->memberData['member_id'] ]['map_folder_id'];
		$this->messengerFunctions->_currentFolderID = $_folder;
		$this->_navigation[] = array( $this->messengerFunctions->_dirData[ $_folder ]['real'], "app=members&amp;module=messaging&amp;section=view&amp;do=showFolder&amp;folderID=".$_folder."&amp;sort=".$this->request['sort'] );
		$this->_navigation[] = array( $topicData['mt_title'], "app=members&amp;module=messaging&amp;section=view&amp;do=showConversation&amp;topicID=".$topicData['mt_id'] );
		
		if ( $type == 'reply' )
		{
			$this->_title        = $this->lang->words['sendMsgTitle'];
			$this->_navigation[] = array( $this->lang->words['sendMsgTitle'], '' );
		}
		else
		{
			$this->_title        = $this->lang->words['edit_msg_title'];
			$this->_navigation[] = array( $this->lang->words['edit_msg_title'], '' );
		}
		
 		return $this->registry->getClass('output')->getTemplate('messaging')->sendReplyForm( $displayData );
 	}

	/**
	 * Show PM form
	 *
	 * @param	string 		Error message
	 * @return	string		returns HTML
	 */
	protected function _showNewTopicForm( $errors='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		/* Check max per day */
		if( $this->messengerFunctions->checkHasHitMax() )
		{
			$this->registry->getClass('output')->showError( 'maxperday_hit', 10271 );
		}
		
		/* Check PM flood */
		if( $this->messengerFunctions->floodControlCheck() !== TRUE )
		{
			$this->registry->getClass('output')->showError( sprintf( $this->lang->words['pm_flood_stop'], $this->messengerFunctions->exceptionData[0] ), 010271 );
		}
		
		/* Check limit*/
		if ( !$this->messengerFunctions->withinPMQuota( $this->memberData ) )
		{
			$this->registry->getClass('output')->showError( 'cannot_send_new_limit', 10273, FALSE, '', 40 );
		}

		$_msg_id          = 0;
		$formMemberID     = intval($this->request['fromMemberID']); # WAS MID
 		$topicID          = intval($this->request['topicID']);
		$preview		  = $this->request['preview'];
		$inviteUsers      = array();
		$displayData	  = array( 'errors'        => $errors ? array( $errors ) : array(),
								   'topicID'       => $topicID,
								   'preview'       => '',
								   'name'          => '',
								   'title'         => '',
								   'message'       => '' );
		
 		$_POST['Post-NS']	= isset($_POST['Post']) ? $_POST['Post'] : '';
 		$_POST['Post']		= IPSText::raw2form( isset($_POST['Post']) ? $_POST['Post'] : '' );

		//-----------------------------------------
		// Load editor
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$_editor = new $classToLoad();
		$_editor->setAllowHtml( false );

    	//-----------------------------------------
    	// Preview post?
    	//-----------------------------------------
    	
    	if ( $preview )
    	{
			/* Grab language for attachment previews */
			$this->registry->getClass( 'class_localization')->loadLanguageFile( array( "public_topic" ), 'forums' );
	
    		IPSText::getTextClass('bbcode')->parse_html					= 0;
			IPSText::getTextClass('bbcode')->parse_nl2br				= 1;
			IPSText::getTextClass('bbcode')->parse_smilies				= 1;
			IPSText::getTextClass('bbcode')->parse_bbcode				= 1;
			IPSText::getTextClass('bbcode')->parsing_section			= 'pms';
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $this->memberData['member_group_id'];
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $this->memberData['mgroup_others'];
			
			$this->settings['max_emos'] = 0;
			
			if( $this->request['_from'] == 'quickPM' )
			{
				$old_msg	= $_POST['Post'];
			}
			else
			{
				$old_msg	= $_editor->process( $_POST['Post'] );
			}
			
			$old_msg = IPSText::getTextClass('bbcode')->preDisplayParse( IPSText::getTextClass('bbcode')->preDbParse( $old_msg ) );
			
			if ( ! is_object( $this->class_attach ) )
			{
				//-----------------------------------------
				// Grab render attach class
				//-----------------------------------------

				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
				$this->class_attach = new $classToLoad( $this->registry );
			}

			//-----------------------------------------
			// Continue...
			//-----------------------------------------

			$this->class_attach->type  = 'msg';
			$this->class_attach->attach_post_key = $this->_postKey;
			$this->class_attach->init();

			$attachData = $this->class_attach->renderAttachments( array( 0 => $old_msg ) );			
			
			$displayData['preview'] = $attachData[0]['html'] . $attachData[0]['attachmentHtml'];
    	}
    	
		//-----------------------------------------
		// Errors
		//-----------------------------------------

    	if ( $errors OR ( IPSText::getTextClass('bbcode')->error != "" ) )
    	{
			if ( IPSText::getTextClass('bbcode')->error )
			{
	    		$displayData['errors'][] = $this->lang->words[ IPSText::getTextClass('bbcode')->error ];
	    	}
    		
    		$preview = 1;
    	}
    	
    	//-----------------------------------------
 		// Did we come from a button with a user ID?
 		//-----------------------------------------
 		
		if ( $formMemberID )
		{ 
			$name = IPSMember::load( $formMemberID, 'core' );
			
			if ( $name['member_id'] )
			{
				$displayData['name'] = $name['members_display_name'];
			}
		}
		else
		{
			$displayData['name'] = $this->request['entered_name'] ? $this->request['entered_name'] : '';
		}
 		
 		//-----------------------------------------
 		// Are we quoting an old message?
 		//-----------------------------------------
 		
 		if ( $preview )
 		{ 
 			$displayData['message'] = $_POST['Post-NS'];

			if( $this->request['_from'] == 'quickPM' )
			{
				$displayData['message'] = IPSText::parseCleanValue( $displayData['message'], true );
			}
			
 			$displayData['title']   = str_replace( "'", "&#39;", str_replace( '"', '&#34;', IPSText::stripslashes($_POST['msg_title']) ) );
 		}
 		
		//-----------------------------------------
		// Sending as draft...
		//-----------------------------------------

 		else if ( $topicID )
 		{
			$draftTopic = $this->messengerFunctions->fetchTopicDataWithMessage( $topicID, TRUE );
 			
 			/* Permission to view this? */
 			if ( $draftTopic['mt_starter_id'] == $this->memberData['member_id'] )
 			{
	 			if ( $draftTopic['mt_to_member_id'] )
	 			{
		 			$displayData['name'] = $draftTopic['from_name'];
	 			}
	 			
	 			if ( $draftTopic['mt_title'] )
	 			{
					$_member = IPSMember::load( $draftTopic['mt_to_member_id'], 'core' );
		
	 				$displayData['name']    = $_member['members_display_name'];
					$displayData['title']   = $draftTopic['mt_title'];
					$_msg_id                = $draftTopic['msg_id'];
					$this->_postKey         = $draftTopic['msg_post_key'];
					
					IPSText::getTextClass('bbcode')->parse_html		= 0;
					IPSText::getTextClass('bbcode')->parse_nl2br	= 1;
					IPSText::getTextClass('bbcode')->parse_smilies	= 1;
					IPSText::getTextClass('bbcode')->parse_bbcode	= 1;
					IPSText::getTextClass('bbcode')->parsing_section = 'pms';
					
					if ( $_editor->getRteEnabled() )
					{
						$displayData['message'] = IPSText::getTextClass('bbcode')->convertForRTE( $draftTopic['msg_post'] );	
					}
					else
					{
						$displayData['message'] = IPSText::getTextClass('bbcode')->preEditParse( $draftTopic['msg_post'] );
					} 
	 			}
	 		}
 		}

		//-----------------------------------------
		// CC Boxes
		//-----------------------------------------

		if ( $this->memberData['g_max_mass_pm'] > 0 )
		{
			if ( $_POST['inviteUsers'] )
			{
				$displayData['inviteUsers'] = IPSText::parseCleanValue( $_POST['inviteUsers'] );
			}
			else if ( $draftTopic['mt_invited_members'] )
			{
				$_inviteUsers  = $this->messengerFunctions->getInvitedUsers( $draftTopic['mt_invited_members'] );
				$__inviteUsers = IPSMember::load( $_inviteUsers, 'core' );
				
				if ( is_array( $__inviteUsers ) )
				{
					$_tmp = array();
					
					foreach( $__inviteUsers as $id => $data )
					{
						$_tmp[] = $data['members_display_name'];
					}
					
					if ( is_array( $_tmp ) )
					{
						$displayData['inviteUsers'] = implode( ", ", $_tmp );
					}
				}
			}
		}

 		$_editor->setContent( $displayData['message'] );
 		
 		$displayData['editor'] = $_editor->show( 'Post', array( 'height' => 350 ) );
		
		//-----------------------------------------
		// More Data...
		//-----------------------------------------
		
		$displayData['uploadData'] = ( $this->_canUpload ) ? array( 'canUpload' => 1 ) : array( 'canUpload' => 0 );
		$displayData['postKey']    = $this->_postKey;
		
		//-----------------------------------------
		// Load attachments so we get some stats
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
		$class_attach = new $classToLoad( $this->registry );
		$class_attach->type = 'msg';
		$class_attach->init();
		$class_attach->getUploadFormSettings();
		
		$displayData['uploadData']['attach_stats']	= $class_attach->attach_stats;
		
 		//-----------------------------------------
 		// Build up the HTML for the send form
 		//-----------------------------------------
		
		$this->_title        = $this->lang->words['sendMsgTitle'];
		$this->_navigation[] = array( $this->lang->words['sendMsgTitle'], '' );

 		return $this->registry->getClass('output')->getTemplate('messaging')->sendNewPersonalTopicForm( $displayData );
 	}
 	

        
}