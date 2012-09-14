<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Warning Actions
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $ (original: Mark)
 * @copyright	© 2011 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @version		$Revision: 10721 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class admin_members_warnings_actions extends ipsCommand
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
		// Init
		//-----------------------------------------
		
		$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'actions_view' );
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_member' ), 'members' );
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_warnings_actions' );
		
		$this->form_code	= $this->html->form_code	= 'module=warnings&amp;section=actions&amp;';
		$this->form_code_js	= $this->html->form_code_js	= 'module=warnings&section=actions&';
		
		//-----------------------------------------
		// What are we doing
		//-----------------------------------------
		
		switch ( $this->request['do'] )
		{
			case 'add':
				$this->form( 'add' );
				break;
				
			case 'edit':
				$this->form( 'edit' );
				break;
				
			case 'save':
				$this->save();
				break;
				
			case 'delete':
				$this->delete();
				break;
				
			case 'do_delete':
				$this->do_delete();
				break;
		
			case 'reorder':
				$this->reorder();
				break;
		
			default:
				$this->manage();
				break;
		}	
	
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Action: Manage
	 */
	private function manage()
	{
		$actions = array();
		$this->DB->build( array( 'select' => '*', 'from' => 'members_warn_actions', 'order' => 'wa_points ASC' ) );
		$this->DB->execute();
		while( $row = $this->DB->fetch() )
		{
			$actions[ $row['wa_id'] ] = $row;
		}
		
		$this->registry->output->html .= $this->html->manage( $actions );
	}
	
	/**
	 * Action: Show form
	 */
	private function form( $type )
	{
		$current = array();
		if ( $type == 'edit' )
		{
			$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'actions_edit' );
			
			$id = intval( $this->request['id'] );
			$current = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'members_warn_actions', 'where' => "wa_id={$id}" ) );
			if ( !$current['wa_id'] )
			{
				ipsRegistry::getClass('output')->showError( 'err_no_warn_reason', '112160', FALSE, '', 404 );
			}			
		}
		else
		{
			$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'actions_add' );
		}
							
		$this->registry->output->html .= $this->html->form( $current );
	}
	
	/**
	 * Action: Save
	 */
	private function save()
	{
		//-----------------------------------------
		// Validate Data
		//-----------------------------------------
		
		if ( !$this->request['points'] )
		{
			ipsRegistry::getClass('output')->showError( 'err_warning_action_points', '112161', FALSE, '', 500 );
		}
	
		//-----------------------------------------
		// Save
		//-----------------------------------------
				
		$save = array( 
			'wa_points'				=> floatval( $this->request['points'] ),
			'wa_mq'					=> ( $this->request['mq_perm'] ) ? -1 : intval( $this->request['mq'] ),
			'wa_mq_unit'			=> ( $this->request['mq_unit'] == 'd' ) ? 'd' : 'h',
			'wa_rpa'				=> ( $this->request['rpa_perm'] ) ? -1 :intval( $this->request['rpa'] ),
			'wa_rpa_unit'			=> ( $this->request['rpa_unit'] == 'd' ) ? 'd' : 'h',
			'wa_suspend'			=> ( $this->request['suspend_perm'] ) ? -1 :intval( $this->request['suspend'] ),
			'wa_suspend_unit'		=> ( $this->request['suspend_unit'] == 'd' ) ? 'd' : 'h',
			'wa_ban_group'			=> ( intval( $this->request['ban_group'] ) > 0 ) ? intval( $this->request['ban_group_id'] ) : 0,
			'wa_override'			=> intval( $this->request['override'] )
			);
			
		if ( $this->request['id'] )
		{
			$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'actions_edit' );
			
			$id = intval( $this->request['id'] );
			$current = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'members_warn_actions', 'where' => "wa_id={$id}" ) );
			if ( !$current['wa_id'] )
			{
				ipsRegistry::getClass('output')->showError( 'err_no_warn_reason', '112162', FALSE, '', 500 );
			}
			
			$this->DB->update( 'members_warn_actions', $save, "wa_id={$id}" );
			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['warn_actions_edited'], $save['wa_name'] ) );
		}
		else
		{
			$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'actions_add' );
			
			$this->DB->insert( 'members_warn_actions', $save );
			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['warn_actions_created'], $save['wa_name'] ) );
		}
				
		//-----------------------------------------
		// Display
		//-----------------------------------------
		
		$this->registry->output->redirect( "{$this->settings['base_url']}app=members&amp;module=warnings&amp;section=actions", $this->lang->words['warn_actions_saved'] );
	}
	
	/**
	 * Action: Delete
	 */
	private function delete()
	{	
		$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'actions_delete' );
		
		$id = intval( $this->request['id'] );
		$current = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'members_warn_actions', 'where' => "wa_id={$id}" ) );
		if ( !$current['wa_id'] )
		{
			ipsRegistry::getClass('output')->showError( 'err_no_warn_reason', '112163', FALSE, '', 404 );
		}
		
		$new = intval( $this->request['new'] );
		$new = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'members_warn_actions', 'where' => "wa_id={$new}" ) );
		if ( !$current['wa_id'] )
		{
			ipsRegistry::getClass('output')->showError( 'err_no_warn_reason', '112164', FALSE, '', 500 );
		}
		
		$this->DB->delete( 'members_warn_actions', "wa_id={$id}" );
		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['warn_actions_deleted'], $current['wa_name'] ) );
		
		$this->registry->output->redirect( "{$this->settings['base_url']}app=members&amp;module=warnings&amp;section=actions", $this->lang->words['warn_actions_del_saved'] );
	}
	
}