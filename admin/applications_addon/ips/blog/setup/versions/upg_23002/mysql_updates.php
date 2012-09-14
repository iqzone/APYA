<?php

// Add indexs on blog_views
$SQL[] = "ALTER TABLE blog_views ADD INDEX ( blog_id );";

// Remove unused settings
$SQL[] = "DELETE FROM core_sys_conf_settings WHERE conf_key='blog_exturl_newwindow';";

// Clear short entries to fix HTML bug
$SQL[] = "UPDATE blog_entries SET entry_short='';";