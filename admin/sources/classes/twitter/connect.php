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

class twitter_connect
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
	 * Twitter OAUTH wrapper
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $_api;
	
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
	 * User: Secret
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_userSecret;
	
	/**
	 * User: Data
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_userData = array();
	
	/**
	 * Construct.
	 * $this->memberData['twitter_token'] $this->memberData['twitter_secret']
	 * @access	public
	 * @return	@e void
	 */
	public function __construct( $registry, $token='', $secret='' )
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
		
		define("CONSUMER_KEY"   , trim( ipsRegistry::$settings['tc_token'] ) );
		define("CONSUMER_SECRET", trim( ipsRegistry::$settings['tc_secret'] ) );
		define("OAUTH_CALLBACK" , $this->settings['_original_base_url'] . '/interface/twitter/index.php?m=' . $this->memberData['member_id'] );

		/* Auto do it man */
		if ( ! $token AND $this->memberData['member_id'] AND $this->memberData['twitter_token'] )
		{
			$token  = $this->memberData['twitter_token'];
			$secret = $this->memberData['twitter_secret'];
		}
		
		$this->_userToken  = trim( $token );
		$this->_userSecret = trim( $secret );
		
		/* Test */
		if ( ! CONSUMER_KEY OR ! CONSUMER_SECRET )
		{
			throw new Exception( 'TWITTER_NOT_SET_UP' );
		}

		/* Set include path.. */
		@set_include_path( IPS_KERNEL_PATH . 'twitter/' . PATH_SEPARATOR . ini_get( 'include_path' ) );/*noLibHook*/
		
		/* Reset the API */
		$this->resetApi( $token, $secret );
	}
	
	/**
	 * Resets API
	 *
	 * @access	public
	 * @param	string		OAUTH user token
	 * @param	string		OAUTH user secret
	 */
	public function resetApi( $token='', $secret='' )
	{
		$this->_userToken  = trim( $token );
		$this->_userSecret = trim( $secret );
		
		/* Load API */
		require_once( IPS_KERNEL_PATH . 'twitter/twitteroauth.php' );/*noLibHook*/
		$this->_api = new TwitterOAuth( CONSUMER_KEY, CONSUMER_SECRET, $this->_userToken, $this->_userSecret );
		
		if ( $this->_userToken AND $this->_userSecret )
		{
			$_userData = $this->_api->get('account/verify_credentials');
			
			if ( $_userData['id'] )
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
	 * Return user data
	 *
	 * @access	public
	 * @return	array
	 */
	public function fetchUserData()
	{
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
	 * Post a status update to twitter based on native content
	 * Which may be longer and such and so on and so forth, etc
	 *
	 * @access	public
	 * @param	string		Content
	 * @param	string		URL to add
	 * @param	bool		Always add the URL regardless of content length
	 */
	public function updateStatusWithUrl( $content, $url, $alwaysAdd=TRUE )
	{
		/* Ensure content is correctly de-html-ized */
		$content = IPSText::UNhtmlspecialchars( $content );
		
		/* Is the text longer than 140 chars? */
		if ( $alwaysAdd === TRUE or IPSText::mbstrlen( $content ) > 140 )
		{
			/* Leave 26 chars for URL shortener */
			if ( IPSText::mbstrlen( $content ) > 117 )
			{
				$content = IPSText::mbsubstr( $content, 0, 114 ) . '...';
			}
			
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
 			
 			return $this->updateStatus( $content . ' ' . $url );
		}
		else
		{
			/* Just post it */
			return $this->updateStatus( $content );
		}
	}
	
	/**
	 * Post a status update to twitter
	 *
	 * @access	public
	 * @return	mixed		status id (int) successful, FALSE, #ftl
	 */
	public function updateStatus( $text )
	{
		if ( IPSLib::twitter_enabled() && $text AND $this->isConnected() )
		{
			$status = $this->_api->post( 'statuses/update', array( 'status' => IPSText::convertCharsets( $text, IPS_DOC_CHAR_SET, 'utf-8' ) ) );
			$code   = $this->_api->http_code;
			
			/* 200 is OK, 403 is returned if API limit is hit */
			if ( $code == 200 AND $status['id_str'] )
			{
				/* Update member */
				if ( $this->memberData['member_id'] )
				{
					/* Update member */
					IPSMember::save( $this->memberData['member_id'], array( 'extendedProfile' => array( 'tc_last_sid_import' => $status['id_str'] ) ) );
				}
				
				return $status['id_str'];
			}
			else
			{
				return false;
			}
		}
		
		return false;
	}
	
	/**
	 * Redirects a user to the oauth connect page.
	 *
	 * @access	public
	 * @return	redirect
	 */
	public function redirectToConnectPage()
	{
		/* Reset api to ensure user is not logged in */
		$this->resetApi();
		
		/* Append OAUTH URL */
		$_urlExtra = '';
		$key       = '';
		
		/* From registration/log in? */
		if ( ! $this->memberData['member_id'] AND $this->request['_reg'] )
		{
			/* Create validating account for the member */
			$key = md5( uniqid( microtime() ) );
			
			/* Append URL with correct member ID and other params */
			$_urlExtra = '&_reg=1&key=' . $key;
		}
		
		/* Generate oAuth token */
		$rToken = $this->_api->getRequestToken( OAUTH_CALLBACK . $_urlExtra );
		
		if ( $rToken['oauth_token'] AND $rToken['oauth_token_secret'] )
		{
			/* From registration? */
			if ( $_urlExtra )
			{
				/* Create validating account for the member */
				$this->DB->insert( 'twitter_connect', array( 't_key'	=> $key,
															 't_token'  => $rToken['oauth_token'],
															 't_secret' => $rToken['oauth_token_secret'],
															 't_time'   => time() ) );
			}

			/* Update user's row */
			if ( $this->memberData['member_id'] )
			{
				IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'twitter_token'  => $rToken['oauth_token'],
																						 'twitter_secret' => $rToken['oauth_token_secret'] ) ) );
			}
																					 
			if ( $this->_api->http_code == 200 )
			{
				$url = $this->_api->getAuthorizeURL( $rToken['oauth_token'] );
    			$this->registry->output->silentRedirect( $url );
			}
			else
			{
				print "There was an error connecting to Twitter";
				exit();
			}
		}
		else
		{
			/* Twitter application is not set up correctly */
			$this->registry->output->showError( 'twitter_app_error', 0.717734 );
		}
	}
	
	/**
	 * Completes the connection
	 *
	 * @access	public
	 * @return	redirect
	 * 
	 */
	public function finishLogin()
	{
		/* From reg flag */
		$connectData = array( 't_key' => '' );
		
		if ( $_REQUEST['key'] )
		{
			$connectData = $this->DB->buildAndFetch( array( 'select' => '*',
															'from'	 => 'twitter_connect',
															'where'  => "t_key='" . IPSText::md5Clean( $_REQUEST['key'] ) . "'" ) );
															
			if ( ! $connectData['t_key'] )
			{
				throw new Exception( "NO_KEY_FOUND" );
			}
			
			/* Delete connect row */
			$this->DB->delete( 'twitter_connect', "t_key='" . IPSText::md5Clean( $_REQUEST['key'] ) . "'" );
			
			$member = array( 'twitter_token'  => $connectData['t_token'],
							 'twitter_secret' => $connectData['t_secret'] );
		}
		
		if ( $_REQUEST['oauth_token'] )
		{
			if ( $member['twitter_token'] == $_REQUEST['oauth_token'] )
			{
				/* Reset api to ensure user is not logged in */
				require_once( IPS_KERNEL_PATH . 'twitter/twitteroauth.php' );/*noLibHook*/
				$this->_api = new TwitterOAuth( CONSUMER_KEY, CONSUMER_SECRET, $member['twitter_token'], $member['twitter_secret'] );
				
				/* Generate oAuth token */
				$rToken = $this->_api->getAccessToken( $_REQUEST['oauth_verifier'] );
				
				if ( $rToken['oauth_token'] AND $rToken['oauth_token_secret'] )
				{
					$_userData = $this->_api->get('account/verify_credentials');
					
					/* From registration? */
					if ( $connectData['t_key'] )
					{
						/* Got a member linked already? */
						$_member = IPSMember::load( $_userData['id'], 'all', 'twitter_id' );
						
						if ( $_member['member_id'] )
						{
							$memberData = array_merge( $member, $_member );
							
							/* Ensure user's row is up to date */
							IPSMember::save( $memberData['member_id'], array( 'core' => array( 'twitter_token'  => $rToken['oauth_token'],
																						       'twitter_secret' => $rToken['oauth_token_secret'] ) ) );
							    
							/* Check for partial member id */
							$pmember = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'members_partial', 'where' => "partial_member_id=" . $memberData['member_id'] ) );

							if ( $pmember['partial_member_id'] )
							{
								$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . 'app=core&module=global&section=register&do=complete_login&mid='. $memberData['member_id'].'&key='.$pmember['partial_date'] );
							}
							else
							{
								/* Here, so log us in!! */
								$this->_login()->loginWithoutCheckingCredentials( $memberData['member_id'], TRUE );
								
								$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] );
							}
						}
						else
						{
							/* No? Create a new member */
							/* Generate BW options */
							foreach( array( 'tc_s_pic', 'tc_s_status', 'tc_s_aboutme', 'tc_si_status' ) as $field )
							{
								$_toSave[ $field ] = 1;
							}
							
							$tc_bwoptions = IPSBWOptions::freeze( $_toSave, 'twitter' );
							$safeFBName   = str_replace( ' ', '', IPSText::convertCharsets( $_userData['screen_name'], 'utf-8', IPS_DOC_CHAR_SET ) );
							
							/* Make sure usernames are safe */
							if ( $this->settings['username_characters'] )
							{
								$check_against = preg_quote( $this->settings['username_characters'], "/" );
								$check_against = str_replace( '\-', '-', $check_against );
								
								$safeFBName = preg_replace( '/[^' . $check_against . ']+/i', '', $safeFBName );
							}
							
							$displayName  = ( ! $this->settings['auth_allow_dnames'] ) ? $safeFBName : FALSE;
							
							/* From reg, so create new account properly */
							$toSave = array( 'core' 		 => array(  'name' 				     => $safeFBName,
															 		    'members_display_name'   => $displayName,
															 		    'members_created_remote' => 1,
															 		    'member_group_id'		 => ( $this->settings['tc_mgid'] ) ? $this->settings['tc_mgid'] : $this->settings['member_group'],
																	    'email'                  => '',
																	    'twitter_id'             => $_userData['id'],
																	    'twitter_token'          => $rToken['oauth_token'],
																	    'twitter_secret'		 => $rToken['oauth_token_secret'] ),
											'extendedProfile' => array( 'pp_about_me'            => IPSText::getTextClass( 'bbcode' )->stripBadWords( IPSText::convertCharsets( $_userData['description'], 'utf-8', IPS_DOC_CHAR_SET ) ),
																		'tc_bwoptions'           => $tc_bwoptions ) );
			
							$memberData = IPSMember::create( $toSave, TRUE, FALSE, TRUE );
							
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
								throw new Exception( 'CREATION_FAIL' );
							}
						}
					}
				}
			}
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
		if ( $_REQUEST['m'] AND $_REQUEST['oauth_token'] )
		{
			/* Load user */
			$member = IPSMember::load( intval( $_REQUEST['m'] ) );
		
			if ( $member['twitter_token'] == $_REQUEST['oauth_token'] )
			{
				/* Reset api to ensure user is not logged in */
				require_once( IPS_KERNEL_PATH . 'twitter/twitteroauth.php' );/*noLibHook*/
				$this->_api = new TwitterOAuth( CONSUMER_KEY, CONSUMER_SECRET, $member['twitter_token'], $member['twitter_secret'] );
				
				/* Generate oAuth token */
				$rToken = $this->_api->getAccessToken( $_REQUEST['oauth_verifier'] );
				
				if ( $rToken['oauth_token'] AND $rToken['oauth_token_secret'] )
				{
					$_userData = $this->_api->get('account/verify_credentials');
					
					/* Ensure user's row is up to date */
					IPSMember::save( $member['member_id'], array( 'core' => array( 'twitter_id'     => $_userData['id'],
																				   'twitter_token'  => $rToken['oauth_token'],
																				   'twitter_secret' => $rToken['oauth_token_secret'] ) ) );
				}
			}
		}
		
		/* Redirect back to settings page */
		$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . 'app=core&module=usercp&tab=core&area=twitter' );
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
			
			if ( $original['member_id'] AND $new['twitter_id'] AND $new['twitter_token'] AND $new['twitter_secret'] )
			{
				IPSMember::save( $original['member_id'], array( 'core' => array( 'twitter_id' => $new['twitter_id'], 'twitter_token' => $new['twitter_token'], 'twitter_secret' => $new['twitter_secret'] ) ) );
				
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Function to resync a member's Twitter data
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
		if ( ! $memberData['twitter_id'] )
		{
			throw new Exception( 'NOT_LINKED' );
		}
		
		/* Not completed sign up ( no display name ) */
		if ( $memberData['member_group_id'] == $this->settings['auth_group'] )
		{
			return false;
		}
		
		/* Thaw Options */
		$bwOptions = IPSBWOptions::thaw( $memberData['tc_bwoptions'], 'twitter' );
		
		/* Grab the data */
		try
		{
			$this->resetApi( $memberData['twitter_token'], $memberData['twitter_secret'] );
			
			if ( $this->isConnected() )
			{
				$user = $this->fetchUserData();
				
				/* Load library */
				if ( $bwOptions['tc_s_pic'] )
				{
					$classToLoad  = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/member/photo.php', 'classes_member_photo' );
					$photo = new $classToLoad( $this->registry );
					
					$photo->save( $memberData, 'twitter' );
				}
				
				if ( $bwOptions['tc_s_aboutme'] )
				{
					$exProfile['pp_about_me'] = IPSText::getTextClass( 'bbcode' )->stripBadWords( IPSText::convertCharsets( $user['description'], 'utf-8', IPS_DOC_CHAR_SET ) );
				}
			
				if ( $bwOptions['tc_si_status'] AND ( isset( $memberData['gbw_no_status_import'] ) AND ! $memberData['gbw_no_status_import'] ) AND !$memberData['bw_no_status_update'] )
				{
					/* Fetch timeline */
					$memberData['tc_last_sid_import'] = ( $memberData['tc_last_sid_import'] < 1 ) ? 100 : $memberData['tc_last_sid_import'];
					$_updates = $this->fetchUserTimeline( $user['id'], $memberData['tc_last_sid_import'], true );
					
					/* Got any? */
					if ( count( $_updates ) )
					{
						$update = array_shift( $_updates );
						
						if ( is_array( $update ) AND isset( $update['text'] ) )
						{
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
								$update['text'] = IPSText::utf8ToEntities( $update['text'] );
							}
							
							/* Set Content */
							$this->registry->getClass('memberStatus')->setContent( trim( IPSText::getTextClass( 'bbcode' )->stripBadWords( $update['text'] ) ) );
							
							/* Set as imported */
							$this->registry->getClass('memberStatus')->setIsImport( 1 );
							
							/* Set creator */
							$this->registry->getClass('memberStatus')->setCreator( 'twitter' );
		
							/* Can we reply? */
							if ( $this->registry->getClass('memberStatus')->canCreate() )
					 		{
								$this->registry->getClass('memberStatus')->create();
								
								$exProfile['tc_last_sid_import'] = $update['id'];
							}
						}
					}
				}
				
				/* Allowed profile customization? */
				if ( $bwOptions['tc_s_bgimg'] AND ( $user['profile_background_image_url'] OR $user['profile_background_color'] ) AND ( $this->memberData['gbw_allow_customization'] AND ! $this->memberData['bw_disable_customization'] ) )
				{
					/* remove bg images */
					IPSMember::getFunction()->removeUploadedBackgroundImages( $memberData['member_id'] );
			
					$exProfile['pp_customization'] = serialize( array( 'bg_url'   => $user['profile_background_image_url'],
																	   'type'     => ( $user['profile_background_image_url'] ) ? 'url' : 'color',
																	   'bg_color' => IPSText::alphanumericalClean( $user['profile_background_color'] ),
																	   'bg_tile'  => intval( $user['profile_background_tile'] ) ) );
				}
										
				/* Update member */
				IPSMember::save( $memberData['member_id'], array( 'core' 			=> array( 'tc_lastsync' => time() ),
																  'extendedProfile' => $exProfile ) );
			
				/* merge and return */
				$memberData['tc_lastsync'] = time();
				$memberData = array_merge( $memberData, $exProfile );
			}
		}
		catch( Exception $e )
		{
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
		
		$updates = $this->_api->get('statuses/user_timeline', array( 'id' => $userId, 'count' => 50, 'since_id' => $minId ) );
		
		if ( is_array( $updates ) AND count( $updates ) )
		{
			foreach( $updates as $update )
			{
				if ( substr( $update['text'], 0, 1 ) != '@' )
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
			$this->_login =  new $classToLoad( $this->registry );
	    	$this->_login->init();
		}
		
		return $this->_login;
	}
	
}