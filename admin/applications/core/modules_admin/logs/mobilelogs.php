<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Mobile Notification logs
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

class admin_core_logs_mobilelogs extends ipsCommand 
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
		
		$this->html			= $this->registry->output->loadTemplate('cp_skin_mobilelogs');
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=logs&amp;section=mobilelogs';
		$this->form_code_js	= $this->html->form_code_js	= 'module=logs&section=mobilelogs';
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_logs' ) );
		
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=logs&section=mobilelogs', $this->lang->words['mlog_mobilelogs'] );
				
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
			$this->DB->delete( 'mobile_notifications' );
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
			
			$this->DB->delete( 'mobile_notifications', "id IN (" . implode( ',', $ids ) . ")" );
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
		
		/* Pagination */
		$total = $this->DB->buildAndFetch( array( 'select' => 'count(*) as logs', 'from' => 'mobile_notifications' ) );
		
		$pageLinks = $this->registry->output->generatePagination( array( 
																		'totalItems'			=> $total['logs'],
																		'itemsPerPage'			=> $perPage,
																		'currentStartValue'		=> $st,
																		'baseUrl'				=> $this->settings['base_url'] . $this->form_code,
																)	);

		/* Query the logs */
		$this->DB->build( array( 
									'select'	=> 'n.*',
									'from'		=> array( 'mobile_notifications' => 'n' ),
									'order'		=> 'n.notify_date DESC',
									'limit'		=> array( $st, $perPage ),
									'add_join'	=> array(
															array(
																	'select'	=> 'm.members_display_name',
																	'from'		=> array( 'members' => 'm' ),
																	'where'		=> 'm.member_id=n.member_id',
																	'type'		=> 'left'
																)
														)
							)		);
		$this->DB->execute();

		while( $row = $this->DB->fetch() )
		{
			$row['_time'] 			= $this->registry->class_localization->getDate( $row['notify_date'], 'LONG' );
			$row['notify_title']	= strip_tags( $row['notify_title'] );
			$row['_sent']			= $row['notify_sent'] ? 'aff_tick.png' : 'aff_cross.png';
			
			$rows[] = $row;
		}
		
		//-----------------------------------------
		// And output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->mobileLogsWrapper( $rows, $pageLinks );
	}
}
