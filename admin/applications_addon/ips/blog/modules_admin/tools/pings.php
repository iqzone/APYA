<?php
/**
 * @file		pings.php 	Ping Services
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
 * @class		admin_blog_tools_pings
 * @brief		Ping Services
 */
class admin_blog_tools_pings extends ipsCommand
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
		/* Load Skin Template */
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_blog' );
		
		/* Load Language */
		$this->lang->loadLanguageFile( array( 'admin_blog' ) );
		
		/* URL Bits */
		$this->html->form_code    = $this->form_code    = 'module=tools&amp;section=pings&amp;';
		$this->html->form_code_js = $this->form_code_js = 'module=tools&section=pings&';
		
		switch( $this->request['do'] )
		{
			case 'service_toggle_enabled':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blog_ping_manage' );
				$this->toggleServiceEnabled();
			break;
			
			case 'service_edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blog_ping_manage' );
				$this->serviceForm( 'edit' );
			break;
			
			case 'service_add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blog_ping_manage' );
				$this->serviceForm( 'add' );
			break;
			
			case 'doeditservice':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blog_ping_manage' );
				$this->saveServiceForm( 'edit' );
			break;
			
			case 'doaddservice':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blog_ping_manage' );
				$this->saveServiceForm( 'add' );
			break;
			
			case 'dodeleteservice':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blog_ping_remove' );
				$this->doDeleteService();
			break;
			
			case 'restoretb':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blog_tb_manage' );
				$this->restoreTrackBack();
			break;
			
			case 'dodeletetb':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blog_tb_manage' );
				$this->doDeleteTrackBack();
			break;
			
			case 'tblogs':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blog_tb_view' );
				$this->trackBackLogs();
			break;
			
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blog_ping_manage' );
				$this->pingsIndex();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();		
	}

	/**
	 * Pings index page
	 *
	 * @return	@e void
	 */
	public function pingsIndex()
	{
        // Last 5 Blocked Trackback Pings
		$this->DB->build( array( 
								'select'   => "ts.*",
								'from'	   => array('blog_trackback_spamlogs' => 'ts'),
								'add_join' => array(
													array( 
															'select' => 'b.blog_name',
															'from'   => array( 'blog_blogs' => 'b' ),
															'where'  => "ts.blog_id = b.blog_id",
															'type'   => 'left'
												 			),
													array( 
															'select' => 'e.entry_name',
															'from'   => array( 'blog_entries' => 'e' ),
															'where'  => "ts.entry_id=e.entry_id",
															'type'   => 'left'
														)
												),
								'order'	   => 'trackback_id DESC',
								'limit'	   => array( 0, 5 )
					     )	);
        $this->DB->execute();

		$content = array();
		while ( $tbspam = $this->DB->fetch() )
        {
        	$tbspam['trackback_excerpt']	= $tbspam['trackback_excerpt'] ? $tbspam['trackback_excerpt'] : $this->lang->words['tp_noexcerpt'];
			$tbspam['trackback_date']		= $this->registry->class_localization->getDate( $tbspam['trackback_date'], 'SHORT' );
			$content[] = $tbspam;
        }
		
        /* Query services */
		$this->DB->build( array( 'select' => "*", 'from' => 'blog_pingservices', 'order' => 'blog_service_id ASC' ) );
        $this->DB->execute();
		
        $services = array();
		while ( $row = $this->DB->fetch() )
        {
			$services[] = $row;
        }
		
        /* Output */
		$this->registry->output->html .= $this->html->pingsIndex( $content, $services );
    }

	/**
	 * Toggle service availability
	 *
	 * @return	@e void
	 */
	public function toggleServiceEnabled()
	{
		/* ID */
		$service_id = intval( $this->request['service_id'] );
		
		$service = $this->DB->buildAndFetch( array( 'select' => "*", 'from' => 'blog_pingservices', 'where' => 'blog_service_id='.$service_id ) );
		
		if ( $service['blog_service_id'] )
		{
			$new_enabled = $service['blog_service_enabled'] ? 0 : 1;
			$this->DB->update( 'blog_pingservices', array( 'blog_service_enabled' => $new_enabled ), 'blog_service_id='.$service_id );
		}
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}

	/**
	 * Display add/edit service form
	 *
	 * @return	@e void
	 */
	public function serviceForm( $mode='edit', $errors=array() )
	{
		if( $mode == 'edit' )
		{
			/* Check ID */
			$service_id = intval( $this->request['service_id'] );
			
			$service = $this->DB->buildAndFetch( array( 'select'	=> "*", 'from'	=> 'blog_pingservices', 'where' => 'blog_service_id=' . $service_id ) );
			
			/* Data */
			if( ! $service['blog_service_id'] )
			{
			    $this->registry->output->showError( $this->lang->words['tp_invalidservice'], 11677 );
			}

			/* Form Do */
			$do = 'doeditservice';
		}
		else
		{
			/* ID */
			$service_id = 0;
			
			/* Data */
			$service = array(
								'blog_service_key'        => isset($this->request['blog_service_key']) ? $this->request['blog_service_key'] : '',
								'blog_service_name'       => isset($this->request['blog_service_name']) ? $this->request['blog_service_name'] : '',
								'blog_service_host'       => isset($this->request['blog_service_host']) ? $this->request['blog_service_host'] : '',
								'blog_service_port'       => isset($this->request['blog_service_port']) ? intval( $this->request['blog_service_port'] ) : 80,
								'blog_service_path'       => isset($this->request['blog_service_path']) ? $this->request['blog_service_path'] : '/',
								'blog_service_methodname' => isset($this->request['blog_service_methodname']) ? $this->request['blog_service_methodname'] : 'weblogUpdates.ping',
								'blog_service_extended'   => isset($this->request['blog_service_extended']) ? intval( $this->request['blog_service_extended'] ) : 0
							);

			/* Form Do */
			$do = 'doaddservice';
		}
		
		/* Error Display */
		if( is_array( $errors ) && count( $errors ) )
		{
			$this->registry->output->html .= $this->registry->output->global_template->warning_box( $this->lang->words['tp_messages'], implode( $errors, '<br />' ) ).'<br />';
		}		
		
    	/* Form Elements */
    	$form = array();
    	
		$form['blog_service_key']        = $this->registry->output->formInput( "blog_service_key", $service['blog_service_key'] );
		$form['blog_service_name']       = $this->registry->output->formInput( "blog_service_name", $service['blog_service_name'] );
		$form['blog_service_host']       = $this->registry->output->formInput( "blog_service_host", $service['blog_service_host'] );
		$form['blog_service_port']       = $this->registry->output->formInput( "blog_service_port", $service['blog_service_port'] );
		$form['blog_service_path']       = $this->registry->output->formInput( "blog_service_path", $service['blog_service_path'] );
		$form['blog_service_methodname'] = $this->registry->output->formInput( "blog_service_methodname", $service['blog_service_methodname'] );
		$form['blog_service_extended']   = $this->registry->output->formYesNo( "blog_service_extended", $service['blog_service_extended'] );
		
		/* Output */
		$this->registry->output->html .= $this->html->pingServiceForm( $service_id, $do, $form, $service );
	}

	/**
	 * Save add/edit service form
	 *
	 * @return	@e void
	 */
    public function saveServiceForm( $mode='edit' )
    {
		/* DB Array */
        $db_array = array(
							'blog_service_key'        => $this->request['blog_service_key'],
							'blog_service_name'       => $this->request['blog_service_name'],
							'blog_service_host'       => $this->request['blog_service_host'],
							'blog_service_port'       => $this->request['blog_service_port'],
							'blog_service_path'       => $this->request['blog_service_path'],
							'blog_service_methodname' => $this->request['blog_service_methodname'],
							'blog_service_extended'	  => $this->request['blog_service_extended']
						);
						
		/* Edit */
		if( $mode == 'edit' )
		{
			/* Check the ID */
			$service_id = intval( $this->request['service_id'] );
	
			$service = $this->DB->buildAndFetch( array( 'select' => "*", 'from' => 'blog_pingservices', 'where' => 'blog_service_id=' . $service_id ) );
			
			if( ! $service['blog_service_id'] )
			{
				$this->registry->output->showError( $this->lang->words['tp_invalidservice'], 11678 );
			}
			
			/* Update */						
			$this->DB->update( 'blog_pingservices', $db_array, "blog_service_id={$service_id}");

			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['tp_editedping'], $service['blog_service_name'] ) );

			$this->registry->output->global_message	= sprintf( $this->lang->words['tp_pingedited'], $service['blog_service_name'] );
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
		}
		/* Add */
		else
		{
			/* Error Checking */
			$errors      = array();			
			$service_key = $this->request['service_key'];
			$service     = $this->DB->buildAndFetch( array( 'select' => "*", 'from' => 'blog_pingservices', 'where' => "blog_service_key='{$service_key}'" ) );
			
			/* Duplicate ID */			
			if( $service['blog_service_id'] )
			{
			    $errors[] = $this->lang->words['tp_serviceexists'];
			}
			
			/* No Key */
			if( empty( $this->request['blog_service_key'] ) )
			{
			    $errors[] = $this->lang->words['tp_nokey'];
			}
			
			/* No Name */
			if( empty( $this->request['blog_service_name'] ) )
			{
			    $errors[] = $this->lang->words['tp_noname'];
			}
			
			/* No Host */
			if( empty( $this->request['blog_service_host'] ) )
			{
			    $errors[] = $this->lang->words['tp_nohost'];
			}
			
			/* Found any errors? */
			if( count( $errors ) )
			{
				$this->serviceForm( $mode, $errors );
				return;
			}

        	$this->DB->insert( 'blog_pingservices', $db_array );

        	$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['tp_addedping'], $db_array['blog_service_name'] ) );

			$this->registry->output->global_message	= sprintf( $this->lang->words['tp_pingadded'], $db_array['blog_service_name'] );
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
		}
	}

	/**
	 * Do delete service
	 *
	 * @return	@e void
	 */
    public function doDeleteService()
    {
    	/* ID */
		$service_id = intval( $this->request['service_id'] );

		$service = $this->DB->buildAndFetch( array( 'select' => "*", 'from' => 'blog_pingservices', 'where' => 'blog_service_id='.$service_id ) );
		
		if( ! $service['blog_service_id'] )
		{
            $this->registry->output->showError( $this->lang->words['tp_invalidservice'], 11679 );
		}

        $this->DB->delete( 'blog_pingservices', "blog_service_id={$service_id}");

        $this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['tp_deletedping'], $service['blog_service_name'] ) );

		$this->registry->output->global_message	= sprintf( $this->lang->words['tp_pingdeleted'], $service['blog_service_name'] );
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}

	/**
	 * Restore trackback
	 *
	 * @return	@e void
	 */
    public function restoreTrackBack()
    {
		$tbid = intval( $this->request['tbid'] );

		$trackback = $this->DB->buildAndFetch( array( 'select' => "*", 'from' => 'blog_trackback_spamlogs', 'where' => 'trackback_id='.$tbid ) );
		
		if ( ! $trackback['trackback_id'] )
		{
            $this->registry->output->showError( $this->lang->words['tp_invalidtrack'], 11680 );
		}
		
		unset( $trackback['trackback_id'] );
        $this->DB->insert( 'blog_trackback', $trackback );
        $this->DB->delete( 'blog_trackback_spamlogs', "trackback_id={$tbid}");
        
        $this->registry->getClass('blogFunctions')->rebuildEntry( $trackback['entry_id'] );

        $this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['tp_restoretrack'], $trackback['trackback_title'] ) );

		$this->registry->output->global_message	= sprintf( $this->lang->words['tp_trackrestored'], $trackback['trackback_title'] );
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}

	/**
	 * Delete trackback
	 *
	 * @return	@e void
	 */
	public function doDeleteTrackBack()
    {
    	if( $this->request['tbid'] == 'all' )
		{
			$this->DB->delete( 'blog_trackback_spamlogs' );
			
	        $this->registry->adminFunctions->saveAdminLog( $this->lang->words['tp_purgetrack'] );

			$this->registry->output->global_message	= $this->lang->words['tp_alltrack'];
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
		}
		else
		{
			$tbid = intval( $this->request['tbid'] );
	
			$trackback = $this->DB->buildAndFetch( array( 'select' => "*", 'from' => 'blog_trackback_spamlogs', 'where' => 'trackback_id=' . $tbid ) );
			if ( ! $trackback['trackback_id'] )
			{
	            $this->registry->output->showError( $this->lang->words['tp_invalidtrack'], 11681 );
			}
	        $this->DB->delete( 'blog_trackback_spamlogs', "trackback_id={$tbid}");

	        $this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['tp_deletetrack'], $trackback['trackback_title'] ) );

			$this->registry->output->global_message	= sprintf( $this->lang->words['tp_trackdeleted'], $trackback['trackback_title'] );
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
        }
	}

	/**
	 * Trackback spam logs
	 *
	 * @return	@e void
	 */
	public function trackBackLogs()
	{
		/* Pagination */
		$st = intval( $this->request['st'] );
		$count = $this->DB->buildAndFetch( array( 'select' => "COUNT(*) as tb_count", 'from' => 'blog_trackback_spamlogs' ) );

		$pages = $this->registry->output->generatePagination( array( 
																	'totalItems'        => $count['tb_count'],
																	'itemsPerPage'      => 25,
																	'currentStartValue' => $st,
																	'baseUrl'           => "{$this->settings['base_url']}{$this->form_code}&amp;req=tblogs"
															)	 );

        /* Query trackbacks */
		$this->DB->build( array( 
								'select'   => "ts.*",
								'from'     => array('blog_trackback_spamlogs' => 'ts'),
								'add_join' => array(
													array( 
															'select' => 'b.blog_name',
															'from'   => array( 'blog_blogs' => 'b' ),
															'where'  => "ts.blog_id = b.blog_id",
															'type'   => 'left'
														),
													array( 
															'select' => 'e.entry_name',
															'from'   => array( 'blog_entries' => 'e' ),
															'where'  => "ts.entry_id=e.entry_id",
															'type'   => 'left'
														)
												),
								'order'    => 'trackback_id DESC',
								'limit'    => array( $st, 25 )
					     )	);
        $this->DB->execute();
		
        $rows = array();
		while ( $tbspam = $this->DB->fetch() )
        {
        	$tbspam['trackback_excerpt']	= $tbspam['trackback_excerpt'] ? $tbspam['trackback_excerpt'] : $this->lang->words['tp_noexcerpt'];
        	$tbspam['trackback_date']		= $this->registry->class_localization->getDate( $tbspam['trackback_date'], 'SHORT' );
			$rows[] = $tbspam;
        }

		$this->registry->output->html .= $this->html->blockedTrackBacks( $rows, $pages );
	}
}