<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Reputation configuration for application
 * Last Updated: $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Rev: 4 $ 
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

$rep_author_config = array( 'comment_id' => array( 'column' => 'member_id', 'table'  => 'blog_comments' ),
							'entry_id'   => array( 'column' => 'entry_author_id', 'table'  => 'blog_entries' ),
							);
					
/*
 * The following config items are for the log viewer in the ACP 
 */

$rep_log_joins  = array( array( 'from'   => array( 'blog_comments' => 'p' ),
								'where'  => "r.type='comment_id' AND r.type_id=p.comment_id AND r.app='blog'",
								'type'   => 'left' ),
						 array( 'select' => 't.entry_name as repContentTitle, t.entry_id as repContentID',
								'from'   => array( 'blog_entries' => 't' ),
								'where'  => 't.entry_id = IFNULL(p.entry_id, r.type_id)',
								'type'   => 'left' ),
						);

$rep_log_where = "r.member_id=%d";

$rep_log_link = "app=blog&amp;showentry=%d";
