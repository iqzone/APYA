<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Incoming Email Routing
 * Last Updated: $LastChangedDate: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}


class admin_core_tools_incomingEmails extends ipsCommand
{
	/**
	 * Main entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Load lang and skin */
		$this->registry->class_localization->loadLanguageFile( array( 'admin_tools' ) );
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_tools' );
		
		$this->form_code    = $this->html->form_code = 'module=tools&amp;section=incomingEmails';
		
		$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'incomingemail_manage' );
				
		/* What to do */
		switch( $this->request['do'] )
		{
			case 'add':
				$this->form('add');
				break;
				
			case 'edit':
				$this->form('edit');
				break;
				
			case 'save':
				$this->save();
				break;
				
			case 'delete':
				$this->delete();
				break;
				
			case 'test':
				$this->test();
			default:
				$this->manage();
				break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Test POP3
	 *
	 * @return	@e void
	 */
	protected function test()
	{
		require_once( IPS_KERNEL_PATH . '/pop3class/pop3.php' );/*noLibHook*/
		$pop3 = new pop3_class;
		
		$pop3->hostname							= $this->settings['pop3_server'];
		$pop3->port								= $this->settings['pop3_port'];
		$pop3->tls								= $this->settings['pop3_tls'];
		$pop3->realm							= '';
		$pop3->workstation						= '';
		$pop3->authentication_mechanism			= 'USER';
		$pop3->debug							= FALSE;
		$pop3->html_debug						= FALSE;
		$pop3->join_continuation_header_lines	= TRUE;
		
		$user					= $this->settings['pop3_user'];
		$password				= $this->settings['pop3_password'];
		$apop					= FALSE;
		
		$open = $pop3->Open();
		if ( $open != '' )
		{
			$this->registry->output->global_error = '<strong>' . $this->lang->words['pop3_err_connect'] . '</strong><br /> ' . $open;
			return;
		}
		
		$login = $pop3->Login($user, $password, $apop );
		if ( $login != '' )
		{
			$this->registry->output->global_error = '<strong>' . $this->lang->words['pop3_err_login'] . '</strong><br /> ' . $login;
			return;
		}
		
		$messages = NULL;
		$size = NULL;
		$pop3->Statistics( $messages, $size );
		if ( $messages === NULL or $size === NULL )
		{
			$this->registry->output->global_error = $this->lang->words['pop3_err_stats'];
		}
		
		$pop3->Close();
		
		$this->registry->output->global_message = sprintf( $this->lang->words['pop3_okay'], $messages );
	}
	
	/**
	 * Manage Rules
	 *
	 * @return	@e void
	 */
	protected function manage()
	{
		$rules = array();
		$this->DB->build( array( 'select' => '*', 'from' => 'core_incoming_emails' ) );
		$this->DB->execute();
		while ( $row = $this->DB->fetch() )
		{
			$rules[] = array(
				'id'		=> $row['rule_id'],
				'criteria'	=> $this->_getRuleText( $row['rule_criteria_field'], $row['rule_criteria_type'], $row['rule_criteria_value'] ),
				'action'	=> ( $row['rule_app'] == '--' ) ? $this->lang->words['ie_ignore'] : ( $this->lang->words['ie_sendto'] . ipsRegistry::$applications[ $row['rule_app'] ]['app_title'] )
				);
		}
	
		$this->registry->output->html .= $this->html->manageEmailRules( $rules );
	}
	
	/**
	 * Show Form
	 *
	 * @return	@e void
	 */
	protected function form( $type )
	{
		$current = array();
		if ( $type == 'edit' )
		{
			$id = intval( $this->request['id'] );
			$current = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_incoming_emails', 'where' => "rule_id={$id}" ) );
			if ( !$current['rule_id'] )
			{
				ipsRegistry::getClass('output')->showError( 'ie_not_found', 12345 );
			}			
		}
		
		$applications = array();
		foreach ( ipsRegistry::$applications as $dir => $app )
		{
			if ( is_file( IPSLib::getAppDir( $dir ) . '/extensions/incomingEmails.php' ) )
			{
				$applications[] = array( $dir, $this->lang->words['ie_sendto'] . $app['app_title'] );
			}
		}
			
		$this->registry->output->html .= $this->html->emailRuleForm( $current, $applications );
	}
	
	/**
	 * Action: Save
	 */
	protected function save()
	{			
		//-----------------------------------------
		// Save
		//-----------------------------------------
				
		$save = array( 
			'rule_criteria_field'	=> $this->request['criteria_field'],
			'rule_criteria_type'	=> $this->request['criteria_type'],
			'rule_criteria_value'	=> str_replace( '&#092;', '\\', $this->request['criteria_value'] ),
			'rule_app'				=> $this->request['action'],
			'rule_added_by'			=> $this->memberData['member_id'],
			'rule_added_date'		=> time(),
			);
						
		if ( $this->request['id'] )
		{
			$id = intval( $this->request['id'] );
			$this->DB->update( 'core_incoming_emails', $save, "rule_id={$id}" );
			
			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ie_updated'], $this->_getRuleText( $save['rule_criteria_field'], $save['rule_criteria_type'], $save['rule_criteria_value'] ) ) );
		}
		else
		{
			$this->DB->insert( 'core_incoming_emails', $save );
			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ie_added'], $this->_getRuleText( $save['rule_criteria_field'], $save['rule_criteria_type'], $save['rule_criteria_value'] ) ) );
		}
		
		//-----------------------------------------
		// Display
		//-----------------------------------------
		
		$this->registry->output->global_message = $this->lang->words['ie_saved'];
		$this->manage();
	}
	
	/**
	 * Action: Delete
	 */
	protected function delete()
	{
		$id = intval( $this->request['id'] );
		$current = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_incoming_emails', 'where' => "rule_id={$id}" ) );
		if ( !$current['rule_id'] )
		{
			ipsRegistry::getClass('output')->showError( 'ie_not_found', 12345 );
		}
		
		$this->DB->delete( 'core_incoming_emails', "rule_id={$id}" );
		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ie_deleted'], $this->_getRuleText( $current['rule_criteria_field'], $current['rule_criteria_type'], $current['rule_criteria_value'] ) ) );
				
		$this->registry->output->global_message = $this->lang->words['ie_delete_done'];
		$this->manage();
	}
	
	/**
	 * Return human-readable interpretation of rule
	 *
	 * @param	string	field
	 * @param	string	type
	 * @param	string	value
	 * @return	string
	 */
	protected function _getRuleText( $field, $type, $value )
	{
		return $this->lang->words[ 'ie_cf_' . $field ] . ' ' . $this->lang->words[ 'ie_ct_' . $type ] . ' "' . $value . '"';
	}
}