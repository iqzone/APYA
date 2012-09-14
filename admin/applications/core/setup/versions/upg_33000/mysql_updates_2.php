<?php
/*
+--------------------------------------------------------------------------
|   IP.Board v3.3.3
|   ========================================
|   by Matthew Mecham
|   (c) 2011 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
*/

$PRE = trim(ipsRegistry::dbFunctions()->getPrefix());
$DB  = ipsRegistry::DB();

$SQL[] = "ALTER TABLE topics ADD COLUMN last_real_post INT(10) NOT NULL DEFAULT 0;";
$SQL[] = "ALTER TABLE topics ADD topic_archive_status INT(1) NOT NULL DEFAULT 0;";
$SQL[] = "ALTER TABLE topics ADD KEY topic_archive_status (topic_archive_status, forum_id);";

if ( $DB->checkForField( 'sub_end', 'members' ) ) 
{
	$SQL[] = "ALTER TABLE members DROP sub_end;";
}

if ( $DB->checkForField( 'subs_pkg_chosen', 'members' ) ) 
{
	$SQL[] = "ALTER TABLE members DROP subs_pkg_chosen;";
}

if ( $DB->checkForField( 'members_editor_choice', 'members' ) ) 
{
	$SQL[] = "ALTER TABLE members DROP members_editor_choice;";
}

if ( ! ipsRegistry::DB()->checkForField( 'pp_profile_update', 'profile_portal' ) )
{
	$SQL[] = "ALTER TABLE profile_portal ADD pp_profile_update INT(10) UNSIGNED NOT NULL default '0';";
}


//