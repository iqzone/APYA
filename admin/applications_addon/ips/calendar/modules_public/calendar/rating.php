<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board vVERSION_NUMBER
 * Add rating fallback
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

class public_calendar_calendar_rating extends ipsCommand
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
		// Check key
		//-----------------------------------------
		
		if ( $this->request['secure_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'usercp_forums_bad_key', 104100, null, null, 403 );
		}
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$event_id	= intval($this->request['event_id']);
		$rating_id	= intval($this->request['rating']);

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
			$this->registry->output->showError( 'no_event_for_rate', 104101, null, null, 404 );
		}

		$event	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'cal_events', 'where' => 'event_id=' . $event_id ) );

		if( !$event['event_id'] )
		{
			$this->registry->output->showError( 'no_event_for_rate', 104102, null, null, 404 );
		}
		
		//-----------------------------------------
		// Check permissions
		//-----------------------------------------
		
		if( !$event['event_approved'] )
		{
			$this->registry->output->showError( 'no_event_for_rate', 104103, null, null, 404 );
		}

		if( $event['event_private'] )
		{
			if( ! $this->memberData['member_id'] OR $this->memberData['member_id'] != $event['event_member_id'] )
			{
				$this->registry->output->showError( 'no_event_for_rate', 104104, null, null, 404 );
			}
		}

		if( $event['event_perms'] != '*' )
		{
			$permissionGroups	= explode( ',', IPSText::cleanPermString( $event['event_perms'] ) );
			
			if( !IPSMember::isInGroup( $this->memberData, $permissionGroups ) )
			{
				$this->registry->output->showError( 'no_event_for_rate', 104105, null, null, 404 );
			}
		}

		//-----------------------------------------
		// Get our calendar
		//-----------------------------------------
		
		$calendar			= $functions->getCalendar( $event['event_calendar_id'] );
		
		if( !$calendar['cal_id'] OR $calendar['cal_id'] != $event['event_calendar_id'] )
		{
			$this->registry->output->showError( 'no_event_for_rate', 104106, null, null, 404 );
		}

		//-----------------------------------------
		// Can we rate?
		//-----------------------------------------
		
		$_can_rate	= ( $this->memberData['member_id'] AND $this->registry->permissions->check( 'rate', $calendar ) ) ? intval( $this->memberData['g_topic_rate_setting'] ) : 0;

		if ( ! $_can_rate )
		{
			$this->registry->output->showError( 'no_rate_permission', 104107, null, null, 403 );
		}

		//-----------------------------------------
		// Sneaky members rating more than 5?
		//-----------------------------------------

		if( $rating_id > 5 )
		{
			$rating_id = 5;
		}
	
		if( $rating_id < 0 )
		{
			$rating_id = 0;
		}

		//-----------------------------------------
		// Have we rated before?
		//-----------------------------------------

		$rating	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'cal_event_ratings', 'where' => "rating_eid={$event['event_id']} and rating_member_id=" . $this->memberData['member_id'] ) );

		if ( $rating['rating_id'] )
		{
			//-----------------------------------------
			// Do we allow re-ratings?
			//-----------------------------------------

			if ( $this->memberData['g_topic_rate_setting'] == 2 )
			{
				if ( $rating_id != $rating['rating_value'] )
				{
					$new_rating	= $rating_id - $rating['rating_value'];
					
					$this->DB->update( 'cal_event_ratings', array( 'rating_value' => $rating_id ), 'rating_id=' . $rating['rating_id'] );
					
					$this->DB->update( 'cal_events', array(
														'event_rating_total'	=> intval($event['event_rating_total']) + $new_rating,
														'event_rating_avg'		=> round( ( intval($event['event_rating_total']) + $new_rating ) / $event['event_rating_hits'] )
														), 'event_id=' . $event['event_id'] );
				}

				$this->registry->output->redirectScreen( $this->lang->words['event_rating_changed'] , $this->settings['base_url'] . "app=calendar&amp;module=calendar&amp;section=view&amp;do=showevent&amp;event_id={$event['event_id']}", $event['event_title_seo'], 'cal_event' );
			}
			else
			{
				$this->registry->output->redirectScreen( $this->lang->words['event_rated_already'] , $this->settings['base_url'] . "app=calendar&amp;module=calendar&amp;section=view&amp;do=showevent&amp;event_id={$event['event_id']}", $event['event_title_seo'], 'cal_event' );
			}
		}
		else
		{
			$this->DB->insert( 'cal_event_ratings', array( 
														'rating_eid'		=> $event['event_id'],
														'rating_member_id'	=> $this->memberData['member_id'],
														'rating_value'		=> $rating_id,
														'rating_ip_address'	=> $this->member->ip_address,
							)							);

			$this->DB->update( 'cal_events', array( 
													'event_rating_total'	=> intval( $event['event_rating_total'] ) + $rating_id,
													'event_rating_hits'		=> intval( $event['event_rating_hits'] ) + 1,
													'event_rating_avg'		=> round( ( intval( $event['event_rating_total'] ) + $rating_id ) / ( intval( $event['event_rating_hits'] ) + 1 ) ),
												), 'event_id=' . $event_id );
		}

		$this->registry->output->redirectScreen( $this->lang->words['event_rating_done'] , $this->settings['base_url'] . "app=calendar&amp;module=calendar&amp;section=view&amp;do=showevent&amp;event_id={$event['event_id']}", $event['event_title_seo'], 'cal_event' );
 	}
}
