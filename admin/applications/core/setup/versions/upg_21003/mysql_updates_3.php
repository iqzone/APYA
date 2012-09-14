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

$SQL[] = "ALTER TABLE sessions ADD location_1_type char(10) NOT NULL default '',
  ADD location_1_id int(10) NOT NULL default '0', 
  ADD location_2_type char(10) NOT NULL default '',
  ADD location_2_id int(10) NOT NULL default '0', 
  ADD location_3_type char(10) NOT NULL default '',
  ADD location_3_id int(10) NOT NULL default '0';";
$SQL[] = "ALTER TABLE sessions ADD INDEX location1(location_1_type, location_1_id);";
$SQL[] = "ALTER TABLE sessions ADD INDEX location2(location_2_type, location_2_id);";
$SQL[] = "ALTER TABLE sessions ADD INDEX location3(location_3_type, location_3_id);";
$SQL[] = "ALTER TABLE sessions DROP in_forum, DROP in_topic;";



$SQL[] = "ALTER TABLE forums ADD forum_last_deletion INT(10) NOT NULL default '0';";
$SQL[] = "ALTER TABLE forums ADD forum_allow_rating TINYINT(1) NOT NULL default '0';";
$SQL[] = "ALTER TABLE groups ADD g_topic_rate_setting SMALLINT(2) NOT NULL default '0';";
$SQL[] = "alter table validating ADD user_verified tinyint(1) NOT NULL default '0';";
$SQL[] = "alter table moderators ADD mod_can_set_open_time tinyint(1) NOT NULL default '0';";
$SQL[] = "alter table moderators ADD mod_can_set_close_time tinyint(1) NOT NULL default '0';";
$SQL[] = "alter table task_manager ADD task_locked INT(10) NOT NULL default '0';";
$SQL[] = "alter table groups ADD g_dname_changes INT(3) NOT NULL default '0',
  					   ADD g_dname_date    INT(5) NOT NULL default '0';";
$SQL[] = "ALTER TABLE search_results ADD PRIMARY KEY(id);";
$SQL[] = "ALTER TABLE search_results ADD INDEX search_date(search_date);";
