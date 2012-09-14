<?php

# Let's remove some old settings never removed properly..
$SQL[] = "DELETE FROM core_sys_conf_settings WHERE conf_key IN( 'topic_marking_noquery', 'live_search_disable' );";

# We love indexes!
$SQL[] = "ALTER TABLE core_hooks ADD INDEX hook_enabled (hook_enabled,hook_position), ADD INDEX hook_key (hook_key);";
