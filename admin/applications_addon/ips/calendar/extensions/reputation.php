<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board vVERSION_NUMBER
 * Reputation configuration for application
 * Last Updated: $Date: 2012-03-24 14:51:58 -0400 (Sat, 24 Mar 2012) $
 * </pre>
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10486 $ 
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class reputation_calendar
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		/* Init editor */
		IPSText::getTextClass('bbcode')->parse_html		 = 0;
		IPSText::getTextClass('bbcode')->parse_nl2br	 = 1;
		IPSText::getTextClass('bbcode')->parse_smilies	 = 1;
		IPSText::getTextClass('bbcode')->parse_bbcode	 = 1;
		IPSText::getTextClass('bbcode')->parsing_section = 'calendar';
		
		/* Init attachments */
		$classname = IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
		$this->class_attach = new $classname( ipsRegistry::instance() );
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_topic' ), 'forums' );
		$this->class_attach->type = 'event';
		$this->class_attach->init();
		$this->class_attach->getUploadFormSettings();
	}
	
	/**
	 * Database Query to get results
	 *
	 * @param	string	'given' if we want to return items as user has liked
	 *					'received' if we want to fetch items a user has posted that others have liked
	 *					NULL to get highest rated
	 * @param	array	Member this applies to
	 *					NULL to get highest rated
	 *
	 * @return	array	Parameters to pass to ipsRegistry::DB()->build
	 *					Must return at least the data from reputation_index
	 *					limit and order will be applied automatically
	 */
	public function fetch( $type=NULL, $member=NULL )
	{
		//-----------------------------------------
		// Get our allowed calendars
		//-----------------------------------------
	
		$allowedCalendarIds = array();
		ipsRegistry::DB()->build( array( 'select' => 'perm_type_id as calendar_id', 'from' => 'permission_index', 'where' => "app='calendar' AND " . ipsRegistry::DB()->buildRegexp( "perm_view", ipsRegistry::member()->perm_id_array ) ) );
		ipsRegistry::DB()->execute();
		while( $r = ipsRegistry::DB()->fetch() )
		{
			$allowedCalendarIds[]	= $r['calendar_id'];
		}
	
		//-----------------------------------------
		// Return query
		//-----------------------------------------
		
		$userGiving = 'r';
		$extraWhere = '';
		$extraJoin = array();
		if ( $type !== NULL )
		{
			$extraWhere = ( ( $type == 'given' ) ? "r.member_id={$member['member_id']}" : "( ( r.type='event_id' AND e.event_member_id={$member['member_id']} ) OR ( r.type='comment_id' AND c.comment_mid ) )" ) . ' AND ';
		}
		else
		{
			$userGiving = 'r2';
			$extraJoin = array( array(
					'from'		=> array( 'reputation_index' => 'r2' ),
					'where'		=> "r2.app=r.app AND r2.type=r.type AND r2.type_id=r.type_id AND r2.member_id=" . ipsRegistry::member()->getProperty('member_id')
				) );
		}
		
		return array(
			'select'	=> "r.*, {$userGiving}.member_id as repUserGiving, e.*, c.*", // we have to do aliases on some of them due to duplicate column names
			'from'		=> array( 'reputation_index' => 'r'),
			'add_join'	=> array_merge( $extraJoin, array(
				array(
					'from'		=> array( 'reputation_cache' => 'rc' ),
					'where'		=> "rc.app=r.app AND rc.type=r.type AND rc.type_id=r.type_id"
					),
				array(
						'from'		=> array( 'cal_event_comments' => 'c' ),
						'where'		=> 'r.type="comment_id" AND r.type_id=c.comment_id',
						'type'		=> 'left'
					),
				array(
						'from'		=> array( 'cal_events' => 'e' ),
						'where'		=> 'e.event_id = IFNULL(c.comment_eid, r.type_id)',
						'type'		=> 'left'
					),
				) ),
			'where'		=>	"r.app='calendar' AND " . // belongs to this app
							$extraWhere . // is for this member
							" e.event_id<>0 AND " . // is valid
							( empty( $allowedCalendarIds ) ? '1=0' : ipsRegistry::DB()->buildWherePermission( $allowedCalendarIds, 'e.event_calendar_id', FALSE ) ), // we have permission to view
			);
	}
	
	/**
	 * Process Results
	 *
	 * @param	array	Row from database using query specified in fetch()
	 * @return	array	Same data with any additional processing necessary
	 */
	public function process( $row )
	{
		if ( !$row['comment_id'] )
		{
			$idField = 'event_id';
			$authorField = 'event_member_id';
			$contentField = 'event_content';
		}
		else
		{
			$idField = 'comment_id';
			$authorField = 'comment_mid';
			$contentField = 'comment_text';
		}
	
		/* Build poster's display data */
		$row = array_merge( $row, IPSMember::buildDisplayData( $row[ $authorField ], array( 'reputation' => 0, 'warn' => 0 ) ) );
		
		/* Parse BBCode */
		$row[ $contentField ] = IPSText::getTextClass('bbcode')->preDisplayParse( $row[ $contentField ] );
		
		/* Parse attachments */
		$messageHTML = array( $row[ $idField ] => $row[ $contentField ] );
		$attachHTML = $this->class_attach->renderAttachments( $messageHTML, array( $row[ $idField ] ) );
		if( is_array( $attachHTML ) AND count( $attachHTML ) )
		{		
			/* Get rid of any lingering attachment tags */
			if ( stristr( $attachHTML[ $row[ $idField ] ]['html'], "[attachment=" ) )
			{
				$attachHTML[ $row[ $idField ] ]['html'] = IPSText::stripAttachTag( $attachHTML[ $row[ $idField ] ]['html'] );
			}
			$row[ $contentField ] = $attachHTML[ $row[ $idField ] ]['html'] . $attachHTML[ $row[ $idField ] ]['attachmentHtml'];
		}
		
		/* Get rep buttons */
		if ( $row['repUserGiving'] == ipsRegistry::member()->getProperty('member_id') )
		{
			$row['has_given_rep'] = $row['rep_rating'];
		}
		$row['repButtons'] = ipsRegistry::getClass('repCache')->getLikeFormatted( array( 'app' => 'calendar', 'type' => $idField, 'id' => $row[ $idField ], 'rep_like_cache' => $row['rep_like_cache'] ) );
		
		/* Return */
		return $row;
	}
	
	/**
	 * Display Results
	 *
	 * @param	array	Results after being processed
	 * @param	string	HTML
	 */
	public function display( $results )
	{
		ipsRegistry::getClass('class_localization')->loadLanguageFile( 'public_calendar', 'calendar' );
		
		return ipsRegistry::getClass('output')->getTemplate('profile')->tabReputation_calendar( $results );
	}
}

$rep_author_config	= array( 
							'comment_id' => array( 'column' => 'comment_mid', 'table'  => 'cal_event_comments' ),
							'event_id' => array( 'column' => 'event_member_id', 'table'  => 'cal_events' ),
							);

$rep_log_joins		= array(
							array(
									'from'		=> array( 'cal_event_comments' => 'c' ),
									'where'		=> 'r.type="comment_id" AND r.type_id=c.comment_id AND r.app="calendar"',
									'type'		=> 'left'
								),
							array(
									'select'	=> 'e.event_title as repContentTitle, e.event_id as repContentID',
									'from'		=> array( 'cal_events' => 'e' ),
									'where'		=> 'e.event_id = IFNULL(c.comment_eid, r.type_id)',
									'type'		=> 'left'
								),
							);

$rep_log_where	= "r.member_id=%d";

$rep_log_link	= 'app=calendar&amp;module=calendar&amp;section=view&amp;do=showevent&amp;event_id=%d#comment_%d';