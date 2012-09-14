#!/usr/bin/php -q
<?php
/**
 * Invision Power Services
 * Incoming Email Handler - Piping
 * Last Updated: $Date: 2012-06-06 05:13:39 -0400 (Wed, 06 Jun 2012) $
 *
 * @author 		$Author: mark $
 * @copyright	(c) 2010 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		12th February 2010
 * @version		$Revision: 10871 $
 */
 
 	$writeDebug	= FALSE;
 	$readDebug	= FALSE;
 
 	//--------------------------------------
 	// Init
 	//--------------------------------------
 	 	 	  	
	define( 'IPS_ENFORCE_ACCESS', TRUE );
	define( 'IPB_THIS_SCRIPT', 'public' );
	
	require_once( str_replace( 'interface/email/piping.php', '', ( isset( $_SERVER['argv'][0] ) ? $_SERVER['argv'][0] : $_SERVER['SCRIPT_FILENAME'] ) ) . 'initdata.php' );/*noLibHook*/
	require_once( IPS_ROOT_PATH . 'sources/base/ipsRegistry.php' );/*noLibHook*/
	require_once( IPS_ROOT_PATH . 'sources/base/ipsController.php' );/*noLibHook*/
	
	$registry = ipsRegistry::instance();
	$registry->init();
			
	//-----------------------------------------
	// Get the Email
	//-----------------------------------------
	
	$debugPath = DOC_IPS_ROOT_PATH . 'cache/_email.txt';
	$email = file_get_contents( $readDebug ? $debugPath : 'php://stdin' );
	
	if ( $writeDebug )
	{
		file_put_contents( $debugPath, $email );
	}
	
	$override = array();
	if ( isset( $_SERVER['argv'][1] ) )
	{
		$override['to'] = $_SERVER['argv'][1];
	}
		
	require_once ( IPS_ROOT_PATH . 'sources/classes/incomingEmail/incomingEmail.php' );
	incomingEmail::parse( $email, $override );
	
	exit();