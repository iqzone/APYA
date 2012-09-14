<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Item Marking
 * Owner: Matt "Oh Lord, why did I get assigned this?" Mecham
 * Last Updated: $Date: 2012-05-31 08:17:13 -0400 (Thu, 31 May 2012) $
 * </pre>
 *
 * @author 		$Author: mmecham $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		9th March 2005 11:03
 * @version		$Revision: 10829 $
 *
 * @todo 	[Future] Perhaps find more ways to reduce the size of items_read
 * @todo 	[Future] Maybe add an IN_DEV tool to warn when markers have been cancelled out / size drastically reduced
 */

class classItemMarking
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
	 * Dead session data
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_deadSessionData	= array();
	
	/**
	 * Module apps
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_moduleApps		= array();
	
	/**
	 * Cache for internal use
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_cache				= array();
	
	/**
	 * Cookie Data
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_cookie			= array();
	
	/**
	 * Item Markers Data
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_itemMarkers		= array();
	
	/**
	 * Last cleared time for key
	 *
	 * @access	protected
	 * @var		int
	 */
	protected $_keyLastCleared	= 0;
	
	/**
	 * DB cut off time stamp
	 *
	 * @access	protected
	 * @var		int
	 */
	protected $_dbCutOff			= 0;
	
	/**
	 * Cookie counter
	 *
	 * @access	protected
	 * @var		int
	 */
	protected $_cookieCounter		= 0;
	
	/**
	 * Instant save on/off
	 * 
	 * @access	protected
	 * @var		bool
	 */
	protected $_instantSave     = true;

	
	/**
	 * Enable cookies?
	 *
	 */
	protected $_useCookies			= true;
	
	/**
	 * Can compress cookies
	 */
	protected $_canCompressCookies = false;
	
	/**
	 * Disable all marking for guests
	 */
	protected $_noGuestMarking     = false;
	
	protected $_setSaveCookie      = false;
	
	protected $_dbFields           = array( 'item_key', 'item_member_id', 'item_app', 'item_last_update', 'item_last_saved', 'item_unread_count', 'item_read_array', 'item_global_reset',
											'item_app_key_1', 'item_app_key_2', 'item_app_key_3', 'item_is_deleted' );
	/**
	 * Method constructor
	 *
	 * @access	public
	 * @param	object		Registry Object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		$_NOW = IPSDebug::getMemoryDebugFlag();
	
		/* Make object */
		$this->registry   =  $registry;
		$this->DB	      =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	  =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
			
 		/* Task? */
		if ( IPS_IS_TASK === TRUE )
		{
			return;
		}
		
		/* Search engine? */
		if ( $this->member->is_not_human === TRUE )
		{
			return;
		}
		
		/* Track markers for guests? */
		$this->_noGuestMarking = ( $this->settings['topic_marking_guests'] ) ? 0 : 1;
		
		/* Use cookie marking for guests */
		if ( ! $this->memberData['member_id'] )
		{
			$this->settings['topic_marking_enable'] = 0;
			
			if ( $this->_noGuestMarking )
			{
				return;
			}
		}
		
		/* Switch off cookies for members */
		if ( $this->memberData['member_id'] )
		{
			/* If we've disabled DB marking, best use cookies anyway */
			$this->_useCookies = ( $this->settings['topic_marking_enable'] ) ? false : true;
		}
		
		/* Build */
		$this->memberData['item_markers'] = $this->_generateIncomingItemMarkers();

		/* Set Up */
		if ( is_array( $this->memberData['item_markers'] ) )
		{
			foreach( $this->memberData['item_markers'] as $app => $key )
			{
				foreach( $this->memberData['item_markers'][ $app ] as $key => $data )
				{
					if ( $app AND $key )
					{
						if ( $data['item_last_saved'] )
						{
							$data['item_last_saved'] = intval( $data['item_last_saved'] );
						}
						
						$this->_itemMarkers[ $app ][ $key ] = $data;
					}
				}
			}
		}

		/* Fetch cookies */
		if ( $this->_useCookies )
		{
			/* Test for cookie compression */
			if ( function_exists('gzdeflate') AND function_exists('gzinflate') )
			{
				$this->_canCompressCookies = true;
			}
		
			foreach( IPSLib::getEnabledApplications() as $_app => $data )
			{
				$_value  = $this->_inflateCookie( IPSCookie::get( 'itemMarking_' . $_app ) );
				$_value2 = $this->_inflateCookie( IPSCookie::get( 'itemMarking_' . $_app . '_items' ) );
				
				$this->_cookie[ 'itemMarking_' . $_app ]           = ( is_array( $_value ) )  ? $_value  : array();
				$this->_cookie[ 'itemMarking_' . $_app . '_items'] = ( is_array( $_value2 ) ) ? $_value2 : array();
				
				/* Clean up  */
				if ( $this->_cookie[ 'itemMarking_' . $_app . '_items'] )
				{
					$_items = ( is_array( $this->_cookie[ 'itemMarking_' . $_app . '_items'] ) ) ? $this->_cookie[ 'itemMarking_' . $_app . '_items'] : unserialize( $this->_cookie[ 'itemMarking_' . $_app . '_items'] );
				
					$this->_cookieCounter = 0;
					arsort( $_items, SORT_NUMERIC );
			
					$_newData = array_filter( $_items, array( $this, 'arrayFilterCookieItems' ) );
					
					$this->_cookie[ 'itemMarking_' . $_app . '_items'] = $_newData;
					
					IPSDebug::addMessage( 'Cookie loaded: itemMarking_' . $_app . '_items' . ' - ' . serialize( $_newData ) );
					IPSDebug::addMessage( 'Cookie loaded: itemMarking_' . $_app . ' - ' . serialize( $_value ) );
				}
			}
		}
		
		IPSDebug::setMemoryDebugFlag( "Topic markers initialized", $_NOW );
	}
	
	/**
	* Compressed the cookie
	*
	* @access	protected
	* @param	mixed		Data (array or string)
	* @return	string		compressed data
	*/
	protected function _compressCookie( $data )
	{
		if ( $this->_canCompressCookies )
		{
			if ( is_array( $data ) )
			{
				$data = serialize( $data );
			}
			
			$data = strtr( base64_encode( addslashes( @gzcompress( $data ) ) ), '+/=', '-_,' );
		}
		
		return $data;
	}
	
	/**
	* Inflate the cookie
	*
	* @access	protected
	* @param	mixed		Compressed string
	* @return	string		Uncompressed data
	*/
	protected function _inflateCookie( $data )
	{
		/* already inflated? */
		if ( ! $data OR is_array( $data ) )
		{
			return $data;
		}
		
		if ( $this->_canCompressCookies )
		{
			$data = @gzuncompress( stripslashes( base64_decode( strtr( $data, '-_,', '+/=' ) ) ) );
			
			if ( substr( $data, 0, 2 ) == 'a:' )
			{
				$data = unserialize( $data );
			}
		}
		
		return $data;
	}
		
	/**
	 * Disable instant save
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function disableInstantSave()
	{
		$this->_instantSave = false;
	}
	
	/**
	 * Enable instant save (Default mode anyway)
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function enableInstantSave()
	{
		$this->_instantSave = true;
	}
	
	/**
	 * Fetch status
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function fetchInstantSaveStatus()
	{
		return ( $this->_instantSave === true ) ? true : false;
	}
	
	/**
	 * Add to cookie
	 *
	 * @access	protected
	 * @param	string 		Key
	 * @param	array
	 * @return	@e void
	 */
	protected function _updateCookieData( $key, $array )
	{
		$this->_cookie[ $key ] = $array;
	}
	
	/**
	 * Fetch cookie data
	 *
	 * @access	protected
	 * @param	key
	 * @return	array
	 */
	protected function _fetchCookieData( $key )
	{
		if ( ! $this->_useCookies )
		{
			return array();
		}
		
		if ( is_array( $this->_cookie[ $key ] ) )
		{
			return $this->_cookie[ $key ];
		}
		else
		{
			return array();
		}
	}
	
	/**
	 * Public method to get cookie
	 *
	 * @access	public
	 * @param	string		APP
	 * @param	string		Type of key (items, read)
	 * @return	mixed
	 */
	public function fetchCookieData( $app, $type )
	{
		if ( $app )
		{
			$key = ( $type != 'items' ) ? 'itemMarking_' . $app : 'itemMarking_' . $app . '_items';
			
			return $this->_fetchCookieData( $key );
		}
	}
	
	/**
	 * Set the flag to save cookies in mydestruct
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _setSaveCookie()
	{
		$this->_setSaveCookie = true;
	}
	
	/**
	 * Save cookie
	 *
	 * @access	protected
	 * @param	string		Key name (leave blank to save out all cookies)
	 * @return	@e void
	 */
	protected function _saveCookie( $key='' )
	{
		if ( ! $this->_useCookies )
		{
			return;
		}

		if ( $key AND is_array( $this->_cookie[ $key ] ) )
		{
			IPSCookie::set( $key, $this->_cookie[ $key ], 1 );
		}
		else
		{
			foreach( $this->_cookie as $k => $v )
			{ 
				if ( is_array( $v ) AND ! count( $v ) )
				{
					/* Do we have a cookie? */
					$test = IPSCookie::get( $k );
					
					if ( $test )
					{ 
						/* set a blank, non sticky cookie */
						IPSCookie::set( $k, '-', 0, -1 );
					}
					else
					{
						continue;
					}
				}
				else
				{
					IPSDebug::addMessage( 'Cookie SAVED: ' . $k . ' - ' .$this->_compressCookie( $v ) );
					IPSCookie::set( $k, $this->_compressCookie( $v ), 1 );
				}
			}
		}
	}
	
	/**
	 * Fetch marking data via a join
	 * 
	 * <code>$join = $itemMarking->getSqlJoin( array( 'item_app_key_1' => 'prefix.field' ) );</code>
	 * 
	 * @param array $data
	 * @param string $app
	 */
	public function getSqlJoin( $data, $app='' )
	{
		if ( ! $this->memberData['member_id'] OR ! $this->settings['topic_marking_enable'] )
		{
			return false;
		}
		
		$app = ( $app ) ? $app : IPS_APP_COMPONENT;
		
		/* Set up where */
		$where[] = 'itemmarking.item_member_id=' . intval( $this->memberData['member_id'] );
		$where[] = 'itemmarking.item_app=\'' . $app . "'";
		
		foreach( $data as $key => $value )
		{
			if ( strstr( $key, 'item_app_key_' ) )
			{
				$where[] = 'itemmarking.' . $key . '=' . $value;
			} 
		}
		
		/* Still here? */
		return array( 'select' => 'itemmarking.*',
					  'from'   => array( 'core_item_markers' => 'itemmarking' ),
					  'where'  => implode( ' AND ', $where ) );
	}
	
	/**
	 * Sets internal data based on data fetched from a join
	 * @param array $data
	 */
	public function setFromSqlJoin( $data, $app='' )
	{
		$app     = ( $app ) ? $app : IPS_APP_COMPONENT;
		$data    = $this->_fetchModule( $app )->convertData( $data );
		$_key    = array();
		$_data   = array();
		
		/* Check key */
		if ( empty( $data['item_key'] ) )
		{
			return $data;
		}
		
		foreach( $this->_dbFields as $field )
		{
			if ( isset( $data[ $field ] ) )
			{
				$_data[ $field ] = $data[ $field ];
				
				unset( $data[ $field ] );
			}
		}
		
		if ( count( $_data ) && empty( $_data['item_is_deleted'] ) )
		{
			if ( IPSLib::isSerialized( $_data['item_read_array'] ) )
			{
				$_data['item_read_array'] = unserialize( $_data['item_read_array'] );
			}
			
			$this->_itemMarkers[ $app ][ $_data['item_key'] ] = $_data;
		}
		
		/* Recommend you always use the returned array as it removes the marking data to reduce memory footprint */
		return $data;
	}
	
	/**
	 * Check the read status of an item
	 *
	 * Forum example (forumID is mapped to :item_app_key_1). itemID is the topic ID
	 *
	 * <code>$read = $itemMarking->isRead( array( 'forumID' => 2, 'itemID' => 99, 'itemLastUpdate' => 1989098989 ) );</code>
	 *
	 * @access	public
	 * @param	array 		Array of data
	 * @param	string		Optional app
	 * @return	boolean     TRUE is read / FALSE is unread
	 */
	public function isRead( $data, $app='' )
	{
		$app         = ( $app ) ? $app : IPS_APP_COMPONENT;
		$data        = $this->_fetchModule( $app )->convertData( $data );
		$_data       = $data;
		$times       = array();
		$cookie      = $this->_fetchCookieData( 'itemMarking_' . $app );
		$cookieItems = $this->_fetchCookieData( 'itemMarking_' . $app . '_items');
		
		if ( ! $this->memberData['member_id'] AND $this->_noGuestMarking )
		{
			return 1;
		}
		
		/* Check check for how long we're tracking for */
		if ( $data['itemLastUpdate'] && $this->settings['topic_marking_keep_days'] )
		{
			if ( ( IPS_UNIX_TIME_NOW - ( $this->settings['topic_marking_keep_days'] * 86400 ) ) > $data['itemLastUpdate'] )
			{
				return 1;
			}
		}
		
		unset( $_data['itemLastUpdate'] );
	
		$times[] = $this->fetchTimeLastMarked( $_data, $app );
		
		if ( $data['itemID'] )
		{
			if ( isset( $cookieItems[ $data['itemID'] ] ) )
			{
				$times[] = intval( $cookieItems[ $data['itemID'] ] );
			}
			
			/* Update the main row? */
			if ( $this->settings['topic_marking_enable'] AND isset( $cookieItems[ $data['itemID'] ] ) AND $cookieItems[ $data['itemID'] ] > $times[1] )
			{
				$mainKey = $this->_findMainRowByKey( $data, $app );
				
				if ( $mainKey )
				{
					$this->_itemMarkers[ $app ][ $mainKey ]['item_read_array'][ $data['itemID'] ] = $cookieItems[ $data['itemID'] ];
				}
			}
		}
	
		return ( IPSLib::fetchHighestNumber( $times ) >= $data['itemLastUpdate'] ) ? TRUE : FALSE; 
	}
	
	/**
	 * Mark as read
	 *
	 * Forum example (forumID is mapped to :item_app_key_1). itemID is the topic ID
	 *
	 * <code>$itemMarking->markAsRead( array( 'forumID' => 34, 'itemID' => 99 ) );</code>
	 * <code>$itemMarking->markAsRead( array( 'forumID' => 34 ) );</code>
	 * If you pass in a timestamp, it will set that as the read date so a topic, for example
	 * won't appear read until you're on the last page
	 * <code>$itemMarking->markAsRead( array( 'forumID' => 34, 'itemID' => 99, 'markDate' => 123456790 ) );</code>
	 * 
	 * If you pass a "containerLastActivityDate" timestamp it will compare the markDate against it and not run the fetchUnreadCount query
	 * <code>$itemMarking->markAsRead( array( 'forumID' => 34, 'itemID' => 99, 'markDate' => 123456790, 'containerLastActivityDate' => 1234567890 ) );</code>
	 * @access	public
	 * @param	array
	 * @param 	string	[App]
	 * @return	@e void
	 */
	public function markRead( $data, $app='' )
	{
		$app         			   = ( $app ) ? $app : IPS_APP_COMPONENT;
		$origData   			   = $data;
		$data        			   = $this->_fetchModule( $app )->convertData( $data );
		$cookie      			   = $this->_fetchCookieData( 'itemMarking_' . $app );
		$cookieItems 			   = $this->_fetchCookieData( 'itemMarking_' . $app . '_items');
		$mainKey     			   = $this->_findMainRowByKey( $data, $app );
		$markDate    			   = ( ! empty( $data['markDate'] ) ) ? intval( $data['markDate'] ) : time();
		$containerLastActivityDate = ( ! empty( $data['containerLastActivityDate'] ) ) ? intval( $data['containerLastActivityDate'] ) : false;
		
		/* Search engine? */
		if ( $this->member->is_not_human === TRUE )
		{
			return;
		}
		
		if ( ! $this->memberData['member_id'] AND $this->_noGuestMarking )
		{
			return;
		}
		
		if ( $data['itemID'] )
		{
			/* Cookie */
			$cookieItems[ $data['itemID'] ] = $markDate;
			$this->_updateCookieData( 'itemMarking_' . $app . '_items', $cookieItems );
			
			$_cookieGreset = isset( $cookie['greset'][ $this->_makeKey( $data, TRUE ) ] ) ? intval( $cookie['greset'][ $this->_makeKey( $data, TRUE ) ] ) : 0;
			$_readItems    = $cookieItems; 
			
			/* Do we need to clean up? */
			if ( $this->settings['topic_marking_enable'] )
			{
				/* DB */
				$this->_itemMarkers[ $app ][ $mainKey ]['item_read_array'][ $data['itemID'] ] = $markDate;
				
				/* Check the cookie again.. */
				if ( $_cookieGreset AND $_cookieGreset > $this->_itemMarkers[ $app ][ $mainKey ]['item_global_reset'] )
				{
					$this->_itemMarkers[ $app ][ $mainKey ]['item_global_reset'] = $_cookieGreset;
				}
				
				/* Overwrite read items */
				if ( is_array( $this->_itemMarkers[ $app ][ $mainKey ]['item_read_array'] ) )
				{
					foreach( $this->_itemMarkers[ $app ][ $mainKey ]['item_read_array'] as $i => $d )
					{
						$_readItems[ $i ] = $d;
					}
				}
			}
			
			/* Check last reset time */
			$_lastReset = IPSLib::fetchHighestNumber( array( $_cookieGreset, isset( $cookie['global']) ? $cookie['global'] : 0, intval( $this->_itemMarkers[ $app ][ $mainKey ]['item_global_reset'] ), intval( $this->memberData['_cache']['gb_mark__'. $app ] ) ) );
			$_doQuery   = ( $this->settings['topic_marking_enable'] ) ? true : false;
			
			/* Fetch unread items */
			if ( $_doQuery === true )
			{
				$_oldest	  = IPS_UNIX_TIME_NOW - ( $this->settings['topic_marking_keep_days'] * 86400 );
				$_lastReset   = ( $_lastReset >= $_oldest ) ? $_lastReset : $_oldest;
				
				$_unreadCount = $this->_fetchModule( $app )->fetchUnreadCount( $origData, $_readItems, $_lastReset );
			
				if ( $_unreadCount['count'] > 0 )
				{
					if ( $this->settings['topic_marking_enable'] )
					{
						$this->_itemMarkers[ $app ][ $mainKey ]['item_unread_count'] = $_unreadCount['count'];
					}
				}
				else
				{
					if ( $this->settings['topic_marking_enable'] )
					{
						/* Update the last global reset time and clear the read array */
						$this->_itemMarkers[ $app ][ $mainKey ]['item_read_array']   = array();
						$this->_itemMarkers[ $app ][ $mainKey ]['item_global_reset'] = time();
						$this->_itemMarkers[ $app ][ $mainKey ]['item_unread_count'] = 0;
					}
					
					/* Cookie */
					$cookie['greset'][ $this->_makeKey( $data, TRUE ) ] = time();
					$this->_updateCookieData( 'itemMarking_' . $app, $cookie );
					$this->_updateCookieData( 'itemMarking_' . $app . '_items', array() );
				}
			}
		}
		else
		{
			if ( $this->settings['topic_marking_enable'] )
			{
				/* Update the last global reset time and clear the read array */
				$this->_itemMarkers[ $app ][ $mainKey ]['item_read_array']   = array();
				$this->_itemMarkers[ $app ][ $mainKey ]['item_global_reset'] = time();
				$this->_itemMarkers[ $app ][ $mainKey ]['item_unread_count'] = 0;
			}
			
			/* Cookie */
			$cookie['greset'][ $this->_makeKey( $data, TRUE ) ] = time();
			$this->_updateCookieData( 'itemMarking_' . $app, $cookie );
			$this->_updateCookieData( 'itemMarking_' . $app . '_items', array() );
		}
		
		/* Add in global update */
		$this->_itemMarkers[ $app ][ $mainKey ]['item_last_update'] = time();
		
		/* Save cookie */
		$this->_setSaveCookie();
		
		/* Save DB */
		$this->_save( $app, $mainKey );
	}
	
	/**
	 * Marks everything within an app as read
	 *
	 * @access	public
	 * @param	string	[App Optional. If ommited, all apps are marked as read]
	 * @return	@e void
	 */
	public function markAppAsRead( $app='' )
	{
		/* Search engine? */
		if ( $this->member->is_not_human === TRUE )
		{
			return;
		}
		
		/* Cookie */
		$cookie['global'] = time();
		$cookie['greset'] = array();
		
		/* One app or all? */
		if ( $app )
		{
			/* Reset member cache */
			IPSMember::packMemberCache( $this->memberData['member_id'], array('gb_mark__' . $app => time() ) );
			
			/* Mark rows for deletion */
			$this->DB->update( 'core_item_markers', array( 'item_is_deleted' => 1 ), "item_app='" . $app . "' AND item_member_id=" . intval( $this->memberData['member_id'] ) );
					
			/* Update cookies */
			$this->_updateCookieData( 'itemMarking_' . $app . '_items', array() );
			$this->_updateCookieData( 'itemMarking_' . $app, $cookie );
		}
		else
		{
			/* Do 'em all */
			$cache = array();
			$apps  = IPSLib::getEnabledApplications( array('itemMarking') );
			
			foreach( $apps as $app => $data )
			{
				$cache[ 'gb_mark__' . $app ] = time();
				
				/* Update cookies */
				$this->_updateCookieData( 'itemMarking_' . $app . '_items', array() );
				$this->_updateCookieData( 'itemMarking_' . $app, $cookie );
			}
			
			if ( count( $cache ) )
			{
				/* Reset member cache */
				IPSMember::packMemberCache( $this->memberData['member_id'], $cache );
			}
			
			/* Mark rows for deletion */
			$this->DB->update( 'core_item_markers', array( 'item_is_deleted' => 1 ), 'item_member_id=' . intval( $this->memberData['member_id'] ) );
			
			/* Reset internal array */
			$this->_itemMarkers = array();
		}
		
		/* Save cookie */
		$this->_setSaveCookie();
	}
	
	/**
	 * Fetch the last time an item was marked
	 * checks app reset... then the different key resets.. then an individual key
	 *
	 * In this example: itemID is within 'item_read_array'.
	 * $lastReset = $this->fetchTimeLastMarked( array( 'forumID' => 2, 'itemID' => '99' ) );
	 *
	 * @access	public
	 * @param	array 		Data
	 * @param	string		Optional app
	 * @return	int 		Timestamp item was last marked or 0 if unread
	 */
	public function fetchTimeLastMarked( $data, $app='' )
	{
		$app   = ( $app ) ? $app : IPS_APP_COMPONENT;
		
		/* Check check for how long we're tracking for */
		if ( $data['itemLastUpdate'] && $this->settings['topic_marking_keep_days'] )
		{
			if ( ( IPS_UNIX_TIME_NOW - ( $this->settings['topic_marking_keep_days'] * 86400 ) ) > $data['itemLastUpdate'] )
			{
				return IPS_UNIX_TIME_NOW;
			}
		}
		
		unset( $data['itemLastUpdate'] );
		
		$data  = $this->_fetchModule( $app )->convertData( $data );

		$times = array();
		
		$times[] = intval( $this->_findLatestGlobalReset( $data, $app ) );

		if ( isset( $data['itemID'] ) )
		{
			$times[] = intval( $this->_findLatestItemReset( $data, $app ) );
		}
		
		return IPSLib::fetchHighestNumber( $times );
	}
	
	/**
	 * Fetch the unread count
	 *
	 * In this example we are retrieving the number of unread items for a forum id 2
	 * $unreadCount = $this->fetchUnreadCount( array( 'forumID' => 2 ) );
	 *
	 * @access	public
	 * @param	array 		Data
	 * @param	string		App
	 * @return	int 		Number of unread items left
	 */
	public function fetchUnreadCount( $data, $app='' )
	{
		$app   = ( $app ) ? $app : IPS_APP_COMPONENT;
		$data  = $this->_fetchModule( $app )->convertData( $data );

		$times = array();
		
		if ( isset( $data['item_app_key_1'] ) )
		{
			$mainKey  = $this->_findMainRowByKey( $data, $app );
			
			return intval( $this->_itemMarkers[ $app ][ $mainKey ]['item_unread_count'] );
		}

		return 0;
	}
	
	/**
	 * Fetches the oldest unread timestamp
	 *
	 * In this example we are retrieving the number of unread items for a forum id 2
	 * $unreadCount = $this->fetchOldestUnreadTimestamp( array( 'forumID' => 2 ) );
	 *
	 * @access	public
	 * @param	array 		Data
	 * @param	string		App
	 * @return	int 		Number of unread items left
	 */
	public function fetchOldestUnreadTimestamp( $data, $app='' )
	{
		$app   = ( $app ) ? $app : IPS_APP_COMPONENT;
		$data  = $this->_fetchModule( $app )->convertData( $data );
		$time  = IPS_UNIX_TIME_NOW;
		
		if ( ! isset( $data['item_app_key_1'] ) )
		{
			if ( is_array( $this->_itemMarkers[ $app ] ) AND count( $this->_itemMarkers[ $app ] ) )
			{
				foreach( $this->_itemMarkers[ $app ] as $key => $_data )
				{
					if ( $_data['item_global_reset'] && ( $_data['item_global_reset'] < $time ) )
					{
						$time = $_data['item_global_reset'];
					}
				}
			}
		}
		else
		{
			$mainKey  = $this->_findMainRowByKey( $data, $app );
			$_data    = $this->_itemMarkers[ $app ][ $mainKey ];
			
			if ( $_data['item_global_reset'] && ( $_data['item_global_reset'] < $time ) )
			{
				$time = $_data['item_global_reset'];
			}
		}
		
		return $time;
	}

	
	/**
	 * Fetch read IDs since last global reset
	 * Returns IDs read based on incoming data
	 *
	 * <code>$readIDs = $itemMarking->fetchReadIds( array( 'forumID' => 2 ) );</code>
	 * @access		public
	 * @param		array 		Array of data
	 * @param		strng		[App]
	 * @return		array 		Array of read item IDs
	 */
	public function fetchReadIds( $data, $app='', $keysOnly=true )
	{
		$app      = ( $app ) ? $app : IPS_APP_COMPONENT;
		$origData = $data;

		/* Get all read ids */
		if ( ! isset( $data['item_app_key_1'] ) )
		{
			$_results	= array();

			if ( is_array( $this->_itemMarkers[ $app ] ) AND count( $this->_itemMarkers[ $app ] ) )
			{
				foreach( $this->_itemMarkers[ $app ] as $key => $_data )
				{
					if ( $_data['item_read_array'] )
					{
						/* + is intentionally used as the arrays will preserve their keys, but will not with array_merge */
						$_results	= $_results + $_data['item_read_array'];
					}
				}
			}

			return ( $keysOnly ) ? array_keys( $_results ) : $_results;
		}
		else
		{
			$data     = $this->_fetchModule( $app )->convertData( $data );
			$mainKey  = $this->_findMainRowByKey( $data, $app );

			if ( isset( $this->_itemMarkers[ $app ][ $mainKey ]['item_read_array'] ) AND is_array($this->_itemMarkers[ $app ][ $mainKey ]['item_read_array']) )
			{
				return ( $keysOnly ) ? array_keys( $this->_itemMarkers[ $app ][ $mainKey ]['item_read_array'] ) : $this->_itemMarkers[ $app ][ $mainKey ]['item_read_array'];
			}
		}

		return array();
	}
	
	/**
	 * Fetch read ID
	 * If forum was cleared, it may not exist as global reset takes precidence. 
	 * <code>$readIDs = $itemMarking->fetchReadId( array( 'forumID' => 2, 'itemId' => 2 ) );</code>
	 * @access		public
	 * @param		array 		Array of data
	 * @param		strng		[App]
	 * @return		int			0 or timestamp
	 */
	public function fetchItemReadTime( $data, $app='' )
	{
		$app      = ( $app ) ? $app : IPS_APP_COMPONENT;
		$origData = $data;
		$data     = $this->_fetchModule( $app )->convertData( $data );
		$mainKey  = $this->_findMainRowByKey( $data, $app );
		
		if ( isset( $this->_itemMarkers[ $app ][ $mainKey ]['item_read_array'] ) AND is_array($this->_itemMarkers[ $app ][ $mainKey ]['item_read_array']) )
		{
			return intval( $this->_itemMarkers[ $app ][ $mainKey ]['item_read_array'][ $data['itemID'] ] );
		}
		else
		{
			return 0;
		}
	}
		
	/**
	 * Find latest Item reset
	 *
	 * @access	protected
	 * @param	array 		Array of data (DB field names)
	 * @param	string 		App to check
	 * @return	int			Timestamp
	 */
	protected function _findLatestItemReset( $data, $app )
	{
		$cookie      = $this->_fetchCookieData( 'itemMarking_' . $app );
		$cookieItems = $this->_fetchCookieData( 'itemMarking_' . $app . '_items');
		$_times      = array();
		
		if ( ! $this->memberData['member_id'] AND $this->_noGuestMarking )
		{
			return time();
		}
		
		$mainKey = $this->_findMainRowByKey( $data, $app );
		$mainRow = $this->_itemMarkers[ $app ][ $mainKey ];
		
		/* Got a DB field? */
		if ( isset( $mainRow['item_read_array'] ) AND is_array( $mainRow['item_read_array'] ) )
		{
			if( isset( $mainRow['item_read_array'][ $data['itemID'] ] ) )
			{
				$_times[] = intval( $mainRow['item_read_array'][ $data['itemID'] ] );
			}
		}
		
		/* Got a cookie field? */
		if ( isset( $cookieItems[ $data['itemID'] ] ) )
		{
			$_times[] = $cookieItems[ $data['itemID'] ];
		}
		
		$_time = IPSLib::fetchHighestNumber( $_times );
		
		return $_time;
	}
		
	/**
	 * Find latest Global reset
	 *
	 * @access	protected
	 * @param	array 		Array of data (DB field names)
	 * @param	string 		App to check
	 * @return	int			Timestamp
	 */
	protected function _findLatestGlobalReset( $data, $app )
	{
		$_time       = 0;
		$_times      = array();
		$_key        = $this->_makeKey( $data );
		$cookie      = $this->_fetchCookieData( 'itemMarking_' . $app );
		$cookieItems = $this->_fetchCookieData( 'itemMarking_' . $app . '_items');
		
		if ( ! $this->memberData['member_id'] AND $this->_noGuestMarking )
		{
			return time();
		}

		$mainKey = $this->_findMainRowByKey( $data, $app );
		
		/* Got all fields? */
		if ( isset( $this->_itemMarkers[ $app ][ $mainKey ]['item_global_reset'] ) )
		{
			$_times[] = $this->_itemMarkers[ $app ][ $mainKey ]['item_global_reset'];
		}
		
		if ( isset( $cookie['greset'][ $this->_makeKey( $data, TRUE ) ] ) )
		{
			$_times[] = intval( $cookie['greset'][ $this->_makeKey( $data, TRUE ) ] );
		}
		
		if ( isset( $cookie['global'] ) )
		{
			$_times[] = intval( $cookie['global'] );
		}
		
		if ( isset( $this->memberData['_cache']['gb_mark__'. $app ] ) )
		{
			$_times[] = $this->memberData['_cache']['gb_mark__'.  $app ];
		}
		
		/* Get most recent time */
		$_time = IPSLib::fetchHighestNumber( $_times );
		
		return $_time;
	}
	
	/**
	 * Find a particular row
	 *
	 * @access	protected
	 * @param	array 		Array of data
	 * @param	string		App
	 * @return	array 		Array of data: core_item_marking row, effectively
	 */
	protected function _findMainRowByKey( $data, $app )
	{
		/* Not interested in this for the main row */
		unset( $data['itemID'], $data['itemLastUpdate'] );
		
		$_key  = $this->_makeKey( $data );
		
		if ( ! isset( $this->_itemMarkers[ $app ] ) OR ! is_array( $this->_itemMarkers[ $app ] ) )
		{
			/* Mark markers as having changed */
			$this->_changesMade = TRUE;
			
			/* Add in extra items */
			$data['item_app']		 = $app;
			$data['item_key']		 = $_key;
			$data['item_member_id']	 = $this->memberData['member_id'];
			$data['item_read_array'] = array();
			$this->_itemMarkers[ $app ]          = array();
			$this->_itemMarkers[ $app ][ $_key ] = $data;
			
			IPSDebug::addMessage( "Item Marking Key Created! $_key" );
			
			return $_key;
		}
		
		if ( ! empty( $this->_itemMarkers[ $app ][ $_key ] ) AND is_array( $this->_itemMarkers[ $app ][ $_key ] ) )
		{
			/* Make sure it contains the app & key */
			$this->_itemMarkers[ $app ][ $_key ]['item_app']		= $app;
			$this->_itemMarkers[ $app ][ $_key ]['item_key']		= $_key;
			$this->_itemMarkers[ $app ][ $_key ]['item_member_id']	= $this->memberData['member_id'];
			
			/* Make sure read IDs are unserialized */
			if ( isset( $this->_itemMarkers[ $app ][ $_key ]['item_read_array'] ) AND ! is_array( $this->_itemMarkers[ $app ][ $_key ]['item_read_array'] ) )
			{
				$this->_itemMarkers[ $app ][ $_key ]['item_read_array'] = unserialize( $this->_itemMarkers[ $app ][ $_key ]['item_read_array'] );
			}
			
			return $_key;
		}
		else
		{
			/* Mark markers as having changed */
			$this->_changesMade = TRUE;
			
			/* Make sure it contains the app & key */
			$this->_itemMarkers[ $app ][ $_key ]['item_app']		= $app;
			$this->_itemMarkers[ $app ][ $_key ]['item_key']		= $_key;
			$this->_itemMarkers[ $app ][ $_key ]['item_member_id']	= $this->memberData['member_id'];
			$this->_itemMarkers[ $app ][ $_key ]['item_read_array'] = array();
			$this->_itemMarkers[ $app ][ $_key ] = $data;
			
			IPSDebug::addMessage( "Item Marking Key returned! $_key" );

			return $_key;
		}
		
		/* Mark markers as having changed */
		
		/**
		 * @todo	Matt: this code is not used anymore? We already return in the if/else above..
		 */
		$this->_changesMade = TRUE;
		
		// Create a new key ...
		$data['item_app']		 = $app;
		$data['item_key']		 = $_key;
		$data['item_member_id']	 = $this->memberData['member_id'];
		$data['item_read_array'] = array();
		
		$this->_itemMarkers[ $app ]          = array();
		$this->_itemMarkers[ $app ][ $_key ] = $data;
		
		return $_key;
	}
	
	/**
	 * Fetch module class
	 *
	 * @access	protected
	 * @param	string			App to fetch
	 * @return	object
	 */
	protected function _fetchModule( $app='' )
	{
		$app = ( $app ) ? $app : IPS_APP_COMPONENT;
		
		if ( isset( $this->_moduleApps[ $app ] ) && is_object( $this->_moduleApps[ $app ] ) )
		{
			return $this->_moduleApps[ $app ];
		}
		else
		{
			$_file = IPSLib::getAppDir( $app ) . '/extensions/coreExtensions.php';
			
			if ( is_file( $_file ) )
			{
				$classToLoad = IPSLib::loadLibrary( $_file, 'itemMarking__'.$app, $app );
				
				if ( class_exists( $classToLoad ) )
				{
					$this->_moduleApps[ $app ] = new $classToLoad( $this->registry );
					
					return $this->_moduleApps[ $app ];
				}
				else
				{
					throw new Exception( "No itemMarking class available for $app" );
				}
			}
			else
			{
				throw new Exception( "No itemMarking class available for $app" );
			}
		}
	}

	/**
	 * Make a new key
	 *
	 * @access	protected
	 * @param   array
	 * @param	bool		Is cookie?
	 * @return	string
	 */
	protected function _makeKey( $data, $isCookie=FALSE )
	{
		/* Not interested in this for the main row */
		unset( $data['itemID'], $data['itemLastUpdate'], $data['containerLastActivityDate'], $data['markDate'] );
		
		return ( $isCookie === TRUE ) ? $data['item_app_key_1'] : md5( serialize( $data ) );
	}
	
	/**
	 * Update topic markers
	 *
	 * @access	protected
	 * @param	string	app
	 * @param	string	mainkey
	 * @return	@e void
	 */
	protected function _save( $app, $mainKey )
	{
		if ( ! $this->settings['topic_marking_enable'] )
		{
			return FALSE;
		}
		
		if ( ! $this->memberData['member_id'] )
		{
			return FALSE;
		}
		
		$row = array( 'item_key'          => $mainKey,
					  'item_member_id'    => $this->memberData['member_id'],
					  'item_app'	      => $app,
					  'item_last_update'  => intval( $this->_itemMarkers[ $app ][ $mainKey ]['item_last_update'] ),
					  'item_last_saved'   => time(),
					  'item_read_array'   => ( is_array( $this->_itemMarkers[ $app ][ $mainKey ]['item_read_array'] ) ) ? serialize( $this->_itemMarkers[ $app ][ $mainKey ]['item_read_array'] ) : $this->_itemMarkers[ $app ][ $mainKey ]['item_read_array'],
					  'item_global_reset' => intval( $this->_itemMarkers[ $app ][ $mainKey ]['item_global_reset'] ),
					  'item_unread_count' => intval( $this->_itemMarkers[ $app ][ $mainKey ]['item_unread_count'] ),
					  'item_app_key_1'	  => intval( $this->_itemMarkers[ $app ][ $mainKey ]['item_app_key_1'] ),
					  'item_app_key_2'	  => intval( $this->_itemMarkers[ $app ][ $mainKey ]['item_app_key_2'] ),
					  'item_app_key_3'	  => intval( $this->_itemMarkers[ $app ][ $mainKey ]['item_app_key_3'] ),
					  'item_is_deleted'   => 0 );
		
		$this->DB->replace( 'core_item_markers', $row, array( 'item_key', 'item_app', 'item_member_id' ) );
	}
	
	/**
	 * Manual destructor called by ips_MemberRegistry::__myDestruct()
	 * Gives us a chance to do anything we need to do before other
	 * classes are culled
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function __myDestruct()
	{
		/* Task? */
		if ( IPS_IS_TASK === TRUE )
		{
			return;
		}
		
		/* Search engine? */
		if ( $this->member->is_not_human === TRUE )
		{
			return;
		}
		
		/* Save cookies */
		if ( $this->_setSaveCookie )
		{
			$this->_saveCookie();
		}
	}
	
	/**
	 * Build item markers.
	 * Ok, this function attempts to gather the corret item markers. 
	 * The function also loads data from the markers DB and attempts to build a 'merged' set of markers
	 * based on all the data it has.
	 *
	 * @access	protected
	 * @return	array
	 */
	protected function _generateIncomingItemMarkers()
	{
		$items = NULL;
		
		/* Not playing? */
		if ( ! $this->settings['topic_marking_enable'] )
		{
			return array();
		}
		
		/* Not a member */
		if ( ! $this->memberData['member_id'] )
		{
			return array();
		}
		
		/* Not loading on init? */
		try
		{
			if ( method_exists( $this->_fetchModule(), 'loadAllMarkers' ) )
			{
				$result = $this->_fetchModule()->loadAllMarkers();
				
				if ( $result === false )
				{
					return;
				}
			}
		}
		catch( Exception $e )
		{
		}	
		
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'core_item_markers',
								 'where'  => 'item_member_id=' . $this->memberData['member_id'] ) );

		$itemMarking = $this->DB->execute();

		while( $row = $this->DB->fetch( $itemMarking ) )
		{
			if ( ! $row['item_is_deleted'] )
			{
				$items[ $row['item_app'] ][ $row['item_key'] ] = $row;
			}
		}
		
		/* Check data */
		if ( is_array( $items ) AND count( $items ) )
		{
			foreach( $items as $app => $item )
			{
				foreach( $item as $key => $_data )
				{
					if ( $app AND $key )
					{
						/* Ensure INT values */
						$items[ $app ][ $key ]['item_last_update']  = intval( $_data['item_last_update'] );
						$items[ $app ][ $key ]['item_global_reset'] = intval( $_data['item_global_reset'] );
						$items[ $app ][ $key ]['item_unread_count'] = intval( $_data['item_unread_count'] );
						$items[ $app ][ $key ]['item_member_id']    = intval( $_data['item_member_id'] );
						$items[ $app ][ $key ]['item_last_saved']   = intval( $_data['item_last_saved'] );
					
						/* Ensure read array is unserialized correctly */
						if ( isset( $_data['item_read_array'] ) AND ! is_array( $_data['item_read_array'] ) )
						{
							$items[ $app ][ $key ]['item_read_array'] = unserialize( $_data['item_read_array'] );
						}
						
						/* Now clean it up */
						if ( isset( $_data['item_read_array'] ) AND is_array( $items[ $app ][ $key ]['item_read_array'] ) )
						{
							/* Remove items that are older than the last marked time */
							if ( $items[ $app ][ $key ]['item_global_reset'] )
							{
								$this->_keyLastCleared = intval( $_data['item_global_reset'] );
								$items[ $app ][ $key ]['item_read_array'] = array_filter( $items[ $app ][ $key ]['item_read_array'], array( $this, 'arrayFilterRemoveAlreadyClearedItems' ) );
							}
						}
						else
						{
							$items[ $app ][ $key ]['item_read_array'] = array();
						}
					}
				}
			}
		}
		else
		{
			return array();
		}

		return $items;
	}
	
	/**
	 * Array sort Used to remove out of date topic marking entries
	 *
	 * @access	public
	 * @param	mixed
	 * @return	mixed
	 * @since	2.0
	 */
    public function arrayFilterRemoveAlreadyClearedItems( $var )
	{
		return $var > $this->_keyLastCleared;
	}
	
	/**
	 * Array sort Used to remove out of date topic marking entries
	 *
	 * @access	public
	 * @param	mixed
	 * @return	mixed
	 * @since	2.0
	 */
    public function arrayFilterCleanReadItems( $var )
	{
		return $var > $this->_dbCutOff;
	}
		
	/**
	 * Array sort Used to make sure there are no more than 50 last read items
	 *
	 * @access	public
	 * @param	mixed
	 * @return	mixed
	 * @since	2.0
	 */
    public function arrayFilterCookieItems( $var )
	{
		$this->_cookieCounter++;
		
		return ( $this->_cookieCounter <= 50 ) ? TRUE : FALSE;
	}

	/** 
    * Save my markers to DB 
    * 
    * Saves the current values in $this->_itemMarkers back to the DB 
    * 
    * @access  public 
    * @return  int    Number of rows saved 
    * @deprecated 3.3.0  Really depricated now!
    */ 
   public function writeMyMarkersToDB()
   {
   	
   }

}