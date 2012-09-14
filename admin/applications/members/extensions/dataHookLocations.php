<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Define data hook locations (Members)
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

	/* MESSENGER DATA LOCATIONS */
	array( 'messengerSendReplyData', 'Messenger: Reply data'),
	array( 'messengerSendTopicData', 'Messenger: New conversation, topic data' ),
	array( 'messengerSendTopicFirstPostData', 'Messenger: New conversation, first post' ),
	
	/* PROFILE DATA LOCATIONS */
	array( 'statusUpdateNew', 'New Status Update' ),
	array( 'statusCommentNew', 'New Status Comment' ),
	array( 'profileFriendsNew', 'Profile: New friend' ),
	
	/* MEMBER WARNINGS LOCATIONS */
	array( 'memberWarningPre', 'Warn Member (Pre Save)' ),
	array( 'memberWarningPost', 'Warn Member (Post Save)' ),
	
);