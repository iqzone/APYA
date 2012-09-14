<?php

$DB  = ipsRegistry::DB();
$PRE = ipsRegistry::dbFunctions()->getPrefix();

$SQL[] = "UPDATE gallery_albums_main SET album_position=10000000 WHERE album_is_global=0;";

if ( ! $DB->checkForField( 'album_g_latest_imgs', 'gallery_albums_main' ) )
{
	$SQL[] = "ALTER TABLE gallery_albums_main ADD album_g_latest_imgs TEXT AFTER album_g_perms_moderate;";
}

if ( ! $DB->checkForField( 'album_child_tree', 'gallery_albums_main' ) )
{
	$SQL[] = "ALTER TABLE gallery_albums_main ADD album_child_tree TEXT;";
}

if ( ! $DB->checkForField( 'album_parent_tree', 'gallery_albums_main' ) )
{
	$SQL[] = "ALTER TABLE gallery_albums_main ADD album_parent_tree TEXT;";
}

if ( ! $DB->checkForField( 'album_can_tag', 'gallery_albums_main' ) )
{
	$SQL[] = "ALTER TABLE gallery_albums_main ADD album_can_tag INT(1) NOT NULL DEFAULT 1, ADD album_preset_tags TEXT;";
}
