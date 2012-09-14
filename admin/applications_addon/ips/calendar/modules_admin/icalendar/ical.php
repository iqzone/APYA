<?php

/**
 * @file		ical.php 	Management of iCalendar feed imports
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		Feb 8th 2011
 * $LastChangedDate: 2012-01-06 06:20:45 -0500 (Fri, 06 Jan 2012) $
 * @version		vVERSION_NUMBER
 * $Revision: 10095 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		admin_calendar_icalendar_ical
 * @brief		Management of iCalendar feed imports
 *
 */
class admin_calendar_icalendar_ical extends ipsCommand 
{
	/**
	 * Skin file
	 *
	 * @var		$html
	 */
	public $html;
	
	/**
	 * Calendar functions
	 *
	 * @var		object
	 */
	protected $functions;

	/**
	 * Main execution method
	 *
	 * @param	object		$registry	ipsRegistry reference
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$this->html					= $this->registry->output->loadTemplate( 'cp_skin_calendar' );
		$this->html->form_code		= 'module=icalendar&amp;section=ical';
		$this->html->form_code_js	= 'module=icalendar&section=ical';
		
		$this->lang->loadLanguageFile( array( 'admin_calendar' ) );
		
		$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'calendar_ical' );
		
		$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir( 'calendar' ) . "/sources/functions.php", 'app_calendar_classes_functions', 'calendar' );
		$this->functions	= new $classToLoad( $this->registry, true );
		
		//-----------------------------------------
		// What are we doing
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'delete':
				$this->deleteFeed();
			break;
			
			case 'add':
				$this->feedForm( 'add' );
			break;
			
			case 'doadd':
				$this->feedSave( 'add' );
			break;
			
			case 'edit':
				$this->feedForm( 'edit' );
			break;
			
			case 'doedit':
				$this->feedSave( 'edit' );
			break;
			
			case 'import':
				$this->importIcsFile();
			break;
			
			case 'recache':
				$this->recacheFeed( intval($this->request['id']) );
			break;

			case 'list':				
			default:
				$this->listFeeds();
			break;
		}
		
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();			
	}

	/**
	 * Delete a feed import
	 *
	 * @return	@e void
	 */
	public function deleteFeed()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$id	= intval( $this->request['id'] );
		
		if ( ! $id )
		{
			$this->registry->output->global_message = $this->lang->words['feed_noiddel'];
			$this->listFeeds();
			return;
		}
		
		$feed	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'cal_import_feeds', 'where' => 'feed_id=' . $id ) );
		
		if ( ! $feed['feed_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['feed_noiddel'];
			$this->listFeeds();
			return;
		}

		//-----------------------------------------
		// Delete just the feed, or all events as well?
		//-----------------------------------------
		
		if( !$this->request['confirm'] )
		{
			$this->registry->output->html	.= $this->html->deleteConfirm( $feed );
			return;
		}

		//-----------------------------------------
		// Deleting all events too?
		//-----------------------------------------
		
		if( $this->request['delete_events'] )
		{
			$_eventIds	= array();
			
			$this->DB->build( array( 'select' => 'import_event_id', 'from' => 'cal_import_map', 'where' => 'import_feed_id=' . $id ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$_eventIds[]	= $r['import_event_id'];
			}
			
			if( count($_eventIds) )
			{
				$this->DB->delete( 'cal_events', 'event_id IN(' . implode( ',', $_eventIds ) . ')' );
				$this->DB->delete( 'cal_event_comments', 'comment_eid IN(' . implode( ',', $_eventIds ) . ')' );
				$this->DB->delete( 'cal_event_ratings', 'rating_eid IN(' . implode( ',', $_eventIds ) . ')' );
				$this->DB->delete( 'cal_event_rsvp', 'rsvp_event_id IN(' . implode( ',', $_eventIds ) . ')' );
			}
		}

		//-----------------------------------------
		// Delete
		//-----------------------------------------
		
		$this->DB->delete( 'cal_import_feeds', 'feed_id=' . $id );
		$this->DB->delete( 'cal_import_map', 'import_feed_id=' . $id );

		//-----------------------------------------
		// Redirect
		//-----------------------------------------
		
		$this->registry->output->global_message	= $this->lang->words['feed_importremoved'];
		$this->listFeeds();
	}		
	
	/**
	 * Save the added/edited feed
	 *
	 * @param	string	$type	Either add or edit	 
	 * @return	@e void
	 */
	public function feedSave( $type='add' )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$id			= intval( $this->request['id'] );
		
		//-----------------------------------------
		// Error checking
		//-----------------------------------------
		
		if ( $type == 'edit' )
		{
			if ( ! $id )
			{
				$this->registry->output->global_message	= $this->lang->words['feed_notfoundedit'];
				$this->listFeeds();
				return;
			}
			
			$feed	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'cal_import_feeds', 'where' => 'feed_id=' . $id ) );
			
			if ( ! $feed['feed_id'] )
			{
				$this->registry->output->global_message = $this->lang->words['feed_notfoundedit'];
				$this->listFeeds();
				return;
			}
		}
		
		//-----------------------------------------
		// Check member
		//-----------------------------------------
		
		$memberName	= trim($this->request['feed_member_id']);
		$member		= $this->DB->buildAndFetch( array( 'select' => 'member_id, name', 'from' => 'members', 'where' => "members_l_display_name='" . $this->DB->addSlashes( strtolower( $memberName ) ) . "'" ) );
		
		if( !$member['member_id'] )
		{
			$this->registry->output->showError( $this->lang->words['feed_importicsmme'], 1149 );
		}

		//-----------------------------------------
		// Format for DB update
		//-----------------------------------------
		
		$array = array( 'feed_title'		=> trim($this->request['feed_title']),
						'feed_url'			=> trim($this->request['feed_url']),
						'feed_recache_freq'	=> intval($this->request['feed_recache_freq']),
						'feed_calendar_id'	=> intval($this->request['feed_calendar_id']),
						'feed_member_id'	=> $member['member_id'],
					 );

		if( $array['feed_recache_freq'] < 60 )
		{
			$array['feed_recache_freq']	= 60;
		}

		if( !in_array( substr( $array['feed_url'], 0, strpos( $array['feed_url'], '://' ) ), array( 'http', 'https', 'webcal' ) ) )
		{
			$this->registry->output->showError( $this->lang->words['invalid_feed_url'], 1141 );
		}
		
		if( !$array['feed_title'] )
		{
			$this->registry->output->showError( $this->lang->words['no_feed_title'], 1142 );
		}
		 
		//-----------------------------------------
		// Insert or update
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			$array['feed_added']	= time();

			$this->DB->insert( 'cal_import_feeds', $array );
			$id	= $this->DB->getInsertId();

			$this->registry->output->global_message	= $this->lang->words['feed_added'];
		}
		else
		{
			$this->DB->update( 'cal_import_feeds', $array, 'feed_id=' . $id );

			$this->registry->output->global_message	= $this->lang->words['feed_updated'];
		}

		//-----------------------------------------
		// Rebuild caches
		//-----------------------------------------

		$this->recacheFeed( $id, true );

		$this->listFeeds();
	}	
	
	/**
	 * Show form to add/edit a feed import
	 *
	 * @param	string	$type	Either add or edit
	 * @return	@e void
	 */
	public function feedForm( $type='add' )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$id			= intval( $this->request['id'] );
		$form		= array();
		
		//-----------------------------------------
		// Defaults
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			$formcode	= 'doadd';
			$title		= $this->lang->words['feed_addnewtext'];
			$button		= $this->lang->words['feed_addnewtext'];

			$feed		= array(
								'feed_title'			=> '',
								'feed_url'				=> '',
								'feed_recache_freq'		=> 60,
								'feed_id'				=> 0,
								'feed_calendar_id'		=> 0,
								'feed_member_id'		=> $this->memberData['member_id'],
							);
		}
		else
		{
			if( !$id )
			{
				$this->registry->output->global_message	= $this->lang->words['feed_notfoundedit'];
				$this->listFeeds();
				return;
			}
			
			$feed		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'cal_import_feeds', 'where' => 'feed_id=' . $id ) );

			if ( ! $feed['feed_id'] )
			{
				$this->registry->output->global_message	= $this->lang->words['feed_notfoundedit'];
				$this->listFeeds();
				return;
			}


			$formcode	= 'doedit';
			$title		= $this->lang->words['feed_edit_title'] . ' ' . $feed['feed_title'];
			$button		= $this->lang->words['feed_editbutton'];
		}

		//-----------------------------------------
		// Form elements
		//-----------------------------------------
		
		$_member						= IPSMember::load( $feed['feed_member_id'] );
		$form['feed_title']				= $this->registry->output->formInput( 'feed_title' , !empty( $_POST['feed_title'] ) ? $_POST['feed_title'] : $feed['feed_title'] );
		$form['feed_url']				= $this->registry->output->formInput( 'feed_url' , !empty( $this->request['feed_url'] ) ? $this->request['feed_url'] : $feed['feed_url'] );
		$form['feed_member_id']			= $this->registry->output->formInput( 'feed_member_id' , !empty( $this->request['feed_member_id'] ) ? $this->request['feed_member_id'] : $_member['members_display_name'] );
		$form['feed_recache_freq']		= $this->registry->output->formSimpleInput( 'feed_recache_freq' , !empty( $this->request['feed_recache_freq'] ) ? $this->request['feed_recache_freq'] : $feed['feed_recache_freq'] );
		
		$calendars	= $this->functions->getCalendars();
		$_calendars	= array();
		
		foreach( $calendars as $_cal )
		{
			$_calendars[]	= array( $_cal['cal_id'], $_cal['cal_title'] );
		}
		
		$form['feed_calendar_id']		= $this->registry->output->formDropdown( 'feed_calendar_id' , $_calendars, !empty( $this->request['feed_calendar_id'] ) ? $this->request['feed_calendar_id'] : $feed['feed_calendar_id'] );
		
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->html	.= $this->html->feedForm( $form, $title, $formcode, $button, $feed );	
	}	
	
	/**
	 * List feeds
	 *
	 * @return	@e void
	 */
	public function listFeeds()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$feeds	= array();
		
		//-----------------------------------------
		// Get feeds
		//-----------------------------------------
				
		$this->DB->build( array( 'select' => '*', 'from' => 'cal_import_feeds' ) );
		$this->DB->execute();

		while( $r = $this->DB->fetch() )
		{
			$feeds[] = $r;				
		}
		
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->html	.= $this->html->feedsList( $feeds, $this->functions->getCalendars() );		
	}	
	
	/**
	 * Reimport a feed
	 *
	 * @param	int		$id		Specify which feed to reimport
	 * @param	bool	$return	Whether to return (false will result in a redirect back to feeds list)
	 * @return	@e mixed
	 */
	public function recacheFeed( $id, $return=false )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		if( !$id )
		{
			if( $return )
			{
				return false;
			}
			else
			{
				$this->registry->output->global_message	= $this->lang->words['feed_notfoundrecache'];
				$this->listFeeds();
				return;
			}
		}
		
		$feed		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'cal_import_feeds', 'where' => 'feed_id=' . $id ) );

		if ( ! $feed['feed_id'] )
		{
			$this->registry->output->global_message	= $this->lang->words['feed_notfoundrecache'];
			$this->listFeeds();
			return;
		}
		
		//-----------------------------------------
		// Fetch the feed
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classFileManagement.php', 'classFileManagement' );
		$fetcher	 = new $classToLoad();
		
		$content	= $fetcher->getFileContents( str_replace( 'webcal://', 'http://', $feed['feed_url'] ) );

		if( !$content )
		{
			$this->registry->output->global_message	= $this->lang->words['feed_importwcalnone'];
			$this->listFeeds();
			return;
		}
		
		//-----------------------------------------
		// Send content to be processed
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('calendar') . '/sources/icalendar.php', 'app_calendar_classes_icalendarOutput', 'calendar' );
		$ical		 = new $classToLoad( $this->registry, $feed['feed_calendar_id'] );
		
		$result	= $ical->import( $content, $feed['feed_member_id'], $feed['feed_id'] );

		//-----------------------------------------
		// Show error if any
		//-----------------------------------------
		
		if( $error = $ical->getError() )
		{
			if( $return )
			{
				return false;
			}
			else
			{
				$this->registry->output->showError( $error, 1144 );
			}
		}
		
		//-----------------------------------------
		// Update last update date and next run date
		//-----------------------------------------
		
		$this->DB->update( 'cal_import_feeds', array( 'feed_lastupdated' => time(), 'feed_next_run' => time() + ( $feed['feed_recache_freq'] * 60 ) ), 'feed_id=' . $id );
		
		//-----------------------------------------
		// Show success message/redirect
		//-----------------------------------------
		
		if( $return )
		{
			return true;
		}
		else
		{
			$this->registry->output->global_message	= sprintf( $this->lang->words['feed_successwebcal'], $result['skipped'], $result['imported'] );
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->html->form_code );
		}
	}
	
	/**
	 * Import an .ics file
	 *
	 * @return	@e void
	 */
	public function importIcsFile()
	{
		//-----------------------------------------
		// Get upload content
		//-----------------------------------------
		
		$content = $this->registry->getClass('adminFunctions')->importXml();
		
		if( !$content )
		{
			$this->registry->output->global_message	= $this->lang->words['feed_importicsnone'];
			$this->listFeeds();
			return;
		}
		
		//-----------------------------------------
		// Get member
		//-----------------------------------------
		
		$memberName	= trim($this->request['member_name']);
		$member		= $this->DB->buildAndFetch( array( 'select' => 'member_id, name', 'from' => 'members', 'where' => "members_l_display_name='" . $this->DB->addSlashes( strtolower( $memberName ) ) . "'" ) );
		
		if( !$member['member_id'] )
		{
			$this->registry->output->global_message	= $this->lang->words['feed_importicsmme'];
			$this->listFeeds();
			return;
		}
		
		//-----------------------------------------
		// Send content to be processed
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('calendar') . '/sources/icalendar.php', 'app_calendar_classes_icalendarOutput', 'calendar' );
		$ical		 = new $classToLoad( $this->registry, intval($this->request['calendar_id']) );
		
		$result	= $ical->import( $content, $member['member_id'] );

		//-----------------------------------------
		// Show error if any
		//-----------------------------------------
		
		if( $error = $ical->getError() )
		{
			$this->registry->output->showError( $error, 1143 );
		}
		
		//-----------------------------------------
		// Show success message/redirect
		//-----------------------------------------
		
		$this->registry->output->global_message	= sprintf( $this->lang->words['feed_successimport'], $result['skipped'], $result['imported'] );
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->html->form_code );
	}
}