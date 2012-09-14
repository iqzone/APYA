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

# 3.0.5

$PRE = trim(ipsRegistry::dbFunctions()->getPrefix());
$DB  = ipsRegistry::DB();

if ( ! $DB->checkForField( 'tag_hidden', 'tags_index' ) )
{
	$SQL[] = "ALTER TABLE tags_index ADD tag_hidden INT(1) NOT NULL default '0', ADD INDEX tag_grab (app, type, type_id, type_2, type_id_2, tag_hidden);";
}

$SQL[] = "ALTER TABLE message_topic_user_map ADD INDEX ( map_topic_id );";

if ( ! $DB->checkForField( 'map_last_topic_reply', 'message_topic_user_map' ) )
{
	$SQL[] = "ALTER TABLE message_topic_user_map ADD map_last_topic_reply INT(10) NOT NULL default '0';";
}

$SQL[] = "UPDATE message_topic_user_map m, `{$PRE}message_topics` t SET m.map_last_topic_reply=t.mt_last_post_time WHERE m.map_topic_id=t.mt_id;";

$SQL[] = "ALTER TABLE forum_tracker CHANGE member_id member_id MEDIUMINT( 8 ) NOT NULL";

$SQL[] = "ALTER TABLE topics add index last_x_topics (forum_id,approved,start_date);";
$SQL[] = "ALTER TABLE topics DROP INDEX last_post;";
$SQL[] = "ALTER TABLE topics ADD INDEX last_post (forum_id,pinned,last_post,state);";


$SQL[] = "ALTER TABLE task_manager DROP INDEX task_next_run, ADD INDEX task_next_run ( task_enabled , task_next_run );";
	
$SQL[] = "ALTER TABLE captcha ADD INDEX ( captcha_date );";

$SQL[] = "ALTER TABLE rss_import ADD INDEX ( rss_import_enabled , rss_import_last_import );";

$SQL[] = "ALTER TABLE core_sys_settings_titles ADD INDEX ( conf_title_keyword );";
$SQL[] = "ALTER TABLE core_sys_conf_settings ADD INDEX ( conf_key );";

$SQL[] = "ALTER TABLE polls ADD INDEX ( tid );";


$SQL[] = "CREATE TABLE core_rss_imported (
			rss_guid CHAR(32) NOT NULL,
			rss_foreign_id INT(10) NOT NULL default '0',
			rss_foreign_key VARCHAR(100) NOT NULL default '',
			PRIMARY KEY (rss_guid),
			KEY rss_grabber (rss_guid, rss_foreign_key)
		);";
		
	