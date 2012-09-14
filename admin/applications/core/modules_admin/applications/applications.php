<?php
/**
 * @file		applications.php 	Applications management
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		15 January 2007
 * $LastChangedDate: 2012-05-31 11:34:45 -0400 (Thu, 31 May 2012) $
 * @version		v3.3.3
 * $Revision: 10844 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		admin_core_applications_applications
 * @brief		Applications management
 */
class admin_core_applications_applications extends ipsCommand
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
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_applications' );
		$this->registry->class_localization->loadLanguageFile( array( 'admin_applications' ) );
		
		$this->form_code	= $this->html->form_code	= 'module=applications&amp;section=applications&amp;';
		$this->form_code_js	= $this->html->form_code_js	= 'module=applications&section=applications&';
		
		//-----------------------------------------
		// What to do...
		//-----------------------------------------
		

		switch( $this->request['do'] )
		{
			default :
			case 'applications_overview' :
				$this->request['do'] = 'applications_overview';
				$this->applicationsOverview();
				break;
			case 'application_details' :
				$this->applicationViewDetails();
				break;
			case 'application_manage_position' :
				$this->registry->getClass( 'class_permissions' )->checkPermissionAutoMsg( 'app_manage' );
				$this->applicationManagePosition();
				break;
			case 'application_edit' :
				$this->registry->getClass( 'class_permissions' )->checkPermissionAutoMsg( 'app_manage' );
				$this->applicationForm( 'edit' );
				break;
			case 'application_add' :
				$this->registry->getClass( 'class_permissions' )->checkPermissionAutoMsg( 'app_install' );
				$this->applicationForm( 'add' );
				break;
			case 'application_edit_do' :
				$this->registry->getClass( 'class_permissions' )->checkPermissionAutoMsg( 'app_manage' );
				$this->applicationSave( 'edit' );
				break;
			case 'application_add_do' :
				$this->registry->getClass( 'class_permissions' )->checkPermissionAutoMsg( 'app_install' );
				$this->applicationSave( 'add' );
				break;
			case 'application_remove' :
				$this->registry->getClass( 'class_permissions' )->checkPermissionAutoMsg( 'app_delete' );
				$this->applicationRemove();
				break;
			case 'application_remove_splash' :
				$this->registry->getClass( 'class_permissions' )->checkPermissionAutoMsg( 'app_delete' );
				$this->applicationRemoveSplash();
				break;
			case 'toggle_app' :
				$this->registry->getClass( 'class_permissions' )->checkPermissionAutoMsg( 'app_manage' );
				$this->applicationToggle();
				break;
			case 'inDevExportApps' :
				$this->registry->getClass( 'class_permissions' )->checkPermissionAutoMsg( 'app_manage' );
				$this->inDevApplicationsExport();
				break;
			case 'inDevRebuildAll' :
				$this->inDevRebuildAll();
				break;
			case 'inDevExportAll' :
				$this->inDevExportAll();
				break;
			case 'module_recache_all' :
				$this->moduleRecacheAll();
				break;
			case 'modules_overview' :
				$this->modules_overview();
				break;
			case 'module_edit' :
				$this->registry->getClass( 'class_permissions' )->checkPermissionAutoMsg( 'module_manage' );
				$this->moduleForm( 'edit' );
				break;
			case 'module_add' :
				$this->registry->getClass( 'class_permissions' )->checkPermissionAutoMsg( 'module_manage' );
				$this->moduleForm( 'add' );
				break;
			case 'module_edit_do' :
				$this->registry->getClass( 'class_permissions' )->checkPermissionAutoMsg( 'module_manage' );
				$this->moduleSave( 'edit' );
				break;
			case 'module_add_do' :
				$this->registry->getClass( 'class_permissions' )->checkPermissionAutoMsg( 'module_manage' );
				$this->moduleSave( 'add' );
				break;
			case 'module_manage_position' :
				$this->registry->getClass( 'class_permissions' )->checkPermissionAutoMsg( 'module_manage' );
				$this->moduleManagePosition();
				break;
			case 'module_remove' :
				$this->registry->getClass( 'class_permissions' )->checkPermissionAutoMsg( 'module_delete' );
				$this->moduleRemove();
				break;
			case 'module_export' :
				$this->registry->getClass( 'class_permissions' )->checkPermissionAutoMsg( 'module_manage' );
				$this->moduleExport();
				break;
			case 'module_import' :
				$this->registry->getClass( 'class_permissions' )->checkPermissionAutoMsg( 'module_manage' );
				$this->moduleImport();
				break;
			
			case 'sphinxBuildConf' :
				$this->registry->getClass( 'class_permissions' )->checkPermissionAutoMsg( 'build_sphinx' );
				$this->sphinxBuildConf();
				break;
			
			case 'sphinxBuildCron' :
				$this->registry->getClass( 'class_permissions' )->checkPermissionAutoMsg( 'build_sphinx' );
				$this->sphinxBuildCron();
				break;
			
			case 'seoRebuild' :
				$this->seoRebuild();
				break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		

		$this->registry->getClass( 'output' )->html_main .= $this->registry->getClass( 'output' )->global_template->global_frame_wrapper();
		$this->registry->getClass( 'output' )->sendOutput();
	}
	
	/**
	 * Toggle application enabled/disabled
	 *
	 * @return	@e void
	 */
	public function applicationToggle()
	{
		/* Get application */
		$application = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_applications', 'where' => 'app_id=' . intval( $this->request['app_id'] ) ) );
		
		if ( ! $application['app_id'] )
		{
			$this->registry->output->showError( $this->lang->words['cannot_find_tog_app'], 111161 );
		}
		
		if ( in_array( $application['app_directory'], array( 'core', 'forums', 'members' ) ) and $application['app_enabled'] )
		{
			$this->registry->output->showError( $this->lang->words['cannot_toggle_defaults'], 111161.1 );
		}
		
		/* We're disabling the app? */
		if ( $application['app_enabled'] )
		{
			$this->DB->update( 'core_applications', array( 'app_enabled' => 0, 'app_position' => 0 ), 'app_id=' . $application['app_id'] );
		}
		else
		{
			$appsCount = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'core_applications', 'where' => 'app_enabled=1' ) );
			$appsCount['total'] = intval($appsCount['total']) + 1;
			
			$this->DB->update( 'core_applications', array( 'app_enabled' => 1, 'app_position' => $appsCount['total'] ), 'app_id=' . $application['app_id'] );
		}
		
		/* Recache */
		$this->moduleRecacheAll( 1 );
		
		//-----------------------------------------
		// FURL templates
		//-----------------------------------------

		try
		{
			IPSLib::cacheFurlTemplates();
			IPSLib::cacheGlobalCaches();
		}
		catch( Exception $e )
		{
		}
		
		/* Check for possible hook warnings */
		if ( $application['app_enabled'] )
		{
			// Switch the enabled value here or check will fail with the current cache! 
			ipsRegistry::$applications[$application['app_directory']]['app_enabled'] = 0;
			
			$this->_checkHooksWarnings( $application['app_directory'] );
		}
		else
		{
			// Re-enable the tasks if we're re-activating the app
			$this->DB->update( 'task_manager', array( 'task_enabled' => 1 ), "task_application='{$application['app_directory']}'" );
		}
		
		/* All done, redirect */
		$this->registry->output->global_message = $this->lang->words['app_toggled_ok'];
		$this->applicationsOverview();
	}
	
	/**
	 * Checks if there are new warnings added in
	 * the hooks after disabling an application
	 * 
	 * @param	string	$appDirectory	Application directory to be checked
	 * @return	@e void [Adds text in the global message]
	 */
	public function _checkHooksWarnings( $appDirectory )
	{
		/* Error check */
		if ( ! $appDirectory )
		{
			return;
		}
		
		/* Init vars */
		$warnings = 0;
		
		/*Get any possible application*/
		$this->DB->build( array( 'select' => 'hook_id, hook_requirements',
								 'from'   => 'core_hooks',
								 //This is a bit hackish but there's no way around...
								 'where'  => 'hook_enabled=1 AND hook_requirements LIKE \'%:"' . $this->DB->addSlashes( $appDirectory ) . '";a:%\''
						 )		);
		$outer = $this->DB->execute();
		
		/* Got results? */
		if ( $this->DB->getTotalRows( $outer ) )
		{
			/* Get hooks file for update check */
			$classToLoad = IPSLib::loadActionOverloader( IPSLib::getAppDir( 'core' ) . '/modules_admin/applications/hooks.php', 'admin_core_applications_hooks' );
			$hooksClass = new $classToLoad();
			$hooksClass->makeRegistryShortcuts( $this->registry );
			
			/* Loop */
			while ( $hook = $this->DB->fetch( $outer ) )
			{
				if ( count( $hooksClass->checkHookRequirements( $hook ) ) )
				{
					$warnings ++;
				}
			}
		}
		
		/* Got warnings? */
		if ( $warnings > 0 )
		{
			$this->registry->getClass( 'output' )->html_main .= $this->registry->output->global_template->warning_box( sprintf( $this->lang->words['app_disable_hook_warnings'], ipsRegistry::$applications[$appDirectory]['app_title'], $warnings, $this->settings['base_url'] . '&amp;module=applications&amp;section=hooks&amp;do=hooks_overview' ) ) . '<br />';
		}
	}
	
	/**
	 * Build the FURL templates file into cache
	 *
	 * @return	@e void
	 */
	public function seoRebuild()
	{
		try
		{
			IPSLib::cacheFurlTemplates();
			$msg = $this->lang->words['furl_cache_rebuilt'];
		}
		catch( Exception $e )
		{
			$msg = $e->getMessage();
			
			switch( $msg )
			{
				case 'CANNOT_WRITE' :
					$msg = $this->lang->words['seo_cannot_write'];
					break;
				case 'NO_DATA_TO_WRITE' :
					$msg = $this->lang->words['seo_no_data'];
					break;
			}
		}
		
		$this->registry->output->global_message = $msg;
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'module=tools&amp;section=cache' );
	}
	
	/**
	 * Rebuild the Sphinx conf and returns
	 * a list of delta indexes too
	 *
	 * @return	@e array Array of content and delta indexes
	 */
	public function rebuildSphinxConfig()
	{
		/* Init vars */
		$sphinxTemplate = '';
		$sphinxCompiled = '';
		$sphinxIndexes = array();
		
		/* No main template? Something odd.. */
		if ( is_file( IPS_ROOT_PATH . '/extensions/sphinxTemplate.php' ) )
		{
			require_once ( IPS_ROOT_PATH . '/extensions/sphinxTemplate.php' );/*noLibHook*/
			
			/* Template file found but empty? */
			if ( $sphinxTemplate )
			{
				//-----------------------------------------
				// Replace out the SQL details
				//-----------------------------------------
				
				$sphinxTemplate = str_replace( "<!--SPHINX_CONF_HOST-->", ipsRegistry::$settings['sql_host'], $sphinxTemplate );
				$sphinxTemplate = str_replace( "<!--SPHINX_CONF_USER-->", ipsRegistry::$settings['sql_user'], $sphinxTemplate );
				$sphinxTemplate = str_replace( "<!--SPHINX_CONF_PASS-->", ipsRegistry::$settings['sql_pass'], $sphinxTemplate );
				$sphinxTemplate = str_replace( "<!--SPHINX_CONF_DATABASE-->", ipsRegistry::$settings['sql_database'], $sphinxTemplate );
				$sphinxTemplate = str_replace( "<!--SPHINX_CONF_PORT-->", ipsRegistry::$settings['sql_port'] ? ipsRegistry::$settings['sql_port'] : 3306, $sphinxTemplate );
				
				//-----------------------------------------
				// Loop over the applications and build
				//-----------------------------------------
				

				foreach( ipsRegistry::$applications as $app_dir => $application )
				{
					$appSphinxTemplate = '';
					
					if ( is_file( IPSLib::getAppDir( $app_dir ) . '/extensions/sphinxTemplate.php' ) )
					{
						require_once ( IPSLib::getAppDir( $app_dir ) . '/extensions/sphinxTemplate.php' );/*maybeLibHook*/
						
						if ( $appSphinxTemplate )
						{
							$matches = array();
							preg_match_all( '#source\s+<!--SPHINX_CONF_PREFIX-->(\w+_delta)\s:{1}#i', $appSphinxTemplate, $matches );
							
							if ( is_array( $matches[1] ) && count( $matches[1] ) )
							{
								foreach( $matches[1] as $idx )
								{
									$sphinxIndexes[] = ipsRegistry::$settings['sphinx_prefix'] . $idx;
								}
							}
							
							$appSphinxTemplate = str_replace( "<!--SPHINX_DB_SET_NAMES-->", ( ! empty( ipsRegistry::$settings['sql_charset'] ) ) ? 'sql_query_pre = SET NAMES ' . ipsRegistry::$settings['sql_charset'] : '', $appSphinxTemplate );
				
							$sphinxCompiled .= $appSphinxTemplate;
						}
					}
				}
				
				//-----------------------------------------
				// Replace DB prefix
				//-----------------------------------------
				

				$sphinxCompiled = str_replace( "<!--SPHINX_DB_PREFIX-->", ipsRegistry::$settings['sql_tbl_prefix'], $sphinxCompiled );
				
				//-----------------------------------------
				// And replace out the content with the compilation
				//-----------------------------------------
				

				$sphinxTemplate = str_replace( "<!--SPHINX_CONTENT-->", $sphinxCompiled, $sphinxTemplate );
				
				//-----------------------------------------
				// Replace out the /var/sphinx/ path
				//-----------------------------------------
				

				$sphinxTemplate = str_replace( "<!--SPHINX_BASE_PATH-->", rtrim( ipsRegistry::$settings['sphinx_base_path'], '/' ), $sphinxTemplate );
				$sphinxTemplate = str_replace( "<!--SPHINX_PORT-->", ipsRegistry::$settings['search_sphinx_port'], $sphinxTemplate );
				$sphinxTemplate = str_replace( "<!--SPHINX_CONF_PREFIX-->", ipsRegistry::$settings['sphinx_prefix'], $sphinxTemplate );
				
				//-----------------------------------------
				// Wildcard support on?
				//-----------------------------------------
				

				if ( ipsRegistry::$settings['sphinx_wildcard'] )
				{
					$sphinxTemplate = str_replace( '#infix_fields', 'infix_fields', $sphinxTemplate );
					$sphinxTemplate = str_replace( '#min_infix_len', 'min_infix_len', $sphinxTemplate );
					$sphinxTemplate = str_replace( '#enable_star', 'enable_star', $sphinxTemplate );
				}
			}
			else
			{
				$sphinxTemplate = 'MAIN SPHINX TEMPLATE IS EMPTY';
			}
		}
		else
		{
			$sphinxTemplate = 'MAIN SPHINX TEMPLATE NOT FOUND';
		}
		
		/* Return our data */
		return array( 'content' => $sphinxTemplate, 'deltas' => $sphinxIndexes );
	}
	
	/**
	 * Build the sphinx.conf file and return it for download
	 *
	 * @return	@e void
	 */
	public function sphinxBuildConf()
	{
		/* Get sphinx data */
		$sphinxContent = $this->rebuildSphinxConfig();
		
		/* Download */
		$this->cache->setCache( 'sphinx_config', time(), array( 'array' => 0 ) );
		$this->registry->output->showDownload( $sphinxContent['content'], 'sphinx.conf', '', 0 );
	}
	
	/**
	 * Build and return the cronjobs strings to rebuild the indexes
	 *
	 * @return	@e void
	 */
	public function sphinxBuildCron()
	{
		/* Got a path? Get the data! */
		$sphinxData = empty( $this->request['sphinx_conf_path'] ) ? array() : $this->rebuildSphinxConfig();
		
		/* Output */
		$this->registry->output->extra_nav[] = array( $this->settings['base_url'] . $this->form_code . '&amp;do=sphinxBuildCron', $this->lang->words['sphinx_cronjob_title'] );
		$this->registry->output->html .= $this->html->sphinxConfForm( $sphinxData );
	}
	
	/**
	 * IN DEV Tool to rebuild all data via XML
	 * 
	 * @return	@e void
	 */
	protected function inDevRebuildAll()
	{
		/* Not IN_DEV? */
		if ( ! IN_DEV )
		{
			$this->applicationsOverview();
			return;
		}
		
		$output = array();
		
		/* Do each app */
		foreach( ipsRegistry::$applications as $app_dir => $data )
		{
			$this->request['_app'] = $app_dir;
			$return = $this->moduleImport( '', 1, FALSE );
			$output[] = 'App - ' . $app_dir . " done: " . $return;
			
			/* In dev time stamp? */
			if ( IN_DEV )
			{
				$cache = $this->caches['indev'];
				$cache['import']['modules'][$app_dir] = time();
				$this->cache->setCache( 'indev', $cache, array( 'donow' => 1, 'array' => 1 ) );
			}
		}
		
		/* Recache */
		$this->applicationsRecache();
		$this->applicationsMenuDataRecache();
		$this->moduleRecache();
		
		$this->registry->output->setMessage( implode( "<br />", $output ), 1 );
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=applications_overview' );
	}
	
	/**
	 * IN DEV Tool to rebuild all data via XML
	 * 
	 * @return	@e void
	 */
	protected function inDevExportAll()
	{
		/* Not IN_DEV? */
		if ( ! IN_DEV )
		{
			$this->applicationsOverview();
			return;
		}
		
		$output = array();
		
		/* Do each app */
		foreach( ipsRegistry::$applications as $app_dir => $data )
		{
			$file = IPSLib::getAppDir( $app_dir ) . '/xml/' . $app_dir . '_modules.xml';
			
			if ( is_file( $file ) && ! is_writeable( $file ) )
			{
				$output[] = "Cannot write to " . $file;
				continue;
			}
			
			$this->request['app_dir'] = $app_dir;
			$moduleXML = $this->moduleExport( 1 );
			
			file_put_contents( $file, $moduleXML['xml'] );
			
			$output[] = $app_dir . " modules exported";
		}
		
		$this->registry->output->setMessage( implode( "<br />", $output ), 1 );
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=applications_overview' );
	}
	
	/**
	 * Remove a module
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	public function moduleRemove()
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------
		

		$app_id = intval( $this->request['app_id'] );
		$sys_module_id = intval( $this->request['sys_module_id'] );
		
		//-----------------------------------------
		// Got an application?
		//-----------------------------------------
		

		$application = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_applications', 'where' => 'app_id=' . $app_id ) );
		
		if ( ! $application['app_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['a_noid'];
			$this->modules_overview();
			return;
		}
		
		//-----------------------------------------
		// Got a module?
		//-----------------------------------------
		

		$module = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_sys_module', 'where' => 'sys_module_id=' . $sys_module_id ) );
		
		if ( ! $module['sys_module_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['a_noid'];
			$this->modules_overview();
			return;
		}
		
		//-----------------------------------------
		// Protected?
		//-----------------------------------------
		

		if ( ! IN_DEV and $module['sys_module_protected'] )
		{
			$this->registry->output->global_message = $this->lang->words['a_protectedmod'];
			$this->modules_overview();
			return;
		}
		
		//-----------------------------------------
		// Remove...
		//-----------------------------------------
		

		$this->DB->delete( 'core_sys_module', 'sys_module_id=' . $sys_module_id );
		
		//-----------------------------------------
		// Recache
		//-----------------------------------------
		

		$this->moduleRecache();
		
		//-----------------------------------------
		// Done...
		//-----------------------------------------
		

		$this->registry->output->global_message = $this->lang->words['a_removed'];
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=modules_overview&amp;app_id=' . $app_id . '&amp;sys_module_admin=' . $module['sys_module_admin'] );
	}
	
	/**
	 * Import a module
	 *
	 * @param	string		[Optional] XML content
	 * @param	integer		IN_DEV override
	 * @return	@e void		[Outputs to screen]
	 */
	public function moduleImport( $content = '', $in_dev = 0, $return = TRUE )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		

		$updated = 0;
		$inserted = 0;
		$modules = array();
		$apps = array();
		$app_id = 0;
		$app_dir = '';
		
		//-----------------------------------------
		// Got content?
		//-----------------------------------------
		

		if ( ! $content )
		{
			//-----------------------------------------
			// INDEV?
			//-----------------------------------------
			

			if ( $in_dev )
			{
				$_FILES['FILE_UPLOAD']['name'] = '';
				$this->request['file_location'] = IPSLib::getAppDir( $this->request['_app'] ) . '/xml/' . $this->request['_app'] . '_modules.xml';
			}
			else
			{
				$this->request['file_location'] = IPS_ROOT_PATH . $this->request['file_location'];
			}
			
			//-----------------------------------------
			// Uploaded file?
			//-----------------------------------------
			

			if ( ! isset( $_FILES['FILE_UPLOAD']['name'] ) or $_FILES['FILE_UPLOAD']['name'] == "" or ! $_FILES['FILE_UPLOAD']['name'] or ! $_FILES['FILE_UPLOAD']['size'] or ( $_FILES['FILE_UPLOAD']['name'] == "none" ) )
			{
				//-----------------------------------------
				// check and load from server
				//-----------------------------------------
				

				if ( ! $this->request['file_location'] )
				{
					if ( $return )
					{
						$this->registry->output->global_message = $this->lang->words['a_nofile'];
						$this->applicationsOverview();
						return;
					}
					else
					{
						return 'Nothing to import';
					}
				}
				
				if ( ! is_file( $this->request['file_location'] ) )
				{
					if ( $return )
					{
						$this->registry->output->global_message = $this->lang->words['a_file404'] . $this->request['file_location'];
						$this->applicationsOverview();
						return;
					}
					else
					{
						return 'Nothing to import';
					}
				}
				
				if ( preg_match( '#\.gz$#', $this->request['file_location'] ) )
				{
					if ( $FH = @gzopen( $this->request['file_location'], 'rb' ) )
					{
						while ( ! @gzeof( $FH ) )
						{
							$content .= @gzread( $FH, 1024 );
						}
						
						@gzclose( $FH );
					}
				}
				else
				{
					if ( $FH = @fopen( $this->request['file_location'], 'rb' ) )
					{
						$content = @fread( $FH, filesize( $this->request['file_location'] ) );
						@fclose( $FH );
					}
				}
			}
			else
			{
				//-----------------------------------------
				// Get uploaded schtuff
				//-----------------------------------------
				

				$tmp_name = $_FILES['FILE_UPLOAD']['name'];
				$tmp_name = preg_replace( '#\.gz$#', "", $tmp_name );
				
				$content = ipsRegistry::getClass( 'adminFunctions' )->importXml( $tmp_name );
			}
		}
		
		//-----------------------------------------
		// Get current applications
		//-----------------------------------------
		

		$this->DB->build( array( 'select' => 'app_id, app_directory', 'from' => 'core_applications', 'order' => 'app_id' ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$apps[$r['app_directory']] = $r['app_id'];
		}
		
		//-----------------------------------------
		// Get current modules
		//-----------------------------------------
		

		$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_module', 'order' => 'sys_module_id' ) );
		
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$modules[$r['sys_module_application']][intval( $r['sys_module_admin'] ) . '-' . $r['sys_module_key']] = $r['sys_module_id'];
		}
		
		//-----------------------------------------
		// Continue
		//-----------------------------------------
		

		require_once ( IPS_KERNEL_PATH . 'classXML.php' );/*noLibHook*/
		$xml = new classXML( IPS_DOC_CHAR_SET );
		
		$xml->loadXML( $content );
		
		//-----------------------------------------
		// pArse
		//-----------------------------------------
		

		$fields = array( 'sys_module_title', 'sys_module_application', 'sys_module_key', 'sys_module_description', 'sys_module_version', 'sys_module_protected', 'sys_module_visible', 'sys_module_position', 'sys_module_admin' );
		
		foreach( $xml->fetchElements( 'module' ) as $xmlelement )
		{
			$data = $xml->fetchElementsFromRecord( $xmlelement );
			
			foreach( $data as $k => $v )
			{
				if ( ! in_array( $k, $fields ) )
				{
					unset( $data[$k] );
				}
			}
			
			$app_dir = $data['sys_module_application'];
			$_key = intval( $data['sys_module_admin'] ) . '-' . $data['sys_module_key'];
			
			//-----------------------------------------
			// Insert, or update...
			//-----------------------------------------
			

			if ( $apps[$app_dir] )
			{
				//-----------------------------------------
				// Insert or update?
				//-----------------------------------------
				

				if ( $modules[$app_dir][$_key] )
				{
					//-----------------------------------------
					// Update
					//-----------------------------------------
					

					$updated ++;
					$this->DB->update( 'core_sys_module', $data, "sys_module_id=" . $modules[$data['sys_module_application']][$_key] );
				
				}
				else
				{
					//-----------------------------------------
					// Insert
					//-----------------------------------------
					

					$inserted ++;
					$this->DB->insert( 'core_sys_module', $data );
				}
			}
		}
		
		//-----------------------------------------
		// Done...
		//-----------------------------------------
		
		/* Recache menu */
		$this->applicationsMenuDataRecache();
		
		if ( $return )
		{
			$this->registry->output->global_message = sprintf( $this->lang->words['a_insertupdated'], $inserted, $updated );
			
			//-----------------------------------------
			// Recache
			//-----------------------------------------
			

			$this->moduleRecache();
			
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=modules_overview&amp;app_id=' . $apps[$app_dir] );
		}
		else
		{
			return sprintf( $this->lang->words['a_insertupdated'], $inserted, $updated );
		}
	}
	
	/**
	 * Export modules
	 *
	 * @param	integer		Return the XML [1] or print to browser [0]
	 * @return	mixed		XML content or outputs to screen
	 */
	public function moduleExport( $return_xml = 0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		

		$app_id = intval( $this->request['app_id'] );
		$app_dir = trim( IPSText::alphanumericalClean( $this->request['app_dir'] ) );
		$xml = '';
		
		//-----------------------------------------
		// Get application
		//-----------------------------------------
		

		if ( $app_id )
		{
			$application = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_applications', 'where' => 'app_id=' . $app_id ) );
		}
		else if ( $app_dir )
		{
			$application = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_applications', 'where' => "app_directory='" . $app_dir . "'" ) );
		}
		
		if ( ! $application['app_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['a_noid'];
			$this->modules_overview();
			return;
		}
		
		//-----------------------------------------
		// Get xml mah-do-dah
		//-----------------------------------------
		

		require_once ( IPS_KERNEL_PATH . 'classXML.php' );/*noLibHook*/
		$xml = new classXML( IPS_DOC_CHAR_SET );
		$xml->newXMLDocument();
		
		//-----------------------------------------
		// Start...
		//-----------------------------------------
		

		$xml->addElement( 'moduleexport' );
		
		//-----------------------------------------
		// Get applications
		//-----------------------------------------
		

		$xml->addElement( 'modulegroup', 'moduleexport' );
		
		$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_module', 'where' => "sys_module_application='" . $application['app_directory'] . "'", 'order' => 'sys_module_admin, sys_module_position' ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			unset( $r['sys_module_id'] );
			
			$xml->addElementAsRecord( 'modulegroup', 'module', $r );
		}
		
		if ( $return_xml )
		{
			return array( 'title' => $application['app_directory'] . '_modules.xml', 'xml' => $xml->fetchDocument() );
		}
		else
		{
			$this->registry->output->showDownload( $xml->fetchDocument(), $application['app_directory'] . '_modules.xml', '', 0 );
		}
	}
	
	/**
	 * Save a module
	 *
	 * @param 	string		Type [add|edit]
	 * @return	@e void		[Outputs to screen]
	 */
	public function moduleSave( $type = 'add' )
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------
		

		$app_id = intval( $this->request['app_id'] );
		$sys_module_admin = intval( $this->request['sys_module_admin'] );
		$sys_module_id = intval( $this->request['sys_module_id'] );
		$sys_module_title = trim( $this->request['sys_module_title'] );
		$sys_module_key = IPSText::alphanumericalClean( trim( $this->request['sys_module_key'] ) );
		$sys_module_description = trim( $this->request['sys_module_description'] );
		$sys_module_version = trim( $this->request['sys_module_version'] );
		$sys_module_protected = intval( $this->request['sys_module_protected'] );
		$sys_module_visible = intval( $this->request['sys_module_visible'] );
		$application = array();
		
		//-----------------------------------------
		// Got an application?
		//-----------------------------------------
		

		$application = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_applications', 'where' => 'app_id=' . $app_id ) );
		
		if ( ! $application['app_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['a_noid'];
			$this->applicationsOverview();
			return;
		}
		
		//--------------------------------------------
		// Check
		//--------------------------------------------
		

		if ( $type == 'edit' )
		{
			$module = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_sys_module', 'where' => 'sys_module_id=' . $sys_module_id ) );
			
			if ( ! $module['sys_module_id'] )
			{
				$this->registry->output->global_message = $this->lang->words['a_noid'];
				$this->modules_overview();
				return;
			}
		}
		else
		{
			//-----------------------------------------
			// Make sure that we don't have a key already
			//-----------------------------------------
			

			$test = $this->DB->buildAndFetch( array( 'select' => 'sys_module_id', 'from' => 'core_sys_module', 'where' => "sys_module_key='" . $sys_module_key . "' AND sys_module_application='" . $application['app_directory'] . "' AND sys_module_admin=" . $sys_module_admin ) );
			
			if ( $test['sys_module_id'] )
			{
				$this->registry->output->global_message = sprintf( $this->lang->words['a_already'], $sys_module_key );
				$this->modules_overview();
				return;
			}
		
		}
		
		//-----------------------------------------
		// Form checks...
		//-----------------------------------------
		

		if ( ! $sys_module_title or ! $sys_module_key )
		{
			$this->registry->output->global_message = $this->lang->words['a_titlekey'];
			$this->moduleForm( $type );
			return;
		}
		
		//--------------------------------------------
		// Check...
		//--------------------------------------------
		

		$array = array( 'sys_module_title' => $sys_module_title, 'sys_module_application' => $application['app_directory'], 'sys_module_key' => $sys_module_key, 'sys_module_description' => $sys_module_description, 'sys_module_version' => $sys_module_version, 'sys_module_visible' => $sys_module_visible, 'sys_module_admin' => $sys_module_admin );
		
		//-----------------------------------------
		// IN DEV?
		//-----------------------------------------
		

		if ( IN_DEV )
		{
			$array['sys_module_protected'] = $sys_module_protected;
		}
		
		//-----------------------------------------
		// Save...
		//-----------------------------------------
		

		if ( $type == 'add' )
		{
			$max = $this->DB->buildAndFetch( array( 'select' => 'MAX(sys_module_position) as position', 'from' => 'core_sys_module' ) );
			
			$array['sys_module_position'] = $max['position'] + 1;
			
			$this->DB->insert( 'core_sys_module', $array );
			$this->registry->output->global_message = $this->lang->words['a_added'];
		}
		else
		{
			$this->DB->update( 'core_sys_module', $array, 'sys_module_id=' . $sys_module_id );
			$this->registry->output->global_message = $this->lang->words['a_edited'];
		}
		
		//-----------------------------------------
		// Recache
		//-----------------------------------------
		

		$this->moduleRecache();
		
		//-----------------------------------------
		// List...
		//-----------------------------------------
		

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=modules_overview&amp;app_id=' . $app_id . '&amp;sys_module_admin=' . $sys_module_admin );
	}
	
	/**
	 * Add/Edit module form
	 *
	 * @param	string		Type [add|edit]
	 * @return	@e void		[Outputs to screen]
	 */
	public function moduleForm( $type = 'add' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		

		$sys_module_admin = intval( $this->request['sys_module_admin'] );
		$sys_module_id = intval( $this->request['sys_module_id'] );
		$app_id = intval( $this->request['app_id'] );
		$module = array( 'sys_module_admin' => $sys_module_admin );
		$modules = array( 'root' => array() );
		$application = array();
		$form = array();
		$module_type = array( 0 => array( 0, $this->lang->words['a_public'] ), 1 => array( 1, $this->lang->words['a_admin'] ) );
		
		//-----------------------------------------
		// Get application
		//-----------------------------------------
		

		$application = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_applications', 'where' => 'app_id=' . $app_id ) );
		
		if ( ! $application['app_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['a_noid'];
			$this->applicationsOverview();
			return;
		}
		
		//-----------------------------------------
		// Add or edit?
		//-----------------------------------------
		

		if ( $type == 'add' )
		{
			$formcode = 'module_add_do';
			$title = $this->lang->words['addnewmodule_apps'];
			$button = $this->lang->words['addnewmodule_apps'];
		}
		else
		{
			$module = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_sys_module', 'where' => 'sys_module_id=' . $sys_module_id ) );
			
			if ( ! $module['sys_module_id'] )
			{
				$this->registry->output->global_message = $this->lang->words['a_noid'];
				$this->applicationsOverview();
				return;
			}
			
			$sys_module_admin = $module['sys_module_admin'];
			$formcode = 'module_edit_do';
			$title = $this->lang->words['a_editmod'] . $module['sys_module_title'];
			$button = $this->lang->words['a_savechanges'];
		}
		
		//-----------------------------------------
		// Form elements
		//-----------------------------------------
		

		$form['sys_module_title'] = $this->registry->output->formInput( 'sys_module_title', $_POST['sys_module_title'] ? $_POST['sys_module_title'] : $module['sys_module_title'] );
		$form['sys_module_description'] = $this->registry->output->formInput( 'sys_module_description', $_POST['sys_module_description'] ? $_POST['sys_module_description'] : $module['sys_module_description'] );
		$form['sys_module_key'] = $this->registry->output->formInput( 'sys_module_key', $_POST['sys_module_key'] ? $_POST['sys_module_key'] : $module['sys_module_key'] );
		$form['sys_module_version'] = $this->registry->output->formInput( 'sys_module_version', $_POST['sys_module_version'] ? $_POST['sys_module_version'] : $module['sys_module_version'] );
		$form['sys_module_protected'] = $this->registry->output->formYesNo( 'sys_module_protected', $_POST['sys_module_protected'] ? $_POST['sys_module_protected'] : $module['sys_module_protected'] );
		$form['sys_module_visible'] = $this->registry->output->formYesNo( 'sys_module_visible', $_POST['sys_module_visible'] ? $_POST['sys_module_visible'] : $module['sys_module_visible'] );
		$form['sys_module_admin'] = $this->registry->output->formDropdown( 'sys_module_admin', $module_type, $_POST['sys_module_admin'] ? $_POST['sys_module_admin'] : $module['sys_module_admin'] );
		
		//-----------------------------------------
		// Nav
		//-----------------------------------------
		

		$this->registry->output->extra_nav[] = array( $this->settings['base_url'] . $this->form_code . '&amp;do=modules_overview&amp;app_id=' . $application['app_id'] . '&amp;sys_module_admin=' . $module['sys_module_admin'], $application['app_title'] );
		
		$this->registry->output->html .= $this->html->module_form( $form, $title, $formcode, $button, $module, $application );
	}
	
	/**
	 * Move a module up/down
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	public function moduleManagePosition()
	{
		$app_id = intval( $this->request['app_id'] );
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		

		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classAjax.php', 'classAjax' );
		$ajax = new $classToLoad();
		
		//-----------------------------------------
		// Checks...
		//-----------------------------------------
		

		if ( ! $app_id )
		{
			$ajax->returnString( $this->lang->words['postform_badmd5'] );
		}
		
		if ( $this->registry->adminFunctions->checkSecurityKey( $this->request['md5check'], true ) === false )
		{
			$ajax->returnString( $this->lang->words['postform_badmd5'] );
		}
		
		//-----------------------------------------
		// Save new position
		//-----------------------------------------
		

		$position = 1;
		
		if ( is_array( $this->request['modules'] ) and count( $this->request['modules'] ) )
		{
			foreach( $this->request['modules'] as $this_id )
			{
				$this->DB->update( 'core_sys_module', array( 'sys_module_position' => $position ), 'sys_module_id=' . $this_id );
				
				$position ++;
			}
		}
		
		$this->moduleRecache();
		
		$ajax->returnString( 'OK' );
		exit();
	}
	
	/**
	 * Recache modules
	 *
	 * @return	@e void
	 */
	public function moduleRecache()
	{
		$modules = array();
		
		//-----------------------------------------
		// Load known modules
		//-----------------------------------------
		$this->DB->build( array( 'select'	=> 'm.*',
								 'from'		=> array( 'core_sys_module' => 'm' ),
								 'where'	=> 'm.sys_module_visible=1',
								 'order'	=> 'a.app_position, m.sys_module_position ASC',
								 'add_join'	=> array( array( 'select' => 'a.*',
															 'from'   => array( 'core_applications' => 'a' ),
															 'where'  => 'm.sys_module_application=a.app_directory',
															 'type'   => 'inner' ) )
						 )		);
		$this->DB->execute();
		
		while ( $row = $this->DB->fetch() )
		{
			$_row = array();
			
			foreach( $row as $k => $v )
			{
				if ( strpos( $k, "sys_" ) === 0 )
				{
					$_row[$k] = $v;
				}
			}
			
			$modules[ $row['sys_module_application'] ][] = $_row;
		}
		
		$this->cache->setCache( 'module_cache', $modules, array( 'array' => 1 ) );
	}
	
	/**
	 * Recache apps, modules and menus
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	public function moduleRecacheAll( $return = 0 )
	{
		$this->applicationsRecache();
		$this->applicationsMenuDataRecache();
		$this->moduleRecache();
		
		if ( ! $return )
		{
			$this->registry->output->global_message = $this->lang->words['a_recachecomplete'];
			
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=applications_overview' );
		}
	}
	
	/**
	 * View al module
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	public function modules_overview()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		

		$sys_module_admin = intval( $this->request['sys_module_admin'] );
		$app_id = intval( $this->request['app_id'] );
		$application = array();
		$modules = array();
		$_modules = array();
		$_parents = array();
		$_modules_admin = ( $sys_module_admin ) ? 'modules_admin' : 'modules_public';
		
		//-----------------------------------------
		// Get application
		//-----------------------------------------
		

		$application = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_applications', 'where' => 'app_id=' . $app_id ) );
		
		if ( ! $application['app_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['a_noid'];
			$this->applicationsOverview();
			return;
		}
		
		//-----------------------------------------
		// Get modules
		//-----------------------------------------
		

		$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_module', 'where' => "sys_module_application='" . $application['app_directory'] . "' AND sys_module_admin=" . $sys_module_admin, 'order' => 'sys_module_position ASC' ) );
		$this->DB->execute();
		
		while ( $row = $this->DB->fetch() )
		{
			//-----------------------------------------
			// Enabled?
			//-----------------------------------------
			

			$row['_sys_module_visible'] = ( $row['sys_module_visible'] ) ? 'tick.png' : 'cross.png';
			
			//-----------------------------------------
			// Add to row
			//-----------------------------------------
			

			$_modules[$row['sys_module_id']] = $row;
			
			$_parents[$row['sys_module_id']] = $row['sys_module_id'];
		}
		
		//-----------------------------------------
		// Loop...
		//-----------------------------------------
		

		foreach( $_parents as $_sys_module_id => $sys_module_id )
		{
			$row = $_modules[$_sys_module_id];
			
			//-----------------------------------------
			// Add to row
			//-----------------------------------------
			

			$modules[] = $row;
		}
		
		//-----------------------------------------
		// List 'em
		//-----------------------------------------
		

		$this->registry->output->html .= $this->html->modules_list( $modules, $application, $sys_module_admin );
	}
	
	/**
	 * Remove an application (confirm screen)
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	public function applicationRemoveSplash()
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------
		

		$app_id = intval( $this->request['app_id'] );
		
		//-----------------------------------------
		// Got an application?
		//-----------------------------------------
		

		$application = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_applications', 'where' => 'app_id=' . $app_id ) );
		
		$this->registry->output->html .= $this->html->application_remove_splash( $application );
	}
	
	/**
	 * Remove an application
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	public function applicationRemove()
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------
		

		$app_id = intval( $this->request['app_id'] );
		
		//-----------------------------------------
		// Got an application?
		//-----------------------------------------
		

		$application = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_applications', 'where' => 'app_id=' . $app_id ) );
		
		if ( ! $application['app_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['a_noid'];
			$this->applicationsOverview();
			return;
		}
		
		//-----------------------------------------
		// Protected?
		//-----------------------------------------
		

		if ( ! IN_DEV and $application['app_protected'] )
		{
			$this->registry->output->global_message = $this->lang->words['a_protectapp'];
			$this->applicationsOverview();
			return;
		}
		
		//-----------------------------------------
		// Remove Settings
		//-----------------------------------------				

		$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_settings_titles', 'where' => "conf_title_app='{$application['app_directory']}'" ) );
		$this->DB->execute();
		
		$conf_title_id = array();

		while ( $r = $this->DB->fetch() )
		{
			$conf_title_id[] = $r['conf_title_id'];
		}
		
		if ( count( $conf_title_id ) )
		{
			$this->DB->delete( 'core_sys_conf_settings', 'conf_group IN(' . implode( ',', $conf_title_id ) . ')' );
		}
		
		$this->DB->delete( 'core_sys_settings_titles', "conf_title_app='{$application['app_directory']}'" );
		
		$settingsFile = IPSLib::getAppDir( $application['app_directory'] ) . '/xml/' . $application['app_directory'] . '_settings.xml';
		if( is_file( $settingsFile ) )
		{
			require_once( IPS_KERNEL_PATH.'classXML.php' );/*noLibHook*/
			$xml = new classXML( IPS_DOC_CHAR_SET );
			$xml->load( $settingsFile );

			$keys = array();
			foreach( $xml->fetchElements('setting') as $setting )
			{
				$entry = $xml->fetchElementsFromRecord( $setting );
				if ( $entry['conf_is_title'] )
				{
					continue;
				}
			
				$keys[] = "'{$entry['conf_key']}'";
			}
			
			if ( !empty( $keys ) )
			{
				$this->DB->delete( 'core_sys_conf_settings', 'conf_key IN(' . implode( ',', $keys ) . ')' );
			}
		}
				
		//-----------------------------------------
		// Remove Application Caches
		//-----------------------------------------		
		

		$_file = IPSLib::getAppDir( $application['app_directory'] ) . '/extensions/coreVariables.php';
		
		if ( is_file( $_file ) )
		{
			$CACHE = array();
			require ( $_file );/*noLibHook*/
			
			if ( is_array( $CACHE ) and count( $CACHE ) )
			{
				foreach( $CACHE as $key => $data )
				{
					$this->DB->delete( 'cache_store', "cs_key='{$key}'" );
				}
			}
		}
		
		//-----------------------------------------
		// Remove tables
		//-----------------------------------------

		$_file = IPSLib::getAppDir( $application['app_directory'] ) . '/setup/versions/install/sql/' . $application['app_directory'] . '_' . ipsRegistry::dbFunctions()->getDriverType() . '_tables.php';
		
		if ( is_file( $_file ) )
		{
			$TABLE = array();
			require ( $_file );/*noLibHook*/
			
			foreach( $TABLE as $q )
			{
				//-----------------------------------------
				// Capture create tables first
				//-----------------------------------------

				preg_match( "/CREATE TABLE (\S+)(\s)?\(/", $q, $match );
				
				if ( $match[1] )
				{
					$this->DB->dropTable( preg_replace( '#^' . ipsRegistry::dbFunctions()->getPrefix() . "(\S+)#", "\\1", $match[1] ) );
				}
				else
				{
					//-----------------------------------------
					// Then capture alter tables
					//-----------------------------------------

					preg_match( "/ALTER TABLE (\S+)\sADD\s(\S+)\s/i", $q, $match );
					
					if ( $match[1] and $match[2] )
					{
						$this->DB->dropField( preg_replace( '#^' . ipsRegistry::dbFunctions()->getPrefix() . "(\S+)#", "\\1", $match[1] ), $match[2] );
					}
				}
			}
		}
		
		//-----------------------------------------
		// Check for uninstall sql
		//-----------------------------------------

		/* Any "extra" configs required for this driver? */
		if ( is_file( IPS_ROOT_PATH . 'setup/sql/' . $this->settings['sql_driver'] . '_install.php' ) )
		{
			require_once ( IPS_ROOT_PATH . 'setup/sql/' . $this->settings['sql_driver'] . '_install.php' );/*noLibHook*/
			
			$extra_install = new install_extra( $this->registry );
		}
		
		$_file = IPSLib::getAppDir( $application['app_directory'] ) . '/setup/versions/install/sql/' . $application['app_directory'] . '_' . ipsRegistry::dbFunctions()->getDriverType() . '_uninstall.php';
		
		if ( is_file( $_file ) )
		{
			$QUERY = array();
			require ( $_file );/*noLibHook*/
			
			if ( is_array( $QUERY ) and count( $QUERY ) )
			{
				foreach( $QUERY as $q )
				{
					if ( $extra_install and method_exists( $extra_install, 'process_query_create' ) )
					{
						$q = $extra_install->process_query_create( $q );
					}
					
					$this->DB->query( $q );
				}
			}
		}
		
		//-----------------------------------------
		// Remove Misc Stuff
		//-----------------------------------------		
		

		$this->DB->delete( 'core_sys_lang_words', "word_app='{$application['app_directory']}'" );
		$this->DB->delete( 'task_manager', "task_application='{$application['app_directory']}'" );
		$this->DB->delete( 'permission_index', "app='{$application['app_directory']}'" );
		$this->DB->delete( 'reputation_index', "app='{$application['app_directory']}'" );
		$this->DB->delete( 'reputation_cache', "app='{$application['app_directory']}'" );
		$this->DB->delete( 'core_tags', "tag_meta_app='{$application['app_directory']}'" );
		$this->DB->delete( 'faq', "app='{$application['app_directory']}'" );
		$this->DB->delete( 'custom_bbcode', "bbcode_app='{$application['app_directory']}'" );
		$this->DB->delete( 'upgrade_history', "upgrade_app='{$application['app_directory']}'" );
		$this->DB->delete( 'core_like_cache', "like_cache_app='{$application['app_directory']}'" );
		$this->DB->delete( 'core_like', "like_app='{$application['app_directory']}'" );
		$this->DB->delete( 'core_item_markers', "item_app='{$application['app_directory']}'" );
		
		//-----------------------------------------
		// Report center..
		//-----------------------------------------

		$plugin = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'rc_classes', 'where' => "app='{$application['app_directory']}'" ) );
		
		if ( $plugin['com_id'] )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'rc_reports_index', 'where' => 'rc_class=' . $plugin['com_id'] ) );
			$outer = $this->DB->execute();
			
			while ( $r = $this->DB->fetch( $outer ) )
			{
				$this->DB->delete( 'rc_reports', "rid=" . $r['id'] );
				$this->DB->delete( 'rc_comments', "rid=" . $r['id'] );
			}
			
			$this->DB->delete( 'rc_reports_index', 'rc_class=' . $plugin['com_id'] );
		}
		
		//-----------------------------------------
		// Attachments
		//-----------------------------------------

		$_plugins = array();
		
		try
		{
			foreach( new DirectoryIterator( IPSLib::getAppDir( $application['app_directory'] ) . '/extensions/attachments/' ) as $file )
			{
				if ( ! $file->isDot() && $file->isFile() )
				{
					if ( preg_match( "/^plugin_(.+?)\.php$/", $file->getFileName(), $matches ) )
					{
						$_plugins[] = $matches[1];
					}
				}
			}
			
			if ( count( $_plugins ) )
			{
				foreach( $_plugins as $_plugin )
				{
					$this->DB->build( array( 'select' => '*', 'from' => 'attachments', 'where' => "attach_rel_module='{$_plugin}'" ) );
					$outer = $this->DB->execute();
					
					while ( $r = $this->DB->fetch( $outer ) )
					{
						if ( is_file( $this->settings['upload_dir'] . "/" . $r['attach_location'] ) )
						{
							@unlink( $this->settings['upload_dir'] . "/" . $r['attach_location'] );
						}
					}
					
					$this->DB->delete( 'attachments', "attach_rel_module='{$_plugin}'" );
				}
			}
		}
		catch( Exception $e )
		{
		}
		
		//-----------------------------------------
		// Get all hook files
		//-----------------------------------------

		if ( is_dir( IPSLib::getAppDir( $application['app_directory'] ) . '/xml/hooks' ) )
		{
			$files = scandir( IPSLib::getAppDir( $application['app_directory'] ) . '/xml/hooks' );
			$hooks = array();
			
			require_once ( IPS_KERNEL_PATH . 'classXML.php' );/*noLibHook*/
			$xml = new classXML( IPS_DOC_CHAR_SET );
			
			if ( count( $files ) and is_array( $files ) )
			{
				foreach( $files as $_hookFile )
				{
					if ( $_hookFile != '.' and $_hookFile != '..' and preg_match( "/(\.xml)$/", $_hookFile ) )
					{
						$xml->loadXML( file_get_contents( IPSLib::getAppDir( $application['app_directory'] ) . '/xml/hooks/' . $_hookFile ) );
						
						foreach( $xml->fetchElements( 'config' ) as $data )
						{
							$config = $xml->fetchElementsFromRecord( $data );
							
							if ( ! count( $config ) )
							{
								continue;
							}
							else
							{
								$hooks[] = $config['hook_key'];
							}
						}
					}
				}
			}
			
			if ( count( $hooks ) )
			{
				foreach( $hooks as $hook )
				{
					$hook = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_hooks', 'where' => "hook_key='" . $hook . "'" ) );
					
					if ( ! $hook['hook_id'] )
					{
						continue;
					}
					
					$this->DB->delete( 'core_hooks', "hook_id={$hook['hook_id']}" );
					
					/* Get associated files */
					$this->DB->build( array( 'select' => 'hook_file_stored', 'from' => 'core_hooks_files', 'where' => 'hook_hook_id=' . $hook['hook_id'] ) );
					$this->DB->execute();
					
					while ( $r = $this->DB->fetch() )
					{
						@unlink( IPS_HOOKS_PATH . $r['hook_file_stored'] );
					}
					
					/* Delete hook file entries */
					$this->DB->delete( 'core_hooks_files', "hook_hook_id={$hook['hook_id']}" );
				}
				
				$this->cache->rebuildCache( 'hooks', 'global' );
			}
		}
		
		//-----------------------------------------
		// Remove Files
		//-----------------------------------------

		/* Languages */
		try
		{
			foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . 'cache/lang_cache/' ) as $dir )
			{
				if ( ! $dir->isDot() && intval( $dir->getFileName() ) )
				{
					foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . 'cache/lang_cache/' . $dir->getFileName() . '/' ) as $file )
					{
						if ( ! $file->isDot() )
						{
							if ( preg_match( "/^({$application['app_directory']}_)/", $file->getFileName() ) )
							{
								unlink( $file->getPathName() );
							}
						}
					}
				}
			}
		}
		catch( Exception $e )
		{
		}
		
		/* Remove Skins */
		if( is_file( IPSLib::getAppDir( $application['app_directory'] ) . '/xml/information.xml' ) )
		{
			require_once ( IPS_KERNEL_PATH . 'classXML.php' );/*noLibHook*/
			$xml = new classXML( $this->settings['gb_char_set'] );
			$xml->load( IPSLib::getAppDir( $application['app_directory'] ) . '/xml/information.xml' );
			
			if ( is_object( $xml->fetchElements( 'template' ) ) )
			{
				foreach( $xml->fetchElements( 'template' ) as $template )
				{
					$name = $xml->fetchItem( $template );
					$match = $xml->fetchAttribute( $template, 'match' );
					
					if ( $name )
					{
						$templateGroups[$name] = $match;
					}
				}
			}
			
			if ( is_array( $templateGroups ) and count( $templateGroups ) )
			{
				/* Loop through skin directories */
				try
				{
					foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . 'cache/skin_cache/' ) as $dir )
					{
						if ( preg_match( "/^(cacheid_)/", $dir->getFileName() ) )
						{
							foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . 'cache/skin_cache/' . $dir->getFileName() . '/' ) as $file )
							{
								if ( ! $file->isDot() )
								{
									foreach( $templateGroups as $name => $match )
									{
										if ( $match == 'contains' )
										{
											if ( stristr( $file->getFileName(), $name ) )
											{
												unlink( $file->getPathName() );
											}
										}
										else if ( $file->getFileName() == $name . '.php' )
										{
											unlink( $file->getPathName() );
										}
									}
								}
							}
						}
					}
				}
				catch( Exception $e )
				{
				}
				
				/* Delete from database */
				foreach( $templateGroups as $name => $match )
				{
					if ( $match == 'contains' )
					{
						$this->DB->delete( 'skin_templates', "template_group LIKE '%{$name}%'" );
					}
					else
					{
						$this->DB->delete( 'skin_templates', "template_group='{$name}'" );
					}
				}
			}
		}
		
		/* CSS files */
		$css_files = array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'skin_css', 'where' => "css_app='" . $application['app_directory'] . "'" ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$css_files[$r['css_group']] = $r['css_group'];
		}
		
		if ( count( $css_files ) )
		{
			$this->DB->delete( 'skin_css', "css_app='" . $application['app_directory'] . "'" );
			$this->DB->delete( 'skin_cache', "cache_type='css' AND cache_value_1 IN('" . implode( "','", $css_files ) . "')" );
			
			try
			{
				foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/style_css/' ) as $dir )
				{
					if ( preg_match( "/^(css_)/", $dir->getFileName() ) )
					{
						foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/style_css/' . $dir->getFileName() . '/' ) as $file )
						{
							if ( ! $file->isDot() )
							{
								foreach( $css_files as $css_file )
								{
									if ( $file->getFileName() == $css_file . '.css' )
									{
										unlink( $file->getPathName() );
									}
								}
							}
						}
					}
				}
			}
			catch( Exception $e )
			{
			}
		}
		
		//-----------------------------------------
		// Remove Modules
		//-----------------------------------------		

		$this->DB->delete( 'core_sys_module', "sys_module_application='{$application['app_directory']}'" );
		
		//-----------------------------------------
		// Remove Application
		//-----------------------------------------

		$this->DB->delete( 'core_applications', 'app_id=' . $app_id );
		
		//-----------------------------------------
		// Recache
		//-----------------------------------------

		$this->moduleRecacheAll( 1 );
		
		$this->cache->rebuildCache( 'settings', 'global' );
		$this->cache->rebuildCache( 'notifications', 'global' );
		
		/* Delete from upgrade */
		$this->DB->delete( 'upgrade_history', "upgrade_app='{$application['app_directory']}'" );
		
		//-----------------------------------------
		// FURL templates
		//-----------------------------------------

		try
		{
			IPSLib::cacheFurlTemplates();
			IPSLib::cacheGlobalCaches();
		}
		catch( Exception $e )
		{
		}
		
		//-----------------------------------------
		// Sphinx involved?
		//-----------------------------------------

		if ( $this->settings['search_method'] == 'sphinx' )
		{
			$this->registry->output->global_message .= sprintf( $this->lang->words['rebuild_sphinx'], $this->settings['_base_url'] );
		}
		
		//-----------------------------------------
		// Done...
		//-----------------------------------------
		

		$this->registry->output->global_message = $this->lang->words['a_appremoved'];
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=applications_overview' );
	}
	
	/**
	 * Export information.xml files
	 * for every application
	 *
	 * @return	@e void	[Returns to the applications list]
	 */
	protected function inDevApplicationsExport()
	{
		/* Not IN_DEV? */
		if ( ! IN_DEV )
		{
			$this->applicationsOverview();
			return;
		}
		
		/* Get our libs! */
		require_once( IPS_KERNEL_PATH . 'classXML.php' );/*noLibHook*/
		require_once( IPS_ROOT_PATH . 'setup/sources/base/setup.php' );/*noLibHook*/
		
		/* Do each app */
		$this->DB->build( array( 'select' => '*', 'from' => 'core_applications' ) );
		$outer = $this->DB->execute();
		
		/* Loop */
		while ( $app = $this->DB->fetch( $outer ) )
		{
			/* Set some vars */
			$oldXml   = array();
			$newXml   = null;
			$infoFile = IPSLib::getAppDir( $app['app_directory'] ) . '/xml/information.xml';
			
			/* Got some 'old' template data to retain? */
			if ( is_file( $infoFile ) )
			{
				if ( ! is_writeable( $infoFile ) )
				{
					$output[] = "Cannot write to {$infoFile}";
					continue;
				}
				
				$oldXml = IPSSetUp::fetchXmlAppInformation( $app['app_directory'], $this->settings['gb_char_set'] );
			}
			
			$data = array( 'name'				=> trim( $app['app_title'] ),
						   'public_name'		=> trim( $app['app_public_title'] ),
						   'hide_tab'			=> intval( $app['app_hide_tab'] ),
						   'author'				=> trim( $app['app_author'] ),
						   'description'		=> trim( $app['app_description'] ),
						   'disabledatinstall'	=> ( isset( $oldXml['disabledatinstall'] ) && $oldXml['disabledatinstall'] ) ? intval( $oldXml['disabledatinstall'] ) : 0,
						   'ipskey'				=> '',
						   'global_caches'		=> trim( $app['app_global_caches'] ),
						   'website'			=> trim( $app['app_website'] ),
						   'update_check'		=> trim( $app['app_update_check'] )
						  );
			
			/* Got an IPS key to add? */
			if ( $app['app_location'] == 'ips' )
			{
				$data['ipskey'] = md5( 'ips_' . $app['app_directory'] );
			}
			else
			{
				unset( $data['ipskey'] );
			}
			
			/* Create xml object */
			$newXml = new classXML( IPS_DOC_CHAR_SET );
			$newXml->newXMLDocument();
			
			/* Create elements */
			$newXml->addElement( 'information' );
			$newXml->addElementAsRecord( 'information', 'data', $data );
			
			/* Get data till now */
			$xmlData = $newXml->fetchDocument();
			
			/* Got templates? */
			if ( isset( $oldXml['templates'] ) && count( $oldXml['templates'] ) )
			{
				$XML_TEMPLATES = <<<XML
    <templategroups>\n
XML;
				
				foreach( $oldXml['templates'] as $k => $v )
				{
					$XML_TEMPLATES .= <<<XML
        <template match="{$v}">{$k}</template>\n
XML;
				}
				
				$XML_TEMPLATES .= <<<XML
    </templategroups>
XML;
			}
			else
			{
				$XML_TEMPLATES = <<<XML
    <templategroups/>
XML;
			}
			
			/* Add templates into our XML - yeah I know.. a bit hackish.. */
			if ( $data['update_check'] )
			{
				$xmlData = str_replace( '</update_check>', "</update_check>\n{$XML_TEMPLATES}", $xmlData );
			}
			else
			{
				$xmlData = str_replace( '<update_check/>', "<update_check/>\n{$XML_TEMPLATES}", $xmlData );
			}
			
			file_put_contents( $infoFile, $xmlData );
			
			$output[] = $app['app_title'] . " information exported in '" . str_replace( DOC_IPS_ROOT_PATH, '', $infoFile ) . "'";
		}
		
		/* Done, setup message and redirect */
		$this->registry->output->setMessage( implode( "<br />", $output ), 1 );
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=applications_overview' );
	}
	
	/**
	 * Save an application
	 *
	 * @param	string		Type [add|edit]
	 * @return	@e void		[Outputs to screen]
	 */
	public function applicationSave( $type = 'add' )
	{
		/* Init vars */
		$app_title     = trim( $this->request['app_title'] );
		$app_public    = trim( $this->request['app_public_title'] );
		$app_directory = trim( $this->request['app_directory'] );
		$app_enabled   = intval( $this->request['app_enabled'] );
		$application   = array();
		$sphinxRebuild = false;
		
		/* Editing? */
		if ( $type == 'edit' )
		{
			$application = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_applications', 'where' => 'app_id=' . intval( $this->request['app_id'] ) ) );
			
			if ( ! $application['app_id'] )
			{
				$this->registry->output->global_message = $this->lang->words['a_noid'];
				$this->applicationsOverview();
				return;
			}
		}
		else
		{
			$_check = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_applications', 'where' => "app_directory='{$app_directory}'" ) );
			
			if ( $_check['app_id'] )
			{
				$this->registry->output->showError( $this->lang->words['app_already_added'], 111161.3 );
			}
		}
		
		/* Error check */
		if ( ! $app_title or ! $app_directory )
		{
			$this->registry->output->global_message = $this->lang->words['a_titledirectory'];
			$this->applicationForm( $type );
			return;
		}
		
		if ( ( empty($app_enabled) || empty($app_public) ) && in_array( $app_directory, array( 'core', 'forums', 'members' ) ) )
		{
			$this->registry->output->showError( $this->lang->words['cannot_toggle_defaults'], 111161.2 );
		}
		
		/* $etup update array */
		$array = array( 'app_title'			=> $app_title,
						'app_public_title'	=> $app_public,
						'app_enabled'		=> $app_enabled,
						'app_hide_tab'		=> intval( $this->request['app_hide_tab'] ),
						'app_description'	=> trim( $this->request['app_description'] ),
						'app_author'		=> trim( $this->request['app_author'] ),
						'app_version'		=> trim( $this->request['app_version'] ),
						'app_directory'		=> $app_directory,
						'app_website'		=> trim( $this->request['app_website'] ),
						'app_update_check'	=> trim( $this->request['app_update_check'] ),
						'app_global_caches'	=> ( is_array( $this->request['app_global_caches'] ) && count( $this->request['app_global_caches'] ) ) ? implode( ',', $this->request['app_global_caches'] ) : '',
						'app_tab_groups'	=> ( is_array( $this->request['app_tab_groups'] ) && count( $this->request['app_tab_groups'] ) ) ? implode( ',', $this->request['app_tab_groups'] ) : '' );
		
		//-----------------------------------------
		// IN DEV?
		//-----------------------------------------
		

		if ( IN_DEV )
		{
			$array['app_protected'] = intval( $this->request['app_protected'] );
		}
		
		//-----------------------------------------
		// Save...
		//-----------------------------------------
		

		if ( $type == 'add' )
		{
			$array['app_added'] = IPS_UNIX_TIME_NOW;
			$array['app_location'] = 'other';
			
			$max = $this->DB->buildAndFetch( array( 'select' => 'MAX(app_position) as position', 'from' => 'core_applications' ) );
			
			$array['app_position'] = intval($max['position']) + 1;
			
			$this->DB->insert( 'core_applications', $array );
			$this->registry->output->global_message = $this->lang->words['a_newapp'];
		}
		else
		{
			/* We're disabling the app? */
			if ( empty($app_enabled) )
			{
				$array['app_position'] = 0;
			}
			else
			{
				if ( empty($application['app_enabled']) )
				{
					$appsCount = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'core_applications', 'where' => 'app_enabled=1' ) );
				
					$array['app_position'] = $appsCount['total'] + 1;
				}
			}
			
			/* Update the application record */
			$this->DB->update( 'core_applications', $array, 'app_id=' . $application['app_id'] );
			
			/* Update modules and tasks, if the application directory changed */
			if ( $application['app_directory'] != $app_directory )
			{
				$sphinxRebuild = true;
				
				$this->DB->update( 'task_manager', array( 'task_application' => $app_directory ), "task_application='{$application['app_directory']}'" );
				$this->DB->update( 'core_sys_module', array( 'sys_module_application' => $app_directory ), "sys_module_application='{$application['app_directory']}'" );
				$this->DB->update( 'admin_logs', array( 'appcomponent' => $app_directory ), "appcomponent='{$application['app_directory']}'" );
				$this->DB->update( 'core_editor_autosave', array( 'eas_app' => $app_directory ), "eas_app='{$application['app_directory']}'" );
				$this->DB->update( 'core_incoming_emails', array( 'rule_app' => $app_directory ), "rule_app='{$application['app_directory']}'" );
				$this->DB->update( 'core_item_markers', array( 'item_app' => $app_directory ), "item_app='{$application['app_directory']}'" );
				$this->DB->update( 'core_like', array( 'like_app' => $app_directory ), "like_app='{$application['app_directory']}'" );
				$this->DB->update( 'core_like_cache', array( 'like_cache_app' => $app_directory ), "like_cache_app='{$application['app_directory']}'" );
				$this->DB->update( 'core_share_links_log', array( 'log_data_app' => $app_directory ), "log_data_app='{$application['app_directory']}'" );
				$this->DB->update( 'core_sys_lang_words', array( 'word_app' => $app_directory ), "word_app='{$application['app_directory']}'" );
				$this->DB->update( 'core_sys_settings_titles', array( 'conf_title_app' => $app_directory ), "conf_title_app='{$application['app_directory']}'" );
				$this->DB->update( 'core_tags', array( 'tag_meta_app' => $app_directory ), "tag_meta_app='{$application['app_directory']}'" );
				$this->DB->update( 'custom_bbcode', array( 'bbcode_app' => $app_directory ), "bbcode_app='{$application['app_directory']}'" );
				$this->DB->update( 'faq', array( 'app' => $app_directory ), "app='{$application['app_directory']}'" );
				$this->DB->update( 'inline_notifications', array( 'notify_meta_app' => $app_directory ), "notify_meta_app='{$application['app_directory']}'" );
				$this->DB->update( 'permission_index', array( 'app' => $app_directory ), "app='{$application['app_directory']}'" );
				$this->DB->update( 'reputation_cache', array( 'app' => $app_directory ), "app='{$application['app_directory']}'" );
				$this->DB->update( 'reputation_index', array( 'app' => $app_directory ), "app='{$application['app_directory']}'" );
				$this->DB->update( 'skin_css', array( 'css_app' => $app_directory ), "css_app='{$application['app_directory']}'" );
				$this->DB->update( 'skin_css_previous', array( 'p_css_app' => $app_directory ), "p_css_app='{$application['app_directory']}'" );
				$this->DB->update( 'upgrade_history', array( 'upgrade_app' => $app_directory ), "upgrade_app='{$application['app_directory']}'" );
			}
			
			/* Set the message */
			$this->registry->output->global_message = $this->lang->words['a_editappdone'];
		}
		
		/* Have we toggled this? */
		if ( $app_enabled != $application['app_enabled'] )
		{
			$sphinxRebuild = true;
		}
		
		//-----------------------------------------
		// Recache
		//-----------------------------------------

		$this->applicationsRecache();
		$this->applicationsMenuDataRecache();
		
		try
		{
			IPSLib::cacheFurlTemplates();
			IPSLib::cacheGlobalCaches();
		}
		catch( Exception $e )
		{
		}
		
		/**
		 * Re-enable the tasks if app is active
		 * (more effective than checking per-case)
		 */
		if ( $app_enabled )
		{
			$this->DB->update( 'task_manager', array( 'task_enabled' => 1 ), "task_application='{$app_directory}'" );
		}
		
		/* Check for possible hook warnings */
		if ( $type == 'edit' && $application['app_enabled'] && ! $app_enabled )
		{
			// Switch the enabled value here or check will fail with the current cache!
			ipsRegistry::$applications[$application['app_directory']]['app_enabled'] = 0;
			
			$this->_checkHooksWarnings( $application['app_directory'] );
		}
		
		//-----------------------------------------
		// Sphinx involved?
		//-----------------------------------------

		if ( $sphinxRebuild AND $this->settings['search_method'] == 'sphinx' && is_file( IPSLib::getAppDir( $app_directory ) . '/extensions/sphinxTemplate.php' ) )
		{
			$this->registry->output->global_message .= sprintf( $this->lang->words['rebuild_sphinx'], $this->settings['_base_url'] );
		}
		
		/* All done */
		$this->applicationsOverview();
	}
	
	/**
	 * Add/edit application form
	 *
	 * @param	string		Type [add|edit]
	 * @return	@e void		[Outputs to screen]
	 */
	public function applicationForm( $type = 'add' )
	{
		/* Init vars */
		$application = array();
		$form        = array();
		$defaultTab  = '';
		
		//-----------------------------------------
		// Add or edit?
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			$formcode = 'application_add_do';
			$title = $this->lang->words['a_addnewapp'];
			$button = $this->lang->words['a_addnewapp'];
		}
		else
		{
			$application = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_applications', 'where' => 'app_id=' . intval( $this->request['app_id'] ) ) );
			
			if ( ! $application['app_id'] )
			{
				$this->registry->output->global_message = $this->lang->words['a_noid'];
				$this->applicationsOverview();
				return;
			}
			
			$formcode = 'application_edit_do';
			$title = $this->lang->words['a_editapp'] . ': ' . $application['app_title'];
			$button = $this->lang->words['a_savechanges'];
		}
		
		//-----------------------------------------
		// Form elements
		//-----------------------------------------
		

		/* Input */
		foreach( array( 'app_title', 'app_public_title', 'app_description', 'app_author', 'app_version', 'app_directory', 'app_website', 'app_update_check' ) as $_app_key )
		{
			$form[$_app_key] = $this->registry->output->formSimpleInput( $_app_key, isset( $_POST[$_app_key] ) ? $_POST[$_app_key] : $application[$_app_key], 60 );
		}
		
		/* Y/N */
		foreach( array( 'app_protected', 'app_enabled', 'app_hide_tab' ) as $_app_key )
		{
			$form[$_app_key] = $this->registry->output->formYesNo( $_app_key, isset( $_POST[$_app_key] ) ? $_POST[$_app_key] : $application[$_app_key] );
		}
		
		/* Sort the tab permissions MDD */
		$_groups = array();
		
		foreach( $this->cache->getCache( 'group_cache' ) as $gid => $gdata )
		{
			$_groups[] = array( $gid, $gdata['g_title'] );
		}
		
		sort( $_groups );
		
		$form['app_tab_groups'] = $this->registry->output->formMultiDropdown( 'app_tab_groups[]', $_groups, isset( $_POST['app_tab_groups'] ) ? $_POST['app_tab_groups'] : explode( ',', $application['app_tab_groups'] ), 7 );
		
		/* Setup the global caches MDD */
		$_caches = array();
		
		$this->DB->build( array( 'select' => 'cs_key', 'from' => 'cache_store', 'order' => 'cs_key ASC' ) );
		$this->DB->execute();
		
		while ( $gc = $this->DB->fetch() )
		{
			$_caches[] = array( $gc['cs_key'], $gc['cs_key'] );
		}
		
		sort( $_caches );
		
		$form['app_global_caches'] = $this->registry->output->formMultiDropdown( 'app_global_caches[]', $_caches, isset( $_POST['app_global_caches'] ) ? $_POST['app_global_caches'] : explode( ',', $application['app_global_caches'] ), 15 );
		
		/* Got a default tab specified? */
		if ( !empty($this->request['_tab']) )
		{
			$defaultTab = trim($this->request['_tab']);
		}
		
		/* Output */
		$this->registry->output->html .= $this->html->application_form( $form, $title, $formcode, $button, $application, $defaultTab );
	}
	
	/**
	 * Move an application up/down
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	public function applicationManagePosition()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		

		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classAjax.php', 'classAjax' );
		$ajax = new $classToLoad();
		
		//-----------------------------------------
		// Checks...
		//-----------------------------------------
		

		if ( $this->registry->adminFunctions->checkSecurityKey( $this->request['md5check'], true ) === false )
		{
			$ajax->returnString( $this->lang->words['postform_badmd5'] );
		}
		
		//-----------------------------------------
		// Save new position
		//-----------------------------------------
		
 		$position	= 1;
 		
 		if ( is_array($this->request['apps']) && count($this->request['apps']) )
 		{
 			/* Reset all apps to 0 temporarily */
 			$this->DB->update( 'core_applications', array( 'app_position' => 0 ) );
 			
 			foreach( $this->request['apps'] as $this_id )
 			{
 				$this->DB->update( 'core_applications', array( 'app_position' => $position ), 'app_id=' . intval($this_id) );
	 			
	 			$position++;
 			}
 		}
		
		$this->applicationsRecache();
		$this->applicationsMenuDataRecache();
		
		$ajax->returnString('OK');
	}
	
	/**
	 * List applications
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	public function applicationsOverview()
	{
		/* Init vars */
		$folders		= array();
		$applications	= array( 'enabled' => array( 'core' => array() ), 'disabled' => array() );
		$_apps			= array();
		$uninstalled	= array();
		$checkUpdates	= false;
		$appsUpdates	= 0;
		$message		= '';
		
		/* Get the setup class */
		require_once ( IPS_ROOT_PATH . "setup/sources/base/setup.php" );/*noLibHook*/
		
		/* Checking for updates? */
		if ( !empty( $this->request['checkUpdates'] ) )
		{
			$checkUpdates = true;
			
			/* Get hooks file for update check */
			$classToLoad = IPSLib::loadActionOverloader( IPSLib::getAppDir( 'core' ) . '/modules_admin/applications/hooks.php', 'admin_core_applications_hooks' );
			$hooksClass = new $classToLoad();
			$hooksClass->makeRegistryShortcuts( $this->registry );
		}
		
		//-----------------------------------------
		// Get DB applications
		//-----------------------------------------

		$this->DB->build( array( 'select' => '*', 'from' => 'core_applications', 'order' => 'app_position' ) );
		$this->DB->execute();
		
		while ( $row = $this->DB->fetch() )
		{
			/* Got updates? */
			if ( $row['app_update_check'] && $checkUpdates === true )
			{
				$row['app_update_available'] = $hooksClass->_updateAvailable( $row['app_update_check'], $row['app_long_version'] );
				
				if ( $row['app_update_available'][0] )
				{
					$appsUpdates++;
				}
			}
			else
			{
				$row['app_update_available'] = array( 0 );
			}
			
			$_apps[ IPSLib::getAppFolder( $row['app_directory'] ) . '/' . $row['app_directory'] ] = $row;
		}
		
		//-----------------------------------------
		// Get folder applications...
		//-----------------------------------------

		foreach( array( 'applications', 'applications_addon/ips', 'applications_addon/other' ) as $folder )
		{
			try
			{
				foreach( new DirectoryIterator( IPS_ROOT_PATH . $folder ) as $file )
				{
					if ( ! $file->isDot() and $file->isDir() )
					{
						$_name = $file->getFileName();
						
						if ( substr( $_name, 0, 1 ) != '.' )
						{
							$folders[$folder . '/' . $_name] = $_name;
						}
					}
				}
			}
			catch( Exception $e )
			{
			}
		}
		
		//-----------------------------------------
		// Installed Loop...
		//-----------------------------------------
		
		foreach( $_apps as $_app_path => $row )
		{
			$app_dir = $row['app_directory'];
			
			/* Version numbers */
			$_a = ( $app_dir == 'forums' or $app_dir == 'members' ) ? 'core' : $app_dir;
			$numbers = IPSSetUp::fetchAppVersionNumbers( $_a );
			
			$row['_human_version'] = $numbers['latest'][1];
			$row['_long_version'] = $numbers['latest'][0];
			
			$row['_human_current'] = $numbers['current'][1];
			$row['_long_current'] = $numbers['current'][0];
			
			/* Nexus? */
			if ( $row['app_directory'] == 'nexus' )
			{
				$encoding = 'Unencoded';
				
				$file = file_get_contents( IPSLib::getAppDir('nexus') . '/app_class_nexus.php' );

				if ( substr( $file, 6, 5 ) == '@Zend' )
				{
					$phpVersion = phpversion();
					$phpVersion = str_replace( substr( $phpVersion, strrpos( $phpVersion, '.' ) ), '', $phpVersion );
					$encoding = "Zend {$phpVersion}";
				}
				elseif ( substr( $file, 36, 7 ) == 'ionCube' or substr( $file, 37, 7 ) == 'ionCube' )
				{
					$encoding = 'Ioncube';
				}
				
				$row['_human_current'] .= " ({$encoding})";
			}
			
			if ( $row['app_enabled'] )
			{
				$applications['enabled'][ $row['app_directory'] ] = $row;
			}
			else
			{
				$applications['disabled'][ $row['app_directory'] ] = $row;
			}
		}
		
		//-----------------------------------------
		// Uninstalled
		//-----------------------------------------

		foreach( $folders as $filepath => $_file )
		{
			if ( ! in_array( $filepath, array_keys( $_apps ) ) )
			{
				$info = IPSSetUp::fetchXmlAppInformation( $_file, $this->settings['gb_char_set'] );
				
				/* OK, we're making no effort to conceal the secret behind the ipskey. It's an honourable setting - do not abuse it.
				   We only mildly obfuscate it to stop copy and paste mistakes in information.xml
				*/
				$okToGo = 0;
				
				if ( strstr( $filepath, 'applications_addon/ips' ) or strstr( $filepath, 'applications/' ) )
				{
					if ( md5( 'ips_' . $_file ) == $info['ipskey'] )
					{
						$okToGo = 1;
					}
				}
				else if ( strstr( $filepath, 'applications_addon/other' ) )
				{
					if ( ! $info['ipskey'] )
					{
						$okToGo = 1;
					}
				}
				
				if ( $info['name'] )
				{
					$uninstalled[$_file] = array( 'title' => $info['name'], 'author' => $info['author'], 'path' => $filepath, 'okToGo' => $okToGo, 'directory' => $_file );
				}
			}
		}
		
		/* Got updates to show? */
		if ( $checkUpdates === true )
		{
			$message = ( $appsUpdates == 1 ) ? $this->lang->words['updates_string_single'] : sprintf( $this->lang->words['updates_string_more'], $appsUpdates );
		}
		
		/* Output */
		$this->registry->output->html .= $this->html->applications_list( $applications, $uninstalled, $message );
	}
	
	/**
	 * View details about an application
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function applicationViewDetails()
	{
		$id	= intval($this->request['app_id']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['a_noid'], 11110.1 );
		}
		
		$appData = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_applications', 'where' => 'app_id=' . $id ) );
		
		if( !$appData['app_id'] )
		{
			$this->registry->output->showError( $this->lang->words['a_nodetails'], 11111.1 );
		}
		
		/* Format dates */
		$lastUpdate = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'upgrade_history', 'where' => "upgrade_app='{$appData['app_directory']}'", 'order' => 'upgrade_version_id DESC', 'limit' => array( 1 ) ) );
		
		$appData['_installed'] = $this->registry->getClass('class_localization')->getDate( $appData['app_added'], 'DATE' );
		$appData['_updated']   = ( $lastUpdate['upgrade_date'] > $appData['app_added'] ) ? $this->registry->getClass('class_localization')->getDate( $lastUpdate['upgrade_date'], 'DATE' ) : '--';
		
		/* Get the setup class */
		require_once ( IPS_ROOT_PATH . "setup/sources/base/setup.php" );/*noLibHook*/
		
		/* Version numbers */
		$_a = in_array( $appData['app_directory'], array( 'forums', 'members' ) ) ? 'core' : $appData['app_directory'];
		$numbers = IPSSetUp::fetchAppVersionNumbers( $_a );
		
		$appData['_human_version'] = $numbers['latest'][1];
		$appData['_long_version']  = $numbers['latest'][0];
		
		$appData['_human_current'] = $numbers['current'][1];
		$appData['_long_current']  = $numbers['current'][0];
		
		/* Got updates? */
		if ( $appData['app_update_check'] )
		{
			/* Get hooks file for update check */
			$classToLoad = IPSLib::loadActionOverloader( IPSLib::getAppDir( 'core' ) . '/modules_admin/applications/hooks.php', 'admin_core_applications_hooks' );
			$hooksClass = new $classToLoad();
			$hooksClass->makeRegistryShortcuts( $this->registry );
			
			$appData['app_update_available'] = $hooksClass->_updateAvailable( $appData['app_update_check'], $appData['app_long_version'] );
		}
		else
		{
			$appData['app_update_available'] = array( 0 );
		}
		
		/* Get related hooks */
		$hooks = array();
		
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'core_hooks',
								 //This is a bit hackish but there's no way around...
								 'where'  => 'hook_requirements LIKE \'%:"' . $this->DB->addSlashes( $appData['app_directory'] ) . '";a:%\'',
								 'order'  => 'hook_id ASC'
						 )		);
		$outer = $this->DB->execute();
		
		/* Got results? */
		if ( $this->DB->getTotalRows( $outer ) )
		{
			/* Get hooks file for update check */
			$classToLoad = IPSLib::loadActionOverloader( IPSLib::getAppDir( 'core' ) . '/modules_admin/applications/hooks.php', 'admin_core_applications_hooks' );
			$hooksClass = new $classToLoad();
			$hooksClass->makeRegistryShortcuts( $this->registry );
			
			/* Loop */
			while ( $rh = $this->DB->fetch( $outer ) )
			{
				/* Check requirements */
				if( $rh['hook_enabled'] )
				{
					$rh['_require_errors'] = $hooksClass->checkHookRequirements( $rh );
				}
				
				$hooks[] = $rh;
			}
		}
		
   		/* Upgrade history */
		$upgradeHistory = array();
   		
   		$this->DB->build( array( 'select' => '*', 'from' => 'upgrade_history', 'where' => "upgrade_app='" . $this->DB->addSlashes( $appData['app_directory'] ) . "'", 'order' => 'upgrade_version_id DESC' ) );
   		$this->DB->execute();
   		
   		while( $r = $this->DB->fetch() )
   		{
   			$upgradeHistory[] = $r;
   		}
   		
		/* Output */
		$this->registry->output->html .= $this->html->application_details( $appData, $upgradeHistory, $hooks );
	}
	
	/**
	 * Recache applications
	 *
	 * @return	@e void
	 */
	public function applicationsRecache()
	{
		$apps = array();
		$incomingEmails = FALSE;
		
		/* Rebuild Applications Cache */
		$this->DB->build( array( 'select' => '*', 'from' => 'core_applications', 'order' => 'app_position ASC' ) );
		$outer = $this->DB->execute();
		
		while ( $row = $this->DB->fetch( $outer ) )
		{
			/* Sort out tab groups */
			$row['app_tab_groups'] = IPSText::cleanPermString( $row['app_tab_groups'] );
			
			if ( $row['app_tab_groups'] )
			{
				$row['app_tab_groups'] = explode( ',', $row['app_tab_groups'] );
			}
			else
			{
				$row['app_tab_groups'] = array();
			}
			
			$apps[$row['app_directory']] = $row;
			
			/* Search */
			// By checking if it's installed here, we don't build the search key properly during a fresh install.
			// We should check if the app is installed at runtime instead.
			//if ( IPSLib::appIsInstalled( $row['app_directory'] ) )
			//{
				$_file = IPSLib::getAppDir( $row['app_directory'] ) . '/extensions/search/config.php';
				
				if ( is_file( $_file ) )
				{
					$CONFIG = array();
					require ( $_file );/*noLibHook*/
					
					if ( is_array( $CONFIG ) and count( $CONFIG ) )
					{
						$apps[$row['app_directory']]['search'] = $CONFIG;
						
						unset( $CONFIG );
					}
				}
			//}
			
			/* Fetch installed extensions */
			$apps[$row['app_directory']]['extensions'] = array( 'itemMarking' => false, 'comments' => false, 'like' => false, 'search' => false, 'incomingEmail' => false );
			
			/* Now go */
			$apps[$row['app_directory']]['extensions']['comments']      = $this->_getHasExtensionComments( $row['app_directory'] );
			$apps[$row['app_directory']]['extensions']['itemMarking']   = $this->_getHasExtensionItemMarking( $row['app_directory'] );
			$apps[$row['app_directory']]['extensions']['incomingEmail'] = $this->_getHasExtensionIncomingEmail( $row['app_directory'] );
			$apps[$row['app_directory']]['extensions']['like']          = $this->_getHasExtensionLike( $row['app_directory'] );
			$apps[$row['app_directory']]['extensions']['search']        = $this->_getHasExtensionSearch( $row['app_directory'] );
			$apps[$row['app_directory']]['extensions']['groupOptions']  = $this->_getGroupExtensions( $row['app_directory'] );
			
			/* incomingEmails? */
			if ( $apps[$row['app_directory']]['extensions']['incomingEmail'] )
			{
				$incomingEmails = TRUE;
			}
			
			/* Has custom header? */
			$apps[ $row['app_directory'] ]['hasCustomHeader'] = ( method_exists( $this->registry->output->getTemplate( $row['app_directory'] . '_global' ), 'overwriteHeader' ) ) ? 1 : 0;
		}
		
		$this->cache->setCache( 'app_cache', $apps, array( 'array' => 1 ) );
		ipsRegistry::$applications = $apps;
		
		/* Do we have an incomingEmails extension anywhere? */
		$systemvars = $this->cache->getCache( 'systemvars' );
		$systemvars['incomingEmails'] = $incomingEmails;
		$this->cache->setCache( 'systemvars', $systemvars, array( 'array' => 1 ) );
		
		/* Rebuild navigation tabs too */
		$tabs = array();
		foreach( $apps as $dir => $data )
		{
			# app_hide_tab is already checked in the output class
			#if ( ! $data['app_hide_tab'] )
			#{
				$tabs[] = array( 'app' => $dir, 'groups' => $data['app_tab_groups'], 'module' => '', // Can't use IPSLib::getAppTitle() because of the IN_ACP check in it
				'title' => $this->lang->words[$data['app_public_title']] ? $this->lang->words[$data['app_public_title']] : $data['app_public_title'] );
			#}
			
			if ( is_file( IPSLib::getAppDir( $dir ) . '/extensions/coreVariables.php' ) )
			{
				$extraTabs = array();
				require_once ( IPSLib::getAppDir( $dir ) . '/extensions/coreVariables.php' );/*noLibHook*/
				
				if ( is_array( $extraTabs ) && count( $extraTabs ) )
				{
					$this->registry->class_localization->loadLanguageFile( array( 'admin_' . $dir ), $dir );
					
					$_extraTabs = array();
					foreach( $extraTabs as $t )
					{
						$t['title'] = $this->lang->words[$t['title']];
						$_extraTabs[] = $t;
					}
					
					$tabs = array_merge( $tabs, $_extraTabs );
				}
				
				unset( $extraTabs );
			}
		}
		
		$this->cache->setCache( 'navigation_tabs', $tabs, array( 'array' => 1 ) );
		
		$this->cache->rebuildCache( 'rss_output_cache', 'global' );
	}
	
	/**
	 * Has Incoming emails extension
	 * @param string $appDir
	 */
	protected function _getHasExtensionIncomingEmail( $appDir )
	{
		if ( is_file( IPSLib::getAppDir( $appDir ) . '/extensions/incomingEmails.php' ) )
		{
			return true;
		}
		
		return false;
	}
	
	/**
	 * Has itemMarking extension
	 * 
	 * @param	string		$appDir		Application directory
	 * @return	@e boolean
	 */
	protected function _getHasExtensionItemMarking( $appDir )
	{
		$_file = IPSLib::getAppDir( $appDir ) . '/extensions/coreExtensions.php';
		if ( is_file( $_file ) )
		{
			$classToLoad = IPSLib::loadLibrary( $_file, 'itemMarking__'.$appDir, $appDir );
			if ( class_exists( $classToLoad ) )
			{
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Has Comments extension
	 * 
	 * @param	string		$appDir		Application directory
	 * @return	@e boolean
	 */
	protected function _getHasExtensionComments( $appDir )
	{
		$return = false;
		
		if ( is_dir( IPSLib::getAppDir( $appDir ) . '/extensions/comments' ) )
		{
			try
			{
				foreach( new DirectoryIterator( IPSLib::getAppDir( $appDir ) . '/extensions/comments' ) as $file )
				{
					if ( ! $file->isDot() and ! $file->isDir() )
					{
						$_name = $file->getFileName();
						
						if ( substr( $_name, - 4 ) == '.php' )
						{
							$return = true;
							break;
						}
					}
				}
			}
			catch( Exception $e )
			{
			}
		}
		
		return $return;
	}
	
	/**
	 * Has Like extension
	 * 
	 * @param	string		$appDir		Application directory
	 * @return	@e boolean
	 */
	protected function _getHasExtensionLike( $appDir )
	{
		$return = false;
		
		if ( is_dir( IPSLib::getAppDir( $appDir ) . '/extensions/like' ) )
		{
			try
			{
				foreach( new DirectoryIterator( IPSLib::getAppDir( $appDir ) . '/extensions/like' ) as $file )
				{
					if ( ! $file->isDot() and ! $file->isDir() )
					{
						$_name = $file->getFileName();
						
						if ( substr( $_name, - 4 ) == '.php' )
						{
							$return = true;
							break;
						}
					}
				}
			}
			catch( Exception $e )
			{
			}
		}
		
		return $return;
	}
	
	/**
	 * Retrieve group inheritence options
	 * 
	 * @param	string		$appDir		Application directory
	 * @return	@e boolean
	 */
	protected function _getGroupExtensions( $appDir )
	{
		$_GROUP = array();
		
		if ( is_file( IPSLib::getAppDir( $appDir ) . '/extensions/coreVariables.php' ) )
		{
			require( IPSLib::getAppDir( $appDir ) . '/extensions/coreVariables.php' );/*noLibHook*/
		}

		return is_array($_GROUP) ? $_GROUP : array();
	}
	
	/**
	 * Has Search extension
	 * 
	 * @param	string		$appDir		Application directory
	 * @return	@e boolean
	 */
	protected function _getHasExtensionSearch( $appDir )
	{
		if ( is_file( IPSLib::getAppDir( $appDir ) . '/extensions/search/config.php' ) )
		{
			return true;
		}
		
		return false;
	}
	
	/**
	 * Recache main version number
	 *
	 * @return	@e void
	 */
	public function versionNumbersRecache()
	{
		$numbers = IPSLib::fetchVersionNumber();
		
		$this->cache->setCache( 'vnums', $numbers, array( 'array' => 1, 'donow' => 1 ) );
	}
	
	/**
	 * Recache menu data
	 *
	 * @return	@e void
	 */
	public function applicationsMenuDataRecache()
	{
		$app_menu_cache = array();
		$modules_cache = array();
		
		//-----------------------------------------
		// Get module data first in one query
		//-----------------------------------------
		

		$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_module', 'where' => 'sys_module_visible=1 AND sys_module_admin=1', 'order' => 'sys_module_position ASC' ) );
		$this->DB->execute();
		
		while ( $module = $this->DB->fetch() )
		{
			$modules_cache[$module['sys_module_application']][] = $module['sys_module_key'];
		}
		
		//-----------------------------------------
		// Now get applications and loop
		//-----------------------------------------
		

		$this->DB->build( array( 'select' => '*', 'from' => 'core_applications', 'order' => 'app_position ASC' ) );
		$outer = $this->DB->execute();
		
		while ( $row = $this->DB->fetch( $outer ) )
		{
			$app_dir	= $row['app_directory'];
			$main_items	= $modules_cache[$app_dir];
			
			//-----------------------------------------
			// Continue...
			//-----------------------------------------

			if( count($main_items) )
			{
				foreach( $main_items as $_current_module )
				{
					$_file = IPSLib::getAppDir( $app_dir ) . "/modules_admin/" . $_current_module . '/xml/menu.xml';
					
					if ( is_file( $_file ) )
					{
						//-----------------------------------------
						// Get xml mah-do-dah
						//-----------------------------------------
						
	
						require_once ( IPS_KERNEL_PATH . 'classXML.php' );/*noLibHook*/
						$xml = new classXML( IPS_DOC_CHAR_SET );
						
						$content = @file_get_contents( $_file );
						
						if ( $content )
						{
							$xml->loadXML( $content );
							$menu = $xml->fetchXMLAsArray();
							$item = array();
							$subItemIndex = 0;
							$itemIndex = 0;
							
							/**
							 * Easiest way I could find to get the data in a proper multi-dimensional array
							 */
							foreach( $menu as $id => $data )
							{
								foreach( $data as $dataKey => $dataValue )
								{
									if ( $dataKey == 'tabitems' )
									{
										foreach( $dataValue as $tabitemsKey => $tabItemsValue )
										{
											if ( $tabitemsKey == 'item' )
											{
												foreach( $tabItemsValue as $itemKey => $itemValue )
												{
													if ( is_int( $itemKey ) )
													{
														foreach( $itemValue as $_itemKey => $_itemValue )
														{
															$subItemIndex = 0;
															
															if ( $_itemKey == 'title' or $_itemKey == 'condition' )
															{
																$item[$itemIndex][$_itemKey] = $_itemValue['#alltext'];
															}
															else if ( $_itemKey == 'subitems' )
															{
																foreach( $_itemValue as $subitemKey => $subitemValue )
																{
																	if ( $subitemKey != '#alltext' )
																	{
																		foreach( $subitemValue as $subitemRealKey => $subitemRealValue )
																		{
																			if ( is_int( $subitemRealKey ) )
																			{
																				foreach( $subitemRealValue as $_subitemRealKey => $_subitemRealValue )
																				{
																					if ( $_subitemRealKey != '#alltext' )
																					{
																						$item[$itemIndex][$_itemKey][$subitemKey][$subItemIndex][$_subitemRealKey] = $_subitemRealValue['#alltext'];
																					}
																				}
																			}
																			else if ( $subitemRealKey != '#alltext' )
																			{
																				$item[$itemIndex][$_itemKey][$subitemKey][$subItemIndex][$subitemRealKey] = $subitemRealValue['#alltext'];
																			}
																			
																			if ( is_int( $subitemRealKey ) )
																			{
																				$subItemIndex ++;
																			}
																		}
																		
																		$subItemIndex ++;
																	}
																}
															}
														}
														
														$itemIndex ++;
													}
													else if ( $itemKey == 'title' )
													{
														$item[$itemIndex][$itemKey] = $itemValue['#alltext'];
													}
													else if ( $itemKey == 'subitems' )
													{
														foreach( $itemValue as $subitemKey => $subitemValue )
														{
															if ( $subitemKey != '#alltext' )
															{
																foreach( $subitemValue as $subitemRealKey => $subitemRealValue )
																{
																	if ( is_int( $subitemRealKey ) )
																	{
																		foreach( $subitemRealValue as $_subitemRealKey => $_subitemRealValue )
																		{
																			if ( $_subitemRealKey != '#alltext' )
																			{
																				$item[$itemIndex][$itemKey][$subitemKey][$subItemIndex][$_subitemRealKey] = $_subitemRealValue['#alltext'];
																			}
																		}
																	}
																	else if ( $subitemRealKey != '#alltext' )
																	{
																		$item[$itemIndex][$itemKey][$subitemKey][$subItemIndex][$subitemRealKey] = $subitemRealValue['#alltext'];
																	}
																	
																	if ( is_int( $subitemRealKey ) )
																	{
																		$subItemIndex ++;
																	}
																}
																
																$subItemIndex ++;
															}
														}
													}
												}
												
												$itemIndex ++;
											}
										}
									}
								}
							}
							
							foreach( $item as $id => $data )
							{
								//-----------------------------------------
								// INIT
								//-----------------------------------------
	
								if ( $data['condition'] )
								{
									$func = create_function( '', $data['condition'] );
	
									if ( ! $func() )
									{
										continue;
									}
								}
								
								$_cat_title = $data['title'];
								$_cat_title = str_replace( '&', '&amp;', $_cat_title ); // Validation thing
								$_nav_main_done = 0;
								
								if ( is_array( $data['subitems'] ) )
								{
									//-----------------------------------------
									// Loop....
									//-----------------------------------------
									
	
									foreach( $data['subitems'] as $__data )
									{
										foreach( $__data as $_id => $_data )
										{
											$_sub_item_title	= $_data['subitemtitle'];
											$_sub_item_url		= $_data['subitemurl'];
											$_sub_is_redirect	= $_data['subisredirect'];
											$_sub_section		= $_data['subsection'];
											$_sub_keywords		= $_data['subitemkeywords'];
											$_sub_item_role_key	= isset( $_data['subitemrolekey'] ) ? $_data['subitemrolekey'] : '';
											$_sub_language		= $_data['subitemlang'];
											
											//-----------------------------------------
											// Continue...
											//-----------------------------------------
											
	
											if ( $_sub_item_title and $_sub_section )
											{
												$app_menu_cache[$app_dir][$id . '_' . $_current_module]['title'] = $_cat_title;
												$app_menu_cache[$app_dir][$id . '_' . $_current_module]['items'][$_id] = array( 'title' => $_sub_item_title, 'module' => $_current_module, 'langkey' => $_sub_language, 'keywords' => $_sub_keywords, 'section' => $_sub_section, 'url' => $_sub_item_url, 'rolekey' => $_sub_item_role_key, 'redirect' => $_sub_is_redirect );
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
		
		$this->cache->setCache( 'app_menu_cache', $app_menu_cache, array( 'array' => 1 ) );
	}
}