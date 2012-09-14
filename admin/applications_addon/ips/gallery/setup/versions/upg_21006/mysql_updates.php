<?php

$SQL = array();

# Fix the date columns - reserved mysql word

$SQL[]	= "ALTER TABLE gallery_media_types CHANGE mime_type mime_type varchar(50) NOT NULL default '';";
$SQL[]	= "ALTER TABLE gallery_categories CHANGE last_name last_name VARCHAR( 255 ) NULL;";
$SQL[]	= "ALTER TABLE gallery_categories CHANGE last_member_id last_member_id MEDIUMINT( 8 ) UNSIGNED NULL;";
