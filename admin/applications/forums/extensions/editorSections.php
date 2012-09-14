<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * BBCode Management : Determines if bbcode can be used in this section
 * Last Updated: $LastChangedDate: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/*
 * An array of key => value pairs
 * When going to parse, the key should be passed to the editor
 *  to determine which bbcodes should be parsed in the section
 *
 */
$BBCODE	= array(
				'topics'			=> ipsRegistry::getClass('class_localization')->words['ctype__topics'],
				'announcement'		=> ipsRegistry::getClass('class_localization')->words['ctype__announcement'],
				'rules'				=> ipsRegistry::getClass('class_localization')->words['ctype__rules'],
				);