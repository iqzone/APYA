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

$DB = ipsRegistry::DB();

$SQL[] = "CREATE TABLE mobile_notifications (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  notify_title text NOT NULL,
  notify_date int(10) unsigned NOT NULL,
  member_id mediumint(8) unsigned NOT NULL,
  notify_sent tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (id),
  UNIQUE KEY id (id)
);";

$SQL[] = "ALTER TABLE members ADD ips_mobile_token VARCHAR( 64 ) NULL;";

if ( ! method_exists( $DB, 'checkForIndex' ) )
{
	print "Your ips_kernel/classDbMysql.php is out of date! Please update it from the download zip and refresh this browser window";
	exit();
}

if ( $DB->checkForIndex( 'group_perms', 'rc_classes' ) )
{
	$SQL[] = "ALTER TABLE rc_classes DROP INDEX group_perms;";
}
else if ( $DB->checkForIndex( 'onoff', 'rc_classes' ) )
{
	$SQL[] = "ALTER TABLE rc_classes DROP INDEX onoff;";
}

$SQL[] = "ALTER TABLE rc_classes CHANGE group_can_report group_can_report TEXT NULL DEFAULT NULL";
$SQL[] = "ALTER TABLE rc_classes CHANGE mod_group_perm mod_group_perm TEXT NULL DEFAULT NULL;";

$SQL[] = "ALTER TABLE rc_classes ADD INDEX onoff ( onoff , mod_group_perm ( 255 ) );";

$SQL[] = "ALTER TABLE pfields_content DROP updated;";
