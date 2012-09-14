#!/usr/bin/php -q
<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Task Handler
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	© 2011 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @version		$Revision: 10721 $
 */

//ini_set( 'display_errors', 'off' );

define( 'IPS_ENFORCE_ACCESS', TRUE );
define( 'IPS_IS_SHELL', TRUE );
require_once( str_replace( '/interface/task.php', '/initdata.php', $_SERVER['argv'][0] ) );/*noLibHook*/

require_once( IPS_ROOT_PATH . 'sources/base/ipsRegistry.php' );/*noLibHook*/
require_once( IPS_ROOT_PATH . 'sources/base/ipsController.php' );/*noLibHook*/
	
$registry = ipsRegistry::instance();
$registry->init();

if ( isset( $_SERVER['argv'][1] ) )
{
	ipsRegistry::$request['ck'] = $_SERVER['argv'][1];
}
else
{
	die;	
}

if ( isset( $_SERVER['argv'][2] ) )
{
	ipsRegistry::$request['allpass'] = $_SERVER['argv'][2];
}


$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_taskmanager.php', 'class_taskmanager' );
$functions = new $classToLoad( $registry );

$functions->runTask();