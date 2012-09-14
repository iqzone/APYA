<?php
/**
 * @file		archive.php 	Archive Action File
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $ (Original: Matt)
 * @since		11 Nov 2011
 * $LastChangedDate: 2010-10-14 13:11:17 -0400 (Thu, 14 Oct 2010) $
 * @version		v3.3.3
 * $Revision: 477 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 * @class		admin_forums_archive_archive
 * @brief		Archive Action File
 */
class admin_forums_archive_archive extends ipsCommand
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
		/* Load Skin and Lang */
		$this->html               = $this->registry->output->loadTemplate( 'cp_skin_archive' );
		$this->form_code          = $this->html->form_code    = 'module=archive&amp;section=archive&amp;';
		$this->html->form_code_js = $this->html->form_code_js = 'module=archive&section=archive&';
		
		$this->lang->loadLanguageFile( array( 'admin_archive' ) );
		
		/* Load up archive class */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/archive/writer.php', 'classes_archive_writer' );
		$this->archiveWriter = new $classToLoad();
		
		$this->archiveWriter->setApp('forums');
		
		/* Check for class_forums */
		if ( ipsRegistry::isClassLoaded('class_forums') !== TRUE )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php", 'class_forums', 'forums' );
			$this->registry->setClass( 'class_forums', new $classToLoad( $registry ) );
			$this->registry->class_forums->forumsInit();
		}
		
		switch( $this->request['do'] )
		{
			case 'toggleArchiving':
				$this->_archiveToggle();
			break;
			case 'rules':
				$this->_archiveRules();
			break;
			case 'saveRules':
				$this->_saveRules();
			break;
			case 'saveRestorePrefs':
				$this->_saveRestorePrefs();
			break;
			case 'overview':						
			default:
				$this->_archiveDash();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Archive Toggle
	 *
	 * @return	@e void
	 */
	protected function _saveRestorePrefs()
	{
		/* Load up archive class */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/archive/restore.php', 'classes_archive_restore' );
		$archiveRestore = new $classToLoad();
		
		$archiveRestore->setApp('forums');
		
		/* Show topics waiting unarchiving */
		$restoreData = $archiveRestore->getRestoreData();
		
		/* Update */
		IPSLib::updateSettings( array( 'archive_restore_days' => intval( $this->request['restoreDays'] ) ) );
		
		/* Fetch and store tids */
		$date  = IPS_UNIX_TIME_NOW - ( 86400 * intval( $this->request['restoreDays'] ) );
		
		$mouse = $this->DB->buildAndFetch( array( 'select'  => 'min(archive_topic_id) as min, max(archive_topic_id) as max',
												  'from'    => 'forums_archive_posts',
												  'where'   => 'archive_content_date > ' . $date ) );
		
		$restoreData['restore_min_tid'] = intval( $mouse['min'] );
		$restoreData['restore_max_tid'] = intval( $mouse['max'] );
		
		/* Save */
		$archiveRestore->setRestoreData( $restoreData );
		
		/* Done */
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . 'do=overview', TRUE );
	}
	
	/**
	 * Archive Toggle
	 *
	 * @return	@e void
	 */
	protected function _archiveToggle()
	{
		/* Update */
		IPSLib::updateSettings( array( 'archive_on' => ( $this->settings['archive_on'] ? 0 : 1 ) ) );
		
		/* Done */
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . 'do=overview', TRUE );
	}
	
	/**
	 * Archive dashboard
	 *
	 * @return	@e void
	 */
	protected function _archiveDash()
	{
		/* Init */ 
		$archTopics		= array();
		$restoreTopics  = array();
		$restoreCount   = 0;
		$unArchiveCount = 0;
		
		/* Test remote DB */
		$connexTest = $this->_connectionTest();
		
		/* Load up archive class */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/archive/restore.php', 'classes_archive_restore' );
		$archiveRestore = new $classToLoad();
		
		$archiveRestore->setApp('forums');
		
		/* Load topic class */
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/topics.php", 'app_forums_classes_topics', 'forums' );
		$topicClass = new $classToLoad( $this->registry );
		
		/* Show topics waiting unarchiving */
		$restoreData = $archiveRestore->getRestoreData();
		
		/* Get pids and that */
		if ( count( $restoreData['restore_manual_tids'] ) )
		{
			$restoreTopics = $topicClass->getTopics( array( 'topicId' => array_keys( $restoreData['restore_manual_tids'] ), 'limit' => 10, 'getCount' => true ) );
			$restoreCount  = $topicClass->getTopicsCount();
			
			foreach( $restoreTopics as $tid => $data )
			{
				$restoreTopics[ $tid ]['nav'] = $this->registry->class_forums->forumsBreadcrumbNav( $data['forum_id'] );
			}
		}
		
		/* Get count of topics left to unarchive */
		if ( $restoreData['restore_min_tid'] AND $restoreData['restore_max_tid'] )
		{
			$date  = IPS_UNIX_TIME_NOW - ( 86400 * intval( $this->settings['archive_restore_days'] ) );
			$restoreCount += $topicClass->getTopics( array( 'archiveState' => array( 'archived' ), 'dateIsGreater' => $date, 'tidIsGreater' => ( $restoreData['restore_min_tid'] -1 ), 'tidIsLess' => ( $restoreData['restore_max_tid'] - 1 ), 'getCountOnly' => true ) );
		
			if ( count( $restoreCount ) < 10 )
			{
				$tmp = $topicClass->getTopics( array( 'archiveState' => array( 'archived' ), 'dateIsGreater' => $date, 'tidIsGreater' => ( $restoreData['restore_min_tid'] - 1 ), 'tidIsLess' => ( $restoreData['restore_max_tid'] - 1 ), 'limit' => (10 - count( $restoreTopics ) ) ) );
			}
			
			foreach( $tmp as $tid => $data )
			{
				$restoreTopics[ $tid ] = $data;
				$restoreTopics[ $tid ]['nav'] = $this->registry->class_forums->forumsBreadcrumbNav( $data['forum_id'] );
			}
		}
		
		/* Fetch latest archived topics */
		$archTopics = $topicClass->getTopics( array( 'archiveState' => array( 'working', 'archived' ), 'topicType' => array( 'hidden', 'visible' ), 'limit' => 10, 'getCount' => true, 'sortField' => 'start_date', 'sortOrder' => 'desc' ) );
		$archCount  = $topicClass->getTopicsCount();
		
		foreach( $archTopics as $tid => $data )
		{
			$archTopics[ $tid ]['nav'] = $this->registry->class_forums->forumsBreadcrumbNav( $data['forum_id'] );
		}
		
		$possibleCounts = $this->archiveWriter->getArchivePossibleCount();
				
		/* Show dash */
		$this->registry->output->html .= $this->html->archiveDashboard( $restoreTopics, $restoreCount, $archTopics, $archCount, $possibleCounts, $connexTest );
	}
	
	/**
	 * Archive Rules overview
	 *
	 * @return	@e void
	 */
	protected function _archiveRules()
	{
		/* Get archive count so far */
		$counts = $this->archiveWriter->getArchivePossibleCount();
		$rules  = $this->archiveWriter->getRulesFromDb();
		
		if ( $counts['count'] < 1 )
		{
			$textString = $this->lang->words['archive_no_query'];
		}
		else
		{
			$textString = sprintf( $this->lang->words['archive_x_query'], $counts['percentage'], $this->lang->formatNumber( $counts['count'] ), $this->lang->formatNumber( $counts['total'] ) );
		}
		
		/* Post process */
		foreach( array('archive', 'skip' ) as $type )
		{
			if ( ! empty( $rules[ $type ]['forum']['text'] ) )
			{
				$ids = IPSLib::isSerialized( $rules[ $type ]['forum']['text'] ) ? unserialize( $rules[ $type ]['forum']['text'] ) : array();
				
				if ( count( $ids ) )
				{
					foreach( $ids as $fid )
					{
						$data = $this->registry->class_forums->getForumbyId( $fid );
						
						if ( $data['id'] )
						{
							$rules[ $type ]['forum']['_parseData'][ $fid ] = array( 'data' => $this->registry->class_forums->getForumbyId( $fid ),
																					'nav'  => $this->html->buildForumNav( $this->registry->class_forums->forumsBreadcrumbNav( $fid, 'showforum=', true ) ) );
						}
					}
				}
			}
			
			if ( ! empty( $rules[ $type ]['member']['text'] ) )
			{
				$ids = ( IPSLib::isSerialized( $rules[ $type ]['member']['text'] ) ) ? unserialize( $rules[ $type ]['member']['text'] ) : array();

				if ( count( $ids ) )
				{
					$members = IPSMember::load( $ids, 'all' );
			
					foreach( $members as $id => $data )
					{
						$members[ $id ] = IPSMember::buildProfilePhoto( $members[ $id ]);
						$members[ $id ]['photoTag'] = IPSMember::buildPhotoTag( $members[ $id ], 'inset' );
					}
					
					foreach( $ids as $fid )
					{
						$rules[ $type ]['member']['_parseData']['count'] = count( $members );
						$rules[ $type ]['member']['_parseData']['data']  = $members;
					}
				}
			}
		}
		
		/* Show rules page */
		$this->registry->output->html .= $this->html->archiveRules( IPSText::jsonEncodeForTemplate( $rules ), $textString );
	}
	
	/**
	 * Save Rules
	 *
	 * @return	@e void
	 */
	protected function _saveRules()
	{
		/* Get fields to save */
		$archiveFields = $this->archiveWriter->getArchiveOnFields();
		$skipFields    = $this->archiveWriter->getArchiveSkipFields();
		
		/* To save */
		$archiveData = array();
		$skipData    = array();
		
		/* Loop through and get archive data */
		foreach( $archiveFields as $k )
		{
			if ( isset( $_POST[ 'archive_field_' . $k ] ) )
			{
				$archiveData[ $k ] = array( 'value' => $_POST[ 'archive_field_' . $k ],
											'text'  => isset( $_POST[ 'archive_field_' . $k . '_text' ] ) ? $_POST[ 'archive_field_' . $k . '_text' ] : '',
											'unit'  => isset( $_POST[ 'archive_field_' . $k . '_unit' ] ) ? $_POST[ 'archive_field_' . $k . '_unit' ] : '' );
			}
		}
		
		/* Loop through and get skup data */
		foreach( $skipFields as $k )
		{
			if ( isset( $_POST[ 'skip_field_' . $k ] ) )
			{
				$skipData[ $k ]    = array( 'value' => $_POST[ 'skip_field_' . $k ],
											'text'  => isset( $_POST[ 'skip_field_' . $k . '_text' ] ) ? $_POST[ 'skip_field_' . $k . '_text' ] : '',
											'unit'  => isset( $_POST[ 'skip_field_' . $k . '_unit' ] ) ? $_POST[ 'skip_field_' . $k . '_unit' ] : '' );
			}
		}
		
		/* Update rules */
		foreach( $archiveData as $k => $data )
		{
			$this->DB->replace( 'core_archive_rules',   array( 'archive_key'   => md5( 'forums_' . $k . '_0' ),
															   'archive_app'   => 'forums',
															   'archive_field' => $k,
															   'archive_value' => $data['value'],
															   'archive_text'  => $data['text'],
															   'archive_unit'  => $data['unit'],
															   'archive_skip'  => 0 ), array( 'archive_key' ) );
		}
		
		/* Update rules */
		foreach( $skipData as $k => $data )
		{
			$this->DB->replace( 'core_archive_rules',   array( 'archive_key'   => md5( 'forums_' . $k . '_1' ),
															   'archive_app'   => 'forums',
															   'archive_field' => $k,
															   'archive_value' => $data['value'],
															   'archive_text'  => $data['text'],
															   'archive_unit'  => $data['unit'],
															   'archive_skip'  => 1 ), array( 'archive_key' ) );
		}
		
		/* Done */
		return $this->_archiveRules();
	}
	
	/**
	 * Test remote DB
	 * 
	 * @return	@e boolean
	 */
	protected function _connectionTest()
	{
		if ( ! empty( $this->settings['archive_remote_sql_database'] ) && ! empty( $this->settings['archive_remote_sql_user'] ) )
		{
			/* Load up archive class */
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/archive/writer.php', 'classes_archive_writer' );
			$archiveWriter = new $classToLoad();
			
			$archiveWriter->setApp('forums');
			
			/* Test */
			$result = $archiveWriter->testConnection();
			
			if ( is_string( $result ) && $result == 'NO_TABLE' )
			{
				/* Try and create table */
				$archiveWriter->createTable();
				$archiveWriter->flush();
				
				/* Try test again */
				return $archiveWriter->testConnection();
			}
			else
			{
				return $result;
			}
		}
		
		return true;
	}
	
}