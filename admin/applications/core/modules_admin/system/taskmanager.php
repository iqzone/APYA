<?php
/**
 * @file		taskmanager.php 	Task manager
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * $LastChangedDate: 2012-03-26 12:16:52 -0400 (Mon, 26 Mar 2012) $
 * @version		v3.3.3
 * $Revision: 10490 $
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
class admin_core_system_taskmanager extends ipsCommand
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
		
		/* URLs */
		$this->form_code    = $this->html->form_code = 'module=system&amp;section=taskmanager';
		$this->form_code_js = $this->html->form_code_js = 'module=system&section=taskmanager';
		
		switch( $this->request['do'] )
		{		
			case 'cron':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'task_manage' );
				$this->showCronPage();
			break;
		
			case 'task_add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'task_manage' );
				$this->taskManagerForm( 'add' );
			break;
				
			case 'task_edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'task_manage' );
				$this->taskManagerForm( 'edit' );
			break;
				
			case 'task_add_do':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'task_manage' );
				$this->taskManagerSave( 'add' );
			break;
				
			case 'task_edit_do':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'task_manage' );
				$this->taskManagerSave( 'edit' );
			break;
				
			case 'task_run_now':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'task_run_lock' );
				$this->taskManagerRunTask();
			break;
				
			case 'task_unlock':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'task_run_lock' );
				$this->taskManagerUnlockTask();
			break;
				
			case 'task_delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'task_remove' );
				$this->taskDelete();
			break;
				
			case 'task_export_xml':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'task_manage' );
				$this->tasksExportToXML();
			break;
			
			case 'task_export':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'task_manage' );
				$this->taskExport();
			break;
			
			case 'task_import':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'task_manage' );
				$this->taskImport();
			break;

			case 'task_rebuild_xml':
			case 'tasksImportAllApps':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'task_manage' );
				$this->tasksImportAllApps();
			break;
			case 'overview':
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'task_manage' );
				$this->request['do'] = 'overview';
				$this->taskManagerOverview();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Perform the tasks import
	 *
	 * @return	@e void
	 */
	public function taskImport()
	{
		$content = $this->registry->getClass('adminFunctions')->importXml();
		
		//-----------------------------------------
		// Got anything?
		//-----------------------------------------
		
		if ( ! $content )
		{
			$this->registry->output->global_message = $this->lang->words['tupload_failed'];
			$this->_bbcodeStart();
			return;
		}

		//-----------------------------------------
		// Get xml class
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH . 'classXML.php' );/*noLibHook*/

		$xml = new classXML( IPS_DOC_CHAR_SET );
		$xml->loadXML( $content );
		
		//-----------------------------------------
		// Get current custom bbcodes
		//-----------------------------------------
		
		$tasks = array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'task_manager' ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$tasks[ $r['task_key'] ] = 1;
		}
		
		//-----------------------------------------
		// pArse
		//-----------------------------------------
		
		foreach( $xml->fetchElements('row') as $task )
		{
			$entry  = $xml->fetchElementsFromRecord( $task );

			unset($entry['task_id']);
			unset($entry['task_next_run']);
			
			/* Update */
			$entry['task_cronkey']     = ( $entry['task_cronkey'] )     ? $entry['task_cronkey']     : md5( uniqid( microtime() ) );
			$entry['task_next_run']    = ( $entry['task_next_run'] )    ? $entry['task_next_run']    : time();
			$entry['task_description'] = ( $entry['task_description'] ) ? $entry['task_description'] : '';	
				
			if ( $tasks[ $entry['task_key'] ] )
			{
				$this->DB->update( 'task_manager', $entry, "task_key='" . $entry['task_key'] . "'" );
			}
			else
			{
				$this->DB->insert( 'task_manager', $entry );
				$tasks[ $entry['task_key'] ] = $entry;
			}
		}
		
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		/* Bounce */
		$this->registry->output->global_message = $this->lang->words['t_simport_success'];
		$this->taskManagerOverview();
	}
	
	/**
	 * Delete a task
	 *
	 * @return	@e void
	 */
	public function taskDelete()
	{
		/* INIT */
		$task_id = intval( $this->request['task_id'] );
		
		/* Check to see if this is a valid task */
		$task = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'task_manager', 'where' => "task_id={$task_id}" ) );
			
		if ( $task['task_safemode'] and ! IN_DEV )
		{
			$this->registry->output->global_message = $this->lang->words['t_nodelete'];
			$this->taskManagerOverview();
			return;
		}
		
		/* Delete this task */
		$this->DB->delete( 'task_manager', 'task_id='.$task['task_id'] );
		
		/* Save next date and bounce */
		$this->func_taskmanager->saveNextRunStamp();
		
		$this->registry->output->global_message = $this->lang->words['t_deleted'];		
		$this->taskManagerOverview();
	}	
	
	/**
	 * Unlock a task
	 *
	 * @return	@e void
	 */
	public function taskManagerUnlockTask()
	{
		/* Unlock */
		$this->func_taskmanager->unlockTask( intval($this->request['task_id']) );
		
		/* Bounce */
		$this->registry->output->global_message = $this->lang->words['t_locknomore'];
		$this->taskManagerOverview();
		return;
	}	
	
	/**
	 * Runs the selected task
	 *
	 * @return	@e void
	 */
	public function taskManagerRunTask()
	{
		/* INIT */
		$task_id = intval( $this->request['task_id'] );
		
		/* Check ID */
		if ( ! $task_id )
		{
			$this->registry->output->global_message = $this->lang->words['t_noid'];
			$this->taskManagerOverview();
			return;
		}
		
		/* Query the task */
		$this_task = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'task_manager', 'where' => 'task_id=' . $task_id ) );
		
		/* NO task found */
		if ( ! $this_task['task_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['t_notask'];
			$this->taskManagerOverview();
			return;
		}
		
		/* Disabled? */
		if ( ! $this_task['task_enabled'] )
		{
			$this->registry->output->global_message = $this->lang->words['t_disabled'];
			$this->taskManagerOverview();
			return;
		}

		/* Locked */
		if ( $this_task['task_locked'] > 0 && ! IN_DEV )
		{
			$this->registry->output->global_message = sprintf( $this->lang->words['t_locked'], gmstrftime( '%c', $this_task['task_locked'] ) );
			$this->taskManagerOverview();
			return;
		}
		
		/* Get the next rund ate and then update the task */
		$newdate = $this->func_taskmanager->generateNextRun( $this_task );
				
		$this->DB->update( 'task_manager', array( 'task_next_run' => $newdate, 'task_locked' => time() ), "task_id=".$task_id );
		
		$this->func_taskmanager->saveNextRunStamp();

		/* Run the task file */
		if( is_file( IPSLib::getAppDir( $this_task['task_application'] ) . '/tasks/' . $this_task['task_file'] ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( $this_task['task_application'] ) . '/tasks/' . $this_task['task_file'], 'task_item', $this_task['task_application'] );
			$myobj = new $classToLoad( $this->registry, $this->func_taskmanager, $this_task );
			$myobj->runTask();
			
			/* Bounce */
			$this->registry->output->global_message = $this->lang->words['t_ran'];
			$this->taskManagerOverview();
			return;
		}
		/* Error locating task file */
		else
		{
			/* Bounce */
			$this->registry->output->global_message = sprintf( $this->lang->words['t_nolocate'], IPSLib::getAppDir( $this_task['task_application'] ),  $this_task['task_file'] );
			$this->taskManagerOverview();
			return;
		}
	}
	
	/**
	 * Save the add/edit task form
	 *
	 * @param	string		$type		Either add or edit data	 
	 * @return	@e void
	 */
	public function taskManagerSave( $type='add' )
	{
		/* INIT */
		$task_id      = intval( $this->request['task_id'] );
		$task_cronkey = $this->request['task_cronkey'];
		
		/* Check for ID */
		if ( $type == 'edit' )
		{
			if ( ! $task_id )
			{
				$this->registry->output->global_message = $this->lang->words['t_noid'];
				$this->taskManagerForm();
				return;
			}
		}
		
		/* Check for title */
		if ( ! $this->request['task_title'] )
		{
			$this->registry->output->global_message = $this->lang->words['t_entertitle'];
			$this->taskManagerForm();
			return;
		}
		
		/* Check for file */
		if ( ! $this->request['task_file'] )
		{
			$this->registry->output->global_message = $this->lang->words['t_entername'];
			$this->taskManagerForm();
			return;
		}
		
		/* Create the database array */
		$save = array( 'task_title'       => $this->request['task_title'],
					   'task_description' => $this->request['task_description'],
					   'task_file'        => $this->request['task_file'],
					   'task_week_day'    => $this->request['task_week_day'],
					   'task_month_day'   => $this->request['task_month_day'],
					   'task_hour'        => $this->request['task_hour'],
					   'task_minute'      => $this->request['task_minute'],
					   'task_log'		  => $this->request['task_log'],
					   'task_cronkey'     => $this->request['task_cronkey'] ? $task_cronkey : md5(microtime()),
					   'task_enabled'     => $this->request['task_enabled'],
					   'task_application' => $this->request['task_application'],
					   'task_key'         => $this->request['task_key']
					 );
		
		if ( IN_DEV )
		{
			$save['task_safemode'] = $this->request['task_safemode'];
		}

		/* Find out the next weekday */		
		if( $this->request['task_week_day'] != -1 )
		{
			$week_days = array(
								0 => 'Sunday',
								1 => 'Monday',
								2 => 'Tuesday',
								3 => 'Wednesday',
								4 => 'Thursday',
								5 => 'Friday',
								6 => 'Saturday',
							);
							
			$_ts = strtotime( "Next {$week_days[$this->request['task_week_day']]}" );
			
			$this->func_taskmanager->date_now['minute']      = intval( gmdate( 'i', $_ts ) );
			$this->func_taskmanager->date_now['hour']        = intval( gmdate( 'H', $_ts ) );
			$this->func_taskmanager->date_now['wday']        = intval( gmdate( 'w', $_ts ) );
			$this->func_taskmanager->date_now['mday']        = intval( gmdate( 'd', $_ts ) );
			$this->func_taskmanager->date_now['month']       = intval( gmdate( 'm', $_ts ) );
			$this->func_taskmanager->date_now['year']        = intval( gmdate( 'Y', $_ts ) );			
		}
		
		/* Get next run date */
		$save['task_next_run'] = $this->func_taskmanager->generateNextRun( $save );
		
		/* Edit */
		if ( $type == 'edit' )
		{
			$this->DB->update( 'task_manager', $save, 'task_id='.$task_id );
			$this->registry->output->global_message = $this->lang->words['t_edited'];
		}
		/* Add */
		else
		{
			$this->DB->insert( 'task_manager', $save );
			$this->registry->output->global_message = $this->lang->words['t_saved'];
		}
		
		/* Save next run and bounce */
		$this->func_taskmanager->saveNextRunStamp();		
		$this->taskManagerOverview();
	}	
	
	/**
	 * Builds the add/edit task form
	 *
	 * @param	string		$type		Either add or edit
	 * @return	@e void
	 */
	public function taskManagerForm( $type='add' )
	{
		/* INIt */		
		$form     = array();
		$task_id  = intval( $this->request['task_id'] );
		$dropdows = array();
		$apps     = array();
		
		/* Application drop down options */
		foreach( ipsRegistry::$applications as $app_dir => $app_data )
		{
			$apps[] = array( $app_dir, $app_data['app_title'] );
		}
		
		/* Edit Task Form */
		if ( $type == 'edit' )
		{
			/* Form Data */
			$task  = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'task_manager', 'where' => "task_id={$task_id}" ) );
			
			/* Form bits */
			$title   = sprintf( $this->lang->words['task_edit_title'], $task['task_title'] );
			$button  = $this->lang->words['edittask_button'];
			$formbit = "task_edit_do";
		}
		/* Add Task Form */
		else
		{
			/* Form Bits */
			$button  = $this->lang->words['t_create'];
			$formbit = "task_add_do";			
			$title   = $this->lang->words['t_creating'];
			
			/* Form Data */
			$task    = array();
		}
		
		/* Create dropdown data */
		$dropdown['_minute'] = array( 0 => array( '-1', $this->lang->words['t_minute']   ) );
		$dropdown['_hour']   = array( 0 => array( '-1', $this->lang->words['t_hour']    ), 1 => array( '0', $this->lang->words['t_midnight'] ) ); 
		$dropdown['_wday']   = array( 0 => array( '-1', $this->lang->words['t_dayweek'] ) );
		$dropdown['_mday']   = array( 0 => array( '-1', $this->lang->words['t_daymonth'] ) );
		
		for( $i = 0 ; $i < 60; $i++ )
		{
			$dropdown['_minute'][] = array( $i, $i );
		}
		
		for( $i = 1 ; $i < 24; $i++ )
		{
			if ( $i < 12 )
			{
				$ampm = $i. $this->lang->words['t_am'];
			}
			else if ( $i == 12 )
			{
				$ampm = $this->lang->words['t_midday'];
			}
			else
			{
				$ampm = $i - 12 . $this->lang->words['t_pm'];
			}
			
			$dropdown['_hour'][] = array( $i, $i. ' - ('.$ampm.')' );
		}
		
		for( $i = 1 ; $i < 32; $i++ )
		{
			$dropdown['_mday'][] = array( $i, $i );
		}
		
		$dropdown['_wday'][]  = array( '0', $this->lang->words['t_sunday']    );
		$dropdown['_wday'][]  = array( '1', $this->lang->words['t_monday']    );
		$dropdown['_wday'][]  = array( '2', $this->lang->words['t_tuesday']   );
		$dropdown['_wday'][]  = array( '3', $this->lang->words['t_wednesday'] );
		$dropdown['_wday'][]  = array( '4', $this->lang->words['t_thursday']  );
		$dropdown['_wday'][]  = array( '5', $this->lang->words['t_friday']    );
		$dropdown['_wday'][]  = array( '6', $this->lang->words['t_saturday']  );
		
		/* Form Elements */
		$form['task_title']       = $this->registry->output->formInput(       'task_title'      , $this->request['task_title']       ? $this->request['task_title']       : $task['task_title'] );
		$form['task_description'] = $this->registry->output->formInput(       'task_description', $this->request['task_description'] ? $this->request['task_description'] : $task['task_description'] );
		$form['task_file']        = $this->registry->output->formSimpleInput( 'task_file'       , $this->request['task_file']        ? $this->request['task_file']        : $task['task_file']       , '20' );
		$form['task_minute']      = $this->registry->output->formDropdown(    'task_minute'     , $dropdown['_minute']               , $this->request['task_minute']      ? $this->request['task_minute']    : $task['task_minute']  ,  '', 'onchange="updatepreview()"' );
		$form['task_hour']        = $this->registry->output->formDropdown(    'task_hour'       , $dropdown['_hour']                 , $this->request['task_hour']        ? $this->request['task_hour']      : $task['task_hour']     , '', 'onchange="updatepreview()"' );
	    $form['task_week_day']    = $this->registry->output->formDropdown(    'task_week_day'   , $dropdown['_wday']                 , $this->request['task_week_day']    ? $this->request['task_week_day']  : $task['task_week_day'] , '', 'onchange="updatepreview()"' );
		$form['task_month_day']   = $this->registry->output->formDropdown(    'task_month_day'  , $dropdown['_mday']                 , $this->request['task_month_day']   ? $this->request['task_month_day'] : $task['task_month_day'], '', 'onchange="updatepreview()"' );
		$form['task_log']         = $this->registry->output->formYesNo(       'task_log'        , $this->request['task_log']         ? $this->request['task_log']         : $task['task_log'] );
		$form['task_enabled']     = $this->registry->output->formYesNo(       'task_enabled'    , $this->request['task_enabled']     ? $this->request['task_enabled']     : $task['task_enabled'] );
		$form['task_application'] = $this->registry->output->formDropdown(    'task_application', $apps, $this->request['task_application'] ? $this->request['task_application'] : $task['task_application'] );
		$form['task_key']		  = $this->registry->output->formInput(       'task_key'        , $this->request['task_key']         ? $this->request['task_key']         : $task['task_key'] );
		
		if ( IN_DEV )
		{
			$form['task_safemode'] = $this->registry->output->formYesNo( 'task_safemode', $this->request['task_safemode'] ? $this->request['task_safemode'] : $task['task_safemode'] );
		}
		
		$this->registry->output->html .= $this->html->taskManagerForm( $form, $button, $formbit, $type, $title, $task );
		
		if ( $type == 'add' )
		{
			$this->registry->output->extra_nav[] = array( "", $this->lang->words['t_adding'] );
		}
		else
		{
			$this->registry->output->extra_nav[] = array( "", sprintf( $this->lang->words['t_editing'], $task['task_title'] ) );
		}
	}	
	
	/**
	 * Builds the task mananger overview screen
	 *
	 * @return	@e void
	 */
	public function taskManagerOverview()
	{
		/* INIT */
		$tasks   = array();
		$row     = array();
		$content = "";
		
		/* Query the tasks */
		$this->DB->build( array( 'select' => '*', 'from' => 'task_manager', 'order' => 'task_safemode, task_next_run' ) );
		$this->DB->execute();
		
		/* Loop through and build the output array */
		while ( $row = $this->DB->fetch() )
		{
			if( !IPSLib::appIsInstalled( $row['task_application'] ) )
			{
				continue;
			}

			$row['task_minute']    = $row['task_minute']    != '-1' ? $row['task_minute']    : '-';
			$row['task_hour']      = $row['task_hour']      != '-1' ? $row['task_hour']      : '-';
			$row['task_month_day'] = $row['task_month_day'] != '-1' ? $row['task_month_day'] : '-';
			$row['task_week_day']  = $row['task_week_day']  != '-1' ? $row['task_week_day']  : '-';
			
			$row['_next_run'] = gmstrftime( '%c', $row['task_next_run'] );
			
			if ( $row['task_enabled'] != 1 )
			{
				$row['_style']    = " style='color:gray'";
				$row['_title']    = ' ' . $this->lang->words['t_disabledcaps'];
				$row['_next_run'] = "<s>{$row['_next_run']}</s>";
			}
			
			if ( $row['task_locked'] )
			{
				$row['_title'] .= ' ' . $this->lang->words['t_lockedcaps'];
			}
			
			$tasks[ $row['task_application'] ][] = $row;
		}
		
		/* Output */
		$this->registry->output->html .= $this->html->taskManagerOverview( $tasks, gmstrftime( '%c' ) );
	}
	
	/**
	 * Shows the cron information screen
	 *
	 * @return	@e void
	 */
	public function showCronPage()
	{
		if ( !$this->settings['task_cron_key'] )
		{
			$this->settings['task_cron_key'] = md5( uniqid() );
		}
		if ( isset( $this->request['toggle'] ) )
		{
			$this->settings['task_use_cron'] = intval( $this->request['toggle'] );
		}
		IPSLib::updateSettings( array( 'task_cron_key' => $this->settings['task_cron_key'], 'task_use_cron' => $this->settings['task_use_cron'] ) );
	
		if ( isset( $this->request['toggle'] ) )
		{
			$this->registry->output->global_message = $this->lang->words['task_cron_mode_toggled_' . $this->settings['task_use_cron']];
			$this->taskManagerOverview();
		}
		else
		{
			$this->registry->output->html .= $this->html->taskCrons();
		}
	}	
	
	/**
	 * Imports tasks from XML
	 *
	 * @param	string		$file			Filename to import tasks from
	 * @param	boolean		$no_return		Set to return true/false, instead of displaying results
	 * @return	@e mixed	True if $no_return is enabled, otherwise void
	 */	
	public function tasksImportFromXML( $file='', $no_return=false )
	{
		/* INIT */
		$file     = ( $file ) ? $file : IPS_PUBLIC_PATH . 'resources/tasks.xml';
		$inserted = 0;
		$updated  = 0;
		$tasks    = array();
		
		/* Check to see if the file exists */
		if ( ! is_file( $file ) )
		{
			$this->registry->output->global_message = sprintf( $this->lang->words['t_import404'], $file );
			$this->taskManagerOverview();
			return;
		}
		
		$content	= @file_get_contents( $file );
		
		/* Grab current tasks */
		$this->DB->build( array( 'select' => '*', 'from' => 'task_manager' ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$tasks[ $row['task_key'] ] = $row;
		}
		
		/* Get the XML class */
		require_once( IPS_KERNEL_PATH.'classXML.php' );/*noLibHook*/

		$xml = new classXML( IPS_DOC_CHAR_SET );
		$xml->loadXML( $content );

		/* Loop through the tasks */
		foreach( $xml->fetchElements('row') as $record )
		{
			$entry  = $xml->fetchElementsFromRecord( $record );
			$_key	= $entry['task_key'];
			
			$entry['task_cronkey']		= $tasks[ $_key ]['task_cronkey'] ? $tasks[ $_key ]['task_cronkey'] : md5( uniqid( microtime() ) );
			$entry['task_next_run']		= $tasks[ $_key ]['task_next_run'] ? $tasks[ $_key ]['task_next_run'] : time();
			$entry['task_description']	= $entry['task_description'] ? $entry['task_description'] : '';
			
			unset( $entry['task_id'] );
				
			if ( $tasks[ $_key ]['task_key'] )
			{
				unset($entry['task_cronkey']);
				unset($entry['task_enabled']);

				$updated++;
				$this->DB->update( 'task_manager', $entry, "task_key='" . $tasks[ $_key ]['task_key'] . "'" );
			}
			else
			{
				$inserted++;
				$this->DB->insert( 'task_manager', $entry );
				$tasks[ $_key ] = $entry;
			}
		}
		
		/* Return or Bounce */
		if ( $no_return )
		{
			$this->registry->output->global_message = sprintf( $this->lang->words['t_inserted'], $inserted, $updated );
			return TRUE;
		}
		else
		{
			$this->registry->output->global_message = sprintf( $this->lang->words['t_inserted'], $inserted, $updated );
			$this->taskManagerOverview();
		}
	}
	
	/**
	 * Import all tasks from XML
	 *
	 * @return	@e void
	 */
	public function tasksImportAllApps()
	{
		/* INIT */
		$tasks = array();
		$_gmsg = array();
		
		/* Grab current tasks */
		$this->DB->build( array( 'select' => '*', 'from' => 'task_manager' ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$tasks[ $row['task_key'] ] = $row;
		}
		
		/* Grab XML class */
		require_once( IPS_KERNEL_PATH . 'classXML.php' );/*noLibHook*/
		
		/* Loop through all the applications */
		foreach( $this->registry->getApplications() as $app => $__data )
		{
			$stats = array( 'inserted' => 0, 'updated' => 0 );
			$xml   = new classXML( IPS_DOC_CHAR_SET );
			$file  = IPSLib::getAppDir(  $app ) . '/xml/' . $app . '_tasks.xml';
			
			if( is_file( $file ) )
			{
				$xml->load( $file );
				
				foreach( $xml->fetchElements('row') as $task )
				{
					$entry = $xml->fetchElementsFromRecord( $task );
					
					/* Remove unneeded */
					unset( $entry['task_id'] );
					unset( $entry['task_next_run'] );
					
					/* Update */
					$entry['task_cronkey']     = ( $entry['task_cronkey'] )     ? $entry['task_cronkey']     : md5( uniqid( microtime() ) );
					$entry['task_next_run']    = ( $entry['task_next_run'] )    ? $entry['task_next_run']    : time();
					$entry['task_description'] = ( $entry['task_description'] ) ? $entry['task_description'] : '';
					$entry['task_locked']      = intval( $entry['task_locked'] );	
						
					if ( $tasks[ $entry['task_key'] ]['task_key'] )
					{
						unset($entry['task_cronkey']);
						unset($entry['task_enabled']);

						$stats['updated']++;
						$this->DB->update( 'task_manager', $entry, "task_key='" . $entry['task_key'] . "'" );
					}
					else
					{
						$stats['inserted']++;
						$this->DB->insert( 'task_manager', $entry );
						
						$tasks[ $entry['task_key'] ] = $entry;
					}
				}
			}
			
			$_gmsg[] = $app . ': ' . sprintf( $this->lang->words['t_inserted'], $stats['inserted'], $stats['updated'] );
			
			/* In dev time stamp? */
			if ( IN_DEV )
			{
				$cache = $this->caches['indev'];
				$cache['import']['tasks'][ $app ] = time();
				$this->cache->setCache( 'indev', $cache, array( 'donow' => 1, 'array' => 1 ) );
			}
		}
		
		/* Got a global message? */
		if ( count($_gmsg) )
		{
			$this->registry->output->setMessage( implode( '<br />', $_gmsg ), 1 );
		}
		
		/* Return */
		$this->taskManagerOverview();
	}

	/**
	 * Export a single task
	 *
	 * @return	@e void
	 */
	public function taskExport()
	{
		/* INIT */
		$entry	= array();
		$id		= intval($this->request['task_id']);
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );/*noLibHook*/

		$xml = new classXML( IPS_DOC_CHAR_SET );
		$xml->newXMLDocument();
		$xml->addElement( 'export' );
		$xml->addElement( 'group', 'export' );

		/* Query tasks */
		$r	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'task_manager', 'where' => "task_id='{$id}'" ) );		

		$xml->addElementAsRecord( 'group', 'row', $r );
			
		/* Finish XML */
		$doc = $xml->fetchDocument();

		$this->registry->output->showDownload( $doc, 'task.xml', '', 0 );
	}

	/**
	 * Export tasks to XML
	 *
	 * @return	@e void
	 */
	public function tasksExportToXML()
	{
		/* INIT */
		$entry = array();
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );/*noLibHook*/
		
		/* Loop through all the applications */
		foreach( $this->registry->getApplications() as $app => $__data )
		{
			$_c  = 0;
			$xml = new classXML( IPS_DOC_CHAR_SET );
			$xml->newXMLDocument();
			$xml->addElement( 'export' );
			$xml->addElement( 'group', 'export' );
			
			/* Query tasks */
			$this->DB->build( array( 'select' => '*', 'from' => 'task_manager', 'where' => "task_application='{$app}'" ) );		
			$this->DB->execute();
			
			/* Loop through and add tasks to XML */
			while ( $r = $this->DB->fetch() )
			{
				$_c++;
				$xml->addElementAsRecord( 'group', 'row', $r );
			}
			
			/* Finish XML */
			$doc = $xml->fetchDocument();
			
			/* Write */
			if ( $doc  AND $_c )
			{
				file_put_contents( IPSLib::getAppDir( $app ) . '/xml/' . $app . '_tasks.xml', $doc );
			}
		}
		
		$this->registry->output->setMessage( $this->lang->words['t_exported'], 1 );
		$this->taskManagerOverview();
	}
}