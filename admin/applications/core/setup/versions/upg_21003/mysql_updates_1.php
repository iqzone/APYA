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

$SQL[] = "ALTER TABLE members ADD members_auto_dst TINYINT(1) NOT NULL default '1';";

$SQL[] = "ALTER TABLE members CHANGE new_msg new_msg tinyint(2) default '0',
  				     	CHANGE msg_total msg_total smallint(5) default '0',
  				     	ADD members_cache MEDIUMTEXT NULL,
  						ADD members_disable_pm INT(1) NOT NULL default '0';";
  						
$SQL[] = "ALTER TABLE members ADD members_display_name VARCHAR(255) NOT NULL default '', ADD members_created_remote TINYINT(1) NOT NULL default '0';";

$SQL[] = "ALTER TABLE members ADD INDEX members_display_name( members_display_name );";

$SQL[] = "ALTER TABLE members ADD members_editor_choice VARCHAR(3) NOT NULL default 'std';";

$SQL[] = "ALTER TABLE members ADD members_markers TEXT NULL;";

$SQL[] = "ALTER TABLE message_text ADD msg_ip_address VARCHAR(16) NOT NULL default '0';";

$SQL[] = "UPDATE members SET members_display_name=name;";

