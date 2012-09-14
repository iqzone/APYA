<?php

$SQL[] = "CREATE TABLE blog_updatepings(
  ping_id int(10) not null auto_increment,
  ping_active tinyint(1) not null default 0,
  ping_time int(10) not null default 0,
  ping_tries tinyint(1) not null default 0,
  blog_id int(10) not null,
  entry_id int(10) not null,
  ping_service varchar(30) not null default '',
PRIMARY KEY(ping_id),
KEY blog_activetime (ping_active, ping_time),
KEY blog_blogentry (blog_id, entry_id)
)";

$SQL[] = "CREATE TABLE blog_views (
	blog_id int(10) not null,
	KEY blog_id (blog_id)
)";

$SQL[] = "CREATE TABLE blog_trackback_spamlogs(
	trackback_id			int(10) not null auto_increment,
	blog_id					int(10) default '0',
	entry_id				int(10) not null,
	ip_address				varchar(16),
	trackback_url			varchar(255) not null,
	trackback_title			varchar(255),
	trackback_excerpt		varchar(255),
	trackback_blog_name		varchar(255),
	trackback_date			int(10),
	trackback_queued		tinyint(1) default '0',
PRIMARY KEY(trackback_id)
)";

$SQL[] = "ALTER TABLE blog_moderators ADD moderate_can_del_trackback TINYINT(1) DEFAULT '0'";
$SQL[] = "ALTER TABLE blog_trackback ADD blog_id INT(10) DEFAULT '0'";
$SQL[] = "ALTER TABLE blog_trackback ADD ip_address VARCHAR(16)";
$SQL[] = "ALTER TABLE blog_trackback ADD trackback_queued TINYINT(1) DEFAULT '0'";

$SQL[] = "INSERT INTO attachments( attach_file, attach_location, attach_thumb_location, attach_hits, attach_date, attach_post_key, attach_member_id,
                                       attach_filesize, attach_thumb_width, attach_thumb_height, attach_is_image, attach_ext, attach_rel_id,
                                       attach_rel_module )
          SELECT attach_file, attach_location, attach_thumb_location, attach_hits, attach_date, attach_post_key, attach_member_id,
                 attach_filesize, attach_thumb_width, attach_thumb_height, attach_is_image, attach_ext,
                 case when attach_entry_id > 0 then attach_entry_id else attach_cbcus_id end,
                 case when attach_entry_id > 0 then 'blogentry' else 'blogcblock' end
          FROM blog_attachments;";
$SQL[] = "DROP TABLE blog_attachments";

$SQL[] = "ALTER TABLE blog_moderators DROP moderate_can_editaboutme";

$SQL[] = "DELETE FROM conf_settings WHERE conf_key='blog_allow_aboutme'";
