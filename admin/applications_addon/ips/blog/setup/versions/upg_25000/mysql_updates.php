<?php

$SQL[] = "DELETE FROM cache_store WHERE cs_key IN ('blog_headers','blog_themes');";
$SQL[] = "ALTER TABLE blog_blogs DROP COLUMN blog_header_id;";
$SQL[] = "DROP TABLE blog_headers;";

$SQL[] = "DELETE FROM core_sys_conf_settings WHERE conf_key IN ('blog_enable_dheader','blog_cache_dheader','blog_headers');";

$SQL[] = "ALTER TABLE blog_entries ADD COLUMN entry_image VARCHAR(255) NOT NULL DEFAULT '';";

$SQL[] = "ALTER TABLE blog_views ADD COLUMN entry_id INT(10) NOT NULL DEFAULT 0;";
$SQL[] = "ALTER TABLE blog_entries ADD COLUMN entry_views INT(10) NOT NULL DEFAULT 0;";

$SQL[] = "INSERT INTO core_share_links (share_title, share_key, share_enabled, share_position, share_canonical) VALUES ('Blog This', 'blogthis', 1, 12, 0);";


//