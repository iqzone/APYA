<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * API Users
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	© 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		1st march 2002
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_logs_xmlrpclogs extends ipsCommand
{
	/**
	 * Skin object
	 *
	 * @var		object			Skin templates
	 */
	protected $html;
	
	/**
	 * Shortcut for url
	 *
	 * @var		string			URL shortcut
	 */
	protected $form_code;
	
	/**
	 * Shortcut for url (javascript)
	 *
	 * @var		string			JS URL shortcut
	 */
	protected $form_code_js;
	
	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load skin...
		//-----------------------------------------
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_tools' ) );
		$this->html = $this->registry->output->loadTemplate('cp_skin_api');
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=logs&amp;section=xmlrpclogs';
		$this->form_code_js	= $this->html->form_code_js	= 'module=logs&section=xmlrpclogs';
		
		//-----------------------------------------
		// What are we to do, today?
		//-----------------------------------------

		switch( $this->request['do'] )
		{
			default:
			case 'log_list':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'api_logs' );
				$this->logList();
			break;
			case 'log_view_detail':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'api_logs' );
				$this->logViewDetail();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * API Logs View
	 * View API Log
	 *
	 * @return	@e void		[Outputs]
	 * @author 	Matt Mecham
	 * @since  	2.3.2
	 */
	public function logViewDetail()
	{
		//-----------------------------------------
		// Fix up navigation bar
		//-----------------------------------------
		
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_logs' ) );
		
		$this->registry->output->core_nav		= array();
		$this->registry->output->ignoreCoreNav	= true;
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=logs&section=logsSplash', $this->lang->words['nav_logssplash'] );
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=logs&section=xmlrpclogs', $this->lang->words['api_error_logs'] );
		
		//-----------------------------------------
		// Get data from the deebee
		//-----------------------------------------
		
		$log = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'api_log', 'where' => 'api_log_id=' . intval( $this->request['api_log_id'] ) ) );
		
		if ( ! $log['api_log_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['a_lognoid'];
			$this->logList();
			return;
		}
		
		//-----------------------------------------
		// Display...
		//-----------------------------------------
		
		$log['_api_log_date']		= ipsRegistry::getClass( 'class_localization')->getDate( $log['api_log_date'], 'LONG' );
		$log['_api_log_allowed']	= $log['api_log_allowed'] ? 'tick.png' : 'cross.png';
		$log['_api_log_query']		= htmlspecialchars( $log['api_log_query'] );
		
		//-----------------------------------------
		// Show...
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->api_log_detail( $log );
		
		$this->registry->output->printPopupWindow();
	}
	

	/**
	 * API Logs List
	 * List API Logs
	 *
	 * @return	@e void		[Outputs]
	 * @author 	Matt Mecham
	 * @since  	2.3.2
	 */
	public function logList()
	{
		//-----------------------------------------
		// Fix up navigation bar
		//-----------------------------------------
		
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_logs' ) );
		
		$this->registry->output->core_nav		= array();
		$this->registry->output->ignoreCoreNav	= true;
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=logs&section=logsSplash', $this->lang->words['nav_logssplash'] );
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=logs&section=xmlrpclogs', $this->lang->words['api_error_logs'] );
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$start   = intval( $this->request['st'] );
		$perpage = 50;
		$logs    = array();
		
		//-----------------------------------------
		// Get log count
		//-----------------------------------------
		
		$count = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count', 'from' => 'api_log' ) );
																
		$links = $this->registry->output->generatePagination( array( 'totalItems'			=> intval( $count['count'] ),
																	 'itemsPerPage'			=> $perpage,
																	 'currentStartValue'	=> $start,
																	 'baseUrl'				=> $this->settings['base_url'] . $this->form_code . '&amp;do=log_list' ) );
									  
		//-----------------------------------------
		// Get from DB
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'api_log', 'order' => 'api_log_date DESC', 'limit' => array( $start, $perpage ) ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$row['_api_log_date']     = ipsRegistry::getClass('class_localization')->getDate( $row['api_log_date'], 'LONG' );
			$row['_api_log_allowed']  = $row['api_log_allowed'] ? 'tick.png' : 'cross.png';
			
			$logs[] = $row;
		}
		
		//-----------------------------------------
		// Print...
		//-----------------------------------------
				
		$this->registry->output->html .= $this->html->api_login_view( $logs, $links );
	}
}