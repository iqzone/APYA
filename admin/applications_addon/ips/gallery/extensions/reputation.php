<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Reputation configuration for application
 * Last Updated: $Date: 2011-10-19 11:07:17 -0400 (Wed, 19 Oct 2011) $
 * </pre>
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Rev: 9635 $ 
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

$rep_author_config = array( 
						'pid' => array( 'column' => 'author_id', 'table'  => 'gallery_comments' ),
						'id'  => array( 'column' => 'member_id', 'table'  => 'gallery_images' )
					);
					
/*
 * The following config items are for the log viewer in the ACP 
 */

$rep_log_joins = array(
						array(
								'from'   => array( 'gallery_comments' => 'p' ),
								'where'  => 'r.type="pid" AND r.type_id=p.pid AND r.app="gallery"',
								'type'   => 'left'
							),
						array(
								'select' => 't.caption as repContentTitle, t.id as repContentID',
								'from'   => array( 'gallery_images' => 't' ),
								'where'  => 'p.img_id=t.id',
								'type'   => 'left'
							),
					);

$rep_log_where = "p.author_id=%s";

$rep_log_link = 'app=gallery&amp;module=images&amp;section=viewimage&amp;img=%d#comment_%d';