<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Remote API Server
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

class API_Server
{
	/**
	 * Defines the service for WSDL
	 *
	 * @var		array
	 */			
	public $__dispatch_map = array();
	
	/**
	 * IPS Global Class
	 *
	 * @var		object
	 */
	protected $registry;
	
	/**
	 * IPS API SERVER Class
	 *
	 * @var		object
	 */
	public $classApiServer;
	
	/**
	 * Constructor
	 * 
	 * @return	@e void
	 */		
	public function __construct( $registry ) 
    {
		//-----------------------------------------
		// Set IPS CLASS
		//-----------------------------------------
		
		$this->registry = $registry;
		
    	//-----------------------------------------
    	// Load allowed methods and build dispatch
		// list
    	//-----------------------------------------
    	$ALLOWED_METHODS = array();
		require_once( DOC_IPS_ROOT_PATH . 'interface/board/modules/ipb/methods.php' );/*noLibHook*/
		
		if ( is_array( $ALLOWED_METHODS ) and count( $ALLOWED_METHODS ) )
		{
			foreach( $ALLOWED_METHODS as $_method => $_data )
			{
				$this->__dispatch_map[ $_method ] = $_data;
			}
		}
	}
	
	/**
	 * Returns the list of online users
	 * 
	 * @param	string  $api_key		Authentication Key
	 * @param 	string  $api_module		Module
	 * @param	string	$sep_character	Separator character
	 * @return	string	xml
	 */	
	public function fetchOnlineUsers( $api_key, $api_module, $sep_character=',' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$api_key	= IPSText::md5Clean( $api_key );
		$api_module	= IPSText::parseCleanValue( $api_module );
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $api_key, $api_module, 'fetchOnlineUsers' ) !== FALSE )
		{
			if ( !ipsRegistry::$settings['au_cutoff'] )
			{
				ipsRegistry::$settings[ 'au_cutoff'] =  15 ;
			}
		 
			$cut_off = ipsRegistry::$settings['au_cutoff'] * 60;
			$time    = time() - $cut_off;
			$rows    = array();
			$ar_time = time();
			
			$this->registry->DB()->build( array( 'select'	=> '*',
												 'from'		=> 'sessions',
									 			 'where'	=> "running_time > {$time}" ) );
			$this->registry->DB()->execute();
			
			//-----------------------------------------
			// FETCH...
			//-----------------------------------------
			
			while ( $r = $this->registry->DB()->fetch() )
			{
				$rows[ $r['running_time'].'.'.$r['id'] ] = $r;
			}
			
			krsort( $rows );

			//-----------------------------------------
			// cache all printed members so we
			// don't double print them
			//-----------------------------------------
			
			$cached = array();
			
			foreach ( $rows as $result )
			{
				$last_date = ipsRegistry::getClass( 'class_localization')->getTime( $result['running_time'] );
				
				//-----------------------------------------
				// Bot?
				//-----------------------------------------
				
				if ( isset( $result['uagent_type'] ) && $result['uagent_type'] == 'search' )
				{
					//-----------------------------------------
					// Seen bot of this type yet?
					//-----------------------------------------
					
					if ( ! $cached[ $result['member_name'] ] )
					{
						$active['NAMES'][] = $result['member_name'];
						$cached[ $result['member_name'] ] = 1;
					}
					else
					{
						//-----------------------------------------
						// Yup, count others as guest
						//-----------------------------------------
						
						$active['GUESTS']++;
					}
				}
				
				//-----------------------------------------
				// Guest?
				//-----------------------------------------
				
				else if ( ! $result['member_id'] OR ! $result['member_name'] )
				{
					$active['GUESTS']++;
				}
				
				//-----------------------------------------
				// Member?
				//-----------------------------------------
				
				else
				{
					if ( empty( $cached[ $result['member_id'] ] ) )
					{
						$cached[ $result['member_id'] ] = 1;

						$result['member_name'] = IPSMember::makeNameFormatted( $result['member_name'], $result['member_group'] );
						
						/* Reset login type in case the board/group setting got changed */
						$result['login_type']  = IPSMember::isLoggedInAnon( array( 'login_anonymous' => $result['login_type'] ), $result['member_group'] );
						
						if ( $result['login_type'] )
						{
							if ( $this->registry->member()->getProperty('g_access_cp') )
							{
								$active['NAMES'][] = "<a href='" . $this->registry->getClass('output')->buildSEOUrl( "showuser={$result['member_id']}", 'public', $result['seo_name'], 'showuser' ) . "' title='$last_date'>{$result['member_name']}</a>*";
								$active['ANON']++;
							}
							else
							{
								$active['ANON']++;
							}
						}
						else
						{
							$active['MEMBERS']++;
							$active['NAMES'][] = "<a href='" . $this->registry->getClass('output')->buildSEOUrl( "showuser={$result['member_id']}", 'public', $result['seo_name'], 'showuser' ) ."' title='$last_date'>{$result['member_name']}</a>";
						}
					}
				}
			}
			
			$active['TOTAL'] = $active['MEMBERS'] + $active['GUESTS'] + $active['ANON'];
			
			//-----------------------------------------
			// Return info
			//-----------------------------------------
			
			$this->classApiServer->apiSendReply( $active );
			exit();
		}
	}
	
	/**
	 * Returns details about the board
	 * 
	 * @param	string  $api_key  	Authentication Key
	 * @param	string  $api_module  Module
	 * @return	string	xml
	 */	
	public function fetchStats( $api_key, $api_module )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$api_key                = IPSText::md5Clean( $api_key );
		$api_module             = IPSText::parseCleanValue( $api_module );
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $api_key, $api_module, 'fetchStats' ) !== FALSE )
		{
			$stats = $this->registry->cache()->getCache('stats');

			$most_time     = ipsRegistry::getClass('class_localization')->getDate( $stats['most_date'], 'LONG' );
			$most_count    = ipsRegistry::getClass('class_localization')->formatNumber( $stats['most_count'] );
			
			$total_posts   = $stats['total_topics'] + $stats['total_replies'];
			
			$total_posts   = ipsRegistry::getClass('class_localization')->formatNumber($total_posts);
			$mem_count     = ipsRegistry::getClass('class_localization')->formatNumber($stats['mem_count']);
			$mem_last_id   = $stats['last_mem_id'];
			$mem_last_name = $stats['last_mem_name'];
			
			//-----------------------------------------
			// Return info
			//-----------------------------------------
			
			$this->classApiServer->apiSendReply( array( 'users_most_online'         => $most_count,
			 												'users_most_date_formatted' => $most_time,
															'users_most_data_unix'		=> $stats['most_date'],
															'total_posts'				=> $total_posts,
															'total_members'				=> $mem_count,
															'last_member_id'			=> $mem_last_id,
															'last_member_name'			=> $mem_last_name ) );
			exit();
		}
	}
	
	/**
	 * Returns hello board test
	 * 
	 * @param	string  $api_key	Authentication Key
	 * @param	string  $api_module	Module
	 * @return	string	xml
	 */	
	public function helloBoard( $api_key, $api_module )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$api_key                = IPSText::md5Clean( $api_key );
		$api_module             = IPSText::parseCleanValue( $api_module );
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $api_key, $api_module, 'helloBoard' ) !== FALSE )
		{
			//-----------------------------------------
	   		// Upgrade history?
	   		//-----------------------------------------

	   		$latest_version = array( 'upgrade_version_id' => NULL );

	   		$this->registry->DB()->build( array( 'select' => '*', 'from' => 'upgrade_history', 'where' => "upgrade_app='core'", 'order' => 'upgrade_version_id DESC', 'limit' => array(1) ) );
	   		$this->registry->DB()->execute();

	   		while( $r = $this->registry->DB()->fetch() )
	   		{
				$latest_version = $r;
	   		}
	
			//-----------------------------------------
			// Return info
			//-----------------------------------------
			
			$this->classApiServer->apiSendReply( array( 'board_name'  		  => ipsRegistry::$settings['board_name'],
			 												'upload_url'  		  => ipsRegistry::$settings['upload_url'],
			 												'ipb_img_url' 		  => ipsRegistry::$settings['ipb_img_url'],
			 												'board_human_version' => $latest_version['upgrade_version_human'],
															'board_long_version'  => !empty($latest_version['upgrade_notes']) ? $latest_version['upgrade_notes'] : ipsRegistry::$vn_full ) );
			
			exit();
		}
	}
	
	/**
	 * Posts a topic to the board remotely
	 * 
	 * @param	string  $api_key		Authentication Key
	 * @param	string  $api_module		Module
	 * @param	string	$member_field	Member field to check (valid: "id", "email", "username", "displayname")
	 * @param	string	$member_key		Member key to check for
	 * @param	integer	$forum_id		Forum id to post in
	 * @param	string	$topic_title	Topic title
	 * @param	string	$topic_description	Topic description
	 * @param	string	$post_content	Posted content
	 * @return	string	xml
	 */	
	public function postTopic( $api_key, $api_module, $member_field, $member_key, $forum_id, $topic_title, $topic_description, $post_content )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$api_key                = IPSText::md5Clean( $api_key );
		$api_module             = IPSText::parseCleanValue( $api_module );
		$member_field           = IPSText::parseCleanValue( $member_field );
		$member_key             = IPSText::parseCleanValue( $member_key );
		$topic_title            = IPSText::parseCleanValue( $topic_title );
		$forum_id			    = intval( $forum_id );
		$UNCLEANED_post_content = $post_content;
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $api_key, $api_module, 'postTopic' ) !== FALSE )
		{
			//-----------------------------------------
			// Add log
			//-----------------------------------------
			
			if ( ipsRegistry::$settings['xmlrpc_log_type'] != 'failed' )
			{
				$this->registry->DB()->insert( 'api_log', array(   'api_log_key'     => $api_key,
																	'api_log_ip'      => $_SERVER['REMOTE_ADDR'],
																	'api_log_date'    => time(),
																	'api_log_query'   => $this->classApiServer->raw_request,
																	'api_log_allowed' => 1 ) );
			}

			//-----------------------------------------
			// Member field...
			//-----------------------------------------
			
			$member	= IPSMember::load( $member_key, 'all', $member_field );
			
			//-----------------------------------------
			// Got a member?
			//-----------------------------------------
			
			if ( ! $member['member_id'] )
			{
				$this->classApiServer->apiSendError( '10', "IP.Board could not locate a member using $member_key / $member_field" );
			}
			
			//-----------------------------------------
			// Get some classes
			//-----------------------------------------

			ipsRegistry::getAppClass( 'forums' );

			require_once( IPSLib::getAppDir( 'forums' ) . '/sources/classes/post/classPost.php' );/*noLibHook*/
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/post/classPostForms.php', 'classPostForms', 'forums' );
			$_postClass = new $classToLoad( $this->registry );
			
			/* reset forum list to allow all access */
			$this->registry->getClass('class_forums')->strip_invisible = false;
			$this->registry->getClass('class_forums')->forumsInit();
				
			//-----------------------------------------
			// Set the data
			//-----------------------------------------
		
			$_postClass->setIsPreview( false );
			$_postClass->setForumData( $this->registry->getClass('class_forums')->forum_by_id[ $forum_id ] );
			$_postClass->setForumID( $forum_id );
			$_postClass->setPostContent( $UNCLEANED_post_content );
			$_postClass->setAuthor( $member['member_id'] );
			$_postClass->setPublished( true );
			$_postClass->setSettings( array( 'enableSignature' => 1,
												   'enableEmoticons' => 1,
												   'post_htmlstatus' => 0,
												   'enableTracker'   => 0 ) );
			$_postClass->setTopicTitle( $topic_title );

			# Switch off perm checks
			$_postClass->setBypassPermissionCheck(true);
			/**
			 * And post it...
			 */
			try
			{
				if ( $_postClass->addTopic() === FALSE )
				{
					$this->classApiServer->apiSendError( '10', "IP.Board could not post the topic: " . $_postClass->getPostError() );
				}
			}
			catch( Exception $error )
			{
				$this->classApiServer->apiSendError( '10', "IP.Board post class exception: " . $error->getMessage() );
			}

			$this->classApiServer->apiSendReply( array( 
														'result'   => 'success',
														'topic_id' => $_postClass->getTopicData('tid')
												)	);
			exit();
		}
	}
	
	/**
	 * Posts a topic reply
	 * 
	 * @param	string  $api_key		Authentication Key
	 * @param	string  $api_module		Module
	 * @param	string	$member_field	Member field to check (valid: "id", "email", "username", "displayname")
	 * @param	string	$member_key		Member key to check for
	 * @param	integer	$topic_id		Topic id to post in
	 * @param	string	$post_content	Posted content
	 * @return	string	xml
	 */	
	public function postReply( $api_key, $api_module, $member_field, $member_key, $topic_id, $post_content )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$api_key                = IPSText::md5Clean( $api_key );
		$api_module             = IPSText::parseCleanValue( $api_module );
		$member_field           = IPSText::parseCleanValue( $member_field );
		$member_key             = IPSText::parseCleanValue( $member_key );
		$topic_id			    = intval( $topic_id );
		$UNCLEANED_post_content = $post_content;
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $api_key, $api_module, 'postReply' ) !== FALSE )
		{
			//-----------------------------------------
			// Add log
			//-----------------------------------------
			
			if ( ipsRegistry::$settings['xmlrpc_log_type'] != 'failed' )
			{
				$this->registry->DB()->insert( 'api_log', array(   'api_log_key'     => $api_key,
																	'api_log_ip'      => $_SERVER['REMOTE_ADDR'],
																	'api_log_date'    => time(),
																	'api_log_query'   => $this->classApiServer->raw_request,
																	'api_log_allowed' => 1 ) );
			}

			//-----------------------------------------
			// Member field...
			//-----------------------------------------
			
			$member	= IPSMember::load( $member_key, 'all', $member_field );
			
			//-----------------------------------------
			// Got a member?
			//-----------------------------------------
			
			if ( ! $member['member_id'] )
			{
				$this->classApiServer->apiSendError( '10', "IP.Board could not locate a member using $member_key / $member_field" );
			}

			//-----------------------------------------
			// Get some classes
			//-----------------------------------------

			ipsRegistry::getAppClass( 'forums' );

			require_once( IPSLib::getAppDir( 'forums' ) . '/sources/classes/post/classPost.php' );/*noLibHook*/
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/post/classPostForms.php', 'classPostForms', 'forums' );
			$_postClass = new $classToLoad( $this->registry );
			
			/* reset forum list to allow all access */
			$this->registry->getClass('class_forums')->strip_invisible = false;
			$this->registry->getClass('class_forums')->forumsInit();
			
			//-----------------------------------------
			// Need the topic...
			//-----------------------------------------
			
			$topic	= $this->registry->DB()->buildAndFetch( array( 'select' => '*', 'from' => 'topics', 'where' => 'tid=' . $topic_id ) );
			
			//-----------------------------------------
			// Set the data
			//-----------------------------------------

			$_postClass->setIsPreview( false );
			$_postClass->setForumData( $this->registry->getClass('class_forums')->forum_by_id[ $topic['forum_id'] ] );
			$_postClass->setForumID( $topic['forum_id'] );
			$_postClass->setTopicID( $topic_id );
			$_postClass->setTopicData( $topic );
			$_postClass->setPostContent( $UNCLEANED_post_content );
			$_postClass->setAuthor( $member['member_id'] );
			$_postClass->setPublished( true );
			$_postClass->setSettings( array( 'enableSignature' => 1,
												   'enableEmoticons' => 1,
												   'post_htmlstatus' => 0,
												   'enableTracker'   => 0 ) );
			
			/**
			 * And post it...
			 */
			try
			{
				if ( $_postClass->addReply() === FALSE )
				{
					//print $_postClass->_postErrors;
					$this->classApiServer->apiSendError( '10', "IP.Board could not add the reply " . $_postClass->_postErrors );
				}
			}
			catch( Exception $error )
			{
				$this->classApiServer->apiSendError( '10', "IP.Board post class exception: " . $error->getMessage() );
			}

			$this->classApiServer->apiSendReply( array( 'result'   => 'success' ) );
															

			exit();
		}
	}
	
	/**
	 * Returns a member
	 * 
	 * @param	string  $api_key		Authentication Key
	 * @param	string  $api_module		Module
	 * @param	string	$search_type	Member field to check (valid: "id", "email", "username", "displayname")
	 * @param	string	$search_string	String to search for
	 * @return	string	xml
	 */	
	public function fetchMember( $api_key, $api_module, $search_type, $search_string )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$api_key                = IPSText::md5Clean( $api_key );
		$api_module             = IPSText::parseCleanValue( $api_module );
		$search_type            = IPSText::parseCleanValue( $search_type );
		$search_string          = IPSText::parseCleanValue( $search_string );
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $api_key, $api_module, 'fetchMember' ) !== FALSE )
		{
			//-----------------------------------------
			// Add log
			//-----------------------------------------
			
			if ( ipsRegistry::$settings['xmlrpc_log_type'] != 'failed' )
			{
				$this->registry->DB()->insert( 'api_log', array(   'api_log_key'     => $api_key,
																	'api_log_ip'      => $_SERVER['REMOTE_ADDR'],
																	'api_log_date'    => time(),
																	'api_log_query'   => $this->classApiServer->raw_request,
																	'api_log_allowed' => 1 ) );
			}

			//-----------------------------------------
			// Fetch forum list
			//-----------------------------------------
			
			$member = IPSMember::load( $search_string, 'all', $search_type );
			
			if ( ! $member['member_id'] )
			{
				$member = array( 'member_id' => 0 );
			}

			//-----------------------------------------
			// Return the data
			//-----------------------------------------
		
			$this->classApiServer->apiSendReply( $member );
			exit();
		}
	}
	
	/**
	 * Check if a member exists
	 * 
	 * @param	string  $api_key		Authentication Key
	 * @param	string  $api_module		Module
	 * @param	string	$search_type	Member field to check (valid: "id", "email", "username", "displayname")
	 * @param	string	$search_string	String to search for
	 * @return	string	xml
	 */	
	public function checkMemberExists( $api_key, $api_module, $search_type, $search_string )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$api_key                = IPSText::md5Clean( $api_key );
		$api_module             = IPSText::parseCleanValue( $api_module );
		$search_type            = IPSText::parseCleanValue( $search_type );
		$search_string          = IPSText::parseCleanValue( $search_string );
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $api_key, $api_module, 'checkMemberExists' ) !== FALSE )
		{
			//-----------------------------------------
			// Add log
			//-----------------------------------------
			
			if ( ipsRegistry::$settings['xmlrpc_log_type'] != 'failed' )
			{
				$this->registry->DB()->insert( 'api_log', array(   'api_log_key'     => $api_key,
																	'api_log_ip'      => $_SERVER['REMOTE_ADDR'],
																	'api_log_date'    => time(),
																	'api_log_query'   => $this->classApiServer->raw_request,
																	'api_log_allowed' => 1 ) );
			}

			$member = IPSMember::load( $search_string, 'all', $search_type );
			
			//-----------------------------------------
			// Return the data
			//-----------------------------------------
		
			$this->classApiServer->apiSendReply( array( 'memberExists' => $member['member_id'] ? true : false ) );
			exit();
		}
	}
	
	/**
	 * Fetch the forum options list.
	 * WARNING: Last two options are deprecated and no longer supported. All viewable forums returned. User is automatically treated like a guest.
	 * 
	 * @param	string  $api_key		Authentication Key
	 * @param	string  $api_module		Module
	 * @param	string	$selected_forum_ids	Comma separated list of forum ids
	 * @param	bool	$view_as_guest	Treat user as a guest
	 * @return	string	xml
	 */	
	public function fetchForumsOptionList( $api_key, $api_module, $selected_forum_ids, $view_as_guest )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$api_key                = IPSText::md5Clean( $api_key );
		$api_module             = IPSText::parseCleanValue( $api_module );
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $api_key, $api_module, 'fetchForumsOptionList' ) !== FALSE )
		{
			//-----------------------------------------
			// Add log
			//-----------------------------------------
			
			if ( ipsRegistry::$settings['xmlrpc_log_type'] != 'failed' )
			{
				$this->registry->DB()->insert( 'api_log', array(   'api_log_key'     => $api_key,
																	'api_log_ip'      => $_SERVER['REMOTE_ADDR'],
																	'api_log_date'    => time(),
																	'api_log_query'   => $this->classApiServer->raw_request,
																	'api_log_allowed' => 1 ) );
			}
			
			//-----------------------------------------
			// Get some classes
			//-----------------------------------------

			ipsRegistry::getAppClass( 'forums' );

			//-----------------------------------------
			// Fetch forum list
			//-----------------------------------------
			
			$list = $this->registry->getClass('class_forums')->forumsForumJump();
			
			//-----------------------------------------
			// Return the data
			//-----------------------------------------
		
			$this->classApiServer->apiSendReply( array( 'forumList' => $list ) );
			exit();
		}
	}
	
	/**
	 * Returns the board's forums.
	 * WARNING: Last option is deprecated and no longer supported.  User is automatically treated like a guest.
	 * 
	 * @param	string  $api_key		Authentication Key
	 * @param	string  $api_module		Module
	 * @param	string	$forum_ids		Comma separated list of forum ids
	 * @param	bool	$view_as_guest	Treat user as a guest
	 * @return	string	xml
	 */	
	public function fetchForums( $api_key, $api_module, $forum_ids, $view_as_guest )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$api_key       = IPSText::md5Clean( $api_key );
		$api_module    = IPSText::parseCleanValue( $api_module );
		$forum_ids     = ( $forum_ids ) ? explode( ',', IPSText::parseCleanValue( $forum_ids ) ) : null;
		$view_as_guest = intval( $view_as_guest );
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $api_key, $api_module, 'fetchForums' ) !== FALSE )
		{
			//-----------------------------------------
			// Add log
			//-----------------------------------------
			
			if ( ipsRegistry::$settings['xmlrpc_log_type'] != 'failed' )
			{
				$this->registry->DB()->insert( 'api_log', array(   'api_log_key'     => $api_key,
																	'api_log_ip'      => $_SERVER['REMOTE_ADDR'],
																	'api_log_date'    => time(),
																	'api_log_query'   => $this->classApiServer->raw_request,
																	'api_log_allowed' => 1 ) );
			}
			
			//-----------------------------------------
			// Get some classes
			//-----------------------------------------

			ipsRegistry::getAppClass( 'forums' );

			//-----------------------------------------
			// Fetch forum list
			//-----------------------------------------
			
			$return	= array();
			
			foreach( $forum_ids as $id )
			{
				$return[]	= $this->registry->getClass('class_forums')->forumsFetchData( $id );
			}
			
			//-----------------------------------------
			// Return the data
			//-----------------------------------------
		
			$this->classApiServer->apiSendReply( $return );
			exit();
		}
	}
	
	/**
	 * Returns topics based on request params
	 * 
	 * @param	string  $api_key		Authentication Key
	 * @param	string  $api_module		Module
	 * @param	string	$forum_ids		Comma separated list of forum ids
	 * @param	string	$order_field	DB field to order by
	 * @param	string	$order_by		One of "asc" or "desc"
	 * @param	integer	$offset			Start point offset for results
	 * @param	integer	$limit			Number of results to pull
	 * @param	bool	$view_as_guest	Treat user as a guest
	 * @return	string	xml
	 */	
	public function fetchTopics( $api_key, $api_module, $forum_ids, $order_field, $order_by, $offset, $limit, $view_as_guest )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$api_key       = IPSText::md5Clean( $api_key );
		$api_module    = IPSText::parseCleanValue( $api_module );
		$forum_ids 	   = IPSText::parseCleanValue( $forum_ids );
		$order_field   = IPSText::parseCleanValue( $order_field );
		$order_by      = ( strtolower( $order_by ) == 'asc' ) ? 'asc' : 'desc';
		$offset		   = intval( $offset );
		$limit		   = intval( $limit );
		$view_as_guest = intval( $view_as_guest );
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $api_key, $api_module, 'fetchTopics' ) !== FALSE )
		{
			//-----------------------------------------
			// Add log
			//-----------------------------------------
			
			if ( ipsRegistry::$settings['xmlrpc_log_type'] != 'failed' )
			{
				$this->registry->DB()->insert( 'api_log', array(   'api_log_key'     => $api_key,
																	'api_log_ip'      => $_SERVER['REMOTE_ADDR'],
																	'api_log_date'    => time(),
																	'api_log_query'   => $this->classApiServer->raw_request,
																	'api_log_allowed' => 1 ) );
			}
			
			//-----------------------------------------
			// Get API classes
			//-----------------------------------------

			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . '/api/forums/api_topic_view.php', 'apiTopicView' );
			$topic_view	 = new $classToLoad();
			
			//-----------------------------------------
			// Fetch topic list
			//-----------------------------------------
			
			$topic_view->topic_list_config['order_field']	= $order_field;
			$topic_view->topic_list_config['order_by']		= $order_by;
			$topic_view->topic_list_config['forums']		= $forum_ids;
			$topic_view->topic_list_config['offset']		= $offset;
			$topic_view->topic_list_config['limit']			= $limit;
			
			$topics = $topic_view->return_topic_list_data( $view_as_guest );
			
			//-----------------------------------------
			// Return the data
			//-----------------------------------------
		
			$this->classApiServer->apiSendReply( $topics );
			exit();
		}
	}

	/**
	 * Checks to see if the request is allowed
	 * 
	 * @param	string	$api_key		Authenticate Key
	 * @param	string	$api_module		Module
	 * @param	string	$api_function	Function 
	 * @return	string	Error message, if any
	 */
	protected function __authenticate( $api_key, $api_module, $api_function )
	{
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( $this->api_user['api_user_id'] )
		{
			$this->api_user['_permissions'] = unserialize( stripslashes( $this->api_user['api_user_perms'] ) );
			
			if ( $this->api_user['_permissions'][ $api_module ][ $api_function ] == 1 )
			{
				return TRUE;
			}
			else
			{
				$this->registry->DB()->insert( 'api_log', array(   'api_log_key'     => $api_key,
																	'api_log_ip'      => $_SERVER['REMOTE_ADDR'],
																	'api_log_date'    => time(),
																	'api_log_query'   => $this->classApiServer->raw_request,
																	'api_log_allowed' => 0 ) );
				
				$this->classApiServer->apiSendError( '200', "API Key {$api_key} does not have permission for {$api_module}/{$api_function}" );

				return FALSE;
			}
		}
		else
		{
			$this->registry->DB()->insert( 'api_log', array(   'api_log_key'     => $api_key,
																'api_log_ip'      => $_SERVER['REMOTE_ADDR'],
																'api_log_date'    => time(),
																'api_log_query'   => $this->classApiServer->raw_request,
																'api_log_allowed' => 0 ) );
			
			$this->classApiServer->apiSendError( '100', "API Key {$api_key} does not have permission for {$api_module}/{$api_function}" );
																																						
			return FALSE;
		}
	}

}