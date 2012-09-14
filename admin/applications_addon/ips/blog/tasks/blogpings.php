<?php
/**
 * @file		blogpings.php 	Task to ping the configured services about chosen blogs 
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		-
 * $LastChangedDate: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * @version		v2.5.2
 * $Revision: 4 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		task_item
 * @brief		Task to ping the configured services about chosen blogs
 *
 */
class task_item
{
	/**
	 * String that contains the error message
	 * from an XML RPC request
	 * 
	 * @var		$ping_errormsg
	 */
	protected $ping_errormsg = '';
	
	/**
	 * Array that holds the data
	 * for all the ping services
	 * 
	 * @var		$pingservices
	 */
	protected $pingservices  = array();
	
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
		/* Enabled? */
		if ( $this->settings['blog_allow_pingblogs'] )
		{
			$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_blog' ), 'blog' );
			
			/* Load XML RPC class */
			require_once( IPS_KERNEL_PATH . 'classXmlRpc.php');/*noLibHook*/
			$this->xmlrpc = new classXmlRpc();
			
			/* Init vars */
			$i		= 0;
			$error	= 0;
			
			//-----------------------------------------
			// Get SQL query
			//-----------------------------------------
			$this->DB->build( array( 
										'select'	=> '*',
										'from'		=> 'blog_updatepings',
										'where'		=> 'ping_active=1 and ping_time<'.time(),
										'order'		=> 'ping_id ASC',
										'limit'		=> array(0, 3)
							)	);
			$qid = $this->DB->execute();
			
			while( $ping = $this->DB->fetch( $qid ) )
			{
				$ping_id = $ping['ping_id'];

				// Load the Blog
				$blog = $this->DB->buildAndFetch( array( 
														'select'	=> 'blog_id, blog_name, blog_settings', 
														'from'		=> 'blog_blogs', 
														'where'		=> "blog_id = {$ping['blog_id']}" 
												)	);
				if( ! $blog['blog_id'] )
				{
					$this->DB->delete( 'blog_updatepings', "ping_id={$ping_id}" );
				}
				else
				{
					$blog['blog_settings'] = unserialize( $blog['blog_settings'] );

					$blog['blog_url'] = $this->settings['board_url']."/index.".$this->settings['php_ext']."&app=blog&module=display&section=blog&blogid=".$blog['blog_id'];
					$entry_url = $blog['blog_url'].'&showentry='.$ping['entry_id'];
					$rss_url = $blog['blog_url'].'&req=syndicate';

					//-----------------------------------------
					// Ping the service...
					//-----------------------------------------
					if( ! $this->sendPing( $ping['ping_service'], $blog['blog_name'], $blog['blog_url'], $entry_url, $rss_url ) )
					{
						$error++;
						if ( $ping['ping_tries'] < 5 )
						{
							$ping['ping_tries']++;
							$ping['ping_time'] = time()+900;
							unset( $ping['ping_id'] );
							$this->DB->insert( 'blog_updatepings', $ping );
						}
						else
						{
							$this->class->appendTaskLog( $this->task, sprintf( $this->lang->words['task_blogpings'], $this->ping_errormsg ) );
						}
					}

					//-----------------------------------------
					// Delete from table
					//-----------------------------------------
					$this->DB->delete( 'blog_updatepings', "ping_id={$ping_id}" );
					
					$i++;
				}
			}

			if( $i == 0 and $error == 0 )
			{
				$done = $this->DB->buildAndFetch( array( 'select' => 'count(*) as num_left', 'from' => 'blog_updatepings', 'where' => 'ping_active=1' ) );
				
				if ( $done['num_left'] )
				{				
					//-----------------------------------------
					// We have delayed tasks, wait 15 mins
					//-----------------------------------------
					$this_task = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'task_manager', 'where' => "task_key='blogpings'" ) );
					$newdate = $this->class->generateNextRun($this_task);
					$newdate += 900;
					$this->DB->update( 'task_manager', array( 'task_next_run' => $newdate ), "task_id=".$this_task['task_id'] );
					$this->class->saveNextRunStamp();
				}				
				else
				{
					//-----------------------------------------
					// We are done; disable task
					//-----------------------------------------
					$this->DB->update( 'task_manager', array( 'task_enabled' => 0 ), "task_key='blogpings'" );
				}
			}
			else
			{
				//-----------------------------------------
				// Log to log table - modify but dont delete
				//-----------------------------------------
				$this->class->appendTaskLog( $this->task, sprintf( $this->lang->words['task_blogpings1'], $i, $error ) );
			}				
		}

		//-----------------------------------------
		// Unlock Task: DO NOT MODIFY!
		//-----------------------------------------

		$this->class->unlockTask( $this->task );
	}
	
	/**
	 * Send a ping to the selected service
	 * using an XML RPC request 
	 *
	 * @param	string	$service		Ping Service
	 * @param	string	$blog_name		Name of the blog
	 * @param	string	$blog_url		URL of the blog
	 * @param	string	$entry_url		URL of the entry [Optional]
	 * @param	string	$rss_url		RSS URL of the blog [Optional]
	 * @param	string	$category		Category [Optional]
	 * @return	bool
	 * 
	 */
	public function sendPing( $service, $blog_name, $blog_url, $entry_url='', $rss_url='', $category='' )
	{
		if( !is_array($this->pingservices) || !count($this->pingservices) )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'blog_pingservices', 'where' => 'blog_service_enabled=1' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$this->pingservices[ $r['blog_service_key'] ] = $r;
			}
		}

		if ( !$this->pingservices[$service]['blog_service_id'] )
		{
			$this->ping_errormsg = $this->lang->words['task_ping_error1'];
			return false;
		}
			
		$xmlmsg = $this->xmlrpc->header."\n";
		$xmlmsg .= "\t<methodCall>\n";
		$xmlmsg .= "\t\t<methodName>{$this->pingservices[$service]['blog_service_methodname']}</methodName>\n";
		$xmlmsg .= "\t\t\t<params>\n";
		$xmlmsg .= "\t\t\t\t<param>\n";
		$xmlmsg .= "\t\t\t\t\t<value>{$blog_name}</value>\n";
		$xmlmsg .= "\t\t\t</param>\n";
		$xmlmsg .= "\t\t\t<param>\n";
		$xmlmsg .= "\t\t\t\t<value>{$blog_url}</value>\n";
		$xmlmsg .= "\t\t\t</param>\n";
		if ( $this->pingservices[$service]['blog_service_extended'] )
		{
			$xmlmsg .= "\t\t\t<param>\n";
			$xmlmsg .= "\t\t\t\t<value>{$entry_url}</value>\n";
			$xmlmsg .= "\t\t\t</param>\n";
			$xmlmsg .= "\t\t\t<param>\n";
			$xmlmsg .= "\t\t\t\t<value>{$rss_url}</value>\n";
			$xmlmsg .= "\t\t\t</param>\n";
			$xmlmsg .= "\t\t\t<param>\n";
			$xmlmsg .= "\t\t\t\t<value>{$category}</value>\n";
			$xmlmsg .= "\t\t\t</param>\n";
		}
		$xmlmsg .= "\t\t</params>\n";
		$xmlmsg .= "\t</methodCall>";

		$host = substr( $this->pingservices[$service]['blog_service_host'], 1, 7 ) == 'http://' ? $this->pingservices[$service]['blog_service_host'] : 'http://'.$this->pingservices[$service]['blog_service_host'];
		$path = $this->pingservices[$service]['blog_service_path'];
		if ( $this->xmlrpc->post( $host.$path, $xmlmsg ) )
		{
			if ( !isset( $this->xmlrpc->xmlrpc_params[0]['flerror'] ) )
			{
				$this->ping_errormsg = $this->lang->words['task_ping_error2'];
				return false;
			}
			else
			{
				if ( $this->xmlrpc->xmlrpc_params[0]['flerror'] )
				{
					$this->ping_errormsg = sprintf( $this->lang->words['task_ping_error3'], $this->xmlrpc->xmlrpc_params[0]['message'] );
					return false;
				}
				else
				{
					$this->ping_errormsg = '';
					return true;
				}
			}
		}
		else
		{
			$this->ping_errormsg = sprintf( $this->lang->words['task_ping_error3'], $this->xmlrpc->errors[0] );
			return false;
		}
	}
}