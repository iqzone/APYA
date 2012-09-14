<?php
/**
 * @file		mobile_notifications.php 	Task to send out mobile notifications
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		-
 * $LastChangedDate: 2012-05-12 18:45:30 -0400 (Sat, 12 May 2012) $
 * @version		v3.3.3
 * $Revision: 10739 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		task_item
 * @brief		Task to send out mobile notifications
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
		$licenseData = $this->registry->cache()->getCache('licenseData');
		if ( isset( $licenseData['iphoneNotifications'] ) and $licenseData['iphoneNotifications'] )
		{
	
			/* INIT */
			$maxNotificationsToProcess	= 250;
			$licenseKey					= ipsRegistry::$settings['ipb_reg_number'];
			$forum						= urlencode( $this->settings['board_name'] );
			$domain 					= urlencode( ipsRegistry::$settings['board_url'] );
			$apiBaseURL					= "http://apn-server.invisionpower.com/index.php?api=addMessageToQueue&key={$licenseKey}&forum={$forum}&domain={$domain}";
	
			/* Get the file managemnet class */
			$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classFileManagement.php', 'classFileManagement' );
			$query = new $classToLoad();
			
			/* Get waiting notifications */
			$this->DB->build( array(
									'select'		=> 'n.*',
									'from'			=> array( 'mobile_notifications' => 'n' ),
									'where'			=> 'n.notify_sent=0',
									'order'			=> 'n.notify_date ASC',
									'limit'			=> array( 0, $maxNotificationsToProcess ),
							)	);
			$e = $this->DB->execute();
			
			$_sentIds = array();
			while( $r = $this->DB->fetch( $e ) )
			{
				$this->DB->build( array( 'select' => '*', 'from' => 'mobile_device_map', 'where' => "member_id={$r['member_id']}" ) );
				$f = $this->DB->execute();
				
				while ( $device = $this->DB->fetch( $f ) )
				{			
					$message	= urlencode( strip_tags( $r['notify_title'] ) );
					
					if( ! $device['token'] || ! $message )
					{
						continue;
					}
		
					/* Query the api */
					$response = $query->getFileContents( "{$apiBaseURL}&ipsToken={$device['token']}&message={$message}&notify_url={$r['notify_url']}" );
					
					/* If the response tells us to delete this device ID, do so */
					if ( $response == 'REMOVE_DEVICE' )
					{
						$this->DB->delete( 'mobile_device_map', "token='{$device['token']}'" );
					}
					
					#IPSDebug::addLogMessage( $response, 'mobileNotifications', array( 'notifications' => $r, 'device' => $device ), true );
				}
				
				/* Save the ID */
				$_sentIds[] = $r['id'];
			}
			
			#IPSDebug::addLogMessage( implode( ',', $_sentIds ), 'mobileNotifications', false, true );
			
			/* Update the table */
			if( count( $_sentIds ) )
			{
				$this->DB->update( 'mobile_notifications', array( 'notify_sent' => 1 ), 'id IN ('.implode( ',', $_sentIds ).')' );
			}
	
			//-----------------------------------------
			// Log to log table - modify but dont delete
			//-----------------------------------------
			
			$this->class->appendTaskLog( $this->task, $this->lang->words['task_mobileNotifications'] );
			
		}
		else
		{
			//-----------------------------------------
			// Log to log table - modify but dont delete
			//-----------------------------------------
			
			$this->class->appendTaskLog( $this->task, $this->lang->words['task_mobileNotifications_badlicense'] );
		}
		
		//-----------------------------------------
		// Unlock Task: DO NOT MODIFY!
		//-----------------------------------------
		
		$this->class->unlockTask( $this->task );
	}
}