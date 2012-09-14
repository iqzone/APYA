<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Installer: Upgrader core file
 * Last Updated: $LastChangedDate: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 *
 */

/*
	This script completes the upgrade from settings onwards
	Move the 'upgradeFinish' folder into your 'admin' directory
	and access via your web browser
*/

$startPoint = 'settings';

define( 'IPB_THIS_SCRIPT', 'admin' );
define( 'IPS_IS_UPGRADER', TRUE );
define( 'IPS_IS_INSTALLER', FALSE );

require_once( '../../initdata.php' );/*noLibHook*/

# Bypass security check
if ( ! $_GET['s'] )
{
	$_GET['section']  = 'index';
	$_POST['section'] = 'index';
}

require_once( IPS_ROOT_PATH . 'setup/sources/base/ipsRegistry_setup.php' );/*noLibHook*/
require_once( IPS_ROOT_PATH . 'setup/sources/base/ipsController_setup.php' );/*noLibHook*/

/* INIT */
ipsRegistry::init();

/* Fetch admin */
if ( ! $_GET['s'] )
{
	$admin = ipsRegistry::DB()->buildAndFetch( array( 'select' => '*',
													  'from'   => 'members',
													  'where'  => 'member_group_id=' . ipsRegistry::$settings['admin_group'],
													  'order'  => 'last_visit DESC',
													  'limit'  => array( 0,1 ) ) );
	/* Set up a session */
	ipsRegistry::$request['s'] = ipsRegistry::member()->sessionClass()->createSession( $admin, $admin['member_login_key'] );


	$_GET['section']                 = 'upgrade';
	$_POST['section']                = 'upgrade';
	ipsRegistry::$request['section'] = 'upgrade';
	ipsRegistry::$current_section    = 'upgrade';

	/* Set up the correct module */
	$_GET['do']  = ( $_GET['do'] )  ? $_GET['do']  : $startPoint;
	$_POST['do'] = ( $_POST['do'] ) ? $_POST['do'] : $startPoint;
	ipsRegistry::$request['do'] = ( ipsRegistry::$request['do'] ) ? ipsRegistry::$request['do'] : $startPoint;

	$apps   = array( 'core', 'forums', 'members' );
	$toSave = array();
	$vNums  = array();

	if ( is_array( $apps ) and count( $apps ) )
	{
		IPSSetUp::setSavedData( 'install_apps', implode( ',', $apps ) );
	
		/* Grab data */
		foreach( $apps as $app )
		{
			/* Grab version numbers */
			$numbers = IPSSetUp::fetchAppVersionNumbers( $app );
		
			/* Grab all numbers */
			$nums[ $app ] = IPSSetUp::fetchXmlAppVersions( $app );
		
			/* Grab app data */
			$appData[ $app ] = IPSSetUp::fetchXmlAppInformation( $app );
		
			$appClasses[ $app ] = IPSSetUp::fetchVersionClasses( $app, $numbers['current'][0], $numbers['latest'][0] );
		
			/* Store starting vnums */
			$vNums[ $app ] = $numbers['current'][0];
		}
	
		/* Got anything? */
		if ( count( $appClasses ) )
		{
			foreach( $appClasses as $app => $data )
			{
				foreach( $data as $num )
				{
					if ( is_file( IPSLib::getAppDir( $app ) . '/setup/versions/upg_' . $num . '/version_class.php' ) )
					{
						$_class = 'version_class_' . $app . '_' . $num;
						require_once( IPSLib::getAppDir( $app ) . '/setup/versions/upg_' . $num . '/version_class.php' );/*noLibHook*/
					
						$_tmp = new $_class( ipsRegistry::instance() );
					
						if ( method_exists( $_tmp, 'preInstallOptionsSave' ) )
						{
							$_t = $_tmp->preInstallOptionsSave();
						
							if ( is_array( $_t ) AND count( $_t ) )
							{
								$toSave[ $app ][ $num ] = $_t;
							}
						}
					}
				}
			}
		
			/* Save it */
			if ( count( $toSave ) )
			{
				IPSSetUp::setSavedData('custom_options', $toSave );
			}
		
			if ( count( $vNums ) )
			{
				IPSSetUp::setSavedData('version_numbers', $vNums );
			}
		}
	
		/* Freeze data */
		IPSSetUp::freezeSavedData();
	
		/* Thaw it */
		IPSSetUp::thawSavedData();
	}
}

/* Run our controller */
ipsController::run();