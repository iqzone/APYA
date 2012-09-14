<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Admin change email/password
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		5th January 2005
 * @version		$Revision: 10721 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_mycp_details extends ipsCommand
{
	/**
	 * Skin object
	 *
	 * @var		object			Skin templates
	 */
	protected $html;

	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Load skin
		//-----------------------------------------

		$this->html = $this->registry->output->loadTemplate('cp_skin_mycp');

		//-----------------------------------------
		// Load language
		//-----------------------------------------

		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_mycp' ) );

		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------

		$this->form_code	= $this->html->form_code	= 'module=mycp&amp;section=dashboard';
		$this->form_code_js	= $this->html->form_code_js	= 'module=mycp&section=dashboard';

		///----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'form':
			default:
				$this->_showForm();
			break;
				
			case 'save':
				$this->_saveForm();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Show the form so admin can reset email/pass
	 *
	 * @return	@e void
	 */
	protected function _showForm()
	{
		$this->registry->output->html .= $this->html->showChangeForm();
	}
	
	/**
	 * Save new email and/or pass
	 *
	 * @return	@e void
	 */
	protected function _saveForm()
	{
		if( !$this->request['email'] AND !$this->request['password'] )
		{
			$this->registry->output->global_error = $this->lang->words['change_nothing_update'];
			$this->_showForm();
			return;
		}
		
		if( $this->request['email'] )
		{
			if( !$this->request['email_confirm'] )
			{
				$this->registry->output->global_error = $this->lang->words['change_both_fields'];
				$this->_showForm();
				return;
			}
			else if( $this->request['email'] != $this->request['email_confirm'] )
			{
				$this->registry->output->global_error = $this->lang->words['change_not_match'];
				$this->_showForm();
				return;
			}
			
			$email		= trim($this->request['email']);
			
			if( ! IPSText::checkEmailAddress( $email ) )
			{
				$this->registry->output->global_error = $this->lang->words['bad_email_supplied'];
				$this->_showForm();
				return;
			}
			
			$email_check = IPSMember::load( strtolower($email) );
			
			if ( $email_check['member_id'] )
			{
				if ( $email_check['member_id'] == $this->memberData['member_id'] )
				{
					$this->registry->output->global_error = $this->lang->words['already_using_email'];
				}
				else
				{
					$this->registry->output->global_error = $this->lang->words['change_email_already_used'];
				}
				
				$this->_showForm();
				return;
			}
			
			//-----------------------------------------
			// Load handler...
			//-----------------------------------------
			
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/handlers/han_login.php', 'han_login' );
			$han_login   = new $classToLoad( $this->registry );
			$han_login->init();
			$han_login->changeEmail( trim( strtolower( $this->memberData['email'] ) ), trim( strtolower( $email ) ), $this->memberData );
			
			IPSLib::runMemberSync( 'onEmailChange', $this->memberData['member_id'], strtolower( $email ), $this->memberData['email'] );
			
			IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'email' => strtolower( $email ) ) ) );
			
			ipsRegistry::getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['changed_email'], $email ) );
		}
		
		if( $this->request['password'] )
		{
			if( !$this->request['password_confirm'] )
			{
				$this->registry->output->global_error = $this->lang->words['change_both_fields'];
				$this->_showForm();
				return;
			}
			else if( $this->request['password'] != $this->request['password_confirm'] )
			{
				$this->registry->output->global_error = $this->lang->words['change_not_match_pw'];
				$this->_showForm();
				return;
			}
			
			$password		= $this->request['password'];
			$salt			= str_replace( '\\', "\\\\", IPSMember::generatePasswordSalt(5) );
			$key			= IPSMember::generateAutoLoginKey();
			$md5_once		= md5( trim($password) );
			
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/handlers/han_login.php', 'han_login' );
			$han_login   = new $classToLoad( $this->registry );
			$han_login->init();
			$han_login->changePass( $this->memberData['email'], $md5_once, $password, $this->memberData );
			
			IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'members_pass_salt' => $salt, 'member_login_key' => $key ) ) );
			IPSMember::updatePassword( $this->memberData['member_id'], $md5_once );
			IPSLib::runMemberSync( 'onPassChange', $this->memberData['member_id'], $password );
	
			ipsRegistry::getClass('adminFunctions')->saveAdminLog( $this->lang->words['changed_password'] );
		}
		
		$this->registry->output->global_message = $this->lang->words['details_updated'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] );
	}
}