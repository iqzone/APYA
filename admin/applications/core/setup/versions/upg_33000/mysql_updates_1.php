<?php
/*
+--------------------------------------------------------------------------
|   IP.Board v3.3.3
|   ========================================
|   by Matthew Mecham
|   (c) 2011 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
*/

$PRE = trim(ipsRegistry::dbFunctions()->getPrefix());
$DB  = ipsRegistry::DB();

$SQL[] = "CREATE TABLE core_archive_log (
	archlog_id	INT(10) AUTO_INCREMENT NOT NULL,
	archlog_app		VARCHAR(255) NOT NULL DEFAULT 0,
	archlog_date	INT(10) NOT NULL DEFAULT 0,
	archlog_ids		MEDIUMTEXT,
	archlog_count	INT(10) NOT NULL DEFAULT 0,
	archlog_is_restore INT(1) NOT NULL DEFAULT 0,
	archlog_is_error	INT(1) NOT NULL DEFAULT 0,
	archlog_msg			TEXT,
	PRIMARY KEY  (archlog_id)
);";


$SQL[] = "CREATE TABLE core_archive_restore (
	restore_min_tid	INT(10) NOT NULL DEFAULT 0,
	restore_max_tid	INT(10) NOT NULL DEFAULT 0,
	restore_manual_tids MEDIUMTEXT
);";

$SQL[] = "CREATE TABLE core_archive_rules (
  archive_key	CHAR(32) NOT NULL DEFAULT '',
  archive_app	VARCHAR(32) NOT NULL DEFAULT 'core',
  archive_field	VARCHAR(255) NOT NULL DEFAULT '',
  archive_value	VARCHAR(255) NOT NULL DEFAULT '',
  archive_text	TEXT NOT NULL,
  archive_unit	VARCHAR(255) NOT NULL DEFAULT '',
  archive_skip	INT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (archive_key)
);";

$SQL[] = "CREATE TABLE forums_archive_posts (
	archive_id				INT(10) DEFAULT 0,
	archive_author_id		INT(10) NOT NULL DEFAULT 0,
	archive_author_name		VARCHAR(255) NOT NULL DEFAULT 0,
	archive_ip_address		VARCHAR(46) NOT NULL DEFAULT '',
	archive_content_date	INT(10) NOT NULL DEFAULT 0,
	archive_content			MEDIUMTEXT,
	archive_queued			INT(1) NOT NULL DEFAULT 1,
	archive_topic_id		INT(10) NOT NULL DEFAULT 0,
	archive_is_first		INT(1) NOT NULL DEFAULT 0,
	archive_bwoptions		INT(10) UNSIGNED NOT NULL DEFAULT 0,
	archive_attach_key		CHAR(32) NOT NULL DEFAULT '',
	archive_html_mode		INT(1) NOT NULL DEFAULT 0,
	archive_show_signature	INT(1) NOT NULL DEFAULT 0,
	archive_show_emoticons	INT(1) NOT NULL DEFAULT 0,
	archive_show_edited_by	INT(1) NOT NULL DEFAULT 0,
	archive_edit_time		INT(10) NOT NULL DEFAULT 0,
	archive_edit_name		VARCHAR(255) NOT NULL DEFAULT '',
	archive_edit_reason		VARCHAR(255) NOT NULL DEFAULT '',
	archive_added			INT(10) NOT NULL DEFAULT 0,
	archive_restored		INT(1) NOT NULL DEFAULT 0,
	PRIMARY KEY (archive_id),
	KEY archive_topic_id (archive_topic_id,archive_queued,archive_content_date),
	KEY archive_author_id (archive_author_id),
	KEY archive_restored (archive_restored),
	KEY archive_content_date (archive_content_date, archive_topic_id)
);";

if ( $DB->checkFulltextSupport() )
{
	$SQL[] = "ALTER TABLE forums_archive_posts ADD FULLTEXT KEY archive_content (archive_content);";
}


$SQL[] = "CREATE TABLE forums_recent_posts (
	post_id		INT(10) NOT NULL DEFAULT 0,
	post_topic_id	INT(10) NOT NULL DEFAULT 0,
	post_forum_id	INT(10) NOT NULL DEFAULT 0,
	post_author_id	INT(10) NOT NULL DEFAULT 0,
	post_date		INT(10) NOT NULL DEFAULT 0,
	PRIMARY KEY (post_id),
	KEY group_lookup (post_author_id, post_forum_id, post_date, post_id )
);";


$SQL[] = "ALTER TABLE attachments ADD attach_is_archived INT(1) NOT NULL DEFAULT 0;";
$SQL[] = "ALTER TABLE forums ADD archived_topics INT(10) NOT NULL DEFAULT 0;";
$SQL[] = "ALTER TABLE forums ADD archived_posts INT(10) NOT NULL DEFAULT 0;";

$SQL[] = "ALTER TABLE rc_reports ADD KEY rid (rid);";

$SQL[] = "DELETE FROM core_sys_conf_settings WHERE conf_key IN ('seo_bad_url', 'spider_sense');";

$SQL[] = "ALTER TABLE announcements ADD COLUMN announce_seo_title VARCHAR(255) NOT NULL DEFAULT '';";

$SQL[] = "ALTER TABLE core_uagents ADD COLUMN uagent_default_regex TEXT;";
$SQL[] = "UPDATE core_uagents SET uagent_default_regex=uagent_regex;";

$SQL[] = "ALTER TABLE rss_import DROP rss_import_inc_pcount;";

$SQL[] = "ALTER TABLE mobile_notifications ADD INDEX (notify_sent,notify_date);";


