<?php

$SQL[] = "ALTER TABLE gallery_albums ADD category_id INT(10) UNSIGNED DEFAULT 0";
$SQL[] = "ALTER TABLE gallery_albums ADD last_name VARCHAR( 255 ) NOT NULL";
$SQL[] = "ALTER TABLE gallery_categories ADD category_only TINYINT(1) UNSIGNED DEFAULT 0";
$SQL[] = "ALTER TABLE gallery_categories ADD last_name VARCHAR( 255 ) NOT NULL";
$SQL[] = "ALTER TABLE gallery_categories ADD last_member_id MEDIUMINT( 8 ) UNSIGNED NOT NULL ";
$SQL[] = "ALTER TABLE groups ADD g_multi_file_limit SMALLINT(2) UNSIGNED DEFAULT 0";
$SQL[] = "ALTER TABLE groups ADD g_zip_upload TINYINT(1) UNSIGNED DEFAULT 0";
$SQL[] = "ALTER TABLE gallery_ratings ADD INDEX ( member_id ) ";
$SQL[] = "ALTER TABLE gallery_media_types CHANGE extension extension VARCHAR( 32 ) NOT NULL";
$SQL[] = "ALTER TABLE gallery_media_types ADD default_type TINYINT( 1 ) UNSIGNED DEFAULT '0' NOT NULL";
$SQL[] = "ALTER TABLE gallery_images CHANGE directory directory INT( 10 ) UNSIGNED DEFAULT '0' NOT NULL"; 
$SQL[] = "ALTER TABLE gallery_albums CHANGE member_id member_id MEDIUMINT( 8 ) UNSIGNED DEFAULT '0' NOT NULL ";
$SQL[] = "ALTER TABLE gallery_bandwidth CHANGE member_id member_id MEDIUMINT( 8 ) UNSIGNED DEFAULT '0' NOT NULL"; 
$SQL[] = "ALTER TABLE gallery_ecardlog CHANGE member_id member_id MEDIUMINT( 8 ) UNSIGNED DEFAULT '0' NOT NULL ";
$SQL[] = "ALTER TABLE gallery_favorites CHANGE member_id member_id MEDIUMINT( 8 ) UNSIGNED DEFAULT '0' NOT NULL ";
$SQL[] = "ALTER TABLE gallery_images CHANGE member_id member_id MEDIUMINT( 8 ) UNSIGNED DEFAULT '0' NOT NULL ";
$SQL[] = "ALTER TABLE gallery_ratings CHANGE member_id member_id MEDIUMINT( 8 ) UNSIGNED DEFAULT '0' NOT NULL ";
$SQL[] = "ALTER TABLE gallery_categories ADD album_mode TINYINT( 1 ) UNSIGNED DEFAULT '0' NOT NULL";
$SQL[] = "INSERT INTO gallery_media_types VALUES ('', 'folder_mime_types/gif.gif', 'JPEG', 'image/jpeg', '.jpg,.jpeg', 1, 0, 0, 0, 0, '<#IMAGE#>', 1);";
$SQL[] = "INSERT INTO gallery_media_types VALUES ('', 'folder_mime_types/gif.gif', 'PNG', 'image/png', '.png', 1, 0, 0, 0, 0, '<#IMAGE#>', 1);";
$SQL[] = "INSERT INTO gallery_media_types VALUES ('', 'folder_mime_types/gif.gif', 'GIF', 'image/gif', '.gif', 1, 0, 0, 0, 0, '<#IMAGE#>', 1);";
