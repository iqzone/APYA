<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Task Manager
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_core_task_manualResolver extends ipsCommand
{
	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Require and run
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_taskmanager.php', 'class_taskmanager' );/*noLibHook*/
		$functions   = new $classToLoad( $registry );
		
		//-----------------------------------------
		// Check shutdown functions
		//-----------------------------------------
		
		if( IPS_USE_SHUTDOWN )
		{
			define( 'IS_SHUTDOWN', 1 );
			register_shutdown_function( array( $functions, 'runTask') );
		}
		else
		{
			$functions->runTask();
		}
		
		if( $functions->type != 'cron' && ! $_SERVER['SHELL'] )
		{
			//-----------------------------------------
			// Print out the 'blank' gif
			//-----------------------------------------
		
			ob_start();
			@header( "Content-Type: image/gif" );
			print base64_decode( "R0lGODlhAQABAID/AMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" );
		}
 	}
}