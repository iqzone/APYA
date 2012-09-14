<?php

/**
 * @file		index.php 	Redirects old lofi search results to the new IP.Board 3 urls 
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		IP.Board 3.0.0
 * $LastChangedDate: 2011-03-11 12:41:48 -0500 (Fri, 11 Mar 2011) $
 * @version		v3.3.3
 * $Revision: 8042 $
 */

define( 'IPS_PUBLIC_SCRIPT', 'index.php' );
define( 'LOFIVERSION_CALLED', true );

require_once( '../initdata.php' );/*noLibHook*/
require_once( IPS_ROOT_PATH . 'sources/base/ipsRegistry.php' );/*noLibHook*/
require_once( IPS_ROOT_PATH . 'sources/base/ipsController.php' );/*noLibHook*/

/* INIT Registry */
$reg = ipsRegistry::instance();
$reg->init();

/* GET INPUT */
$url    = my_getenv('REQUEST_URI') ? my_getenv('REQUEST_URI') : my_getenv('PHP_SELF');
$qs     = my_getenv('QUERY_STRING');
$link   = 'act=idx';
$id     = 0;
$st     = 0;

$justKeepMe = str_replace( '.html', '', ( $qs ) ? $qs : str_replace( "/", "", strrchr( $url, "/" ) ) );

/* Got pages? */
if ( strstr( $justKeepMe, "-" ) )
{
	list( $_mainBit, $_startBit ) = explode( "-", $justKeepMe );
	
	$justKeepMe = $_mainBit;
	$st         = intval( $_startBit );
}

if ( strstr( $justKeepMe, 't' ) AND is_numeric( substr( $justKeepMe, 1 ) ) )
{
	$id = intval( substr( $justKeepMe, 1 ) );
	
	$link = 'showtopic=' . $id;
	
	if ( $st )
	{
		$link .= '&amp;st=' . $st;
	}
}
else if ( strstr( $justKeepMe, 'f' ) AND is_numeric( substr( $justKeepMe, 1 ) ) )
{
	$id  = intval( substr( $justKeepMe, 1 ) );
	
	$link = 'showforum=' . $id;
	
	if ( $st )
	{
		$link .= '&amp;st=' . $st;
	}
}

/* GO GADGET GO */
if ( isset( $_SERVER['SERVER_PROTOCOL'] ) AND strstr( $_SERVER['SERVER_PROTOCOL'], '/1.0' ) )
{
	header("HTTP/1.0 301 Moved Permanently");
}
else
{
	header("HTTP/1.1 301 Moved Permanently");
}

header("Location: " . $reg->output->formatUrl( $reg->output->buildUrl( $link, 'public' ) ) );

exit();