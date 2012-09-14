<?php

$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_array) VALUES ('bbcode', '', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_array) VALUES ('moderators', '', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_array) VALUES ('multimod', '', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_array) VALUES ('banfilters', '', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_array) VALUES ('attachtypes', '', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_array) VALUES ('emoticons', '', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_array) VALUES ('badwords', '', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_array) VALUES ('systemvars', '', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_array) VALUES ('adminnotes', '', 0);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_array) VALUES ('ranks', '', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_array) VALUES ('group_cache', '', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_array) VALUES ('stats', '', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_array) VALUES ('profilefields', 'a:0:{}', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_array) VALUES ('settings','', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_array) VALUES ('birthdays', 'a:0:{}', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_array) VALUES ('calendar', 'a:0:{}', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_array) VALUES ('calendars', 'a:0:{}', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_array) VALUES ('chatting', 'a:0:{}', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_array) VALUES ('rss_export', 'a:0:{}', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_array) VALUES ('rss_calendar', 'a:0:{}', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_array) VALUES ('announcements', 'a:0:{}', 1);";
$INSERT[] = "INSERT INTO cache_store (cs_key, cs_value, cs_array) VALUES ('hooks', 'a:0:{}', 1);";

$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set, emo_position) VALUES (1, ':)', 'smile.png', 1, 'default', 1);";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set, emo_position) VALUES (2, ';)', 'wink.png', 1, 'default', 2);";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set, emo_position) VALUES (3, ':P', 'tongue.png', 1, 'default', 3);";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set, emo_position) VALUES (4, ':D', 'biggrin.png', 1, 'default', 4);";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set, emo_position) VALUES (5, ':lol:', 'laugh.png', 1, 'default', 5);";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set, emo_position) VALUES (6, ':(', 'sad.png', 1, 'default', 6);";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set, emo_position) VALUES (7, ':angry:', 'angry.png', 1, 'default', 7);";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set, emo_position) VALUES (8, ':mellow:', 'mellow.png', 1, 'default', 8);";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set, emo_position) VALUES (9, ':huh:', 'huh.png', 1, 'default', 9);";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set, emo_position) VALUES (10, '^_^', 'happy.png', 0, 'default', 10);";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set, emo_position) VALUES (11, ':o', 'ohmy.png', 1, 'default', 11);";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set, emo_position) VALUES (12, 'B)', 'cool.png', 1, 'default', 12);";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set, emo_position) VALUES (13, ':rolleyes:', 'rolleyes.gif', 1, 'default', 13);";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set, emo_position) VALUES (14, '-_-', 'sleep.png', 0, 'default', 14);";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set, emo_position) VALUES (15, '&lt;_&lt;', 'dry.png', 1, 'default', 15);";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set, emo_position) VALUES (16, ':wub:', 'wub.png', 0, 'default', 16);";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set, emo_position) VALUES (17, ':unsure:', 'unsure.png', 1, 'default', 17);";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set, emo_position) VALUES (18, ':wacko:', 'wacko.png', 0, 'default', 18);";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set, emo_position) VALUES (19, ':blink:', 'blink.png', 1, 'default', 19);";
$INSERT[] = "INSERT INTO emoticons (id, typed, image, clickable, emo_set, emo_position) VALUES (20, ':ph34r:', 'ph34r.png', 0, 'default', 20);";

# Profile fields stuff
$INSERT[] = "INSERT INTO pfields_data (pf_id, pf_title, pf_desc, pf_content, pf_type, pf_not_null, pf_member_hide, pf_max_input, pf_member_edit, pf_position, pf_show_on_reg, pf_input_format, pf_admin_only, pf_topic_format, pf_group_id, pf_icon, pf_key) VALUES
(1, 'AIM', '', '', 'input', 0, 0, 0, 1, 0, 0, '', 0, '', 1, 'style_extra/cprofile_icons/profile_aim.gif', 'aim'),
(2, 'MSN', '', '', 'input', 0, 0, 0, 1, 0, 0, '', 0, '', 1, 'style_extra/cprofile_icons/profile_msn.gif', 'msn'),
(3, 'Website URL', '', '', 'input', 0, 0, 0, 1, 0, 0, '', 0, '', 1, 'style_extra/cprofile_icons/profile_website.gif', 'website'),
(4, 'ICQ', '', '', 'input', 0, 0, 0, 1, 0, 0, '', 0, '', 1, 'style_extra/cprofile_icons/profile_icq.gif', 'icq'),
(5, 'Gender', '', 'u=Not Telling|m=Male|f=Female', 'drop', 0, 0, 0, 1, 0, 0, '', 0, '', 2, '', 'gender'),
(6, 'Location', '', '', 'input', 0, 0, 0, 1, 0, 0, '', 0, '<span class=\'ft\'>{title}</span><span class=\'fc\'>{content}</span>', 2, '', 'location'),
(7, 'Interests', '', '', 'textarea', 0, 0, 0, 1, 0, 0, '', 0, '', 2, '', 'interests'),
(8, 'Yahoo', '', '', 'input', 0, 0, 0, 1, 0, 0, '', 0, '', 1, 'style_extra/cprofile_icons/profile_yahoo.gif', 'yahoo'),
(9, 'Jabber', '', '', 'input', 0, 0, 0, 1, 0, 0, '', 0, '', 1, 'style_extra/cprofile_icons/profile_jabber.gif', 'jabber'),
(10, 'Skype', '', '', 'input', 0, 0, 0, 1, 0, 0, '', 0, '', 1, 'style_extra/cprofile_icons/profile_skype.gif', 'skype')";

$INSERT[] = "INSERT INTO pfields_groups (pf_group_id, pf_group_name, pf_group_key) VALUES
(1, 'Contact Methods', 'contact'),
(2, 'Profile Information', 'profile_info')";

$INSERT[] = "INSERT INTO core_sys_lang VALUES(1, 'en_US', 'English (USA)', 1, 0, 1)";

$INSERT[] = "INSERT INTO titles (id, posts, title, pips) VALUES (1, 0, 'Newbie', '1');";
$INSERT[] = "INSERT INTO titles (id, posts, title, pips) VALUES (2, 10, 'Member', '2');";
$INSERT[] = "INSERT INTO titles (id, posts, title, pips) VALUES (4, 30, 'Advanced Member', '3');";

$INSERT[] ="INSERT INTO permission_index VALUES(1, 'members', 'profile_view', 1, '*', '', '', '', '', '', '', 0, 0, NULL)";
$INSERT[] ="INSERT INTO permission_index VALUES(2, 'core', 'help', 1, '*', '', '', '', '', '', '', 0, 0, NULL)";

/* Report center stuff */

$INSERT[] = <<<EOF
INSERT INTO rc_classes (onoff, class_title, class_desc, author, author_url, pversion, my_class, group_can_report, mod_group_perm, extra_data, lockd, app)
VALUES(1, 'Simple Plugin Example', 'Plugin that does not require any programming, but does need to be configured.', 'Invision Power Services, Inc', 'http://invisionpower.com', 'v1.0', 'default', ',3,4,6,', ',4,6,', 'a:5:{s:14:"required_input";a:1:{s:8:"video_id";s:13:"[^A-Za-z0-9_]";}s:10:"string_url";s:41:"http://www.youtube.com/watch?v={video_id}";s:12:"string_title";s:25:"#PAGE_TITLE# ({video_id})";s:13:"section_title";s:7:"YouTube";s:11:"section_url";s:22:"http://www.youtube.com";}', 1, 'core');
EOF;
$INSERT[] = <<<EOF
INSERT INTO rc_classes (onoff, class_title, class_desc, author, author_url, pversion, my_class, group_can_report, mod_group_perm, extra_data, lockd, app)
VALUES(1, 'Forum Plugin', 'This is the plugin used for reporting posts on the forum.', 'Invision Power Services, Inc', 'http://invisionpower.com', 'v1.0', 'post', ',1,2,3,4,6,', ',4,6,', 'a:1:{s:15:"report_supermod";i:1;}', 1, 'forums');
EOF;
$INSERT[] = <<<EOF
INSERT INTO rc_classes (onoff, class_title, class_desc, author, author_url, pversion, my_class, group_can_report, mod_group_perm, extra_data, lockd, app)
VALUES(1, 'Private Messages Plugin', 'This plugin allows private messages to be reported', 'Invision Power Services, Inc', 'http://invisionpower.com', 'v1.0', 'messages', ',1,2,3,4,6,', ',4,6,', 'a:1:{s:18:"plugi_messages_add";s:5:"4";}', 1, 'members');
EOF;
$INSERT[] = <<<EOF
INSERT INTO rc_classes (onoff, class_title, class_desc, author, author_url, pversion, my_class, group_can_report, mod_group_perm, extra_data, lockd, app)
VALUES(1, 'Member Profiles', 'Allows you to report member profiles', 'Invision Power Services, Inc', 'http://invisionpower.com', 'v1.0', 'profiles', ',1,2,3,4,6,', ',4,6,', 'N;', 1, 'members');
EOF;


$INSERT[] = "INSERT INTO rc_status VALUES(1, 'New Report', 1, 5, 1, 0, 1, 1);";
$INSERT[] = "INSERT INTO rc_status VALUES(2, 'Under Review', 1, 5, 0, 0, 1, 2);";
$INSERT[] = "INSERT INTO rc_status VALUES(3, 'Complete', 0, 0, 0, 1, 0, 3);";

$INSERT[] = <<<EOF
INSERT INTO rc_status_sev (id, status, points, img, is_png, width, height) VALUES
(1, 1, 1, 'style_extra/report_icons/flag_gray.png', 1, 16, 16),
(2, 1, 2, 'style_extra/report_icons/flag_blue.png', 1, 16, 16),
(3, 1, 4, 'style_extra/report_icons/flag_green.png', 1, 16, 16),
(4, 1, 7, 'style_extra/report_icons/flag_orange.png', 1, 16, 16),
(5, 1, 12, 'style_extra/report_icons/flag_red.png', 1, 16, 16),
(6, 2, 1, 'style_extra/report_icons/flag_gray_review.png', 1, 16, 16),
(7, 3, 0, 'style_extra/report_icons/completed.png', 1, 16, 16),
(8, 2, 2, 'style_extra/report_icons/flag_blue_review.png', 1, 16, 16),
(9, 2, 4, 'style_extra/report_icons/flag_green_review.png', 1, 16, 16),
(10, 2, 7, 'style_extra/report_icons/flag_orange_review.png', 1, 16, 16),
(11, 2, 12, 'style_extra/report_icons/flag_red_review.png', 1, 16, 16);
EOF;

$INSERT[] = "INSERT INTO reputation_levels VALUES(1, -20, 'Bad', '');";
$INSERT[] = "INSERT INTO reputation_levels VALUES(2, -10, 'Poor', '');";
$INSERT[] = "INSERT INTO reputation_levels VALUES(3, 0, 'Neutral', '');";
$INSERT[] = "INSERT INTO reputation_levels VALUES(4, 10, 'Good', '');";
$INSERT[] = "INSERT INTO reputation_levels VALUES(5, 20, 'Excellent', '');";

$INSERT[] = "INSERT INTO core_share_links (share_id, share_title, share_key, share_enabled, share_position, share_canonical) VALUES(1, 'Twitter', 'twitter', 1, 1, 1);";
$INSERT[] = "INSERT INTO core_share_links (share_id, share_title, share_key, share_enabled, share_position, share_canonical) VALUES(2, 'Facebook', 'facebook', 1, 2, 1);";
$INSERT[] = "INSERT INTO core_share_links (share_id, share_title, share_key, share_enabled, share_position, share_canonical) VALUES(3, 'Google Plus One', 'googleplusone', 1, 3, 1);";
$INSERT[] = "INSERT INTO core_share_links (share_id, share_title, share_key, share_enabled, share_position, share_canonical) VALUES(4, 'Digg', 'digg', 1, 4, 1);";
$INSERT[] = "INSERT INTO core_share_links (share_id, share_title, share_key, share_enabled, share_position, share_canonical) VALUES(5, 'Del.icio.us', 'delicious', 1, 6, 1);";
$INSERT[] = "INSERT INTO core_share_links (share_id, share_title, share_key, share_enabled, share_position, share_canonical) VALUES(6, 'Reddit', 'reddit', 1, 7, 1);";
$INSERT[] = "INSERT INTO core_share_links (share_id, share_title, share_key, share_enabled, share_position, share_canonical) VALUES(7, 'StumbleUpon', 'stumble', 1, 8, 1);";
$INSERT[] = "INSERT INTO core_share_links (share_id, share_title, share_key, share_enabled, share_position, share_canonical) VALUES(8, 'Email', 'email', 1, 9, 1);";
$INSERT[] = "INSERT INTO core_share_links (share_id, share_title, share_key, share_enabled, share_position, share_canonical) VALUES(10, 'Print', 'print', 1, 10, 0);";
$INSERT[] = "INSERT INTO core_share_links (share_id, share_title, share_key, share_enabled, share_position, share_canonical) VALUES(11, 'Download', 'download', 1, 11, 0);";

$INSERT[] = "INSERT INTO members_warn_reasons (wr_id, wr_name, wr_points, wr_points_override, wr_remove, wr_remove_unit, wr_remove_override, wr_order)
VALUES
	(1, 'Spamming', 1, 0, 0, 'h', 0, 1),
	(2, 'Inappropriate Language', 1, 0, 0, 'h', 0, 2),
	(3, 'Signature Violation', 1, 0, 0, 'h', 0, 3),
	(4, 'Abusive Behaviour', 1, 0, 0, 'h', 0, 4),
	(5, 'Topic Bumping', 1, 0, 0, 'h', 0, 5);";


