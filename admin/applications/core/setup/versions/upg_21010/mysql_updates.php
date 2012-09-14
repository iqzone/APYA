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


$SQL[] = "DELETE FROM conf_settings WHERE conf_key='csite_skinchange_show'";
$SQL[] = "DELETE FROM conf_settings WHERE conf_key='csite_pm_show'";
$SQL[] = "DELETE FROM conf_settings WHERE conf_key='csite_search_show'";
$SQL[] = "UPDATE conf_settings SET conf_end_group=1 WHERE conf_key='recent_topics_discuss_number'";				

