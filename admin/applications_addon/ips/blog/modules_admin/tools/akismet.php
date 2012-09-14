<?php
/**
 * @file		akismet.php 	Akismet logs
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		27th January 2004
 * $LastChangedDate: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * @version		v2.5.2
 * $Revision: 4 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		admin_blog_tools_akismet
 * @brief		Akismet logs
 */
class admin_blog_tools_akismet extends ipsCommand
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
		/* Load Language */
		$this->lang->loadLanguageFile( array( 'admin_blog' ) );

		/* Check Settings */
		/*if( ! $this->settings['blog_akismet_key'] )
		{
			$this->registry->output->showError( sprintf ( $this->lang->words['ta_nokey'], $this->settings['_base_url'] ), 11667 );
		}*/

		/* Load Skin Template */
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_blog', 'blog' );
		
		/* URL Bits */
		$this->html->form_code    = $this->form_code    = 'module=tools&amp;section=akismet&amp;';
		$this->html->form_code_js = $this->form_code_js = 'module=tools&section=akismet&';
		
		switch( $this->request['do'] )
		{
			case 'remove':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blogs_akismet_delete' );
				$this->removeEntries();
			break;
			
			case 'submitHam':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blogs_akismet_spamham' );
				$this->submitHam();
			break;
			
			case 'submitSpam':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blogs_akismet_spamham' );
				$this->submitSpam();
			break;
				
		    case 'viewlog':
		    	$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blogs_akismet_view' );
		    	$this->viewLog();
		    break;
			
		    case 'list':
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blogs_akismet_view' );
				$this->listCurrent();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * View a single log
	 *
	 * @return	@e void
	 */
	public function viewLog()
	{
		if( ! $this->request['id'] )
		{
			$this->registry->output->showError( $this->lang->words['ta_nologid'], 11668 );
		}
		
		$id = intval( $this->request['id'] );
		
		$log = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'blog_akismet_logs', 'where' => 'log_id=' . $id ) );
		
		if( ! $log['log_id'] )
		{
			$this->registry->output->showError( $this->lang->words['ta_nologid'], 11669 );
		}
		
		/* Find out the entry... */
		if( $log['log_type'] == 'comment' )
		{
			$entry = $this->DB->buildAndFetch( array(
														'select'	=> 'c.entry_id',
														'from'		=> array( 'blog_comments' => 'c' ),
														'where'		=> 'c.comment_id=' . $log['log_etbid'],
														'add_join'	=> array(
																			array( 'select' => 'e.blog_id, e.entry_name',
																					'from'	=> array( 'blog_entries' => 'e' ),
																					'where'	=> 'e.entry_id=c.entry_id',
																					'type'	=> 'left'
																				)
																			)
											)	);
		}
		else
		{
			$entry = $this->DB->buildAndFetch( array(
														'select'	=> 'tr.entry_id',
														'from'		=> array( 'blog_trackback' => 'tr' ),
														'where'		=> 'tr.trackback_id=' . $log['log_etbid'],
														'add_join'	=> array(
																			array( 'select' => 'e.blog_id, e.entry_name',
																					'from'	=> array( 'blog_entries' => 'e' ),
																					'where'	=> 'e.entry_id=tr.entry_id',
																					'type'	=> 'left'
																				)
																			)
											)	);
		}
		
		$log['comm'] 		= $log['log_connect_error'] ? "<img src='{$this->settings['skin_acp_url']}/images/icons/tick.png' alt='YN' />" : $this->lang->words['ta_no'];
		$log['spam'] 		= $log['log_isspam'] ? "<img src='{$this->settings['skin_acp_url']}/images/icons/tick.png' alt='YN' />" : $this->lang->words['ta_no'];
		$log['submit']		= !$log['log_submitted'] ?
								( $log['log_isspam'] ? "<a href='{$this->settings['base_url']}{$this->form_code}do=submitHam&amp;id={$log['log_id']}&amp;popup=1'>{$this->lang->words['ta_submitham']}</a>" :
									"<a href='{$this->settings['base_url']}&{$this->form_code}do=submitSpam&amp;id={$log['log_id']}&amp;popup=1'>{$this->lang->words['ta_submitspam']}</a>" ) : $this->lang->words['ta_alreadysub'];
				
		$log['_log_date'] 	= $this->registry->class_localization->getDate( $log['log_date'], 'LONG' );
		$log['log_action']	= $log['log_action'] ? $log['log_action'] : $this->lang->words['ta_none'];
		
		$log['errors'] 		= $log['log_errors'] ? var_export( unserialize( $log['log_errors'] ), true ) : $this->lang->words['ta_none'];
		$log['data']		= $log['log_data']	 ? var_export( unserialize( $log['log_data'] ), true ) : $this->lang->words['ta_none'];
										 
		$this->registry->output->html .= $this->html->akismetViewLogEntry( $log, $entry );
		$this->registry->output->printPopupWindow();
	}
	
	/**
	 * Remove logs
	 *
	 * @return	@e void
	 */
	public function removeEntries()
	{
		if ( $this->request['type'] == 'all' )
		{
			$this->DB->delete( 'blog_akismet_logs' );
		}
		else
		{
			$ids = array();
		
			foreach ($this->request as $k => $v)
			{
				if ( preg_match( '/^id_(\d+)$/', $k, $match ) )
				{
					if ($this->request[ $match[0] ])
					{
						$ids[] = $match[1];
					}
				}
			}
			
			$ids = IPSLib::cleanIntArray( $ids );
			
			//-----------------------------------------
			
			if ( count($ids) < 1 )
			{
				$this->registry->output->showError( $this->lang->words['ta_noneselected'], 11670 );
			}
			
			$this->DB->delete( 'blog_akismet_logs', "log_id IN (".implode(',', $ids ).")" );
		}
		
		$this->registry->adminFunctions->saveAdminLog($this->lang->words['ta_logsremoved']);
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
		exit();
	}
	
	/**
	 * Submit Ham
	 *
	 * @return	@e void
	 */
	public function submitHam()
	{
		$id = intval($this->request['id']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['ta_nologid'], 11671 );
		}
		
		$data = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'blog_akismet_logs', 'where' => 'log_id=' . $id ) );
		
		if( !$data['log_id'] )
		{
			$this->registry->output->showError( $this->lang->words['ta_nologid'], 11672 );
		}
		else if( !$data['log_data'] )
		{
			$this->registry->output->showError( $this->lang->words['ta_unableham'], 11673 );
		}
		
		/* Setup request */
		$hamData = unserialize($data['log_data']);
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/lib/akismet.class.php', 'Akismet', 'blog' );
		$akismet = new $classToLoad( $this->settings['board_url'], $this->settings['blog_akismet_key'] );
		
		$akismet->setCommentType( $data['log_type'] );
		$akismet->setCommentAuthor( $hamData['author'] );
		$akismet->setCommentAuthorEmail( $hamData['email'] );
		$akismet->setCommentContent( $hamData['body'] );
		$akismet->setPermalink( $hamData['permalink'] );
		$akismet->setUserIP( $hamData['user_ip'] );
		
		try
		{
			$akismet->submitHam();
		}
		catch( Exception $e ) {}
		
		// Redirect
		if( $this->request['popup'] )
		{
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . 'do=viewlog&amp;id=' . $id );
		}
		else
		{
			$this->registry->output->global_message	= $this->lang->words['ta_hamsubmitted'];
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
		}
	}
		
	/**
	 * Submit Spam
	 *
	 * @return	@e void
	 */
	public function submitSpam()
	{
		$id = intval($this->request['id']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['ta_nologid'], 11674 );
		}
		
		$data = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'blog_akismet_logs', 'where' => 'log_id=' . $id ) );
		
		if( !$data['log_id'] )
		{
			$this->registry->output->showError( $this->lang->words['ta_nologid'], 11675 );
		}
		else if( !$data['log_data'] )
		{
			$this->registry->output->showError( $this->lang->words['ta_unablespam'], 11676 );
		}
		
		/* Setup request */
		$spamData = unserialize($data['log_data']);
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/lib/akismet.class.php', 'Akismet', 'blog' );
		$akismet = new $classToLoad( $this->settings['board_url'], $this->settings['blog_akismet_key'] );
		
		$akismet->setCommentType( $data['log_type'] );
		$akismet->setCommentAuthor( $spamData['author'] );
		$akismet->setCommentAuthorEmail( $spamData['email'] );
		$akismet->setCommentContent( $spamData['body'] );
		$akismet->setPermalink( $spamData['permalink'] );
		$akismet->setUserIP( $spamData['user_ip'] );
		
		try
		{
			$akismet->submitSpam();
		}
		catch( Exception $e ) {}
		
		if( $this->request['popup'] )
		{
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . 'do=viewlog&id=' . $id );
		}
		else
		{
			$this->registry->output->global_message	= $this->lang->words['ta_spamsubmitted'];
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
		}
	}
	
	/**
	 * List all logs
	 *
	 * @return	@e void
	 */
	public function listCurrent()
	{
		$form_array = array();
		
		$start = intval($this->request['st']) >= 0 ? intval($this->request['st']) : 0;

		//-----------------------------------------
		// Check URL parameters
		//-----------------------------------------
		
		$url_query = array();
		$db_query  = array();
		
		if ( !empty($this->request['type']) )
		{
			switch( $this->request['type'] )
			{
				case 'comment':
					$url_query[] = 'type=comment';
					$db_query[]  = "log_type='comment'";
					break;
				case 'trackback':
					$url_query[] = 'type=trackback';
					$db_query[]  = "log_type='trackback'";
					break;
				case 'spam':
					$url_query[] = 'type=spam';
					$db_query[]  = "log_isspam=1";
					break;
				case 'connect':
					$url_query[] = 'type=connect';
					$db_query[]  = "log_connect_error=1";
					break;
			}
		}

		//-----------------------------------------
		// LIST 'EM
		//-----------------------------------------
		
		$dbe = "";
		$url = "";
		
		if ( count( $db_query ) > 0 )
		{
			$dbe = implode(' AND ', $db_query );
		}
		
		if ( count( $url_query ) > 0 )
		{
			$url = '&amp;'.implode( '&amp;', $url_query);
		}
		
		$count = $this->DB->buildAndFetch( array( 'select' => 'count(*) as cnt', 'from' => 'blog_akismet_logs', 'where' => $dbe ) );
		
		$links = $this->registry->output->generatePagination( array( 'totalItems'		=> intval($count['cnt']),
														  			 'itemsPerPage'		=> 25,
														  			 'currentStartValue'=> $start,
														  			 'baseUrl'			=> "{$this->settings['base_url']}{$this->form_code}do=overview{$url}",
															)	);
		
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'blog_akismet_logs',
								 'where'  => $dbe,
								 'order'  => 'log_id DESC',
								 'limit'  => array( $start, 25 )
						 )		);
		$outer = $this->DB->execute();
		
		$log_rows = array();
		
		while ( $row = $this->DB->fetch($outer) )
		{
			$row['_log_date']	= $this->registry->class_localization->getDate( $row['log_date'], 'SHORT' );
			$row['comm'] 		= $row['log_connect_error'] ? "<img src='{$this->settings['skin_acp_url']}/images/icons/tick.png' alt='YN' />" : '';
			$row['spam'] 		= $row['log_isspam'] ? "<img src='{$this->settings['skin_acp_url']}/images/icons/tick.png' alt='YN' />" : '';
			$row['submit']		= ! $row['log_submitted'] ?
									( $row['log_isspam'] ? "<a href='{$this->settings['base_url']}{$this->form_code}do=submitHam&id={$row['log_id']}'>{$this->lang->words['ta_submitham']}</a>" :
										"<a href='{$this->settings['base_url']}{$this->form_code}do=submitSpam&id={$row['log_id']}'>{$this->lang->words['ta_submitspam']}</a>" ) : '';
			
			$log_rows[] = $row;
		}
		
		/* Type Options */
		$form_array = array(
							array( 'comment'	, $this->lang->words['ta_comments']		),
							array( 'trackback'	, $this->lang->words['ta_trackbacks']	),
							array( 'spam'		, $this->lang->words['ta_markedspam']	),
							array( 'connect'	, $this->lang->words['ta_connecterror']	),
						   );
						   
 		/* Output */
 		$this->registry->output->html .= $this->html->akismetLogsOverview( $log_rows, $this->registry->output->formDropdown( 'type', $form_array, $this->request['type'] ), $links );
	}
}