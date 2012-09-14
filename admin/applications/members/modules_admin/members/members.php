<?php
/**
 * @file		members.php 	Provides methods to deal with the members management for administrators
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: AndyMillne $
 * @since		1st March 2002
 * $LastChangedDate: 2012-05-31 08:15:31 -0400 (Thu, 31 May 2012) $
 * @version		v3.3.3
 * $Revision: 10828 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}


/**
 *
 * @class		admin_members_members_members
 * @brief		Provides methods to deal with the members management for administrators
 */
class admin_members_members_members extends ipsCommand
{
	/**
	 * Skin object shortcut
	 *
	 * @var		$html
	 */
	public $html;
	
	/**
	 * String for the screen url bit
	 *
	 * @var		$form_code
	 */
	public $form_code    = '';
	
	/**
	 * String for the JS url bit
	 *
	 * @var		$form_code_js
	 */
	public $form_code_js = '';

	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		
		$this->html			= $this->registry->output->loadTemplate('cp_skin_member');
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=members&amp;section=members';
		$this->form_code_js	= $this->html->form_code_js	= 'module=members&section=members';
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_member' ), 'members' );

		///-----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'member_edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_edit' );
				$this->_memberDoEdit();
			break;

			case 'unsuspend':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_suspend' );
				$this->_memberUnsuspend();
			break;

			case 'add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_add' );
				$this->_memberAddForm();
			break;
			
			case 'doadd':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_add' );
				$this->_memberDoAdd();
			break;

			case 'doprune':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_prune' );
				$this->_memberDoPrune();
			break;
			
			case 'domove':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_move' );
				$this->_memberDoMove();
			break;
			
			case 'banmember':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_suspend' );
				$this->_memberSuspendStart();
			break;
			
			case 'ban_member':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_ban' );
				$this->_memberBanDo();
			break;
				
			case 'dobanmember':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_suspend' );
				$this->_memberSuspendDo();
			break;
			
			case 'toggleSpam':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_ban' );
				$this->_memberToggleSpam();
			break;
			
			case 'viewmember':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_edit' );
				$this->_memberView();
			break;

			case 'member_delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_delete' );
				$this->_memberDelete();
			break;
			
			case 'new_photo':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_photo' );
				$this->_memberNewPhoto();
			break;
						
			case 'member_login':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_login' );
				$this->_loginAsMember();
			break;

			case 'members_overview':
			case 'members_list':
			default:
				$this->_memberList();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Determines if we should show the admin restrictions form stuff
	 *
	 * @param	array		$member			Member information
	 * @param	array		$old_mgroups	Old member groups [primary and secondary]
	 * @return	@e boolean	When TRUE adds HTML to the output
	 * @author	Brandon Farber
	 */
	protected function _showAdminForm( $member, $old_mgroups )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------

		$groups			= array( $member['member_group_id'] );
		$old_mgroups	= is_array($old_mgroups) ? $old_mgroups : array();
		$is_admin		= false;
		$just_now		= false;
		$admins			= array();
		
		if( $member['mgroup_others'] )
		{
			$groups	= array_merge( $groups, explode( ',', IPSText::cleanPermString( $member['mgroup_others'] ) ) );
		}
		
		//-----------------------------------------
		// Are they an admin?
		//-----------------------------------------
		
		foreach( $groups as $group_id )
		{
			if( $this->caches['group_cache'][ $group_id ]['g_access_cp'] )
			{
				$is_admin				= true;
				$admins[ $group_id ]	= false;
			}
		}
		
		if( !$is_admin )
		{
			return false;
		}
		
		//-----------------------------------------
		// Were they before?
		//-----------------------------------------
		
		foreach( $admins as $admin_group_id => $restricted )
		{
			if( !in_array( $admin_group_id, $old_mgroups ) )
			{
				$just_now	= true;
			}
		}
		
		if( !$just_now )
		{
			return false;
		}
		
		//-----------------------------------------
		// Do they already have restrictions?
		//-----------------------------------------
		
		$test = $this->DB->buildAndFetch( array( 'select' => 'row_id', 'from' => 'admin_permission_rows', 'where' => "row_id_type='member' AND row_id=" . $member['member_id'] ) );
		
		if( $test['row_id'] )
		{
			return false;
		}
		
		//-----------------------------------------
		// Determine if they have group restrictions
		//-----------------------------------------

		$this->DB->build( array( 'select' => '*', 'from' => 'admin_permission_rows', 'where' => "row_id_type='group' AND row_id IN(" . implode( ',', array_keys( $admins ) ) . ")" ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$admins[ $r['row_id'] ] = true;
		}
		
		//-----------------------------------------
		// And show teh form.. o.O.o <-- three eyed monster from Lilo and Stitch
		//-----------------------------------------

		$this->registry->output->html .= $this->html->memberAdminConfirm( $member, $admins );
		
		return true;
	}
		
	/**
	 * Uploads a new photo for the member [process]
	 *
	 * @return	@e void
	 */
	protected function _memberNewPhoto()
	{
		if ( !$this->request['member_id'] )
		{
			$this->registry->output->showError( $this->lang->words['m_specify'], 11224 );
		}
		
		$member = IPSMember::load( $this->request['member_id'] );
		
		//-----------------------------------------
		// Allowed to upload pics for administrators?
		//-----------------------------------------
		
		if( $member['g_access_cp'] AND !$this->registry->getClass('class_permissions')->checkPermission( 'member_photo_admin' ) )
		{
			$this->registry->output->global_message = $this->lang->words['m_noupload'];
			$this->_memberView();
			return;
		}
		
		$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/member/photo.php', 'classes_member_photo' );
		$photos			= new $classToLoad( $this->registry );
		
		$status	= $photos->uploadPhoto( intval($this->request['member_id']) );

		if( $status['status'] == 'fail' )
		{
			switch( $status['error'] )
			{
				default:
				case 'upload_failed':
					$this->registry->output->showError( $this->lang->words['m_upfailed'], 11225 );
				break;
				
				case 'invalid_file_extension':
					$this->registry->output->showError( $this->lang->words['m_invfileext'], 11226 );
				break;
				
				case 'upload_to_big':
					$this->registry->output->showError( $this->lang->words['m_thatswhatshesaid'], 11227 );
				break;
			}
		}
		else
		{
			$bwOptions					= IPSBWOptions::thaw( $member['fb_bwoptions'], 'facebook' );
			$tcbwOptions				= IPSBWOptions::thaw( $member['tc_bwoptions'], 'twitter' );
			$bwOptions['fbc_s_pic']		= 0;
			$tcbwOptions['tc_s_pic']	= 0;
			
			IPSMember::save( $this->request['member_id'], array( 'extendedProfile' => array( 'pp_main_photo'   => $status['final_location'],
													  				   	 	'pp_main_width'		=> intval($status['final_width']),
																		   	'pp_main_height'	=> intval($status['final_height']),
																			'pp_thumb_photo'	=> $status['t_final_location'],
																			'pp_thumb_width'	=> intval($status['t_final_width']),
																			'pp_thumb_height'	=> intval($status['t_final_height']),
																			'pp_photo_type'		=> 'custom',
																			'pp_profile_update'  => IPS_UNIX_TIME_NOW,
																			'fb_photo'			=> '',
																			'fb_photo_thumb'	=> '',
																			'fb_bwoptions'		=> IPSBWOptions::freeze( $bwOptions, 'facebook' ),
																			'tc_photo'			=> '',
																			'tc_bwoptions'		=> IPSBWOptions::freeze( $tcbwOptions, 'twitter' ),
																		 ) ) );
			
			//-----------------------------------------
			// Redirect
			//-----------------------------------------
			
			$this->registry->output->global_message	= $this->lang->words['m_photoupdated'];
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=viewmember&amp;member_id=' . $this->request['member_id'] );
		}
	}
	
	/**
	 * View a member's details
	 *
	 * @return	@e void
	 * @todo 	[Future] Settings: joined, dst_in_use, coppa_user, auto_track, ignored_users, members_auto_dst, 
	 * 				 members_created_remote, members_profile_views, failed_logins, failed_login_count, fb_photo, fb_photo_thumb
	 */
	protected function _memberView()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$member_id	= intval( $this->request['member_id'] );
		$member		= array();
		$sidebar	= array();
		$blocks		= array();

		//-----------------------------------------
		// Get member data
		//-----------------------------------------
		
		$member = IPSMember::load( $member_id, 'all' );

		//-----------------------------------------
		// Allowed to ban administrators?
		//-----------------------------------------
		
		if( $member['member_id'] != $this->memberData['member_id'] AND $member['g_access_cp'] AND !$this->registry->getClass('class_permissions')->checkPermission( 'member_edit_admin') )
		{
			
			$this->registry->output->global_message = $this->lang->words['m_editadmin'];
			$this->_memberList();
			return;
		}

		$member['custom_fields'] = array();
		
		//-----------------------------------------
		// Just a safeguard to prevent admin mistake
		//-----------------------------------------
		
		if( !$member['member_group_id'] )
		{
			$member['member_group_id']	= $this->settings['member_group'];
		}

		//-----------------------------------------
		// Got a member?
		//-----------------------------------------
	
		if ( ! $member['member_id'] )
		{
			$this->registry->output->global_error = $this->lang->words['m_noid'];
			$this->_memberList();
			return;
		}
		
		//-----------------------------------------
		// Ok? Load interface and child classes
		//-----------------------------------------
		
		$tabsUsed	= 5;
		$firsttab   = false;
		
		IPSLib::loadInterface( 'admin/member_form.php' );
		
		foreach( IPSLib::getEnabledApplications() as $app_dir => $app_data )
		{
			if ( is_file( IPSLib::getAppDir(  $app_dir ) . '/extensions/admin/member_form.php' ) )
			{
				$_class		= IPSLib::loadLibrary( IPSLib::getAppDir( $app_dir ) . '/extensions/admin/member_form.php', 'admin_member_form__' . $app_dir, $app_dir );
				$_object	= new $_class( $this->registry );
				
				$sidebar[ $app_dir ] = $_object->getSidebarLinks( $member );
				
				$data = $_object->getDisplayContent( $member, $tabsUsed );
				$blocks['area'][ $app_dir ]  = $data['content'];
				$blocks['tabs'][ $app_dir ]  = $data['tabs'];
				
				$tabsUsed	= $data['tabsUsed'] ? ( $tabsUsed + $data['tabsUsed'] ) : ( $tabsUsed + 1 );
				
				if ( $this->request['_initTab'] == $app_dir )
				{
					$firsttab = $tabsUsed;
				}
			}
		}
		
		//-----------------------------------------
		// Format Member
		//-----------------------------------------

		$member['_joined']	= ipsRegistry::getClass( 'class_localization')->getDate( $member['joined'], 'LONG' );
		$member				= IPSMember::buildDisplayData( $member );

    	//-----------------------------------------
		// Editors
		//-----------------------------------------

		/* Load editor stuff */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$_editor = new $classToLoad();
		
		/* Signature editor */
		$sig_editor = $member['signature'];
		
		$_editor->setAllowBbcode( true );
		$_editor->setAllowSmilies( false );
		$_editor->setAllowHtml( $member['g_dohtml'] );
		$_editor->setContent( $sig_editor, 'signatures' );
		
		$member['signature_editor']	= $_editor->show( 'signature', array( 'noSmilies' => true, 'height' => 350 ) );
		
		/* About me editor */
		$ame_editor	= $member['pp_about_me'];
		
		$_editor->setAllowBbcode( true );
		$_editor->setAllowSmilies( true );
		$_editor->setAllowHtml( $member['g_dohtml'] );
		$_editor->setContent( $ame_editor, 'aboutme' );
		
		$member['aboutme_editor']	= $_editor->show( 'aboutme', array( 'height' => 350 ) );

    	//-----------------------------------------
		// Custom fields
		//-----------------------------------------
		
		$classToLoad   = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/customfields/profileFields.php', 'customProfileFields' );
		$custom_fields = new $classToLoad();
		
		$custom_fields->member_data = $member;
		$custom_fields->initData( 'edit' );
		$custom_fields->parseToEdit();
		
		$member['custom_fields'] = array();
		if ( count( $custom_fields->out_fields ) )
		{
			foreach( $custom_fields->out_fields as $id => $data )
	    	{
	    		if ( ! $data )
	    		{
	    			$data = $this->lang->words['gbl_no_info'];
	    		}
	    		
				$member['custom_fields'][ $id ] = array( 'name' => $custom_fields->field_names[ $id ], 'data' => $data );
	    	}
		}
	
		//-----------------------------------------
		// Notifications library
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
		$notifyLibrary	= new $classToLoad( $this->registry );
		$notifyLibrary->setMember( $member );
		
		$_basicOptions	= array( array( 'email', $this->lang->words['notopt__email'] ), array( 'inline', $this->lang->words['notopt__inline'] ), array( 'mobile', $this->lang->words['notopt__mobile'] ) );
		$_configOptions	= $notifyLibrary->getNotificationData( TRUE );
		$_notifyConfig	= $notifyLibrary->getMemberNotificationConfig( $member );
		$_defaultConfig	= $notifyLibrary->getDefaultNotificationConfig();
		$_formOptions	= array();
		
		foreach( $_configOptions as $option )
		{
			$_thisConfig	= $_notifyConfig[ $option['key'] ];
			
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

			$_formOptions[ $option['key'] ]['defaults']		= $_thisConfig['selected'];
			$_formOptions[ $option['key'] ]['disabled']		= 0;
			
			//-----------------------------------------
			// Don't allow member to configure
			// Still show, but disable on form
			//-----------------------------------------
			
			if( $_defaultConfig[ $option['key'] ]['disable_override'] )
			{
				$_formOptions[ $option['key'] ]['disabled']		= 1;
				$_formOptions[ $option['key'] ]['defaults']		= $_defaultConfig[ $option['key'] ]['selected'];
			}
		}
		
		//-----------------------------------------
		// Get it printed!
		//-----------------------------------------
		
		$this->registry->output->extra_nav[] = array( '', $this->lang->words['nav_view_mem'] . $member['members_display_name'] );

		$this->registry->output->html .= $this->html->member_view( $member, $blocks, $sidebar, $_formOptions );
	}
	
	/**
	 * Toggle member spam [process]
	 *
	 * @return	@e void
	 */
	protected function _memberToggleSpam()
	{
		/* INIT */
		$toSave = array();
		$this->request['member_id'] =  intval($this->request['member_id']);
		
		if ( !$this->request['member_id'] )
		{
			$this->registry->output->showError( $this->lang->words['m_specify'], 11228 );
		}
		
		$member = IPSMember::load( $this->request['member_id'] );

		if ( ! $member['member_id'] )
		{
			$this->registry->output->showError( $this->lang->words['m_noid'], 11229 );
		}
		
		//-----------------------------------------
		// Allowed to spam administrators?
		//-----------------------------------------
		
		if( $member['g_access_cp'] AND !$this->registry->getClass('class_permissions')->checkPermission( 'member_ban_admin') )
		{
			$this->registry->output->global_message = $this->lang->words['m_banadmin'];
			$this->_memberView();
			return;
		}
		
		/* Load mod lib */
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/moderate.php', 'moderatorLibrary', 'forums' );
		$this->modLibrary	=  new $classToLoad( $this->registry );
		
		/* Spam or not ? */
		if ( $member['bw_is_spammer'] )
		{
			$toSave['core']['bw_is_spammer']      = 0;
			$toSave['core']['restrict_post']      = 0;
			$toSave['core']['members_disable_pm'] = 0;
			
			/* Flag them as not a spammer */
			IPSMember::save( $member['member_id'], $toSave );
			
			/* Un-spammed ;) */
			IPSLib::runMemberSync( 'onUnSetAsSpammer', $member );
		}
		else
		{
			IPSMember::flagMemberAsSpammer( $member, $this->memberData );
		}
		
		//-----------------------------------------
		// Redirect
		//-----------------------------------------

		ipsRegistry::getClass('adminFunctions')->saveAdminLog(sprintf( $this->lang->words['t_log_spam'], $member['members_display_name'] ) );

		$this->registry->output->global_message	= $this->lang->words['t_log_spam'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=viewmember&amp;member_id=' . $member['member_id'] );
	}
	
	/**
	 * Ban a member [process]
	 *
	 * @return	@e void
	 */
	protected function _memberBanDo()
	{
		$this->request['member_id'] =  intval($this->request['member_id']);
		
		if ( !$this->request['member_id'] )
		{
			$this->registry->output->showError( $this->lang->words['m_specify'], 11228 );
		}
		
		$member = IPSMember::load( $this->request['member_id'] );
		
		if ( ! $member['member_id'] )
		{
			$this->registry->output->showError( $this->lang->words['m_noid'], 11229 );
		}
		
		//-----------------------------------------
		// Allowed to ban administrators?
		//-----------------------------------------
		
		if( $member['g_access_cp'] AND !$this->registry->getClass('class_permissions')->checkPermission( 'member_ban_admin') )
		{
			$this->registry->output->global_message = $this->lang->words['m_banadmin'];
			$this->_memberView();
			return;
		}
		
		//-----------------------------------------
		// Check ban settings...
		//-----------------------------------------

		$ban_filters 	= array( 'email' => array(), 'name' => array(), 'ip' => array() );
		$email_banned	= false;
		$ip_banned		= array();
		$name_banned	= false;
		
		//-----------------------------------------
		// Grab existing ban filters
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'banfilters' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$ban_filters[ $r['ban_type'] ][] = $r['ban_content'];
		}
		
		//-----------------------------------------
		// Check name and email address
		//-----------------------------------------
		
		if( in_array( $member['email'], $ban_filters['email'] ) )
		{
			$email_banned	= true;
		}
		
		if( in_array( $member['name'], $ban_filters['name'] ) )
		{
			$name_banned	= true;
		}
		
		if( $this->request['ban__email'] AND !$email_banned )
		{
			$this->DB->insert( 'banfilters', array( 'ban_type' => 'email', 'ban_content' => $member['email'], 'ban_date' => time() ) );
		}
		else if( !$this->request['ban__email'] AND $email_banned )
		{
			$this->DB->delete( 'banfilters', "ban_type='email' AND ban_content='{$member['email']}'" );
		}
		
		if( $this->request['ban__member'] AND !$member['member_banned'] )
		{
			IPSMember::save( $member['member_id'], array( 'core' => array( 'member_banned' => 1 ) ) );
		}
		else if( !$this->request['ban__member'] AND $member['member_banned'] )
		{
			IPSMember::save( $member['member_id'], array( 'core' => array( 'member_banned' => 0 ) ) );
		}
		
		if( $this->request['ban__name'] AND !$name_banned )
		{
			$this->DB->insert( 'banfilters', array( 'ban_type' => 'name', 'ban_content' => $member['name'], 'ban_date' => time() ) );
		}
		else if( !$this->request['ban__name'] AND $name_banned )
		{
			$this->DB->delete( 'banfilters', "ban_type='name' AND ban_content='{$member['name']}'" );
		}
				
		//-----------------------------------------
		// Retrieve IP addresses
		//-----------------------------------------
		
		$ip_addresses	= IPSMember::findIPAddresses( $member['member_id'] );

		//-----------------------------------------
		// What about IPs?
		//-----------------------------------------

		if( is_array($ip_addresses) AND count($ip_addresses) )
		{
			foreach( $ip_addresses as $ip_address => $count )
			{
				if( in_array( $ip_address, $ban_filters['ip'] ) )
				{
					if( !$this->request[ 'ban__ip_' . str_replace( '.', '_', $ip_address ) ] )
					{
						$this->DB->delete( 'banfilters', "ban_type='ip' AND ban_content='{$ip_address}'" );
					}
				}
				else
				{
					if( $this->request[ 'ban__ip_' . str_replace( '.', '_', $ip_address ) ] )
					{
						$this->DB->insert( 'banfilters', array( 'ban_type' => 'ip', 'ban_content' => $ip_address, 'ban_date' => time() ) );
					}
				}
			}
		}

		if( $this->request['ban__group'] AND $this->request['ban__group_change'] AND $this->request['ban__group'] != $member['member_group_id'] )
		{
			IPSMember::save( $member['member_id'], array( 'core' => array( 'member_group_id' => intval($this->request['ban__group']) ) ) );
			
			/* Password has been changed! */
			IPSLib::runMemberSync( 'onGroupChange', $member['member_id'], intval($this->request['ban__group']), $member['member_group_id'] );
		}
		
		/* Rebuild the cache */
		$this->cache->rebuildCache( 'banfilters', 'global' );
		
		//-----------------------------------------
		// Redirect
		//-----------------------------------------

		ipsRegistry::getClass('adminFunctions')->saveAdminLog(sprintf( $this->lang->words['m_bannedlog'], $member['members_display_name'] ) );

		$this->registry->output->global_message	= $this->lang->words['m_banned'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=viewmember&amp;member_id=' . $member['member_id'] );
	}
	
	/**
	 * Suspend a member [form/confirmation]
	 *
	 * @return	@e void
	 */
	protected function _memberSuspendStart()
	{
		$this->registry->output->extra_nav[] = array( '', $this->lang->words['m_suspend'] );
		
		if ( !$this->request['member_id'] )
		{
			$this->registry->output->showError( $this->lang->words['m_specify'], 11230 );
		}
		
		$member = IPSMember::load( intval($this->request['member_id']) );

		if ( ! $member['member_id'] )
		{
			$this->registry->output->showError( $this->lang->words['m_noid'], 11231 );
		}
		
		//-----------------------------------------
		// Allowed to suspend administrators?
		//-----------------------------------------
		
		if( $member['g_access_cp'] AND !$this->registry->getClass('class_permissions')->checkPermission( 'member_suspend_admin') )
		{
			$this->registry->output->global_message = $this->lang->words['m_suspadmin'];
			$this->_memberView();
			return;
		}
					     		
		$ban = IPSMember::processBanEntry( $member['temp_ban'] );
		$ban['contents'] = sprintf( $this->lang->words['m_yoursusp'], $this->settings['board_name'] ) . $this->settings['board_url'] . "/index.php";
		
		$this->registry->output->html .= $this->html->memberSuspension( array_merge( $member, $ban ) );
	}
	
	/**
	 * Suspend a member [process]
	 *
	 * @return	@e void
	 */
	protected function _memberSuspendDo()
	{
		$this->request[ 'member_id'] =  intval($this->request['member_id'] );
		
		if ( !$this->request['member_id'] )
		{
			$this->registry->output->showError( $this->lang->words['m_specify'], 11232 );
		}
		
		$member = IPSMember::load( $this->request['member_id'] );

		if ( ! $member['member_id'] )
		{
			$this->registry->output->showError( $this->lang->words['m_noid'], 11233 );
		}
		
		//-----------------------------------------
		// Allowed to suspend administrators?
		//-----------------------------------------
		
		if( $member['g_access_cp'] AND !$this->registry->getClass('class_permissions')->checkPermission( 'member_suspend_admin') )
		{
			$this->registry->output->global_message = $this->lang->words['m_suspadmin'];
			$this->_memberView();
			return;
		}	
		
		//-----------------------------------------
		// Work out end date
		//-----------------------------------------
		
		$this->request[ 'timespan'] =  intval($this->request['timespan'] );
		
		if ( $this->request['timespan'] == "" )
		{
			$new_ban = "";
		}
		else
		{
			$new_ban = IPSMember::processBanEntry( array( 'timespan' => intval($this->request['timespan']), 'unit' => $this->request['units'] ) );
		}
		
		$show_ban = IPSMember::processBanEntry( $new_ban );
			
		//-----------------------------------------
		// Update and show confirmation
		//-----------------------------------------

		IPSMember::save( $member['member_id'], array( 'core' => array( 'temp_ban' => $new_ban ) ) );

		// I say, did we choose to email 'dis member?
		
		if ( $this->request['send_email'] )
		{
			// By golly, we did!

			$msg = trim(IPSText::stripslashes($_POST['email_contents']));
			
			$msg = str_replace( "{membername}", $member['members_display_name']       , $msg );
			$msg = str_replace( "{date_end}"  , ipsRegistry::getClass('class_localization')->getDate( $show_ban['date_end'], 'LONG') , $msg );
			
			IPSText::getTextClass('email')->message	= stripslashes( IPSText::getTextClass('email')->cleanMessage($msg) );
			IPSText::getTextClass('email')->subject	= $this->lang->words['m_acctsusp'];
			IPSText::getTextClass('email')->to		= $member['email'];
			IPSText::getTextClass('email')->sendMail();
		}
		
		//-----------------------------------------
		// Redirect
		//-----------------------------------------

		ipsRegistry::getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['m_susplog'], $member['members_display_name'] ) );

		$this->registry->output->global_message	= $this->lang->words['m_suspended'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=viewmember&amp;member_id=' . $member['member_id'] );
	}
	
	/**
	 * Unsuspend a member [process]
	 *
	 * @return	@e void
	 */
	protected function _memberUnsuspend()
	{
		if ( !$this->request['member_id'] )
		{
			$this->registry->output->showError( $this->lang->words['m_specify'], 11234 );
		}
		
		$member = IPSMember::load( $this->request['member_id'] );
		
		//-----------------------------------------
		// Allowed to suspend administrators?
		//-----------------------------------------
		
		if( $member['g_access_cp'] AND !$this->registry->getClass('class_permissions')->checkPermission( 'member_suspend_admin') )
		{
			$this->registry->output->global_message = $this->lang->words['m_unsuspadmin'];
			$this->_memberView();
			return;
		}	
		
		if ( $this->request['member_id'] == 'all' )
		{
			$this->DB->update( 'members', array( 'temp_ban' => 0 ) );
			
			ipsRegistry::getClass('adminFunctions')->saveAdminLog( $this->lang->words['m_unsuspall'] );

			//-----------------------------------------
			// Redirect
			//-----------------------------------------

			$this->registry->output->global_message	= $this->lang->words['m_allunsusp'];
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=members_list' );
		}
		else
		{
			$mid = intval($this->request['member_id']);
			
			IPSMember::save( $mid, array( 'core' => array( 'temp_ban' => 0 ) ) );
			
			$member = IPSMember::load( $mid );
			
			ipsRegistry::getClass('adminFunctions')->saveAdminLog(sprintf( $this->lang->words['m_unsusplog'], $member['members_display_name'] ) );

			//-----------------------------------------
			// Redirect
			//-----------------------------------------

			$this->registry->output->global_message	= sprintf( $this->lang->words['m_unsuspended'], $member['members_display_name'] );
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=viewmember&amp;member_id=' . $member['member_id'] );
		}
	}

	/**
	 * Prune members [confirmation]
	 *
	 * @param	integer		$count		Number of members to prune
	 * @return	@e void
	 */
	protected function _memberPruneForm( $count )
	{
		$this->registry->output->extra_nav[] = array( '', $this->lang->words['m_prune'] );
		
		//-----------------------------------------
		// Got members?
		//-----------------------------------------
		
		if ( !$count )
		{
			return;
		}

		$this->registry->output->html .= $this->html->pruneConfirm( $count );
	}
	
	/**
	 * Move members to another group [confirmation]
	 *
	 * @param	integer		$count		Number of members to move
	 * @return	@e void
	 */
	protected function _memberMoveForm( $count )
	{ 
		$this->registry->output->extra_nav[] = array( '', $this->lang->words['m_move'] );
		
		//-----------------------------------------
		// Got members?
		//-----------------------------------------
		
		if ( !$count )
		{
			return;
		}

		$this->registry->output->html .= $this->html->moveConfirm( $count );
	}

	/**
	 * Get extra query if cannot prune admins
	 * 
	 * @return	@e string
	 */
 	protected function _getExtraQuery()
 	{
 		$extraQuery	= '';

		if( !$this->registry->getClass('class_permissions')->checkPermission( 'member_prune_admin' ) )
		{
			$admin_group_ids	= array();
			$_sql				= array();
			
			foreach( $this->cache->getCache( 'group_cache' ) as $group )
			{
				if( $group['g_access_cp'] )
				{
					$admin_group_ids[]	= $group['g_id'];
					
					$_sql[]	= "m.mgroup_others NOT LIKE '%," . $group['g_id'] . ",%'";
				}
			}
			
			$_sql[]	= "m.member_group_id NOT IN(" . implode( ',', $admin_group_ids ) . ")";
			
			if( count($_sql) )
			{
				$extraQuery	= implode( ' AND ', $_sql );
			}
		}
		
		return $extraQuery;
 	}

	/**
	 * Prune members [process]
	 *
	 * @return	@e void
	 */
	protected function _memberDoPrune()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('members') . '/sources/adminSearch.php', 'adminMemberSearch', 'members' );
		$searchHelper	= new $classToLoad( $this->registry );
		
		$data			= $searchHelper->generateFilterBoxes();

		//-----------------------------------------
		// Allowed to prune administrators?
		//-----------------------------------------
		
		$extraQuery	= $this->_getExtraQuery();	

		//-----------------------------------------
		// Got a query?
		//-----------------------------------------
		
		if ( !$searchHelper->getWhereClause() AND !$extraQuery )
		{
			$this->registry->output->showError( $this->lang->words['m_noprune'], 11235.1 );
		}
		
		//-----------------------------------------
		// Get the number of results
		//-----------------------------------------

		$count	= $searchHelper->getSearchResultsCount( $extraQuery );

		//-----------------------------------------
		// Reset if we have no results
		//-----------------------------------------
		
		if ( !$count )
		{
			$this->registry->output->global_message	= $this->lang->words['m_noprune'];

			ipsRegistry::getClass('adminFunctions')->staffSaveCookie( 'memberFilter', array() );
			
			return $this->_memberList();
		}

		//-----------------------------------------
		// Run the query
		//-----------------------------------------

		$results	= $searchHelper->getSearchResults( 0, 0, $extraQuery, true );

		if( !count($results['ids']) )
		{
			$this->registry->output->showError( $this->lang->words['m_noprune'], 11235 );
		}

		//-----------------------------------------
		// Delete members
		//-----------------------------------------
		
		IPSMember::remove( $results['ids'], true );

		//-----------------------------------------
		// Admin log
		//-----------------------------------------
		
		ipsRegistry::getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['m_deletedlog'], implode( ",", $results['names'] ) ) );
		
		//-----------------------------------------
		// Reset staff cookie
		//-----------------------------------------
		
		ipsRegistry::getClass('adminFunctions')->staffSaveCookie( 'memberFilter', array() );

		//-----------------------------------------
		// Redirect
		//-----------------------------------------
		
		$this->registry->output->global_message	= $this->lang->words['m_deleted'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=members_list' );
	}
	
	/**
	 * Move members [process]
	 *
	 * @return	@e void
	 */
	protected function _memberDoMove()
	{
		//-----------------------------------------
		// Error checking
		//-----------------------------------------
		
		if( !$this->request['move_to_group'] )
		{
			$this->registry->output->showError( $this->lang->words['m_whatgroup'], 11236 );
		}
		
		if( !$this->registry->getClass('class_permissions')->checkPermission( 'member_move_admin2') )
		{
			if( $this->caches['group_cache'][ $this->request['move_to_group'] ]['g_access_cp'] )
			{
				$this->registry->output->global_message	= $this->lang->words['m_adminpromote'];
				
				$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
			}
		}
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('members') . '/sources/adminSearch.php', 'adminMemberSearch', 'members' );
		$searchHelper	= new $classToLoad( $this->registry );
		
		$data			= $searchHelper->generateFilterBoxes();

		//-----------------------------------------
		// Allowed to move to/from administrators?
		//-----------------------------------------
		
		$extraQuery	= $this->_getExtraQuery();	

		//-----------------------------------------
		// Get the number of results
		//-----------------------------------------
		
		$count	= $searchHelper->getSearchResultsCount( $extraQuery );

		//-----------------------------------------
		// Reset if we have no results
		//-----------------------------------------
		
		if ( !$count )
		{
			$this->registry->output->global_message	= $this->lang->words['m_nomembers'];

			ipsRegistry::getClass('adminFunctions')->staffSaveCookie( 'memberFilter', array() );
			
			return $this->_memberList();
		}

		//-----------------------------------------
		// Run the query
		//-----------------------------------------

		$results	= $searchHelper->getSearchResults( 0, 0, $extraQuery, true );

		if( !count($results['ids']) )
		{
			$this->registry->output->showError( $this->lang->words['m_nomembers'], 11237 );
		}

		//-----------------------------------------
		// Move the members
		//-----------------------------------------
		
		$this->DB->update( 'members', array( 'member_group_id' => intval($this->request['move_to_group']) ), 'member_id IN(' . implode( ',', $results['ids'] ) . ')' );
		
		//-----------------------------------------
		// Store admin log
		//-----------------------------------------

		ipsRegistry::getClass('adminFunctions')->saveAdminLog( sprintf($this->lang->words['m_movedlog'], $this->caches['group_cache'][ $this->request['move_to_group'] ]['g_title'], implode( ",", $results['names'] )  ) );
		
		//-----------------------------------------
		// Reset cookie
		//-----------------------------------------
		
		ipsRegistry::getClass('adminFunctions')->staffSaveCookie( 'memberFilter', array() );

		//-----------------------------------------
		// And redirect
		//-----------------------------------------

		$this->registry->output->global_message	= $this->lang->words['m_moved'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=members_list' );
	}
	
	/**
	 * Delete members [form+process]
	 *
	 * @return	@e void
	 */
	protected function _memberDelete()
	{
		//-----------------------------------------
		// Check input
		//-----------------------------------------
		
		if ( ! $this->request['member_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['m_nomember'];
			$this->request['do']	= 'members_list';
			$this->_memberList();
			return;
		}
		
		//-----------------------------------------
		// Single or more?
		//-----------------------------------------
		
		if ( strstr( $this->request['member_id'], ',' ) )
		{
			$ids = explode( ',', $this->request['member_id'] );
		}
		else
		{
			$ids = array( $this->request['member_id'] );
		}
		
		$ids = IPSLib::cleanIntArray( $ids );
		
		/* Don't delete our selves */
		if( in_array( $this->memberData['member_id'], $ids ) )
		{
			$this->registry->output->global_message = $this->lang->words['m_nodeleteslefr'];
			$this->request['do']	= 'members_list';
			$this->_memberList();
			return;
		}

		//-----------------------------------------
		// Get accounts
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => 'member_id, name, member_group_id, mgroup_others', 'from' => 'members', 'where' => 'member_id IN (' . implode( ",", $ids ) . ')' ) );
		$this->DB->execute();
		
		$names = array();
		
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
			
			$names[] = $r['name'];
		}
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! count( $names ) )
		{
			$this->registry->output->global_message = $this->lang->words['m_nomember'];
			$this->request['do']	= 'members_list';
			$this->_memberList();
			return;
		}
		
		//-----------------------------------------
		// Delete
		//-----------------------------------------
		
		IPSMember::remove( $ids, true );
		
		//-----------------------------------------
		// Clear "cookies"
		//-----------------------------------------
		
		ipsRegistry::getClass('adminFunctions')->staffSaveCookie( 'memberFilter', array() );
		
		//-----------------------------------------
		// Redirect
		//-----------------------------------------
		
		$page_query = "";

		ipsRegistry::getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['m_deletedlog'], implode( ",", $names ) ) );
		
		$this->registry->output->global_message = sprintf( $this->lang->words['m_deletedlog'], implode( ",", $names ) );
		$this->request['do']	= 'members_list';
		$this->_memberList();
	}
		
	/**
	 * Add a member [form]
	 *
	 * @return	@e void
	 */
	protected function _memberAddForm()
	{
		//-----------------------------------------
		// Groups
		//-----------------------------------------
		
		$mem_group		= array();

		foreach( $this->cache->getCache('group_cache') as $r )
		{
			if ( $r['g_access_cp'] AND !$this->registry->getClass('class_permissions')->checkPermission( 'member_add_admin') )
			{
				continue;
			}
			
			$mem_group[] = array( $r['g_id'] , $r['g_title'] );
		}

    	//-----------------------------------------
		// Custom fields
		//-----------------------------------------
		
		$classToLoad   = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/customfields/profileFields.php', 'customProfileFields' );
		$custom_fields = new $classToLoad();
		
		$custom_fields->member_data = array();
		$custom_fields->initData( 'edit' );
		$custom_fields->parseToEdit();
	     						     
		$this->registry->output->html .= $this->html->memberAddForm( $mem_group, $custom_fields );
	}
	
	/**
	 * Add a member [process]
	 *
	 * @return	@e void
	 */
	protected function _memberDoAdd()
	{
		/* Init vars */
		$in_username 			= trim($this->request['name']);
		$in_password 			= trim($this->request['password']);
		$in_email    			= trim(strtolower($this->request['email']));
		$members_display_name	= $this->request['mirror_loginname'] ? $in_username : trim($this->request['members_display_name'] );
		
		$this->registry->output->global_error = '';
		
		$this->registry->class_localization->loadLanguageFile( array( 'public_register' ), 'core' );
		
		/* Check erros */
		foreach( array('name', 'password', 'email', 'member_group_id') as $field )
		{
			if ( ! $_POST[ $field ] )
			{
				$this->registry->output->showError( $this->lang->words['m_completeform'], 11238 );
			}
		}
		
		//-----------------------------------------
		// Check
		//-----------------------------------------

		if( ! IPSText::checkEmailAddress( $in_email ) )
		{
			$this->registry->output->global_error = $this->lang->words['m_emailinv'];
		}
		
		$userName		= IPSMember::getFunction()->cleanAndCheckName( $in_username, array(), 'name' );
		$displayName	= IPSMember::getFunction()->cleanAndCheckName( $members_display_name, array(), 'members_display_name' );

		if( count($userName['errors']) )
		{
			$_message	= $this->lang->words[ $userName['errors']['username'] ] ? $this->lang->words[ $userName['errors']['username'] ] : $userName['errors']['username'];
			$this->registry->output->global_error .= '<p>' . $this->lang->words['sm_loginname'] . ': ' . $_message . '</p>';
		}

		if( $this->settings['auth_allow_dnames'] AND count($displayName['errors']) )
		{
			$_message	= $this->lang->words[ $displayName['errors']['dname'] ] ? $this->lang->words[ $displayName['errors']['dname'] ] : $displayName['errors']['dname'];
			$this->registry->output->global_error .= '<p>' . $this->lang->words['sm_display'] . ': ' . $_message . '</p>';
		}

		/* Errors? */
		if( $this->registry->output->global_error )
		{
			$this->_memberAddForm();
			return;
		}

        //-----------------------------------------
    	// Load handler...
    	//-----------------------------------------
    	
    	$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/handlers/han_login.php', 'han_login' );
    	$this->han_login = new $classToLoad( $this->registry );
    	$this->han_login->init();

        //-----------------------------------------
    	// Only check local, else a user being in Converge
    	// means that you can't manually add the user to the board
    	//-----------------------------------------
    	
		$email_check = $this->DB->buildAndFetch( array( 'select' => 'member_id', 'from' => 'members', 'where' => "email='" . $in_email . "'" ) );

		if( $email_check['member_id'] )
		{
			$this->registry->output->global_error = $this->lang->words['m_emailalready'];
			$this->_memberAddForm();
			return;
		}
		
    	//$this->han_login->emailExistsCheck( $in_email );

    	//if( $this->han_login->return_code AND $this->han_login->return_code != 'METHOD_NOT_DEFINED' AND $this->han_login->return_code != 'EMAIL_NOT_IN_USE' )
    	//{
		//	$this->registry->output->global_message = $this->lang->words['m_emailalready'];
		//	$this->_memberAddForm();
		//	return;
    	//}

		//-----------------------------------------
		// Allowed to add administrators?
		//-----------------------------------------
		
		if( $this->caches['group_cache'][ intval($this->request['member_group_id']) ]['g_access_cp'] AND !$this->registry->getClass('class_permissions')->checkPermission( 'member_add_admin') )
		{
			$this->registry->output->global_error = $this->lang->words['m_addadmin'];
			$this->_memberAddForm();
			return;
		}

		$member = array( 'name'						=> $in_username,
						 'members_display_name'		=> $members_display_name ? $members_display_name : $in_username,
						 'email'					=> $in_email,
						 'member_group_id'			=> intval($this->request['member_group_id']),
						 'joined'					=> time(),
						 'ip_address'				=> $this->member->ip_address,
						 'time_offset'				=> $this->settings['time_offset'],
						 'coppa_user'				=> intval($this->request['coppa']),
						 'allow_admin_mails'		=> 1,
						 'password'					=> $in_password,
						 'language'					=> IPSLib::getDefaultLanguage(),
						);

		//-----------------------------------------
		// Create the account
		//-----------------------------------------

		$member	= IPSMember::create( array( 'members' => $member, 'pfields_content' => $this->request ), FALSE, FALSE, FALSE );
		
		//-----------------------------------------
		// Login handler create account callback
		//-----------------------------------------

   		$this->han_login->createAccount( array(	'email'			=> $in_email,
   												'joined'		=> $member['joined'],
   												'password'		=> $in_password,
   												'ip_address'	=> $member['ip_address'],
   												'username'		=> $member['members_display_name'],
   										)		);

		/*if( $this->han_login->return_code AND $this->han_login->return_code != 'METHOD_NOT_DEFINED' AND $this->han_login->return_code != 'SUCCESS' )
		{
			$this->registry->output->global_message = sprintf( $this->lang->words['m_cantadd'], $this->han_login->return_code ) . $this->han_login->return_details;
			$this->_memberAddForm();
			return;
		}*/

		//-----------------------------------------
		// Restriction permissions stuff
		//-----------------------------------------
		
		if ( $this->memberData['row_perm_cache'] )
		{
			if ( $this->caches['group_cache'][ intval($this->request['member_group_id']) ]['g_access_cp'] )
			{
				//-----------------------------------------
				// Copy restrictions...
				//-----------------------------------------
				
				$this->DB->insert( 'admin_permission_rows', array( 
																	'row_member_id'  => $member['member_id'],
																	'row_perm_cache' => $this->memberData['row_perm_cache'],
																	'row_updated'    => time() 
								)	 );
			}
		}
		
		//-----------------------------------------
		// Send teh email (I love 'teh' as much as !!11!!1)
		//-----------------------------------------
		
		if( $this->request['sendemail'] )
		{
			IPSText::getTextClass('email')->setPlainTextTemplate( IPSText::getTextClass('email')->getTemplate("account_created") );
			
			IPSText::getTextClass('email')->buildMessage( array(
												'NAME'         => $member['name'],
												'EMAIL'        => $member['email'],
												'PASSWORD'	   => $in_password
											  )
										);
										
			IPSText::getTextClass('email')->to		= $member['email'];
			IPSText::getTextClass('email')->sendMail();
		}
		
		//-----------------------------------------
		// Stats
		//-----------------------------------------
		
		$this->cache->rebuildCache( 'stats', 'global' );
		$this->cache->rebuildCache( 'birthdays', 'calendar' );

		//-----------------------------------------
		// Log and bog?
		//-----------------------------------------
		             
		ipsRegistry::getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['m_createlog'], $this->request['name'] ) );
		
		$this->registry->output->global_message = $this->lang->words['m_memadded'];

		$this->request['member_id']	= $member['member_id'];
		
		$this->_showAdminForm( $member, array() );
		$this->_memberView();		
	}
	
	/**
	 * List members
	 *
	 * @return	@e void
	 */
	protected function _memberList()
	{	
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('members') . '/sources/adminSearch.php', 'adminMemberSearch', 'members' );
		$searchHelper	= new $classToLoad( $this->registry );

		//-----------------------------------------
		// Output filters
		//-----------------------------------------
		
		$_html							 = $searchHelper->getHtmlPresets();
		$this->registry->output->html	.= $this->html->member_list_context_menu_filters( $_html['form'], $_html['fields'], $_html['presets'] );

		//-----------------------------------------
		// Get the number of results
		//-----------------------------------------
		
		$count	= $searchHelper->getSearchResultsCount();

		//-----------------------------------------
		// If we have none, show message and reset cookie
		//-----------------------------------------
		
		if ( $count < 1 and !$this->request['type'] )
		{
			$this->registry->output->global_message = $this->lang->words['m_nomembers'];

			ipsRegistry::getClass('adminFunctions')->staffSaveCookie( 'memberFilter', array() );
			
			$count	= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'members' ) );
			$count	= $count['count'];
			
			$searchHelper->resetFilters();
		}

		//-----------------------------------------
		// Generate pagination
		//-----------------------------------------
		
		$st			= intval($this->request['st']);
		$perpage	= 20;

		$pages		= $this->registry->output->generatePagination( array(
																		'totalItems'			=> $count,
																		'itemsPerPage'			=> $perpage,
																		'currentStartValue'		=> $st,
																		'baseUrl'				=> $this->settings['base_url'] . $this->form_code . "&amp;do=" . $this->request['do'],
																)		);
		
		//-----------------------------------------
		// Run the query
		//-----------------------------------------
		
		$members	= $searchHelper->getSearchResults( $st, $perpage );
		
		//-----------------------------------------
		// Prune you fookers?
		//-----------------------------------------

		$_searchType	= $searchHelper->getSearchType();
		
		if ( $_searchType == 'delete' )
		{
			$this->_memberPruneForm( $count );
			return;
		}
		else if( $_searchType == 'move' )
		{
			$this->_memberMoveForm( $count );
			return;
		}
		
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->extra_nav[]	= array( '', $this->lang->words['m_viewlist'] );

		$this->registry->output->html			.= $this->html->members_list( $members, $pages );
	}

	/**
	 * Edit a member [process]
	 *
	 * @return	@e void
	 */
	protected function _memberDoEdit()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->request['member_id'] = intval($this->request['member_id']);
		
		//-----------------------------------------
		// Send to form if this isn't a POST request
		//-----------------------------------------
		
		if( $this->request['request_method'] != 'post' )
		{
			$this->_memberView();
			return;
		}
		
		//-----------------------------------------
		// Auth check...
		//-----------------------------------------
		
		ipsRegistry::getClass('adminFunctions')->checkSecurityKey( $this->request['secure_key'] );

		//-----------------------------------------
		// Load and config the std/rte editors
		//-----------------------------------------

		IPSText::getTextClass('editor')->from_acp         = 1;

        //-----------------------------------------
        // Get member
        //-----------------------------------------
		
        $member		= IPSMember::load( $this->request['member_id'], 'all' );

		//-----------------------------------------
		// Allowed to edit administrators?
		//-----------------------------------------
		
		if( $member['member_id'] != $this->memberData['member_id'] AND $member['g_access_cp'] AND !$this->registry->getClass('class_permissions')->checkPermission( 'member_edit_admin') )
		{
			$this->registry->output->global_message = $this->lang->words['m_editadmin'];
			$this->_memberView();
			return;
		}

		//-----------------------------------------
		// Allowed to change an admin's groups?
		//-----------------------------------------
		
		if( $member['g_access_cp'] AND !$this->registry->getClass('class_permissions')->checkPermission('member_move_admin1') )
		{
			$same		= false;
			
			if( $this->request['member_group_id'] == $member['member_group_id'] )
			{
				$member['mgroup_others']	= IPSText::cleanPermString( $member['mgroup_others'] );

				$omgroups	= $member['mgroup_others'] ? explode( ',', $member['mgroup_others'] ) : array();
				$groups		= $this->request['mgroup_others'] ? $this->request['mgroup_others'] : array();
				
				if( !count( array_diff( $omgroups, $groups ) ) AND !count( array_diff( $groups, $omgroups ) ) )
				{
					$same	= true;
				}
			}

			if( !$same )
			{
				$this->registry->output->global_message = $this->lang->words['m_admindemote'];
				$this->_memberView();
				return;
			}
		}

		//-----------------------------------------
		// What about promoting to admin?
		//-----------------------------------------
		
		if( !$member['g_access_cp'] AND !$this->registry->getClass('class_permissions')->checkPermission('member_move_admin2') )
		{
			$groups		= $_POST['mgroup_others'] ? $_POST['mgroup_others'] : array();
			$groups[]	= intval($this->request['member_group_id']);
			
			foreach( $groups as $group_id )
			{
				if( $this->caches['group_cache'][ $group_id ]['g_access_cp'] )
				{
					$this->registry->output->global_message = $this->lang->words['m_adminpromote'];
					$this->_memberView();
					return;
				}
			}
		}

		/* Load editor stuff */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$_editor = new $classToLoad();

		/* Get signature */
		$_editor->setAllowBbcode( true );
		$_editor->setAllowSmilies( false );
		$_editor->setAllowHtml( $member['g_dohtml'] );
		
		$signature	= $_editor->process( $_POST['signature'] );
		
		IPSText::getTextClass('bbcode')->parse_smilies		= 0;
		IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
		IPSText::getTextClass('bbcode')->parse_html			= $member['g_dohtml'];
		IPSText::getTextClass('bbcode')->parse_nl2br		= 1;
		IPSText::getTextClass('bbcode')->parsing_section	= 'signatures';
		
		$signature		= IPSText::getTextClass('bbcode')->preDbParse( $signature );
		$cacheSignature	= IPSText::getTextClass('bbcode')->preDisplayParse( $signature );

		/* About me editor */
		$_editor->setAllowBbcode( true );
		$_editor->setAllowSmilies( true );
		$_editor->setAllowHtml( $member['g_dohtml'] );
		
		$aboutme 		= $_editor->process( $_POST['aboutme'] );
		IPSText::getTextClass('bbcode')->parse_smilies		= 1;
		IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
		IPSText::getTextClass('bbcode')->parse_html			= $member['g_dohtml'];
		IPSText::getTextClass('bbcode')->parse_nl2br		= 1;
		IPSText::getTextClass('bbcode')->parsing_section	= 'aboutme';
		
		$aboutme		= IPSText::getTextClass('bbcode')->preDbParse( $aboutme );

		//-----------------------------------------
		// Ok? Load interface and child classes
		//-----------------------------------------
		
		$additionalCore		= array();
		$additionalExtended	= array();
		$additionalMCache	= array();

		IPSLib::loadInterface( 'admin/member_form.php' );
		
		foreach( IPSLib::getEnabledApplications() as $app_dir => $app_data )
		{
			if ( is_file( IPSLib::getAppDir(  $app_dir ) . '/extensions/admin/member_form.php' ) )
			{
				$_class  = IPSLib::loadLibrary( IPSLib::getAppDir(  $app_dir ) . '/extensions/admin/member_form.php', 'admin_member_form__' . $app_dir, $app_dir );
				$_object = new $_class( $this->registry );
				
				$remote = $_object->getForSave();

				$additionalCore		= is_array($remote['core']) ? array_merge( $remote['core'], $additionalCore ) : $additionalCore;
				$additionalExtended	= is_array($remote['extendedProfile']) ? array_merge( $remote['extendedProfile'], $additionalExtended ) : $additionalExtended;
				$additionalMCache	= is_array($remote['member_cache']) ? array_merge( $remote['member_cache'], $additionalMCache ) : $additionalMCache;
			}
		}
		
		//-----------------------------------------
		// Fix custom title
		// @see	http://forums.invisionpower.com/index.php?app=tracker&showissue=17383
		//-----------------------------------------
		
		$memberTitle	= $this->request['title'];
		$rankCache		= ipsRegistry::cache()->getCache( 'ranks' );
		
		if ( is_array( $rankCache ) && count( $rankCache ) )
		{
			foreach( $rankCache as $k => $v)
			{
				if ( $member['posts'] >= $v['POSTS'] )
				{
					/* If this is the title passed to us from the form, we didn't have a custom title */
					if ( $v['TITLE'] == $memberTitle )
					{
						$memberTitle	= '';
					}

					break;
				}
			}
		}

		//-----------------------------------------
		// Start array
		//-----------------------------------------
		
		$newMember = array( 'member_group_id'		=> intval($this->request['member_group_id']),
							'title'					=> $memberTitle,
							'time_offset'			=> floatval($this->request['time_offset']),
							'members_auto_dst'		=> intval($this->request['dstCheck']),
							'dst_in_use'			=> intval($this->request['dstOption']),
							'language'				=> $this->request['language'],
							'skin'					=> intval($this->request['skin']),
							'allow_admin_mails'		=> intval($this->request['allow_admin_mails']),
							'view_sigs'				=> intval($this->request['view_sigs']),
							'posts'					=> intval($this->request['posts']),
							'bday_day'				=> intval($this->request['bday_day']),
							'bday_month'			=> intval($this->request['bday_month']),
							'bday_year'				=> intval($this->request['bday_year']),
							'warn_level'			=> intval($this->request['warn_level']),
							'members_disable_pm'	=> intval($this->request['members_disable_pm']),
							'mgroup_others'			=> $this->request['mgroup_others'] ? ',' . implode( ",", $this->request['mgroup_others'] ) . ',' : '',
							'members_bitoptions'	=> IPSBWOPtions::freeze( $this->request, 'members', 'global' ), # Saves all BW options for all apps
							'member_uploader'		=> $this->request['member_uploader'],
							);

		//-----------------------------------------
		// Notifications library
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
		$notifyLibrary	= new $classToLoad( $this->registry );
		$notifyLibrary->setMember( $member );

		//-----------------------------------------
		// Show the form
		//-----------------------------------------
		
		$_basicOptions		= array( array( 'email', $this->lang->words['notopt__email'] ), array( 'inline', $this->lang->words['notopt__inline'] ), array( 'mobile', $this->lang->words['notopt__mobile'] ) );
		$_configOptions		= $notifyLibrary->getNotificationData();
		$_notifyConfig		= $notifyLibrary->getMemberNotificationConfig( $newMember );
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
		
		IPSMember::packMemberCache( $member['member_id'], array_merge( $additionalMCache, array( 'notifications' => $_saveConfig, 'show_notification_popup' => intval($this->request['show_notification_popup']) ) ), $member['members_cache'] );

		//-----------------------------------------
		// Topic preferences
		//-----------------------------------------
		
		$_trackChoice	= '';
		
		if ( $this->request['auto_track'] )
		{
 			if ( in_array( $this->request['auto_track_method'], array( 'none', 'immediate', 'offline', 'daily', 'weekly' ) ) )
 			{
 				$_trackChoice = $this->request['auto_track_method'];
 			}
 		}
 		
 		$newMember['auto_track']	= $_trackChoice;

		//-----------------------------------------
		// Custom profile field stuff
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/customfields/profileFields.php', 'customProfileFields' );
		$fields      = new $classToLoad();

    	$fields->initData( 'edit' );
    	$fields->parseToSave( $_POST );
		
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
		// Throw to the DB
		//-----------------------------------------
		
		IPSMember::save( $this->request['member_id'],
						 array( 
							 	'core'				=> array_merge( $newMember, $additionalCore ),
							 	'extendedProfile'	=> array_merge( array(
															'pp_about_me'					=> $aboutme,
															'signature'						=> $signature,
															'pp_reputation_points'			=> intval($this->request['pp_reputation_points']),
															'pp_setting_count_visitors'		=> intval($this->request['pp_setting_count_visitors']),
															'pp_setting_count_comments'		=> intval($this->request['pp_setting_count_comments']),
															'pp_setting_count_friends'		=> intval($this->request['pp_setting_count_friends']),
															'pp_setting_moderate_comments'	=> intval($this->request['pp_setting_moderate_comments']),
															'pp_setting_moderate_friends'	=> intval($this->request['pp_setting_moderate_friends']),
															'pp_customization'				=> ( $this->request['removeCustomization'] ) ? serialize( array() ) : $member['pp_customization']
															), $additionalExtended ),
								'customFields'		=> count($fields->out_fields) ? $fields->out_fields : array(),
						 	  )
						);
						
		if( $member['member_group_id'] != $newMember['member_group_id'] )
		{
			IPSLib::runMemberSync( 'onGroupChange', $this->request['member_id'], $newMember['member_group_id'], $member['member_group_id'] );
			
			//-----------------------------------------
			// Remove restrictions if member demoted
			// Commenting out as this may cause more problems than it's worth
			// e.g. if you had accidentally changed their group, you'd need to reconfigure all restrictions
			//-----------------------------------------

			/*if( !$this->caches['group_cache'][ $newMember['member_group_id'] ]['g_access_cp'] )
			{
				$this->DB->delete( 'admin_permission_rows', 'row_id=' . $member['member_id'] . " AND row_id_type='member'" );
			}*/
		}						
		
		//-----------------------------------------
		// Restriction permissions stuff
		//-----------------------------------------

		if ( is_array($this->registry->getClass('class_permissions')->restrictions_row) AND count($this->registry->getClass('class_permissions')->restrictions_row) )
		{
			$is_admin	= 0;
			$groups		= ipsRegistry::cache()->getCache('group_cache');
			
			if ( is_array( $this->request['mgroup_others'] ) AND count( $this->request['mgroup_others'] ) )
			{
				foreach( $this->request['mgroup_others'] as $omg )
				{
					if ( $groups[ intval($omg) ]['g_access_cp'] )
					{
						$is_admin	= 1;
						break;
					}
				}
			}
			
			if( $groups[ intval($this->request['member_group_id']) ]['g_access_cp'] )
			{
				$is_admin	= 1;
			}

			if ( $is_admin )
			{
				//-------------------------------------------------
				// Copy restrictions if they do not have any yet...
				//-------------------------------------------------
				
				$check = $this->DB->buildAndFetch( array( 'select' => 'row_updated', 'from' => 'admin_permission_rows', 'where' => "row_id_type='member' AND row_id=" . $this->request['member_id'] ) );
				
				if( !$check['row_updated'] )
				{
					$this->DB->replace( 'admin_permission_rows', array( 'row_id'			=> $this->request['member_id'],
																		'row_id_type'		=> 'member',
																		'row_perm_cache'	=> serialize($this->registry->getClass('class_permissions')->restrictions_row),
																		'row_updated'		=> time() ), array( 'row_id', 'row_id_type' ) );
				}
			}
		}	

		//-----------------------------------------
		// Moved from validating group?
		//-----------------------------------------
		
		if ( $member['member_group_id'] == $this->settings['auth_group'] )
		{
			if ( $this->request['member_group_id'] != $this->settings['auth_group'] )
			{
				//-----------------------------------------
				// Yes...
				//-----------------------------------------
				
				$this->DB->delete( 'validating', "member_id=" . $this->request['member_id'] );
			}
		}
				
		/* Update cache */
		IPSContentCache::update( $this->request['member_id'], 'sig', $cacheSignature );
		
		/* Rebuild birthday cache */
		$this->cache->rebuildCache( 'birthdays', 'calendar' );
		
		//-----------------------------------------
		// Redirect
		//-----------------------------------------
		
		ipsRegistry::getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['m_editedlog'], $member['members_display_name'] ) );
		
		$this->registry->output->global_message = $this->lang->words['m_edited'];

		$triggerGroups	= $member['mgroup_others'] ? implode( ',', array_merge( is_array($member['mgroup_others']) ? $member['mgroup_others'] : array(), array( $member['member_group_id'] ) ) ) : $member['member_group_id'];
		//$this->_memberView();
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&do=viewmember&trigger=' . $triggerGroups . '&member_id=' . $this->request['member_id'] );
	}
	
	/**
	 * Action: Log in as member
	 */
	protected function _loginAsMember()
	{
		$memberID = intval( $this->request['member_id'] );
		
		//-----------------------------------------
		// Load member
		//-----------------------------------------
		
		$member = IPSMember::load( $memberID, 'all' );
		if ( !$member['member_id'] )
		{
			return $this->_memberView();
		}
		
		if ( $member['g_access_cp'] )
		{
			$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_edit_admin' );
		}
						
		//-----------------------------------------
		// Generate a new log in key
		//-----------------------------------------
		
		$_ok     = 1;
		$_time   = ( $this->settings['login_key_expire'] ) ? ( time() + ( intval($this->settings['login_key_expire']) * 86400 ) ) : 0;
		$_sticky = $_time ? 0 : 1;
		$_days   = $_time ? $this->settings['login_key_expire'] : 365;
		
		if ( $this->settings['login_change_key'] OR !$member['member_login_key'] OR ( $this->settings['login_key_expire'] AND ( time() > $member['member_login_key_expire'] ) ) )
		{
			$member['member_login_key'] = IPSMember::generateAutoLoginKey();
			
			$core['member_login_key']			= $member['member_login_key'];
			$core['member_login_key_expire']	= $_time;
		}
	
		//-----------------------------------------
		// Cookie me softly?
		//-----------------------------------------
		
		if ( $setCookies )
		{
			IPSCookie::set( "member_id"   , $member['member_id']       , 1 );
			IPSCookie::set( "pass_hash"   , $member['member_login_key'], $_sticky, $_days );
		}
		else
		{
			IPSCookie::set( "member_id"   , $member['member_id'], 0 );
			IPSCookie::set( "pass_hash"   , $member['member_login_key'], 0 );
		}
		
		//-----------------------------------------
		// Create / Update session
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/session/publicSessions.php', 'publicSessions' );
		$sessionClass = new $classToLoad;
		
		$session_id = $sessionClass->convertGuestToMember( array( 'member_name'	    => $member['members_display_name'],
													   			     		 	  'member_id'		=> $member['member_id'],
																			      'member_group'	=> $member['member_group_id'],
																			      'login_type'		=> 0 ) );
			
		//-----------------------------------------
		// Boink
		//-----------------------------------------
		
		$this->registry->output->silentRedirect( $this->settings['board_url'] );
	}
}