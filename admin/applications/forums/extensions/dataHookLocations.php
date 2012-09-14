<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Define data hook locations (Forums)
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 */

$dataHookLocations = array(

	/* POSTING LIBRARY DATA LOCATIONS */
	array( 'postAddReply', 'Add Reply' ),
	array( 'postAddReplyPoll','Add Reply: Poll' ),
	array( 'postAutoMerge', 'Add Reply: Auto merge with previous post' ),
	array( 'postAddReplyTopicUpdate', 'Add Reply: Topic Data' ),
	array( 'postAddTopic', 'New Topic: Topic Data' ),
	array( 'postFirstPost', 'New Topic: First Post' ),
	array( 'postAddTopicPoll', 'New Topic: Poll' ),
	array( 'editPostAddPoll', 'Edit Post: Added Poll' ),
	array( 'editPostUpdatePoll', 'Edit Post: Updated Poll' ),
	array( 'editPostData', 'Edit Post: Post Data' ),
	array( 'editPostTopicData', 'Edit Post: Update Topic'),
	array( 'updateForumLastPostData', 'Forum last post update data' ),
	array( 'incrementUsersPostCount', 'Increment users post count' ),
	
	/* OUTPUT ARRAYS */
	array( 'topicViewQuery', 'Topic View Query: Members Table and Joins' ),
	
);