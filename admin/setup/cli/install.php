<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Installer CLI Script
 * Last Updated: $LastChangedDate: 2012-05-16 05:59:33 -0400 (Wed, 16 May 2012) $
 * </pre>
 *
 * @author 		$Author: mark $
 * @copyright	© 2011 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10756 $
 *
 */

/**
 * USAGE:
 * php install.php [APPS] [DIRECTORY] [URL] [LICENSE_KEY] [MYSQL_HOST] [MYSQL_DATABASE] [MYSQL_USER] [MYSQL_PASS] [ACP_USER] [ACP_PASS] [ACP_EMAIL]
 */

//-----------------------------------------
// Checks
//-----------------------------------------

if ( !isset( $_SERVER['argv'] ) )
{
	exit;
}

if ( is_file( "../../../cache/installer_lock.php" ) )
{
	exit;
}

//-----------------------------------------
// Init
//-----------------------------------------

error_reporting( ~E_NOTICE );

date_default_timezone_set( 'UTC' );

function my_getenv( $k ) { return $_SERVER[ $k ]; }

define( 'IPS_ROOT_PATH', "../../" );
define( 'DOC_IPS_ROOT_PATH', "../../../" );
define( 'IPS_KERNEL_PATH', "../../../ips_kernel/" );
define( 'IPB_VERSION', 0 );
define( 'IPB_LONG_VERSION', 0 );
define( 'IPS_IS_UPGRADER', FALSE );
define( 'IN_IPB', TRUE );
define( 'IN_ACP', TRUE );
define( 'PUBLIC_DIRECTORY', 'public' );

require_once( IPS_ROOT_PATH . 'setup/sources/base/ipsRegistry_setup.php' );/*noLibHook*/
require_once( IPS_ROOT_PATH . 'setup/sources/base/ipsController_setup.php' );/*noLibHook*/
require_once( IPS_ROOT_PATH . 'setup/sources/classes/output/output.php' );/*noLibHook*/
require_once( IPS_ROOT_PATH . 'setup/cli/output.php' );/*noLibHook*/

$steps = array(
	1 => 'sql',
	'sql_steps',
	'applications',
	'modules',
	'settings',
	'templates',
	'tasks',
	'languages',
	'clientlanguages',
	'bbcode',
	'acphelp',
	'other',
	'caches'
	);
ipsRegistry::init();
$registry = ipsRegistry::instance();

//-----------------------------------------
// Set Configuration
//-----------------------------------------

IPSSetUp::setSavedData('install_apps', $_SERVER['argv'][1] );
IPSSetUp::setSavedData('install_dir', $_SERVER['argv'][2] );
IPSSetUp::setSavedData('install_url', $_SERVER['argv'][3] );
IPSSetUp::setSavedData('lkey', $_SERVER['argv'][4] );
IPSSetUp::setSavedData('sql_driver', 'mysql' );
IPSSetUp::setSavedData('db_host', $_SERVER['argv'][5] );
IPSSetUp::setSavedData('db_name', $_SERVER['argv'][6] );
IPSSetUp::setSavedData('db_user', $_SERVER['argv'][7] );
IPSSetUp::setSavedData('db_pass', $_SERVER['argv'][8] );
IPSSetUp::setSavedData('db_pre' ,'' );
IPSSetUp::setSavedData('admin_user', $_SERVER['argv'][9] );
IPSSetUp::setSavedData('admin_pass', $_SERVER['argv'][10] );
IPSSetUp::setSavedData('admin_email', $_SERVER['argv'][11] );

/* Write it */
IPSInstall::writeConfiguration();

//-----------------------------------------
// Install
//-----------------------------------------

file_get_contents( "http://license.invisionpower.com/?a=activate&key={$_SERVER['argv'][4]}&url={$_SERVER['argv'][3]}" );
file_put_contents( "../../../cache/installer_lock.php", "AUTOINSTALLED" );

require_once( IPS_ROOT_PATH . 'setup/applications/install/sections/install.php' );/*noLibHook*/
$controller = new install_install();
$output = new CLIOutput( $steps, $controller );
$registry->setClass( 'output', $output );
$controller->makeRegistryShortcuts( $registry );
$output->setNextAction( 'do=sql' );
$output->sendOutput();

exit;