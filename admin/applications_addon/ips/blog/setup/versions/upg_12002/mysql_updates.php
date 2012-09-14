<?php

$SQL[] = "ALTER TABLE blog_authmembers DROP INDEX blog_id;";
$SQL[] = "ALTER TABLE blog_authmembers ADD PRIMARY KEY(blog_id, member_id);";
