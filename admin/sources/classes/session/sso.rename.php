<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Sample SSO Class
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
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

class ssoSessionExtension
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
	 * CheckSSOForGuest
	 * Used when IP.Board session auth validates user as a guest
	 *
	 * @access	public
	 * @param	string		$type		Either: 'update' (Session is being updated) or 'create' (Session is being created)
	 */
	public function checkSSOForGuest( $type='create' )
	{
		/*
			You can take several courses of action here, you can check for your own cookies or $_SESSION values and log the user in, creating an account
			in IP.Board at the same time or you can just update a 'guest' counter on your own site, etc
		*/
	}
	
	/**
	 * checkSSOForMember
	 * Used when IP.Board session auth validates user as a member
	 *
	 * @access	public
	 * @param	string		$type		Either: 'update' (Session is being updated) or 'create' (Session is being created)
	 */
	public function checkSSOForMember( $type='create' )
	{
		/*
			Again, you can check your own cookies or $_SESSION values and update your own session for this user or you can create a user in your own database, etc
		 */
	}
}

