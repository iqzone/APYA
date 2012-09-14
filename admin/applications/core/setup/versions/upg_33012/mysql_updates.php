<?php

$PRE = trim(ipsRegistry::dbFunctions()->getPrefix());
$DB  = ipsRegistry::DB();

$SQL[] = "ALTER TABLE inline_notifications ADD INDEX notify_from_id (notify_from_id);";
$SQL[] = "ALTER TABLE message_posts ADD INDEX msg_author_id (msg_author_id);";

# 3.3.0 fresh install still has this field
if ( $DB->checkForField( 'rss_import_inc_pcount', 'rss_import' ) ) 
{
	$SQL[] = "ALTER TABLE rss_import DROP rss_import_inc_pcount;";
}


# 3.3.x install might still have this old/unused fields
if ( $DB->checkForField( 'sys_module_parent', 'core_sys_module' ) ) 
{
	$SQL[] = "ALTER TABLE core_sys_module DROP sys_module_parent;";
}
if ( $DB->checkForField( 'sys_module_tables', 'core_sys_module' ) ) 
{
	$SQL[] = "ALTER TABLE core_sys_module DROP sys_module_tables;";
}
if ( $DB->checkForField( 'sys_module_hooks', 'core_sys_module' ) ) 
{
	$SQL[] = "ALTER TABLE core_sys_module DROP sys_module_hooks;";
}

# New blog column
if ( ! $DB->checkForField( 'blogs_recache', 'members' ) )
{
	$SQL[] = "ALTER TABLE members ADD blogs_recache tinyint(1) NULL DEFAULT NULL;";
	$SQL[] = "ALTER TABLE members ADD INDEX blogs_recache (blogs_recache);";
}

# New mobile app stuff
$SQL[] = "ALTER TABLE mobile_notifications ADD COLUMN notify_url text null;";
$SQL[] = "CREATE TABLE mobile_app_style (
  id int(10) NOT NULL AUTO_INCREMENT,
  filename varchar(255) NOT NULL,
  hasRetina tinyint(1) NOT NULL DEFAULT '0',
  isInUse tinyint(1) NOT NULL DEFAULT '0',
  lastUpdated int(10) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY filename (filename)
);";
$SQL[] = "CREATE TABLE mobile_device_map (
  token varchar(64) NOT NULL DEFAULT '',
  member_id mediumint(8) DEFAULT NULL,
  PRIMARY KEY (token)
);"; // We don't import the old values since the new mobile app will need resetup anyway. I've also not dropped the old column in case users are still using the old app

$SQL[] = "ALTER TABLE cache_store CHANGE cs_value cs_value mediumtext null default null;";

$SQL[] = "CREATE TABLE reputation_totals (
 rt_key		CHAR(32) NOT NULL,
 rt_app_type CHAR(32) NOT NULL,
 rt_total	INT(10) NOT NULL DEFAULT 0,
 rt_type_id	INT(10) NOT NULL DEFAULT 0,
 PRIMARY KEY (rt_key),
 INDEX rt_app_type (rt_app_type, rt_total)
);";


$SQL[] = "INSERT INTO reputation_totals SELECT MD5( CONCAT( app, ';', type, ';', type_id) ), MD5( CONCAT( app, ';', type ) ), SUM(rep_rating), type_id FROM {$PRE}reputation_index GROUP BY app, type, type_id ON DUPLICATE KEY UPDATE rt_key=rt_key;";

$SQL[] = "ALTER TABLE forums_archive_posts ADD archive_forum_id INT(10) NOT NULL DEFAULT 0;";


//