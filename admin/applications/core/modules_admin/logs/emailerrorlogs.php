<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Email error logs
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

class admin_core_logs_emailerrorlogs extends ipsCommand 
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
		
		$this->html			= $this->registry->output->loadTemplate('cp_skin_emailerrorlogs');
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=logs&amp;section=emailerrorlogs';
		$this->form_code_js	= $this->html->form_code_js	= 'module=logs&section=emailerrorlogs';
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_logs' ) );
		
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=logs&section=emailerrorlogs', $this->lang->words['elog_email_err_logs'] );
				
		///----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			default:
			case 'list':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'emailerrorlogs_view' );
				$this->_listCurrent();
			break;
				
			case 'remove':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'emailerrorlogs_delete' );
				$this->_remove();
			break;
				
		    case 'viewemail':
		    	$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'emailerrorlogs_view' );
		    	$this->_viewEmail();
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
		if ( $this->request['type'] == 'all' )
		{
			$this->DB->delete( 'mail_error_logs' );
		}
		else
		{
			$ids = array();
		
			foreach ( $this->request as $k => $v )
			{
				if ( preg_match( "/^id_(\d+)$/", $k, $match ) )
				{
					if ($this->request[  $match[0] ] )
					{
						$ids[] = $match[1];
					}
				}
			}

			$ids = IPSLib::cleanIntArray( $ids );
			
			//-----------------------------------------
			
			if ( count($ids) < 1 )
			{
				$this->registry->output->showError( $this->lang->words['erlog_noneselected'], 11115 );
			}
			
			$this->DB->delete( 'mail_error_logs', "mlog_id IN (" . implode( ',', $ids ) . ")" );
		}
		
		$this->registry->getClass('adminFunctions')->saveAdminLog( $this->lang->words['erlog_removed'] );
		
		$this->registry->output->silentRedirect( $this->settings['base_url']."&{$this->form_code}" );
	}
	
	/**
	 * List the current logs
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _listCurrent()
	{
		$start = intval($this->request['st']) >= 0 ? intval($this->request['st']) : 0;
		
		//-----------------------------------------
		// Check URL parameters
		//-----------------------------------------
		
		$url_query	= array();
		$db_query	= array();
		
		if ( $this->request['type'] AND $this->request['type'] != "" )
		{
			$string = IPSText::parseCleanValue( urldecode($this->request['string']) );
			
			if ( $string == "" )
			{
				$this->registry->output->showError( $this->lang->words['erlog_enter_sumthang_yo'], 11116 );
			}
			
			$url_query[]	= 'type=' . $this->request['type'];
			$url_query[]	= 'string=' . urlencode($string);
			
			switch( $this->request['type'] )
			{
				case 'subject':
					$db_query[]	= $this->request['match'] == 'loose' ? "mlog_subject LIKE '%". preg_replace_callback( '/([=_\?\x00-\x1F\x80-\xFF])/', create_function( '$match', 'return "=" . strtoupper( dechex( ord( "$match[1]" ) ) );' ), $string ) ."%'" : "mlog_subject='{$string}'";
				break;

				case 'email_from':
					$db_query[]	= $this->request['match'] == 'loose' ? "mlog_from LIKE '%{$string}%'" : "mlog_from='{$string}'";
				break;
				case 'email_to':
					$db_query[]	= $this->request['match'] == 'loose' ? "mlog_to LIKE '%{$string}%'" : "mlog_to='{$string}'";
				break;
				case 'error':
					$db_query[]  = $this->request['match'] == 'loose' ? "mlog_msg LIKE '%{$string}%' or mlog_smtp_msg LIKE '%{$string}%'" : "mlog_msg='{$string} or mlog_smtp_msg='{$string}'";
				break;
			}
		}
		
		if( $this->request['match'] )
		{
			$url_query[]	= 'match=' . $this->request['match'];
		}

		//-----------------------------------------
		// LIST 'EM
		//-----------------------------------------
		
		$dbe	= "";
		$url	= "";
		
		if ( count($db_query) > 0 )
		{
			$dbe = implode( ' AND ', $db_query );
		}
		
		if ( count($url_query) > 0 )
		{
			$url = '&amp;' . implode( '&amp;', $url_query );
		}
		
		$count = $this->DB->buildAndFetch( array( 'select' => 'count(*) as cnt', 'from' => 'mail_error_logs', 'where' => $dbe ) );

		$links = $this->registry->output->generatePagination( array( 'totalItems'			=> $count['cnt'],
																		'itemsPerPage'		=> 25,
																		'currentStartValue'	=> $start,
																		'baseUrl'			=> $this->settings['base_url'] . "&{$this->form_code}" . $url,
																	)
															);
		
		$this->DB->build( array( 'select' => '*', 'from' => 'mail_error_logs', 'where' => $dbe, 'order' => 'mlog_date DESC', 'limit' => array( $start, 25 ) ) );
		$this->DB->execute();
		
		while ( $row = $this->DB->fetch() )
		{
			$row['_date']			= $this->registry->class_localization->getDate( $row['mlog_date'], 'SHORT' );
			
			$row['mlog_subject']    = ( empty( $row['mlog_subject'] ) ) ? '--' : $row['mlog_subject'];
			$row['mlog_subject']	= ( strpos( $row['mlog_subject'], "=?".IPS_DOC_CHAR_SET."?Q?" ) !== FALSE ) 
										? str_replace( "=?".IPS_DOC_CHAR_SET."?Q?", "", str_replace( "?=", "", preg_replace_callback( '/=([A-F0-9]{2})/', create_function( '$match', 'return chr( hexdec( "$match[1]" ) );' ), $row['mlog_subject'] ) ) )
										: $row['mlog_subject'];

			$rows[]	= $row;
		}
		
		$this->registry->output->html .= $this->html->emailerrorlogsWrapper( $rows, $links );
	}
	
	/**
	 * View an individual email
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _viewEmail()
	{
		if ( !$this->request['id'] )
		{
			$this->registry->output->showError( $this->lang->words['erlog_404'], 11117 );
		}
		
		$id = intval($this->request['id']);
		
		$row = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'mail_error_logs', 'where' => "mlog_id={$id}" ) );

		if ( ! $row['mlog_id'] )
		{
			$this->registry->output->showError( $this->lang->words['erlog_404'], 11118 );
		}

		$row['_date']			= $this->registry->class_localization->getDate( $row['mlog_date'], 'LONG' );
		$row['mlog_content']	= nl2br($row['mlog_content']);
		
		$row['mlog_subject']	= ( strpos( $row['mlog_subject'], "=?".IPS_DOC_CHAR_SET."?Q?" ) !== FALSE ) 
									? str_replace( "=?".IPS_DOC_CHAR_SET."?Q?", "", str_replace( "?=", "", preg_replace_callback( '/=([A-F0-9]{2})/', create_function( '$match', 'return chr( hexdec( "$match[1]" ) );' ), $row['mlog_subject'] ) ) )
									: $row['mlog_subject'];
		
		$row['mlog_msg']		= $row['mlog_msg']			? $row['mlog_msg']			: $this->lang->words['erlog_noinfo'];
		$row['mlog_code']		= $row['mlog_code']			? $row['mlog_code']			: $this->lang->words['erlog_noinfo'];
		$row['mlog_smtp_error']	= $row['mlog_smtp_error']	? $row['mlog_smtp_error']	: $this->lang->words['erlog_noinfo'];

		$this->registry->output->html .= $this->html->emailerrorlogsEmail( $row );
		
		$this->registry->output->printPopupWindow();
	}
}