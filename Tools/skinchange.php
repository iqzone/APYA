<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Simple skin change script
 * Place in your site root directory to send a user to a specified skin
 * Last Updated: $Date: 2012-05-16 10:43:54 -0400 (Wed, 16 May 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10760 $
 *
 */

define( 'IPS_PUBLIC_SCRIPT', 'index.php' );
define( 'IPB_THIS_SCRIPT', 'public' );
require_once( './initdata.php' );/*noLibHook*/

require_once( IPS_ROOT_PATH . 'sources/base/ipsRegistry.php' );/*noLibHook*/
require_once( IPS_ROOT_PATH . 'sources/base/ipsController.php' );/*noLibHook*/

$reg = ipsRegistry::instance();
$reg->init();

if ( $_GET['id'] )
{
	@header("Location: " . ipsRegistry::$settings['base_url'] . '&settingNewSkin=' . intval( $_GET['id'] ) . '&k=' . $reg->member()->form_hash );
}
else
{
	die("No ID passed");
}

exit();