<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Converge Server Interface
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	IP.Converge
 * @link		http://www.invisionpower.com
 * @since		2.1.0
 * @version		$Revision: 10721 $
 *
 */

/**
* Script type
*
*/
define( 'IPB_THIS_SCRIPT', 'api' );

/**
* Matches IP address of requesting API
* Set to 0 to not match with IP address
*/
define( 'CVG_IP_MATCH', 1 );

require_once( '../initdata.php' );/*noLibHook*/

//===========================================================================
// MAIN PROGRAM
//===========================================================================

define( 'CCS_GATEWAY_CALLED', true );
require_once( IPS_ROOT_PATH . 'sources/base/ipsRegistry.php' );/*noLibHook*/

$registry = ipsRegistry::instance();
$registry->init();

//--------------------------------
//  Initialize the FUNC
//--------------------------------

if ( ! ipsRegistry::$settings['ipconverge_enabled'] )
{
	@header( "Content-type: text/xml" );
	print "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
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
			                  <string>IP.Converge is not enabled from your ACP Control Panel. Log into your IP.Board ACP and visit: Tools &amp; Settings -&gt; IP.Converge Configuration and update &quot;Enable IP.Converge&quot;</string>
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

require_once( DOC_IPS_ROOT_PATH   . 'converge_local/apis/server_functions.php' );/*noLibHook*/
require_once( IPS_KERNEL_PATH . 'classApiServer.php' );/*noLibHook*/

//===========================================================================
// Create the XML-RPC Server
//===========================================================================

$server     = new classApiServer();
$webservice = new Converge_Server( $registry );
$webservice->classApiServer =& $server;
$api        = $server->decodeRequest();

$server->addObjectMap( $webservice, 'UTF-8' );

//-----------------------------------------
// Process....
//-----------------------------------------

$server->getXmlRpc();



exit;