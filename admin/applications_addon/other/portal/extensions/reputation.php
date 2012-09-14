<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.1
 * Reputation configuration for application
 * Last Updated: $Date: 2010-12-17 07:53:02 -0500 (Fri, 17 Dec 2010) $
 * </pre>
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Rev: 7443 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

$rep_author_config = array(
						'id' => array( 'column' => 'user_id', 'table'  => 'portal_logbook' ),
						'log_id' => array( 'column' => 'log_member_id', 'table'  => 'log_reply' )
					);

/*
 * The following config items are for the log viewer in the ACP
 */

/*$rep_log_joins = array(
						array(
								'from'   => array( 'portal_logbook' => 'pl' ),
								'where'  => 'r.type="sid" AND r.type_id=pl.id AND r.app="portal"',
								'type'   => 'left'
							),
						array(
								'from'   => array( 'log_reply' => 'lr' ),
								'where'  => 'r.type="sid" AND r.type_id=lr.log_id AND r.app="portal"',
								'type'   => 'left'
						),
					);*/

//$rep_log_where = "pl.user_id=%s";

//$rep_log_link = 'app=portal&amp;showfile=%d#comment_%d';