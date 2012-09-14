<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Core user control panel plugin
 * Last Updated: $Date: 2012-05-21 16:37:50 -0400 (Mon, 21 May 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		20th February 2002
 * @version		$Rev: 10777 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class usercpForms_core extends public_core_usercp_manualResolver implements interface_usercp
{
	/**
	 * Tab name
	 * This can be left blank and the application title will
	 * be used
	 *
	 * @var		string
	 */
	public $tab_name = "Settings";

	/**
	 * Default area code
	 *
	 * @var		string
	 */
	public $defaultAreaCode = 'profileinfo';
	
	/**
	 * OK Message
	 * This is an optional message to return back to the framework
	 * to replace the standard 'Settings saved' message
	 *
	 * @var		string
	 */
	public $ok_message = '';

	/**
	 * Hide 'save' button and form elements
	 * Useful if you have custom output that doesn't
	 * need to use it
	 *
	 * @var		bool
	 */
	public $hide_form_and_save_button = false;

	/**
	 * If you wish to allow uploads, set a value for this
	 *
	 * @var		integer
	 */
	public $uploadFormMax = 0;

	/**
	 * Flag to indicate that the user is a facebook logged in user doozer
	 *
	 * @var		boolean
	 */
	protected $_isFBUser = false;
	
	/**
	 * Flag to indicate compatibility
	 * 
	 * @var		int
	 */
 	public $version	= 32;

	/**
	 * Initiate this module
	 *
	 * @return	@e void
	 */
	public function init( )
	{
		$this->tab_name	= ipsRegistry::getClass('class_localization')->words['tab__core'];

		/* Facebook? */
		if ( IPSLib::fbc_enabled() === TRUE AND $this->memberData['fb_uid'] )
		{
			$this->_isFBUser = true;
		}
	}

	/**
	 * Return links for this tab
	 * You may return an empty array or FALSE to not have
	 * any links show in the tab.
	 *
	 * The links must have 'area=xxxxx'. The rest of the URL
	 * is added automatically.
	 * 'area' can only be a-z A-Z 0-9 - _
	 *
	 * @author	Matt Mecham
	 * @return	array 		Links
	 */
	public function getLinks()
	{
		ipsRegistry::instance()->getClass('class_localization')->loadLanguageFile( array( 'public_usercp' ), 'core' );

		$array = array();

		$array[] = array( 'url'    => 'area=profileinfo',
						  'title'  => ipsRegistry::instance()->getClass('class_localization')->words['change_settings'],
						  'active' => $this->request['tab'] == 'core' && $this->request['area'] == 'profileinfo' ? 1 : 0,
						  'area'   => 'profileinfo'
						   );
		
		if ( $this->memberData['gbw_allow_customization'] AND ! $this->memberData['bw_disable_customization'] )
		{
			$array[] = array( 'url'    => 'area=customize',
							  'title'  => ipsRegistry::instance()->getClass('class_localization')->words['m_customize'],
							  'active' => $this->request['tab'] == 'core' && $this->request['area'] == 'customize' ? 1 : 0,
							  'area'   => 'customize'
							 );
		}
		
		$array[] = array( 'url'    => 'area=email',
						  'title'  => ipsRegistry::instance()->getClass('class_localization')->words['m_email_pass_change'],
						  'active' => $this->request['tab'] == 'core' && $this->request['area'] == 'email' ? 1 : 0,
						  'area'   => 'email'
						);
		
		if ( $this->settings['auth_allow_dnames'] == 1 AND $this->memberData['g_dname_changes'] != 0 )
		{
			$array[] = array( 'url'    => 'area=displayname',
							  'title'  => ipsRegistry::instance()->getClass('class_localization')->words['ucp_change_name'],
							  'active' => $this->request['tab'] == 'core' && $this->request['area'] == 'displayname' ? 1 : 0,
							  'area'   => 'displayname'
							);
		}
		
		$sig_restrictions	= explode( ':', $this->memberData['g_signature_limits'] );
		
		if ( ! $sig_restrictions[0] OR ( $sig_restrictions[0] AND $this->memberData['g_sig_unit'] ) )
		{
			$array[] = array( 'url'    => 'area=signature',
							  'title'  => ipsRegistry::instance()->getClass('class_localization')->words['m_sig_info'],
							  'active' => $this->request['tab'] == 'core' && $this->request['area'] == 'signature' ? 1 : 0,
							  'area'   => 'signature'
							  );
		}
		
		$array[] = array( 'url'    => 'area=ignoredusers',
						  'title'  => ipsRegistry::instance()->getClass('class_localization')->words['m_ignore_users'],
						  'active' => $this->request['tab'] == 'core' && $this->request['area'] == 'ignoredusers' ? 1 : 0,
						  'area'   => 'ignoredusers'
						 );
						
		if ( IPSLib::fbc_enabled() === TRUE )
		{
			$array[] = array( 'url'    => 'area=facebook',
							  'title'  => ipsRegistry::instance()->getClass('class_localization')->words['m_facebook'],
							  'active' => $this->request['tab'] == 'core' && $this->request['area'] == 'facebook' ? 1 : 0,
							  'area'   => 'facebook'
							 );
		}
		
		if ( IPSLib::twitter_enabled() === TRUE )
		{
			$array[] = array( 'url'    => 'area=twitter',
							  'title'  => ipsRegistry::instance()->getClass('class_localization')->words['m_twitter'],
							  'active' => $this->request['tab'] == 'core' && $this->request['area'] == 'twitter' ? 1 : 0,
							  'area'   => 'twitter'
							 );
		}
		
		if ( $this->memberData['g_attach_max'] != -1 )
		{
			$array[] = array(
							'url'    => 'area=attachments',
							'title'  => ipsRegistry::instance()->getClass('class_localization')->words['m_attach'],
							'active' => $this->request['tab'] == 'core' && $this->request['area'] == 'attachments' ? 1 : 0,
							'area'   => 'attachments'
							);
		}

		$array[] = array( 'url'    => 'area=notifications',
						  'title'  => ipsRegistry::instance()->getClass('class_localization')->words['m_notifications'],
						  'active' => $this->request['tab'] == 'core' && $this->request['area'] == 'notifications' ? 1 : 0,
						  'area'   => 'notifications'
						);
						
		$array[] = array( 'url'    => 'area=notificationlog',
						  'title'  => ipsRegistry::instance()->getClass('class_localization')->words['m_arch_notify'],
						  'active' => ( in_array( $this->request['area'], array( 'notificationlog', 'viewNotification', 'removeNotifications' ) ) ) ? 1 : 0,
						  'area'   => 'notificationlog'
						);

		return $array;
	}


	/**
	 * Run custom event
	 *
	 * If you pass a 'do' in the URL / post form that is not either:
	 * save / save_form or show / show_form then this function is loaded
	 * instead. You can return a HTML chunk to be used in the UserCP (the
	 * tabs and footer are auto loaded) or redirect to a link.
	 *
	 * If you are returning HTML, you can use $this->hide_form_and_save_button = 1;
	 * to remove the form and save button that is automatically placed there.
	 *
	 * @author	Matt Mecham
	 * @param	string		Current area
	 * @return	mixed		html or void
	 */
	public function runCustomEvent( $currentArea )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$html = '';

		//-----------------------------------------
		// What to do?
		//-----------------------------------------

		switch( $currentArea )
		{
			case 'removeIgnoredUser':
				return $this->customEvent_removeIgnoredUser();
			break;
			case 'toggleIgnoredUser':
				return $this->customEvent_toggleIgnoredUser();
			break;
			case 'facebookSync':
				$html = $this->customEvent_facebookSync();
			break;
			case 'facebookRemove':
				$html = $this->customEvent_facebookRemove();
			break;
			case 'twitterRemove':
				$html = $this->customEvent_twitterRemove();
			break;
			case 'facebookLink':
				$html = $this->customEvent_facebookLink();
			break;
			case 'updateAttachments':
				return $this->customEvent_updateAttachments();
			break;
			
			case 'viewNotification':
				$html	= $this->customEvent_viewNotification();
			break;
			
			case 'markNotification':
				return $this->customEvent_markNotification();
			break;
			
			case 'removeNotifications':
				return $this->customEvent_removeNotifications();
			break;
		}

		//-----------------------------------------
		// Turn off save button
		//-----------------------------------------

		$this->hide_form_and_save_button = 1;

		//-----------------------------------------
		// Return
		//-----------------------------------------

		return $html;
	}

	/**
	 * Delete attachments
	 *
	 * @author	Matt Mecham
	 * @return	string		Processed HTML
	 */
	public function customEvent_updateAttachments()
	{
		//-----------------------------------------
 		// Get the ID's to delete
 		//-----------------------------------------

 		$finalIDs = array();

		//-----------------------------------------
		// Grab post IDs
		//-----------------------------------------

		if ( is_array( $_POST['attach'] ) and count( $_POST['attach'] ) )
		{
			foreach( $_POST['attach'] as $id => $value )
			{
				$finalIDs[ $id ] = intval( $id );
			}
		}

 		if ( count($finalIDs) > 0 )
 		{
			$this->DB->build( array(	'select'	=> 'a.*',
											'from'		=> array( 'attachments' => 'a' ),
											'where'		=> "a.attach_id IN (" . implode( ",", $finalIDs ) .") AND a.attach_rel_module IN( 'post', 'msg' ) AND attach_member_id=" . $this->memberData['member_id'],
											'add_join'	=> array(
																array( 'select'	=> 'p.topic_id, p.pid',
																		'from'	=> array( 'posts' => 'p' ),
																		'where'	=> "p.pid=a.attach_rel_id AND a.attach_rel_module='post'",
																		'type'	=> 'left'
																	),
																array( 'select'	=> 'mt.msg_id, mt.msg_topic_id',
																		'from'	=> array( 'message_posts' => 'mt' ),
																		'where'	=> "mt.msg_id=a.attach_rel_id AND a.attach_rel_module='msg'",
																		'type'	=> 'left'
																	),
																)
								)		);

			$o = $this->DB->execute();

			while ( $killmeh = $this->DB->fetch( $o ) )
			{
				if ( $killmeh['attach_location'] )
				{
					@unlink( $this->settings['upload_dir']."/".$killmeh['attach_location'] );
				}
				if ( $killmeh['attach_thumb_location'] )
				{
					@unlink( $this->settings['upload_dir']."/".$killmeh['attach_thumb_location'] );
				}

				if ( $killmeh['topic_id'] )
				{
					$this->DB->update( 'topics', 'topic_hasattach=topic_hasattach-1', 'tid='.$killmeh['topic_id'], true, true );
				}
				else if( $killmeh['msg_id'] )
				{
					$this->DB->update( 'message_topics', 'mt_hasattach=mt_hasattach-1', 'mt_id='.$killmeh['msg_topic_id'], true, true );
				}
			}

			$this->DB->delete( 'attachments', 'attach_id IN ('.implode(",",$finalIDs).') and attach_member_id='.$this->memberData['member_id'] );
 		}

		$this->registry->getClass('output')->silentRedirect( $this->settings['base_url']."app=core&amp;module=usercp&amp;tab=core&amp;area=attachments&amp;do=show" );
	}

	/**
	 * UserCP handle our notifications
	 *
	 * @return	boolean		Successful
	 */
	public function customEvent_removeNotifications()
	{
		//-----------------------------------------
		// Check form hash
		//-----------------------------------------
		
		$this->request['secure_key'] = $this->request['secure_key'] ? $this->request['secure_key'] : $this->request['md5check'];

		if( $this->request['secure_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'usercp_forums_bad_key', 1021523 );
		}
		
		//-----------------------------------------
		// Notifications library
		//-----------------------------------------
		
		$classToLoad		= IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
		$notifyLibrary		= new $classToLoad( $this->registry );
		$notifyLibrary->setMember( $this->memberData );

		//-----------------------------------------
		// Delete the notifications
		//-----------------------------------------
		
		$_toDelete	= IPSLib::cleanIntArray( $this->request['notifications'] );
		
		if( !count($_toDelete) )
		{
			return $this->showInlineNotifications( $this->lang->words['no_notify_del'] );
		}
		
		$this->DB->delete( 'inline_notifications', "notify_id IN(" . implode( ',', $_toDelete ) . ") AND notify_to_id=" . $this->memberData['member_id'] );
		
		//-----------------------------------------
		// If member has 'unread' count, rebuild count
		//-----------------------------------------
		
		if( $this->memberData['notification_cnt'] )
		{
			$notifyLibrary->rebuildUnreadCount();
		}

		//-----------------------------------------
		// Redirect
		//-----------------------------------------
		
		$this->registry->getClass('output')->silentRedirect( $this->settings['base_url']."app=core&amp;module=usercp&amp;tab=core&amp;area=notificationlog&amp;confirm=1" );
	}
	
	/**
	 * Mark a notification
	 *
	 * @return	string	HTML
	 */
	public function customEvent_markNotification()
	{
		//-----------------------------------------
		// Notifications library
		//-----------------------------------------
		
		$classToLoad		= IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
		$notifyLibrary		= new $classToLoad( $this->registry );
		$notifyLibrary->setMember( $this->memberData );

		//-----------------------------------------
		// Get notification
		//-----------------------------------------
		
		/* Marking them all as read? */
		if ( $this->request['mark'] == 'all' )
		{
			$this->DB->update( 'inline_notifications', array( 'notify_read' => time() ), 'notify_to_id=' . $this->memberData['member_id'] );
		}
		else
		{
			$id				= intval($this->request['mark']);
			$notification	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'inline_notifications', 'where' => 'notify_id=' . $id ) );
			
			//-----------------------------------------
			// Error checking
			//-----------------------------------------
			
			if( !$notification['notify_id'] )
			{
				if( $this->request['ajax'] )
				{
					print 'ok';
					exit;
				}
				else
				{
					$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . "app=core&amp;module=usercp&amp;tab=core&amp;area=notificationlog" );
				}
			}
	
			if( $notification['notify_to_id'] != $this->memberData['member_id'] )
			{
				if( $this->request['ajax'] )
				{
					print 'ok';
					exit;
				}
				else
				{
					$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . "app=core&amp;module=usercp&amp;tab=core&amp;area=notificationlog" );
				}
			}
			
			//-----------------------------------------
			// Update read timestamp
			//-----------------------------------------
			
			$this->DB->update( 'inline_notifications', array( 'notify_read' => time() ), 'notify_id=' . $id );
		}
		
		//-----------------------------------------
		// If member has 'unread' count, rebuild count
		//-----------------------------------------
		
		if( $this->memberData['notification_cnt'] )
		{
			$notifyLibrary->rebuildUnreadCount();
		}
		
		if( $this->request['ajax'] )
		{
			print 'ok';
			exit;
		}
		else
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . "app=core&amp;module=usercp&amp;tab=core&amp;area=notificationlog" );
		}
	}
	
	/**
	 * View a notification
	 *
	 * @return	string	HTML
	 */
	public function customEvent_viewNotification()
	{
		//-----------------------------------------
		// Notifications library
		//-----------------------------------------
		
		$classToLoad		= IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
		$notifyLibrary		= new $classToLoad( $this->registry );
		$notifyLibrary->setMember( $this->memberData );

		//-----------------------------------------
		// Get notification
		//-----------------------------------------
		
		$id				= intval($this->request['view']);
		$notification	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'inline_notifications', 'where' => 'notify_id=' . $id ) );
		
		//-----------------------------------------
		// Error checking
		//-----------------------------------------
		
		if( !$notification['notify_id'] )
		{
			$this->registry->output->showError( 'bad_notify_id', 10191 );
		}

		if( $notification['notify_to_id'] != $this->memberData['member_id'] )
		{
			$this->registry->output->showError( 'bad_notify_id', 10192 );
		}
		
		//-----------------------------------------
		// Update read timestamp
		//-----------------------------------------
		
		$this->DB->update( 'inline_notifications', array( 'notify_read' => time() ), 'notify_id=' . $id );
		
		//-----------------------------------------
		// If member has 'unread' count, rebuild count
		//-----------------------------------------
		
		if( $this->memberData['notification_cnt'] )
		{
			$notifyLibrary->rebuildUnreadCount();
		}
		
		//-----------------------------------------
		// Parse for display
		//-----------------------------------------
		
		/* As the email template parser makes an attempt to reparse 'safe' HTML, we need to make it safe here */
		$notification['notify_text'] = IPSText::htmlspecialchars( $notification['notify_text'] );
			
 		IPSText::getTextClass('bbcode')->parse_smilies				= 1;
 		IPSText::getTextClass('bbcode')->parse_nl2br				= 1;
 		IPSText::getTextClass('bbcode')->parse_html					= 0;
 		IPSText::getTextClass('bbcode')->parse_bbcode				= 1;
 		IPSText::getTextClass('bbcode')->parsing_section			= 'global';
 		
 		$notification['notify_text'] = IPSText::getTextClass('bbcode')->preDisplayParse( nl2br( $notification['notify_text'] ) );
 		
		//-----------------------------------------
		// Show notification
		//-----------------------------------------
		
		$this->_nav[] = array( $this->lang->words['m_arch_notify'], 'app=core&amp;module=usercp&amp;tab=core&amp;area=notificationlog' );
		return $this->registry->getClass('output')->getTemplate('ucp')->showNotification( $notification );
	}
	
	/**
	 * Custom Event: Remove Twitter link
	 *
	 * @return	@e void  
	 */
	public function customEvent_twitterRemove()
	{
		//-----------------------------------------
		// Check secure hash...
		//-----------------------------------------
		
		if ( $this->request['secure_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'authorization_error', 100, true, null, 403 );
		}
		
		//-----------------------------------------
		// Okay... 
		//-----------------------------------------
		
		if ( $this->memberData['twitter_id'] )
		{
			/* Remove the link */
			IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'twitter_id' => 0, 'twitter_token' => '', 'twitter_secret' => '' ) ) );
		}
		
		/* Log the user out */
		$this->registry->getClass('output')->silentRedirect( $this->settings['base_url']."app=core&module=global&section=login&do=logout&k=" . $this->member->form_hash );
	}
	
	/**
	 * Custom Event: Create facebook link
	 *
	 * @return	@e void  
	 */
	public function customEvent_facebookLink()
	{
		//-----------------------------------------
		// Check secure hash...
		//-----------------------------------------
		
		if ( $this->request['secure_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'authorization_error', 100, true, null, 403 );
		}
		
		/* Load application */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/facebook/connect.php', 'facebook_connect' );
		$facebook    = new $classToLoad( $this->registry );
		
		try
		{
			$facebook->linkMember( $this->memberData['member_id'] );
		}
		catch( Exception $error )
		{
			$msg = $error->getMessage();
		
			switch( $msg )
			{
				default:
				case 'NO_FACEBOOK_USER_LOGGED_IN':
				case 'ALREADY_LINKED':
					$this->registry->getClass('output')->showError( 'fbc_authorization_screwup', 1005.99, null, null, 403 );
				break;
			}
		}
		
		//-----------------------------------------
		// Return
		//-----------------------------------------

		$this->registry->getClass('output')->silentRedirect( $this->settings['base_url']."app=core&amp;module=usercp&amp;tab=core&amp;area=facebook&amp;do=show" );
	}
	
	/**
	 * Custom Event: Remove facebook link
	 *
	 * @return	@e void  
	 */
	public function customEvent_facebookRemove()
	{
		//-----------------------------------------
		// Check secure hash...
		//-----------------------------------------
		
		if ( $this->request['secure_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'authorization_error', 100, true, null, 403 );
		}
		
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/facebook/connect.php', 'facebook_connect' );
		$facebook    = new $classToLoad( $this->registry );
			
		//-----------------------------------------
		// Okay...
		//-----------------------------------------
		
		if ( $this->memberData['fb_uid'] )
		{
			/* Unauthorize application */
			$facebook->revokeAuthorization();
						
			/* Remove the link */
			IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'fb_uid' => 0, 'fb_emailhash' => '', 'fb_token' => '', 'fb_lastsync' => 0 ) ) );
		}
		
		/* Log the user out */
		$this->registry->getClass('output')->silentRedirect( $this->settings['base_url']."app=core&module=global&section=login&do=logout&k=" . $this->member->form_hash );
	}
	
	/**
	 * Custom Event: Sync up facebook
	 * NO LONGER USED. LEFT FOR FIX CONFIRMATION
	 *
	 * @return	@e void  
	 */
	public function customEvent_facebookSync()
	{
		if ( IPSLib::fbc_enabled() === TRUE )
		{
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/facebook/connect.php', 'facebook_connect' );
			$facebook    = new $classToLoad( $this->registry );
			
			try
			{
				$facebook->syncMember( $this->memberData );
			}
			catch( Exception $error )
			{
				$msg = $error->getMessage();
				
				switch( $msg )
				{
					case 'NOT_LINKED':
					case 'NO_MEMBER':
					default:
						$this->registry->getClass('output')->showError( 'fbc_authorization_screwup', 1005, null, null, 403 );
					break;
				}
			}
			
			//-----------------------------------------
			// Return
			//-----------------------------------------

			$this->registry->getClass('output')->silentRedirect( $this->settings['base_url']."app=core&amp;module=usercp&amp;tab=core&amp;area=facebook&amp;do=show" );
		}
	}
	
	/**
	 * Custom Event: Run the find user tool
	 *
	 * @return	@e void  
	 */
	public function customEvent_toggleIgnoredUser()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$member_id = intval( $this->request['id'] );
		$field	   = $this->request['field'];
		$update    = array();
		
		//-----------------------------------------
		// Grab user
		//-----------------------------------------
		
		$ignoredUser = $this->DB->buildAndFetch( array( 'select' => '*',
														'from'   => 'ignored_users',
													    'where'  => 'ignore_ignore_id=' . $member_id . ' AND ignore_owner_id=' . $this->memberData['member_id'] ) );
														
		if ( $ignoredUser['ignore_id'] )
		{
			switch( $field )
			{
				default:
				case 'topics':
					$update = array( 'ignore_topics' => ( $ignoredUser['ignore_topics'] == 1 ) ? 0 : 1 );
				break;
				case 'messages':
					$update = array( 'ignore_messages' => ( $ignoredUser['ignore_messages'] == 1 ) ? 0 : 1 );
				break;
				case 'signatures':
					$update = array( 'ignore_signatures' => ( $ignoredUser['ignore_signatures'] == 1 ) ? 0 : 1 );
				break;
				case 'chats':
					$update = array( 'ignore_chats' => ( $ignoredUser['ignore_chats'] == 1 ) ? 0 : 1 );
				break;
			}
			
			//-----------------------------------------
			// Update
			//-----------------------------------------

			$this->DB->update( 'ignored_users', $update, 'ignore_id=' . $ignoredUser['ignore_id'] );
		
			/* Rebuild cache */
			IPSMember::rebuildIgnoredUsersCache( $this->memberData );
		}
	
		//-----------------------------------------
		// Return
		//-----------------------------------------
		
		$this->registry->getClass('output')->silentRedirect( $this->settings['base_url']."app=core&amp;module=usercp&amp;tab=core&amp;area=ignoredusers&amp;do=show" );
	}
	
	/**
	 * Custom event: Remove ignored user
	 *
	 * @author	Matt Mecham
	 * @return	string		Processed HTML
	 */
	public function customEvent_removeIgnoredUser()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$removeID = intval( $this->request['id'] );
		
		$this->DB->delete( 'ignored_users', 'ignore_owner_id=' . $this->memberData['member_id'] . ' AND ignore_ignore_id=' . $removeID );
 		
		/* Rebuild cache */
		IPSMember::rebuildIgnoredUsersCache( $this->memberData );
		
		$this->registry->getClass('output')->silentRedirect( $this->settings['base_url']."app=core&amp;module=usercp&amp;tab=core&amp;area=ignoredusers&amp;do=show" );
	}
	
	/**
	 * UserCP Form Show
	 *
	 * @author	Matt Mecham
	 * @param	string		Current area as defined by 'get_links'
	 * @param	array		Array of errors
	 * @return	string		Processed HTML
	 */
	public function showForm( $current_area, $errors=array() )
	{
		//-----------------------------------------
		// Where to go, what to see?
		//-----------------------------------------

		switch( $current_area )
		{
			default:
			case 'profileinfo':
				return $this->formProfileInfo();
			break;
			case 'signature':
				return $this->formSignature();
			break;
			case 'photo':
				return $this->formPhoto();
			break;
			case 'ignoredusers':
				return $this->formIgnoredUsers();
			break;
			case 'facebook':
				return $this->formFacebook();
			break;
			case 'twitter':
				return $this->formTwitter();
			break;
			case 'customize':
				return $this->formCustomize();
			break;
			case 'email':
				return $this->showFormEmailPassword();
			break;
			case 'displayname':
				return $this->showFormDisplayname();
			break;
			case 'attachments':
				return $this->showFormAttachments();
			break;
			case 'notifications':
				return $this->showFormNotifications();
			break;
			case 'notificationlog':
				return $this->showInlineNotifications();
			break;
		}
	}
	
	/**
	 * Show the customization form
	 *
	 * @author	Matt Mecham
	 * @param	string		Any inline message to show
	 * @return	string		Processed HTML
	 */
	public function formCustomize( $inlineMsg='' )
	{
		/* Allow uploads */
		$this->uploadFormMax = 10000 * 1024;
		
		if ( ! $this->memberData['gbw_allow_customization'] OR $this->memberData['bw_disable_customization'] )
		{		
			$this->registry->getClass('output')->showError( 'no_permission', 1005.5 );
		}
		
		/* Grab current options */
		$options = unserialize( $this->memberData['pp_customization'] );
		$options = is_array( $options ) ? $options : array();
		
		/* Build input */
		foreach( $options as $k => $v )
		{
			$input[ $k ] = ( $this->request[ $k ] ) ? $this->request[ $k ] : $v;
		}
		
		/* Figure out preview URL */
		if ( $options['type'] == 'url' AND $options['bg_url'] )
		{
			$input['_preview'] = $options['bg_url'];
		}
		else if ( $options['type'] == 'upload' AND $options['bg_url'] )
		{
			$input['_preview'] = $this->settings['upload_url'] . '/' . $options['bg_url'];
			$input['bg_url']   = '';
		}
		
		/* Show form */
		return $this->registry->getClass('output')->getTemplate('ucp')->membersProfileCustomize( $options, $input, $inlineMsg );
	}
	
	/**
	 * Show the twitter form
	 *
	 * @author	Matt Mecham
	 * @param	string		Any inline message to show
	 * @return	string		Processed HTML
	 */
	public function formTwitter( $inlineMsg='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		if( !IPSLib::twitter_enabled() )
		{		
			$this->registry->getClass('output')->showError( 'twitter_disabled', 1005.1 );
		}
		
		//-----------------------------------------
		// Twitter user logged in?
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/twitter/connect.php', 'twitter_connect' );
		$twitter	 = new $classToLoad( $this->registry, $this->memberData['twitter_token'], $this->memberData['twitter_secret'] );
		
		//-----------------------------------------
		// Thaw bitfield options
		//-----------------------------------------
		
		$bwOptions = IPSBWOptions::thaw( $this->memberData['tc_bwoptions'], 'twitter' );
		
		//-----------------------------------------
		// Merge..
		//-----------------------------------------
		
		if ( is_array( $bwOptions ) )
		{
			foreach( $bwOptions as $k => $v )
			{
				$this->memberData[ $k ] = $v;
			}
		}
		
		if( ! $twitter->isConnected() )
		{
			$this->hide_form_and_save_button = 1;
		}
		
		$userData = $twitter->fetchUserData();
		
		if ( isset( $userData['status']['text'] ) )
		{	
			if ( IPS_DOC_CHAR_SET != 'UTF-8' )
			{
				$userData['status']['text'] = IPSText::utf8ToEntities( $userData['status']['text'] );
			}
			
			/* Make safe */
			$userData['status']['text'] = str_replace( array( '<', '>' ), array( '&lt;', '&gt;' ), $userData['status']['text'] );
		}
		
		return $this->registry->getClass('output')->getTemplate('ucp')->membersTwitterConnect( $twitter->isConnected(), $userData );
	}
	
	
	/**
	 * Show the member form
	 *
	 * @author	Matt Mecham
	 * @param	string		Any inline message to show
	 * @return	string		Processed HTML
	 */
	public function formFacebook( $inlineMsg='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		if( !IPSLib::fbc_enabled() )
		{		
			$this->registry->getClass('output')->showError( 'fbc_disabled', 1005.2 );
		}
		
		//-----------------------------------------
		// Shut off save button if not associated yet
		//-----------------------------------------
		
		if( !$this->memberData['fb_uid'] )
		{
			$this->hide_form_and_save_button	= true;
			$userData							= array();
			$linkedMemberData					= array();
			$perms								= array();
		}
		else
		{
			//-----------------------------------------
			// FB user logged in?
			//-----------------------------------------
			
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/facebook/connect.php', 'facebook_connect' );
			$facebook    = new $classToLoad( $this->registry );
			
			/* Now get the linked user */
			$linkedMemberData = IPSMember::load( intval($this->memberData['fb_uid']), 'all', 'fb_uid' );
			
			$userData = $facebook->fetchUserData();
							
			/* Email */
			$perms['email']          = $facebook->fetchHasAppPermission( 'email' );
			
			/* Publish Stream */
			$perms['publish_stream'] = $facebook->fetchHasAppPermission( 'publish_stream' );
			
			/* Read stream */
			$perms['read_stream']    = $facebook->fetchHasAppPermission( 'read_stream' );
			
			/* Offline access */
			$perms['offline_access'] = $facebook->fetchHasAppPermission( 'offline_access' );
			
			
			//-----------------------------------------
			// Thaw bitfield options
			//-----------------------------------------
			
			$bwOptions = IPSBWOptions::thaw( $this->memberData['fb_bwoptions'], 'facebook' );
			
			//-----------------------------------------
			// Merge..
			//-----------------------------------------
			
			if ( is_array( $bwOptions ) )
			{
				foreach( $bwOptions as $k => $v )
				{
					$this->memberData[ $k ] = $v;
				}
			}
			
			//-----------------------------------------
			// Able to update status?
			//-----------------------------------------
	
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/member/status.php', 'memberStatus' );
			$this->registry->setClass( 'memberStatus', new $classToLoad( $this->registry ) );
			
			$this->registry->memberStatus->setAuthor( $this->memberData );
			$this->memberData['can_updated_status'] = $this->registry->memberStatus->canCreate();
			
			$_updates = $facebook->fetchUserTimeline( $userData['id'], 0, true );
						
			/* Got any? */
			if ( count( $_updates ) )
			{
				$update = array_shift( $_updates );
				
				if ( count( $update ) AND is_array( $update ) )
				{
					$userData['status'] = $update;
				}
			}
			
			if ( is_array( $userData ) AND $userData['status']['message'] AND IPS_DOC_CHAR_SET != 'UTF-8' )
			{
				$userData['status']['message'] = IPSText::utf8ToEntities( $userData['status']['message'] );
			}
			
			/* Make safe */
			$userData['status']['message'] = str_replace( array( '<', '>' ), array( '&lt;', '&gt;' ), $userData['status']['message'] );
		}
		
		return $this->registry->getClass('output')->getTemplate('ucp')->membersFacebookConnect( trim($this->memberData['fb_uid']), $userData, $linkedMemberData, $perms );
	}
	
	/**
	 * Show the ignored users
	 *
	 * @author	Matt Mecham
	 * @return	string		Processed HTML
	 */
	public function formIgnoredUsers()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$final_users  = array();
 		$temp_users   = array();
 		$uid          = intval( $this->request['uid'] );
		$ignoredUsers = array();
		
 		//-----------------------------------------
 		// Do we have incoming?
 		//-----------------------------------------
 		
 		if ( $uid )
 		{
 			$newmem = IPSMember::load( $uid );

 			$this->request['newbox_1']	= $newmem['members_display_name'];
 		}
 		
 		//-----------------------------------------
 		// Get ignored users
 		//-----------------------------------------
 		
 		$perPage = 25;
 		
 		/* Count */
		$count = $this->DB->buildAndFetch( array( 'select' => 'count(*) as dracula', 'from' => 'ignored_users', 'where' => 'ignore_owner_id=' . $this->memberData['member_id'] ) );
 		
 		/* Sort out pagination */
		$st = intval($this->request['st']) >=0 ? intval($this->request['st']) : 0;
		$pagination = $this->registry->output->generatePagination( array( 
			'totalItems'		=> $count['dracula'],
			'itemsPerPage'		=> $perPage,
			'currentStartValue'	=> $st,
			'baseUrl'			=> 'app=core&module=usercp&tab=core&area=ignoredusers',
			)	);

		/* Get em */ 		
		$this->DB->build( array( 'select' => '*', 'from' => 'ignored_users', 'where' => 'ignore_owner_id=' . $this->memberData['member_id'], 'limit' => array( $st, $perPage ) ) );
 		$this->DB->execute();

		while( $row = $this->DB->fetch() )
		{
			$ignoredUsers[ $row['ignore_ignore_id'] ] = $row;
		}
 		
 		//-----------------------------------------
 		// Get members and check to see if they've
 		// since been moved into a group that cannot
 		// be ignored
 		//-----------------------------------------
 		
 		foreach( $ignoredUsers as $_id => $data )
 		{
 			if ( intval($_id) )
 			{
 				$temp_users[] = $_id;
 			}
 		}
 		
 		if ( count($temp_users) )
 		{
 			$members = IPSMember::load( $temp_users, 'all' );
		
 			foreach( $members as $m )
 			{
 				$m['g_title'] = IPSMember::makeNameFormatted( $this->caches['group_cache'][ $m['member_group_id'] ]['g_title'], $m['member_group_id'] );
 				
 				$final_users[ $m['member_id'] ] = IPSMember::buildDisplayData( $m );
				$final_users[ $m['member_id'] ]['ignoreData'] = $ignoredUsers[ $m['member_id'] ];
 			}
 		}

 		$this->request['newbox_1'] = $this->request['newbox_1'] ? $this->request['newbox_1'] : '';
 		
 		return $this->registry->getClass('output')->getTemplate('ucp')->membersIgnoredUsersForm( $final_users, $pagination );
	}
	
	
	/**
	 * Show the signature page
	 *
	 * @author	Matt Mecham
	 * @return	string		Processed HTML
	 */
	public function formSignature()
	{
		/* Load editor stuff */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$this->editor = new $classToLoad();
		
		//-----------------------------------------
		// Check to make sure that we can edit profiles..
		//-----------------------------------------
				
		$sig_restrictions = explode( ':', $this->memberData['g_signature_limits'] );
		
		if ( ! $this->memberData['g_edit_profile'] OR ( $sig_restrictions[0] AND ! $this->memberData['g_sig_unit'] ) )
		{
			$this->registry->getClass('output')->showError( 'members_profile_disabled', 1024, null, null, 403 );
		}
		
		/* Signature Limits */
	 	if ( $sig_restrictions[0] AND $this->memberData['g_sig_unit'] )
		{
			if ( $this->memberData['gbw_sig_unit_type'] )
			{
				/* days */
				if ( $this->memberData['joined'] > ( time() - ( 86400 * $this->memberData['g_sig_unit'] ) ) )
				{
					$this->hide_form_and_save_button = 1;
					$form['_noPerm'] = sprintf( $this->lang->words['sig_group_restrict_date'], $this->lang->getDate( $this->memberData['joined'] + ( 86400 * $this->memberData['g_sig_unit'] ), 'long' ) );
				}
			}
			else
			{
				/* Posts */
				if ( $this->memberData['posts'] < $this->memberData['g_sig_unit'] )
				{
					$this->hide_form_and_save_button = 1;
					$form['_noPerm'] = sprintf( $this->lang->words['sig_group_restrict_posts'], $this->memberData['g_sig_unit'] - $this->memberData['posts'] );
				}
			}
			
			if( $form['_noPerm'] )
			{
				return $this->registry->getClass('output')->getTemplate('ucp')->membersSignatureFormError( $form );
			}
		}
	
 		//-----------------------------------------
 		// Set max length
 		//-----------------------------------------

 		$current_sig	= '';
 		$t_sig			= '';
 		
		/* Set content in editor */
		$this->editor->setAllowBbcode( true );
		$this->editor->setAllowSmilies( false );
		$this->editor->setAllowHtml( $this->memberData['g_dohtml'] );
		$this->editor->setContent( $this->memberData['signature'], 'signatures' );
		
		/* Current signature preview */
		IPSText::getTextClass('bbcode')->parse_bbcode			= 1;
		IPSText::getTextClass('bbcode')->parse_smilies			= 0;
		IPSText::getTextClass('bbcode')->parse_html				= $this->memberData['g_dohtml'];
		IPSText::getTextClass('bbcode')->parse_nl2br			= 1;
		IPSText::getTextClass('bbcode')->parsing_section		= 'signatures';
		IPSText::getTextClass('bbcode')->parsing_mgroup			= $this->memberData['member_group_id'];
		IPSText::getTextClass('bbcode')->parsing_mgroup_others	= $this->memberData['mgroup_others'];
		
		$signature	= IPSText::getTextClass('bbcode')->preDisplayParse( $this->memberData['signature'] );
		
		return $this->registry->getClass('output')->getTemplate('ucp')->membersSignatureForm( $this->editor->show( 'Post', array( 'noSmilies' => true ) ), $sig_restrictions, $signature );
	}

	
	/**
	 * Show the profile information
	 *
	 * @author	Matt Mecham
	 * @return	string		Processed HTML
	 */
	public function formProfileInfo()
	{
		/* Load Lang File */
		$this->registry->class_localization->loadLanguageFile( array( 'public_profile' ), 'members' );
		
		/* Load editor stuff */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$this->editor = new $classToLoad();
		
		/* INIT */
	 	$required_output = "";
		$optional_output = "";
		
		/* Permission Check */
		if( ! $this->memberData['g_edit_profile'] )
		{
			$this->registry->getClass('output')->showError( 'members_profile_disabled', 1026, null, null, 403 );
		}
		
		/* Format the birthday drop boxes.. */
		$date = getdate();
		
		$day   = array();
		$mon   = array();
		$year  = array();
		$times = array();
		
		/* Build the day options */
		$day[] = array( '0', '--' );
		for ( $i = 1 ; $i < 32 ; $i++ )
		{
			$day[] = array( $i, $i );			
		}
		
		/* Build the month options */
		$mon[] = array( '0', '--' );
		for( $i = 1 ; $i < 13 ; $i++ )
		{
			$mon[] = array( $i, $this->lang->words['M_' . $i ] );
		}
		
		/* Build the years options */
		$i = $date['year'] - 1;
		$j = $date['year'] - 100;
		
		$year[] = array( '0', '--' );
		for( $i ; $j < $i ; $i-- )
		{
			$year[] = array( $i, $i );
		}
	
		/* Custom Fields */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/customfields/profileFields.php', 'customProfileFields' );
		$fields      = new $classToLoad();
		
		$fields->member_data = $this->member->fetchMemberData();
		$fields->initData( 'edit' );
		$fields->parseToEdit();
		
		$field_output = array();
		
		if ( count( $fields->out_fields ) )
		{
			foreach( $fields->out_fields as $id => $element )
			{
				$data = $fields->cache_data[ $id ];
				$field_output[ $data['pf_group_key'] ][ $id ] = array(
																		'field' => $this->registry->getClass('output')->getTemplate('ucp')->field_entry( $fields->field_names[ $id ], $fields->field_desc[ $id ], $fields->out_fields[ $id ], $id ),
																		'required' => $data['pf_not_null']
																	);
			}
																		
			/*foreach( $fields->out_fields as $id => $data )
	    	{
	    		if ( $fields->cache_data[ $id ]['pf_not_null'] == 1 )
				{
					$ftype = 'required_output';
				}
				else
				{
					$ftype = 'optional_output';
				}

				${$ftype} .= $this->registry->getClass('output')->getTemplate('ucp')->field_entry( $fields->field_names[ $id ], $fields->field_desc[ $id ], $data, $id );
	    	}*/
		}
		
		/* About me */
		$this->editor->setContent( $this->memberData['pp_about_me'] );
		
		$amEditor = $this->editor->show( 'Post', array( 'delayInit' => 1 ) );
		
		/* Times */
		foreach ( $this->lang->words as $k => $v )
		{
			if ( strpos( $k, "time_" ) === 0 )
			{
				$k = str_replace( "time_", '', $k );

				if( preg_match( '/^[\-\d\.]+$/', $k ) )
				{
					$times[ $k ]	= $v;
				}
			}
		}

		ksort( $times );
		
		/* Build and return the form */
		$template = $this->registry->getClass('output')->getTemplate('ucp')->membersProfileForm( $field_output, $fields->fetchGroupTitles(), $day, $mon, $year, $amEditor, $times );

		return $template;
	}

	/**
	 * Show the logged inline notifications
	 *
	 * @author	Brandon Farber
	 * @param	string		Error message
	 * @return	string		Processed HTML
	 */
	public function showInlineNotifications( $error='' )
	{
		/* Init */
		$start   = intval( $this->request['st'] );
		$perPage = 50;
		
		//-----------------------------------------
		// Get class
		//-----------------------------------------
		
		$classToLoad		= IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
		$notifyLibrary		= new $classToLoad( $this->registry );
		$notifyLibrary->setMember( $this->memberData );	
		
		/* Clear them? - used in mobile skin */
		if ( isset($this->request['clear']) && trim($this->request['clear']) == 'true' )
		{
			$this->DB->update( 'inline_notifications', array( 'notify_read' => 1 ), 'notify_to_id=' . $this->memberData['member_id'] );
		
			$notifyLibrary->rebuildUnreadCount();
		}
		
		//-----------------------------------------
		// Turn off normal form
		//-----------------------------------------
		
		$this->hide_form_and_save_button = 1;
		
		//-----------------------------------------
		// Get notifications
		//-----------------------------------------
		
		$_notifications	= array();
		$mids			= array();
		
		$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as er',
										  		  'from'   => 'inline_notifications',
										  		  'where'  => 'notify_to_id=' . $this->memberData['member_id'] ) );
		
		if ( $count['er'] )
		{
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'inline_notifications',
									 'where'  => 'notify_to_id=' . $this->memberData['member_id'],
									 'limit'  => array( $start, $perPage ),
									 'order'  => 'notify_sent DESC' ) );
			$outer	= $this->DB->execute();
			
			while( $r = $this->DB->fetch($outer) )
			{
				$r['notify_icon']	= $notifyLibrary->getNotificationIcon( $r['notify_type_key'] );
				
				$_notifications[]	= $r;
	
				$mids[ $r['notify_from_id'] ] = $r['notify_from_id'];
			}
	
			/* Get members */
			if ( count( $mids ) )
			{
				$members = IPSMember::load( array_keys( $mids ), 'all' );
				
				if ( count( $members ) )
				{
					foreach( $_notifications as $key => $data )
					{ 
						if ( isset($members[ $data['notify_from_id'] ]) )
						{
							$_notifications[ $key ]['member'] = IPSMember::buildProfilePhoto( $members[ $data['notify_from_id'] ] );
						}
					}
				}
			}
		}

		$pages = $this->registry->getClass('output')->generatePagination( array( 'totalItems'         => $count['er'],
														   					 	 'itemsPerPage'       => $perPage,
																				 'currentStartValue'  => $start,
																				 'baseUrl'            => "app=core&amp;module=usercp&amp;tab=core&amp;area=notificationlog"
																		 )		);
		
		//-----------------------------------------
		// Send to template
		//-----------------------------------------
		
		return $this->registry->getClass('output')->getTemplate('ucp')->notificationsLog( $_notifications, $error, $pages );
	}

	/**
	 * Show notification configuration form
	 *
	 * @author	Brandon Farber
	 * @return	string		Processed HTML
	 * 
	 * @note	Updating this function update also mobileApiRequest::_handleNotificationTypes()
	 */
	public function showFormNotifications()
	{
		//-----------------------------------------
		// Notifications library
		//-----------------------------------------
		
		$classToLoad		= IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
		$notifyLibrary		= new $classToLoad( $this->registry );
		$notifyLibrary->setMember( $this->memberData );
		
		//-----------------------------------------
		// Show the form
		//-----------------------------------------
		
		$_basicOptions	= array( array( 'email', $this->lang->words['notopt__email'] ), array( 'inline', $this->lang->words['notopt__inline'] ), array( 'mobile', $this->lang->words['notopt__mobile'] ) );
		$_configOptions	= $notifyLibrary->getNotificationData( TRUE );
		$_notifyConfig	= $notifyLibrary->getMemberNotificationConfig( $this->memberData );
		$_defaultConfig	= $notifyLibrary->getDefaultNotificationConfig();
		$_formOptions	= array();
		
		foreach( $_configOptions as $option )
		{
			$_thisConfig	= isset($_notifyConfig[ $option['key'] ]) ? $_notifyConfig[ $option['key'] ] : $_defaultConfig[ $option['key'] ];
			
			//-----------------------------------------
			// Determine available options
			//-----------------------------------------
			
			$_available	= array();
			
			foreach( $_basicOptions as $_bo )	// ewwww :P
			{
				if( !is_array($_defaultConfig[ $option['key'] ]['disabled']) OR !in_array( $_bo[0], $_defaultConfig[ $option['key'] ]['disabled'] ) )
				{
					$_available[]	= $_bo;
				}
			}
			
			//-----------------------------------------
			// If none available, at least give inline
			//-----------------------------------------
			
			if( !count($_available) )
			{
				$_available[]	= array( 'inline', $this->lang->words['notify__inline'] );
			}
			
			//-----------------------------------------
			// Start setting data to pass to form
			//-----------------------------------------
			
			$_formOptions[ $option['key'] ]					= array();
			$_formOptions[ $option['key'] ]['key']			= $option['key'];
			$_formOptions[ $option['key'] ]['app']			= $option['app'];
			
			//-----------------------------------------
			// Rikki asked for this...
			//-----------------------------------------
			
			foreach( $_available as $_availOption )
			{
				$_formOptions[ $option['key'] ]['options'][ $_availOption[0] ]	= $_availOption;
			}
			
			//$_formOptions[ $option['key'] ]['options']		= $_available;
			
			$_formOptions[ $option['key'] ]['defaults']		= is_array($_thisConfig['selected']) ? $_thisConfig['selected'] : array();
			$_formOptions[ $option['key'] ]['disabled']		= 0;
			
			//-----------------------------------------
			// Don't allow member to configure
			// Still show, but disable on form
			//-----------------------------------------
			
			if( $_defaultConfig[ $option['key'] ]['disable_override'] )
			{
				$_formOptions[ $option['key'] ]['disabled']		= 1;
				$_formOptions[ $option['key'] ]['defaults']		= is_array($_defaultConfig[ $option['key'] ]['selected']) ? $_defaultConfig[ $option['key'] ]['selected'] : array();
			}
		}
		
		//-----------------------------------------
		// Other settings
		//-----------------------------------------

		$_emailData   = array();
		
		//-----------------------------------------
		// Email settings...
		//-----------------------------------------
		
		$_emailData['auto_track']	= $this->memberData['auto_track'] ? 'checked="checked"' : '';
		
		foreach( array( 'none', 'immediate', 'offline', 'daily', 'weekly' ) as $_opt )
		{
			$_emailData['trackOption'][ $_opt ] = ( $this->memberData['auto_track'] == $_opt ) ? 'selected="selected"' : '';
		}
		
 		return $this->registry->getClass('output')->getTemplate('ucp')->notificationsForm( $_formOptions, $_emailData );
	}

	/**
	 * Show the attachments form
	 *
	 * @author	Matt Mecham
	 * @return	string		Processed HTML
	 */
	public function showFormAttachments()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$info        = array();
 		$start       = intval( $this->request['st'] );
 		$perpage     = 15;
 		$sort_key    = "";
 		$attachments = array();
		
		$this->hide_form_and_save_button = 1;
		
		//-----------------------------------------
		// Sort it
		//-----------------------------------------
		
 		switch ( $this->request['sort'] )
 		{
 			case 'date':
 				$sort_key = 'a.attach_date ASC';
 				$info['date_order'] = 'rdate';
 				$info['size_order'] = 'size';
 				break;
 			case 'rdate':
 				$sort_key = 'a.attach_date DESC';
 				$info['date_order'] = 'date';
 				$info['size_order'] = 'size';
 				break;
 			case 'size':
 				$sort_key = 'a.attach_filesize DESC';
 				$info['date_order'] = 'date';
 				$info['size_order'] = 'rsize';
 				break;
 			case 'rsize':
 				$sort_key = 'a.attach_filesize ASC';
 				$info['date_order'] = 'date';
 				$info['size_order'] = 'size';
 				break;
 			default:
 				$sort_key = 'a.attach_date DESC';
 				$info['date_order'] = 'date';
 				$info['size_order'] = 'size';
 				break;
 		}

 		//-----------------------------------------
 		// Get some stats...
 		//-----------------------------------------

 		$maxspace = intval($this->memberData['g_attach_max']);

 		if ( $this->memberData['g_attach_max'] == -1 )
 		{
 			$this->registry->getClass('output')->showError( 'no_permission_to_attach', 1010 );
 		}

 		//-----------------------------------------
 		// Limit by forums
 		//-----------------------------------------

 		$stats = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count, ' . $this->DB->buildCoalesce( array( 'sum(attach_filesize)', 0 ) ) . ' as sum',
 												  'from'   => 'attachments',
 												  'where'  => 'attach_member_id=' . $this->memberData['member_id'] . " AND attach_rel_module IN( 'post', 'msg' )" ) );

 		 		
 		if ( $maxspace > 0 )
 		{
			//-----------------------------------------
			// Figure out percentage used
			//-----------------------------------------

			$info['has_limit']    = 1;
			$info['full_percent'] = $stats['sum'] ? sprintf( "%.0f", ( ( $stats['sum'] / ($maxspace * 1024) ) * 100) ) : 0;

			if ( $info['full_percent'] > 100 )
			{
				$info['full_percent'] = 100;
			}
			else if ( $info['full_percent'] < 1 AND $stats['count'] > 0 )
			{
				$info['full_percent'] = 1;
			}

			$info['attach_space_count'] = sprintf( $this->lang->words['attach_space_count'], intval($stats['count']), intval($info['full_percent']) );
			$info['attach_space_used']  = sprintf( $this->lang->words['attach_space_used'] , IPSLib::sizeFormat($stats['sum']), IPSLib::sizeFormat($maxspace * 1024) );
 		}
 		else
 		{
 			$info['has_limit'] = 0;
 			$info['attach_space_used']  = sprintf( $this->lang->words['attach_space_unl'] , IPSLib::sizeFormat($stats['sum']) );
 		}

 		//-----------------------------------------
 		// Pages
 		//-----------------------------------------

 		$pages = $this->registry->getClass('output')->generatePagination( array(  'totalItems'         => $stats['count'],
														   					 	  'itemsPerPage'       => $perpage,
																				  'currentStartValue'  => $start,
																				  'baseUrl'            => "app=core&amp;module=usercp&amp;tab=core&amp;area=attachments&amp;sort=" . $this->request['sort'] . "",
																		  )      );

 		//-----------------------------------------
 		// Get attachments...
 		//-----------------------------------------

		if( $stats['count'] )
		{
	 		$this->DB->build( array( 'select'	=> 'a.*',
									 'from'		=> array( 'attachments' => 'a' ),
									 'where'	=> "a.attach_member_id=" . $this->memberData['member_id'] . " AND a.attach_rel_module IN( 'post', 'msg' )",
									 'order'	=> $sort_key,
									 'limit'	=> array( $start, $perpage ),
									 'add_join'	=> array(
														array( 'select'	=> 'p.topic_id',
																'from'	=> array( 'posts' => 'p' ),
																'where'	=> 'p.pid=a.attach_rel_id',
																'type'	=> 'left'
															),
														array( 'select'	=> 't.*',
																'from'	=> array( 'topics' => 't' ),
																'where'	=> 't.tid=p.topic_id',
																'type'	=> 'left'
															) )
							 )		);
	    	$outer = $this->DB->execute();
	
			$this->registry->getClass( 'class_localization')->loadLanguageFile( array( 'public_topic' ), 'forums' );
	
			$cache = $this->cache->getCache('attachtypes');
	
			while ( $row = $this->DB->fetch( $outer ) )
			{
				if ( IPSMember::checkPermissions('read', $row['forum_id'] ) != TRUE )
				{
					$row['title'] = $this->lang->words['attach_topicmoved'];
				}
	
				//-----------------------------------------
				// Full attachment thingy
				//-----------------------------------------
	
				if ( $row['attach_rel_module'] == 'post' )
				{
					$row['_type'] = 'post';
				}
				else if ( $row['attach_rel_module'] == 'msg' )
				{
					$row['_type'] = 'msg';
					$row['title'] = $this->lang->words['attach_inpm'];
				}
	
				/* IPB 2.x conversion */
				$row['image']       = str_replace( 'folder_mime_types', 'mime_types', $cache[ $row['attach_ext'] ]['atype_img'] );
				$row['short_name']  = IPSText::truncate( $row['attach_file'], 30 );
				$row['attach_date'] = $this->registry->getClass( 'class_localization')->getDate( $row['attach_date'], 'SHORT' );
				$row['real_size']   = IPSLib::sizeFormat( $row['attach_filesize'] );
	
				$attachments[]      = $row;
			}
		}

    	return $this->registry->getClass('output')->getTemplate('ucp')->coreAttachments( $info, $pages, $attachments );
	}

	/**
	 * Show the Email & Password form
	 *
	 * @author	Matt Mecham
	 * @param	string		Returned error message (if any)
	 * @return	string		Processed HTML
	 */
	public function showFormEmailPassword( $_message='' )
	{
		//-----------------------------------------
		// Do not allow validating members to change
		// email when admin validation is on
		// @see	http://community.invisionpower.com/tracker/issue-19964-loophole-in-registration-procedure/
		//-----------------------------------------
		
		if( $this->memberData['member_group_id'] == $this->settings['auth_group'] AND in_array( $this->settings['reg_auth_type'], array( 'admin', 'admin_user' ) ) )
		{
			$this->registry->output->showError( $this->lang->words['admin_val_no_email_chg'], 10189 );
		}
		
		//-----------------------------------------
    	// Do we have another URL for email resets?
    	//-----------------------------------------

		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/handlers/han_login.php', 'han_login' );
		$this->han_login   = new $classToLoad( $this->registry );
    	$this->han_login->init();
    	$this->han_login->checkMaintenanceRedirect();

		//$txt = $this->lang->words['ce_current'] . $this->memberData['email'];

 		if ( $this->settings['reg_auth_type'])
 		{
 			$txt .= $this->lang->words['ce_auth'];
 		}
 		
		$_message = $_message ? $this->lang->words[$_message] : '';

		if( $this->memberData['g_access_cp'] )
		{
			$this->hide_form_and_save_button	= true;
		}

 		return $this->registry->getClass('output')->getTemplate('ucp')->emailPasswordChangeForm( $txt, $_message, $this->_isFBUser );
	}

	/**
	 * Show the display name form
	 *
	 * @author	Matt Mecham
	 * @param	string		Error message (if any)
	 * @return	string		Processed HTML
	 */
	public function showFormDisplayname( $error="" )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$form = array();

		//-----------------------------------------
		// CHECK (please)
		//-----------------------------------------

		if ( ! $this->settings['auth_allow_dnames'] OR $this->memberData['g_dname_changes'] == 0 OR $this->memberData['g_dname_date'] == 0 )
		{
			$this->registry->getClass('output')->showError( 'no_permission_for_display_names', 1011 );
		}

		$this->request['display_name'] =  $this->request['display_name'] ? $this->request['display_name'] : '';

		$this->settings['username_errormsg'] =  str_replace( '{chars}', $this->settings['username_characters'], $this->settings['username_errormsg'] );

		//-----------------------------------------
		// Grab # changes > 24 hours
		//-----------------------------------------

		$time_check = time() - 86400 * $this->memberData['g_dname_date'];

		if( $time_check < $this->memberData['joined'] )
		{
			$time_check = $this->memberData['joined'];
		}

		$name_count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count, MIN(dname_date) as min_date', 'from' => 'dnames_change', 'where' => "dname_member_id=" . $this->memberData['member_id'] . " AND dname_date > $time_check AND dname_discount=0" ) );

		$name_count['count']    = intval( $name_count['count'] );
		$name_count['min_date'] = intval( $name_count['min_date'] ) ? intval( $name_count['min_date'] ) : $time_check;

		//-----------------------------------------
		// Calculate # left
		//-----------------------------------------

		/* Check new permissions */
		$_g = $this->caches['group_cache'][ $this->memberData['member_group_id'] ];

		if ( $_g['g_displayname_unit'] )
		{
			if ( $_g['gbw_displayname_unit_type'] )
			{
				/* days */
				if ( $this->memberData['joined'] > ( time() - ( 86400 * $_g['g_displayname_unit'] ) ) )
				{
					$this->hide_form_and_save_button = 1;
					$form['_noPerm'] = sprintf( $this->lang->words['dname_group_restrict_date'], $this->lang->getDate( $this->memberData['joined'] + ( 86400 * $_g['g_displayname_unit'] ), 'long' ) );
				}
			}
			else
			{
				/* Posts */
				if ( $this->memberData['posts'] < $_g['g_displayname_unit'] )
				{
					$this->hide_form_and_save_button = 1;
					$form['_noPerm'] = sprintf( $this->lang->words['dname_group_restrict_posts'], $_g['g_displayname_unit'] - $this->memberData['posts'] );
				}
			}
		}

		if( !$form['_noPerm'] )
		{
			if ( $this->memberData['g_dname_changes'] == -1 )
			{
				$form['_lang_string'] = $this->lang->words['dname_string2'];
			}
			else
			{
				$form['_changes_left'] = $this->memberData['g_dname_changes'] - $name_count['count'];
				$form['_changes_done'] = $name_count['count'];
	
				# Make sure changes done isn't larger than allowed
				# This happens when changing via ACP
	
				if ( $form['_changes_done'] > $this->memberData['g_dname_changes'] )
				{
					$form['_changes_done'] = $this->memberData['g_dname_changes'];
				}
	
				$form['_first_change'] = $this->registry->getClass( 'class_localization')->getDate( $name_count['min_date'], 'date', 1 );
				$form['_lang_string']  = sprintf( $this->lang->words['dname_string'],
													$form['_changes_done'], $this->memberData['g_dname_changes'],
													$form['_first_change'], $this->memberData['g_dname_changes'],
													$this->memberData['g_dname_date'] ) . $this->lang->words['dname_string2'];
			}
		}

		//-----------------------------------------
		// Print
		//-----------------------------------------

		$this->_pageTitle = $this->lang->words['m_dname_change'];

		return $this->registry->getClass('output')->getTemplate('ucp')->displayNameForm( $form, $error, '', $this->_isFBUser );
	}

	/**
	 * UserCP Form Check
	 *
	 * @author	Matt Mecham
	 * @param	string		Current area as defined by 'get_links'
	 * @return	string		Processed HTML
	 */
	public function saveForm( $current_area )
	{
		//-----------------------------------------
		// Where to go, what to see?
		//-----------------------------------------

		switch( $current_area )
		{
			default:
			case 'profileinfo':
				return $this->saveProfileInfo();
			break;
			case 'signature':
				return $this->saveSignature();
			break;
			case 'photo':
				return $this->savePhoto();
			break;
			case 'ignoredusers':
				return $this->saveIgnoredUsers();
			break;
			case 'facebook':
				return $this->saveFacebook();
			break;
			case 'twitter':
				return $this->saveTwitter();
			break;
			case 'customize':
				return $this->saveCustomize();
			break;
			case 'email':
				return $this->saveFormEmailPassword();
			break;
			case 'password':
				return $this->saveFormPassword();
			break;
			case 'displayname':
				return $this->saveFormDisplayname();
			break;
			case 'notifications':
				return $this->saveFormNotifications();
			break;
		}
	}
	
	/**
	 * UserCP Save Form: Customize
	 *
	 * @return	array	Errors
	 */
	public function saveCustomize()
	{
		/* Init */
		$errors   = array();
		$custom   = array();
		$bg_nix   = trim( $this->request['bg_nix'] );
		$bg_url   = trim( $this->request['bg_url'] );
		$bg_tile  = intval( $this->request['bg_tile'] );
		$bg_color = trim( str_replace( '#', '', $this->request['bg_color'] ) );
		
		/* reset custom */
		$custom   = unserialize( $this->memberData['pp_customization'] );
		
		/* Bug #21578 */
		if( ! $bg_color && $custom['bg_color'] )
		{
			$bg_color = $custom['bg_color'];
		}
		
		/* Delete all? */
		if ( $bg_nix )
		{
			/* reset array */
			$custom = array( 'bg_url' => '', 'type' => '', 'bg_color' => '', 'bg_tile' => '' );
			
			/* remove bg images */
			IPSMember::getFunction()->removeUploadedBackgroundImages( $this->memberData['member_id'] );
		}
		else
		{
			if ( $bg_url AND $this->memberData['gbw_allow_url_bgimage'] )
			{
				/* Check */
				if ( ! stristr( $bg_url, 'http://' ) OR preg_match( '#\(\*#', $bg_url ) )
				{
					return array( 0 => $this->lang->words['pp_bgimg_url_bad'] );
				}
				
				$image_extension = strtolower( pathinfo( $bg_url, PATHINFO_EXTENSION ) );
				
				if( ! in_array( $image_extension, array( 'png', 'jpg', 'gif', 'jpeg'  ) ) )
				{
					return array( 0 => $this->lang->words['pp_bgimg_ext_bad'] );
				}
				
				$custom['bg_url'] = $bg_url;
				$custom['type']   = 'url';
			}
			else if ( $this->memberData['gbw_allow_upload_bgimage'] )
			{
				/* Load more lang strings */
				$this->registry->class_localization->loadLanguageFile( array( 'public_profile' ), 'members' );
		
				/* Upload img */
				$img = IPSMember::getFunction()->uploadBackgroundImage();
		
				if ( $img['status'] == 'fail' )
				{
					return array( 0 => $this->lang->words[ 'pp_' . $img['error'] ] );
				}
				else if ( $img['final_location'] )
				{
					$custom['bg_url'] = $img['final_location'];
					$custom['type']   = 'upload';
				}
			}
		}
		
		/* BG color */
		$custom['bg_color'] = $bg_nix ? '' : IPSText::alphanumericalClean( $bg_color );
		
		/* Tile */
		$custom['bg_tile']  = $bg_nix ? '' : $bg_tile;
		
		/* Save it */
		if ( ! $this->memberData['bw_disable_customization'] AND $this->memberData['gbw_allow_customization'] )
		{
			IPSMember::save( $this->memberData['member_id'], array( 'extendedProfile' => array( 'pp_customization' => serialize( $custom ) ) ) );
		}
		
		return TRUE;
	}
	
	/**
	 * UserCP Save Form: Twitter
	 *
	 * @return	array	Errors
	 */
	public function saveTwitter()
	{
		if( !IPSLib::twitter_enabled() )
		{		
			$this->registry->getClass('output')->showError( 'twitter_disabled', 1005.2 );
		}
		
		//-----------------------------------------
		// Data
		//-----------------------------------------
		
		$toSave = IPSBWOptions::thaw( $this->memberData['tc_bwoptions'], 'twitter' );
		
		//-----------------------------------------
		// Loop and save... simple
		//-----------------------------------------
		
		foreach( array( 'tc_s_pic', 'tc_s_status', 'tc_s_aboutme', 'tc_s_bgimg', 'tc_si_status' ) as $field )
		{
			$toSave[ $field ] = intval( $this->request[ $field ] );
		}
		
		$this->memberData['tc_bwoptions'] = IPSBWOptions::freeze( $toSave, 'twitter' );		
		$return = IPSMember::save( $this->memberData['member_id'], array( 'extendedProfile' => array( 'tc_bwoptions' => $this->memberData['tc_bwoptions'] ) ) );
		
		//-----------------------------------------
		// Now sync
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/twitter/connect.php', 'twitter_connect' );
		$twitter	 = new $classToLoad( $this->registry, $this->memberData['twitter_token'], $this->memberData['twitter_secret'] );
		
		try
		{
			$twitter->syncMember( $this->memberData );
		}
		catch( Exception $error )
		{
			$msg = $error->getMessage();
			
			switch( $msg )
			{
				case 'NOT_LINKED':
				case 'NO_MEMBER':
				break;
			}
		}
		
		return TRUE;
	}
	
	/**
	 * UserCP Save Form: Facebook
	 *
	 * @return	array	Errors
	 */
	public function saveFacebook()
	{
		if( !IPSLib::fbc_enabled() )
		{		
			$this->registry->getClass('output')->showError( 'fbc_disabled', 1005 );
		}
		
		//-----------------------------------------
		// Data
		//-----------------------------------------
		
		$toSave = IPSBWOptions::thaw( $this->memberData['members_bitoptions'], 'members' );
		
		//-----------------------------------------
		// Loop and save... simple
		//-----------------------------------------
		
		foreach( array( 'fbc_s_pic', 'fbc_s_status', 'fbc_s_aboutme', 'fbc_si_status' ) as $field )
		{
			$toSave[ $field ] = intval( $this->request[ $field ] );
		}
		
		$this->memberData['fb_bwoptions'] = IPSBWOptions::freeze( $toSave, 'facebook' );
		IPSMember::save( $this->memberData['member_id'], array( 'extendedProfile' => array( 'fb_bwoptions' => $this->memberData['fb_bwoptions'] ) ) );
		
		//-----------------------------------------
		// Now sync
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/facebook/connect.php', 'facebook_connect' );
		$facebook    = new $classToLoad( $this->registry );
		
		try
		{
			$facebook->syncMember( $this->memberData );
		}
		catch( Exception $error )
		{
			$msg = $error->getMessage();
			
			switch( $msg )
			{
				case 'NOT_LINKED':
				case 'NO_MEMBER':
				break;
			}
		}
		
		return TRUE;
	}
	
	/**
	 * UserCP Save Form: Ignore Users
	 *
	 * @return	array	Errors
	 */
	public function saveIgnoredUsers()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$newName = $this->request['newbox_1'];
		$dnvs    = intval( $this->request['donot_view_sigs'] );
		
		IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'view_sigs' => ( $dnvs ) ? 0 : 1 ) ) );
		
		if ( trim( $newName ) && $_POST['newbox_1'] != $this->lang->words['ucp_members_name'] )
		{	
			//-----------------------------------------
			// Load
			//-----------------------------------------
			
			$member = IPSMember::load( $newName, 'core', 'displayname' );
			
			if ( ! $member['member_id'] )
			{
				return array( 0 => $this->lang->words['ignoreuser_nomem'] );
			}
			
			if ( $member['member_id'] == $this->memberData['member_id'] )
			{
				return array( 0 => $this->lang->words['ignoreuser_cannot'] );
			}
			
			//-----------------------------------------
			// Already ignoring?
			//-----------------------------------------
			
			$ignoreMe = $this->DB->buildAndFetch( array( 
														'select' => '*',
														'from'   => 'ignored_users',
														'where'  => 'ignore_owner_id=' . $this->memberData['member_id'] . ' AND ignore_ignore_id=' . $member['member_id'] 
												)	 );
			
			if ( $ignoreMe['ignore_id'] )
			{
				return array( 0 => $this->lang->words['ignoreuser_already'] );
			}
			
			//-----------------------------------------
			// Can we ignore them?
			//-----------------------------------------
			
			if ( $member['_canBeIgnored'] !== TRUE )
			{
				return array( 0 => $this->lang->words['ignoreuser_cannot'] );
		 	}
	
			//-----------------------------------------
			// Add it
			//-----------------------------------------
	
			$this->DB->insert( 'ignored_users', array( 
														'ignore_owner_id'	=> $this->memberData['member_id'],
														'ignore_ignore_id'	=> $member['member_id'],
														'ignore_messages'	=> !empty( $this->request['ignore_messages'] ) ? 1 : 0,
														'ignore_topics'		=> !empty( $this->request['ignore_topics'] ) ? 1 : 0,
														'ignore_signatures'	=> !empty( $this->request['ignore_signatures'] ) ? 1 : 0,
														'ignore_chats'		=> !empty( $this->request['ignore_chats'] ) ? 1 : 0,
													) 
							);
							
			/* Rebuild cache */
			IPSMember::rebuildIgnoredUsersCache( $this->memberData );
		}
		
		return TRUE;
	}
	
	
	/**
	 * UserCP Save Form: Signature
	 *
	 * @return	array	Errors
	 */
	public function saveSignature()
	{
		/* Load editor stuff */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$this->editor = new $classToLoad();
		
		//-----------------------------------------
		// Check to make sure that we can edit profiles..
		//-----------------------------------------

		$sig_restrictions	= explode( ':', $this->memberData['g_signature_limits'] );
		
		if ( ! $this->memberData['g_edit_profile'] OR ( $sig_restrictions[0] AND ! $this->memberData['g_sig_unit'] ) )
		{
			$this->registry->getClass('output')->showError( 'members_profile_disabled', 1028, null, null, 403 );
		}

		//-----------------------------------------
		// Post process the editor
		// Now we have safe HTML and bbcode
		//-----------------------------------------
		
		$signature = $this->editor->process( $_POST['Post'] );
		
		//-----------------------------------------
		// Parse post
		//-----------------------------------------
		
		IPSText::getTextClass( 'bbcode' )->parse_smilies    = 0;
		IPSText::getTextClass( 'bbcode' )->parse_html       = $this->memberData['g_dohtml'];
		IPSText::getTextClass( 'bbcode' )->parse_bbcode     = 1;
		IPSText::getTextClass( 'bbcode' )->parsing_section	= 'signatures';

		$signature		= IPSText::getTextClass('bbcode')->preDbParse( $signature );
						
		$testSignature	= IPSText::getTextClass('bbcode')->preDisplayParse( $signature );
				
		if ( IPSText::getTextClass( 'bbcode' )->error )
		{
			$this->lang->loadLanguageFile( array( 'public_post' ), 'forums' );
			
			$this->registry->getClass('output')->showError( IPSText::getTextClass( 'bbcode' )->error, 10210 );
		}
		
		//-----------------------------------------
		// Signature restrictions...
		//-----------------------------------------
		
		$sig_errors	= array();
		
		//-----------------------------------------
		// Max number of images...
		//-----------------------------------------
		
		if( isset($sig_restrictions[1]) and $sig_restrictions[1] !== '' )
		{
			if( substr_count( strtolower($signature), "[img]" ) > $sig_restrictions[1] )
			{
				$sig_errors[] = sprintf( $this->lang->words['sig_toomanyimages'], $sig_restrictions[1] );
			}
		}
		
		//-----------------------------------------
		// Max number of urls...
		//-----------------------------------------
				
		if( isset($sig_restrictions[4]) and $sig_restrictions[4] !== '' )
		{
			if( substr_count( strtolower($signature), "[url" ) > $sig_restrictions[4] )
			{
				$sig_errors[] = sprintf( $this->lang->words['sig_toomanyurls'], $sig_restrictions[4] );
			}
			else
			{
				preg_match_all( '#(^|\s|>)((http|https|news|ftp)://\w+[^\s\[\]\<]+)#is', $signature, $matches );
				
				if( count($matches[1]) > $sig_restrictions[4] )
				{
					$sig_errors[] = sprintf( $this->lang->words['sig_toomanyurls'], $sig_restrictions[4] );
				}
			}
		}
		
		//-----------------------------------------
		// Max number of lines of text...
		//-----------------------------------------
						
		if( isset($sig_restrictions[5]) and $sig_restrictions[5] !== '' )
		{
			$this->settings['signature_line_length']	= $this->settings['signature_line_length'] > 0 ? $this->settings['signature_line_length'] : 200;

			$testSig	= wordwrap( $signature, $this->settings['signature_line_length'], '<br />' );
			
			// http://community.invisionpower.com/tracker/issue-35105-signature-restriction-minor-bug
			$testSig	= preg_replace( '#^\s*(<br />)+#i', '', $testSig );
			$testSig	= preg_replace( '#(<br />)+?\s*$#i', '', $testSig );
			
			if( substr_count( $testSig, "<br />" ) >= $sig_restrictions[5] )
			{
				$sig_errors[] = sprintf( $this->lang->words['sig_toomanylines'], $sig_restrictions[5] );
			}
		}
		
		//-----------------------------------------
		// Now the crappy part..
		//-----------------------------------------
				
		if( isset($sig_restrictions[2]) and $sig_restrictions[2] !== '' AND isset($sig_restrictions[3]) and $sig_restrictions[3] !== '' )
		{
			preg_match_all( '/\[img\](.+?)\[\/img\]/i', $signature, $allImages );

			if( count($allImages[1]) )
			{
				foreach( $allImages[1] as $foundImage )
				{
					$imageProperties = @getimagesize( $foundImage );
					
					if( is_array($imageProperties) AND count($imageProperties) )
					{
						if( $imageProperties[0] > $sig_restrictions[2] OR $imageProperties[1] > $sig_restrictions[3] )
						{
							$sig_errors[] = sprintf( $this->lang->words['sig_imagetoobig'], $foundImage, $sig_restrictions[2], $sig_restrictions[3] );
						}
					}
				}
			}
		}
		
		if( count($sig_errors) )
		{
			$this->registry->getClass('output')->showError( implode( '<br />', $sig_errors ), 10211 );
		}
		
		//-----------------------------------------
		// Write it to the DB.
		//-----------------------------------------
		
		IPSMember::save( $this->memberData['member_id'], array( 'extendedProfile' => array( 'signature' => $signature ) ) );
		
		/* Update cache */
		IPSContentCache::update( $this->memberData['member_id'], 'sig', $testSignature );
		
		return TRUE;
	}
	
	
	/**
	 * UserCP Save Form: Profile Info
	 *
	 * @return	array	Errors
	 */
	public function saveProfileInfo()
	{
		/* Load editor stuff */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$this->editor = new $classToLoad();
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$pp_setting_moderate_comments = intval( $this->request['pp_setting_moderate_comments'] );
		$pp_setting_moderate_friends  = intval( $this->request['pp_setting_moderate_friends'] );
		$pp_setting_count_visitors    = intval( $this->request['pp_setting_count_visitors'] );
		$pp_setting_count_comments    = intval( $this->request['pp_setting_count_comments'] );
		$pp_setting_count_friends     = intval( $this->request['pp_setting_count_friends'] );
		
		//-----------------------------------------
		// Check to make sure that we can edit profiles..
		//-----------------------------------------
		
		if ( ! $this->memberData['g_edit_profile'] )
		{
			$this->registry->getClass('output')->showError( 'members_profile_disabled', 10214, null, null, 403 );
		}
		
		//-----------------------------------------
		// make sure that either we entered
		// all calendar fields, or we left them
		// all blank
		//-----------------------------------------
		
		$c_cnt = 0;
		
		foreach ( array('day','month','year') as $v )
		{
			if ( $this->request[ $v ] )
			{
				$c_cnt++;
			}
		}
		
		if( $c_cnt > 0 && $c_cnt < 2 )
		{
			$this->registry->getClass('output')->showError( 'member_bad_birthday', 10215 );
		}
		else if( $c_cnt > 0 )
		{
			//-----------------------------------------
			// Make sure it's a legal date
			//-----------------------------------------
			
			$_year = $this->request['year'] ? $this->request['year'] : 1999;
			
			if ( ! checkdate( $this->request['month'], $this->request['day'], $_year ) )
			{
				$this->registry->getClass('output')->showError( 'member_bad_birthday', 10216 );
			}
		}

		//-----------------------------------------
		// Start off our array
		//-----------------------------------------
		
		$core = array( 'bday_day'    => $this->request['day'],
					   'bday_month'  => $this->request['month'],
					   'bday_year'   => $this->request['year'],
					  );

		$extendedProfile = array( 'pp_setting_moderate_comments' => $pp_setting_moderate_comments,
								  'pp_setting_moderate_friends'  => $pp_setting_moderate_friends,
								  'pp_setting_count_visitors'    => $pp_setting_count_visitors,
								  'pp_setting_count_comments'    => $pp_setting_count_comments,
								  'pp_setting_count_friends'     => $pp_setting_count_friends );
		
		//-----------------------------------------
		// check to see if we can enter a member title
		// and if one is entered, update it.
		//-----------------------------------------
		
		if( isset( $this->request['member_title'] ) and ( $this->settings['post_titlechange'] ) and ( $this->memberData['posts'] >= $this->settings['post_titlechange']) )
		{
			$core['title'] = IPSText::getTextClass('bbcode')->stripBadWords( $this->request['member_title'] );
		}
		
		//-----------------------------------------
		// Custom profile field stuff
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/customfields/profileFields.php', 'customProfileFields' );
		$fields      = new $classToLoad();
		
		$fields->member_data = $this->member->fetchMemberData();
		$fields->initData( 'edit' );
		/* Use $_POST and not ipsRegistry::$request as the custom profile field kernel class has its own cleaning routines for saving and showing
		   which means we end up with double & -> &amp; conversion (&amp;lt;, etc) */
		$fields->parseToSave( $_POST );
		
		if( $fields->error_messages )
		{
			return $fields->error_messages;
		}
		
		/* Check the website url field */
		$website_field = $fields->getFieldIDByKey( 'website' );
		
		if( $website_field && $fields->out_fields[ 'field_' . $website_field ] )
		{
			if( stristr( $fields->out_fields[ 'field_' . $website_field ], 'http://' ) === FALSE && stristr( $fields->out_fields[ 'field_' . $website_field ], 'https://' ) === FALSE )
			{
				$fields->out_fields[ 'field_' . $website_field ] = 'http://' . $fields->out_fields[ 'field_' . $website_field ];
			}
		}

		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( count( $fields->error_fields['empty'] ) )
		{
			$this->registry->getClass('output')->showError( array( 'customfields_empty', $fields->error_fields['empty'][0]['pf_title'] ), 10217 );
		}
		
		if ( count( $fields->error_fields['invalid'] ) )
		{
			$this->registry->getClass('output')->showError( array( 'customfields_invalid', $fields->error_fields['invalid'][0]['pf_title'] ), 10218 );
		}
		
		if ( count( $fields->error_fields['toobig'] ) )
		{
			$this->registry->getClass('output')->showError( array( 'customfields_toobig', $fields->error_fields['toobig'][0]['pf_title'] ), 10219 );
		}
		
		/* About me */	
		$extendedProfile['pp_about_me'] = $this->editor->process( $_POST['Post'] );
		
		/* Open ID & times*/
		$timeZone    = IPSText::alphanumericalClean( $this->request['timeZone'], '+.' );
		$dst_correct = intval( $this->request['dst_correct'] );
		
		/* Add into array */
		$core['time_offset']      = $timeZone;
		$core['dst_in_use']       = $this->settings['time_dst_auto_correction'] ? ( ( $this->request['dstOption'] AND intval($this->request['dstCheck']) == 0 ) ? intval($this->request['dstOption']) : 0 ) : $this->memberData['dst_in_use'];
		$core['members_auto_dst'] = $this->settings['time_dst_auto_correction'] ? intval($this->request['dstCheck']) : $this->memberData['members_auto_dst'];
		
		//-----------------------------------------
		// Update the DB
		//-----------------------------------------
		
		IPSMember::save( $this->memberData['member_id'], array( 'core'            => $core,
													 		    'customFields'    => $fields->out_fields,
													 		    'extendedProfile' => $extendedProfile ) );

		//-----------------------------------------
		// Update birthdays cache if user set to today
		// or if birthday was today but isn't now
		//-----------------------------------------
		
		if( $core['bday_month'] == date('m') AND $core['bday_day'] == date('d') )
		{
			$this->cache->rebuildCache( 'birthdays', 'calendar' );
		}
		else if( $this->memberData['bday_month'] == date('m') AND $this->memberData['bday_day'] == date('d') AND ( $core['bday_month'] != date('m') OR $core['bday_day'] != date('d') ) )
		{
			$this->cache->rebuildCache( 'birthdays', 'calendar' );
		}

		return TRUE;
	}
	
	/**
	 * UserCP Save Form: Notifications config
	 *
	 * @return	boolean		Successful
	 */
	public function saveFormNotifications()
	{
		//-----------------------------------------
		// Notifications library
		//-----------------------------------------
		
		$classToLoad		= IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
		$notifyLibrary		= new $classToLoad( $this->registry );
		$notifyLibrary->setMember( $this->memberData );

		//-----------------------------------------
		// Show the form
		//-----------------------------------------
		
		$_basicOptions		= array( array( 'email', $this->lang->words['notopt__email'] ), array( 'inline', $this->lang->words['notopt__inline'] ), array( 'mobile' => $this->lang->words['notopt__mobile'] ) );
		$_configOptions		= $notifyLibrary->getNotificationData();
		$_notifyConfig		= $notifyLibrary->getMemberNotificationConfig( $this->memberData );
		$_defaultConfig		= $notifyLibrary->getDefaultNotificationConfig();
		$_saveConfig		= array();

		foreach( $_configOptions as $option )
		{
			$_saveConfig[ $option['key'] ]						= array();
			$_saveConfig[ $option['key'] ]['selected']			= array();
			
			//-----------------------------------------
			// Loop through and mark what we selected.
			// Do not allow changing of stuff from disable_override
			//	and disabled, however
			//-----------------------------------------
			
			if( is_array($this->request['config_' . $option['key'] ]) AND count($this->request['config_' . $option['key'] ]) )
			{
				foreach( $this->request['config_' . $option['key'] ] as $_selected )
				{
					if( !is_array($_defaultConfig[ $option['key'] ]['disabled']) OR !in_array( $_selected, $_defaultConfig[ $option['key'] ]['disabled'] ) )
					{
						$_saveConfig[ $option['key'] ]['selected'][]	= $_selected;
					}
				}
			}
			
			if( $_defaultConfig[ $option['key'] ]['disable_override'] )
			{
				$_saveConfig[ $option['key'] ]['selected']	= $_defaultConfig[ $option['key'] ]['selected'];
			}
		}

		//-----------------------------------------
		// Save
		//-----------------------------------------
		
		IPSMember::packMemberCache( $this->memberData['member_id'], array( 'notifications' => $_saveConfig, 'show_notification_popup' => intval($this->request['show_notification_popup']) ), $this->memberData['members_cache'] );

		//-----------------------------------------
		// Topic preferences
		//-----------------------------------------
		
		$_trackChoice	= '';
		
		if ( $this->request['auto_track'] )
		{
 			if ( in_array( $this->request['trackchoice'], array( 'none', 'immediate', 'offline', 'daily', 'weekly' ) ) )
 			{
 				$_trackChoice = $this->request['trackchoice'];
 			}
 		}
 		
 		//-----------------------------------------
 		// Profile preferences
 		//-----------------------------------------

		IPSMember::save( $this->memberData['member_id'], array( 'core' => array(  
													   							  'allow_admin_mails'	=> intval( $this->request['admin_send'] ),
																				  'auto_track'			=> $_trackChoice
																				) ) );

		return TRUE;
	}
	/**
	 * UserCP Save Form: Password
	 *
	 * @param	array	Array of member / core_sys_login information (if we're editing)
	 * @return	mixed	Array of errors / boolean true
	 */
	public function saveFormPassword( $member=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$cur_pass = trim($this->request['current_pass']);
 		$new_pass = trim($this->request['new_pass_1']);
 		$chk_pass = trim($this->request['new_pass_2']);
		$isRemote = ( ! $this->memberData['bw_local_password_set'] AND $this->memberData['members_created_remote'] ) ? true : false;
		
		//-----------------------------------------
		// Checks...
		//-----------------------------------------

		if( $this->memberData['g_access_cp'] )
		{
			return array( 0 => $this->lang->words['admin_emailpassword'] );
		}

		if ( $isRemote === false AND ( ! $_POST['current_pass'] OR ( empty($new_pass) ) or ( empty($chk_pass) ) ) )
 		{
			return array( 0 => $this->lang->words['complete_entire_form'] );
 		}

 		//-----------------------------------------
 		// Do the passwords actually match?
 		//-----------------------------------------

 		if ( $new_pass != $chk_pass )
 		{
 			return array( 0 => $this->lang->words['passwords_not_matchy'] );
 		}

 		//-----------------------------------------
 		// Check password...
 		//-----------------------------------------
		
		if ( $isRemote === false )
		{
			if ( $this->_checkPassword( $cur_pass ) !== TRUE )
			{
				return array( 0 => $this->lang->words['current_pw_bad'] );
			}
		}
		else
		{
			/* This is INIT in _checkPassword */
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/handlers/han_login.php', 'han_login' );
			$this->han_login   = new $classToLoad( $this->registry );
    		$this->han_login->init();
    	}

 		//-----------------------------------------
 		// Create new password...
 		//-----------------------------------------

 		$md5_pass = md5($new_pass);

        //-----------------------------------------
    	// han_login was loaded during check_password
    	//-----------------------------------------

    	$this->han_login->changePass( $this->memberData['email'], $md5_pass, $new_pass, $this->memberData );

    	if ( $this->han_login->return_code AND $this->han_login->return_code != 'METHOD_NOT_DEFINED' AND $this->han_login->return_code != 'SUCCESS' )
    	{
			return array( 0 => $this->lang->words['hanlogin_pw_failed'] );
    	}

 		//-----------------------------------------
 		// Update the DB
 		//-----------------------------------------

 		IPSMember::updatePassword( $this->memberData['email'], $md5_pass );

 		IPSLib::runMemberSync( 'onPassChange', $this->memberData['member_id'], $new_pass );

 		//-----------------------------------------
 		// Update members log in key...
 		//-----------------------------------------

 		$key  = IPSMember::generateAutoLoginKey();

		IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'member_login_key' => $key, 'bw_local_password_set' => 1 ) ) );

		$this->ok_message = $this->lang->words['pw_change_successful'];

 		return TRUE;
	}

	/**
	 * UserCP Save Form: Display Name
	 *
	 * @return	mixed	Array of errors / boolean true
	 */
	public function saveFormDisplayname()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$members_display_name  = trim($this->request['displayName']);
		$password_check        = trim( $this->request['displayPassword'] );

		//-----------------------------------------
		// Check for blanks...
		//-----------------------------------------

		if ( ! $members_display_name OR ( ! $this->_isFBUser AND ! $password_check ) )
		{
			return array( 0 => $this->lang->words['complete_entire_form'] );
		}

		//-----------------------------------------
		// Check password
		//-----------------------------------------

		if ( ! $this->_isFBUser )
		{
			if ( $this->_checkPassword( $password_check ) === FALSE )
			{
				return array( 0 => $this->lang->words['current_pw_bad'] );
			}
		}

		try
		{
			if ( IPSMember::getFunction()->updateName( $this->memberData['member_id'], $members_display_name, 'members_display_name' ) === TRUE )
			{
				$this->cache->rebuildCache( 'stats', 'global' );

				return $this->showFormDisplayname( '', $this->lang->words['dname_change_ok'] );
			}
			else
			{
				# We should absolutely never get here. So this is a fail-safe, really to
				# prevent a "false" positive outcome for the end-user
				return array( 0 => $this->lang->words['name_taken_change'] );
			}
		}
		catch( Exception $error )
		{
			switch( $error->getMessage() )
			{
				case 'NO_MORE_CHANGES':
					return array( 0 => $this->lang->words['name_change_no_more'] );
				break;
				case 'NO_USER':
					return array( 0 => $this->lang->words['name_change_noload'] );
				break;
				case 'NO_PERMISSION':
					return array( 0 => $this->lang->words['name_change_noperm'] );
				case 'NO_NAME':
					return array( 0 => sprintf( $this->lang->words['name_change_tooshort'], $this->settings['max_user_name_length'] ) );
				break;
				case 'TOO_LONG':
					return array( 0 => sprintf( $this->lang->words['name_change_tooshort'], $this->settings['max_user_name_length'] ) );
				break;
				case 'ILLEGAL_CHARS':
					return array( 0 => $this->lang->words['name_change_illegal'] );
				break;
				case 'USER_NAME_EXISTS':
					return array( 0 => $this->lang->words['name_change_taken'] );
				break;
				default:
					return array( 0 => $error->getMessage() );
				break;
			}
		}

		return TRUE;
	}

	/**
	 * UserCP Save Form: Email Address
	 *
	 * @return	mixed		Array of errors / boolean true
	 */
	public function saveFormEmailPassword()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$_emailOne         = strtolower( trim($this->request['in_email_1']) );
		$_emailTwo         = strtolower( trim($this->request['in_email_2']) );
		$cur_pass = trim($this->request['current_pass']);
 		$new_pass = trim($this->request['new_pass_1']);
 		$chk_pass = trim($this->request['new_pass_2']);
		$isRemote = ( ! $this->memberData['bw_local_password_set'] AND $this->memberData['members_created_remote'] ) ? true : false;
		
		if ( $_emailOne or $_emailTwo )
		{
			//-----------------------------------------
			// Do not allow validating members to change
			// email when admin validation is on
			// @see	http://community.invisionpower.com/tracker/issue-19964-loophole-in-registration-procedure/
			//-----------------------------------------
			
			if( $this->memberData['member_group_id'] == $this->settings['auth_group'] AND in_array( $this->settings['reg_auth_type'], array( 'admin', 'admin_user' ) ) )
			{
				$this->registry->output->showError( $this->lang->words['admin_val_no_email_chg'], 10190 );
			}
			
			//-----------------------------------------
			// Check input
			//-----------------------------------------
	
			if( $this->memberData['g_access_cp'] )
			{
				return array( 0 => $this->lang->words['admin_emailpassword'] );
			}
	
			if ( ! $_POST['in_email_1'] OR ! $_POST['in_email_2'] )
			{
				return array( 0 => $this->lang->words['complete_entire_form'] );
			}
	
			//-----------------------------------------
			// Check password...
			//-----------------------------------------
	
			if ( ! $this->_isFBUser )
			{
				if ( $this->_checkPassword( $this->request['password'] ) === FALSE )
				{
					return array( 0 => $this->lang->words['current_pw_bad'] );
				}
			}
	
			//-----------------------------------------
			// Test email addresses
			//-----------------------------------------
	
			if ( $_emailOne != $_emailTwo )
			{
				return array( 0 => $this->lang->words['emails_no_matchy'] );
			}
	
			if ( IPSText::checkEmailAddress( $_emailOne ) !== TRUE )
			{
				return array( 0 => $this->lang->words['email_not_valid'] );
			}
	
			//-----------------------------------------
			// Is this email addy taken?
			//-----------------------------------------
	
			if ( IPSMember::checkByEmail( $_emailOne ) == TRUE )
			{
				return array( 0 => $this->lang->words['email_is_taken'] );
			}
	
			//-----------------------------------------
			// Load ban filters
			//-----------------------------------------
			$banfilters = array();
			$this->DB->build( array( 'select' => '*', 'from' => 'banfilters' ) );
			$this->DB->execute();
	
			while( $r = $this->DB->fetch() )
			{
				$banfilters[ $r['ban_type'] ][] = $r['ban_content'];
			}
	
			//-----------------------------------------
			// Check in banned list
			//-----------------------------------------
	
			if ( isset($banfilters['email']) AND is_array( $banfilters['email'] ) and count( $banfilters['email'] ) )
			{
				foreach ( $banfilters['email'] as $email )
				{
					$email = str_replace( '\*', '.*' ,  preg_quote($email, "/") );
	
					if ( preg_match( "/^{$email}$/i", $_emailOne ) )
					{
						return array( 0 => $this->lang->words['email_is_taken'] );
					}
				}
			}
	
			//-----------------------------------------
			// Load handler...
			//-----------------------------------------
	
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/handlers/han_login.php', 'han_login' );
			$this->han_login   = new $classToLoad( $this->registry );
			$this->han_login->init();
	
			if ( $this->han_login->emailExistsCheck( $_emailOne ) !== FALSE )
			{
				return array( 0 => $this->lang->words['email_is_taken'] );
			}
	
			$this->han_login->changeEmail( $this->memberData['email'], $_emailOne, $this->memberData );
	
			if ( $this->han_login->return_code AND $this->han_login->return_code != 'METHOD_NOT_DEFINED' AND $this->han_login->return_code != 'SUCCESS' )
			{
			 	return array( 0 => $this->lang->words['email_is_taken'] );
			}
	
			//-----------------------------------------
			// Want a new validation? NON ADMINS ONLY
			//-----------------------------------------
	
			if ( $this->settings['reg_auth_type'] AND !$this->memberData['g_access_cp'] )
			{
				//-----------------------------------------
				// Remove any existing entries
				//-----------------------------------------
				
				$_previous	= $this->DB->buildAndFetch( array( 'select' => 'prev_email, real_group', 'from' => 'validating', 'where' => "member_id={$this->memberData['member_id']} AND email_chg=1" ) );
				
				if( $_previous['prev_email'] )
				{
					$this->DB->delete( 'validating', "member_id={$this->memberData['member_id']} AND email_chg=1" );
					
					$this->memberData['email']				= $_previous['prev_email'];
					$this->memberData['member_group_id']	= $_previous['real_group'];
				}
				
				$validate_key = md5( IPSMember::makePassword() . time() );
	
				//-----------------------------------------
				// Update the new email, but enter a validation key
				// and put the member in "awaiting authorisation"
				// and send an email..
				//-----------------------------------------
	
				$db_str = array(
								'vid'         => $validate_key,
								'member_id'   => $this->memberData['member_id'],
								'temp_group'  => $this->settings['auth_group'],
								'entry_date'  => time(),
								'coppa_user'  => 0,
								'email_chg'   => 1,
								'ip_address'  => $this->member->ip_address,
								'prev_email'  => $this->memberData['email'],
							   );
	
				if ( $this->memberData['member_group_id'] != $this->settings['auth_group'] )
				{
					$db_str['real_group'] = $this->memberData['member_group_id'];
				}
	
				$this->DB->insert( 'validating', $db_str );
				
				IPSLib::runMemberSync( 'onEmailChange', $this->memberData['member_id'], strtolower( $_emailOne ), $this->memberData['email'] );
	
				IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'member_group_id' => $this->settings['auth_group'],
																								  'email'           => $_emailOne ) ) );
	
				//-----------------------------------------
				// Update their session with the new member group
				//-----------------------------------------
	
				if ( $this->member->session_id  )
				{
					$this->member->sessionClass()->convertMemberToGuest();
				}
	
				//-----------------------------------------
				// Kill the cookies to stop auto log in
				//-----------------------------------------
	
				IPSCookie::set( 'pass_hash'  , '-1', 0 );
				IPSCookie::set( 'member_id'  , '-1', 0 );
				IPSCookie::set( 'session_id' , '-1', 0 );
	
				//-----------------------------------------
				// Dispatch the mail, and return to the activate form.
				//-----------------------------------------
	
				IPSText::getTextClass( 'email' )->getTemplate("newemail");
	
				IPSText::getTextClass( 'email' )->buildMessage( array(
													'NAME'         => $this->memberData['members_display_name'],
													'THE_LINK'     => $this->settings['base_url']."app=core&module=global&section=register&do=auto_validate&type=newemail&uid=".$this->memberData['member_id']."&aid=".$validate_key,
													'ID'           => $this->memberData['member_id'],
													'MAN_LINK'     => $this->settings['base_url']."app=core&module=global&section=register&do=07",
													'CODE'         => $validate_key,
												  ) );
	
				IPSText::getTextClass( 'email' )->subject = $this->lang->words['lp_subject'].' '.$this->settings['board_name'];
				IPSText::getTextClass( 'email' )->to      = $_emailOne;
	
				IPSText::getTextClass( 'email' )->sendMail();
	
				$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . 'app=core&amp;module=global&amp;section=register&amp;do=07' );
			}
			else
			{
				//-----------------------------------------
				// No authorisation needed, change email addy and return
				//-----------------------------------------
				
				IPSLib::runMemberSync( 'onEmailChange', $this->memberData['member_id'], strtolower( $_emailOne ), $this->memberData['email'] );
	
				IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'email' => $_emailOne ) ) );
	
				//-----------------------------------------
				// Add to OK message
				//-----------------------------------------
	
				$this->ok_message = $this->lang->words['ok_email_changed'];
			}
		}
		else if ( $cur_pass OR $new_pass )
		{
			if( $this->memberData['g_access_cp'] )
			{
				return array( 0 => $this->lang->words['admin_emailpassword'] );
			}
	
			if ( $isRemote === false AND ( ! $_POST['current_pass'] OR ( empty($new_pass) ) or ( empty($chk_pass) ) ) )
	 		{
				return array( 0 => $this->lang->words['complete_entire_form'] );
	 		}
	
	 		//-----------------------------------------
	 		// Do the passwords actually match?
	 		//-----------------------------------------
	
	 		if ( $new_pass != $chk_pass )
	 		{
	 			return array( 0 => $this->lang->words['passwords_not_matchy'] );
	 		}
	
	 		//-----------------------------------------
	 		// Check password...
	 		//-----------------------------------------
			
			if ( $isRemote === false )
			{
				if ( $this->_checkPassword( $cur_pass ) !== TRUE )
				{
					return array( 0 => $this->lang->words['current_pw_bad'] );
				}
			}
			else
			{
				/* This is INIT in _checkPassword */
				$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/handlers/han_login.php', 'han_login' );
				$this->han_login   = new $classToLoad( $this->registry );
	    		$this->han_login->init();
	    	}
	
	 		//-----------------------------------------
	 		// Create new password...
	 		//-----------------------------------------
	
	 		$md5_pass = md5($new_pass);
	
	        //-----------------------------------------
	    	// han_login was loaded during check_password
	    	//-----------------------------------------
	
	    	$this->han_login->changePass( $this->memberData['email'], $md5_pass, $new_pass, $this->memberData );
	
	    	if ( $this->han_login->return_code AND $this->han_login->return_code != 'METHOD_NOT_DEFINED' AND $this->han_login->return_code != 'SUCCESS' )
	    	{
				return array( 0 => $this->lang->words['hanlogin_pw_failed'] );
	    	}
	
	 		//-----------------------------------------
	 		// Update the DB
	 		//-----------------------------------------
	
	 		IPSMember::updatePassword( $this->memberData['email'], $md5_pass );
	
	 		IPSLib::runMemberSync( 'onPassChange', $this->memberData['member_id'], $new_pass );
	
	 		//-----------------------------------------
	 		// Update members log in key...
	 		//-----------------------------------------
	
	 		$key  = IPSMember::generateAutoLoginKey();
	
			IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'member_login_key' => $key, 'bw_local_password_set' => 1 ) ) );
	
			$this->ok_message = $this->lang->words['pw_change_successful'];
		}
		
		return TRUE;
	}

	/**
	 * Password check
	 *
	 * @param	string		Plain Text Password
	 * @return	boolean		Password matched or not
	 */
	protected function _checkPassword( $password_check )
	{
		//-----------------------------------------
		// Ok, check password first
		//-----------------------------------------

		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/handlers/han_login.php', 'han_login' );
		$this->han_login   = new $classToLoad( $this->registry );
    	$this->han_login->init();

		//-----------------------------------------
		// Is this a username or email address?
		//-----------------------------------------

		$this->han_login->loginPasswordCheck( $this->memberData['name'], $this->memberData['email'], $password_check );

		if ( $this->han_login->return_code == 'SUCCESS' )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
}