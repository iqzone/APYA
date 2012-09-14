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

// $SQL[] = "";

$SQL[] = "DELETE FROM conf_settings WHERE conf_key='converge_login_method';";

$SQL[] = "ALTER TABLE member_extra CHANGE avatar_location avatar_location varchar(255) NOT NULL default '';";

$SQL[] = "ALTER TABLE skin_sets CHANGE set_css set_css mediumtext NULL,
	CHANGE set_cache_macro set_cache_macro mediumtext NULL,
	CHANGE set_wrapper set_wrapper mediumtext NULL,
	CHANGE set_cache_css set_cache_css mediumtext NULL,
	CHANGE set_cache_wrapper set_cache_wrapper mediumtext NULL;";
	
	
$SQL[] = "ALTER TABLE forums CHANGE rules_text rules_text TEXT NULL;";


$SQL[] = "ALTER TABLE cal_events ADD event_all_day TINYINT( 1 ) NOT NULL DEFAULT '0';";

