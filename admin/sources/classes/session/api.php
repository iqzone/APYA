<?php
/**
 * @file		api.php 	Provides global methods to retrieve active users from the sessions table
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		Friday 12th November 2010 16:58
 * $LastChangedDate: 2012-05-29 10:44:35 -0400 (Tue, 29 May 2012) $
 * @version		v3.3.3
 * $Revision: 10809 $
 */

/**
 *
 * @class		session_api
 * @brief		Provides global methods to retrieve active users from the sessions table
 *
 */
class session_api
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
	
	private $_sClass = null;
	
	const NO_AUTO_SESSION_PARSING = true;
	
	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
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
	}
	
	/**
	 * Returns a list (with buildProfilePhoto set) of users in an application
	 *
	 * @param	string		$app		Application folder/key
	 * @param	array		$options	Array of options to add/override checks for the query
	 * @return	@e array Array of found sessions (members, guests, bots, anons)
	 *
	 * <b>Filters:</b>
	 * - cutoff:		Specify a different cutoff time rather than using the default setting
	 * - addJoins:		Add more joins for the query
	 * - addWhere:		Add more checks for the 'where' part of the query (everything is joined with ' AND ')
	 * - overrideWhere:	Specify manually the 'where' part of the query, the query must be already compiled
	 * - overrideGroup:	if set to true the check against the 'gbw_view_online_lists' group setting is skipped
	 * - skipParsing:	If set to true the parseOnlineEntries from coreExtensions is NOT executed
	 * - includeErrors:	If set to true the in_error=1 lines are loaded as well from the table
	 * - excludeViewer:	If set to true skips loading the row for the current session_id, it is required to load the current member viewing the page as sessions table is not updated at this point yet but only on destruct
	 * 
	 * <b>Example Usage:</b>
	 * @code
	 * $onlineUsers = $this->getUsersIn( 'forums', array( 'cutoff' => 90 ) );
	 * $onlineUsers = $this->getUsersIn( 'forums', array( 'cutoff' => 90, 'addJoins' => array( ... ), 'addWhere' => array( ... ) ) );
	 * $onlineUsers = $this->getUsersIn( 'forums', array( 'overrideWhere' => "s.current_appcomponent='forums' AND (s.running_time > TIME_A OR s.running_time < TIME_B)" ) );
	 * @endcode
	 */
	public function getUsersIn( $app, $options=array() )
	{
		/* App we're checking is not even installed? */
		if ( ! IPSLib::appIsInstalled( $app, false ) )
		{
			return array();
		}
		
		/* Can't view online lists? */
		if ( empty($options['overrideGroup']) && ! $this->memberData['gbw_view_online_lists'] )
		{
			return array();
		}
		
		/* Init vars and check options */
		$return = array(  'stats' => array( 'total'   => 0,
											'members' => 0,
										    'guests'  => 0,
											'bots'	  => 0,
											'anon'	  => 0 ),
						  'rows'  => array( 'members' => array(),
											'bots'    => array(),
											'guests'  => array(),
											'anon' 	  => array() ),
						  'names' => array()
						 );
		
		$cutoff = empty($options['cutoff']) ? $this->settings['au_cutoff'] : $options['cutoff'];
		$limit	= time() - ( $cutoff * 60 );
		$rows	= array();
		$NOWJIM = IPS_UNIX_TIME_NOW;
		$cached = array();
		
		/* Sort joins */
		$_joins = array( array( 'select' => 'm.*',
								'from'   => array( 'members' => 'm' ),
								'where'  => 'm.member_id=s.member_id',
								'type'   => 'left' ) );
		
		if ( isset($options['addJoins']) && is_array($options['addJoins']) && count($options['addJoins']) )
		{
			$_joins = array_merge( $_joins, $options['addJoins'] );
		}
		
		/* Sort where.. override? */
		if ( !empty($options['overrideWhere']) && is_string($options['overrideWhere']) )
		{
			$where = $options['overrideWhere'];
		}
		else
		{
			/* Normal where */
			$where = array( "s.current_appcomponent='" . $this->DB->addSlashes( $app ) . "'", 's.running_time > ' . $limit );
			
			/* Load error rows? */
			if ( empty($options['includeErrors']) )
			{
				$where[] = 's.in_error=0';
			}
			
			/* Add more where parts */
			if ( isset($options['addWhere']) && is_array($options['addWhere']) && count($options['addWhere']) )
			{
				$where = array_merge( $where, $options['addWhere'] );
			}
			
			$where = implode( ' AND ', $where );
		}
		
		/* We're a viewer too? Get our session separately */
		$_extraWhere = empty($options['excludeViewer']) ? "s.id='{$this->member->session_id}' OR " : '';
		
		/* Dee bee */
		$this->DB->build( array( 'select'   => 's.*, s.id as row_session_id',
								 'from'	    => array( 'sessions' => 's' ),
								 'where'    => "{$_extraWhere}({$where})",
								 'add_join' => $_joins ) );
		$this->DB->execute();

		while( $session = $this->DB->fetch() )
		{
			/* Reset for possible bad joins */
			$session['id'] = $session['row_session_id'];
			
			/* Update our own session properly? */
			if ( $session['id'] == $this->member->session_id ) 
			{
				$session = array_merge( $session, $this->member->sessionClass()->returnCurrentSession() );
			}
			
			$rows[ $session['running_time'] . '.' . $session['id'] ] = $session;
		}
		
		/* No rows? */
		if ( ! count( $rows ) )
		{
			return $return;
		}
		
		krsort( $rows );
		
		/* Are we parsing online entries or want only the names */
		if ( empty( $options['skipParsing'] ) )
		{
			/* Process them */
			$filename = IPSLib::getAppDir( $app ) . '/extensions/coreExtensions.php';
						
			if ( is_file( $filename ) )
			{
				$classToLoad = IPSLib::loadLibrary( $filename, 'publicSessions__' . $app, $app );
				$loader      = new $classToLoad();
	
				if ( method_exists( $loader, 'parseOnlineEntries' ) )
				{
					$rows = $loader->parseOnlineEntries( $rows );
				}
			}
			
			/* No rows? */
			if ( ! count( $rows ) )
			{
				return $return;
			}
		}
		
		/* Sort through */
		foreach( $rows as $id => $result )
		{
			$last_date = $this->registry->getClass('class_localization')->getTime( $result['running_time'] );

			/* ROBOT - or DODOT! */
			if ( strstr( $result['id'], '_session' ) )
			{
				$botname = preg_replace( '/^(.+?)=/', "\\1", $result['id'] );

				if ( ! $cached[ 'srch_' . $result['member_name'] ] )
				{
					$result 					= IPSMember::buildProfilePhoto( $result );
					$result['parsedMemberName'] = $result['member_name'];
					$return['rows']['bots'][ $result['id'] ] = $result;
					$return['names'][ $result['id'] ]		 = $result['parsedMemberName'];

					$cached[ 'srch_' . $result['member_name'] ]['count'] = 1;
				}
				else
				{
					$cached[ 'srch_' . $result['member_name'] ]['count']++;
				}
				
				$return['stats']['bots']++;
			}
			/* Guest */
			else if ( ! $result['member_id'] )
			{
				$result						= IPSMember::buildProfilePhoto( 0 );
				$result['parsedMemberName'] = $this->lang->words['global_guestname'];
				$return['rows']['guests'][ $result['id'] ] = $result;
				
				$return['stats']['guests']++;
			}
			/* Member */
			else
			{
				if ( empty( $cached[ $result['member_id'] ] ) )
				{
					$cached[ $result['member_id'] ] = 1;
					
					$result						= IPSMember::buildProfilePhoto( $result );
					$result['parsedMemberName'] = IPSMember::makeNameFormatted( $result['member_name'], $result['member_group'] );
					
					/* Reset login type in case the board/group setting got changed */
					$result['login_type']  = IPSMember::isLoggedInAnon( array( 'login_anonymous' => $result['login_type'] ), $result['member_group_id'] );
					
					if ( $result['login_type'] )
					{
						if ( $this->memberData['g_access_cp'] || ( $this->memberData['member_id'] == $result['member_id'] ) )
						{
							$result['parsedMemberName']  = IPSMember::makeProfileLink( $result['parsedMemberName'], $result['member_id'], $result['seo_name'] );
							$result['parsedMemberName'] .= '*'; # Add anonymous asterisk
							$return['rows']['anon'][ $result['id'] ] = $result;
							$return['names'][ $result['id'] ]		 = $result['parsedMemberName'];
						}
						
						$return['stats']['anon']++;
					}
					else
					{
						$result['parsedMemberName']  = IPSMember::makeProfileLink( $result['parsedMemberName'], $result['member_id'], $result['seo_name'] );
						$return['rows']['members'][ $result['id'] ] = $result;
						$return['names'][ $result['id'] ]			= $result['parsedMemberName'];
						
						$return['stats']['members']++;
					}
				}
			}
		}
	
		/* Process bots */
		foreach( $cached as $name => $val )
		{
			if ( $val['count'] && substr( $name, 0, 5 ) == 'srch_' )
			{ 
				foreach( $return['rows']['bots'] as $row )
				{
					if ( $row['parsedMemberName'] == substr( $name, 5 ) )
					{
						 $return['rows']['bots'][ $row['id'] ]['parsedMemberName'] .= ' (' . $val['count'] . ')';
						 $return['rows']['bots'][ $row['id'] ]['member_name'] = $return['rows']['bots'][ $row['id'] ]['parsedMemberName'];
						 $return['names'][ $row['id'] ] = $return['rows']['bots'][ $row['id'] ]['parsedMemberName'];
						 break;
					}
				}
			}	
		}
		
		$return['stats']['total'] = intval( $return['stats']['bots'] ) + intval( $return['stats']['guests'] ) + intval( $return['stats']['anon'] ) + intval( $return['stats']['members'] );
	
		return $return;
	}
	
	/**
	 * Fetch session by member id
	 * @param	int		Member Id
	 * @return	array	session data
	 */
	public function getSessionByMemberId( $memberId )
	{
		$_session = $this->DB->buildAndFetch( array( 'select'	=> '*',
										   			 'from'	    => 'sessions',
													 'where'    => "member_id=" . intval( $memberId ) ) );

		if ( $_session['id'] )
		{
			/* Test for browser.... */
			if ( $this->settings['match_browser'])
			{
				if ( $_session['browser'] != substr( $this->member->user_agent, 0, 200 ) )
				{
					return false;
				}
			}

			/* Test for IP Address... */
			if ( $this->settings['match_ipaddress'] )
			{
				if ( $_session['ip_address'] != $this->member->ip_address )
				{
					return false;
				}
			}
			
			return $_session;
		}
		
		return false;
	}
	
	/**
	 * Log user in remotely.
	 * @param	array	Array of member data (member_id, etc) or Id
	 */
	public function logGuestInAsMember( $member )
	{
		if ( is_numeric( $member ) )
		{
			$member = IPSMember::load( $member );
		}
		
		/* Check for existing session */
		$ip = ipsRegistry::member()->ip_address;
		$_session = $this->DB->buildAndFetch( array( 'select'	=> '*',
										   			 'from'	    => 'sessions',
													 'where'    => "ip_address='{$ip}'" ) );
				
		if ( !$_session['id'] or !$_session['member_id'] )
		{
			$this->_getSessionClass()->convertGuestToMember( $member, $member );
		}
	}
	
	/**
	 * Load session class if not loaded
	 */
	protected function _getSessionClass()
	{
		if ( ! is_object( $this->_sClass ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/session/publicSessions.php', 'publicSessions' );

			/**
			 * Support for extending the session class
			 */
			if ( is_file( IPS_ROOT_PATH . "sources/classes/session/ssoPublicSessions.php" ) )
			{
				$classToLoadA = IPSLib::loadLibrary( IPS_ROOT_PATH . "sources/classes/session/ssoPublicSessions.php", 'ssoPublicSessions' );

				/**
				 * Does the ssoPublicSessions class exist?
				 */
				if( class_exists( $classToLoadA ) )
				{
					$parent = get_parent_class( $classToLoadA );

					/**
					 * Is it a child of publicSessions
					 */
					if ( $parent == $classToLoad )
					{
						$this->_sClass = new $classToLoadA( self::NO_AUTO_SESSION_PARSING );
					}
					else
					{
						$this->_sClass = new $classToLoad( self::NO_AUTO_SESSION_PARSING );
					}
				}
			}
			else
			{
				$this->_sClass = new $classToLoad( self::NO_AUTO_SESSION_PARSING );
			}
		}
		
		return $this->_sClass;
	}
}