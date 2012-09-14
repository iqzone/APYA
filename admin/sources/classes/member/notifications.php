<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Notification library
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $ (Original: bfarber)
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		Thursday Jan 7, 2010
 * @version		$Revision: 10721 $
 *
 */

/**
 * Notifications class.
 * Sends notifications to member(s) based on their configured notification options.
 */
class notifications
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
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	/**#@-*/
	
	/**
	 * Member data
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_member				= array();
	
	/**
	 * Multiple Recipients
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_recipients			= array();
	
	/**
	 * From member data (usually $this->memberData)
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_from				= array();

	/**
	 * Notification definitions
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_notificationData	= array();
	
	/**
	 * Notification key
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_notificationKey		= '';
	
	/**
	 * Notification text
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_notificationText_forDisplay	= '';
	protected $_notificationText_forEmails	= '';

	/**
	 * Notification title
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_notificationTitle	= '';

	/**
	 * Notification URL
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_notificationUrl		= '';
	
	/**
	 * Send EMails as HTML
	 *
	 * @var		bool
	 */
	protected $_mail_html_on		= FALSE;

	protected $_emailTitle			= false;
	protected $_metaData			= array();
	protected $_appClass			= array();
	protected $_showInlinePopUp     = array();
	protected $_blockedFromInline   = array();
	
	/**
	 * CONSTRUCTOR
	 *
	 * @param  blog_show $class
	 * @return void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make registry objects */
		$this->registry		=  $registry;
		$this->DB			=  $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->lang			=  $this->registry->getClass('class_localization');
		$this->member		=  $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		=  $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		
		//-----------------------------------------
		// Set default
		//-----------------------------------------
		
		$this->_from		= $this->memberData;
		
		/* We only really force the inline pop-up for PMs currently
		 * loosely hardcoded this so it can change at some point
		 */
		$this->_showInlinePopUp = array( 'new_private_message', 'reply_private_message' );
		
		/* We don't want any PM notifications to show on the inline list
		 * so we can loosely hardcode this too
		 */
		$this->_blockedFromInline = array( 'new_private_message', 'reply_private_message', 'invite_private_message' );
		
		/* Set up default handler */
		$this->setHtmlEmails( $this->settings['email_use_html'] );
	}
	
	/**
	 * @return the $_blockedFromInline
	 */
	public function getBlockedFromInline()
	{
		return ( is_array( $this->_blockedFromInline ) ) ? $this->_blockedFromInline : false;
	}
	
	/**
	 * @return the $_meta_data
	 */
	public function getMetaData()
	{
		return ( is_array( $this->_metaData ) && ! empty( $this->_metaData['meta_app'] ) ) ? $this->_metaData : false;
	}

	/**
	 * @param array $_meta_data
	 */
	public function setMetaData( array $_metaData )
	{
		if ( empty( $_metaData['meta_app'] ) )
		{
			$_metaData['meta_app'] = IPS_APP_COMPONENT;
		}
		
		if ( empty( $_metaData['meta_area'] ) )
		{
			$_metaData['meta_area'] = $this->_notificationKey;
		}
		
		$this->_metaData = $_metaData;
	}

	/**
	 * Set Member
	 *
	 * @access	public
	 * @param	mixed		Member id (int) or member data (array)
	 * @return	@e void
	 */
	public function setMember( $member )
	{
		if( is_numeric($member) )
		{
			$member	= IPSMember::load( intval($member) );
		}
		
		$this->_member	= $member;
	}
	
	/**
	 * Set Multiple Recipients
	 * Use to CC the notification Email rather than send to each individually
	 *
	 * @param	array		Recipients. Each element should be an array of member data or an Email address - NOT a member id
	 * @return	@e void
	 */
	public function setMultipleRecipients( $recipients )
	{
		$this->_recipients = $recipients;
	}
	
	/**
	 * Set member message should come 'from'
	 *
	 * @access	public
	 * @param	mixed		Member id (int) or member data (array)
	 * @return	@e void
	 */
	public function setFrom( $member )
	{
		if( is_numeric($member) )
		{
			$member	= IPSMember::load( intval($member) );
		}
		
		$this->_from	= $member;
	}
	
	/**
	 * Set notification type key
	 *
	 * @access	public
	 * @param	string		Notification type key
	 * @return	@e void
	 */
	public function setNotificationKey( $key )
	{
		$this->_notificationKey		= $key;
	}
	
	/**
	 * Set notification URL
	 *
	 * @access	public
	 * @param	string		Notification URL
	 * @return	@e void
	 */
	public function setNotificationUrl( $url )
	{
		$this->_notificationUrl		= $url;
	}
	
	/**
	 * Set notification text
	 *
	 * @access	public
	 * @param	string		Text
	 * @return	@e void
	 */
	public function setNotificationText( $text )
	{
		$this->_notificationText_forDisplay	= $text;
		$this->_notificationText_forEmails	= $text;
	}
	
	/**
	 * Set notification text
	 *
	 * @access	public
	 * @param	string		Text for display
	 * @param	string		Text for Emails
	 * @return	@e void
	 */
	public function setMultipleNotificationTexts( $forDisplay, $forEmails )
	{
		$this->_notificationText_forDisplay	= $forDisplay;
		$this->_notificationText_forEmails	= $forEmails;
	}
	
	/**
	 * Set notification title
	 *
	 * @access	public
	 * @param	string		Title
	 * @return	@e void
	 */
	public function setNotificationTitle( $title )
	{
		$this->_notificationTitle	= $title;
	}
	
	/**
	 * Set email subject
	 *
	 * @param	array		$data			Subject data
	 * @param	string		$language		Language to use
	 * @param	string		$lang_file		Language file to load
	 * @param	string		$app			Application of language file
	 * @return	@e void
	 */
	public function setEmailSubject( $data, $language='', $lang_file='public_email_content', $app='core' )
	{
		if ( ! is_array( $data ) OR ! $data['key'] )
		{
			return;
		}
		
		/* This is a bit hacky. Sorry */
		$tmp = $this->lang->words;
		
		if ( ! $language )
		{
			$language = IPSLib::getDefaultLanguage();
		}
		
		$this->registry->class_localization->loadLanguageFile( array( $lang_file ), $app, $language, TRUE );
		
		//-----------------------------------------
		// Stored KEY?
		//-----------------------------------------
		
		if ( ! isset($this->lang->words[ $data['key'] ]) )
		{
			if ( $language != IPSLib::getDefaultLanguage() )
			{
				$this->registry->class_localization->loadLanguageFile( array( $lang_file ), $app, IPSLib::getDefaultLanguage(), TRUE );
			}
		}
		
		if ( is_array( $data['params'] ) )
		{
			foreach( $data['params'] as $p )
			{
				/* Each iteration should replace one %s */
				$this->_emailTitle = sprintf( $this->lang->words[ $data['key'] ], $p );
			}
		}
		
		/* Yeah */
		$this->lang->words = $tmp;
	}
	
	/**
	 * Set HTML Email Support
	 *
	 * @access	public
	 * @param	bool		If true, Emails will be sent with HTML support
	 * @return	@e void
	 */
	public function setHtmlEmails( $html_on )
	{
		$this->_mail_html_on		= $html_on;
	}
	
	/**
	 * Get all notification types
	 *
	 * @access	public
	 * @return	array
	 */
	public function getNotificationKeys()
	{
		$data	= $this->getNotificationData();
		
		return array_keys( $data );
	}
	
	/**
	 * Get notification config file data
	 *
	 * @access	public
	 * @param	bool	If true, will check show_callback to see if user has permission
	 * @return	array
	 */
	public function getNotificationData( $checkCallbacks=FALSE )
	{	
		//-----------------------------------------
		// Already stored the data?
		//-----------------------------------------
		
		if( count($this->_notificationData) )
		{
			return $this->_notificationData;
		}
		
		//-----------------------------------------
		// Get for each application
		//-----------------------------------------
				
		foreach( IPSLib::getEnabledApplications( array(), TRUE ) as $app_dir => $application )
		{
			$newLocation = IPSLib::getAppDir( $app_dir ) . '/extensions/notifications/config.php';
			$oldLocation = IPSLib::getAppDir( $app_dir ) . '/extensions/notifications.php';
			
			if ( ! is_file( $newLocation ) )
			{
				$newLocation = $oldLocation;
			}
			
			if ( is_file( $newLocation ) )
			{
				$classToLoad = IPSLib::loadLibrary( $newLocation, $app_dir . '_notifications', $app_dir );
				
				if ( class_exists( $classToLoad ) )
				{
					$class = new $classToLoad();
					$class->memberData = ipsRegistry::member()->fetchMemberData();
						
					$_NOTIFY	= $class->getConfiguration();

					if ( $checkCallbacks )
					{
						foreach( $_NOTIFY as $n )
						{
							$n['app']	= $app_dir;

							if ( $n['show_callback'] and method_exists( $class, $n['key'] ) )
							{
								if ( $class->$n['key']( $this->_member ) )
								{
									$this->_notificationData[] = $n;
								}
							}
							else
							{
								$this->_notificationData[] = $n;
							}
						}
					}
					else
					{
						$_NEW		= array();
						
						foreach( $_NOTIFY as $notify )
						{
							$notify['app']	= $app_dir;
							$_NEW[]			= $notify;
						}
						
						$_NOTIFY	= $_NEW;
						
						$this->_notificationData    = ( is_array( $this->_notificationData ) ) ? $this->_notificationData : array();
						$this->_notificationData	= array_merge( $this->_notificationData, $_NOTIFY );
					}
				}
			}
		}
		
		return $this->_notificationData;
	}
	
	/**
	 * Format the notification data as if it were configured
	 *
	 * @param	bool	Retain current config?
	 * @return	array
	 */
	public function formatNotificationData( $retain=false )
	{
		$_data		= $this->getNotificationData();
		$_defaults	= $this->cache->getCache('notifications');
		$_return	= array();

		foreach( $_data as $data )
		{
			$_return[ $data['key'] ]						= array();
			$_return[ $data['key'] ]['selected']			= $retain ? ( isset($_defaults[ $data['key'] ]['selected']) ? $_defaults[ $data['key'] ]['selected'] : $data['default'] ) : $data['default'];
			$_return[ $data['key'] ]['disabled']			= $retain ? ( isset($_defaults[ $data['key'] ]['disabled']) ? $_defaults[ $data['key'] ]['disabled'] : $data['disabled'] ) : $data['disabled'];
			$_return[ $data['key'] ]['disable_override']	= $retain ? ( isset($_defaults[ $data['key'] ]['disable_override']) ? $_defaults[ $data['key'] ]['disable_override'] : 0 ) : 0;
			$_return[ $data['key'] ]['app']					= $data['app'];
		}

		return $_return;
	}
	
	/**
	 * Get the ACP-set notification configuration
	 *
	 * @access	public
	 * @return	array
	 */
	public function getDefaultNotificationConfig()
	{
		return $this->cache->getCache('notifications') ? $this->cache->getCache('notifications') : $this->formatNotificationData();
	}
	
	/**
	 * Save the ACP-set notification configuration
	 *
	 * @access	public
	 * @param	array 	Notification configuration
	 * @return	@e void
	 */
	public function saveNotificationConfig( $config )
	{
		$this->cache->setCache( 'notifications', $config, array( 'array' => 1 ) );
	}
	
	/**
	 * Rebuild notifications cache
	 *
	 * @return	@e void
	 */
	public function rebuildNotificationsCache()
	{
		$this->saveNotificationConfig( $this->formatNotificationData( true ) );
	}
	
	/**
	 * Get the member's notification configuration
	 *
	 * @access	public
	 * @param	array		Member data
	 * @return	array
	 * @link	http://community.invisionpower.com/tracker/issue-23529-notification-defaults-dont-apply/
	 * @link	http://community.invisionpower.com/tracker/issue-23663-notification-issues/
	 */
	public function getMemberNotificationConfig( $member )
	{
		$_cache	= !empty($member['members_cache']) ? IPSMember::unpackMemberCache( $member['members_cache'] ) : array();
		
		if( !empty($_cache['notifications']) )
		{
			$savedTypes		= array_keys( $_cache['notifications'] );
			$_default		= $this->getDefaultNotificationConfig();
			$defaultTypes	= array_keys( $_default );
			$missingTypes	= array_diff( $defaultTypes, $savedTypes );
			
			//-----------------------------------------
			// Grab any missing types
			//-----------------------------------------
			
			foreach( $missingTypes as $_type )
			{
				$_cache['notifications'][ $_type ]	= $_default[ $_type ]['selected'];
			}
			
			//-----------------------------------------
			// Make changes if admin has disallowed override
			// since we saved our config
			//-----------------------------------------

			foreach( $_default as $k => $sub )
			{
				if( $sub['disable_override'] )
				{
					$_cache['notifications'][ $k ]['selected']	= $sub['selected'];
				}
				else if( $sub['disabled'] )
				{
					$_newSelection	= array();
					
					if( is_array($_cache['notifications'][ $k ]['selected']) AND count($_cache['notifications'][ $k ]['selected']) )
					{
						foreach( $_cache['notifications'][ $k ]['selected'] as $_thisType )
						{
							if( !in_array( $_thisType, $sub['disabled'] ) )
							{
								$_newSelection[]	= $_thisType;
							}
						}
					}

					$_cache['notifications'][ $k ]['selected']	= $_newSelection;
				}
			}

			return $_cache['notifications'];
		}
		else
		{
			return $this->getDefaultNotificationConfig();
		}
	}
	
	/**
	 * Send notification
	 *
	 * @access	public
	 * @return	bool
	 * @throws	NO_MEMBER_ID, MEMBER_BANNED, NO_NOTIFY_KEY, BAD_NOTIFY_KEY
	 */
	public function sendNotification()
	{
		$recipients = array();
	
		//-----------------------------------------
		// Who are we sending this to?
		//-----------------------------------------
		
		if ( !empty( $this->_recipients ) )
		{
			$recipients = $this->_recipients;
		}
		else
		{
			$recipients = array( $this->_member );
		}
				
		//-----------------------------------------
		// Loop recipients
		//-----------------------------------------
		
		$emailTo = array();
		
		foreach ( $recipients as $r )
		{
			//-----------------------------------------
			// If this is a member, send the notification
			// like normal
			//-----------------------------------------
			
			if ( is_array( $r ) )
			{
				if( $r['member_banned'] )
				{
					throw new Exception( 'MEMBER_BANNED' );
				}
				
				if( ! $r['member_id'] )
				{
					throw new Exception( 'NO_MEMBER_ID' );
				}
				
				if( ! $this->_notificationKey )
				{
					throw new Exception( 'NO_NOTIFY_KEY' );
				}
				
				$_config	= $this->getMemberNotificationConfig( $r );
				
				/* update msg show notification for inline pop-up */
				if ( in_array( $this->_notificationKey, $this->_showInlinePopUp ) )
				{
					IPSMember::save( $r['member_id'], array( 'core' => array( 'msg_show_notification' => 1 ) ) );
					
					/* If inline is disabled, we need to store as hidden anyway */
					$data = $this->formatNotificationData();
					
					if ( in_array( 'inline', $data[ $this->_notificationKey ]['disabled'] ) )
					{
						$this->_sendInlineNotification( $r, true );
					}
				}
				elseif ( !isset( $_config[ $this->_notificationKey ] ) or !is_array( $_config[ $this->_notificationKey ] ) )
				{
					throw new Exception( 'BAD_NOTIFY_KEY' );
				}
				
				//-----------------------------------------
				// Send appropriate notifications.
				// We can have more than one notification method for each
				//	notification type.
				//-----------------------------------------
						
				if ( is_array($_config[ $this->_notificationKey ]['selected']) AND count($_config[ $this->_notificationKey ]['selected']) )
				{
					foreach( $_config[ $this->_notificationKey ]['selected'] as $_type )
					{
						switch( $_type )
						{
							case 'email':
								$emailTo[] = $r['email'];
							break;
							
							case 'inline':
								$this->_sendInlineNotification( $r );
							break;
							
							case 'mobile':
								$this->_sendMobileNotification( $r );
							break;
						}
					}
				}
			}
			
			//-----------------------------------------
			// If it's just an Email, add it to the
			// list of Emails to send to
			//-----------------------------------------
			
			else
			{
				$emailTo[] = $r;
			}
		}
	
		//-----------------------------------------
		// Now send the Emails
		//-----------------------------------------
				
		if ( !empty( $emailTo ) )
		{
			$this->_sendEmailNotification( $emailTo );
		}
		
	}
	
	/**
	 * Send a notification via mobile device
	 *
	 * @access	protected
	 * @param	array		Member data
	 * @return	mixed		True, or can output an error
	 */
	protected function _sendMobileNotification( $member )
	{
		/* Just save the notification, a task will handle it later */
		if( IPSMember::canReceiveMobileNotifications( $member ) )
		{
			$this->DB->insert( 'mobile_notifications', array(
																'notify_title'	=> strip_tags( $this->_notificationTitle ),
																'notify_date'	=> time(),
																'member_id'		=> $member['member_id'],
																'notify_url'	=> $this->_notificationUrl,
															)
							);
		}
	}
	
	/**
	 * Send a notification via email
	 *
	 * @access	protected
	 * @param	array		Email Addresses
	 * @return	bool
	 */
	protected function _sendEmailNotification( $recipients )
	{
		/* Recipients */
		$to = $recipients[0];
		$cc = array();
		
		unset( $recipients[0] );

		if ( ! empty( $recipients ) )
		{
			$cc = $recipients;
		}
		
		$subject = ( ! empty( $this->_emailTitle ) ) ? strip_tags( $this->_emailTitle ) : strip_tags( $this->_notificationTitle );
		
		/* Add to mail queue */
		$this->DB->insert( 'mail_queue', array( 'mail_to'      => $to,
												'mail_cc'      => ( count($cc) ? implode( ',', $cc ) : '' ),
												'mail_from'    => $this->settings['email_out'],
												'mail_date'    => time(),
												'mail_html_on' => intval( $this->_mail_html_on ),
												'mail_subject' => $subject,
												'mail_content' => $this->_notificationText_forEmails ) );

		$cache					= $this->cache->getCache('systemvars');
		$cache['mail_queue']	+= 1;
		$this->cache->setCache( 'systemvars', $cache, array( 'array' => 1, 'donow' => 1 ) );
		
		return true;
	}
	
	/**
	 * Send an inline notification
	 *
	 * @access	protected
	 * @param	array		Member data
	 * @return	bool
	 */
	protected function _sendInlineNotification( $member, $hidden=false )
	{
		$blockedFromInline = $this->getBlockedFromInline();
		
		//-----------------------------------------
		// First, make sure member doesn't have too many
		//-----------------------------------------
		
		$this->_truncateInlineNotifications( $member );
		
		/* Blocked? */
		if ( $hidden === false )
		{
			 if ( in_array( $this->_notificationKey, $blockedFromInline ) )
			 {
			 	$hidden = true;
			 }
		}
		
		//-----------------------------------------
		// Insert new notification
		//-----------------------------------------
		
		$_insert	= array( 'notify_to_id'		=> $member['member_id'],
							 'notify_from_id'	=> intval($this->_from['member_id']),
							 'notify_sent'		=> IPS_UNIX_TIME_NOW,
							 'notify_read'		=> ( $hidden === true ) ? 2 : 0,
							 'notify_title'		=> $this->_notificationTitle,
							 'notify_text'		=> $this->_notificationText_forDisplay,
							 'notify_type_key'	=> $this->_notificationKey,
							 'notify_url'		=> $this->_notificationUrl );

		/* Meta? */
		$meta = $this->getMetaData();
		
		if ( $meta !== false )
		{
			$_insert['notify_meta_app']  = $meta['meta_app'];
			$_insert['notify_meta_area'] = $meta['meta_area'];
			$_insert['notify_meta_id']   = $meta['meta_id'];
			$_insert['notify_meta_key']  = $this->_getMetaKey( $meta );
		}
		
		$this->DB->insert( 'inline_notifications', $_insert );
		
		//-----------------------------------------
		// Update member record
		//-----------------------------------------
		
		if ( ! $hidden )
		{
			$this->DB->update( 'members', 'notification_cnt=notification_cnt+1', 'member_id=' . $member['member_id'], true, true );
		}
		
		return true;
	}
	
	/**
	 * Clear out old notifications if there's a limit to how many member can have
	 *
	 * @access	public
	 * @param	array		Member data
	 * @return	@e void
	 */
	public function _truncateInlineNotifications( $member )
	{
		//-----------------------------------------
		// Determine member's limit first
		//-----------------------------------------
		
		$groups	= array( $member['member_group_id'] );
		
		if( $member['mgroup_others'] )
		{
			$_others	= IPSText::cleanPermString( $member['mgroup_others'] );
			$groups		= ( is_array( $groups ) AND is_array( $_others ) ) ? array_merge( $_others, $groups ) : array();
		}
		
		//-----------------------------------------
		// 0 is best, otherwise higher is better
		//-----------------------------------------
		
		$_limit			= 0;
		
		foreach( $groups as $_group )
		{
			$_thisLimit	= $this->caches['group_cache'][ $_group ]['g_max_notifications'];
			
			if( !$_thisLimit )
			{
				$_limit	= 0;
				break;
			}
			else if( $_thisLimit > $_limit )
			{
				$_limit	= $_thisLimit;
			}
		}

		//-----------------------------------------
		// We have a limit
		//-----------------------------------------

		if( $_limit )
		{
			//-----------------------------------------
			// Get current count
			//-----------------------------------------
			
			$_count	= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'inline_notifications', 'where' => 'notify_to_id=' . $member['member_id'] ) );
			
			//-----------------------------------------
			// At limit?
			// We use >= because this is run immediately before
			//	we add a new notification, so if we are at limit
			//	we still need to remove 1
			//-----------------------------------------

			if( $_count['total'] >= $_limit )
			{
				$_toDelete	= ( $_count['total'] + 1 ) - $_limit;
				$ids        = array();
				
				/* Fetch Ids */
				$this->DB->build( array( 'select' => 'notify_id',
										 'from'   => 'inline_notifications',
										 'where'  => 'notify_to_id=' . $member['member_id'],
										 'order'  => 'notify_sent ASC',
										 'limit'  => array( 0, $_toDelete ) ) );
				
				$this->DB->execute();
				
				while( $row = $this->DB->fetch() )
				{
					$ids[] = $row['notify_id'];
				}
				
				/* Delete */
				if ( count( $ids ) )
				{
					$this->DB->delete( 'inline_notifications', 'notify_id IN(' . implode( ',', $ids ) . ')' );
				}
			}
		}

		return;
	}
	
	/**
	 * Rebuild a member's unread notification count
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function rebuildUnreadCount( $memberId=null )
	{
		$memberId = ( $memberId === null ) ? $this->_member['member_id'] : intval( $memberId );
		
		$count	= $this->DB->buildAndFetch( array( 'select' => 'count(*) as total', 'from' => 'inline_notifications', 'where' => 'notify_to_id=' . $memberId . ' AND notify_read=0' ) );
		
		$this->DB->update( 'members', array( 'notification_cnt' => $count['total'] ), 'member_id=' . $memberId );
	}
	
	/**
	 * Fetch new PM notification
	 *
	 * @access	public
	 * @param	int			Number if items to limit
	 * @param	string		Sort column
	 * @param	string		Sort order
	 * @param	bool		Only get unread notifications
	 * @param	bool		Run text through preDisplayParse
	 * @return 	array 		Unread notifications
	 */
	public function fetchUnreadNotifications( $limit=0, $sortKey='notify_sent', $sortOrder='desc', $unread=1, $parseText=false, $keyNames=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$return   = array();
		$limit    = ( $limit ) ? array( 0, intval( $limit ) ) : array( 0, 500 );
		$members  = array();
		$mids     = array();
		$where    = "";
		$unread   = ( is_array( $unread ) ) ? $unread : array( $unread );
		
		if ( count( $keyNames ) )
		{
			$where = " AND notify_type_key IN ('" . implode( "','", $keyNames ) . "')";
		}
		
		//-----------------------------------------
		// Fetch unread notifications
		//-----------------------------------------
		
		$this->DB->build( array( 'select'   => '*',
								 'from'     => 'inline_notifications',
								 'where'    => 'notify_to_id=' . $this->_member['member_id'] . ' AND notify_read IN (' . implode( ',', $unread ) . ')' . $where,
								 'order'    => $sortKey . ' ' . $sortOrder,
								 'limit'    => $limit ) );
		$outer	= $this->DB->execute();
		
		while( $row = $this->DB->fetch($outer) )
		{
			/* As the email template parser makes an attempt to reparse 'safe' HTML, we need to make it safe here */
			$row['notify_text'] = IPSText::htmlspecialchars( $row['notify_text'] );
	
	 		IPSText::getTextClass('bbcode')->parse_smilies				= 1;
	 		IPSText::getTextClass('bbcode')->parse_nl2br				= 1;
	 		IPSText::getTextClass('bbcode')->parse_html					= 0;
	 		IPSText::getTextClass('bbcode')->parse_bbcode				= 1;
	 		IPSText::getTextClass('bbcode')->parsing_section			= 'global';
	 		
	 		if( $parseText )
	 		{
	 			$row['notify_text'] = IPSText::getTextClass('bbcode')->preDisplayParse( nl2br( $row['notify_text'] ) );
 			}
	 		
	 		$row['notify_icon']	= $this->getNotificationIcon( $row['notify_type_key'] );
 			
			$return[ $row['notify_sent'] . '.' . $row['notify_id'] ] = $row;
			
			/* Store member id */
			$mids[ $row['notify_from_id'] ] = $row['notify_from_id'];
		}
		
		/* Got anything? */
		if ( ! count( $return ) )
		{
			return array();
		}
		
		if ( count( $mids ) )
		{
			$members = IPSMember::load( array_keys( $mids ), 'all' );
			
			if ( count( $members ) )
			{
				foreach( $return as $key => $data )
				{ 
					if ( in_array( $data['notify_from_id'], array_keys( $members ) ) )
					{
						$return[ $key ]['member'] = IPSMember::buildProfilePhoto( $members[ $data['notify_from_id'] ] );
					}
				}
			}
		}

		/* 3.1 didn't store notify_from_id so we need to catch that */
		foreach( $return as $key => $data )
		{
			if( !$data['member']['member_id'] )
			{
				$return[ $key ]['member'] = IPSMember::buildProfilePhoto( 0 );
			}
		}
		
		/* Return 'em */
		return $return;
	}
	
	/**
	 * Fetch latest notifications
	 *
	 * @access	public
	 * @param	int			Number if items to limit
	 */
	public function fetchLatestNotifications( $limit=10, $keyNames=array(), $getHidden=false )
	{
		$unread = ( $getHidden ) ? array(0,1,2) : array(0,1);
		
		return $this->fetchUnreadNotifications( $limit, 'notify_sent', 'desc', $unread, false, $keyNames );
	}
	
	/**
	 * Get me the latest notification and FAST or whatever...
	 */
	public function getLatestNotificationForInlinePopUp()
	{
		$latest = $this->fetchLatestNotifications( 1, $this->_showInlinePopUp, true );
		$detail = array();
		
		if ( ! is_array( $latest ) OR !count( $latest ) )
		{
			return false;
		}
		
		$latest = array_shift( $latest );
		
		if( !is_array($latest) )
		{
			$latest	= array();
		}
		
		/* Alright can we get a better version of the text? */
		if ( ! empty( $latest['notify_meta_app'] ) && ! empty( $latest['notify_meta_area'] ) && ! empty( $latest['notify_meta_id'] ) )
		{
			$detail = $this->getLinkedDataByMetaData( $this->_cleanMeta( $latest ) );
		}
		
		/* Do it the old fashioned way */
		if ( empty( $detail['content'] ) )
		{
			$detail = array( 'authorId' => $latest['notify_from_id'],
							 'content'	=> $latest['notify_text'],
							 'date'		=> $latest['notify_sent'],
							 'title'	=> $latest['notify_title'],
							 'type'		=> $this->lang->words['gbl_notify_item'] );
		}
		
		/* Parse date */
		$detail['date_parsed'] = $this->lang->getDate( $detail['date'], 'short' );
		
		/* Format the content */
		$detail['content'] = nl2br( IPSText::getTextClass( 'bbcode' )->stripAllTags( $detail['content'] ) );
		
		/* Format the author */
		$author  = IPSMember::buildDisplayData( IPSMember::load( $detail['authorId'], 'all' ), array( '__all__' => 1 ) );
		
		/* Flatten for JSON template eval */
		foreach( $author as $k => $v )
		{
			$detail['member_' . $k ] = $v;
		}
		
		$detail['member_PhotoTag'] = IPSMember::buildPhotoTag( $author, 'mini' );
		
		/* Slap on the URL */
		$detail['url']    = $latest['notify_url'];
		
		return array_merge( $latest, $detail );
	}
	
	/**
	 * Get notification icon
	 *
	 * @access	public
	 * @param	string		Notification key
	 * @return	string		Notification icon
	 */
	public function getNotificationIcon( $key )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------

		if( !$key )
		{
			return '';
		}
		
		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		$this->_notificationData = $this->getNotificationData();
		
		//-----------------------------------------
		// Now look for key and return icon
		//-----------------------------------------
		
		foreach( $this->_notificationData as $data )
		{
			if( $data['key'] == $key )
			{
				return $data['icon'];
			}
		}
		
		return '';
	}
	
	public function getLinkedDataByMetaData( $metaData=array() )
	{
		$data     = false;
		$metaData = ( count( $metaData ) ) ? $metaData : $this->getMetaData();
		
		if ( $this->_hasAppClass( $metaData ) )
		{
			$data = $this->_getAppClass( $metaData )->getLinkedDataByMetaData( $metaData );
		}
		
		return $data;
	}
	
	/**
	 * Mark a user's notifications as read
	 * @param int $memberId
	 */
	public function markNotificationsAsReadByMemberId( $memberId )
	{
		$this->DB->update( 'inline_notifications', array( 'notify_read' => 1 ), 'notify_to_id=' . intval( $memberId ) . ' AND notify_read=0' );
		
		$this->rebuildUnreadCount( $memberId );
	}
	
	/**
	 * Mark notifications as read by meta data (used by external methods)
	 * @param unknown_type $metaData
	 */
	public function markNotificationsAsReadByMetaData( $metaData=array(), $memberData=null )
	{
		$app        = trim( $metaData['meta_app'] );
		$area       = trim( $metaData['meta_area'] );
		$ids        = ( ! is_array( $metaData['meta_id'] ) ) ? array( $metaData['meta_id'] ) : $metaData['meta_id'];
		$memberData = ( $memberData === null ) ? $this->_member : $memberData;
		
		$keys = array();
		
		if ( $app && $area && count( $ids ) )
		{
			foreach( $ids as $id )
			{
				$keys[] = $this->DB->addSlashes( $this->_getMetaKey( array( 'meta_app' => $app, 'meta_area' => $area, 'meta_id' => $id ) ) );
			}
			
			if ( count( $keys ) )
			{
				$this->DB->update( 'inline_notifications', array( 'notify_read' => 1 ), 'notify_meta_key IN(\'' . implode( "','", $keys ) . '\') AND notify_read=0' );
			}
			
			if ( $memberData['notification_cnt'] )
			{
				$this->setMember( $memberData );
				$this->rebuildUnreadCount();
			}
		}
	}
	
	/**
	 * Delete notifications as read by meta data (used by external methods)
	 * @param unknown_type $metaData
	 */
	public function deleteNotificationsAsReadByMetaData( $metaData=array(), $memberData=null )
	{
		$app        	 = trim( $metaData['meta_app'] );
		$area       	 = trim( $metaData['meta_area'] );
		$ids        	 = ( ! is_array( $metaData['meta_id'] ) ) ? array( $metaData['meta_id'] ) : $metaData['meta_id'];
		$notify_type_key = ( ! empty( $metaData['notify_type_key'] ) ) ? $metaData['notify_type_key'] : false;
		$memberData 	 = ( $memberData === null ) ? $this->_member : $memberData;
		
		$keys = array();
		
		if ( $app && $area && count( $ids ) )
		{
			foreach( $ids as $id )
			{
				$keys[] = $this->DB->addSlashes( $this->_getMetaKey( array( 'meta_app' => $app, 'meta_area' => $area, 'meta_id' => $id ) ) );
			}
			
			if ( count( $keys ) )
			{
				if ( $notify_type_key !== false )
				{
					$notify_type_key = ( is_array( $notify_type_key ) ) ? ' AND notify_type_key=\'' . implode( "'", $notify_type_key ) . '\')' : ' AND notify_type_key=\'' . $notify_type_key . '\'';
				}
				
				$this->DB->update( 'inline_notifications', array( 'notify_read' => 2 ), 'notify_meta_key IN(\'' . implode( "','", $keys ) . '\')' . $notify_type_key );
			}
			
			if ( $memberData['notification_cnt'] )
			{
				$this->setMember( $memberData );
				$this->rebuildUnreadCount();
			}
		}
	}
	
	/**
	 * Prune notification entries older than a specific date
	 * @param int $unix
	 */
	public function deleteNotificationsOlderThan( $unix )
	{
		if ( $unix AND $unix <= time() )
		{
			/* Fetch them */
			$this->DB->build( array( 'select' => 'notify_id, notify_to_id, notify_sent, notify_read',
									 'from'   => 'inline_notifications',
									 'where'  => 'notify_sent <= ' . $unix,
									 'order'  => 'notify_sent ASC',
									 'limit'  => array( 0, 500 ) ) );
			
			$this->DB->execute();
			
			$mids = array();
			$nids = array();
			
			while( $row = $this->DB->fetch() )
			{
				$mids[ $row['notify_to_id'] ] = $row['notify_to_id'];
				$nids[ $row['notify_id'] ]    = $row['notify_id'];
			}
			
			/* Delete the notifications */
			if ( count( $nids ) )
			{
				$this->DB->delete( 'inline_notifications', 'notify_id IN (' . implode( ',', array_keys( $nids ) ) . ')' );
			}
			
			/* Recount members */
			if ( count( $mids ) )
			{
				foreach( $mids as $id )
				{
					$this->rebuildUnreadCount( $id );
				}
			}
			
			return count( $nids );
		}
		
		return 0;
	}
	
	/**
	 * Do we have an application class?
	 * @return boolean
	 */
	private function _hasAppClass( $metaData=array() )
	{
		$metaData = ( count( $metaData ) ) ? $metaData : $this->getMetaData();
		
		if ( is_object( $this->_appClass ) )
		{
			return true;
		}
		
		$app      = $metaData['meta_app'];
		$area     = $metaData['meta_area'];
		$file     = IPSLib::getAppDir( $app ) . '/extensions/notifications/' . $area . '.php';
		$key      = $metaData['meta_app'] . ';' . $metaData['meta_area'];
		
		if ( IPSLib::appIsInstalled( $app ) )
		{
			if ( is_file( $file ) )
			{
				$classToLoad = IPSLib::loadLibrary( $file, $app . '_class_notifications', $app );
				
				if ( class_exists( $classToLoad ) )
				{
					$this->_appClass[ $key ] = new $classToLoad();
					
					return true;
				}
			}
		}
		
		return false;
	}
	
	/**
	 * Fetch the app specific class
	 */
	private function _getAppClass( $metaData )
	{
		$metaData = ( count( $metaData ) ) ? $metaData : $this->getMetaData();
		$key      = $metaData['meta_app'] . ';' . $metaData['meta_area'];
		
		if ( is_object( $this->_appClass[ $key ] ) )
		{
			return $this->_appClass[ $key ];
		}
		
		$metaData = $this->getMetaData();
		$app      = $metaData['meta_app'];
		$area     = $metaData['meta_area'];
		$file     = IPSLib::getAppDir( $app ) . '/extensions/notifications/' . $area . '.php';
		
		if ( IPSLib::appIsInstalled( $app) )
		{
			if ( is_file( $file ) )
			{
				$classToLoad = IPSLib::loadLibrary( $file, $app . '_class_notifications', $app );
				
				if ( class_exists( $classToLoad ) )
				{
					$this->_appClass[ $key ] = new $classToLoad();
					
					return $this->_appClass[ $key ];
				}
			}
		}
	}
	
	/**
	 * Generates a meta key ...
	 * @param array $array
	 * @return string
	 */
	private function _getMetaKey( array $array )
	{
		return md5( $array['meta_app'] . ';' . $array['meta_area'] . ';' . $array['meta_id'] );
	}
	
	/**
	 * Clean meta stuffs
	 * Ensures that DB naming conventions aren't used
	 * @param	Array	Dirty meta
	 * @return	Array	Clean is betta haha!
	 */
	private function _cleanMeta( $meta )
	{
		$clean = array( 'notify_meta_app', 'notify_meta_area', 'notify_meta_id' );
		
		if ( is_numeric( $meta ) )
		{
			$meta = array( 'meta_id' => $meta );
		}
		
		if ( is_array( $meta ) )
		{
			foreach( $meta as $k => $v )
			{
				if ( in_array( $k, $clean ) )
				{
					unset( $meta[ $k ] );
					$meta[ str_replace( 'notify_', '', $k ) ] = $v;
				}
			}
		}
		
		if ( empty( $meta['meta_app'] ) )
		{
			$meta['meta_app'] = IPS_APP_COMPONENT;
		}
		
		if ( empty( $meta['meta_area'] ) )
		{
			$meta['meta_area'] = $this->_notificationKey;
		}
		
		return $meta;
	}
}