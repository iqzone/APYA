<?php

$SQL[] = "CREATE TABLE blog_cblock_cache(
  blog_id int(10) not null,
  cbcache_key varchar(32) not null,
  cbcache_lastupdate int(10) default '0' not null,
  cbcache_refresh tinyint(1) default '0' not null,
  cbcache_content text null,
PRIMARY KEY( blog_id, cbcache_key ) )";
