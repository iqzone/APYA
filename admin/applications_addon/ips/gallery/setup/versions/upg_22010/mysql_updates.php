<?php

$SQL[] = "ALTER TABLE groups ADD g_gal_avatar TINYINT( 1 ) NOT NULL DEFAULT '1';";
$SQL[] = "ALTER TABLE gallery_images ADD comments_queued INT( 10 ) NOT NULL DEFAULT '0' AFTER comments;";
$SQL[] = "ALTER TABLE gallery_categories ADD mod_comments INT( 10 ) NOT NULL DEFAULT '0' AFTER mod_images;";
$SQL[] = "ALTER TABLE gallery_albums ADD mod_images INT( 10 ) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE gallery_albums ADD mod_comments INT( 10 ) NOT NULL DEFAULT '0';";
