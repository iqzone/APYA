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
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_core_ajax_login extends ipsAjaxCommand 
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
    	//-----------------------------------------
    	// Load handler...
    	//-----------------------------------------
    	
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/handlers/han_login.php', 'han_login' );
		$this->han_login = new $classToLoad( $this->registry );
    	$this->han_login->init();
    	
    	$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_login' ), 'core' );
    	
    	//-----------------------------------------
    	// Show form or process login?
    	//-----------------------------------------
    	
		if ( $this->request['do'] == 'showForm' )
		{
			$additional_data	= $this->han_login->additionalFormHTML();
			$replace			= false;
			$data				= array();
			
			if( !is_null($additional_data) AND is_array($additional_data) AND count($additional_data) )
			{
				$replace 	= $additional_data[0];
				$data		= $additional_data[1];
			}

			$this->returnHtml( $this->registry->getClass('output')->getTemplate('global')->loginForm( $replace == 'replace' ? true : false, $data ) );
		}
		else if ( $this->request['do'] == 'authenticateUser' )
		{
			return $this->_authenticateUser();
		}
		else
		{
			return $this->_doLogIn();
		}
	}
	
	/**
	 * Authenticate a user, returns a JSON array
	 *
	 * @return	@e void		[Outputs JSON to browser AJAX call]
	 */
	protected function _authenticateUser()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$email    = $this->request['emailaddress'];
		$password = $this->request['password'];
		
		/* Force email check */
		$this->han_login->setForceEmailCheck( TRUE );
    	
    	$return = $this->han_login->loginPasswordCheck( '', $email, $password );
		
    	if ( $return !== TRUE )
		{
			$this->returnJsonArray( array( 'status' => 'error' ) );
		}
		else
		{
			$this->returnJsonArray( array( 'status' => 'ok', 'memberData' => $this->han_login->member_data ) );
		}
	}
	
	/**
	 * Main AJAX log in routine
	 *
	 * @return	@e void		[Outputs JSON to browser AJAX call]
	 */
	protected function _doLogIn()
	{
		//-----------------------------------------
    	// Use central method
    	//-----------------------------------------
    	
    	$return = $this->han_login->verifyLogin();

    	if( is_array($return[2]) AND count($return[2]) )
		{
			$this->returnJsonArray( array( 'error' => $return[2]['MSG'] ) );
		}
		else
		{
			$this->returnJsonArray( array( 'message' => $return[0], 'url' => $return[1] ) );
		}
	}
}