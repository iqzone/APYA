<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Login handler abstraction
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

class login_core
{
	/**#@+
	 * Registry objects
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $member;
	protected $cache;
	/**#@-*/
	
	/**
	 * Authentication errors
	 *
	 * @access	public
	 * @var		array
	 */
	public $auth_errors 	= array();
	
	/**
	 * Return code
	 *
	 * @access	public
	 * @var		string
	 */
	public $return_code 	= "";
	
	/**
	 * Member information
	 *
	 * @access	public
	 * @var		array
	 */
	public $member_data  	= array();
	
	/**
	 * Flag : Admin authentication
	 *
	 * @access	public
	 * @var		boolean
	 */
	public $is_admin_auth	= false;
	
	/**
	 * Unlock account time left
	 *
	 * @access	public
	 * @var		integer
	 */
	public $account_unlock	= 0;

	/**
	 * Force email check
	 *
	 * @access protected
	 * @var		boolean
	 */
	protected $_forceEmailCheck = FALSE;
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		$this->registry = $registry;
		$this->DB       = $this->registry->DB();
		$this->cache	= $this->registry->cache();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->member   = $this->registry->member();
	}
	
	/**
	 * Force email check flag, currently used for facebook
	 *
	 * @access	public
	 * @param 	boolean
	 * @return  null
	 */
	public function setForceEmailCheck( $boolean )
	{
		$this->_forceEmailCheck = ( $boolean ) ? TRUE : FALSE;
	}
	
	/**
	 * Local authentication
	 *
	 * @access	public
	 * @param	string		Username
	 * @param	string		Email Address
	 * @param	string		Password
	 * @return	boolean		Authentication successful
	 */
	public function authLocal( $username, $email_address, $password )
	{
		$password = md5( $password );
		
		//-----------------------------------------
		// Type of login
		//-----------------------------------------
		
		$type	= 'username';
		
		if( is_array($this->method_config) AND $this->method_config['login_folder_name'] == 'internal' )
		{
			$type = $this->method_config['login_user_id'];
		}
		
		/* Forcing email? */
		if ( $this->_forceEmailCheck )
		{
			$type = 'email';
		}
		
		/* If we only have one, just take it and run with it */
		$input = NULL;

		if ( $email_address xor $username )
		{
			$input = ( $email_address ) ? $email_address : $username;
		}
		
		switch( $type )
		{
			case 'username':
				$this->member_data = IPSMember::load( $input ? $input : $username, 'groups', 'username' );
			break;
			
			case 'email':
				$this->member_data = IPSMember::load( $input ? $input : $email_address, 'groups', 'email' );
			break;
			
			case 'either':
				$_username = IPSMember::load( $input ? $input : $username, 'groups', 'username' );
				
				if ( !$_username['member_id'] )
				{
					$this->member_data = IPSMember::load( $input ? $input : $email_address, 'groups', 'email' );
				}
				else
				{
					$this->member_data = $_username;
				}
			break;
		}
	
		//-----------------------------------------
		// Got an account
		//-----------------------------------------
		
		if ( ! $this->member_data['member_id'] )
		{
			$this->return_code = 'NO_USER';
			return false;
		}
	
		//-----------------------------------------
		// Verify it is not blocked
		//-----------------------------------------
		
		if( !$this->_checkFailedLogins() )
		{
			return false;
		}
	
		//-----------------------------------------
		// Check password...
		//-----------------------------------------
		
		if ( IPSMember::authenticateMember( $this->member_data['member_id'], $password ) != true )
		{ 
			if( !$this->_appendFailedLogin() )
			{
				return false;
			}
			
			$this->return_code = 'WRONG_AUTH';
			return false;
		}
		else
		{
			$this->return_code = 'SUCCESS';
			return true;
		}
	}
	
	/**
	 * Admin authentication
	 *
	 * @access	public
	 * @param	string		Username
	 * @param	string		Email Address
	 * @param	string		Password
	 * @return	boolean		Authentication successful
	 */
	public function adminAuthLocal( $username, $email_address, $password )
	{
		return $this->authLocal( $username, $email_address, $password );
	}

	/**
	 * Create a local member account [public interface]
	 *
	 * @access	public
	 * @param	array		Member Information [members,pfields,profile_portal]
	 * @return	array		New member information
	 */
	public function createLocalMember( $member )
	{
		$member['members']['members_created_remote']	= true;
		$member['members']['members_display_name']		= ( $member['members']['members_display_name'] ) ? $member['members']['members_display_name'] : $member['members']['name'];
		
		$_return	= IPSMember::create( $member, FALSE, FALSE, TRUE );
		
		$this->cache->rebuildCache( 'stats', 'global' );
		
		if( $_return['full'] )
		{
			IPSLib::runMemberSync( 'onCompleteAccount', $_return );
		}

		return $_return;
	}

	/**
	 * Check failed logins
	 *
	 * @access	protected
	 * @return	boolean		Account ok or not
	 */
	protected function _checkFailedLogins()	
	{
		if ( $this->settings['ipb_bruteforce_attempts'] > 0 )
		{
			$failed_attempts = explode( ",", IPSText::cleanPermString( $this->member_data['failed_logins'] ) );
			$failed_count	 = 0;
			$total_failed	 = 0;
			$thisip_failed	 = 0;
			$non_expired_att = array();
			
			if( is_array($failed_attempts) AND count($failed_attempts) )
			{
				foreach( $failed_attempts as $entry )
				{
					if ( ! strpos( $entry, "-" ) )
					{
						continue;
					}
					
					list ( $timestamp, $ipaddress ) = explode( "-", $entry );
					
					if ( ! $timestamp )
					{
						continue;
					}
					
					$total_failed++;
					
					if ( $ipaddress != $this->member->ip_address )
					{
						continue;
					}
					
					$thisip_failed++;
					
					if ( $this->settings['ipb_bruteforce_period'] AND
						$timestamp < time() - ($this->settings['ipb_bruteforce_period']*60) )
					{
						continue;
					}
					
					$non_expired_att[] = $entry;
					$failed_count++;
				}
				
				sort($non_expired_att);
				$oldest_entry  = array_shift( $non_expired_att );
				list($oldest,) = explode( "-", $oldest_entry );
			}

			if( $thisip_failed >= $this->settings['ipb_bruteforce_attempts'] )
			{
				if( $this->settings['ipb_bruteforce_unlock'] )
				{
					if( $failed_count >= $this->settings['ipb_bruteforce_attempts'] )
					{
						$this->account_unlock	= $oldest;
						$this->return_code		= 'ACCOUNT_LOCKED';
						
						return false;
					}
				}
				else
				{
					$this->return_code = 'ACCOUNT_LOCKED';
					
					return false;
				}
			}
		}
		
		return true;
	}
	
	/**
	 * Append a failed login
	 *
	 * @access	protected
	 * @return	boolean		Account ok or not
	 */
	protected function _appendFailedLogin()
	{
		if( $this->settings['ipb_bruteforce_attempts'] > 0 )
		{
			$failed_logins 	 = explode( ",", $this->member_data['failed_logins'] );
			$failed_logins[] = time() . '-' . $this->member->ip_address;
			
			$failed_count	 = 0;
			$total_failed	 = 0;
			$non_expired_att = array();

			foreach( $failed_logins as $entry )
			{
				list($timestamp,$ipaddress) = explode( "-", $entry );
				
				if( !$timestamp )
				{
					continue;
				}
				
				$total_failed++;
				
				if( $ipaddress != $this->member->ip_address )
				{
					continue;
				}
				
				if( $this->settings['ipb_bruteforce_period'] > 0
					AND $timestamp < time() - ($this->settings['ipb_bruteforce_period']*60) )
				{
					continue;
				}
				
				$failed_count++;
				$non_expired_att[] = $entry;
			}

			if( $this->member_data['member_id'] AND !$this->settings['failed_done'] )
			{
				IPSMember::save( $this->member_data['email'], array( 
																	'core' => array(
																					'failed_logins' => implode( ",", $non_expired_att ), 
																					'failed_login_count' => $total_failed 
																					) 
																	)		);

				$this->settings['failed_done']	= true;
			}

			if( $failed_count >= $this->settings['ipb_bruteforce_attempts'] )
			{
				if( $this->settings['ipb_bruteforce_unlock'] )
				{
					sort($non_expired_att);
					$oldest_entry  = array_shift( $non_expired_att );
					list($oldest,) = explode( "-", $oldest_entry );
					
					$this->account_unlock = $oldest;
				}

				$this->return_code = 'ACCOUNT_LOCKED';
				return false;
			}
		}
		
		return true;
	}
}