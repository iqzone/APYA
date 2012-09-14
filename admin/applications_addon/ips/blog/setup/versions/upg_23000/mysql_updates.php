<?php

// Add comment_approved for new comments functionality:
$SQL[] = "ALTER TABLE blog_comments ADD comment_approved INT(1) DEFAULT '0'";
$SQL[] = "UPDATE blog_comments SET comment_approved = 1 WHERE comment_queued = 0";
$SQL[] = "UPDATE blog_comments SET comment_approved = 0 WHERE comment_queued = 1";

// Get DB prefix:
$PRE = ipsRegistry::dbFunctions()->getPrefix();
$DB  = ipsRegistry::DB();

// Convert tracker to like:
if ( $DB->checkForTable('blog_tracker') )
{
	$SQL[] = "INSERT IGNORE INTO `{$PRE}core_like` (like_id, like_lookup_id, like_app, like_area, like_rel_id, like_member_id, like_is_anon, like_added, like_notify_do, like_notify_freq) SELECT MD5(CONCAT('blog;blog;', blog_id, ';', member_id)), MD5(CONCAT('blog;blog;', blog_id)), 'blog', 'blog', blog_id, member_id, 0, UNIX_TIMESTAMP(), 1, 'immediate' FROM `{$PRE}blog_tracker`";

	$SQL[] = "INSERT IGNORE INTO `{$PRE}core_like` (like_id, like_lookup_id, like_app, like_area, like_rel_id, like_member_id, like_is_anon, like_added, like_notify_do, like_notify_freq) SELECT MD5(CONCAT('blog;entries;', entry_id, ';', member_id)), MD5(CONCAT('blog;entries;', entry_id)), 'blog', 'entries', entry_id, member_id, 0, UNIX_TIMESTAMP(), 1,'immediate' FROM `{$PRE}blog_tracker` WHERE entry_id <> 0 AND entry_id IS NOT NULL";

	$SQL[] = "DROP TABLE blog_tracker";
	$SQL[] = "DROP TABLE blog_tracker_queue";
}

// Fix for bug #25360 - http://community.invisionpower.com/tracker/issue-25360-report-plugin-not-updated-properly/
$SQL[] = 'UPDATE rc_classes SET app = \'blog\' WHERE my_class = \'blog\'';