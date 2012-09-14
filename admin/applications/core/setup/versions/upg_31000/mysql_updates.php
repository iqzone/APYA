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

# 3.1.0 Beta 1

$PRE = trim(ipsRegistry::dbFunctions()->getPrefix());
$DB  = ipsRegistry::DB();

$SQL[] = "ALTER TABLE members CHANGE language language MEDIUMINT( 4 ) NULL DEFAULT NULL;";

$has_right_index = false;
$has_wrong_index = false;
$DB->query( "SHOW INDEX FROM {$PRE}rss_import;" );
while ( $row = $DB->fetch() )
{
	if ( $row['Key_name'] == 'rss_grab' )
	{
		$has_right_index = true;
	}
	if ( $row['Key_name'] == 'rss_import_enabled' )
	{
		$has_wrong_index = true;
	}
}

if ( $has_wrong_index )
{
	$SQL[] = "ALTER TABLE rss_import DROP INDEX rss_import_enabled;";
}
if ( !$has_right_index )
{
	$SQL[] = "ALTER TABLE rss_import ADD INDEX rss_grab ( rss_import_enabled , rss_import_last_import );";
}

$SQL[] = "ALTER TABLE core_item_markers ADD item_is_deleted INT(1) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE core_item_markers DROP INDEX item_member_id;";
$SQL[] = "ALTER TABLE core_item_markers ADD INDEX item_member_id (item_member_id, item_is_deleted);";

$SQL[] = "ALTER TABLE pfields_data ADD pf_search_type varchar(5) NOT NULL default 'loose';";
$SQL[] = "UPDATE pfields_data SET pf_search_type = 'loose';";

$SQL[] = "DELETE FROM core_sys_conf_settings WHERE conf_key='photo_ext';";
$SQL[] = "delete from core_sys_conf_settings where conf_key='seo_r301';";
$SQL[] = "DELETE from core_sys_settings_titles WHERE conf_title_keyword='searchenginespiders';";

$SQL[] = "ALTER TABLE sessions CHANGE login_type login_type tinyint(1) default '0';";
$SQL[] = "ALTER TABLE sessions CHANGE location_1_type location_1_type VARCHAR( 255 ) NOT NULL;";
$SQL[] = "ALTER TABLE sessions CHANGE location_2_type location_2_type VARCHAR( 255 ) NOT NULL;";
$SQL[] = "ALTER TABLE sessions CHANGE location_3_type location_3_type VARCHAR( 255 ) NOT NULL;";

$SQL[] = "ALTER TABLE core_sys_cp_sessions ADD INDEX ( session_running_time );";
$SQL[] = "ALTER TABLE core_sys_cp_sessions ADD INDEX ( session_member_id );";
$SQL[] = "ALTER TABLE upgrade_history ADD INDEX upgrades ( upgrade_app , upgrade_version_id );";
$SQL[] = "ALTER TABLE validating ADD INDEX ( lost_pass );";
$SQL[] = "ALTER TABLE members ADD INDEX ( failed_login_count );";
$SQL[] = "ALTER TABLE validating ADD INDEX ( coppa_user );";
$SQL[] = "ALTER TABLE api_log ADD INDEX ( api_log_date );";
$SQL[] = "ALTER TABLE core_applications ADD INDEX ( app_directory );";
$SQL[] = "ALTER TABLE bulk_mail ADD INDEX ( mail_start );";
$SQL[] = "ALTER TABLE skin_collections ADD INDEX ( set_is_default );";
$SQL[] = "ALTER TABLE members ADD INDEX ( joined );";
$SQL[] = "ALTER TABLE rc_classes ADD INDEX ( onoff , mod_group_perm );";

//-----------------------------------------
// @link	http://community.invisionpower.com/tracker/issue-22903-error-on-upgrade-305-to-310/
//-----------------------------------------

$_tasks	= array();
$DB->build( array( 'select' => 'task_id, task_key, task_application', 'from' => 'task_manager' ) );
$DB->execute();

while( $_r = $DB->fetch() )
{
	$_tasks[ $_r['task_key'] . $_r['task_application'] ]	= $_r['task_id'];
}

$SQL[]	= "DELETE FROM task_manager WHERE task_key='' OR task_key IS NULL;";
$SQL[]	= "DELETE FROM task_manager WHERE task_application='' OR task_application IS NULL;";

if( is_array($_tasks) AND count($_tasks) )
{
	$SQL[]	= "DELETE FROM task_manager WHERE task_id NOT IN(" . implode( ',', $_tasks )  . ");";
}

$SQL[] = "ALTER TABLE task_manager ADD UNIQUE task_key ( task_application , task_key );";
$SQL[] = "ALTER TABLE reputation_index ADD INDEX member_rep ( member_id , rep_rating , rep_date );";
$SQL[] = "ALTER TABLE announcements ADD INDEX ( announce_end );";

if ( $DB->checkForTable( 'cal_events' ) )
{
	$SQL[] = "ALTER TABLE cal_events DROP INDEX daterange;";
	$SQL[] = "ALTER TABLE cal_events ADD INDEX daterange ( event_approved , event_unix_from , event_unix_to );";
	$SQL[] = "ALTER TABLE cal_calendars ADD INDEX ( cal_rss_export );";
}

$SQL[] = "ALTER TABLE core_sys_conf_settings ADD INDEX conf_group ( conf_group , conf_position , conf_title );";
$SQL[] = "ALTER TABLE core_uagent_groups ADD INDEX ( ugroup_title );";
$SQL[] = "ALTER TABLE core_uagents ADD INDEX ordering ( uagent_position , uagent_key );";

$SQL[] = "ALTER TABLE core_sys_conf_settings ADD INDEX ( conf_add_cache );";
$SQL[] = "ALTER TABLE emoticons ADD INDEX ( emo_set );";
$SQL[] = "ALTER TABLE skin_templates ADD INDEX ( template_set_id );";
$SQL[] = "ALTER TABLE skin_css ADD INDEX ( css_set_id );";

$SQL[] = "ALTER TABLE message_topic_user_map DROP INDEX map_user;";
$SQL[] = "ALTER TABLE message_topic_user_map ADD INDEX map_user ( map_user_id , map_folder_id , map_last_topic_reply );";
	
$SQL[] = "ALTER TABLE validating ADD spam_flag TINYINT NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE validating ADD INDEX ( spam_flag );";
$SQL[] = "ALTER TABLE validating ADD INDEX ( member_id );";

$SQL[] = "ALTER TABLE core_sys_lang ADD lang_protected tinyint(1) NOT NULL DEFAULT '0';";

$SQL[] = "CREATE TABLE member_status_actions (
  action_id int(10) NOT NULL AUTO_INCREMENT,
  action_status_id int(10) unsigned NOT NULL DEFAULT '0',
  action_reply_id int(10) unsigned NOT NULL DEFAULT '0',
  action_member_id int(10) unsigned NOT NULL DEFAULT '0',
  action_date int(10) unsigned NOT NULL DEFAULT '0',
  action_key varchar(200) NOT NULL DEFAULT '',
  action_status_owner int(10) unsigned NOT NULL DEFAULT '0',
  action_app varchar(255) NOT NULL DEFAULT 'members',
  action_custom_text text,
  action_custom int(1) NOT NULL DEFAULT '0',
  action_custom_url text,
  PRIMARY KEY (action_id),
  KEY action_status_id (action_status_id),
  KEY action_member_id ( action_member_id , action_date ),
  KEY action_date (action_date),
  KEY action_custom ( action_custom , action_date )
);";

$SQL[] = "CREATE TABLE member_status_replies (
  reply_id int(10) NOT NULL AUTO_INCREMENT,
  reply_status_id int(10) unsigned NOT NULL DEFAULT '0',
  reply_member_id int(10) unsigned NOT NULL DEFAULT '0',
  reply_date int(10) unsigned NOT NULL DEFAULT '0',
  reply_content text,
  PRIMARY KEY (reply_id),
  KEY reply_status_id (reply_status_id),
  KEY reply_member_id (reply_member_id),
  KEY reply_status_count (reply_status_id,reply_member_id),
  KEY reply_date (reply_date)
);";

$SQL[] = "CREATE TABLE member_status_updates (
  status_id int(10) NOT NULL AUTO_INCREMENT,
  status_member_id int(10) unsigned NOT NULL DEFAULT '0',
  status_date int(10) unsigned NOT NULL DEFAULT '0',
  status_content text,
  status_replies int(10) unsigned NOT NULL DEFAULT '0',
  status_last_ids text,
  status_is_latest int(1) NOT NULL DEFAULT '0',
  status_is_locked int(1) NOT NULL DEFAULT '0',
  status_hash varchar(32) NOT NULL DEFAULT '',
  status_imported int(1) NOT NULL DEFAULT '0',
  status_creator varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (status_id),
  KEY status_member_id (status_member_id),
  KEY status_date (status_date),
  KEY status_is_latest ( status_is_latest , status_date ),
  KEY s_hash (status_member_id,status_hash,status_imported)
);";
 
$SQL[] = "CREATE TABLE inline_notifications (
notify_id INT NOT NULL AUTO_INCREMENT ,
notify_to_id INT NOT NULL DEFAULT '0',
notify_sent INT(10) NOT NULL DEFAULT '0',
notify_read INT(10) NOT NULL DEFAULT '0',
notify_title text NULL ,
notify_text TEXT NULL DEFAULT NULL ,
notify_from_id INT NOT NULL DEFAULT '0',
notify_type_key VARCHAR( 255 ) NULL DEFAULT NULL ,
notify_url text null,
PRIMARY KEY (notify_id),
KEY notify_to_id ( notify_to_id , notify_sent ),
KEY grabber (notify_to_id, notify_read, notify_sent)
);";

$SQL[] = "CREATE TABLE core_soft_delete_log (
  sdl_id int(10) NOT NULL AUTO_INCREMENT,
  sdl_obj_id int(10) NOT NULL DEFAULT '0',
  sdl_obj_key varchar(20) NOT NULL DEFAULT '',
  sdl_obj_member_id int(10) NOT NULL DEFAULT '0',
  sdl_obj_date int(10) NOT NULL DEFAULT '0',
  sdl_obj_reason text,
  sdl_locked int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (sdl_id),
  KEY look_up (sdl_obj_id,sdl_obj_key)
);";

$SQL[] = "CREATE TABLE twitter_connect (
	t_key VARCHAR(32) NOT NULL default '',
	t_token VARCHAR(255) NOT NULL default '',
	t_secret VARCHAR(255) NOT NULL default '',
	t_time INT(10) NOT NULL default '0'
);";

$SQL[] = "CREATE TABLE core_share_links (
	share_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	share_title VARCHAR(255) NOT NULL DEFAULT '',
	share_url TEXT,
	share_key VARCHAR(50) NOT NULL DEFAULT '',
	share_enabled INT(1) NOT NULL DEFAULT '0',
	share_position INT(3) NOT NULL DEFAULT '0',
	share_markup TEXT,
	share_canonical INT(1) NOT NULL DEFAULT '1',
	PRIMARY KEY share_id (share_id)
);";

$SQL[] = "CREATE TABLE core_share_links_log (
	log_id				  INT(10) NOT NULL auto_increment,
	log_date			  INT(10) NOT NULL default '0',
	log_member_id		  INT(10) NOT NULL default '0',
	log_url				  TEXT,
	log_title			  TEXT,
	log_share_key		  VARCHAR(50) NOT NULL default '',
	log_data_app		  VARCHAR(50) NOT NULL default '',
	log_data_type		  VARCHAR(50) NOT NULL default '',
	log_data_primary_id   INT(10) NOT NULL default '0',
	log_data_secondary_id INT(10) NOT NULL default '0',
	log_ip_address		  VARCHAR(16) NOT NULL DEFAULT '',
	PRIMARY KEY log_id (log_id),
	KEY findstuff (log_data_app, log_data_type, log_data_primary_id),
	KEY log_date (log_date),
	KEY log_member_id (log_member_id),
	KEY log_share_key (log_share_key),
	KEY log_ip_address (log_ip_address)
);";

$SQL[] = "CREATE TABLE core_share_links_caches (
	cache_id INT(10) NOT NULL auto_increment,
	cache_key VARCHAR(255) NOT NULL default '',
	cache_data MEDIUMTEXT,
	cache_date INT(10) NOT NULL default '0',
	PRIMARY KEY cache_id( cache_id )
);";

$SQL[] = "CREATE TABLE core_incoming_emails (
	rule_id INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	rule_criteria_field VARCHAR( 4 ) NOT NULL ,
	rule_criteria_type VARCHAR( 4 ) NOT NULL ,
	rule_criteria_value TEXT NOT NULL ,
	rule_app VARCHAR( 255 ) NOT NULL ,
	rule_added_by MEDIUMINT( 8 ) NOT NULL ,
	rule_added_date INT( 10 ) NOT NULL
);";

$SQL[] = "CREATE TABLE search_sessions (
	session_id VARCHAR(32) NOT NULL DEFAULT '',
	session_created INT(10) NOT NULL DEFAULT '0',
	session_updated  INT(10) NOT NULL DEFAULT '0',
	session_member_id INT(10) NOT NULL DEFAULT '0',
	session_data MEDIUMTEXT,
	KEY session_updated (session_updated),
	PRIMARY KEY (session_id)
);";

$SQL[] = "CREATE TABLE skin_merge_session (
	merge_id INT(10) NOT NULL auto_increment,
	merge_date INT(10) NOT NULL DEFAULT '0',
	merge_set_id INT(10) NOT NULL DEFAULT '0',
	merge_master_key VARCHAR(200) NOT NULL DEFAULT '',
	merge_old_version VARCHAR(200) NOT NULL DEFAULT '',
	merge_new_version VARCHAR(200) NOT NULL DEFAULT '',
	merge_templates_togo INT(10) NOT NULL DEFAULT '0',
	merge_css_togo INT(10) NOT NULL DEFAULT '0',
	merge_templates_done INT(10) NOT NULL DEFAULT '0',
	merge_css_done INT(10) NOT NULL DEFAULT '0',
	merge_m_templates_togo INT(10) NOT NULL DEFAULT '0',
	merge_m_css_togo INT(10) NOT NULL DEFAULT '0',
	merge_m_templates_done INT(10) NOT NULL DEFAULT '0',
	merge_m_css_done INT(10) NOT NULL DEFAULT '0',
	merge_diff_done INT(1) NOT NULL DEFAULT '0',
	PRIMARY KEY (merge_id)
);";

$SQL[] = "CREATE TABLE skin_merge_changes (
	change_id INT(10) NOT NULL auto_increment,
	change_key VARCHAR(255) NOT NULL DEFAULT '',
	change_session_id INT(10) NOT NULL DEFAULT '0',
	change_updated INT(10) NOT NULL DEFAULT '0',
	change_data_group VARCHAR(255) NOT NULL DEFAULT '',
	change_data_title VARCHAR(255) NOT NULL DEFAULT '',
	change_data_content MEDIUMTEXT,
	change_data_type VARCHAR(10) NOT NULL DEFAULT 'template',
	change_is_new INT(1) NOT NULL DEFAULT '0',
	change_is_diff INT(1) NOT NULL DEFAULT '0',
	change_can_merge INT(1) NOT NULL DEFAULT '0',
	change_merge_content MEDIUMTEXT,
	change_is_conflict INT(1) NOT NULL DEFAULT '0',
	change_final_content MEDIUMTEXT,
	change_changes_applied INT(1) NOT NULL DEFAULT '0',
	change_original_content MEDIUMTEXT,
	PRIMARY KEY (change_id),
	KEY (change_key, change_data_type)
);";

$SQL[] = "CREATE TABLE facebook_oauth_temp (
	f_key VARCHAR(32) NOT NULL default '',
	f_token VARCHAR(255) NOT NULL default '',
	f_time INT(10) NOT NULL default '0'
);";

$SQL[] = "INSERT INTO member_status_updates (status_member_id, status_date, status_content, status_replies, status_is_latest ) SELECT pp_member_id, pp_status_update, pp_status, 0, 1 FROM `{$PRE}profile_portal` WHERE LENGTH(pp_status) > 0;";
	

$SQL[] = "ALTER TABLE members ADD twitter_id VARCHAR(255) NOT NULL DEFAULT '';";
$SQL[] = "ALTER TABLE members ADD twitter_token  VARCHAR(255) NOT NULL DEFAULT '';";
$SQL[] = "ALTER TABLE members ADD twitter_secret VARCHAR(255) NOT NULL DEFAULT '';";
$SQL[] = "ALTER TABLE members ADD notification_cnt MEDIUMINT NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE members ADD tc_lastsync INT(10) NOT NULL default '0';";
$SQL[] = "ALTER TABLE members ADD fb_session VARCHAR(200) NOT NULL default '';";
$SQL[] = "ALTER TABLE members DROP fb_emailallow;";
$SQL[] = "ALTER TABLE members ADD fb_token TEXT NULL;";

$SQL[] = "ALTER TABLE members ADD INDEX fb_uid (fb_uid);";
$SQL[] = "ALTER TABLE members ADD INDEX twitter_id (twitter_id);";


$SQL[] = "ALTER TABLE profile_portal ADD tc_last_sid_import BIGINT(20) UNSIGNED NOT NULL default '0';";
$SQL[] = "ALTER TABLE profile_portal ADD tc_photo text;";
$SQL[] = "ALTER TABLE profile_portal ADD tc_bwoptions  int(10) unsigned NOT NULL default '0';";
$SQL[] = "ALTER TABLE profile_portal ADD pp_customization mediumtext;";

$SQL[] = "ALTER TABLE groups ADD g_max_notifications MEDIUMINT NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE groups ADD g_max_bgimg_upload INT(10) NOT NULL default '0';";

$SQL[] = "ALTER TABLE rc_classes ADD app VARCHAR( 32 ) NOT NULL;";
$SQL[] = "ALTER TABLE forums ADD disable_sharelinks INT(1) NOT NULL default '0';";

$SQL[] = "ALTER TABLE topics ADD topic_deleted_posts INT(10) NOT NULL default '0';";
$SQL[] = "ALTER TABLE forums ADD deleted_posts INT(10) NOT NULL default '0';";
$SQL[] = "ALTER TABLE forums ADD deleted_topics INT(10) NOT NULL default '0';";

$SQL[] = "ALTER TABLE forums ADD rules_raw_html tinyint(1) NOT NULL default '0';";

$SQL[] = "UPDATE rc_classes SET app='core' WHERE my_class='default';";
$SQL[] = "UPDATE rc_classes SET app='forums' WHERE my_class='post';";
$SQL[] = "UPDATE rc_classes SET app='blog' WHERE my_class='blog';";
$SQL[] = "UPDATE rc_classes SET app='gallery' WHERE my_class='gallery';";
$SQL[] = "UPDATE rc_classes SET app='downloads' WHERE my_class='downloads';";
$SQL[] = "UPDATE rc_classes SET app='members' WHERE my_class='messages';";
$SQL[] = "UPDATE rc_classes SET app='members' WHERE my_class='profiles';";
$SQL[] = "UPDATE rc_classes SET app='calendar' WHERE my_class='calendar';";


$SQL[] = "INSERT INTO core_share_links VALUES(1, 'Twitter', 'http://twitter.com/home?status={title}%20{url}', 'twitter', 1, 1, '',1);";
$SQL[] = "INSERT INTO core_share_links VALUES(2, 'Facebook', 'http://www.facebook.com/share.php?u={url}', 'facebook', 1, 2, '',1);";
$SQL[] = "INSERT INTO core_share_links VALUES(3, 'Digg', 'http://digg.com/submit?phase=2&amp;url={url}&amp;title={title}', 'digg', 1, 3, '',1);";
$SQL[] = "INSERT INTO core_share_links VALUES(4, 'Del.icio.us', 'http://del.icio.us/post?v=2&amp;url={url}&amp;title={title}', 'delicious', 1, 4, '',1);";
$SQL[] = "INSERT INTO core_share_links VALUES(5, 'Reddit', 'http://reddit.com/submit?url={url}&amp;title={title}', 'reddit', 1, 5, '',1);";
$SQL[] = "INSERT INTO core_share_links VALUES(6, 'StumbleUpon', 'http://www.stumbleupon.com/submit?url={url}&title={title}', 'stumble', 1, 6, '',1);";
$SQL[] = "INSERT INTO core_share_links VALUES(8, 'Email', '', 'email', 1, 7, '',1);";
$SQL[] = "INSERT INTO core_share_links VALUES(9, 'Buzz', '', 'buzz', 1, 3, '', 1);";
$SQL[] = "INSERT INTO core_share_links VALUES(10, 'Print', '', 'print', 1, 10, '', 0);";
$SQL[] = "INSERT INTO core_share_links VALUES(11, 'Download', '', 'download', 1, 11, '', 0);";

/* Grab stuff */
$DB->build( array( 'select' => '*',
				   'from'   => 'skin_collections' ) );
$DB->execute();

$skins = array();

while( $row = $DB->fetch() )
{
	if ( in_array( $row['set_key'], array( 'default', 'lofi', 'xmlskin' ) ) )
	{
		$skins[ $row['set_key'] ] = $row['set_id'];
	}
}

$ids = implode( ',', array_values( $skins ) );

/* Do we have a master skin? */
if ( ! $skins['default'] )
{
	/* Insert it */
	$DB->insert( 'skin_collections', array( 'set_name'      	 => 'IP.Board',
											'set_key'       	 => 'default',
											'set_parent_id'		 => 0,
											'set_parent_array'   => 'a:0:{}',
											'set_child_array'    => 'a:0:{}',
											'set_permissions'    => '*',
											'set_is_default'     => 0,
											'set_author_name'	 => "Invision Power Services, Inc",
										    'set_author_url'	 => 'http://www.invisionpower.com',
										    'set_image_dir'		 => 'master',
										    'set_emo_dir'		 => 'default',
										    'set_hide_from_list' => 1,
										    'set_css_groups'	 => '<![CDATA[a:20:{s:6:"1.5832";a:2:{s:9:"css_group";s:15:"calendar_select";s:12:"css_position";s:1:"1";}s:6:"1.5846";a:2:{s:9:"css_group";s:6:"ipblog";s:12:"css_position";s:1:"1";}s:6:"1.5833";a:2:{s:9:"css_group";s:12:"ipb_calendar";s:12:"css_position";s:1:"1";}s:6:"1.5834";a:2:{s:9:"css_group";s:10:"ipb_common";s:12:"css_position";s:1:"1";}s:6:"1.5835";a:2:{s:9:"css_group";s:10:"ipb_editor";s:12:"css_position";s:1:"1";}s:6:"0.5836";a:2:{s:9:"css_group";s:8:"ipb_help";s:12:"css_position";s:1:"0";}s:6:"0.5837";a:2:{s:9:"css_group";s:6:"ipb_ie";s:12:"css_position";s:1:"0";}s:6:"1.5838";a:2:{s:9:"css_group";s:18:"ipb_login_register";s:12:"css_position";s:1:"1";}s:6:"1.5839";a:2:{s:9:"css_group";s:13:"ipb_messenger";s:12:"css_position";s:1:"1";}s:6:"1.5840";a:2:{s:9:"css_group";s:9:"ipb_mlist";s:12:"css_position";s:1:"1";}s:6:"1.5841";a:2:{s:9:"css_group";s:9:"ipb_print";s:12:"css_position";s:1:"1";}s:6:"1.5842";a:2:{s:9:"css_group";s:11:"ipb_profile";s:12:"css_position";s:1:"1";}s:6:"2.5843";a:2:{s:9:"css_group";s:10:"ipb_search";s:12:"css_position";s:1:"2";}s:6:"1.5844";a:2:{s:9:"css_group";s:10:"ipb_styles";s:12:"css_position";s:1:"1";}s:6:"1.5845";a:2:{s:9:"css_group";s:7:"ipb_ucp";s:12:"css_position";s:1:"1";}s:6:"1.5847";a:2:{s:9:"css_group";s:6:"ipchat";s:12:"css_position";s:1:"1";}s:6:"1.5848";a:2:{s:9:"css_group";s:9:"ipcontent";s:12:"css_position";s:1:"1";}s:6:"1.5849";a:2:{s:9:"css_group";s:11:"ipdownloads";s:12:"css_position";s:1:"1";}s:6:"1.5850";a:2:{s:9:"css_group";s:9:"ipgallery";s:12:"css_position";s:1:"1";}s:6:"1.5851";a:2:{s:9:"css_group";s:19:"ipgallery_slideshow";s:12:"css_position";s:1:"1";}}]]>',
										    'set_output_format'  => 'html' ) );
}

/* Do we have a lofi skin? */
if ( ! $skins['lofi'] )
{
	/* Insert it */
	$DB->insert( 'skin_collections', array( 'set_name'      	 => 'IP.Board Lofi',
											'set_key'       	 => 'lofi',
											'set_parent_id'		 => 0,
											'set_parent_array'   => 'a:0:{}',
											'set_child_array'    => 'a:0:{}',
											'set_permissions'    => '*',
											'set_is_default'     => 0,
											'set_author_name'	 => "Invision Power Services, Inc",
										    'set_author_url'	 => 'http://www.invisionpower.com',
										    'set_image_dir'		 => 'master',
										    'set_emo_dir'		 => 'default',
										    'set_hide_from_list' => 1,
										    'set_css_groups'	 => '<![CDATA[a:20:{s:6:"1.5832";a:2:{s:9:"css_group";s:15:"calendar_select";s:12:"css_position";s:1:"1";}s:6:"1.5846";a:2:{s:9:"css_group";s:6:"ipblog";s:12:"css_position";s:1:"1";}s:6:"1.5833";a:2:{s:9:"css_group";s:12:"ipb_calendar";s:12:"css_position";s:1:"1";}s:6:"1.5834";a:2:{s:9:"css_group";s:10:"ipb_common";s:12:"css_position";s:1:"1";}s:6:"1.5835";a:2:{s:9:"css_group";s:10:"ipb_editor";s:12:"css_position";s:1:"1";}s:6:"0.5836";a:2:{s:9:"css_group";s:8:"ipb_help";s:12:"css_position";s:1:"0";}s:6:"0.5837";a:2:{s:9:"css_group";s:6:"ipb_ie";s:12:"css_position";s:1:"0";}s:6:"1.5838";a:2:{s:9:"css_group";s:18:"ipb_login_register";s:12:"css_position";s:1:"1";}s:6:"1.5839";a:2:{s:9:"css_group";s:13:"ipb_messenger";s:12:"css_position";s:1:"1";}s:6:"1.5840";a:2:{s:9:"css_group";s:9:"ipb_mlist";s:12:"css_position";s:1:"1";}s:6:"1.5841";a:2:{s:9:"css_group";s:9:"ipb_print";s:12:"css_position";s:1:"1";}s:6:"1.5842";a:2:{s:9:"css_group";s:11:"ipb_profile";s:12:"css_position";s:1:"1";}s:6:"2.5843";a:2:{s:9:"css_group";s:10:"ipb_search";s:12:"css_position";s:1:"2";}s:6:"1.5844";a:2:{s:9:"css_group";s:10:"ipb_styles";s:12:"css_position";s:1:"1";}s:6:"1.5845";a:2:{s:9:"css_group";s:7:"ipb_ucp";s:12:"css_position";s:1:"1";}s:6:"1.5847";a:2:{s:9:"css_group";s:6:"ipchat";s:12:"css_position";s:1:"1";}s:6:"1.5848";a:2:{s:9:"css_group";s:9:"ipcontent";s:12:"css_position";s:1:"1";}s:6:"1.5849";a:2:{s:9:"css_group";s:11:"ipdownloads";s:12:"css_position";s:1:"1";}s:6:"1.5850";a:2:{s:9:"css_group";s:9:"ipgallery";s:12:"css_position";s:1:"1";}s:6:"1.5851";a:2:{s:9:"css_group";s:19:"ipgallery_slideshow";s:12:"css_position";s:1:"1";}}]]>',
										    'set_output_format'  => 'html' ) );
}

/* Make sure we have a HTML default... */
$skin = $DB->buildAndFetch( array( 'select' => 'set_id',
								   'from'   => 'skin_collections',
								   'where'  => 'set_output_format=\'html\' AND set_is_default=1' ) );
								   
if ( ! $skin['set_id'] )
{
	$DB->update( 'skin_collections', array( 'set_is_default' => 1, 'set_hide_from_list' => 0 ), "set_key='default'" );
}

if ( ! $DB->checkForField( 'bbcode_custom_regex', 'custom_bbcode' ) )
{
	$SQL[] = "ALTER TABLE custom_bbcode ADD bbcode_custom_regex TEXT NULL DEFAULT NULL;";
}

$SQL[] = "ALTER TABLE skin_templates ADD template_master_key VARCHAR(100) NOT NULL DEFAULT '';";
$SQL[] = "ALTER TABLE skin_templates ADD INDEX template_master_key (template_master_key);";

$SQL[] = "ALTER TABLE skin_css ADD css_master_key VARCHAR(100) NOT NULL DEFAULT '';";

$SQL[] = "ALTER TABLE skin_collections ADD set_master_key VARCHAR(100) NOT NULL DEFAULT '';";

# will need to sort children out too
$uagents = 'a:2:{s:6:"groups";a:0:{}s:7:"uagents";a:10:{s:10:"blackberry";s:0:"";s:6:"iphone";s:0:"";s:9:"ipodtouch";s:0:"";s:12:"sonyericsson";s:0:"";s:5:"nokia";s:0:"";s:8:"motorola";s:0:"";s:7:"samsung";s:0:"";s:3:"htc";s:0:"";s:2:"lg";s:0:"";s:4:"palm";s:0:"";}}';
$SQL[] = "UPDATE skin_collections SET set_master_key='root' WHERE set_key='default';";
$SQL[] = "UPDATE skin_collections SET set_name='IP.Board Mobile', set_master_key='mobile', set_key='mobile', set_image_dir='mobile', set_locked_uagent='{$uagents}' WHERE set_key='lofi';";
$SQL[] = "UPDATE skin_collections SET set_master_key='xmlskin' WHERE set_key='xmlskin';";

$SQL[] = "UPDATE skin_templates SET template_master_key='root' WHERE template_set_id=0;";

$SQL[] = "UPDATE skin_css SET css_master_key='root' WHERE css_set_id=0;";

if ( trim($ids) )
{
	$SQL[] = "DELETE FROM skin_templates WHERE template_set_id IN (" . $ids . ");";
	$SQL[] = "DELETE FROM skin_css WHERE css_set_id IN (" . $ids . ");";
}

$SQL[] = "ALTER TABLE  skin_collections ADD set_order INT( 10 ) NOT NULL;";
$SQL[] = "ALTER TABLE  skin_collections ADD INDEX ( set_order );";

$SQL[] = "ALTER TABLE skin_replacements ADD replacement_master_key VARCHAR(100) NOT NULL DEFAULT '';";
$SQL[] = "UPDATE skin_replacements SET replacement_master_key='root' WHERE replacement_set_id=0;";


# Optimizations
$SQL[] = "ALTER TABLE forum_tracker DROP INDEX member_id;";
$SQL[] = "ALTER TABLE forum_tracker ADD INDEX member_id ( member_id , last_sent );";

$SQL[] = "ALTER TABLE tracker DROP INDEX tm_id;";
$SQL[] = "ALTER TABLE tracker ADD INDEX tm_id ( member_id , topic_id , last_sent );";

$SQL[] = "ALTER TABLE forum_tracker ADD INDEX ( forum_track_type );";
$SQL[] = "ALTER TABLE tracker ADD INDEX ( topic_track_type );";

$SQL[] = "ALTER TABLE skin_templates ADD INDEX ( template_name(100) , template_group(100) );";

$SQL[]	= "ALTER TABLE members ADD INDEX ( email );";

