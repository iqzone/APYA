<?php

$SQL = array();

$SQL[]	= "ALTER TABLE gallery_comments ADD INDEX ( post_date );";

$SQL[]	= "CREATE TABLE gallery_subscriptions (
sub_id INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
sub_mid INT( 10 ) NOT NULL DEFAULT '0',
sub_type VARCHAR( 25 ) NOT NULL DEFAULT 'image',
sub_toid INT( 10 ) NOT NULL DEFAULT '0',
sub_added VARCHAR( 13 ) NOT NULL DEFAULT '0',
sub_last VARCHAR( 13 ) NOT NULL DEFAULT '0',
INDEX ( sub_mid )
);";

$SQL[]	= "ALTER TABLE gallery_albums ADD def_view VARCHAR( 50 ) NOT NULL DEFAULT 'idate:DESC:*';";
$SQL[]	= "ALTER TABLE groups ADD g_album_private TINYINT( 1 ) NOT NULL DEFAULT '1';";
# No longer relevant, in IPB 3 conf_settings table was dropped - $SQL[]	= "DELETE FROM conf_settings WHERE conf_key='gallery_guest_access';";

$SQL[]	= "ALTER TABLE gallery_categories CHANGE last_pic last_pic_id INT( 10 ) UNSIGNED NOT NULL DEFAULT '0';";
$SQL[]	= "ALTER TABLE gallery_albums DROP last_name;";
$SQL[]	= "ALTER TABLE gallery_categories DROP last_name;";
$SQL[]	= "ALTER TABLE gallery_categories ADD last_pic_date VARCHAR( 13 ) NOT NULL DEFAULT '0';";
$SQL[]	= "ALTER TABLE gallery_albums ADD last_pic_date VARCHAR( 13 ) NOT NULL DEFAULT '0';";
$SQL[]	= "ALTER TABLE gallery_albums CHANGE last_pic last_pic_id INT( 10 ) UNSIGNED NOT NULL DEFAULT '0';";
$SQL[]	= "ALTER TABLE gallery_albums ADD last_pic_name VARCHAR( 255 ) NULL ;";
$SQL[]	= "ALTER TABLE gallery_categories ADD last_pic_name VARCHAR( 255 ) NULL AFTER id ;";

$SQL[]	= "INSERT INTO cache_store ( cs_key , cs_value , cs_extra , cs_array ) VALUES ( 'gallery_media_types', '', '', '1' );";
$SQL[]	= "INSERT INTO cache_store ( cs_key , cs_value , cs_extra , cs_array ) VALUES ( 'gallery_stats', '', '', '1' );";
$SQL[]	= "INSERT INTO cache_store ( cs_key , cs_value , cs_extra , cs_array ) VALUES ( 'gallery_post_form', '', '', '1' );";
$SQL[]	= "INSERT INTO cache_store ( cs_key , cs_value , cs_extra , cs_array ) VALUES ( 'gallery_albums', '', '', '1' );";
