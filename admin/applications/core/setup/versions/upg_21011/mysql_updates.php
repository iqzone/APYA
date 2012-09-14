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


$SQL[] = "ALTER TABLE members CHANGE email email varchar( 150 ) NOT NULL default ''";

$SQL[] = "ALTER TABLE subscription_currency CHANGE `subcurrency_exchange` `subcurrency_exchange` DECIMAL( 16, 8 ) DEFAULT '0.00000000' NOT NULL";

