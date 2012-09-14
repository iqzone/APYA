<?php

$SQL[] = "ALTER TABLE groups ADD g_blog_allowprivclub TINYINT(1) NOT NULL DEFAULT '0'";
$SQL[] = "ALTER TABLE groups ADD g_blog_alloweditors TINYINT(1) NOT NULL DEFAULT '0'";

$SQL[] = "ALTER TABLE blog_entries ADD entry_author_id INT(8) NOT NULL DEFAULT '0'";
$SQL[] = "ALTER TABLE blog_entries ADD entry_author_name VARCHAR(255) NOT NULL DEFAULT ''";
$SQL[] = "ALTER TABLE blog_entries ADD category_id int(10) not null default '0'";
$SQL[] = "ALTER TABLE blog_entries ADD entry_use_emo tinyint(1) not null default '0'";
$SQL[] = "ALTER TABLE blog_entries ADD INDEX entry_category_id (blog_id, category_id)";

$SQL[] = "ALTER TABLE blog_blogs DROP blog_num_entries";
$SQL[] = "ALTER TABLE blog_blogs DROP blog_num_drafts";
$SQL[] = "ALTER TABLE blog_blogs DROP blog_num_comments";
$SQL[] = "ALTER TABLE blog_blogs DROP blog_last_entry";
$SQL[] = "ALTER TABLE blog_blogs DROP blog_last_entryname";
$SQL[] = "ALTER TABLE blog_blogs DROP blog_last_date";
$SQL[] = "ALTER TABLE blog_blogs DROP blog_last_comment";
$SQL[] = "ALTER TABLE blog_blogs DROP blog_last_comment_date";
$SQL[] = "ALTER TABLE blog_blogs DROP blog_last_comment_entry";
$SQL[] = "ALTER TABLE blog_blogs DROP blog_last_comment_entryname";
$SQL[] = "ALTER TABLE blog_blogs DROP blog_last_comment_name";
$SQL[] = "ALTER TABLE blog_blogs DROP blog_last_comment_mid";

$SQL[] = "ALTER TABLE blog_moderators ADD moderate_can_editcblocks tinyint(1) default '0'";
$SQL[] = "ALTER TABLE blog_moderators ADD moderate_can_editaboutme tinyint(1) default '0'";

$SQL[] = "CREATE TABLE blog_categories(
  category_id int(10) not null auto_increment,
  blog_id int(10) not null default '0',
  category_name varchar(255) not null,
  category_default tinyint(1) not null default '1',
  category_type varchar(12) not null default '',
PRIMARY KEY(category_id),
KEY category_blog(blog_id, category_type)
)";

$SQL[] = "CREATE TABLE blog_lastinfo(
  blog_id int(10) not null,
  level varchar(15) not null,
  blog_num_entries 				int(10) null default '0',
  blog_num_drafts				int(10) null default '0',
  blog_num_comments				int(10) null default '0',
  blog_last_entry				int(10) null,
  blog_last_entryname			varchar(250) null,
  blog_last_date				int(10) null,
  blog_last_comment				int(10) null,
  blog_last_comment_date		int(10) null,
  blog_last_comment_entry		int(10) null,
  blog_last_comment_entryname	varchar(250) null,
  blog_last_comment_name		varchar(255) null,
  blog_last_comment_mid			int(10) null,
  blog_last_update				int(10) default '0',
PRIMARY KEY(blog_id, level)
)";

$SQL[] = "CREATE TABLE blog_tracker (
  tracker_id int(10) NOT NULL auto_increment,
  blog_id int(10) NOT NULL,
  member_id int(8) NOT NULL,
PRIMARY KEY(tracker_id),
KEY tracker_blogentry(blog_id, member_id)
)";

$SQL[] = "CREATE TABLE blog_tracker_queue (
  tq_id int(10) NOT NULL auto_increment,
  blog_id int(10) NOT NULL,
  entry_id int(10) NOT NULL,
  tq_to varchar(255) NOT NULL default '',
  tq_subject text NOT NULL,
  tq_content text NOT NULL,
  PRIMARY KEY  (tq_id),
  KEY trackqueue_blogentry(blog_id, entry_id)
)";

$SQL[] = "INSERT INTO blog_default_cblocks(cbdef_name, cbdef_function, cbdef_default, cbdef_order, cbdef_locked, cbdef_enabled)
VALUES('Active Users','get_active_users', 0, 8, 0, 1)";
$SQL[] = "INSERT INTO blog_default_cblocks(cbdef_name, cbdef_function, cbdef_default, cbdef_order, cbdef_locked, cbdef_enabled)
VALUES('Categories','get_my_categories', 0, 9, 0, 1)";
$SQL[] = "INSERT INTO blog_default_cblocks(cbdef_name, cbdef_function, cbdef_default, cbdef_order, cbdef_locked, cbdef_enabled)
VALUES('My Search','get_my_search', 0, 10, 0, 1)";

$SQL[] ="INSERT INTO custom_bbcode (bbcode_title, bbcode_desc, bbcode_tag, bbcode_replace, bbcode_useoption, bbcode_example) VALUES ('Extract Blog Entry', 'This will allow users to define an extract for an entry. Only this piece of the entry will be displayed on the main blog page and will show up in the RSS feed.', 'extract', '<!--blog.extract.start-->{content}<!--blog.extract.end-->', 0, '[extract]This is an example![/extract]')";
$SQL[] ="INSERT INTO custom_bbcode (bbcode_title, bbcode_desc, bbcode_tag, bbcode_replace, bbcode_useoption, bbcode_example) VALUES ('Blog Link', 'This tag provides an easy way to link to a blog.', 'blog', '<a href=\'index.php?autocom=blog&amp;blogid={option}\'>{content}</a>', 1, '[blog=100]Click me![/blog]')";
$SQL[] ="INSERT INTO custom_bbcode (bbcode_title, bbcode_desc, bbcode_tag, bbcode_replace, bbcode_useoption, bbcode_example) VALUES ('Blog Entry Link', 'This tag provides an easy way to link to a blog entry.', 'entry', '<a href=\'index.php?autocom=blog&amp;cmd=showentry&amp;eid={option}\'>{content}</a>', 1, '[entry=100]Click me![/entry]')";

$SQL[] ="DELETE FROM conf_settings WHERE conf_key='blog_allow_stripheader'";
