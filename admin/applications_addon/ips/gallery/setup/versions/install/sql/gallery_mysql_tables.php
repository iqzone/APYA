<?php
/**
* Installation Schematic File
* Generated on Thu, 19 Feb 2009 08:15:47 +0000 GMT
*/

$DB  = ipsRegistry::DB();
$PRE = ipsRegistry::dbFunctions()->getPrefix();

if ( ! $DB->checkForTable( 'core_geolocation_cache' ) )
{
	$TABLE[] = "CREATE TABLE core_geolocation_cache (
			geocache_key		VARCHAR(32) NOT NULL,
			geocache_lat		VARCHAR(100) NOT NULL,
			geocache_lon		VARCHAR(100) NOT NULL,
			geocache_raw		TEXT,
			geocache_country	VARCHAR(255) NOT NULL DEFAULT '',
			geocache_district	VARCHAR(255) NOT NULL DEFAULT '',
			geocache_district2	VARCHAR(255) NOT NULL DEFAULT '',
			geocache_locality	VARCHAR(255) NOT NULL DEFAULT '',
			geocache_type		VARCHAR(255) NOT NULL DEFAULT '',
			geocache_engine		VARCHAR(255) NOT NULL DEFAULT '',
			geocache_added		INT(10) NOT NULL DEFAULT '0',
			geocache_short		TEXT,
			PRIMARY KEY	geocache_key (geocache_key),
			KEY geo_lat_lon (geocache_lat, geocache_lon)
		);";
}


$TABLE[] = "CREATE TABLE gallery_albums_main (
  album_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  album_parent_id bigint(20) unsigned NOT NULL DEFAULT '0',
  album_owner_id int(8) unsigned NOT NULL DEFAULT '0',
  album_name varchar(255) NOT NULL DEFAULT '',
  album_name_seo varchar(255) NOT NULL DEFAULT '',
  album_description text,
  album_is_public int(1) NOT NULL DEFAULT '0',
  album_is_global int(1) NOT NULL DEFAULT '0',
  album_is_profile int(1) NOT NULL DEFAULT '0',
  album_count_imgs int(10) unsigned NOT NULL DEFAULT '0',
  album_count_comments int(10) unsigned NOT NULL DEFAULT '0',
  album_count_imgs_hidden int(10) unsigned NOT NULL DEFAULT '0',
  album_count_comments_hidden int(10) unsigned NOT NULL DEFAULT '0',
  album_cover_img_id bigint(20) unsigned NOT NULL DEFAULT '0',
  album_last_img_id bigint(20) unsigned NOT NULL DEFAULT '0',
  album_last_img_date int(10) unsigned NOT NULL DEFAULT '0',
  album_sort_options text,
  album_allow_comments int(1) unsigned NOT NULL DEFAULT '0',
  album_allow_rating int(1) unsigned NOT NULL DEFAULT '0',
  album_cache mediumtext,
  album_g_approve_img int(1) unsigned NOT NULL DEFAULT '0',
  album_g_approve_com int(1) unsigned NOT NULL DEFAULT '0',
  album_g_bitwise int(10) unsigned NOT NULL DEFAULT '0',
  album_g_rules mediumtext,
  album_g_container_only int(1) unsigned NOT NULL DEFAULT '0',
  album_g_perms_thumbs text,
  album_g_perms_view text,
  album_g_perms_images text,
  album_g_perms_comments text,
  album_g_perms_moderate text,
  album_g_latest_imgs TEXT,
  album_node_level bigint(20) unsigned NOT NULL DEFAULT '0',
  album_node_left bigint(20) unsigned NOT NULL DEFAULT '0',
  album_node_right bigint(20) unsigned NOT NULL DEFAULT '0',
  album_rating_aggregate int(2) NOT NULL DEFAULT '0',
  album_rating_count int(10) NOT NULL DEFAULT '0',
  album_rating_total int(10) unsigned NOT NULL DEFAULT '0',
  album_after_forum_id int(10) unsigned NOT NULL DEFAULT '0',
  album_detail_default INT(1) UNSIGNED NOT NULL DEFAULT 0,
  album_watermark INT(1) UNSIGNED NOT NULL DEFAULT 0,
  album_position INT(10) NOT NULL DEFAULT 0,
  album_child_tree TEXT,
  album_parent_tree TEXT,
  album_can_tag INT(1) NOT NULL DEFAULT 1,
  album_preset_tags TEXT,
  PRIMARY KEY (album_id),
  KEY album_parent_id (album_parent_id),
  KEY album_owner_id (album_owner_id),
  KEY album_last_img_date (album_last_img_date),
  KEY album_has_a_perm (album_is_global,album_is_public),
  KEY album_count_imgs (album_count_imgs),
  KEY album_nodes (album_node_left,album_node_level,album_node_right),
  KEY album_child_lup (album_is_global,album_parent_id,album_node_level,album_node_left,album_cover_img_id,album_last_img_date)
);";

$TABLE[] = "CREATE TABLE gallery_albums_temp (
	album_id	INT(10),
	album_g_perms_view	TEXT
);";

$TABLE[] = "CREATE TABLE gallery_bandwidth (
  bid bigint(10) unsigned NOT NULL auto_increment,
  member_id mediumint(8) unsigned NOT NULL default '0',
  file_name varchar(60) NOT NULL default '',
  bdate int(10) unsigned NOT NULL default '0',
  bsize int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (bid),
  KEY file_name (file_name),
  KEY member_id (member_id),
  KEY bdate (bdate)
);";

$TABLE[] = "CREATE TABLE gallery_comments (
  pid int(10) NOT NULL auto_increment,
  edit_time int(10) default NULL,
  author_id mediumint(8) NOT NULL default '0',
  author_name varchar(32) default NULL,
  ip_address varchar(46) NOT NULL default '',
  post_date int(10) default NULL,
  comment text,
  approved tinyint(1) default NULL,
  img_id int(10) NOT NULL default '0',
  PRIMARY KEY  (pid),
  KEY img_id (img_id,author_id),
  KEY author_id (author_id),
  KEY post_date (post_date),
  KEY img_id_2 (img_id ,pid)
);";
$TABLE[] = "CREATE TABLE gallery_images (
  id bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  member_id mediumint(8) unsigned NOT NULL DEFAULT '0',
  img_album_id bigint(20) unsigned NOT NULL DEFAULT '0',
  caption varchar(255) NOT NULL,
  description text,
  directory varchar(60) NOT NULL DEFAULT '',
  masked_file_name varchar(255) NOT NULL,
  medium_file_name varchar(255) NOT NULL,
  original_file_name varchar(255) NULL DEFAULT NULL,
  file_name varchar(255) NOT NULL,
  file_size int(10) unsigned NOT NULL DEFAULT '0',
  file_type varchar(50) NOT NULL DEFAULT '',
  approved tinyint(1) unsigned NOT NULL DEFAULT '0',
  thumbnail tinyint(1) unsigned NOT NULL DEFAULT '0',
  views bigint(10) unsigned NOT NULL DEFAULT '0',
  comments int(10) unsigned NOT NULL DEFAULT '0',
  comments_queued int(10) NOT NULL DEFAULT '0',
  idate int(10) unsigned NOT NULL DEFAULT '0',
  ratings_total int(10) unsigned NOT NULL DEFAULT '0',
  ratings_count int(10) unsigned NOT NULL DEFAULT '0',
  rating tinyint(2) unsigned NOT NULL DEFAULT '0',
  pinned tinyint(1) unsigned NOT NULL DEFAULT '0',
  lastcomment int(10) unsigned NOT NULL DEFAULT '0',
  media tinyint(1) unsigned NOT NULL,
  credit_info text,
  copyright varchar(120) NOT NULL DEFAULT '',
  metadata text,
  media_thumb varchar(75) NOT NULL DEFAULT '0',
  caption_seo varchar(255) NOT NULL,
  image_notes text NULL DEFAULT NULL,
  image_privacy int(1) NOT NULL DEFAULT '0',
  image_data text NOT NULL,
  image_parent_permission varchar(255) NOT NULL DEFAULT '',
  image_feature_flag int(1) NOT NULL DEFAULT '0',
  image_gps_raw text,
  image_gps_latlon varchar(255) DEFAULT NULL,
  image_gps_show int(1) NOT NULL DEFAULT '0',
  image_gps_lat varchar(255) DEFAULT NULL,
  image_gps_lon varchar(255) DEFAULT NULL,
  image_loc_short text,
  image_media_data text,
  PRIMARY KEY (id),
  KEY album_id (img_album_id),
  KEY member_id (member_id),
  KEY album_id_2 (img_album_id,approved,idate),
  KEY im_select (approved,image_privacy,member_id,idate),
  KEY gb_select (approved,image_parent_permission),
  KEY image_feature_flag (image_feature_flag),
  KEY rnd_lookup (image_privacy,member_id,image_parent_permission,approved,idate),
  KEY cmt_lookup (image_privacy,member_id,image_parent_permission,img_album_id,approved,lastcomment),
  KEY idate (idate),
  KEY lastcomment (lastcomment)
);";
$TABLE[] = "CREATE TABLE gallery_images_uploads (
  upload_key char(32) NOT NULL,
  upload_session char(32) NOT NULL,
  upload_member_id int(10) unsigned NOT NULL DEFAULT '0',
  upload_album_id int(10) unsigned NOT NULL DEFAULT '0',
  upload_date int(10) unsigned NOT NULL DEFAULT '0',
  upload_file_directory varchar(255) NOT NULL DEFAULT '',
  upload_file_orig_name varchar(255) NOT NULL DEFAULT '',
  upload_file_name varchar(255) NOT NULL DEFAULT '',
  upload_file_name_original VARCHAR(255) NULL DEFAULT NULL,
  upload_file_size int(10) unsigned NOT NULL DEFAULT '0',
  upload_file_type varchar(50) NOT NULL DEFAULT '',
  upload_thumb_name varchar(255) NOT NULL DEFAULT '',
  upload_medium_name varchar(255) NOT NULL DEFAULT '',
  upload_title text,
  upload_description text,
  upload_copyright text,
  upload_exif text,
  upload_data text,
  upload_feature_flag int(1) NOT NULL DEFAULT '0',
  upload_geodata text,
  upload_media_data text,
  PRIMARY KEY (upload_key),
  KEY upload_member_id (upload_member_id,upload_album_id),
  KEY upload_date (upload_date),
  KEY upload_session (upload_session)
);";
$TABLE[] = "CREATE TABLE gallery_ratings (
  id bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  member_id mediumint(8) unsigned NOT NULL DEFAULT '0',
  rating_foreign_id bigint(20) unsigned NOT NULL DEFAULT '0',
  rdate int(10) unsigned NOT NULL DEFAULT '0',
  rate smallint(1) unsigned NOT NULL DEFAULT '0',
  rating_where varchar(10) NOT NULL DEFAULT 'image',
  PRIMARY KEY (id),
  KEY rating_find_me (member_id,rating_foreign_id,rating_where)
);";


$TABLE[] = "ALTER TABLE members ADD gallery_perms VARCHAR( 10 ) DEFAULT '1:1:1' NOT NULL";

$TABLE[] = "ALTER TABLE groups ADD g_max_diskspace INT( 10 ) default '0' NOT NULL";
$TABLE[] = "ALTER TABLE groups ADD g_max_upload INT( 10 ) default '0' NOT NULL";
$TABLE[] = "ALTER TABLE groups ADD g_max_transfer INT( 10 ) default '0' NOT NULL";
$TABLE[] = "ALTER TABLE groups ADD g_max_views INT( 10 ) default '0' NOT NULL";
$TABLE[] = "ALTER TABLE groups ADD g_create_albums TINYINT( 1 ) UNSIGNED default '0' NOT NULL";
$TABLE[] = "ALTER TABLE groups ADD g_album_limit INT( 10 ) default '0' NOT NULL";
$TABLE[] = "ALTER TABLE groups ADD g_img_album_limit INT( 10 ) default '0' NOT NULL";
$TABLE[] = "ALTER TABLE groups ADD g_comment TINYINT( 1 ) UNSIGNED default '0' NOT NULL";
$TABLE[] = "ALTER TABLE groups ADD g_edit_own TINYINT( 1 ) UNSIGNED default '0' NOT NULL";
$TABLE[] = "ALTER TABLE groups ADD g_del_own TINYINT( 1 ) UNSIGNED default '0' NOT NULL";
$TABLE[] = "ALTER TABLE groups ADD g_mod_albums TINYINT( 1 ) UNSIGNED default '0' NOT NULL";
$TABLE[] = "ALTER TABLE groups ADD g_img_local TINYINT( 1 ) UNSIGNED default '0' NOT NULL";
$TABLE[] = "ALTER TABLE groups ADD g_movies TINYINT( 1 ) UNSIGNED default '0' NOT NULL";
$TABLE[] = "ALTER TABLE groups ADD g_movie_size INT( 10 ) default '0' NOT NULL";
$TABLE[] = "ALTER TABLE groups ADD g_gallery_use TINYINT( 1 ) NOT NULL DEFAULT '1';";
$TABLE[] = "ALTER TABLE groups ADD g_album_private TINYINT( 1 ) NOT NULL DEFAULT '1';";
