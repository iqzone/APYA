<?php

$SQL[] = "ALTER TABLE gallery_albums ADD INDEX ( public_album )";
$SQL[] = "ALTER TABLE gallery_images ADD INDEX ( album_id )";
$SQL[] = "ALTER TABLE gallery_images ADD INDEX ( member_id )";
