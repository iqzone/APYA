<?php
/*
+--------------------------------------------------------------------------
|   IP.Board v3.3.3
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2009 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
*/

$PRE = trim(ipsRegistry::dbFunctions()->getPrefix());
$DB  = ipsRegistry::DB();

/* Status update and messenger changes */

$SQL[] = "ALTER TABLE member_status_updates ADD status_author_id INT(10) UNSIGNED NOT NULL DEFAULT '0',
	ADD status_author_ip VARCHAR(46) NOT NULL DEFAULT '',
	ADD status_approved INT(1) NOT NULL DEFAULT 1,
	ADD INDEX status_author_lookup (status_author_id, status_member_id, status_date),
	ADD INDEX ( status_member_id, status_approved, status_date );";

$SQL[] = "UPDATE member_status_updates SET status_author_id=status_member_id;";

$SQL[] = "INSERT INTO member_status_updates (status_member_id, status_author_id, status_date, status_content, status_replies, status_last_ids, status_is_latest, status_is_locked, status_hash, status_imported, status_creator, status_author_ip, status_approved)
	SELECT comment_for_member_id, comment_by_member_id, comment_date, comment_content, 0, '', 0, 0, MD5(comment_content), 0, '', comment_ip_address, comment_approved FROM {$PRE}profile_comments;";

$SQL[] = "DROP TABLE profile_comments;";

$SQL[] = "ALTER TABLE message_posts CHANGE msg_ip_address msg_ip_address VARCHAR( 46 ) NOT NULL DEFAULT '0';";

$SQL[] = "UPDATE groups SET g_view_board=1 WHERE g_id=" . ipsRegistry::$settings['guest_group'] . ";";