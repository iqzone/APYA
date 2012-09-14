<?php

$PRE = trim(ipsRegistry::dbFunctions()->getPrefix());
$DB  = ipsRegistry::DB();

$SQL[] = "ALTER TABLE dnames_change ADD dname_discount TINYINT(1) NOT NULL DEFAULT 0;";

/* Missed from install SQL */
if ( ! $DB->checkForField( 'unacknowledged_warnings', 'members' ) ) 
{
	$SQL[] = "ALTER TABLE members ADD unacknowledged_warnings TINYINT(1) DEFAULT NULL;";
}

//