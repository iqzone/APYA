<?php
/*
+--------------------------------------------------------------------------
|   IP.Board vVERSION_NUMBER
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

$SQL[] = "ALTER TABLE cal_events ADD event_all_day TINYINT NOT NULL DEFAULT '0';";
$SQL[] = "UPDATE cal_events SET event_all_day=1 WHERE TIME(event_start_date) = '00:00:00';";
