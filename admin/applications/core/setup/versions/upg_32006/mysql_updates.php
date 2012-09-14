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

$SQL[] = "ALTER TABLE rss_import DROP rss_import_charset;";
$SQL[] = "ALTER TABLE sessions ADD session_msg_id INT(10) NOT NULL DEFAULT 0;";

$SQL[] = "CREATE TABLE core_inline_messages (
inline_msg_id		INT(10) NOT NULL auto_increment,
inline_msg_date		INT(10) NOT NULL DEFAULT 0,
inline_msg_content	TEXT,
PRIMARY KEY (inline_msg_id),
KEY inline_msg_date (inline_msg_date)
);";