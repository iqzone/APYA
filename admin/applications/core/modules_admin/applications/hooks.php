<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Hooks Management
 * Last Updated: $Date: 2012-05-31 11:34:45 -0400 (Thu, 31 May 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		3.0.0
 * @version		$Revision: 10844 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_applications_hooks extends ipsCommand
{
	/**
	 * Skin object
	 *
	 * @var		object			Skin templates
	 */
	protected $html;
	
	/**
	 * Hooks library
	 *
	 * @var		object			Hooks library
	 */
	protected $hooksFunctions;
	
	/**
	 * Existing hooks
	 *
	 * @var		array 			Existing hooks
	 */
	protected $hooks;	
	
	/**
	 * Array of all the cache versions
	 * for the required applications
	 * 
	 * @var	$cachedVersions
	 */
	protected $cachedVersions = array();
	
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
		
		$this->html = $this->registry->output->loadTemplate('cp_skin_applications');
		
		//-----------------------------------------
		// Load hooks library
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('core') . '/sources/classes/hooksFunctions.php', 'hooksFunctions' );
		$this->hooksFunctions = new $classToLoad( $registry );
		
		// lang already loaded by hookFunctions
		//$this->registry->class_localization->loadLanguageFile( array( 'admin_applications' ), 'core' );

		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=applications&amp;section=hooks&amp;';
		$this->form_code_js	= $this->html->form_code_js	= 'module=applications&section=hooks&';
		
		//-----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'reenable_all_hooks':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'hooks_manage' );
				$this->_reenableAllHooks();
			break;
			case 'disable_all_hooks':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'hooks_manage' );
				$this->_disableAllHooks();
			break;
				
			break;
			case 'disable_hook':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'hooks_manage' );
				$this->_disableHook();
			break;
			case 'enable_hook':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'hooks_manage' );
				$this->_enableHook();
			break;
			
			case 'uninstall_hook':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'hooks_delete' );
				$this->_uninstallHook();
			break;
			case 'install_hook':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'hooks_manage' );
				$this->_installHook();
			break;
			
			case 'reorder':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'hooks_manage' );
				$this->_reorder();
			break;
			
			case 'view_details':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'hooks_manage' );
				$this->_viewDetails();
			break;
			
			case 'check_requirements':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'hooks_manage' );
				$this->_checkRequirements();
			break;
			
			case 'export_hook':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'hooks_manage' );
				$this->_exportHook();
			break;
			
			case 'do_export_hook':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'hooks_manage' );
				$this->_doExportHook();
			break;
			
			case 'create_hook':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'hooks_manage' );
				$this->_hookForm( 'add' );
			break;
			
			case 'edit_hook':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'hooks_manage' );
				$this->_hookForm( 'edit' );
			break;
			
			case 'do_create_hook':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'hooks_manage' );
				$this->_hookSave( 'add' );
			break;
			
			case 'do_edit_hook':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'hooks_manage' );
				$this->_hookSave( 'edit' );
			break;
			
			case 'removeDeadCaches':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'hooks_manage' );
				$this->removeDeadCaches();
			break;

			case 'reimport_apps':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'hooks_manage' );
				$this->reimportAppHooks();
			break;

			case 'hooks_overview':
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'hooks_manage' );
				$this->request['do'] = 'hooks_overview';
				$this->_hooksOverview();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Reimport all hooks for all installed applications
	 *
	 * @return	@e void
	 */
	protected function reimportAppHooks()
	{
		$stats	= array( 'inserted' => 0, 'updated' => 0, 'skipped' => 0 );
		
		foreach( ipsRegistry::$applications as $app )
		{
			$_stats	= $this->installAppHooks( $app['app_directory'] );
			
			$stats['inserted']	= $stats['inserted'] + $_stats['inserted'];
			$stats['updated']	= $stats['updated'] + $_stats['updated'];
			$stats['skipped']	= $stats['skipped'] + $_stats['skipped'];
		}
		
		/* Rebuild cache */
		$this->rebuildHooksCache();
		
		/* Rebuild global caches */
		try
		{
			IPSLib::cacheGlobalCaches();
		}
		catch( Exception $e ){}
		
		/* Flag skins for recache */
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );/*noLibHook*/
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );/*noLibHook*/
		$skinCaching = new skinCaching( $this->registry );
		$skinCaching->flagSetForRecache();
		
		/* Redirect */
		$this->registry->output->global_message = sprintf( $this->lang->words['app_hooks_rebuilt'], $stats['inserted'], $stats['updated'], $stats['skipped'] );
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=hooks_overview' );
	}
	
	/**
	 * Rebuild skins following hook import that included a template
	 *
	 * @return	@e void
	 */
	protected function removeDeadCaches()
	{
		$messages = array();
		$keep	  = array();
		$unlink	  = array();
		
		/* Grab all current hooks caches */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'core_hooks_files' ) );
								
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$keep[] = $row['hook_file_stored'];
		}
		
		try
		{
			foreach( new DirectoryIterator( IPS_HOOKS_PATH ) as $file )
			{
				if ( ! $file->isDot() )
				{
					$_name = $file->getFileName();
					
					if ( preg_match( "#_[a-z0-9]{32}\.php$#", $_name ) )
					{
						if ( ! in_array( $_name, $keep ) )
						{
							$unlink[] = $_name;
						}
					}
				}
			}
		} catch ( Exception $e ) { print $e->getMessage(); }
		
		/* Anything to unlink? */
		if ( count( $unlink ) )
		{
			foreach( $unlink as $file )
			{
				@unlink( IPS_HOOKS_PATH . $file );
			}
		}
		
		/* Print message */
		$this->registry->output->global_message = sprintf( $this->lang->words['hook_removed_files'], count( $unlink ) ) . '<br />' . implode( '<br />', $unlink );
		$this->_hooksOverview();
	}

	/**
	 * Reorder hooks
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _reorder()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classAjax.php', 'classAjax' );
		$ajax		 = new $classToLoad();
		
		//-----------------------------------------
		// Checks...
		//-----------------------------------------

		if( $this->registry->adminFunctions->checkSecurityKey( $this->request['md5check'], true ) === false )
		{
			$ajax->returnString( $this->lang->words['postform_badmd5'] );
		}
 		
 		//-----------------------------------------
 		// Save new position
 		//-----------------------------------------

 		$position	= 1;
 		
 		if( is_array($this->request['hooks']) AND count($this->request['hooks']) )
 		{
 			/* Reset all hooks to 0 temporarily */
 			$this->DB->update( 'core_hooks', array( 'hook_position' => 0 ) );
 			
 			foreach( $this->request['hooks'] as $this_id )
 			{
 				$this->DB->update( 'core_hooks', array( 'hook_position' => $position ), 'hook_id=' . $this_id );
 				
 				$position++;
 			}
 		}
 		
		$this->rebuildHooksCache();

 		$ajax->returnString('OK');
	}
	
	/**
	 * Install a new hook
	 *
	 * @return   void
	 */
	protected function _installHook()
	{
		//-----------------------------------------
		// Get uploaded schtuff
		//-----------------------------------------

		$tmp_name = $_FILES['FILE_UPLOAD']['name'];
		$tmp_name = preg_replace( "#\.gz$#", "", $tmp_name );
		
		$content  = ipsRegistry::getClass('adminFunctions')->importXml( $tmp_name );
		
		if( ! $content )
		{
			$this->registry->output->showError( $this->lang->words['h_noxml'], 1110 );
		}

		$this->installHook( $content, TRUE, FALSE );
		
		/* Rebuild cache */
		$this->rebuildHooksCache();
		
		/* Rebuild global caches */
		try
		{
			IPSLib::cacheGlobalCaches();
		}
		catch( Exception $e ){}
		
		/* Flag skins for recache */
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );/*noLibHook*/
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );/*noLibHook*/
		$skinCaching = new skinCaching( $this->registry );
		$skinCaching->flagSetForRecache();
		
		/* Redirect */
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=hooks_overview' );
	}
	
	/**
	 * Function mostly used in installer/upgrader
	 * Inserts new hooks
	 *
	 * @param	string		Application to install hooks for
	 */
	public function installAppHooks( $app )
	{
		static $hooks	= array();

		$msgs    = array( 'inserted' => 0, 'updated' => 0, 'skipped' => 0 );
		$xmlData = array();
		$path    = IPSLib::getAppDir( $app ) . '/xml';
		
		if ( is_file( $path . '/hooks.xml' ) AND is_dir( $path . '/hooks' ) )
		{
			if( !count($hooks) )
			{
				/* Fetch current hooks */
				$this->DB->build( array( 'select' => '*',
										 'from'   => 'core_hooks' ) );
				$this->DB->execute();
				
				while( $row = $this->DB->fetch() )
				{
					if ( $row['hook_key'] )
					{
						$hooks[ $row['hook_key'] ] = $row;
					}
				}
			}
			
			/* Alright. We're in. Read the XML file */
			require_once( IPS_KERNEL_PATH . 'classXML.php' );/*noLibHook*/
			$xml    = new classXML( IPS_DOC_CHAR_SET );

			/* Grab wrapper file */
			$xml->load(  $path . '/hooks.xml' );

			foreach( $xml->fetchElements('hook') as $data )
			{
				$xmlData[] = $xml->fetchElementsFromRecord( $data );
			}

			/* Examine XML */
			if ( is_array( $xmlData ) AND count( $xmlData ) )
			{
				foreach( $xmlData as $x )
				{
					if ( is_file( $path . '/hooks/' . $x['file'] ) )
					{
						$xml->load(  $path . '/hooks/' . $x['file'] );
						
						foreach( $xml->fetchElements('config') as $data )
						{
							$config	= $xml->fetchElementsFromRecord( $data );
						}
						
						if ( ! isset( $hooks[ $config['hook_key'] ] ) )
						{
							/* Add it */
							$msgs['inserted']++;
							$this->installHook( file_get_contents( $path . '/hooks/' . $x['file'] ), FALSE, FALSE, $x['enabled'] );
						} 
						else
						{
							$this->installHook( file_get_contents( $path . '/hooks/' . $x['file'] ), FALSE, FALSE, $x['enabled'] );
							$msgs['updated']++;
						}
					}
				}
			}
		}
		
		return $msgs;
	}
	
	/**
	 * Public install hook so we can use it in the installer and elsewhere
	 *
	 * @param	string		XML data
	 * @param	boolean		Add message to output->global_message
	 * @param	boolean		Allow skins to recache
	 * @param	int			Install enabled
	 * @return	@e void
	 */
	public function installHook( $content, $addMessage=FALSE, $allowSkinRecache=TRUE, $enabled=1 )
	{
		//-----------------------------------------
		// Hooks directory writable?
		//-----------------------------------------
		
		if( !is_writable( IPS_HOOKS_PATH ) )
		{
			if( !$addMessage )
			{
				return false;
			}

			$this->registry->output->showError( $this->lang->words['h_dir_notwritable'], 111159 );
		}
		
		//-----------------------------------------
		// Got our hooks?
		//-----------------------------------------
		
		if( !is_array($this->hooks) OR !count($this->hooks) )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'core_hooks' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$this->hooks[ $r['hook_key'] ]	= $r;
			}
		}

		//-----------------------------------------
		// Get xml mah-do-dah
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH . 'classXML.php' );/*noLibHook*/
		$xml    = new classXML( IPS_DOC_CHAR_SET );
		
		//-----------------------------------------
		// Unpack the datafile
		//-----------------------------------------
		
		$xml->loadXML( $content );

		foreach( $xml->fetchElements('config') as $data )
		{
			$config	= $xml->fetchElementsFromRecord( $data );

			if( !count($config) )
			{
				$this->registry->output->showError( $this->lang->words['h_xmlwrong'], 1111 );
			}
		}
		
		//-----------------------------------------
		// Temp
		//-----------------------------------------
		
		$tempExtraData = unserialize( $config['hook_extra_data'] );

		//-----------------------------------------
		// Set config
		//-----------------------------------------
		
		$config = array('hook_name'				=> $config['hook_name'],
						'hook_desc'				=> $config['hook_desc'],
						'hook_author'			=> $config['hook_author'],
						'hook_email'			=> $config['hook_email'],
						'hook_website'			=> $config['hook_website'],
						'hook_update_check'		=> $config['hook_update_check'],
						'hook_requirements'		=> $config['hook_requirements'],
						'hook_version_human'	=> $config['hook_version_human'],
						'hook_version_long'		=> $config['hook_version_long'],
						'hook_key'				=> $config['hook_key'],
						'hook_global_caches'	=> $config['hook_global_caches'],
						'hook_enabled'			=> $enabled,
						'hook_updated'			=> IPS_UNIX_TIME_NOW,
						);

		$extra_data	= array();

		//-----------------------------------------
		// Set files
		//-----------------------------------------
		
		$files		= array();

		foreach( $xml->fetchElements('hookfiles') as $node )
		{
			foreach( $xml->fetchElements('file', $node) as $_file )
			{
				$file	= $xml->fetchElementsFromRecord( $_file );
	
				if( $file['hook_type'] )
				{
					$files[] 	= array(
										'hook_file_real'	=> $file['hook_file_real'],
										'hook_type'			=> $file['hook_type'],
										'hook_classname'	=> $file['hook_classname'],
										'hook_data'			=> $file['hook_data'],
										'hooks_source'		=> $file['hooks_source'],
										);
				}
			}
		}

		//-----------------------------------------
		// Set the custom script
		//-----------------------------------------

		$custom		 = array();
		$customClass = null;

		foreach( $xml->fetchElements('hookextras_custom') as $node )
		{
			foreach( $xml->fetchElements('file', $node) as $_file )
			{
				$file	= $xml->fetchElementsFromRecord( $_file );
				
				if( count($file) )
				{
					$custom	= array( 'filename'	=> $file['filename'],
									 'source'	=> $file['source'],
									);
				}
			}
		}
		
		/* Got any custom file to initialize? */
		if ( $custom['filename'] && $custom['source'] )
		{
			# Our dirty trick to avoid saving the file on disk just yet...
			$_custom = preg_replace( '#^(\s*)<\?(php)+#i', '', $custom['source'] );
			eval($_custom);
			
			$classname = str_replace( '.php', '', $custom['filename'] );
			
			if( class_exists( $classname ) )
			{
				$customClass = new $classname( $this->registry );
				
				if( method_exists( $customClass, 'pre_install' ) )
				{
					$customClass->pre_install();
				}
			}
		}
		
		//-----------------------------------------
		// Set the settings
		//-----------------------------------------
		
		$settings		= array();
		$settingGroups	= array();

		foreach( $xml->fetchElements('hookextras_settings') as $node )
		{
			foreach( $xml->fetchElements('setting', $node) as $_setting )
			{
				$setting	= $xml->fetchElementsFromRecord( $_setting );
	
				if( $setting['conf_is_title'] == 1)
				{
					$settingGroups[]	= array(
												'conf_title_title'		=> $setting['conf_title_title'],
												'conf_title_desc'		=> $setting['conf_title_desc'],
												'conf_title_noshow'		=> $setting['conf_title_noshow'],
												'conf_title_keyword'	=> $setting['conf_title_keyword'],
												'conf_title_app'		=> $setting['conf_title_app'],
												'conf_title_tab'		=> $setting['conf_title_tab'],
												);
				}
				else
				{
					$settings[]			= array(
												'conf_title'			=> $setting['conf_title'],
												'conf_description'		=> $setting['conf_description'],
												'conf_group'			=> $setting['conf_group'],
												'conf_type'				=> $setting['conf_type'],
												'conf_key'				=> $setting['conf_key'],
												'conf_default'			=> $setting['conf_default'],
												'conf_extra'			=> $setting['conf_extra'],
												'conf_evalphp'			=> $setting['conf_evalphp'],
												'conf_protected'		=> $setting['conf_protected'],
												'conf_position'			=> $setting['conf_position'],
												'conf_start_group'		=> $setting['conf_start_group'],
												'conf_add_cache'		=> $setting['conf_add_cache'],
												'conf_title_keyword'	=> $setting['conf_title_keyword'],
												);
				}
			}
		}

		//-----------------------------------------
		// Set the lang bits
		//-----------------------------------------
		
		$language		= array();

		foreach( $xml->fetchElements('hookextras_language') as $node )
		{
			foreach( $xml->fetchElements('language', $node) as $_langbit )
			{
				$langbit	= $xml->fetchElementsFromRecord( $_langbit );
				
				$language[]	= array(
									'word_app'		=> $langbit['word_app'],
									'word_pack'		=> $langbit['word_pack'],
									'word_key'		=> $langbit['word_key'],
									'word_default'	=> $langbit['word_default'],
									'word_custom'	=> $langbit['word_custom'],
									'word_js'		=> intval($langbit['word_js']),
									);
			}
		}
		
		//-----------------------------------------
		// Set the modules
		//-----------------------------------------
		
		$modules		= array();

		foreach( $xml->fetchElements('hookextras_modules') as $node )
		{
			foreach( $xml->fetchElements('module', $node) as $_module )
			{
				$module		= $xml->fetchElementsFromRecord( $_module );
				$modules[]	= array(
									'sys_module_title'			=> $module['sys_module_title'],
									'sys_module_application'	=> $module['sys_module_application'],
									'sys_module_key'			=> $module['sys_module_key'],
									'sys_module_description'	=> $module['sys_module_description'],
									'sys_module_version'		=> $module['sys_module_version'],
									'sys_module_protected'		=> $module['sys_module_protected'],
									'sys_module_visible'		=> $module['sys_module_visible'],
									'sys_module_position'		=> $module['sys_module_position'],
									'sys_module_admin'			=> $module['sys_module_admin'],
									);
			}
		}
		
		//-----------------------------------------
		// Set the help files
		//-----------------------------------------
		
		$help			= array();

		foreach( $xml->fetchElements('hookextras_help') as $node )
		{
			foreach( $xml->fetchElements('help', $node) as $_helpfile )
			{
				$helpfile	= $xml->fetchElementsFromRecord( $_helpfile );
				$help[]		= array(
									'title'			=> $helpfile['title'],
									'text'			=> $helpfile['text'],
									'description'	=> $helpfile['description'],
									'position'		=> $helpfile['position'],
									);
			}
		}
		
		//-----------------------------------------
		// Set the templates
		//-----------------------------------------
		
		$templates		= array();

		foreach( $xml->fetchElements('hookextras_templates') as $node )
		{
			foreach( $xml->fetchElements('templates', $node) as $_template )
			{
				$template		= $xml->fetchElementsFromRecord( $_template );
				$templates[]	= array(
										'template_set_id'		=> 0,
										'template_group'		=> $template['template_group'],
										'template_content'		=> $template['template_content'],
										'template_name'			=> $template['template_name'],
										'template_data'			=> $template['template_data'],
										'template_updated'		=> $template['template_updated'],
										'template_removable'	=> $template['template_removable'],
										'template_added_to'		=> intval($template['template_added_to']),
										'template_user_added'	=> 1,
										'template_user_edited'  => 0,
										'template_master_key'   => $template['template_master_key'] ? $template['template_master_key'] : 'root',
										);
			}
		}
		
		//-----------------------------------------
		// Set the CSS
		//-----------------------------------------
		
		$css			= array();

		foreach( $xml->fetchElements('hookextras_css') as $node )
		{
			foreach( $xml->fetchElements('css', $node) as $_css )
			{
				$_css	= $xml->fetchElementsFromRecord( $_css );
				$css[]	= array(
									'css_set_id'		=> 0,
									'css_group'			=> $_css['css_group'],
									'css_content'		=> $_css['css_content'],
									'css_position'		=> $_css['css_position'],
									'css_added_to'		=> 1,
									'css_app'			=> $_css['css_app'],
									'css_app_hide'		=> $_css['css_app_hide'],
									'css_attributes'	=> $_css['css_attributes'],
									'css_removed'		=> $_css['css_removed'],
									'css_modules'		=> $_css['css_modules'],
									'css_master_key'	=> $_css['css_master_key'],
									);
			}
		}
		
		//-----------------------------------------
		// Set the Replacements
		//-----------------------------------------
		
		$replacements	= array();
		
		foreach( $xml->fetchElements('hookextras_replacements') as $node )
		{
			foreach( $xml->fetchElements('replacements', $node) as $_r )
			{
				$_r	= $xml->fetchElementsFromRecord( $_r );
				$replacements[]	= array(
									'replacement_key'		=> $_r['replacement_key'],
									'replacement_content'	=> $_r['replacement_content'],
									);
			}
		}
		
		//-----------------------------------------
		// Set the tasks
		//-----------------------------------------
		
		$tasks			= array();

		foreach( $xml->fetchElements('hookextras_tasks') as $node )
		{
			foreach( $xml->fetchElements('tasks', $node) as $_task )
			{
				$task		= $xml->fetchElementsFromRecord( $_task );
				$tasks[]	= array(
									'task_title'		=> $task['task_title'],
									'task_file'			=> $task['task_file'],
									'task_week_day'		=> $task['task_week_day'],
									'task_month_day'	=> $task['task_month_day'],
									'task_hour'			=> $task['task_hour'],
									'task_minute'		=> $task['task_minute'],
									'task_cronkey'		=> $task['task_cronkey'],
									'task_log'			=> $task['task_log'],
									'task_description'	=> $task['task_description'],
									'task_enabled'		=> $task['task_enabled'],
									'task_key'			=> $task['task_key'],
									'task_safemode'		=> $task['task_safemode'],
									'task_locked'		=> $task['task_locked'],
									'task_application'	=> $task['task_application'],
									);
			}
		}

		//-----------------------------------------
		// Set the database changes
		//-----------------------------------------
		
		$database		= array(
								'create'	=> array(),
								'alter'		=> array(),
								'update'	=> array(),
								'insert'	=> array(),
								);

		foreach( $xml->fetchElements('hookextras_database_create') as $node )
		{
			foreach( $xml->fetchElements('create', $node) as $_table )
			{
				$table		= $xml->fetchElementsFromRecord( $_table );
				$database['create'][]	= array(
											'name'		=> $table['name'],
											'fields'	=> $table['fields'],
											'tabletype'	=> $table['tabletype'],
											);
			}
		}

		foreach( $xml->fetchElements('hookextras_database_alter') as $node )
		{
			foreach( $xml->fetchElements('alter', $node) as $_table )
			{
				$table		= $xml->fetchElementsFromRecord( $_table );
				$database['alter'][]	= array(
											'altertype'	=> $table['altertype'],
											'table'		=> $table['table'],
											'field'		=> $table['field'],
											'newfield'	=> $table['newfield'],
											'fieldtype'	=> $table['fieldtype'],
											'default'	=> $table['default'],
											);
			}
		}

		foreach( $xml->fetchElements('hookextras_database_update') as $node )
		{
			foreach( $xml->fetchElements('update', $node) as $_table )
			{
				$table		= $xml->fetchElementsFromRecord( $_table );
				$database['update'][]	= array(
											'table'		=> $table['table'],
											'field'		=> $table['field'],
											'newvalue'	=> $table['newvalue'],
											'oldvalue'	=> $table['oldvalue'],
											'where'		=> $table['where'],
											);
			}
		}

		foreach( $xml->fetchElements('hookextras_database_insert') as $node )
		{
			foreach( $xml->fetchElements('insert', $node) as $_table )
			{
				$table		= $xml->fetchElementsFromRecord( $_table );
				$database['insert'][]	= array(
											'table'		=> $table['table'],
											'updates'	=> $table['updates'],
											'fordelete'	=> $table['fordelete'],
											);
			}
		}

		//-----------------------------------------
		// Set some vars for display tallies
		//-----------------------------------------

		$filesInserted			= 0;
		$settingGroupsInserted	= 0;
		$settingsInserted		= 0;
		$settingsUpdated		= 0;
		$languageInserted		= 0;
		$languageUpdated		= 0;
		$modulesInserted		= 0;
		$modulesUpdated			= 0;
		$helpInserted			= 0;
		$helpUpdated			= 0;
		$templatesInserted		= 0;
		$templatesUpdated		= 0;
		$templateHooks		    = false;
		$tasksInserted			= 0;
		$tasksUpdated			= 0;
		$createQueries			= 0;
		$alterQueries			= 0;
		$updateQueries			= 0;
		$insertQueries			= 0;
		$cssInserted			= 0;
		$cssUpdated				= 0;
		$replacementsInserted	= 0;
		$replacementsUpdated	= 0;
		
		//-----------------------------------------
		// Insert/update DB records
		//-----------------------------------------
		
		if( $this->hooks[ $config['hook_key'] ]['hook_id'] )
		{
			//-----------------------------------------
			// Don't change enabled/disabled status
			//-----------------------------------------
			
			unset( $config['hook_enabled'] );
			
			$this->DB->update( 'core_hooks', $config, 'hook_id=' . $this->hooks[ $config['hook_key'] ]['hook_id'] );
			
			$hook_id	= $this->hooks[ $config['hook_key'] ]['hook_id'];
			
			$extra_data	= unserialize( $this->hooks[ $config['hook_key'] ]['hook_extra_data'] );
			
			/* Reset DB */
			$extra_data['database'] = array();
		}
		else
		{
			$config['hook_installed']			= IPS_UNIX_TIME_NOW;
			$this->hook[ $config['hook_key'] ]	= $config;
			
			$this->DB->insert( 'core_hooks', $config );
			
			$hook_id	= $this->DB->getInsertId();
			
			$this->hook[ $config['hook_key'] ]['hook_id'] = $hook_id;
			
			$extra_data['display']	= $tempExtraData['display'];
		}

		if( count($files) )
		{
			//-----------------------------------------
			// If we are updating, remove old files
			//-----------------------------------------
			
			if( $this->hooks[ $config['hook_key'] ]['hook_id'] )
			{
				$this->DB->build( array( 'select' => 'hook_file_id, hook_file_stored', 'from' => 'core_hooks_files', 'where' => 'hook_hook_id=' . $this->hooks[ $config['hook_key'] ]['hook_id'] ) );
				$outer = $this->DB->execute();
				
				while( $r = $this->DB->fetch($outer) )
				{
					@unlink( IPS_HOOKS_PATH . $r['hook_file_stored'] );
					$this->DB->delete( 'core_hooks_files', 'hook_file_id=' . $r['hook_file_id'] );
				}
			}
				
			foreach( $files as $file )
			{
				//-----------------------------------------
				// Store new files
				//-----------------------------------------
				
				$filename	= $file['hook_classname'] . '_' . md5( uniqid( microtime(), true ) ) . '.php';
				
				file_put_contents( IPS_HOOKS_PATH . $filename, $file['hooks_source'] );
				chmod( IPS_HOOKS_PATH . $filename, IPS_FILE_PERMISSION );
				
				$file['hook_file_stored']	= $filename;
				$file['hook_hook_id']		= $hook_id;
				
				$this->DB->insert( 'core_hooks_files', $file );
				$filesInserted++;
				
				/* Need to recache skins? */
				if ( $file['hook_type'] == 'templateHooks' )
				{
					$templateHooks = true;
				}
			}
		}
		
		//-----------------------------------------
		// Put custom install/uninstall file
		//-----------------------------------------
		
		if( is_object($customClass) )
		{
			$extra_data['display']['custom'] = $custom['filename'];
			
			file_put_contents( IPS_HOOKS_PATH . 'install_' . $custom['filename'], $custom['source'] );
			chmod( IPS_HOOKS_PATH . 'install_' . $custom['filename'], IPS_FILE_PERMISSION );
		}
				
		//-----------------------------------------
		// (1) Settings
		//-----------------------------------------
		
		if( count($settingGroups) OR count($settings) )
		{
			$setting_groups = array();
			
			$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_settings_titles' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$setting_groups[ $r['conf_title_id'] ] = $r;
				$setting_groups_by_key[ $r['conf_title_keyword'] ] = $r;
			}
			
			//-----------------------------------------
			// Get current settings.
			//-----------------------------------------
			
			$this->DB->build( array( 'select' => 'conf_id, conf_key', 'from' => 'core_sys_conf_settings' ) );
			$this->DB->execute();
			
			while ( $r = $this->DB->fetch() )
			{
				$cur_settings[ $r['conf_key'] ] = $r['conf_id'];
			}
		}
			
		if( count($settingGroups) )
		{
			$need_to_update = array();
			
			foreach( $settingGroups as $data )
			{
				if ( $data['conf_title_title'] AND $data['conf_title_keyword'] )
				{
					//-----------------------------------------
					// Get ID based on key
					//-----------------------------------------
					
					$conf_id = $setting_groups_by_key[ $data['conf_title_keyword'] ]['conf_title_id'];
					
					$save = array( 'conf_title_title'   => $data['conf_title_title'],
								   'conf_title_desc'    => $data['conf_title_desc'],
								   'conf_title_keyword' => $data['conf_title_keyword'],
								   'conf_title_app'     => $data['conf_title_app'],
								   'conf_title_tab'		=> $data['conf_title_tab'],
								   'conf_title_noshow'  => $data['conf_title_noshow']  );
					
					//-----------------------------------------
					// Not got a row, insert first!
					//-----------------------------------------
					
					if ( ! $conf_id )
					{
						$this->DB->insert( 'core_sys_settings_titles', $save );
						$conf_id = $this->DB->getInsertId();
						$settingGroupsInserted++;
						$extra_data['settingGroups'][] = $save['conf_title_keyword'];
					}
					else
					{
						//-----------------------------------------
						// Update...
						//-----------------------------------------
						
						$this->DB->update( 'core_sys_settings_titles', $save, 'conf_title_id='.$conf_id );
					}
					
					//-----------------------------------------
					// Update settings cache
					//-----------------------------------------
					
					$save['conf_title_id']									= $conf_id;
					$setting_groups_by_key[ $save['conf_title_keyword'] ]	= $save;
					$setting_groups[ $save['conf_title_id'] ]				= $save;
						
					//-----------------------------------------
					// Set need update...
					//-----------------------------------------
					
					$need_update[] = $conf_id;
				}
			}
		}
		
		if( count($settings) )
		{
			foreach( $settings as $idx => $data )
			{
				$data['conf_group'] = $setting_groups_by_key[ $data['conf_title_keyword'] ]['conf_title_id'];
				
				//-----------------------------------------
				// Remove from array
				//-----------------------------------------
				
				unset( $data['conf_title_keyword'] );
				
				if ( $cur_settings[ $data['conf_key'] ] )
				{
					$this->DB->update( 'core_sys_conf_settings', $data, 'conf_id='.$cur_settings[ $data['conf_key'] ] );
					$settingsUpdated++;
				}
				else
				{
					$this->DB->insert( 'core_sys_conf_settings', $data );
					$settingsInserted++;
					$extra_data['settings'][] = $data['conf_key'];
				}
			}
		}
		
		//-----------------------------------------
		// Update group counts...
		//-----------------------------------------
		
		if ( count( $need_update ) )
		{
			foreach( $need_update as $i => $idx )
			{
				$conf = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count', 'from' => 'core_sys_conf_settings', 'where' => 'conf_group=' . $idx ) );
			
				$count = intval($conf['count']);
				
				$this->DB->update( 'core_sys_settings_titles', array( 'conf_title_count' => $count ), 'conf_title_id='.$idx );
			}
		}
		
		if( count($settingGroups) OR count($settings) )
		{
			$this->cache->rebuildCache( 'settings', 'global' );
		}

		//-----------------------------------------
		// (2) Languages
		//-----------------------------------------

		if( count($language) )
		{
			$langPacks	= array();
			
			$this->DB->build( array( 'select' => 'lang_id', 'from' => 'core_sys_lang' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$langPacks[] = $r['lang_id'];
			}

			foreach( $language as $langbit )
			{
				foreach( $langPacks as $lang_id )
				{
					$langbit['lang_id'] = $lang_id;
					
					// See if it exists
					$cnt = $this->DB->buildAndFetch( array( 'select' => 'word_id', 'from' => 'core_sys_lang_words', 'where' => "lang_id={$lang_id} AND word_app='{$langbit['word_app']}' AND word_key='{$langbit['word_key']}' AND word_pack='{$langbit['word_pack']}'" ) );
					
					if( $cnt['word_id'] )
					{
						$this->DB->update( 'core_sys_lang_words', $langbit, 'word_id=' . $cnt['word_id'] );
						$languageUpdated++;
					}
					else
					{
						$this->DB->insert( 'core_sys_lang_words', $langbit );
						$languageInserted++;
						$word_id = $this->DB->getInsertId();
						$extra_data['language'][ $langbit['word_pack'] ][]	= $langbit['word_key'];
					}
				}
			}
			
			$classToLoad = IPSLib::loadActionOverloader( IPSLib::getAppDir('core') . '/modules_admin/languages/manage_languages.php', 'admin_core_languages_manage_languages' );
			$langLib     = new $classToLoad();
			$langLib->makeRegistryShortcuts( $this->registry );
			
			foreach( $langPacks as $langId )
			{
				$langLib->cacheToDisk( $langId );
			}
		}
		
		//-----------------------------------------
		// (3) Modules
		//-----------------------------------------
		
		if( count($modules) )
		{
			//-----------------------------------------
			// Get current modules
			//-----------------------------------------
			$cur_modules = array();
			$this->DB->build( array( 'select'	=> '*',
										'from'	=> 'core_sys_module',
										'order'	=> 'sys_module_id' ) );
			
			$this->DB->execute();
			
			while ( $r = $this->DB->fetch() )
			{
				$cur_modules[ $r['sys_module_application'] ][ $r['sys_module_key'] ] = $r['sys_module_id'];
			}
			
			foreach( $modules as $module )
			{
				//-----------------------------------------
				// Insert or update?
				//-----------------------------------------
			
				if ( $cur_modules[ $module['sys_module_application'] ][ $module['sys_module_key'] ] )
				{
					$this->DB->update( 'core_sys_module', $module, "sys_module_id=" . $cur_modules[ $module['sys_module_application'] ][ $module['sys_module_key'] ] );
					$modulesUpdated++;
				}
				else
				{
					$this->DB->insert( 'core_sys_module', $module );
					$modulesInserted++;
					
					$extra_data['modules'][] = ( $module['sys_module_admin'] ? 'admin' : 'public' ) . '-' . $module['sys_module_application'] . '-' . $module['sys_module_key'];
				}
			}
			
			$classToLoad = IPSLib::loadActionOverloader( IPSLib::getAppDir('core') . '/modules_admin/applications/applications.php', 'admin_core_applications_applications' );
			$moduleLib   = new $classToLoad();
			$moduleLib->makeRegistryShortcuts( $this->registry );
			$moduleLib->moduleRecache();
			$moduleLib->applicationsMenuDataRecache();
		}
		
		//-----------------------------------------
		// (4) Help Files
		//-----------------------------------------
		
		if( count($help) )
		{
			$keys		= array();
			
			$this->DB->build( array( 'select' => 'title', 'from' => 'faq' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$keys[] = $r['title'];
			}

			foreach( $help as $entry )
			{
				if( in_array( $entry['title'], $keys ) )
				{
					$this->DB->update( 'faq', $entry, "title='{$entry['title']}'" );
					$helpUpdated++;
				}
				else
				{	
					$this->DB->insert( 'faq', $entry );
					$helpInserted++;
					$help_id = $this->DB->getInsertId();
					$extra_data['help'][] = $entry['title'];
				}
			}
		}

		//-----------------------------------------
		// (6) Templates
		//-----------------------------------------
		
		if( count($templates) )
		{
			$bits		= array();
			
			/* Root will always be updated */
			$this->DB->build( array( 'select' => 'template_name,template_group,template_master_key', 'from' => 'skin_templates' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$bits[ $r['template_master_key'] ][ $r['template_group'] ][] = $r['template_name'];
			}

			foreach( $templates as $template )
			{
				//-----------------------------------------
				// If template is not in root set, add it to
				// prevent fatal error selecting other skins
				//-----------------------------------------
				
				if ( $template['template_master_key'] != 'root' && ( !isset($bits['root'][ $template['template_group'] ]) OR !in_array( $template['template_name'], $bits['root'][ $template['template_group'] ] ) ) )
				{
					$this->DB->insert( 'skin_templates', array_merge( $template, array( 'template_set_id' => 0, 'template_master_key' => 'root', 'template_content' => '' ) ) );
					
					$extra_data['templates'][ $template['template_group'] ][ $template['template_name'] ]	= $template['template_name'];
					$bits['root'][ $template['template_group'] ][]											= $template['template_name'];
				}
				
				//-----------------------------------------
				// Ignore unknown skin sets
				//-----------------------------------------
				
				if ( !isset($bits[ $template['template_master_key'] ]) )
				{
					continue;
				}
				
				if( isset($bits[ $template['template_master_key'] ][ $template['template_group'] ]) AND in_array( $template['template_name'], $bits[ $template['template_master_key'] ][ $template['template_group'] ] ) )
				{
					$template['template_updated'] = IPS_UNIX_TIME_NOW;

					$this->DB->update( 'skin_templates', $template, "template_master_key='{$template['template_master_key']}' AND template_group='{$template['template_group']}' AND template_name='{$template['template_name']}'" );
					$templatesUpdated++;
				}
				else
				{	
					$this->DB->insert( 'skin_templates', $template );
					$templatesInserted++;

					$extra_data['templates'][ $template['template_group'] ][ $template['template_name'] ]	= $template['template_name'];
					$bits[ $template['template_master_key'] ][ $template['template_group'] ][]				= $template['template_name'];
				}
			}
		}
		
		//-----------------------------------------
		// CSS
		//-----------------------------------------
		
		if( count( $css ) )
		{
			$bits		= array();
			
			$this->DB->build( array( 'select' => 'css_master_key, css_group', 'from' => 'skin_css' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$bits[ $r['css_master_key'] ][ $r['css_group'] ] = $r['css_group'];
			}

			foreach( $css as $entry )
			{
				//-----------------------------------------
				// Ignore unknown skin sets
				//-----------------------------------------
				
				if ( !isset( $bits[ $entry['css_master_key'] ] ) )
				{
					continue;
				}
				
				if( in_array( $entry['css_group'], $bits[ $entry['css_master_key'] ] ) )
				{
					$entry['css_updated']	= IPS_UNIX_TIME_NOW;

					$this->DB->update( 'skin_css', $entry, "css_master_key='{$entry['css_master_key']}' AND css_group='{$entry['css_group']}'" );
					$cssUpdated++;
				}
				else
				{	
					$this->DB->insert( 'skin_css', $entry );
					$cssInserted++;

					$extra_data['css'][ $entry['css_group'] ]					= $entry['css_group'];
					$bits[ $entry['css_master_key'] ][ $entry['css_group'] ]	= $entry['css_group'];
				}
			}
		}
		
		//-----------------------------------------
		// Skin Replacements
		//-----------------------------------------
				
		if( count( $replacements ) )
		{
			$bits		= array();
			
			$this->DB->build( array( 'select' => 'replacement_master_key, replacement_key', 'from' => 'skin_replacements' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$bits[ $r['replacement_master_key'] ][ $r['replacement_key'] ] = $r['replacement_key'];
			}
			
			foreach( $replacements as $entry )
			{
				if ( !isset( $bits[ $entry['replacement_master_key'] ] ) )
				{
					$entry['replacement_master_key'] = 'root';
				}
			
				if( in_array( $entry['replacement_key'], $bits[ $entry['replacement_master_key'] ] ) )
				{
					$this->DB->update( 'skin_replacements', $entry, "replacement_master_key='{$entry['replacement_master_key']}' AND replacement_key='{$entry['replacement_key']}'" );
					$replacementsUpdated++;
				}
				else
				{	
					$this->DB->insert( 'skin_replacements', $entry );
					$replacementsInserted++;

					$extra_data['replacements'][ $entry['replacement_key'] ]		= $entry['replacement_key'];
				}
			}
		}
		
		//-----------------------------------------
		// (7) Tasks
		//-----------------------------------------
		
		if( count($tasks) )
		{
			$keys		= array();
			
			$this->DB->build( array( 'select' => 'task_key', 'from' => 'task_manager' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$keys[] = $r['task_key'];
			}

			foreach( $tasks as $entry )
			{
				if( in_array( $entry['task_key'], $keys ) )
				{
					$this->DB->update( 'task_manager', $entry, "task_key='{$entry['task_key']}'" );
					$tasksUpdated++;
				}
				else
				{	
					$this->DB->insert( 'task_manager', $entry );
					$tasksInserted++;
					$task_id = $this->DB->getInsertId();
					$extra_data['tasks'][] = $entry['task_key'];
				}
			}
		}
		
		//-----------------------------------------
		// (8) Create new tables
		//-----------------------------------------
		
		if( count($database['create']) )
		{
			foreach($database['create'] as $create )
			{
				$query	= "CREATE TABLE {$this->settings['sql_tbl_prefix']}{$create['name']} (
							{$create['fields']}
							)";
				
				if( $create['tabletype'] )
				{
					$query .= " ENGINE=" . $create['tabletype'];
				}
				else if( $this->settings['sql_driver'] == 'mysql' AND $this->settings['mysql_tbl_type'] )
				{
					$query .= " ENGINE=" . $this->settings['mysql_tbl_type'];
				}
				
				//-----------------------------------------
				// Fix prefix
				//-----------------------------------------

				$this->DB->return_die = true;
				$this->DB->query( $query );
				$this->DB->return_die = false;
				$createQueries++;
				
				$extra_data['database']['create'][] = $create;
			}
		}

		//-----------------------------------------
		// (9) Alter tables
		//-----------------------------------------
		
		if( count($database['alter']) )
		{
			foreach( $database['alter'] as $alter )
			{
				$this->DB->return_die = true;
				
				switch( $alter['altertype'] )
				{
					case 'remove':
						$this->DB->dropField( $alter['table'], $alter['field'] );
					break;
					
					case 'add':
						$this->DB->addField( $alter['table'], $alter['field'], $alter['fieldtype'], $alter['default'] );
					break;
					
					case 'change':
						$this->DB->changeField( $alter['table'], $alter['field'], $alter['newfield'], $alter['fieldtype'], $alter['default'] );
					break;
				}
				
				$this->DB->return_die = false;
				$alterQueries++;
				$extra_data['database']['alter'][] = $alter;
			}
		}
		
		//-----------------------------------------
		// (10) Run update queries
		//-----------------------------------------
		
		if( count($database['update']) )
		{
			foreach( $database['update'] as $update )
			{
				$this->DB->return_die = true;
				$this->DB->update( $update['table'], array( $update['field'] => $update['newvalue'] ), html_entity_decode( $update['where'], ENT_QUOTES ) );
				$this->DB->return_die = false;
				$updateQueries++;
				$extra_data['database']['update'][] = $update;
			}
		}
		
		//-----------------------------------------
		// (11) Run insert queries
		//-----------------------------------------
		
	//	if( !$this->hooks[ $config['hook_key'] ]['hook_id'] )
	//	{
			if( count($database['insert']) )
			{
				foreach( $database['insert'] as $insert )
				{
					$fields		= array();
					$content	= explode( ',', $insert['updates'] );
					
					foreach( $content as $value )
					{
						list( $field, $toInsert ) = explode( '=', $value );
						
						$fields[ trim($field) ] = str_replace( '~C~', ',', $toInsert );
					}

					$this->DB->return_die = true;
					$this->DB->insert( $insert['table'], $fields );
					$this->DB->return_die = false;
					$insertQueries++;
					$extra_data['database']['insert'][] = $insert;
				}
			}
	//	}
		
		/* Got an install function to run too? */
		if( is_object($customClass) )
		{
			if( method_exists( $customClass, 'install' ) )
			{
				$customClass->install();
			}
		}
		
		if( count($extra_data) )
		{
			$this->DB->update( 'core_hooks', array( 'hook_extra_data' => serialize( $extra_data ) ), 'hook_id=' . $hook_id );
		}
					
		//print_r($config);
		//print_r($files);
		//print_r($custom);
		//print_r($settingGroups);
		//print_r($settings);
		//print_r($language);
		//print_r($modules);
		//print_r($templates);
		//print_r($tasks);
		//print_r($help);
		//print_r($database);

		if ( $addMessage )
		{
			$_tempMsg = <<<EOF
		<strong>{$this->lang->words['h_newhookin']}</strong>
		<ul>
			<li>{$filesInserted} {$this->lang->words['h_filesin']}</li>
			<li>{$settingGroupsInserted} {$this->lang->words['h_settinggin']}</li>
			<li>{$settingsInserted} {$this->lang->words['h_settingin']}</li>
			<li>{$settingsUpdated} {$this->lang->words['h_settingup']}</li>
			<li>{$languageInserted} {$this->lang->words['h_langbitin']}</li>
			<li>{$languageUpdated} {$this->lang->words['h_langbitup']}</li>
			<li>{$modulesInserted} {$this->lang->words['h_modin']}</li>
			<li>{$modulesUpdated} {$this->lang->words['h_modup']}</li>
			<li>{$helpInserted} {$this->lang->words['h_helpin']}</li>
			<li>{$helpUpdated} {$this->lang->words['h_helpup']}</li>
			<li>{$templatesInserted} {$this->lang->words['h_tempin']}</li>
			<li>{$templatesUpdated} {$this->lang->words['h_tempup']}</li>
			<li>{$cssInserted} {$this->lang->words['h_cssin']}</li>
			<li>{$cssUpdated} {$this->lang->words['h_cssup']}</li>
			<li>{$replacementsInserted} {$this->lang->words['h_replacementsin']}</li>
			<li>{$replacementsUpdated} {$this->lang->words['h_replacementsup']}</li>
			<li>{$tasksInserted} {$this->lang->words['h_taskin']}</li>
			<li>{$tasksUpdated} {$this->lang->words['h_taskup']}</li>
			<li>{$createQueries} {$this->lang->words['h_dbcreated']}</li>
			<li>{$alterQueries} {$this->lang->words['h_dbaltered']}</li>
			<li>{$updateQueries} {$this->lang->words['h_updateran']}</li>
			<li>{$insertQueries} {$this->lang->words['h_insertran']}</li>
		</ul>
EOF;

			$this->registry->output->setMessage( $_tempMsg, 1 );
		}
		
		//-----------------------------------------
		// Got some skin recaching to do...
		//-----------------------------------------
		
		if( $allowSkinRecache === TRUE AND ( $templatesInserted OR $templatesUpdated OR $templateHooks OR $cssUpdated OR $cssInserted ) )
		{
			/* Rebuild cache */
			$this->rebuildHooksCache();
			
			/* Flag skins for recache */
			require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );/*noLibHook*/
			require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );/*noLibHook*/
			$skinCaching = new skinCaching( $this->registry );
			$skinCaching->flagSetForRecache();
		}
	}

	/**
	 * Show the form to export a hook
	 *
	 * @return   void
	 */
	protected function _exportHook()
	{
		/* Get the hook */
		$id	= intval($this->request['id']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['h_noexport'], 1112 );
		}
		
		$hookData = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_hooks', 'where' => 'hook_id=' . $id ) );
		
		if( !$hookData['hook_id'] )
		{
			$this->registry->output->showError( $this->lang->words['h_noexport'], 1113 );
		}
		
		/* Set defaults */
		$hookData['hook_extra_data'] = unserialize( $hookData['hook_extra_data'] );

		/* Output */
		$this->registry->output->html .= $this->html->hooksExport( $hookData );
	}
	
	/**
	 * Actually export the damn hook already
	 * Sorry, it has been a long day...
	 *
	 * @return	@e void
	 */
	protected function _doExportHook()
	{
		//-----------------------------------------
		// Get hook
		//-----------------------------------------
		
		$id	= intval($this->request['id']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['h_noexport'], 1114 );
		}
		
		$hookData	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_hooks', 'where' => 'hook_id=' . $id ) );
		
		if( !$hookData['hook_id'] )
		{
			$this->registry->output->showError( $this->lang->words['h_noexport'], 1115 );
		}
		
		$extra_data	= unserialize( $hookData['hook_extra_data'] );
		
		//-----------------------------------------
		// Get hook files
		//-----------------------------------------
		
		$files = array();
		$index = 1;
		
		$this->DB->build( array( 'select' => '*', 'from' => 'core_hooks_files', 'where' => 'hook_hook_id=' . $id, 'order' => 'hook_file_id ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$files[ $index ]	= $r;
			$index++;
		}

		//-----------------------------------------
		// Get XML class
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );/*noLibHook*/

		$xml = new classXML( IPS_DOC_CHAR_SET );
		
		$xml->newXMLDocument();
		$xml->addElement( 'hookexport' );

		//-----------------------------------------
		// Put hook data in export
		//-----------------------------------------
		
		$xml->addElement( 'hookdata', 'hookexport' );
		$content	= array();
		
		foreach( $hookData as $k => $v )
		{
			if( in_array( $k, array( 'hook_id', 'hook_enabled', 'hook_installed', 'hook_updated', 'hook_position' ) ) )
			{
				continue;
			}
			
			$content[ $k ] = $v;
		}
		
		$xml->addElementAsRecord( 'hookdata', 'config', $content );
		
		//-----------------------------------------
		// Put hook files in export
		//-----------------------------------------
		
		$xml->addElement( 'hookfiles', 'hookexport' );

		foreach( $files as $index => $r )
		{
			$content	= array();
			
			foreach( $r as $k => $v )
			{
				if( in_array( $k, array( 'hook_file_id', 'hook_hook_id', 'hook_file_stored', 'hooks_source' ) ) )
				{
					continue;
				}
				
				$content[ $k ] = $v;
			}
			
			$source	= is_file( IPS_HOOKS_PATH . $r['hook_file_stored'] ) ? file_get_contents( IPS_HOOKS_PATH . $r['hook_file_stored'] ) : '';
			
			if ( !empty($source) && in_array( $r['hook_type'], array( 'skinHooks', 'commandHooks', 'libraryHooks' ) ) )
			{
				$source	= $this->_cleanSource( $source );
			}

			$content['hooks_source'] = $source;

			$xml->addElementAsRecord( 'hookfiles', 'file', $content );
		}

		//-----------------------------------------
		// Custom install/uninstall script?
		//-----------------------------------------
		
		if( $extra_data['custom'] )
		{
			$content	= array();
			$xml->addElement( 'hookextras_custom', 'hookexport' );
		
			$content['filename']	= $extra_data['custom'];
			$content['source']		= is_file( IPS_HOOKS_PATH . 'install_' . $extra_data['custom'] ) ? file_get_contents( IPS_HOOKS_PATH . 'install_' . $extra_data['custom'] ) : '';
			
			$xml->addElementAsRecord( 'hookextras_custom', 'file', $content );
		}
		
		//-----------------------------------------
		// Settings or setting groups?
		//-----------------------------------------
		
		$entry		= array();
		$_groups	= array();
		$_settings	= array();
		$titles		= array();
		$content	= array();
		
		$xml->addElement( 'hookextras_settings', 'hookexport' );
		
		# Store group ids and setting ids for entire setting groups
		if( is_array($extra_data['settingGroups']) AND count($extra_data['settingGroups']) )
		{
			$_groups	= $extra_data['settingGroups'];
			$_groupIds	= array();
			
			$this->DB->build( array( 'select' => 'conf_title_id', 'from' => 'core_sys_settings_titles', 'where' => "conf_title_keyword IN('" . implode( "','", $_groups ) . "')" ) );
			$this->DB->execute();
			
			while( $rr = $this->DB->fetch() )
			{
				$_groupIds[] = $rr['conf_title_id'];
			}
			
			if( count($_groupIds) )
			{
				$this->DB->build( array( 'select' => 'conf_key', 'from' => 'core_sys_conf_settings', 'where' => 'conf_group IN(' . implode( ',', $_groupIds ) . ')' ) );
				$this->DB->execute();
				
				while( $setting = $this->DB->fetch() )
				{
					$_settings[] = $setting['conf_key'];
				}
			}
		}
		
		# Store group ids and setting ids for indvidual settings
		if( is_array($extra_data['settings']) AND count($extra_data['settings']) )
		{
			foreach( $extra_data['settings'] as $_aSetting )
			{
				$_settings[] = $_aSetting;
			}
			
			$this->DB->build( array( 'select'	=> 't.conf_title_keyword', 
									 'from'		=> array( 'core_sys_settings_titles' => 't' ), 
									 'where'	=> "c.conf_key IN('" . implode( "','", $extra_data['settings'] ) . "')",
									 'add_join'	=> array(
											 			array(
											 				'from'	=> array( 'core_sys_conf_settings' => 'c' ),
											 				'where'	=> 'c.conf_group=t.conf_title_id',
											 				'type'	=> 'left',
											 				)
									 					)
							)		);
			$this->DB->execute();
			
			while( $group = $this->DB->fetch() )
			{
				$_groups[] = $group['conf_title_keyword'];
			}
		}
		
		if( count($_groups) )
		{
			# Now get the group data for the XML file
			$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_settings_titles', 'where' => "conf_title_keyword IN('" . implode( "','", $_groups ) . "')" ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$content	= array();
				
				$titles[ $r['conf_title_id'] ] = $r['conf_title_keyword'];
				
				$content['conf_is_title']	= 1;
				
				foreach( $r as $k => $v )
				{
					if( in_array( $k, array( 'conf_title_tab', 'conf_title_keyword', 'conf_title_title', 'conf_title_desc', 'conf_title_app', 'conf_title_noshow' ) ) )
					{
						$content[ $k ] = $v;
					}
				}

				$xml->addElementAsRecord( 'hookextras_settings', 'setting', $content );
			}
		}
		
		if( count($_settings) )
		{
			# Now get the group data for the XML file
			$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_conf_settings', 'where' => "conf_key IN('" . implode( "','", $_settings ) . "')" ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$r['conf_value']			= '';
				$r['conf_title_keyword']	= $titles[ $r['conf_group'] ];
				$r['conf_is_title']			= 0;

				$xml->addElementAsRecord( 'hookextras_settings', 'setting', $r );
			}
		}

		//-----------------------------------------
		// Language strings/files
		//-----------------------------------------
		
		$entry		= array();
		
		$xml->addElement( 'hookextras_language', 'hookexport' );
		
		if( is_array($extra_data['language']) AND count($extra_data['language']) )
		{
			foreach( $extra_data['language'] as $file => $strings )
			{
				$bits = explode( '_', $file );
				$app  = $bits[0];
				$pack = str_replace( $app.'_', '', $file );
				
				$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_lang_words', 'where' => "word_app='{$app}' AND word_pack='{$pack}' AND word_key IN('" . implode( "','", $strings ) . "')" ) );
				$this->DB->execute();
				
				while( $r = $this->DB->fetch() )
				{
					$content	= array();
					
					foreach( $r as $k => $v )
					{
						if( !in_array( $k, array( 'word_app', 'word_pack', 'word_key', 'word_default' ) ) )
						{
							continue;
						}
						
						$content[ $k ] = $v;
					}
	
					$xml->addElementAsRecord( 'hookextras_language', 'language', $content );
				}
			}
		}

		//-----------------------------------------
		// Modules
		//-----------------------------------------

		$xml->addElement( 'hookextras_modules', 'hookexport' );
		
		if( is_array($extra_data['modules']) AND count($extra_data['modules']) )
		{
			foreach ( $extra_data['modules'] as $module )
			{
				list( $_side, $_app, $_module ) = explode( '-', $module );
				$_is_admin = ( $_side == 'admin' ) ? 1 : 0;
				
				$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_module', 'where' => "sys_module_application='{$_app}' AND sys_module_key='{$_module}' AND sys_module_admin={$_is_admin}" ) );
				$this->DB->execute();
				
				while( $r = $this->DB->fetch() )
				{
					unset($r['sys_module_id']);
					
					$xml->addElementAsRecord( 'hookextras_modules', 'module', $r );
				}
			}
		}
		
		//-----------------------------------------
		// Help files
		//-----------------------------------------

		$xml->addElement( 'hookextras_help', 'hookexport' );
		
		if( is_array($extra_data['help']) AND count($extra_data['help']) )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'faq', 'where' => "title IN('" . implode( "','", $extra_data['help'] ) . "')" ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				unset($r['id']);

				$xml->addElementAsRecord( 'hookextras_help', 'help', $r );
			}
		}
		
		//-----------------------------------------
		// Skin templates
		//-----------------------------------------

		$remapData	= $this->registry->output->buildRemapData( true );

		$xml->addElement( 'hookextras_templates', 'hookexport' );
				
		if( is_array($extra_data['templates']) AND count($extra_data['templates']) )
		{
			foreach( $extra_data['templates'] as $file => $templates )
			{
				$this->DB->build( array( 'select' => '*', 'from' => 'skin_templates', 'where' => "template_set_id IN ('" . implode( "','", $remapData['export'] ) . "') AND template_master_key != '' AND template_group='{$file}' AND template_name IN('" . implode( "','", $templates ) . "')" ) );
				$this->DB->execute();
				
				while( $r = $this->DB->fetch() )
				{
					unset($r['template_id']);
					unset($r['template_set_id']);
	
					$xml->addElementAsRecord( 'hookextras_templates', 'templates', $r );
				}
			}
		}
		
		//-----------------------------------------
		// CSS
		//-----------------------------------------

		$xml->addElement( 'hookextras_css', 'hookexport' );
				
		if( is_array( $extra_data['css'] ) AND count ( $extra_data['css'] ) )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'skin_css', 'where' => "css_set_id IN ('" . implode( "','", $remapData['export'] ) . "') AND css_master_key != '' AND css_group IN('" . implode( "','", $extra_data['css'] ) . "')" ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				unset($r['css_id']);
				unset($r['css_set_id']);

				$xml->addElementAsRecord( 'hookextras_css', 'css', $r );
			}
		}
		
		//-----------------------------------------
		// Replacements
		//-----------------------------------------

		$xml->addElement( 'hookextras_replacements', 'hookexport' );
				
		if( is_array( $extra_data['replacements'] ) AND count ( $extra_data['replacements'] ) )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'skin_replacements', 'where' => "replacement_key IN('" . implode( "','", $extra_data['replacements'] ) . "')", 'group' => 'replacement_key' ) );
			
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				unset($r['replacement_id']);
				unset($r['replacement_set_id']);

				$xml->addElementAsRecord( 'hookextras_replacements', 'replacements', $r );
			}
		}

		//-----------------------------------------
		// Tasks
		//-----------------------------------------

		$xml->addElement( 'hookextras_tasks', 'hookexport' );
				
		if( is_array($extra_data['tasks']) AND count($extra_data['tasks']) )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'task_manager', 'where' => "task_key IN('" . implode( "','", $extra_data['tasks'] ) . "')" ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				unset($r['task_id']);
				unset($r['task_next_run']);

				$xml->addElementAsRecord( 'hookextras_tasks', 'tasks', $r );
			}
		}

		//-----------------------------------------
		// Database changes
		//-----------------------------------------

		$xml->addElement( 'hookextras_database_create', 'hookexport' );
		
		if( is_array($extra_data['database']['create']) AND count($extra_data['database']['create']) )
		{
			foreach( $extra_data['database']['create'] as $create_query )
			{
				$xml->addElementAsRecord( 'hookextras_database_create', 'create', $create_query );
			}
		}

		$xml->addElement( 'hookextras_database_alter', 'hookexport' );
		
		if( is_array($extra_data['database']['alter']) AND count($extra_data['database']['alter']) )
		{
			foreach( $extra_data['database']['alter'] as $alter_query )
			{
				$xml->addElementAsRecord( 'hookextras_database_alter', 'alter', $alter_query );
			}
		}

		$xml->addElement( 'hookextras_database_update', 'hookexport' );
		
		if( is_array($extra_data['database']['update']) AND count($extra_data['database']['update']) )
		{
			foreach( $extra_data['database']['update'] as $update_query )
			{
				$xml->addElementAsRecord( 'hookextras_database_update', 'update', $update_query );
			}
		}

		$xml->addElement( 'hookextras_database_insert', 'hookexport' );
		
		if( is_array($extra_data['database']['insert']) AND count($extra_data['database']['insert']) )
		{
			foreach( $extra_data['database']['insert'] as $insert_query )
			{
				$xml->addElementAsRecord( 'hookextras_database_insert', 'insert', $insert_query );
			}
		}

		//-----------------------------------------
		// Print to browser
		//-----------------------------------------

		$this->registry->output->showDownload( $xml->fetchDocument(), $hookData['hook_key'] . '.xml', '', 0 );
	}
	
	/**
	 * Clean source code for export..
	 *
	 * @param	string			Hook source code
	 * @return	string			"Cleaned" source code
	 */
	protected function _cleanSource( $source )
	{
		$source = preg_replace( "/class\s+(\S+)\s+extends\s+(\S+)/i", "class \\1 extends (~extends~)", $source );
		
		return $source;
	}
	
	/**
	 * Fix hook positions
	 *
	 * @return   @e void
	 */
	protected function _fixPositions()
	{
		/* Init vars */
		$new_order   = 0;
		$usedActions = array();

		$this->DB->build( array( 'select' => 'hook_id, hook_position', 'from' => 'core_hooks', 'where' => 'hook_enabled=1', 'order' => 'hook_position ASC' ) );
		$qid = $this->DB->execute();

		while ( $hook = $this->DB->fetch( $qid ) )
		{
			$new_order++;
			$this->DB->update( 'core_hooks', array ( 'hook_position' => $new_order ), "hook_id={$hook['hook_id']}" );
			
			$this->DB->build( array( 'select' => '*', 'from' => 'core_hooks_files', 'where' => "hook_type IN ('commandHooks', 'skinHooks', 'libraryHooks' ) AND hook_hook_id=" . $hook['hook_id'] ) );
			$this->DB->execute();
		
			while( $file = $this->DB->fetch() )
			{
				if( $file['hooks_source'] )
				{
					$source = $this->_cleanSource( $file['hooks_source'] );
				}
				else
				{
					$source = is_file( IPS_HOOKS_PATH . $file['hook_file_stored'] ) ? file_get_contents( IPS_HOOKS_PATH . $file['hook_file_stored'] ) : '';
					
					if( $source )
					{
						 $source = $this->_cleanSource( $source );
					}
				}
				
				if( $source )
				{
					$hook_data	= unserialize( $file['hook_data'] );
					$overload	= $hook_data['classToOverload'];
					$newClass	= $overload;
					
					$app = ( $file['hook_type'] == 'libraryHooks' ) ? trim($hook_data['libApplication']) : '~_NO_APP_~';
					
					if( isset( $usedActions[ $app ][ $overload ] ) )
					{
						$newClass = $usedActions[ $app ][ $overload ];
					}
					else if( $file['hook_type'] == 'skinHooks' )
					{
						$newClass .= "(~id~)";
					}
					
					$source = str_replace( "(~extends~)", $newClass, $source );
					
					file_put_contents( IPS_HOOKS_PATH . $file['hook_file_stored'], $source );
					
					$usedActions[ $app ][ $hook_data['classToOverload'] ] = $file['hook_classname'];
				}
			}
		}
	}
	
	/**
	 * Check if there is an update for a hook
	 *
	 * @param	string 		URL to check
	 * @param	string		Long version number
	 * @return	bool
	 */
	public function _updateAvailable( $url, $version )
	{
		if( empty($url) || $version === '' )
		{
			return false;
		}
		
		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . '/classFileManagement.php', 'classFileManagement' );
		$checker = new $classToLoad();
		
		/* Timeout to prevent page from taking too long */
		$checker->timeout = 5;
		
		/* Setup url and check */
		$url  = str_replace( 'php&', 'php?', $url );
		$url .= '&boardVersion=' . IPB_LONG_VERSION . '&version=' . $version;
		
		$return = $checker->getFileContents( $url );
		
		/* Return the content (if valid) or return 'no update' */
		return ( strpos( $return, '0' ) === 0 || strpos( $return, '1' ) === 0 ) ? explode( '|', $return ) : array( 0 );
	}
	
	/**
	 * Sorts hooks by their position
	 * (this is used to avoid a mysql filesort)
	 * 
	 * @param	array		$a		First hook data
	 * @param	array		$b		Second hook data
	 * @return	@e integer
	 */
	protected function sortHooksByPosition( $a, $b )
	{
		if ( $a['hook_position'] == $b['hook_position'] )
		{
			return 0;
		}
		
		return ( $a['hook_position'] < $b['hook_position'] ) ? -1 : 1;
	}
	
	/**
	 * Hooks overview
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	public function _hooksOverview()
	{
		/* INI */
		$hooksFound			= array( 'installed' => array(), 'uninstalled' => array() );
		$hookwarnings		= 0;
		$hookUpdates		= 0;
		$checkUpdates		= !empty( $this->request['checkUpdates'] ) ? true : false;
		$message			= '';
		$confGroupIdsToLoad	= array();
		$confGroupKeysToLoad= array();
		
		/* Get current hooks */
		$this->DB->build( array( 'select' => '*', 'from' => 'core_hooks' ) );
		$outer	= $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			/* Format some dates */
			$r['_updated']   = $this->registry->getClass('class_localization')->formatTime( $r['hook_updated'] );
			$r['_installed'] = $this->registry->getClass('class_localization')->formatTime( $r['hook_installed'] );
			
			/* Got updates? */
			if ( $r['hook_update_check'] && $checkUpdates === true )
			{
				$r['hook_update_available'] = $this->_updateAvailable( $r['hook_update_check'], $r['hook_version_long'] );
				
				if ( $r['hook_update_available'][0] )
				{
					$hookUpdates++;
				}
			}
			else
			{
				$r['hook_update_available']	= array( 0 );
			}
			
			/* Got some setting groups to take care of? */
			$r['_has_setting_links'] = null;
			$r['_hook_extra_data']   = IPSLib::isSerialized($r['hook_extra_data']) ? unserialize($r['hook_extra_data']) : array();
			
			if ( is_array($r['_hook_extra_data']['settingGroups']) && count($r['_hook_extra_data']['settingGroups']) )
			{
				$r['_has_setting_links'] = array();
				
				foreach( $r['_hook_extra_data']['settingGroups'] as $_group )
				{
					$confGroupKeysToLoad[ $_group ] = $_group;
				}
			}
			
			if ( is_array($r['_hook_extra_data']['settings']) && count($r['_hook_extra_data']['settings']) )
			{
				$r['_has_setting_links'] = array();
				
				$this->DB->build( array( 'select' => 'conf_group, conf_key', 'from' => 'core_sys_conf_settings', 'where' => "conf_key IN ('" . implode( "','", $r['_hook_extra_data']['settings'] ) . "')" ) );
				$this->DB->execute();
				
				while( $hsg = $this->DB->fetch() )
				{
					$confGroupIdsToLoad[ $hsg['conf_key'] ] = $hsg['conf_group'];
				}
			}
			
			/* Check requirements */
			$r['_require_errors'] = $this->checkHookRequirements( $r );
			
			/* Store */
			if( $r['hook_enabled'] )
			{
				if ( count($r['_require_errors']) )
				{
					$hookwarnings++;
				}
				
				$hooksFound['installed'][ $r['hook_id'] ]	= $r;
			}
			else
			{
				$hooksFound['uninstalled'][ $r['hook_id'] ]	= $r;
			}
		}
		
		/* Got groups to load? */
		$where = array();
		
		if ( count($confGroupKeysToLoad) )
		{
			$where[] = "conf_title_keyword IN ('" . implode( "','", $confGroupKeysToLoad ) . "')";
		}
		
		if ( count($confGroupIdsToLoad) )
		{
			$where[] = "conf_title_id IN (" . implode( ",", $confGroupIdsToLoad ) . ")";
		}
		
		if ( count($where) )
		{
			$confGroupsLoaded = array();
			
			$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_settings_titles', 'where' => implode( ' OR ', $where ) ) );
			$this->DB->execute();
			
			while( $gr = $this->DB->fetch() )
			{
				if ( empty($gr['conf_title_noshow']) )
				{
					$confGroupsLoaded['ids'][ $gr['conf_title_id'] ]		= $gr;
					$confGroupsLoaded['keys'][ $gr['conf_title_keyword'] ]	= & $confGroupsLoaded['ids'][ $gr['conf_title_id'] ];
				}
			}
			
			/* Merge back the data */
			foreach( $hooksFound as $rk => $rt )
			{
				foreach( $rt as $sk => $st )
				{
					if ( is_array($st['_has_setting_links']) )
					{
						if ( is_array($st['_hook_extra_data']['settingGroups']) && count($st['_hook_extra_data']['settingGroups']) )
						{
							foreach( $st['_hook_extra_data']['settingGroups'] as $_group )
							{
								if ( isset($confGroupsLoaded['keys'][ $_group ]) )
								{
									$hooksFound[ $rk ][ $sk ]['_has_setting_links'][ $confGroupsLoaded['keys'][ $_group ]['conf_title_id'] ] = $confGroupsLoaded['keys'][ $_group ]['conf_title_title'];
								}
							}
						}
						
						if ( is_array($st['_hook_extra_data']['settings']) && count($st['_hook_extra_data']['settings']) )
						{
							foreach( $st['_hook_extra_data']['settings'] as $_id )
							{
								if ( isset($confGroupsLoaded['ids'][ $confGroupIdsToLoad[ $_id ] ]) )
								{
									$hooksFound[ $rk ][ $sk ]['_has_setting_links'][ $confGroupIdsToLoad[ $_id ] ] = $confGroupsLoaded['ids'][ $confGroupIdsToLoad[ $_id ] ]['conf_title_title'];
								}
							}
						}
					}
				}
			}
		}
		
		/* Got updates to show? */
		if ( $checkUpdates === true )
		{
			$message = ( $hookUpdates == 1 ) ? $this->lang->words['updates_string_single'] : sprintf( $this->lang->words['updates_string_more'], $hookUpdates );
		}
		
		if ( count($hooksFound['installed']) )
		{
			uasort( $hooksFound['installed'], array( $this, 'sortHooksByPosition' ) );
		}
		
		/* Output */
		$this->registry->output->html .= $this->html->hooksOverview( $hooksFound, $hookwarnings, $message );
	}
	
	/**
	 * Disables all hook
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _disableAllHooks()
	{
		/* INIT */
		$activeHookIds = array();
		
		/* Query Enabled Hooks */
		$this->DB->build( array( 'select' => 'hook_id', 'from' => 'core_hooks', 'where' => "hook_enabled=1" ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$activeHookIds[] = $r['hook_id'];
		}
		
		/* Make sure there were active hooks */
		if( ! count( $activeHookIds ) )
		{
			$this->registry->output->global_message = $this->lang->words['hook_disable_all_none'];
			$this->_hooksOverview();
			return;
		}
		
		/* Disable hooks */
		$this->DB->update( 'core_hooks', array( 'hook_enabled' => 0 ), 'hook_id IN( ' . implode( ',', $activeHookIds ) . ' ) ' );
		
		/* Save to cache */
		$this->cache->setCache( 'disabledHooksCache', $activeHookIds, array( 'array' => 1 ) );
		
		/* Rebuild cache */
		$this->rebuildHooksCache();
		
		/* All Done */
		$this->registry->output->global_message = $this->lang->words['hook_disabled_all'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=hooks_overview' );
	}
	
	/**
	 * Re-enable all hook
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _reenableAllHooks()
	{
		/* INIT */
		$activeHookIds = $this->cache->getCache( 'disabledHooksCache' );
		
		/* Make sure there were active hooks */
		if( ! count( $activeHookIds ) )
		{
			$this->registry->output->global_message = $this->lang->words['hook_enable_all_none'];
			$this->_hooksOverview();
			return;
		}
		
		/* Disable hooks */
		$this->DB->update( 'core_hooks', array( 'hook_enabled' => 1 ), 'hook_id IN( ' . implode( ',', $activeHookIds ) . ' ) ' );
		
		/* Save to cache */
		$this->cache->setCache( 'disabledHooksCache', array(), array( 'array' => 1 ) );
		
		/* Rebuild cache */
		$this->rebuildHooksCache();
		
		/* Flag skins for recache */
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );/*noLibHook*/
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );/*noLibHook*/
		$skinCaching = new skinCaching( $this->registry );
		
		/* Flag all for recache */
		$skinCaching->flagSetForRecache();
		
		/* All Done */
		$this->registry->output->global_message = $this->lang->words['hook_reenable_all'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=hooks_overview' );
	}
	
	/**
	 * Disables a hook
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _disableHook()
	{
		/* INI */
		$hook_id = intval( $this->request['id'] );
		
		if( !$hook_id )
		{
			$this->registry->output->showError( $this->lang->words['h_noedit'], '1118.D' );
		}
		
		$hookData = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_hooks', 'where' => 'hook_id=' . $hook_id ) );
		
		if( !$hookData['hook_id'] )
		{
			$this->registry->output->showError( $this->lang->words['h_noedit'], '1119.D' );
		}
		
		/* Do update */
		$this->DB->update( 'core_hooks', array( 'hook_enabled' => 0, 'hook_position' => 0 ), "hook_id={$hookData['hook_id']}" );
		
		/* Rebuild cache */
		$this->rebuildHooksCache();
		
		/* Rebuild global caches */
		try
		{
			IPSLib::cacheGlobalCaches();
		}
		catch( Exception $e ){}
		
		/* Flag skins for recache */
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );/*noLibHook*/
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );/*noLibHook*/
		$skinCaching = new skinCaching( $this->registry );
		$skinCaching->flagSetForRecache();
		
		/* Redirect */
		$this->registry->output->setMessage( $this->lang->words['h_hasbeendisabled'] );
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=hooks_overview' );
	}	
	
	/**
	 * Enables a hook
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _enableHook()
	{
		/* INI */
		$hook_id = intval($this->request['id']);
		
		if( !$hook_id )
		{
			$this->registry->output->showError( $this->lang->words['h_noedit'], '1118.E' );
		}
		
		$hookData = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_hooks', 'where' => 'hook_id=' . $hook_id ) );
		
		if( !$hookData['hook_id'] )
		{
			$this->registry->output->showError( $this->lang->words['h_noedit'], '1119.E' );
		}
		
		/* Check for requirements */
		if ( empty($this->request['skipRequirements']) )
		{
			$errors = $this->checkHookRequirements( $hookData );
			
			if ( count($errors) )
			{
				$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=check_requirements&amp;fromInstall=1&amp;id='.$hook_id );
			}
		}
		
		/* Do update */
		$this->DB->update( 'core_hooks', array( 'hook_enabled' => 1 ), "hook_id={$hookData['hook_id']}" );
		
		/* Rebuild cache */
		$this->rebuildHooksCache();
		
		/* Rebuild global caches */
		try
		{
			IPSLib::cacheGlobalCaches();
		}
		catch( Exception $e ){}
		
		/* Flag skins for recache */
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );/*noLibHook*/
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );/*noLibHook*/
		$skinCaching = new skinCaching( $this->registry );
		$skinCaching->flagSetForRecache();
		
		/* Redirect */
		$this->registry->output->global_message = $this->lang->words['h_hasbeenenabled'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=hooks_overview' );
	}
	
	/**
	 * Uninstall a hook
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _uninstallHook()
	{
		/* Init vars */
		$hook_id	= intval( $this->request['id'] );
		
		$hook		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_hooks', 'where' => 'hook_id=' . $hook_id ) );
		$extra_data	= unserialize( $hook['hook_extra_data'] );
		
		if ( ! $hook['hook_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['h_hasbeenun'];
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=hooks_overview' );
		}
		
		/* Got any custom file to initialize? */
		$_customFile = '';
		$customClass = null;
		
		if ( $extra_data['display']['custom'] )
		{
			$_customFile = IPS_HOOKS_PATH . 'install_' . $extra_data['display']['custom'];
			
			if( is_file( $_customFile ) )
			{
				require_once( $_customFile );/*noLibHook*/
				
				$classname = str_replace( '.php', '', $extra_data['display']['custom'] );
				
				if( class_exists( $classname ) )
				{
					$customClass = new $classname( $this->registry );
					
					if( method_exists( $customClass, 'pre_uninstall' ) )
					{
						$customClass->pre_uninstall();
					}
				}
			}
		}
		
		/* Get associated files */
		$this->DB->build( array( 'select' => 'hook_file_stored', 'from' => 'core_hooks_files', 'where' => 'hook_hook_id=' . $hook_id ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			@unlink( IPS_HOOKS_PATH . $r['hook_file_stored'] );
		}
		
		/* Delete hook file entries */
		$this->DB->delete( 'core_hooks_files', "hook_hook_id={$hook_id}" );
		
		//-----------------------------------------
		// Settings or setting groups?
		//-----------------------------------------
		
		if( count($extra_data['settingGroups']) )
		{
			$this->DB->delete( 'core_sys_settings_titles', "conf_title_keyword IN('" . implode( "','", $extra_data['settingGroups'] ) . "')" );
		}
		
		if( count($extra_data['settings']) )
		{
			$this->DB->delete( 'core_sys_conf_settings', "conf_key IN('" . implode( "','", $extra_data['settings'] ) . "')" );
		}
		
		$this->cache->rebuildCache( 'settings', 'global' );
		
		//-----------------------------------------
		// Language strings/files
		//-----------------------------------------
		
		if( count($extra_data['language']) )
		{
			foreach( $extra_data['language'] as $file => $bit )
			{
				$this->DB->delete( 'core_sys_lang_words', "word_pack='{$file}' AND word_key IN('" . implode( "','", $bit ) . "')" );
			}
			
			$langPacks	= array();
			
			$this->DB->build( array( 'select' => 'lang_id', 'from' => 'core_sys_lang' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$langPacks[] = $r['lang_id'];
			}
			
			$classToLoad = IPSLib::loadActionOverloader( IPSLib::getAppDir('core') . '/modules_admin/languages/manage_languages.php', 'admin_core_languages_manage_languages' );
			$langLib     = new $classToLoad();
			$langLib->makeRegistryShortcuts( $this->registry );
			
			foreach( $langPacks as $langId )
			{
				$langLib->cacheToDisk( $langId );
			}
		}
		
		//-----------------------------------------
		// Modules
		//-----------------------------------------
		
		if( count($extra_data['modules']) )
		{
			foreach( $extra_data['modules'] as $module )
			{
				list( $_side, $_app, $_module ) = explode( '-', $module );
				$_is_admin = ( $_side == 'admin' ) ? 1 : 0;
				
				$this->DB->delete( 'core_sys_module', "sys_module_application='{$_app}' AND sys_module_key='{$_module}' AND sys_module_admin={$_is_admin}" );
			}
			
			$this->cache->rebuildCache( 'module_cache', 'global' );
			$this->cache->rebuildCache( 'app_menu_cache', 'global' );
		}
		
		//-----------------------------------------
		// Help files
		//-----------------------------------------
		
		if( count($extra_data['help']) )
		{
			$this->DB->delete( 'faq', "title IN('" . implode( "','", $extra_data['help'] ) . "')" );
		}
		
		//-----------------------------------------
		// Skin templates
		//-----------------------------------------
		
		if( count($extra_data['templates']) )
		{
			foreach( $extra_data['templates'] as $group => $bits )
			{
				$this->DB->delete( 'skin_templates', "template_group='{$group}' AND template_name IN('" . implode( "','", $bits ) . "')" );
			}

			require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );/*noLibHook*/
			require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );/*noLibHook*/
			$skinCaching = new skinCaching( $this->registry );
			$skinCaching->rebuildPHPTemplates( 0 );
		}
		
		//-----------------------------------------
		// CSS
		//-----------------------------------------
		
		if( count( $extra_data['css'] ) )
		{
			$this->DB->delete( 'skin_css', "css_group IN('" . implode( "','", $extra_data['css'] ) . "')" );
			
			if( ! $skinCaching )
			{
				require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );/*noLibHook*/
				require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );/*noLibHook*/
				$skinCaching = new skinCaching( $this->registry );
			}
			
			$skinCaching->rebuildCSSCache( 0 );
		}
		
		//-----------------------------------------
		// Tasks
		//-----------------------------------------
		
		if( count($extra_data['tasks']) )
		{
			$this->DB->delete( 'task_manager', "task_key IN('" . implode( "','", $extra_data['tasks'] ) . "')" );
		}
		
		//-----------------------------------------
		// Database changes
		//-----------------------------------------
		
		if( is_array($extra_data['database']['create']) AND count($extra_data['database']['create']) )
		{
			foreach( $extra_data['database']['create'] as $create_query )
			{
				$this->DB->return_die	= true;
				$this->DB->dropTable( $create_query['name'] );
				$this->DB->return_die	= false;
			}
		}
		
		if( is_array($extra_data['database']['alter']) AND count($extra_data['database']['alter']) )
		{
			foreach( $extra_data['database']['alter'] as $alter_query )
			{
				$this->DB->return_die	= true;
				
				if( $alter_query['altertype'] == 'add' )
				{
					if( $this->DB->checkForField( $alter_query['field'], $alter_query['table'] ) )
					{
						$this->DB->dropField( $alter_query['table'], $alter_query['field'] );
					}
				}
				else if( $alter_query['altertype'] == 'change' )
				{
					if( $this->DB->checkForField( $alter_query['newfield'], $alter_query['table'] ) )
					{
						$this->DB->changeField( $alter_query['table'] , $alter_query['newfield'], $alter_query['field'], $alter_query['fieldtype'], $alter_query['default'] );
					}
				}
				
				$this->DB->return_die	= false;
			}
		}
		
		if( is_array($extra_data['database']['update']) AND count($extra_data['database']['update']) )
		{
			foreach( $extra_data['database']['update'] as $update_query )
			{
				$this->DB->return_die	= true;
				$this->DB->update( $update_query['table'], array( $update_query['field'] => $update_query['oldvalue'] ), $update_query['where'] );
				$this->DB->return_die	= false;
			}
		}
		
		if( is_array($extra_data['database']['insert']) AND count($extra_data['database']['insert']) )
		{
			foreach( $extra_data['database']['insert'] as $insert_query )
			{
				if( $insert_query['fordelete'] )
				{
					$this->DB->return_die	= true;
					$this->DB->delete( $insert_query['table'], $insert_query['fordelete'] );
					$this->DB->return_die	= false;
				}
			}
		}
		
		//-----------------------------------------
		// Custom install/uninstall script?
		//-----------------------------------------
		
		if ( $_customFile )
		{
			if( is_object($customClass) && method_exists( $customClass, 'uninstall' ) )
			{
				$customClass->uninstall();
			}
			
			@unlink( $_customFile );
		}
		
		/* Delete main hook entry */
		$this->DB->delete( 'core_hooks', "hook_id={$hook_id}" );
		
		$this->rebuildHooksCache();
		
		/* Done */
		$this->registry->output->global_message = $this->lang->words['h_hasbeenun'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=hooks_overview' );
	}	
	
	/**
	 * Hook add/edit form
	 * This dynamic form allows users to associate multiple files with a single hook.
	 *
	 * @param	string		[add|edit]
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _hookForm( $type='add' )
	{
		/* Init vars */
		$form			= array();
		$files			= array();
		$hookData		= array();
		$requirements	= array();
		$entryPoints	= array( 'foreach' => array( array( 'outer.pre', $this->lang->words['h_outerpre'] ),
													 array( 'inner.pre', $this->lang->words['h_innerpre'] ),
													 array( 'inner.post', $this->lang->words['h_innerpost'] ),
													 array( 'outer.post', $this->lang->words['h_outerpost'] )
													),
								 'if' => array( array( 'pre.startif', $this->lang->words['h_prestartif'] ),
												array( 'post.startif', $this->lang->words['h_poststartif'] ),
												array( 'pre.else', $this->lang->words['h_preelse']),
												array( 'post.else', $this->lang->words['h_postelse'] ),
												array( 'pre.endif', $this->lang->words['h_preendif'] ),
												array( 'post.endif', $this->lang->words['h_postendif'] )
												)
								);
		
		/* Edit time? */
		if( $type == 'edit' )
		{
			$id	= intval($this->request['id']);
			
			if( !$id )
			{
				$this->registry->output->showError( $this->lang->words['h_noedit'], 1116 );
			}
			
			$hookData = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_hooks', 'where' => 'hook_id=' . $id ) );
			
			if( !$hookData['hook_id'] )
			{
				$this->registry->output->showError( $this->lang->words['h_noedit'], 1117 );
			}
			
			/* Sort out extra stuff and requirements */
			$hookData['hook_extra_data']	= unserialize( $hookData['hook_extra_data'] );
			$hookData['hook_requirements']	= unserialize( $hookData['hook_requirements'] );
			
			/* Old data? - @todo: we should remove that around 3.3(4?) */
			if ( ! isset($hookData['hook_requirements']['required_applications']['core']) && isset($hookData['hook_requirements']['hook_ipb_version_min']) && ( $hookData['hook_requirements']['hook_ipb_version_min'] > 0 || $hookData['hook_requirements']['hook_ipb_version_max'] > 0 ) )
			{
				$requirements['core'] = array( 'min_version' => intval($hookData['hook_requirements']['hook_ipb_version_min']), 'max_version' => intval($hookData['hook_requirements']['hook_ipb_version_max']) );
			}
			
			if ( is_array($hookData['hook_requirements']['required_applications']) && count($hookData['hook_requirements']['required_applications']) )
			{
				/* Get the setup class */
				require_once( IPS_ROOT_PATH . 'setup/sources/base/setup.php' );/*noLibHook*/
				
				foreach( $hookData['hook_requirements']['required_applications'] as $appKey => $versionData )
				{
					/* Fetch and check versions */
					if ( !isset($this->cachedVersions[ $appKey ]) )
					{
						$this->cachedVersions[ $appKey ] = IPSSetUp::fetchXmlAppVersions( $appKey );
					}
					
					$_versions = $this->cachedVersions[ $appKey ];
					
					krsort($_versions);
					
					/* Setup our default 'no version' value */
					$versions = array( array( 0, $this->lang->words['h_any_version'] ) );
					
					foreach( $_versions as $long => $human )
					{
						if ( $long < 30000 && in_array( $appKey, array( 'core', 'forums', 'members' ) ) )
						{
							continue;
						}
						
						$versions[] = array( $long, $human );
					}
					
					$versionData['_versions'] = $versions;
					$requirements[ $appKey ]  = $versionData;
				}
			}
			
			/* Sort out hook files */
			$index		= 1;
			$skinGroups = $this->hooksFunctions->getSkinGroups();
			
			$this->DB->build( array( 'select' => '*', 'from' => 'core_hooks_files', 'where' => 'hook_hook_id=' . $id ) );
			$outer = $this->DB->execute();
			
			/* Get them outside the while cycle to prevent warnings */
			$_dataHooks = IPSLib::getDataHookLocations();
			
			while( $r = $this->DB->fetch($outer) )
			{
				$r['hook_data']		= unserialize( $r['hook_data'] );

				if( $r['hook_type'] == 'templateHooks' )
				{
					$templates	= $this->hooksFunctions->getSkinMethods( $r['hook_data']['skinGroup'] );
					$hookIds	= $this->hooksFunctions->getHookIds( $r['hook_data']['skinFunction'], $r['hook_data']['type'] );
					
					$r['_skinDropdown']		= $this->registry->output->formDropdown( "skinGroup[{$index}]", $skinGroups, $r['hook_data']['skinGroup'], "skinGroup[{$index}]", "onchange='getTemplatesForAdd({$index});'" );
					$r['_templateDropdown']	= $this->registry->output->formDropdown( "skinFunction[{$index}]", $templates, $r['hook_data']['skinFunction'], "skinFunction[{$index}]", "onchange='getTypeOfHook({$index});'" );
					$r['_hookTypeDropdown']	= $this->registry->output->formDropdown( "type[{$index}]", array( array( 0, $this->lang->words['a_selectone'] ), array( 'foreach', $this->lang->words['hook_foreach_loop'] ), array( 'if', $this->lang->words['hook_if_statement'] ) ), $r['hook_data']['type'], "type[{$index}]", "onchange='getHookIds({$index});'" );
					$r['_hookIdsDropdown']	= $this->registry->output->formDropdown( "id[{$index}]", $hookIds, $r['hook_data']['id'], "id[{$index}]", "onchange='getHookEntryPoints({$index});'" );
					$r['_hookEPDropdown']	= $this->registry->output->formDropdown( "position[{$index}]", $r['hook_data']['type'] == 'foreach' ? $entryPoints['foreach'] : $entryPoints['if'], $r['hook_data']['position'] );
				}
				
				if( $r['hook_type'] == 'dataHooks' )
				{
					$r['_dataLocationDropdown']	= $this->registry->output->formDropdown( "dataLocation[{$index}]", $_dataHooks, $r['hook_data']['dataLocation'] );
				}
				
				$files[ $index ] = $r;
				$index++;
			}
			
			$action = 'do_edit_hook';
		}
		else
		{
			$action = 'do_create_hook';
		}
		
		/* Info */
		foreach( array( 'hook_name', 'hook_desc', 'hook_key', 'hook_version_human', 'hook_version_long', 'hook_author', 'hook_email', 'hook_website', 'hook_update_check' ) as $_hook_key )
		{
			$form[ $_hook_key ] = $this->registry->output->formSimpleInput( $_hook_key , isset($this->request[ $_hook_key ]) ? $this->request[ $_hook_key ] : $hookData[ $_hook_key ], 60 );
		}
		
		/* Requirements */
		foreach( array( 'hook_php_version_min', 'hook_php_version_max' ) as $_version_key )
		{
			$form[ $_version_key ] = $this->registry->output->formSimpleInput( $_version_key , isset($this->request[ $_version_key ]) ? $this->request[ $_version_key ] : $hookData['hook_requirements'][ $_version_key ], 20 );
		}
		
		/* Setup the global caches DD */
		$_caches = array();
		
		$this->DB->build( array( 'select' => 'cs_key',
								 'from'   => 'cache_store',
								 'order'  => 'cs_key ASC'
						 )		);
		$this->DB->execute();
		
		while( $gc = $this->DB->fetch() )
		{
			$_caches[] = array( $gc['cs_key'], $gc['cs_key'] );
		}
		
		sort( $_caches );
		
		$form['hook_global_caches'] = $this->registry->output->formMultiDropdown( 'hook_global_caches[]', $_caches, isset($_POST['hook_global_caches']) ? $_POST['hook_global_caches'] : explode(',',$hookData['hook_global_caches']), 15 );
		
		/* Get some data for the javascript */
		$form['jsDataTypes']  = IPSText::br2nl( $this->registry->output->formDropdown( "type[#{index}]", array( array( 0, $this->lang->words['a_selectone'] ), array( 'foreach', $this->lang->words['hook_foreach_loop'] ), array( 'if', $this->lang->words['hook_if_statement'] ) ), '', "type[#{index}]", "onchange='getHookIds(#{index});'" ) );
		$form['jsDataPoints'] = IPSText::br2nl( $this->registry->output->formDropdown( "position[#{index}]", $r['hook_data']['type'] == 'foreach' ? $entryPoints['foreach'] : $entryPoints['if'] ) );
		
		/* Output */
		$this->registry->output->html .= $this->html->hookForm( $form, $action, $hookData, $files, $requirements );
	}

	/**
	 * Hook add/edit save
	 * Save the new (or updated) hook record
	 *
	 * @param	string		[add|edit]
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _hookSave( $type='add' )
	{
		/* Init vars */
		$hookData   = array();
		$newFiles	= array();
		$requireApp	= array();
		$_hookFiles	= array();
		$extraKey   = '';
		
		/* Get data if we are editing */
		if( $type == 'edit' )
		{
			$id	= intval($this->request['hook_id']);
			
			if( !$id )
			{
				$this->registry->output->showError( $this->lang->words['h_noedit'], 1118 );
			}
			
			$hookData = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_hooks', 'where' => 'hook_id=' . $id ) );
			
			if( !$hookData['hook_id'] )
			{
				$this->registry->output->showError( $this->lang->words['h_noedit'], 1119 );
			}
			
			$extraKey = ' AND hook_id != ' . $hookData['hook_id'];
		}
		
		/* Error Checking */
		if( ! $this->request['hook_name'] )
		{
			$errors[] = $this->lang->words['hook_form_no_title'];
		}
		
		if( empty( $this->request['hook_key'] ) )
		{
			$errors[] = $this->lang->words['hook_form_no_key'];
		}
		else
		{
			$keyCheck = $this->DB->buildAndFetch( array( 'select' => 'count(*) as found', 'from' => 'core_hooks', 'where' => "hook_key='" . $this->DB->addSlashes( $this->request['hook_key'] ) . "'" . $extraKey ) );
			
			if ( $keyCheck['found'] > 0 )
			{
				$errors[] = $this->lang->words['hook_form_dupe_key'];
			}
		}
		
		/* Got any errors? */
		if ( is_array( $errors ) && count( $errors ) )
		{
			$this->registry->output->global_error = implode( '<br />', $errors );
			$this->_hookForm();
			return;
		}
		
		/* Not IN_DEV? */
		if ( ! IN_DEV )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'core_hooks_files', 'where' => 'hook_hook_id=' . intval($this->request['hook_id']) ) );
			$this->DB->execute();
			
			while( $_r = $this->DB->fetch() )
			{
				$_hookFiles[ $_r['hook_classname'] ] = $_r['hook_file_stored'];
			}
		}
		
		/* Check for requirements */
		if ( is_array( $this->request['requireApp'] ) AND count( $this->request['requireApp'] ) )
		{
			foreach( $this->request['requireApp'] as $_index => $app_key )
			{
				if ( $app_key )
				{
					$minVersion = intval($this->request['minVersion'][ $_index ]);
					$maxVersion = intval($this->request['maxVersion'][ $_index ]);
					
					$requireApp[ $app_key ] = array( 'app_name'		=> ipsRegistry::$applications[ $app_key ]['app_title'],
													 'min_version'	=> $minVersion,
													 'max_version'	=> $maxVersion
													);
				}
			}
		}
		
		/* Check for files */
		if ( is_array( $this->request['file'] ) AND count( $this->request['file'] ) )
		{
			foreach( $this->request['file'] as $index => $file )
			{
				if ( $file )
				{
					$newFiles[ $index ]	= array('hook_file_real'	=> $file,
												'hook_type'			=> $this->request['hook_type'][ $index ],
												'hook_classname'	=> $this->request['hook_classname'][ $index ],
												'hook_data'			=> serialize( array('dataLocation'		=> trim( $this->request['dataLocation'][ $index ] ),
																						'libApplication'	=> trim( $this->request['libApplication'][ $index ] ),
																						'classToOverload'	=> trim($this->request['classToOverload'][ $index ]),
																						'skinGroup'			=> $this->request['skinGroup'][ $index ],
																						'skinFunction'		=> $this->request['skinFunction'][ $index ],
																						'type'				=> $this->request['type'][ $index ],
																						'id'				=> $this->request['id'][ $index ],
																						'position'			=> $this->request['position'][ $index ],
																						)
																				 )
												);

					/**
					 * @link	http://community.invisionpower.com/tracker/issue-22084-hook-edit-does-not-preserve-correct-name/
					 * We don't want to reset the stored name if you are not in developer mode
					 */
					if ( IN_DEV )
					{
						$newFiles[ $index ]['hook_file_stored']	= $file; // During import this is a random name, but for devs it's actual file
					}
					else
					{
						$newFiles[ $index ]['hook_file_stored']	= $_hookFiles[ $this->request['hook_classname'][ $index ] ] ? $_hookFiles[ $this->request['hook_classname'][ $index ] ] : $file;
					}
				}
			}
		}
		
		/* Get position */
		if ( $type == 'add' )
		{
			$position = $this->DB->buildAndFetch( array( 'select' => 'MAX(hook_position) as newPos', 'from' => 'core_hooks' ) );
			
			$position['newPos'] = intval($position['newPos']) + 1;
		}
		else
		{
			$position['newPos'] = $hookData['hook_position'];
		}

		$mainHookRecord	= array('hook_name'				=> trim($this->request['hook_name']),
								'hook_key'				=> substr( trim($this->request['hook_key']), 0, 32 ),
								'hook_global_caches'	=> ( is_array($this->request['hook_global_caches']) && count($this->request['hook_global_caches']) ) ? implode(',', $this->request['hook_global_caches']) : '',
								'hook_desc'				=> trim($this->request['hook_desc']),
								'hook_version_human'	=> trim($this->request['hook_version_human']),
								'hook_version_long'		=> trim($this->request['hook_version_long']),
								'hook_author'			=> trim($this->request['hook_author']),
								'hook_email'			=> trim($this->request['hook_email']),
								'hook_website'			=> trim($this->request['hook_website']),
								'hook_update_check'		=> trim($this->request['hook_update_check']),
								'hook_enabled'			=> $type == 'add' ? 1 : $hookData['hook_enabled'],
								'hook_installed'		=> $type == 'add' ? IPS_UNIX_TIME_NOW : $hookData['hook_installed'],
								'hook_updated'			=> $type == 'add' ? 0 : IPS_UNIX_TIME_NOW,
								'hook_position'			=> $position['newPos'],
								'hook_requirements'		=> serialize( array('required_applications'	=> $requireApp,
																			'hook_php_version_min'	=> trim($this->request['hook_php_version_min']),
																			'hook_php_version_max'	=> trim($this->request['hook_php_version_max'])
																			)
																	 )
								);
		
		if( $type == 'edit' )
		{
			$this->DB->update( 'core_hooks', $mainHookRecord, 'hook_id=' . $hookData['hook_id'] );
			
			$this->DB->delete( 'core_hooks_files', 'hook_hook_id=' . $hookData['hook_id'] );
		}
		else
		{
			$this->DB->insert( 'core_hooks', $mainHookRecord );
			
			$hookData['hook_id'] = $this->DB->getInsertId();
		}
		
		foreach( $newFiles as $index => $toInsert )
		{
			$toInsert['hook_hook_id'] = $hookData['hook_id'];
			
			$this->DB->insert( 'core_hooks_files', $toInsert );
		}
		
		/* Rebuild cache */
		$this->rebuildHooksCache();
		
		/* Rebuild global caches */
		try
		{
			IPSLib::cacheGlobalCaches();
		}
		catch( Exception $e ){}
		
		/* Flag skins for recache */
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );/*noLibHook*/
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );/*noLibHook*/
		$skinCaching = new skinCaching( $this->registry );
		$skinCaching->flagSetForRecache();
		
		/* Redirect */
		$this->registry->output->setMessage( $this->lang->words['h_saved'] );
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=hooks_overview' );
	}
	
	/**
	 * View details about a hook
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _viewDetails()
	{
		$id	= intval($this->request['id']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['h_nodetails'], 11110 );
		}
		
		$hookData = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_hooks', 'where' => 'hook_id=' . $id ) );
		
		if( !$hookData['hook_id'] )
		{
			$this->registry->output->showError( $this->lang->words['h_nodetails'], 11111 );
		}
		
		/* Extra data */
		$hookData['_updated']		 = $this->registry->getClass('class_localization')->formatTime( $hookData['hook_updated'] );
		$hookData['hook_extra_data'] = unserialize( $hookData['hook_extra_data'] );
		
		/* Meets requirements? */
		$hookData['_require_errors'] = $this->checkHookRequirements( $hookData );
		
		/* Got updates? */
		if ( $hookData['hook_update_check'] )
		{
			$hookData['hook_update_available']	= $this->_updateAvailable( $hookData['hook_update_check'], $hookData['hook_version_long'] );
		}
		else
		{
			$hookData['hook_update_available']	= array( 0 );
		}
		
		/* Get files */
		$files = array();
		$index = 1;
		
		$this->DB->build( array( 'select' => '*', 'from' => 'core_hooks_files', 'where' => 'hook_hook_id=' . $id ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$r['hook_data']  = unserialize( $r['hook_data'] );
			$files[ $index ] = $r;
			$index++;
		}

		/* Output */
		$this->registry->output->html .= $this->html->hookDetails( $hookData, $files );
	}
	
	/**
	 * Check if the hook meets all the requirements
	 * 
	 * @param	mixed	$hook	Can be an hook ID or an array of the hook data
	 * @return	@e array Errors found
	 */
	public function checkHookRequirements( $hook )
	{
		/* Init vars */
		$hookData = array();
		$reqsData = array();
		$errors   = array();
		
		/* We already have some data? */
		if ( is_array($hook) && count($hook) )
		{
			$hookData = $hook;
		}
		elseif ( is_int($hook) )
		{
			$hookData = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_hooks', 'where' => 'hook_id=' . $hook ) );
		}
			
		/* Requirements are still serialized? */
		if ( IPSLib::isSerialized($hookData['hook_requirements']) )
		{
			$hookData['hook_requirements'] = unserialize($hookData['hook_requirements']);
		}
		
		/* Make our var shorter... */
		$reqsData = &$hookData['hook_requirements'];
		
		/* Old data? - @todo: remove this check around 3.3(4?) */
		if ( ! isset($reqsData['required_applications']['core']) && isset($reqsData['hook_ipb_version_min']) && ( $reqsData['hook_ipb_version_min'] > 0 || $reqsData['hook_ipb_version_max'] > 0 ) )
		{
			$reqsData['hook_ipb_version_min'] = ( $reqsData['hook_ipb_version_min'] < 30000 ) ? 30000 : $reqsData['hook_ipb_version_min'];
			
			$reqsData['required_applications']['core'] = array( 'min_version' => intval($reqsData['hook_ipb_version_min']), 'max_version' => intval($reqsData['hook_ipb_version_max']) );
		}
		
		//-----------------------------------------
		// Let's start checking requirements
		//-----------------------------------------
		
		/* PHP */
		if( $reqsData['hook_php_version_min'] OR $reqsData['hook_php_version_max'] )
		{
			if( $reqsData['hook_php_version_min'] AND version_compare( PHP_VERSION, $reqsData['hook_php_version_min'], '<' ) == true )
			{
				$errors['php_min'] = sprintf( $this->lang->words['h_phpold'],$reqsData['hook_php_version_min'] );
			}
			
			if( $reqsData['hook_php_version_max'] AND version_compare( PHP_VERSION, $reqsData['hook_php_version_max'], '>' ) == true )
			{
				$errors['php_max'] = sprintf( $this->lang->words['h_phpnew'], $reqsData['hook_php_version_max'] );
			}
		}
		
		/* Additional applications */
		if ( is_array($reqsData['required_applications']) && count($reqsData['required_applications']) )
		{
			/* Get the setup class */
			require_once( IPS_ROOT_PATH . 'setup/sources/base/setup.php' );/*noLibHook*/
			
			/* Loop through all apps */
			foreach( $reqsData['required_applications'] as $appKey => $appData )
			{
				/* Versions file doesn't exist? */
				if ( !is_file( IPSLib::getAppDir( $appKey ) . '/xml/versions.xml' ) )
				{
					$errors[ $appKey.'_app' ] = sprintf( $this->lang->words['hook_require_appnotfound'], $appData['app_name'] );
				}
				/* App not installed/enabled? */
				elseif ( !IPSLib::appIsInstalled( $appKey ) )
				{
					$errors[ $appKey.'_app' ] = sprintf( $this->lang->words['hook_require_appdisabled'], ipsRegistry::$applications[ $appKey ]['app_title'] );
				}
				elseif ( $appData['min_version'] OR $appData['max_version'] )
				{
					/* Fetch and check versions */
					if ( !isset($this->cachedVersions[ $appKey ]) )
					{
						$this->cachedVersions[ $appKey ] = IPSSetUp::fetchXmlAppVersions( $appKey );
					}
					
					$versions = $this->cachedVersions[ $appKey ];
					
					if ( is_array($versions) && count($versions) )
					{
						if ( !isset($this->cachedUpgradeInfo[ $appKey ]) )
						{
							$_key	= in_array( $appKey, array( 'forums', 'members' ) ) ? 'core' : $appKey;
							$this->cachedUpgradeInfo[ $_key ]	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'upgrade_history', 'where' => "upgrade_app='{$_key}'", 'order' => 'upgrade_version_id DESC', 'limit' => array( 1 ) ) );
							
							/* Extra caching for the three core apps */
							if( in_array( $appKey, array( 'core', 'forums', 'members' ) ) )
							{
								$this->cachedUpgradeInfo['core']	= $this->cachedUpgradeInfo[ $_key ];
								$this->cachedUpgradeInfo['forums']	= $this->cachedUpgradeInfo[ $_key ];
								$this->cachedUpgradeInfo['members']	= $this->cachedUpgradeInfo[ $_key ];
							}
						}
						
						/* Do we meet tha requirements? */
						if ( $appData['min_version'] AND $this->cachedUpgradeInfo[ $appKey ]['upgrade_version_id'] < $appData['min_version'] )
						{
							$errors[ $appKey.'_min' ] = sprintf( $this->lang->words['hook_require_tooold'], isset($versions[ $appData['min_version'] ]) ? $versions[ $appData['min_version'] ] : $appData['min_version'] );
						}
						
						if ( $appData['max_version'] AND $this->cachedUpgradeInfo[ $appKey ]['upgrade_version_id'] > $appData['max_version'] )
						{
							$errors[ $appKey.'_max' ] = sprintf( $this->lang->words['hook_require_toonew'], isset($versions[ $appData['max_version'] ]) ? $versions[ $appData['max_version'] ] : $appData['max_version'] );
						}
					}
				}
			}
		}
		
		return $errors;
	}
	
	/**
	 * Check if you meet hook requirements
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _checkRequirements()
	{
		$id	= intval($this->request['id']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['h_norequirements'], 11112 );
		}
		
		$hookData = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_hooks', 'where' => 'hook_id=' . $id ) );
		
		if( !$hookData['hook_id'] )
		{
			$this->registry->output->showError( $this->lang->words['h_norequirements'], 11113 );
		}
		
		/* Get requirements */
		$hookData['hook_requirements'] = unserialize($hookData['hook_requirements']);
		
		/* Check requirements */
		$errors = $this->checkHookRequirements( $hookData );
		
		/* Output */
		$this->registry->output->html .= $this->html->hookRequirements( $hookData, $errors, $this->cachedVersions );
	}
	
	/**
	 * Rebuild hooks cache
	 *
	 * @return	@e void
	 */
	public function rebuildHooksCache()
	{
		/* INI */
		$cache = array( 'commandHooks' => array(), 'skinHooks' => array(), 'templateHooks' => array(), 'dataHooks' => array(), 'libraryHooks' => array() );

		/* First fix positions */
		$this->_fixPositions();

		/* Get current hooks */
		$this->DB->build( array( 'select'	=> 'f.*', 
								 'from'		=> array( 'core_hooks_files' => 'f' ), 
								 'where'	=> 'c.hook_enabled=1',
								 'order'	=> 'c.hook_position ASC',
								 'add_join'	=> array( array( 'select' => 'c.hook_id',
															 'from'   => array( 'core_hooks' => 'c' ),
															 'where'  => 'c.hook_id=f.hook_hook_id',
															 'type'   => 'left' ) )
							)		);
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			/* Got no main hook id? Weird but skip.. */
			if ( empty($r['hook_id']) )
			{
				continue;
			}
			
			/* INIT */
			$_cache	= array();
			$data	= unserialize( $r['hook_data'] );
			
			/* General Stuff */
			$_cache	= array( 'filename'  => $r['hook_file_stored'],
							 'className' => $r['hook_classname']
							);
			
			/* Hook type specific stuff */
			if( $r['hook_type'] == 'templateHooks' )
			{
				$_cache['type']				= $data['type'];
				$_cache['skinFunction']		= $data['skinFunction'];
				$_cache['id']				= $data['id'];
				$_cache['position']			= $data['position'];
			}
			else if( /*$r['hook_type'] == 'dataHooks' OR*/ $r['hook_type'] == 'libraryHooks' )
			{
				$_cache['classToOverload']	= $data['classToOverload'];
			}
			
			/* Add to array */
			if( $r['hook_type'] == 'templateHooks' )
			{
				$cache[ $r['hook_type'] ][ $data['skinGroup'] ][] = $_cache;
			}
			else if( $r['hook_type'] == 'dataHooks' )
			{
				$cache[ $r['hook_type'] ][ $data['dataLocation'] ][] = $_cache;
			}
			else if( $r['hook_type'] == 'libraryHooks' )
			{
				$cache[ $r['hook_type'] ][ $data['libApplication'] ][ $data['classToOverload'] ][] = $_cache;
			}
			else
			{
				$cache[ $r['hook_type'] ][ $data['classToOverload'] ][] = $_cache;
			}
		}
		
		/* Update the cache */
		$this->cache->setCache( 'hooks', $cache, array( 'array' => 1 ) );
	}
}