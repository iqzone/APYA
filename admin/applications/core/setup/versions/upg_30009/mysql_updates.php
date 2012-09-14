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

# 3.0.2

/* Field was incorrectly added during beta ..er.. something */
if ( ipsRegistry::DB()->checkForField( 'fb_status', 'profile_portal' ) )
{
	$SQL[] = "ALTER TABLE profile_portal DROP fb_status;";
}

/* Added during 2.3.6 upgrade */
if ( ! ipsRegistry::DB()->checkForField( 'app_hide_tab', 'core_applications' ) )
{
	$SQL[] = "ALTER TABLE core_applications ADD app_hide_tab TINYINT(1) NOT NULL DEFAULT '0';";
}

if( ! ipsRegistry::DB()->checkForField( 'app_tab_groups', 'core_applications' ) )
{
	$SQL[] = "ALTER TABLE core_applications ADD app_website VARCHAR(255) NULL DEFAULT NULL,
		ADD app_update_check VARCHAR(255) NULL DEFAULT NULL,
		ADD app_global_caches VARCHAR(255) NULL DEFAULT NULL,
		ADD app_tab_groups TEXT NULL DEFAULT NULL AFTER app_hide_tab;";
}

# Bug 17345 Removes unrequired task files
$SQL[] = "DELETE FROM task_manager WHERE task_key IN ('doexpiresubs', 'expiresubs') AND task_application != 'subscriptions';";

$SQL[] = "CREATE TABLE spam_service_log (
  id int(10) unsigned NOT NULL auto_increment,
  log_date int(10) unsigned NOT NULL,
  log_code smallint(1) unsigned NOT NULL,
  log_msg varchar(32) NOT NULL,
  email_address varchar(255) NOT NULL,
  ip_address varchar(32) NOT NULL,
  PRIMARY KEY  (id)
);";

