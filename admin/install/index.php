<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Installation Gateway
 * Last Updated: $LastChangedDate: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		14th May 2003
 * @version		$Rev: 10721 $
 */

define( 'IPB_THIS_SCRIPT', 'admin' );
define( 'IPS_IS_UPGRADER', FALSE );
define( 'IPS_IS_INSTALLER', TRUE );

require_once( '../../initdata.php' );/*noLibHook*/

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

/**
* Are we overwriting an existing IP.Board 2 installation?
*/
if ( is_file( DOC_IPS_ROOT_PATH . 'sources/ipsclass.php' ) )
{
	@header( "Location: http://" . $_SERVER["SERVER_NAME"] . str_replace( "/install/", "/upgrade/", $_SERVER["PHP_SELF"] ) );
	exit();
}

require_once( IPS_ROOT_PATH . 'setup/sources/base/ipsRegistry_setup.php' );/*noLibHook*/
require_once( IPS_ROOT_PATH . 'setup/sources/base/ipsController_setup.php' );/*noLibHook*/

ipsController::run();

exit();

?>