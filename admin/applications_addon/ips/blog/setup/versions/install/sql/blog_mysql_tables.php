<?php
/**
* Installation Schematic File
* Generated on Thu, 19 Feb 2009 08:15:47 +0000 GMT
*/
$TABLE[] = "CREATE TABLE blog_akismet_logs (
  log_id int(10) NOT NULL auto_increment,
  log_date int(10) NOT NULL default '0',
  log_msg varchar(255) default NULL,
  log_errors text,
  log_data text,
  log_type varchar(32) default NULL,
  log_etbid int(10) NOT NULL default '0',
  log_isspam tinyint(1) NOT NULL default '0',
  log_action varchar(255) default NULL,
  log_submitted tinyint(1) NOT NULL default '0',
  log_connect_error tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (log_id),
  KEY log_etbid (log_etbid)
);";

$TABLE[] = "CREATE TABLE blog_blogs (
  blog_id int(10) NOT NULL auto_increment,
  member_id mediumint(8) NOT NULL,
  blog_name varchar(250) default NULL,
  blog_desc varchar(250) default NULL,
  blog_type varchar(10) default NULL,
  blog_exturl varchar(250) default NULL,
  blog_num_exthits int(10) default '0',
  blog_num_views int(10) default '0',
  blog_private tinyint(1) NOT NULL default '0',
  blog_pinned tinyint(1) NOT NULL default '0',
  blog_disabled tinyint(1) NOT NULL default '0',
  blog_allowguests tinyint(1) NOT NULL default '1',
  blog_rating_total int(10) default '0',
  blog_rating_count int(10) default '0',
  blog_last_delete int(10) default '0',
  blog_skin_id smallint(5) default '0',
  blog_friendly_url varchar(250) default '',
  blog_settings text,
  blog_theme_id int(10) NOT NULL default '0',
  blog_theme_custom text,
  blog_theme_final text,
  blog_theme_approved tinyint(1) NOT NULL default '0',
  blog_last_visitors text,
  blog_view_level VARCHAR( 12 ) NOT NULL,
  blog_seo_name varchar(255) default null,
  blog_categories TEXT,
  blog_editors INT(3),
  blog_groupblog_ids VARCHAR(255) NOT NULL default '',
  blog_groupblog_name VARCHAR(255) NOT NULL default '',
  blog_groupblog INT(1) NOT NULL DEFAULT '0',
  blog_last_edate INT(10) NOT NULL default '0',
  blog_lentry_banish int(1) DEFAULT '0',
  blog_last_udate INT(10) NOT NULL default '0',
  blog_owner_only INT(1) NOT NULL default '0',
  blog_authorized_users VARCHAR(255) NULL,
  PRIMARY KEY  (blog_id),
  KEY blog_groupblog (blog_groupblog),
  KEY blog_grabber (blog_disabled, blog_type, blog_view_level),
  KEY blog_view_level (blog_view_level),
  KEY blog_pinned (blog_pinned),
  KEY blog_last_edate(blog_last_edate),
  KEY blog_member_id (member_id),
  KEY blog_lentry_banish (blog_lentry_banish),
  KEY auth_user (blog_owner_only, blog_authorized_users),
  KEY as_list_view (blog_pinned, blog_disabled, blog_allowguests, blog_last_edate)
);";

$TABLE[] = "CREATE TABLE blog_categories (
  category_id int(10) unsigned NOT NULL AUTO_INCREMENT,
  category_blog_id int(10) unsigned NOT NULL DEFAULT '0',
  category_parent int(10) unsigned NOT NULL DEFAULT '0',
  category_title varchar(255) NOT NULL DEFAULT '',
  category_title_seo varchar(255) NOT NULL DEFAULT '',
  category_position int(5) NOT NULL DEFAULT '0',
  PRIMARY KEY (category_id),
  KEY category_blog_id (category_blog_id),
  KEY member_cat_meow (category_id,category_blog_id)
);";

$TABLE[] = "CREATE TABLE blog_category_mapping (
  map_category_id int(10) unsigned NOT NULL,
  map_entry_id int(10) unsigned NOT NULL,
  map_blog_id int(10) unsigned NOT NULL,
  map_is_draft int(1) NOT NULL DEFAULT '0',
  map_is_private int(1) NOT NULL DEFAULT '0',
  KEY cap_map_lookup (map_category_id,map_blog_id),
  KEY map_blog_id ( map_blog_id, map_entry_id )
);";

$TABLE[] = "CREATE TABLE blog_cblock_cache (
  blog_id int(10) NOT NULL,
  cbcache_key varchar(32) NOT NULL,
  cbcache_lastupdate int(10) NOT NULL default '0',
  cbcache_refresh tinyint(1) NOT NULL default '0',
  cbcache_content text,
  PRIMARY KEY  (blog_id,cbcache_key)
);";
$TABLE[] = "CREATE TABLE blog_cblocks (
  cblock_id int(10) NOT NULL auto_increment,
  blog_id int(10) NOT NULL,
  member_id int(10) NOT NULL,
  cblock_order smallint(5) default '0',
  cblock_show tinyint(1) default '0',
  cblock_type varchar(10) default NULL,
  cblock_ref_id int(11) NOT NULL,
  cblock_position varchar(10) NOT NULL default 'right',
  cblock_config text,
  PRIMARY KEY  (cblock_id),
  KEY cblock_blog_id (blog_id),
  KEY cblock_member_id (member_id),
  KEY cblock_ref_id (cblock_ref_id)
);";
$TABLE[] = "CREATE TABLE blog_comments (
  comment_id int(10) NOT NULL auto_increment,
  entry_id int(10) NOT NULL,
  member_id int(10) default NULL,
  member_name varchar(255) default NULL,
  ip_address varchar(46) default NULL,
  comment_date int(10) default NULL,
  comment_edit_time int(10) default NULL,
  comment_text text,
  comment_approved int(1) default '0',
  PRIMARY KEY  (comment_id),
  KEY comment_member_id ( member_id, comment_date ),
  KEY entry_comment( comment_id, entry_id ),
  KEY entry_id ( entry_id, comment_approved )
);";
$TABLE[] = "CREATE TABLE blog_custom_cblocks (
  cbcus_id int(10) NOT NULL auto_increment,
  cbcus_name varchar(255) default NULL,
  cbcus text,
  cbcus_post_key varchar(32) default NULL,
  cbcus_has_attach smallint(3) default '0',
  cbcus_html_state tinyint(1) default '0',
  PRIMARY KEY  (cbcus_id)
);";
$TABLE[] = "CREATE TABLE blog_default_cblocks (
  cbdef_id int(10) NOT NULL auto_increment,
  cbdef_name varchar(255) default NULL,
  cbdef_function varchar(255) default NULL,
  cbdef_default tinyint(1) default '0',
  cbdef_order smallint(5) default '0',
  cbdef_locked tinyint(1) default '0',
  cbdef_enabled tinyint(1) default '0',
  PRIMARY KEY  (cbdef_id)
);";

$TABLE[] = "CREATE TABLE blog_editors_map (
  editor_member_id int(10) unsigned NOT NULL DEFAULT '0',
  editor_blog_id int(10) unsigned NOT NULL DEFAULT '0',
  editor_added int(10) unsigned NOT NULL DEFAULT '0',
  UNIQUE KEY editor_map (editor_member_id,editor_blog_id)
);";

$TABLE[] = "CREATE TABLE blog_entries (
  entry_id int(10) NOT NULL auto_increment,
  blog_id int(10) NOT NULL,
  entry_author_id int(10) NOT NULL default '0',
  entry_author_name varchar(255) NOT NULL default '',
  entry_date int(10) default NULL,
  entry_name varchar(250) default NULL,
  entry text,
  entry_status varchar(10) default NULL,
  entry_locked tinyint(1) default '0',
  entry_num_comments int(10) default '0',
  entry_last_comment int(10) default NULL,
  entry_last_comment_date int(10) default NULL,
  entry_last_comment_name varchar(255) default NULL,
  entry_last_comment_mid int(10) default NULL,
  entry_queued_comments smallint(5) NOT NULL default '0',
  entry_has_attach smallint(5) default '0',
  entry_post_key varchar(32) default NULL,
  entry_edit_time int(10) default NULL,
  entry_edit_name varchar(255) default NULL,
  entry_html_state tinyint(1) default '0',
  entry_use_emo tinyint(1) default '0',
  entry_trackbacks smallint(5) NOT NULL default '0',
  entry_sent_trackbacks text,
  entry_last_update int(10) default '0',
  entry_gallery_album int(10) default '0',
  entry_poll_state tinyint(1) NOT NULL default '0',
  entry_last_vote int(10) NOT NULL default '0',
  entry_featured tinyint(1) NOT NULL default '0',
  entry_hastags tinyint(1) NOT NULL default '0',
  entry_category TEXT null,
  entry_name_seo varchar(255) NOT NULL default '',
  entry_tag_cache TEXT null,
  entry_short TEXT null,
  entry_rating_total int(10) default '0',
  entry_rating_count int(10) default '0',
  entry_rss_import	INT(1) NOT NULL default '0',
  entry_future_date INT(1) NOT NULL default '0',
  entry_banish INT(1) NOT NULL default '0',
  entry_image VARCHAR(255) NOT NULL DEFAULT '',
  entry_views INT(10) NOT NULL DEFAULT 0,
  PRIMARY KEY  (entry_id),
  KEY entry_blog_id (blog_id,entry_status,entry_date),
  KEY entry_last_update (blog_id,entry_status,entry_last_update),
  KEY entry_category_id (blog_id),
  KEY entry_featured (entry_featured),
  KEY entry_date (entry_date),
  KEY entry_rss_import (entry_rss_import),
  KEY entry_status (entry_status, entry_last_update),
  KEY entry_banish (entry_banish),
  KEY entry_future_date (entry_future_date, entry_date)
);";

$TABLE[] = "CREATE TABLE blog_lastinfo (
  blog_id int(10) NOT NULL,
  blog_num_entries int(10) default '0',
  blog_num_drafts int(10) default '0',
  blog_num_comments int(10) default '0',
  blog_last_entry int(10) default NULL,
  blog_last_entryname varchar(250) default NULL,
  blog_last_date int(10) default NULL,
  blog_last_comment int(10) default NULL,
  blog_last_comment_date int(10) default NULL,
  blog_last_comment_entry int(10) default NULL,
  blog_last_comment_entryname varchar(250) default NULL,
  blog_last_comment_name varchar(255) default NULL,
  blog_last_comment_mid int(10) default NULL,
  blog_last_update int(10) default '0',
  blog_last_entry_excerpt text,
  blog_tag_cloud mediumtext,
  blog_cblocks text,
  blog_last_comment_20 text,
  blog_cblocks_available text,
  PRIMARY KEY  (blog_id)
);";

$TABLE[] = "CREATE TABLE blog_mediatag (
  mediatag_id smallint(10) unsigned NOT NULL auto_increment,
  mediatag_name varchar(255) NOT NULL,
  mediatag_match text,
  mediatag_replace text,
  PRIMARY KEY  (mediatag_id)
);";
$TABLE[] = "CREATE TABLE blog_moderators (
  moderate_id int(10) NOT NULL auto_increment,
  moderate_type varchar(10) NOT NULL,
  moderate_mg_id int(10) NOT NULL,
  moderate_can_edit_comments tinyint(1) NOT NULL default '0',
  moderate_can_edit_entries tinyint(1) NOT NULL default '0',
  moderate_can_del_comments tinyint(1) NOT NULL default '0',
  moderate_can_del_entries tinyint(1) NOT NULL default '0',
  moderate_can_lock tinyint(1) NOT NULL default '0',
  moderate_can_publish tinyint(1) NOT NULL default '0',
  moderate_can_approve tinyint(1) NOT NULL default '0',
  moderate_can_editcblocks tinyint(1) NOT NULL default '0',
  moderate_can_del_trackback tinyint(1) NOT NULL default '0',
  moderate_can_view_draft tinyint(1) NOT NULL default '0',
  moderate_can_view_private tinyint(1) NOT NULL default '0',
  moderate_can_warn tinyint(1) NOT NULL default '0',
  moderate_can_pin tinyint(1) NOT NULL default '0',
  moderate_can_disable tinyint(1) NOT NULL default '0',
  moderator_can_feature tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (moderate_id)
);";
$TABLE[] = "CREATE TABLE blog_pingservices (
  blog_service_id int(10) NOT NULL auto_increment,
  blog_service_key varchar(10) NOT NULL,
  blog_service_name varchar(255) NOT NULL default '',
  blog_service_host varchar(255) NOT NULL default '',
  blog_service_port smallint(5) default NULL,
  blog_service_path varchar(255) NOT NULL default '',
  blog_service_methodname varchar(255) NOT NULL default '',
  blog_service_extended tinyint(1) NOT NULL default '0',
  blog_service_enabled tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (blog_service_id)
);";
$TABLE[] = "CREATE TABLE blog_polls (
  poll_id mediumint(8) NOT NULL auto_increment,
  entry_id int(10) NOT NULL default '0',
  start_date int(10) default NULL,
  choices text,
  starter_id mediumint(8) NOT NULL default '0',
  votes smallint(5) NOT NULL default '0',
  poll_question varchar(255) default NULL,
  PRIMARY KEY  (poll_id),
  KEY entry_id (entry_id)
);";
$TABLE[] = "CREATE TABLE blog_ratings (
  rating_id int(10) NOT NULL auto_increment,
  member_id int(8) NOT NULL,
  blog_id int(10) NOT NULL,
  rating_date int(10) NOT NULL,
  rating smallint(1) NOT NULL default '0',
  entry_id int(10) default '0',
  PRIMARY KEY  (rating_id),
  KEY rating_blog_id (blog_id,member_id),
  KEY entryrating_blog_id (member_id, blog_id, entry_id)
);";
$TABLE[] = "CREATE TABLE blog_rsscache (
  blog_id int(10) NOT NULL,
  rsscache_refresh tinyint(1) NOT NULL default '0',
  rsscache_feed mediumtext,
  PRIMARY KEY  (blog_id)
);";

$TABLE[] = "CREATE TABLE blog_rssimport (
	rss_id			INT(10) NOT NULL auto_increment,
	rss_blog_id		INT(10) NOT NULL default '0',
	rss_url			VARCHAR(255) NOT NULL default '',
	rss_per_go		INT(3) NOT NULL default '10',
	rss_auth_user	VARCHAR(255) NOT NULL default '',
	rss_auth_pass	VARCHAR(255) NOT NULL default '',
	rss_last_import	INT(10) NOT NULL default '0',
	rss_in_progress INT(1) NOT NULL default '0',
	rss_count		INT(10) NOT NULL default '0',
	rss_tags		TEXT NULL,
	rss_cats		TEXT NULL,
	PRIMARY KEY rss_id (rss_id),
	KEY blog_time (rss_blog_id, rss_last_import),
	KEY rss_blog_id (rss_blog_id),
	KEY rss_last_import( rss_last_import )
);";

$TABLE[] = "CREATE TABLE blog_this (
  bt_id int(10) NOT NULL AUTO_INCREMENT,
  bt_entry_id int(10) NOT NULL DEFAULT '0',
  bt_app varchar(255) NOT NULL DEFAULT '',
  bt_id1 int(10) NOT NULL DEFAULT '0',
  bt_id2 int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (bt_id),
  KEY lookup (bt_app,bt_id1,bt_id2),
  KEY entry_id (bt_entry_id)
);";

$TABLE[] = "CREATE TABLE blog_themes (
  theme_id int(10) NOT NULL auto_increment,
  theme_on tinyint(1) NOT NULL default '0',
  theme_css mediumtext,
  theme_images varchar(255) default NULL,
  theme_opts text,
  theme_name varchar(255) default NULL,
  theme_author varchar(255) default NULL,
  theme_homepage varchar(255) default NULL,
  theme_email varchar(255) default NULL,
  theme_desc text,
  theme_css_overwrite tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (theme_id),
  KEY theme_on (theme_on)
);";
$TABLE[] = "CREATE TABLE blog_trackback (
  trackback_id int(10) NOT NULL auto_increment,
  blog_id int(10) default '0',
  entry_id int(10) NOT NULL,
  ip_address varchar(46) default NULL,
  trackback_url varchar(255) NOT NULL,
  trackback_title varchar(255) default NULL,
  trackback_excerpt varchar(255) default NULL,
  trackback_blog_name varchar(255) default NULL,
  trackback_date int(10) default NULL,
  trackback_queued tinyint(1) default '0',
  PRIMARY KEY  (trackback_id),
  KEY entry_id (entry_id)
);";
$TABLE[] = "CREATE TABLE blog_trackback_spamlogs (
  trackback_id int(10) NOT NULL auto_increment,
  blog_id int(10) default '0',
  entry_id int(10) NOT NULL,
  ip_address varchar(46) default NULL,
  trackback_url varchar(255) NOT NULL,
  trackback_title varchar(255) default NULL,
  trackback_excerpt varchar(255) default NULL,
  trackback_blog_name varchar(255) default NULL,
  trackback_date int(10) default NULL,
  trackback_queued tinyint(1) default '0',
  PRIMARY KEY  (trackback_id)
);";

$TABLE[] = "CREATE TABLE blog_updatepings (
  ping_id int(10) NOT NULL auto_increment,
  ping_active tinyint(1) NOT NULL default '0',
  ping_time int(10) NOT NULL default '0',
  ping_tries tinyint(1) NOT NULL default '0',
  blog_id int(10) NOT NULL,
  entry_id int(10) NOT NULL,
  ping_service varchar(30) NOT NULL default '',
  PRIMARY KEY  (ping_id),
  KEY blog_activetime (ping_active,ping_time),
  KEY blog_blogentry (blog_id,entry_id)
);";

$TABLE[] = "CREATE TABLE blog_views (
	blog_id int(10) NOT NULL,
	entry_id INT(10) NOT NULL DEFAULT 0,
	KEY blog_id (blog_id)
);";
$TABLE[] = "CREATE TABLE blog_voters (
  vote_id int(10) NOT NULL auto_increment,
  ip_address varchar(46) NOT NULL default '',
  vote_date int(10) NOT NULL default '0',
  entry_id int(10) NOT NULL default '0',
  member_id varchar(32) default NULL,
  PRIMARY KEY  (vote_id),
  KEY entry_id (entry_id,member_id)
);";

$TABLE[] = "ALTER TABLE groups ADD g_blog_attach_max INT(10) NOT NULL DEFAULT '-1'";
$TABLE[] = "ALTER TABLE groups ADD g_blog_attach_per_entry INT(10) NOT NULL DEFAULT '0'";
$TABLE[] = "ALTER TABLE groups ADD g_blog_do_html TINYINT(1) NOT NULL DEFAULT '0'";
$TABLE[] = "ALTER TABLE groups ADD g_blog_do_commenthtml TINYINT(1) NOT NULL DEFAULT '0'";
$TABLE[] = "ALTER TABLE groups ADD g_blog_allowpoll TINYINT(1) NOT NULL DEFAULT '1'";
$TABLE[] = "ALTER TABLE groups ADD g_blog_allowprivate TINYINT(1) NOT NULL DEFAULT '1'";
$TABLE[] = "ALTER TABLE groups ADD g_blog_allowprivclub TINYINT(1) NOT NULL DEFAULT '1'";
$TABLE[] = "ALTER TABLE groups ADD g_blog_alloweditors TINYINT(1) NOT NULL DEFAULT '1'";
$TABLE[] = "ALTER TABLE groups ADD g_blog_allowskinchoose TINYINT(1) NOT NULL DEFAULT '1'";
$TABLE[] = "ALTER TABLE groups ADD g_blog_preventpublish TINYINT(1) NOT NULL DEFAULT '0'";
$TABLE[] = "ALTER TABLE groups ADD g_blog_settings TEXT NULL;";

# IPB might not have it yet
if ( ! ipsRegistry::DB()->checkForField( 'blogs_recache', 'members' ) ) 
{
	$TABLE[] = "ALTER TABLE members ADD blogs_recache tinyint(1) NULL DEFAULT NULL;";
}