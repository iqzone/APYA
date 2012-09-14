<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board vVERSION_NUMBER
 * Calendar View
 * Last Updated: $Date: 2012-03-05 17:44:03 -0500 (Mon, 05 Mar 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Calendar
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10392 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_calendar_calendar_post extends ipsCommand
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
		$this->registry->class_localization->loadLanguageFile( array( 'public_usercp' ), 'core' );
		$this->registry->class_localization->loadLanguageFile( array( 'public_post' ), 'forums' );

		$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir( 'calendar' ) . "/sources/functions.php", 'app_calendar_classes_functions', 'calendar' );
		$this->functions	= new $classToLoad( $this->registry );
		$this->calendar		= $this->functions->getCalendar();
		$this->timezone		= ( $this->memberData['time_offset'] OR $this->memberData['time_offset'] === '0' OR $this->memberData['time_offset'] === 0 ) ? $this->memberData['time_offset'] : $this->settings['time_offset'];

		//-----------------------------------------
		// Check permissions
		//-----------------------------------------

		if ( !$this->memberData['member_id'] )
		{
			$this->registry->output->showError( 'calendar_no_guests', 1046, null, null, 403 );
		}

		if ( !$this->registry->permissions->check( 'start', $this->calendar ) )
		{
			$this->registry->output->showError( 'calendar_no_post_perm', 1047, null, null, 403 );
		}

		//-----------------------------------------
		// Start output stuff
		//-----------------------------------------
		
		$this->registry->output->addNavigation( $this->lang->words['cal_page_title'], 'app=calendar', 'false', 'app=calendar' );
		$this->registry->output->addNavigation( $this->calendar['cal_title'], "app=calendar&amp;module=calendar&amp;section=view&amp;cal_id={$this->calendar['cal_id']}", $this->calendar['cal_title_seo'], 'cal_calendar' );

		//-----------------------------------------
		// What are we doing
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'newevent':
				$this->calendarEventForm( 'add' );
			break;

			case 'addnewevent':
				$this->calendarEventSave( 'add' );
			break;

			case 'edit':
				$this->calendarEventForm( 'edit' );
			break;

			case 'doedit':
				$this->calendarEventSave( 'edit' );
			break;
		}

		//-----------------------------------------
		// Output
		//-----------------------------------------

		$this->registry->output->setTitle( $this->lang->words['cal_page_title'] . ' - ' . $this->settings['board_name'] );

		$this->registry->output->addContent( $this->output );
		$this->registry->output->sendOutput();
	}

	/**
	 * Form for creating/modifying calendar events
	 *
	 * @param	string  $type  Either add or edit
	 * @return	@e void
	 */
	public function calendarEventForm( $type='add' )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$event_id		= $this->request['event_id'] ? intval( $this->request['event_id'] ) : 0;
		$calendar_jump	= $this->functions->getCalendarJump( $this->calendar['cal_id'], array( 'start' ) );
		$group_select	= '';
		$allDay			= 1;
		
		//-----------------------------------------
		// Output stuff
		//-----------------------------------------
		
		$this->registry->output->addToDocumentHead( 'javascript', "{$this->settings['js_base_url']}js/3rd_party/calendar_date_select/calendar_date_select.js" );
		$this->registry->output->addToDocumentHead( 'javascript', "{$this->settings['js_base_url']}js/3rd_party/calendar_date_select/format_{$this->settings['cal_date_format']}.js" );
		$this->registry->output->addToDocumentHead( 'importcss', "{$this->settings['css_base_url']}style_css/{$this->registry->output->skin['_csscacheid']}/calendar_select.css" );

		//-----------------------------------------
		// Adding or editing?
		//-----------------------------------------
		
		if( $type == 'add' )
		{
			if( $this->timezone )
			{
				$_offset	= ( $this->timezone * 3600 );
			}

			$nd				= $this->request['d'] ? str_pad( $this->request['d'], 2, '0', STR_PAD_LEFT ) : gmstrftime( '%d', time() + $_offset );
			$nm				= $this->request['m'] ? str_pad( $this->request['m'], 2, '0', STR_PAD_LEFT ) : gmstrftime( '%m', time() + $_offset );
			$ny				= $this->request['y'] ? $this->request['y'] : gmstrftime( '%Y', time() + $_offset );
			$nh				= $this->settings['cal_time_format'] == 'standard' ? gmstrftime( '%I', time() + $_offset ) : gmstrftime( '%H', time() + $_offset );
			$nmi			= '00';
			$nap			= gmstrftime( '%p', time() + $_offset );

			$_fauxEndTime	= strtotime( "now + 2 hours" );
			$fd				= gmstrftime( '%d', $_fauxEndTime + $_offset );
			$fm				= gmstrftime( '%m', $_fauxEndTime + $_offset );
			$fy				= gmstrftime( '%Y', $_fauxEndTime + $_offset );
			$fh				= $this->settings['cal_time_format'] == 'standard' ? gmstrftime( '%I', $_fauxEndTime + $_offset ) : gmstrftime( '%H', $_fauxEndTime + $_offset );
			$fmi			= '00';
			$fap			= gmstrftime( '%p', $_fauxEndTime + $_offset );

			$_setEndTime	= false;

			$event			= array(
									'event_smilies'		=> 1,
									'event_content'		=> '',
									'event_timeset'		=> '',
									'event_recurring'	=> 0,
									'event_id'			=> 0,
									'event_post_key'	=> $this->request['post_key'] ? $this->request['post_key'] : md5( uniqid( microtime(), true ) ),
									);
		}
		else
		{
			//-----------------------------------------
			// Get event
			//-----------------------------------------
			
			if ( !$event_id )
			{
				$this->registry->output->showError( 'calendar_event_not_found', 1048, null, null, 404 );
			}

			$event	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'cal_events', 'where' => 'event_id=' . $event_id ) );

			if ( !$event['event_id'] )
			{
				$this->registry->output->showError( 'calendar_event_not_found', 1049, null, null, 404 );
			}

			//-----------------------------------------
			// Check permissions
			//-----------------------------------------

			if ( $event['event_perms'] != '*' )
			{
				$permissionGroups	= explode( ',', IPSText::cleanPermString( $event['event_perms'] ) );
				
				if( !IPSMember::isInGroup( $this->memberData, $permissionGroups ) )
				{
					$this->registry->output->showError( 'calendar_event_not_found', 10411, null, null, 404 );
				}
			}

			if ( !$this->memberData['g_is_supmod'] AND $this->memberData['member_id'] != $event['event_member_id'] )
			{
				$this->registry->output->showError( 'calendar_no_edit_perm', 10410, null, null, 403 );
			}

			//-----------------------------------------
			// Date stuff
			//-----------------------------------------

			$_eventStart	= strtotime( $event['event_start_date'] );
			$_eventEnd		= ( $event['event_end_date'] AND $event['event_end_date'] != '0000-00-00 00:00:00' ) ? strtotime( $event['event_end_date'] ) : 0;
			
			//-----------------------------------------
			// Apply timezone offset for editing user
			//-----------------------------------------
			
			if( $this->timezone AND !$event['event_all_day'] )
			{
				$_eventStart	+= ( $this->timezone * 3600 );
				$_eventEnd		= $_eventEnd ? ( $_eventEnd + ( $this->timezone * 3600 ) ) : 0;
			}

			$_hour	= $this->settings['cal_time_format'] == 'standard' ? '%I' : '%H';
			
			list( $nd, $nm, $ny, $nh, $nmi, $nap, $_nh )	= explode( '-', gmstrftime( "%d-%m-%Y-{$_hour}-%M-%p-%H", $_eventStart ) );
			
			if( $_eventEnd )
			{
				list( $fd, $fm, $fy, $fh, $fmi, $fap )	= explode( '-', gmstrftime( "%d-%m-%Y-{$_hour}-%M-%p", $_eventEnd ) );
				$_setEndTime	= true;
			}
			else
			{
				$fd = $fm = $fy = $fh = $fmi = $fap = 0;
				$_setEndTime	= false;
			}

			$allDay	= $event['event_all_day'];
		}
		
		//-----------------------------------------
		// If we are previewing, reset..
		//-----------------------------------------
		
		if( $this->request['preview'] )
		{
			$event['event_title']		= $this->request['event_title'];
			$event['event_recurring']	= $this->request['set_recurfields'] ? $this->request['recur_unit'] : 0;
			$event['event_private']		= $this->request['e_type'] == 'public' ? 0 : 1;
			$event['event_smilies']		= $this->request['event_smilies'];
			$event['event_content']		= $_POST['Post'];
			$event['event_perms']		= count($this->request['e_groups']) ? implode( ",", $this->request['e_groups'] ) : '*';

			$_ndate						= $this->request['start_date'];
			$_stTime					= explode( ':', $this->request['start_time'] );
			$nh							= $_stTime[0];
			$nmi						= $_stTime[1];
			$nap						= $this->request['start_time_ampm'];
			$_edate						= $this->request['end_date'];
			$_etTime					= explode( ':', $this->request['end_time'] );
			$fh							= $_etTime[0];
			$fmi						= $_etTime[1];
			$fap						= $this->request['end_time_ampm'];
			$allDay						= $this->request['all_day'];
			$_setEndTime				= $this->request['set_enddate'];
			
			$this->timezone				= $this->request['event_timezone'];
		}
		
		//-----------------------------------------
		// Time zone dropdown
		//-----------------------------------------
		
		$_timeZoneOptions	= '';
		
		foreach( $this->lang->words as $_key => $_word )
		{
			if( preg_match( "/^time_(-?[\d\.]+)$/", $_key, $match ) )
			{
				$_timeZoneOptions	.= "<option value='{$match[1]}'" . ( $this->timezone == $match[1] ? " selected='selected'" : '' ) . ">{$_word}</option>\n";
			}
		}
		
		//-----------------------------------------
		// Get editor
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$editor = new $classToLoad();

		//-----------------------------------------
		// Build group select menu
		//-----------------------------------------
		
		if( $this->memberData['g_access_cp'] )
		{
			foreach( $this->caches['group_cache'] as $r )
			{
				$selected		= IPSMember::isInGroup( array( 'member_group_id' => $r['g_id'], 'mgroup_others' => '' ), explode( ',', IPSText::cleanPermString( $event['event_perms'] ) ), false ) ? " selected='selected'" : '';

				$group_select	.= "<option value='" . $r['g_id'] . "'" . $selected . ">" . $r['g_title'] . "</option>\n";
			}
		}

		//-----------------------------------------
		// Unparse event content for editing
		//-----------------------------------------
		
		if ( $event['event_content'] )
		{
			$editor->setContent( $event['event_content'], 'calendar' );
		}
		
		//-----------------------------------------
		// Get attachments class
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
		$class_attach	= new $classToLoad( $this->registry );
		$class_attach->type				= 'event';
		$class_attach->attach_post_key	= $event['event_post_key'];
		$class_attach->init();
		$class_attach->getUploadFormSettings();
		
		//-----------------------------------------
		// Date (formatted based on setting)
		//-----------------------------------------

		if( !$this->request['preview'] )
		{
			switch( $this->settings['cal_date_format'] )
			{
				case 'american':
				default:
					$_ndate	= $nm . '/' . $nd . '/' . $ny;
					$_edate	= $fm ? $fm . '/' . $fd . '/' . $fy : '';
				break;
				
				case 'danish':
					$_ndate	= $ny . '/' . $nm . '/' . $nd;
					$_edate	= $fm ? $fy . '/' . $fm . '/' . $fd : '';
				break;
				
				case 'italian':
					$_ndate	= $nd . '/' . $nm . '/' . $ny;
					$_edate	= $fm ? $fd . '/' . $fm . '/' . $fy : '';
				break;
				
				case 'db':
					$_ndate	= $ny . '-' . $nm . '-' . $nd;
					$_edate	= $fm ? $fy . '-' . $fm . '-' . $fd : '';
				break;
			}
		}

		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->output	.= $this->registry->output->getTemplate( 'calendar' )->calendarEventForm(
																								array(
																									'event'			=> $event,
																									'calendar'		=> $this->calendar,
																									'code'			=> $type == 'edit' ? 'doedit' : 'addnewevent',
																									'jump'			=> $calendar_jump,
																									'timezone'		=> $this->timezone,
																									'_time_zones'	=> $_timeZoneOptions,
																									'_can_set_rsvp'	=> $this->registry->permissions->check( 'askrsvp', $this->calendar ),
																									'dates'			=> array(
																															'nd'		=> $nd,
																															'nm'		=> $nm,
																															'ny'		=> $ny,
																															'nh'		=> $nh,
																															'nmi'		=> $nmi,
																															'nap'		=> $nap,
																															'fd'		=> $fd,
																															'fm'		=> $fm,
																															'fy'		=> $fy,
																															'fh'		=> $fh,
																															'fmi'		=> $fmi,
																															'fap'		=> $fap,
																															'allday'	=> $allDay,
																															'enddate'	=> $_setEndTime,
																															'ndate'		=> $_ndate,
																															'edate'		=> $_edate,
																															),
																									'_groupSelect'	=> $group_select,
																									'editor'		=> $editor->show( 'Post' ),
																									'button'		=> $type == 'edit' ? $this->lang->words['calendar_edit_submit'] : $this->lang->words['calendar_submit'],
																									'uploadForm'	=> $this->memberData['g_attach_max'] != -1 ? 
																														$this->registry->getClass('output')->getTemplate('post')->uploadForm( $event['event_post_key'], 'event', $class_attach->attach_stats, $event['event_id'], 0 ) : 
																														"",
																									)
																								);

		$this->registry->output->addNavigation( $this->lang->words['post_new_event'] );
	}

	/**
	 * Saves the add/edit calendar event form
	 *
	 * @param	string	$type	Either add or edit
	 * @return	@e void
	 */
	public function calendarEventSave( $type='add' )
	{
		//-----------------------------------------
		// Check permissions
		//-----------------------------------------

		if ( $this->request['auth_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'no_permission', 10410, null, null, 403 );
		}
		
		if( $this->request['preview'] )
		{
			return $this->calendarEventForm( $type );
		}
		
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$event_id		= intval( $this->request['event_id'] );
		$calendar_id	= intval( $this->request['event_calendar_id'] );
		$_calendar		= $this->functions->getCalendar( $calendar_id );
		$event_title	= IPSText::getTextClass( 'bbcode' )->stripBadWords( trim( $this->request['event_title'] ) );
		$start_date		= '';
		$end_date		= '';
		$recurring		= 0;
		
		//-----------------------------------------
		// Verify start date/time
		//-----------------------------------------

		switch( $this->settings['cal_date_format'] )
		{
			case 'american':
			default:
				$_startBits		= explode( '/', $this->request['start_date'] );
				
				if( $this->request['set_enddate'] )
				{
					$_endBits		= $this->request['end_date'] ? explode( '/', $this->request['end_date'] ) : array();
				}
			break;
			
			case 'danish':
				$_inputStart	= explode( '/', $this->request['start_date'] );
				$_startBits		= array(
										0	=> $_inputStart[1],
										1	=> $_inputStart[2],
										2	=> $_inputStart[0],
										);

				if( $this->request['set_enddate'] )
				{
					$_inputEnd		= $this->request['end_date'] ? explode( '/', $this->request['end_date'] ) : array();
					$_endBits		= array(
											0	=> $_inputEnd[1],
											1	=> $_inputEnd[2],
											2	=> $_inputEnd[0],
											);
				}
			break;
			
			case 'italian':
				$_inputStart	= explode( '/', $this->request['start_date'] );
				$_startBits		= array(
										0	=> $_inputStart[1],
										1	=> $_inputStart[0],
										2	=> $_inputStart[2],
										);

				if( $this->request['set_enddate'] )
				{
					$_inputEnd		= $this->request['end_date'] ? explode( '/', $this->request['end_date'] ) : array();
					$_endBits		= array(
											0	=> $_inputEnd[1],
											1	=> $_inputEnd[0],
											2	=> $_inputEnd[2],
											);
				}
			break;
			
			case 'db':
				$_inputStart	= explode( '-', $this->request['start_date'] );
				$_startBits		= array(
										0	=> $_inputStart[1],
										1	=> $_inputStart[2],
										2	=> $_inputStart[0],
										);

				if( $this->request['set_enddate'] )
				{
					$_inputEnd		= $this->request['end_date'] ? explode( '-', $this->request['end_date'] ) : array();
					$_endBits		= array(
											0	=> $_inputEnd[1],
											1	=> $_inputEnd[2],
											2	=> $_inputEnd[0],
											);
				}
			break;
		}

		if( !$this->request['start_date'] OR count($_startBits) != 3 )
		{
			$this->registry->output->showError( 'calendar_invalid_date', 10427.0 );
		}
		else if( !@checkdate( $_startBits[0], $_startBits[1], $_startBits[2] ) )
		{
			$this->registry->output->showError( 'calendar_invalid_date', 10427.1 );
		}

		if( $this->request['all_day'] )
		{
			$start_date	= gmmktime( 0, 0, 0, $_startBits[0], $_startBits[1], $_startBits[2] );
		}
		else
		{
			$_time		= explode( ':', $this->request['start_time'] );
			
			if( $this->settings['cal_time_format'] == 'standard' )
			{
				if( count($_time) != 2 OR $_time[0] > 12 OR $_time[1] > 59 )
				{
					$this->registry->output->showError( 'calendar_invalid_time', 10427.2 );
				}
				
				if( $this->request['start_time_ampm'] == 'PM' AND $_time[0] < 12 )
				{
					$_time[0]	+= 12;
				}
				else if( $this->request['start_time_ampm'] == 'AM' AND $_time[0] == 12 )
				{
					$_time[0]	= 0;
				}
			}
			else
			{
				if( count($_time) != 2 OR $_time[0] > 23 OR $_time[1] > 59 )
				{
					$this->registry->output->showError( 'calendar_invalid_time', 10427.2 );
				}
			}

			$start_date	= gmmktime( $_time[0], $_time[1], 0, $_startBits[0], $_startBits[1], $_startBits[2] );
		}
		
		//-----------------------------------------
		// Verify end date/time
		//-----------------------------------------
		
		if( $this->request['set_enddate'] )
		{
			if( count($_endBits) != 3 )
			{
				$this->registry->output->showError( 'calendar_invalid_date', 10427.3 );
			}
			else if( !@checkdate( $_endBits[0], $_endBits[1], $_endBits[2] ) )
			{
				$this->registry->output->showError( 'calendar_invalid_date', 10427.4 );
			}
	
			if( $this->request['all_day'] )
			{
				$end_date	= gmmktime( 0, 0, 0, $_endBits[0], $_endBits[1], $_endBits[2] );
			}
			else
			{
				$_time		= explode( ':', $this->request['end_time'] );
				
				if( $this->settings['cal_time_format'] == 'standard' )
				{
					if( count($_time) != 2 OR $_time[0] > 12 OR $_time[1] > 59 )
					{
						$this->registry->output->showError( 'calendar_invalid_date', 10427.5 );
					}
					
					if( $this->request['end_time_ampm'] == 'PM' )
					{
						$_time[0]	+= 12;
					}
				}
				else
				{
					if( count($_time) != 2 OR $_time[0] > 23 OR $_time[1] > 59 )
					{
						$this->registry->output->showError( 'calendar_invalid_date', 10427.5 );
					}
				}
	
				$end_date	= gmmktime( $_time[0], $_time[1], 0, $_endBits[0], $_endBits[1], $_endBits[2] );
			}
		}
		
		if( $end_date AND $end_date < $start_date )
		{
			$this->registry->output->showError( 'calendar_range_wrong', 10421 );
		}
		else if( $this->request['end_date'] AND $this->request['set_enddate'] AND !$end_date )
		{
			$this->registry->output->showError( 'calendar_range_wrong', 10421.1 );
		}

		//-----------------------------------------
		// Set recurring flag
		//-----------------------------------------
		
		if( $this->request['set_recurfields'] )
		{
			if( !$end_date )
			{
				$this->registry->output->showError( 'recurring_requires_enddate', 10427.6 );
			}
			
			$recurring	= intval($this->request['recur_unit']);
		}

		//-----------------------------------------
		// Adjust to GMT
		//-----------------------------------------
		
		if( $this->request['event_timezone'] AND !$this->request['all_day'] )
		{
			$start_date	= $start_date - ( $this->request['event_timezone'] * 3600 );
			
			if( $end_date )
			{
				$end_date	= $end_date - ( $this->request['event_timezone'] * 3600 );
			}
		}

		$start_date	= gmstrftime( "%Y-%m-%d %H:%M:00", $start_date );
		$end_date	= $end_date ? gmstrftime( "%Y-%m-%d %H:%M:00", $end_date ) : 0;

		//-----------------------------------------
		// Check posted content for errors
		//-----------------------------------------
		
		if ( strlen( trim( IPSText::removeControlCharacters( IPSText::br2nl( $_POST['Post'] ) ) ) ) < 1 )
		{
			$this->registry->output->showError( 'calendar_post_too_short', 10417, null, null, 403 );
		}

		if( IPSText::mbstrlen( $_POST['Post'] ) > ( $this->settings['max_post_length'] * 1024 ) )
		{
			$this->registry->output->showError( 'calendar_post_too_long', 10418, null, null, 403 );
		}

		if( !$event_title OR IPSText::mbstrlen( $event_title ) < 2 )
		{
			$this->registry->output->showError( 'calendar_no_title', 10419, null, null, 403 );
		}

		if( IPSText::mbstrlen( $event_title ) > 200 )
		{
			$this->registry->output->showError( 'calendar_title_too_long', 10420, null, null, 403 );
		}

		//-----------------------------------------
		// Adding or editing?
		//-----------------------------------------

		if ( $type == 'edit' )
		{
			//-----------------------------------------
			// Get event
			//-----------------------------------------
			
			if ( ! $event_id )
			{
				$this->registry->output->showError( 'calendar_event_not_found', 10414, null, null, 404 );
			}

			$event	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'cal_events', 'where' => 'event_id=' . $event_id ) );

			if ( !$event['event_id'] )
			{
				$this->registry->output->showError( 'calendar_event_not_found', 10415, null, null, 404 );
			}

			//-----------------------------------------
			// Do we have permission to edit?
			//-----------------------------------------

			if ( !$this->memberData['g_is_supmod'] AND $this->memberData['member_id'] != $event['event_member_id'] )
			{
				$this->registry->output->showError( 'calendar_no_edit_perm', 10416, null, null, 403 );
			}
		}

		//-----------------------------------------
		// Set event view permissions
		//-----------------------------------------

		if( $this->memberData['g_access_cp'] )
		{
			if( is_array( $this->request['e_groups'] ) )
			{
				foreach( $this->cache->getCache('group_cache') as $group )
				{
					if( $group['g_access_cp'] )
					{
						$this->request['e_groups'][] = $group['g_id'];
					}
				}

				$read_perms	= implode( ",", $this->request['e_groups'] );
			}
		}
		
		$read_perms	= $read_perms ? $read_perms : '*';

		//-----------------------------------------
		// Get editor and format post
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$editor = new $classToLoad();
		
		$event_content = $editor->process( $_POST['Post'] );
		
		IPSText::getTextClass( 'bbcode' )->parse_html		= 0;
		IPSText::getTextClass( 'bbcode' )->parse_smilies	= intval($this->request['enableemo']);
		IPSText::getTextClass( 'bbcode' )->parse_bbcode		= 1;
		IPSText::getTextClass( 'bbcode' )->parsing_section	= 'calendar';

 		$event_content	= IPSText::getTextClass( 'bbcode' )->preDbParse( $event_content );

 		//-----------------------------------------
 		// Event approved?
 		//-----------------------------------------
 		
		if( $this->request['e_type'] == 'private' )
		{
			$event_approved	= 1;
		}
		else
		{
			$event_approved	= $this->registry->permissions->check( 'nomod', $_calendar ) ? 1 : ( $_calendar['cal_moderate'] ? 0 : 1 );
		}

 		//-----------------------------------------
 		// Store the event
 		//-----------------------------------------
 		
 		if ( $type == 'add' )
 		{
			//-----------------------------------------
			// Format array for storage
			//-----------------------------------------

 			$_eventData	= array(
 								'event_calendar_id'	=> $calendar_id,
								'event_member_id'	=> $this->memberData['member_id'],
								'event_content'		=> $event_content,
								'event_title'		=> $event_title,
								'event_title_seo'	=> IPSText::makeSeoTitle( $event_title ),
								'event_smilies'		=> intval($this->request['enableemo']),
								'event_comments'	=> 0,
								'event_perms'		=> $read_perms,
								'event_private'		=> $this->request['e_type'] == 'private' ? 1 : 0,
								'event_approved'	=> $event_approved,
								'event_saved'		=> time(),
								'event_lastupdated'	=> time(),
								'event_recurring'	=> $recurring,
								'event_start_date'	=> $start_date,
								'event_end_date'	=> $end_date,
								'event_post_key'	=> $this->request['post_key'],
								'event_rsvp'		=> $this->registry->permissions->check( 'askrsvp', $_calendar ) ? intval($this->request['event_rsvp']) : 0,
								'event_sequence'	=> 0,
								'event_all_day'		=> intval($this->request['all_day']),
 								);
 			
 			//-----------------------------------------
 			// Data hooks
 			//-----------------------------------------
 			
			IPSLib::doDataHooks( $_eventData, 'calendarAddEvent' );
 			
			//-----------------------------------------
			// Insert
			//-----------------------------------------
			
			$this->DB->insert( 'cal_events', $_eventData );
			
			$event_id	= $this->DB->getInsertId();

			//-----------------------------------------
			// Set language strings
			//-----------------------------------------
			
			$_langString	= $event_approved ? $this->lang->words['new_event_redirect'] : $this->lang->words['new_event_mod'];
		}
		else
		{
			//-----------------------------------------
			// Format array for storage
			//-----------------------------------------

 			$_eventData	= array(
 								'event_calendar_id'	=> $calendar_id,
								'event_content'		=> $event_content,
								'event_title'		=> $event_title,
								'event_title_seo'	=> IPSText::makeSeoTitle( $event_title ),
								'event_smilies'		=> intval($this->request['enableemo']),
								'event_perms'		=> $read_perms,
								'event_private'		=> $this->request['e_type'] == 'private' ? 1 : 0,
								'event_approved'	=> $event_approved,
								'event_lastupdated'	=> time(),
								'event_recurring'	=> $recurring,
								'event_start_date'	=> $start_date,
								'event_end_date'	=> $end_date,
								'event_post_key'	=> $this->request['post_key'],
								'event_rsvp'		=> $this->registry->permissions->check( 'askrsvp', $_calendar ) ? intval($this->request['event_rsvp']) : $event['event_rsvp'],
								'event_sequence'	=> intval($event['event_rsvp']) + 1,
								'event_all_day'		=> intval($this->request['all_day']),
 								);

 			//-----------------------------------------
 			// Data hooks
 			//-----------------------------------------
 			
			IPSLib::doDataHooks( $_eventData, 'calendarEditEvent' );
			
			//-----------------------------------------
			// Update database
			//-----------------------------------------
			
			$this->DB->update( 'cal_events', $_eventData, 'event_id=' . $event_id );

			//-----------------------------------------
			// Set language strings
			//-----------------------------------------

			$_langString	= $event_approved ? $this->lang->words['edit_event_redirect'] : $this->lang->words['new_event_mod'];
		}
		
		//-----------------------------------------
		// Upload attachments
		//-----------------------------------------
		
		if( $this->memberData['g_attach_max'] != -1 )
		{
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
			$class_attach	= new $classToLoad( $this->registry );
			
			$class_attach->type				= 'event';
			$class_attach->attach_post_key	= $_eventData['event_post_key'];
			$class_attach->attach_rel_id	= $event_id;
			$class_attach->init();
	
			$class_attach->processMultipleUploads();
			$class_attach->postProcessUpload( array() );
		}
		
		//-----------------------------------------
		// Send notifications
		//-----------------------------------------
		
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$_like	= classes_like::bootstrap( 'calendar', $type == 'edit' ? 'events' : 'calendars' );
		
		$_url	= $this->registry->output->buildSEOUrl( 'app=calendar&amp;module=calendar&amp;section=view&amp;do=showevent&amp;event_id=' . $event_id, 'public', $_eventData['event_title_seo'], 'cal_event' );
		
		$_like->sendNotifications( $type == 'edit' ? $event_id : $_eventData['event_calendar_id'], array( 'immediate', 'offline' ), 
																			array(
																					'notification_key'		=> $type == 'edit' ? 'updated_event' : 'new_event',
																					'notification_url'		=> $_url,
																					'email_template'		=> $type. '_event_follow',
																					'email_subject'			=> sprintf( $this->lang->words[ $type . '_event_follow_subject'], $_url, $_eventData['event_title'] ),
																					'build_message_array'	=> array(
																													'NAME'  		=> '-member:members_display_name-',
																													'AUTHOR'		=> $this->memberData['members_display_name'],
																													'TITLE' 		=> $_eventData['event_title'],
																													'URL'			=> $_url,
																													)
																			) 		);
		
		//-----------------------------------------
		// Rebuild cache
		//-----------------------------------------
		
		$this->cache->rebuildCache( 'calendar_events', 'calendar' );

		//-----------------------------------------
		// Redirect
		//-----------------------------------------
		
		if ( $event_approved )
		{
			$this->registry->output->redirectScreen( $_langString, $this->settings['base_url'] . "app=calendar&amp;module=calendar&amp;section=view&amp;do=showevent&amp;event_id=" . $event_id, $_eventData['event_title_seo'], 'cal_event' );
		}
		else
		{
			$this->registry->output->redirectScreen( $_langString, $this->settings['base_url'] . "app=calendar&amp;module=calendar&amp;section=view&amp;cal_id=" . $calendar_id, $this->caches['calendars'][ $calendar_id ]['cal_title_seo'], 'cal_calendar' );
		}
	}
}