<?php
/*
+--------------------------------------------------------------------------
|   IP.Board v4.2.1
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

$DB  = ipsRegistry::DB();
$PRE = ipsRegistry::dbFunctions()->getPrefix();

$DEAD_TASKS = "gallerystats";

/* Delete member group fields */
$SQL[] = "DELETE FROM task_manager WHERE task_application='gallery' AND task_key IN ('" . implode( "','", explode( ",", $DEAD_TASKS ) ) . "');";

