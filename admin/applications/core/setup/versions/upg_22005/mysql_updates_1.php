<?php
/*
+--------------------------------------------------------------------------
|   IP.Board v3.3.3
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2004 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
*/


# Nothing of interest!

$SQL[]="ALTER TABLE members ADD members_profile_views INT(10) UNSIGNED NOT NULL default '0';";
$SQL[]="ALTER TABLE members ADD member_login_key_expire INT(10) NOT NULL default '0' AFTER member_login_key;";

$SQL[]="alter table members ADD members_l_display_name VARCHAR(255) NOT NULL default '0';";
$SQL[]="alter table members ADD members_l_username   VARCHAR(255) NOT NULL default '0';";
$SQL[]="alter table members DROP INDEX name;";
$SQL[]="alter table members DROP INDEX members_display_name;";
$SQL[]="alter table member_extra change interests interests text NULL;";
$SQL[]="alter table members change ignored_users ignored_users text NULL;";
$SQL[]="alter table members change members_markers members_markers text NULL;";

$SQL[]="ALTER TABLE members ADD INDEX members_l_display_name (members_l_display_name), ADD INDEX members_l_username (members_l_username);";

$SQL[]="ALTER TABLE members ADD failed_logins TEXT NULL;";
$SQL[]="ALTER TABLE members ADD failed_login_count SMALLINT( 3 ) DEFAULT '0' NOT NULL;";

$SQL[]="ALTER TABLE members_partial ADD partial_email_ok INT(1) NOT NULL default '0';";

$SQL[] ="ALTER TABLE attachments ADD attach_rel_id           INT(10) NOT NULL default '0',
                            ADD attach_rel_module       VARCHAR(100) NOT NULL default '0';";

$SQL[] ="ALTER TABLE attachments add attach_img_width        INT(5) NOT NULL default '0',
                            add attach_img_height       INT(5) NOT NULL default '0';";
                                                        
$SQL[] ="ALTER TABLE attachments DROP INDEX attach_mid_size,
                            DROP INDEX attach_pid,
                            DROP INDEX attach_msg,
                            ADD INDEX attach_pid (attach_rel_id),
                            ADD INDEX attach_mid_size (attach_member_id,attach_rel_module, attach_filesize),
                            ADD INDEX attach_where (attach_rel_module, attach_rel_id);";
