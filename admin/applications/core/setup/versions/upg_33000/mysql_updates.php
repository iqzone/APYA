<?php

$SQL[] = "CREATE TABLE members_warn_actions (
  wa_id int(11) unsigned NOT NULL AUTO_INCREMENT,
  wa_points int(32) DEFAULT NULL,
  wa_mq int(2) DEFAULT NULL,
  wa_mq_unit char(1) DEFAULT NULL,
  wa_rpa int(2) DEFAULT NULL,
  wa_rpa_unit char(1) DEFAULT NULL,
  wa_suspend int(2) DEFAULT NULL,
  wa_suspend_unit char(1) DEFAULT NULL,
  wa_ban_group tinyint(1) DEFAULT NULL,
  wa_override tinyint(1) DEFAULT NULL,
  PRIMARY KEY (wa_id),
  KEY wa_points (wa_points)
);";

$SQL[] = "CREATE TABLE members_warn_logs (
  wl_id int(11) unsigned NOT NULL AUTO_INCREMENT,
  wl_member mediumint(8) DEFAULT NULL,
  wl_moderator mediumint(8) DEFAULT NULL,
  wl_date int(10) DEFAULT NULL,
  wl_reason int(10) DEFAULT NULL,
  wl_points int(5) DEFAULT NULL,
  wl_note_member text,
  wl_note_mods text,
  wl_mq int(2) DEFAULT NULL,
  wl_mq_unit char(1) DEFAULT NULL,
  wl_rpa int(2) DEFAULT NULL,
  wl_rpa_unit char(1) DEFAULT NULL,
  wl_suspend int(2) DEFAULT NULL,
  wl_suspend_unit char(1) DEFAULT NULL,
  wl_ban_group tinyint(1) DEFAULT NULL,
  wl_expire int(2) DEFAULT NULL,
  wl_expire_unit char(1) DEFAULT NULL,
  wl_acknowledged tinyint(1) DEFAULT NULL,
  wl_content_app varchar(32) DEFAULT NULL,
  wl_content_id1 varchar(32) DEFAULT NULL,
  wl_content_id2 varchar(32) DEFAULT NULL,
  wl_expire_date INT(10) DEFAULT NULL,
  PRIMARY KEY (wl_id),
  KEY wl_member (wl_member),
  KEY wl_moderator (wl_moderator),
  KEY wl_date (wl_member,wl_date),
  KEY content (wl_content_app,wl_content_id1,wl_content_id2),
  KEY wl_expire_date (wl_expire_date)
);";

$SQL[] = "CREATE TABLE members_warn_reasons (
  wr_id int(11) unsigned NOT NULL AUTO_INCREMENT,
  wr_name varchar(255) DEFAULT NULL,
  wr_points float DEFAULT NULL,
  wr_points_override tinyint(1) DEFAULT NULL,
  wr_remove int(2) DEFAULT NULL,
  wr_remove_unit char(1) DEFAULT NULL,
  wr_remove_override tinyint(1) DEFAULT NULL,
  wr_order int(10) DEFAULT NULL,
  PRIMARY KEY (wr_id),
  KEY wr_order (wr_order)
);";

$SQL[] = "INSERT INTO members_warn_reasons (wr_id, wr_name, wr_points, wr_points_override, wr_remove, wr_remove_unit, wr_remove_override, wr_order)
VALUES
	(1, 'Spamming', 1, 0, 0, 'h', 0, 1),
	(2, 'Inappropriate Language', 1, 0, 0, 'h', 0, 2),
	(3, 'Signature Violation', 1, 0, 0, 'h', 0, 3),
	(4, 'Abusive Behaviour', 1, 0, 0, 'h', 0, 4),
	(5, 'Topic Bumping', 1, 0, 0, 'h', 0, 5);";
	
$SQL[] = "ALTER TABLE members ADD unacknowledged_warnings TINYINT(1) DEFAULT NULL;";

$SQL[] = "DELETE FROM core_sys_conf_settings WHERE conf_key IN ('warn_min', 'warn_max', 'warn_past_max', 'warn_mod_ban', 'warn_mod_modq', 'warn_mod_post', 'warn_gmod_ban', 'warn_gmod_modq', 'warn_gmod_post', 'resize_img_percent');";

$SQL[] = "UPDATE moderators SET mod_bitoptions=mod_bitoptions-496 WHERE mod_bitoptions > 496;";

$SQL[] = "ALTER TABLE reputation_cache ADD cache_date INT(10) NOT NULL DEFAULT 0, ADD KEY cache_date(cache_date);";
$SQL[] = "ALTER TABLE core_item_markers DROP KEY marker_index, ADD KEY marker_index (item_member_id, item_app, item_app_key_1);";

//
