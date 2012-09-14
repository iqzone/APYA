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

$PRE = trim(ipsRegistry::dbFunctions()->getPrefix());
$DB  = ipsRegistry::DB();

/* Member and profile_portal table changes */

$SQL[] = "ALTER TABLE members CHANGE ip_address ip_address VARCHAR( 46 ) NOT NULL,
	DROP hide_email,
	DROP email_full,
	DROP view_prefs,
	DROP view_avs,
	DROP INDEX mgroup,
	ADD INDEX mgroup ( member_group_id , member_id );";


$SQL[] = "UPDATE profile_portal SET pp_setting_count_visitors=1 WHERE pp_setting_count_visitors > 1;";

$SQL[] = "ALTER TABLE profile_portal CHANGE tc_last_sid_import tc_last_sid_import VARCHAR(50) NULL DEFAULT '0',
	ADD pp_gravatar VARCHAR(255) NOT NULL DEFAULT '' AFTER pp_reputation_points,
	ADD pp_photo_type VARCHAR(20) NOT NULL DEFAULT '' AFTER pp_gravatar,
	CHANGE pp_setting_count_visitors pp_setting_count_visitors TINYINT(1) NOT NULL DEFAULT '0';";

$SQL[] = "TRUNCATE TABLE content_cache_sigs;";
