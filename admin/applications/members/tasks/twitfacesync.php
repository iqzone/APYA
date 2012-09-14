<?php
/**
 * @file		twitfacesync.php 	Task to update data from Twitter and Facebook (photos, statuses, etc)
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: mmecham $
 * @since		-
 * $LastChangedDate: 2012-05-07 11:35:06 -0400 (Mon, 07 May 2012) $
 * @version		v3.3.3
 * $Revision: 10696 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		task_item
 * @brief		Task to update data from Twitter and Facebook (photos, statuses, etc)
 *
 */
class task_item
{
	/**
	 * Object that stores the parent task manager class
	 *
	 * @var		$class
	 */
	protected $class;
	
	/**
	 * Array that stores the task data
	 *
	 * @var		$task
	 */
	protected $task = array();
	
	/**
	 * Registry Object Shortcuts
	 *
	 * @var		$registry
	 * @var		$DB
	 * @var		$settings
	 * @var		$lang
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $lang;
	
	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @param	object		$class			Task manager class object
	 * @param	array		$task			Array with the task data
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $class, $task )
	{
		/* Make registry objects */
		$this->registry	= $registry;
		$this->DB		= $this->registry->DB();
		$this->settings	=& $this->registry->fetchSettings();
		$this->lang		= $this->registry->getClass('class_localization');
		
		$this->class	= $class;
		$this->task		= $task;
	}
	
	/**
	 * Run this task
	 *
	 * @return	@e void
	 */
	public function runTask()
	{
		$counter = 0;
		$_sync   = time() - 10800;
		$_active = time() - ( 86400 * 14 );
		$members = array();
		
		if ( IPSLib::fbc_enabled() !== TRUE && IPSLib::twitter_enabled() !== TRUE )
		{
			$this->class->unlockTask( $this->task );
			return;
		}
		
		//-----------------------------------------
		// Fetch members / Edit - only complete members
		// @link http://community.invisionpower.com/tracker/issue-29269-display-names-not-always-shown-in-status-updates
		//-----------------------------------------
		
		$this->DB->build( array( 'select'	=> 'm.member_id',
								 'from'		=> array( 'members' => 'm' ),
								 'where'	=> '( (m.twitter_id != \'\' AND m.tc_lastsync < ' . $_sync .') OR (m.fb_uid > 0 AND m.fb_lastsync < ' . $_sync . ') ) AND p.partial_id ' . $this->DB->buildIsNull(true) . ' AND m.last_visit > ' . $_active,
								 'order'	=> 'm.last_visit DESC',
								 'limit'	=> array( 0, 30 ),
								 'add_join'	=> array(
								 					array(
								 						'from'	=> array( 'members_partial' => 'p' ),
								 						'where'	=> 'p.partial_member_id=m.member_id',
								 						'type'	=> 'left',
								 						)
								 					)
						 )		);
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$members[] = $row['member_id'];
		}
		
		$members = IPSMember::load( $members );
		
		foreach( $members as $member )
		{
			//-----------------------------------------
			// Facebook Sync
			//-----------------------------------------
		
			if ( IPSLib::fbc_enabled() === TRUE )
			{ 
				if ( ! empty( $member['fb_uid'] ) )
				{
					/* We have a linked member and options, so check if they haven't sync'd in 24 hours and have been active in the past 90 days... */
					try
					{
						$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/facebook/connect.php', 'facebook_connect' );
						$facebook	 = new $classToLoad( ipsRegistry::instance(), null, null, true );
					
						$_member = $facebook->syncMember( $member, $member['fb_token'], $member['fb_uid'] );
						
						$counter++;
						
						if ( $_member AND is_array( $_member ) )
						{
							$member = $_member;
							unset( $_member );
						}
					}
					catch( Exception $error )
					{
						$msg = $error->getMessage();

						switch( $msg )
						{
							case 'NOT_LINKED':
							case 'NO_MEMBER':
							case 'FACEBOOK_NO_APP_ID':
							break;
						}
					}
				}
			}
			
			//-----------------------------------------
			// Twitter Sync
			//-----------------------------------------
		
			if ( IPSLib::twitter_enabled() === TRUE )
			{ 
				if ( ! empty( $member['twitter_id'] ) and ! empty( $member['tc_bwoptions'] ) )
				{
					/* We have a linked member and options, so check if they haven't sync'd in 3 hours and have been active in the past 90 days... */
					$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/twitter/connect.php', 'twitter_connect' );
					$twitter	 = new $classToLoad( ipsRegistry::instance() );
					
					try
					{
						$_member = $twitter->syncMember( $member );
						
						$counter++;
						
						if ( $_member AND is_array( $_member ) )
						{
							$member = $_member;
							unset( $_member );
						}
					}
					catch( Exception $error )
					{
						$msg = $error->getMessage();

						switch( $msg )
						{
							case 'NOT_LINKED':
							case 'NO_MEMBER':
							break;
						}
					}
				}
			}
		}
		
		//-----------------------------------------
		// Log to log table - modify but dont delete
		//-----------------------------------------
		
		$this->class->appendTaskLog( $this->task, "Updated " . $counter );
		
		//-----------------------------------------
		// Unlock Task: DO NOT MODIFY!
		//-----------------------------------------
		
		$this->class->unlockTask( $this->task );
	}
}