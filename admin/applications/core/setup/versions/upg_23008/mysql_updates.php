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


$SQL[] = "ALTER TABLE profile_portal ADD pp_about_me MEDIUMTEXT NULL;";

$SQL[] = "ALTER TABLE moderator_logs CHANGE topic_title topic_title VARCHAR( 255 ) NULL DEFAULT NULL ,
			CHANGE query_string query_string VARCHAR( 255 ) NULL DEFAULT NULL ;";

$SQL[] = "UPDATE cal_events SET event_unix_from= (event_unix_from - (event_tz * 2)) WHERE event_timeset != 0;";
