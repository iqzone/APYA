<?php
$SQL[] = "ALTER TABLE gallery_images ADD medium_file_name VARCHAR( 75 ) DEFAULT '' NOT NULL";
$SQL[] = "ALTER TABLE groups ADD g_can_search_gallery TINYINT( 1 ) DEFAULT '1' NOT NULL";
$SQL[] = "ALTER TABLE gallery_categories ADD cat_rule_method TINYINT( 1 ) UNSIGNED DEFAULT '0' NOT NULL , ADD cat_rule_title VARCHAR( 120 ) NOT NULL , ADD cat_rule_text TEXT NULL";
$SQL[] = "ALTER TABLE gallery_images ADD credit_info TEXT NULL , ADD copyright VARCHAR( 120 ) NOT NULL";
$SQL[] = "ALTER TABLE contacts ADD gallery_album_perms TEXT NULL";
