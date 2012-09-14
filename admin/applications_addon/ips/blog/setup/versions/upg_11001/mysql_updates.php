<?php

$SQL[] = "ALTER TABLE groups ADD g_blog_allowskinchoose TINYINT(1) NOT NULL DEFAULT '0'";
$SQL[] = "ALTER TABLE blog_blogs ADD blog_skin_id smallint(5) default '0'";
$SQL[] = "ALTER TABLE blog_moderators ADD moderate_can_approve tinyint(1) default '0'";
$SQL[] = "ALTER TABLE blog_entries ADD entry_queued_comments smallint(5) NOT NULL default '0'";

$SQL[] = "CREATE TABLE blog_authmembers(
  blog_id	int not null,
  member_id	int not null,
KEY blog_id (blog_id),
KEY member_id (member_id))";

$SQL[] = "ALTER TABLE groups DROP g_blog_allowownmod";

$SQL[] = "INSERT INTO blog_default_cblocks(cbdef_name, cbdef_function, cbdef_default, cbdef_order, cbdef_locked, cbdef_enabled)
VALUES('My Picture','get_my_picture', 0, 6, 0, 1)";

$SQL[] = "INSERT INTO skin_macro (macro_value, macro_replace, macro_can_remove, macro_set) VALUES ('P_APPROVE', '<img src=\'style_images/<#IMG_DIR#>/p_approve.gif\' border=\'0\' alt=\'Approve\' />', 1, 1)";
$SQL[] = "INSERT INTO skin_macro (macro_value, macro_replace, macro_can_remove, macro_set) VALUES ('BP_NEW', '<img src=\'style_images/<#IMG_DIR#>/bp_new.gif\' border=\'0\' alt=\'Private Blog\' />', 1, 1)";
$SQL[] = "INSERT INTO skin_macro (macro_value, macro_replace, macro_can_remove, macro_set) VALUES ('BP_NONEW', '<img src=\'style_images/<#IMG_DIR#>/bp_nonew.gif\' border=\'0\' alt=\'Private Blog\' />', 1, 1)";
$SQL[] = "INSERT INTO skin_macro (macro_value, macro_replace, macro_can_remove, macro_set) VALUES ('BLOG_RATE_1', '<img src=\'style_images/<#IMG_DIR#>/blog_rate1.gif\' border=\'0\' alt=\'X\' />', 1, 1)";
$SQL[] = "INSERT INTO skin_macro (macro_value, macro_replace, macro_can_remove, macro_set) VALUES ('BLOG_RATE_2', '<img src=\'style_images/<#IMG_DIR#>/blog_rate2.gif\' border=\'0\' alt=\'XX\' />', 1, 1)";
$SQL[] = "INSERT INTO skin_macro (macro_value, macro_replace, macro_can_remove, macro_set) VALUES ('BLOG_RATE_3', '<img src=\'style_images/<#IMG_DIR#>/blog_rate3.gif\' border=\'0\' alt=\'XXX\' />', 1, 1)";
$SQL[] = "INSERT INTO skin_macro (macro_value, macro_replace, macro_can_remove, macro_set) VALUES ('BLOG_RATE_4', '<img src=\'style_images/<#IMG_DIR#>/blog_rate4.gif\' border=\'0\' alt=\'XXXX\' />', 1, 1)";
$SQL[] = "INSERT INTO skin_macro (macro_value, macro_replace, macro_can_remove, macro_set) VALUES ('BLOG_RATE_5', '<img src=\'style_images/<#IMG_DIR#>/blog_rate5.gif\' border=\'0\' alt=\'XXXXX\' />', 1, 1)";
$SQL[] = "INSERT INTO skin_macro (macro_value, macro_replace, macro_can_remove, macro_set) VALUES ('ADDFAV', '<img src=\'style_images/<#IMG_DIR#>/add_fav.gif\' border=\'0\' alt=\'\' />', 1, 1)";
$SQL[] = "INSERT INTO skin_macro (macro_value, macro_replace, macro_can_remove, macro_set) VALUES ('DELFAV', '<img src=\'style_images/<#IMG_DIR#>/del_fav.gif\' border=\'0\' alt=\'\' />', 1, 1)";
