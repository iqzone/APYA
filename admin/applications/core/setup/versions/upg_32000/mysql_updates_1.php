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

$PRE = trim(ipsRegistry::dbFunctions()->getPrefix());
$DB  = ipsRegistry::DB();

/* Generic changes */
$SQL[] = "ALTER TABLE login_methods ADD login_custom_config TEXT NULL DEFAULT NULL;";

$SQL[] = "ALTER TABLE reputation_cache ADD rep_like_cache MEDIUMTEXT,
	CHANGE rep_points rep_points INT( 10 ) NOT NULL DEFAULT '0';";

$SQL[] = "ALTER TABLE core_like ADD like_lookup_area CHAR(32) NOT NULL DEFAULT '' AFTER like_lookup_id,
	ADD like_visible TINYINT NOT NULL DEFAULT '1',
	DROP INDEX find_rel_favs,
	ADD INDEX find_rel_likes ( like_lookup_id , like_visible , like_is_anon , like_added ),
	DROP INDEX like_member_id,
	ADD INDEX like_member_id ( like_member_id , like_visible , like_added ),
	ADD INDEX like_lookup_area ( like_lookup_area , like_visible ),
	ADD INDEX notification_task ( like_notify_do, like_app (50), like_area (50), like_visible, like_notify_sent, like_notify_freq (50) );";
$SQL[] = "UPDATE core_like SET like_lookup_area = MD5(CONCAT(like_app, ';', like_area, ';', like_member_id ) );";

if( !$DB->checkForField( 'app_tab_groups', 'core_applications' ) )
{
	$SQL[] = "ALTER TABLE core_applications ADD app_website VARCHAR(255) NULL DEFAULT NULL,
		ADD app_update_check VARCHAR(255) NULL DEFAULT NULL,
		ADD app_global_caches VARCHAR(255) NULL DEFAULT NULL,
		ADD app_tab_groups TEXT NULL DEFAULT NULL AFTER app_hide_tab;";
}
	
$SQL[] = "ALTER TABLE cache_store DROP cs_extra,
	ADD cs_rebuild TINYINT( 1 ) NOT NULL DEFAULT '0';";
	
$SQL[] = "ALTER TABLE banfilters DROP ban_nocache, 
	ADD ban_reason VARCHAR(255) NULL DEFAULT NULL;";

$SQL[] = "ALTER TABLE rc_reports_index ADD INDEX ( status );";

$SQL[] = "ALTER TABLE rc_comments ADD approved TINYINT NOT NULL DEFAULT '1',
	ADD edit_date INT NOT NULL DEFAULT '0',
	ADD author_name VARCHAR( 255 ) NULL DEFAULT NULL ,
	ADD ip_address VARCHAR( 46 ) NULL DEFAULT NULL;";

$SQL[] = "ALTER TABLE inline_notifications ADD notify_meta_app VARCHAR(50) NULL DEFAULT NULL,
	ADD notify_meta_area VARCHAR(100) NULL DEFAULT NULL,
	ADD notify_meta_id INT(10) DEFAULT 0,
	ADD notify_meta_key VARCHAR(32) NULL DEFAULT NULL,
	ADD KEY notify_meta_key (notify_meta_key);";

$SQL[] = "ALTER TABLE ignored_users ADD ignore_signatures INT(1) NOT NULL DEFAULT '0',
	ADD ignore_chats INT(1) NOT NULL DEFAULT '0';";

$SQL[] = "ALTER TABLE mail_queue ADD mail_cc TEXT DEFAULT NULL;";

$SQL[] = "ALTER TABLE permission_index CHANGE perm_2 perm_2 TEXT NULL DEFAULT NULL,
	CHANGE perm_3 perm_3 TEXT NULL DEFAULT NULL,
	CHANGE perm_4 perm_4 TEXT NULL DEFAULT NULL,
	CHANGE perm_5 perm_5 TEXT NULL DEFAULT NULL,
	CHANGE perm_6 perm_6 TEXT NULL DEFAULT NULL,
	CHANGE perm_7 perm_7 TEXT NULL DEFAULT NULL;";

$SQL[] = "ALTER TABLE forums DROP quick_reply,
	ADD tag_predefined TEXT NULL;";

$SQL[] = "ALTER TABLE captcha CHANGE captcha_ipaddress captcha_ipaddress VARCHAR( 46 ) NOT NULL;";
$SQL[] = "ALTER TABLE converge_local CHANGE converge_ip_address converge_ip_address VARCHAR( 46 ) NOT NULL;";
$SQL[] = "ALTER TABLE api_log CHANGE api_log_ip api_log_ip VARCHAR( 46 ) NOT NULL;";
$SQL[] = "ALTER TABLE api_users CHANGE api_user_ip api_user_ip VARCHAR( 46 ) NOT NULL;";
$SQL[] = "ALTER TABLE core_sys_cp_sessions CHANGE session_ip_address session_ip_address VARCHAR( 46 ) NOT NULL;";

$SQL[] = "ALTER TABLE sessions DROP location,
	CHANGE ip_address ip_address VARCHAR( 46 ) NULL DEFAULT NULL,
	CHANGE member_name member_name VARCHAR( 255 ) NULL DEFAULT NULL,
	CHANGE search_thread_time search_thread_time INT( 10 ) NOT NULL DEFAULT '0';";

$SQL[] = "ALTER TABLE upgrade_sessions CHANGE session_ip_address session_ip_address VARCHAR( 46 ) NOT NULL;";

$SQL[] = "ALTER TABLE validating CHANGE ip_address ip_address VARCHAR( 46 ) NOT NULL DEFAULT '0';";

$SQL[] = "ALTER TABLE core_sys_lang CHANGE lang_short lang_short varchar(32) NOT NULL;";

$SQL[] = "CREATE TABLE core_editor_autosave (
	eas_key			CHAR(32) NOT NULL,
	eas_member_id	INT(10) UNSIGNED NOT NULL DEFAULT '0',
	eas_app			VARCHAR(50) NOT NULL DEFAULT '',
	eas_section		VARCHAR(100) NOT NULL DEFAULT '',
	eas_updated	INT(10) UNSIGNED NOT NULL DEFAULT '0',
	eas_content	MEDIUMTEXT,
	UNIQUE KEY eas_key (eas_key),
	KEY eas_member_lookup (eas_member_id, eas_app, eas_section),
	KEY eas_updated (eas_updated)
);";

$SQL[] = "CREATE TABLE core_tags (
	tag_id 				BIGINT(20) UNSIGNED NOT NULL auto_increment,
	tag_aai_lookup		CHAR(32) NOT NULL DEFAULT '',
	tag_aap_lookup		CHAR(32) NOT NULL DEFAULT '',
	tag_meta_app		VARCHAR(200) NOT NULL default '',		
	tag_meta_area		VARCHAR(200) NOT NULL default '',
	tag_meta_id			INT(10) UNSIGNED NOT NULL DEFAULT 0,
	tag_meta_parent_id	INT(10) UNSIGNED NOT NULL DEFAULT 0,
	tag_member_id		INT(10) UNSIGNED NOT NULL DEFAULT 0,
	tag_added			INT(10) UNSIGNED NOT NULL DEFAULT 0,
	tag_prefix			INT(1)  UNSIGNED NOT NULL DEFAULT 0,
	tag_text			VARCHAR(255),
	PRIMARY KEY (tag_id),
	KEY tag_aai_lookup (tag_aai_lookup),
	KEY tag_app (tag_meta_app (100), tag_meta_area (100)),
	KEY tag_member_id (tag_member_id),
	KEY tag_aap_lookup (tag_aap_lookup, tag_text (200)),
	KEY tag_added (tag_added ) );";

$SQL[] = "CREATE TABLE core_tags_perms (
	tag_perm_aai_lookup CHAR(32) NOT NULL DEFAULT '',
	tag_perm_aap_lookup CHAR(32) NOT NULL DEFAULT '',
	tag_perm_text VARCHAR(255) NOT NULL DEFAULT '',
	tag_perm_visible INT(1) UNSIGNED NOT NULL DEFAULT 1,		
	UNIQUE KEY tag_perm_aai_lookup (tag_perm_aai_lookup),
	KEY tag_perm_aap_lookup( tag_perm_aap_lookup ),
	KEY tag_lookup (tag_perm_text, tag_perm_visible) );";
	
$SQL[] = "CREATE TABLE core_tags_cache (
	tag_cache_key	CHAR(32) NOT NULL DEFAULT '',
	tag_cache_text	text,
	tag_cache_date	INT(10) NOT NULL DEFAULT 0,
	UNIQUE KEY (tag_cache_key ) );";

$SQL[] = "CREATE TABLE cache_simple (
	cache_id	   VARCHAR(32) NOT NULL DEFAULT '',
	cache_perm_key VARCHAR(32) NOT NULL DEFAULT '',
	cache_time	   INT(10) NOT NULL DEFAULT 0,
	cache_data	   MEDIUMTEXT NOT NULL,
	UNIQUE KEY lookup ( cache_id, cache_perm_key )
);";

$SQL[] = "CREATE TABLE core_incoming_email_log (
  log_id int(11) NOT NULL AUTO_INCREMENT,
  log_email varchar(255) DEFAULT NULL,
  log_time int(10) DEFAULT NULL,
  PRIMARY KEY (log_id)
);";

if ( ! $DB->checkForTable('core_geolocation_cache') )
{
	$SQL[] = "CREATE TABLE core_geolocation_cache (
  geocache_key varchar(32) NOT NULL,
  geocache_lat varchar(100) NOT NULL,
  geocache_lon varchar(100) NOT NULL,
  geocache_raw text,
  geocache_country varchar(255) NOT NULL default '',
  geocache_district varchar(255) NOT NULL default '',
  geocache_district2 varchar(255) NOT NULL default '',
  geocache_locality varchar(255) NOT NULL default '',
  geocache_type varchar(255) NOT NULL default '',
  geocache_engine varchar(255) NOT NULL default '',
  geocache_added int(10) NOT NULL default '0',
  geocache_short text,
  PRIMARY KEY  (geocache_key),
  KEY geo_lat_lon (geocache_lat,geocache_lon)
);";
}

if ( ! $DB->checkForTable('skin_generator_sessions') )
{
/* Skin generator */
	$SQL[] = "CREATE TABLE skin_generator_sessions (
	sg_session_id	VARCHAR(32) NOT NULL DEFAULT '',
	sg_member_id	INT(10) NOT NULL DEFAULT 0,
	sg_skin_set_id	INT(10) NOT NULL DEFAULT 0,
	sg_date_start	INT(10) NOT NULL DEFAULT 0,
	sg_data			MEDIUMTEXT,
	PRIMARY KEY (sg_session_id)
);";
}

$SQL[] = "DROP TABLE search_results;";
$SQL[] = "ALTER TABLE attachments_type DROP atype_photo;";
$SQL[] = "ALTER TABLE groups DROP g_avatar_upload;";
$SQL[] = "ALTER TABLE custom_bbcode DROP bbcode_strip_search;";

$SQL[] = "ALTER TABLE emoticons ADD emo_position INT(5) NOT NULL DEFAULT '0';";

$SQL[] = "UPDATE emoticons SET image=REPLACE(image, '.gif', '.png') WHERE emo_set='default' AND image IN ( 'angry.gif', 'biggrin.gif', 'blink.gif', 'blush.gif', 'cool.gif', 'dry.gif', 'excl.gif', 
	'happy.gif', 'huh.gif', 'laugh.gif', 'mellow.gif', 'ohmy.gif', 'ph34r.gif', 'sad.gif', 'sleep.gif', 'smile.gif', 'tongue.gif', 'unsure.gif', 'wacko.gif', 'wink.gif', 'wub.gif' );";
	
