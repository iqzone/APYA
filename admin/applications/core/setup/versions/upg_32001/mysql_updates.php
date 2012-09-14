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

$SQL[] = "ALTER TABLE cache_simple CHANGE cache_data cache_data MEDIUMTEXT NULL DEFAULT NULL;";
$SQL[] = "UPDATE groups SET g_photo_max_vars=REPLACE(g_photo_max_vars, ':150:150', ':200:300') WHERE g_photo_max_vars LIKE '%:150:150';";

if ( ! $DB->checkForTable('skin_generator_sessions') )
{
	/* Skin generator */
	$SQL[] = "CREATE TABLE skin_generator_sessions (
	sg_session_id	VARCHAR(32) NOT NULL DEFAULT '',
	sg_member_id	INT(10) NOT NULL DEFAULT 0,
	sg_skin_set_id	INT(10) NOT NULL DEFAULT 0,
	sg_date_start	INT(10) NOT NULL DEFAULT 0,
	sg_data			MEDIUMTEXT,
	PRIMARY KEY (sg_session_id)
);";
}

$SQL[] = "ALTER TABLE skin_collections ADD set_by_skin_gen INT(1) NOT NULL DEFAULT 0, ADD set_skin_gen_data MEDIUMTEXT;";

$SQL[] = "INSERT INTO core_share_links (share_title, share_key, share_enabled, share_position, share_canonical) VALUES('Google Plus One', 'googleplusone', 1, 2, 1);";
$SQL[] = "DELETE FROM core_share_links WHERE share_key='buzz';";
