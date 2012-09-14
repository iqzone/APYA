<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Twitter Connect Library
 * Created by Matt Mecham
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 *
 */

class facebook_connect
{
	/**#@+
	* Registry Object Shortcuts
	*
	* @access	protected
	* @var		object
	*/
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	/**#@-*/
	
	/**
	 * Facebook wrapper
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $_api;
	
	/**
	 * Facebook OAUTH wrapper
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $_oauth;
	
	/**
	 * IPBs log in handler
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $_login;
	
	/**
	 * User connected
	 * 
	 * @access	protected
	 * @var		boolean
	 */
	protected $_connected = false;
	
	/**
	 * User: Token
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_userToken;
	
	/**
	 * User: ID
	 *
	 * @access	protected
	 * @var		int
	 */
	protected $_userId;
	
	/**
	 * User: Data
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_userData = array();
	
	/**
	 * Required permissions
	 *
	 * @access	public
	 * @var		array
	 */
	public $extendedPerms = array( 'email', 'read_stream', 'publish_stream', 'user_photos' );
	
	/**
	 * Construct.
	 * $this->memberData['twitter_token'] $this->memberData['twitter_secret']
	 * @param	object		Registry object
	 * @param	string		Facebook user token
	 * @param	int			User ID
	 * @param	boolean		Force an exception to be thrown rather than output error (used in IPSMember::buildDisplayPhoto)
	 * @access	public
	 * @return	@e void
	 */
	public function __construct( $registry, $token='', $userId=0, $forceThrowException=false )
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
		
		define("FACEBOOK_APP_ID"      , trim( $this->settings['fbc_appid'] ) );
		define("FACEBOOK_APP_SECRET"  , trim( $this->settings['fbc_secret'] ) );
		define("FACEBOOK_CALLBACK"    , $this->settings['_original_base_url'] . '/interface/facebook/index.php?m=' . $this->memberData['member_id'] );
				
		/* Auto do it man */
		if ( ! $token AND $this->memberData['member_id'] AND $this->memberData['fb_token'] )
		{
			$token  = $this->memberData['fb_token'];
		}
		
		/* Auto do it man */
		if ( ! $userId AND $this->memberData['member_id'] AND $this->memberData['fb_uid'] )
		{
			$userId  = $this->memberData['fb_uid'];
		}
		
		$this->_userToken  = trim( $token );
		$this->_userId     = trim( $userId ); /* never int - max ids are larger than int */
		
		/* Test */
		if ( ! FACEBOOK_APP_ID OR ! FACEBOOK_APP_SECRET )
		{
			/* Give upgraders a helping hand */
			if ( ! FACEBOOK_APP_ID )
			{ 
				if ( $forceThrowException === false )
				{ 
					$this->registry->output->showError( $this->lang->words['gbl_fb_no_app_id'], 1090001 );
				}
				else
				{ 
					throw new Exception( 'FACEBOOK_NO_APP_ID' );
				}
			}
			else
			{
				throw new Exception( 'FACEBOOK_NOT_SET_UP' );
			}
		}
		
		/* Reset the API */
		$this->resetApi( $token, $userId );
	}
	
	/**
	 * Resets API
	 *
	 * @access	public
	 * @param	string		OAUTH user token
	 */
	public function resetApi( $token='', $userId='' )
	{
		$this->_userToken  = trim( $token );
		$this->_userId     = trim( $userId );
		
		/* A user token is always > 32 */
		if ( strlen( $this->_userToken ) <= 32 )
		{
			/* we store a tmp md5 key during auth, so ensure we don't pass this as a token */
			$this->_userToken = '';
		}
		
		/* Load oAuth */
		require_once( IPS_KERNEL_PATH . 'facebook/facebookoauth.php' );/*noLibHook*/
		$this->_oauth = new FacebookOAuth( FACEBOOK_APP_ID, FACEBOOK_APP_SECRET, FACEBOOK_CALLBACK, $this->extendedPerms );
		
		/* Load API */
		require_once( IPS_KERNEL_PATH . 'facebook/facebook.php' );/*noLibHook*/
		$this->_api = new Facebook( array( 'appId' => FACEBOOK_APP_ID, 'secret' => FACEBOOK_APP_SECRET, 'cookie' => true ) );
		
		if ( $this->_userToken AND $this->_userId  )
		{
			try
			{
				$_userData = $this->_api->api('me', array( 'access_token' => $this->_userToken ) );
			}
			catch( Exception $e )
			{
				/* Try re-authorising */
				if ( stristr( $e->getMessage(), 'invalid' ) )
				{
					$this->redirectToConnectPage();
				}
			}
			
			if ( $_userData['id'] AND $_userData['id'] == $this->_userId )
			{
				$this->_userData  = $_userData;
				$this->_connected = true;
			}
			else
			{
				$this->_userData  = array();
				$this->_connected = false;
			}
		}
		else
		{
			$this->_userData  = array();
			$this->_connected = false;
		}
	}
	
	/**
	 * Revoke app authorization
	 *
	 * @access	public
	 * @return	boolean
	 */
	public function revokeAuthorization()
	{
		if ( $this->_userToken AND $this->_userId )
		{
			try
			{
				$val = $this->_api->api( array( 'method' => 'auth.revokeAuthorization', 'access_token' => $this->_userToken, 'uid' => $this->_userId ) );
			}
			catch( Exception $e )
			{
				$this->registry->output->logErrorMessage( $e->getMessage(), 'FB-EXCEPTION' );
			}
		}
		
		return $val;
	}

	
	/**
	 * User has removed app from Facebook
	 * @link http://wiki.developers.facebook.com/index.php/Post-Remove_Callback_URL
	 *
	 * @access	public
	 */
	public function userHasRemovedApp()
	{
		/* INIT */
		$sig    = '';
		$userId = intval( $_POST['fb_sig_user'] );
		
		/* Generate signature */
		ksort($_POST);
		
		foreach( $_POST as $key => $val )
		{
    		if ( substr( $key, 0, 7 ) == 'fb_sig_' )
    		{
        		$sig .= substr( $key, 7 ) . '=' . $val;
    		} 
		}

		$sig   .= FACEBOOK_APP_SECRET;
		$verify = md5($sig);
	
		if ( $userId AND $verify == $_POST['fb_sig'] )
		{
			/* Load user */
			$_member = IPSMember::load( $userId, 'all', 'fb_uid' );
			
			if ( $_member['member_id'] )
			{
   				/* Remove any FB stuffs */
   				IPSMember::save( $_member['member_id'], array( 'core' => array( 'fb_uid' => 0, 'fb_lastsync' => 0, 'fb_session' => '', 'fb_emailhash' => '', 'fb_token' => '' ) ) );
   	   		}
   	   	}
   	}
	
	/**
	 * Fetch user has app permission
	 * Wrapper so we can change it later
	 *
	 * @access	public
	 * @param	string	Permission mask ('email', etc)
	 * @return	boolean
	 */
	public function fetchHasAppPermission( $permission )
	{
		if ( $this->_userToken AND $this->_userId )
		{
			try
			{
				$val = $this->_api->api( array( 'method' => 'users.hasAppPermission', 'access_token' => $this->_userToken, 'uid' => $this->_userId, 'ext_perm' => $permission ) );
			}
			catch( Exception $e )
			{
				$this->registry->output->logErrorMessage( $e->getMessage(), 'FB-EXCEPTION' );
			}
		}
		
		return $val;
	}
	
	/**
	 * Return user data
	 *
	 * @access	public
	 * @return	array
	 */
	public function fetchUserData( $token='' )
	{
		$token = ( $token ) ? $token : $this->_userToken;
		
		if ( $token AND is_array( $this->_userData ) AND $this->_userData['id'] AND ! isset( $this->_userData['pic'] ) )
		{
			/* Query extra data - returns annoying https images */
			try
			{
				$updates = $this->_api->api( array( 'method'       => 'fql.query',
	        									    'query'        => 'select pic_small, pic_big, pic_square, pic, timezone, sex from user where uid=' . $this->_userData['id'],
	        									    'access_token' => $token ) );
        	}
        	catch( Exception $e )
        	{ 
        		/* Try re-authorising */
				if ( stristr( $e->getMessage(), 'invalid' ) )
				{
					$this->redirectToConnectPage();
				}
        	}
        							    
        	if ( count( $updates[0] ) )
        	{
        		foreach( $updates[0] as $k => $v )
        		{
        			$this->_userData[ $k ] = $v;
        		}
        	}
        	
        	/* Now fetch about information */
        	try
        	{
        		$aboutme = $this->_api->api( $this->_userData['id'], 'GET', array( 'access_token' => $token ) );
    		}
    		catch( Exception $e )
    		{
    			/* Try re-authorising */
				if ( stristr( $e->getMessage(), 'invalid' ) )
				{
					$this->redirectToConnectPage();
				}
    		}
        	
        	if ( count( $aboutme ) )
        	{
        		foreach( $aboutme as $k => $v )
        		{
        			if ( $k == 'about' )
        			{
        				$v = nl2br( $v );
        			}
        			
        			$this->_userData[ $k ] = $v;
        		}
        	}
		}
		
		return $this->_userData;
	}
	
	/**
	 * Return whether or not the user is connected to twitter
	 *
	 * @access	public
	 * @return	boolean
	 */
	public function isConnected()
	{
		return ( $this->_connected == true ) ? true : false;
	}
	
	/**
	 * Post a link to the user's FB wall
	 *
	 * @access	public
	 * @param	string		URL
	 * @param	string		Comment (can be NUFING)
	 */
	public function postLinkToWall( $url, $comment='', $shorten=true )
	{
		$memberData = $this->memberData;
		
		/* Got a member? */
		if ( ! $memberData['member_id'] )
		{
			throw new Exception( 'NO_MEMBER' );
		}
		
		/* Linked account? */
		if ( ! $memberData['fb_uid'] OR ! $memberData['fb_token'] )
		{
			throw new Exception( 'NOT_LINKED' );
		}
		
		/* Shorten? */
		if ( $shorten )
		{
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/url/shorten.php', 'urlShorten' );
			$shortenApi  = new $classToLoad();
			
			$data = $shortenApi->shorten( $url, IPS_URL_SHORTEN_SERVICE );
	 		$url  = $data['url'];
		}	
	 	
		/* POST the data */
		try
		{
			$this->_api->api( 'me/links', 'POST', array( 'access_token' => $this->_userToken, 'link' => $url, 'message' => $comment ) );
		}
		catch( Exception $e )
		{
			$this->registry->output->logErrorMessage( $e->getMessage(), 'FB-EXCEPTION' );
		}
	}
	
	/**
	 * Post a status update to Facebook based on native content
	 * Which may be longer and such and so on and so forth, etc
	 *
	 * @access	public
	 * @param	string		Content
	 * @param	string		URL to add
	 * @param	bool		Always add the URL regardless of content length
	 */
	public function updateStatusWithUrl( $content, $url, $alwaysAdd=false )
	{
		$memberData = $this->memberData;
		
		/* Got a member? */
		if ( ! $memberData['member_id'] )
		{
			throw new Exception( 'NO_MEMBER' );
		}
		
		/* Linked account? */
		if ( ! $memberData['fb_uid'] )
		{
			throw new Exception( 'NOT_LINKED' );
		}
		
		/* Ensure content is correctly de-html-ized */
		$content = IPSText::UNhtmlspecialchars( $content );
		
		/* Ensure it's converted cleanly into utf-8 */
    	$content = html_entity_decode( $content, ENT_QUOTES, 'UTF-8' );
    	
		/* Is the text longer than 140 chars? */
		if ( $alwaysAdd === TRUE or IPSText::mbstrlen( $content ) > 500 )
		{
			/* Leave 26 chars for URL shortener */
			$content = IPSText::mbsubstr( $content, 0, 474 ) . '...';
			
			if ( IPSText::mbstrlen( $url ) > 26 )
			{
				/* Generate short URL */
				$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/url/shorten.php', 'urlShorten' );
				$shorten  = new $classToLoad();
	 			
	 			try
	 			{
	 				$data = $shorten->shorten( $url, IPS_URL_SHORTEN_SERVICE );
	 				$url  = $data['url'];
	 			}
	 			catch( Exception $ex )
	 			{
	 				/* Stop the exception bubbling back to parent classes */
	 			}
	 		}
 			
 			$content .= ' ' . $url;
		}
		
		/* POST the data */
		try
		{
			$this->_api->api( array( 'method' => 'users.setStatus', 'access_token' => $this->_userToken, 'uid' => $this->_userId, 'status' => $content, 'status_includes_verb' => true ) );
		}
		catch( Exception $e )
		{
			$this->registry->output->logErrorMessage( $e->getMessage(), 'FB-EXCEPTION' );
		}
	}
	
	/**
	 * Redirects a user to the oauth connect page.
	 *
	 * @access	public
	 * @return	redirect
	 */
	public function redirectToConnectPage()
	{
		/* Stop - no need to redirect if ACP / IN_TASK  */
		if ( IN_ACP OR IPS_IS_TASK )
		{
			return false;
		}
		
		/* Reset api to ensure user is not logged in */
		$this->resetApi();
		
		/* Append OAUTH URL */
		$_urlExtra = '';
		$key       = md5( uniqid( microtime() ) );
		$_urlExtra = '&key=' . $key;
		
		if ( $this->request['_reg'] )
		{
			$_urlExtra .= '&_reg=1';
		}
		
		/* Update user's row */
		if ( $this->memberData['member_id'] )
		{
			IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'fb_token'  => $key ) ) );
		}
		
		/* Is mobile? */
		if ( $this->member->isMobileApp || ! empty( $_REQUEST['mobile'] ) )
		{
			$this->_oauth->setFormFactor( 'touch' );
		}
		
		/* Update callback url */
		$this->_oauth->setCallBackUrl( FACEBOOK_CALLBACK . $_urlExtra );
		
		$url = $this->_oauth->getAuthorizeURL();
		$this->registry->output->silentRedirect( $url );
	}
	
	/**
	 * Completes the connection
	 *
	 * @access	public
	 * @return	redirect
	 */
	public function finishLogin()
	{
		/* From reg flag */
		if ( $_REQUEST['code'] )
		{
			/* Load oAuth */
			require_once( IPS_KERNEL_PATH . 'facebook/facebookoauth.php' );/*noLibHook*/
			$this->_oauth = new FacebookOAuth( FACEBOOK_APP_ID, FACEBOOK_APP_SECRET, FACEBOOK_CALLBACK, $this->extendedPerms );
			
			/* Load API */
			require_once( IPS_KERNEL_PATH . 'facebook/facebook.php' );/*noLibHook*/
			$this->_api = new Facebook( array( 'appId' => FACEBOOK_APP_ID, 'secret' => FACEBOOK_APP_SECRET, 'cookie' => true ) );
			
			/* Ensure URL is correct */
			$_urlExtra = '';
			
			if ( $_REQUEST['key'] )
			{
				$_urlExtra .= '&key=' . $_REQUEST['key'];
			}
			
			if ( $_REQUEST['_reg'] )
			{
				$_urlExtra .= '&_reg=1';
			}
			
			/* Update callback url */
			$this->_oauth->setCallBackUrl( FACEBOOK_CALLBACK . $_urlExtra );
			
			/* Generate oAuth token */
			$rToken = $this->_oauth->getAccessToken( $_REQUEST['code'] );
			
			if ( is_string( $rToken ) )
			{
				try
				{
					$_userData = $this->_api->api('me', array( 'access_token' => $rToken ) );
				}
				catch( Exception $e )
				{
					/* Try re-authorising */
					if ( stristr( $e->getMessage(), 'invalid' ) )
					{
						$this->redirectToConnectPage();
					}
				}
				
				/* A little gymnastics */
				$this->_userData = $_userData;
				$_userData = $this->fetchUserData( $rToken );
				
				/* Got a member linked already? */
				$_member = IPSMember::load( $_userData['id'], 'all', 'fb_uid' );
			
				/* Not connected, check email address */
				if ( ! $_member['member_id'] AND $_userData['email'] )
				{
					$_member = IPSMember::load( $_userData['email'], 'all', 'email' );
					
					/* We do have an existing account, so trash email forcing user to sign up with new */
					if ( $_member['member_id'] )
					{
						/* Update row */
						IPSMember::save( $_member['member_id'], array( 'core' => array( 'fb_uid' => $_userData['id'], 'fb_token' => $rToken ) ) );
					}
				}
								
				if ( $_member['member_id'] )
				{
					$memberData = $_member;
					
					/* Ensure user's row is up to date */
					IPSMember::save( $memberData['member_id'], array( 'core' => array( 'fb_token' => $rToken ) ) );
					    
					/* Here, so log us in!! */
					$data = $this->_login()->loginWithoutCheckingCredentials( $memberData['member_id'], TRUE );
					
					$this->registry->getClass('output')->silentRedirect( $this->settings['_original_base_url'], '', true, 'act=idx' );
				}
				else
				{
					/* No? Create a new member */
					foreach( array( 'fbc_s_pic', 'fbc_s_status', 'fbc_s_aboutme' ) as $field )
					{
						$toSave[ $field ] = 1;
					}

					$fb_bwoptions = IPSBWOptions::freeze( $toSave, 'facebook' );
					$safeFBName   = ( IPS_DOC_CHAR_SET != 'UTF-8' ) ? IPSText::utf8ToEntities( $_userData['name'] ) : $_userData['name'];
					
					/* Make sure usernames are safe */
					if ( $this->settings['username_characters'] )
					{
						$check_against = preg_quote( $this->settings['username_characters'], "/" );
						$check_against = str_replace( '\-', '-', $check_against );
						
						$safeFBName = preg_replace( '/[^' . $check_against . ']+/i', '', $safeFBName );
					}
					
					/* Check ban filters? */
					if ( IPSMember::isBanned( 'email', $_userData['email'] ) or IPSMember::isBanned( 'name', $safeFBName ) )
					{
						$this->registry->output->showError( 'you_are_banned', 1090003 );
					}
					
					$displayName = ( $this->settings['fb_realname'] == 'enforced' ) ? $safeFBName : '';
		
					/* From reg, so create new account properly */
					$toSave = array( 'core' 		 => array(  'name' 				     => $safeFBName,
													 		    'members_display_name'   => $displayName,
													 		    'members_created_remote' => 1,
													 		    'member_group_id'		 => ( $this->settings['fbc_mgid'] ) ? $this->settings['fbc_mgid'] : $this->settings['member_group'],
															    'email'                  => $_userData['email'],
															    'fb_uid'                 => $_userData['id'],
															    'time_offset'            => $_userData['timezone'],
																'members_auto_dst'		 => 1,
															    'fb_token'               => $rToken ),
									'extendedProfile' => array( 'pp_about_me'            => IPSText::getTextClass( 'bbcode' )->stripBadWords( IPSText::convertCharsets( $_userData['about'], 'utf-8', IPS_DOC_CHAR_SET ) ),
																'fb_bwoptions'    		 => $fb_bwoptions ) );
	
					
					$memberData = IPSMember::create( $toSave, FALSE, FALSE, TRUE );
					
					if ( ! $memberData['member_id'] )
					{
						throw new Exception( 'CREATION_FAIL' );
					}
								
					/* Sync up photo */
					$this->syncMember( $memberData['member_id'] );
					
					$pmember = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'members_partial', 'where' => "partial_member_id=" . $memberData['member_id'] ) );

					if ( $pmember['partial_member_id'] )
					{
						$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . 'app=core&module=global&section=register&do=complete_login&mid='. $memberData['member_id'].'&key='.$pmember['partial_date'] );
					}
					else
					{
						/* Already got a display name */
						if ( $displayName )
						{
							/* Here, so log us in!! */
							$data = $this->_login()->loginWithoutCheckingCredentials( $memberData['member_id'], TRUE );
							
							IPSLib::runMemberSync( 'onCompleteAccount', $memberData );
							
							if ( $this->settings['new_reg_notify'] )
							{
								$this->registry->class_localization->loadLanguageFile( array( 'public_register' ), 'core' );
								
								IPSText::getTextClass('email')->setPlainTextTemplate( IPSText::getTextClass('email')->getTemplate("admin_newuser") );
							
								IPSText::getTextClass('email')->buildMessage( array( 'DATE'			=> $this->registry->getClass('class_localization')->getDate( time(), 'LONG', 1 ),
																					 'LOG_IN_NAME'  => $safeFBName,
																					 'EMAIL'		=> $_userData['email'],
																					 'IP'			=> $this->member->ip_address,
																					 'DISPLAY_NAME'	=> $displayName ) );
															
								IPSText::getTextClass('email')->subject = sprintf( $this->lang->words['new_registration_email'], $this->settings['board_name'] );
								IPSText::getTextClass('email')->to      = $this->settings['email_in'];
								IPSText::getTextClass('email')->sendMail();
							}
		
							$this->registry->getClass('output')->silentRedirect( $data[1] ? $data[1] : $this->settings['base_url'] );
						}
						else
						{
							throw new Exception( 'CREATION_FAIL' );
						}
					}
				}
			}
			else
			{
				throw new Exception( 'CREATION_FAIL' );
			}
		}
		else
		{
			/* Need to re-auth */
			
		}
	}
	
	/**
	 * Completes the connection
	 *
	 * @access	public
	 * @return	redirect
	 */
	public function finishConnection()
	{
		if ( $_REQUEST['m'] AND $_REQUEST['code'] )
		{
			/* Load user */
			$member = IPSMember::load( intval( $_REQUEST['m'] ) );
		
			if ( $member['fb_token'] == $_REQUEST['key'] )
			{
				/* Load oAuth */
				require_once( IPS_KERNEL_PATH . 'facebook/facebookoauth.php' );/*noLibHook*/
				$this->_oauth = new FacebookOAuth( FACEBOOK_APP_ID, FACEBOOK_APP_SECRET, FACEBOOK_CALLBACK, $this->extendedPerms );
				
				/* Load API */
				require_once( IPS_KERNEL_PATH . 'facebook/facebook.php' );/*noLibHook*/
				$this->_api = new Facebook( array( 'appId' => FACEBOOK_APP_ID, 'secret' => FACEBOOK_APP_SECRET, 'cookie' => true ) );
				
				/* Ensure URL is correct */
				$_urlExtra = '';
				
				if ( $_REQUEST['key'] )
				{
					$_urlExtra .= '&key=' . $_REQUEST['key'];
				}
				
				if ( $_REQUEST['_reg'] )
				{
					$_urlExtra .= '&_reg=1';
				}
				
				/* Update callback url */
				$this->_oauth->setCallBackUrl( FACEBOOK_CALLBACK . $_urlExtra );
			
				/* Generate oAuth token */
				$rToken = $this->_oauth->getAccessToken( $_REQUEST['code'] );

				if ( is_string( $rToken ) )
				{
					try
					{
						$_userData = $this->_api->api('me', array( 'access_token' => $rToken ) );
					}
					catch( Exception $e )
					{
						$this->registry->output->logErrorMessage( $e->getMessage(), 'FB-EXCEPTION' );
					}
					
					/* Ensure user's row is up to date */
					IPSMember::save( $member['member_id'], array( 'core' => array( 'fb_uid'    => $_userData['id'],
																				   'fb_token'  => $rToken ) ) );
				}
			}
		}
		
		/* Redirect back to settings page */
		$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . 'app=core&module=usercp&tab=core&area=facebook' );
	}

	/**
	 * Finish a log-in connection
	 * WARNING: NO PERMISSION CHECKS ARE PERFORMED IN THIS FUNCTION.
	 *
	 * @access		public
	 * @param		int			Forum ID of original member (member to keep)
	 * @param		int			Forum ID of linking member  (member to remove)
	 * @return		boolean
	 */
	public function finishNewConnection( $originalId, $newId )
	{
		if ( $originalId AND $newId )
		{
			$original = IPSMember::load( $originalId, 'all' );
			$new      = IPSMember::load( $newId, 'all' );
			
			if ( $original['member_id'] AND $new['fb_uid'] AND $new['fb_token'] )
			{
				IPSMember::save( $original['member_id'], array( 'core' => array( 'fb_uid' => $new['fb_uid'], 'fb_token' => $new['fb_token'] ) ) );
				
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Function to resync a member's Facebook data
	 *
	 * @access	public
	 * @param	mixed		Member Data in an array form (result of IPSMember::load( $id, 'all' ) ) or a member ID
	 * @return	array 		Updated member data	
	 *
	 * EXCEPTION CODES:
	 * NO_MEMBER		Member ID does not exist
	 * NOT_LINKED		Member ID or data specified is not linked to a FB profile
	 */
	public function syncMember( $memberData )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$exProfile = array();
		
		/* Do we need to load a member? */
		if ( ! is_array( $memberData ) )
		{
			$memberData = IPSMember::load( intval( $memberData ), 'all' );
		}
		
		/* Got a member? */
		if ( ! $memberData['member_id'] )
		{
			throw new Exception( 'NO_MEMBER' );
		}
		
		/* Linked account? */
		if ( ! $memberData['fb_uid'] )
		{
			throw new Exception( 'NOT_LINKED' );
		}
		
		/* Thaw Options */
		$bwOptions = IPSBWOptions::thaw( $memberData['fb_bwoptions'], 'facebook' );
		
		/* Grab the data */
		try
		{
			$this->resetApi( $memberData['fb_token'], $memberData['fb_uid'] );
			
			if ( $this->isConnected() )
			{
				$user = $this->fetchUserData();
				
				/* Load library */
				if ( $bwOptions['fbc_s_pic'] )
				{
					$classToLoad  = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/member/photo.php', 'classes_member_photo' );
					$photo = new $classToLoad( $this->registry );
					
					$photo->save( $memberData, 'facebook' );
				}			

				if ( $bwOptions['fbc_si_status'] AND ( isset( $memberData['gbw_no_status_import'] ) AND ! $memberData['gbw_no_status_import'] ) AND !$memberData['bw_no_status_update'] )
				{
					/* Fetch timeline */
					//$memberData['tc_last_sid_import'] = ( $memberData['tc_last_sid_import'] < 1 ) ? 100 : $memberData['tc_last_sid_import'];
					$_updates = $this->fetchUserTimeline( $user['id'], 0, true );
					
					/* Got any? */
					if ( count( $_updates ) )
					{
						$update = array_shift( $_updates );
						
						if ( is_array( $update ) AND isset( $update['message'] ) )
						{
							/* @link	http://community.invisionpower.com/tracker/issue-27746-video-in-facebook-status */
							$update['message']	= strip_tags( $update['message'] );
						
							/* Load status class */
							if ( ! $this->registry->isClassLoaded( 'memberStatus' ) )
							{
								$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/member/status.php', 'memberStatus' );
								$this->registry->setClass( 'memberStatus', new $classToLoad( ipsRegistry::instance() ) );
							}
							
							/* Set Author */
							$this->registry->getClass('memberStatus')->setAuthor( $memberData );
							$this->registry->getClass('memberStatus')->setStatusOwner( $memberData );
							
							/* Convert if need be */
							if ( IPS_DOC_CHAR_SET != 'UTF-8' )
							{
								$update['message'] = IPSText::utf8ToEntities( $update['message'] );
							}
							
							/* Set Content */
							$this->registry->getClass('memberStatus')->setContent( trim( IPSText::getTextClass( 'bbcode' )->stripBadWords( $update['message'] ) ) );
							
							/* Set as imported */
							$this->registry->getClass('memberStatus')->setIsImport( 1 );
							
							/* Set creator */
							$this->registry->getClass('memberStatus')->setCreator( 'facebook' );
		
							/* Can we reply? */
							if ( $this->registry->getClass('memberStatus')->canCreate() )
					 		{
								$this->registry->getClass('memberStatus')->create();
								
								//$exProfile['tc_last_sid_import'] = $update['id'];
							}
						}
					}
				}
				
				/* Update member */
				IPSMember::save( $memberData['member_id'], array( 'core' 			=> array( 'fb_lastsync' => time() ),
																  'extendedProfile' => $exProfile ) );
			
				/* merge and return */
				$memberData['fb_lastsync'] = time();
				$memberData = array_merge( $memberData, $exProfile );
			}
			else
			{
				/* Update member even if it failed so it's not selected on next task run */
				IPSMember::save( $memberData['member_id'], array( 'core' => array( 'fb_lastsync' => time() ) ) );
			}
		}
		catch( Exception $e )
		{
			/* Update member even if it failed so it's not selected on next task run */
			IPSMember::save( $memberData['member_id'], array( 'core' => array( 'fb_lastsync' => time() ) ) );
			
			$this->registry->output->logErrorMessage( $e->getMessage(), 'FB-EXCEPTION' );
		}
		
		return $memberData;
	}
	
	/**
	 * Fetch a user's recent status updates (max 50)
	 *
	 * @access	public
	 * @param	int		Twitter ID
	 * @param	bool	Strip @replies (true default)
	 * @param	int		Minimum ID to grab from
	 * @return	array
	 */
	public function fetchUserTimeline( $userId=0, $minId=0, $stripReplies=true )
	{
		$userId = ( $userId ) ? $userId : $this->_userData['id'];
		$count  = 50;
		$final  = array();
		
		if ( $this->_userToken AND $userId )
		{
			try
			{
				$updates = $this->_api->api( array( 'method'       => 'fql.query',
		        									'query'        => 'select uid,status_id,message from status where uid=' . $userId . ' and status_id > ' . $minId . ' ORDER BY time DESC LIMIT 0, ' . $count,
		        									'access_token' => $this->_userToken ) );
	        }
	        catch( Exception $e )
	        {
	        	$this->registry->output->logErrorMessage( $e->getMessage(), 'FB-EXCEPTION' );
	        }
			
			if ( is_array( $updates ) AND count( $updates ) )
			{
				foreach( $updates as $update )
				{
					$final[] = $update;
				}
			}
		}
		
		return $final;
	}
	
	/**
	 * Accessor for the log in functions
	 *
	 * @access	public
	 * @return	object
	 */
	public function _login()
	{
		if ( ! is_object( $this->_login ) )
		{
			$classToLoad  = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/handlers/han_login.php', 'han_login' );
			$this->_login = new $classToLoad( $this->registry );
	    	$this->_login->init();
		}
		
		return $this->_login;
	}
}