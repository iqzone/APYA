<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board vVERSION_NUMBER
 * RSVP for event
 * Last Updated: $Date: 2011-05-05 07:03:47 -0400 (Thu, 05 May 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Calendar
 * @link		http://www.invisionpower.com
 * @since		25th January 2011
 * @version		$Revision: 8644 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_calendar_ajax_rsvp extends ipsAjaxCommand
{
	/**
	* Class entry point
	*
	* @access	public
	* @param	object		Registry reference
	* @return	@e void		[Outputs to screen/redirects]
	*/
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$event_id	= intval($this->request['event_id']);

		$this->registry->class_localization->loadLanguageFile( array( 'public_calendar' ) );
		
		//-----------------------------------------
		// Functions class
		//-----------------------------------------
		
		$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir( 'calendar' ) . "/sources/functions.php", 'app_calendar_classes_functions', 'calendar' );
		$functions			= new $classToLoad( $this->registry );
		
		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		if ( ! $event_id )
		{
			$this->returnJsonError( $this->lang->words['rsvp__no_event'] );
		}

		$event	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'cal_events', 'where' => 'event_id=' . $event_id ) );

		if( !$event['event_id'] )
		{
			$this->returnJsonError( $this->lang->words['rsvp__no_event'] );
		}
		
		//-----------------------------------------
		// Check permissions
		//-----------------------------------------
		
		if( !$event['event_approved'] )
		{
			$this->returnJsonError( $this->lang->words['rsvp__no_event'] );
		}

		if( $event['event_private'] )
		{
			if( ! $this->memberData['member_id'] OR $this->memberData['member_id'] != $event['event_member_id'] )
			{
				$this->returnJsonError( $this->lang->words['rsvp__no_event'] );
			}
		}

		if( $event['event_perms'] != '*' )
		{
			$permissionGroups	= explode( ',', IPSText::cleanPermString( $event['event_perms'] ) );
			
			if( !IPSMember::isInGroup( $this->memberData, $permissionGroups ) )
			{
				$this->returnJsonError( $this->lang->words['rsvp__no_event'] );
			}
		}

		//-----------------------------------------
		// Get our calendar
		//-----------------------------------------
		
		$calendar			= $functions->getCalendar( $event['event_calendar_id'] );
		
		if( !$calendar['cal_id'] OR $calendar['cal_id'] != $event['event_calendar_id'] )
		{
			$this->returnJsonError( $this->lang->words['rsvp__no_event'] );
		}

		//-----------------------------------------
		// Can we RSVP?
		//-----------------------------------------
		
		$_can_rsvp	= ( $this->memberData['member_id'] AND $this->registry->permissions->check( 'rsvp', $calendar ) AND $event['event_rsvp'] ) ? 1 : 0;

		if ( ! $_can_rsvp )
		{
			$this->returnJsonError( $this->lang->words['rsvp__no_perm'] );
		}

		//-----------------------------------------
		// Have we already RSVP?
		//-----------------------------------------

		$_check	= $this->DB->buildAndFetch( array( 'select' => 'rsvp_id', 'from' => 'cal_event_rsvp', 'where' => 'rsvp_event_id=' . $event['event_id'] . ' AND rsvp_member_id=' . $this->memberData['member_id'] ) );
		
		if( $_check['rsvp_id'] )
		{
			$this->returnJsonError( $this->lang->words['rsvp__already_rsvp'] );
		}

		//-----------------------------------------
		// Store RSVP
		//-----------------------------------------

		$_insert	= array(
							'rsvp_event_id'		=> $event['event_id'],
							'rsvp_member_id'	=> $this->memberData['member_id'],
							'rsvp_date'			=> time(),
							);

		$this->DB->insert( 'cal_event_rsvp', $_insert );
		
		$rsvp_id	= $this->DB->getInsertId();
		
		$this->returnJsonArray( array( 'html' => $this->registry->output->getTemplate( 'calendar' )->eventAttendee( array_merge( array( 'rsvp_id' => $rsvp_id, 'rsvp_date' => time() ), $this->memberData ), $event ) ) );
 	}
}
