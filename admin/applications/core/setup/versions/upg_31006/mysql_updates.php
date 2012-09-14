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

$DB = ipsRegistry::DB();

$SQL[] = "ALTER TABLE pfields_data ADD pf_filtering TINYINT( 1 ) NOT NULL DEFAULT '0'";

if ( $DB->checkForTable('cal_events') )
{
	$SQL[] = "ALTER TABLE cal_events CHANGE event_tz event_tz VARCHAR( 4 ) NOT NULL DEFAULT '0'";
}

/* @link http://community.invisionpower.com/tracker/issue-32451-calendar-upgrade-issue-warning-and-then-driver-error
	This may cause two entries for calendar for this version number, but that doesn't matter */
$SQL[] = "INSERT INTO upgrade_history (upgrade_version_id, upgrade_version_human, upgrade_date, upgrade_mid, upgrade_app) VALUES (31006, '3.1.3', " . time() . ", 1, 'calendar');";

$SQL[] = "ALTER TABLE mobile_notifications DROP INDEX id;";
$SQL[] = "ALTER TABLE profile_friends DROP INDEX friends_member_id;";
$SQL[] = "ALTER TABLE member_status_updates DROP INDEX status_member_id;";
$SQL[] = "ALTER TABLE profile_friends_flood DROP INDEX friends_member_id;";
$SQL[] = "ALTER TABLE moderator_logs CHANGE topic_title topic_title TEXT NULL DEFAULT NULL;";
$SQL[] = "ALTER TABLE moderator_logs CHANGE query_string query_string TEXT NULL DEFAULT NULL;";
$SQL[] = "ALTER TABLE moderator_logs CHANGE http_referer http_referer TEXT NULL DEFAULT NULL;";
$SQL[] = "ALTER TABLE moderator_logs CHANGE action action TEXT NULL DEFAULT NULL;";
$SQL[] = "ALTER TABLE profile_portal_views ADD INDEX ( views_member_id );";
$SQL[] = "ALTER TABLE topic_views ADD INDEX ( views_tid );";
$SQL[] = "ALTER TABLE attachments DROP INDEX attach_where;";
$SQL[] = "DELETE FROM core_sys_conf_settings WHERE conf_key='acp_tutorial_mode';";
$SQL[] = "UPDATE skin_templates SET template_data='\$required_output=\'\',\$optional_output=\'\',\$day=\'\',\$mon=\'\',\$year=\'\'' WHERE template_name='membersProfileForm';";

//-----------------------------------------
// Remove unused tables
//-----------------------------------------

if( $DB->checkForTable('facebook_oauth_temp') )
{
	$SQL[] = "DROP TABLE facebook_oauth_temp;";
}

if( $DB->checkForTable('search_index') )
{
	$SQL[] = "DROP TABLE search_index;";
}

// Tracker is using this apparently
//if( $DB->checkForTable('search_results') )
//{
//	$SQL[] = "DROP TABLE search_results;";
//}

if( $DB->checkForTable('templates_diff_import') )
{
	$SQL[] = "DROP TABLE templates_diff_import;";
}

if( $DB->checkForTable('template_diff_changes') )
{
	$SQL[] = "DROP TABLE template_diff_changes;";
}

if( $DB->checkForTable('template_diff_session') )
{
	$SQL[] = "DROP TABLE template_diff_session;";
}

//-----------------------------------------
// And unused columns *sigh*
// Fun fact:
// Matt said: YES - and all below apart from that massive ALTER list and Im not touching that. :p so there
//-----------------------------------------

if( $DB->checkForField( 'attach_approved', 'attachments' ) )
{
	$SQL[] = "ALTER TABLE attachments DROP attach_approved;";
}

if( $DB->checkForField( 'attach_temp', 'attachments' ) )
{
	$SQL[] = "ALTER TABLE attachments DROP attach_temp;";
}

if( $DB->checkForField( 'mail_honor', 'bulk_mail' ) )
{
	$SQL[] = "ALTER TABLE bulk_mail DROP mail_honor;";
}

//if( $DB->checkForField( 'cs_extra', 'cache_store' ) )
//{
//	$SQL[] = "ALTER TABLE cache_store DROP cs_extra;";
//}

if( $DB->checkForField( 'rss_foreign_id', 'core_rss_imported' ) )
{
	$SQL[] = "ALTER TABLE core_rss_imported DROP rss_foreign_id;";
}

if( $DB->checkForField( 'share_url', 'core_share_links' ) )
{
	$SQL[] = "ALTER TABLE core_share_links DROP share_url;";
}

if( $DB->checkForField( 'share_markup', 'core_share_links' ) )
{
	$SQL[] = "ALTER TABLE core_share_links DROP share_markup;";
}

if( $DB->checkForField( 'conf_end_group', 'core_sys_conf_settings' ) )
{
	$SQL[] = "ALTER TABLE core_sys_conf_settings DROP conf_end_group;";
}

if( $DB->checkForField( 'lang_currency_symbol', 'core_sys_lang' ) )
{
	$SQL[] = "ALTER TABLE core_sys_lang DROP lang_currency_symbol;";
}

if( $DB->checkForField( 'lang_decimal', 'core_sys_lang' ) )
{
	$SQL[] = "ALTER TABLE core_sys_lang DROP lang_decimal;";
}

if( $DB->checkForField( 'lang_comma', 'core_sys_lang' ) )
{
	$SQL[] = "ALTER TABLE core_sys_lang DROP lang_comma;";
}

if( $DB->checkForField( 'sys_login_skin', 'core_sys_login' ) )
{
	$SQL[] = "ALTER TABLE core_sys_login DROP sys_login_skin;";
}

if( $DB->checkForField( 'sys_login_language', 'core_sys_login' ) )
{
	$SQL[] = "ALTER TABLE core_sys_login DROP sys_login_language;";
}

if( $DB->checkForField( 'sys_login_last_visit', 'core_sys_login' ) )
{
	$SQL[] = "ALTER TABLE core_sys_login DROP sys_login_last_visit;";
}

if( $DB->checkForField( 'conf_title_module', 'core_sys_settings_titles' ) )
{
	$SQL[] = "ALTER TABLE core_sys_settings_titles DROP conf_title_module;";
}

if( $DB->checkForField( 'bbcode_parse', 'custom_bbcode' ) )
{
	$SQL[] = "ALTER TABLE custom_bbcode DROP bbcode_parse;";
}

if( $DB->checkForField( 'topic_id', 'email_logs' ) )
{
	$SQL[] = "ALTER TABLE email_logs DROP topic_id;";
}

if( $DB->checkForField( 'redirect_loc', 'forums' ) )
{
	$SQL[] = "ALTER TABLE forums DROP redirect_loc;";
}

if( $DB->checkForField( 'topic_mm_id', 'forums' ) )
{
	$SQL[] = "ALTER TABLE forums DROP topic_mm_id;";
}

if( $DB->checkForField( 'permission_array', 'forums' ) )
{
	$SQL[] = "ALTER TABLE forums DROP permission_array;";
}

if( $DB->checkForField( 'g_invite_friend', 'groups' ) )
{
	$SQL[] = "ALTER TABLE groups DROP g_invite_friend;";
}

if( $DB->checkForField( 'g_can_remove', 'groups' ) )
{
	$SQL[] = "ALTER TABLE groups DROP g_can_remove;";
}

if( $DB->checkForField( 'login_date', 'login_methods' ) )
{
	$SQL[] = "ALTER TABLE login_methods DROP login_date;";
}

if( $DB->checkForField( 'mail_type', 'mail_queue' ) )
{
	$SQL[] = "ALTER TABLE mail_queue DROP mail_type;";
}

if( $DB->checkForField( 'edit_user', 'moderators' ) )
{
	$SQL[] = "ALTER TABLE moderators DROP edit_user;";
}

if( $DB->checkForField( 'rating_added', 'profile_ratings' ) )
{
	$SQL[] = "ALTER TABLE profile_ratings DROP rating_added;";
}

if( $DB->checkForField( 'misc', 'reputation_index' ) )
{
	$SQL[] = "ALTER TABLE reputation_index DROP misc;";
}

if( $DB->checkForField( 'pp_bio_content', 'profile_portal' ) )
{
	$SQL[] = "ALTER TABLE profile_portal DROP pp_bio_content;";
}

if( $DB->checkForField( 'pp_comment_count', 'profile_portal' ) )
{
	$SQL[] = "ALTER TABLE profile_portal DROP pp_comment_count;";
}

if( $DB->checkForField( 'pp_setting_notify_comments', 'profile_portal' ) )
{
	$SQL[] = "ALTER TABLE profile_portal DROP pp_setting_notify_comments;";
}

if( $DB->checkForField( 'pp_setting_notify_friend', 'profile_portal' ) )
{
	$SQL[] = "ALTER TABLE profile_portal DROP pp_setting_notify_friend;";
}

if( $DB->checkForField( 'pp_friend_count', 'profile_portal' ) )
{
	$SQL[] = "ALTER TABLE profile_portal DROP pp_friend_count;";
}

if( $DB->checkForField( 'pp_gender', 'profile_portal' ) )
{
	$SQL[] = "ALTER TABLE profile_portal DROP pp_gender;";
}

if( $DB->checkForField( 'pp_profile_views', 'profile_portal' ) )
{
	$SQL[] = "ALTER TABLE profile_portal DROP pp_profile_views;";
}

if( $DB->checkForField( 'links', 'profile_portal' ) )
{
	$SQL[] = "ALTER TABLE profile_portal DROP links;";
}

if( $DB->checkForField( 'bio', 'profile_portal' ) )
{
	$SQL[] = "ALTER TABLE profile_portal DROP bio;";
}

if( $DB->checkForField( 'ta_size', 'profile_portal' ) )
{
	$SQL[] = "ALTER TABLE profile_portal DROP ta_size;";
}

if( $DB->checkForField( 'fb_status', 'profile_portal' ) )
{
	$SQL[] = "ALTER TABLE profile_portal DROP fb_status;";
}

if( $DB->checkForField( 'pp_status', 'profile_portal' ) )
{
	$SQL[] = "ALTER TABLE profile_portal DROP pp_status;";
}

if( $DB->checkForField( 'pp_status_update', 'profile_portal' ) )
{
	$SQL[] = "ALTER TABLE profile_portal DROP pp_status_update;";
}

if( $DB->checkForField( 'max_points', 'rc_modpref' ) )
{
	$SQL[] = "ALTER TABLE rc_modpref DROP max_points;";
}

if( $DB->checkForField( 'reports_pp', 'rc_modpref' ) )
{
	$SQL[] = "ALTER TABLE rc_modpref DROP reports_pp;";
}

if( $DB->checkForField( 'by_pm', 'rc_modpref' ) )
{
	$SQL[] = "ALTER TABLE rc_modpref DROP by_pm;";
}

if( $DB->checkForField( 'by_email', 'rc_modpref' ) )
{
	$SQL[] = "ALTER TABLE rc_modpref DROP by_email;";
}

if( $DB->checkForField( 'by_alert', 'rc_modpref' ) )
{
	$SQL[] = "ALTER TABLE rc_modpref DROP by_alert;";
}

//if( $DB->checkForField( 'location', 'sessions' ) )
//{
//	$SQL[] = "ALTER TABLE sessions DROP location;";
//}

if( $DB->checkForField( 'misc', 'tags_index' ) )
{
	$SQL[] = "ALTER TABLE tags_index DROP misc;";
}

if( $DB->checkForField( 'total_votes', 'topics' ) )
{
	$SQL[] = "ALTER TABLE topics DROP total_votes;";
}

if( $DB->checkForField( 'email_pm', 'members' ) )
{
	$SQL[] = "ALTER TABLE members DROP email_pm;";
}

if( $DB->checkForField( 'view_pop', 'members' ) )
{
	$SQL[] = "ALTER TABLE members DROP view_pop;";
}