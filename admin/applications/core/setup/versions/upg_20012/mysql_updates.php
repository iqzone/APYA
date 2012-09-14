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
# Fix bug where unread PM count not incremented

$SQL[] = "ALTER TABLE members change msg_total msg_total smallint(5) default '0';";
$SQL[] = "ALTER TABLE members change new_msg new_msg smallint(5) default '0';";
$SQL[] = "UPDATE members SET new_msg=0;";


# Add has_blog

$DB  = ipsRegistry::DB();

if( !$DB->checkForField( 'has_blog', 'members' ) )
{
	$SQL[] = "ALTER TABLE members add has_blog TINYINT(1) NOT NULL default '0';";
}

# Efficiency

$SQL[] = "ALTER TABLE members_converge ADD INDEX converge_email(converge_email);";
$SQL[] = "ALTER TABLE polls ADD INDEX tid(tid);";

