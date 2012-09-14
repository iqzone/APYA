<?php
/**
 * @file		monitor.php		IP.Nexus Server Monitoring Response Interface
 *
 * $Copyright: $
 * $License: $
 * $Author: mark $
 * $LastChangedDate: 2012-05-14 09:27:37 -0400 (Mon, 14 May 2012) $
 * $Revision: 10743 $
 * @since 		7th September 2011
 */

define( 'IPS_ENFORCE_ACCESS', TRUE );
define( 'IPB_THIS_SCRIPT', 'public' );
require_once( '../initdata.php' );/*noLibHook*/

require_once( IPS_ROOT_PATH . 'sources/base/ipsRegistry.php' );/*noLibHook*/
require_once( IPS_ROOT_PATH . 'sources/base/ipsController.php' );/*noLibHook*/

$registry = ipsRegistry::instance();
$registry->init();
$settings	=& $registry->fetchSettings();

$server = intval( $_GET['server'] );
$server = ipsRegistry::DB()->buildAndFetch( array( 'select' => '*', 'from' => 'nexus_hosting_servers', 'where' => "server_id={$server}" ) );
if ( !$server['server_id'] )
{
	exit;
}

$by = urldecode( $_GET['by'] );

if ( $_GET['a'] == 'a' )
{
	ipsRegistry::DB()->update( 'nexus_hosting_servers', array( 'server_monitor_acknowledged' => 1 ), "server_id={$server['server_id']}" );

	foreach ( explode( ',', $settings['monitoring_alert'] ) as $n )
	{
		$encoded = urlencode( $n );
	
		IPSText::getTextClass( 'email' )->subject	= "{$server['server_hostname']} (Acknowledged)";
		IPSText::getTextClass( 'email' )->to		= $n;
		IPSText::getTextClass( 'email' )->from		= $settings['monitoring_from'];
		IPSText::getTextClass( 'email' )->message	= "{$server['server_hostname']} acknowledged by {$by}<br /><br /><a href='{$settings['board_url']}/interface/monitor.php?a=r&server={$server['server_id']}&by={$encoded}'>Reset</a>";
		IPSText::getTextClass( 'email' )->html_email= 1;
		IPSText::getTextClass( 'email' )->sendMail();
	}
	
	if ( file_exists( IPSLib::getAppDir('nexus') . '/sources/actions/monitor.php' ) )
	{
		require_once( IPSLib::getAppDir('nexus') . '/sources/actions/monitor.php' );
		monitoring::acknowledge( $server, $by );
	}
}
elseif ( $_GET['a'] == 'r' )
{
	ipsRegistry::DB()->update( 'nexus_hosting_servers', array( 'server_monitor_acknowledged' => 0, 'server_monitor_fails' => 0 ), "server_id={$server['server_id']}" );
	
	foreach ( explode( ',', $settings['monitoring_alert'] ) as $n )
	{
		$encoded = urlencode( $n );
	
		IPSText::getTextClass( 'email' )->subject	= "{$server['server_hostname']} (Reset)";
		IPSText::getTextClass( 'email' )->to		= $n;
		IPSText::getTextClass( 'email' )->from		= $settings['monitoring_from'];
		IPSText::getTextClass( 'email' )->message	= "{$server['server_hostname']} reset by {$by}";
		IPSText::getTextClass( 'email' )->html_email= 1;
		IPSText::getTextClass( 'email' )->sendMail();
	}
	
	if ( file_exists( IPSLib::getAppDir('nexus') . '/sources/actions/monitor.php' ) )
	{
		require_once( IPSLib::getAppDir('nexus') . '/sources/actions/monitor.php' );
		monitoring::reset( $server, $by );
	}
}

echo "Done";
exit;