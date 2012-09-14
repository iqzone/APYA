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

$SQL[] = "ALTER TABLE topics CHANGE approved approved tinyint(1) NOT NULL default '0';";
$SQL[] = "ALTER TABLE topics DROP INDEX last_post;";
$SQL[] = "ALTER TABLE topics DROP INDEX forum_id;";
$SQL[] = "alter table topics add index forum_id( forum_id, pinned, approved);";
$SQL[] = "ALTER TABLE topics add index last_post(forum_id, pinned, last_post);";
$SQL[] = "ALTER TABLE topics ADD topic_rating_total SMALLINT UNSIGNED NOT NULL default '0';";
$SQL[] = "ALTER TABLE topics ADD topic_rating_hits  SMALLINT UNSIGNED NOT NULL default '0';";
$SQL[] = "ALTER TABLE topics DROP rating;";
$SQL[] = "alter table topics ADD topic_open_time INT(10) NOT NULL default '0';";
$SQL[] = "alter table topics ADD topic_close_time INT(10) NOT NULL default '0';";

