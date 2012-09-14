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

/* spider logs table */

$TABLE	= 'spider_logs';
$SQL[]	= "ALTER TABLE spider_logs CHANGE ip_address ip_address VARCHAR( 46 ) NOT NULL,
	ADD request_addr TEXT NULL DEFAULT NULL;";


