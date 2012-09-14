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


$SQL[] = "alter table topics add index last_post_sorting(last_post,forum_id);";
$SQL[] = "alter table profile_comments drop index my_comments;";
$SQL[] = "alter table profile_comments add index my_comments (comment_for_member_id,comment_date);";
$SQL[] = "ALTER TABLE sessions ADD INDEX ( running_time );";

$SQL[] = "ALTER TABLE conf_settings DROP conf_help_key;";
