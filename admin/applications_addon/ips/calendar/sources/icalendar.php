<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board vVERSION_NUMBER
 * Library to facilitate iCalendar input and output
 * Last Updated: $Date: 2012-02-27 16:50:51 -0500 (Mon, 27 Feb 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Calendar
 * @link		http://www.invisionpower.com
 * @since		5th January 2005
 * @version		$Revision: 10365 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class app_calendar_classes_icalendarOutput
{
	/**#@+
	 * Registry objects
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	/**#@-*/

	/**
	 * Calendar data
	 *
	 * @var		array
	 */
	protected $calendar			= array();

	/**
	 * Calendar events to insert in feed
	 *
	 * @var		array
	 */
	protected $_events			= array();

	/**
	 * Calendar functions
	 *
	 * @var		object
	 */
	protected $functions;
	
	/**
	 * Error message from import
	 *
	 * @var		string
	 */
	protected $_error			= '';
	
	/**
	 * Type of begin block we are currently parsing
	 *
	 * @var		string
	 */
	protected $_begin			= '';
	
	/**
	 * Raw iCalendar feed data after parsing
	 *
	 * @var		array
	 */
	protected $_parsedIcsData	= array();
	
	/**
	 * Raw iCalendar data before parsing
	 *
	 * @var		array
	 */
	protected $_rawIcsData		= array();
	
	/**
	 * Temp: Current timezone ID we are parsing
	 *
	 * @var		string
	 */
	protected $_tzId			= '';

	/**
	 * Temp: Current timezone ID we are beginning to parse
	 *
	 * @var		string
	 */
	protected $_tzBegin			= '';

	/**
	 * Earliest timestamp from feed
	 *
	 * @var		int
	 */
	protected $_feedEarliest	= 0;

	/**
	 * Latest timestamp from feed
	 *
	 * @var		int
	 */
	protected $_feedLatest		= 0;
	
	/**
	 * Constructor
	 *
	 * @param	object		Registry object
	 * @param	int			Calendar we want to output
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $calendarId )
	{
		/* Make object */
		$this->registry = $registry;
		$this->DB       = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang     = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache    = $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();

		//-----------------------------------------
		// Language files
		//-----------------------------------------
		
		$this->registry->class_localization->loadLanguageFile( array( 'public_calendar' ), 'calendar' );

		//-----------------------------------------
		// Functions class
		//-----------------------------------------
		
		$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir( 'calendar' ) . "/sources/functions.php", 'app_calendar_classes_functions', 'calendar' );
		$this->functions	= new $classToLoad( $this->registry, true );

		//-----------------------------------------
		// Get our calendar
		//-----------------------------------------
		
		$this->calendar		= $this->functions->getCalendar( $calendarId );
		
	}

	/**
	 * Add an event
	 *
	 * @param	array 	Event data
	 * @return	@e void
	 */
	public function addEvent( $event )
	{
		if( $event['event']['event_id'] )
		{
			$this->_events[ $event['event']['event_id'] ]	= $event;
		}
	}

	/**
	 * Remove an event
	 *
	 * @param	int 	Event id
	 * @return	@e void
	 */
	public function removeEvent( $eventId )
	{
		if( $eventId )
		{
			unset($this->_events[ $eventId ]);
		}
	}

	/**
	 * Return events to be output
	 *
	 * @return	@e void
	 */
	public function getEvents()
	{
		return $this->_events;
	}
	
	/**
	 * Build iCalendar feed and return
	 *
	 * @return	string		iCalendar feed (can be downloaded or sent as webcal subscription)
	 */
	public function buildICalendarFeed()
	{
		//-----------------------------------------
		// Start of output
		//-----------------------------------------

		$output	 = "BEGIN:VCALENDAR\r\n";
		$output	.= "VERSION:2.0\r\n";
		$output	.= "PRODID:-//IP.Board Calendar " . ipsRegistry::$version . "//EN\r\n";
		$output	.= "METHOD:PUBLISH\r\n";
		$output	.= "CALSCALE:GREGORIAN\r\n";
		$output	.= "X-WR-CALNAME:" . $this->_encodeSpecialCharacters( $this->calendar['cal_title'] ) . "\r\n";
		
		//-----------------------------------------
		// Format the time zones (GMT only)
		//-----------------------------------------
		
		$output	.= $this->_addTimezones();
		
		//-----------------------------------------
		// Format the events
		//-----------------------------------------
		
		$output	.= $this->_addEvents();
		
		//-----------------------------------------
		// End of output
		//-----------------------------------------
		
		$output	.= "END:VCALENDAR\r\n";
		
		//-----------------------------------------
		// Return the output
		//-----------------------------------------
		
		return $output;
	}
	
	/**
	 * Build the VEVENT parts of the iCalendar feed
	 *
	 * @return	string
	 */
	protected function _addEvents()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$output	= '';

		//-----------------------------------------
		// Loop over events
		//-----------------------------------------
		
		$events	= $this->getEvents();

		if( count($events) )
		{
			foreach( $events as $event )
			{
				//-----------------------------------------
				// Basic output stuff
				//-----------------------------------------
				
				$output	.= "BEGIN:VEVENT\r\n";
				$output	.= "SUMMARY:" . $this->_encodeSpecialCharacters( $event['event']['event_title'] ) . "\r\n";
				$output	.= "DTSTAMP:" . $this->_buildIcalDate( $event['event']['event_saved'] ) . "\r\n";
				$output	.= "SEQUENCE:" . $event['event']['event_sequence'] . "\r\n";
				$output	.= "UID:" . $this->_buildUid( array( $event['event']['event_saved'], $event['event']['event_id'] ) ) . "\r\n";
				$output	.= $this->_foldLines( "ORGANIZER;CN=\"" . $this->_encodeSpecialCharacters( $event['member']['members_display_name'], false ) . '":' . $this->settings['email_out'] ) . "\r\n";
				
				//-----------------------------------------
				// Attachments...
				//-----------------------------------------
				
				$attachments	= array();
				
				if( $event['event']['event_attach_content'] )
				{
					preg_match_all( "/(http.+?attach_id=(\d+))/i", $event['event']['event_attach_content'], $matches );

					if( is_array($matches) AND count($matches) )
					{
						foreach( $matches[2] as $k => $v )
						{
							$attachments[ $v ]	= $v;
						}
					}
				}
				
				preg_match_all( "/(http.+?attach_id=(\d+))/i", $event['event']['event_content'], $matches );
				
				if( is_array($matches) AND count($matches) )
				{
					foreach( $matches[2] as $k => $v )
					{
						$attachments[ $v ]	= $v;
					}
				}
				
				if( count($attachments) )
				{
					$this->DB->build( array( 'select' => '*', 'from' => 'attachments', 'where' => 'attach_id IN(' . implode( ',', $attachments ) . ')' ) );
					$this->DB->execute();
					
					while( $r = $this->DB->fetch() )
					{
						$output	.= "ATTACH;FMTTYPE=" . $this->caches['attachtypes'][ $r['attach_ext'] ]['atype_mimetype'] . ":" . $this->registry->output->buildSEOUrl( "app=core&module=attach&section=attach&attach_id=" . $r['attach_id'], 'public' ) . "\r\n";
					}
				}
				
				//-----------------------------------------
				// Output description/content
				//-----------------------------------------
				
				$output	.= "DESCRIPTION:" . $this->_encodeSpecialCharacters( $event['event']['event_content'] ) . "\r\n";
				
				//-----------------------------------------
				// Figure out times (the hard part...)
				//-----------------------------------------
				
				$_startTime	= @strtotime( $event['event']['event_start_date'] );
				$_endTime	= @strtotime( $event['event']['event_end_date'] );
				$_allDay	= $event['event']['event_all_day'];

				if( $event['event']['event_recurring'] )
				{
					if( $event['event_all_day'] )
					{
						$output	.= "DTSTART;VALUE=DATE:" . $this->_buildIcalDate( $_startTime, true ) . "\r\n";
						$output	.= "DTEND;VALUE=DATE:" . $this->_buildIcalDate( $_startTime + 86400, true ) . "\r\n";
					}
					else
					{
						$output	.= "DTSTART;TZID=Europe/London:" . $this->_buildIcalDate( $_startTime ) . "\r\n";
						$output	.= "DTEND;TZID=Europe/London:" . $this->_buildIcalDate( $_startTime ) . "\r\n";
					}
				}
				else if( $_endTime )
				{
					if( $_allDay )
					{
						$output	.= "DTSTART;VALUE=DATE:" . $this->_buildIcalDate( $_startTime, true ) . "\r\n";
						$output	.= "DTEND;VALUE=DATE:" . $this->_buildIcalDate( $_endTime + 86400, true ) . "\r\n";
					}
					else
					{
						$output	.= "DTSTART;TZID=Europe/London:" . $this->_buildIcalDate( $_startTime ) . "\r\n";
						$output	.= "DTEND;TZID=Europe/London:" . $this->_buildIcalDate( $_endTime + 86400 ) . "\r\n";
					}
				}
				else
				{
					if( $_allDay )
					{
						$output	.= "DTSTART;VALUE=DATE:" . $this->_buildIcalDate( $_startTime, true ) . "\r\n";
					}
					else
					{
						$output	.= "DTSTART;TZID=Europe/London:" . $this->_buildIcalDate( $_startTime ) . "\r\n";
					}
				}
				
				//-----------------------------------------
				// Recurring
				//-----------------------------------------
				
				if ( $event['event']['event_recurring'] )
				{
					$_freq	= $event['event']['event_recurring'] == 3 ? 'YEARLY' : ( $event['event']['event_recurring'] == 2 ? 'MONTHLY' : 'WEEKLY' );

					$output	.= "RRULE:FREQ=" . $_freq . ";INTERVAL=1;UNTIL=" . $this->_buildIcalDate( $_endTime ) . "\r\n";
				}
				
				//-----------------------------------------
				// Attendees?
				//-----------------------------------------
				
				if( is_array($event['event']['_rsvp_attendees']) AND count($event['event']['_rsvp_attendees']) )
				{
					foreach( $event['event']['_rsvp_attendees'] as $mid => $member )
					{
						$output	.= $this->_foldLines( "ATTENDEE;CN=\"" . $this->_encodeSpecialCharacters( $member['members_display_name'], false ) . '";CUTYPE=INDIVIDUAL;PARTSTAT=ACCEPTED:' . $this->settings['email_out'] ) . "\r\n";
					}
				}
				
				$output	.= "END:VEVENT\r\n";
			}
		}

		//-----------------------------------------
		// Return the output
		//-----------------------------------------
		
		return $output;
	}
	
	/**
	 * Build the VTIMEZONE parts of the iCalendar feed
	 *
	 * @return	string
	 */
	protected function _addTimezones()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$output	= '';

		//-----------------------------------------
		// Get the needed data
		//-----------------------------------------
		
		$events	= $this->getEvents();
		$years	= array();
		
		if( count($events) )
		{
			foreach( $events as $event )
			{
				$_startTime	= @strtotime( $event['event']['event_start_date'] );
				$_endTime	= @strtotime( $event['event']['event_end_date'] );
				
				$_year				= gmdate( 'Y', $_startTime );
				$years[ $_year ]	= $_year;
				
				if( $_endTime )
				{
					while( $_startTime < $_endTime )
					{
						$_year				= gmdate( 'Y', $_startTime );
						$years[ $_year ]	= $_year;
						
						$_year				= gmdate( 'Y', $_endTime );
						$years[ $_year ]	= $_year;
						
						$_startTime	+= 2592000;	// add one month
					}
				}
			}
		}
		
		//-----------------------------------------
		// Add the timezones to the object
		//-----------------------------------------
		
		foreach( $years as $year )
		{
			$_daylight_start	= strtotime( 'last Sunday of March ' . $year );
			$_standard_start	= strtotime( 'last Sunday of October ' . $year );
			$_daylight			= gmmktime( 2, 0, 0, 3 , gmdate( 'j', $_daylight_start ), $year );
			$_standard			= gmmktime( 2, 0, 0, 10, gmdate( 'j', $_standard_start ), $year );
			
			$output	.= "BEGIN:VTIMEZONE\r\n";
			$output	.= "TZID:Europe/London\r\n";
			$output	.= "TZURL:http://tzurl.org/zoneinfo/Europe/London\r\n";
			$output	.= "X-LIC-LOCATION:Europe/London\r\n";

			$output	.= "BEGIN:DAYLIGHT\r\n";
			$output	.= "TZOFFSETFROM:+0000\r\n";
			$output	.= "TZOFFSETTO:+0100\r\n";
			$output	.= "TZNAME:BST\r\n";
			$output	.= "DTSTART:" . $this->_buildIcalDate( $_daylight ) . "\r\n"; 
			$output	.= "RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU\r\n";
			$output	.= "END:DAYLIGHT\r\n";

			$output	.= "BEGIN:STANDARD\r\n";
			$output	.= "TZOFFSETFROM:+0100\r\n";
			$output	.= "TZOFFSETTO:+0000\r\n";
			$output	.= "TZNAME:GMT\r\n";
			$output	.= "DTSTART:" . $this->_buildIcalDate( $_standard ) . "\r\n"; 
			$output	.= "RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU\r\n";
			$output	.= "END:STANDARD\r\n";

			$output	.= "END:VTIMEZONE\r\n";
		}
		
		//-----------------------------------------
		// Return the output
		//-----------------------------------------
		
		return $output;
	}
	
	/**
	 * Return a UID for iCalendar
	 *
	 * @param	array		Parameters to use to generate UID
	 * @param	string		UID
	 */
	protected function _buildUid( $params )
	{
		return md5( implode( '.', $params ) ) . '@' . $_SERVER['SERVER_ADDR'];
	}
	
	/**
	 * Return a date in iCalendar format
	 *
	 * @param	int			Timestamp
	 * @param	bool		Build date only format
	 * @param	string		Date in format of YYYYMMDDTHHMMSSZ OR YYYYMMDD
	 */
	protected function _buildIcalDate( $time, $dateOnly=false )
	{
		if( $dateOnly )
		{
			return gmdate( 'Ymd', $time );
		}
		else
		{
			return gmdate( 'Ymd\THis\Z', $time );
		}
	}
	
	/**
	 * Encode special characters in a string for iCalendar
	 *
	 * @param	string		String to encode
	 * @param	bool		Line-fold
	 * @param	string		Encoded string
	 */
	protected function _encodeSpecialCharacters( $text, $lineFold=true )
	{
		$text	= strip_tags( IPSText::br2nl( $text ) );
		$text	= str_replace( "\\", "\\\\", $text );
		$text	= str_replace( "\n" , '\\n', $text );
		$text	= str_replace( "\r" , '\\n', $text );
		$text	= str_replace( ','  , '\,', $text );
		$text	= str_replace( ';'  , '\;', $text );
		//$text	= str_replace( ':'  , '\:', $text );
		$text	= str_replace( '"', '\"', $text );
		
		if( $lineFold )
		{
			$text	= $this->_foldLines( $text );
		}
		
		return $text;
	}
	
	/**
	 * Un-encode special characters in a string coming from iCalendar feed
	 *
	 * @param	string		String to unencode
	 * @param	string		Encoded string
	 * @link	http://community.invisionpower.com/tracker/issue-33787-formatting-problem-with-imported-ics-file-calendar-app/
	 */
	protected function _unencodeSpecialCharacters( $text )
	{
		/* Reverse encoding */
		if( stripos( $text, 'encoding=' ) === 0 )
		{
			preg_match( "#encoding=(.+?):(.+?)$#i", $text, $matches );
			
			if( $matches[1] )
			{
				switch( strtolower($matches[1]) )
				{
					case 'base64':
						$text	= base64_decode( $matches[2] );
					break;
					
					case 'quoted-printable':
						$text	= quoted_printable_decode( $matches[2] );
					break;
				}
			}
			else
			{
				$text	= substr( $text, strpos( $text, ':' ) );
			}
		}

		$text	= str_replace( '\\n', "\n", $text );
		$text	= str_replace( '\,', "," , $text );
		$text	= str_replace( '\;', ";" , $text );
		$text	= str_replace( '\:', ":" , $text );
		$text	= str_replace( 'DQUOTE', '"' , $text );

		return $text;
	}
	
	/**
	 * Fold lines per RFC2445
	 *
	 * @param	string		$text	String to fold
	 * @return	string
	 * @link	https://gist.github.com/81747
	 */
	protected function _foldLines( $text )
	{
		/* Do we have mb functions? */
		if ( function_exists('mb_list_encodings') AND function_exists('mb_substr') AND function_exists('mb_strlen') )
		{
			$valid_encodings	= mb_list_encodings();

			if ( count($valid_encodings) )
			{
				/* Does mb functions cover our charset? */
				if ( in_array( strtoupper(IPS_DOC_CHAR_SET), $valid_encodings ) )
				{
					mb_internal_encoding( strtoupper(IPS_DOC_CHAR_SET) );
					
					$return	= array();
					$_extra	= 15; /* Takes into account line beginning, i.e. "DESCRIPTION:" */
					
					while( strlen($text) > 60 )
					{
						$space	= 75 - $_extra; /* Remove line beginning - subsequent loops this will be tab character */
						$mbcc	= $space;
						
						while( $mbcc )
						{
							$line	= mb_substr( $text, 0, $mbcc );	/* Get first chunk of chars */
							$octet	= strlen( $line ); /* Determine how long this really is (3-byte letters could triple the size) */
							
							/* Too long ? */
							if( $octet > $space )
							{
								$mbcc -= $octet - $space;
							}
							else
							{
								$return[]	= $line;
								$_extra		= 1;
								$text		= mb_substr( $text, $mbcc );
								break;
							}
						}
					}
					
					/* Anything left? */
					if( !empty($text) )
					{
						$return[]	= $text;
					}
					
					/* Return now */
					return implode( "\r\n\t", $return );
				}
			}
		}
		
		/* Use regular string functions - can break mb characters however, but if mb charset is used, mb functions should be enabled anyways */
		return implode( "\r\n\t", str_split( $text, 60 ) );
	}

	/**
	 * Unfold lines per RFC2445 4.1
	 *
	 * @param	string		$string	Starting string
	 * @param	int			$line	Starting line number
	 * @param	string
	 */
	protected function _unfoldLines( $string, $line )
	{
		//-----------------------------------------
		// Recursively unfold as needed
		//-----------------------------------------
		
		if( isset( $this->_rawIcsData[ $line + 1 ] ) AND ( substr( $this->_rawIcsData[ $line + 1 ], 0, 1 ) == ' ' OR substr( $this->_rawIcsData[ $line + 1 ], 0, 1 ) == "\t" ) )
		{
			$string	.= ltrim( $this->_rawIcsData[ $line + 1 ] );
			$string	= $this->_unfoldLines( $string, $line + 1 );
		}
		
		return $string;
	}
	
	/**
	 * Unparse time information from iCalendar datetime info
	 *
	 * @param	string		$string	iCalendar line
	 * @return	array 		Time information
	 */
	protected function _unparseTimeInfo( $string )
	{
		if( strpos( $string, '=' ) !== false )
		{
			$_tmp	= explode( '=', $string );
			$_key	= explode( ';', $_tmp[0] );
			$tmp	= explode( ':', $_tmp[1] );
		}
		else
		{
			$_tmp	= array();
			$_key	= array();
			$tmp	= explode( ':', $string );
			$tmp[0]	= 'DATETIME';
		}
		
		$tzid	= '';
		
		//-----------------------------------------
		// Got a TZID?
		//-----------------------------------------

		if ( $_key[1] == 'TZID' )
		{
			$tzid	= $tmp[0];
		}

		//-----------------------------------------
		// Is it a date?
		//-----------------------------------------
		
		if ( $tmp[0] == 'DATE' )
		{
			$timestamp	= strtotime( $tmp[1] );
		}
		else 
		{
			$timestamp	= '?';
		}

		$return  = array(
							'type'		=> $tmp[0],
							'raw'		=> $tmp[1],
							'raw_ts'	=> strtotime( $tmp[1] ),
							'ts'		=> $timestamp,
							'tzid'		=> str_replace( '"', '', $tzid ),
						);
						
		//-----------------------------------------
		// Earliest? Latest?
		//-----------------------------------------
		
		if ( ( $this->_feedEarliest == 0 ) OR ( $return['raw_ts'] < $this->_feedEarliest ) )
		{
			$this->_feedEarliest	= $return['raw_ts'];
		}
		
		if ( ( $this->_feedLatest == 0 ) OR ( $return['raw_ts'] > $this->_feedLatest ) )
		{
			$this->_feedLatest		= $return['raw_ts'];
		}
		
		//-----------------------------------------
		// Return
		//-----------------------------------------
		
		return $return;
	}
	
	/**
	 * Unformat content from incoming ical feed
	 *
	 * @param	string	$string	String content to unparse
	 * @param	int		$line	Line number
	 * @return	mixed	Array of data, or false
	 */
	protected function _unparseContent( $string, $line )
	{
		//-----------------------------------------
		// If line starts with a space, it was folded (skip)
		//-----------------------------------------
		
		if( substr( $this->_rawIcsData[ $line ], 0, 1 ) == ' ' )
		{
			return false;
		}
		
		//-----------------------------------------
		// Process
		//-----------------------------------------
		
		$_temp	= preg_split( "/(:|;)/", $string );
		$_type	= array_shift( $_temp );
		$_data	= implode( ':', $_temp );
		
		//-----------------------------------------
		// Unfold folded lines if necessary
		//-----------------------------------------
		
		$_data	= $this->_unfoldLines( $_data, $line );
		
		//-----------------------------------------
		// Return data
		//-----------------------------------------
		
		return array( 'type' => $_type, 'data' => $_data );
	}
	
	/**
	 * Get event recurrence data
	 *
	 * @param	string		Data
	 * @return	array
	 */
	protected function _getRecurrenceData( $data )
	{
		$tmp	= explode( ':', $data );
					
		foreach( $tmp as $t )
		{
			$recurr						= explode( '=', $t );
			$rrule_array[ $recurr[0] ]	= $recurr[1];
			
			//-----------------------------------------
			// Format a timestamp for the 'UNTIL' date
			//-----------------------------------------
			
			if ( $recurr[0] == 'UNTIL' )
			{
				$rrule_array['until_ts']	= strtotime( $recurr[1] );
			}
		}
		
		return $rrule_array;
	}
	
	/**
	 * Retrieve stored error message
	 *
	 * @return	string
	 */
	public function getError()
	{
		return $this->_error;
	}
	
	/**
	 * Import and parse an iCalendar feed
	 *
	 * @param	string		$content	Feed content
	 * @param	int			$member_id	Member to save events under
	 * @param	int			$feed_id	Feed id
	 * @return	mixed		False on failure, otherwise an array with keys 'skipped' and 'imported'
	 */
	public function import( $content, $member_id=0, $feed_id=0 )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$this->lang->loadLanguageFile( array( 'admin_calendar' ), 'calendar' );
		
		//-----------------------------------------
		// Error checking
		//-----------------------------------------
		
		if( !$content )
		{
			$this->_error	= $this->lang->words['icali_nocontent'];
			return;
		}
		
		$_raw	= preg_replace( "#(\n\r|\r|\n){1,}#", "\n", $content );
		$_raw	= explode( "\n", $_raw );
		
		if( !count($_raw) )
		{
			$this->_error	= $this->lang->words['icali_nocontent'];
			return;
		}
		
		if( $_raw[0] != 'BEGIN:VCALENDAR' )
		{
			$this->_error	= $this->lang->words['icali_badcontent'];
			return;
		}

		$this->_rawIcsData	= $_raw;
		
		//-----------------------------------------
		// Loop and start parsing
		//-----------------------------------------
		
		foreach( $this->_rawIcsData as $k => $v )
		{
			$line	= explode( ':', $v );
			
			switch( $line[0] )
			{
				case 'BEGIN':
					$this->_parseBeginBlock( $line[1], $k );
				break;
				
				/* Unsupported at this time */
				case 'CALSCALE':
				case 'METHOD':
				case 'X-WR-TIMEZONE':
				case 'X-WR-RELCALID':
				default:
				break;
			}
		}
		
		//-----------------------------------------
		// Process raw ICS data now
		//-----------------------------------------
		
		if( count($this->_parsedIcsData) )
		{
			$this->_parsedIcsData	= $this->_convertToGmt( $this->_parsedIcsData );
		}
		
		//-----------------------------------------
		// And loop over results to insert
		//-----------------------------------------
		
		$_imported	= 0;
		$_skipped	= 0;
// Leave this here - useful for debugging
//print_r($this->_parsedIcsData);exit;
		if ( count( $this->_parsedIcsData ) )
		{
			//-----------------------------------------
			// Get member data
			//-----------------------------------------
			
			$_member	= IPSMember::load( $member_id );

			if( !$_member )
			{
				$this->_error	= $this->lang->words['icali_nomember'];
				return false;
			}

			//-----------------------------------------
			// Get like class for notifications
			//-----------------------------------------
			
			require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
			$_like	= classes_like::bootstrap( 'calendar', 'calendars' );
		
			//-----------------------------------------
			// Loop over the events
			//-----------------------------------------
			
			foreach( $this->_parsedIcsData['events'] as $event )
			{
				$event['uid']		= $event['uid'] ? $event['uid'] : md5( implode( ',', $event ) );

				$event_unix_from	= $event['start']['gmt_ts'];
				$event_unix_to		= $event['end']['gmt_ts'];
				$event_all_day		= ( $event['start']['type'] == 'DATE' ) ? 1 : 0;
				
				//-----------------------------------------
				// End dates are "exclusive" in iCalendar format, meaning
				// they are actually the day ahead.
				// @link	http://microformats.org/wiki/dtend-issue
				//-----------------------------------------
				
				if( $event_unix_to AND $event['end']['type'] == 'DATE' )
				{
					$event_unix_to	-= 86400;
				}
				
				//-----------------------------------------
				// If end date is same as start date, it's a single day event
				//-----------------------------------------
				
				if( $event_unix_from == $event_unix_to )
				{
					$event_unix_to	= 0;
				}

				//-----------------------------------------
				// If no end date, but we have a duration, calculate end date
				//-----------------------------------------

				if ( ! $event_unix_to AND $event['duration'] )
				{
					preg_match( "#(\d+?)H#is", $event['duration'], $match );
					$hour   = $match[1] ? $match[1] : 0;

					preg_match( "#(\d+?)M#is", $event['duration'], $match );
					$minute = $match[1] ? $match[1] : 0;

					preg_match( "#(\d+?)S#is", $event['duration'], $match );
					$second = $match[1] ? $match[1] : 0;

					$event_unix_to	= $event_unix_from + ( $hour * 3600 ) + ( $minute * 60 ) + $second;
				}

				//-----------------------------------------
				// Recurring...
				//-----------------------------------------

				$recurring	= 0;
				
				if ( isset( $event['recurr']['FREQ'] ) )
				{
					if ( $event['recurr']['FREQ'] == 'MONTHLY' AND $event['recurr']['INTERVAL'] == 12 )
					{
						$event['recurr']['FREQ'] = 'YEARLY';
					}

					switch( $event['recurr']['FREQ'] )
					{
						case 'WEEKLY':
							$recurring	= 1;
						break;
						case 'MONTHLY':
							$recurring	= 2;
						break;
						case 'YEARLY':
							$recurring	= 3;
						break;
					}

					$event_unix_to	= ( $event['recurr']['until_ts'] ) ? $event['recurr']['until_ts'] : time() + 86400 * 365 * 10;
				}
				
				//-----------------------------------------
				// Adjust timestamps if all day event
				//-----------------------------------------
				
				if( $event_all_day )
				{
					$event_unix_from	= gmmktime( 0, 0, 0, gmstrftime( '%m', $event_unix_from ), gmstrftime( '%d', $event_unix_from ), gmstrftime( '%Y', $event_unix_from ) );
					$event_unix_to		= $event_unix_to ? gmmktime( 0, 0, 0, gmstrftime( '%m', $event_unix_to ), gmstrftime( '%d', $event_unix_to ), gmstrftime( '%Y', $event_unix_to ) ) : 0;
				}

				//-----------------------------------------
				// If we are missing crucial data, skip
				//-----------------------------------------
				
				if( !($event['description'] OR $event['summary']) OR !$event_unix_from )
				{
					$_skipped++;
					continue;
				}
								
				//-----------------------------------------
				// Skip previously imported events
				//-----------------------------------------
				
				if( $event['uid'] )
				{
					$_check	= $this->DB->buildAndFetch( array( 'select' => 'import_id', 'from' => 'cal_import_map', 'where' => "import_guid='" . $this->DB->addSlashes( $event['uid'] ) . "'" ) );
					
					if( $_check['import_id'] )
					{
						$_skipped++;
						continue;
					}
				}
				
				//-----------------------------------------
				// Format array for storage
				//-----------------------------------------
					
	 			$_eventData	= array(
	 								'event_calendar_id'	=> $this->calendar['cal_id'],
									'event_member_id'	=> $_member['member_id'],
									'event_content'		=> $event['description'] ? nl2br($event['description']) : $event['summary'],
									'event_title'		=> $event['summary'] ? $event['summary'] : IPSText::truncate( $event['description'], 100 ),
									'event_title_seo'	=> IPSText::makeSeoTitle( $event['summary'] ),
									'event_smilies'		=> 1,
									'event_comments'	=> 0,
									'event_rsvp'		=> count($event['attendee']) ? 1 : 0,
									'event_perms'		=> '*',
									'event_private'		=> 0,
									'event_approved'	=> 1,
									'event_saved'		=> $event['created'] ? $event['created'] : time(),
									'event_lastupdated'	=> $event['last_modified'] ? $event['last_modified'] : ( $event['created'] ? $event['created'] : time() ),
									'event_recurring'	=> $recurring,
									'event_start_date'	=> strftime( "%Y-%m-%d %H:%M:00", $event_unix_from ),
									'event_end_date'	=> $event_unix_to ? strftime( "%Y-%m-%d %H:%M:00", $event_unix_to ) : 0,
									'event_post_key'	=> md5( uniqid( microtime(), true ) ),
									'event_sequence'	=> intval($event['sequence']),
									'event_all_day'		=> $event_all_day,
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
				
				$_imported++;
				
				//-----------------------------------------
				// Insert mapping
				//-----------------------------------------
				
				$this->DB->insert( 'cal_import_map', array(
															'import_feed_id'	=> $feed_id,
															'import_event_id'	=> $event_id,
															'import_guid'		=> $event['uid'],
									)						);

				//-----------------------------------------
				// If we have attendees that are members, insert them
				//-----------------------------------------
				
				if( isset($event['attendee']) AND count($event['attendee']) )
				{
					foreach( $event['attendee'] as $attendee )
					{
						if( $attendee['email'] )
						{
							$_loadedMember	= IPSMember::load( $attendee['email'] );
							
							if( $_loadedMember['member_id'] )
							{
								$this->DB->insert( 'cal_event_rsvp', array(
																			'rsvp_member_id'	=> $_loadedMember['member_id'],
																			'rsvp_event_id'		=> $event_id,
																			'rsvp_date'			=> time(),
													)						);
							}
						}
					}
				}
				
				//-----------------------------------------
				// Send notifications
				//-----------------------------------------
				
				$_url	= $this->registry->output->buildSEOUrl( 'app=calendar&amp;module=calendar&amp;section=view&amp;do=showevent&amp;event_id=' . $event_id, 'public', $_eventData['event_title_seo'], 'cal_event' );
				
				$_like->sendNotifications( $_eventData['event_calendar_id'], array( 'immediate', 'offline' ), 
																			array(
																					'notification_key'		=> 'new_event',
																					'notification_url'		=> $_url,
																					'email_template'		=> 'add_event_follow',
																					'email_subject'			=> sprintf( $this->lang->words[ 'add_event_follow_subject'], $_url, $_eventData['event_title'] ),
																					'build_message_array'	=> array(
																													'NAME'  		=> '-member:members_display_name-',
																													'AUTHOR'		=> $_member['members_display_name'],
																													'TITLE' 		=> $_eventData['event_title'],
																													'URL'			=> $_url,
																													)
																			) 		);
			}
		}
		
		//-----------------------------------------
		// Rebuild cache
		//-----------------------------------------
		
		$this->cache->rebuildCache( 'calendar_events', 'calendar' );

		//-----------------------------------------
		// Return
		//-----------------------------------------
		
		return array( 'skipped' => $_skipped, 'imported' => $_imported );
	}
	
	/**
	 * Convert times to GMT based on timezones
	 *
	 * @param	array 	$data	Parsed data
	 * @return	array
	 */
	protected function _convertToGmt( $data )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$timezones = array();

		//-----------------------------------------
		// Got any timezones?
		//-----------------------------------------
		
		if ( is_array( $data['timezones'] ) AND count( $data['timezones'] ) )
		{
			foreach( $data['timezones'] as $type => $_data )
			{
				$timezones[ $type ] = $_data;
			}
		}

		//-----------------------------------------
		// Fix up events
		//-----------------------------------------
		
		if ( is_array( $data['events'] ) AND count( $data['events'] ) )
		{
			foreach( $data['events'] as $id => $event )
			{
				foreach( array( 'start', 'end' ) as $method )
				{
					//-----------------------------------------
					// Set up constraints
					//-----------------------------------------
			
					if ( isset( $event[ $method ]['tzid'] ) AND isset( $timezones[ $event[ $method ]['tzid'] ] ) AND is_array( $timezones[ $event[ $method ]['tzid'] ] ) )
					{
						$_standard	= ( isset($timezones[ $event[ $method ]['tzid'] ]['standard']['start'] ) ) ? intval( $timezones[ $event[ $method ]['tzid'] ]['standard']['start'] ) : 0;
						$_daylight	= ( isset($timezones[ $event[ $method ]['tzid'] ]['daylight']['start'] ) ) ? intval( $timezones[ $event[ $method ]['tzid'] ]['daylight']['start'] ) : 0;
						$_offset	= 0;
					
						if ( isset( $event[ $method ]['tzid'] ) AND $event[ $method ]['raw_ts'] )
						{
							//if ( $event[ $method ]['raw_ts'] < $_daylight )
							//{
							//	$_offset = $timezones[ $event[ $method ]['tzid'] ]['standard']['tz_offset_to'];
							//}
							//else if ( $event[ $method ]['raw_ts'] > $_standard )
							//{
							//	$_offset = $timezones[ $event[ $method ]['tzid'] ]['standard']['tz_offset_to'];
							//}
							//else
							//{
							//	$_offset = $timezones[ $event[ $method ]['tzid'] ]['daylight']['tz_offset_to'];
							//}
							
							$_offset = $timezones[ $event[ $method ]['tzid'] ]['standard']['tz_offset_to'];
						
							$event[ $method ]['gmt_ts']		= $event[ $method ]['raw_ts'] - ( $_offset / 100 * 3600 );
							$event[ $method ]['offset']		= $_offset;
							$event[ $method ]['gmt_rfc']	= gmdate( 'r', $event[ $method ]['gmt_ts'] );
						}
					}
					else
					{
						if ( isset( $event[ $method ]['raw_ts'] ) )
						{
							$event[ $method ]['gmt_ts']		= $event[ $method ]['raw_ts'];
							$event[ $method ]['offset']		= 0;
							$event[ $method ]['gmt_rfc']	= gmdate( 'r', $event[ $method ]['gmt_ts'] );
						}
					}
				}
				
				$data['events'][ $id ] = $event;
			}
		}

		return $data;
	}
	
	/**
	 * Parse a 'BEGIN:' block in an iCalendar feed
	 *
	 * @param	string	$type	Type of 'BEGIN' object
	 * @param	int		$start	Line number
	 * @return	@e void
	 */
	protected function _parseBeginBlock( $type, $start )
	{
		switch( $type )
		{
			case 'VCALENDAR':
				$this->_begin	= 'VCALENDAR';
				$this->_processVcalendarObject( $start + 1 );
			break;
			
			case 'VTIMEZONE':
				$this->_begin	= 'VTIMEZONE';
				$this->_processTimezoneObject( $start + 1 );
			break;
			
			case 'STANDARD':
				if ( $this->_begin	== 'VTIMEZONE' )
				{
					$this->_processTimezoneTypeObject( $start + 1, 'STANDARD' );
				}
			break;
			
			case 'DAYLIGHT':
				if ( $this->_begin	== 'VTIMEZONE' )
				{
					$this->_processTimezoneTypeObject( $start + 1, 'DAYLIGHT' );
				}
			break;
			
			case 'VEVENT':
				$this->_begin	= 'VEVENT';
				$this->_processEventObject( $start + 1 );
			break;
			
			/* Anything else is unsupported at this time */
			default:
			break;
		}
	}
	
	/**
	 * Parse event object in an ical feed
	 *
	 * @param	int		$start	Line number
	 * @return	@e void
	 */
	protected function _processEventObject( $start )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$_break	= false;
		$_event	= array();

		//-----------------------------------------
		// Loop over lines
		//-----------------------------------------

		for( $i = $start, $j = count( $this->_rawIcsData ); $i < $j; $i++ )
		{
			//-----------------------------------------
			// Unparse and get the data
			//-----------------------------------------

			$tmp	= $this->_unparseContent( $this->_rawIcsData[$i], $i );
			
			if ( !$tmp )
			{
				continue;
			}
				
			$_type	= $tmp['type'];
			$_data	= $tmp['data'];
			
			switch( $_type )
			{
				case 'CLASS':
					$_event['access_class']			= $_data;
				break;
				
				case 'CREATED':
					if( !$_event['created'] )
					{
						$_event['created']			= strtotime( $_data );
					}
				break;
				
				case 'SUMMARY':
					/* @link	http://community.invisionpower.com/tracker/issue-32941-ical-summary/ */
					if( strpos( $_data, 'LANGUAGE=' ) === 0 )
					{
						$_data	= preg_replace( "/^LANGUAGE=(.+?):(.+?)$/i", "\\2", $_data );
					}

					$_event['summary']				= $this->_unencodeSpecialCharacters( $_data );
				break;

				case 'DESCRIPTION':
					$_event['description']			= $this->_unencodeSpecialCharacters( $_data );
				break;
				
				case 'DURATION':
					$_event['duration']				= $_data;
				break;

				case 'DTSTART':
					$_event['start']				= $this->_unparseTimeInfo( $this->_rawIcsData[$i] );
				break;
				
				case 'DTEND':
					$_event['end']					= $this->_unparseTimeInfo( $this->_rawIcsData[$i] );
				break;
				
				case 'DTSTAMP':
					$_event['created']				= strtotime( $_data );
				break;
				
				case 'LAST-MODIFIED':
					$_event['last_modified']		= strtotime( $_data );
				break;

				case 'TRANSP':
					$_event['time_transparent']		= $_data;
				break;								

				case 'GEO':
					$_event['geo']					= $_data;
				break;

				case 'ORGANIZER':
					$line							= explode( ':', $_data );
					$_event['organizer']			= array( 'name' => str_replace( 'CN=', '', $line[0] ), 'email' => $line[2] );
				break;

				case 'ATTENDEE':
					$line							= explode( ':', $_data );
					$_email							= '';
					
					foreach( $line as $_line )
					{
						$_line	= str_replace( 'cn=', '', strtolower($_line) );

						if( IPSText::checkEmailAddress( $_line ) )
						{
							$_email	= $_line;
						}
					}

					$_event['attendee'][]			= array( 'name' => str_replace( 'CN=', '', $line[0] ), 'email' => $_email );
				break;
				
				case 'UID':
					$_event['uid']					= $_data;
				break;
				
				case 'STATUS':
					$_event['status']				= $_data;
				break;
				
				case 'LOCATION':
					$_event['location']				= $_data;
				break;

				case 'SEQUENCE':
					$_event['sequence']				= intval($_data);
				break;
				
				case 'RRULE':
					$_event['recurr']				= $_event['recurr'] ? $_event['recurr'] : array();
					$_event['recurr']				= array_merge( $_event['recurr'], $this->_getRecurrenceData( $_data ) );			
				break;
				
				case 'BEGIN':
					$this->_parseBeginBlock( $_data, $i );
				break;
				
				case 'END':
					$_break	= true;
				break;
			}
			
			if( $_break )
			{
				$this->_parsedIcsData['events'][] = $_event;
				break;
			}
		}
	}
	
	/**
	 * Parse core vcalendar object in an ical feed
	 *
	 * @param	int		$start	Line number
	 * @return	@e void
	 */
	protected function _processVcalendarObject( $start )
	{
		//-----------------------------------------
		// Loop over lines
		//-----------------------------------------

		for( $i = $start, $j = count( $this->_rawIcsData ); $i < $j; $i++ )
		{
			//-----------------------------------------
			// Unparse and get the data
			//-----------------------------------------

			$tmp	= $this->_unparseContent( $this->_rawIcsData[$i], $i );
			
			if ( !$tmp )
			{
				continue;
			}
				
			$_type	= $tmp['type'];
			$_data	= $tmp['data'];
			
			switch( $_type )
			{
				case 'PRODID':
					$this->_parsedIcsData['core']['product']		= $_data;
				break;

				case 'VERSION':
					$this->_parsedIcsData['core']['version']		= $_data;
				break;

				case 'BEGIN':
					$this->_parseBeginBlock( $_data, $i );
				break;

				case 'X-WR-CALNAME':
					$this->_parsedIcsData['core']['calendar_name']	= $_data;
				break;
				
				case 'END':
					return;
				break;
			}
		}
	}
	
	/**
	 * Parse a timezone object in an ical feed
	 *
	 * @param	int		$start	Line number
	 * @param	string	$type	Type of time zone object to parse
	 * @return	@e void
	 */
	protected function _processTimezoneTypeObject( $start, $type )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$type	= strtolower( $type );
		$break	= FALSE;
		
		//-----------------------------------------
		// Loop over line numbers
		//-----------------------------------------
		
		for( $i = $start, $j = count( $this->_rawIcsData ); $i < $j; $i++ )
		{
			//-----------------------------------------
			// Unparse and get the data
			//-----------------------------------------

			$tmp	= $this->_unparseContent( $this->_rawIcsData[$i], $i );
			
			if ( !$tmp )
			{
				continue;
			}
				
			$_type	= $tmp['type'];
			$_data	= $tmp['data'];
			
			switch( $_type )
			{
				case 'DTSTART':
					$this->_parsedIcsData['timezones'][ $this->_tzId ][ $type ]['start']			= strtotime( $_data );
				break;
				
				case 'TZOFFSETTO':
					$this->_parsedIcsData['timezones'][ $this->_tzId ][ $type ]['tz_offset_to']		= $_data;
				break;
				
				case 'TZOFFSETFROM':
					$this->_parsedIcsData['timezones'][ $this->_tzId ][ $type ]['tz_offset_from']	= $_data;
				break;
				
				case 'TZNAME':
					$this->_parsedIcsData['timezones'][ $this->_tzId ][ $type ]['tz_name']			= $_data;
				break;
				
				case 'END':
					return;
				break;
			}
		}
	}
	
	/**
	 * Parse a timezone object in an ical feed
	 *
	 * @param	int		$start	Line number
	 * @return	@e void
	 */
	protected function _processTimezoneObject( $start )
	{
		//-----------------------------------------
		// Loop over lines
		//-----------------------------------------
		
		for( $i = $start, $j = count( $this->_rawIcsData ); $i < $j; $i++ )
		{
			//-----------------------------------------
			// Unparse and get the data
			//-----------------------------------------
			
			$tmp	= $this->_unparseContent( $this->_rawIcsData[$i], $i );
			
			if( !$tmp )
			{
				continue;
			}
							
			$_type	= $tmp['type'];
			$_data	= $tmp['data'];
			
			switch( $_type )
			{
				case 'TZID':
					$this->_tzId	= $_data;
					$this->_parsedIcsData['timezones'][ $_data ]	= array();
				break;

				case 'LAST-MODIFIED':
					$this->_parsedIcsData['timezones'][ $this->_tzId ]['last_modified']	= $_data;
				break;

				case 'BEGIN':
					$this->_tzBegin = $_data;
					$this->_parseBeginBlock( $_data, $i );
				break;

				case 'END':
					if ( $this->_tzBegin == $_data )
					{
						return;
					}
				break;
			}
		}
	}
}