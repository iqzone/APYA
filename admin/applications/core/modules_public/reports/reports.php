<?php
/**
 * @file		reports.php 	Reports content central management
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: mark $
 * @author		Based on original "Report Center" by Luke Scott
 * @since		-
 * $LastChangedDate: 2012-04-09 11:09:31 -0400 (Mon, 09 Apr 2012) $
 * @version		v3.3.3
 * $Revision: 10580 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		public_core_reports_reports
 * @brief		Reports content central management
 */
class public_core_reports_reports extends ipsCommand
{	
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load basic things
		//-----------------------------------------

		$this->registry->class_localization->loadLanguageFile( array( 'public_reports' ) );

		$this->DB->loadCacheFile( IPSLib::getAppDir('core') . '/sql/' . ips_DBRegistry::getDriverType() . '_report_queries.php', 'report_sql_queries' );
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('core') .'/sources/classes/reportLibrary.php', 'reportLibrary' );
		$this->registry->setClass( 'reportLibrary', new $classToLoad( $this->registry ) );

		//-----------------------------------------
		// Check permissions...
		//-----------------------------------------

		if( $this->request['do'] AND $this->request['do'] != 'report' AND !IPSMember::isInGroup( $this->memberData, explode( ',', IPSText::cleanPermString( $this->settings['report_mod_group_access'] ) ) ) )
		{
			$this->registry->output->showError( 'no_reports_permission', 2018, true, null, 403 );
		}
		
		$this->registry->output->setTitle( $this->lang->words['main_title'] . ' - ' . ipsRegistry::$settings['board_name'] );

		//-----------------------------------------
		// Which road are we going to take?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			default:
			case 'report':
				$this->_initReportForm();
				break;
			
			case 'showMessage':
				$this->_viewReportedMessage();
				break;
			
			case 'index':
				$this->_displayReportCenter();
				break;
			
			case 'process':
				$this->_processReports();
				break;
			
			case 'findfirst':
				$this->findFirstReport();
				break;

			case 'show_report':
				$this->_displayReport();
				break;
		}
		
		if( $this->request['do'] AND $this->request['do'] != 'report' )
		{
			/* Init some data */
			$_tabs		= array();
			$_activeNav = array( 'primary' => 'reported_content', 'secondary' => 'reports' );
			$moderator	= $this->registry->class_forums->getModerator();
			
			$this->registry->class_localization->loadLanguageFile( array( 'public_modcp' ), 'core' );

			/**
			 * Loop through all apps and get plugins
			 * 
			 * @note	When updating this code below remember to update also the core in public_core_modcp_modcp
			 */
			foreach( IPSLib::getEnabledApplications() as $appDir => $appData )
			{
				if( is_dir( IPSLib::getAppDir( $appDir ) . '/extensions/modcp' ) )
				{
					try
					{
						foreach( new DirectoryIterator( IPSLib::getAppDir( $appDir ) . '/extensions/modcp' ) as $file )
						{
							if( ! $file->isDot() && $file->isFile() )
							{
								if( preg_match( "/^plugin_(.+?)\.php$/", $file->getFileName(), $matches ) )
								{
									//-----------------------------------------
									// We load each plugin so it can determine
									// if it should show based on permissions
									//-----------------------------------------
									
									$classToLoad = IPSLib::loadLibrary( $file->getPathName(), 'plugin_' . $appDir . '_' . $matches[1], $appDir );
									$_plugins[ $appDir ][ $matches[1] ] = new $classToLoad( $this->registry );
	
									if( $_plugins[ $appDir ][ $matches[1] ]->canView( $moderator ) )
									{
										//-----------------------------------------
										// Hacky solution - we want forum plugins to
										// come first as they're the most used
										//-----------------------------------------
										
										if( $appDir == 'forums' AND !empty($_tabs[ $_plugins[ $appDir ][ $matches[1] ]->getPrimaryTab() ]) )
										{
											array_unshift( $_tabs[ $_plugins[ $appDir ][ $matches[1] ]->getPrimaryTab() ], array( $_plugins[ $appDir ][ $matches[1] ]->getSecondaryTab(), $appDir, $matches[1] ) );
										}
										else
										{
											$_tabs[ $_plugins[ $appDir ][ $matches[1] ]->getPrimaryTab() ][] = array( $_plugins[ $appDir ][ $matches[1] ]->getSecondaryTab(), $appDir, $matches[1] );
										}
									}
								}
							}
						}
					} catch ( Exception $e ) {}
				}
			}
			
			// Move trash can to the bottom - if available
			if ( isset($_tabs['deleted_content']) )
			{
				$trashCan = $_tabs['deleted_content'];
				unset( $_tabs['deleted_content'] );
				$_tabs['deleted_content'] = $trashCan;
			}
			
			$this->output = $this->registry->output->getTemplate('modcp')->portalPage( $this->output, $_tabs, $_activeNav );
		}

		//-----------------------------------------
		// Output
		//-----------------------------------------

		$this->registry->getClass('output')->addContent( $this->output );
		$this->registry->output->sendOutput();
	}
	
	/**
	 * View a reported private message as it shows in the messenger
	 *
	 * @return	@e void
	 */
	public function _viewReportedMessage()
	{
		//-----------------------------------------
		// Do we have permission?
		//-----------------------------------------
		
		$this->registry->getClass('reportLibrary')->buildQueryPermissions();

		if( !IPSMember::isInGroup( $this->memberData, explode( ',', $this->registry->getClass('reportLibrary')->plugins['messages']->_extra['plugi_messages_add'] ) ) )
		{
			$this->registry->getClass('output')->showError( 'no_permission_addreport', 20115, null, null, 403 );
		}

		//-----------------------------------------
		// First see if we are already in map...
		//-----------------------------------------
		
		$topicId	= intval($this->request['topicID']);
		
		$mapRecord	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'message_topic_user_map', 'where' => "map_user_id={$this->memberData['member_id']} AND map_topic_id={$topicId}" ) );
		
		//-----------------------------------------
		// Doesn't exist?
		//-----------------------------------------
		
		if( !$mapRecord['map_user_id'] )
		{
			define( 'FROM_REPORT_CENTER', true );
			
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'members' ) . '/sources/classes/messaging/messengerFunctions.php', 'messengerFunctions', 'members' );
			$messengerFunctions = new $classToLoad( $this->registry );

			//-----------------------------------------
			// Add ourselves
			//-----------------------------------------
			
			try
			{
				$messengerFunctions->addTopicParticipants( $topicId, array( $this->memberData['members_display_name'] ), $this->memberData['member_id'] );
			}
			
			//-----------------------------------------
			// Must already be in there
			//-----------------------------------------
			
			catch( Exception $e )
			{
				
			}
		}
		
		//-----------------------------------------
		// Already a participant, make sure we're active
		//-----------------------------------------
		
		else
		{
			$update	= array();
			
			if( !$mapRecord['map_user_active'] )
			{
				$update['map_user_active']	= 1;
			}
			
			if( $mapRecord['map_folder_id'] == 'finished' )
			{
				$update['map_folder_id']	= 'myconvo';
			}
			
			if( $mapRecord['map_user_banned'] )
			{
				$update['map_user_banned']	= 0;
			}
			
			if( count($update) )
			{
				$this->DB->update( 'message_topic_user_map', $update, "map_user_id={$this->memberData['member_id']} AND map_topic_id={$topicId}" );
			}
		}

		$this->registry->output->silentRedirect( $this->settings['base_url'] . "app=members&amp;module=messaging&amp;section=view&amp;do=findMessage&amp;topicID=" . $topicId . "&amp;msgID=" . $this->request['msg'] );
	}
	
	/**
	 * Main function for displaying reports in a list
	 *
	 * @return	@e void
	 */
	public function _displayReportCenter()
	{
		//-----------------------------------------
		// Check for rss key and if none make one
		//-----------------------------------------
		
		$this->registry->getClass('reportLibrary')->checkMemberRSSKey();

		//-----------------------------------------
		// Basic title and nav routine..
		//-----------------------------------------
	
		$this->registry->output->addNavigation( $this->lang->words['main_title'], 'app=core&amp;module=reports&amp;do=index' );
		
		//-----------------------------------------
		// We need some extra permisisons sql..
		//-----------------------------------------
		
		$COM_PERM = $this->registry->getClass('reportLibrary')->buildQueryPermissions();
		
		$reports = array();
		
		//-----------------------------------------
		// By default we will only show active reports.
		// If there are no active reports, show all instead.
		// Alternatively, user can click a show all link we need to honor.
		//-----------------------------------------
		
		$_where	= $COM_PERM . ' AND stat.is_active=1';

		if( $this->request['showall'] )
		{
			$_where	= $COM_PERM;
		}

		//-----------------------------------------
		// Show me the money! err.. Reports!
		//-----------------------------------------
		
		$total = $this->DB->buildAndFetch( array(
												'select'	=> 'COUNT(*) as reports',
												'from'		=> array( 'rc_reports_index' => 'rep' ),
												'where'		=> $_where,
												'add_join'	=> array(
																	array(
																		'from'	=> array( 'rc_classes' => 'rcl' ),
																		'where'	=> 'rcl.com_id=rep.rc_class'
																		),
																	array(
																		'from'	=> array( 'rc_status' => 'stat' ),
																		'where'	=> 'stat.status=rep.status'
																		)
																	)
										)		);

		if( !$total['reports'] AND !$this->request['showall'] )
		{
			$this->request['showall']	= 1;

			$_where	= $COM_PERM;
			$total	= $this->DB->buildAndFetch( array(
													'select'	=> 'COUNT(*) as reports',
													'from'		=> array( 'rc_reports_index' => 'rep' ),
													'where'		=> $_where,
													'add_join'	=> array(
																		array(
																			'from'	=> array( 'rc_classes' => 'rcl' ),
																			'where'	=> 'rcl.com_id=rep.rc_class'
																			),
																		array(
																			'from'	=> array( 'rc_status' => 'stat' ),
																			'where'	=> 'stat.status=rep.status'
																			)
																		)
											)		);
		}
		
		$this->DB->buildFromCache( 'reports_index', array( 'WHERE' => $_where, 'START' => intval($this->request['st']), 'LIMIT' => 10 ), 'report_sql_queries' );
		$res = $this->DB->execute();
		
		while( $row = $this->DB->fetch($res) )
		{
			$row['_isRead']		= $this->registry->classItemMarking->isRead( array( 'forumID' => 0, 'itemID' => $row['id'], 'itemLastUpdate' => $row['date_updated'] ), 'core' );
			$sec_data			= $this->registry->getClass('reportLibrary')->plugins[$row['my_class']]->giveSectionLinkTitle( $row );
			$sec_data['url']	= $this->registry->getClass('reportLibrary')->processUrl( $sec_data['url'], $sec_data['seo_title'], $sec_data['seo_template'] );
			$row['points']		= isset( $row['points'] ) ? $row['points'] :  $this->settings['_tmpPoints'][ $row['id'] ];
			$row['section']		= $sec_data;
			$row['status_icon']	= $this->registry->getClass('reportLibrary')->buildStatusIcon( $row );
			
			$reports[ $row['id'] ]	= $row;
			
			$members_to_load[] = $row['updated_by'];
		}
		
		$members = IPSMember::load( $members_to_load );
		
		foreach( $reports as $id => $data )
		{
			$reports[ $id ]['member'] = IPSMember::buildDisplayData( $members[ $data['updated_by'] ] );
		}
		
		//-----------------------------------------
		// Manually build status array without severities
		//-----------------------------------------
		
		$stats	= array();
		$_tmp	= $this->registry->getClass('reportLibrary')->flag_cache;

		foreach( $_tmp as $sid => $sta )
		{
			if( is_array( $sta ) && count( $sta ) )
			{
				foreach( $sta as $points => $info )
				{
					if( $stats[ $sid ] )
					{
						break;
					}
					
					$stats[ $sid ] = $info;
				}
			}
		}

		//-----------------------------------------
		// Display Page Navigation
		//-----------------------------------------

		$_url	= 'app=core&amp;module=reports&amp;do=index';
		
		if( $this->request['showall'] )
		{
			$_url	.= '&amp;showall=1';
		}

		$pages	= $this->registry->output->generatePagination( array( 'totalItems'			=> $total['reports'],
																	  'itemsPerPage'		=> 10,
																	  'currentStartValue'	=> $this->request['st'],
																	  'baseUrl'				=> $_url
									  )
							   );
		
		$this->output .= $this->registry->getClass('output')->getTemplate('reports')->reportsIndex( $reports, $this->registry->getClass('reportLibrary')->buildStatuses(), $pages, $stats );
	}
	
	/**
	 * Basic functions for processing actions on 'Report Index' page (Drop Down)
	 *
	 * @return	@e void
	 */
	public function _processReports()
	{
		//-----------------------------------------
		// Check form key
		//-----------------------------------------

        if ( $this->request['k'] != $this->member->form_hash )
        {
        	$this->registry->getClass('output')->showError( 'no_permission', 20112, null, null, 403 );
        }

		//-----------------------------------------
		// Are we pruning?
		//-----------------------------------------

		if( is_numeric($this->request['pruneDays']) && $this->request['newstatus'] == 'p' )
		{
			if( !$this->memberData['g_access_cp'] )
			{
				$this->registry->output->showError( 'no_report_prune_perm', 2019, true, null, null, 403 );
			}

			//-----------------------------------------
			// Let's prune those reports.. if we can
			//-----------------------------------------
		
			$prune_time		= ceil(time() - (intval($this->request['pruneDays']) * 86400));
			$total_pruned	= $this->_pruneReports( $prune_time );
			
			if( $total_pruned )
			{
				$this->registry->output->redirectScreen( $this->lang->words['report_prune_message_done'],  $this->settings['base_url'] . "app=core&module=reports&do=index" );
			}
			else
			{
				$this->registry->output->redirectScreen( $this->lang->words['report_prune_message_none'],  $this->settings['base_url'] . "app=core&module=reports&do=index" );
			}
		}
		
		//-----------------------------------------
		// Either deleting or updating status?
		//-----------------------------------------
		
		elseif( $this->request['report_ids'] && is_array($this->request['report_ids']) )
		{
			$ids	= implode( ',', IPSLib::cleanIntArray( $this->request['report_ids'] ) );

			if( strlen($ids) > 0 && ( ! preg_match( "/[^0-9,]/", $ids ) ) )
			{
				if( $this->request['newstatus'] == 'd' )
				{
					if( !$this->memberData['g_access_cp'] )
					{
						$this->registry->output->showError( 'no_report_prune_perm', 20110, true, null, null, 403 );
					}

					//-----------------------------------------
					// Time to delete some stuff!
					//-----------------------------------------
		
					$this->_deleteReports( $ids, true );
					$this->registry->getClass('reportLibrary')->updateCacheTime();
					
					$this->registry->output->redirectScreen( $this->lang->words['redirect_delete_report'],  $this->settings['base_url'] . "app=core&module=reports&do=index" );
				}
				else
				{
					//----------------------------------------------
					// Change the status of these reports...
					//----------------------------------------------
		
					$build_update = array(
										'status'		=> intval($this->request['newstatus']),
										'date_updated'	=> time(),
										'updated_by'	=> $this->memberData['member_id'],
										);
					
					$this->DB->update( 'rc_reports_index', $build_update, "id IN({$ids})" );
					
					$this->registry->getClass('reportLibrary')->updateCacheTime();
					
					$this->registry->output->redirectScreen( $this->lang->words['redirect_mark_status'],  $this->settings['base_url'] . "app=core&module=reports&do=index" );
				}
			}
		}
		
		//-----------------------------------------
		// If we're still here show an error
		//-----------------------------------------
		
		if( !$this->memberData['g_access_cp'] )
		{
			$this->registry->output->showError( 'no_report_none_perm', 10131, null, null, 403 );
		}
		else
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . "app=core&module=reports&do=index" );
		}
	}
	
	/**
	 * Finds first post reported using topic-id
	 *
	 * @return	@e void
	 */
	public function findFirstReport()
	{
		$this->registry->getClass('reportLibrary')->buildStatuses( true );
		
		$tid = intval($this->request['tid']);
		$cid = intval($this->request['cid']);
		
		if( $tid < 1 || $cid < 1 )
		{
			$this->registry->output->showError( 'reports_need_tidcid', 10132 );
		}
		
		$row = $this->DB->buildAndFetch( array( 'select' => 'exdat2, exdat3', 'from' => 'rc_reports_index', 'where' => "exdat2={$tid} AND rc_class={$cid} AND status!={$this->registry->getClass('reportLibrary')->report_is_complete}", 'order' => "exdat2 asc", 'limit' => 1 ) );
		
		if( !$row['exdat2'] )
		{
			$this->registry->output->showError( 'reports_no_topic', 10133 );
		}

		$this->registry->output->silentRedirect( $this->settings['base_url'] . "showtopic={$row['exdat2']}&view=findpost&p={$row['exdat3']}" );
	}
	
	/**
	 * Main function for making reports and uses the custom plugins
	 *
	 * @return	@e void
	 */
	public function _initReportForm()
	{
		//-----------------------------------------
		// Make sure we have an rcom
		//-----------------------------------------
		
		$rcom = IPSText::alphanumericalClean($this->request['rcom']);

		if( !$rcom )
		{
			$this->registry->output->showError( 'reports_what_now', 10134 );
		}
		
		//-----------------------------------------
		// Request plugin info from database
		//-----------------------------------------

		$row = $this->caches['report_plugins'][ $rcom ];
		
		if( !$row['com_id'] )
		{
			$this->registry->output->showError( 'reports_what_now', 10135 );
		}
		else
		{
			//-----------------------------------------
			// Can this group report this type of page?
			//-----------------------------------------
			
			if( !$row['my_class'] OR !IPSMember::isInGroup( $this->memberData, explode( ',', IPSText::cleanPermString( $row['group_can_report'] ) ) ) )
			{
				$this->registry->output->showError( 'reports_cant_report', 10136, null, null, 403 );
			}
			
 			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('core') . '/sources/classes/reportNotifications.php', 'reportNotifications' );
			$notify = new $classToLoad( $this->registry );
			
			//-----------------------------------------
			// Let's get cooking! Load the plugin
			//-----------------------------------------
			
			$this->registry->getClass('reportLibrary')->loadPlugin( $row['my_class'], $row['app'] );
			
			if( !is_object($this->registry->getClass('reportLibrary')->plugins[ $row['my_class'] ]) )
			{
				$this->registry->output->showError( 'reports_no_plugin', 10136.1, null, null, 403 );
			}
			
			//-----------------------------------------
			// Process 'extra data' for the plugin
			//-----------------------------------------
			
			if( $row['extra_data'] && $row['extra_data'] != 'N;' )
			{
				$this->registry->getClass('reportLibrary')->plugins[ $row['my_class'] ]->_extra = unserialize( $row['extra_data'] );
			}
			else
			{
				$this->registry->getClass('reportLibrary')->plugins[ $row['my_class'] ]->_extra = array();
			}
			
			$send_code = intval($this->request['send']);
			
			if( $send_code == 0 )
			{
				//-----------------------------------------
				// Request report form from plugin
				//-----------------------------------------
				
				$this->output .= $this->registry->getClass('reportLibrary')->plugins[ $row['my_class'] ]->reportForm( $row );
			}
			else
			{
				//-----------------------------------------
				// Form key not valid
				//-----------------------------------------
				
				if ( $this->request['k'] != $this->member->form_hash )
				{
					$this->registry->getClass('output')->showError( 'no_permission', 20114, null, null, 403 );
				}

				//-----------------------------------------
				// Empty report
				//-----------------------------------------
				
				if( !trim(strip_tags($this->request['message'])) )
				{
					$this->registry->output->showError( 'reports_cant_empty', 10181 );
				}

				//-----------------------------------------
				// Sending report... do necessary things
				//-----------------------------------------
				
				$report_data = $this->registry->getClass('reportLibrary')->plugins[ $row['my_class'] ]->processReport( $row );
				
				$this->registry->getClass('reportLibrary')->updateCacheTime();
				
				//-----------------------------------------
				// Send out notfications...
				//-----------------------------------------

				$notify->initNotify( $this->registry->getClass('reportLibrary')->plugins[ $row['my_class'] ]->getNotificationList( IPSText::cleanPermString( $row['mod_group_perm'] ), $report_data ), $report_data );
				$notify->sendNotifications();
				
				//-----------------------------------------
				// Redirect...
				//-----------------------------------------
				
				$this->registry->getClass('reportLibrary')->plugins[ $row['my_class'] ]->reportRedirect( $report_data );
			}
		}
	}
	
	/**
	 * Displays a report
	 *
	 * @return	@e void
	 */
	public function _displayReport()
	{
		//-----------------------------------------
		// Lets make sure this report exists...
		//-----------------------------------------
		
		$rid		= intval($this->request['rid']);
		$options	= array( 'rid'	=> $rid );
		$reports	= array();
		$comments	= array();

		if( !$rid )
		{
			$this->registry->output->showError( 'reports_no_rid', 10137 );
		}
		
		$this->registry->class_localization->loadLanguageFile( array( 'public_editors' ) );
		
		$report_index = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'rc_reports_index', 'where' => "id=" . $rid ) );
		
		//-----------------------------------------
		// Basic title and nav routine..
		//-----------------------------------------

		$this->registry->output->addNavigation( $this->lang->words['main_title'], 'app=core&amp;module=reports&amp;do=index' );
		$this->registry->output->addNavigation( $report_index['title'], '' );

		if ( !$report_index['id'] )
		{
			$this->registry->output->showError( 'reports_no_rid', 10138 );
		}
		
		$COM_PERM = $this->registry->getClass('reportLibrary')->buildQueryPermissions();
		
		IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
		IPSText::getTextClass('bbcode')->parse_html			= 0;
		IPSText::getTextClass('bbcode')->parse_emoticons	= 1;
		IPSText::getTextClass('bbcode')->parse_nl2br		= 0;
		IPSText::getTextClass('bbcode')->parsing_section	= 'global';

		//-----------------------------------------
		// Get reports
		//-----------------------------------------

		$this->DB->buildFromCache( 'grab_report', array( 'COM' => $COM_PERM, 'rid' => $rid ), 'report_sql_queries' );
		$outer = $this->DB->execute();

		while( $row = $this->DB->fetch($outer) )
		{
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $row['member_group_id'];
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $row['mgroup_others'];
			
			$row['points']		= isset( $row['points'] ) ? $row['points'] :  $this->settings['_tmpPoints'][ $row['id'] ];
			
			if( !$options['url'] && $row['url'] )
			{
				$options['url'] = $this->registry->getClass('reportLibrary')->processUrl( $row['url'], $row['seoname'], $row['seotemplate'] );
			}
			
			if( !$options['class'] && $row['my_class'] )
			{
				$options['class'] = $row['my_class'];
			}

			if( $row['my_class'] == 'messages' && !$options['topicID'] && $row['exdat1'] )
			{
				$options['topicID'] = intval($row['exdat1']);
			}
			
			$options['title'] = $row['title'];
			$options['status_id'] = $row['status'];

			if( !$options['status_icon'] )
			{
				$options['status_icon']	= $this->registry->getClass('reportLibrary')->buildStatusIcon( $row );
				$options['status_text']	= $this->registry->getClass('reportLibrary')->flag_cache[ $row['status'] ][ $row['points'] ]['title'];
			}
			
			/* Stupid stupid stupidness */
			$row['_title']  = $row['title'];
			$row['title']   = $row['member_title'];
			
			$row['author']  = IPSMember::buildDisplayData( $row );
			
			$row['title']   = $row['_title'];
			$row['report']	= IPSText::getTextClass('bbcode')->preDisplayParse( $row['report'] );

			$reports[]	= $row;
		}
		
		if( !$options['class'] )
		{
			$this->registry->output->showError( 'reports_no_rid', 10138 );
		}

		$_tmp	= $this->registry->getClass('reportLibrary')->flag_cache;
		
		// Manually build array get just the statuses, not severities
		foreach( $_tmp as $sid => $sta )
		{
			if( is_array( $sta ) && count( $sta ) )
			{
				foreach( $sta as $points => $info )
				{
					if( $options['statuses'][ $sid ] )
					{
						break;
					}
					
					$options['statuses'][ $sid ] = $info;
				}
			}
		}
		
		//-----------------------------------------
		// Get comments
		//-----------------------------------------

		require_once( IPS_ROOT_PATH . 'sources/classes/comments/bootstrap.php' );/*noLibHook*/
		$this->_comments = classes_comments_bootstrap::controller( 'core-reports' );
		
		$comments = array( 'html'  => $this->_comments->fetchFormatted( $report_index, array( 'offset' => intval( $this->request['st'] ) ) ),
						   'count' => $this->_comments->count( $report_index ),
						  );

		//-----------------------------------------
		// Mark as read
		//-----------------------------------------

		$this->registry->classItemMarking->markRead( array( 'forumID' => 0, 'itemID' => $rid ), 'core' );
		
		//-----------------------------------------
		// And output
		//-----------------------------------------

		$this->output .= $this->registry->getClass('output')->getTemplate('reports')->viewReport( $options, $reports, $comments );
	}

	/**
	 * Responsible for pruning reports. Uses the delete reports function to finish
	 *
	 * @param	integer   $stamp	Seconds used for pruning reports
	 * @return	@e void
	 */
	public function _pruneReports( $stamp )
	{
		$ids = array();

		//--------------------------------------------------
		// Let's grab a list of reports and check stuff...
		//--------------------------------------------------
		
		$this->DB->build( array('select'	=> 'rep.id',
								'from'		=> array( 'rc_reports_index' => 'rep' ),
								'where'		=> $this->registry->getClass('reportLibrary')->buildQueryPermissions() . ' AND stat.is_complete=1 And rep.date_updated<' . $stamp,
								'add_join'	=> array(
													array(
														'from'	=> array( 'rc_classes' => 'rcl' ),
														'where'	=> 'rcl.com_id=rep.rc_class'
														),
													array(
														'from'	=> array( 'rc_status' => 'stat' ),
														'where'	=> 'stat.status=rep.status'
														),
													)
						)		);
		$this->DB->execute();

		while( $row = $this->DB->fetch() )
		{
			$ids[] = $row['id'];
		}
		
		//-----------------------------------------
		// OK lets delete them! I love OOP
		//-----------------------------------------
		
		if( count($ids) )
		{
			$this->_deleteReports( implode( ',', $ids ), false );
		}
		
		return count($ids);
	}
	
	/**
	 * Responsible for deleting reports
	 *
	 * @param	string   $rids		Report IDS (#,#,#,...)
	 * @param	boolean  $toCheck	Security check?
	 * @return	@e boolean TRUE if all is fine otherwise FALSE
	 */
	public function _deleteReports( $rids='', $toCheck=false )
	{
		if( $this->memberData['g_access_cp'] != 1 )
		{
			return false;
		}
		
		//-----------------------------------------
		// Lets make sure we got this right...
		//-----------------------------------------
		
		if( ! $rids || ! preg_match("/[0-9,]+/", $rids ) )
		{
			return false;
		}
		
		//-----------------------------------------
		// Are we checking security now?
		//-----------------------------------------
		
		if( $toCheck == true )
		{
			$num = $this->DB->buildAndFetch( array( 'select'	=> 'count(rep.id) as total',
													'from'		=> array( 'rc_reports_index' => 'rep' ),
													'where'		=> $this->registry->getClass('reportLibrary')->buildQueryPermissions() . ' AND rep.id IN(' . $rids . ')',
													'add_join'	=> array( array('from'	=> array( 'rc_classes' => 'rcl' ),
																				'where'	=> 'rcl.com_id=rep.rc_class' ) )
												)		);

			if( count( explode( ',' , $rids ) ) != intval($num['total']) )
			{
				$this->registry->output->showError( 'reports_like_whoa', 20111, true );
			}
		}
		
		//-----------------------------------------
		// Time to call for the good ol' shredder
		//-----------------------------------------
		
		$this->DB->delete( 'rc_reports_index', 'id IN(' . $rids . ')' );
		$this->DB->delete( 'rc_reports', 'rid IN(' . $rids . ')' );
		$this->DB->delete( 'rc_comments', 'rid IN(' . $rids . ')' );
		
		//-----------------------------------------
		// I think we should update the numbers..
		//-----------------------------------------
		
		$this->registry->getClass('reportLibrary')->updateCacheTime();
		
		return true;
	}
}