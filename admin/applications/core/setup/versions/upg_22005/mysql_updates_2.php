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

$SQL[]="ALTER TABLE posts DROP INDEX topic_id, ADD INDEX topic_id ( topic_id , queued , pid , post_date );";
$SQL[]="ALTER TABLE posts ADD INDEX post_key (post_key), ADD INDEX ip_address (ip_address);";
$SQL[]="ALTER TABLE posts ADD post_edit_reason VARCHAR(255) NOT NULL default '';";
$SQL[]="ALTER TABLE posts CHANGE post post MEDIUMTEXT NULL;";
$SQL[]="ALTER TABLE topics ADD INDEX starter_id (starter_id, forum_id, approved);";

