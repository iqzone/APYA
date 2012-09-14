<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Application Installation
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
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


class admin_core_applications_setup extends ipsCommand
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
	 * Current app path
	 *
	 * @var		object
	 */
	protected $app_full_path;
	
	/**
	 * Product information
	 *
	 * @var		array
	 */
	protected $product_information;
	
	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'app_install' );
		
		/* Get Template and Language */
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_setup' );
		
		$this->lang->loadLanguageFile( array( 'admin_setup', 'admin_system', 'admin_tools', 'admin_templates' ), 'core' );
		
		/* URL Bits */
		$this->form_code    = $this->html->form_code = 'module=applications&amp;section=setup';
		$this->form_code_js = $this->html->form_code_js = 'module=applications&section=setup';
		
		/* Get the setup class */
		require( IPS_ROOT_PATH . "setup/sources/base/setup.php" );/*noLibHook*/
		
		/* Set the path */
		$this->app_full_path = IPSLib::getAppDir( $this->request['app_directory'] ) . '/';
		
		/* Set up product info from XML file */
		$this->product_information = IPSSetUp::fetchXmlAppInformation( $this->request['app_directory'], $this->settings['gb_char_set'] );
		
		if( ! $this->app_full_path OR ! $this->product_information['title'] )
		{
			$this->registry->output->global_message = $this->lang->words['error__cannot_init'];
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] );
			return;		
		}
		
		/* Sequence of Events:
			# SQL
			# App Module
			# Check for more modules
			# Templates
			# Languages
			# Tasks
			# Settings
			# Template Cache
			# Caches / Done
		*/
		switch( $this->request['do'] )
		{
			default:
			case 'start':
				$this->start();
			break;
			case 'sql':
				$this->sqlBasics();
			break;
			case 'sql_steps':
				$this->sqlSteps();
			break;
			case 'next_check':
				$this->nextCheck();
			break;
			
			case 'templates':
				$this->templates();
			break;
			case 'languages':
				$this->languages();
			break;
			case 'tasks':
				$this->tasks();
			break;
			case 'bbcode':
				$this->bbcode();
			break;
			case 'help':
				$this->help();
			break;
			case 'settings':
				$this->settings();
			break;
			case 'hooks':
				$this->hooks();
			break;
			case 'tplcache':
				$this->recacheTemplates();
			break;
			case 'finish':
				$this->finish();
			break;
		}

		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Rebuild PHP Templates Cache
	 *
	 * @return	@e void
	 */
	public function recacheTemplates()
	{
		/* Flag skins for recache */
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );/*noLibHook*/
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );/*noLibHook*/
		$skinCaching = new skinCaching( $this->registry );
		
		/* Flag all for recache */
		$skinCaching->flagSetForRecache();
		
		$data = $this->getVars();
		
		$this->showRedirectScreen( $data['app_directory'], array( $this->lang->words['install_skins_flag_recache'] ), '', $this->getNextURL( 'finish', $data ) );			
	}	
	
	/**
	 * Finalizes installation and rebuilds caches
	 *
	 * @return	@e void
	 */
	public function finish()
	{
		/* INIT */
		$vars      = $this->getVars();
		$output    = array();
		$errors    = array();
		
		/* Init Data */
		$data      = IPSSetUp::fetchXmlAppInformation( $vars['app_directory'], $this->settings['gb_char_set'] );
		$_numbers  = IPSSetUp::fetchAppVersionNumbers( $vars['app_directory'] );
		
		/* Grab Data */
		$data['app_directory']   = $vars['app_directory'];
		$data['current_version'] = ( $_numbers['current'][0] ) ? $_numbers['current'][0] : $this->lang->words['cur_version_none'];
		$data['latest_version']  = $_numbers['latest'][1];
		$data['next_version']    = $_numbers['next'][0];
		
		/* Rebuild applications and modules cache */
		$this->cache->rebuildCache( 'app_cache', 'global' );
		$this->cache->rebuildCache( 'module_cache', 'global' );
		$this->cache->rebuildCache( 'app_menu_cache', 'global' );
		$this->cache->rebuildCache( 'group_cache', 'global' );
		$this->cache->rebuildCache( 'notifications', 'global' );
		$this->cache->rebuildCache( 'report_plugins', 'global' );

		/* Rebuild application specific caches */
		$_file = $this->app_full_path . 'extensions/coreVariables.php';
			
		if( is_file( $_file ) )
		{
			$CACHE = array();
			require( $_file );/*noLibHook*/
			
			if( is_array( $CACHE ) AND count( $CACHE ) )
			{
				foreach( $CACHE as $key => $cdata )
				{
					$this->cache->rebuildCache( $key, $vars['app_directory'] );
				}
			}
		}
		
		/* Rebuild GLOBAL CACHES! */
		try
		{
			IPSLib::cacheGlobalCaches();
		}
		catch( Exception $e )
		{
			// Show an error?
		}
		
		/* Rebuild FURLs */
		if( is_file( $this->app_full_path . 'extensions/furlTemplates.php' ) )
		{
			try
			{
				IPSLib::cacheFurlTemplates();
			}
			catch( Exception $e )
			{
				// Show an error?
			}
		}	
		
		/* Show completed screen... */
		$this->registry->output->html .= $this->html->setup_completed_screen( $data, $vars['type'] );
	}
	
	/**
	 * Next Check
	 *
	 * @return	@e void
	 */
	public function nextCheck()
	{
		/* INIT */
		$vars      = $this->getVars();
		$output    = array();
		$errors    = array();
		
		/* Init Data */
		$data      = IPSSetUp::fetchXmlAppInformation( $vars['app_directory'], $this->settings['gb_char_set'] );
		$_numbers  = IPSSetUp::fetchAppVersionNumbers( $vars['app_directory'] );
		$modules   = IPSSetUp::fetchXmlAppModules( $vars['app_directory'], $this->settings['gb_char_set'] );
		
		/* Grab Data */
		$data['app_directory']   = $vars['app_directory'];
		$data['current_version'] = ( $_numbers['current'][0] ) ? $_numbers['current'][0] : $this->lang->words['cur_version_none'];
		$data['latest_version']  = $_numbers['latest'][1];
		$data['next_version']    = $_numbers['next'][0];
		
		/* Update the app DB */
		if( $vars['type'] == 'install' )
		{
			/* Get current max position */
			$pos = $this->DB->buildAndFetch( array( 'select' => 'MAX(app_position) as pos', 'from' => 'core_applications' ) );
			$new_pos = intval( $pos['pos'] ) + 1;
			
			/* Insert details into the DB */
			$this->DB->insert( 'core_applications', array(  'app_title'			=> $this->product_information['name'],
															'app_public_title'	=> $this->product_information['public_name'],
															'app_author'		=> $this->product_information['author'],
															'app_description'	=> $this->product_information['description'],
															'app_hide_tab'		=> intval($this->product_information['hide_tab']),
															'app_version'		=> $_numbers['latest'][1],
															'app_long_version'	=> $_numbers['latest'][0],
															'app_directory'		=> $vars['app_directory'],
															'app_location'		=> $vars['app_location'],
															'app_added'			=> time(),
															'app_position'		=> $new_pos,
															'app_protected'		=> 0,
															'app_enabled'		=> $this->product_information['disabledatinstall'] ? 0 : 1,
															'app_website'		=> trim($this->product_information['website']),
															'app_update_check'	=> trim($this->product_information['update_check']),
															'app_global_caches'	=> trim($this->product_information['global_caches']),
															)
								);
			
			$this->DB->insert( 'upgrade_history', array( 
														'upgrade_version_id'	=> $_numbers['latest'][0],
														'upgrade_version_human'	=> $_numbers['latest'][1],
														'upgrade_date'			=> time(),
														'upgrade_notes'			=> '',
														'upgrade_mid'			=> $this->memberData['member_id'],
														'upgrade_app'			=> $vars['app_directory'],
												)	);
			
			/* Insert the modules */
			foreach( $modules as $key => $module )
			{
				$this->DB->insert( 'core_sys_module', $module );
			}
		}
		else
		{
			$this->DB->update( 'core_applications', array(  'app_title'			=> $this->product_information['name'],
															'app_public_title'	=> $this->product_information['public_name'],
															'app_author'		=> $this->product_information['author'],
															'app_description'	=> $this->product_information['description'],
															'app_hide_tab'		=> intval($this->product_information['hide_tab']),
															'app_version'		=> $_numbers['current'][1],
															'app_long_version'	=> $_numbers['current'][0],
															'app_website'		=> trim($this->product_information['website']),
															'app_update_check'	=> trim($this->product_information['update_check']),
															'app_global_caches'	=> trim($this->product_information['global_caches']),
							), "app_directory='" . $vars['app_directory'] . "'" );
			
			/* Update the modules */
			foreach( $modules as $key => $module )
			{
				$this->DB->update( 'core_sys_module', $module, "sys_module_application='{$module['sys_module_application']}' AND sys_module_key='{$module['sys_module_key']}'" );
			}
		}
		
		/* Finish? */
		if( $vars['type'] == 'install' OR $vars['version'] == $_numbers['latest'][0] )
		{
			/* Go back and start over with the new version */
			$output[] = $this->lang->words['redir__nomore_modules'];

			$this->showRedirectScreen( $vars['app_directory'], $output, $errors, $this->getNextURL( 'templates', $vars ) );
		}
		else
		{
			/* Go back and start over with the new version */
			$output[] = sprintf( $this->lang->words['redir__upgraded_to'], $_numbers['current'][1] );
			
			/* Work out the next step */
			$vars['version'] = $_numbers['next'][0];
			
			$this->showRedirectScreen( $vars['app_directory'], $output, $errors, $this->getNextURL( 'sql', $vars ) );
		}
	}
	
	/**
	 * Import Hooks
	 *
	 * @return	@e void
	 */
	public function hooks()
	{
		/* INIT */
		$vars          = $this->getVars();
		$output        = array();
		$errors        = array();
		$knownSettings = array();
		
		$this->registry->class_localization->loadLanguageFile( array( 'admin_tools' ) );
		
		if( is_file( $this->app_full_path . 'xml/hooks.xml' ) )
		{
			/* Get the hooks class */
			$classToLoad = IPSLib::loadActionOverloader( IPS_ROOT_PATH . 'applications/core/modules_admin/applications/hooks.php', 'admin_core_applications_hooks' );
			$hooks = new $classToLoad();
			$hooks->makeRegistryShortcuts( $this->registry );

			$return = $hooks->installAppHooks( $vars['app_directory'] );
			$this->cache->rebuildCache( 'hooks', 'global' );
			
			$output[] = sprintf( $this->lang->words['redir__hooks'], $return['inserted'], $return['updated'] );
		}
		else
		{
			$this->registry->output->global_message	= $this->lang->words['hooks_nofile'];
			
			$output[] = $this->registry->output->global_message;
		}
		
		/* Clear main messaage */
		$this->registry->output->global_message = '';

		/* Show redirect... */
		$this->showRedirectScreen( $vars['app_directory'], $output, $errors, $this->getNextURL( 'tplcache', $vars ) );
	}
	
	/**
	 * Import Settings
	 *
	 * @return	@e void
	 */
	public function settings()
	{
		/* INIT */
		$vars          = $this->getVars();
		$output        = array();
		$errors        = array();
		$knownSettings = array();
		
		$this->registry->class_localization->loadLanguageFile( array( 'admin_tools' ), 'core' );
		
		if( is_file( $this->app_full_path . 'xml/' . $vars['app_directory'] . '_settings.xml' ) )
		{
			/* Get the settings class */
			$classToLoad = IPSLib::loadActionOverloader( IPS_ROOT_PATH . 'applications/core/modules_admin/settings/settings.php', 'admin_core_settings_settings' );
			$settings = new $classToLoad();
			$settings->makeRegistryShortcuts( $this->registry );
			
			$this->request['app_dir'] = $vars['app_directory'];
			
			//-----------------------------------------
			// Known settings
			//-----------------------------------------

			if ( substr( $this->settings['_original_base_url'], -1 ) == '/' )
			{
				IPSSetUp::setSavedData('install_url', substr( $this->settings['_original_base_url'], 0, -1 ) );
			}
			
			if ( substr( $this->settings['base_dir'], -1 ) == '/' )
			{
				IPSSetUp::setSavedData('install_dir', substr( $this->settings['base_dir'], 0, -1 ) );
			}
			
			/* Fetch known settings  */
			if ( is_file( IPSLib::getAppDir( $vars['app_directory'] ) . '/setup/versions/install/knownSettings.php' ) )
			{
				require( IPSLib::getAppDir( $vars['app_directory'] ) . '/setup/versions/install/knownSettings.php' );/*noLibHook*/
			}
			
			$settings->importAllSettings( 1, 1, $knownSettings );
			$settings->settingsRebuildCache();
		}
		else
		{
			$this->registry->output->global_message	= $this->lang->words['settings_nofile'];
		}
		
		$output[] = $this->registry->output->global_message;
		
		/* Clear main messaage */
		$this->registry->output->global_message = '';

		/* Show redirect... */
		$this->showRedirectScreen( $vars['app_directory'], $output, $errors, $this->getNextURL( 'hooks', $vars ) );
	}
	
	/**
	 * Import tasks
	 *
	 * @return	@e void
	 */
	public function tasks()
	{
		/* INIT */
		$vars      = $this->getVars();
		$output    = array();
		$errors    = array();
		
		if( is_file( $this->app_full_path . 'xml/' . $vars['app_directory'] . '_tasks.xml' ) )
		{
			/* Get the language class */
			$classToLoad = IPSLib::loadActionOverloader( IPS_ROOT_PATH . 'applications/core/modules_admin/system/taskmanager.php', 'admin_core_system_taskmanager' );
			$task_obj = new $classToLoad();
			$task_obj->makeRegistryShortcuts( $this->registry );
			
			$task_obj->tasksImportFromXML( $this->app_full_path . 'xml/' . $vars['app_directory'] . '_tasks.xml', true );
		}
		
		$output[] = $this->registry->output->global_message ? $this->registry->output->global_message : $this->lang->words['no_tasks_for_import'];
		
		/* Clear main msg */
		$this->registry->output->global_message = '';

		/* Show redirect... */
		$this->showRedirectScreen( $vars['app_directory'], $output, $errors, $this->getNextURL( 'bbcode', $vars ) );
	}
	
	/**
	 * Import bbcode
	 *
	 * @return	@e void
	 */
	public function bbcode()
	{
		/* INIT */
		$vars      = $this->getVars();
		$output    = array();
		$errors    = array();
		
		if( is_file( $this->app_full_path . 'xml/' . $vars['app_directory'] . '_bbcode.xml' ) )
		{
			/* Get the language class */
			$classToLoad = IPSLib::loadActionOverloader( IPS_ROOT_PATH . 'applications/core/modules_admin/posts/bbcode.php', 'admin_core_posts_bbcode' );
			$bbcode = new $classToLoad();
			$bbcode->makeRegistryShortcuts( $this->registry );
			
			$bbcode->bbcodeImportDo( file_get_contents( $this->app_full_path . 'xml/' . $vars['app_directory'] . '_bbcode.xml' ) );
			
			$output[] = $this->lang->words['bbcode_and_media'];
		}
		
		if( is_file( $this->app_full_path . 'xml/' . $vars['app_directory'] . '_mediatag.xml' ) )
		{
			/* Get the language class */
			$classToLoad = IPSLib::loadActionOverloader( IPS_ROOT_PATH . 'applications/core/modules_admin/posts/media.php', 'admin_core_posts_media' );
			$bbcode = new $classToLoad();
			$bbcode->makeRegistryShortcuts( $this->registry );
			
			$bbcode->doMediaImport( file_get_contents( $this->app_full_path . 'xml/' . $vars['app_directory'] . '_mediatag.xml' ) );
			
			if( !count($output) )
			{
				$output[] = $this->lang->words['bbcode_and_media'];
			}
		}
		
		if( !count($output) )
		{
			$output[] = $this->lang->words['no_bbcode_media'];
		}

		/* Show redirect... */
		$this->showRedirectScreen( $vars['app_directory'], $output, $errors, $this->getNextURL( 'help', $vars ) );
	}
	
	/**
	 * Import help
	 *
	 * @return	@e void
	 */
	public function help()
	{
		/* INIT */
		$vars      = $this->getVars();
		$output    = array();
		$errors    = array();
		
		if( is_file( $this->app_full_path . 'xml/' . $vars['app_directory'] . '_help.xml' ) )
		{
			$classToLoad = IPSLib::loadActionOverloader( IPS_ROOT_PATH . 'applications/core/modules_admin/tools/help.php', 'admin_core_tools_help' );
			$help = new $classToLoad();
			$help->makeRegistryShortcuts( $this->registry );

			$done = $help->helpFilesXMLImport_app( $vars['app_directory'] );
			
			$output[] = sprintf( $this->lang->words['imported_x_help'], ($done['added'] + $done['updated']) );
		}
		else
		{
			$output[] = $this->lang->words['imported_no_help'];
		}
		

		/* Show redirect... */
		$this->showRedirectScreen( $vars['app_directory'], $output, $errors, $this->getNextURL( 'settings', $vars ) );
	}
	
	/**
	 * Language Import
	 *
	 * @return	@e void
	 */
	public function languages()
	{
		/* INIT */
		$vars      = $this->getVars();
		$output    = array();
		$errors    = array();
		
		/* Load the language file */
		$this->registry->class_localization->loadLanguageFile( array( 'admin_system' ) );
		
		/* Get the language stuff */
		$classToLoad = IPSLib::loadActionOverloader( IPS_ROOT_PATH . 'applications/core/modules_admin/languages/manage_languages.php', 'admin_core_languages_manage_languages' );
		$lang = new $classToLoad();
		$lang->makeRegistryShortcuts( $this->registry );	
			
		/* Loop through the xml directory and look for lang packs */
		$_PATH = $this->app_full_path . '/xml/';		
		
		try
		{
			foreach( new DirectoryIterator( $_PATH ) as $f )
			{
				if( preg_match( "#(.+?)_language_pack.xml#", $f->getFileName() ) )
				{
					$this->request['file_location'] = $_PATH . $f->getFileName();
					$lang->imprtFromXML( true, true, true, $vars['app_directory'] );				
				}
			}
		} catch ( Exception $e ) {}
		
		$output[] = $this->registry->output->global_message ? $this->registry->output->global_message : $this->lang->words['redir__nolanguages'];
		
		/* Clear main msg */
		$this->registry->output->global_message = '';

		/* Show redirect... */
		$this->showRedirectScreen( $vars['app_directory'], $output, $errors, $this->getNextURL( 'tasks', $vars ) );
	}
	
	/**
	 * Install templates
	 *
	 * @return	@e void
	 */
	public function templates()
	{
		/* INIT */
		$vars      = $this->getVars();
		$output    = array();
		$errors    = array();
		
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );/*noLibHook*/
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );/*noLibHook*/
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinImportExport.php' );/*noLibHook*/
		$skinFunctions	= new skinImportExport( $this->registry );
		$skinCaching	= new skinCaching( $this->registry );
		
		/* Grab skin data */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'skin_collections' ) );
								
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			/* Bit of jiggery pokery... */
			if ( $row['set_key'] == 'default' )
			{
				$row['set_key'] = 'root';
				$row['set_id']  = 0;
			}
			
			$skinSets[ $row['set_key'] ] = $row;
		}
			
		foreach( $skinSets as $skinKey => $skinData )
		{
			/* Skin files first */
			if( is_file( $this->app_full_path . 'xml/' . $vars['app_directory'] . '_' . $skinKey . '_templates.xml' ) )
			{
				$return = $skinFunctions->importTemplateAppXML( $vars['app_directory'], $skinKey, $skinData['set_id'], TRUE );
				
				$output[] = sprintf( $this->lang->words['redir__templates'], $return['insertCount'], $return['updateCount'], $skinData['set_name'] );
			}
			
			/* Then CSS files */
			if ( is_file( $this->app_full_path . 'xml/' . $vars['app_directory'] . '_' . $skinKey . '_css.xml' ) )
			{
				//-----------------------------------------
				// Install
				//-----------------------------------------
		
				$return = $skinFunctions->importCSSAppXML( $vars['app_directory'], $skinKey, $skinData['set_id'] );
				
				$output[] = sprintf( $this->lang->words['redir__cssfiles'], $return['insertCount'], $return['updateCount'], $skinData['set_name'] );
			}
			
			/* And we can support replacements for good measure */
			if ( is_file( $this->app_full_path . 'xml/' . $vars['app_directory'] . '_replacements.xml' ) )
			{
				//-----------------------------------------
				// Install
				//-----------------------------------------
		
				$return = $skinFunctions->importReplacementsXMLArchive( file_get_contents( $this->app_full_path . 'xml/' . $vars['app_directory'] . '_replacements.xml' ), $skinKey );
				
				$output[] = $this->lang->words['redir__replacements'];
			}
		}

		/* Recache */
		//$skinCaching->rebuildPHPTemplates( 0 );
		//$skinCaching->rebuildCSS( 0 );
		//$skinCaching->rebuildReplacementsCache( 0 );
		
		/* No templates?  Give some sort of feedback */
		if ( !count( $output ) )
		{
			$output[] = $this->lang->words['redir__no_templates'];
		}

		/* Show redirect... */
		$this->showRedirectScreen( $vars['app_directory'], $output, $errors, $this->getNextURL( 'languages', $vars ) );
	}
	
	/**
	 * Runs any additional sql files
	 *
	 * @return	@e void
	 */
	public function sqlSteps()
	{
		/* INIT */
		$vars      = $this->getVars();
		$output    = array();
		$errors    = array();
		$id        = intval( $this->request['id'] );
		$id        = ( $id < 1 ) ? 1 : $id;
		$sql_files = array();
		
		/* Any "extra" configs required for this driver? */
		if( is_file( IPS_ROOT_PATH . 'setup/sql/' . strtolower( $this->settings['sql_driver'] ) . '_install.php' ) )
		{
			require_once( IPS_ROOT_PATH . 'setup/sql/' . strtolower( $this->settings['sql_driver'] ) . '_install.php' );/*noLibHook*/

			$extra_install = new install_extra( $this->registry );
		}
		
		
		/* Run any sql files we found */
		if( is_file( $this->app_full_path . 'setup/versions/install/sql/' . $vars['app_directory']. '_' . strtolower( ips_DBRegistry::getDriverType() )  . '_sql_' . $id .'.php' ) )
		{
			/* INIT */
			$new_id = $id + 1;
			$count  = 0;
			$SQL    = array();
			
			/* Get the sql file */
			require_once( $this->app_full_path . 'setup/versions/install/sql/' . $vars['app_directory']. '_' . strtolower( ips_DBRegistry::getDriverType() )  . '_sql_' . $id .'.php' );/*noLibHook*/

			$this->DB->return_die = 1;
			
			/* Run the queries */
			foreach( $SQL as $query )
			{
				$this->DB->allow_sub_select 	= 1;
				$this->DB->error				= '';

				$query = str_replace( "<%time%>", time(), $query );
				
				if( $extra_install AND method_exists( $extra_install, 'process_query_insert' ) )
				{
					 $query = $extra_install->process_query_insert( $query );
				}
				
				$this->DB->query( $query );

				if ( $this->DB->error )
				{
					$errors[] = $query."<br /><br />".$this->DB->error;
				}
				else
				{
					$count++;
				}				
			}
			
			$output[] = sprintf( $this->lang->words['redir__sql_run'], $count );
			
			/* Show redirect... */
			$this->showRedirectScreen( $vars['app_directory'], $output, $errors, $this->getNextURL( 'sql_steps', $vars ) . '&amp;id=' . $new_id );
		}
		else
		{
			$output[] = $this->lang->words['redir__nomore_sql'];

			/* Show redirect... */
			$this->showRedirectScreen( $vars['app_directory'], $output, $errors, $this->getNextURL( 'next_check', $vars ) );
		}
	}
	
	/**
	 * Creates Tables, Runs Inserts, and Indexes
	 *
	 * @return	@e void
	 */
	public function sqlBasics()
	{
		/* INIT */
		$vars		= $this->getVars();
		$output		= array();
		$errors		= array();
		$skipped	= 0;
		$count		= 0;

		/* Any "extra" configs required for this driver? */
		if( is_file( IPS_ROOT_PATH . 'setup/sql/' . strtolower( $this->settings['sql_driver'] ) . '_install.php' ) )
		{
			require_once( IPS_ROOT_PATH . 'setup/sql/' . strtolower( $this->settings['sql_driver'] ) . '_install.php' );/*noLibHook*/

			$extra_install = new install_extra( $this->registry );
		}

		//-----------------------------------------
		// Tables
		//-----------------------------------------
		
		$this->DB->return_die = 1;

		if ( is_file( $this->app_full_path . 'setup/versions/install/sql/' . $vars['app_directory']. '_' . strtolower( ipsRegistry::dbFunctions()->getDriverType() ) . '_tables.php' ) )
		{
			$TABLE = array();
			include( $this->app_full_path . 'setup/versions/install/sql/' . $vars['app_directory']. '_' . strtolower( ipsRegistry::dbFunctions()->getDriverType() ) . '_tables.php' );/*noLibHook*/

			if ( is_array( $TABLE ) and count( $TABLE ) )
			{
				foreach( $TABLE as $q )
				{
					//-----------------------------------------
					// Is this a create?
					//-----------------------------------------
					
					preg_match("/CREATE TABLE\s+(\S+)(\s)?\(/", str_ireplace( 'if not exists', '', $q ), $match);

					if( $match[1] AND $vars['dupe_tables'] == 'drop' )
					{
						$this->DB->dropTable( str_replace( $this->settings['sql_tbl_prefix'], '', $match[1] ) );
					}
					else if( $match[1] )
					{
						if( $this->DB->getTableSchematic( $match[1] ) )
						{
							$skipped++;
							continue;
						}
					}
					
					//-----------------------------------------
					// Is this an alter?
					//-----------------------------------------
					
					preg_match("/ALTER\s+TABLE\s+(\S+)\s+ADD\s+(\S+)\s+/i", $q, $match);

					if( $match[1] AND $match[2] AND $vars['dupe_tables'] == 'drop' )
					{
						$this->DB->dropField( str_replace( $this->settings['sql_tbl_prefix'], '', $match[1] ), $match[2] );
					}
					else if( $match[1] AND $match[2] )
					{
						if( $this->DB->checkForField( $match[2], $match[1] ) )
						{
							$skipped++;
							continue;
						}
					}
		
					if ( $extra_install AND method_exists( $extra_install, 'process_query_create' ) )
					{
						 $q = $extra_install->process_query_create( $q );
					}
					$this->DB->error = '';
				
					$this->DB->query( $q );
					
					if ( $this->DB->error )
					{
						$errors[] = $q."<br /><br />".$this->DB->error;
					}
					else
					{
						$count++;
					}
				}
			}
			
			$output[] = sprintf( $this->lang->words['redir__sql_tables'], $count, $skipped );
		}
		
		//---------------------------------------------
		// Create the fulltext index...
		//---------------------------------------------

		if ( $this->DB->checkFulltextSupport() )
		{
			if ( is_file( $this->app_full_path . 'setup/versions/install/sql/' . $vars['app_directory']. '_' . strtolower( ipsRegistry::dbFunctions()->getDriverType() ) . '_fulltext.php' ) )
			{
				$INDEX = array();
				include( $this->app_full_path . 'setup/versions/install/sql/' . $vars['app_directory']. '_' . strtolower( ipsRegistry::dbFunctions()->getDriverType() ) . '_fulltext.php' );/*noLibHook*/
				
				$count	= 0;
	
				foreach( $INDEX as $q )
				{
					//---------------------------------------------
					// Pass to handler
					//---------------------------------------------
					
					if ( $extra_install AND method_exists( $extra_install, 'process_query_index' ) )
					{
						$q = $extra_install->process_query_index( $q );
					}
					
					//---------------------------------------------
					// Pass query
					//---------------------------------------------
					$this->DB->error = '';
					$this->DB->query( $q );
					
					if ( $this->DB->error )
					{
						$errors[] = $q."<br /><br />".$this->DB->error;
					}
					else
					{
						$count++;
					}
				}
				
				$output[] = sprintf( $this->lang->words['redir__sql_indexes'], $count );
			}
		}
		
		/* INSERTS */
		if ( is_file( $this->app_full_path . 'setup/versions/install/sql/' . $vars['app_directory']. '_' . strtolower( ipsRegistry::dbFunctions()->getDriverType() ) . '_inserts.php' ) )
		{
			$INSERT = array();
			$count  = 0;
			
			/* Get the SQL File */
			include( $this->app_full_path . 'setup/versions/install/sql/' . $vars['app_directory']. '_' . strtolower( ipsRegistry::dbFunctions()->getDriverType() ) . '_inserts.php' );/*noLibHook*/
			
			foreach( $INSERT as $q )
			{
				/* Extra Handler */
			 	if( $extra_install AND method_exists( $extra_install, 'process_query_insert' ) )
			 	{
					$q = $extra_install->process_query_insert( $q );
				}
				
				$q = str_replace( "<%time%>", time(), $q );
				$this->DB->error = '';
				$this->DB->query( $q );
				
				if ( $this->DB->error )
				{
					$errors[] = $q."<br /><br />".$this->DB->error;
				}
				else
				{
					$count++;
				}
			}
			
			$output[] = sprintf( $this->lang->words['redir__sql_inserts'], $count );
		}
		
		/* If we did no queries, show something here */
		if ( !count( $output ) )
		{
			$output[] = sprintf( $this->lang->words['redir__sql_run'], 0 );
		}

		/* Show Redirect... */
		$this->showRedirectScreen( $vars['app_directory'], $output, $errors, $this->getNextURL( 'sql_steps', $vars ) );
	}
	
	/**
	 * Begin installation
	 *
	 * @return	@e void
	 */
	public function start()
	{
		/* INIT */
		$app_directory = IPSText::alphanumericalClean( $this->request['app_directory'] );
		$type          = 'upgrade';
		$data          = array();
		$ok            = 1;
		$errors        = array();
		$localfiles    = array( DOC_IPS_ROOT_PATH . 'cache/skin_cache' );
		$info          = array();
		
		/* Init Data */
		$data      = IPSSetUp::fetchXmlAppInformation( $app_directory, $this->settings['gb_char_set'] );
		$_numbers  = IPSSetUp::fetchAppVersionNumbers( $app_directory );
		$_files    = IPSSetUp::fetchXmlAppWriteableFiles( $app_directory );
		
		/* Grab Data */
		$data['app_directory']   = $app_directory;
		$data['current_version'] = ( $_numbers['current'][0] ) ? $_numbers['current'][0] : $this->lang->words['cur_version_none'];
		$data['latest_version']  = $_numbers['latest'][1];
		$data['next_version']    = $_numbers['next'][0];
		
		/* Install, or upgrade? */
		if ( ! $_numbers['current'][0] )
		{
			$type = 'install';
		}
		
		//-----------------------------------------
		// For upgrade, redirect
		//-----------------------------------------
		
		else
		{
			@header( "Location: {$this->settings['board_url']}/" . CP_DIRECTORY . "/upgrade/" );
			exit;
		}
		
		/* Version Check */
		if( $data['current_version'] > 0 AND $data['current_version'] == $data['latest_version'] )
		{
			$this->registry->output->global_message = $this->lang->words['error__up_to_date'];
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] );
			return;
		}
		
		/* Check local files */
		foreach( $localfiles as $_path )
		{
			if ( ! file_exists( $_path ) )
			{
				if ( $data['dir'] )
				{
					if ( ! @mkdir( $_path, IPS_FOLDER_PERMISSION, TRUE ) )
					{
						$info['notexist'][] = $_path;
					}
				}
				else
				{
					$info['notexist'][] = $_path;
				}
			}
			else if ( ! is_writeable( $_path ) )
			{
				if ( ! @chmod( $_path, is_dir( $_path ) ? IPS_FOLDER_PERMISSION : IPS_FILE_PERMISSION ) )
				{
					$info['notwrite'][] = $_path;
				}
			}
		}
		
		/* Check files... */
		if( is_array( $_files ) AND count( $_files ) )
		{
			$info = array_merge( $info, $_files );
		}
		
 		if ( count( $info['notexist'] ) )
		{
			foreach( $info['notexist'] as $path )
			{
				$errors[] = sprintf( $this->lang->words['error__file_missing'], $path );
			}
		}
		
		if ( count( $info['notwrite'] ) )
		{
			foreach( $info['notwrite'] as $path )
			{
				$errors[] = sprintf( $this->lang->words['error__file_chmod'], $path );
			}
		}
		
		/**
		 * Custom errors
		 */
		if ( count( $info['other'] ) )
		{
			foreach( $info['other'] as $error )
			{
				$errors[]	= $error;
			}
		}
		
		/* Check for xml files */
		$required_xml = array( 
								"information",
								//"{$app_directory}_modules",
								//"{$app_directory}_settings",
								//"{$app_directory}_tasks",
								//"{$app_directory}_templates", 
							);

		foreach( $required_xml as $r )
		{
			if( ! is_file( $this->app_full_path . "xml/{$r}.xml" ) )
			{
				$errors[] = sprintf( $this->lang->words['error__file_needed'], $this->app_full_path . "xml/{$r}.xml" );
			}
		}

		/* Show splash */
		$this->registry->output->html .= $this->html->setup_splash_screen( $data, $errors, $type );
	}
	
	/**
	 * Get environment vars
	 *
	 * @return	array
	 */
	protected function getVars()
	{
		/* INIT */
		$env = array();
		
		/* Get the infos */
		$env['type']			= strtolower( $this->request['type'] );
		$env['version']			= $this->request['version'];
		$env['dupe_tables']		= $this->request['dupe_tables'];
		$env['app_directory']	= $this->request['app_directory'];
		
		$env['app_location']	= 'other';
		
		if( $this->product_information['ipskey'] )
		{
			if ( strstr( $this->app_full_path, 'applications_addon/ips' ) or strstr( $this->app_full_path, 'applications/' ) )
			{
				if ( md5( 'ips_' . basename($this->app_full_path) ) == $this->product_information['ipskey'] )
				{
					$env['app_location']	= 'ips';
				}
			}
		}
		
		$env['path'] = ( $env['type'] == 'install' ) ? $this->app_full_path . 'setup/versions/install'
											         : $this->app_full_path . 'setup/versions/' . $env['version'];

		return $env;
	}
	
	/**
	 * Get next action URL
	 *
	 * @param	string	$next_action
	 * @param	array	$env
	 * @return	string
	 */
	protected function getNextURL( $next_action, $env )
	{
		return $this->settings['base_url'] . $this->form_code . '&amp;do=' . $next_action . '&amp;app_directory=' . $env['app_directory'] . '&amp;type=' . $env['type'] . '&amp;version=' . $env['version'];
	}
	
	/**
	 * Show the redirect screen
	 *
	 * @param	string	$app_directory
	 * @param	string	$output
	 * @param	string	$errors
	 * @param	string	$next_url
	 * @return	@e void
	 */
	protected function showRedirectScreen( $app_directory, $output, $errors, $next_url )
	{
		/* Init Data */
		$data		= IPSSetUp::fetchXmlAppInformation( $app_directory, $this->settings['gb_char_set'] );
		$_numbers	= IPSSetUp::fetchAppVersionNumbers( $app_directory );
		
		/* Grab Data */
		$data['app_directory']   = $app_directory;
		$data['current_version'] = ( $_numbers['current'][0] ) ? $_numbers['current'][0] : $this->lang->words['cur_version_none'];
		$data['latest_version']  = $_numbers['latest'][1];
		$data['next_version']    = $_numbers['next'][0];
			
		/* Setup Redirect */
		$this->registry->output->html .= $this->html->setup_redirectScreen( $output, $errors, $next_url );
	}
}