<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Error logs
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

class admin_core_logs_errorlogs extends ipsCommand 
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
		
		$this->html			= $this->registry->output->loadTemplate('cp_skin_errorlogs');
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=logs&amp;section=errorlogs';
		$this->form_code_js	= $this->html->form_code_js	= 'module=logs&section=errorlogs';
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_logs' ) );
		
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=logs&section=errorlogs', $this->lang->words['error_log_thelogs'] );
				
		///----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			default:
			case 'list':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'errorlogs_view' );
				$this->_listCurrent();
			break;
				
			case 'remove':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'errorlogs_delete' );
				$this->_remove();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();	
	}
	
	/**
	 * Remove email logs
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _remove()
	{
		if( $this->request['type'] == 'all' )
		{
			$this->DB->delete( 'error_logs' );
		}
		else
		{
			$ids = array();
		
			foreach( $this->request as $k => $v )
			{
				if ( preg_match( "/^id_(\d+)$/", $k, $match ) )
				{
					if ($this->request[ $match[0] ] )
					{
						$ids[] = $match[1];
					}
				}
			}

			$ids = IPSLib::cleanIntArray( $ids );
			
			//-----------------------------------------
			
			if( count($ids) < 1 )
			{
				$this->registry->output->showError( $this->lang->words['erlog_noneselected'], 11115 );
			}
			
			$this->DB->delete( 'error_logs', "log_id IN (" . implode( ',', $ids ) . ")" );
		}
		
		$this->registry->adminFunctions->saveAdminLog( $this->lang->words['error_log_removed'] );
		
		$this->registry->output->silentRedirect( $this->settings['base_url'] . "&{$this->form_code}" );
	}
	
	/**
	 * List the current logs
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _listCurrent()
	{
		$start = intval( $this->request['st'] ) >= 0 ? intval( $this->request['st'] ) : 0;
		
		/* Check URL parameters */
		$url_query	= array();
		$db_query	= array();
		
		if( $this->request['type'] )
		{		
			$string = IPSText::parseCleanValue( urldecode( $this->request['string'] ) );
			
			if( $string == "" )
			{
				$this->registry->output->showError( $this->lang->words['erlog_enter_sumthang_yo'], 11116 );
			}
			
			$url_query[] = 'type=' . $this->request['type'];
			$url_query[] = 'string=' . urlencode( $string );
			
			switch( $this->request['type'] )
			{
				case 'log_error':
					$db_query[]	= $this->request['match'] == 'loose'  ? "e.log_error LIKE '%{$string}%'"            : "e.log_error='{$string}'";
				break;
				
				case 'log_error_code':
					$db_query[]	= $this->request['match'] == 'loose'  ? "e.log_error_code LIKE '%{$string}%'"       : "e.log_error_code='{$string}'";
				break;
				
				case 'log_request_uri':
					$db_query[]  = $this->request['match'] == 'loose' ? "e.log_request_uri LIKE '%{$string}%'"      : "e.log_request_uri='{$string}'";
				break;
				
				case 'members_display_name':
					$db_query[]  = $this->request['match'] == 'loose' ? "m.members_display_name LIKE '%{$string}%'" : "m.members_display_name='{$string}'";
				break;				
			}
		}
		
		/* Build extra query stuff */
		$dbe = '';
		$url = '';
		
		if( count( $db_query ) )
		{
			$dbe = implode( ' AND ', $db_query );
		}
		
		if( count( $url_query ) )
		{
			$url = '&amp;' . implode( '&amp;', $url_query );
		}
		
		/* Pagination */
		$count = $this->DB->buildAndFetch( array( 
												'select'   => 'count(*) as cnt', 
												'from'     => array( 'error_logs' => 'e' ),
												'where'    => $dbe, 
												'add_join' => array(
																	array(
																			'from'   => array( 'members' => 'm' ),
																			'where'  => 'e.log_member=m.member_id',
																			'type'   => 'left',
																		)
																	)
										) );

		$links = $this->registry->output->generatePagination( array( 
																		'totalItems'		=> $count['cnt'],
																		'itemsPerPage'		=> 25,
																		'currentStartValue'	=> $start,
																		'baseUrl'			=> $this->settings['base_url'] . "&{$this->form_code}" . $url,
																	)
															);
		
		/* Query the logs */
		$this->DB->build( array( 
								'select'   => 'e.*', 
								'from'     => array( 'error_logs' => 'e' ),
								'where'    => $dbe, 
								'order'    => 'e.log_date DESC', 
								'limit'    => array( $start, 25 ),
								'add_join' => array(
													array(
															'select' => 'm.members_display_name',
															'from'   => array( 'members' => 'm' ),
															'where'  => 'e.log_member=m.member_id',
															'type'   => 'left',
														)
													)
						)	 );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$row['_date'] = $this->registry->class_localization->getDate( $row['log_date'], 'SHORT' );
			
			$rows[]	= $row;
		}
		
		$this->registry->output->html .= $this->html->errorlogsWrapper( $rows, $links );
	}
}
