<?php

$SQL[] = "ALTER TABLE gallery_categories ADD images_per_page SMALLINT( 4 ) NOT NULL DEFAULT '25';";
$SQL[] = "ALTER TABLE gallery_images ADD INDEX ( album_id , approved , idate ) ;";
$SQL[] = "ALTER TABLE groups ADD g_max_notes SMALLINT( 6 ) NOT NULL default '5';";
$SQL[] = "ALTER TABLE gallery_images ADD image_notes TEXT NOT NULL;";
$SQL[] = "ALTER TABLE gallery_albums ADD profile_album TINYINT( 1 ) NOT NULL;";
$SQL[] = "ALTER TABLE gallery_albums ADD cover_img_id BIGINT( 10 ) NOT NULL default '0';";
$SQL[] = "ALTER TABLE gallery_categories ADD cover_img_id BIGINT( 10 ) NOT NULL default '0';";
$SQL[] = "ALTER TABLE groups ADD g_gallery_cat_cover TINYINT( 1 ) NOT NULL default '0';";
$SQL[] = "ALTER TABLE gallery_albums ADD parent BIGINT( 10 ) NOT NULL;";
$SQL[] = "ALTER TABLE gallery_albums ADD friend_only TINYINT( 1 ) NOT NULL;";
$SQL[] = "ALTER TABLE gallery_albums ADD name_seo VARCHAR( 60 ) NOT NULL default '';";
$SQL[] = "ALTER TABLE gallery_categories ADD name_seo VARCHAR( 60 ) NOT NULL default '';";
$SQL[] = "ALTER TABLE gallery_images ADD caption_seo VARCHAR( 255 ) NOT NULL default '';";

$SQL[] = "CREATE TABLE gallery_image_views (
  views_img int(10) NOT NULL DEFAULT '0'
);";

$SQL[] = "UPDATE gallery_categories SET images_per_page=imgs_per_col*imgs_per_row";

$SQL[] = "ALTER TABLE gallery_albums ADD INDEX ( member_id , name );";
$SQL[] = "ALTER TABLE gallery_comments ADD INDEX (img_id ,pid );";
$SQL[] = "ALTER TABLE gallery_albums ADD INDEX ( category_id , last_pic_date );";

$SQL[] = "ALTER TABLE gallery_images CHANGE masked_file_name masked_file_name VARCHAR( 255 ) NOT NULL;";
$SQL[] = "ALTER TABLE gallery_images CHANGE medium_file_name medium_file_name VARCHAR( 255 ) NOT NULL;";
$SQL[] = "ALTER TABLE gallery_images CHANGE file_name file_name VARCHAR( 255 ) NOT NULL;";