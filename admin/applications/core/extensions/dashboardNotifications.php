<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Dashboard Notifications
 * Last Updated: $Date
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
* Main loader class
*/
class dashboardNotifications__core
{
	/**
	 * Registry Object Shortcuts
	 *
	 * @var		object
	 */
	public $settings;
	public $lang;
	public $cache;
	public $caches;
	public $DB;
	
	public function __construct()
	{
		$this->settings	= ipsRegistry::fetchSettings();
		$this->lang		= ipsRegistry::getClass('class_localization');
		$this->caches	=& ipsRegistry::cache()->fetchCaches();
		$this->cache	= ipsRegistry::cache();
		$this->DB		= ipsRegistry::DB();
	}
	
	public function get()
	{
		/* INIT */
		$entries = array();
		
		if( ! $this->settings['ipb_reg_number'] )
		{
			$entries[] = array( $this->lang->words['lc_title_nokey'], sprintf( $this->lang->words['lc_msg_nokey'], "{$this->settings['base_url']}module=tools&amp;section=licensekey" ) );
		}
		else
		{
			/* Is the Spam Service Working? */
			if ( $this->settings['spam_service_enabled'] )
			{
				$GOT_SPAM_ERROR = false;
				
				/* Are we entitled to it? */
				$licenseData = $this->cache->getCache( 'licenseData' );
				
				if ( is_array( $licenseData['ipbMain'] ) && count( $licenseData['ipbMain'] ) )
				{
					foreach ( $licenseData['ipbMain'] as $data )
					{
						if ( $data['name'] == 'Spam Monitoring Service' && $data['status'] != 'Ok' )
						{
							$disableLink = $this->settings['base_url'] . "app=core&amp;module=settings&amp;section=settings&amp;do=findsetting&amp;key=spamservice";
							
							if ( is_numeric( $data['_expires'] ) && time() > $data['_expires'] )
							{
								$entries[] = array( $this->lang->words['spam_service_error'], sprintf( $this->lang->words['spam_service_expired'], $disableLink ) );
							}
							else
							{
								$entries[] = array( $this->lang->words['spam_service_error'], sprintf( $this->lang->words['spam_service_unavailable'], $disableLink ) );
							}
							
							$GOT_SPAM_ERROR = true;
							
							break;
						}
					}
				}
				
				if ( ! $GOT_SPAM_ERROR )
				{
					/* Get last 5 logs, and if all 5 are errors, show message */
					$_errors	= 0;
					$_entries	= 0;
					$_lastError	= '';
					
					$this->DB->build( array( 'select' => 'log_code, log_msg', 'from' => 'spam_service_log', 'order' => 'id DESC', 'limit' => 5 ) );
					$this->DB->execute();
					
					while( $_r = $this->DB->fetch() )
					{
						$_entries++;
						
						if( $_r['log_code'] === '0' )
						{
							$_errors++;
							
							if( !$_lastError )
							{
								$_lastError	= $_r['log_msg'];
							}
						}
					}
					
					if( $_entries > 0 && $_errors == $_entries )
					{
						$entries[] = array( $this->lang->words['spam_service_error'], sprintf( $this->lang->words['spam_service_error_msg'], $_lastError ) );
					}
				}
			}
			/* If it's disabled, are we entitiled to it? */
			else
			{
				$licenseData = $this->cache->getCache( 'licenseData' );
				
				if ( is_array( $licenseData['ipbMain'] ) && count( $licenseData['ipbMain'] ) )
				{
					foreach ( $licenseData['ipbMain'] as $data )
					{
						if ( $data['name'] == 'Spam Monitoring Service' && $data['status'] == 'Ok' )
						{
							$entries[] = array( $this->lang->words['spam_service_disabled'], $this->lang->words['spam_service_disabled_msg'] );
							break;
						}
					}
				}
			}
		}
		
		/* FURL cache OOD? */
		if ( is_file( IPS_CACHE_PATH . 'cache/furlCache.php' ) )
		{
			$mtime = intval( @filemtime( IPS_CACHE_PATH . 'cache/furlCache.php' ) );

			/* Check mtimes on extensions.. */
			foreach( ipsRegistry::$applications as $app_dir => $application )
			{
				if ( is_file( IPSLib::getAppDir( $app_dir ) . '/extensions/furlTemplates.php' ) )
				{
					$_mtime = intval( @filemtime( IPSLib::getAppDir( $app_dir ) . '/extensions/furlTemplates.php' ) );

					if ( $_mtime > $mtime )
					{
						$entries[] = array( $this->lang->words['furlcache_outofdate'], sprintf( $this->lang->words['furlcache_outofdate_desc'], $application['app_title'] ) . "<a href='" . $this->settings['base_url'] . "app=core&amp;module=applications&amp;section=applications&amp;do=seoRebuild'>{$this->lang->words['rebuild_furl_cache']}</a>" );
						break;
					}
				}
			}
		}
		
		/* Sphinx cache OOD? */
		if ( $this->settings['search_method'] == 'sphinx' )
		{
			$mtime = intval( $this->cache->getCache('sphinx_config') );

			/* Check mtimes on extensions.. */
			foreach( ipsRegistry::$applications as $app_dir => $application )
			{
				if ( is_file( IPSLib::getAppDir( $app_dir ) . '/extensions/sphinxTemplate.php' ) )
				{
					$_mtime = intval( @filemtime( IPSLib::getAppDir( $app_dir ) . '/extensions/sphinxTemplate.php' ) );

					if ( !$mtime OR $_mtime > $mtime )
					{
						$entries[] = array( $this->lang->words['sphinxconfig_outofdate'], $this->lang->words['sphinxconfig_outofdate_desc'] . ' ' . "<a href='" . $this->settings['base_url'] . "app=core&amp;module=applications&amp;section=applications&amp;do=applications_overview'>{$this->lang->words['rebuild_sphinx_config']}</a>" );
						break;
					}
				}
			}
		}
		
		/* Minify on but /cache/tmp not writeable? */
		if ( !empty( $this->settings['_use_minify'] ) )
		{
			$entries[] = array( $this->lang->words['minifywrite_head'], $this->lang->words['minifynot_writeable'] );
		}
		
		/* Installer Check */
		if( @is_file( IPS_ROOT_PATH . 'install/index.php' ) )
		{
			if ( ! @is_file( DOC_IPS_ROOT_PATH . 'cache/installer_lock.php' ) )
			{
				$this->lang->words['cp_unlocked_warning'] = sprintf( $this->lang->words['cp_unlocked_warning'], CP_DIRECTORY );
				$entries[] = array( $this->lang->words['cp_unlockedinstaller'], $this->lang->words['cp_unlocked_warning'] );
			}
		}

		/* Unfinished Upgrade */
		require_once( IPS_ROOT_PATH . '/setup/sources/base/setup.php' );/*noLibHook*/
		$versions	= IPSSetUp::fetchAppVersionNumbers( 'core' );

		if( $versions['current'][0] != $versions['latest'][0] )
		{
			$this->lang->words['cp_upgrade_warning'] = sprintf( $this->lang->words['cp_upgrade_warning'], $versions['current'][1], $versions['latest'][1], $this->settings['base_acp_url'] );

			$entries[] = array( $this->lang->words['cp_unfinishedupgrade'], $this->lang->words['cp_upgrade_warning'] );
		}
		
		/* PHP Version Check */
		if( PHP_VERSION < '5.1.0' )
		{
			$entries[] = array( sprintf( $this->lang->words['cp_yourphpversion'],  PHP_VERSION ), $this->lang->words['cp_php_warning'] );
		}

		/* Outgoing email address specified */
		if ( !$this->settings['email_out'] OR !$this->settings['email_in'] )
		{
			$entries[] = array( $this->lang->words['cp_missingemail'], "{$this->lang->words['cp_missingemail1']}<br /><br />{$this->lang->words['_raquo']} <a href='" . $this->settings['base_url'] . "&amp;module=settings&amp;section=settings&amp;do=findsetting&amp;key=email'>{$this->lang->words['cp_missingemail2']}</a>" );
		}
		
		/* Board Offline Check */
		if ( $this->settings['board_offline'] )
		{
			$entries[] = array( $this->lang->words['cp_boardoffline'], "{$this->lang->words['cp_boardoffline1']}<br /><br />{$this->lang->words['_raquo']} <a href='" . $this->settings['base_url'] . "&amp;module=settings&amp;section=settings&amp;do=findsetting&amp;key=boardoffline'>{$this->lang->words['cp_boardoffline2']}</a>" );
		}
		
		/* Fulltext Check */
		if( $this->settings['search_method'] == 'traditional' AND !$this->settings['use_fulltext'] AND !$this->settings['hide_ftext_note'] )
		{
			$entries[] = array( $this->lang->words['fulltext_off'], "{$this->lang->words['fulltext_turnon']}<br /><br />{$this->lang->words['_raquo']} <a href='" . $this->settings['base_url'] . "&amp;module=settings&amp;section=settings&amp;do=findsetting&amp;key=searchsetup'>{$this->lang->words['fulltext_find']}</a>" );
		}
		
		/* Make sure the profile directory is writable */
		if( ! is_dir( $this->settings['upload_dir'] . '/profile/' ) || ! is_writable( $this->settings['upload_dir'] . '/profile/' ) )
		{
			$entries[] = array( $this->lang->words['cp_profilephotoerr_title'], sprintf( $this->lang->words['cp_profilephotoerr_msg'], $this->settings['upload_dir'] . '/profile/' ) );
		}
		
		/* Check for upgrade finish folder */
		if( is_dir( IPS_ROOT_PATH . 'upgradeFinish/' ) )
		{
			$entries[] = array( $this->lang->words['cp_upgradefinishfolder'], sprintf( $this->lang->words['cp_upgradefinishfolder_msg'], IPS_ROOT_PATH . 'upgradeFinish/' ) );
		}
		
		/* Check to see if GD is intalled */
		if(! extension_loaded( 'gd' ) || ! function_exists( 'gd_info' ) )
		{
			$entries[] = array( $this->lang->words['cp_gdnotinstalled_title'], $this->lang->words['cp_gdnotinstalled_msg'] );
		}
		
		/* Performance mode check */
		$perfMode = $this->cache->getCache('performanceCache');
		
		if( is_array( $perfMode ) && count( $perfMode ) )
		{
			$entries[] = array( $this->lang->words['cp_perfmodeon_title'], $this->lang->words['cp_perfmodeon_msg'] );
		}
		
		/* Suhosin check */
		if( extension_loaded( 'suhosin' ) )
		{
			$_postMaxVars	= @ini_get('suhosin.post.max_vars');
			$_reqMaxVars	= @ini_get('suhosin.request.max_vars');
			$_postMaxLen	= @ini_get('suhosin.post.max_value_length');
			$_reqMaxLen		= @ini_get('suhosin.request.max_value_length');
			
			if( $_postMaxVars < 4096 )
			{
				$entries[] = array( $this->lang->words['suhosin_notification'], sprintf( $this->lang->words['suhosin_badvalue1'], $_postMaxVars ) );
			}
			
			if( $_reqMaxVars < 4096 )
			{
				$entries[] = array( $this->lang->words['suhosin_notification'], sprintf( $this->lang->words['suhosin_badvalue2'], $_reqMaxVars ) );
			}
			
			if( $_postMaxLen < 1000000 )
			{
				$entries[] = array( $this->lang->words['suhosin_notification'], sprintf( $this->lang->words['suhosin_badvalue3'], $_postMaxLen ) );
			}
			
			if( $_reqMaxLen < 1000000 )
			{
				$entries[] = array( $this->lang->words['suhosin_notification'], sprintf( $this->lang->words['suhosin_badvalue4'], $_reqMaxLen ) );
			}
		}
		
		/* SQL error check */
		/*if ( is_file( IPS_CACHE_PATH . 'cache/sql_error_latest.cgi' ) )
		{
			$unix = @filemtime( IPS_CACHE_PATH . 'cache/sql_error_latest.cgi' );
			
			if ( $unix )
			{
				$mtime = gmdate( 'd-j-Y', $unix );
				$now   = gmdate( 'd-j-Y', time() );
				
				if ( $mtime == $now )
				{
					// Display a message
				}
			}
		}*/
		
		return $entries;
	}
}