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


$TABLE	= 'moderator_logs';
$SQL[]	= "ALTER TABLE moderator_logs CHANGE ip_address ip_address VARCHAR( 46 ) NOT NULL DEFAULT '0', 
	CHANGE member_name member_name VARCHAR( 255 ) NOT NULL;";


