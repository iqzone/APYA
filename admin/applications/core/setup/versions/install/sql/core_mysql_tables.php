<?php
/**
* Installation Schematic File
* Generated on Thu, 19 Feb 2009 08:15:47 +0000 GMT
*/
$TABLE[] = "CREATE TABLE admin_login_logs (
  admin_id int(10) NOT NULL auto_increment,
  admin_ip_address varchar(46) NOT NULL default '0.0.0.0',
  admin_username varchar(40) NOT NULL default '',
  admin_time int(10) unsigned NOT NULL default 0,
  admin_success int(1) unsigned NOT NULL default 0,
  admin_post_details text,
  PRIMARY KEY  (admin_id),
  KEY admin_ip_address (admin_ip_address),
  KEY admin_time (admin_time)
);";
$TABLE[] = "CREATE TABLE admin_logs (
  id bigint(20) NOT NULL auto_increment,
  member_id int(10) default 0,
  ctime int(10) default 0,
  note text,
  ip_address varchar(46) default NULL,
  appcomponent varchar(255) NOT NULL default '',
  module varchar(255) NOT NULL default '',
  section varchar(255) NOT NULL default '',
  do varchar(255) NOT NULL default '',
  PRIMARY KEY  (id),
  KEY ctime (ctime),
  KEY ip_address(ip_address)
);";
$TABLE[] = "CREATE TABLE admin_permission_rows (
  row_id int(8) NOT NULL,
  row_id_type varchar(13) NOT NULL default 'member',
  row_perm_cache mediumtext,
  row_updated int(10) NOT NULL default 0,
  PRIMARY KEY  (row_id,row_id_type)
);";
$TABLE[] = "CREATE TABLE announcements (
  announce_id int(10) unsigned NOT NULL auto_increment,
  announce_title varchar(255) NOT NULL default '',
  announce_post text NOT NULL,
  announce_forum text,
  announce_member_id mediumint(8) unsigned NOT NULL default 0,
  announce_html_enabled tinyint(1) NOT NULL default 0,
  announce_nlbr_enabled tinyint(1) NOT NULL default 0,
  announce_views int(10) unsigned NOT NULL default 0,
  announce_start int(10) unsigned NOT NULL default 0,
  announce_end int(10) unsigned NOT NULL default 0,
  announce_active tinyint(1) NOT NULL default 1,
  announce_seo_title VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY  (announce_id),
  KEY announce_end (announce_end)
);";
$TABLE[] = "CREATE TABLE api_log (
  api_log_id int(10) unsigned NOT NULL auto_increment,
  api_log_key varchar(32) NOT NULL,
  api_log_ip varchar(46) NOT NULL,
  api_log_date int(10) NOT NULL,
  api_log_query text NOT NULL,
  api_log_allowed tinyint(1) unsigned NOT NULL,
  PRIMARY KEY  (api_log_id),
  KEY api_log_date (api_log_date)
);";
$TABLE[] = "CREATE TABLE api_users (
  api_user_id int(4) unsigned NOT NULL auto_increment,
  api_user_key char(32) NOT NULL,
  api_user_name varchar(32) NOT NULL,
  api_user_perms text NOT NULL,
  api_user_ip varchar(46) NOT NULL,
  PRIMARY KEY  (api_user_id)
);";
$TABLE[] = "CREATE TABLE attachments (
  attach_id int(10) NOT NULL auto_increment,
  attach_ext varchar(10) NOT NULL default '',
  attach_file varchar(250) NOT NULL default '',
  attach_location varchar(250) NOT NULL default '',
  attach_thumb_location varchar(250) NOT NULL default '',
  attach_thumb_width smallint(5) NOT NULL default 0,
  attach_thumb_height smallint(5) NOT NULL default 0,
  attach_is_image tinyint(1) NOT NULL default 0,
  attach_hits int(10) NOT NULL default 0,
  attach_date int(10) NOT NULL default 0,
  attach_post_key varchar(32) NOT NULL default '0',
  attach_member_id int(8) NOT NULL default 0,
  attach_filesize int(10) NOT NULL default 0,
  attach_rel_id int(10) NOT NULL default 0,
  attach_rel_module varchar(100) NOT NULL default '0',
  attach_img_width int(5) NOT NULL default 0,
  attach_img_height int(5) NOT NULL default 0,
  attach_parent_id INT NOT NULL DEFAULT '0',
  attach_is_archived INT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY  (attach_id),
  KEY attach_pid (attach_rel_id),
  KEY attach_post_key (attach_post_key),
  KEY attach_mid_size (attach_member_id,attach_rel_module,attach_filesize),
  KEY attach_parent_id ( attach_parent_id, attach_rel_module )
);";
$TABLE[] = "CREATE TABLE attachments_type (
  atype_id int(10) NOT NULL auto_increment,
  atype_extension varchar(18) NOT NULL default '',
  atype_mimetype varchar(255) NOT NULL default '',
  atype_post tinyint(1) NOT NULL default 1,
  atype_img text,
  PRIMARY KEY  (atype_id),
  KEY atype (atype_post),
  KEY atype_extension (atype_extension)
);";
$TABLE[] = "CREATE TABLE badwords (
  wid int(3) NOT NULL auto_increment,
  type varchar(250) NOT NULL default '',
  swop varchar(250) default NULL,
  m_exact tinyint(1) default 0,
  PRIMARY KEY  (wid)
);";
$TABLE[] = "CREATE TABLE banfilters (
  ban_id int(10) NOT NULL auto_increment,
  ban_type varchar(10) NOT NULL default 'ip',
  ban_content text,
  ban_date int(10) NOT NULL default 0,
  ban_reason VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY  (ban_id),
  KEY ban_content (ban_content(200))
);";
$TABLE[] = "CREATE TABLE bbcode_mediatag (
  mediatag_id smallint(10) unsigned NOT NULL auto_increment,
  mediatag_name varchar(255) NOT NULL,
  mediatag_match text,
  mediatag_replace text,
  mediatag_position SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY  (mediatag_id)
);";
$TABLE[] = "CREATE TABLE bulk_mail (
  mail_id int(10) NOT NULL auto_increment,
  mail_subject varchar(255) NOT NULL default '',
  mail_content mediumtext NOT NULL,
  mail_groups mediumtext,
  mail_opts mediumtext,
  mail_start int(10) NOT NULL default 0,
  mail_updated int(10) NOT NULL default 0,
  mail_sentto int(10) NOT NULL default 0,
  mail_active tinyint(1) NOT NULL default 0,
  mail_pergo smallint(5) NOT NULL default 0,
  PRIMARY KEY  (mail_id),
  KEY mail_start (mail_start)
);";


$TABLE[] = "CREATE TABLE content_cache_sigs (
	cache_content_id		INT(10) UNSIGNED NOT NULL default 0,
	cache_content			MEDIUMTEXT,
	cache_updated			INT(10) NOT NULL default 0,
	PRIMARY KEY cache_content_id( cache_content_id ),
	KEY date_index (cache_updated )
);";

$TABLE[] = "CREATE TABLE content_cache_posts (
	cache_content_id		INT(10) UNSIGNED NOT NULL default 0,
	cache_content			MEDIUMTEXT,
	cache_updated			INT(10) NOT NULL default 0,
	PRIMARY KEY cache_content_id( cache_content_id ),
	KEY date_index (cache_updated )
);";

$TABLE[] = "CREATE TABLE core_editor_autosave (
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

$TABLE[] = "CREATE TABLE core_like (
  like_id VARCHAR(32),
  like_lookup_id VARCHAR(32) default NULL,
  like_lookup_area VARCHAR(32) NOT NULL DEFAULT '',
  like_app varchar(150) NOT NULL default '',
  like_area varchar(200) NOT NULL default '',
  like_rel_id bigint(20) unsigned NOT NULL default '0',
  like_member_id int(10) unsigned NOT NULL default '0',
  like_is_anon int(1) NOT NULL default '0',
  like_added int(10) unsigned NOT NULL default '0',
  like_notify_do int(1) NOT NULL default '0',
  like_notify_meta text,
  like_notify_freq varchar(200) NOT NULL default '',
  like_notify_sent int(10) unsigned NOT NULL default '0',
  like_visible TINYINT NOT NULL DEFAULT '1',
  PRIMARY KEY  (like_id),
  KEY find_rel_likes (like_lookup_id,like_visible,like_is_anon,like_added),
  KEY like_member_id (like_member_id , like_visible , like_added),
  KEY like_lookup_area (like_lookup_area , like_visible),
  KEY notification_task ( like_notify_do, like_app (50), like_area (50), like_visible, like_notify_sent, like_notify_freq (50) )
);";

$TABLE[] = "CREATE TABLE core_like_cache (
  like_cache_id VARCHAR(32),
  like_cache_app varchar(150) NOT NULL default '',
  like_cache_area varchar(200) NOT NULL default '',
  like_cache_rel_id bigint(20) unsigned NOT NULL default '0',
  like_cache_data text,
  like_cache_expire int(10) NOT NULL default '0',
  PRIMARY KEY  (like_cache_id)
);";

$TABLE[] = "CREATE TABLE IF NOT EXISTS core_geolocation_cache (
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

$TABLE[] = "CREATE TABLE core_tags (
	tag_id 				BIGINT(20) NOT NULL auto_increment,
	tag_aai_lookup		CHAR(32) NOT NULL DEFAULT '',
	tag_aap_lookup		CHAR(32) NOT NULL DEFAULT '',
	tag_meta_app		VARCHAR(200) NOT NULL default '',		
	tag_meta_area		VARCHAR(200) NOT NULL default '',
	tag_meta_id			INT(10) NOT NULL DEFAULT 0,
	tag_meta_parent_id	INT(10) NOT NULL DEFAULT 0,
	tag_member_id		INT(10) NOT NULL DEFAULT 0,
	tag_added			INT(10) NOT NULL DEFAULT 0,
	tag_prefix			INT(1) NOT NULL DEFAULT 0,
	tag_text			VARCHAR(255),
	PRIMARY KEY (tag_id),
	KEY tag_aai_lookup (tag_aai_lookup),
	KEY tag_app (tag_meta_app (100), tag_meta_area (100)),
	KEY tag_member_id (tag_member_id),
	KEY tag_aap_lookup (tag_aap_lookup, tag_text (200)),
	KEY tag_added (tag_added )
);";

$TABLE[] = "CREATE TABLE core_tags_perms (
	tag_perm_aai_lookup CHAR(32) NOT NULL DEFAULT '',
	tag_perm_aap_lookup CHAR(32) NOT NULL DEFAULT '',
	tag_perm_text VARCHAR(255) NOT NULL DEFAULT '',
	tag_perm_visible INT(1) NOT NULL DEFAULT 1,		
	UNIQUE KEY tag_perm_aai_lookup (tag_perm_aai_lookup),
	KEY tag_perm_aap_lookup( tag_perm_aap_lookup ),
	KEY tag_lookup (tag_perm_text, tag_perm_visible) 
);";
	
$TABLE[] = "CREATE TABLE core_tags_cache (
	tag_cache_key	CHAR(32) NOT NULL DEFAULT '',
	tag_cache_text	text,
	tag_cache_date	INT(10) NOT NULL DEFAULT 0,
	UNIQUE KEY (tag_cache_key ) 
);";
	
$TABLE[] = "CREATE TABLE cache_store (
  cs_key varchar(255) NOT NULL default '',
  cs_value mediumtext NULL DEFAULT NULL,
  cs_array tinyint(1) NOT NULL default 0,
  cs_updated int(10) NOT NULL default 0,
  cs_rebuild TINYINT( 1 ) NOT NULL DEFAULT '0',
  PRIMARY KEY  (cs_key)
);";
$TABLE[] = "CREATE TABLE captcha (
  captcha_unique_id varchar(32) NOT NULL default '',
  captcha_string varchar(100) NOT NULL default '',
  captcha_ipaddress varchar(46) NOT NULL default '',
  captcha_date int(10) NOT NULL default 0,
  PRIMARY KEY  (captcha_unique_id),
  KEY captcha_date ( captcha_date )
);";

$TABLE[] = "CREATE TABLE converge_local (
  converge_api_code varchar(32) NOT NULL default '',
  converge_product_id int(10) NOT NULL default 0,
  converge_added int(10) NOT NULL default 0,
  converge_ip_address varchar(46) NOT NULL default '',
  converge_url varchar(255) NOT NULL default '',
  converge_active int(1) NOT NULL default 0,
  converge_http_user varchar(255) NOT NULL default '',
  converge_http_pass varchar(255) NOT NULL default '',
  PRIMARY KEY  (converge_api_code),
  KEY converge_active (converge_active)
);";
$TABLE[] = "CREATE TABLE core_archive_log (
	archlog_id	INT(10) AUTO_INCREMENT NOT NULL,
	archlog_app		VARCHAR(255) NOT NULL DEFAULT 0,
	archlog_date	INT(10) NOT NULL DEFAULT 0,
	archlog_ids		MEDIUMTEXT,
	archlog_count	INT(10) NOT NULL DEFAULT 0,
	archlog_is_restore INT(1) NOT NULL DEFAULT 0,
	archlog_is_error	INT(1) NOT NULL DEFAULT 0,
	archlog_msg			TEXT,
	PRIMARY KEY  (archlog_id)
);";
$TABLE[] = "CREATE TABLE core_archive_restore (
	restore_min_tid	INT(10) NOT NULL DEFAULT 0,
	restore_max_tid	INT(10) NOT NULL DEFAULT 0,
	restore_manual_tids MEDIUMTEXT
);";
$TABLE[] = "CREATE TABLE core_archive_rules (
  archive_key	CHAR(32) NOT NULL DEFAULT '',
  archive_app	VARCHAR(32) NOT NULL DEFAULT 'core',
  archive_field	VARCHAR(255) NOT NULL DEFAULT '',
  archive_value	VARCHAR(255) NOT NULL DEFAULT '',
  archive_text	TEXT NOT NULL,
  archive_unit	VARCHAR(255) NOT NULL DEFAULT '',
  archive_skip	INT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (archive_key)
);";

$TABLE[] = "CREATE TABLE core_applications (
  app_id int(10) NOT NULL auto_increment,
  app_title varchar(255) NOT NULL default '',
  app_public_title varchar(255) NOT NULL default '',
  app_description varchar(255) NOT NULL default '',
  app_author varchar(255) NOT NULL default '',
  app_version varchar(255) NOT NULL default '',
  app_long_version int(10) NOT NULL default 10000,
  app_directory varchar(255) NOT NULL default '',
  app_added int(10) NOT NULL default 0,
  app_position int(2) NOT NULL default 0,
  app_protected int(1) NOT NULL default 0,
  app_enabled int(1) NOT NULL default 0,
  app_location varchar(32) NOT NULL default '',
  app_hide_tab TINYINT(1) NOT NULL DEFAULT 0,
  app_tab_groups TEXT NULL DEFAULT NULL,
  app_website VARCHAR(255) NULL DEFAULT NULL,
  app_update_check VARCHAR(255) NULL DEFAULT NULL,
  app_global_caches VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY  (app_id),
  KEY app_directory (app_directory)
);";
$TABLE[] = "CREATE TABLE core_hooks (
  hook_id mediumint(4) unsigned NOT NULL auto_increment,
  hook_enabled tinyint(1) NOT NULL default 0,
  hook_name varchar(255) default NULL,
  hook_desc text,
  hook_author varchar(255) default NULL,
  hook_email varchar(255) default NULL,
  hook_website text,
  hook_update_check text,
  hook_requirements text,
  hook_version_human varchar(32) default NULL,
  hook_version_long varchar(32) NOT NULL default '0',
  hook_installed int NOT NULL default 0,
  hook_updated int NOT NULL default 0,
  hook_position mediumint(9) NOT NULL default 0,
  hook_extra_data text,
  hook_key varchar(32) default NULL,
  hook_global_caches VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY  (hook_id),
  KEY hook_enabled (hook_enabled,hook_position),
  KEY hook_key (hook_key)
);";
$TABLE[] = "CREATE TABLE core_hooks_files (
  hook_file_id int(10) NOT NULL auto_increment,
  hook_hook_id int(10) NOT NULL default 0,
  hook_file_stored varchar(255) default NULL,
  hook_file_real varchar(255) default NULL,
  hook_type varchar(32) default NULL,
  hook_classname varchar(255) default NULL,
  hook_data text,
  hooks_source longtext,
  PRIMARY KEY  (hook_file_id),
  KEY hook_hook_id (hook_hook_id)
);";
$TABLE[] = "CREATE TABLE core_incoming_emails (
	rule_id INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	rule_criteria_field VARCHAR( 4 ) NOT NULL ,
	rule_criteria_type VARCHAR( 4 ) NOT NULL ,
	rule_criteria_value TEXT NOT NULL ,
	rule_app VARCHAR( 255 ) NOT NULL ,
	rule_added_by MEDIUMINT( 8 ) NOT NULL ,
	rule_added_date INT( 10 ) NOT NULL
);";

$TABLE[] = "CREATE TABLE core_incoming_email_log (
  log_id int(11) NOT NULL AUTO_INCREMENT,
  log_email varchar(255) DEFAULT NULL,
  log_time int(10) DEFAULT NULL,
  PRIMARY KEY (log_id)
);";

$TABLE[] = "CREATE TABLE core_inline_messages (
inline_msg_id		INT(10) NOT NULL auto_increment,
inline_msg_date		INT(10) NOT NULL DEFAULT 0,
inline_msg_content	TEXT,
PRIMARY KEY (inline_msg_id),
KEY inline_msg_date (inline_msg_date)
);";

$TABLE[] = "CREATE TABLE core_item_markers (
  item_key char(32) NOT NULL,
  item_member_id int(8) NOT NULL default 0,
  item_app varchar(255) NOT NULL default 'core',
  item_last_update int(10) NOT NULL default 0,
  item_last_saved int(10) NOT NULL default 0,
  item_unread_count int(5) NOT NULL default 0,
  item_read_array mediumtext,
  item_global_reset int(10) NOT NULL default 0,
  item_app_key_1 int(10) NOT NULL default 0,
  item_app_key_2 int(10) NOT NULL default 0,
  item_app_key_3 int(10) NOT NULL default 0,
  item_is_deleted INT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY combo_key (item_key,item_member_id,item_app),
  KEY marker_index (item_member_id, item_app, item_app_key_1),
  KEY item_last_saved (item_last_saved),
  KEY item_member_id (item_is_deleted,item_member_id)
);";

$TABLE[] = "CREATE TABLE core_item_markers_storage (
  item_member_id int(8) NOT NULL default 0,
  item_markers mediumtext,
  item_last_updated int(10) NOT NULL default 0,
  item_last_saved int(10) NOT NULL default 0,
  KEY item_last_saved (item_last_saved),
  PRIMARY KEY  (item_member_id)
);";

$TABLE[] = "CREATE TABLE core_rss_imported (
	rss_guid			CHAR(32) NOT NULL,
	rss_foreign_key		VARCHAR(100) NOT NULL default '',
	PRIMARY KEY (rss_guid),
	KEY rss_grabber (rss_guid, rss_foreign_key)
);";

$TABLE[] = "CREATE TABLE core_share_links (
	share_id		INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	share_title		VARCHAR(255) NOT NULL DEFAULT '',
	share_key		VARCHAR(50) NOT NULL DEFAULT '',
	share_enabled	INT(1) NOT NULL DEFAULT 0,
	share_position	INT(3) NOT NULL DEFAULT 0,
	share_canonical INT(1) NOT NULL DEFAULT 1,
	PRIMARY KEY share_id (share_id)
);";

$TABLE[] = "CREATE TABLE core_share_links_log (
	log_id				  INT(10) NOT NULL auto_increment,
	log_date			  INT(10) NOT NULL default 0,
	log_member_id		  INT(10) NOT NULL default 0,
	log_url				  TEXT,
	log_title			  TEXT,
	log_share_key		  VARCHAR(50) NOT NULL default '',
	log_data_app		  VARCHAR(50) NOT NULL default '',
	log_data_type		  VARCHAR(50) NOT NULL default '',
	log_data_primary_id   INT(10) NOT NULL default 0,
	log_data_secondary_id INT(10) NOT NULL default 0,
	log_ip_address		  VARCHAR(46) NOT NULL DEFAULT '',
	PRIMARY KEY log_id (log_id),
	KEY findstuff (log_data_app, log_data_type, log_data_primary_id),
	KEY log_date (log_date),
	KEY log_member_id (log_member_id),
	KEY log_share_key (log_share_key),
	KEY log_ip_address (log_ip_address)
);";

$TABLE[] = "CREATE TABLE core_share_links_caches (
	cache_id		INT(10) NOT NULL auto_increment,
	cache_key		VARCHAR(255) NOT NULL default '',
	cache_data		MEDIUMTEXT,
	cache_date		INT(10) NOT NULL default 0,
	PRIMARY KEY cache_id( cache_id )
);";

$TABLE[] = "CREATE TABLE core_soft_delete_log (
  sdl_id int(10) NOT NULL AUTO_INCREMENT,
  sdl_obj_id int(10) NOT NULL DEFAULT 0,
  sdl_obj_key varchar(20) NOT NULL DEFAULT '',
  sdl_obj_member_id int(10) NOT NULL DEFAULT 0,
  sdl_obj_date int(10) NOT NULL DEFAULT 0,
  sdl_obj_reason text,
  sdl_locked int(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (sdl_id),
  KEY look_up (sdl_obj_id,sdl_obj_key)
);";

$TABLE[] = "CREATE TABLE core_sys_conf_settings (
  conf_id int(10) NOT NULL auto_increment,
  conf_title varchar(255) NOT NULL default '',
  conf_description text,
  conf_group smallint(3) NOT NULL default 0,
  conf_type varchar(255) NOT NULL default '',
  conf_key varchar(255) NOT NULL default '',
  conf_value text,
  conf_default text,
  conf_extra text,
  conf_evalphp text,
  conf_protected tinyint(1) NOT NULL default 0,
  conf_position smallint(3) NOT NULL default 0,
  conf_start_group varchar(255) NOT NULL default '',
  conf_add_cache tinyint(1) NOT NULL default 1,
  conf_keywords text,
  PRIMARY KEY  (conf_id),
  KEY conf_key (conf_key),
  KEY conf_group ( conf_group , conf_position , conf_title ),
  KEY conf_add_cache (conf_add_cache)
);";

$TABLE[] = "CREATE TABLE core_sys_cp_sessions (
  session_id varchar(32) NOT NULL default '',
  session_ip_address varchar(46) NOT NULL default '',
  session_member_name varchar(255) NOT NULL default '',
  session_member_id mediumint(8) NOT NULL default 0,
  session_member_login_key varchar(32) NOT NULL default '',
  session_location varchar(64) NOT NULL default '',
  session_log_in_time int(10) NOT NULL default 0,
  session_running_time int(10) NOT NULL default 0,
  session_url TEXT,
  session_app_data text,
  PRIMARY KEY  (session_id),
  KEY ( session_running_time ),
  KEY ( session_member_id )
);";
$TABLE[] = "CREATE TABLE core_sys_lang (
  lang_id mediumint(4) unsigned NOT NULL auto_increment,
  lang_short varchar(32) NOT NULL default '',
  lang_title varchar(255) NOT NULL default '',
  lang_default tinyint(1) unsigned NOT NULL default 0,
  lang_isrtl tinyint(1) unsigned NOT NULL default 0,
  lang_protected tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY  (lang_id),
  KEY lang_short (lang_short),
  KEY lang_default (lang_default)
);";
$TABLE[] = "CREATE TABLE core_sys_lang_words (
  word_id int(10) unsigned NOT NULL auto_increment,
  lang_id mediumint(4) unsigned NOT NULL,
  word_app varchar(255) NOT NULL,
  word_pack varchar(255) NOT NULL,
  word_key varchar(64) NOT NULL,
  word_default text NOT NULL,
  word_custom text,
  word_default_version varchar(10) NOT NULL default '1',
  word_custom_version varchar(10) default NULL,
  word_js tinyint(1) unsigned NOT NULL default 0,
  PRIMARY KEY  (word_id),
  KEY word_js (word_js),
  KEY word_find (lang_id, word_app(32), word_pack(100))
);";
$TABLE[] = "CREATE TABLE core_sys_login (
  sys_login_id int(8) NOT NULL default 0,
  sys_cookie mediumtext,
  PRIMARY KEY  (sys_login_id)
);";
$TABLE[] = "CREATE TABLE core_sys_module (
  sys_module_id mediumint(4) unsigned NOT NULL auto_increment,
  sys_module_title varchar(32) NOT NULL default '',
  sys_module_application varchar(32) NOT NULL default '',
  sys_module_key varchar(32) NOT NULL default '',
  sys_module_description varchar(100) NOT NULL default '',
  sys_module_version varchar(32) NOT NULL default '',
  sys_module_protected tinyint(1) unsigned NOT NULL default 0,
  sys_module_visible tinyint(1) unsigned NOT NULL default 1,
  sys_module_position int(5) NOT NULL default 0,
  sys_module_admin int(1) NOT NULL default 0,
  PRIMARY KEY  (sys_module_id),
  KEY sys_module_application (sys_module_application),
  KEY sys_module_visible (sys_module_visible),
  KEY sys_module_key (sys_module_key)
);";
$TABLE[] = "CREATE TABLE core_sys_settings_titles (
  conf_title_id smallint(3) NOT NULL auto_increment,
  conf_title_title varchar(255) NOT NULL default '',
  conf_title_desc text,
  conf_title_count smallint(3) NOT NULL default 0,
  conf_title_noshow tinyint(1) NOT NULL default 0,
  conf_title_keyword varchar(200) NOT NULL default '',
  conf_title_app varchar(200) NOT NULL default '',
  conf_title_tab varchar(32) default NULL,
  PRIMARY KEY  (conf_title_id),
  KEY conf_title_keyword ( conf_title_keyword )
);";

$TABLE[] = "CREATE TABLE core_uagent_groups (
  ugroup_id int(10) NOT NULL auto_increment,
  ugroup_title varchar(255) NOT NULL default '',
  ugroup_array mediumtext,
  PRIMARY KEY  (ugroup_id),
  KEY ugroup_title (ugroup_title)
);";
$TABLE[] = "CREATE TABLE core_uagents (
  uagent_id int(10) NOT NULL auto_increment,
  uagent_key varchar(200) NOT NULL default '',
  uagent_name varchar(200) NOT NULL default '',
  uagent_regex text,
  uagent_regex_capture int(1) NOT NULL default 0,
  uagent_type varchar(200) NOT NULL default '',
  uagent_position int(10) NOT NULL default 0,
  uagent_default_regex TEXT,
  PRIMARY KEY  (uagent_id),
  KEY uagent_key (uagent_key),
  KEY ordering ( uagent_position , uagent_key )
);";
$TABLE[] = "CREATE TABLE custom_bbcode (
  bbcode_id int(10) NOT NULL auto_increment,
  bbcode_title varchar(255) NOT NULL default '',
  bbcode_desc text,
  bbcode_tag varchar(255) NOT NULL default '',
  bbcode_replace text,
  bbcode_useoption tinyint(1) NOT NULL default 0,
  bbcode_example text,
  bbcode_switch_option int(1) NOT NULL default 0,
  bbcode_menu_option_text varchar(200) NOT NULL default '',
  bbcode_menu_content_text varchar(200) NOT NULL default '',
  bbcode_single_tag tinyint(1) NOT NULL default 0,
  bbcode_groups varchar(255) default NULL,
  bbcode_sections varchar(255) default NULL,
  bbcode_php_plugin varchar(255) default NULL,
  bbcode_no_parsing tinyint(1) NOT NULL default 0,
  bbcode_protected tinyint(1) NOT NULL default 0,
  bbcode_aliases varchar(255) default NULL,
  bbcode_optional_option tinyint(1) NOT NULL default 0,
  bbcode_image varchar(255) default NULL,
  bbcode_app varchar(50) NOT NULL default '',
  bbcode_custom_regex TEXT NULL DEFAULT NULL,
  PRIMARY KEY  (bbcode_id)
);";
$TABLE[] = "CREATE TABLE dnames_change (
  dname_id int(10) NOT NULL auto_increment,
  dname_member_id int(8) NOT NULL default 0,
  dname_date int(10) NOT NULL default 0,
  dname_ip_address varchar(46) NOT NULL default '',
  dname_previous varchar(255) NOT NULL default '',
  dname_current varchar(255) NOT NULL default '',
  dname_discount TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY  (dname_id),
  KEY dname_member_id (dname_member_id),
  KEY date_id (dname_member_id,dname_date),
  KEY dname_ip_address (dname_ip_address)
);";

$TABLE[] = "CREATE TABLE emoticons (
  id smallint(3) NOT NULL auto_increment,
  typed varchar(32) NOT NULL default '',
  image varchar(128) NOT NULL default '',
  clickable smallint(2) NOT NULL default 1,
  emo_set varchar(64) NOT NULL default 'default',
  emo_position INT(5) NOT NULL DEFAULT '0',
  PRIMARY KEY  (id),
  KEY emo_set (emo_set)
);";
$TABLE[] = "CREATE TABLE error_logs (
  log_id int(11) NOT NULL auto_increment,
  log_member int(11) NOT NULL default 0,
  log_date int NOT NULL default 0,
  log_error text,
  log_error_code varchar(24) NOT NULL DEFAULT 0,
  log_ip_address varchar(46) default NULL,
  log_request_uri text,
  PRIMARY KEY  (log_id),
  KEY log_date (log_date),
  KEY log_ip_address (log_ip_address)
);";
$TABLE[] = "CREATE TABLE faq (
  id mediumint(8) NOT NULL auto_increment,
  title varchar(128) NOT NULL default '',
  text text,
  description text,
  position smallint(3) NOT NULL default 0,
  app VARCHAR(32) NOT NULL default 'core',
  PRIMARY KEY  (id)
);";

$TABLE[] = "CREATE TABLE groups (
  g_id int(3) unsigned NOT NULL auto_increment,
  g_view_board tinyint(1) default NULL,
  g_mem_info tinyint(1) default NULL,
  g_other_topics tinyint(1) default NULL,
  g_use_search tinyint(1) default NULL,
  g_edit_profile tinyint(1) default NULL,
  g_post_new_topics tinyint(1) default NULL,
  g_reply_own_topics tinyint(1) default NULL,
  g_reply_other_topics tinyint(1) default NULL,
  g_edit_posts tinyint(1) default NULL,
  g_delete_own_posts tinyint(1) default NULL,
  g_open_close_posts tinyint(1) default NULL,
  g_delete_own_topics tinyint(1) default NULL,
  g_post_polls tinyint(1) default NULL,
  g_vote_polls tinyint(1) default NULL,
  g_use_pm tinyint(1) default 0,
  g_is_supmod tinyint(1) default NULL,
  g_access_cp tinyint(1) default NULL,
  g_title varchar(32) NOT NULL default '',
  g_append_edit tinyint(1) default NULL,
  g_access_offline tinyint(1) default NULL,
  g_avoid_q tinyint(1) default NULL,
  g_avoid_flood tinyint(1) default NULL,
  g_icon text,
  g_attach_max bigint(20) default NULL,
  prefix varchar(250) default NULL,
  suffix varchar(250) default NULL,
  g_max_messages int(5) default 50,
  g_max_mass_pm int(5) default 0,
  g_search_flood mediumint(6) default 20,
  g_edit_cutoff int(10) default 0,
  g_promotion varchar(10) default '-1&-1',
  g_hide_from_list tinyint(1) default 0,
  g_post_closed tinyint(1) default 0,
  g_perm_id varchar(255) NOT NULL default '',
  g_photo_max_vars varchar(200) default '100:200:300',
  g_dohtml tinyint(1) NOT NULL default 0,
  g_edit_topic tinyint(1) NOT NULL default 0,
  g_bypass_badwords tinyint(1) NOT NULL default 0,
  g_can_msg_attach tinyint(1) NOT NULL default 0,
  g_attach_per_post int(10) NOT NULL default 0,
  g_topic_rate_setting smallint(2) NOT NULL default 0,
  g_dname_changes int(3) NOT NULL default 0,
  g_dname_date int(5) NOT NULL default 0,
  g_mod_preview tinyint(1) unsigned NOT NULL default 0,
  g_rep_max_positive mediumint(8) unsigned NOT NULL default 0,
  g_rep_max_negative mediumint(8) unsigned NOT NULL default 0,
  g_signature_limits varchar(255) default NULL,
  g_can_add_friends tinyint(1) NOT NULL default 1,
  g_hide_online_list tinyint(1) NOT NULL default 0,
  g_bitoptions int(10) unsigned NOT NULL default 0,
  g_pm_perday smallint(6) NOT NULL default 0,
  g_mod_post_unit int(5) UNSIGNED NOT NULL default 0,
  g_ppd_limit int(5) UNSIGNED NOT NULL default 0,
  g_ppd_unit int(5) UNSIGNED NOT NULL default 0,
  g_displayname_unit int(5) UNSIGNED NOT NULL default 0,
  g_sig_unit int(5) UNSIGNED NOT NULL default 0,
  g_pm_flood_mins INT(5) UNSIGNED NOT NULL default 0,
  g_max_notifications MEDIUMINT NOT NULL DEFAULT 0,
  g_max_bgimg_upload INT(10) NOT NULL default 0,
  PRIMARY KEY  (g_id)
);";
$TABLE[] = "CREATE TABLE ignored_users (
  ignore_id int(10) NOT NULL auto_increment,
  ignore_owner_id int(8) NOT NULL default 0,
  ignore_ignore_id int(8) NOT NULL default 0,
  ignore_messages int(1) NOT NULL default 0,
  ignore_topics int(1) NOT NULL default 0,
  ignore_signatures INT(1) NOT NULL DEFAULT '0',
  ignore_chats INT( 1 ) NOT NULL DEFAULT '0',
  PRIMARY KEY  (ignore_id),
  KEY ignore_owner_id (ignore_owner_id),
  KEY ignore_ignore_id (ignore_ignore_id)
);";

$TABLE[] = "CREATE TABLE cache_simple (
	cache_id	   VARCHAR(32) NOT NULL DEFAULT '',
	cache_perm_key VARCHAR(32) NOT NULL DEFAULT '',
	cache_time	   INT(10) NOT NULL DEFAULT 0,
	cache_data	   MEDIUMTEXT NULL DEFAULT NULL,
	UNIQUE KEY lookup ( cache_id, cache_perm_key )
);";

$TABLE[] = "CREATE TABLE inline_notifications (
 notify_id INT NOT NULL AUTO_INCREMENT,
 notify_to_id INT NOT NULL DEFAULT 0,
 notify_sent INT(10) NOT NULL DEFAULT 0,
 notify_read INT(10) NOT NULL DEFAULT 0,
 notify_title TEXT NULL DEFAULT NULL ,
 notify_text TEXT NULL DEFAULT NULL ,
 notify_from_id INT NOT NULL DEFAULT 0,
 notify_type_key VARCHAR( 255 ) NULL DEFAULT NULL ,
 notify_url text,
 notify_meta_app VARCHAR(50) NULL DEFAULT NULL,
 notify_meta_area VARCHAR(100) NULL DEFAULT NULL,
 notify_meta_id INT(10) NOT NULL DEFAULT 0,
 notify_meta_key VARCHAR(32) NULL DEFAULT NULL,
 PRIMARY KEY  (notify_id),
 KEY notify_to_id ( notify_to_id, notify_sent ),
 KEY grabber (notify_to_id, notify_read, notify_sent),
 KEY notify_meta_key (notify_meta_key),
 KEY notify_from_id (notify_from_id)
);";

$TABLE[] = "CREATE TABLE login_methods (
  login_id int(10) NOT NULL auto_increment,
  login_title varchar(255) NOT NULL default '',
  login_description varchar(255) NOT NULL default '',
  login_folder_name varchar(255) NOT NULL default '',
  login_maintain_url varchar(255) NOT NULL default '',
  login_register_url varchar(255) NOT NULL default '',
  login_alt_login_html text,
  login_alt_acp_html text,
  login_settings int(1) NOT NULL default 0,
  login_enabled int(1) NOT NULL default 0,
  login_safemode int(1) NOT NULL default 0,
  login_replace_form int(1) NOT NULL default 0,
  login_user_id varchar(255) NOT NULL default 'username',
  login_login_url varchar(255) NOT NULL default '',
  login_logout_url varchar(255) NOT NULL default '',
  login_order smallint(3) NOT NULL default 0,
  login_custom_config TEXT NULL DEFAULT NULL,
  PRIMARY KEY  (login_id)
);";
$TABLE[] = "CREATE TABLE mail_error_logs (
  mlog_id int(10) NOT NULL auto_increment,
  mlog_date int(10) NOT NULL default 0,
  mlog_to varchar(250) NOT NULL default '',
  mlog_from varchar(250) NOT NULL default '',
  mlog_subject varchar(250) NOT NULL default '',
  mlog_content varchar(250) NOT NULL default '',
  mlog_msg text,
  mlog_code varchar(200) NOT NULL default '',
  mlog_smtp_msg text,
  PRIMARY KEY  (mlog_id)
);";
$TABLE[] = "CREATE TABLE mail_queue (
  mail_id int(10) NOT NULL auto_increment,
  mail_date int(10) NOT NULL default 0,
  mail_to varchar(255) NOT NULL default '',
  mail_from varchar(255) NOT NULL default '',
  mail_subject text,
  mail_content text,
  mail_html_on int(1) NOT NULL default 0,
  mail_cc TEXT NULL DEFAULT NULL,
  PRIMARY KEY  (mail_id)
);";
$TABLE[] = "CREATE TABLE members (
  member_id mediumint(8) NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  member_group_id smallint(3) NOT NULL default 0,
  email varchar(150) NOT NULL default '',
  joined int(10) NOT NULL default 0,
  ip_address varchar(46) NOT NULL default '',
  posts mediumint(7) default 0,
  title varchar(64) default NULL,
  allow_admin_mails tinyint(1) default NULL,
  time_offset varchar(10) default NULL,
  skin smallint(5) default NULL,
  warn_level int(10) default NULL,
  warn_lastwarn int(10) NOT NULL default 0,
  language mediumint(4) default NULL,
  last_post int(10) default NULL,
  restrict_post varchar(100) NOT NULL default '0',
  view_sigs tinyint(1) default 1,
  view_img tinyint(1) default 1,
  bday_day int(2) default NULL,
  bday_month int(2) default NULL,
  bday_year int(4) default NULL,
  msg_count_new int(2) NOT NULL default 0,
  msg_count_total int(3) NOT NULL default 0,
  msg_count_reset int(1) NOT NULL default 0,
  msg_show_notification int(1) NOT NULL default 0,
  misc varchar(128) default NULL,
  last_visit int(10) default 0,
  last_activity int(10) default 0,
  dst_in_use tinyint(1) default 0,
  coppa_user tinyint(1) default 0,
  mod_posts varchar(100) NOT NULL default '0',
  auto_track varchar(50) default '0',
  temp_ban varchar(100) default '0',
  login_anonymous char(3) NOT NULL default '0&0',
  ignored_users text,
  mgroup_others varchar(255) NOT NULL default '',
  org_perm_id varchar(255) NOT NULL default '',
  member_login_key varchar(32) NOT NULL default '',
  member_login_key_expire int(10) NOT NULL default 0,
  has_blog TEXT NULL,
  blogs_recache tinyint(1) NULL DEFAULT NULL,
  has_gallery tinyint(1) NOT NULL default 0,
  members_auto_dst tinyint(1) NOT NULL default 1,
  members_display_name varchar(255) NOT NULL default '',
  members_seo_name varchar(255) NOT NULL default '',
  members_created_remote tinyint(1) NOT NULL default 0,
  members_cache mediumtext,
  members_disable_pm int(1) NOT NULL default 0,
  members_l_display_name varchar(255) NOT NULL default '',
  members_l_username varchar(255) NOT NULL default '',
  failed_logins text,
  failed_login_count smallint(3) NOT NULL default 0,
  members_profile_views int(10) unsigned NOT NULL default 0,
  members_pass_hash varchar(32) NOT NULL default '',
  members_pass_salt varchar(5) NOT NULL default '',
  member_banned tinyint(1) NOT NULL default 0,
  member_uploader varchar(32) NOT NULL default 'default',
  members_bitoptions int(10) unsigned NOT NULL default 0,
  fb_uid bigint(20) unsigned NOT NULL default 0,
  fb_emailhash varchar(60) NOT NULL default '',
  fb_lastsync int(10) NOT NULL default 0,
  members_day_posts VARCHAR(32) NOT NULL default '0,0',
  live_id VARCHAR( 32 ) NULL,
  twitter_id VARCHAR(255) NOT NULL DEFAULT '',
  twitter_token VARCHAR(255) NOT NULL DEFAULT '',
  twitter_secret VARCHAR(255) NOT NULL DEFAULT '',
  notification_cnt MEDIUMINT NOT NULL DEFAULT 0,
  tc_lastsync INT(10) NOT NULL default 0,
  fb_session VARCHAR(200) NOT NULL default '',
  fb_token TEXT NULL, 
  ips_mobile_token VARCHAR( 64 ) NULL,
  unacknowledged_warnings TINYINT(1) DEFAULT NULL,
  PRIMARY KEY  (member_id),
  KEY members_l_display_name (members_l_display_name),
  KEY members_l_username (members_l_username),
  KEY mgroup ( member_group_id , member_id ),
  KEY member_groups ( member_group_id, mgroup_others ),
  KEY bday_day (bday_day),
  KEY bday_month (bday_month),
  KEY member_banned (member_banned),
  KEY members_bitoptions (members_bitoptions),
  KEY ip_address (ip_address),
  KEY failed_login_count (failed_login_count),
  KEY joined (joined),
  KEY fb_uid (fb_uid),
  KEY twitter_id (twitter_id),
  KEY email (email),
  KEY blogs_recache (blogs_recache)
);";


$TABLE[] = "CREATE TABLE members_partial (
  partial_id int(10) NOT NULL auto_increment,
  partial_member_id int(8) NOT NULL default 0,
  partial_date int(10) NOT NULL default 0,
  partial_email_ok int(1) NOT NULL default 0,
  PRIMARY KEY  (partial_id),
  KEY partial_member_id (partial_member_id)
);";

$TABLE[] = "CREATE TABLE members_warn_actions (
  wa_id int(11) unsigned NOT NULL AUTO_INCREMENT,
  wa_points int(32) DEFAULT NULL,
  wa_mq int(2) DEFAULT NULL,
  wa_mq_unit char(1) DEFAULT NULL,
  wa_rpa int(2) DEFAULT NULL,
  wa_rpa_unit char(1) DEFAULT NULL,
  wa_suspend int(2) DEFAULT NULL,
  wa_suspend_unit char(1) DEFAULT NULL,
  wa_ban_group tinyint(1) DEFAULT NULL,
  wa_override tinyint(1) DEFAULT NULL,
  PRIMARY KEY (wa_id),
  KEY wa_points (wa_points)
);";

$TABLE[] = "CREATE TABLE members_warn_logs (
  wl_id int(11) unsigned NOT NULL AUTO_INCREMENT,
  wl_member mediumint(8) DEFAULT NULL,
  wl_moderator mediumint(8) DEFAULT NULL,
  wl_date int(10) DEFAULT NULL,
  wl_reason int(10) DEFAULT NULL,
  wl_points int(5) DEFAULT NULL,
  wl_note_member text,
  wl_note_mods text,
  wl_mq int(2) DEFAULT NULL,
  wl_mq_unit char(1) DEFAULT NULL,
  wl_rpa int(2) DEFAULT NULL,
  wl_rpa_unit char(1) DEFAULT NULL,
  wl_suspend int(2) DEFAULT NULL,
  wl_suspend_unit char(1) DEFAULT NULL,
  wl_ban_group tinyint(1) DEFAULT NULL,
  wl_expire int(2) DEFAULT NULL,
  wl_expire_unit char(1) DEFAULT NULL,
  wl_acknowledged tinyint(1) DEFAULT NULL,
  wl_content_app varchar(32) DEFAULT NULL,
  wl_content_id1 varchar(32) DEFAULT NULL,
  wl_content_id2 varchar(32) DEFAULT NULL,
  wl_expire_date INT(10) DEFAULT NULL,
  PRIMARY KEY (wl_id),
  KEY wl_member (wl_member),
  KEY wl_moderator (wl_moderator),
  KEY wl_date (wl_member,wl_date),
  KEY content (wl_content_app,wl_content_id1,wl_content_id2),
  KEY wl_expire_date (wl_expire_date)
);";

$TABLE[] = "CREATE TABLE members_warn_reasons (
  wr_id int(11) unsigned NOT NULL AUTO_INCREMENT,
  wr_name varchar(255) DEFAULT NULL,
  wr_points float DEFAULT NULL,
  wr_points_override tinyint(1) DEFAULT NULL,
  wr_remove int(2) DEFAULT NULL,
  wr_remove_unit char(1) DEFAULT NULL,
  wr_remove_override tinyint(1) DEFAULT NULL,
  wr_order int(10) DEFAULT NULL,
  PRIMARY KEY (wr_id),
  KEY wr_order (wr_order)
);";

$TABLE[] = "CREATE TABLE member_status_actions (
  action_id int(10) NOT NULL AUTO_INCREMENT,
  action_status_id int(10) unsigned NOT NULL DEFAULT 0,
  action_reply_id int(10) unsigned NOT NULL DEFAULT 0,
  action_member_id int(10) unsigned NOT NULL DEFAULT 0,
  action_date int(10) unsigned NOT NULL DEFAULT 0,
  action_key varchar(200) NOT NULL DEFAULT '',
  action_status_owner int(10) unsigned NOT NULL DEFAULT 0,
  action_app varchar(255) NOT NULL DEFAULT 'members',
  action_custom_text text,
  action_custom int(1) NOT NULL DEFAULT 0,
  action_custom_url text,
  PRIMARY KEY (action_id),
  KEY action_status_id (action_status_id),
  KEY action_member_id ( action_member_id , action_date ),
  KEY action_date (action_date),
  KEY action_custom ( action_custom , action_date )
);";

$TABLE[] = "CREATE TABLE member_status_replies (
  reply_id int(10) NOT NULL AUTO_INCREMENT,
  reply_status_id int(10) unsigned NOT NULL DEFAULT 0,
  reply_member_id int(10) unsigned NOT NULL DEFAULT 0,
  reply_date int(10) unsigned NOT NULL DEFAULT 0,
  reply_content text,
  PRIMARY KEY (reply_id),
  KEY reply_status_id (reply_status_id),
  KEY reply_member_id (reply_member_id),
  KEY reply_status_count (reply_status_id,reply_member_id),
  KEY reply_date (reply_date)
);";

$TABLE[] = "CREATE TABLE member_status_updates (
  status_id int(10) NOT NULL AUTO_INCREMENT,
  status_member_id int(10) unsigned NOT NULL DEFAULT 0,
  status_date int(10) unsigned NOT NULL DEFAULT 0,
  status_content text,
  status_replies int(10) unsigned NOT NULL DEFAULT 0,
  status_last_ids text,
  status_is_latest int(1) NOT NULL DEFAULT 0,
  status_is_locked int(1) NOT NULL DEFAULT 0,
  status_hash varchar(32) NOT NULL DEFAULT '',
  status_imported int(1) NOT NULL DEFAULT 0,
  status_creator varchar(100) NOT NULL DEFAULT '',
  status_author_id INT(10) NOT NULL DEFAULT '0',
  status_author_ip VARCHAR(46) NOT NULL DEFAULT '',
  status_approved INT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (status_id),
  KEY status_date (status_date),
  KEY s_hash (status_member_id , status_hash , status_date),
  KEY status_is_latest ( status_is_latest , status_date ),
  KEY status_author_lookup (status_author_id, status_member_id, status_date),
  KEY status_member_id ( status_member_id, status_approved, status_date )
);";

$TABLE[] = "CREATE TABLE permission_index (
  perm_id int(10) unsigned NOT NULL auto_increment,
  app varchar(32) NOT NULL,
  perm_type varchar(32) NOT NULL,
  perm_type_id int(10) unsigned NOT NULL,
  perm_view text NOT NULL,
  perm_2 text NULL DEFAULT NULL,
  perm_3 text NULL DEFAULT NULL,
  perm_4 text NULL DEFAULT NULL,
  perm_5 text NULL DEFAULT NULL,
  perm_6 text NULL DEFAULT NULL,
  perm_7 text NULL DEFAULT NULL,
  owner_only tinyint(1) NOT NULL default 0,
  friend_only tinyint(1) NOT NULL default 0,
  authorized_users varchar(255) default NULL,
  PRIMARY KEY  (perm_id),
  KEY perm_index (perm_type,perm_type_id),
  KEY perm_type (app,perm_type,perm_type_id)
);";
$TABLE[] = "CREATE TABLE pfields_content (
  member_id mediumint(8) NOT NULL default 0,
  field_1 text,
  field_2 text,
  field_3 text,
  field_4 text,
  field_5 text,
  field_6 text,
  field_7 text,
  field_8 text,
  field_9 text,
  field_10 text,
  PRIMARY KEY  (member_id)
);";
$TABLE[] = "CREATE TABLE pfields_data (
  pf_id smallint(5) NOT NULL auto_increment,
  pf_title varchar(250) NOT NULL default '',
  pf_desc varchar(250) NOT NULL default '',
  pf_content text,
  pf_type varchar(250) NOT NULL default '',
  pf_not_null tinyint(1) NOT NULL default 0,
  pf_member_hide tinyint(1) NOT NULL default 0,
  pf_max_input smallint(6) NOT NULL default 0,
  pf_member_edit tinyint(1) NOT NULL default 0,
  pf_position smallint(6) NOT NULL default 0,
  pf_show_on_reg tinyint(1) NOT NULL default 0,
  pf_input_format text,
  pf_admin_only tinyint(1) NOT NULL default 0,
  pf_topic_format text,
  pf_group_id mediumint(4) unsigned NOT NULL,
  pf_icon varchar(255) default NULL,
  pf_key varchar(255) default NULL,
  pf_search_type varchar(5) NOT NULL default 'loose',
  pf_filtering TINYINT( 1 ) NOT NULL DEFAULT '0',
  PRIMARY KEY  (pf_id)
);";
$TABLE[] = "CREATE TABLE pfields_groups (
  pf_group_id mediumint(4) unsigned NOT NULL auto_increment,
  pf_group_name varchar(255) NOT NULL,
  pf_group_key varchar(255) NOT NULL,
  PRIMARY KEY  (pf_group_id)
);";

$TABLE[] = "CREATE TABLE profile_friends (
  friends_id int(10) NOT NULL auto_increment,
  friends_member_id int(10) unsigned NOT NULL default 0,
  friends_friend_id int(10) unsigned NOT NULL default 0,
  friends_approved tinyint(1) NOT NULL default 0,
  friends_added int(10) unsigned NOT NULL default 0,
  PRIMARY KEY  (friends_id),
  KEY my_friends (friends_member_id,friends_friend_id)
);";
$TABLE[] = "CREATE TABLE profile_friends_flood (
  friends_id int(10) NOT NULL auto_increment,
  friends_member_id int(10) unsigned NOT NULL default 0,
  friends_friend_id int(10) unsigned NOT NULL default 0,
  friends_removed int(10) unsigned NOT NULL default 0,
  PRIMARY KEY  (friends_id),
  KEY my_friends (friends_member_id,friends_friend_id)
);";
$TABLE[] = "CREATE TABLE profile_portal (
  pp_member_id int(10) NOT NULL default 0,
  pp_last_visitors text,
  pp_rating_hits int(10) unsigned NOT NULL default 0,
  pp_rating_value int(10) unsigned NOT NULL default 0,
  pp_rating_real int(10) unsigned NOT NULL default 0,
  pp_main_photo varchar(255) NOT NULL default '',
  pp_main_width int(5) unsigned NOT NULL default 0,
  pp_main_height int(5) unsigned NOT NULL default 0,
  pp_thumb_photo varchar(255) NOT NULL default '',
  pp_thumb_width int(5) unsigned NOT NULL default 0,
  pp_thumb_height int(5) unsigned NOT NULL default 0,
  pp_setting_moderate_comments tinyint(1) NOT NULL default 0,
  pp_setting_moderate_friends tinyint(1) NOT NULL default 0,
  pp_setting_count_friends int(2) NOT NULL default 0,
  pp_setting_count_comments int(2) NOT NULL default 0,
  pp_setting_count_visitors tinyint(1) NOT NULL default 0,
  pp_about_me mediumtext,
  pp_reputation_points int(10) NOT NULL default 0,
  pp_gravatar VARCHAR(255) NOT NULL DEFAULT '',
  pp_photo_type VARCHAR(20) NOT NULL DEFAULT '',
  signature text,
  avatar_location varchar(255) default NULL,
  avatar_size varchar(9) NOT NULL default '0',
  avatar_type varchar(15) default NULL,
  pconversation_filters text,
  fb_photo text,
  fb_photo_thumb text,
  fb_bwoptions int(10) unsigned NOT NULL default 0,
  tc_last_sid_import VARCHAR(50) NULL DEFAULT '0',
  tc_photo text,
  tc_bwoptions int(10) unsigned NOT NULL default 0,
  pp_customization mediumtext,
  pp_profile_update INT(10) UNSIGNED NOT NULL default '0',
  PRIMARY KEY  (pp_member_id)
);";

$TABLE[] = "CREATE TABLE profile_portal_views (
  views_member_id int(10) NOT NULL default 0,
  KEY views_member_id (views_member_id)
);";
$TABLE[] = "CREATE TABLE profile_ratings (
  rating_id int(10) NOT NULL auto_increment,
  rating_for_member_id int(10) NOT NULL default 0,
  rating_by_member_id int(10) NOT NULL default 0,
  rating_ip_address varchar(46) NOT NULL default '',
  rating_value int(2) NOT NULL default 0,
  PRIMARY KEY  (rating_id),
  KEY rating_for_member_id (rating_for_member_id),
  KEY rating_ip_address (rating_ip_address)
);";
$TABLE[] = "CREATE TABLE question_and_answer (
  qa_id int(11) NOT NULL auto_increment,
  qa_question text,
  qa_answers text,
  PRIMARY KEY  (qa_id)
);";
$TABLE[] = "CREATE TABLE rc_classes (
  com_id smallint(4) NOT NULL auto_increment,
  onoff tinyint(1) NOT NULL default 0,
  class_title varchar(255) NOT NULL default '',
  class_desc text NOT NULL,
  author varchar(255) NOT NULL default '',
  author_url varchar(255) NOT NULL default '',
  pversion varchar(255) NOT NULL default '',
  my_class varchar(100) NOT NULL default '',
  group_can_report TEXT NULL default NULL,
  mod_group_perm TEXT NULL default NULL,
  extra_data text NOT NULL,
  lockd tinyint(1) NOT NULL default 0,
  app VARCHAR( 32 ) NOT NULL,
  PRIMARY KEY  (com_id),
  KEY onoff (onoff , mod_group_perm ( 255 ))
);";
$TABLE[] = "CREATE TABLE rc_comments (
  id int(10) NOT NULL auto_increment,
  rid int(11) NOT NULL default 0,
  comment text NOT NULL,
  comment_by mediumint(8) NOT NULL default 0,
  comment_date int(10) NOT NULL default 0,
  approved TINYINT NOT NULL DEFAULT '1',
  edit_date INT NOT NULL DEFAULT '0',
  author_name VARCHAR( 255 ) NULL DEFAULT NULL,
  ip_address VARCHAR( 46 ) NULL DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY report_comments ( rid, approved, comment_date )
);";
$TABLE[] = "CREATE TABLE rc_modpref (
  mem_id mediumint(8) NOT NULL default 0,
  rss_key varchar(32) NOT NULL default '',
  rss_cache mediumtext NOT NULL,
  PRIMARY KEY  (mem_id)
);";
$TABLE[] = "CREATE TABLE rc_reports (
  id int(10) NOT NULL auto_increment,
  rid int(11) NOT NULL default 0,
  report MEDIUMTEXT NOT NULL,
  report_by mediumint(8) NOT NULL default 0,
  date_reported int(10) NOT NULL default 0,
  PRIMARY KEY  (id),
  KEY rid (rid)
);";
$TABLE[] = "CREATE TABLE rc_reports_index (
  id int(11) NOT NULL auto_increment,
  uid varchar(32) NOT NULL default '',
  title varchar(255) NOT NULL default '',
  status smallint(2) NOT NULL default 1,
  url varchar(255) NOT NULL default '',
  img_preview varchar(255) NOT NULL default '',
  rc_class smallint(3) NOT NULL default 0,
  updated_by mediumint(8) NOT NULL default 0,
  date_updated int(10) NOT NULL default 0,
  date_created int(10) NOT NULL default 0,
  exdat1 int(10) NOT NULL default 0,
  exdat2 int(10) NOT NULL default 0,
  exdat3 int(10) NOT NULL default 0,
  num_reports smallint(4) NOT NULL default 0,
  num_comments smallint(4) NOT NULL default 0,
  seoname varchar(255) default NULL,
  seotemplate varchar(255) default NULL,
  PRIMARY KEY  (id),
  KEY uid (uid),
  KEY (status)
);";
$TABLE[] = "CREATE TABLE rc_status (
  status tinyint(2) NOT NULL auto_increment,
  title varchar(100) NOT NULL default '',
  points_per_report smallint(4) NOT NULL default 1,
  minutes_to_apoint double NOT NULL default '5',
  is_new tinyint(1) NOT NULL default 0,
  is_complete tinyint(1) NOT NULL default 0,
  is_active tinyint(1) NOT NULL default 0,
  rorder smallint(3) NOT NULL default 0,
  PRIMARY KEY  (status)
);";
$TABLE[] = "CREATE TABLE rc_status_sev (
  id smallint(4) NOT NULL auto_increment,
  status tinyint(2) NOT NULL default 0,
  points smallint(4) NOT NULL default 0,
  img varchar(255) NOT NULL default '',
  is_png tinyint(1) NOT NULL default 0,
  width smallint(3) NOT NULL default '16',
  height smallint(3) NOT NULL default '16',
  PRIMARY KEY  (id),
  KEY status (status,points)
);";
$TABLE[] = "CREATE TABLE reputation_cache (
  id bigint(10) unsigned NOT NULL auto_increment,
  app varchar(32) NOT NULL,
  type varchar(32) NOT NULL,
  type_id int(10) unsigned NOT NULL,
  rep_points int(10) NOT NULL DEFAULT '0',
  rep_like_cache MEDIUMTEXT,
  cache_date INT(10) NOT NULL DEFAULT 0,
  PRIMARY KEY  (id),
  UNIQUE KEY app (app, type, type_id),
  KEY cache_date (cache_date),
  KEY type (type, type_id)
);";
$TABLE[] = "CREATE TABLE reputation_index (
  id bigint(10) unsigned NOT NULL auto_increment,
  member_id mediumint(8) unsigned NOT NULL,
  app varchar(32) NOT NULL,
  type varchar(32) NOT NULL,
  type_id int(10) unsigned NOT NULL,
  rep_date int(10) unsigned NOT NULL,
  rep_msg text NOT NULL,
  rep_rating tinyint(1) NOT NULL,
  PRIMARY KEY  (id),
  KEY app (app,type,type_id,member_id),
  KEY member_rep ( member_id , rep_rating , rep_date )
);";
$TABLE[] = "CREATE TABLE reputation_levels (
  level_id mediumint(8) unsigned NOT NULL auto_increment,
  level_points int(10) NOT NULL,
  level_title varchar(255) NOT NULL,
  level_image varchar(255) NOT NULL,
  PRIMARY KEY  (level_id)
);";

$TABLE[] = "CREATE TABLE reputation_totals (
  rt_key CHAR(32) NOT NULL,
  rt_app_type CHAR(32) NOT NULL,
  rt_total INT(10) NOT NULL DEFAULT 0,
  rt_type_id INT(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (rt_key),
  INDEX rt_app_type (rt_app_type, rt_total)
);";

$TABLE[] = "CREATE TABLE rss_export (
  rss_export_id int(10) NOT NULL auto_increment,
  rss_export_enabled tinyint(1) NOT NULL default 0,
  rss_export_title varchar(255) NOT NULL default '',
  rss_export_desc varchar(255) NOT NULL default '',
  rss_export_image varchar(255) NOT NULL default '',
  rss_export_forums text,
  rss_export_include_post tinyint(1) NOT NULL default 0,
  rss_export_count smallint(3) NOT NULL default 0,
  rss_export_cache_time smallint(3) NOT NULL default '30',
  rss_export_cache_last int(10) NOT NULL default 0,
  rss_export_cache_content mediumtext,
  rss_export_sort varchar(4) NOT NULL default 'DESC',
  rss_export_order varchar(20) NOT NULL default 'start_date',
  PRIMARY KEY  (rss_export_id)
);";
$TABLE[] = "CREATE TABLE rss_import (
  rss_import_id int(10) NOT NULL auto_increment,
  rss_import_enabled tinyint(1) NOT NULL default 0,
  rss_import_title varchar(255) NOT NULL default '',
  rss_import_url varchar(255) NOT NULL default '',
  rss_import_forum_id int(10) NOT NULL default 0,
  rss_import_mid mediumint(8) NOT NULL default 0,
  rss_import_pergo smallint(3) NOT NULL default 0,
  rss_import_time smallint(3) NOT NULL default 0,
  rss_import_last_import int(10) NOT NULL default 0,
  rss_import_showlink varchar(255) NOT NULL default '0',
  rss_import_topic_open tinyint(1) NOT NULL default 0,
  rss_import_topic_hide tinyint(1) NOT NULL default 0,
  rss_import_topic_pre varchar(50) NOT NULL default '',
  rss_import_allow_html tinyint(1) NOT NULL default 0,
  rss_import_auth tinyint(1) NOT NULL default 0,
  rss_import_auth_user varchar(255) NOT NULL default 'Not Needed',
  rss_import_auth_pass varchar(255) NOT NULL default 'Not Needed',
  PRIMARY KEY  (rss_import_id),
  KEY rss_grab ( rss_import_enabled , rss_import_last_import )
);";

$TABLE[] = "CREATE TABLE rss_imported (
  rss_imported_guid char(32) NOT NULL default '0',
  rss_imported_tid int(10) NOT NULL default 0,
  rss_imported_impid int(10) NOT NULL default 0,
  PRIMARY KEY  (rss_imported_guid),
  KEY rss_imported_impid (rss_imported_impid)
);";

$TABLE[] = "CREATE TABLE search_sessions (
	session_id			VARCHAR(32) NOT NULL DEFAULT '',
	session_created		INT(10) NOT NULL DEFAULT 0,
	session_updated 	INT(10) NOT NULL DEFAULT 0,
	session_member_id	INT(10) NOT NULL DEFAULT 0,
	session_data		MEDIUMTEXT,
	KEY session_updated (session_updated),
	PRIMARY KEY (session_id)
);";
$TABLE[] = "CREATE TABLE sessions (
  id varchar(60) NOT NULL default '0',
  member_name varchar(255) default NULL,
  seo_name varchar(255) NOT NULL default '',
  member_id mediumint(8) NOT NULL default 0,
  ip_address varchar(46) default NULL,
  browser varchar(200) NOT NULL default '',
  running_time int(10) default NULL,
  login_type tinyint(1) default 0,
  member_group smallint(3) default NULL,
  in_error tinyint(1) NOT NULL default 0,
  location_1_type varchar(255) NOT NULL default '',
  location_1_id int(10) NOT NULL default 0,
  location_2_type varchar(255) NOT NULL default '',
  location_2_id int(10) NOT NULL default 0,
  location_3_type varchar(255) NOT NULL default '',
  location_3_id int(10) NOT NULL default 0,
  current_appcomponent varchar(100) NOT NULL default '',
  current_module varchar(100) NOT NULL default '',
  current_section varchar(100) NOT NULL default '',
  uagent_key varchar(200) NOT NULL default '',
  uagent_version varchar(100) NOT NULL default '',
  uagent_type varchar(200) NOT NULL default '',
  uagent_bypass int(1) NOT NULL default 0,
  search_thread_id int(11) NOT NULL default 0,
  search_thread_time int NOT NULL default 0,
  session_msg_id INT(10) NOT NULL DEFAULT 0,
  PRIMARY KEY  (id),
  KEY location1 (location_1_type,location_1_id),
  KEY location2 (location_2_type,location_2_id),
  KEY location3 (location_3_type,location_3_id),
  KEY running_time (running_time),
  KEY member_id (member_id)
);";

$TABLE[] = "CREATE TABLE skin_cache (
  cache_id int(10) NOT NULL auto_increment,
  cache_updated int(10) NOT NULL default 0,
  cache_type varchar(200) NOT NULL default '',
  cache_set_id int(10) NOT NULL default 0,
  cache_key_1 varchar(200) NOT NULL default '',
  cache_value_1 varchar(200) NOT NULL default '',
  cache_key_2 varchar(200) NOT NULL default '',
  cache_value_2 varchar(200) NOT NULL default '',
  cache_value_3 varchar(200) NOT NULL default '',
  cache_content mediumtext NOT NULL,
  cache_key_3 varchar(200) NOT NULL default '',
  cache_key_4 varchar(200) NOT NULL default '',
  cache_value_4 varchar(200) NOT NULL default '',
  cache_key_5 varchar(200) NOT NULL default '',
  cache_value_5 varchar(200) NOT NULL default '',
  cache_key_6 varchar(200) NOT NULL default '',
  cache_value_6 varchar(200) NOT NULL default '',
  PRIMARY KEY  (cache_id),
  KEY cache_type (cache_type),
  KEY cache_set_id (cache_set_id)
);";
$TABLE[] = "CREATE TABLE skin_collections (
  set_id int(10) NOT NULL auto_increment,
  set_name varchar(200) NOT NULL default '',
  set_key varchar(100) NOT NULL default '',
  set_parent_id int(5) NOT NULL default '-1',
  set_parent_array mediumtext,
  set_child_array mediumtext,
  set_permissions text NULL,
  set_is_default int(1) NOT NULL default 0,
  set_author_name varchar(255) NOT NULL default '',
  set_author_url varchar(255) NOT NULL default '',
  set_image_dir varchar(255) NOT NULL default 'default',
  set_emo_dir varchar(255) NOT NULL default 'default',
  set_css_inline int(1) NOT NULL default 0,
  set_css_groups text,
  set_added int(10) NOT NULL default 0,
  set_updated int(10) NOT NULL default 0,
  set_output_format varchar(200) NOT NULL default 'html',
  set_locked_uagent mediumtext,
  set_hide_from_list int(1) NOT NULL default 0,
  set_minify	INT(1) NOT NULL default 0,
  set_master_key VARCHAR(100) NOT NULL DEFAULT '',
  set_order INT(10) NOT NULL,
  set_by_skin_gen INT(1) NOT NULL DEFAULT 0,
  set_skin_gen_data MEDIUMTEXT,
  PRIMARY KEY  (set_id),
  KEY parent_set_id ( set_parent_id, set_id ),
  KEY set_is_default (set_is_default),
  KEY set_order (set_order)
);";
$TABLE[] = "CREATE TABLE skin_css (
  css_id int(10) NOT NULL auto_increment,
  css_set_id int(10) NOT NULL default 0,
  css_updated int(10) NOT NULL default 0,
  css_group varchar(255) NOT NULL default '0',
  css_content mediumtext,
  css_position int(10) NOT NULL default 0,
  css_added_to int(10) NOT NULL default 0,
  css_app varchar(200) NOT NULL default '0',
  css_app_hide int(1) NOT NULL default 0,
  css_attributes text,
  css_modules varchar(250) NOT NULL default '',
  css_removed int(1) NOT NULL default 0,
  css_master_key VARCHAR(100) NOT NULL DEFAULT '',
  PRIMARY KEY  (css_id),
  KEY css_set_id (css_set_id)
);";

$TABLE[] = "CREATE TABLE skin_generator_sessions (
	sg_session_id	VARCHAR(32) NOT NULL DEFAULT '',
	sg_member_id	INT(10) NOT NULL DEFAULT 0,
	sg_skin_set_id	INT(10) NOT NULL DEFAULT 0,
	sg_date_start	INT(10) NOT NULL DEFAULT 0,
	sg_data			MEDIUMTEXT,
	PRIMARY KEY (sg_session_id)
);";

$TABLE[] = "CREATE TABLE skin_merge_session (
	merge_id				INT(10) NOT NULL auto_increment,
	merge_date				INT(10) NOT NULL DEFAULT 0,
	merge_set_id			INT(10) NOT NULL DEFAULT 0,
	merge_master_key		VARCHAR(200) NOT NULL DEFAULT '',
	merge_old_version		VARCHAR(200) NOT NULL DEFAULT '',
	merge_new_version		VARCHAR(200) NOT NULL DEFAULT '',
	merge_templates_togo	INT(10) NOT NULL DEFAULT 0,
	merge_css_togo			INT(10) NOT NULL DEFAULT 0,
	merge_templates_done	INT(10) NOT NULL DEFAULT 0,
	merge_css_done			INT(10) NOT NULL DEFAULT 0,
	merge_m_templates_togo	INT(10) NOT NULL DEFAULT 0,
	merge_m_css_togo		INT(10) NOT NULL DEFAULT 0,
	merge_m_templates_done	INT(10) NOT NULL DEFAULT 0,
	merge_m_css_done		INT(10) NOT NULL DEFAULT 0,
	merge_diff_done			INT(1) NOT NULL DEFAULT 0,
	PRIMARY KEY (merge_id)
);";

$TABLE[] = "CREATE TABLE skin_merge_changes (
	change_id				INT(10) NOT NULL auto_increment,
	change_key				VARCHAR(255) NOT NULL DEFAULT '',
	change_session_id		INT(10) NOT NULL DEFAULT 0,
	change_updated			INT(10) NOT NULL DEFAULT 0,
	change_data_group		VARCHAR(255) NOT NULL DEFAULT '',
	change_data_title		VARCHAR(255) NOT NULL DEFAULT '',
	change_data_content		MEDIUMTEXT,
	change_data_type		VARCHAR(10) NOT NULL DEFAULT 'template',
	change_is_new			INT(1) NOT NULL DEFAULT 0,
	change_is_diff			INT(1) NOT NULL DEFAULT 0,
	change_can_merge		INT(1) NOT NULL DEFAULT 0,
	change_merge_content	MEDIUMTEXT,
	change_is_conflict		INT(1) NOT NULL DEFAULT 0,
	change_final_content	MEDIUMTEXT,
	change_changes_applied	INT(1) NOT NULL DEFAULT 0,
	change_original_content MEDIUMTEXT,
	PRIMARY KEY (change_id),
	KEY (change_key, change_data_type)
);";

$TABLE[] = "CREATE TABLE skin_replacements (
  replacement_id int(10) NOT NULL auto_increment,
  replacement_key varchar(255) NOT NULL default '',
  replacement_content text,
  replacement_set_id int(10) NOT NULL default 0,
  replacement_added_to int(10) NOT NULL default 0,
  replacement_master_key VARCHAR(100) NOT NULL DEFAULT '',
  PRIMARY KEY  (replacement_id),
  KEY replacement_set_id (replacement_set_id)
);";


$TABLE[] = "CREATE TABLE skin_templates (
  template_id int(10) NOT NULL auto_increment,
  template_set_id int(10) NOT NULL default 0,
  template_group varchar(255) NOT NULL default '',
  template_content mediumtext,
  template_name varchar(255) default NULL,
  template_data text,
  template_updated int(10) NOT NULL default 0,
  template_removable int(4) NOT NULL default 0,
  template_added_to int(10) NOT NULL default 0,
  template_user_added  INT(0) NOT NULL DEFAULT 0,
  template_user_edited INT(0) NOT NULL DEFAULT 0,
  template_master_key VARCHAR(100) NOT NULL DEFAULT '',
  PRIMARY KEY  (template_id),
  KEY template_set_id (template_set_id),
  KEY template_master_key (template_master_key),
  KEY template_name (template_name(100), template_group(100))
);";

$TABLE[] = "CREATE TABLE skin_templates_previous (
  p_template_id int(10) NOT NULL AUTO_INCREMENT,
  p_template_group varchar(255) NOT NULL DEFAULT '',
  p_template_content MEDIUMTEXT,
  p_template_name varchar(255) DEFAULT NULL,
  p_template_data TEXT,
  p_template_master_key	VARCHAR(100) NOT NULL DEFAULT '',
  p_template_long_version	VARCHAR(100) NOT NULL DEFAULT '',
  p_template_human_version	VARCHAR(100) NOT NULL DEFAULT '',
  PRIMARY KEY (p_template_id)
);";

$TABLE[] = "CREATE TABLE skin_css_previous (
  p_css_id int(10) NOT NULL AUTO_INCREMENT,
  p_css_group varchar(255) NOT NULL DEFAULT '0',
  p_css_content mediumtext,
  p_css_app varchar(200) NOT NULL DEFAULT '0',
  p_css_attributes text,
  p_css_modules varchar(250) NOT NULL DEFAULT '',
  p_css_master_key VARCHAR(100) NOT NULL DEFAULT '',
  p_css_long_version	VARCHAR(100) NOT NULL DEFAULT '',
  p_css_human_version	VARCHAR(100) NOT NULL DEFAULT '',
  PRIMARY KEY (p_css_id)
);";

$TABLE[] = "CREATE TABLE skin_templates_cache (
  template_id varchar(32) NOT NULL default '',
  template_group_name varchar(255) NOT NULL default '',
  template_group_content mediumtext,
  template_set_id int(10) NOT NULL default 0,
  PRIMARY KEY  (template_id),
  KEY template_set_id (template_set_id),
  KEY template_group_name (template_group_name)
);";

$TABLE[] = "CREATE TABLE skin_url_mapping (
  map_id int(10) NOT NULL auto_increment,
  map_title varchar(200) NOT NULL default '',
  map_match_type varchar(10) NOT NULL default 'contains',
  map_url varchar(200) NOT NULL default '',
  map_skin_set_id int(10) unsigned NOT NULL default 0,
  map_date_added int(10) unsigned NOT NULL default 0,
  PRIMARY KEY  (map_id)
);";
$TABLE[] = "CREATE TABLE spider_logs (
  sid int(10) NOT NULL auto_increment,
  bot varchar(255) NOT NULL default '',
  query_string text,
  entry_date int(10) NOT NULL default 0,
  ip_address varchar(46) NOT NULL default '',
  request_addr TEXT NULL DEFAULT NULL,
  PRIMARY KEY  (sid),
  KEY entry_date (entry_date)
);";
$TABLE[] = "CREATE TABLE tags_index (
  id bigint(10) unsigned NOT NULL auto_increment,
  app varchar(255) NOT NULL,
  tag varchar(255) NOT NULL,
  type varchar(32) NOT NULL,
  type_id bigint(10) unsigned NOT NULL,
  type_2 varchar(32) NOT NULL,
  type_id_2 bigint(10) unsigned NOT NULL,
  updated int(10) unsigned NOT NULL,
  member_id mediumint(8) unsigned NOT NULL,
  tag_hidden INT(1) NOT NULL default 0,
  PRIMARY KEY  (id),
  KEY app (app),
  KEY tag_grab (app, type, type_id, type_2, type_id_2, tag_hidden)
);";
	
$TABLE[] = "CREATE TABLE task_logs (
  log_id int(10) NOT NULL auto_increment,
  log_title varchar(255) NOT NULL default '',
  log_date int(10) NOT NULL default 0,
  log_ip varchar(46) NOT NULL default '0',
  log_desc text,
  PRIMARY KEY  (log_id),
  KEY log_date (log_date)
);";
$TABLE[] = "CREATE TABLE task_manager (
  task_id int(10) NOT NULL auto_increment,
  task_title varchar(255) NOT NULL default '',
  task_file varchar(255) NOT NULL default '',
  task_next_run int(10) NOT NULL default 0,
  task_week_day smallint(1) NOT NULL default '-1',
  task_month_day smallint(2) NOT NULL default '-1',
  task_hour smallint(2) NOT NULL default '-1',
  task_minute smallint(2) NOT NULL default '-1',
  task_cronkey varchar(32) NOT NULL default '',
  task_log tinyint(1) NOT NULL default 0,
  task_description text,
  task_enabled tinyint(1) NOT NULL default 1,
  task_key varchar(30) NOT NULL default '',
  task_safemode tinyint(1) NOT NULL default 0,
  task_locked int(10) NOT NULL default 0,
  task_application varchar(100) NOT NULL,
  PRIMARY KEY  (task_id),
  UNIQUE task_key ( task_application , task_key ),
  KEY task_next_run (task_enabled , task_next_run)
);";

$TABLE[] = "CREATE TABLE template_sandr (
  sandr_session_id int(10) NOT NULL auto_increment,
  sandr_set_id int(10) NOT NULL default 0,
  sandr_search_only int(1) NOT NULL default 0,
  sandr_search_all int(1) NOT NULL default 0,
  sandr_search_for text,
  sandr_replace_with text,
  sandr_is_regex int(1) NOT NULL default 0,
  sandr_template_count int(5) NOT NULL default 0,
  sandr_template_processed int(5) NOT NULL default 0,
  sandr_results mediumtext,
  sandr_updated int(10) NOT NULL default 0,
  PRIMARY KEY  (sandr_session_id)
);";

$TABLE[] = "CREATE TABLE titles (
  id smallint(5) NOT NULL auto_increment,
  posts int(10) default NULL,
  title varchar(128) default NULL,
  pips varchar(128) default NULL,
  PRIMARY KEY  (id),
  KEY posts (posts)
);";

$TABLE[] = "CREATE TABLE twitter_connect (
	t_key		VARCHAR(32) NOT NULL default '',
	t_token		VARCHAR(255) NOT NULL default '',
	t_secret	VARCHAR(255) NOT NULL default '',
	t_time		INT(10) NOT NULL default 0
);";

$TABLE[] = "CREATE TABLE upgrade_history (
  upgrade_id int(10) NOT NULL auto_increment,
  upgrade_version_id int(10) NOT NULL default 0,
  upgrade_version_human varchar(200) NOT NULL default '',
  upgrade_date int(10) NOT NULL default 0,
  upgrade_mid int(10) NOT NULL default 0,
  upgrade_notes text NULL default NULL,
  upgrade_app varchar(32) NOT NULL default 'core',
  PRIMARY KEY  (upgrade_id),
  KEY upgrades ( upgrade_app , upgrade_version_id )
);";
$TABLE[] = "CREATE TABLE upgrade_sessions (
  session_id varchar(32) NOT NULL default '',
  session_member_id int(10) NOT NULL default 0,
  session_member_key varchar(32) NOT NULL default '',
  session_start_time int(10) NOT NULL default 0,
  session_current_time int(10) NOT NULL default 0,
  session_ip_address varchar(46) NOT NULL default '',
  session_section varchar(32) NOT NULL default '',
  session_post text,
  session_get text,
  session_data text,
  session_extra text,
  PRIMARY KEY  (session_id)
);";
$TABLE[] = "CREATE TABLE validating (
  vid varchar(32) NOT NULL default '',
  member_id mediumint(8) NOT NULL default 0,
  real_group smallint(3) NOT NULL default 0,
  temp_group smallint(3) NOT NULL default 0,
  entry_date int(10) NOT NULL default 0,
  coppa_user tinyint(1) NOT NULL default 0,
  lost_pass tinyint(1) NOT NULL default 0,
  new_reg tinyint(1) NOT NULL default 0,
  email_chg tinyint(1) NOT NULL default 0,
  ip_address varchar(46) NOT NULL default '0',
  user_verified tinyint(1) NOT NULL default 0,
  prev_email varchar(150) NOT NULL default '0',
  spam_flag TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY  (vid),
  KEY new_reg (new_reg),
  KEY ip_address (ip_address),
  KEY lost_pass (lost_pass),
  KEY coppa_user (coppa_user),
  KEY spam_flag (spam_flag),
  KEY member_id (member_id)
);";
$TABLE[] = "CREATE TABLE voters (
  vid int(10) NOT NULL auto_increment,
  ip_address varchar(46) NOT NULL default '',
  vote_date int(10) NOT NULL default 0,
  tid int(10) NOT NULL default 0,
  member_id int(11) NOT NULL default 0,
  forum_id smallint(5) NOT NULL default 0,
  member_choices text,
  PRIMARY KEY  (vid),
  KEY tid (tid),
  KEY ip_address (ip_address)
);";
$TABLE[] = "CREATE TABLE warn_logs (
  wlog_id int(10) NOT NULL auto_increment,
  wlog_mid mediumint(8) NOT NULL default 0,
  wlog_notes text,
  wlog_contact varchar(250) NOT NULL default 'none',
  wlog_contact_content text,
  wlog_date int(10) NOT NULL default 0,
  wlog_type varchar(6) NOT NULL default 'pos',
  wlog_addedby mediumint(8) NOT NULL default 0,
  PRIMARY KEY  (wlog_id),
  KEY wlog_mid (wlog_mid, wlog_date)
);";
$TABLE[] = "CREATE TABLE spam_service_log (
  id int(10) unsigned NOT NULL auto_increment,
  log_date int(10) unsigned NOT NULL,
  log_code smallint(1) unsigned NOT NULL,
  log_msg varchar(32) NOT NULL,
  email_address varchar(255) NOT NULL,
  ip_address varchar(46) NOT NULL,
  PRIMARY KEY  (id)
);";
$TABLE[] = "CREATE TABLE mobile_app_style (
  id int(10) NOT NULL AUTO_INCREMENT,
  filename varchar(255) NOT NULL,
  hasRetina tinyint(1) NOT NULL DEFAULT '0',
  isInUse tinyint(1) NOT NULL DEFAULT '0',
  lastUpdated int(10) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY filename (filename)
);";
$TABLE[] = "CREATE TABLE mobile_notifications (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  notify_title text NOT NULL,
  notify_date int(10) unsigned NOT NULL,
  member_id mediumint(8) unsigned NOT NULL,
  notify_sent tinyint(3) unsigned NOT NULL DEFAULT 0,
  notify_url text null,
  PRIMARY KEY (id),
  KEY (notify_sent,notify_date)
);";
$TABLE[] = "CREATE TABLE mobile_device_map (
  token varchar(64) NOT NULL DEFAULT '',
  member_id mediumint(8) DEFAULT NULL,
  PRIMARY KEY (token)
);";
?>