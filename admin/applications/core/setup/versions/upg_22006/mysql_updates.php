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

$SQL[] = "ALTER TABLE custom_bbcode ADD bbcode_switch_option     INT(1) NOT NULL default '0',
                              ADD bbcode_add_into_menu     INT(1) NOT NULL default '0',
                              ADD bbcode_menu_option_text  VARCHAR(200) NOT NULL default '',
                              ADD bbcode_menu_content_text VARCHAR(200) NOT NULL default '';";

$SQL[] = "DELETE FROM conf_settings WHERE conf_key IN ('rte_width', 'rte_pm_width');";
