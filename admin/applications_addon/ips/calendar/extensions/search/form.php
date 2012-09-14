<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board vVERSION_NUMBER
 * Returns HTML for the form (optional class, not required)
 * Last Updated: $Date: 2011-05-05 07:03:47 -0400 (Thu, 05 May 2011) $
 * </pre>
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Calendar
 * @link		http://www.invisionpower.com
 * @version		$Rev: 8644 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class search_form_calendar
{
	/**
	 * Constructor
	 *
	 * @return	@e void
	 */
	public function __construct()
	{
	}
	
	/**
	 * Return sort drop down
	 *
	 * @return	array
	 */
	public function fetchSortDropDown()
	{
		$array = array( 'date'  => ipsRegistry::instance()->getClass('class_localization')->words['s_search_type_0'],
					    'title' => ipsRegistry::instance()->getClass('class_localization')->words['forum_sort_title'] );
		
		return $array;
	}
}
