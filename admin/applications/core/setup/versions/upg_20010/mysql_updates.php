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

# Fix bug where ICQ alt text missing last single quote

$SQL[] = "DELETE FROM skin_macro WHERE macro_value='PRO_ICQ' and macro_set=1";
$SQL[] = "INSERT INTO skin_macro (macro_value, macro_replace, macro_can_remove, macro_set) VALUES ('PRO_ICQ', '<img src=\'style_images/<#IMG_DIR#>/profile_icq.gif\' border=\'0\'  alt=\'ICQ\' />', 1, 1);";

# Fix bug where "select * from members where temp_ban..." prevents NULL IS NOT NULL confusion

$SQL[] = "ALTER TABLE members change temp_ban temp_ban varchar(100) default '0'";
