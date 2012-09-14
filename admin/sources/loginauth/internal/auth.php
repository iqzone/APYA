<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Login handler abstraction : Internal Method
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		Tuesday 1st March 2005 (11:52)
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class login_internal extends login_core implements interface_login
{
	/**
	 * Login method configuration
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $method_config	= array();
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @param	array 		Configuration info for this method
	 * @param	array 		Custom configuration info for this method
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $method, $conf=array() )
	{
		$this->method_config	= $method;
		
		parent::__construct( $registry );
	}
	
	/**
	 * Authenticate the request
	 *
	 * @access	public
	 * @param	string		Username
	 * @param	string		Email Address
	 * @param	string		Password
	 * @return	boolean		Authentication successful
	 */
	public function authenticate( $username, $email_address, $password )
	{
		if ( ( !$username AND !$email_address ) OR !$password )
		{
			$this->return_code	= 'MISSING_DATA';
			return false;
		}

		return $this->authLocal( $username, $email_address, $password );
	}
	
	/**
	 * Check if an email already exists
	 *
	 * @access	public
	 * @param	string		Email Address
	 * @return	boolean		Request was successful
	 */
	public function emailExistsCheck( $email )
	{
		$email_check = $this->DB->buildAndFetch( array( 'select' => 'member_id', 'from' => 'members', 'where' => "email='".$email."'" ) );
		
		$this->return_code = $email_check['member_id'] ? 'EMAIL_IN_USE' : 'EMAIL_NOT_IN_USE';
		return true;
	}
	
	/**
	 * Check if the username already exists
	 *
	 * @access	public
	 * @param	string		User Name
	 * @param	string		Array of member data
	 * @param	srting		Field to check, members_l_username or members_l_display_name
	 * @return	boolean		Request was successful
	 * @link	http://community.invisionpower.com/tracker/issue-21962-duplicate-display-names/
	 */
	public function nameExistsCheck( $name, $memberData, $fieldToCheck )
	{
		return false;
		
		$this->DB->build( array( 
									'select' => "{$fieldToCheck}, member_id",
									'from'   => 'members',
									'where'  => $fieldToCheck . "='". $this->DB->addSlashes( strtolower($name) )."' AND member_id != ".$memberData['member_id'],
									'limit'  => array( 0,1 ) ) );

    	$this->DB->execute();

    	
    	if ( $this->DB->getTotalRows() )
 		{
			$this->return_code = 'NAME_IN_USE';
    		return TRUE;
    	}
	}
}