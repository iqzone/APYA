<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Admin logs
 * Last Updated: $LastChangedDate: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_logs_spamlogs extends ipsCommand 
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
		
		$this->html			= $this->registry->output->loadTemplate('cp_skin_spamlogs');
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=logs&amp;section=spamlogs';
		$this->form_code_js	= $this->html->form_code_js	= 'module=logs&section=spamlogs';
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_logs' ) );
		
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=logs&section=spamlogs', $this->lang->words['slog_spamlogs'] );
		
		///----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'remove':
				$this->_remove();
			break;

			default:
				$this->_listCurrent();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();	
	}

	/**
	 * Remove log
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _remove()
	{
		if( $this->request['type'] == 'all' )
		{
			$this->DB->delete( 'spam_service_log' );
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
			
			$this->DB->delete( 'spam_service_log', "id IN (" . implode( ',', $ids ) . ")" );
		}
		
		$this->registry->output->silentRedirect( $this->settings['base_url']."&{$this->form_code}" );
	}
	
	/**
	 * List the current logs
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _listCurrent()
	{
		/* INIT */
		$rows		= array();
		$st			= $this->request['st'] ? intval( $this->request['st'] ) : 0;
		$perPage	= 25;
		$where		= '';
		$qs			= '';
		
		if( $this->request['search_term'] )
		{
			$where	= "log_msg LIKE '%{$this->request['search_term']}%' OR email_address LIKE '%{$this->request['search_term']}%'";
			$qs		= '&search_term=' . $this->request['search_term'];
		}
		
		/* Pagination */
		$total = $this->DB->buildAndFetch( array( 'select' => 'count(*) as logs', 'from' => 'spam_service_log', 'where' => $where ) );
		
		if( $this->request['search_term'] )
		{
			$this->registry->output->global_message	= sprintf( $this->lang->words['spamlog_sresults'], $total['logs'] );
		}

		$pageLinks = $this->registry->output->generatePagination( array( 
																		'totalItems'			=> $total['logs'],
																		'itemsPerPage'			=> $perPage,
																		'currentStartValue'		=> $st,
																		'baseUrl'				=> $this->settings['base_url'] . $this->form_code . $qs,
																)	);

		/* Query the logs */
		$this->DB->build( array( 
								'select'	=> '*',
								'from'		=> 'spam_service_log',
								'order'		=> 'log_date DESC',
								'where'		=> $where,
								'limit'		=> array( $st, $perPage ),
						)		);
		$this->DB->execute();

		while( $row = $this->DB->fetch() )
		{
			$row['_time'] = $this->registry->class_localization->getDate( $row['log_date'], 'LONG' );

			$rows[] = $row;
		}
		
		//-----------------------------------------
		// And output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->spamlogsWrapper( $rows, $pageLinks );
	}
}
