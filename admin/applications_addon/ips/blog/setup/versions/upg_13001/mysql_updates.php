<?php

$SQL[] = "CREATE TABLE blog_rsscache(
  blog_id int(10) not null,
  rsscache_refresh tinyint(1) not null default '0',
  rsscache_feed text null,
PRIMARY KEY( blog_id ))";

$SQL[] = "CREATE TABLE blog_pingservices(
  blog_service_id int(10) not null auto_increment,
  blog_service_key varchar(10) not null,
  blog_service_name varchar(255) not null default '',
  blog_service_host varchar(255) not null default '',
  blog_service_port smallint(5),
  blog_service_path varchar(255) not null default '',
  blog_service_methodname varchar(255) not null default '',
  blog_service_extended tinyint(1) not null default '0',
  blog_service_enabled tinyint(1) not null default '0',
PRIMARY KEY( blog_service_id ))";

$SQL[] = "INSERT INTO blog_pingservices(blog_service_key, blog_service_name, blog_service_host, blog_service_port, blog_service_path, blog_service_methodname, blog_service_extended, blog_service_enabled )
VALUES( 'technorati', 'Technorati', 'rpc.technorati.com', 80, '/rpc/ping', 'weblogUpdates.ping', 0, 1 )";
$SQL[] = "INSERT INTO blog_pingservices(blog_service_key, blog_service_name, blog_service_host, blog_service_port, blog_service_path, blog_service_methodname, blog_service_extended, blog_service_enabled )
VALUES( 'weblogs', 'Weblogs.com', 'rpc.weblogs.com', 80, '/RPC2', 'weblogUpdates.extendedPing', 1, 1 )";
$SQL[] = "INSERT INTO blog_pingservices(blog_service_key, blog_service_name, blog_service_host, blog_service_port, blog_service_path, blog_service_methodname, blog_service_extended, blog_service_enabled )
VALUES( 'pingomatic', 'Ping-o-Matic', 'rpc.pingomatic.com', 80, '/RPC2', 'weblogUpdates.ping', 0, 1 )";

$SQL[] = "ALTER TABLE groups ADD g_blog_preventpublish TINYINT(1) NOT NULL DEFAULT '0'";
