<?php
# FORUMS: Last field: forums_bitoptions

$INSERT[] = "INSERT INTO forums VALUES (1, 0, 0, 0, 0, '', 'A Test Category', 'A test category that may be removed at any time', 1, 1, 0, '', '', '', 0, 'last_post', 'Z-A', 30, 'all', 0, 0, 1, 1, 1, 0, -1, '', 0, 0, '', '', '', 0, '', 1, 0, 0, 1, 0, '', 0,0,0,1,0,'a-test-category','','','', 0, 0, 0, 0, 0, '', 0, 0);";
$INSERT[] = "INSERT INTO forums VALUES (2, 1, 0, UNIX_TIMESTAMP(), 1, '<%admin_name%>', 'A Test Forum', 'A test forum that may be removed at any time', 1, 1, 0, '', '', 'Welcome&#33;', 1, 'last_post', 'Z-A', 100, 'all', 0, 0, 1, 1, 1, 0, 1, '', 0, 0, '', '', '', 1, '', 0, 0, 0, 1, 0, 'Welcome&#33;', 1,0,0,1,0,'a-test-forum','welcome','<%admin_seoname%>','a:1:{i:1;i:<%time%>;}', 0, 0, 0, 0, 0, '', 0, 0);";
$INSERT[] ="INSERT INTO permission_index VALUES(NULL, 'forums', 'forum', 1, '*', '*', '*', '*', ',4,3,', ',4,3,', '', 0, 0, NULL)";
$INSERT[] ="INSERT INTO permission_index VALUES(NULL, 'forums', 'forum', 2, '*', '*', '*', '*', ',4,3,', ',4,3,', '', 0, 0, NULL)";

$INSERT[] = "INSERT INTO forum_perms SET perm_name='Validating Forum Set', perm_id=1";
$INSERT[] = "INSERT INTO forum_perms SET perm_name='Member Forum Set', perm_id=3";
$INSERT[] = "INSERT INTO forum_perms SET perm_name='Guest Forum Set', perm_id=2";
$INSERT[] = "INSERT INTO forum_perms SET perm_name='Admin Forum Set', perm_id=4";
$INSERT[] = "INSERT INTO forum_perms SET perm_name='Banned Forum Set', perm_id=5";
$INSERT[] = "INSERT INTO forum_perms SET perm_name='Moderator Forum Set', perm_id=6";

$INSERT[] = "INSERT INTO posts (pid, append_edit, edit_time, author_id, author_name, use_sig, use_emo, ip_address, post_date, post, queued, topic_id, new_topic, edit_name, post_key, post_htmlstate) VALUES (1, 0, NULL, 1, '<%admin_name%>', 0, 1, '127.0.0.1', UNIX_TIMESTAMP(), 'Welcome to your new Invision Power Board&#33;<br /><br />  <br /><br /> Congratulations on your purchase of our software and setting up your community.  Please take some time and read through the Getting Started Guide and Administrator Documentation.  The Getting Started Guide will walk you through some of the necessary steps to setting up an IP.Board and starting your community. The Administrator Documentation takes you through the details of the capabilities of IP.Board.<br /><br />  <br /><br /> You can remove this message, topic, forum or even category at any time.<br /><br />  <br /><br /> [url=http://external.ipslink.com/ipboard30/landing/?p=docs-ipb]Go to the documentation now...[/url]', 0, 1, 1, NULL, '0', 0);";

# TOPICS: seo_first_name
$INSERT[] = "INSERT INTO topics VALUES (1, 'Welcome&#33;', 'open', 0, 1, UNIX_TIMESTAMP(), 1, UNIX_TIMESTAMP(), '<%admin_name%>', '<%admin_name%>', '0', 0, 0, 2, 1, 1, 0, null, 0, 1, 0, 0, 0, 0, 0, 'welcome', '<%admin_seoname%>', '<%admin_seoname%>', 0, 0, 0, 0, UNIX_TIMESTAMP());";

