<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Notifications class for reported content
 * Last Updated: $LastChangedDate: 2012-05-30 13:28:08 -0400 (Wed, 30 May 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @author		Based on original "Report Center" by Luke Scott
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10824 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class reportNotifications
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
	 * Messenger object
	 *
	 * @var		object
	 */	
	protected $messenger;
	
	/**
	 * Data for the members
	 *
	 * @var		array
	 */	
	public $my_data;

	/**
	 * Data for the reported content
	 *
	 * @var		array
	 */	
	public $my_report_data;
	
	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Make object
		//-----------------------------------------
		
		$this->registry 	= $registry;
		$this->DB	    	= $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->member   	= $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache		= $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		$this->lang			= $this->registry->getClass('class_localization');

		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'members' ) . '/sources/classes/messaging/messengerFunctions.php', 'messengerFunctions', 'members' );
		$this->messenger	= new $classToLoad( $this->registry );
	}
	
	/**
	 * Initialize library
	 *
	 * @param	array 		Member data
	 * @param	array 		Reported content	
	 * @return	@e void
	 */
	public function initNotify( $data, $report_data )
	{
		$this->my_data			= $data;
		$this->my_report_data	= $report_data;
	}
	
	/**
	 * Send the notifications for comments/replies to a report
	 *
	 * @param	string	$comment	Comment string
	 * @return	@e void
	 */
	public function sendReplyNotifications( $postContent )
	{
		//-----------------------------------------
		// Notifications library
		//-----------------------------------------
		
		$classToLoad		= IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
		$notifyLibrary		= new $classToLoad( $this->registry );
		
		$_memberIds		= array();
		
		foreach( $this->my_data as $_data )
		{
			$_memberIds[]	= $_data['member_id'];
		}

		$_memberData	= IPSMember::load( $_memberIds );
		
		foreach( $this->my_data as $user )
		{
			//-----------------------------------------
			// Don't send notification to self
			//-----------------------------------------
			
			if( $user['member_id'] == $this->memberData['member_id'] )
			{
				continue;
			}

			$user	= array_merge( $user, $_memberData[ $user['member_id'] ] );

			IPSText::getTextClass( 'email' )->getTemplate( "report_reply", $user['language'] );
			
			IPSText::getTextClass( 'email' )->buildMessage( array(
																'MOD_NAME'	=> $user['members_display_name'],
																'COMMENTER'	=> $this->memberData['members_display_name'],
																'LINK'		=> $this->registry->getClass('reportLibrary')->processUrl( $this->my_report_data['SAVED_URL'], $this->my_report_data['SEOTITLE'], $this->my_report_data['TEMPLATE'] ),
																'REPORTLINK'=> $this->settings['base_url'] . 'app=core&module=reports&do=show_report&rid=' . $this->my_report_data['REPORT_INDEX'],
																'COMMENT'	=> $postContent,
																	)
															);

			$_subject	= sprintf(
									$this->lang->words['subject_reportreply'],
									$this->registry->output->buildSEOUrl( 'showuser=' . $this->memberData['member_id'], 'public', $this->memberData['members_seo_name'], 'showuser' ), 
									$this->memberData['members_display_name'],
									$this->settings['base_url'] . 'app=core&module=reports&do=show_report&rid=' . $this->my_report_data['REPORT_INDEX'],
									$this->registry->getClass('reportLibrary')->processUrl( $this->my_report_data['SAVED_URL'], $this->my_report_data['SEOTITLE'], $this->my_report_data['TEMPLATE'] )
									);
			$notifyLibrary->setMember( $user );
			$notifyLibrary->setFrom( $this->memberData );
			$notifyLibrary->setNotificationKey( 'report_center' );
			$notifyLibrary->setNotificationUrl( $this->settings['base_url'] . 'app=core&module=reports&do=show_report&rid=' . $this->my_report_data['REPORT_INDEX'] );
			$notifyLibrary->setNotificationText( IPSText::getTextClass('email')->message );
			$notifyLibrary->setNotificationTitle( $_subject );
			
			try
			{
				$notifyLibrary->sendNotification();
			}
			catch( Exception $e ){}
		}

		$this->_buildRSSFeed( $this->my_data, $this->my_report_data );
	}
	
	/**
	 * Send the notifications
	 *
	 * @return	@e void
	 */
	public function sendNotifications()
	{
		/* Notifications library */
		$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
		$notifyLibrary	= new $classToLoad( $this->registry );
		
		$_memberIds		= array();
		
		foreach( $this->my_data as $_data )
		{
			$_memberIds[]	= $_data['member_id'];
		}

		$_memberData	= IPSMember::load( $_memberIds );
		
		foreach( $this->my_data as $user )
		{
			//-----------------------------------------
			// Don't send notification to self
			//-----------------------------------------
			
			if( $user['member_id'] == $this->memberData['member_id'] )
			{
				continue;
			}
			
			$user	= array_merge( $user, $_memberData[ $user['member_id'] ] );
			
			IPSText::getTextClass('email')->getTemplate( "report_emailpm", $user['language'], 'public_reports', 'core' );
			
			IPSText::getTextClass('email')->buildMessage( array(
																'MOD_NAME'	=> $user['members_display_name'],
																'USERNAME'	=> $this->memberData['members_display_name'],
																'LINK'		=> $this->registry->getClass('reportLibrary')->processUrl( $this->my_report_data['SAVED_URL'], $this->my_report_data['SEOTITLE'], $this->my_report_data['TEMPLATE'] ),
																'REPORTLINK'=> $this->settings['base_url'] . 'app=core&module=reports&do=show_report&rid=' . $this->my_report_data['REPORT_INDEX'],
																'REPORT'	=> $this->my_report_data['REPORT'],
																	)
															);

			$_subject	= sprintf(
									$this->lang->words['subject_report'],
									$this->registry->output->buildSEOUrl( 'showuser=' . $this->memberData['member_id'], 'public', $this->memberData['members_seo_name'], 'showuser' ), 
									$this->memberData['members_display_name'],
									$this->settings['base_url'] . 'app=core&module=reports&do=show_report&rid=' . $this->my_report_data['REPORT_INDEX'],
									$this->registry->getClass('reportLibrary')->processUrl( $this->my_report_data['SAVED_URL'], $this->my_report_data['SEOTITLE'], $this->my_report_data['TEMPLATE'] )
									);
			
			$notifyLibrary->setMember( $user );
			$notifyLibrary->setFrom( $this->memberData );
			$notifyLibrary->setNotificationKey( 'report_center' );
			$notifyLibrary->setNotificationUrl( $this->settings['base_url'] . 'app=core&module=reports&do=show_report&rid=' . $this->my_report_data['REPORT_INDEX'] );
			$notifyLibrary->setNotificationText( IPSText::getTextClass('email')->message );
			$notifyLibrary->setNotificationTitle( $_subject );
			
			try
			{
				$notifyLibrary->sendNotification();
			}
			catch( Exception $e ){}
		}

		$this->_buildRSSFeed( $this->my_data, $this->my_report_data );
	}

	/**
	 * Build a private RSS feed for the member to monitor reports
	 *
	 * @return	@e void
	 */
	protected function _buildRSSFeed( $data=array(), $report_data )
	{
		//-----------------------------------------
		// Check member ids
		//-----------------------------------------
		
		$ids = array();

		if( is_array($data) AND count($data) )
		{
			foreach( $data as $user )
			{
				$ids[] = intval($user['member_id']);
			}
		}
		
		if( count( $ids ) == 0 )
		{
			return;
		}
		
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$rssClassToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classRss.php', 'classRss' );
		
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_reports' ), 'core' );
		
		//-----------------------------------------
		// Get status data
		//-----------------------------------------
		
		$status	= array();
		$active	= array( 0 );
		
		$this->DB->build( array( 'select' 	=> 'status, is_new, is_complete', 
								 'from'		=> 'rc_status', 
								 'where'	=> "is_new=1 OR is_complete=1 OR is_active=1",
								) 		);
		$this->DB->execute();

		while( $row = $this->DB->fetch() )
		{
			if( $row['is_new'] == 1 )
			{
				$status['new'] = $row['status'];
			}
			elseif( $row['is_complete'] == 1 )
			{
				$status['complete'] = $row['status'];
			}
			
			if( $row['is_active'] )
			{
				$active[]	= $row['status'];
			}
		}
		
		//-----------------------------------------
		// Now we need to find all open reports
		//-----------------------------------------
		
		$_reports	= array();
		
		$this->DB->build( array(
								'select'	=> 'i.*',
								'from'		=> array( 'rc_reports_index' => 'i' ),
								'where'		=> 'i.status IN(' . implode( ',', $active ) . ')',
								'add_join'	=> array(
													array(
														'from'		=> array( 'rc_status' => 's' ),
														'where'		=> 's.status=i.status',
														'type'		=> 'left',
														),
													array(
														'select'	=> 'c.my_class, c.mod_group_perm, c.app',
														'from'		=> array( 'rc_classes' => 'c' ),
														'where'		=> 'c.com_id=i.rc_class',
														'type'		=> 'left',
														),
													)
						)		);
		$outer = $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			//-----------------------------------------
			// Skip deleted plugins
			//-----------------------------------------
			
			if( $r['my_class'] == '' )
			{
				continue;
			}

			//-----------------------------------------
			// Fix stuff....this is hackish :(
			//-----------------------------------------
			
			if( $r['my_class'] == 'post' )
			{
				$r['FORUM_ID']	= $r['exdat1'];
			}
			
			//-----------------------------------------
			// Get notify list
			//-----------------------------------------
			
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( $r['app'] ) . '/extensions/reportPlugins/' . $r['my_class'] . '.php', $r['my_class'] . '_plugin', $r['app'] );
			$object			= new $classToLoad( $this->registry );
			
			$notify			= $object->getNotificationList( IPSText::cleanPermString( $r['mod_group_perm'] ), $r );
			
			$_reports[]		= array( 'data' => $r, 'notify' => $notify );
		}

		//-----------------------------------------
		// Now, we loop over each of the member ids
		//-----------------------------------------
		
		foreach( $ids as $id )
		{
			if( !$id )
			{
				continue;
			}

			//-----------------------------------------
			// Clear out for new RSS doc and add channel
			//-----------------------------------------
			
			$rss		=  new $rssClassToLoad();
			$channel_id	= $rss->createNewChannel( array( 'title'			=> $this->lang->words['rss_feed_title'],
															'link'			=> $this->settings['board_url'],
															'description'	=> $this->lang->words['reports_rss_desc'],
															'pubDate'		=> $rss->formatDate( time() )
												)		);

			//-----------------------------------------
			// What reports can we access?
			//-----------------------------------------
			
			if( count($_reports) )
			{
				foreach( $_reports as $_report )
				{
					$pass	= false;
					
					if( is_array($_report['notify']) AND count($_report['notify']) )
					{
						foreach( $_report['notify'] as $memberAccount )
						{
							if( $memberAccount['mem_id'] == $id )
							{
								$pass = true;
								break;
							}
						}
					}
					
					if( $pass )
					{
						$url = $this->registry->getClass('reportLibrary')->processUrl( str_replace( '&amp;', '&', $_report['data']['url'] ) );
						
						$rss->addItemToChannel( $channel_id, array( 'title'			=> $url,
																	'link'			=> $url,
																	'description'	=> $_report['data']['title'],
																	'content'		=> $_report['data']['title'],
																	'pubDate'		=> $rss->formatDate( $_report['data']['date_updated'] )
											)					);
					}
				}
			}

			$rss->createRssDocument();
	
			$this->DB->replace( 'rc_modpref', array( 'rss_cache' => $rss->rss_document ), "mem_id=" . $id, array( 'mem_id' ) );
		}
	}
}