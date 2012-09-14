<?php
/**
 * @file		removelocked.php 	Task to remove locked members from the ACP list who are already unlocked
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		-
 * $LastChangedDate: 2011-02-08 17:20:18 -0500 (Tue, 08 Feb 2011) $
 * @version		v3.3.3
 * $Revision: 7750 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		task_item
 * @brief		Task to remove locked members from the ACP list who are already unlocked
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
		
		if ( $this->settings['ipb_bruteforce_attempts'] AND $this->settings['ipb_bruteforce_unlock'] )
		{
			/* Init vars */
			$count		= 0;
			$canUnlock	= array();
			
			$this->DB->build( array( 'select'	=> 'member_id, failed_logins, failed_login_count',
									 'from'		=> 'members',
									 'where'	=> 'failed_login_count > 0 AND failed_logins ' . $this->DB->buildIsNull( false ),
							 )		);
			$outer = $this->DB->execute();
		
			while( $r = $this->DB->fetch($outer) )
			{
				$used_ips 		= array();
				$this_attempt 	= array();
				$oldest			= 0;
				$newest			= 0;
				
				if( $r['failed_logins'] )
				{
					$failed_logins = explode( ",", IPSText::cleanPermString( $r['failed_logins'] ) );
					
					if( is_array($failed_logins) AND count($failed_logins) )
					{
						sort($failed_logins);
						
						foreach( $failed_logins as $attempt )
						{
							$this_attempt = explode( "-", $attempt );
							
							if( isset($used_ips[ $this_attempt[1] ]) AND $this_attempt[0] > $used_ips[ $this_attempt[1] ] )
							{
								$used_ips[ $this_attempt[1] ] = $this_attempt[0];
							}
						}

						$totalLocked	= count($used_ips);
						$totalToUnlock	= 0;
						
						if( count($used_ips) )
						{
							foreach( $used_ips as $ip => $timestamp )
							{
								if( $timestamp < time() - ($this->settings['ipb_bruteforce_period']*60) )
								{
									$totalToUnlock++;
								}
							}
						}
						
						if( $totalToUnlock == $totalLocked )
						{
							$canUnlock[] = $r['member_id'];
						}
					}
					else
					{
						$canUnlock[]	= $r['member_id'];
					}
				}
				else
				{
					$canUnlock[]	= $r['member_id'];
				}
			}
			
			if( count($canUnlock) )
			{
				$this->DB->update( 'members', array( 'failed_logins' => null, 'failed_login_count' => 0 ), 'member_id IN(' . implode( ',', $canUnlock ) . ')' );
			}

			//-----------------------------------------
			// Log to log table - modify but dont delete
			//-----------------------------------------
			
			$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_global' ), 'core' );
			$this->class->appendTaskLog( $this->task, sprintf( $this->lang->words['task_removelocked'], count($canUnlock) ) );
		}
		
		//-----------------------------------------
		// Unlock Task: DO NOT MODIFY!
		//-----------------------------------------
		
		$this->class->unlockTask( $this->task );
	}
}