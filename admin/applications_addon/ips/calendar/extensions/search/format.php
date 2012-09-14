<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board vVERSION_NUMBER
 * Formats calendar search results
 * Last Updated: $Date: 2011-09-08 15:22:26 -0400 (Thu, 08 Sep 2011) $
 * </pre>
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Calendar
 * @link		http://www.invisionpower.com
 * @version		$Rev: 9469 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class search_format_calendar extends search_format
{
	/**
	 * Constructor
	 *
	 * @param	object	ipsRegistry
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		$registry->class_localization->loadLanguageFile( array( 'public_calendar' ), 'calendar' );

		parent::__construct( $registry );
	}
	
	/**
	 * Parse search results
	 *
	 * @access	private
	 * @param	array 	$r				Search result
	 * @return	array 	$html			Blocks of HTML
	 */
	public function parseAndFetchHtmlBlocks( $rows )
	{
		return parent::parseAndFetchHtmlBlocks( $rows );
	}
	
	/**
	 * Formats the forum search result for display
	 *
	 * @access	public
	 * @param	array   $event		Array of data
	 * @return	mixed	Formatted content, ready for display, or array containing a $sub section flag, and content
	 */
	public function formatContent( $event )
	{
		//-----------------------------------------
		// Times and dates
		//-----------------------------------------
		
		$event['_start_time']	= strtotime( $event['event_start_date'] );
		$event['_end_time']		= ( $event['event_end_date'] AND $event['event_end_date'] != '0000-00-00 00:00:00' ) ? strtotime( $event['event_end_date'] ) : 0;

		if( !$event['event_all_day'] )
		{
			if( $this->memberData['member_id'] AND $this->memberData['time_offset'] )
			{
				$event['_start_time']	= $event['_start_time'] + ( $this->memberData['time_offset'] * 3600 );
			}
			else if( !$this->memberData['member_id'] AND $this->settings['time_offset'] )
			{
				$event['_start_time']	= $event['_start_time'] + ( $this->settings['time_offset'] * 3600 );
			}
		}
		
		if( !$event['event_all_day'] )
		{
			if( $this->memberData['member_id'] AND $this->memberData['time_offset'] )
			{
				$event['_end_time']		= $event['_end_time'] ? ( $event['_end_time'] + ( $this->memberData['time_offset'] * 3600 ) ) : 0;
			}
			else if( !$this->memberData['member_id'] AND $this->settings['time_offset'] )
			{
				$event['_end_time']		= $event['_end_time'] ? ( $event['_end_time'] + ( $this->settings['time_offset'] * 3600 ) ) : 0;
			}
		}

		$event['_start_date']	= gmstrftime( $this->settings['clock_date'], $event['_start_time'] );
		$event['_event_time']	= '';
		$event['_event_etime']	= '';
		
		if( !$event['event_all_day'] )
		{
			$event['_event_time']	= ltrim( gmstrftime( '%I:%M %p', $event['_start_time'] ), '0' );
			$event['_event_etime']	= $event['_end_time'] ? ltrim( gmstrftime( '%I:%M %p', $event['_end_time'] ), '0' ) : ':00';
			
			if( strpos( ':00', $event['_event_time'] ) === 0 OR gmstrftime( '%H:%M', $event['_start_time'] ) == '00:00' )
			{
				$event['_event_time']	= '';
			}

			if( strpos( ':00', $event['_event_etime'] ) === 0 OR gmstrftime( '%H:%M', $event['_end_time'] ) == '00:00' )
			{
				$event['_event_etime']	= '';
			}
		}

		//-----------------------------------------
		// Event type
		//-----------------------------------------
		
		$type	= $this->lang->words['se_normal'];
		$ends	= '';
	
		if( !$event['event_recurring'] AND $event['_end_time'] AND gmstrftime( $this->settings['clock_date'], $event['_end_time'] ) != $event['_start_date'] )
		{
			$type	= $this->lang->words['se_range'];
			$ends	= sprintf( $this->lang->words['se_ends'], gmstrftime( $this->settings['clock_date'], $event['_end_time'] ) );
		}
		else if ( $event['event_recurring'] )
		{
			$type	= $this->lang->words['se_recur'];
			$ends	= sprintf( $this->lang->words['se_ends'], gmstrftime( $this->settings['clock_date'], $event['_end_time'] ) );
		}

		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		return array( ipsRegistry::getClass( 'output' )->getTemplate( 'calendar' )->calEventSearchResult( $event, IPSSearchRegistry::get('opt.searchType') == 'titles' ? true : false, $type, $ends ), 0 );
	}

	/**
	 * Return the output for the followed content results
	 *
	 * @param	array 	$results	Array of results to show
	 * @param	array 	$followData	Meta data from follow/like system
	 * @return	@e string
	 */
	public function parseFollowedContentOutput( $results, $followData )
	{
		/* Events? */
		if( IPSSearchRegistry::get('in.followContentType') == 'events' )
		{
			if( count($results) )
			{
				$_results	= array();
				
				$this->DB->build( array( 
										'select'	=> "c.*",
										'from'		=> array( 'cal_events' => 'c' ),
										'where'		=> 'c.event_id IN(' . implode( ',', $results ) . ')',
										'order'		=> 'c.event_start_date DESC',
										'add_join'	=> array(
															array(
																	'select'	=> 'i.*',
																	'from'		=> array( 'permission_index' => 'i' ),
																	'where'		=> "i.perm_type='calendar' AND i.perm_type_id=c.event_calendar_id",
																	'type'		=> 'left',
																),
															array(
																	'select'	=> 'mem.*',
																	'from'		=> array( 'members' => 'mem' ),
																	'where'		=> "mem.member_id=c.event_member_id",
																	'type'		=> 'left',
																),
															)
											)		);
				$this->DB->execute();
		
				while( $r = $this->DB->fetch() )
				{
					$_results[ $r['event_id'] ] = $this->genericizeResults( $r );
				}

				/* Merge in follow data */
				foreach( $followData as $_follow )
				{
					$_results[ $_follow['like_rel_id'] ]['_followData']	= $_follow;
				}
			}

			return $this->registry->output->getTemplate('calendar')->calEventFollowedWrapper( $this->parseAndFetchHtmlBlocks( $_results ) );
		}
		/* Or calendars? */
		else
		{
			$calendars	= array();
			$member_ids	= array();
			
			if( count($results) )
			{
				/* Load calendars cache */
				$_calCache	= $this->cache->getCache( 'calendars' );

				/* Get calendar data */
				foreach( $results as $result )
				{
					$calendars[ $result ]	= $_calCache[ $result ];
				}
				
				/* Merge in follow data */
				foreach( $followData as $_follow )
				{
					$calendars[ $_follow['like_rel_id'] ]['_followData']	= $_follow;
				}
			}

			return $this->registry->output->getTemplate('calendar')->followedContentCalendars( $calendars );
		}
	}

	/**
	 * Formats / grabs extra data for results
	 * Takes an array of IDS (can be IDs from anything) and returns an array of expanded data.
	 *
	 * @param	array 	$ids			Ids
	 * @param	array	$followData		Retrieve the follow meta data
	 * @return array
	 */
	public function processResults( $ids )
	{
		$rows = array();
		
		foreach( $ids as $i => $d )
		{
			$rows[ $i ] = $this->genericizeResults( $d );
		}
		
		return $rows;	
	}
	
	/**
	 * Reassigns fields in a generic way for results output
	 *
	 * @param  array  $r
	 * @return array
	 */
	public function genericizeResults( $r )
	{
		$r['app']			= 'calendar';
		$r['content']		= $r['event_content'];
		$r['content_title']	= $r['event_title'];
		$r['updated']		= $r['event_lastupdated'];
		$r['type_2']		= 'event';
		$r['type_id_2']		= $r['event_id'];
		$r['misc']			= null;
		$r['member_id']		= $r['event_member_id'];
		
		return $r;
	}

}