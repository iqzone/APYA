<?php

/* Remove old caches & modules */
$SQL[] = "DELETE FROM cache_store WHERE cs_key IN ('gallery_cats','gallery_albums','gallery_post_form','gallery_media_types');";
$SQL[] = "DELETE FROM core_sys_module WHERE sys_module_application='gallery' AND sys_module_admin=1 AND sys_module_key IN ('cats','groups','media','postform','tools');";
$SQL[] = "DELETE FROM core_sys_module WHERE sys_module_application='gallery' AND sys_module_admin=0 AND sys_module_key IN ('stats','subscribe','user');";

/* Lets drop some old tables and fields */
if ( ipsRegistry::DB()->checkForTable('gallery_form_fields') )
{
	$SQL[] = "DROP TABLE gallery_form_fields;";
}
if ( ipsRegistry::DB()->checkForTable('gallery_media_types') )
{
	$SQL[] = "DROP TABLE gallery_media_types;";
}
if ( ipsRegistry::DB()->checkForField('album_g_password','gallery_albums_main') )
{
	$SQL[] = "ALTER TABLE gallery_albums_main DROP album_g_password;";
}


/* Moved here from 40007 */
if ( ipsRegistry::DB()->checkForTable('gallery_image_views') )
{
	$SQL[] = "DROP TABLE gallery_image_views";
}

/* Need this query here to prevent driver errors upgrading from 4.0.x */
if ( !ipsRegistry::DB()->checkForField( 'album_position', 'gallery_albums_main' ) )
{
	$SQL[] = "ALTER TABLE gallery_albums_main ADD album_position INT(10) NOT NULL DEFAULT 0;";
}

$SQL[] = "ALTER TABLE gallery_albums_main ADD album_detail_default INT(1) unsigned NOT NULL DEFAULT 0;";

/* Fix wrong ratings */
$SQL[] = "UPDATE gallery_images SET rating=ROUND(ratings_total/ratings_count) WHERE ratings_total>0 AND ratings_count>0 AND rating=0;";

/* Re-add watermark */
$SQL[] = "ALTER TABLE gallery_albums_main ADD album_watermark INT(1) unsigned NOT NULL DEFAULT 0;";
$SQL[] = "ALTER TABLE gallery_images ADD original_file_name varchar(255) NULL DEFAULT NULL;";
$SQL[] = "ALTER TABLE gallery_images_uploads ADD upload_file_name_original VARCHAR(255) NULL DEFAULT NULL;";


/* Sometimes g_gallery_use is missing from old upgrades */
if ( !ipsRegistry::DB()->checkForField('g_gallery_use','groups') )
{
	$SQL[] = "ALTER TABLE groups ADD g_gallery_use TINYINT( 1 ) NOT NULL DEFAULT '1';";
}
