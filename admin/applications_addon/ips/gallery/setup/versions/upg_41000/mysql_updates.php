<?php

$DB  = ipsRegistry::DB();
$PRE = ipsRegistry::dbFunctions()->getPrefix();


$SQL[] = "DELETE FROM custom_bbcode WHERE bbcode_tag='gallery' AND bbcode_php_plugin='gallery.php';";
$SQL[] = "DELETE FROM core_sys_conf_settings WHERE conf_key='gallery_create_thumbs';";

$SQL[] = "ALTER TABLE gallery_comments CHANGE ip_address ip_address VARCHAR( 46 ) NOT NULL;";
$SQL[] = "ALTER TABLE gallery_images CHANGE image_notes image_notes TEXT NULL DEFAULT NULL;"; // Strict mode complains...

/* Drop unused fields */
$SQL[] = "ALTER TABLE gallery_comments DROP append_edit, DROP use_sig, DROP use_emo, DROP edit_name;";

/* Got a field to add? ;O */
if ( !$DB->checkForField( 'album_position', 'gallery_albums_main' ) )
{
	$SQL[] = "ALTER TABLE gallery_albums_main ADD album_position INT(10) NOT NULL DEFAULT 0;";
}
