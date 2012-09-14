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


$TABLE	= 'error_logs';
$SQL[]	= "ALTER TABLE error_logs CHANGE log_ip_address log_ip_address VARCHAR( 46 ) NULL DEFAULT NULL, 
	CHANGE log_date log_date INT( 10 ) NOT NULL DEFAULT '0';";


