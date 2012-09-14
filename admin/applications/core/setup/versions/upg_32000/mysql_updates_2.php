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

$PRE = trim(ipsRegistry::dbFunctions()->getPrefix());
$DB  = ipsRegistry::DB();

/* Change to potentially large tables */

$SQL[] = "DROP TABLE email_logs;";
	
$SQL[] = "ALTER TABLE profile_ratings CHANGE rating_ip_address rating_ip_address VARCHAR( 46 ) NOT NULL;";

$SQL[] = "ALTER TABLE topic_ratings CHANGE rating_ip_address rating_ip_address VARCHAR( 46 ) NOT NULL;";

$SQL[] = "ALTER TABLE voters CHANGE ip_address ip_address VARCHAR( 46 ) NOT NULL;";

$SQL[] = "ALTER TABLE core_hooks CHANGE hook_installed hook_installed INT( 10 ) NOT NULL DEFAULT '0',
	CHANGE hook_updated hook_updated INT( 10 ) NOT NULL DEFAULT '0',
	ADD hook_global_caches VARCHAR(255) NULL DEFAULT NULL;";

$SQL[] = "ALTER TABLE attachments ADD attach_parent_id INT NOT NULL DEFAULT '0',
	ADD INDEX ( attach_parent_id, attach_rel_module );";
	
$SQL[] = "UPDATE attachments a SET a.attach_parent_id=CASE WHEN (select p.topic_id from {$PRE}posts p WHERE p.pid=a.attach_rel_id) IS NULL THEN 0 ELSE (select p.topic_id from {$PRE}posts p WHERE p.pid=a.attach_rel_id) END WHERE a.attach_rel_module='post';";
