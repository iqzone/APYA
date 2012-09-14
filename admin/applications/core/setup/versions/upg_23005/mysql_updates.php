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


$SQL[] = "delete from conf_settings where conf_key='csite_search_show';";
$SQL[] = "delete from conf_settings where conf_key='csite_skinchange_show';";

$SQL[] = "ALTER TABLE forums CHANGE last_poster_name last_poster_name VARCHAR( 255 ) NULL DEFAULT NULL;";

$SQL[] = "ALTER TABLE announcements ADD announce_nlbr_enabled TINYINT( 1 ) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE subscription_trans CHANGE subtrans_start_date subtrans_start_date VARCHAR( 13 ) NOT NULL DEFAULT '0', CHANGE subtrans_end_date subtrans_end_date VARCHAR( 13 ) NOT NULL DEFAULT '0';";

$SQL[] = "CREATE TABLE api_log (
  api_log_id 		int(10) unsigned NOT NULL auto_increment,
  api_log_key 		VARCHAR(32) NOT NULL,
  api_log_ip 		VARCHAR(16) NOT NULL,
  api_log_date 		INT(10) NOT NULL,
  api_log_query 	TEXT NOT NULL,
  api_log_allowed 	TINYINT(1) unsigned NOT NULL,
  PRIMARY KEY  (api_log_id)
);";

$SQL[] = "CREATE TABLE api_users (
  api_user_id		INT(4) unsigned NOT NULL auto_increment,
  api_user_key		CHAR(32) NOT NULL,
  api_user_name		VARCHAR(32) NOT NULL,
  api_user_perms 	TEXT NOT NULL,
  api_user_ip 		VARCHAR(16) NOT NULL,
  PRIMARY KEY  (api_user_id)
);";

$SQL[] = "ALTER TABLE skin_sets ADD set_protected TINYINT( 1 ) NOT NULL DEFAULT '0';";
