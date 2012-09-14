<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Admin Login Logs
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

class admin_core_logs_loginlog extends ipsCommand
{
	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Check Permissions */
		$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'acplogin_log' );
		
		/* Language */
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_logs', 'admin_system' ) );
		
		/* URLs */
		$this->form_code    = 'module=logs&amp;section=loginlog';
		$this->form_code_js = 'module=logs&section=loginlog';
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=logs&section=loginlog', $this->lang->words['al_error_logs'] );
				
		/* Load skin */
		$this->html = $this->registry->output->loadTemplate('cp_skin_system');
		
		switch( $this->request['do'] )
		{
			default:
				$this->loginLogsView();
			break;
			
			case 'view_detail':
				$this->loginLogDetails();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * View Details of a Log in Attempt
	 *
	 * @return	@e void
	 */
	public function loginLogDetails()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$admin_id = intval( $this->request['detail'] );
		
		//-----------------------------------------
		// Get data from the deebee
		//-----------------------------------------
		
		$log = $this->DB->buildAndFetch( array( 'select' => '*','from' => 'admin_login_logs', 'where' => 'admin_id='.$admin_id ) );
															
		if ( ! $log['admin_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['ll_noid'];
			$this->login_logs_view();
			return;
		}
		
		//-----------------------------------------
		// Display...
		//-----------------------------------------
		
		$log['_admin_time'] 		= $this->registry->class_localization->getDate( $log['admin_time'], 'LONG' );
		$log['_admin_post_details'] = unserialize( $log['admin_post_details'] );
		
		foreach( array( 'get', 'post' ) as $r )
		{
			if ( is_array( $log['_admin_post_details'][ $r ] ) )
			{
				foreach( $log['_admin_post_details'][ $r ] as $k => $v )
				{
					$log['_admin_post_details'][ $r ][ $k ] = htmlspecialchars( $v );
				}
			}
		}
				
		//-----------------------------------------
		// Show...
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->acp_last_logins_detail( $log );		
		$this->registry->output->printPopupWindow();
	}	
	
	/**
	 * View admin login logs
	 *
	 * @return	@e void
	 */
	public function loginLogsView()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$start   = intval( $this->request['st'] );
		$perpage = 50;
			
		//-----------------------------------------
		// Get log count
		//-----------------------------------------
		
		$count = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count', 'from' => 'admin_login_logs' ) );
																
		$links = $this->registry->output->generatePagination( array( 
																	'totalItems'        => intval( $count['count'] ),
																	'itemsPerPage'      => $perpage,
																	'currentStartValue' => $start,
																	'baseUrl'           => $this->settings['base_url'].$this->form_code 
															)	 );
									  
		//-----------------------------------------
		// Get from DB
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from'  => 'admin_login_logs', 'order' => 'admin_time DESC', 'limit' => array( $start, $perpage ) ) );												
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$row['_admin_time'] = $this->registry->class_localization->getDate( $row['admin_time'], 'ACP' );
			
			$logins .= $this->html->acp_last_logins_row( $row );
		}
		
		//-----------------------------------------
		// Print...
		//-----------------------------------------
		
		$this->registry->output->html .= $this->registry->output->global_template->information_box( $this->lang->words['ll_title'], $this->lang->words['ll_msg'] );
		$this->registry->output->html .= $this->html->acp_last_logins_wrapper( $logins, $links );
	}
}