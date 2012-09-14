<?php
/**
 * @file		ips.php			API for informing IPB that license data needs to be refreshed - called when license is renewed, etc.
 *
 * $Copyright: $
 * $License: $
 * $Author: mark $
 * $LastChangedDate: 2012-02-22 12:07:55 -0500 (Wed, 22 Feb 2012) $
 * $Revision: 10349 $
 * @since 		22nd February 2012
 */

define( 'IPS_ENFORCE_ACCESS', TRUE );
define( 'IPB_THIS_SCRIPT', 'public' );
require_once( '../initdata.php' );/*noLibHook*/

require_once( IPS_ROOT_PATH . 'sources/base/ipsRegistry.php' );/*noLibHook*/
require_once( IPS_ROOT_PATH . 'sources/base/ipsController.php' );/*noLibHook*/

$registry = ipsRegistry::instance();
$registry->init();

if ( ipsRegistry::$settings['ipb_reg_number'] and ipsRegistry::$request['key'] == md5( ipsRegistry::$settings['ipb_reg_number'] ) )
{
	ipsRegistry::DB()->update( 'cache_store', array( 'cs_rebuild' => 1 ), "cs_key='licenseData'" );
	ipsRegistry::cache()->putWithCacheLib( 'licenseData', 'rebuildCache', 200 );
}

exit;
