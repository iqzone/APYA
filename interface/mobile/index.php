<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Mobile API
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	© 2001 - 2008 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 *
 */

define( 'IPS_IS_MOBILE_APP', true );
define( 'IPB_THIS_SCRIPT', 'public' );

require_once( '../../initdata.php' );/*noLibHook*/
		
require_once( IPS_ROOT_PATH . 'sources/base/ipsRegistry.php' );/*noLibHook*/
require_once( IPS_ROOT_PATH . 'sources/base/ipsController.php' );/*noLibHook*/

class mobileApiRequest
{
	/**#@+
	 * Registry Object Shortcuts
	 *
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
	
	/**
	 * Make the registry shortcuts
	 *
	 * @param	object	ipsRegistry reference
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make registry objects */
		$this->registry   =  $registry;
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
	}
	
	/**
	 * Figure out what api is being called
	 *
	 * @return	@e void
	 */
	public function dispatch()
	{
		/* Force a cookie to identify as a mobile app */
		if (!$this->request['dontSetCookie'])
			IPSCookie::set("mobileApp", 'true', -1);
		
		/* Figure out the action */
		switch( $this->request['api'] )
		{
			case 'getNotifications':
				$this->_handleGetNotifications();
			break;
			
			case 'toggleNotifications':
				$this->_handleToggleNotifications();
			break;
			
			case 'toggleNotificationKey':
				$this->_hanldeToggleNotificaionKey();
			break;
			
			case 'notificationTypes':
				$this->_handleNotificationTypes();
			break;
			
			case 'login':
				$this->_handleLogin();
			break;
			
			case 'postImage':
				$this->_handlePostImage();
			break;
			
			case 'postStatus':
				$this->_handlePostStatus();
			break;
			
			case 'postTopic':
				$this->_handlePostTopic();
			break;
			
			case 'postReply':
				$this->_handlePostReply();
			break;
			
			case 'getStyle':
				$this->_handleGetStyle();
			break;
			
			case 'getApns':
				$this->_getApns();
			break;
			
			default:
				$this->_invalidApi();
			break;
		}
	}
	
	/**
	 * Returns a list of unread notifications
	 *
	 * @return	string		XML
	 */
	protected function _handleGetNotifications()
	{
		/* INIT */
		$unreadOnly = ( $this->request['unread'] == 1 || ! isset( $this->request['unread'] ) ) ? true : false;
		
		/* Make sure we're logged in */
		if( ! $this->memberData['member_id'] )
		{
			$this->_returnError( "You're no longer logged in" );
		}
		
		/* Check the form hash */
		if( $this->member->form_hash != $this->request['form_hash'] )
		{
			$this->_returnError( "Invalid Request" );
		}
		
		/* Load the library */
		$classToLoad		= IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
		$notifyLibrary		= new $classToLoad( $this->registry );
		$notifyLibrary->setMember( $this->memberData );
		
		/* Fetch the notifications */
		$_data = $notifyLibrary->fetchUnreadNotifications( 10, 'notify_sent', 'DESC', $unreadOnly, true );

		/* XML Parser */
		require_once( IPS_KERNEL_PATH . 'classXML.php' );/*noLibHook*/
		$xml = new classXML( 'utf-8' );
		$xml->newXMLDocument();
		
		/* Build Document */
		$xml->addElement( 'notifications' );

		/* Loop through list */
		if( is_array( $_data ) && count( $_data ) )
		{
			foreach( $_data as $r )
			{
				$xml->addElementAsRecord( 'notifications', array( 'notification' ), array( 
																							'id'			=> $r['notify_id'], 
																							'dateSent'		=> $this->registry->class_localization->getDate( $r['notify_sent'], 'short' ),
																							'notifyTitle'	=> strip_tags( $r['notify_title'] ),
																							'notifyMessage'	=> $r['notify_text'],
																							'notifyURL'		=> $r['notify_url'],
																							'notifyIcon'	=> $r['notify_icon']
																						) 
										);
			}
		}
		
		/* Output */
		echo $xml->fetchDocument();
		exit();
	}
	
	/**
	 * Toggles a specific notification key for a user
	 *
	 * @return	string		XML
	 */
	protected function _hanldeToggleNotificaionKey()
	{
		/* INIT */
		$notifyKey		= $this->request['key'];
		$notifyStatus	= $this->request['status'];
		
		/* Check the form hash */
		if( $this->member->form_hash != $this->request['form_hash'] )
		{
			$this->_returnError( "Invalid Request" );
		}
		
		/* Notifications Library */
		$classToLoad		= IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
		$notifyLibrary		= new $classToLoad( $this->registry );
		$notifyLibrary->setMember( $this->memberData );

		/* Notifications Data */
		$_notifyConfig	= $notifyLibrary->getMemberNotificationConfig( $this->memberData );

		if( $notifyStatus )
		{
			$_notifyConfig[ $notifyKey ][ 'selected' ][] = 'mobile';
		}
		else
		{
			$_newConfig = array();
			
			foreach( $_notifyConfig[ $notifyKey ][ 'selected' ] as $_v )
			{
				if( $_v != 'mobile' )
				{
					$_newConfig[] = $_v;
				}
			}
			
			$_notifyConfig[ $notifyKey ][ 'selected' ] = $_newConfig;
		}

		/* Save */
		IPSMember::packMemberCache( $this->memberData['member_id'], array( 'notifications' => $_notifyConfig ), $this->memberData['members_cache'] );
	}
	
	/**
	 * Toggles notifications on/off for logged in user
	 *
	 * @return	string		XML
	 */
	protected function _handleToggleNotifications()
	{
		/* INIT */
		$ips_mobile_token	= $this->request['token'];
		$enable				= $this->request['enable'];
		
		/* Check the form hash */
		if( $this->member->form_hash != $this->request['form_hash'] )
		{
			$this->_returnError( "Invalid Request" );
		}
		
		/* Make sure we're logged in */
		if( ! $this->memberData['member_id'] )
		{
			$this->_returnError( "You're no longer logged in" );
		}
		
		/* Check to see if notifications are enabled */
		if( ! IPSMember::canReceiveMobileNotifications() )
		{
			$this->_returnError( "You are not authorized to receive mobile notifications" );
		}
		
		/* Insert */
		if ( $enable )
		{
			$this->DB->insert( 'mobile_device_map', array( 'token' => $ips_mobile_token, 'member_id' => $this->memberData['member_id'] ), array( 'token' ) );
		}
		else
		{
			$ips_mobile_token = $this->DB->addSlashes( $this->request['token'] );
			$this->DB->delete( 'mobile_device_map', "token='{$ips_mobile_token}'" );
		}
	}
	
	/**
	 * Returns a list of notification options
	 *
	 * @return	string		XML
	 */
	protected function _handleNotificationTypes()
	{
		/* Check to see if notifications are enabled */
		if( ! IPSMember::canReceiveMobileNotifications() )
		{
			$this->_returnError( "You are not authorized to receive mobile notifications" );
		}
		
		/* Lang */
		$this->lang->loadLanguageFile( array( 'public_usercp' ), 'core' );
		
		/* Notifications Library */
		$classToLoad		= IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
		$notifyLibrary		= new $classToLoad( $this->registry );
		$notifyLibrary->setMember( $this->memberData );
		
		/* Options */
		$_basicOptions	= array( array( 'email', $this->lang->words['notopt__email'] ), array( 'pm', $this->lang->words['notopt__pm'] ), array( 'inline', $this->lang->words['notopt__inline'] ), array( 'mobile', $this->lang->words['notopt__mobile'] ) );
		$_configOptions	= $notifyLibrary->getNotificationData( TRUE );
		$_notifyConfig	= $notifyLibrary->getMemberNotificationConfig( $this->memberData );
		$_defaultConfig	= $notifyLibrary->getDefaultNotificationConfig();
		$_formOptions	= array();
		
		foreach( $_configOptions as $option )
		{
			$_thisConfig	= $_notifyConfig[ $option['key'] ];
			
			//-----------------------------------------
			// Determine available options
			//-----------------------------------------
			
			$_available	= array();
			
			foreach( $_basicOptions as $_bo )	// ewwww :P
			{
				if( !is_array($_defaultConfig[ $option['key'] ]['disabled']) OR !in_array( $_bo[0], $_defaultConfig[ $option['key'] ]['disabled'] ) )
				{
					$_available[]	= $_bo;
				}
			}
			
			//-----------------------------------------
			// If none available, at least give inline
			//-----------------------------------------
			
			if( !count($_available) )
			{
				$_available[]	= array( 'inline', $this->lang->words['notify__inline'] );
			}
			
			//-----------------------------------------
			// Start setting data to pass to form
			//-----------------------------------------
			
			$_formOptions[ $option['key'] ]					= array();
			$_formOptions[ $option['key'] ]['key']			= $option['key'];
			
			//-----------------------------------------
			// Rikki asked for this...
			//-----------------------------------------
			
			foreach( $_available as $_availOption )
			{
				$_formOptions[ $option['key'] ]['options'][ $_availOption[0] ]	= $_availOption;
			}
			
			//$_formOptions[ $option['key'] ]['options']		= $_available;
			
			$_formOptions[ $option['key'] ]['defaults']		= is_array($_thisConfig['selected']) ? $_thisConfig['selected'] : array();
			$_formOptions[ $option['key'] ]['disabled']		= 0;
			
			//-----------------------------------------
			// Don't allow member to configure
			// Still show, but disable on form
			//-----------------------------------------
			
			if( $_defaultConfig[ $option['key'] ]['disable_override'] )
			{
				$_formOptions[ $option['key'] ]['disabled']		= 1;
				$_formOptions[ $option['key'] ]['defaults']		= is_array($_defaultConfig[ $option['key'] ]['selected']) ? $_defaultConfig[ $option['key'] ]['selected'] : array();
			}
		}
		
		/* Groups */
		$this->notifyGroups = array(
									'topics_posts'		=> array( 'new_topic', 'new_reply', 'post_quoted' ),
									'status_updates'	=> array( 'reply_your_status', 'reply_any_status', 'friend_status_update' ),
									'profiles_friends'	=> array( 'profile_comment', 'profile_comment_pending', 'friend_request', 'friend_request_pending', 'friend_request_approve' ),
									'private_msgs' 		=> array( 'new_private_message', 'reply_private_message', 'invite_private_message' )
		);
		
		/* XML Parser */
		require_once( IPS_KERNEL_PATH . 'classXML.php' );/*noLibHook*/
		$xml = new classXML( 'utf-8' );
		$xml->newXMLDocument();
		
		/* Build Document */
		$xml->addElement( 'notifications' );
		
		foreach( $this->notifyGroups as $groupKey => $group )
		{
			$xml->addElement( 'group', 'notifications' );
			$xml->addElementasRecord( 'group', array( 'info' ), array( 'groupTitle' => IPSText::UNhtmlspecialchars( $this->lang->words[ 'notifytitle_' . $groupKey ] ) ) );
			$xml->addElement( 'options', 'group' );
			
			foreach( $group as $key )
			{
				if( ! is_array( $_formOptions[$key] ) )
				{
					continue;
				}
				
				/* Set the done flag */
				$_formOptions[$key]['done'] = 1;
				
				/* Set the title */
				$_title = $this->lang->words[ 'notify__short__' . $key ] ? $this->lang->words[ 'notify__short__' . $key ] : $this->lang->words[ 'notify__' . $key ];
				
				/* Add to XML */
				$xml->addElementAsRecord( 'options', array( 'option' ), array( 
																				'optionKey'		=> $key, 
																				'optionTitle'	=> IPSText::UNhtmlspecialchars( $_title ),
																				'optionEnabled'	=> in_array( 'mobile', $_formOptions[$key]['defaults'] ) ? '1' : '0'
																			) 
										);
			}
		}
		
		/* Other Options */
		$xml->addElement( 'group', 'notifications' );
		$xml->addElementasRecord( 'group', array( 'info' ), array( 'groupTitle' => IPSText::UNhtmlspecialchars( $this->lang->words[ 'notifytitle_other' ] ) ) );
		$xml->addElement( 'options', 'group' );
		
		foreach( $_formOptions as $key => $data )
		{
			if( $data['done'] )
			{
				continue;
			}
			
			/* Set the title */
			$_title = $this->lang->words[ 'notify__short__' . $key ] ? $this->lang->words[ 'notify__short__' . $key ] : $this->lang->words[ 'notify__' . $key ];
			
			/* Add to XML */
			$xml->addElementAsRecord( 'options', array( 'option' ), array( 
																			'optionKey'		=> $key, 
																			'optionTitle'	=> IPSText::UNhtmlspecialchars( $_title ),
																			'optionEnabled'	=> in_array( 'mobile', $data['defaults'] ) ? '1' : '0'
																		) 
									);
		}
		

		/* Output */
		echo $xml->fetchDocument();
		exit();
	}
	
	/**
	 * Attempt to login a user to the mobile service
	 *
	 * @return	string		XML
	 */
	protected function _handleLogin()
	{
		/* 3.2 upwards renames these fields, but since we do this prior to getting capabilities, we don't know version yet */
		$this->request['ips_username'] = ipsRegistry::$request['ips_username'] = $this->request['username'];
		$_REQUEST['ips_username'] = $_REQUEST['username'];
		$this->request['ips_password'] = ipsRegistry::$request['ips_password'] = $this->request['password'];
		$_REQUEST['ips_password'] = $_REQUEST['password'];
		
		/* Load the login handler */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/handlers/han_login.php', 'han_login' );
		$this->han_login = new $classToLoad( $this->registry );
		$this->han_login->init();

		/* Attempt login */
		$loginResult = $this->han_login->verifyLogin();
		
		/* Fail */
		if( $loginResult[2] )
		{
			$this->_returnError( 'Login Failed' );
		}
		/* Success */
		else
		{
    		$this->_returnXml( array(
    									'success'			=> 1,
    									'gallery'			=> $this->_userHasGallery( $this->han_login->member_data ) ? '1' : '0',
    									'status'			=> $this->_canUpdateStatus( $this->han_login->member_data ) ? '1' : '0',
										'notifications'		=> $this->_userEnabledNotifications( $this->han_login->member_data ) ? '1' : '0',
										'facebook'			=> IPSLib::fbc_enabled() && $this->han_login->member_data['fb_uid'] ? '1' : '0',
										'twitter'			=> IPSLib::twitter_enabled() && $this->han_login->member_data['twitter_id'] ? '1' : '0',
    									'albums'			=> $this->_userAlbums( $this->han_login->member_data ),
    									'version_id'		=> ipsRegistry::$vn_full,
    									'version_text'		=> ipsRegistry::$version,
										'form_hash'			=> md5( $this->han_login->member_data['email'].'&'.$this->han_login->member_data['member_login_key'].'&'.$this->han_login->member_data['joined'] )
    						)	);
		}
	}
	
	/**
	 * Determines if a user has notifications enabled
	 *
	 * @return	string		XML
	 */
	protected function _userEnabledNotifications( $memberData )
	{
		/* Check to see if notifications are enabled */
		if( ! IPSMember::canReceiveMobileNotifications( $memberData ) )
		{
			return 0;
		}
		
		$count = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count', 'from' => 'mobile_device_map', 'where' => "member_id={$this->memberData['member_id']}" ) );
		if ( $count['count'] )
		{
			return 1;
		}
		
		return 0;
	}
	
	/**
	 * Determines if a user has gallery
	 *
	 * @param	array		$memberData
	 * @return	integer		1 or 0
	 */
	protected function _userHasGallery( $memberData )
	{
		/* Gallery installed? */
		if( ! IPSLib::appIsInstalled( 'gallery' ) )
		{
			return 0;
		}
		
		/* User has gallery? */
		if( ! $memberData['has_gallery'] )
		{
			return 0;
		}
		
		return 1;
	}
	
	/**
	 * Determines if a user can update their status
	 *
	 * @param	array		$memberData
	 * @return	integer		1 or 0
	 */
	protected function _canUpdateStatus( $memberData )
	{
		/* Load status class */
		if ( ! $this->registry->isClassLoaded( 'memberStatus' ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/member/status.php', 'memberStatus' );
			$this->registry->setClass( 'memberStatus', new $classToLoad( ipsRegistry::instance() ) );
		}
		
		return $this->registry->getClass('memberStatus')->canCreate( $memberData ) ? '1' : '0';
	}
	
	/**
	 * Determines if a user can update their status
	 *
	 * @param	array		$memberData
	 * @return	array		Array of albums
	 */
	protected function _userAlbums( $memberData )
	{
		$albums = array();
		
		/* Make sure we have gallery */
		if( ! IPSLib::appIsInstalled( 'gallery' ) )
		{
			return array();
		}
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
		$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
		
		/* Fetch albums */
		$_albums = $this->registry->gallery->helper('albums')->fetchAlbumsByOwner( $memberData['member_id'] );
		
		foreach( $_albums as $id => $data )
		{
			$albums[] = array( 'id' => $id, 'name' => $data['album_name'] );
		}
		 
		return $albums;
	}
	
	/**
	 * Attempt to post an image to a user album
	 *
	 * @return	string		XML
	 */
	protected function _handlePostImage()
	{
		/* Check the form hash */
		if( $this->member->form_hash != $this->request['form_hash'] )
		{
			$this->_returnError( "Invalid Request" );
		}
		
		/* Make sure we're logged in */
		if( ! $this->memberData['member_id'] )
		{
			$this->_returnError( "You're no longer logged in" );
		}
		
		/* Make sure we have gallery */
		if( ! IPSLib::appIsInstalled( 'gallery' ) )
		{
			$this->_returnError( "Gallery has been disabled" );
		}
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
		$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
		
		/* Get the album */
		$albumId = intval( $this->request['album'] );
		
		$album = $this->registry->gallery->helper('albums')->fetchAlbumsById( $albumId );
		
		if ( ! $this->registry->gallery->helper('albums')->isUploadable( $album ) )
		{
			$this->_returnError( "You are not allowed to upload images to that album" );
		}
		
		/* Get upload settings */
		$settings = $this->getUploadSettings( $album );
		
		/* Upload it */
		$this->registry->gallery->helper('upload')->addImage( 'image', $albumId, array( 'title'       => $this->request['caption'],
																						'description' => $this->request['description'],
																						'approved'    => $settings['approved'] ) );
		
		exit();
	}
	
	/**
	 * Attempt to post a user status update
	 *
	 * @return	string		XML
	 */
	protected function _handlePostStatus()
	{
		/* Check the form hash */
		if( $this->member->form_hash != $this->request['form_hash'] )
		{
			$this->_returnError( "Invalid Request" );
		}
		
		/* INIT */
		$smallSpace  = intval( $this->request['smallSpace'] );
		$su_Twitter  = intval( $this->request['su_twitter'] );
		$su_Facebook = intval( $this->request['su_facebook'] );
		
		/* Load status class */
		if ( ! $this->registry->isClassLoaded( 'memberStatus' ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/member/status.php', 'memberStatus' );
			$this->registry->setClass( 'memberStatus', new $classToLoad( ipsRegistry::instance() ) );
		}
		
		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classAjax.php', 'classAjax' );
		$this->ajax  = new $classToLoad();
		
		/* Got content? */
		if( !trim( $this->ajax->convertAndMakeSafe( $_POST['content'] ) ) )
		{
			$this->returnJsonError( $this->lang->words['no_status_sent'] );
		}
		
		/* Set Author */
		$this->registry->getClass('memberStatus')->setAuthor( $this->memberData );
		
		/* Set Content */
		$this->registry->getClass('memberStatus')->setContent( trim( $this->ajax->convertAndMakeSafe( $_POST['content'] ) ) );
		
		/* Set post outs */
		$this->registry->getClass('memberStatus')->setExternalUpdates( array( 'twitter' => $su_Twitter, 'facebook' => $su_Facebook ) );
		
		/* Set creator */
		$this->registry->getClass('memberStatus')->setCreator( 'ipbmobiphone' );
							
		/* Can we reply? */
		if ( ! $this->registry->getClass('memberStatus')->canCreate() )
 		{
			$this->returnJsonError( $this->lang->words['status_off'] );
		}

		/* Update */
		$newStatus = $this->registry->getClass('memberStatus')->create();
		
		/* Now grab the reply and return it */
		$new = $this->registry->getClass('output')->getTemplate('profile')->statusUpdates( $this->registry->getClass('memberStatus')->fetch( $this->memberData['member_id'], array( 'member_id' => $this->memberData['member_id'], 'sort_dir' => 'desc', 'limit' => 1 ) ), $smallSpace );
		exit;
	}
	
	/**
	 * Handle posting a new topic
	 * 
	 * @return	string		XML
	 */
	protected function _handlePostTopic()
	{
		/* Check the form hash */
		if( $this->member->form_hash != $this->request['form_hash'] )
		{
			$this->_returnError( "Invalid Request" );
		}
		
		$topic_forum = intval( $this->request['f'] );
		
		ipsRegistry::getAppClass( 'forums' );
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('forums') . '/sources/classes/post/classPost.php', 'classPost' );
		$this->registry->setClass( 'classPost', new $classToLoad( ipsRegistry::instance() ) );
		
		$this->registry->getClass('class_forums')->strip_invisible = true;
		$this->registry->getClass('class_forums')->forumsInit();
		
		$this->registry->classPost->setIsPreview( false );
		$this->registry->classPost->setForumData( $this->registry->getClass('class_forums')->forum_by_id[ $topic_forum ] );
		$this->registry->classPost->setForumID( $topic_forum );
		$this->registry->classPost->setTopicTitle( $_POST['title'] );
		$this->registry->classPost->setPostContent( $_POST['body'] );
		$this->registry->classPost->setAuthor( $this->memberData['member_id'] );
		$this->registry->classPost->setPublished( true );
		$this->registry->classPost->setSettings( array( 'enableSignature' => 1,
											   'enableEmoticons' => 1,
											   'post_htmlstatus' => 0,
											   'enableTracker'   => 0 ) );
		
		try {
			if ( $this->registry->classPost->addTopic() === FALSE ) {
				$this->_returnError( "Topic could not be posted: " . $this->registry->classPost->getPostError() );
			} else {
				$topic_id = $this->registry->classPost->getTopicData( 'tid' );
			}
		} catch ( Exception $ex ) {
			$this->_returnError( "Error posting topic: " . $ex->getMessage() );
		}
		
		require_once( IPS_KERNEL_PATH . 'classXML.php' );/*noLibHook*/
		$xml = new classXML( 'utf-8' );
		$xml->newXMLDocument();
		
		$xml->addElement( 'topic' );
		$xml->addElementAsRecord( 'topic', 'info', array( 'topic_id'	=>	$topic_id ) );
		
		echo $xml->fetchDocument();
		exit();
	}
	
	/**
	 * Handle posting a new reply
	 * 
	 * @return	string		XML
	 */
	protected function _handlePostReply()
	{
		/* Check the form hash */
		if( $this->member->form_hash != $this->request['form_hash'] )
		{
			$this->_returnError( "Invalid Request" );
		}
		
		$topic_forum = intval( $this->request['f'] );
		$topic_topic = intval( $this->request['t'] );
		
		ipsRegistry::getAppClass( 'forums' );
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('forums') . '/sources/classes/post/classPost.php', 'classPost' );
		$this->registry->setClass( 'classPost', new $classToLoad( ipsRegistry::instance() ) );
		
		$this->registry->getClass('class_forums')->strip_invisible = true;
		$this->registry->getClass('class_forums')->forumsInit();
		
		//-----------------------------------------
		// Need the topic...
		//-----------------------------------------
			
		$topic	= $this->registry->DB()->buildAndFetch( array( 'select' => '*', 'from' => 'topics', 'where' => 'tid=' . $topic_topic ) );
		
		$this->registry->classPost->setIsPreview( false );
		$this->registry->classPost->setForumData( $this->registry->getClass('class_forums')->forum_by_id[ $topic_forum ] );
		$this->registry->classPost->setForumID( $topic_forum );
		$this->registry->classPost->setTopicID( $topic_topic );
		$this->registry->classPost->setTopicData( $topic );
		$this->registry->classPost->setTopicTitle( $_POST['title'] );
		$this->registry->classPost->setPostContent( $_POST['body'] );
		$this->registry->classPost->setAuthor( $this->memberData['member_id'] );
		$this->registry->classPost->setPublished( true );
		$this->registry->classPost->setSettings( array( 'enableSignature' => 1,
											   'enableEmoticons' => 1,
											   'post_htmlstatus' => 0,
											   'enableTracker'   => 0 ) );
		
		try {
			if ( $this->registry->classPost->addReply() === FALSE ) {
				$this->_returnError( "Reply could not be posted: " . $this->registry->classPost->getPostError() );
			} else {
				$topic_id = $this->registry->classPost->getTopicData( 'tid' );
				$post_id = $this->registry->classPost->getPostData( 'pid' );
			}
		} catch ( Exception $ex ) {
			$this->_returnError( "Error posting reply: " . $ex->getMessage() );
		}
		
		require_once( IPS_KERNEL_PATH . 'classXML.php' );/*noLibHook*/
		$xml = new classXML( 'utf-8' );
		$xml->newXMLDocument();
		
		$xml->addElement( 'topic' );
		$xml->addElementAsRecord( 'topic', 'info', array( 'topic_id'	=>	$topic_id ) );
		$xml->addElementAsRecord( 'topic', 'info', array( 'post_id'		=>	$post_id ) );
		
		echo $xml->fetchDocument();
		exit();
	}
	
	
	/**
	 * Displays a flat file containing all new style information 
	 *
	 * @return	string		FlatFile
	 */
	protected function _handleGetStyle()
	{
		/* Grab all images to be downloaded */
		$this->DB->build(
					 array(
							'select'		=> '*',
							'from'			=> 'mobile_app_style',
							'where'			=> 'lastUpdated > ' . intval($this->request['lastUpdated']) . ' AND isInUse = 1',
						  )	
						);
		$this->DB->execute();
		while( $result = $this->DB->fetch())
		{
			//------------------------
			// If this is a retnia display insert @2x before fileExtention
			//------------------------
			if ($this->request['hasRetina'] == 1 && $result['hasRetina'] == 1)
			{
				$result['filename'] = preg_replace('/(.*)\.(\w*)$/', '$1@2x.$2', $result['filename']);
			}
			echo $this->settings['public_dir'].'style_images/mobile_app/' . $result['filename'] ."\n";
		}
		exit();
	}
	
	/**
	 * Get the users device IDs
	 *
	 * @return @void	(outputs to screen)
	 */
	protected function _getApns()
	{
		/* XML Parser */
		require_once( IPS_KERNEL_PATH . 'classXML.php' );/*noLibHook*/
		$xml = new classXML( 'utf-8' );
		$xml->newXMLDocument();
		
		/* Main element */
		$xml->addElement( 'apns' );
		
		/* Fetch Devices */
		if ( $this->memberData['member_id'] )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'mobile_device_map', 'where' => "member_id={$this->memberData['member_id']}" ) );
			$this->DB->execute();
			while ( $row = $this->DB->fetch() )
			{
				$xml->addElementAsRecord( 'apns', 'apn', array( 'token' => $row['token'] ) );
			}
		}
		
		/* Output */
		echo $xml->fetchDocument();
		exit();
	}

	
	/**
	 * Send an error about the selected api
	 *
	 * @return	string		XML
	 */
	protected function _invalidApi()
	{
		$this->_returnError( "Invalid API Request" );
	}
	
	/**
	 * Sends an error message in xml
	 *
	 * @param	string	$msg
	 * @return	@e void
	 */
	protected function _returnError( $msg )
	{
		/* XML Parser */
		require_once( IPS_KERNEL_PATH . 'classXML.php' );/*noLibHook*/
		$xml = new classXML( 'utf-8' );
		$xml->newXMLDocument();
		
		/* Build Document */
		$xml->addElement( 'forum' );
		$xml->addElementAsRecord( 'forum', 'error', array( 'msg' => $msg ) );

		/* Output */
		echo $xml->fetchDocument();
		exit();
	}
		
	/**
	 * Sends forum data in xml format
	 *
	 * @param	array	$dataArray
	 * @return	@e void
	 */
	protected function _returnXml( $dataArray )
	{
		/* XML Parser */
		require_once( IPS_KERNEL_PATH . 'classXML.php' );/*noLibHook*/
		$xml = new classXML( 'utf-8' );
		$xml->newXMLDocument();
		
		/* Build Document */
		$xml->addElement( 'forum' );
		$xml->addElementAsRecord( 'forum', 'capabilites', array( 'gallery'			=> $dataArray['gallery'] ) );
		$xml->addElementAsRecord( 'forum', 'capabilites', array( 'facebook'			=> $dataArray['facebook'] ) );
		$xml->addElementAsRecord( 'forum', 'capabilites', array( 'twitter'			=> $dataArray['twitter'] ) );
		$xml->addElementAsRecord( 'forum', 'capabilites', array( 'status'			=> $dataArray['status'] ) );
		$xml->addElementAsRecord( 'forum', 'capabilites', array( 'notifications'	=> $dataArray['notifications'] ) );
		$xml->addElement( 'albums', 'forum' );
		
		$xml->addElementAsRecord( 'forum', 'security', array( 'form_hash' => $dataArray['form_hash'] ) );
		
		/* Loop through albums */
		if( is_array( $dataArray['albums'] ) && count( $dataArray['albums'] ) )
		{
			foreach( $dataArray['albums'] as $r )
			{
				$xml->addElementAsRecord( 'albums', array( 'album' ), array( 'id' => $r['id'], 'name' => $r['name'] ) );
			}
		}
		
		$xml->addElementAsRecord( 'forum', 'version', array( 'version_id' => $dataArray['version_id'] ) );
		$xml->addElementAsRecord( 'forum', 'version', array( 'version_text' => $dataArray['version_text'] ) );
		
		/* Output */
		echo $xml->fetchDocument();
		exit();
	}
	
	/**
	 * Returns settings used for uploading images
	 *
	 * @param	array		Album array
	 * @return	array
	 */
	protected function getUploadSettings( $album=array() )
	{
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
		$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
		
		return array( 	'thumb'     	=> 1,
						'watermark' 	=> ( $this->settings['gallery_watermark_path'] ) ? 1 : 0,
						'html'      	=> 0,
						'code'			=> 1,
						'allow_media' 	=> $this->registry->gallery->helper('media')->allow( $this->memberData['member_id'] ),
						'approve'   	=> ( $this->registry->gallery->helper('albums')->isGlobal( $album ) && $album['album_g_approve_img'] ) ? 1 : 0,
						'container' 	=> $album['album_id'],
						'allow_images'	=> 1,
					);
	}
}

/* Setup the registry */
$registry = ipsRegistry::instance();
$registry->init();

/* Handle the request */
$apiRequest = new mobileApiRequest( $registry );
$apiRequest->dispatch();

exit();