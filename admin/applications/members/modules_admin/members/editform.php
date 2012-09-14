<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Member property updater (AJAX)
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

class admin_members_members_editform extends ipsCommand 
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
			case 'save_display_name':
				$this->save_member_name( 'members_display_name' );
			break;
			case 'save_name':
				$this->save_member_name( 'name' );
			break;
			case 'save_password':
				$this->save_password();
			break;
			case 'save_email':
				$this->save_email();
			break;
    	}
	}

	/**
	 * Change a member's email address
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function save_email()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$member_id	= intval( $this->request['member_id'] );
		$email		= trim( $this->request['email'] );
		
		//-----------------------------------------
		// Get member
		//-----------------------------------------
		
		$member = IPSMember::load( $member_id );
																	
		if ( ! $member['member_id'] )
		{
			$this->registry->output->showError( $this->lang->words['m_noid'] );
		}
		
		//-----------------------------------------
		// Allowed to edit administrators?
		//-----------------------------------------
		
		if( $member['g_access_cp'] AND !$this->registry->getClass('class_permissions')->checkPermission( 'member_edit_admin', 'members', 'members' ) )
		{
			$this->registry->output->showError( $this->lang->words['m_editadmin'] );
		}
		
		//-----------------------------------------
		// Is this email addy taken? CONVERGE THIS??
		//-----------------------------------------
		
		$email_check = IPSMember::load( strtolower($email) );
		
		if ( $email_check['member_id'] AND $email_check['member_id'] != $member_id )
		{
			$this->registry->output->showError( $this->lang->words['m_emailalready'] );
		}
		
        //-----------------------------------------
    	// Load handler...
    	//-----------------------------------------
    	
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/handlers/han_login.php', 'han_login' );
		$han_login   = new $classToLoad( $this->registry );
    	$han_login->init();
    	$han_login->changeEmail( trim( strtolower( $member['email'] ) ), trim( strtolower( $email ) ), $member );
    	
    	//-----------------------------------------
    	// We don't want to die just from a Converge error
    	//-----------------------------------------
    	
    	/*if ( $han_login->return_code AND ( $han_login->return_code != 'METHOD_NOT_DEFINED' AND $han_login->return_code != 'SUCCESS' ) )
	    {
			$this->returnJsonError( $this->lang->words['m_emailalready'] );
			exit();
    	}*/
    	
		//-----------------------------------------
		// Update member
		//-----------------------------------------
		
		IPSLib::runMemberSync( 'onEmailChange', $member_id, strtolower( $email ), $member['email'] );
		
		IPSMember::save( $member_id, array( 'core' => array( 'email' => strtolower( $email ) ) ) );
		
		ipsRegistry::getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['m_emailchangelog'], $member['email'], $email, $member_id ) );

		$this->registry->output->global_message	= $this->lang->words['email_updated_success'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'module=members&do=viewmember&member_id=' . $member_id );
	}
	
	/**
	 * Change a member's password
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function save_password()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$member_id		= intval( $this->request['member_id'] );
		$password		= IPSText::parseCleanValue( $_POST['password'] );
		$password2		= IPSText::parseCleanValue( $_POST['password2'] );
		$new_key		= intval( $this->request['new_key'] );
		$new_salt		= intval( $this->request['new_salt'] );
		$salt			= str_replace( '\\', "\\\\", IPSMember::generatePasswordSalt(5) );
		$key			= IPSMember::generateAutoLoginKey();
		$md5_once		= md5( trim($password) );
		
		//-----------------------------------------
		// AJAX debug
		//-----------------------------------------
		
		IPSDebug::fireBug( 'info', array( 'Password: ' . $password ) );
		
		//-----------------------------------------
		// Check
		//-----------------------------------------

		if ( ! $password OR ! $password2 )
		{
			$this->registry->output->showError( $this->lang->words['password_nogood'] );
		}
		
		if ( $password != $password2 )
		{
			$this->registry->output->showError( $this->lang->words['m_passmatch'] );
		}

		//-----------------------------------------
		// Get member
		//-----------------------------------------
		
		$member = IPSMember::load( $member_id );
		
		//-----------------------------------------
		// Allowed to edit administrators?
		//-----------------------------------------
		
		if( $member['g_access_cp'] AND !$this->registry->getClass('class_permissions')->checkPermission( 'member_edit_admin', 'members', 'members' ) )
		{
			$this->registry->output->showError( $this->lang->words['m_editadmin'] );
		}
		
		//-----------------------------------------
		// Check Converge: Password
		//-----------------------------------------
    	
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/handlers/han_login.php', 'han_login' );
		$han_login   = new $classToLoad( $this->registry );
    	$han_login->init();
    	$han_login->changePass( $member['email'], $md5_once, $password, $member );
    	
    	/*if ( $han_login->return_code != 'METHOD_NOT_DEFINED' AND $han_login->return_code != 'SUCCESS' )
    	{
			$this->returnJsonError( $this->lang->words['m_passchange']);
			exit();
    	}*/
		
		//-----------------------------------------
		// Local DB
		//-----------------------------------------
		
		$update = array();
		
		if( $new_salt )
		{
			$update['members_pass_salt']	= $salt;
		}
		
		if( $new_key )
		{
			$update['member_login_key']		= $key;
		}
		
		if( count($update) )
		{
			IPSMember::save( $member_id, array( 'core' => $update ) );
		}
		
		IPSMember::updatePassword( $member_id, $md5_once );
		IPSLib::runMemberSync( 'onPassChange', $member_id, $password );

		ipsRegistry::getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['m_passlog'], $member_id ) );

		$this->registry->output->global_message	= $this->lang->words['pw_updated_success'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'module=members&do=viewmember&member_id=' . $member_id );
	}
	
	/**
	 * Update a user's login or display name
	 *
	 * @param	string		Field to update
	 * @return	@e void		[Outputs to screen]
	 */
	protected function save_member_name( $field='members_display_name' )
	{
		$member_id	= intval( $this->request['member_id'] );
		
		$member = IPSMember::load( $member_id );
		
		//-----------------------------------------
		// Allowed to edit administrators?
		//-----------------------------------------
		
		if( $member['g_access_cp'] AND !$this->registry->getClass('class_permissions')->checkPermission( 'member_edit_admin', 'members', 'members' ) )
		{
			$this->registry->output->showError( $this->lang->words['m_editadmin'] );
		}
		
		if ( $field == 'members_display_name' )
		{
			$display_name	= $this->request['display_name'];
    		$display_name	= str_replace("&#43;", "+", $display_name );
    	}
		else
		{
			$display_name	= $this->request['name'];
    		$display_name	= str_replace("&#43;", "+", $display_name );
    		
			$display_name = str_replace( '|', '&#124;' , $display_name );
			$display_name = trim( preg_replace( "/\s{2,}/", " ", $display_name ) );    		
		}
		
		if ( $this->settings['strip_space_chr'] )
    	{
    		// use hexdec to convert between '0xAD' and chr
			$display_name          = IPSText::removeControlCharacters( $display_name );
		}
		
		if ( $field == 'members_display_name' AND preg_match( "#[\[\];,\|]#", str_replace( '&#39;', "'", str_replace( '&amp;', '&', str_replace( '&quot;', '"', str_replace( '&#33;', '!', $display_name ) ) ) ) ) )
		{
			$this->registry->output->showError( $this->lang->words['m_displaynames'] );
		}
		
		try
		{
			if ( IPSMember::getFunction()->updateName( $member_id, $display_name, $field, TRUE ) === TRUE )
			{
				if ( $field == 'members_display_name' )
				{
					ipsRegistry::getClass('adminFunctions')->saveAdminLog(sprintf( $this->lang->words['m_dnamelog'], $member['members_display_name'], $display_name ));
				}
				else
				{
					ipsRegistry::getClass('adminFunctions')->saveAdminLog(sprintf( $this->lang->words['m_namelog'], $member['name'], $display_name ) );
					
					//-----------------------------------------
					// If updating a name, and display names 
					//	disabled, update display name too
					//-----------------------------------------
					
					if( !ipsRegistry::$settings['auth_allow_dnames'] )
					{
						IPSMember::getFunction()->updateName( $member_id, $display_name, 'members_display_name', TRUE );
					}

					//-----------------------------------------
					// I say, did we choose to email 'dis member?
					//-----------------------------------------

					if ( $this->request['send_email'] == 1 )
					{
						//-----------------------------------------
						// By golly, we did!
						//-----------------------------------------

						$msg = trim( IPSText::stripslashes( nl2br( $_POST['email_contents'] ) ) );

						$msg = str_replace( "{old_name}", $member['name'], $msg );
						$msg = str_replace( "{new_name}", $display_name  , $msg );
						$msg = str_replace( "<#BOARD_NAME#>", $this->settings['board_name'], $msg );
						$msg = str_replace( "<#BOARD_ADDRESS#>", $this->settings['board_url'] . '/index.' . $this->settings['php_ext'], $msg );

						IPSText::getTextClass('email')->message	= stripslashes( IPSText::getTextClass('email')->cleanMessage($msg) );
						IPSText::getTextClass('email')->subject	= $this->lang->words['m_changesubj'];
						IPSText::getTextClass('email')->to		= $member['email'];
						IPSText::getTextClass('email')->sendMail();
					}
				}
				
				$this->cache->rebuildCache( 'stats', 'global' );
			}
			else
			{
				# We should absolutely never get here. So this is a fail-safe, really to
				# prevent a "false" positive outcome for the end-user
				$this->registry->output->showError( $this->lang->words['m_namealready'] );
			}
		}
		catch( Exception $error )
		{
		//	$this->returnJsonError( $error->getMessage() );
			
			switch( $error->getMessage() )
			{
				case 'NO_USER':
					$this->registry->output->showError( $this->lang->words['m_noid'] );
				break;
				case 'NO_PERMISSION':
				case 'NO_NAME':
					$this->registry->output->showError( sprintf($this->lang->words['m_morethan3'], $this->settings['max_user_name_length'] ) );
				break;
				case 'ILLEGAL_CHARS':
					$this->registry->output->showError( $this->lang->words['m_illegal'] );
				break;
				case 'USER_NAME_EXISTS':
					$this->registry->output->showError( $this->lang->words['m_namealready'] );
				break;
				default:
					$this->registry->output->showError( $error->getMessage() );
				break;
			}
		}
		
        //-----------------------------------------
    	// Load handler...
    	//-----------------------------------------
    	
    	if( $field == 'name' )
    	{
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/handlers/han_login.php', 'han_login' );
			$han_login   = new $classToLoad( $this->registry );
	    	$han_login->init();
	    	$han_login->changeName( $member['name'], $display_name, $member['email'], $member );
    	}
    	else
    	{
    		IPSLib::runMemberSync( 'onNameChange', $member_id, $display_name );
    	}

		$this->registry->output->global_message	= $this->lang->words[ $field . '_updated_success'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'module=members&do=viewmember&member_id=' . $member_id );
	}
}