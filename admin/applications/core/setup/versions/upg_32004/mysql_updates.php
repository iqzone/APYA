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

$SQL[] = "DELETE FROM core_sys_conf_settings WHERE conf_key IN( 'posting_allow_rte', 'spider_group' );";
$SQL[] = "UPDATE core_sys_conf_settings SET conf_key='member_photo_gif_animate' WHERE conf_key='member_photo_gif_no_animate';";
$SQL[] = "ALTER TABLE groups DROP g_email_friend, DROP g_email_limit;";
$SQL[] = "ALTER TABLE reputation_cache ADD INDEX ( type , type_id ) ;";
$SQL[] = "ALTER TABLE skin_collections CHANGE set_permissions set_permissions TEXT NULL;";
$SQL[] = "ALTER TABLE member_status_updates DROP INDEX s_hash , ADD INDEX s_hash ( status_member_id , status_hash , status_date );";

