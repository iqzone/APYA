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

/* Topic table changes */

$SQL[] = "ALTER TABLE topics ADD tdelete_time INT NOT NULL DEFAULT 0,
	ADD moved_on INT NOT NULL DEFAULT '0',
	ADD INDEX approved (approved,tdelete_time),
	ADD INDEX moved_redirects ( moved_on, moved_to, pinned ),
	DROP INDEX starter_id,
	ADD INDEX starter_id ( starter_id, forum_id, approved, start_date );";

/* Populate 'like' from tracker */
$SQL[] = "INSERT IGNORE INTO {$PRE}core_like
				(like_id, like_lookup_id, like_lookup_area, like_app, like_area, like_rel_id, like_member_id, like_is_anon, like_added, like_notify_do, like_notify_freq, like_notify_sent, like_visible)
				SELECT MD5(CONCAT('forums;forums;', forum_id, ';', member_id)), MD5(CONCAT('forums;forums;', forum_id)), MD5(CONCAT('forums;forums;', member_id)), 'forums', 'forums', forum_id, member_id, 0, start_date, CASE WHEN forum_track_type='none' THEN 0 ELSE 1 END, forum_track_type, last_sent, 1
				FROM {$PRE}forum_tracker;";

$SQL[] = "INSERT IGNORE INTO {$PRE}core_like
				(like_id, like_lookup_id, like_lookup_area, like_app, like_area, like_rel_id, like_member_id, like_is_anon, like_added, like_notify_do, like_notify_freq, like_notify_sent, like_visible)
				SELECT MD5(CONCAT('forums;topics;', topic_id, ';', member_id)), MD5(CONCAT('forums;topics;', topic_id)), MD5(CONCAT('forums;topics;', member_id)), 'forums', 'topics', topic_id, member_id, 0, start_date, CASE WHEN topic_track_type='none' THEN 0 ELSE 1 END, topic_track_type, last_sent, 1
				FROM {$PRE}tracker;";

$SQL[] = "DROP TABLE forum_tracker;";
$SQL[] = "DROP TABLE tracker;";

$SQL[] = "TRUNCATE TABLE content_cache_posts;";