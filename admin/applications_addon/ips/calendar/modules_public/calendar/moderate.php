<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board vVERSION_NUMBER
 * Calendar View
 * Last Updated: $Date: 2011-09-13 21:55:41 -0400 (Tue, 13 Sep 2011) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Calendar
 * @link		http://www.invisionpower.com
 * @version		$Rev: 9487 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_calendar_calendar_moderate extends ipsCommand
{
	/**
	 * Calendar data
	 *
	 * @var		array
	 */
	protected $calendar			= array();
	
	/**
	 * Calendar functions
	 *
	 * @var		object
	 */
	protected $functions;

	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$this->registry->class_localization->loadLanguageFile( array( 'public_calendar' ), 'calendar' );

		$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir( 'calendar' ) . "/sources/functions.php", 'app_calendar_classes_functions', 'calendar' );
		$this->functions	= new $classToLoad( $this->registry );
		$this->calendar		= $this->functions->getCalendar();

		//-----------------------------------------
		// Switch
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'calendarEventApprove':
				$this->calendarEventApprove();
			break;

			case 'delete':
				$this->calendarEventDelete();
			break;
			
			case 'calendarRSVPRemove':
				$this->removeRSVPAttendee();
			break;
		}
	}
	
	/**
	 * Deletes an attendee from RSVP list
	 *
	 * @return	@e void
	 */
	public function removeRSVPAttendee()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------

		$event_id	= intval( $this->request['event_id'] );
		$rsvp_id	= intval( $this->request['attendee_id'] );
		$md5check	= trim( $this->request['md5check'] );
		
		if ( !$event_id OR !$rsvp_id )
		{
			$this->registry->output->showError( 'attendee_bad_delete', 1043.1, null, null, 404 );
		}
		
		if ( $md5check != $this->member->form_hash )
		{
			$this->registry->output->showError( 'calendar_bad_key', 2040.1, null, null, 403 );
		}

		//-----------------------------------------
		// Get event and check permissions
		//-----------------------------------------
		
		$event	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'cal_events', 'where' => "event_id=" . $event_id . " AND event_calendar_id=" . $this->calendar['cal_id'] ) );
		$rsvp	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'cal_event_rsvp', 'where' => 'rsvp_id=' . $rsvp_id ) );

		if ( ! $this->memberData['g_is_supmod'] )
		{
			if( !$this->calendar['cal_rsvp_owner'] OR !$this->memberData['member_id'] OR ( $this->memberData['member_id'] != $event['event_member_id'] AND $this->memberData['member_id'] != $rsvp['rsvp_member_id'] ) )
			{
				$this->registry->output->showError( 'calendar_delete_no_perm', 1042.1, null, null, 403 );
			}
		}

		//-----------------------------------------
		// Delete attendee
		//-----------------------------------------

		$this->DB->delete( 'cal_event_rsvp', 'rsvp_id=' . $rsvp_id );

		//-----------------------------------------
		// Redirect
		//-----------------------------------------
		
		$this->registry->output->redirectScreen( $this->lang->words['cal_event_rsvpdelete'] , $this->settings['base_url'] . "app=calendar&amp;module=calendar&amp;section=view&amp;do=showevent&amp;event_id=" . $event['event_id'], $event['event_title_seo'], 'cal_event' );
	}

	/**
	 * Deletes an event from the calendar
	 *
	 * @return	@e void
	 */
	public function calendarEventDelete()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------

		$event_id	= intval( $this->request['event_id'] );
		$md5check	= trim( $this->request['md5check'] );
		
		if ( !$event_id )
		{
			$this->registry->output->showError( 'calendar_bad_delete', 1041, null, null, 404 );
		}
		
		if ( $md5check != $this->member->form_hash )
		{
			$this->registry->output->showError( 'calendar_bad_key', 2040, null, null, 403 );
		}

		//-----------------------------------------
		// Get event and check permissions
		//-----------------------------------------
		
		$event	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'cal_events', 'where' => "event_id=" . $event_id . " AND event_calendar_id=" . $this->calendar['cal_id'] ) );

		if ( ! $this->memberData['g_is_supmod'] )
		{
			if( !$this->memberData['member_id'] OR $this->memberData['member_id'] != $event['event_member_id'] )
			{
				$this->registry->output->showError( 'calendar_delete_no_perm', 1042, null, null, 403 );
			}
		}

		//-----------------------------------------
		// Delete event
		//-----------------------------------------

		$this->DB->delete( 'cal_events', 'event_id=' . $event_id );
		$this->DB->delete( 'cal_event_ratings', 'rating_eid=' . $event_id );
		$this->DB->delete( 'cal_event_comments', 'comment_eid=' . $event_id );
		$this->DB->delete( 'cal_event_rsvp', 'rsvp_event_id=' . $event_id );
		$this->DB->delete( 'cal_import_map', 'import_event_id=' . $event_id );
		
		//-----------------------------------------
		// Delete attachments
		//-----------------------------------------
	
		$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
		$class_attach		= new $classToLoad( $this->registry );
		$class_attach->type	= 'event';
		$class_attach->init();
		$class_attach->bulkRemoveAttachment( array( $event_id ) );

		//-----------------------------------------
		// Remove likes
		//-----------------------------------------
		
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$_like	= classes_like::bootstrap( 'calendar', 'events' );
		$_like->remove( $event_id );

		//-----------------------------------------
		// Recache
		//-----------------------------------------
		
		$this->cache->rebuildCache( 'calendar_events', 'calendar' );

		//-----------------------------------------
		// Redirect
		//-----------------------------------------
		
		$this->registry->output->redirectScreen( $this->lang->words['cal_event_delete'] , $this->settings['base_url'] . "app=calendar&amp;module=calendar&amp;section=view&amp;cal_id=" . $this->calendar['cal_id'], $this->calendar['cal_title_seo'], 'cal_calendar' );
	}

	/**
	 * Approves an event
	 *
	 * @return	@e void
	 */
	public function calendarEventApprove()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$event_id	= intval( $this->request['event_id'] );
		$md5check	= trim( $this->request['md5check'] );

		if ( ! $this->memberData['g_is_supmod'] )
		{
			$this->registry->output->showError( 'calendar_bad_approve', 1043, null, null, 403 );
		}

		if ( $md5check != $this->member->form_hash )
		{
			$this->registry->output->showError( 'calendar_bad_key', 2041, null, null, 403 );
		}

		//-----------------------------------------
		// Get event
		//-----------------------------------------
		
		$event	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'cal_events', 'where' => "event_id=" . $event_id . " AND event_calendar_id=" . $this->calendar['cal_id'] ) );

		if( ! $event['event_id'] )
		{
			$this->registry->output->showError( 'calendar_event_not_found', 1045, null, null, 404 );
		}

		//-----------------------------------------
		// Update event
		//-----------------------------------------
		
		$this->DB->update( 'cal_events', array( 'event_approved' => $event['event_approved'] ? 0 : 1 ), 'event_id=' . $event_id );
		
		//-----------------------------------------
		// Hide likes
		//-----------------------------------------
		
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$_like	= classes_like::bootstrap( 'calendar', 'events' );
		$_like->toggleVisibility( $event_id, $event['event_approved'] ? 0 : 1 );

		//-----------------------------------------
		// Recache
		//-----------------------------------------
		
		$this->cache->rebuildCache( 'calendar_events', 'calendar' );

		//-----------------------------------------
		// Redirect
		//-----------------------------------------
		
		$_dateData	= explode( ' ', $event['event_start_date'] );
		$_dateBits	= explode( '-', $_dateData[0] );

		$this->registry->output->silentRedirect( $this->settings['base_url'] . "app=calendar&amp;module=calendar&amp;section=view&amp;cal_id={$this->calendar['cal_id']}&amp;do=showday&amp;y={$_dateBits[0]}&amp;m={$_dateBits[1]}&amp;d={$_dateBits[2]}&amp;modfilter={$this->request['modfilter']}", $this->calendar['cal_title_seo'], true, 'cal_day' );
	}
}