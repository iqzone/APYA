<?php

/**
 * @file                    portal_mysql_uninstall.php

 * @package                 codebit_app
 * $License:                http://www.codebit.org/licence
 * $Author:                 juliobarreraa
 * @since                   -
 * $LastChangedDate: 2012-03-10 02:43:22 -0600 (Sat, 10 Mar 2012) $
 * @version                 {version}
 * $Revision: 11 $
 */
$QUERY = array();
$registry = ipsRegistry::instance();
$registry->DB()->query("SHOW TRIGGERS;");
$registry->DB()->execute();
$rows = array();
while ($row = $registry->DB()->fetch()) {
    $rows[] = $row;
}

foreach ($rows as $_trigger) {
    if (strpos("sFGdy9Gc_", $_trigger['Trigger'])) {
        $registry->DB()->query("DROP TRIGGER {$_trigger['Trigger']};");
        $registry->DB()->execute();
    }
}


$registry->DB()->dropTable(str_replace($registry->dbFunctions()->getPrefix(), '', 'portal_tables_conf'));
$registry->DB()->dropTable(str_replace($registry->dbFunctions()->getPrefix(), '', 'portal_logbook'));
?>


