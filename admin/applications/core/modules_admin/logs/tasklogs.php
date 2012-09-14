<?php
/**
 * @file		taskmanager.php 	Task manager
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: mark $
 * $LastChangedDate: 2012-04-02 11:17:19 -0400 (Mon, 02 Apr 2012) $
 * @version		v3.3.3
 * $Revision: 10537 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		admin_core_system_taskmanager
 * @brief		Task manager
 */
class admin_core_logs_tasklogs extends ipsCommand
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
		/* Load Class */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/class_taskmanager.php', 'class_taskmanager' );
		$this->func_taskmanager = new $classToLoad( $registry );
		
		/* Load Skin and Language */
		$this->html = $this->registry->output->loadTemplate('cp_skin_system');
				
		$this->registry->class_localization->loadLanguageFile( array( 'admin_system' ) );
		
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=logs&section=tasklogs', $this->lang->words['sched_error_logs'] );
		
		/* URLs */
		$this->form_code    = $this->html->form_code = 'module=logs&amp;section=tasklogs';
		$this->form_code_js = $this->html->form_code_js = 'module=logs&section=tasklogs';
		
		switch( $this->request['do'] )
		{	
			default:
			case 'task_logs':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tasklogs_view' );
				$this->taskLogsOverview();
			break;
			
			case 'task_log_show':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tasklogs_view' );
				$this->taskLogsShow();
			break;
				
			case 'task_log_delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tasklogs_delete' );
				$this->taskLogsDelete();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Remove logs for a specific task or all
	 *
	 * @return	@e void
	 */
	public function taskLogsDelete()
	{
		/* INIT */
		$prune = intval( $this->request['task_prune'] ) ? intval( $this->request['task_prune'] ) : 30;
		$prune = time() - ( $prune * 86400 );
		
		if( $this->request['task_title'] != -1 )
		{
			$where = "log_title='{$this->request['task_title']}' AND log_date > {$prune}";
		}
		else
		{
			$where = "log_date > {$prune}";
		}
		
		/* Delete */
		$this->DB->delete( 'task_logs', $where );
		
		/* Bounce */
		$this->registry->output->global_message = $this->lang->words['t_removed'];
		$this->taskLogsOverview();
	}	
	
	/**
	 * Show task logs
	 *
	 * @return	@e void
	 */
	public function taskLogsShow()
	{		
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_logs' ) );
				
		/* INIT */
		$limit = intval( $this->request['task_count'] ) ? intval( $this->request['task_count'] ) : 30;
		$limit = $limit > 150 ? 150 : $limit;
		
		/* Query the tasks */
		if ( $this->request['task_title'] != -1 )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'task_logs', 'where' => "log_title='".$this->request['task_title']."'", 'order' => 'log_date DESC', 'limit' => array(0,$limit) ) );
		}
		else
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'task_logs', 'order' => 'log_date DESC', 'limit' => array(0,$limit) ) );
		}
		
		$this->DB->execute();
		
		/* Loop through the tasks */
		$rows = array();
		
		while( $row = $this->DB->fetch() )
		{
			$row['log_date'] = ipsRegistry::getClass( 'class_localization')->getDate( $row['log_date'], 'TINY' );
			$rows[] = $row;
		}
		
		$this->registry->output->html .= $this->html->taskManagerLogsShowWrapper( $rows );
	}	
	
	/**
	 * Builds the task log overview screen
	 *
	 * @return	@e void
	 */
	public function taskLogsOverview()
	{
		//-----------------------------------------
		// Fix up navigation bar
		//-----------------------------------------
		
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_logs' ) );
		
		$this->registry->output->core_nav		= array();
		$this->registry->output->ignoreCoreNav	= true;
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=tools', $this->lang->words['nav_toolsmodule'] );
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=tools&section=logsSplash', $this->lang->words['nav_logssplash'] );
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=system&section=taskmanager&do=task_logs', $this->lang->words['sched_error_logs'] );
		
		/* INIT */
		$tasks = array( 0 => array( -1, 'All tasks' ) );
		$last5 = "";
		$form  = array();
		
		/* Get thet ask titles */
		$this->DB->build( array( 'select' => '*', 'from' => 'task_manager', 'order' => 'task_title' ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$tasks[] = array( $r['task_title'], $r['task_title'] );
		}
		
		/* Get the last 5 logs */
		$this->DB->build( array( 'select' => '*', 'from' => 'task_logs', 'order' => 'log_date DESC', 'limit' => array(0,5) ) );
		$this->DB->execute();
		
		$last5 = array();
		while ( $row = $this->DB->fetch() )
		{
			//$row['log_desc'] = IPSText::truncate( $row['log_desc'] );
			$row['log_date'] = $this->registry->class_localization->getDate( $row['log_date'], 'TINY' );
			$last5[] = $row;
		}
		
		/* Build the form elements */
		$form['task_title']         = $this->registry->output->formDropdown( 'task_title', $tasks, $this->request['task_title'] );
		$form['task_title_delete']  = $this->registry->output->formDropdown( 'task_title', $tasks, $this->request['task_title_delete'] );
		$form['task_count']         = $this->registry->output->formInput(    'task_count', $this->request['task_count'] ? $this->request['task_count'] : 30 );
		$form['task_prune']         = $this->registry->output->formInput(    'task_prune', $this->request['task_prune'] ? $this->request['task_prune'] : 30 );
		
		/* Output */
		$this->registry->output->html .= $this->html->taskManagerLogsOverview( $last5, $form );
	}	
}