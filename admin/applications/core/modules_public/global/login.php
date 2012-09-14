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

class public_core_global_login extends ipsCommand
{
	/**
	 * Login handler object
	 *
	 * @var		object
	 */
	protected $han_login;
	
	/**
	 * Facebook connect class
	 *
	 * @var		object
	 */
	protected $_facebook;
	
	/**
	 * Initiate login handler
	 *
	 * @return	@e void
	 */
	public function initHanLogin()
	{
    	$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/handlers/han_login.php', 'han_login' );
    	$this->han_login =  new $classToLoad( $this->registry );
    	$this->han_login->init();
	}
	
	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		/* Make object */
		$this->registry   =  $registry;
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		
		$this->registry->getClass( 'class_localization' )->loadLanguageFile( array( 'public_login' ), 'core' );
    	
    	//-----------------------------------------
    	// Load handler...
    	//-----------------------------------------
    	
		$this->initHanLogin();
		
    	//-----------------------------------------
    	// Are we enforcing log ins?
    	//-----------------------------------------
    	
    	$msg = "";
    	
    	if ( !$this->request['do'] == 'showForm' AND $this->settings['force_login'] == 1 )
    	{
    		$msg = 'admin_force_log_in';
    	}
    	
    	//-----------------------------------------
    	// Meta tags
    	//-----------------------------------------
    	
    	$this->registry->output->addMetaTag( 'robots', 'noindex' );
    	
    	//-----------------------------------------
    	// What to do?
    	//-----------------------------------------
    	
    	switch( $this->request['do'] )
    	{
    		case 'process':
    			$return = $this->doLogin();

				if( $return[2] )
				{
					//$this->registry->getClass('output')->showError( $return[2], 1014 );
					$this->loginForm( $return[2], $return[3] );
				}
				else
				{
    				$this->registry->getClass('output')->redirectScreen( $return[0], $return[1] );
				}
    		break;
    		
    		case 'logout':
    			$return = $this->doLogout();
    			
    			/* URL */
    			$return[2] = $return[2] ? $return[2] : '';

    			if( $return[0] == 'immediate' )
				{
					$this->registry->getClass('output')->silentRedirect( $return[2] );
				}
				else
				{
					$this->registry->getClass('output')->redirectScreen( $return[1], $return[2] );
				}
    		break;
    	
    		case 'deleteCookies':
    			$return = $this->deleteCookies();
    			$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] );
    		break;
    			
    		case 'autologin':
    			$return = $this->autoLogin();
    		break;
    			
    		case 'showForm':
    		default:
    			$return = $this->loginForm($msg);
    		break;
    	}
    	
    	//-----------------------------------------
    	// If we have any HTML to print, do so...
    	//-----------------------------------------
    	
    	$this->registry->getClass('output')->addContent( $this->output );
        $this->registry->getClass('output')->sendOutput();
 	}
	
	/**
	 * Attempt to automatically log a user in
	 *
	 * @return	array		[0=Words to display,1=URL to send to]
	 */
 	public function autoLogin()
 	{
 		/* Verify the login */
		$this->han_login->verifyLogin();
		
		/* Lang Bits */
 		$true_words  = $this->lang->words['logged_in'];
 		$false_words = $this->lang->words['not_logged_in'];
 		$method      = 'no_show';
 		
 		/* Register Redirect */
 		if ($this->request['fromreg'] == 1)
 		{
 			$true_words  = $this->lang->words['reg_log_in'];
 			$false_words = $this->lang->words['reg_not_log_in'];
 			$method = 'show';
 		}
 		/* Email Redirect */
 		else if ($this->request['fromemail'] == 1)
 		{
 			$true_words  = $this->lang->words['email_log_in'];
 			$false_words = $this->lang->words['email_not_log_in'];
 			$method = 'show';
 		}
 		/* Password Redirect */
 		else if ($this->request['frompass'] == 1)
 		{
 			$true_words  = $this->lang->words['pass_log_in'];
 			$false_words = $this->lang->words['pass_not_log_in'];
 			$method = 'show';
 		}
 		
 		if( $this->memberData['member_id'] )
 		{
			/* Member Sync */
			IPSLib::runMemberSync( 'onLogin', $this->memberData );
		
			if ( ! $this->request['fromreg'] )
			{
				IPSCookie::set('session_id', '0', -1 );
			}
			
 			if( $method == 'show' )
 			{
 				$this->registry->getClass('output')->redirectScreen( $true_words, $this->settings['base_url'] );
 			}
 			else
 			{
 				$this->registry->getClass('output')->silentRedirect( $this->settings['board_url'] . '/index.php' );
 			}
 		}
 		else
 		{
 			if( $method == 'show' )
 			{
 				$this->registry->getClass('output')->redirectScreen( $false_words, $this->settings['base_url'] . 'app=core&module=global&section=login' );
 			}
 			else
 			{
 				$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . 'app=core&module=global&section=login' );
 			}
 		}
 	}
 	
	/**
	 * Delete a user's cookies
	 *
	 * @param	boolean		Check the key
	 * @return	mixed		Output error page if key checking fails, else boolean true
	 */
 	public function deleteCookies( $check_key=true )
 	{
		//-----------------------------------------
        // Check the md5 key
        //-----------------------------------------
        
	 	if( $check_key )
	 	{
			$key = $this->request['k'];

			if ( $key != $this->member->form_hash )
			{
				$this->registry->getClass('output')->showError( 'bad_delete_cookies_key', 2010 );
			}
		}

		//-----------------------------------------
		// Wipe out any forum password cookies
		//-----------------------------------------
        
		if ( is_array($_COOKIE) )
 		{
 			foreach( $_COOKIE as $cookie => $value )
 			{
 				if ( stripos( $cookie, $this->settings['cookie_id']."ipbforum" ) !== false )
 				{
 					IPSCookie::set( str_replace( $this->settings['cookie_id'], "", $cookie ), '-', -1 );
 				}

				if ( stripos( $cookie, $this->settings['cookie_id']."itemMarking_" ) !== false )
 				{
 					IPSCookie::set( str_replace( $this->settings['cookie_id'], "", $cookie ), '-', -1 );
 				}
 			}
 		}
 		
		//-----------------------------------------
		// And the rest of the cookies
		//-----------------------------------------
		
 		IPSCookie::set('pass_hash' , '-1');
 		IPSCookie::set('member_id' , '-1');
 		IPSCookie::set('session_id', '-1');
 		
 		return true;
	}  
	
	/**
	 * Show the login form
	 *
	 * @param	string		Message to show on login form
	 * @return	string		Login form HTML
	 */
    public function loginForm( $message="", $replacement='' )
    {
        //-----------------------------------------
        // INIT
        //-----------------------------------------
        
        $extra_form   = "";
        $show_form    = 1;
		$template     = '';
		$serviceClick = trim( $this->request['serviceClick'] );
		
        //-----------------------------------------
		// Are they banned?
		//-----------------------------------------
		
		if ( IPSMember::isBanned( 'ip', $this->member->ip_address ) )
		{
			$this->registry->getClass('output')->showError( 'you_are_banned', 2011, null, null, 403 );
		}
        
        if ( $message != "" )
        {
			if( $replacement )
			{
				$message = sprintf( $this->lang->words[ $message ], $replacement );
			}
			else
			{
        		$message	= $this->lang->words[ $message ];
			}
        	$name		= $this->request['UserName'] ? $this->request['UserName'] : $this->request['address'];
        	$message	= str_replace( "<#NAME#>", "<b>" . $name . "</b>", $message );
        
			$template .= $this->registry->getClass('output')->getTemplate('login')->errors($message);
		}
		
		//-----------------------------------------
		// Using an alternate log in form?
		//-----------------------------------------
		
		$this->han_login->checkLoginUrlRedirect();
		
		/* Did we click a service? */
		if ( $serviceClick )
		{
			switch ($serviceClick)
			{
				case 'facebook':
					$this->registry->getClass('output')->silentRedirect( $this->settings['_original_base_url'] . "/interface/facebook/index.php?_reg=1" );
				break;
				case 'twitter':
					$this->registry->getClass('output')->silentRedirect( $this->settings['_original_base_url'] . "/interface/twitter/index.php?_reg=1" );
				break;
			}
		}
		
		//-----------------------------------------
		// Extra  HTML?
		//-----------------------------------------
		
		$additionalForm	= $this->han_login->additionalFormHTML();
		
		if ( count($additionalForm[1]) )
		{
			if ( $additionalForm[0] == 'add' )
			{
				$extra_form	= $additionalForm[1];
				$show_form	= 1;
			}
			else
			{
				$template	.= $additionalForm[1];
				$show_form	= 0;
			}
		}

		//-----------------------------------------
		// Continue...
		//-----------------------------------------
		
		if ( $show_form )
		{
			if( $this->request['referer'] )
			{
				$http_referrer	= $this->request['referer'];
			}
			else
			{
				/* @link	http://community.invisionpower.com/tracker/issue-32302-login-redirect-when-activating */
				$_urlPieces	= @parse_url($this->settings['board_url']);

				if( strpos( my_getenv('HTTP_REFERER'), $_urlPieces['host'] ? $_urlPieces['host'] : $this->settings['board_url'] ) !== false )
				{
					$http_referrer = my_getenv('HTTP_REFERER');
				}
				else
				{
					$http_referrer	= '';
				}					
			}
			
			$login_methods	= false;
			$uses_name		= false;
			$uses_email		= false;
			
			foreach( $this->cache->getCache('login_methods') as $method )
			{
				$login_methods[ $method['login_folder_name'] ]	= $method['login_folder_name'];

				if( $method['login_user_id'] == 'username' or $method['login_user_id'] == 'either' )
				{
					$uses_name	= true;
				}
				
				if( $method['login_user_id'] == 'email' or $method['login_user_id'] == 'either' )
				{
					$uses_email	= true;
				}
			}
		
			if( $uses_name AND $uses_email )
			{
				$this->lang->words['enter_name']	= $this->lang->words['enter_name_and_email'];
			}
			else if( $uses_email )
			{
				$this->lang->words['enter_name']	= $this->lang->words['enter_useremail'];
			}
			else
			{
				$this->lang->words['enter_name']	= $this->lang->words['enter_username'];
			}

			$template .= $this->registry->getClass('output')->getTemplate('login')->showLogInForm( $this->lang->words['please_log_in'], htmlentities(urldecode($http_referrer)), $extra_form, $login_methods );
		}

		
		$this->registry->getClass('output')->addNavigation( $this->lang->words['log_in'], '' );
		$this->registry->getClass('output')->setTitle( $this->lang->words['log_in'] . ' - ' . ipsRegistry::$settings['board_name'] );
		$this->registry->getClass('output')->addContent( $template );
        $this->registry->getClass('output')->sendOutput();
    }
    
	/**
	 * Verify login form submission and log user in
	 *
	 * @return	mixed		array [0=Words to show, 1=URL to send to, 2=error array]
	 */
    public function doLogin()
    {
    	if( !defined('IGNORE_AUTH_KEY') OR !IGNORE_AUTH_KEY )
    	{
			if ( $this->request['auth_key'] != $this->member->form_hash )
			{
				$this->registry->getClass('output')->showError( 'login_no_authkey', 10193, null, null, 403 );
			}
		}

		return $this->han_login->verifyLogin();
	}
	
	/**
	 * Log a user out
	 *
	 * @param	integer		Flag to check md5 key
	 * @return	mixed		Error message or array [0=immediate|redirect, 1=words to show, 2=URL to send to]
	 */
	public function doLogout( $check_key=true )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		if ( $check_key )
		{
			$key = $this->request['k'];
			
			# Check for funny business
			if ( $key != $this->member->form_hash )
			{
				$this->registry->getClass('output')->showError( 'bad_logout_key', 2012 );
			}
		}
		
		//-----------------------------------------
		// Set some cookies
		//-----------------------------------------
		
		IPSCookie::set( "member_id" , "0"  );
		IPSCookie::set( "pass_hash" , "0"  );
		
		if( is_array( $_COOKIE ) )
 		{
 			foreach( $_COOKIE as $cookie => $value)
 			{
 				if ( stripos( $cookie, $this->settings['cookie_id'] . 'ipbforumpass' ) !== false AND ! strstr( $value, 'mobileApp' ) )
 				{
 					IPSCookie::set( str_replace( $this->settings['cookie_id'], "", $cookie ), '-', -1 );
 				}
 			}
 		}

		//-----------------------------------------
		// Do it..
		//-----------------------------------------

		$this->member->sessionClass()->convertMemberToGuest();

		$privacy = intval( IPSMember::isLoggedInAnon($this->memberData) );
		
		IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'login_anonymous' => "{$privacy}&0", 'last_activity' => IPS_UNIX_TIME_NOW ) ) );

		//-----------------------------------------
		// Logout callbacks...
		//-----------------------------------------
		
		IPSLib::runMemberSync( 'onLogOut', $this->memberData );
		
		$this->han_login->logoutCallback();
		
		//-----------------------------------------
		// Return..
		//-----------------------------------------
		
		$url = "";
		
		if ( $this->request['return'] AND $this->request['return'] != "" )
		{
			$return = urldecode($this->request['return']);
			
			if ( strpos( $return, "http://" ) === 0 )
			{
				return array( 'immediate', '', $return );
			}
		}
		
		return array( 'redirect', $this->lang->words['thanks_for_logout'], $this->settings['base_url'] );
	}
}