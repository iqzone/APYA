<?php
/**
 * @file		composite.php 	Provides classes to manage the like system
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: mmecham $
 * @since		11th October 2010
 * $LastChangedDate: 2012-06-06 05:12:48 -0400 (Wed, 06 Jun 2012) $
 * @version		v3.3.3
 * $Revision: 10870 $
 * 
 * <b>Example Usage:</b>
 * @code
 * $app  = 'gallery';
 * $area = 'images';
 * $like = classes_like::bootstrap( $app, $area );
 * print $like->isLiked( $relId );
 * $html	= $like->render( 'summary', $relId );
 * print $html;
 * 
 * RESULT: Matt, Joe, Bob and 5 others like this
 * @endcode
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		classes_like_registry
 * @brief		Quick registry class for common methods
 * @author		Matt
 */
class classes_like_registry
{
	/**
	 * App key
	 *
	 * @var		$app
	 */
	static protected $app	= null;
	
	/**
	 * Area key
	 *
	 * @var		$area
	 */
	static protected $area	= null;
	
	/**
	 * Total count
	 *
	 * @var		$count
	 */
	static protected $count	= null;
	
	/**
	 * Cached bootstrap loaders
	 *
	 * @var		$bootstraps
	 */
	static protected $bootstraps = array();
	
	/**
	 * Get application key
	 * 
	 * @return	@e string
	 */
	static public function getApp()
	{
		return self::$app;
	}
	
	/**
	 * Set application key
	 * 
	 * @param	string		$app		Application key
	 * @return	@e void
	 */
	static public function setApp( $app )
	{
		self::$app = $app;
	}
	
	/**
	 * Get area key
	 * 
	 * @return	@e string
	 */
	static public function getArea()
	{
		return self::$area;
	}
	
	/**
	 * Set area key
	 * 
	 * @param	string		$area		Area key
	 * @return	@e void
	 */
	static public function setArea( $area )
	{
		self::$area = $area;
	}
	
	/**
	 * Get total count (saves on queries)
	 * 
	 * @return	@e integer
	 */
	static public function getTotalCount()
	{
		return self::$count;
	}
	
	/**
	  * Set total count (saves on queries)
	 * 
	 * @param	integer		$count		Likes count
	 * @return	@e void
	 */
	static public function setTotalCount( $count )
	{
		self::$count = $count;
	}
	
	/**
	 * Build key, either for cache table or full table
	 *
	 * @param	integer		$relId		Relationship ID
	 * @param	integer		$memberId	Member ID
	 * @return	@e binary	Binary key
	 */
	static public function getKey( $relId, $memberId=null )
	{
		/* Check */
		if ( empty( $relId ) AND empty( $memberId ) )
		{		
			trigger_error( "Relationship ID missing in " . __CLASS__ . '::' . __FUNCTION__, E_USER_WARNING );
		}
		
		if ( $memberId === null )
		{
			/* For relative item only */
			return md5( self::$app . ';' . self::$area . ';' . $relId );
		}
		else if ( ! empty( $memberId ) )
		{
			if ( $relId === null )
			{
				/* For member speciifc relative item */
				return md5( self::$app . ';' . self::$area . ';' . $memberId );
			}
			else
			{
				/* For member speciifc relative item */
				return md5( self::$app . ';' . self::$area . ';' . $relId . ';' . $memberId );
			}
		}
		else
		{
			trigger_error( "Member ID missing in " . __CLASS__ . '::' . __FUNCTION__, E_USER_WARNING );
		}
	}
}


/**
 *
 * @class		classes_like_meta
 * @brief		Simple static class for fetching meta data
 * @author		Matt
 */
class classes_like_meta
{
	/**
	 * Retrieve the content title
	 *
	 * @param	array		$like		Like data
	 * @return	@e string	Title of content liked
	 */
	static public function getTitleFromId( $like )
	{
		if ( empty( $like['like_app'] ) OR empty( $like['like_area'] ) OR empty( $like['like_rel_id'] ) )
		{
			trigger_error( "You must have like_app, like_rel_id and like_area set", E_USER_WARNING );
		}
		
		$_bootstrap	= classes_like::bootstrap( $like['like_app'], $like['like_area'] );
		
		return $_bootstrap->getTitleFromId( $like['like_rel_id'] );
	}

	/**
	 * Retrieve the content URL
	 *
	 * @param	array		$like		Like data
	 * @return	@e string	URL of content liked
	 */
	static public function getUrlFromId( $like )
	{
		if ( empty( $like['like_app'] ) OR empty( $like['like_area'] ) OR empty( $like['like_rel_id'] ) )
		{
			trigger_error( "You must have like_app, like_rel_id and like_area set", E_USER_WARNING );
		}
		
		$_bootstrap	= classes_like::bootstrap( $like['like_app'], $like['like_area'] );
		
		return $_bootstrap->getUrlFromId( $like['like_rel_id'] );
	}
	
	/**
	 * Fetch all meta data from various like rows
	 * 
	 * @param	array		$likes		Like rows
	 * @return	@e array
	 */
	static public function get( array $likes )
	{
		$apps    = array();
		$process = array();
		$lookUp  = array();
		
		foreach( $likes as $id => $like )
		{
			if ( empty( $like['like_app'] ) OR empty( $like['like_area'] ) OR empty( $like['like_rel_id'] ) )
			{
				continue;
			}
			
			$apps[ $like['like_app'] . '-' . $like['like_area'] ][ $id ] = $like;
			$lookUp[ $like['like_app'] . '-' . $like['like_area'] . '-' . $like['like_rel_id'] ] = $like['like_id'];
		}
		
		/* Process based on apps */
		foreach( $apps as $app => $data )
		{
			list( $_app, $_area ) = explode( '-', $app );
			
			$_bootstrap	= classes_like::bootstrap( $_app, $_area );
			
			$relIds = array();
			
			foreach( $data as $_k => $_v )
			{
				$relIds[] = $_v['like_rel_id'];
			}
		
			/* Bulk fetch */
			$process[ $app ] = $_bootstrap->getMeta( $relIds );
		}
		
		/* Fold up array */
		foreach( $process as $app => $data )
		{
			list( $_app, $_area ) = explode( '-', $app );
			
			foreach( $data as $relId => $_like )
			{
				if ( $relId )
				{
					$like_id = $lookUp[ $_app . '-' . $_area . '-' . $relId ];
					
					if ( $like_id AND is_array( $_like ) AND is_array( $likes[ $like_id ] ) )
					{
						$likes[ $like_id ] = array_merge( $likes[ $like_id ], $_like );
					}
				}
			}
		}
		
		return $likes;
	}
}


/**
 *
 * @class		classes_like
 * @brief		Factory class, loads composite and child class
 * @author		Matt
 */
class classes_like
{
	/**
	 * Applications objects
	 *
	 * @var $apps
	 */
	static protected $apps;
	
	/**
	 * Bootstrap function to load the required file/class and setup the initial variables
	 *
	 * @param	string		$app		Application key
	 * @param	string		$area		Area
	 * @return	@e object	Like object for the loaded key (app.area)
	 */
	static public function bootstrap( $app=null, $area=null )
	{
		if ( $app === null OR $area === null )
		{
			trigger_error( "App or area missing from classes_like", E_USER_WARNING );
		}
		
		/* Pointless comment! */
		if( $area != 'default' )
		{
			$_file	= IPSLib::getAppDir( $app ) . '/extensions/like/' . $area . '.php';
			$_class	= 'like_' . $app . '_' . $area . '_composite';
		}

		$_key	= md5( $app . $area );
		
		/* Get from cache if already cached */
		if( isset( self::$apps[ $_key ] ) )
		{
			return self::$apps[ $_key ];
		}
		
		/* Otherwise create object and cache */
		if ( ! is_file( $_file ) )
		{
			$_file = IPSLib::getAppDir( $app ) . '/extensions/like/default.php';
			$_class = 'like_' . $app . '_composite';
			
			if ( ! is_file( $_file ) )
			{
				throw new Exception( "No like class available for $app - $area" );
			}
		}
				
		$classToLoad = IPSLib::loadLibrary( $_file, $_class, $app );
		
		if ( class_exists( $classToLoad ) )
		{
			classes_like_registry::setApp( $app );
			classes_like_registry::setArea( $area );
			
			self::$apps[ $_key ] = new $classToLoad();
			self::$apps[ $_key ]->init();
		}
		else
		{
			throw new Exception( "No like class available for $app - $area" );
		}
		
		return self::$apps[ $_key ];
	}
}


/**
 *
 * @class		classes_like_composite
 * @brief		Composite class, holds main functionality
 * @author		Matt
 */
abstract class classes_like_composite
{
	/**
	 * Registry Object Shortcuts
	 *
	 * @var		$registry
	 * @var		$DB
	 * @var		$settings
	 * @var		$request
	 * @var		$lang
	 * @var		$member
	 * @var		$memberData
	 * @var		$cache
	 * @var		$caches
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
	 * Cache object
	 *
	 * @var		$likeCache
	 */
	protected $likeCache = null;
	
	/**
	 * Application key
	 *
	 * @var		$_app
	 */
	protected $_app;
	
	/**
	 * Area key
	 *
	 * @var		$_area
	 */
	protected $_area;
	
	/**
	 * Init all. Yes, it is.
	 *
	 * @return	@e void
	 */
	public function init()
	{
		$this->_app  = classes_like_registry::getApp();
		$this->_area = classes_like_registry::getArea();
		
		if ( empty( $this->_app ) OR empty( $this->_area ) )
		{
			trigger_error( "Missing area or app variable in " . __CLASS__ . '::' . __FUNCTION__, E_USER_WARNING );
		}
		
		/* Fetch cache class */
		$this->likeCache = classes_like_cache::getInstance();
		
		/* Set a default cache expiration of 24 hours */
		$this->likeCache->setExpiration( 86400 );
	}
	
	/**
	 * Toggle visibility
	 * 
	 * @param	mixed		$relId		Relationship ID or array ids
	 * @param	boolean		$visible	Visible (true) or not (false)
	 * @return	@e boolean
	 */
	public function toggleVisibility( $relId, $visible=true )
	{
		if ( empty( $relId ) )
		{
			trigger_error( "Data missing in " . __CLASS__ . '::' . __FUNCTION__, E_USER_WARNING  );
		}
		
		/* Update any rows pointing to this rel id to be visible/invisible */
		if( is_array($relId) )
		{
			if( count($relId) )
			{
				$this->DB->update( 'core_like', array( 'like_visible' => $visible ? 1 : 0 ), "like_app='{$this->_app}' AND like_area='{$this->_area}' AND like_rel_id IN (" . implode( ',', $relId ) . ')' );
			}
		}
		else
		{
			$this->DB->update( 'core_like', array( 'like_visible' => $visible ? 1 : 0 ), "like_app='{$this->_app}' AND like_area='{$this->_area}' AND like_rel_id=" . $relId );
		}

		/* Flag cache as stale */
		$this->likeCache->isNowStale( $relId );
		
		return true;
	}
	
	/**
	 * Add a like
	 * 
	 * @param	integer		$relId			Relationship ID
	 * @param	integer		$memberID		Member ID of user being added
	 * @param	array		$notifyOpts		Notification options
	 * @param	integer		$isAnon			Anonymous flag
	 * @return	@e boolean
	 */
	public function add( $relId, $memberId, array $notifyOpts, $isAnon=0 )
	{
		if ( empty( $relId ) OR empty( $memberId ) )
		{
			trigger_error( "Data missing in " . __CLASS__ . '::' . __FUNCTION__, E_USER_WARNING  );
		}
		
		/* first check to ensure we've not already like'd this item */
		if ( $this->isLiked( $relId, $memberId, true ) )
		{
			/* if any one cares to check, then we're all good */
			return false;
		}
		
		$memberData = IPSMember::load( $memberId );
		
		/* Build */
		$save = array(  'like_id'          => classes_like_registry::getKey( $relId, $memberId ),
					    'like_lookup_id'   => classes_like_registry::getKey( $relId ),
						'like_lookup_area' => classes_like_registry::getKey( null, $memberId ),
						'like_app'         => $this->_app,
						'like_area'        => $this->_area,
						'like_rel_id'      => $relId,
					 	'like_member_id'   => $memberId,
						'like_added'       => IPS_UNIX_TIME_NOW,
						'like_is_anon'	   => intval($isAnon),
						'like_notify_do'   => $notifyOpts['like_notify_do'],
						'like_notify_meta' => $notifyOpts['like_notify_meta'],
						'like_notify_freq' => ( $notifyOpts['like_notify_do'] ) ? $notifyOpts['like_notify_freq'] : '',
						'like_visible'	   => 1,
						'like_notify_sent' => 0 );
		
		/* Do we have permission ? */
		if ( ! $this->notificationCanSend( array_merge( $save, $memberData ) ) )
		{
			return false;
		}
		
		$notifyOpts = $this->_cleanNotifyOptions($notifyOpts);
		
		/* Save to deebee */
		$this->DB->insert( 'core_like', $save );
		
		/* Flag cache as stale */
		$this->likeCache->isNowStale( $relId );
		
		return true;
	}
	
	/**
	 * Function to let plugins determine if a notification should not be sent.  Return false to send notification, or true to skip sending it.
	 * 
	 * @param	array	Notification data
	 * @return	@e bool
	 */
 	public function excludeNotification( $row )
 	{
 		return false;
 	}
	
	/**
	 * Send notifications to anyone subscribed to item
	 *
	 * @param	integer		$relId				Relationship ID
	 * @param	array		$type				Types of notifications to send (Possible: immediate, offline, daily, weekly)
	 * @param	array		$notificationOpts	Notification options [Keys: notification_key, notification_url, email_template, email_subject, email_lang_file, email_lang_app, build_message_array, from (optional), ignore_data( type => ids )]
	 * @param	array		$sentToIds			Pass a value by reference to get the ID numbers of members that were sent a notification
	 * @return	@e boolean
	 * @see		allowedFrequencies()
	 */
	public function sendNotifications( $relId, $type, $notificationOpts=array(), &$sentToIds=array() )
	{
		$sentToIds = array();
		
		if ( empty( $relId ) )
		{
			trigger_error( "Data missing in " . __CLASS__ . '::' . __FUNCTION__, E_USER_WARNING  );
		}
		
		$type	= is_array($type) ? $type : array( $type );
		
		/* Use session expiration, unless it is longer than 1 hour, then limit to 1 hour */
		$_limit	= $this->settings['session_expiration'];
		
		if( $_limit > 3600 )
		{
			$_limit	= 3600;
		}
		
		$expire = IPS_UNIX_TIME_NOW - $_limit;
				
		/* Fetch data by relID */
		$data = $this->getDataByRelationshipId( $relId, false, (array) $notificationOpts['ignore_data'], $type );
				
		/* Remove ourselves from notifications... */
		if ( $this->memberData['member_id'] && isset($data[ $this->memberData['member_id'] ]) && $notificationOpts['includeOwner'] !== true )
		{
			unset($data[ $this->memberData['member_id'] ]);
		}
		
		/* If no users, forget it */
		if ( is_null($data) )
		{
			return false;
		}
		
		$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
		$notifyLibrary	= new $classToLoad( $this->registry );
		
		foreach( $data as $row )
		{
			if ( ! $row['like_notify_freq'] OR ! in_array( $row['like_notify_freq'], $type ) )
			{
				continue;
			}
			
			/* make sure user is offline */
			if ( $row['like_notify_freq'] == 'offline' AND $row['last_activity'] > $expire )
			{
				continue;
			}
			
			/* Custom content type exclusion rules (e.g. in forums only send one notification per visit for offline types) */
			if( $this->excludeNotification( $row ) )
			{
				continue;
			}
			
			/* Can we actually send this one? */
			if ( $this->notificationCanSend( $row ) === false )
			{
				continue;
			}

			/* Extra protection in case a ghost record is in the table for a member that doesn't exist
				@link	http://community.invisionpower.com/tracker/issue-36793-error-when-editing-some-marketplace-files */
			if( !$row['like_member_id'] )
			{
				continue;
			}
				
			$buildMessage = array();
			
			if ( is_array( $notificationOpts['build_message_array'] ) and count( $notificationOpts['build_message_array'] ) )
			{
				foreach( $notificationOpts['build_message_array'] as $k => $v )
				{
					if ( preg_match( '/\-member:(.+?)\-/', $v, $_matches ) )
					{
						$v = str_replace( $_matches[0], $row[$_matches[1]], $v );
					}
					
					$buildMessage[ $k ] = $v;
				}
			}
			
			$sentToIds[] = $row['like_member_id'];
			
			/* Add in unsubscribe link */
			$key = $row['like_app'] . ';' . $row['like_area'] . ';' . $row['like_rel_id'] . ';' . $row['like_member_id'] . ';' . $row['member_id'] . ';' . $row['email'];
			$buildMessage['UNSUBCRIBE_URL'] = $this->registry->output->buildSEOUrl( 'app=core&amp;module=global&amp;section=like&amp;do=unsubscribe&amp;key=' . IPSText::base64_encode_urlSafe( $key ), 'public', 'unsubscribe', 'likeunsubscribe' );
			
			IPSText::getTextClass('email')->setPlainTextTemplate( IPSText::getTextClass('email')->getTemplate( $notificationOpts['email_template'], $row['language'], trim($notificationOpts['email_lang_file']), trim($notificationOpts['email_lang_app']) ) );
			IPSText::getTextClass('email')->buildMessage( $buildMessage );
			
			$notifyLibrary->setMember( $row );
			$notifyLibrary->setFrom( $notificationOpts['from'] ? $notificationOpts['from'] : $this->memberData );
			$notifyLibrary->setNotificationKey( $notificationOpts['notification_key'] );
			$notifyLibrary->setNotificationUrl( $notificationOpts['notification_url'] );
			$notifyLibrary->setNotificationText( IPSText::getTextClass('email')->getPlainTextContent() );
			$notifyLibrary->setNotificationTitle( $notificationOpts['email_subject'] ? $notificationOpts['email_subject'] : IPSText::getTextClass('email')->subject );
			
			if ( ! empty( $notificationOpts['email_only_subject'] ) && $notificationOpts['email_only_subject'] != IPSText::getTextClass('email')->subject )
			{
				$notifyLibrary->setEmailSubject( $notificationOpts['email_only_subject'], $row['language'], trim($notificationOpts['email_lang_file']), trim($notificationOpts['email_lang_app']) );
			}
			
			try
			{
				$notifyLibrary->sendNotification();
			}
			catch( Exception $e ){ }
			
			//-----------------------------------------
			// Update sent timestamp
			//-----------------------------------------
			
			$this->DB->update( 'core_like', array( 'like_notify_sent' => IPS_UNIX_TIME_NOW ), "like_id='" . classes_like_registry::getKey( $row['like_rel_id'], $row['like_member_id'] ) . "'" );
		}
		
		return ( is_array( $sentToIds ) ) ? array_unique( $sentToIds ) : true;
	}
	
	/**
	 * Send digest notifications to anyone subscribed to item
	 *
	 * @param	string		$type		Types of notifications to send (Possible: daily, weekly)
	 * @param	integer		$sendMax	Null (use ipsRegistry::$setting or send INT only)
	 * @return	@e boolean
	 * @see		allowedFrequencies()
	 */
	public function sendDigestNotifications( $type, $sendMax=null )
	{
		/* Fetch data */
		$blackhole = time(); // mktime( 0, 0 ); - http://community.invisionpower.com/tracker/issue-35320-daily-digests-every-2-days
		$time      = ( $type == 'daily' ) ? $blackhole - ( 86400 ) : $blackhole - ( 86400 * 7 );
		$data      = $this->getDataByAreaAndLastSentOlderThanDate( $time, array( $type ), false, $sendMax );
		
		if ( ! count( $data ) )
		{
			return false;
		}
		
		/* Check for outdated notifications and update them as required */
		$oldestPossDate = 0;
		
		if ( in_array( 'weekly', $types ) )
		{
			/* Grab 12 days to ensure we don't update rows ready to be sent */
			$oldestPossDate = $blackhole - ( 86400 * 10 );
		}
		else
		{
			/* Grab 4 days to account for timezone differences */
			$oldestPossDate = $blackhole - ( 86400 * 4 );
		}
		
		if ( $oldestPossDate )
		{
			$this->DB->update( 'core_like', array( 'like_notify_sent' => IPS_UNIX_TIME_NOW ), 'like_notify_sent < ' . $oldestPossDate );
		}
		
		$classToLoad   = IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
		$notifyLibrary = new $classToLoad( $this->registry );
		
		/* Array structure will be $data[ $memberId ][ $likeId ] = array(); */
		foreach( $data as $memberId => $_rows )
		{
			foreach( $_rows as $row )
			{
				if ( ! $row['like_notify_freq'] OR $row['like_notify_freq'] != $type )
				{
					continue;
				}
				
				/* Make sure we don't send huge digests if this is their first email since subscribing */
				$row['like_notify_sent']	= $row['like_notify_sent'] ? $row['like_notify_sent'] : $row['like_added'];

				/* Want to fetch the data from the plug in files? */
				$notificationOpts = $this->buildNotificationData( $row, $type );
					
				if ( ! is_array( $notificationOpts ) OR ! count( $notificationOpts ) )
				{
					/* This will get hit if topic hasn't had a post since notification was last sent - to prevent the same row from getting picked up each time this runs
						we need to update the last sent timestamp for the like */
					$this->DB->update( 'core_like', array( 'like_notify_sent' => IPS_UNIX_TIME_NOW ), 'like_id=\'' . classes_like_registry::getKey( $row['like_rel_id'], $row['like_member_id'] ) . '\'' );

					continue;
				}
				
				$buildMessage	= array();
				
				if ( is_array( $notificationOpts['build_message_array'] ) and count( $notificationOpts['build_message_array'] ) )
				{
					foreach( $notificationOpts['build_message_array'] as $k => $v )
					{
						if ( preg_match( '/\-member:(.+?)\-/', $v, $_matches ) )
						{
							$v = str_replace( $_matches[0], $row[$_matches[1]], $v );
						}
						
						$buildMessage[$k] = $v;
					}
				}
				
				IPSText::getTextClass('email')->getTemplate( $notificationOpts['email_template'], $row['language'], trim($notificationOpts['email_lang_file']), trim($notificationOpts['email_lang_app']) );
				IPSText::getTextClass('email')->buildMessage( $buildMessage );
	
				$notifyLibrary->setMember( $row );
				$notifyLibrary->setFrom( $notificationOpts['from'] ? $notificationOpts['from'] : $this->memberData );
				$notifyLibrary->setNotificationKey( $notificationOpts['notification_key'] );
				$notifyLibrary->setNotificationUrl( $notificationOpts['notification_url'] );
				$notifyLibrary->setNotificationText( IPSText::getTextClass('email')->message );
				$notifyLibrary->setNotificationTitle( $notificationOpts['email_subject'] ? $notificationOpts['email_subject'] : IPSText::getTextClass('email')->subject );
				
				try
				{
					$notifyLibrary->sendNotification();
				}
				catch( Exception $e ){ }
				
				/* Update sent timestamp */
				$this->DB->update( 'core_like', array( 'like_notify_sent' => IPS_UNIX_TIME_NOW ), 'like_id=\'' . classes_like_registry::getKey( $row['like_rel_id'], $row['like_member_id'] ) . '\'' );
			}
		}
		
		return true;
	}
	
	/**
	 * Removes all likes based on member ID
	 * 
	 * @param	integer		$memberId		Member ID
	 * @return	@e boolean
	 */
	public function removeByMemberId( $memberId )
	{
		if ( empty( $memberId ) )
		{
			trigger_error( "Data missing in " . __CLASS__ . '::' . __FUNCTION__, E_USER_WARNING  );
		}
		
		$where = "like_app='{$this->_app}' AND like_area='{$this->_area}' AND like_member_id=" . intval($memberId);
		
		/* Get rel ids */
		$relIds	= array();
		
		$this->DB->build( array( 'select' => 'like_rel_id', 'from' => 'core_like', 'where' => $where ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$relIds[]	= $r['like_rel_id'];
		}

		/* Delete likes */
		$this->DB->delete( 'core_like', $where );
									
		/* Flag cache as stale */
		$this->likeCache->isNowStale( $relIds );
		
		return true;
	}
	
	/**
	 * Removes a like
	 * 
	 * @param	mixed		$relId		Relationship IDs (int|array)
	 * @param	integer		$memberId	Optional - If supplied, it'll remove that member rel. or it will remove all
	 * @return	@e boolean
	 */
	public function remove( $relId, $memberId=null )
	{
		if ( empty( $relId ) )
		{
			trigger_error( "Data missing in " . __CLASS__ . '::' . __FUNCTION__, E_USER_WARNING  );
		}
		
		$where = '';
		
		if ( is_numeric($relId) )
		{
			$where = "='" . classes_like_registry::getKey( $relId, $memberId ) . "'";
		}
		elseif ( is_array($relId) )
		{
			$relId = IPSLib::cleanIntArray($relId);
			$keys  = array();
			
			if ( count($relId) )
			{
				foreach( $relId as $id )
				{
					$keys[] = "'" . classes_like_registry::getKey( $id, $memberId ) . "'";
				}
				
				$where = " IN (" . implode( ",", $keys ) . ")";
			}
		}
		
		/* Nowhere to be found? */
		if ( $where == '' )
		{
			return false;
		}
		
		/* Still here? Delete then! */
		if ( $memberId === null )
		{
			$this->DB->delete( 'core_like', "like_lookup_id " . $where );
		}
		else
		{
			/* Not liked? Save us the bother, then */
			if ( ! $this->isLiked($relId, $memberId) )
			{
				/* if any one cares to check, then we're all good */
				return false;
			}
			
			$this->DB->delete( 'core_like', "like_id " . $where );
		}
												
		/* Flag cache as stale */
		$this->likeCache->isNowStale( $relId );
		
		return true;
	}
	
	/**
	 * Updates a member's ID in the like rows
	 *
	 * @param	integer	Old Id
	 * @param	integer	New Id
	 */
	public function updateMemberId( $oldId, $newId )
	{
		$_likes	= array();
		
		/* We might be merging */
		$this->DB->build( array( 'select' => 'like_lookup_id', 'from' => 'core_like', 'where' => "like_member_id=" . $newId ) );
		$this->DB->execute();
			
		while( $r = $this->DB->fetch() )
		{
			$_likes[]	= $r['like_lookup_id'];
		}
		/* not done */
		if ( count( $_likes ) )
		{
			$this->DB->update( 'core_like'		 , array( 'like_member_id' => $newId ), "like_member_id=" . $oldId . " AND like_lookup_id NOT IN('" . implode( "','", array_map( 'addslashes', $_likes ) ) . "')" );
			$this->DB->delete( 'core_like_cache' , "like_cache_id IN('" . implode( "','", array_map( 'addslashes', $_likes ) ) . "')" );
		}
		else
		{
			$this->DB->update( 'core_like'		 , array( 'like_member_id' => $newId ), "like_member_id=" . $oldId );
		}
		
		/* Now remove duplicates */
		$dupes = array();
		
		$this->DB->build( array( 'select' => 'like_id, count(*) as cnt',
								 'from'   => 'core_like',
								 'where'  => 'like_member_id=' . $newId,
								 'group'  => 'like_app, like_area, like_member_id, like_rel_id',
								 'order'  => 'cnt desc' ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			if ( $row['cnt'] < 2 )
			{
				break;
			}
			else
			{
				$dupes[] = $row['like_id'];
			}
		}
		
		/* Do it? */
		if ( count( $dupes ) )
		{
			$this->DB->delete( 'core_like' , "like_cache IN('" . implode( "','", array_map( 'addslashes', $dupes ) ) . "')" );
		}
		
		/* Now re-update so that hashes are correct */
		$update = "like_id=MD5( CONCAT( like_app, ';', like_area, ';', like_rel_id, ';', like_member_id ) ), " .
				  "like_lookup_area=MD5( CONCAT( like_app, ';', like_area, ';', like_member_id ) )";
		
		$where  = "like_member_id=" . $newId;
		
		$this->DB->update( 'core_like', $update, $where, false, true );
	}
	
	/**
	 * Return number of people who 'like' the item
	 * 
	 * @param	integer		$relId		Relationship ID
	 * @return	@e integer
	 */
	public function getCount( $relId )
	{
		$cache = $this->likeCache->get( $relId );

		return $cache['count'];
	}
	
	/**
	 * Render a view
	 * 
	 * @param	string		$view		Type of View
	 * @param	integer		$relId		Relationship ID
	 * @param	array		$opts		Options
	 * @param	integer		$memberId	Member ID
	 * @return	@e string	Formatted HTML
	 * @todo	If $cache['count'] is 0, we can probably skip the getDataByMemberIdAndRelationshipId() call as it will be empty
	 */
	public function render( $view, $relId, $opts=array(), $memberId=null )
	{
		/* In the future we could abstract this out to its own class 
		 *  At this time we only have a few views so it would be overkill */
		if ( $view == 'summary' )
		{
			$data   = array( 'names' => array(), 'count' => 0, 'formatted' => '', 'iLike' => false, 'iLikeAnon' => false );
			$cache  = $this->likeCache->get( $relId, $memberId );
			$max	= 0;
			
			/* Fetch data for us? */
			if ( $this->memberData['member_id'] && ( $cache['count'] + $cache['count_anon'] ) > 0 )
			{
				/* We can't use isLiked or it runs twice the query and adding a cache to the function doesn't work either */
				$_me = $this->getDataByMemberIdAndRelationshipId( $relId, $this->memberData['member_id'] );
				
				if ( is_array($_me) && count($_me) && $_me['like_id'] )
				{
					/* Now mix up the data */
					$data['names'][] = array( 'name' => $this->lang->words['fave_moi'], 'seo' => $this->memberData['members_seo_name'], 'id' => $this->memberData['member_id'] );
					
					/* Flag as me */
					$data['iLike'] = true;
					
					/* Anonymous? Let the template know */
					if ( $_me['like_is_anon'] )
					{
						$data['iLikeAnon'] = true;
					}
				}
			}
			
			/* We had the total count set from cache.get - doesn't count anonymous members thought! */
			if ( classes_like_registry::getTotalCount() )
			{
				if ( is_array( $cache['members'] ) )
				{
					$i   = 1;
					$max = 3;
					
					foreach( $cache['members'] as $mid => $mdata )
					{
						$_last = ( $i == $cache['count'] || $i == $max ) ? 1 : 0;
						
						/* Is this you? */
						if ( $mid == $this->memberData['member_id'] )
						{
							continue;
						}
						
						/* Push it on */
						$data['names'][] = array( 'name' => $mdata['n'], 'seo' => $mdata['s'], 'id' => $mid, 'last' => $_last );
						
						$i++;
						
						if ( $i > $max )
						{
							/* Done thanks */
							break;
						}
					}
				}
			}
			
			/* Finish off */
			$data['totalCount']  = $cache['count'] + $cache['count_anon'];
			$data['anonCount']   = $cache['count_anon'];
			$data['othersCount'] = ( $cache['count'] > $max ) ? $cache['count'] - $max : 0;
			$data['app']		 = $this->_app;
			$data['area']		 = $this->_area;
			$data['formatted']   = $this->_formatNameString( $data );
			$data['vernacular']	 = $this->getVernacular();
			
			if ( IPS_IS_AJAX )
			{
				$_template	= $this->templatePrefix() . 'likeSummaryContents';
				
				/* Got an override template? */
				if ( method_exists( $this->registry->output->getTemplate( $this->skin() ), $_template ) )
				{
					return $this->registry->output->getTemplate( $this->skin() )->$_template( $data, $relId, $opts );
				}
				else
				{
					/* Fallback on default template */
					return $this->registry->output->getTemplate('global_other')->likeSummaryContents( $data, $relId, $opts );
				}
			}
			else
			{
				$_template	= $this->templatePrefix() . 'likeSummary';
								
				/* Got an override template? */
				if ( method_exists( $this->registry->output->getTemplate( $this->skin() ), $_template ) )
				{
					return $this->registry->output->getTemplate( $this->skin() )->$_template( $data, $relId, $opts );
				}
				else
				{
					/* Fallback on default template */
					return $this->registry->output->getTemplate('global_other')->likeSummary( $data, $relId, $opts );
				}
			}
		}
		else if ( $view == 'more' )
		{
			/* We need some counts here because we need them! */
			$cache				 = $this->likeCache->get( $relId, $memberId );
			$cache['totalCount'] = $cache['count'] + $cache['count_anon'];
			$cache['anonCount']	 = $cache['count_anon'];
			
			/* Fetch members who have wanted to like this item */
			$data		= $this->getDataByRelationshipId( $relId );
			$_template	= $this->templatePrefix() . 'likeMoreDialogue';
			
			/* Sort out some numbers if we're following anonymously */
			if ( $this->memberData['member_id'] && isset($data[ $this->memberData['member_id'] ]) && $data[ $this->memberData['member_id'] ]['like_is_anon'] )
			{
				$cache['count_anon']--;
				$cache['anonCount']--;
			}
			
			/* Got an override template? */
			if ( method_exists( $this->registry->output->getTemplate( $this->skin() ), $_template ) )
			{
				return $this->registry->output->getTemplate( $this->skin() )->$_template( $data, $relId, $cache );
			}
			else
			{
				/* Fallback on default template */
				return $this->registry->output->getTemplate('global_other')->likeMoreDialogue( $data, $relId, $cache );
			}
		}
	}

	/**
	 * Fetch form data for set dialogue
	 * 
	 * @param	integer		$relId		Relationship ID
	 * @return	@e array
	 */
	public function getDataForSetDialogue( $relid )
	{
		$return = array( 'frequencies' => $this->allowedFrequencies(),
						 'notifyType'  => $this->getNotifyType(),
						 'vernacular'  => $this->getVernacular()
						);
		
		return $return;
	}
	
	/**
	 * Has this user made this item a like already dudes?
	 * 
	 * @param	mixed		$relId		Relationship IDs (int|array)
	 * @param	integer		$memberId	Member ID
	 * @param	boolean		$checkNotVisibleToo	return a match even if not visible to the user
	 * @return	@e boolean
	 */
	public function isLiked( $relId, $memberId, $checkNotVisibleToo=false )
	{
		/* Grab the data */
		return ( $this->getDataByMemberIdAndRelationshipId( $relId, $memberId, $checkNotVisibleToo ) === null ) ? false : true;
	}
	
	/**
	 * Get data based on a relationship ID and a member ID
	 *
	 * @param	mixed		$relId		Relationship IDs (int|array)
	 * @param	integer		$memberId	Member ID
	 * @param	boolean		$checkNotVisibleToo	return a match even if not visible to the user
	 * @return	@e mixed	Array of likes data OR null
	 */
	public function getDataByMemberIdAndRelationshipId( $relId, $memberId, $checkNotVisibleToo=false )
	{
		$where	= '';
		$data	= null;
		
		if ( is_numeric( $relId ) )
		{
			$where = "like_id='" . classes_like_registry::getKey( $relId, $memberId ) . "'";
		}
		else if ( is_array( $relId ) )
		{
			$relId = IPSLib::cleanIntArray( $relId );
			$keys  = array();
			
			foreach( $relId as $id )
			{
				$keys[] = "'" . classes_like_registry::getKey( $id, $memberId ) . "'";
			}
			
			if ( ! count( $keys ) )
			{
				return null;
			}
			
			$where = 'like_id IN (' . implode( ',', $keys ) . ')';
			
		}
		
		if ( $checkNotVisibleToo === false )
		{
			$where .= ' AND like_visible=1';
		}
		
		$this->DB->build( array( 'select' => '*',
					   			 'from'   => 'core_like',
								 'where'  => $where ) );
		
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			$data[ $row['like_rel_id'] ] = $row;
		}
		
		/* Just the one? */
		if ( is_numeric($relId) && count($data) )
		{
			$data = array_shift($data);
		}
		
		return is_array($data) ? $data : null;
	}
	
	/**
	 * Get data based on a member ID
	 *
	 * @param	integer		$memberId		Member ID
	 * @param	integer		$limit			Max results
	 * @return	@e mixed 	Array of likes data OR null
	 */
	public function getDataByMemberIdAndArea( $memberId, $limit=500 )
	{
		$data = null;
		
		$this->DB->build( array( 'select' => '*',
					   			 'from'   => 'core_like',
								 'where'  => 'like_lookup_area=\'' . classes_like_registry::getKey( null, $memberId ) . '\' AND like_visible=1',
								 'order'  => 'like_added DESC',
								 'limit'  => array( 0, $limit ) ) );
		
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			$data[ $row['like_rel_id'] ] = $row;
		}	
		
		return ( is_array( $data ) ) ? $data : null;
	}
	
	/**
	 * Get data based on a relationship ID
	 *
	 * @param	integer		$relId		Relationship ID
	 * @param	boolean		$skipAnon	Skip anonymous rows flag
	 * @param	array
	 * @param	array		Type of follow (immediate, none, offline, daily, etc)
	 * @return	@e mixed	Array of likes data OR null
	 */
	public function getDataByRelationshipId( $relId, $skipAnon=true, $ignoreData=array(), $followType=array() )
	{
		/* Init */
		$mids	    = array();
		$members    = array();
		$rows       = array();
		$followType	= is_array( $followType ) ? $followType : array( $followType );
		$_isAnon    = $skipAnon ? 'AND like_is_anon=0' : '';
		$_whereB    = $this->memberData['member_id'] ? " OR like_id='" . classes_like_registry::getKey( $relId, $this->memberData['member_id'] ) . "'" : '';
		
		/* Add in follow type */
		if ( count( $followType ) )
		{
			$_whereB .= " AND like_notify_freq IN ('" . implode( "','", $followType ) ."')";
		}
		
		/* Fetch data */
		$this->DB->build( array( 'select' => '*',
					   			 'from'   => 'core_like',
								 'where'  => "((like_lookup_id='" . classes_like_registry::getKey( $relId ) . "' {$_isAnon}) {$_whereB}) AND like_visible=1",
								 'order'  => 'like_added DESC',
								 'limit'  => array( 0, 500 ) ) );
				
				
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			$data[ $row['like_member_id'] ] = $row;
			$mids[ $row['like_member_id'] ] = intval( $row['like_member_id'] );
		}
		
		/* Just the one? */
		if ( count( $mids ) )
		{
			$members = IPSMember::load( $mids, 'all' );
		
			foreach( $members as $i => $d )
			{
				$_m = IPSMember::buildProfilePhoto( $d );
				$data[ $i ] = array_merge( (array) $_m, (array) $data[ $i ] );
			}
		}
		
		/* Are we giving this bloke a good ignoring? */
		if ( !empty( $ignoreData ) )
		{
			$ignoreWhere = array();
			foreach ( $ignoreData as $type => $ids )
			{
				$ignoreWhere[] = "( {$type}=1 AND " . $this->DB->buildWherePermission( $ids, 'ignore_ignore_id', FALSE ) . ')';
			}
			
			$this->DB->build( array( 'select' => '*', 'from' => 'ignored_users', 'where' => implode( ' OR ', $ignoreWhere ) ) );
			$this->DB->execute();
			while ( $row = $this->DB->fetch() )
			{
				if ( isset( $data[ $row['ignore_owner_id'] ] ) )
				{
					unset( $data[ $row['ignore_owner_id'] ] );
				}
			}
			
		}
				
		return is_array($data) ? $data : null;
	}
	
	/**
	 * Get data based on an area and last sent greater than date [unix timestampe]
	 *
	 * @param	integer		$date			Unix timestamp
	 * @param	array		$types			Array of notification types (optional)
	 * @param	array		$parseMembers	Parse extra data for each member and build display photo (false is default)
	 * @param	integer		$sendMax	Null (use ipsRegistry::$setting or send INT only)
	 * @return	@e mixed	Array of likes data OR null
	 * @see		allowedFrequencies()	 
	 */
	public function getDataByAreaAndLastSentOlderThanDate( $date, $types=array(), $parseMembers=false, $sendMax=null )
	{
		/* Init */
		$mids	 = array();
		$members = array();
		$rows    = array();
		$joins   = array();
		$where   = ( is_array( $types ) ) ? ' AND l.like_notify_freq IN (\'' . implode( "','", $types ) . '\')' : '';
		$sendMax = ( $sendMax !== null )  ? $sendMax : ipsRegistry::$settings['like_notifications_limit'];
		
		/* figure out joins */
		$joins[] = array( 'select' => 'm.*',
						  'from'   => array( 'members' => 'm' ),
						  'where'  => 'm.member_id=l.like_member_id',
					      'type'   => 'left' );
		
		$moreJoins = $this->getDataJoins();
		
		if ( is_array( $moreJoins ) AND count( $moreJoins ) )
		{
			foreach( $moreJoins as $join )
			{
				$joins[] = $join;
			}
		}
		
		/* Prevent it going back EONS AND EONS */
		$oldestPossDate = 0;
		
		if ( in_array( 'weekly', $types ) )
		{
			/* Grab 8 days to account for timezone differences */
			$oldestPossDate = $date - ( 86400 * 8 );
		}
		else
		{
			/* Grab 2 days to account for timezone differences */
			$oldestPossDate = $date - ( 86400 * 2 );
		}
		
		if ( $oldestPossDate )
		{
			$where .= ' AND ( CASE WHEN l.like_notify_sent > 0 THEN l.like_notify_sent ELSE l.like_added END ) > ' . intval( $oldestPossDate );
		}
		
		/* Fetch data */	
		$this->DB->build( array( 'select'   => 'l.*',
					   			 'from'     => array( 'core_like' => 'l' ),
								 'where'    => 'l.like_notify_do=1 AND l.like_app=\'' . classes_like_registry::getApp() . '\' AND l.like_area=\'' . classes_like_registry::getArea() . '\' AND l.like_visible=1 AND ( CASE WHEN l.like_notify_sent > 0 THEN l.like_notify_sent ELSE l.like_added END ) < ' . intval( $date ) . $where,
								 'order'    => 'l.like_notify_sent ASC',
								 'limit'    => array( 0, $sendMax ),
								 'add_join' => $joins ) );
		
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			$row['like_member_id']			= intval($row['like_member_id']);
			$mids[ $row['like_member_id'] ]	= $row['like_member_id'];
			
			/* Need to apply secondary groups and grab g_perm_id if $parseMembers is false (default)
				@link http://community.invisionpower.com/tracker/issue-34691-digest-notifications-not-going-out/ */
			$row['mgroup_others']	= ($row['mgroup_others'] != '') ? IPSText::cleanPermString($row['mgroup_others']) : '';
			$row					= array_merge( $row, $this->caches['group_cache'][ $row['member_group_id'] ] );
			$row					= $this->registry->member()->setUpSecondaryGroups( $row );
			
			if( $row['topic_last_post'] )
			{
				$row['last_post']	= $row['topic_last_post'];
			}
			
			/* @link http://community.invisionpower.com/tracker/issue-32204-dailyweekly-notifications */
			$data[ $row['like_member_id'] ][ $row['like_id'] ]	= $row;
		}
		
		/* Just the one? */
		if ( $parseMembers && count($mids) )
		{
			$members = IPSMember::load( $mids, 'all' );
		
			foreach( $members as $i => $d )
			{
				$_m = IPSMember::buildProfilePhoto( $d );
				
				foreach( $data[ $i ] as $likeId => $likeData )
				{
					$data[ $i ][ $likeId ] = array_merge( (array) $_m, (array) $data[ $i ][ $likeId ] );
				}
			}
		}
		
		return is_array($data) ? $data : null;
	}
	
	/**
	 * Clean the notification options to make sure they're all nice and fluffy
	 * 
	 * @param	array		$notifyOpts		Notification options
	 * @return	@e array	Cleaned notification options array
	 */
	protected function _cleanNotifyOptions( array $notifyOpts )
	{
		if ( isset( $notifyOpts['like_notify_do'] ) )
		{
			$notifyOpts['like_notify_do'] = intval( $notifyOpts['like_notify_do'] );
		}
		else
		{
			$notifyOpts['like_notify_do'] = 0;
		}
		
		if ( ! isset( $notifyOpts['like_notify_meta'] ) )
		{
			$notifyOpts['like_notify_meta'] = '';
		}
		
		if ( isset( $notifyOpts['like_notify_freq'] ) )
		{
			$notifyOpts['like_notify_freq'] = ( in_array( $notifyOpts['like_notify_freq'], $this->allowedFrequencies() ) ) ? $notifyOpts['like_notify_freq'] : '';
		}
		else
		{
			$notifyOpts['like_notify_freq'] = 0;
		}
		
		return $notifyOpts;
	}
	
    /**
	 * Return an array of acceptable frequencies
	 * Possible: immediate, offline, daily, weekly
	 * 
	 * @return	@e array
	 */
	public function allowedFrequencies()
	{
		return array( 'immediate', 'offline', 'daily', 'weekly' );
	}
	
	/**
	 * Return types of notification available for this item
	 * 
	 * @return	@e array	array( key, human readable )
	 */
	public function getNotifyType()
	{
		return array( 'comments', ipsRegistry::getClass('class_localization')->words['gbl_comments_like'] );
	}
	
	/**
	 * Fetch the template group
	 * 
	 * @return	@e string
	 */
	public function skin()
	{
		return 'global_other';
	}

	/**
	 * Fetch the template prefix.  This allows you to have two follow implementations in
	 * one skin file (i.e. skin_calendars -> eventLikeMoreDialog() and skin_calendars -> calendarLikeMoreDialog())
	 * 
	 * @return	@e string
	 */
	public function templatePrefix()
	{
		return '';
	}
	
	/**
	 * Gets the vernacular (like or follow)
	 *
	 * @return	@e string
	 */
	public function getVernacular()
	{
		return 'like';
	}
	
	/**
	 * Builds the notification data via the app class
	 * 
	 * @param	array		$data		like_ DB data and like owner member data
	 * @param	string		$type		Types of notifications to send
	 * @return	@e array	array( notification_key, notification_url, email_template, email_subject, build_message_array )
	 * @see		allowedFrequencies()
	 */
	public function buildNotificationData( $data, $type )
	{
		return array();
	}
	
	/**
	 * Check notifications that are to be sent to make sure they're valid and that
	 * 
	 * @param	array		$metaData		like_ DB data and like owner member data
	 * @return	@e boolean
	 */
	public function notificationCanSend( $metaData )
	{
		return true;
	}
	
	/**
	 * Fetches joins for fetching data
	 * 
	 * @param	string		$field		DB field name (defaults to 'l.like_rel_id')
	 * @return	@e array
	 */
	public function getDataJoins( $field='l.like_rel_id' )
	{
		return array();
	}
	
	/**
	 * Return the title based on the passed id
	 * 
	 * @param	mixed		$relId		Relationship ID or array of IDs
	 * @return	@e mixed	Title or array of titles
	 */
	public function getTitleFromId( $relId )
	{
		$meta = $this->getMeta( $relId, array( 'title' ) );
		
		if ( is_numeric( $relId ) )
		{
			return $meta[ $relId ]['like.title'];
		}
		else
		{
			$return = array();
			
			foreach( $meta as $id => $data )
			{
				$return[ $id ] = $data['like.title'];
			}
			
			return $return;
		}
	}

	/**
	 * Return the URL based on the passed id
	 * 
	 * @param	mixed		$relId		Relationship ID or array of IDs
	 * @return	@e mixed	URL or array of URLs
	 */
	public function getUrlFromId( $relId )
	{
		$meta = $this->getMeta( $relId, array( 'url' ) );
		
		if ( is_numeric( $relId ) )
		{
			return $meta[ $relId ]['like.url'];
		}
		else
		{
			$return = array();
			
			foreach( $meta as $id => $data )
			{
				$return[ $id ] = $data['like.url'];
			}
			
			return $return;
		}
	}
	
	/**
	 * Formats the Bob, Bill, Joe and 2038 Others Hate You
	 * 
	 * @param	array		$data		Likes data
	 * @return	@e string	Formatted names
	 */
	protected function _formatNameString( array $data )
	{
		$langString  = '';
		$seeMoreLink = 'app=core&amp;module=global&amp;section=like&amp;do=more';
		
		if ( ! is_array( $data['names'] ) OR ! count( $data['names'] ) )
		{
			return false;
		}
		/* Format up the names */
		$i      = 0;
		$_names = array();
		
		foreach( $data['names'] as $name )
		{
			if ( $this->memberData['member_id'] AND $this->memberData['member_id'] == $name['id'] )
			{
				$_names[$i] = $name['name'];
			}
			else
			{
				$_names[$i] = IPSMember::makeProfileLink($name['name'], $name['id'], $name['seo'] );
			}
			
			$i++;
		}

		/* More than one? */
		if ( $data['totalCount'] > 1 )
		{
			/* Joe and Matt love you */
			if ( $data['totalCount'] == 2 )
			{
				$_n = $_names[0] . ' ' . $this->lang->words['fave_and'] . ' ' . $_names[1];
				
				$langString = sprintf( $this->lang->words['fave_formatted_many'], $_n );
			}
			/* Joe, Matt and Mike love you more */
			else if ( $data['totalCount'] == 3 )
			{
				$_n = $_names[0] . ', ' . $_names[1] . ' ' . $this->lang->words['fave_and'] . ' ' . $_names[2];
				
				$langString = sprintf( $this->lang->words['fave_formatted_many'], $_n );
			}
			/* Joe, Matt, Mike and 1 more love you */
			else if ( $data['totalCount'] == 4 )
			{
				$_n = $_names[0] . ', ' . $_names[1] . ' ' . $this->lang->words['fave_and'] . ' ' . $_names[2];
				
				$langString = sprintf( $this->lang->words['fave_formatted_one_more'], $_n, $seeMoreLink );
			}
			/* Joe, Matt, Mike and 5 more are indifferent to your redonkulous comments */
			else
			{
				$_n = $_names[0] . ', ' . $_names[1] . ', ' . $_names[2];
				
				$langString = sprintf( $this->lang->words['fave_formatted_more'], $_n, $seeMoreLink, $data['othersCount'] );
			}
		}
		else
		{
			/* Just the one and it might be you! */	
			if ( $data['names'][0]['id'] == $this->memberData['member_id'] )
			{
				$langString = $this->lang->words['fave_formatted_me'];
			}
			else
			{
				$langString = sprintf( $this->lang->words['fave_formatted_one'], $_names[0] );
			}
		}

		return $langString;
	}
}

/**
 *
 * @class		classes_like_cache
 * @brief		Favorites cache class
 * @author		Matt
 */
class classes_like_cache
{
	/**
	 * Registry Object Shortcuts
	 *
	 * @var		$registry
	 * @var		$DB
	 * @var		$settings
	 * @var		$request
	 * @var		$lang
	 * @var		$member
	 * @var		$memberData
	 * @var		$cache
	 * @var		$caches
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
	 * Cache expiration time in seconds
	 *
	 * @var		$_expire
	 */
	protected $_expire = 0;
	
	/**
	 * Application key
	 *
	 * @var		$_app
	 */
	protected $_app;
	
	/**
	 * Area key
	 *
	 * @var		$_area
	 */
	protected $_area;
	
	/**
	 * Instance of an object
	 *
	 * @var		$instance
	 */
	private static $instance = null;
	
	/**
	 * Singleton
	 *
	 * @return	@e object
	 */
	public static function getInstance()
	{
		if ( self::$instance === null )
		{
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	/**
	 * Constructor
	 *
	 * @return	@e void
	 */
	public function __construct()
	{
		/* Fetch registry like a good boy */
		$this->registry   =  ipsRegistry::instance();
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		
		$this->_app  = classes_like_registry::getApp();
		$this->_area = classes_like_registry::getArea();
		
		if ( empty( $this->_app ) OR empty( $this->_area ) )
		{
			trigger_error( "Missing area or app variable in " . __CLASS__ . '::' . __FUNCTION__, E_USER_WARNING );
		}
	}
	
	/**
	 * Set the cache expiration
	 *
	 * @param	integer		$seconds		Time in seconds
	 * @return	@e void
	 */
	public function setExpiration( $seconds )
	{
		$this->_expire = intval( $seconds );
	}
	
	/**
	 * Fetch an item from the cache
	 * 
	 * @param	integer		$relId		Relationship ID
	 * @param	integer		$memberId 	Member ID
	 * @return	@e array
	 */
	public function get( $relId, $memberId=null )
	{
		$cache = array();
		
		/* Possible future expansion */
		if ( $memberId === null )
		{
			$cache = $this->DB->buildAndFetch( array( 'select' => '*',
													  'from'   => 'core_like_cache',
													  'where'  => 'like_cache_id=\'' .  classes_like_registry::getKey( $relId ) . '\'' ) );
			
			if ( !is_array($cache) OR $cache['like_cache_expire'] <= IPS_UNIX_TIME_NOW )
			{ 
				/* We don't have a valid cache, so create one and return the data */
				$cache = $this->create( $relId, $memberId );
			}
			
			$tmp = unserialize($cache['like_cache_data']);
			$cache['members']	= $tmp['members'];
			$cache['count']		= intval($tmp['count']);
			$cache['count_anon']= intval($tmp['count_anon']);
			
			/* Set total count for use elsewhere */
			classes_like_registry::setTotalCount( $cache['count'] );
		}
		
		return $cache;
	}
	
	/**
	 * Creates/updates the relationship ID's cache
	 * 
	 * @param	integer		$relId		Relationship ID
	 * @param	integer		$memberId 	Member ID
	 * @return	@e array	Array of stored data
	 */
	public function create( $relId, $memberId=null )
	{
		/* Init vars */
		$store   = array();
		$items   = array();
		$members = array();
		$mids	 = array();
		$data    = array( 'members' => null, 'count' => 0 );
		
		/* Possible future expansion */
		if ( $memberId === null )
		{
			/* Count all public first */
			$cou = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as nt',
									 				'from'   => 'core_like',
									 				'where'  => 'like_lookup_id=\'' . classes_like_registry::getKey( $relId ) . '\' AND like_is_anon=0 AND like_visible=1' ) );
			
			$data['count'] = intval( $cou['nt'] );
			
			/* Count all anonymous second */
			$ano = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as nt',
									 				'from'   => 'core_like',
									 				'where'  => 'like_lookup_id=\'' . classes_like_registry::getKey( $relId ) . '\' AND like_is_anon!=0 AND like_visible=1' ) );
			
			$data['count_anon'] = intval( $ano['nt'] );
			
			/* Fetch all items for this app:key:$relId */
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'core_like',
									 'where'  => 'like_lookup_id=\'' . classes_like_registry::getKey( $relId ) . '\' AND like_is_anon=0 AND like_visible=1',
									 'order'  => 'like_added DESC',
									 'limit'  => array( 0, 5 )  ) );
			
			$o = $this->DB->execute();
			
			while( $row = $this->DB->fetch($o) )
			{
				$items[ $row['like_rel_id'] ]   = $row;
				$mids[ $row['like_member_id'] ] = $row['like_member_id'];
			}
			
			/* Load members */
			$members = IPSMember::load( $mids, 'core' );
			
			/* We don't need all the member's infos */
			foreach( $members as $mid => $mdata )
			{
				$data['members'][ $mid ] = array( 'n' => $mdata['members_display_name'], 's' => $mdata['members_seo_name'] );
			}
		
			/* Build save array */
			$store = array( 'like_cache_id'     => classes_like_registry::getKey( $relId ),
						    'like_cache_app'    => $this->_app,
						    'like_cache_area'   => $this->_area,
						    'like_cache_rel_id' => $relId,
						    'like_cache_data'   => serialize( $data ),
						    'like_cache_expire' => IPS_UNIX_TIME_NOW + $this->_expire );
			
			/* Update the cache */
			$this->DB->replace( 'core_like_cache', $store, array( 'like_cache_id' ) );
		}
		else
		{
			/* We know there's a previous cache row, so delete it */
			$this->DB->delete( 'core_like_cache', 'like_cache_id=\'' . classes_like_registry::getKey( $relId ) . '\'' );
		}
		
		return $store;
	}
	
	/**
	 * Deletes a cache
	 * 
	 * @param	mixed		$relId		Relationship ID or array of IDs
	 * @param	integer		$memberId 	Member ID
	 * @return	@e void
	 */
	public function delete( $relId, $memberId=null )
	{
		/* Possible future expansion */
		if ( $memberId === null )
		{
			$where = '';
			
			if ( is_numeric($relId) )
			{
				$where = "='" . classes_like_registry::getKey( $relId ) . "'";
			}
			elseif ( is_array($relId) )
			{
				$relId = IPSLib::cleanIntArray($relId);
				$keys  = array();
				
				foreach( $relId as $id )
				{
					$keys[] = "'" . classes_like_registry::getKey( $id ) . "'";
				}
				
				if ( ! count($keys) )
				{
					return null;
				}
				
				$where = " IN (" . implode( ",", $keys ) . ")";
			}
			
			$this->DB->delete( 'core_like_cache', 'like_cache_id ' . $where );
		}
	}
	
	/**
	 * Flags a cache as stale. We choose to delete, but the cache class should make the call
	 * not the application
	 * 
	 * @param	mixed		$relId		Relationship ID or array of IDs
	 * @param	integer		$memberId 	Member ID
	 * @return	@e void
	 */
	public function isNowStale( $relId, $memberId = null )
	{
		return $this->delete( $relId, $memberId );
	}
}