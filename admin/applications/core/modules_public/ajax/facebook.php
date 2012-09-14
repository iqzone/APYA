<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Login handler abstraction : AJAX login
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		Tuesday 1st March 2005 (11:52)
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_core_ajax_facebook extends ipsAjaxCommand 
{
	/**
	 * Login handler object
	 *
	 * @var		object
	 */
	protected $han_login;
	
	/**
	 * Flag : Logged in
	 *
	 * @var		boolean
	 */
	protected $logged_in		= false;
	
	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
    	/* What to do */
		switch( $this->request['do'] )
		{
			case 'getUserByFbId':
				$return = $this->_getUserByFbId();
			break;
			case 'storeFacebookAuthDetails':
				$this->_storeFacebookAuthDetails();
			break;
		}
		
		/* Output */
		$this->returnHtml( $return );
	}
	
	/**
	 * Stores main facebook data
	 *
	 * @return	@e void		[Outputs JSON to browser AJAX call]
	 */
	protected function _storeFacebookAuthDetails()
	{
		$rToken  = trim( $this->request['accessToken'] );
		$rUserId = trim( $this->request['userId'] ); # Do not INTVAL as Facebook UID > Intval() max
		
		/* Store it */
		IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'fb_uid'    => $rUserId,
																	  			 'fb_token'  => $rToken ) ) );
																	  			 
		$this->returnJsonArray( array( 'status' => 'ok' ) );
	}
		
	/**
	 * Main AJAX log in routine
	 *
	 * @return	@e void		[Outputs JSON to browser AJAX call]
	 */
	protected function _getUserByFbId()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$fbUid = is_numeric( $this->request['fbid'] ) ? $this->request['fbid'] : 0;
		$member = array( 'member_id' => 0 );
		
		if ( $fbUid )
		{
			$_mid = $this->DB->buildAndFetch( array( 'select' => 'member_id',
													 'from'   => 'members',
													 'where'  => 'fb_uid=' . $fbUid ) );
													
			if ( $_mid['member_id'] )
			{
				$member = IPSMember::load( $_mid['member_id'], 'all' );
			}
		}
		
		$this->returnJsonArray( $member );
	}
}