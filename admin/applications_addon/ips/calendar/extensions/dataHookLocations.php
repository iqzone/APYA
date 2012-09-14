<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board vVERSION_NUMBER
 * Define data hook locations (Calendar)
 * Last Updated: $Date: 2011-01-26 19:04:21 -0500 (Wed, 26 Jan 2011) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @version		$Rev: 7653 $
 */

$dataHookLocations = array(

	/* POSTING DATA LOCATIONS */
	array( 'calendarAddEvent', 'Add Calendar Event' ),
	array( 'calendarEditEvent', 'Edit Calendar Event' ),
	array( 'calendarAddComment', 'Add Event Comment' ),
	array( 'calendarEditComment', 'Edit Event Comment' ),
	array( 'calendarCommentAddPostSave', 'Add Event Comment (Post Save)' ),
	array( 'calendarCommentEditPostSave', 'Edit Event Comment (Post Save)' ),
	array( 'calendarCommentPostDelete', 'Comment Deletion' ),
	array( 'calendarCommentToggleVisibility', 'Comment Visibility Toggled' ),
);