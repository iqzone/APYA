<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Main public executable wrapper.
 * Set-up and load module to run
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2008 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 *
 */

define( 'IPS_ENFORCE_ACCESS', TRUE );
define( 'IPB_THIS_SCRIPT', 'public' );
require_once( '../../initdata.php' );/*noLibHook*/

require_once( IPS_ROOT_PATH . 'sources/base/ipsRegistry.php' );/*noLibHook*/
require_once( IPS_ROOT_PATH . 'sources/base/ipsController.php' );/*noLibHook*/

$registry = ipsRegistry::instance();
$registry->init();

$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/facebook/connect.php', 'facebook_connect' );
$facebook = new $classToLoad( $registry );

/* IPB fiddles with CODE to make it DO */
if ( ! $_REQUEST['code'] AND $_REQUEST['do'] )
{
	$_REQUEST['code'] = $_REQUEST['do'];
}

/* User pinging the un-install app? */
if ( $_POST['fb_sig'] AND $_POST['fb_sig_uninstall'] )
{
	$facebook->userHasRemovedApp();
	exit();
}

if ( $_REQUEST['code'] )
{
	/* From the log in page */
	if ( $_REQUEST['key'] )
	{
		try
		{
			if ( ! intval( $_REQUEST['m'] ) )
			{
				$facebook->finishLogin();
			}
			else
			{
				$facebook->finishConnection();
			}
		}
		catch( Exception $error )
		{
			$msg = $error->getMessage();
		
			/* Try re-authorising */
			if ( stristr( $error->getMessage(), 'invalid' ) )
			{
				$facebook->redirectToConnectPage();
			}
			switch( $msg )
			{
				default:
					$registry->getClass('output')->showError( 'fb_ohnoes', 1090091, null, null, 403 );
				break;
				case 'FACEBOOK_NOT_SET_UP':
					$registry->getClass('output')->showError( 'fb_not_on', 1090092, null, null, 403 );
				case 'NOT_REMOTE_MEMBER':
					$registry->getClass('output')->showError( 'fb_not_remote', 1090093, null, null, 403 );
				break;
			}
		}
	}
	else
	{
		$facebook->finishConnection();
	}
}
else
{
	$facebook->redirectToConnectPage();
}

exit();

?>