<?php
/*
+--------------------------------------------------------------------------
|   IP.Board v4.2.1
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2004 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
*/

$DB  = ipsRegistry::DB();
$PRE = ipsRegistry::dbFunctions()->getPrefix();

$DEAD_GROUP_FIELDS = "g_max_notes,g_gallery_cat_cover,g_can_search_gallery,g_ecard,g_rate,g_favorites,g_slideshows,g_move_own,g_zip_upload,g_multi_file_limit";

$DEAD_SETTINGS = "gallery_display_category,
gallery_display_album,
gallery_full_image,
gallery_display_block_row,
gallery_display_photostrip,
gallery_last_updated,
gallery_show_lastpic,
gallery_display_subcats,
gallery_images_per_row,
gallery_dir_images,
gallery_stats_where,
gallery_images_per_block,
gallery_last5_images,
gallery_random_images,
gallery_stats,
gallery_create_thumbnails,
gallery_thumbnail_link,
gallery_thumb_width,
gallery_thumb_height,
gallery_bandwidth_thumbs,
gallery_use_rate,
gallery_rate_display,
gallery_comment_order,
gallery_guests_ecards,
display_hotlinking,
gallery_use_ecards,
gallery_allow_usercopyright,
gallery_copyright_default,
gallery_notices_img,
gallery_notices_album,
gallery_notices_cat,
gallery_exif_sections,
gallery_iptc,
gallery_exif,
gallery_antileech_image,
gallery_allowed_domains";

$SQL[] = "CREATE TABLE gallery_albums_main (
	album_id					BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	album_parent_id				BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
	album_owner_id				INT(8) UNSIGNED NOT NULL DEFAULT 0,
	album_name					VARCHAR(255) NOT NULL DEFAULT '',
	album_name_seo				VARCHAR(255) NOT NULL DEFAULT '',
	album_description			TEXT,
	album_is_public 			INT(1) NOT NULL DEFAULT 0,
	album_is_global	 			INT(1) NOT NULL DEFAULT 0,
	album_is_profile			INT(1) NOT NULL DEFAULT 0,
	album_count_imgs			INT(10) UNSIGNED NOT NULL DEFAULT 0,
	album_count_comments		INT(10) UNSIGNED NOT NULL DEFAULT 0,
	album_count_imgs_hidden		INT(10) UNSIGNED NOT NULL DEFAULT 0,
	album_count_comments_hidden	INT(10) UNSIGNED NOT NULL DEFAULT 0,
	album_cover_img_id			BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
	album_last_img_id			BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
	album_last_img_date			INT(10) UNSIGNED NOT NULL DEFAULT 0,
	album_sort_options			TEXT,
	album_allow_comments		INT(1) UNSIGNED NOT NULL DEFAULT 0,
	album_allow_rating			INT(1) UNSIGNED NOT NULL DEFAULT 0,
	album_cache					MEDIUMTEXT,
	album_node_level			BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
	album_node_left				BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
	album_node_right			BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
	album_g_approve_img			INT(1) UNSIGNED NOT NULL DEFAULT 0,
	album_g_approve_com			INT(1) UNSIGNED NOT NULL DEFAULT 0,
	album_g_bitwise				INT(10) UNSIGNED NOT NULL DEFAULT 0,
	album_g_rules				MEDIUMTEXT,
	album_g_container_only		INT(1) UNSIGNED NOT NULL DEFAULT 0,
	album_g_perms_thumbs 		TEXT,
	album_g_perms_view 			TEXT,
	album_g_perms_images 		TEXT,
	album_g_perms_comments 		TEXT,
	album_g_perms_moderate 		TEXT,
	album_g_latest_imgs 		TEXT,
	album_rating_aggregate		INT(2) NOT NULL DEFAULT 0,
	album_rating_total	    	INT(10) UNSIGNED NOT NULL DEFAULT 0,
	album_rating_count			INT(10) NOT NULL DEFAULT 0,
	album_after_forum_id	    INT(10) UNSIGNED NOT NULL DEFAULT 0,
	album_position				INT(10) NOT NULL DEFAULT 0,
	album_child_tree TEXT,
    album_parent_tree TEXT,
    album_can_tag INT(1) NOT NULL DEFAULT 1,
    album_preset_tags TEXT,
	PRIMARY KEY (album_id),
	KEY album_parent_id (album_parent_id),
	KEY album_owner_id (album_owner_id),
	KEY album_last_img_date (album_last_img_date),
	KEY album_has_a_perm (album_is_global, album_is_public),
	KEY album_nodes (album_node_left, album_node_level, album_node_right ),
	KEY album_count_imgs ( album_count_imgs ),
	KEY album_child_lup (album_is_global, album_parent_id, album_node_level, album_node_left, album_cover_img_id, album_last_img_date)
);";

$SQL[] = "CREATE TABLE gallery_images_uploads (
	upload_key			  CHAR(32) NOT NULL,
	upload_session		  CHAR(32) NOT NULL,
	upload_member_id  	  INT(10) UNSIGNED NOT NULL default 0,
	upload_album_id		  INT(10) UNSIGNED NOT NULL default 0,
	upload_date			  INT(10) UNSIGNED NOT NULL default 0,
	upload_file_directory VARCHAR(255) NOT NULL default '',
	upload_file_orig_name VARCHAR(255) NOT NULL default '',
	upload_file_name	  VARCHAR(255) NOT NULL default '',
	upload_file_size	  INT(10) UNSIGNED NOT NULL default 0,
	upload_file_type	  VARCHAR(50) NOT NULL default '',
	upload_thumb_name	  VARCHAR(255) NOT NULL default '',
	upload_medium_name	  VARCHAR(255) NOT NULL default '',
	upload_title		  TEXT,
	upload_description	  TEXT,
	upload_copyright	  TEXT,
	upload_exif			  TEXT,
	upload_data			  TEXT,
	upload_feature_flag   INT(1) NOT NULL DEFAULT '0',
	upload_geodata		  TEXT,
	upload_media_data	  TEXT,
	PRIMARY KEY (upload_key),
	KEY upload_member_id (upload_member_id, upload_album_id),
	KEY upload_date (upload_date),
	KEY upload_session (upload_session)
);";
# CREATE NEW TABLES
if ( ! $DB->checkForTable( 'core_geolocation_cache' ) )
{
	$SQL[] = "CREATE TABLE core_geolocation_cache (
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

if ( ! $DB->checkForTable( 'gallery_albums_temp' ) )
{
	$SQL[] = "CREATE TABLE gallery_albums_temp (
		album_id	INT(10),
		album_g_perms_view	TEXT
	);";
}

$SQL[] = "ALTER TABLE gallery_images ADD image_media_data TEXT;";
$SQL[] = "alter table gallery_images CHANGE album_id img_album_id INT(10) UNSIGNED NOT NULL DEFAULT 0;";
$SQL[] = "alter table gallery_images CHANGE directory directory VARCHAR(255) NOT NULL DEFAULT '';";
$SQL[] = "ALTER TABLE gallery_images ADD image_data TEXT NOT NULL;";
$SQL[] = "ALTER TABLE gallery_images ADD image_privacy INT(1) NOT NULL DEFAULT 0;";
$SQL[] = "ALTER TABLE gallery_images ADD image_parent_permission VARCHAR(255) NOT NULL DEFAULT '';";
$SQL[] = "ALTER TABLE gallery_images ADD image_feature_flag	INT(1) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE gallery_images ADD image_gps_raw	TEXT;";
$SQL[] = "ALTER TABLE gallery_images ADD image_gps_lat	VARCHAR(255);";
$SQL[] = "ALTER TABLE gallery_images ADD image_gps_lon	VARCHAR(255);";
$SQL[] = "ALTER TABLE gallery_images ADD image_gps_show	INT(1) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE gallery_images ADD image_loc_short	TEXT;";

$SQL[] = "ALTER TABLE gallery_image_views ADD INDEX ( views_img );";
$SQL[] = "ALTER TABLE gallery_images ADD KEY im_select (approved, image_privacy, member_id, idate );";
$SQL[] = "ALTER TABLE gallery_images ADD KEY gb_select (approved, image_parent_permission);";
$SQL[] = "ALTER TABLE gallery_images ADD KEY image_feature_flag (image_feature_flag );";
$SQL[] = "ALTER TABLE gallery_images ADD KEY idate (idate);";
$SQL[] = "ALTER TABLE gallery_images ADD KEY lastcomment (lastcomment);";
$SQL[] = "ALTER TABLE gallery_images ADD KEY cmt_lookup (image_privacy, member_id, image_parent_permission, img_album_id, approved, lastcomment);";
$SQL[] = "ALTER TABLE gallery_images ADD KEY rnd_lookup (image_privacy, member_id, image_parent_permission, approved, idate );";

$SQL[] = "ALTER TABLE groups CHANGE g_max_diskspace g_max_diskspace INT(10) DEFAULT '0';";
$SQL[] = "ALTER TABLE groups CHANGE g_max_upload g_max_upload INT(10) DEFAULT '0';";
$SQL[] = "ALTER TABLE groups CHANGE g_max_transfer g_max_transfer INT(10) DEFAULT '0';";
$SQL[] = "ALTER TABLE groups CHANGE g_max_views g_max_views INT(10) DEFAULT '0';";
$SQL[] = "ALTER TABLE groups CHANGE g_album_limit g_album_limit INT(10) DEFAULT '0';";
$SQL[] = "ALTER TABLE groups CHANGE g_img_album_limit g_img_album_limit INT(10) DEFAULT '0';";
$SQL[] = "ALTER TABLE groups CHANGE g_movie_size g_movie_size INT(10) DEFAULT '0';";

$SQL[] = "UPDATE groups SET g_max_diskspace= -1 WHERE g_max_diskspace = 0;";
$SQL[] = "UPDATE groups SET g_max_upload= -1 WHERE g_max_upload = 0;";
$SQL[] = "UPDATE groups SET g_max_transfer= -1 WHERE g_max_transfer = 0;";
$SQL[] = "UPDATE groups SET g_max_views= -1 WHERE g_max_views = 0;";
$SQL[] = "UPDATE groups SET g_album_limit= -1 WHERE g_album_limit = 0;";
$SQL[] = "UPDATE groups SET g_img_album_limit= -1 WHERE g_img_album_limit = 0;";
$SQL[] = "UPDATE groups SET g_movie_size= -1 WHERE g_movie_size = 0;";

$SQL[] = "ALTER TABLE gallery_ratings CHANGE img_id rating_foreign_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0;";
$SQL[] = "ALTER TABLE gallery_ratings ADD rating_where	VARCHAR(10) NOT NULL DEFAULT 'image';";

$SQL[] = "ALTER TABLE gallery_ratings DROP KEY member_id;";
$SQL[] = "ALTER TABLE gallery_ratings DROP KEY img_id;";

$SQL[] = "ALTER TABLE gallery_ratings ADD KEY rating_find_me (member_id, rating_foreign_id, rating_where);";

/* Delete settings */
$SQL[] = "DELETE FROM core_sys_conf_settings WHERE conf_key IN ('" . implode( "','", explode( ",", str_replace( array("\n", "\r", " "), "", $DEAD_SETTINGS ) ) ) . "');";

/* Delete member group fields */
foreach( explode( ',', $DEAD_GROUP_FIELDS ) as $field )
{
	$field = trim( $field );
	
	if ( $DB->checkForField( $field, 'groups' ) )
	{
		$SQL[] = "ALTER TABLE groups DROP " . $field . ";";
	}
}

// Fix for bug #25360 - http://community.invisionpower.com/tracker/issue-25360-report-plugin-not-updated-properly/
$SQL[] = 'UPDATE rc_classes SET app = \'gallery\' WHERE my_class = \'gallery\'';

?>