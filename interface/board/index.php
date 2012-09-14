<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Remote API integration gateway file
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
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

/**
* Script type
*
*/
define( 'IPB_THIS_SCRIPT', 'api' );
define( 'IPB_LOAD_SQL'   , 'queries' );

/**
* Matches IP address of requesting API
* Set to 0 to not match with IP address
*/
define( 'CVG_IP_MATCH', 1 );

require_once( '../../initdata.php' );/*noLibHook*/

//===========================================================================
// MAIN PROGRAM
//===========================================================================

require_once( IPS_ROOT_PATH . 'sources/base/ipsRegistry.php' );/*noLibHook*/
require_once( IPS_ROOT_PATH . 'sources/base/ipsController.php' );/*noLibHook*/

//-----------------------------------------
// Set up cookie stuff
//-----------------------------------------

$registry = ipsRegistry::instance();
$registry->init();

IPSCookie::$sensitive_cookies      = array( 'session_id', 'ipb_admin_session_id', 'member_id', 'pass_hash' );

//--------------------------------
//  Set up our vars
//--------------------------------

$registry->DB()->obj['use_shutdown'] = 0;

//--------------------------------
// Set debug mode
//--------------------------------

$registry->DB()->setDebugMode( ipsRegistry::$settings['sql_debug'] == 1 ? intval($_GET['debug']) : 0 );

//--------------------------------
//  Initialize the FUNC
//--------------------------------

if ( ! ipsRegistry::$settings['xmlrpc_enable'] )
{
	@header( "Content-type: text/xml" );
	print"<?xml version=\"1.0\" encoding=\"UTF-8\"?>
			<methodResponse>
			   <fault>
			      <value>
			         <struct>
			            <member>
			               <name>faultCode</name>
			               <value>
			                  <int>1</int>
			                  </value>
			               </member>
			            <member>
			               <name>faultString</name>
			               <value>
			                  <string>IP.Board's XML-RPC API system is not enabled. Log into your IP.Board ACP and visit: System -&gt; System Settings -&gt; Advanced -&gt; XML-RPC API and update &quot;Enable XML-RPC API System&quot;</string>
			               </value>
			               </member>
			            </struct>
			         </value>
			            </fault>
			   </methodResponse>";
	exit();
}

//===========================================================================
// Define Service
//===========================================================================

require_once( IPS_KERNEL_PATH . 'classApiServer.php' );/*noLibHook*/

//===========================================================================
// Create the XML-RPC Server
//===========================================================================

$server     = new classApiServer();
$api        = $server->decodeRequest();
$module     = $server->params['api_module'];
$user       = IPSText::md5Clean( $server->params['api_key']);

//-----------------------------------------
// Check for module
//-----------------------------------------

if ( $module AND is_file( DOC_IPS_ROOT_PATH . 'interface/board/modules/' . $module . '/api.php' ) )
{
	require_once( DOC_IPS_ROOT_PATH . 'interface/board/modules/' . $module . '/api.php' );/*noLibHook*/
	
	$webservice = new API_Server( $registry );
	$webservice->classApiServer =& $server;
}
else
{
	$server->apiSendError( '2', "IP.Board could not locate an API module called '{$module}'" );
	
	$registry->DB()->insert( 'api_log', array( 'api_log_key'     => $user,
												'api_log_ip'      => $_SERVER['REMOTE_ADDR'],
												'api_log_date'    => time(),
												'api_log_query'   => $server->raw_request,
												'api_log_allowed' => 0 ) );
	exit();
}

//-----------------------------------------
// Check user...
//-----------------------------------------

if ( $user )
{
	$webservice->api_user = $registry->DB()->buildAndFetch( array( 'select' => '*',
																		'from'   => 'api_users',
																		'where'  => "api_user_key='" . $user . "'" ) );
																		
	if ( ! $webservice->api_user['api_user_id'] )
	{
		$registry->DB()->insert( 'api_log', array( 'api_log_key'     => $user,
													'api_log_ip'      => $_SERVER['REMOTE_ADDR'],
													'api_log_date'    => time(),
													'api_log_query'   => $server->raw_request,
													'api_log_allowed' => 0 ) );
													
		$server->apiSendError( '3', "IP.Board could not locate a valid API user with that API key" );
										
		exit();
	}
}
else
{
	$registry->DB()->insert( 'api_log', array( 'api_log_key'     => $user,
												'api_log_ip'      => $_SERVER['REMOTE_ADDR'],
												'api_log_date'    => time(),
												'api_log_query'   => $server->raw_request,
												'api_log_allowed' => 0 ) );
												
	$server->apiSendError( '4', "No API Key was sent in the request" );
	exit();
}

//-----------------------------------------
// Check for IP address
//-----------------------------------------

if ( $webservice->api_user['api_user_ip'] )
{
	if ( $_SERVER['REMOTE_ADDR'] != $webservice->api_user['api_user_ip'] )
	{
		$registry->DB()->insert( 'api_log', array( 'api_log_key'     => $user,
													'api_log_ip'      => $_SERVER['REMOTE_ADDR'],
													'api_log_date'    => time(),
													'api_log_query'   => $server->raw_request,
													'api_log_allowed' => 0 ) );
		
		$server->apiSendError( '5', "Incorrect IP Address ({$_SERVER['REMOTE_ADDR']}). You must update the API User Key with that IP Address." );

		exit();
	}
}

//-----------------------------------------
// Add web service
//-----------------------------------------

$server->addObjectMap( $webservice, IPS_DOC_CHAR_SET );

//-----------------------------------------
// Process....
//-----------------------------------------

$server->getXmlRpc();

exit;