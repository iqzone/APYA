<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Bulk mail management
 * Last Updated: $Date: 2012-06-07 06:27:31 -0400 (Thu, 07 Jun 2012) $
 * </pre>
 *
 * @author 		$Author: AndyMillne $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @version		$Revision: 10886 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class admin_members_bulkmail_bulkmail extends ipsCommand
{
	/**
	 * Skin object
	 *
	 * @var		object			Skin templates
	 */
	protected $html;
	
	/**
	 * Shortcut for url
	 *
	 * @var		string			URL shortcut
	 */
	protected $form_code;
	
	/**
	 * Shortcut for url (javascript)
	 *
	 * @var		string			JS URL shortcut
	 */
	protected $form_code_js;
	
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
		
		$this->html			= $this->registry->output->loadTemplate('cp_skin_bulkmail');
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=bulkmail&amp;section=bulkmail';
		$this->form_code_js	= $this->html->form_code_js	= 'module=bulkmail&section=bulkmail';
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_bulkmail' ) );
		
		switch( $this->request['do'] )
		{
			case 'mail_new':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bulkmail_addedit' );
				$this->_mailForm('add');
			break;

			case 'mail_edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bulkmail_addedit' );
				$this->_mailForm('edit');
			break;

			case 'mail_save':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bulkmail_addedit' );
				$this->_mailSave();
			break;
			
			case 'mail_preview':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bulkmail_view' );
				$this->_mailPreviewStart();
			break;
			
			case 'mail_preview_do':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bulkmail_view' );
				$this->_mailPreviewComplete();
			break;
			
			case 'mail_send_start':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bulkmail_send' );
				$this->_mailSendStart();
			break;
			
			case 'mail_send_complete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bulkmail_send' );
				$this->_mailSendComplete();
			break;
			
			case 'mail_send_cancel':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bulkmail_cancel' );
				$this->_mailSendCancel();
			break;
			
			case 'mail_delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bulkmail_delete' );
				$this->_mailDelete();
			break;

			default:
			case 'bulk_mail':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bulkmail_view' );
				$this->_mailStart();
			break;
		}

		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Delete a bulk mail
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _mailDelete()
	{
		$id = intval( $this->request['id'] );
		
		$active = $this->DB->buildAndFetch( array( 'select' => 'mail_id', 'from' => 'bulk_mail', 'where' => 'mail_active=1 AND mail_id <>' . $id ) );
		
		if( !$active['mail_id'] )
		{
			$this->DB->update( 'task_manager', array( 'task_enabled' => 0 ), "task_key='bulkmail'" );
		}
		
		$this->DB->delete( 'bulk_mail', 'mail_id=' . $id );
											
		$this->registry->output->global_message = $this->lang->words['b_deleted'];
		$this->_mailStart();
	}
	
	/**
	 * Cancels a bulk mail
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _mailSendCancel()
	{
		$this->DB->update( 'bulk_mail', array(	'mail_active'	=> 0,
													'mail_updated'	=> time(),
										  		), "mail_active=1" );
											
		$this->DB->update( 'task_manager', array( 'task_enabled' => 0 ), "task_key='bulkmail'" );
		
		$this->registry->output->global_message = $this->lang->words['b_cancelled'];
		$this->_mailStart();
	}
	
	/**
	 * Processes a bulk mail
	 *
	 * @return	@e void
	 */
	public function mailSendProcess()
	{
		//-----------------------------------------
		// Set up
		//-----------------------------------------

		$done	= 0;
		$sent	= 0;
		
		//-----------------------------------------
		// Get it from the db
		//-----------------------------------------
		
		$mail = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'bulk_mail', 'where' => 'mail_active=1' ) );
		
		if ( ! $mail['mail_subject'] and ! $mail['mail_content'] )
		{
			/* Just return, if there's nothing to send.  Bug #21494 */
			return;
			//$done	= 1;
		}
		
		//-----------------------------------------
		// Per go...
		//-----------------------------------------
		
		$pergo = intval($mail['mail_pergo']);
		
		if ( ! $pergo or $pergo > 1000 )
		{
			$pergo = 50;
		}
		
		//-----------------------------------------
		// So far...
		//-----------------------------------------
		
		$sofar = intval($mail['mail_sentto']);
		
		$mail['mail_content']	= IPSText::stripslashes( $mail['mail_content'] );
		$mail['mail_subject']	= IPSText::stripslashes( $mail['mail_subject'] );
		
		//-----------------------------------------
		// Unconvert options
		//-----------------------------------------
		
		$opts = unserialize( stripslashes( $mail['mail_opts'] ) );
		
		foreach( $opts as $k => $v )
		{
			$mail[ $k ] = $v;
		}

		//-----------------------------------------
 		// Format the query
 		//-----------------------------------------
 		
 		$query = $this->_buildMembersQuery( $mail );
 		
 		//-----------------------------------------
 		// Clear out any other temp headers
 		//-----------------------------------------
 		
 		IPSText::getTextClass('email')->clearHeaders();

		//-----------------------------------------
		// Now get members....
		//-----------------------------------------
		
		$this->DB->build( array( 'select'	=> '*',
										'from'	=> 'members',
										'where'	=> $query,
										'order'	=> 'member_id',
										'limit'	=> array( $sofar, $pergo ) ) );
		$o = $this->DB->execute();
									  
		while ( $r = $this->DB->fetch( $o ) )
		{
			$sent++;
			
			$contents = str_replace( "\r\n", "\n", $this->_convertQuicktags( $mail['mail_content'], $r ) );
			
			/* Clear out previous data */
			IPSText::getTextClass('email')->clearContent();
		
			/* Specifically a HTML email */
			if ( $mail['mail_html_on'] )
			{
				IPSText::getTextClass('email')->setHtmlEmail( true );
				IPSText::getTextClass('email')->setHtmlTemplate( str_replace( "\n", "", $contents ) );
				IPSText::getTextClass('email')->setHtmlWrapper( '<#content#>' );
			}
			else if ( $this->settings['email_use_html'] )
			{
				IPSText::getTextClass('email')->setHtmlEmail( true );
				IPSText::getTextClass('email')->setHtmlTemplate( $contents );
			}
			else
			{
				/* We want to parse the plain text emails */
				IPSText::getTextClass('email')->setPlainTextTemplate( $contents );
			}
			
			/* Build plain/HTML versions */
			IPSText::getTextClass('email')->buildMessage( array() );
		
			IPSText::getTextClass('email')->from		= $this->settings['email_out'];
			IPSText::getTextClass('email')->to			= $r['email'];
			IPSText::getTextClass('email')->subject		= $mail['mail_subject'];
			IPSText::getTextClass('email')->setHeader( 'Precedence', 'bulk' );
			IPSText::getTextClass('email')->sendMail();
		}
		
		//-----------------------------------------
		// Did we send any?
		//-----------------------------------------
		
		if ( ! $sent )
		{
			$done	= 1;
		}

		//-----------------------------------------
		// Save out..
		//-----------------------------------------
		
		if ( $done )
		{
			$this->DB->update( 'bulk_mail', array( 	'mail_active'	=> 0,
														'mail_updated'	=> time(),
														'mail_sentto'	=> $sofar + $sent 
													), 'mail_id=' . $mail['mail_id'] );
												
			$this->DB->update( 'task_manager', array( 'task_enabled' => 0 ), "task_key='bulkmail'" );
		}
		else
		{
			$this->DB->update( 'bulk_mail', array(	'mail_updated'	=> time(),
														'mail_sentto'	=> $sofar + $sent 
													), 'mail_id=' . $mail['mail_id'] );
		}			
	}
	
	/**
	 * Complete bulk mail processing
	 *
	 * @return	@e void
	 */
	protected function _mailSendComplete()
	{
		$pergo = intval( $this->request['pergo'] );
		$id    = intval( $this->request['id'] );
		
		if ( ! $id )
		{
			$this->registry->output->global_message = $this->lang->words['b_norecord'];
			$this->_mailStart();
			return;
		}
		
		//-----------------------------------------
		// Get it from the db
		//-----------------------------------------
		
		$mail = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'bulk_mail', 'where' => 'mail_id=' . $id ) );
		
		if ( ! $mail['mail_subject'] and ! $mail['mail_content'] )
		{
			$this->registry->output->global_message = $this->lang->words['b_nosend'];
			$this->_mailStart();
			return;
		}
		
		//-----------------------------------------
		// Update mail
		//-----------------------------------------
		
		if ( ! $pergo or $pergo > 1000 )
		{
			$pergo = 50;
		}
		
		$this->DB->update( 'bulk_mail', array( 'mail_active' => 1, 'mail_pergo' => $pergo, 'mail_sentto' => 0, 'mail_start' => time() ), 'mail_id=' . $id );
		$this->DB->update( 'bulk_mail', array( 'mail_active' => 0 ) , 'mail_id <> ' . $id );
		
		//-----------------------------------------
		// Wake up task manager
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_taskmanager.php', 'class_taskmanager' );
		$task        = new $classToLoad( $this->registry );

		$this->DB->update( 'task_manager', array( 'task_enabled' => 1 ), "task_key='bulkmail'" );
		
		$this_task = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'task_manager', 'where' => "task_key='bulkmail'" ) );

		$newdate = $task->generateNextRun( $this_task );
		
		$this->DB->update( 'task_manager', array( 'task_next_run' => $newdate ), "task_id=".$this_task['task_id'] );
			
		$task->saveNextRunStamp();
		
		//-----------------------------------------
		// Sit back and watch the show
		//-----------------------------------------
		
		$this->registry->output->global_message = $this->lang->words['b_initiated'];
		
		$this->_mailStart();
	}

	/**
	 * Start the sending of the bulk mail
	 *
	 * @return	@e void
	 */
	protected function _mailSendStart()
	{
		$id = intval($this->request['id']);
		
		if ( ! $id )
		{
			$this->registry->output->global_message = $this->lang->words['b_noid'];
			$this->_mailStart();
			return;
		}
		
		//-----------------------------------------
		// Get it from the db
		//-----------------------------------------
		
		$mail = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'bulk_mail', 'where' => 'mail_id=' . $id ) );
		
		if ( ! $mail['mail_subject'] and ! $mail['mail_content'] )
		{
			$this->registry->output->global_message = $this->lang->words['b_nosend'];
			$this->_mailStart();
			return;
		}
		
		//-----------------------------------------
		// Unconvert options
		//-----------------------------------------
		
		$opts = unserialize( stripslashes( $mail['mail_opts'] ) );
		
		foreach( $opts as $k => $v )
		{
			$mail[ $k ] = $v;
		}
		
		//-----------------------------------------
 		// Format the query
 		//-----------------------------------------
 		
 		$query = $this->_buildMembersQuery( $mail );
								
		//-----------------------------------------
		// Count how many matches
		//-----------------------------------------
		
		$count = $this->DB->buildAndFetch( array( 'select' => 'count(*) as cnt', 'from' => 'members', 'where' => $query ) );
		
		$the_count = intval( $count['cnt'] );
		
		//-----------------------------------------
		// Print 'continue' screen
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->mailSendStart( $mail, $the_count );
	}
		
	/**
	 * Process the sending of the bulk mail
	 *
	 * @return	@e void
	 */
	protected function _mailPreviewComplete()
	{
		$id			= intval($this->request['id']);
		$content	= "";
		
		//-----------------------------------------
		// Grab content and format it
		//-----------------------------------------
		
		if( $id )
		{		
			//-----------------------------------------
			// Get it from the db
			//-----------------------------------------
		
			$mail		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'bulk_mail', 'where' => 'mail_id=' . $id ) );

			$content	= $this->_convertQuickTags( IPSText::stripslashes($mail['mail_content']), $this->memberData );
			
			$mailopts	= unserialize( $mail['mail_opts'] );

			if( $mailopts['mail_html_on'] == 0 )
			{
				$content	= nl2br( htmlspecialchars( $content, ENT_QUOTES ) );
			}
		}
		else
		{
			if( $_POST['html'] )
			{
				$content	= $this->_convertQuickTags( IPSText::stripslashes($_POST['text']), $this->memberData );
			}
			else
			{
				$content	= nl2br( htmlspecialchars( $this->_convertQuickTags( IPSText::stripslashes($_POST['text']), $this->memberData ), ENT_QUOTES) );
			}
		}
		
		//-----------------------------------------
		// Print headers and content (to iframe)
		//-----------------------------------------
		
		header("Content-Disposition: inline");

		ob_end_clean();

		$this->registry->output->html .= $this->html->mailIframeContent( $content );
		$this->registry->output->printPopupWindow();

		exit();
	}
	
	/**
	 * Preview the email (javascript popup)
	 *
	 * @return	@e void
	 */
	protected function _mailPreviewStart()
	{
		$this->registry->output->html .= $this->html->mailPopupContent();
		$this->registry->output->printPopupWindow();
		exit();
	}
	
	/**
	 * Save the new or edited bulk mail
	 *
	 * @return	@e void
	 */
	protected function _mailSave()
	{
		//-----------------------------------------
		// Set up
		//-----------------------------------------
		
		$ids	= array();
		$id		= intval($this->request['id']);
		$type	= $this->request['type'];
		
		//-----------------------------------------
		// Start
		//-----------------------------------------

		if ( ! $this->request['mail_subject'] or ! $this->request['mail_content'] )
		{
			$this->registry->output->global_message = $this->lang->words['b_entercont'];
			$this->_mailForm( $type );
			return;
		}
		
		//-----------------------------------------
		// Groups...
		//-----------------------------------------
		
		foreach( $_POST as $key => $value )
 		{
 			if( preg_match( '/^sg_(\d+)$/', $key, $match ) )
 			{ 				
 				if( $this->request[ $match[0] ] AND $value )
 				{
 					$ids[] = $match[1];
 				}
 			}
 		}

 		$ids = IPSLib::cleanIntArray( $ids );

 		if( ! count( $ids ) )
 		{
 			$this->registry->output->global_message = $this->lang->words['b_nogroups'];
 			$this->_mailForm( $type );
 			return;
 		}
 		
 		$this->request['mail_groups'] =  implode( ",", $ids  );
 		
 		//-----------------------------------------
 		// Format the query
 		//-----------------------------------------
 		
 		$query = $this->_buildMembersQuery( array( 'mail_post_ltmt'			=> $this->request['mail_post_ltmt'],
													'mail_filter_post'		=> $this->request['mail_filter_post'],
													'mail_visit_ltmt'		=> $this->request['mail_visit_ltmt'],
													'mail_filter_visit'		=> intval($this->request['mail_filter_visit']),
													'mail_joined_ltmt'		=> $this->request['mail_joined_ltmt'],
													'mail_filter_joined'	=> intval($this->request['mail_filter_joined']),
													'mail_groups'			=> $this->request['mail_groups'],
											)      );

		//-----------------------------------------
		// Count how many matches
		//-----------------------------------------
		
		$count = $this->DB->buildAndFetch( array( 'select' => 'count(*) as cnt', 'from' => 'members', 'where' => $query ) );
		
		if ( ! $count['cnt'] )
		{
			$this->registry->output->global_message = $this->lang->words['b_nonefound'];
			$this->_mailForm( $type );
			return;
		}
		
		//-----------------------------------------
		// Save
		//-----------------------------------------
		
		$save_array = array(
							'mail_subject'	=> IPSText::stripslashes( $_POST['mail_subject'] ),
							'mail_content'	=> IPSText::stripslashes( $_POST['mail_content'] ),
							'mail_groups'	=> $this->request['mail_groups'],
							'mail_start'	=> time(),
							'mail_updated'	=> time(),
							'mail_sentto'	=> 0,
							'mail_opts'		=> serialize( array( 'mail_post_ltmt'     => $_POST['mail_post_ltmt'],
																 'mail_filter_post'   => $_POST['mail_filter_post'],
																 'mail_visit_ltmt'    => $_POST['mail_visit_ltmt'],
																 'mail_filter_visit'  => $_POST['mail_filter_visit'],
																 'mail_joined_ltmt'   => $_POST['mail_joined_ltmt'],
																 'mail_filter_joined' => $_POST['mail_filter_joined'],
																 'mail_html_on'       => $_POST['mail_html_on'],
													    )      )
						 );
						 
		if ( $type == 'add' )
		{
			//-----------------------------------------
			// Save to DB
			//-----------------------------------------
			
			$this->DB->insert( 'bulk_mail', $save_array );
			
			$this->request[ 'id'] =  $this->DB->getInsertId( );

			ipsRegistry::getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['b_maillogadd'], $this->request['mail_subject'] ) );
			$this->_mailSendStart();
			return;
		}
		else
		{
			if ( ! $id )
			{
				$this->registry->output->global_message = $this->lang->words['b_norecord'];
				$this->_mailForm( $type );
				return;
			}
			
			$this->DB->update( 'bulk_mail', $save_array, 'mail_id=' . $id );
			
			ipsRegistry::getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['b_maillogedit'], $this->request['mail_subject'] ) );
			
			$this->registry->output->global_message = $this->lang->words['b_edited'];
			$this->_mailStart();
			return;
		}
	}
	
	/**
	 * Show the edit bulk mail form
	 *
	 * @param	string		[add|edit]
	 * @return	@e void
	 */
	protected function _mailForm( $type='add' )
	{
		//-----------------------------------------
		// Init some values
		//-----------------------------------------
		
		$id			= intval($this->request['id']);
		
		if ( $type == 'add' )
		{
			$mail			= array();
		}
		else
		{
			$mail 			= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'bulk_mail', 'where' => 'mail_id='.$this->request['id'] ) );
		}
		
		if ( $this->request['mail_groups'] )
		{
			$mail['mail_groups'] = $this->request['mail_groups'];
		}

		//-----------------------------------------
		// Format mail content
		//-----------------------------------------
		
		$mail_content	= $_POST['mail_content'] ? IPSText::stripslashes($_POST['mail_content']) : $mail['mail_content'];
		$mail_content	= preg_replace( "[^\r]\n", "\r\n", $mail_content );
		
		if ( !$mail_content and $type == 'add' )
		{
			$mail_content = $this->_getDefaultMailContents();
		}
		
		$mail_content	= htmlspecialchars( $mail_content, ENT_QUOTES );

		$this->registry->output->html .= $this->html->mailForm( $type, $mail, $mail_content );
	}
	
	/**
	 * Show the main bulk mail overview screen
	 *
	 * @return	@e void
	 */
	protected function _mailStart()
	{
		$content	= '';
		$st			= intval( $this->request['st'] );
		$perpage	= 50;
		
		/* Get count */
		$items = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as cnt',
												  'from'   => 'bulk_mail' ) );
												  
		
		//-----------------------------------------
		// Get mail from DB
		// WHERE clause helps query use index properly
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'bulk_mail', 'where' => 'mail_start > 0', 'order' => 'mail_start DESC', 'limit' => array( $st, $perpage ) ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$r['_mail_start']	= ipsRegistry::getClass( 'class_localization')->getDate( $r['mail_start'], 'SHORT' );
			$r['_mail_sentto']	= ipsRegistry::getClass('class_localization')->formatNumber( $r['mail_sentto'] ) . ' members';

			$content .= $this->html->mailOverviewRow( $r );
		}
		
		/* Pages */
		$pages = $this->registry->output->generatePagination( array( 'totalItems'			=> $items['cnt'],
																	 'itemsPerPage'			=> $perpage,
																	 'currentStartValue'	=> $st,
																	 'baseUrl'				=> $this->settings['base_url'] . 'app=members&amp;module=bulkmail&do=bulk_mail' ) );

		$this->registry->output->html .= $this->html->mailOverviewWrapper( $content, $pages );
	}
	
	/**
	 * Build the query to retrieve the members
	 *
	 * @param	array 		Arguments to send to the query
	 * @return	string		Formatted query
	 */
	protected function _buildMembersQuery( $args = array() )
	{
		$query = array();
		
		if ( is_numeric($args['mail_filter_post']) )
		{
			$ltmt    = $args['mail_post_ltmt'] == 'lt' ? '<' : '>';
			$query[] = "posts " . $ltmt . " " . intval($args['mail_filter_post']);
		}
		
		if ( $args['mail_filter_visit'] )
		{
			$ltmt    = $args['mail_visit_ltmt'] == 'lt' ? '>' : '<';
			$time    = time() - ( $args['mail_filter_visit'] * 86400 );
			$query[] = "last_visit " . $ltmt . " " . $time;
		}
		
		if ( $args['mail_filter_joined'] )
		{
			$ltmt    = $args['mail_joined_ltmt'] == 'lt' ? '>' : '<';
			$time    = time() - ( $args['mail_filter_joined'] * 86400 );
			$query[] = "joined " . $ltmt . " " . $time;
		}
		
		$query[] = "allow_admin_mails=1";

		/* Make sure member is not flagged as banned/spammer. Bug #37748 */
		$query[] = "member_banned=0";
		$query[] = "( ! " . IPSBWOptions::sql( 'bw_is_spammer', 'members_bitoptions', 'members', 'global', 'has' ) . ")";
		
		if ( $args['mail_groups'] )
		{
			$tmp_q = '(member_group_id IN (' . $args['mail_groups'] . ')';
			
			$temp  = explode( ',', $args['mail_groups'] );
			
			if ( is_array( $temp ) and count( $temp ) )
			{
				$tmp = array();
				
				foreach( $temp as $id )
				{
					$tmp[] = $this->DB->buildConcat( array( array( ',', 'string' ), array( 'mgroup_others' ), array( ',', 'string' ) ) ) . "LIKE '%,{$id},%'";
				}
				
				$tmp_q .= " OR ( " . implode( ' OR ', $tmp ) . " ) )";
			}
			else
			{
				$tmp_q .= ")";
			}
			
			$query[] = $tmp_q;
		}
	
		return implode( ' AND ', $query );
	}
	
	/**
	 * Conver the 'quick tags' in the email
	 *
	 * @param 	string		The email contents
	 * @param	array 		Member information
	 * @return	string		The email contents, replaced
	 */
	protected function _convertQuickTags( $contents="", $member=array() )
	{
		$contents = str_replace( "{board_name}"   , str_replace( "&#39;", "'", $this->settings['board_name'] ) , $contents );
		$contents = str_replace( "{board_url}"    , $this->settings['board_url'] . "/index." . $this->settings['php_ext'] , $contents );
		$contents = str_replace( "{reg_total}"    , $this->caches['stats']['mem_count'] , $contents );
		$contents = str_replace( "{total_posts}"  , $this->caches['stats']['total_topics'] + $this->caches['stats']['total_replies'] , $contents );
		$contents = str_replace( "{busy_count}"   , $this->caches['stats']['most_count'] , $contents );
		$contents = str_replace( "{busy_time}"    , ipsRegistry::getClass( 'class_localization')->getDate( $this->caches['stats']['most_date'], 'SHORT' ), $contents );
		$contents = str_replace( "{member_id}"    , $member['member_id'], $contents );
		$contents = str_replace( "{member_name}"  , $member['members_display_name'], $contents );
		$contents = str_replace( "{member_joined}", ipsRegistry::getClass( 'class_localization')->getDate( $member['joined'], 'JOINED' ), $contents );
		$contents = str_replace( "{member_posts}" , $member['posts'], $contents );
		
		return $contents;
	}
	
	/**
	 * Retrieve the 'default' email contents
	 *
	 * @return	string		Default email contents
	 */
	protected function _getDefaultMailContents()
	{
		$mail = $this->lang->words['b_mailcontents'];
			  
		return $mail;
	}	
}