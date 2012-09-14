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

$SQL[] = "DELETE FROM admin_permission_keys WHERE perm_main='tools' and perm_child='login';";
$SQL[] = "DELETE FROM conf_settings WHERE conf_key='max_poll_questions' AND conf_group='';";
$SQL[] = "DELETE FROM conf_settings WHERE conf_key IN( 'poll_disable_noreply', 'chat04_who_save', 'chat04_whodat_server_addr' );";
$SQL[] = "UPDATE conf_settings SET conf_group=12 WHERE conf_key='smtp_pass';";
$SQL[] = "UPDATE conf_settings SET conf_group=21 WHERE conf_key='chat04_default_lang';";
$SQL[] = "UPDATE conf_settings SET conf_group=5, conf_position=43 WHERE conf_key='max_h_flash';";
$SQL[] = "DELETE FROM components WHERE com_filename='registration';";
$SQL[] = "UPDATE components SET com_section='chatpara', com_filename='chatpara' where com_filename='chat';";
$SQL[] = "ALTER TABLE member_extra CHANGE icq_number icq_number varchar(40) NOT NULL default '';";

