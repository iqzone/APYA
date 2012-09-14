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

# 3.1.0 Beta 1

$PRE = trim(ipsRegistry::dbFunctions()->getPrefix());
$DB  = ipsRegistry::DB();

if ( ! $DB->checkForField( 'replacement_master_key', 'skin_replacements' ) )
{
	$SQL[] = "ALTER TABLE skin_replacements ADD replacement_master_key VARCHAR(100) NOT NULL DEFAULT '';";
	$SQL[] = "UPDATE skin_replacements SET replacement_master_key='root' WHERE replacement_set_id=0;";
}

