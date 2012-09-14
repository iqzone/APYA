<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * SQL error logs
 * Last Updated: $LastChangedDate: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		27th January 2004
 * @version		$Rev: 10721 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_logs_sqlerror extends ipsCommand 
{
	/**
	 * Skin object
	 *
	 * @var		object			Skin templates
	 */
	protected $html;
	
	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		
		$this->html			= $this->registry->output->loadTemplate('cp_skin_adminlogs');
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=logs&amp;section=sqlerror';
		$this->form_code_js	= $this->html->form_code_js	= 'module=logs&section=sqlerror';
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_logs' ) );
		
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=logs&section=sqlerror', $this->lang->words['sqllog_title'] );
				
		///----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'view':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'sqlerrorlogs_view' );
				$this->_view();
			break;
				
			case 'remove':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'sqlerrorlogs_delete' );
				$this->_remove();
			break;
			
			case 'delete_all':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'sqlerrorlogs_delete' );
				$this->_remove( true );
			break;

			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'sqlerrorlogs_view' );
				$this->_listCurrent();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();	
	}
	
	/**
	 * View an SQL log
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _view()
	{
		/* INIT */
		$file = trim( $this->request['file'] );
		
		/* Check file name */
		if ( ! preg_match( "#^sql_error_log_(\d+)_(\d+)_(\d+).cgi$#", $file ) OR ! is_file( IPS_CACHE_PATH . 'cache/' . $file ) )
		{
			$this->registry->output->global_message = $this->lang->words['sqllog_nofile'];
			$this->_listCurrent();
			return;
		}
		
		/* Fetch size */
		$size = @filesize( IPS_CACHE_PATH . 'cache/' . $file );
		
		/* Fetch content */
		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classFileManagement.php', 'classFileManagement' );
		$classFileManagement = new $classToLoad();
		
		/* Get some tail! */
		$content  = $classFileManagement->tailFile( IPS_CACHE_PATH . 'cache/' . $file, 300 );
		$tailSize = IPSLib::strlenToBytes( strlen( $content ) );
		
		/* Can't believe I typed that last comment */
		$this->registry->output->html .= $this->html->sqlLogsView( $file, $size, htmlentities( $content ), $tailSize );
	}
	
	/**
	 * Remove logs by an admin
	 *
	 * @param	bool		$doAll	Whether to delete all logs or not
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _remove( $doAll=false )
	{
		//-----------------------------------------
		// Deleting all?
		//-----------------------------------------
		
		if( $doAll )
		{
			$count	= 0;

			try
			{
				foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . 'cache' ) as $file )
				{
					if ( $file->isDot() OR ! $file->isFile() )
					{
						continue;
					}
				
					if ( preg_match( "#^sql_error_log_(\d+)_(\d+)_(\d+).cgi$#", $file->getFilename() ) )
					{
						$count++;
						
						@unlink( IPS_CACHE_PATH . 'cache/' . $file->getFilename() );
					}
				}
			} catch ( Exception $e ) {}
		}
		else
		{
			$file	= trim( $this->request['file'] );
			$count	= 1;
			
			//-----------------------------------------
			// Verify filename
			//-----------------------------------------
			
			if ( ! preg_match( "#^sql_error_log_(\d+)_(\d+)_(\d+).cgi$#", $file ) OR ! is_file( IPS_CACHE_PATH . 'cache/' . $file ) )
			{
				$this->registry->output->global_message = $this->lang->words['sqllog_nofile'];
				$this->_listCurrent();
				return;
			}
			
			@unlink( IPS_CACHE_PATH . 'cache/' . $file );
		}
		
		//-----------------------------------------
		// Show list again
		//-----------------------------------------
		
		$this->registry->output->global_message = sprintf( $this->lang->words['sqllog_removed'], $count );
		$this->_listCurrent();
	}
	
	/**
	 * List the current SQL logs
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _listCurrent()
	{
		$rows        = array();
		$latestError = '';
		
		/* Got a latest? */
		if ( is_file( IPS_CACHE_PATH . 'cache/sql_error_latest.cgi' ) )
		{ 
			$unix = @filemtime( IPS_CACHE_PATH . 'cache/sql_error_latest.cgi' );
			
			if ( $unix )
			{
				$mtime = gmdate( 'dmY', $unix );
				$now   = gmdate( 'dmY', time() );
				
				if ( $mtime == $now )
				{
					$contents = htmlentities( file_get_contents( IPS_CACHE_PATH . 'cache/sql_error_latest.cgi' ) );
					
					/* Display a message */
					$latestError = sprintf( $this->lang->words['sqllog_latest'], $this->registry->class_localization->getDate( $unix, 'LONG' ), $contents );
				}
			}
		}
		
		try
		{
			foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . 'cache' ) as $file )
			{
				if ( $file->isDot() OR ! $file->isFile() )
				{
					continue;
				}
        	
				if ( preg_match( "#^sql_error_log_(\d+)_(\d+)_(\d+).cgi$#", $file->getFilename(), $matches ) )
				{
					$rows[ $file->getFilename() ] = array( 'name'   => $file->getFilename(),
									 'mtime'  => $file->getMTime(),
									 'size'   => $file->getSize() );
				}
			}
		} catch ( Exception $e ) {}

		ksort( $rows );
		
		//-----------------------------------------
		// And output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->sqllogsWrapper( $rows, $latestError );
	}
}