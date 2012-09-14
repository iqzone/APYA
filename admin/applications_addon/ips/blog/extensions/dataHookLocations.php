<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Define data hook locations (Blog)
 * Last Updated: $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @version		$Rev: 4 $
 */

$dataHookLocations = array(

	/* POSTING LIBRARY DATA LOCATIONS */
	array( 'blogAddEntry', 'Add Blog Entry: Entry' ),
	array( 'blogAddEntryPoll','Add Blog Entry: Poll' ),
	array( 'blogAddBlog', 'Add New Blog' ),
	array( 'blogEditEntryData', 'Edit Blog Entry: Entry Data' ),
	array( 'blogEditEntryAddPoll', 'Edit Blog Entry: Added Poll' ),
	array( 'blogEditEntryUpdatePoll', 'Edit Blog Entry: Updated Poll' ),
	array( 'blogPreAddComment', 'Before Blog Comment is Added'),
	array( 'blogPreEditComment', 'Before Blog Comment is Edited'),
	array( 'blogPostAddComment', 'After Blog Comment is Added'),
	array( 'blogPostEditComment', 'After Blog Comment is Edited'),
	array( 'blogPostDeleteComments', 'After Blog Comments are Deleted'),
	array( 'blogPostCommentVisibilityToggle', 'After Blog Comments Visibility is Toggled'),
);