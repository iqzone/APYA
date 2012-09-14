<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Dashboard
 * Last Updated: $Date: 2012-05-24 16:20:59 -0400 (Thu, 24 May 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		5th January 2005
 * @version		$Revision: 10792 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_mycp_dashboard extends ipsCommand
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

		$this->html = $this->registry->output->loadTemplate('cp_skin_mycp');

		//-----------------------------------------
		// Load language
		//-----------------------------------------

		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_mycp' ) );

		/* This is a little hacky, but we have to allow access to the whole module to get access to 
		   'change my details'.  This check just makes sure that we don't also get access to the Dashboard
		   if the permission system automatically added permission for 'change my details' */
		if( $this->registry->getClass('class_permissions')->editDetailsOnly )
		{
			/* If they just don't have access to the dashboard, let's show them something we do have access to */
			if ( !$this->request['app'] )
			{
				foreach( ipsRegistry::$applications as $k => $data )
				{
					if ( $this->registry->getClass('class_permissions')->checkForAppAccess( $k ) and ( $k != 'core' or !$this->registry->getClass('class_permissions')->editDetailsOnly ) )
					{
						foreach( ipsRegistry::$modules[ $k ] as $module )
						{
							if ( $this->registry->getClass('class_permissions')->checkForModuleAccess( $k, $module['sys_module_key'] ) )
							{
								$filepath  = IPSLib::getAppDir( $k ) . '/modules_admin/' . $module['sys_module_key'] . '/defaultSection.php';
								if ( is_file( $filepath ) )
								{
									$DEFAULT_SECTION = '';
									include( $filepath );/*noLibHook*/
					
									if ( $this->registry->getClass('class_permissions')->checkForSectionAccess( $k, $module['sys_module_key'], $DEFAULT_SECTION ) )
									{
										$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . "app={$k}&amp;module={$module['sys_module_key']}&amp;section={$DEFAULT_SECTION}" );
									}
								}
							}
						}
					}
				}
			}
			
			/* If all else fails, take them to the change details page */
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . "core&amp;module=mycp&amp;section=details" );
		}
		else if( !$this->registry->getClass('class_permissions')->checkPermission( 'dashboard', 'core', 'mycp' ) )
		{
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . "core&amp;module=mycp&amp;section=details" );
		}

		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------

		$this->form_code	= $this->html->form_code	= 'module=mycp&amp;section=dashboard';
		$this->form_code_js	= $this->html->form_code_js	= 'module=mycp&section=dashboard';
		
		//-----------------------------------------
		// Hang on, do we need the upgrader?
		//-----------------------------------------
		
		if ( !IN_DEV AND ( !defined('SKIP_UPGRADE_CHECK') OR !SKIP_UPGRADE_CHECK ) )
		{
			require_once( IPS_ROOT_PATH . 'setup/sources/base/setup.php' );/*noLibHook*/
			foreach( ipsRegistry::$applications as $app_dir => $app )
			{
				$_a = ( $app_dir == 'forums' or $app_dir == 'members' ) ? 'core' : $app_dir;
				$numbers = IPSSetUp::fetchAppVersionNumbers( $_a );
	
				if ( $numbers['latest'][0] and $numbers['latest'][0] > $numbers['current'][0] )
				{
					$this->registry->output->silentRedirect( $this->settings['base_acp_url'] . '/upgrade/index.php?_acpRedirect=1' );
					return;
				}
			}
		}
		
		//-----------------------------------------
		// Get external data
		//-----------------------------------------
		
		$content         	= array();
		$thiscontent     	= "";
   		$latest_version  	= array();
   		$reg_end         	= "";
		$unfinished_upgrade	= 0;
				
		$ipsNewsData = $this->cache->getCache( 'ipsNewsData' );
		if ( !isset( $ipsNewsData['time'] ) or $ipsNewsData['time'] < ( time() - 43200 ) ) // 12 hour cache
		{
			$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classFileManagement.php', 'classFileManagement' );
			$classFileManagement = new $classToLoad();
			
			if( strpos( $this->settings['base_url'], 'https://' ) !== false )
			{
				$ipsNewsData['news'] = $classFileManagement->getFileContents( 'https://external.ipslink.com/ipboard33/dashboard/index.php?v=' . ipsRegistry::$vn_full );
				$ipsNewsData['vcheck'] = $classFileManagement->getFileContents( 'https://www.invisionpower.com/latestversioncheck/ipb30x.php?' . base64_encode( ipsRegistry::$vn_full.'|^|'.$this->settings['board_url'] ) );
			}
			else
			{
				$ipsNewsData['news'] = $classFileManagement->getFileContents( 'http://external.ipslink.com/ipboard33/dashboard/index.php?v=' . ipsRegistry::$vn_full );
				$ipsNewsData['vcheck'] = $classFileManagement->getFileContents( 'http://www.invisionpower.com/latestversioncheck/ipb30x.php?' . base64_encode( ipsRegistry::$vn_full.'|^|'.$this->settings['board_url'] ) );
			}
			
			$ipsNewsData['time'] = time();
			
			$this->cache->setCache( 'ipsNewsData', $ipsNewsData, array( 'array' => 1 ) );
		}

		//-----------------------------------------
		// Get MySQL & PHP Version
		//-----------------------------------------

		$this->DB->getSqlVersion();

   		//-----------------------------------------
   		// Upgrade history?
   		//-----------------------------------------

   		$latest_version = array( 'upgrade_version_id' => NULL );

   		$this->DB->build( array( 'select' => '*', 'from' => 'upgrade_history', 'order' => 'upgrade_version_id DESC', 'limit' => array(1) ) );
   		$this->DB->execute();

   		while( $r = $this->DB->fetch() )
   		{
			$latest_version = $r;
   		}

		//-----------------------------------------
		// Resetting security image?
		//-----------------------------------------

		if ( $this->request['reset_security_flag'] AND $this->request['reset_security_flag'] == 1 AND $this->request['new_build'] )
		{
			$_latest	 = IPSLib::fetchVersionNumber('core');
			$new_build   = intval( $this->request['new_build'] );
			$new_reason  = trim( substr( $this->request['new_reason'], 0, 1 ) );
			$new_version = $_latest['long'].'.'.$new_build.'.'.$new_reason;

			$this->DB->update( 'upgrade_history', array( 'upgrade_notes' => $new_version ), 'upgrade_version_id='.$latest_version['upgrade_version_id'] );

			$latest_version['upgrade_notes'] = $new_version;
		}

		//-----------------------------------------
		// Got real version number?
		//-----------------------------------------

		ipsRegistry::$version = 'v'.$latest_version['upgrade_version_human'];
		ipsRegistry::$vn_full = !empty($latest_version['upgrade_notes']) ? $latest_version['upgrade_notes'] : ipsRegistry::$vn_full;

		//-----------------------------------------
		// Notepad
		//-----------------------------------------

		if ( $this->request['save'] AND $this->request['save'] == 1 )
		{
			$_POST['notes'] = $_POST['notes'] ? $_POST['notes'] : $this->lang->words['cp_acpnotes'];
			$this->cache->setCache( 'adminnotes', IPSText::stripslashes($_POST['notes']), array( 'donow' => 1, 'array' => 0 ) );
		}

		$text = $this->lang->words['cp_acpnotes'];

		if ( !$this->cache->getCache('adminnotes') )
		{
			$this->cache->setCache( 'adminnotes', $text, array( 'donow' => 1, 'array' => 0 ) );
		}

		$this->cache->updateCacheWithoutSaving( 'adminnotes', htmlspecialchars($this->cache->getCache('adminnotes'), ENT_QUOTES) );
		$this->cache->updateCacheWithoutSaving( 'adminnotes', str_replace( "&amp;#", "&#", $this->cache->getCache('adminnotes') ) );

		$content['ad_notes'] = $this->html->acp_notes( $this->cache->getCache('adminnotes') );

		//-----------------------------------------
		// ADMINS USING CP
		//-----------------------------------------

		$t_time    = time() - 60*10;
		$time_now  = time();
		$seen_name = array();
		$acponline = "";

		$this->DB->build( array(
								'select'   => 's.session_member_name, s.session_member_id, s.session_location, s.session_log_in_time, s.session_running_time, s.session_ip_address, s.session_url',
								'from'     => array( 'core_sys_cp_sessions' => 's' ),
								'add_join' => array( array(  'select' => 'm.*',
															'from'   => array( 'members' => 'm' ),
															'where'  => "m.member_id=s.session_member_id",
															'type'   => 'left'
														),
													 array(  'select' => 'pp.*',
															'from'   => array( 'profile_portal' => 'pp' ),
															'where'  => 'pp.pp_member_id=m.member_id',
															'type'   => 'left'
														)  )  )	);

		$q = $this->DB->execute();

		while ( $r = $this->DB->fetch( $q ) )
		{
			if ( isset($seen_name[ $r['session_member_name'] ]) AND $seen_name[ $r['session_member_name'] ] == 1 )
			{
				continue;
			}
			else
			{
				$seen_name[ $r['session_member_name'] ] = 1;
			}

			$r['_log_in'] = $time_now - $r['session_log_in_time'];
			$r['_click']  = $time_now - $r['session_running_time'];

			if ( ($r['_log_in'] / 60) < 1 )
			{
				$r['_log_in'] = sprintf("%0d", $r['_log_in']) . ' ' . $this->lang->words['cp_secondsago'];
			}
			else
			{
				$r['_log_in'] = sprintf("%0d", ($r['_log_in'] / 60) ) . ' ' . $this->lang->words['cp_minutesago'];
			}

			if ( ($r['_click'] / 60) < 1 )
			{
				$r['_click'] = sprintf("%0d", $r['_click']) . ' ' . $this->lang->words['cp_secondsago'];
			}
			else
			{
				$r['_click'] = sprintf("%0d", ($r['_click'] / 60) ) . ' ' . $this->lang->words['cp_minutesago'];
			}

			$r['session_location'] = $r['session_location'] ? $r['session_location'] : $this->lang->words['cp_index'];
			
			$r['seo_link'] = $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?showuser=' . $r['session_member_id'], 'none', $r['members_seo_name'], 'showuser' );
				
			$admins_online[] = $r;
		}

		$content['acp_online'] = $this->html->acp_onlineadmin_wrapper( $admins_online );

		//-----------------------------------------
		// Members awaiting admin validation?
		//-----------------------------------------

		if( $this->settings['reg_auth_type'] == 'admin_user' OR $this->settings['reg_auth_type'] == 'admin' )
		{
			$where_extra = $this->settings['reg_auth_type'] == 'admin_user' ? ' AND user_verified=1' : '';

			$admin_reg	= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as reg'  , 'from' => 'validating', 'where' => 'new_reg=1' . $where_extra ) );

			if( $admin_reg['reg'] > 0 )
			{
				// We have some member's awaiting admin validation
				$data = null;

				$this->DB->build( array(
											'select' 	=> 'v.*',
											'from'		=> array( 'validating' => 'v' ),
											'where'	=> 'new_reg=1' . $where_extra,
											'limit'	=> array( 3 ),
											'add_join'	=> array(
											 					array(
																		'type'		=> 'left',
														 				'select'	=> 'm.members_display_name, m.email, m.ip_address',
														 				'from'		=> array( 'members' => 'm' ),
														 				'where'		=> 'm.member_id=v.member_id'
														 			)
														 		)
								)	);
				$this->DB->execute();

				while( $r = $this->DB->fetch() )
				{
					if ($r['coppa_user'] == 1)
					{
						$r['_coppa'] = ' ( COPPA )';
					}
					else
					{
						$r['_coppa'] = "";
					}

					$r['_entry']  = $this->registry->getClass( 'class_localization')->getDate( $r['entry_date'], 'TINY' );

					$data .= $this->html->acp_validating_block( $r );


				}

				$content['validating'] = $this->html->acp_validating_wrapper( $data );
			}
		}
		
		//-----------------------------------------
		// Info for the stats bar
		//-----------------------------------------

		$stats	= array(
						'performance'	=> false,
						'active_users'	=> 0,
						'server_load'	=> 0,
						);
		
		$record = $this->cache->getCache('performanceCache');

		if( is_array($record) AND count($record) )
		{
		     $stats['performance']	= true;
		}
		
		list( $load, $time )	= explode( '-', $this->caches['systemvars']['loadlimit'] );
		
		$time	= time() - ( $this->settings['au_cutoff'] * 60 );
		$online	= $this->DB->buildAndFetch( array( 'select' => 'count(*) as rows', 'from' => 'sessions', 'where' => "running_time > {$time}" ) );
		
		$stats['active_users']	= $online['rows'];
		$stats['server_load']	= $load;

		//-----------------------------------------
		// Piece it together
		//-----------------------------------------

		$this->registry->output->html .= $this->html->mainTemplate( $content, $ipsNewsData, $this->getNotificationPanelEntries(), $stats );

		//-----------------------------------------
		// Left log all on?
		//-----------------------------------------

		if ( IPS_LOG_ALL === TRUE )
		{
			$_html = $this->html->warning_box( $this->lang->words['ds_log_all_title'], $this->lang->words['ds_log_all_desc'] ) . "<br />";
			$this->registry->output->html = str_replace( '<!--in_dev_check-->', $_html . '<!--in_dev_check-->', $this->registry->output->html );
		}

		//-----------------------------------------
		// IN DEV stuff...
		//-----------------------------------------

		if ( IN_DEV )
		{
			$lastUpdate     = $this->caches['indev'];
			$lastUpdate     = ( is_array( $lastUpdate ) ) ? $lastUpdate : array( 'import' => array( 'settings' => array() ) );
			$lastModUpdate  = ( is_array( $lastUpdate ) ) ? $lastUpdate : array( 'import' => array( 'modules'  => array() ) );
			$lastTaskUpdate = ( is_array( $lastUpdate ) ) ? $lastUpdate : array( 'import' => array( 'tasks'    => array() ) );
			$lastHelpUpdate = ( is_array( $lastUpdate ) ) ? $lastUpdate : array( 'import' => array( 'help'     => array() ) );
			$lastbbUpdate   = ( is_array( $lastUpdate ) ) ? $lastUpdate : array( 'import' => array( 'bbcode'   => array() ) );
			$content        = array();
			$modContent     = array();
			$tasksContent	= array();
			$helpContent    = array();
			$bbContent      = array();
						$_html          = '';

			foreach( ipsRegistry::$applications as $app_dir => $data )
			{
				/* Settings */
				$lastMtime  = intval( @filemtime( IPSLib::getAppDir( $app_dir ) . '/xml/' . $app_dir . '_settings.xml' ) );
				$lastDBtime = intval( $lastUpdate['import']['settings'][ $app_dir ] );

				if ( $lastMtime > $lastDBtime )
				{
					$_mtime  = $this->registry->getClass( 'class_localization')->getDate( $lastMtime , 'JOINED' );
					$_dbtime = $this->registry->getClass( 'class_localization')->getDate( $lastDBtime, 'JOINED' );

					$content[] = "<strong>" . $data['app_title'] . " {$this->lang->words['cp_settingsupdated']}.</strong><br />-- {$this->lang->words['cp_lastimportrun']}: {$_dbtime}<br />-- {$this->lang->words['cp_lastxmlexport']}: {$_mtime}";
				}

				/* Modules */
				$lastMtime  = intval( @filemtime( IPSLib::getAppDir( $app_dir ) . '/xml/' . $app_dir . '_modules.xml' ) );
				$lastDBtime = intval( $lastUpdate['import']['modules'][ $app_dir ] );

				if ( $lastMtime > $lastDBtime )
				{
					$_mtime  = $this->registry->getClass( 'class_localization')->getDate( $lastMtime , 'JOINED' );
					$_dbtime = $this->registry->getClass( 'class_localization')->getDate( $lastDBtime, 'JOINED' );

					$modContent[] = "<strong>" . $data['app_title'] . " {$this->lang->words['cp_modulessneedup']}.</strong><br />-- {$this->lang->words['cp_lastimportrun']}: {$_dbtime}<br />-- {$this->lang->words['cp_lastxmlexport']}: {$_mtime}";
				}

				/* Tasks */
				$lastMtime  = intval( @filemtime( IPSLib::getAppDir( $app_dir ) . '/xml/' . $app_dir . '_tasks.xml' ) );
				$lastDBtime = intval( $lastUpdate['import']['tasks'][ $app_dir ] );

				if ( $lastMtime > $lastDBtime )
				{
					$_mtime  = $this->registry->getClass( 'class_localization')->getDate( $lastMtime , 'JOINED' );
					$_dbtime = $this->registry->getClass( 'class_localization')->getDate( $lastDBtime, 'JOINED' );

					$tasksContent[] = "<strong>" . $data['app_title'] . " {$this->lang->words['cp_taskssneedup']}.</strong><br />-- {$this->lang->words['cp_lastimportrun']}: {$_dbtime}<br />-- {$this->lang->words['cp_lastxmlexport']}: {$_mtime}";
				}

				/* Help Files */
				$lastMtime  = intval( @filemtime( IPSLib::getAppDir( $app_dir ) . '/xml/' . $app_dir . '_help.xml' ) );
				$lastDBtime = intval( $lastUpdate['import']['help'][ $app_dir ] );

				if ( $lastMtime > $lastDBtime )
				{
					$_mtime  = $this->registry->getClass( 'class_localization')->getDate( $lastMtime , 'JOINED' );
					$_dbtime = $this->registry->getClass( 'class_localization')->getDate( $lastDBtime, 'JOINED' );

					$helpContent[] = "<strong>" . $data['app_title'] . " {$this->lang->words['cp_helpneedup']}.</strong><br />-- {$this->lang->words['cp_lastimportrun']}: {$_dbtime}<br />-- {$this->lang->words['cp_lastxmlexport']}: {$_mtime}";
				}

				/* BBCode Files */
				$lastMtime  = intval( @filemtime( IPSLib::getAppDir( $app_dir ) . '/xml/' . $app_dir . '_bbcode.xml' ) );
				$lastDBtime = intval( $lastUpdate['import']['bbcode'][ $app_dir ] );

				if ( $lastMtime > $lastDBtime )
				{
					$_mtime  = $this->registry->getClass( 'class_localization')->getDate( $lastMtime , 'JOINED' );
					$_dbtime = $this->registry->getClass( 'class_localization')->getDate( $lastDBtime, 'JOINED' );

					$bbContent[] = "<strong>" . $data['app_title'] . " {$this->lang->words['cp_bbcodeneedup']}.</strong><br />-- {$this->lang->words['cp_lastimportrun']}: {$_dbtime}<br />-- {$this->lang->words['cp_lastxmlexport']}: {$_mtime}";
				}
			}

			if ( count( $content ) )
			{
				$_html = $this->html->warning_box( $this->lang->words['cp_settingsneedup'], implode( $content, "<br />" ) . "<br /><a href='" . $this->settings['base_url'] . "app=core&amp;module=settings&amp;section=settings&amp;do=settingsImportApps'>{$this->lang->words['cp_clickhere']}</a> {$this->lang->words['cp_clickhere_info']}.");
			}

			if ( count( $modContent ) )
			{
				$_html .= $this->html->warning_box( $this->lang->words['cp_modulessneedup'], implode( $modContent, "<br />" ) . "<br /><a href='" . $this->settings['base_url'] . "app=core&amp;module=applications&amp;section=applications&amp;do=inDevRebuildAll'>{$this->lang->words['cp_clickhere']}</a> {$this->lang->words['cp_clickhere_info']}.");
			}

			if ( count( $tasksContent ) )
			{
				$_html .= $this->html->warning_box( $this->lang->words['cp_taskssneedup'], implode( $tasksContent, "<br />" ) . "<br /><a href='" . $this->settings['base_url'] . "app=core&amp;module=system&amp;section=taskmanager&amp;do=tasksImportAllApps'>{$this->lang->words['cp_clickhere']}</a> {$this->lang->words['cp_clickhere_info']}.");
			}

			if ( count( $helpContent ) )
			{
				$_html .= $this->html->warning_box( $this->lang->words['cp_helpneedup'], implode( $helpContent, "<br />" ) . "<br /><a href='" . $this->settings['base_url'] . "app=core&amp;module=tools&amp;section=help&amp;do=importXml'>{$this->lang->words['cp_clickhere']}</a> {$this->lang->words['cp_clickhere_info']}.");
			}

			if ( count( $bbContent ) )
			{
				$_html .= $this->html->warning_box( $this->lang->words['cp_bbcodeneedup'], implode( $bbContent, "<br />" ) . "<br /><a href='" . $this->settings['base_url'] . "app=core&amp;module=posts&amp;section=bbcode&amp;do=bbcode_import_all'>{$this->lang->words['cp_clickhere']}</a> {$this->lang->words['cp_clickhere_info']}.");
			}

			$this->registry->output->html = str_replace( '<!--in_dev_check-->', $_html, $this->registry->output->html );
			
			/* Got notes!? */
			if ( is_file( DOC_IPS_ROOT_PATH . '_dev_notes.txt' ) )
			{
				/* file retains tabs, file_get_contents not! */
				$_notes = file( DOC_IPS_ROOT_PATH . '_dev_notes.txt' );
				
				if ( $_notes )
				{
					/* sanitize data and convert tabs! */
					//$_notes = array_map( 'htmlentities', $_notes );
					$_notes = implode( '', $_notes );
					$_notes = str_replace( "\t", '&nbsp;&nbsp;&nbsp;&nbsp;', $_notes );
					
					$_html = $this->registry->output->global_template->warning_box( $this->lang->words['cp_devnotes'], nl2br($_notes) ) . "<br />";
					$this->registry->output->html = str_replace( '<!--in_dev_notes-->', $_html, $this->registry->output->html );
				}
			}
		}

		//-----------------------------------------
		// Last 5 log in attempts
		//-----------------------------------------

		$this->registry->getClass('class_permissions')->return	= true;

		if( $this->registry->getClass('class_permissions')->checkPermission( 'acplogin_log', 'core', 'system' ) )
		{
			$this->DB->build( array(
										'select' => '*',
										'from'   => 'admin_login_logs',
										'where'	 => 'admin_success = 0 AND admin_time > 0',		// This just helps mysql use the index properly, which is useful for sorting
										'order'  => 'admin_time DESC',
										'limit'  => array( 0, 4 )
							)	);

			$this->DB->execute();

			while ( $rowb = $this->DB->fetch() )
			{
				$rowb['_admin_time'] = $this->registry->class_localization->getDate( $rowb['admin_time'], 'long' );

				$logins .= $this->html->acp_last_logins_row( $rowb );
			}
			
			$this->registry->output->html = str_replace( '<!--acplogins-->', $this->html->acp_last_logins_wrapper( $logins ), $this->registry->output->html );
		}

		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------

		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Builds a list of nag panel entries
	 *
	 * @return	array
	 */
	public function getNotificationPanelEntries()
	{
		/* INIT */
		$entries = array();
		
		/* Look for notification classes */
		foreach( IPSLib::getEnabledApplications() as $r )
		{
			/* Notification Class */
			$_file	= IPSLib::getAppDir( $r['app_directory'] ) . '/extensions/dashboardNotifications.php';
			
			/* Look for the file */
			if( is_file( $_file ) )
			{
				/* Get the file */
				$_class = IPSLib::loadLibrary( $_file, 'dashboardNotifications__' . $r['app_directory'], $r['app_directory'] );
				
				/* Look for the class */
				if( class_exists( $_class ) )
				{
					/* Create the object */
					$notifyObj = new $_class;
					
					/* Look for the method */
					if( method_exists( $notifyObj, 'get' ) )
					{
						/* Get the entries */
						$_entries = $notifyObj->get();
						
						if( is_array( $_entries ) && count( $_entries ) )
						{
							$entries = array_merge( $entries, $_entries );
						}
					}
				}
			}
		}

		/* Return entries */
		return $entries;
	}
}