<?php

$SQL[] = "ALTER TABLE blog_rsscache CHANGE rsscache_feed rsscache_feed MEDIUMTEXT NULL;";
$SQL[] = "UPDATE blog_rsscache SET rsscache_refresh=1;";

$SQL[] = "INSERT INTO blog_pingservices(blog_service_key, blog_service_name, blog_service_host, blog_service_port, blog_service_path, blog_service_methodname, blog_service_extended, blog_service_enabled )
VALUES( 'google', 'Google Blog Search', 'blogsearch.google.com', 80, 'ping/RPC2', 'weblogUpdates.extendedPing', 1, 1 )";
