<?php

# 2.1.0 beta 1

$DB  = ipsRegistry::DB();
$PRE = ipsRegistry::dbFunctions()->getPrefix();


$SQL[] = "DROP TABLE IF EXISTS blog_categories;";

$SQL[] = "CREATE TABLE blog_categories (
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

$SQL[] = "CREATE TABLE blog_category_mapping (
  map_category_id int(10) unsigned NOT NULL,
  map_entry_id int(10) unsigned NOT NULL,
  map_blog_id int(10) unsigned NOT NULL,
  map_is_draft int(1) NOT NULL DEFAULT '0',
  map_is_private int(1) NOT NULL DEFAULT '0',
  KEY cap_map_lookup (map_category_id,map_blog_id),
  KEY map_entry_id (map_entry_id)
);";

$SQL[] = "CREATE TABLE blog_editors_map (
  editor_member_id int(10) unsigned NOT NULL DEFAULT '0',
  editor_blog_id int(10) unsigned NOT NULL DEFAULT '0',
  editor_added int(10) unsigned NOT NULL DEFAULT '0',
  UNIQUE KEY editor_map (editor_member_id,editor_blog_id)
);";

$SQL[] = "CREATE TABLE blog_this (
  bt_id int(10) NOT NULL AUTO_INCREMENT,
  bt_entry_id int(10) NOT NULL DEFAULT '0',
  bt_app varchar(255) NOT NULL DEFAULT '',
  bt_id1 int(10) NOT NULL DEFAULT '0',
  bt_id2 int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (bt_id),
  KEY lookup (bt_app,bt_id1,bt_id2),
  KEY entry_id (bt_entry_id)
);";

$SQL[] = "CREATE TABLE blog_rssimport (
	rss_id INT(10) NOT NULL auto_increment,
	rss_blog_id INT(10) NOT NULL default '0',
	rss_url VARCHAR(255) NOT NULL default '',
	rss_per_go INT(3) NOT NULL default '10',
	rss_auth_user VARCHAR(255) NOT NULL default '',
	rss_auth_pass VARCHAR(255) NOT NULL default '',
	rss_last_import INT(10) NOT NULL default '0',
	rss_in_progress INT(1) NOT NULL default '0',
	rss_count INT(10) NOT NULL default '0',
	rss_tags TEXT NULL,
	rss_cats TEXT NULL,
	PRIMARY KEY rss_id (rss_id),
	KEY blog_time (rss_blog_id, rss_last_import),
	KEY rss_blog_id (rss_blog_id),
	KEY rss_last_import( rss_last_import )
);";

if ( ! $DB->checkForTable( 'core_rss_imported' ) )
{
	$SQL[] = "CREATE TABLE core_rss_imported (
	rss_guid CHAR(32) NOT NULL,
	rss_foreign_id INT(10) NOT NULL default '0',
	rss_foreign_key VARCHAR(100) NOT NULL default '',
	PRIMARY KEY (rss_guid),
	KEY rss_grabber (rss_guid, rss_foreign_key)
);";
}


$SQL[] = "ALTER TABLE blog_blogs ADD blog_seo_name varchar(255) default null;";
$SQL[] = "ALTER TABLE blog_blogs ADD blog_categories TEXT;";
$SQL[] = "ALTER TABLE blog_blogs ADD blog_editors INT(3);";
$SQL[] = "ALTER TABLE blog_blogs ADD blog_groupblog_ids VARCHAR(255) NOT NULL default '';";
$SQL[] = "ALTER TABLE blog_blogs ADD blog_groupblog_name VARCHAR(255) NOT NULL default '';";
$SQL[] = "ALTER TABLE blog_blogs ADD blog_groupblog INT(1) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE blog_blogs ADD blog_last_edate INT(10) NOT NULL default '0';";
$SQL[] = "ALTER TABLE blog_blogs ADD blog_lentry_banish int(1) DEFAULT '0';";
$SQL[] = "ALTER TABLE blog_blogs ADD blog_last_udate INT(10) NOT NULL default '0';";
$SQL[] = "ALTER TABLE blog_blogs ADD blog_owner_only INT(1) NOT NULL default '0';";
$SQL[] = "ALTER TABLE blog_blogs ADD blog_authorized_users VARCHAR(255) NULL;";
$SQL[] = "ALTER TABLE blog_blogs ADD INDEX blog_lentry_banish (blog_lentry_banish);";
$SQL[] = "ALTER TABLE blog_blogs ADD INDEX blog_groupblog (blog_groupblog);";
$SQL[] = "ALTER TABLE blog_blogs ADD INDEX blog_grabber (blog_disabled, blog_type, blog_view_level);";
$SQL[] = "ALTER TABLE blog_blogs ADD INDEX blog_view_level (blog_view_level);";
$SQL[] = "ALTER TABLE blog_blogs ADD INDEX blog_pinned (blog_pinned);";
$SQL[] = "ALTER TABLE blog_blogs ADD INDEX blog_last_edate(blog_last_edate);";
$SQL[] = "ALTER TABLE blog_blogs ADD INDEX as_list_view (blog_pinned, blog_disabled, blog_allowguests, blog_last_edate);";
$SQL[] = "ALTER TABLE blog_blogs ADD INDEX auth_user (blog_owner_only, blog_authorized_users);";

$SQL[] = "ALTER TABLE blog_lastinfo ADD blog_tag_cloud text;";
$SQL[] = "ALTER TABLE blog_lastinfo ADD blog_cblocks text;";
$SQL[] = "ALTER TABLE blog_lastinfo ADD blog_last_comment_20 text;";
$SQL[] = "ALTER TABLE blog_lastinfo ADD blog_cblocks_available text;";

$SQL[] = "UPDATE blog_blogs b, `{$PRE}blog_lastinfo` i SET b.blog_last_edate=i.blog_last_date WHERE b.blog_id=i.blog_id;";
$SQL[] = "UPDATE blog_blogs b, `{$PRE}blog_lastinfo` i SET b.blog_last_udate=i.blog_last_update WHERE b.blog_id=i.blog_id;";
$SQL[] = "UPDATE blog_blogs b, `{$PRE}permission_index` p SET b.blog_owner_only=p.owner_only, b.blog_authorized_users=p.authorized_users WHERE p.app='blog' AND b.blog_id=p.perm_type_id;";

if ( $DB->checkForField( 'entry_category', 'blog_entries' ) )
{
	$SQL[] = "ALTER TABLE blog_entries DROP entry_category";
}

$SQL[] = "ALTER TABLE blog_entries ADD entry_category TEXT null;";
$SQL[] = "ALTER TABLE blog_entries ADD entry_name_seo varchar(255) NOT NULL default '';";
$SQL[] = "ALTER TABLE blog_entries ADD entry_tag_cache TEXT null;";
$SQL[] = "ALTER TABLE blog_entries ADD entry_short TEXT null;";
$SQL[] = "ALTER TABLE blog_entries ADD entry_rating_total int(10) default '0';";
$SQL[] = "ALTER TABLE blog_entries ADD entry_rating_count int(10) default '0';";
$SQL[] = "ALTER TABLE blog_entries ADD entry_rss_import INT(1) NOT NULL default '0';";
$SQL[] = "ALTER TABLE blog_entries ADD entry_future_date INT(1) NOT NULL default '0';";
$SQL[] = "ALTER TABLE blog_entries ADD entry_banish int(1) DEFAULT '0';";
$SQL[] = "ALTER TABLE blog_entries ADD INDEX entry_banish (entry_banish);";
$SQL[] = "ALTER TABLE blog_entries ADD INDEX entry_future_date (entry_future_date, entry_date);";
$SQL[] = "ALTER TABLE blog_entries ADD INDEX entry_rss_import (entry_rss_import);";
$SQL[] = "ALTER TABLE blog_entries ADD INDEX entry_status (entry_status);";
$SQL[] = "ALTER TABLE blog_entries ADD INDEX entry_date ( entry_date );";

$SQL[] = "ALTER TABLE blog_comments ADD INDEX entry_comment( comment_id, entry_id );";

$SQL[] = "ALTER TABLE members CHANGE has_blog has_blog TEXT null;";
$SQL[] = "UPDATE members SET has_blog=null WHERE has_blog=0;";
$SQL[] = "UPDATE members set has_blog='recache' WHERE has_blog=1;";

if ( ! $DB->checkForField( 'tag_hidden', 'tags_index' ) )
{
	$SQL[] = "ALTER TABLE tags_index ADD tag_hidden INT(1) NOT NULL default '0';";
	$SQL[] = "ALTER TABLE tags_index ADD INDEX tag_grab (app, type, type_id, type_2, type_id_2, tag_hidden);";
}

$SQL[] = "ALTER TABLE blog_ratings ADD entry_id int(10) default '0';";
$SQL[] = "ALTER TABLE blog_ratings ADD INDEX entryrating_blog_id (member_id, blog_id, entry_id);";

$SQL[] = "UPDATE blog_default_cblocks SET cbdef_name='Tags', cbdef_function='get_my_tags' where cbdef_function='get_my_categories';";
$SQL[] = "INSERT INTO blog_default_cblocks (cbdef_name, cbdef_function, cbdef_default, cbdef_order, cbdef_locked, cbdef_enabled) VALUES ('Categories', 'get_my_categories', 1, 11, 0, 1 );";

$SQL[] = "DELETE from core_sys_conf_settings WHERE conf_key='blog_default_view';";
$SQL[] = "DELETE from core_sys_conf_settings WHERE conf_key='blog_allow_viewmode';";
$SQL[] = "DELETE from core_sys_conf_settings WHERE conf_key='blog_lastread_cutoff';";
$SQL[] = "DELETE from core_sys_conf_settings WHERE conf_key='blog_resize_img_percent';";
$SQL[] = "DELETE from core_sys_conf_settings WHERE conf_key='blog_resize_linked_img';";
$SQL[] = "DELETE from core_sys_conf_settings WHERE conf_key='blog_allow_draft';";
$SQL[] = "DELETE from core_sys_conf_settings WHERE conf_key='blog_show_img_upload';";
$SQL[] = "DELETE from core_sys_conf_settings WHERE conf_key='blog_thumb';";
$SQL[] = "DELETE from core_sys_conf_settings WHERE conf_key='blog_guest_captcha';";
$SQL[] = "DELETE from core_sys_conf_settings WHERE conf_key='blog_showstats';";

$SQL[] = "ALTER TABLE blog_tracker ADD entry_id int(10) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE blog_tracker DROP INDEX tracker_blogentry;";
$SQL[] = "ALTER TABLE blog_tracker ADD INDEX tracker_blogentry (blog_id,member_id,entry_id);";
$SQL[] = "ALTER TABLE blog_tracker ADD auto_comments INT(1) NOT NULL default '0';";
$SQL[] = "ALTER TABLE blog_tracker ADD INDEX auto_comments (auto_comments);";

$SQL[] = "DELETE FROM blog_tracker_queue;";

$SQL[] = "DROP TABLE IF EXISTS blog_authmembers;";

$SQL[] = "DELETE FROM core_sys_module WHERE sys_module_application='blog' AND sys_module_key='groups';";



