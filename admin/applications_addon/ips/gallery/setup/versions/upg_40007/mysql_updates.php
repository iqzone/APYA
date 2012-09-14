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

if ( ! $DB->checkForField( 'image_gps_latlon', 'gallery_images' ) )
{
	$SQL[] = "ALTER TABLE gallery_images ADD image_gps_latlon VARCHAR(255) DEFAULT '';";
}

if ( ! $DB->checkForTable( 'gallery_albums_temp' ) )
{
	$SQL[] = "CREATE TABLE gallery_albums_temp (
		album_id	INT(10),
		album_g_perms_view	TEXT
	);";
}
