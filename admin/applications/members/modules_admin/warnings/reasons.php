<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Warning Reasons
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

class admin_members_warnings_reasons extends ipsCommand
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
		
		$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'reasons_view' );
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_member' ), 'members' );
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_warnings_reasons' );
		
		$this->form_code	= $this->html->form_code	= 'module=warnings&amp;section=reasons&amp;';
		$this->form_code_js	= $this->html->form_code_js	= 'module=warnings&section=reasons&';
		
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
		$reasons = array();
		$this->DB->build( array( 'select' => '*', 'from' => 'members_warn_reasons', 'order' => 'wr_order' ) );
		$this->DB->execute();
		while( $row = $this->DB->fetch() )
		{
			$reasons[ $row['wr_id'] ] = $row;
		}
		
		$this->registry->output->html .= $this->html->manage( $reasons );
	}
	
	/**
	 * Action: Show form
	 */
	private function form( $type )
	{
		$current = array();
		if ( $type == 'edit' )
		{
			$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'reasons_edit' );
			
			$id = intval( $this->request['id'] );
			$current = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'members_warn_reasons', 'where' => "wr_id={$id}" ) );
			if ( !$current['wr_id'] )
			{
				ipsRegistry::getClass('output')->showError( 'err_no_warn_reason', '112150', FALSE, '', 404 );
			}			
		}
		else
		{
			$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'reasons_add' );
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
		
		if ( !$this->request['name'] )
		{
			ipsRegistry::getClass('output')->showError( 'err_warning_reason_name', '112151', FALSE, '', 500 );
		}
	
		//-----------------------------------------
		// Save
		//-----------------------------------------
				
		$save = array( 
			'wr_name'				=> $this->request['name'],
			'wr_points'				=> floatval( $this->request['points'] ),
			'wr_points_override'	=> intval( $this->request['points_override'] ),
			'wr_remove'				=> intval( $this->request['remove'] ),
			'wr_remove_unit'		=> ( $this->request['remove_unit'] == 'd' ) ? 'd' : 'h',
			'wr_remove_override'	=> intval( $this->request['remove_override'] )
			);
			
		if ( $this->request['id'] )
		{
			$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'reasons_edit' );
			
			$id = intval( $this->request['id'] );
			$current = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'members_warn_reasons', 'where' => "wr_id={$id}" ) );
			if ( !$current['wr_id'] )
			{
				ipsRegistry::getClass('output')->showError( 'err_no_warn_reason', '112152', FALSE, '', 500 );
			}
			
			$this->DB->update( 'members_warn_reasons', $save, "wr_id={$id}" );
			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['warn_reasons_edited'], $save['wr_name'] ) );
		}
		else
		{
			$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'reasons_add' );
			
			$top = $this->DB->buildAndFetch( array( 'select' => 'MAX(wr_id) as _top', 'from' => 'members_warn_reasons' ) );
			$save['wr_order'] = ++$top['_top'];
			$this->DB->insert( 'members_warn_reasons', $save );
			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['warn_reasons_created'], $save['wr_name'] ) );
		}
				
		//-----------------------------------------
		// Display
		//-----------------------------------------
		
		$this->registry->output->redirect( "{$this->settings['base_url']}app=members&amp;module=warnings&amp;section=reasons", $this->lang->words['warn_reasons_saved'] );
	}
	
	/**
	 * Action: Delete
	 */
	private function delete()
	{	
		$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'reasons_delete' );
		
		$id = intval( $this->request['id'] );
		$current = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'members_warn_reasons', 'where' => "wr_id={$id}" ) );
		if ( !$current['wr_id'] )
		{
			ipsRegistry::getClass('output')->showError( 'err_no_warn_reason', '112153', FALSE, '', 404 );
		}
		
		$new = intval( $this->request['new'] );
		$new = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'members_warn_reasons', 'where' => "wr_id={$new}" ) );
		if ( !$current['wr_id'] )
		{
			ipsRegistry::getClass('output')->showError( 'err_no_warn_reason', '112154', FALSE, '', 500 );
		}
		
		$this->DB->delete( 'members_warn_reasons', "wr_id={$id}" );
		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['warn_reasons_deleted'], $current['wl_name'] ) );
		
		$this->registry->output->redirect( "{$this->settings['base_url']}app=members&amp;module=warnings&amp;section=reasons", $this->lang->words['warn_reasons_del_saved'] );
	}
	
	/**
	 * AJAX Action: Reorder
	 */
	private function reorder()
	{			
		/* Get ajax class */
		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classAjax.php', 'classAjax' );
		$ajax		 = new $classToLoad();
		
		//-----------------------------------------
		// Checks...
		//-----------------------------------------
		
		if( $this->registry->adminFunctions->checkSecurityKey( $this->request['md5check'], true ) === false )
		{
			$ajax->returnString( $this->lang->words['postform_badmd5'] );
			exit();
		}
 		
 		//-----------------------------------------
 		// Save new position
 		//-----------------------------------------

 		$position	= 1;
 		
 		if( is_array($this->request['reasons']) AND count($this->request['reasons']) )
 		{
 			foreach( $this->request['reasons'] as $this_id )
 			{
 				$this->DB->update( 'members_warn_reasons', array( 'wr_order' => $position ), 'wr_id=' . $this_id );
 				
 				$position++;
 			}
 		}
 		
 		$ajax->returnString( 'OK' );
	}
}