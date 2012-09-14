<?php


$SQL[] = "ALTER TABLE blog_blogs add blog_pinned TINYINT(1) NOT NULL default '0'";
$SQL[] = "ALTER TABLE blog_blogs add blog_disabled TINYINT(1) NOT NULL default '0'";
