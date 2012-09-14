<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Login handler abstraction
 * Last Updated: $Date: 2012-05-31 11:34:45 -0400 (Thu, 31 May 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		Tuesday 1st March 2005 (11:52)
 * @version		$Revision: 10844 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class han_login
{
	/**
	 * Registry Object Shortcuts
	 *
	 * @var		object
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	
	/**
	 * Login module registry
	 *
	 * @var		array
	 */
	protected $modules			= array();
	
	/**
	 * Flag :: ACP Login
	 *
	 * @var		integer
	 */
	public $is_admin_auth 		= 0;
	
	/**
	 * Login handler return code
	 *
	 * @var		string
	 */
	public $return_code   		= 'WRONG_AUTH';
	
	/**
	 * Login handler return details
	 *
	 * @var		string
	 */
	public $return_details		= "";
	
	/**
	 * Flag :: Account unlocked
	 *
	 * @var		integer
	 */
	public $account_unlock		= 0;
	
	/**
	 * Member data returned
	 *
	 * @var		array
	 */
	public $member_data  		= array( 'member_id' => 0 );
	
	/**
	 * Login methods
	 *
	 * @var		array
	 */
	protected $login_methods	= array();
	
	/**
	 * Login configuration details
	 *
	 * @var		array
	 */
	protected $login_confs		= array();
	
	/**
	 * Constructor
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
		$this->registry = $registry;
		$this->DB       = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		
		//-----------------------------------------
		// Do not set member, memberData or lang
		// Causes issues with SSO implementations
		//-----------------------------------------
	}
	
	/**
	 * loginWithoutCheckingCredentials: Just log the user in.
	 * DOES NOT CHECK FOR A USERNAME OR PASSWORD.
	 * << USE WITH CAUTION >>
	 *
	 * @param	int			Member ID to log in
	 * @param	boolean		Set cookies
	 * @return	mixed		FALSE on error or array [0=Words to show, 1=URL to send to] on success
	 */
	public function loginWithoutCheckingCredentials( $memberID, $setCookies=TRUE )
	{
		//-----------------------------------------
		// Load member
		//-----------------------------------------
		
		$member = IPSMember::load( $memberID, 'all' );
		
		if ( ! $member['member_id'] )
		{
			return FALSE;
		}
		
		//-----------------------------------------
		// Is this a partial member?
		// Not completed their sign in?
		//-----------------------------------------
		
		if ( $member['members_created_remote'] AND isset($member['full']) AND !$member['full'] )
		{
			//-----------------------------------------
			// If this is a resume, i.e. from Facebook,
			// timenow won't be set
			//-----------------------------------------
			
			if( !$member['timenow'] )
			{
				$partial	= $this->DB->buildAndFetch( array( 'select' => 'partial_date', 'from' => 'members_partial', 'where' => 'partial_member_id=' . $member['member_id'] ) );
				$member['timenow']	= $partial['partial_date'];
			}
			
			return array( $this->registry->getClass('class_localization')->words['partial_login'], $this->settings['base_url'] . 'app=core&amp;module=global&amp;section=register&amp;do=complete_login&amp;mid='.$member['member_id'].'&amp;key='.$member['timenow'] );
		}
		
		//-----------------------------------------
		// Generate a new log in key
		//-----------------------------------------
		
		$_ok     = 1;
		$_time   = ( $this->settings['login_key_expire'] ) ? ( time() + ( intval($this->settings['login_key_expire']) * 86400 ) ) : 0;
		$_sticky = $_time ? 0 : 1;
		$_days   = $_time ? $this->settings['login_key_expire'] : 365;
		
		if ( !$member['member_login_key'] OR ( $this->settings['login_key_expire'] AND ( time() > $member['member_login_key_expire'] ) ) )
		{
			$member['member_login_key'] = IPSMember::generateAutoLoginKey();
			
			$core['member_login_key']			= $member['member_login_key'];
			$core['member_login_key_expire']	= $_time;
		}
	
		//-----------------------------------------
		// Cookie me softly?
		//-----------------------------------------
		
		if ( $setCookies )
		{
			IPSCookie::set( "member_id"   , $member['member_id']       , 1 );
			IPSCookie::set( "pass_hash"   , $member['member_login_key'], $_sticky, $_days );
		}
		else
		{
			IPSCookie::set( "member_id"   , $member['member_id'], 0 );
			IPSCookie::set( "pass_hash"   , $member['member_login_key'], 0 );
		}
		
		//-----------------------------------------
		// Remove any COPPA cookies previously set
		//-----------------------------------------
		
		IPSCookie::set("coppa", '0', 0);
		
		//-----------------------------------------
		// Update profile if IP addr missing
		//-----------------------------------------
		
		if ( $member['ip_address'] == "" OR $member['ip_address'] == '127.0.0.1' )
		{
			$core['ip_address']	= $this->registry->member()->ip_address;
		}
		
		//-----------------------------------------
		// Create / Update session
		//-----------------------------------------
		
		$privacy = IPSMember::isLoggedInAnon( $member );
		
		$session_id = $this->registry->member()->sessionClass()->convertGuestToMember( array( 'member_name'	    => $member['members_display_name'],
																   			     		 	  'member_id'		=> $member['member_id'],
																						      'member_group'	=> $member['member_group_id'],
																						      'login_type'		=> $privacy
																					  )		 );
				
		if ( !empty( $this->request['referer'] ) AND $this->request['section'] != 'register' )
		{
			if ( stripos( $this->request['referer'], 'section=register' ) OR stripos( $this->request['referer'], 'section=login' ) OR stripos( $this->request['referer'], 'section=lostpass' ) )
			{ 
				$url = $this->settings['base_url'];
			}
			else
			{ 
				$url = str_replace( '&amp;'		   , '&', $this->request['referer'] );
				//$url = str_replace( "{$this->settings['board_url']}/index.{$this->settings['php_ext']}", "", $url );
				//$url = str_replace( "{$this->settings['board_url']}/", "", $url );
				//$url = str_replace( "{$this->settings['board_url']}", "", $url );
				//$url = preg_replace( "#^(.+?)\?#", ""	, $url );
				$url = preg_replace( '#s=(\w){32}#', ""	, $url );
				$url = ltrim( $url, '?' );
			}
		}
		else
		{
			$url = $this->settings['base_url'];
		}
	

		//-----------------------------------------
		// Set our privacy status
		//-----------------------------------------
		
		$core['login_anonymous']		= intval($privacy) . '&1';
		$core['failed_logins']			= '';
		$core['failed_login_count']		= 0;

		IPSMember::save( $member['member_id'], array( 'core' => $core ) );

		//-----------------------------------------
		// Clear out any passy change stuff
		//-----------------------------------------
		
		$this->DB->delete( 'validating', 'member_id=' . $this->registry->member()->getProperty('member_id') . ' AND lost_pass=1' );

		//-----------------------------------------
		// Run member sync
		//-----------------------------------------
		
		IPSLib::runMemberSync( 'onLogin', $member );
		
		//-----------------------------------------
		// Redirect them to either the board
		// index, or where they came from
		//-----------------------------------------

		if ( !empty($this->request['return']) )
		{
			$return = urldecode($this->request['return']);
			
			if ( strpos( $return, "http://" ) === 0 || strpos( $return, "https://" ) === 0 )
			{
				return array( $this->registry->getClass('class_localization')->words['partial_login'], $return );
			}
		}
		
		//-----------------------------------------
		// Still here?
		//-----------------------------------------
		
		return array( $this->registry->getClass('class_localization')->words['partial_login'], str_replace( '?&', '?', $url . '&s=' . $session_id ) );
	}
	
	/**
	 * Wrapper for loginAuthenticate - returns more information
	 *
	 * @return	mixed		array [0=Words to show, 1=URL to send to, 2=error message language key]
	 */
	public function verifyLogin()
	{
    	$url		= "";
    	$member		= array();
    	$username	= '';
    	$email		= '';
		$password	= trim( $this->request['ips_password'] );
		$errors		= '';
		$core		= array();
		$mobileSSO  = false;
		
		/* Mobile app + sso */
		if ( $this->memberData['userAgentType'] == 'mobileApp' )
		{
			$file = IPS_ROOT_PATH . 'sources/classes/session/ssoMobileAppLogIn.php';
			
			if ( is_file( $file ) )
			{
				require_once( $file );/*noLibHook*/ #NoTeraThisDoesNotNeedAHookButIForgotYourLittleCodeSorryX
				
				if ( class_exists( 'ssoMobileAppLogIn' ) )
				{
					$mobileSSO = true;
					$logIn     = new ssoMobileAppLogIn( $this->registry );
					$done      = $logIn->authenticate( $this->request['ips_username'], $password );
					
					$this->return_code = $done['code'];
					$this->member_data = IPSMember::load( intval( $done['memberId'] ) );
					$member            = $this->member_data;
				}
			}
		}
		
		/* No mobile log in? Log in normally */
		if ( ! $mobileSSO )
		{
			//-----------------------------------------
			// Is this a username or email address?
			//-----------------------------------------
			
			if( IPSText::checkEmailAddress( $this->request['ips_username'] ) )
			{
				$email		= $this->request['ips_username'];
			}
			else
			{
				$username	= $this->request['ips_username'];
			}
			
			//-----------------------------------------
			// Check auth
			//-----------------------------------------
			
			$this->loginAuthenticate( $username, $email, $password );
			
			$member = $this->member_data;
		}
		
		//-----------------------------------------
		// Check return code...
		//-----------------------------------------

		if ( $this->return_code != 'SUCCESS' )
		{
			if( $this->return_code == 'MISSING_DATA' )
			{
				return array( null, null, 'complete_form' );
			}

			if ( $this->return_code == 'ACCOUNT_LOCKED' )
			{
				$extra = "<!-- -->";

				if( $this->settings['ipb_bruteforce_unlock'] )
				{
					if( $this->account_unlock )
					{
						$time = time() - $this->account_unlock;
						$time = ( $this->settings['ipb_bruteforce_period'] - ceil( $time / 60 ) > 0 ) ? $this->settings['ipb_bruteforce_period'] - ceil( $time / 60 ) : 1;
					}
				}
				
				return array( null, null, $this->settings['ipb_bruteforce_unlock'] ? 'bruteforce_account_unlock' : 'bruteforce_account_lock', $time );
			}
			else if( $this->return_code == 'MISSING_EXTENSIONS' )
			{
				return array( null, null, 'missing_extensions' );
			}
			else if( $this->return_code == 'FLAGGED_REMOTE' )
			{
				return array( null, null, 'flagged_remote' );
			}
			else
			{
				return array( null, null, 'wrong_auth' );
			}
		}

		//-----------------------------------------
		// Is this a partial member?
		// Not completed their sign in?
		//-----------------------------------------

		if ( $member['members_created_remote'] AND isset($member['full']) AND !$member['full'] )
		{
			return array( $this->registry->getClass('class_localization')->words['partial_login'], $this->settings['base_url'] . 'app=core&amp;module=global&amp;section=register&amp;do=complete_login&amp;mid='.$member['member_id'].'&amp;key='.$member['timenow'] );
		}

		//-----------------------------------------
		// Generate a new log in key
		//-----------------------------------------
		
		$_ok     = 1;
		$_time   = ( $this->settings['login_key_expire'] ) ? ( time() + ( intval($this->settings['login_key_expire']) * 86400 ) ) : 0;
		$_sticky = $_time ? 0 : 1;
		$_days   = $_time ? $this->settings['login_key_expire'] : 365;
		
		if ( !$member['member_login_key'] OR ( $this->settings['login_key_expire'] AND ( time() > $member['member_login_key_expire'] ) ) )
		{
			$member['member_login_key'] = IPSMember::generateAutoLoginKey();
			
			$core['member_login_key']			= $member['member_login_key'];
			$core['member_login_key_expire']	= $_time;
		}
	
		//-----------------------------------------
		// Cookie me softly?
		//-----------------------------------------
		
		if ( $this->request['rememberMe'] )
		{
			IPSCookie::set( "member_id"   , $member['member_id']       , 1 );
			IPSCookie::set( "pass_hash"   , $member['member_login_key'], $_sticky, $_days );
		}
		else
		{
			IPSCookie::set( "member_id"   , $member['member_id'], 0 );
			IPSCookie::set( "pass_hash"   , $member['member_login_key'], 0 );
		}
		
		//-----------------------------------------
		// Remove any COPPA cookies previously set
		//-----------------------------------------
		
		IPSCookie::set("coppa", '0', 0);
		
		//-----------------------------------------
		// Update profile if IP addr missing
		//-----------------------------------------
		
		if ( $member['ip_address'] == "" OR $member['ip_address'] == '127.0.0.1' )
		{
			$core['ip_address']	= $this->registry->member()->ip_address;
		}
		
		//-----------------------------------------
		// Create / Update session
		//-----------------------------------------
		
		$privacy = ( $member['g_hide_online_list'] || ( empty($this->settings['disable_anonymous']) && ! empty($this->request['anonymous']) ) ) ? 1 : 0;
		
		$session_id = $this->registry->member()->sessionClass()->convertGuestToMember( array( 'member_name'	    => $member['members_display_name'],
																   			     		 	  'member_id'		=> $member['member_id'],
																						      'member_group'	=> $member['member_group_id'],
																						      'login_type'		=> $privacy
																					  )		 );

		if ( !empty($this->request['referer']) AND $this->request['section'] != 'register' )
		{
			if ( stripos( $this->request['referer'], 'section=register' ) OR stripos( $this->request['referer'], 'section=login' ) OR stripos( $this->request['referer'], 'section=lostpass' ) OR stripos( $this->request['referer'], CP_DIRECTORY . '/' ) )
			{ 
				$url = $this->settings['base_url'];
			}
			else
			{
				$url = str_replace( '&amp;', '&', $this->request['referer'] );
				
				if( $this->registry->member()->session_type == 'cookie' )
				{
					$url = preg_replace( '#s=(\w){32}#', ""	, $url );
				}
			}
		}
		else
		{
			$url = $this->settings['base_url'];
		}
		
		//-----------------------------------------
		// Set our privacy status
		//-----------------------------------------
		
		$core['login_anonymous']		= intval($privacy) . '&1';
		$core['failed_logins']			= '';
		$core['failed_login_count']		= 0;

		IPSMember::save( $member['member_id'], array( 'core' => $core ) );

		//-----------------------------------------
		// Clear out any passy change stuff
		//-----------------------------------------
		
		$this->DB->delete( 'validating', 'member_id=' . $this->registry->member()->getProperty('member_id') . ' AND lost_pass=1' );

		//-----------------------------------------
		// Run member sync
		//-----------------------------------------
		
		$member['plainPassword'] = $password;

		IPSLib::runMemberSync( 'onLogin', $member );

		unset( $member['plainPassword'] );
		
		//-----------------------------------------
		// Redirect them to either the board
		// index, or where they came from
		//-----------------------------------------

		if ( !empty($this->request['return']) )
		{
			$return = urldecode($this->request['return']);
			
			if ( strpos( $return, "http://" ) === 0 || strpos( $return, "https://" ) === 0 )
			{
				return array( $this->registry->getClass('class_localization')->words['partial_login'], $return );
			}
		}
		
		//-----------------------------------------
		// Still here?
		//-----------------------------------------

		return array( $this->registry->getClass('class_localization')->words['partial_login'], $url );
	}
	
	/**
	 * Initialize class
	 *
	 * @return	@e void
	 */
    public function init()
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	require_once( IPS_PATH_CUSTOM_LOGIN . '/login_core.php' );/*noLibHook*/
    	require_once( IPS_PATH_CUSTOM_LOGIN . '/login_interface.php' );/*noLibHook*/
    	
    	$classes	= array();
    	$configs	= array();
    	$methods	= array();
    	
    	//-----------------------------------------
    	// Do we have cache?
    	//-----------------------------------------
    	
    	$cache = $this->registry->cache()->getCache( 'login_methods' );
    	
    	if( is_array($cache) AND count($cache) )
		{
			foreach( $cache as $login_method )
			{
				if( $login_method['login_enabled'] )
				{
					if( is_file( IPS_PATH_CUSTOM_LOGIN . '/' . $login_method['login_folder_name'] . '/auth.php' ) )
					{
						$classes[ $login_method['login_order'] ]				= IPS_PATH_CUSTOM_LOGIN . '/' . $login_method['login_folder_name'] . '/auth.php';
						$this->login_methods[ $login_method['login_order'] ]	= $login_method;
						$this->login_confs[ $login_method['login_order'] ]		= $login_method['login_custom_config'] ? @unserialize( $login_method['login_custom_config'] ) : array();

						$classname = IPSLib::loadLibrary( $classes[ $login_method['login_order'] ], 'login_' . $login_method['login_folder_name'] );
						$this->modules[ $login_method['login_order'] ] = new $classname( $this->registry, $login_method, $this->login_confs[ $login_method['login_order'] ] );
					}
				}
			}
		}

    	//-----------------------------------------
    	// No cache info
    	//-----------------------------------------
    	
    	else
		{
    		$this->DB->build( array( 'select' => '*', 'from' => 'login_methods', 'where' => 'login_enabled=1' ) );
    		$this->DB->execute();
    		
			while( $login_method = $this->DB->fetch() )
			{
				if( is_file( IPS_PATH_CUSTOM_LOGIN . '/' . $login_method['login_folder_name'] . '/auth.php' ) )
				{
					$classes[ $login_method['login_order'] ]				= IPS_PATH_CUSTOM_LOGIN . '/' . $login_method['login_folder_name'] . '/auth.php';
					$this->login_methods[ $login_method['login_order'] ]	= $login_method;
					$this->login_confs[ $login_method['login_order'] ]		= $login_method['login_custom_config'] ? @unserialize( $login_method['login_custom_config'] ) : array();
					
					$classToLoad = IPSLib::loadLibrary( $classes[ $login_method['login_order'] ], 'login_' . $login_method['login_folder_name'] );
					$this->modules[ $login_method['login_order'] ]			= new $classToLoad( $this->registry, $login_method, $this->login_confs[ $login_method['login_order'] ] );
				}
			}
		}
    	
    	//-----------------------------------------
    	// Got nothing?
    	//-----------------------------------------
    	
    	if ( !count($classes) )
    	{
    		$login_method = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'login_methods', 'where' => "login_folder_name='internal'" ) );
    		
    		if( $login_method['login_id'] )
			{
	    		$classes[ 0 ]				= IPS_PATH_CUSTOM_LOGIN . '/internal/auth.php';
				$this->login_methods[ 0 ]	= $login_method;
				$this->login_confs[ 0 ]		= array();
	
				$classToLoad = IPSLib::loadLibrary( $classes[ 0 ], 'login_internal' );
				$this->modules[ 0 ]			= new $classToLoad( $this->registry, $login_method, array() );
			}
		}
		
    	//-----------------------------------------
    	// If we're here, there is no enabled login
    	// handler and internal was deleted
    	//-----------------------------------------
    	
    	if( !count($this->modules) )
		{
			$this->registry->output->showError( $this->registry->getClass('class_localization')->words['no_login_methods'], 4000 );
		}

		//-----------------------------------------
		// Pass of some data
		//-----------------------------------------
		
		foreach( $this->modules as $k => $obj_reference )
		{
			$obj_reference->is_admin_auth	= $this->is_admin_auth;
			$obj_reference->login_method	= $this->login_methods[ $k ];
			$obj_reference->login_conf		= $this->login_confs[ $k ];
		}
    }
    
	/**
	 * Force email check flag in any modules, currently used for facebook
	 *
	 * @param 	boolean
	 * @return  null
	 */
	public function setForceEmailCheck( $boolean )
	{
		foreach( $this->modules as $k => $obj_reference )
		{
			if( method_exists( $obj_reference, 'setForceEmailCheck' ) )
			{
				$obj_reference->setForceEmailCheck( $boolean );
			}
		}
	}
	
	/**
	 * Checks if password authenticates
	 *
	 * @param	string		Username
	 * @param	string		Email Address
	 * @param	string		Password
	 * @return	boolean		Password check successful
	 */
  	public function loginPasswordCheck( $username, $email_address, $password )
  	{
		foreach( $this->modules as $k => $obj_reference )
		{
			$obj_reference->authenticate( $username, $email_address, $password );
			$this->return_code 		= ( $obj_reference->return_code == 'SUCCESS' ? 'SUCCESS' : 'FAIL' );
			$this->member_data		= $obj_reference->member_data;
			
			if( $this->return_code == 'SUCCESS' )
			{
				break;
			}
  		}
  		
  		return ( $this->return_code == 'SUCCESS' ) ? true : false;
  	}
    
	/**
	 * Authenticate the user - creates account if possible
	 *
	 * @param	string		Username
	 * @param	string		Email Address
	 * @param	string		Password
	 * @return	boolean		Authenticate successful
	 */
  	public function loginAuthenticate( $username, $email_address, $password )
  	{
		foreach( $this->modules as $k => $obj_reference )
		{
			$obj_reference->authenticate( $username, $email_address, $password );
			$this->return_code 		= $obj_reference->return_code;
			$this->member_data		= $obj_reference->member_data;
			$this->account_unlock 	= ( $obj_reference->account_unlock ) ? $obj_reference->account_unlock : $this->account_unlock;
			
			/* Locked */
			if( $this->return_code == 'ACCOUNT_LOCKED' )
			{
				return false;
			}
			
			if( $this->return_code == 'SUCCESS' )
			{
				break;
			}
			else
			{
				//-----------------------------------------
				// Want to redirect somewhere to login?
				//-----------------------------------------
				
				if( $this->login_methods[ $k ]['login_login_url'] )
				{
					$redirect = $this->login_methods[ $k ]['login_login_url'];
				}
			}
  		}
  		
		//-----------------------------------------
		// If we found a login url, go to it now
		// but only if we aren't already logged in
		//-----------------------------------------
		
  		if( $this->return_code != 'SUCCESS' AND $redirect )
  		{
  			$this->registry->getClass('output')->silentRedirect( $redirect );
  		}
  		
  		return ( $this->return_code == 'SUCCESS' ) ? true : false;
  	}
  	
	/**
	 * Logout callback - called when a user logs out
	 *
	 * @return	mixed		Possible redirection based on login method config, else array of messages
	 */
  	public function logoutCallback()
  	{
  		$returns 	= array();
  		$redirect	= '';
  		
		foreach( $this->modules as $k => $obj_reference )
		{
			if( method_exists( $obj_reference, 'logoutCallback' ) )
			{
				$returns[] = $obj_reference->logoutCallback();
			}
			
			//-----------------------------------------
			// Grab first logout callback url found
			//-----------------------------------------

			if( !$redirect AND $this->login_methods[ $k ]['login_logout_url'] )
			{
				$redirect = $this->login_methods[ $k ]['login_logout_url'];
			}
  		}
  		
		//-----------------------------------------
		// If we found a logout url, go to it now
		//-----------------------------------------
		
  		if( $redirect )
  		{
  			$this->registry->getClass('output')->silentRedirect( $redirect );
  		}

  		return $returns;
  	}
  	
	/**
	 * Alternate login URL redirection
	 *
	 * @return	mixed		Possible redirection based on login method config, else false
	 */
  	public function checkLoginUrlRedirect()
  	{
  		$redirect	= '';
  		
		foreach( $this->modules as $k => $obj_reference )
		{
			if( method_exists( $obj_reference, 'checkLoginUrlRedirect' ) )
			{
				$obj_reference->checkLoginUrlRedirect();
			}
			
			//-----------------------------------------
			// Grab first logout callback url found
			//-----------------------------------------

			if( !$redirect AND $this->login_methods[ $k ]['login_login_url'] )
			{
				$redirect = $this->login_methods[ $k ]['login_login_url'];
			}
  		}
  		
		//-----------------------------------------
		// If we found a logout url, go to it now
		//-----------------------------------------
		
  		if( $redirect )
  		{
  			$this->registry->getClass('output')->silentRedirect( $redirect );
  		}

  		return false;
  	}
  	
	/**
	 * User maintenance callback
	 *
	 * @return	mixed		Possible redirection based on login method config, else false
	 */
  	public function checkMaintenanceRedirect()
  	{
  		$redirect	= '';
  		
		foreach( $this->modules as $k => $obj_reference )
		{
			if( method_exists( $obj_reference, 'checkMaintenanceRedirect' ) )
			{
				$obj_reference->checkMaintenanceRedirect();
			}
			
			//-----------------------------------------
			// Grab first logout callback url found
			//-----------------------------------------

			if( !$redirect AND $this->login_methods[ $k ]['login_maintain_url'] )
			{
				$redirect = $this->login_methods[ $k ]['login_maintain_url'];
			}
  		}
  		
		//-----------------------------------------
		// If we found a logout url, go to it now
		//-----------------------------------------
		
  		if( $redirect )
  		{
  			$this->registry->getClass('output')->silentRedirect( $redirect );
  		}

  		return false;
  	}

	/**
	 * Check if the username is already in use
	 *
	 * @param	string		User Name
	 * @param	string		Array of member data
	 * @param	srting		Field to check, members_l_username or members_l_display_name
	 * @return	boolean		Authenticate successful
	 */
  	public function nameExistsCheck( $nameToCheck, $memberData, $fieldToCheck='members_l_username' )
  	{
  		$this->return_code = 'METHOD_NOT_DEFINED';
  		
		foreach( $this->modules as $k => $obj_reference )
		{
			if( method_exists( $obj_reference, 'nameExistsCheck' ) )
			{
				$obj_reference->nameExistsCheck( $nameToCheck, $memberData, $fieldToCheck );
				$this->return_code 		= $obj_reference->return_code;
				
				if( $this->return_code AND !in_array( $this->return_code, array( 'NAME_NOT_IN_USE', 'METHOD_NOT_DEFINED', 'WRONG_AUTH', 'MISSING_EXTENSIONS' ) ) )
				{
					break;
				}
			}
  		}
  		
		if( $this->return_code AND !in_array( $this->return_code, array( 'NAME_NOT_IN_USE', 'METHOD_NOT_DEFINED', 'WRONG_AUTH', 'MISSING_EXTENSIONS' ) ) )
		{
			return true;
		}
		else
		{
			return false;
		}
  	}

	/**
	 * Check if the email is already in use
	 *
	 * @param	string		Email address
	 * @return	boolean		Authenticate successful
	 */
  	public function emailExistsCheck( $email )
  	{
  		$this->return_code = 'METHOD_NOT_DEFINED';
  		
		foreach( $this->modules as $k => $obj_reference )
		{
			if( method_exists( $obj_reference, 'emailExistsCheck' ) )
			{
				$obj_reference->emailExistsCheck( $email );
				$this->return_code 		= $obj_reference->return_code;
				
				if( $this->return_code AND !in_array( $this->return_code, array( 'EMAIL_NOT_IN_USE', 'METHOD_NOT_DEFINED', 'WRONG_AUTH', 'MISSING_EXTENSIONS' ) ) )
				{
					break;
				}
			}
  		}
  		
		if( $this->return_code AND !in_array( $this->return_code, array( 'EMAIL_NOT_IN_USE', 'METHOD_NOT_DEFINED', 'WRONG_AUTH', 'MISSING_EXTENSIONS' ) ) )
		{
			return true;
		}
		else
		{
			return false;
		}
  	}
  	
	/**
	 * Change a user's email address
	 *
	 * @param	string		Old Email address
	 * @param	string		New Email address
	 * @return	boolean		Email changed successfully
	 */
  	public function changeEmail( $old_email, $new_email, $memberData )
  	{
  		$this->return_code = 'METHOD_NOT_DEFINED';
  		
		foreach( $this->modules as $k => $obj_reference )
		{
			if( method_exists( $obj_reference, 'changeEmail' ) )
			{
				$obj_reference->changeEmail( $old_email, $new_email, $memberData );
				$this->return_code 		= $obj_reference->return_code;
			}
  		}
  		
  		return ( $this->return_code == 'SUCCESS' ) ? true : false;
  	}
  	
	/**
	 * Change a user's password
	 *
	 * @param	string		Email address
	 * @param	string		New password
	 * @param	string		Plain Text Password
	 * @param	string		Member Array
	 * @return	boolean		Password changed successfully
	 */
  	public function changePass( $email, $new_pass, $plain_pass, $memberData )
  	{
  		$this->return_code = 'METHOD_NOT_DEFINED';
  		
		foreach( $this->modules as $k => $obj_reference )
		{
			if( method_exists( $obj_reference, 'changePass' ) )
			{
				$obj_reference->changePass( $email, $new_pass, $plain_pass, $memberData );
				$this->return_code 		= $this->return_code == 'SUCCESS' ? 'SUCCESS' : $obj_reference->return_code;
			}
  		}
  		
  		return ( $this->return_code == 'SUCCESS' ) ? true : false;
  	}
  	
	/**
	 * Change a login name
	 *
	 * @param	string		Old Name
	 * @param	string		New Name
	 * @param	string		User's email address
	 * @param	array 		Arra of Member Data
	 * @return	boolean		Request was successful
	 */
	public function changeName( $old_name, $new_name, $email_address, $memberData )
  	{
  		$this->return_code = 'METHOD_NOT_DEFINED';
  		
		foreach( $this->modules as $k => $obj_reference )
		{
			if( method_exists( $obj_reference, 'changeName' ) )
			{
				$obj_reference->changeName( $old_name, $new_name, $email_address, $memberData );
				$this->return_code 		= $obj_reference->return_code;
			}
  		}
  		
  		return ( $this->return_code == 'SUCCESS' ) ? true : false;
  	}
  	
	/**
	 * Create a user's account
	 *
	 * @param	array		Array of member information
	 * @return	boolean		Account created successfully
	 */
  	public function createAccount( $member=array() )
  	{
	  	if( !is_array( $member ) )
	  	{
		  	$this->return_code = 'FAIL';
		  	return false;
	  	}
	  	
	  	$this->return_code = '';

		foreach( $this->modules as $k => $obj_reference )
		{
			if( method_exists( $obj_reference, 'createAccount' ) )
			{
				$obj_reference->createAccount( $member );
				$this->return_code 		= $obj_reference->return_code;
				$this->return_details  .= $obj_reference->return_details . '<br />';
			}
  		}
  	}
  	
	/**
	 * Determine email address or username login
	 *
	 * @return	integer		[1=Username, 2=Email, 3=Both]
	 */
  	public function emailOrUsername()
  	{
  		$username 	= false;
  		$email		= false;

		foreach( $this->login_methods as $k => $method )
		{
			/* @link http://community.invisionpower.com/tracker/issue-27425-misleading-enter-username-or-email-text */
			if( $method['login_folder_name'] == 'live' )
			{
				continue;
			}

			if( $method['login_user_id'] == 'either' )
			{
				$username	= true;
				$email		= true;
				break;
			}
			if( $method['login_user_id'] == 'username' )
			{
				$username	= true;
			}
			else if( $method['login_user_id'] == 'email' )
			{
				$email		= true;
			}
  		}
  		
  		if( $username AND !$email )
  		{
  			return 1;
  		}
  		else if( !$username AND $email )
  		{
  			return 2;
  		}	
  		else if( $username AND $email )
  		{
  			return 3;
  		}

		//-----------------------------------------
		// If we're here, none of the methods
		//	want username or email, which is bad
		//-----------------------------------------
		
  		else
  		{
  			return 1;
  		}
  	}
  	
	/**
	 * Get additional login form HTML add/replace
	 *
	 * @return	mixed		Null or Array [0=Add or replace flag, 1=Array of HTML blocks to add/replace with]
	 */
  	public function additionalFormHTML()
  	{
  		$has_more_than_one	= false;
  		$additional_details	= array();
  		$add_or_replace		= null;
  		
  		if( count($this->login_methods) > 1 )
  		{
  			$has_more_than_one	= true;
  			$add_or_replace		= 'add';
  		}

		foreach( $this->login_methods as $k => $method )
		{
			if( !$has_more_than_one )
			{
				if( $method['login_replace_form'] == 1 )
				{
					$add_or_replace	= 'replace';
				}
				else
				{
					$add_or_replace	= 'add';
				}
			}
			
			if( $this->is_admin_auth )
			{
				if( $method['login_alt_acp_html'] )
				{
					$additional_details[]	= $method['login_alt_acp_html'];
				}
			}
			else
			{
				if( $method['login_alt_login_html'] )
				{
					$additional_details[]	= $method['login_alt_login_html'];
				}
			}
  		}
  		
		if( count($additional_details) )
		{
			return array( $add_or_replace, $additional_details );
		}
		else
		{
			return null;
		}
  	}
}