<?php

$SQL = array();

# Fix the date columns - reserved mysql word

$SQL[]	= "ALTER TABLE gallery_bandwidth CHANGE date bdate INT( 10 ) UNSIGNED NOT NULL DEFAULT '0';";
$SQL[]	= "ALTER TABLE gallery_images CHANGE date idate INT( 10 ) UNSIGNED NOT NULL DEFAULT '0';";
$SQL[]	= "ALTER TABLE gallery_ecardlog CHANGE date edate INT( 10 ) UNSIGNED NOT NULL DEFAULT '0';";
$SQL[]	= "ALTER TABLE gallery_ratings CHANGE date rdate INT( 10 ) UNSIGNED NOT NULL DEFAULT '0';";

# Fix date column for the category view definition column
$SQL[]	= "ALTER TABLE gallery_categories CHANGE def_view def_view varchar(30) default 'idate:DESC:*';";
$SQL[]	= "UPDATE gallery_categories SET def_view=REPLACE(def_view, 'date:', 'idate:');";
$SQL[]	= "UPDATE gallery_categories SET def_view=REPLACE(def_view, ':30', ':*');";


# Some other cols

$SQL[]	= "ALTER TABLE gallery_albums CHANGE description description TEXT NULL;";

$SQL[]	= "ALTER TABLE gallery_media_types DROP thumb_width, DROP thumb_height, DROP thumb_prop;";

$SQL[]	= "ALTER TABLE gallery_categories ADD mem_gallery TEXT NULL;";

$SQL[]	= "ALTER TABLE gallery_albums ADD INDEX(public_album);";

$SQL[]	= "ALTER TABLE gallery_images CHANGE file_type file_type varchar(50) NOT NULL default '0',
				ADD metadata TEXT NULL, ADD media_thumb varchar(75) not null default '0';";
				
$SQL[]	= "ALTER TABLE gallery_images ADD INDEX(album_id), ADD INDEX(member_id);";
$SQL[]	= "ALTER TABLE gallery_ratings ADD INDEX(img_id);";

$SQL[] 	= "UPDATE gallery_form_fields SET required=0 WHERE LOWER(name) IN ('caption', 'description', 'photo information')";

/* No longer relevant, in IPB 3 conf_settings table was dropped
$SQL[]	= "DELETE FROM conf_settings WHERE conf_key='gallery_user_category'";
$SQL[]	= "DELETE FROM conf_settings WHERE conf_key='gallery_user_gview'";
$SQL[]	= "DELETE FROM conf_settings WHERE conf_key='gallery_album_position'";
$SQL[]	= "DELETE FROM conf_settings WHERE conf_key='gallery_album_where'";
$SQL[]	= "DELETE FROM conf_settings WHERE conf_key='gallery_album_list'";
$SQL[]	= "DELETE FROM conf_settings WHERE conf_key='gallery_user_row'";
$SQL[]	= "DELETE FROM conf_settings WHERE conf_key='gallery_user_col'";
$SQL[]	= "DELETE FROM conf_settings WHERE conf_key='gallery_album_sort'";
$SQL[]	= "DELETE FROM conf_settings WHERE conf_key='gallery_album_order'";
$SQL[]	= "DELETE FROM conf_settings WHERE conf_key='gallery_display_photobar'";
$SQL[]	= "DELETE FROM conf_settings WHERE conf_key='gallery_albums_page'";
*/
