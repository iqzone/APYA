<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Friends library
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @since		20th February 2002
 * @version		$Rev: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class profileFriendsLib
{
	/**
	 * Registry object
	 *
	 * @var		object
	 */	
	protected $registry;
	
	/**
	 * Database object
	 *
	 * @var		object
	 */	
	protected $DB;
	
	/**
	 * Settings object
	 *
	 * @var		object
	 */	
	protected $settings;
	
	/**
	 * Request object
	 *
	 * @var		object
	 */	
	protected $request;
	
	/**
	 * Member object
	 *
	 * @var		object
	 */	
	protected $member;
	protected $memberData;
	
	/**
	 * Is approval pending?
	 *
	 * @var		boolean
	 */
	public $pendingApproval	= false;
	
	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
		$this->registry = $registry;
		$this->DB       = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
	}
	
	/**
	 * Adds a friend to the account that is logged in or specified
	 *
	 * @param	integer	$friend_id			The friend being added to the account
	 * @param	integer	$from_id			The requesting member, defaults to current member
	 * @param	boolean	$forceApproval		Automatically approve, regardless of setting
	 * @param	boolean	$sendNotification	If false, no notification will be sent to the member being added
	 * @return	string						Error Key or blank for success
	 */
	public function addFriend( $friend_id, $from_id=0, $forceApproval=false, $sendNotification=true )
	{
		/* INIT */
		$friend_id			= intval( $friend_id );
		$from_id			= $from_id ? intval($from_id) : $this->memberData['member_id'];
		$friend				= array();
		$member				= array();
		$friends_approved	= 1;
		$message			= '';
		$subject			= '';
		$to					= array();
		$from				= array();
		$return_msg			= '';

		/* Can't add yourself */
		if( $from_id == $friend_id )
    	{
			return 'error';
    	}
		
		/* Load our friends account */
		$friend = IPSMember::load( $friend_id );
		
		/* Load our account */
		$member = IPSMember::load( $from_id );
		
    	/* This group not allowed to add friends */
    	if( !$member['g_can_add_friends'] )
    	{
    		return 'error';
    	}
		
		/* Make sure we found ourselves and our friend */
		if( ! $friend['member_id'] OR ! $member['member_id'] )
		{
			return 'error';
		}
		
		/* Are we already friends? */
		$friend_check = $this->DB->buildAndFetch( array( 
														'select'	=> 'friends_id',
														'from'		=> 'profile_friends',
														'where'		=> "friends_member_id={$from_id} AND friends_friend_id={$friend_id}" 
												)	 );
																		
		if( $friend_check['friends_id'] )
		{
			return 'pp_friend_already';
		}
		
		/* Check flood table */
		if ( $this->_canAddFriend( $from_id, $friend['member_id'] ) !== TRUE )
		{
			return 'pp_friend_timeflood';
		}
		
		/* Do we approve our friends first? */
		if( !$forceApproval AND $friend['pp_setting_moderate_friends'] )
		{
			$friends_approved		= 0;
			$this->pendingApproval	= true;
		}
		
		$_profileFriendsData = array( 
										'friends_member_id'	=> $member['member_id'],
										'friends_friend_id'	=> $friend['member_id'],
										'friends_approved'	=> $friends_approved,
										'friends_added'		=> time()
									);
		
		/* Data Hook Location */
		IPSLib::doDataHooks( $_profileFriendsData, 'profileFriendsNew' );
		
		/* Insert the friend */
		$this->DB->insert( 'profile_friends', $_profileFriendsData );

		/* Do we need to send notifications? */
		if( ! $friends_approved )
		{
			//-----------------------------------------
			// Notifications library
			//-----------------------------------------
			
			if ( $sendNotification )
			{
			
				$classToLoad		= IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
				$notifyLibrary		= new $classToLoad( $this->registry );
	
				IPSText::getTextClass('email')->getTemplate( "new_friend_request", $friend['language'] );
			
				IPSText::getTextClass( 'email' )->buildMessage( array( 
																		'MEMBERS_DISPLAY_NAME'	=> $friend['members_display_name'],
																		'FRIEND_NAME'			=> $member['members_display_name'],
																		'LINK'					=> "{$this->settings['board_url']}/index.{$this->settings['php_ext']}?app=members&amp;section=friends&amp;module=profile&amp;do=list&amp;tab=pending"
																)		);
	
				IPSText::getTextClass('email')->subject	= sprintf( 
																	IPSText::getTextClass('email')->subject, 
																	$this->registry->output->buildSEOUrl( 'showuser=' . $member['member_id'], 'public', $member['members_seo_name'], 'showuser' ), 
																	$member['members_display_name'],
																	"{$this->settings['board_url']}/index.{$this->settings['php_ext']}?app=members&amp;section=friends&amp;module=profile&amp;do=list&amp;tab=pending"
																);
																		
				$notifyLibrary->setMember( $friend );
				$notifyLibrary->setFrom( $member );
				$notifyLibrary->setNotificationKey( 'friend_request' );
				$notifyLibrary->setNotificationUrl( "{$this->settings['board_url']}/index.{$this->settings['php_ext']}?app=members&amp;section=friends&amp;module=profile&amp;do=list&amp;tab=pending" );
				$notifyLibrary->setNotificationText( IPSText::getTextClass('email')->message );
				$notifyLibrary->setNotificationTitle( IPSText::getTextClass('email')->subject );
				try
				{
					$notifyLibrary->sendNotification();
				}
				catch( Exception $e ){}
	
			}
			
			$return_msg = 'pp_friend_added_mod';
		}
		else
		{
			/* Don't notify yourself */
			if( $sendNotification and $friend['member_id'] != $this->memberData['member_id'] )
			{
				//-----------------------------------------
				// Notifications library
				//-----------------------------------------
				
				$classToLoad		= IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
				$notifyLibrary		= new $classToLoad( $this->registry );
	
				IPSText::getTextClass('email')->getTemplate( "new_friend_added", $friend['language'] );
			
				IPSText::getTextClass( 'email' )->buildMessage( array( 
																		'MEMBERS_DISPLAY_NAME'	=> $friend['members_display_name'],
																		'FRIEND_NAME'			=> $member['members_display_name'],
																		'LINK'					=> "{$this->settings['board_url']}/index.{$this->settings['php_ext']}?app=members&amp;section=friends&amp;module=profile&amp;do=list"
																)		);
	
				IPSText::getTextClass('email')->subject	= sprintf( 
																	IPSText::getTextClass('email')->subject, 
																	$this->registry->output->buildSEOUrl( 'showuser=' . $member['member_id'], 'public', $member['members_seo_name'], 'showuser' ), 
																	$member['members_display_name']
																);
																
				$notifyLibrary->setMember( $friend );
				$notifyLibrary->setFrom( $member );
				$notifyLibrary->setNotificationKey( 'friend_request' );
				$notifyLibrary->setNotificationUrl( $this->registry->output->buildSEOUrl( 'showuser=' . $member['member_id'], 'public', $member['members_seo_name'], 'showuser' ) );
				$notifyLibrary->setNotificationText( IPSText::getTextClass('email')->message );
				$notifyLibrary->setNotificationTitle( IPSText::getTextClass('email')->subject );
				try
				{
					$notifyLibrary->sendNotification();
				}
				catch( Exception $e ){}
			}

			$return_msg	= 'pp_friend_added';
		}

		/* Reache */
		$this->recacheFriends( $member );
		$this->recacheFriends( $friend );
		
		return '';
	}
	
	/**
	 * Removes a friend from the logged in account
	 *
	 * @param	integer	$friend_id	The friend being removed
	 * @param	integer	$from_id	The requesting member, defaults to current member
	 * @return	string				Error Key or blank for success
	 */	
	public function removeFriend( $friend_id, $from_id=0 )
	{
		/* INIT */		
		$friend_id	= intval( $friend_id );
		$from_id	= $from_id ? intval($from_id) : $this->memberData['member_id'];
		$friend		= array();
		$member		= array();
		
		/* Get our friend */
		$friend = IPSMember::load( $friend_id );
		
		/* Get our member */
		$member = IPSMember::load( $from_id );
		
		/* Make sure we have both ids */
		if( ! $friend['member_id'] OR ! $member['member_id'] )
		{
			return 'error';
		}
		
		/* Check for the friend */
		$friend_check = $this->DB->buildAndFetch( array( 
																'select' => 'friends_id', 
																'from'   => 'profile_friends', 
																'where'  => "friends_member_id={$from_id} AND friends_friend_id={$friend['member_id']}"
														)	 );
																		
		if( ! $friend_check['friends_id'] )
		{
			return 'error';
		}
		
		/* Remove from the db */
		$this->DB->delete( 'profile_friends', 'friends_id=' . $friend_check['friends_id'] );
		
		/* Remove from flood */
		$this->_addFloodEntry( $from_id, $friend['member_id'] );
		
		/* Recache */
		$this->recacheFriends( $member );
		$this->recacheFriends( $friend );		
	}
	
 	/**
 	 * Recaches member's friends
 	 *
 	 * @param	array	$member	Member array to recache
 	 * @return	boolean
 	 */
 	public function recacheFriends( $member )
 	{
		/* INIT */
		$friends = array();
		
		/* Check the member id */
		if( ! $member['member_id'] )
		{
			return FALSE;
		}
		
		/* Get our friends */
		$this->DB->build( array( 'select' => '*', 'from' => 'profile_friends', 'where' => 'friends_member_id=' . $member['member_id'] ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$friends[ $row['friends_friend_id'] ] = $row['friends_approved'];
		}
		
		/* Update the cache */
		IPSMember::packMemberCache( $member['member_id'], array( 'friends' => $friends ) );
		
		return TRUE;
	}
	
	/**
	 * Check to see if we can add this member
	 * Just checks flood table right now, but this can be expanded upon
	 *
	 * @param	int			Member ID
	 * @param	int			Friend ID
	 * @return	boolean
	 */
	protected function _canAddFriend( $member_id, $friend_id )
	{
		/* Clean flood table */
		$this->_cleanFloodTable();
		
		$test = $this->DB->buildAndFetch( array( 'select' => '*',
												 'from'   => 'profile_friends_flood',
												 'where'  => 'friends_member_id=' . intval( $member_id ) . ' AND friends_friend_id=' . intval( $friend_id ) ) );
												
		if ( $test['friends_member_id'] )
		{
			return FALSE;
		}
		else
		{
			return TRUE;
		}
	}
	
	/**
	 * Add entry to the flood table
	 *
	 * @param	int			Member ID
	 * @param	int			Friend ID
	 * @return	boolean
	 */
	protected function _addFloodEntry( $member_id, $friend_id )
	{
		/* Clean flood table */
		$this->_cleanFloodTable();
		
		/* Add into flood table */
		$this->DB->replace( 'profile_friends_flood', array( 'friends_member_id' => intval( $member_id ),
															'friends_friend_id' => intval( $friend_id ),
															'friends_removed'   => time() ), array( 'friends_member_id', 'friends_friend_id' ) );
	}
	
	/**
	 * Clean flood table
	 *
	 */
	protected function _cleanFloodTable()
	{
		$time = time() - ( 60 * 5 );
		
		$this->DB->delete( 'profile_friends_flood', 'friends_removed < ' . $time );
	}
}