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


$SQL[] = "ALTER TABLE attachments_type ADD index(atype_extension);";
$SQL[] = "DELETE FROM admin_permission_keys WHERE perm_key='content:mem:add';";

$SQL[] = "ALTER TABLE upgrade_history CHANGE upgrade_notes upgrade_notes TEXT NULL;";

$SQL[] = "ALTER TABLE attachments_type ADD INDEX atype ( atype_post , atype_photo );";

$SQL[] = "ALTER TABLE moderator_logs CHANGE query_string query_string TEXT NULL;";

$SQL[] = "ALTER TABLE rss_import CHANGE rss_import_url rss_import_url TEXT NULL;";

