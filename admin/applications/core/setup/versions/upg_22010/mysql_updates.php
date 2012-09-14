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

// Fix an SQL error if last_id is null and strict mode is enabled
$SQL[] = "UPDATE forums SET last_id=(CASE WHEN last_id IS NULL THEN 0 ELSE last_id END);";

$SQL[] = "ALTER TABLE forums CHANGE last_title last_title varchar(250) NOT NULL default '';";
$SQL[] = "ALTER TABLE forums CHANGE last_id last_id int(10) NOT NULL default '0';";
$SQL[] = "UPDATE components SET com_title='AddOnChat' WHERE com_section='chatsigma';";

