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

# Final

/* Bug #15747 */
//$SQL[] = "ALTER TABLE topics CHANGE description description varchar(250) default NULL;";

$SQL[] = "delete from core_sys_conf_settings where conf_key='number_format';";
$SQL[] = "delete from core_sys_conf_settings where conf_key='decimal_seperator';";

?>