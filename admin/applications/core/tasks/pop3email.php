<?php
/**
 * @file		pop3mail.php 	Task to handle incoming emails through POP3
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: mark $
 * @since		25th June 2010
 * $LastChangedDate: 2012-05-08 11:35:49 -0400 (Tue, 08 May 2012) $
 * @version		v3.3.3
 * $Revision: 10705 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		task_item
 * @brief		Task to handle incoming emails through POP3
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
	 * @var		$settings
	 * @var		$lang
	 */
	protected $registry;
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
		//--------------------------------------
		// Init
		//--------------------------------------
		
		require_once( IPS_KERNEL_PATH . '/pop3class/pop3.php' );/*noLibHook*/
		$this->pop3 = new pop3_class;
		
		$this->pop3->hostname							= $this->settings['pop3_server'];
		$this->pop3->port								= $this->settings['pop3_port'];
		$this->pop3->tls								= $this->settings['pop3_tls'];
		$this->pop3->realm							= '';
		$this->pop3->workstation						= '';
		$this->pop3->authentication_mechanism			= 'USER';
		$this->pop3->debug							= FALSE;
		$this->pop3->html_debug						= FALSE;
		$this->pop3->join_continuation_header_lines	= FALSE;
		
		$user					= $this->settings['pop3_user'];
		$password				= $this->settings['pop3_password'];
		$apop					= FALSE;
		
		//--------------------------------------
		// Connect and login
		//--------------------------------------
		
		$open = $this->pop3->Open();
		if ( $open != '' )
		{
			return;
		}
		
		$login = $this->pop3->Login( $user, $password, $apop );
		if ( $login != '' )
		{
			return;
		}
		
		//--------------------------------------
		// Any messages?
		//--------------------------------------
		
		$messages = NULL;
		$size = NULL;
		$this->pop3->Statistics( $messages, $size );
				
		if ( !$messages )
		{
			$this->pop3->Close();
			
			/* Log to log table - modify but dont delete */
			$this->class->appendTaskLog( $this->task, $this->lang->words['task_pop3email'] );
			
			/* Unlock Task: DO NOT MODIFY! */
			$this->class->unlockTask( $this->task );
			
			return;
		}
		
		//--------------------------------------
		// Well get them then!
		//--------------------------------------
		
		require_once ( IPS_ROOT_PATH . 'sources/classes/incomingEmail/incomingEmail.php' );
		
		$result = $this->pop3->ListMessages( '', TRUE );
						
		if ( is_array( $result ) and !empty( $result ) )
		{
			foreach ( $result as $id => $messageID )
			{
				$headers = NULL;
				$body = NULL;
				$getMessage = $this->RetrieveMessage( $id );
				if ( $getMessage === NULL )
				{
					continue;
				}
				
				incomingEmail::parse( $getMessage );
				
				// And delete
				$this->pop3->DeleteMessage( $id );
			}
		}
		
		//--------------------------------------
		// Log off
		//--------------------------------------
		
		$this->pop3->Close();
		
		/* Log to log table - modify but dont delete */
		$this->class->appendTaskLog( $this->task, $this->lang->words['task_pop3email'] );
		
		/* Unlock Task: DO NOT MODIFY! */
		$this->class->unlockTask( $this->task );
	}
	
	protected function RetrieveMessage( $id )
	{
		if( $this->pop3->PutLine( "RETR {$id}" ) == 0 )
		{
			return NULL;
		}
		
		$response = $this->pop3->GetLine();
		if ( substr( $response, 0, 3 ) != '+OK' )
		{
			return NULL;
		}
		
		$message = '';
		while ( TRUE == TRUE )
		{
			$line = $this->pop3->GetLine();
			if ( $line == '.' )
			{
				break;
			}
			$message .= $line . "\n";
		}
		
		return $message;
	}
}