<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board vVERSION_NUMBER
 * Sets up SEO templates
 * Last Updated: $Date: 2011-04-12 14:30:41 -0400 (Tue, 12 Apr 2011) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Rev: 8309 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

$_SEOTEMPLATES = array(
						'cal_event'		=> array(
											'app'			=> 'calendar',
											'allowRedirect'	=> 1,
											'out'			=> array( '#app=calendar(?:&|&amp;)module=calendar(?:&|&amp;)section=view(?:&|&amp;)do=showevent(?:&|&amp;)event_id=(\d+?)(&|$)#i', 'calendar/event/$1-#{__title__}/$2' ),
											'in'			=> array( 
																		'regex'		=> "#/calendar/event/(\d+?)-([^/]+?)(/|$)#i",
																		'matches'	=> array(	array( 'app'		, 'calendar' ),
																								array( 'module'		, 'calendar' ),
																								array( 'section'	, 'view' ),
																								array( 'do'			, 'showevent' ),
																								array( 'event_id'	, '$1' ) )
																	)	),

						'cal_post'	=> array(
											'app'			=> 'calendar',
											'allowRedirect'	=> 1,
											'out'			=> array( '#app=calendar(?:&|&amp;)module=calendar(?:&|&amp;)section=post(?:&|&amp;)cal_id=(.+?)(?:&|&amp;)do=newevent#i', 'calendar/$1-#{__title__}/add' ),
											'in'			=> array( 
																		'regex'		=> "#/calendar/(\d+?)-([^/]+?)/add(/|$)#i",
																		'matches'	=> array(	array( 'app'		, 'calendar' ),
																								array( 'module'		, 'calendar' ),
																								array( 'section'	, 'post' ),
																								array( 'do'			, 'newevent' ),
																								array( 'cal_id'		, '$1' ) )
																	)	),
																	
						'cal_day'		=> array(
											'app'			=> 'calendar',
											'allowRedirect'	=> 1,
											'out'			=> array( '#app=calendar(?:&|&amp;)module=calendar(?:&|&amp;)section=view(?:&|&amp;)cal_id=(.+?)(?:&|&amp;)do=showday(?:&|&amp;)y=(.+?)(?:&|&amp;)m=(.+?)(?:&|&amp;)d=(.+?)(&|$)#i', 'calendar/$1-#{__title__}/day-$2-$3-$4$5' ),
											'in'			=> array( 
																		'regex'		=> "#/calendar/(\d+?)-([^/]+?)/day-(\d+?)-(\d+?)-(\d+?)(/|$)#i",
																		'matches'	=> array(	array( 'app'		, 'calendar' ),
																								array( 'module'		, 'calendar' ),
																								array( 'section'	, 'view' ),
																								array( 'do'			, 'showday' ),
																								array( 'cal_id'		, '$1' ),
																								array( 'y'			, '$3' ),
																								array( 'm'			, '$4' ),
																								array( 'd'			, '$5' ) )
																	)	),

						'cal_week'		=> array(
											'app'			=> 'calendar',
											'allowRedirect'	=> 1,
											'out'			=> array( '#app=calendar(?:&|&amp;)module=calendar(?:&|&amp;)section=view(?:&|&amp;)cal_id=(\d+?)(?:&|&amp;)do=showweek(?:&|&amp;)week=(\d+?)(?:&|$)#i', 'calendar/$1-#{__title__}/week-$2' ),
											'in'			=> array( 
																		'regex'		=> "#/calendar/(\d+?)-([^/]+?)/week-(\d+?)(/|$)#i",
																		'matches'	=> array(	array( 'app'		, 'calendar' ),
																								array( 'module'		, 'calendar' ),
																								array( 'section'	, 'view' ),
																								array( 'do'			, 'showweek' ),
																								array( 'cal_id'		, '$1' ),
																								array( 'week'		, '$3' ) )
																	)	),

						'cal_month'		=> array(
											'app'			=> 'calendar',
											'allowRedirect'	=> 1,
											'out'			=> array( '#app=calendar(?:&|&amp;)module=calendar(?:&|&amp;)section=view(?:&|&amp;)cal_id=(.+?)(?:&|&amp;)m=(.+?)(?:&|&amp;)y=(.+?)(?:&|$)#i', 'calendar/$1-#{__title__}/$2-$3' ),
											'in'			=> array( 
																		'regex'		=> "#/calendar/(\d+?)-([^/]+?)/(\d+?)-(\d+?)(/|$)#i",
																		'matches'	=> array(	array( 'app'		, 'calendar' ),
																								array( 'module'		, 'calendar' ),
																								array( 'section'	, 'view' ),
																								array( 'cal_id'		, '$1' ),
																								array( 'm'			, '$3' ),
																								array( 'y'			, '$4' ) )
																	)	),
												
						'cal_calendar'	=> array(
											'app'			=> 'calendar',
											'allowRedirect'	=> 1,
											'out'			=> array( '#app=calendar(?:&|&amp;)module=calendar(?:&|&amp;)section=view(?:&|&amp;)cal_id=(.+?)#i', 'calendar/$1-#{__title__}' ),
											'in'			=> array( 
																		'regex'		=> "#/calendar/(\d+?)-([^/]+?)(/|$)#i",
																		'matches'	=> array(	array( 'app'		, 'calendar' ),
																								array( 'module'		, 'calendar' ),
																								array( 'section'	, 'view' ),
																								array( 'cal_id'		, '$1' ) )
																	)	),

						'app=calendar'	=> array(
											'app'			=> 'calendar',
											'allowRedirect'	=> 1,
											'out'			=> array( '#app=calendar$#i', 'calendar/' ),
											'in'			=> array( 
																		'regex'		=> "#/calendar(/|$|\?)#i",
																		'matches'	=> array( array( 'app', 'calendar' ) )
																	)	),
					);
