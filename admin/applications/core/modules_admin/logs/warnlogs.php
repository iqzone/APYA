<?php
/**
 * @file		warnlogs.php 	Warn logs
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		27th January 2004
 * $LastChangedDate: 2012-04-24 13:35:22 -0400 (Tue, 24 Apr 2012) $
 * @version		v3.3.3
 * $Revision: 10631 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 * @class		admin_core_logs_warnlogs
 * @brief		Warn logs
 */
class admin_core_logs_warnlogs extends ipsCommand 
{
	/**
	 * Skin object shortcut
	 *
	 * @var		$html
	 */
	public $html;
	
	/**
	 * String for the screen url bit
	 *
	 * @var		$form_code
	 */
	public $form_code    = '';
	
	/**
	 * String for the JS url bit
	 *
	 * @var		$form_code_js
	 */
	public $form_code_js = '';

	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		
		$this->html			= $this->registry->output->loadTemplate('cp_skin_warnlogs');
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=logs&amp;section=warnlogs';
		$this->form_code_js	= $this->html->form_code_js	= 'module=logs&section=warnlogs';
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_logs' ) );
		
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=logs&section=warnlogs', $this->lang->words['wlog_warn_logs'] );
				
		///----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'view':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'warnlogs_view' );
				$this->_view();
			break;
						
			case 'viewnote':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'warnlogs_view' );
				$this->_viewNote();
			break;
				
			case 'remove':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'warnlogs_delete' );
				$this->_remove();
			break;

			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'warnlogs_view' );
				$this->_listCurrent();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();	
	}
	
	/**
	 * View all logs for a given moderator
	 *
	 * @return	@e void
	 */
	protected function _view()
	{
		///----------------------------------------
		// Basic init
		//-----------------------------------------
		
		$start	= intval($this->request['st']) >= 0 ? intval($this->request['st']) : 0;
		
		/* Load reasons */
		$reasons = array();
		$this->DB->build( array( 'select' => '*', 'from' => 'members_warn_reasons', 'order' => 'wr_order' ) );
		$this->DB->execute();
		while ( $row = $this->DB->fetch() )
		{
			$reasons[ $row['wr_id'] ] = $row;
		}


		///----------------------------------------
		// No mid or search string?
		//-----------------------------------------
		
		if ( !$this->request['search_string'] AND !$this->request['mid'] )
		{
			$this->registry->output->global_message = $this->lang->words['wlog_nostring'];
			$this->_listCurrent();
			return;
		}
		
		///----------------------------------------
		// mid?
		//-----------------------------------------
		
		if ( !$this->request['search_string'] )
		{
			$row	= $this->DB->buildAndFetch( array( 'select' => 'COUNT(wl_id) as count', 'from' => 'members_warn_logs', 'where' => "wl_member=".intval($this->request['mid']) ) );

			$query	= "{$this->form_code}&amp;mid=" . $this->request['mid'] . "&amp;do=view";
			
			$this->DB->build( array( 'select'		=> 'l.*',
											'from'		=> array( 'members_warn_logs' => 'l' ),
											'where'		=> 'l.wl_member=' . intval($this->request['mid']),
											'order'		=> 'l.wl_date DESC',
											'limit'		=> array( $start, 30 ),
											'add_join'	=> array(
																array( 'select'	=> 'm.member_id as a_id, m.members_display_name as a_name',
																		'from'	=> array( 'members' => 'm' ),
																		'where'	=> 'm.member_id=l.wl_member',
																		'type'	=> 'left'
																	),
																array( 'select'	=> 'p.member_id as p_id, p.members_display_name as p_name',
																		'from'	=> array( 'members' => 'p' ),
																		'where'	=> 'p.member_id=l.wl_moderator',
																		'type'	=> 'left'
																	),
																)
								)		);
			$this->DB->execute();
		}
		else
		{
			$this->request[ 'search_string'] =  IPSText::parseCleanValue( urldecode($this->request['search_string'] ) );
			
			$dbq = "l.wl_note_member LIKE '%" . $this->request['search_string'] . "%' OR l.wl_note_mods LIKE '%" . $this->request['search_string'] . "%'";
			
			$row	= $this->DB->buildAndFetch( array( 'select' => 'COUNT(l.wl_id) as count', 'from' => 'members_warn_logs l', 'where' => $dbq ) );

			$query	= "{$this->form_code}&amp;do=view&amp;search_type=" . $this->request['search_type'] . "&amp;search_string=" . urlencode($this->request['search_string']);
			
			$this->DB->build( array( 'select'		=> 'l.*',
											'from'		=> array( 'members_warn_logs' => 'l' ),
											'where'		=> $dbq,
											'order'		=> 'l.wl_date DESC',
											'limit'		=> array( $start, 30 ),
											'add_join'	=> array(
																array( 'select'	=> 'm.member_id as a_id, m.members_display_name as a_name',
																		'from'	=> array( 'members' => 'm' ),
																		'where'	=> 'm.member_id=l.wl_member',
																		'type'	=> 'left'
																	),
																array( 'select'	=> 'p.member_id as p_id, p.members_display_name as p_name',
																		'from'	=> array( 'members' => 'p' ),
																		'where'	=> 'p.member_id=l.wl_moderator',
																		'type'	=> 'left'
																	),
																)
								)		);
			$this->DB->execute();
		}
		
		///----------------------------------------
		// Page links
		//-----------------------------------------
		
		$links = $this->registry->output->generatePagination( array( 'totalItems'			=> $row['count'],
																	 'itemsPerPage'			=> 30,
																	 'currentStartValue'	=> $start,
																	 'baseUrl'				=> $this->settings['base_url'] . $query,
														)
												 );

		///----------------------------------------
		// Get teh results
		//-----------------------------------------
		
		$days = array( 'd' => $this->lang->words['wlog_days'], 'h' => $this->lang->words['wlog_hours'] );

		while ( $row = $this->DB->fetch() )
		{
			$row['_a_name']		= $row['a_name'] ? $row['a_name'] : sprintf( $this->lang->words['wlog_deleted'], $row['wl_member'] );
			$row['_date']		= $this->registry->class_localization->getDate( $row['wl_date'], 'LONG' );
			$row['wl_reason']	= $row['wl_reason'] ? $reasons[ $row['wl_reason'] ]['wr_name'] : '--';
			$row['_mod']		= $row['wl_mq'] == -1 ? $this->lang->words['wlog_indef'] : ( $row['wl_mq'] == 0 ? $this->lang->words['wlog_none'] : $row['wl_mq'] . ' ' . $days[ $row['wl_mq_unit'] ? $row['wl_mq_unit'] : 'd' ] );
			$row['_susp']		= $row['wl_suspend'] == -1 ? $this->lang->words['wlog_indef'] : ( $row['wl_suspend'] == 0 ? $this->lang->words['wlog_none'] : $row['wl_suspend'] . ' ' . $row[ $row['wl_suspend_unit'] ? $row['wl_suspend_unit'] : 'd' ] );
			$row['_post']		= $row['wl_rpa'] == -1 ? $this->lang->words['wlog_indef'] : ( $row['wl_rpa'] == 0 ? $this->lang->words['wlog_none'] : $row['wl_rpa'] . ' ' . $days[ $row['wl_rpa_unit'] ? $row['wl_rpa_unit'] : 'd' ] );
			
			$rows[] 			= $row;
		}
		
		///----------------------------------------
		// And output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->warnlogsView( $rows, $links );
	}
	
	/**
	 * Remove logs by a moderator
	 *
	 * @return	@e void
	 */
	protected function _remove()
	{
		if ( $this->request['mid'] == "" )
		{
			$this->registry->output->showError( $this->lang->words['wlog_nofind'], 11133 );
		}
		
		$this->DB->delete( 'members_warn_logs', "wl_member=" . intval($this->request['mid']) );
		
		$this->registry->getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['wlog_removelogs'], $this->request['mid'] ) );
		
		$this->registry->output->silentRedirect( $this->settings['base_url']."&{$this->form_code}" );	
	}
	
	/**
	 * List the current logs with links to view per-admin
	 *
	 * @return	@e void
	 */
	protected function _listCurrent()
	{
		/* Init vars */
		$rows    = array();
		$members = array();
		
		//-----------------------------------------
		// VIEW LAST 5
		//-----------------------------------------
		
		$this->DB->build( array( 'select'		=> 'l.*',
										'from'		=> array( 'members_warn_logs' => 'l' ),
										'order'		=> 'l.wl_date DESC',
										'limit'		=> array( 0, 10 ),
										'add_join'	=> array(
															array( 'select'	=> 'm.member_id as a_id, m.members_display_name as a_name',
																	'from'	=> array( 'members' => 'm' ),
																	'where'	=> 'm.member_id=l.wl_member',
																	'type'	=> 'left'
																),
															array( 'select'	=> 'p.member_id as p_id, p.members_display_name as p_name',
																	'from'	=> array( 'members' => 'p' ),
																	'where'	=> 'p.member_id=l.wl_moderator',
																	'type'	=> 'left'
																),
															)
							)		);
		$this->DB->execute();

		while ( $row = $this->DB->fetch() )
		{
			$row['_a_name']		= $row['a_name'] ? $row['a_name'] : sprintf( $this->lang->words['wlog_deleted'], $row['wl_member'] );
			$row['_date']		= $this->registry->class_localization->getDate( $row['wl_date'], 'LONG' );

			$rows[]				= $row;
		}

        //-----------------------------------------
		// All members
		//-----------------------------------------
		
		$st = intval( $this->request['st'] );
		$perpage = 15;
		$count = $this->DB->buildAndFetch( array( 'select' => 'count( ' .$this->DB->buildDistinct( 'wl_member' ) . ') as count', 'from' => 'members_warn_logs' ) );
		
		if ( $count['count'] )
		{
			$this->DB->build( array( 
								'select'	=> 'count(l.wl_member) as act_count',
								'from'		=> array( 'members_warn_logs' => 'l' ),
								'group'		=> 'l.wl_member, m.member_id, m.members_display_name',
								'order'		=> 'act_count DESC',
								'add_join'	=> array(
													array(
														'select'	=> 'm.member_id, m.members_display_name',
														'from'		=> array( 'members' => 'm' ),
														'where'		=> 'm.member_id=l.wl_member',
														'type'		=> 'left',
														),
													),
								'limit'		=> array( $st, $perpage ),
							)		);
			$this->DB->execute();
	
			while ( $r = $this->DB->fetch() )
			{
				$members[ $r['member_id'] ] = $r;
			}
		}
		
		$pagination = $this->registry->output->generatePagination( array( 'totalItems'			=> $count['count'],
																		  'itemsPerPage'		=> $perpage,
																		  'currentStartValue'	=> $st,
																		  'baseUrl'				=> $this->settings['base_url'] . $this->form_code,
																  )		 );


		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->warnlogsWrapper( $rows, $members, $pagination );
	}

	/**
	 * Popup window to view a note
	 *
	 * @return	@e void
	 */
	protected function _viewNote()
	{
		if ( !$this->request['id'] )
		{
			$this->registry->output->showError( $this->lang->words['wlog_nologs'], 11134 );
		}

		$id = intval($this->request['id']);
		
		$row = $this->DB->buildAndFetch( array( 'select'		=> 'l.*',
														'from'		=> array( 'members_warn_logs' => 'l' ),
														'where'		=> 'l.wl_id=' . $id,
														'add_join'	=> array(
																			array( 'select'	=> 'm.member_id as a_id, m.members_display_name as a_name',
																					'from'	=> array( 'members' => 'm' ),
																					'where'	=> 'm.member_id=l.wl_member',
																					'type'	=> 'left'
																				),
																			array( 'select'	=> 'p.member_id as p_id, p.members_display_name as p_name, p.member_group_id, p.mgroup_others',
																					'from'	=> array( 'members' => 'p' ),
																					'where'	=> 'p.member_id=l.wl_moderator',
																					'type'	=> 'left'
																				),
																			)
											)		);

		if ( ! $row['wl_id'] )
		{
			$this->registry->output->showError( $this->lang->words['wlog_cantresolve'], 11135 );
		}

		$row['_date']		= $this->registry->class_localization->getDate( $row['wl_date'], 'LONG' );
		$row['a_name']		= $row['a_name'] ? $row['a_name'] : sprintf( $this->lang->words['deleted_member_warnlog'], $row['wl_member'] );
		
		IPSText::getTextClass('bbcode')->parse_bbcode				= 1;
		IPSText::getTextClass('bbcode')->parse_smilies				= 1;
		IPSText::getTextClass('bbcode')->parse_html					= 0;
		IPSText::getTextClass('bbcode')->parse_nl2br				= 1;
		IPSText::getTextClass('bbcode')->parsing_section			= 'warn';
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $row['member_group_id'];
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $row['mgroup_others'];
		
		$row['wl_note_member'] = IPSText::getTextClass('bbcode')->preDisplayParse( $row['wl_note_member'] );
		$row['wl_note_mods'] = IPSText::getTextClass('bbcode')->preDisplayParse( $row['wl_note_mods'] );

		$this->registry->output->html .= $this->html->warnlogsNote( $row );
		
		$this->registry->output->printPopupWindow();
	}
}