<?php

$PRE = trim(ipsRegistry::dbFunctions()->getPrefix());
$DB  = ipsRegistry::DB();

$SQL[] = "DELETE FROM task_manager WHERE task_key='openidcleanup';";
$SQL[] = "DELETE FROM login_methods WHERE login_folder_name='openid';";

if ( $DB->checkForField( 'identity_url', 'members' ) ) 
{
	$SQL[] = "ALTER TABLE members DROP identity_url;";
}

$SQL[] = "DROP TABLE openid_temp;";



//