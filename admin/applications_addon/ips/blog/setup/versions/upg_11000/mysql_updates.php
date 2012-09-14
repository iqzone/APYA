<?php

$SQL[] = "CREATE TABLE blog_moderators (
	moderate_id	int(10) not null auto_increment,
	moderate_type varchar(10) not null,
	moderate_mg_id int(10) not null,
	moderate_can_edit_comments tinyint(1) not null default '0',
	moderate_can_edit_entries tinyint(1) not null default '0',
	moderate_can_del_comments tinyint(1) not null default '0',
	moderate_can_del_entries tinyint(1) not null default '0',
	moderate_can_lock tinyint not null default '0',
	moderate_can_publish tinyint not null default '0',
	moderate_can_view_draft tinyint not null default '0',
	moderate_can_view_private tinyint not null default '0',
	moderate_can_warn tinyint not null default '0',
	moderate_can_pin tinyint not null default '0',
	moderate_can_disable tinyint not null default '0',
PRIMARY KEY( moderate_id )
)";

$SQL[] = "CREATE TABLE blog_ratings (
  rating_id int(10) NOT NULL auto_increment,
  member_id int(8) NOT NULL,
  blog_id int(10) NOT NULL,
  rating_date int(10) NOT NULL,
  rating smallint(1) NOT NULL default '0',
  PRIMARY KEY (rating_id),
  KEY rating_blog_id (blog_id, member_id)
)";

$SQL[] = "ALTER TABLE blog_blogs ADD blog_rating_total int(10) default '0'";
$SQL[] = "ALTER TABLE blog_blogs ADD blog_rating_count int(10) default '0'";
$SQL[] = "ALTER TABLE blog_blogs ADD blog_last_delete int(10) default '0'";

$SQL[] = "CREATE TABLE blog_read (
  blog_id int(10) NOT NULL,
  member_id int(8) NOT NULL,
  last_read int(10) NOT NULL default '0',
  unread_count smallint(5) NOT NULL default '0',
  last_count int(10) NOT NULL default '0',
  entries_read text NULL,
PRIMARY KEY (blog_id, member_id)
)";

$SQL[] = "ALTER TABLE blog_entries ADD entry_last_update int(10) default '0'";
$SQL[] = "ALTER TABLE blog_entries ADD entry_gallery_album int(10) default '0'";
$SQL[] = "ALTER TABLE blog_entries ADD INDEX entry_last_update (blog_id, entry_status, entry_last_update)";
$SQL[] = "UPDATE blog_entries
SET entry_last_update = entry_date
WHERE entry_date>entry_last_comment_date or entry_last_comment_date is null";
$SQL[] = "UPDATE blog_entries
SET entry_last_update = entry_last_comment_date
WHERE entry_last_comment_date>=entry_date and entry_last_comment_date is not null";

$SQL[] = "ALTER TABLE blog_cblocks ADD cblock_position VARCHAR(10) NOT NULL default 'right'";

$SQL[] = "INSERT INTO skin_macro (macro_value, macro_replace, macro_can_remove, macro_set) VALUES ('B_NONEW', '<img src=\'style_images/<#IMG_DIR#>/bb_nonew.gif\' border=\'0\' alt=\'Hosted Blog\' />', 1, 1)";

$SQL[] = "INSERT INTO blog_default_cblocks(cbdef_name, cbdef_function, cbdef_default, cbdef_order, cbdef_locked, cbdef_enabled)
VALUES('Last 10 Comments','get_last_comments', 1, 5, 0, 1)";
