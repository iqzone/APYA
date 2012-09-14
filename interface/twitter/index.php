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

$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/twitter/connect.php', 'twitter_connect' );
$twitter = new $classToLoad( $registry );

if ( $_REQUEST['oauth_token'] )
{
	/* From the log in page */
	if ( $_REQUEST['key'] )
	{
		try
		{
			if ( ! intval( $_REQUEST['m'] ) )
			{
				$twitter->finishLogin();
			}
			else
			{
				$twitter->finishConnection();
			}
		}
		catch( Exception $error )
		{
			$msg = $error->getMessage();
		
			switch( $msg )
			{
				default:
					$registry->getClass('output')->showError( 'twit_ohnoes', 1090094, null, null, 403 );
				break;
				case 'TWITTER_NOT_SET_UP':
					$registry->getClass('output')->showError( 'twit_not_on', 1090095, null, null, 403 );
				case 'NOT_REMOTE_MEMBER':
					$registry->getClass('output')->showError( 'twit_not_remote', 1090096, null, null, 403 );
				break;
			}
		}
	}
	else
	{
		$twitter->finishConnection();
	}
}
else
{
	$twitter->redirectToConnectPage();
}

exit();