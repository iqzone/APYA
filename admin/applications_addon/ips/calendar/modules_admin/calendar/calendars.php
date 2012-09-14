<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board vVERSION_NUMBER
 * Calendar Management
 * Last Updated: $LastChangedDate: 2012-02-13 05:07:16 -0500 (Mon, 13 Feb 2012) $
 * </pre>
 *
 * @author 		$Author: mark $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Calendar
 * @link		http://www.invisionpower.com
 * @since		27th January 2004
 * @version		$Rev: 10290 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_calendar_calendar_calendars extends ipsCommand 
{
	/**
	 * Skin file
	 *
	 * @var		object
	 */
	public $html;
	
	/**
	 * Main execution method
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$this->html					= $this->registry->output->loadTemplate( 'cp_skin_calendar' );
		$this->html->form_code		= 'module=calendar&amp;section=calendars';
		$this->html->form_code_js	= 'module=calendar&section=calendars';
		
		$this->lang->loadLanguageFile( array( 'admin_calendar' ) );
		
		//-----------------------------------------
		// What are we doing
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'calendar_delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'calendar_delete' );
				$this->calendarDelete();
			break;
			
			case 'calendar_add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'calendar_manage' );
				$this->calendarForm( 'new' );
			break;
			
			case 'calendar_add_do':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'calendar_manage' );
				$this->calendarSave( 'new' );
			break;
			
			case 'calendar_edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'calendar_manage' );
				$this->calendarForm( 'edit' );
			break;
			
			case 'calendar_edit_do':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'calendar_manage' );
				$this->calendarSave( 'edit' );
			break;
			
			case 'calendar_move':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'calendar_manage' );
				$this->calendarMove();
			break;
			
			case 'calendar_rebuildcache':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'calendar_manage' );
				$this->cache->rebuildCache( 'calendar_events', 'calendar' );
				
				$this->registry->output->global_message	= $this->lang->words['c_recached'];
				$this->calendarsList();
			break;
			
			case 'calendar_rss_cache':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'calendar_manage' );
				$this->calendarRSSCache( intval( $this->request['cal_id'] ) );
				
				$this->registry->output->global_message	= $this->lang->words['c_rssrecached'];
				$this->calendarsList();
			break;
			
			case 'calendars_list':				
			default:
				$this->calendarsList();
			break;
		}
		
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();			
	}
	
	/**
	 * Update the calendar position
	 *
	 * @return	@e void
	 */
	public function calendarMove()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classAjax.php', 'classAjax' );
		$ajax		 = new $classToLoad();
		
		//-----------------------------------------
		// Checks...
		//-----------------------------------------

		if( $this->registry->adminFunctions->checkSecurityKey( $this->request['md5check'], true ) === false )
		{
			$ajax->returnString( $this->lang->words['postform_badmd5'] );
			exit();
		}
 		
 		//-----------------------------------------
 		// Save new position
 		//-----------------------------------------

 		$position	= 1;
 		
 		if( is_array($this->request['calendars']) AND count($this->request['calendars']) )
 		{
 			foreach( $this->request['calendars'] as $this_id )
 			{
 				$this->DB->update( 'cal_calendars', array( 'cal_position' => $position ), 'cal_id=' . $this_id );
 				
 				$position++;
 			}
 		}
 		
 		$this->calendarsRebuildCache();

 		$ajax->returnString( 'OK' );
 		exit();
	}	
	
	/**
	 * Delete a calendar
	 *
	 * @return	@e void
	 */
	public function calendarDelete()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$cal_id	= intval( $this->request['cal_id'] );
		
		if ( ! $cal_id )
		{
			$this->registry->output->global_message = $this->lang->words['c_noid'];
			$this->calendarsList();
			return;
		}
		
		//-----------------------------------------
		// Delete
		//-----------------------------------------
		
		$this->DB->delete( 'cal_calendars', 'cal_id=' . $cal_id );
		$this->DB->delete( 'permission_index', "app='calendar' AND perm_type='calendar' AND perm_type_id=" . $cal_id );
		
		//-----------------------------------------
		// Get feeds for removal
		//-----------------------------------------
		
		$_feedIds	= array();
		
		$this->DB->build( array( 'select' => 'feed_id', 'from' => 'cal_import_feeds', 'where' => 'feed_calendar_id=' . $cal_id ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$_feedIds[]	= $r['feed_id'];
		}
		
		$this->DB->delete( 'cal_import_feeds', 'feed_calendar_id=' . $cal_id );
		
		if( count($_feedIds) )
		{
			$this->DB->delete( 'cal_import_map', 'import_feed_id IN(' . implode( ',', $_feedIds ) . ')' );
		}
		
		//-----------------------------------------
		// Get event ids for comments/ratings
		//-----------------------------------------
		
		$_eventIds	= array();
		
		$this->DB->build( array( 'select' => 'event_id', 'from' => 'cal_events', 'where' => 'event_calendar_id=' . $cal_id ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$_eventIds[]	= $r['event_id'];
		}
		
		if( count($_eventIds) )
		{
			$this->DB->delete( 'cal_event_comments', 'comment_eid IN(' . implode( ',', $_eventIds ) . ')' );
			$this->DB->delete( 'cal_event_ratings', 'rating_eid IN(' . implode( ',', $_eventIds ) . ')' );
			$this->DB->delete( 'cal_event_rsvp', 'rsvp_event_id IN(' . implode( ',', $_eventIds ) . ')' );
			
			//-----------------------------------------
			// Delete attachments
			//-----------------------------------------
		
			$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
			$class_attach		= new $classToLoad( $this->registry );
			$class_attach->type	= 'event';
			$class_attach->init();
			$class_attach->bulkRemoveAttachment( $_eventIds );
		}
		
		$this->DB->delete( 'cal_events', 'event_calendar_id=' . $cal_id );

		//-----------------------------------------
		// Remove likes
		//-----------------------------------------
		
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$_like	= classes_like::bootstrap( 'calendar', 'calendars' );
		$_like->remove( $cal_id );
		
		//-----------------------------------------
		// Rebuild caches
		//-----------------------------------------
		
		$this->calendarsRebuildCache();
		$this->cache->rebuildCache( 'calendar_events', 'calendar' );
		$this->cache->rebuildCache( 'rss_calendar', 'calendar' );
		
		//-----------------------------------------
		// Redirect
		//-----------------------------------------
		
		$this->registry->output->global_message	= $this->lang->words['c_removed'];
		$this->calendarsList();
	}		
	
	/**
	 * Handles the calednar new/edit form
	 *
	 * @param	string	$type	Either new or edit	 
	 * @return	@e void
	 */
	public function calendarSave( $type='new' )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$cal_id					= intval( $this->request['cal_id'] );
		$cal_title				= trim( IPSText::stripslashes( IPSText::htmlspecialchars( $_POST['cal_title'] ) ) );
		$cal_moderate			= intval( $this->request['cal_moderate'] );
		$cal_comment_moderate	= intval( $this->request['cal_comment_moderate'] );
		$cal_event_limit		= intval( $this->request['cal_event_limit'] );
		$cal_bday_limit			= intval( $this->request['cal_bday_limit'] );
		$cal_rss_export			= intval( $this->request['cal_rss_export'] );
		$cal_rss_export_days	= intval( $this->request['cal_rss_export_days'] );
		$cal_rss_export_max		= intval( $this->request['cal_rss_export_max'] );
		$cal_rss_update			= intval( $this->request['cal_rss_update'] );
		$cal_rsvp				= intval( $this->request['cal_rsvp_owner'] );
		
		//-----------------------------------------
		// Error checking
		//-----------------------------------------
		
		if ( $type == 'edit' )
		{
			if ( ! $cal_id )
			{
				$this->registry->output->global_message	= $this->lang->words['c_noid'];
				$this->calendarsList();
				return;
			}
		}

		if ( ! $cal_title )
		{
			$this->registry->output->global_message	= $this->lang->words['c_completeform'];
			$this->calendarForm( $type );
			return;
		}

		//-----------------------------------------
		// Format for DB update
		//-----------------------------------------
		
		$array = array( 'cal_title'				=> $cal_title,
						'cal_title_seo'			=> IPSText::makeSeoTitle( $cal_title ),
						'cal_moderate'			=> $cal_moderate,
						'cal_comment_moderate'	=> $cal_comment_moderate,
						'cal_event_limit'		=> $cal_event_limit,
						'cal_bday_limit'		=> $cal_bday_limit,
						'cal_rss_export'		=> $cal_rss_export,
						'cal_rss_export_days'	=> $cal_rss_export_days,
						'cal_rss_export_max'	=> $cal_rss_export_max,
						'cal_rss_update'		=> $cal_rss_update,
						'cal_rsvp_owner'		=> $cal_rsvp,
					 );
		 
		//-----------------------------------------
		// Insert or update
		//-----------------------------------------
		
		if ( $type == 'new' )
		{
			$this->DB->insert( 'cal_calendars', $array );
			$cal_id	= $this->DB->getInsertId();

			$this->registry->output->global_message	= $this->lang->words['c_added'];
		}
		else
		{
			$this->DB->update( 'cal_calendars', $array, 'cal_id=' . $cal_id );

			$this->registry->output->global_message	= $this->lang->words['c_edited'];
		}

		//-----------------------------------------
		// Save permissions
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php', 'classPublicPermissions' );
		$permissions	= new $classToLoad( $this->registry );
		$permissions->savePermMatrix( $this->request['perms'], $cal_id, 'calendar' );		
		
		//-----------------------------------------
		// Rebuild caches and redirect
		//-----------------------------------------

		$this->calendarsRebuildCache();
		$this->cache->rebuildCache( 'calendar_events', 'calendar' );
		$this->calendarRSSCache( $cal_id );
		$this->cache->rebuildCache( 'rss_output_cache' );
		
		$this->calendarsList();
	}	
	
	/**
	 * Add/Edit Calendar Form
	 *
	 * @param	string	$type	Either new or edit
	 * @return	@e void
	 */
	public function calendarForm( $type='new' )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$cal_id		= $this->request['cal_id'] ? intval( $this->request['cal_id'] ) : 0;
		$form		= array();
		
		//-----------------------------------------
		// Defaults
		//-----------------------------------------
		
		if ( $type == 'new' )
		{
			$formcode	= 'calendar_add_do';
			$title		= $this->lang->words['c_addcal'];
			$button		= $this->lang->words['c_addcal'];

			$calendar	= array(
								'cal_title'				=> '',
								'cal_moderate'			=> 0,
								'cal_comment_moderate'	=> 0,
								'cal_rsvp_owner'		=> 0,
								'cal_event_limit'		=> '',
								'cal_bday_limit'		=> '',
								'cal_rss_export'		=> '',
								'cal_rss_update'		=> '',
								'cal_rss_export_days'	=> '',
								'cal_rss_export_max'	=> '',
								'cal_id'				=> 0 
							);
		}
		else
		{
			$formcode	= 'calendar_edit_do';
			$title		= $this->lang->words['c_editbutton'].$calendar['cal_title'];
			$button		= $this->lang->words['c_savebutton'];
			
			$calendar	= $this->DB->buildAndFetch( array( 
														'select'	=> 'c.*', 
														'from'		=> array( 'cal_calendars' => 'c' ), 
														'where'		=> 'c.cal_id=' . $cal_id,
														'add_join'	=> array(
																			array(
																					'select' => 'p.*',
																					'from'   => array( 'permission_index' => 'p' ),
																					'where'  => "p.perm_type='calendar' AND perm_type_id=c.cal_id",
																					'type'   => 'left',
																				)
															)
												)	 );

			if ( ! $calendar['cal_id'] )
			{
				$this->registry->output->global_message	= $this->lang->words['c_noid'];
				$this->calendarsList();
				return;
			}
		}
		
		//-----------------------------------------
		// Permissions
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php', 'classPublicPermissions' );
		$permissions	= new $classToLoad( $this->registry );

		//-----------------------------------------
		// Form elements
		//-----------------------------------------
		
   		$form['perm_matrix'] 			= $permissions->adminPermMatrix( 'calendar', $calendar, 'calendar', '', false );
		$form['cal_title']				= $this->registry->output->formInput( 'cal_title' , !empty( $_POST['cal_title'] ) ? $_POST['cal_title'] : $calendar['cal_title'] );
		$form['cal_moderate']			= $this->registry->output->formYesNo( 'cal_moderate' , !empty( $this->request['cal_moderate'] ) ? $this->request['cal_moderate'] : $calendar['cal_moderate'] );
		$form['cal_moderatec']			= $this->registry->output->formYesNo( 'cal_comment_moderate' , !empty( $this->request['cal_comment_moderate'] ) ? $this->request['cal_comment_moderate'] : $calendar['cal_comment_moderate'] );
		$form['cal_event_limit']		= $this->registry->output->formSimpleInput( 'cal_event_limit' , !empty( $this->request['cal_event_limit'] ) ? $this->request['cal_event_limit'] : $calendar['cal_event_limit'], 5 );
		$form['cal_bday_limit']			= $this->registry->output->formSimpleInput( 'cal_bday_limit' , !empty( $this->request['cal_bday_limit'] ) ? $this->request['cal_bday_limit'] : $calendar['cal_bday_limit'], 5 );
		$form['cal_rss_export']			= $this->registry->output->formYesNo( 'cal_rss_export' , !empty( $this->request['cal_rss_export'] ) ? $this->request['cal_rss_export'] : $calendar['cal_rss_export'] );
		$form['cal_rss_update']			= $this->registry->output->formSimpleInput( 'cal_rss_update' , !empty( $this->request['cal_rss_update'] ) ? $this->request['cal_rss_update'] : $calendar['cal_rss_update'], 5 );
		$form['cal_rss_export_days']	= $this->registry->output->formSimpleInput( 'cal_rss_export_days' , !empty( $this->request['cal_rss_export_days'] ) ? $this->request['cal_rss_export_days'] : $calendar['cal_rss_export_days'], 5 );
		$form['cal_rss_export_max']		= $this->registry->output->formSimpleInput( 'cal_rss_export_max' , !empty( $this->request['cal_rss_export_max'] ) ? $this->request['cal_rss_export_max'] : $calendar['cal_rss_export_max'], 5 );
		$form['cal_rsvp']				= $this->registry->output->formYesNo( 'cal_rsvp_owner' , !empty( $this->request['cal_rsvp_owner'] ) ? $this->request['cal_rsvp_owner'] : $calendar['cal_rsvp_owner'] );
		
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->html	.= $this->html->calendarForm( $form, $title, $formcode, $button, $calendar );	
	}	
	
	/**
	 * List Calendars
	 *
	 * @return	@e void
	 */
	public function calendarsList()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$rows        = array();
		
		//-----------------------------------------
		// Get calendars
		//-----------------------------------------
				
		$this->DB->build( array( 'select' => '*', 'from' => 'cal_calendars', 'order' => 'cal_position ASC' ) );
		$this->DB->execute();

		while( $r = $this->DB->fetch() )
		{
			$rows[] = $r;				
		}
		
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->html	.= $this->html->calendarOverviewScreen( $rows );		
	}	
	
	/**
	 * Rebuild the RSS Cache
	 *
	 * @param	mixed	$calendar_id	Specify which calendar to rebuild, all is default
	 * @return	@e void
	 */
	public function calendarRSSCache( $calendar_id='all' )
	{
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('calendar') . '/sources/cache.php', 'calendar_cache', 'calendar' );
		$library		= new $classToLoad( $this->registry );
		
		$library->rebuildCalendarRSSCache( $calendar_id );
	}

	/**
	 * Builds a cache of the current calendars
	 *
	 * @return	bool	Returns true
	 */
	public function calendarsRebuildCache()
	{
		//-----------------------------------------
		// Verify calendar is installed
		//-----------------------------------------
		
		if( !$this->DB->checkForTable('cal_calendars') )
		{
			return;
		}
		
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$cache = array();
			
		//-----------------------------------------
		// Get calendars
		//-----------------------------------------
		
		$this->DB->build( array( 
								'select'	=> 'c.*', 
								'from'		=> array( 'cal_calendars' => 'c' ), 
								'order'		=> 'c.cal_position ASC',
								'add_join'	=> array(
													array(
														'select'	=> 'p.*',
														'from'		=> array( 'permission_index' => 'p' ),
														'where'		=> "p.perm_type='calendar' AND p.perm_type_id=c.cal_id",
														'type'		=> 'left',
														)
													)
								)	 );		
		$this->DB->execute();

		while( $r = $this->DB->fetch() )
		{
			$cache[ $r['cal_id'] ]	= $r;
		}
		
		//-----------------------------------------
		// Save cache and return
		//-----------------------------------------
		
		$this->cache->setCache( 'calendars', $cache, array( 'array' => 1 ) );
		
		return TRUE;
	}
}