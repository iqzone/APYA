<?php

$SQL[] = "DELETE FROM core_sys_conf_settings WHERE conf_key='blog_use_friendlyurls';";
$SQL[] = "DELETE FROM core_sys_conf_settings WHERE conf_key='blog_friendly_url';";
$SQL[] = "DELETE FROM core_sys_conf_settings WHERE conf_key='blog_friendlyurl_path';";
$SQL[] = "DELETE FROM core_sys_conf_settings WHERE conf_key='blog_boardindex_path';";
$SQL[] = "UPDATE blog_entries SET entry_short='';";
//$SQL[] = "ALTER TABLE blog_lastinfo DROP cat_level;";
