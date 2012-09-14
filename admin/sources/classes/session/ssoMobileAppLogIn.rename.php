<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Sample Mobile App SSO Class
 * Last Updated: $Date: 2012-05-10 21:10:13 +0100 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: Matt $
 * @copyright	(c) 2001 - 2012 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		22nd Septmeber 2009
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class ssoMobileAppLogIn
{
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		ipsRegistry object
	 */
	public function __construct( ipsRegistry $registry )
	{
	
	}
	
	/**
	 * authenticate
	 * Used to authenticate a user from the mobile app to your SSO framework
	 *
	 * @access	public
	 * @param	string		$username		Username sent from app
	 * @param	string		$password		Password sent from app (plain text version)
	 */
	public function authenticate( $username, $password )
	{
		/*
		You are expected to authenticate the user against your SSO framework.
		The return value is an array:
		'code' is a string of:
		SUCCESS			-	User authenticated
		FAILED			-   User did not authenticate
		'memberId' is an INT of the IPB member ID of the user who authenticated
		*/
		
		/* Default value */
		return array( 'code'     => 'FAILED',
					  'memberId' => 0 );
	}

}

