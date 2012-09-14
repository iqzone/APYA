<?php
/*
+--------------------------------------------------------------------------
|   IP.Board v3.3.3
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2004 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
*/


# Nothing of interest!
$SQL	= array();

$SQL[]	= "INSERT INTO cal_calendars (cal_id, cal_title, cal_moderate, cal_position, cal_event_limit, cal_bday_limit, cal_rss_export, cal_rss_export_days, cal_rss_export_max, cal_rss_update, cal_rss_update_last, cal_rss_cache, cal_permissions) VALUES (1, 'Community Calendar', 1, 0, 2, 1, 1, 14, 20, 1440, <%time%>, '', 'a:3:{s:9:\"perm_read\";s:1:\"*\";s:9:\"perm_post\";s:3:\"4,3\";s:10:\"perm_nomod\";s:0:\"\";}');";

$SQL[]	= "INSERT INTO login_methods (login_id, login_title, login_description, login_folder_name, login_maintain_url, login_register_url, login_type, login_alt_login_html, login_date, login_settings, login_enabled, login_safemode, login_installed, login_replace_form, login_allow_create) VALUES (1, 'IPB Internal', 'The standard method of authentication', 'internal', '', '', 'passthrough', '', <%time%>, 0, 1, 1, 1, 0, 1);";
$SQL[]	= "INSERT INTO login_methods (login_id, login_title, login_description, login_folder_name, login_maintain_url, login_register_url, login_type, login_alt_login_html, login_date, login_settings, login_enabled, login_safemode, login_installed, login_replace_form, login_allow_create) VALUES (2, 'LDAP Authentication', 'LDAP / Active Directory Authentication', 'ldap', '', '', 'passthrough', '', <%time%>, 0, 0, 1, 0, 0, 1);";
$SQL[]	= "INSERT INTO login_methods (login_id, login_title, login_description, login_folder_name, login_maintain_url, login_register_url, login_type, login_alt_login_html, login_date, login_settings, login_enabled, login_safemode, login_installed, login_replace_form, login_allow_create) VALUES (3, 'External Database', 'Authentication via an external database', 'external', '', '', 'passthrough', '', <%time%>, 0, 0, 1, 0, 0, 1);";

$SQL[]	= "INSERT INTO task_manager (task_title, task_file, task_next_run, task_week_day, task_month_day, task_hour, task_minute, task_cronkey, task_log, task_description, task_enabled, task_key, task_safemode) VALUES ('Update Topic Views Counter', 'updateviews.php', <%time%>, -1, -1, 3, -1, 'ddce954b5ba1c163bc627ca20725b595', 0, 'Used when topic views are not incremented immediately', 1, 'updateviews', 0);";
$SQL[]	= "INSERT INTO task_manager (task_title, task_file, task_next_run, task_week_day, task_month_day, task_hour, task_minute, task_cronkey, task_log, task_description, task_enabled, task_key, task_safemode) VALUES ('Subscription Expiration Notification', 'expiresubs.php', <%time%>, -1, -1, 1, 0, '21fa5f52cf9122c6fe940e1c6dac0b5a', 1, 'Sends out an email to all members who have a subscription due to expire', 1, 'expiresubs', 0);";
$SQL[]	= "INSERT INTO task_manager (task_title, task_file, task_next_run, task_week_day, task_month_day, task_hour, task_minute, task_cronkey, task_log, task_description, task_enabled, task_key, task_safemode) VALUES ('RSS Import Update', 'rssimport.php', <%time%>, -1, -1, -1, 30, '8f17dc0ba334e5f18e762f154365a578', 0, 'Imports any new RSS articles', 1, 'rssimport', 1);";

$SQL[]	= "INSERT INTO task_manager (task_title, task_file, task_next_run, task_week_day, task_month_day, task_hour, task_minute, task_cronkey, task_log, task_description, task_enabled, task_key, task_safemode) VALUES ('Expire Paid Subscriptions', 'doexpiresubs.php', 1121212800, -1, -1, 0, 0, 'bb57399ea05eb9240b42d5d5f53575fb', 1, 'Unsubscribes members from their subscribed packages', 1, 'doexpiresubs', 1)";


$SQL[]	= "INSERT INTO components (com_id, com_title, com_author, com_url, com_version, com_date_added, com_menu_data, com_enabled, com_safemode, com_section, com_filename, com_description, com_url_title, com_url_uri, com_position) VALUES (1, 'Invision Gallery', 'Invision Power Services', 'http://www.invisiongallery.com', '1.3.0', 1113309894, 'a:1:{i:1;a:5:{s:9:\"menu_text\";s:24:\"Purchase and Information\";s:8:\"menu_url\";s:9:\"code=show\";s:13:\"menu_redirect\";i:0;s:12:\"menu_permbit\";s:0:\"\";s:13:\"menu_permlang\";s:0:\"\";}}', 0, 1, 'gallery', 'gallery', 'Complete gallery system for your members', '{ipb.lang[\'gallery\']}', '{ipb.base_url}act=module&module=gallery', 1);";
$SQL[]	= "INSERT INTO components (com_id, com_title, com_author, com_url, com_version, com_date_added, com_menu_data, com_enabled, com_safemode, com_section, com_filename, com_description, com_url_title, com_url_uri, com_position) VALUES (2, 'Invision Community Blog', 'Invision Power Services', 'http://www.invisionblog.com', '1.1.2', 1113310263, 'a:1:{i:1;a:5:{s:9:\"menu_text\";s:24:\"Purchase and Information\";s:8:\"menu_url\";s:9:\"code=show\";s:13:\"menu_redirect\";i:0;s:12:\"menu_permbit\";s:0:\"\";s:13:\"menu_permlang\";s:0:\"\";}}', 0, 1, 'blog', 'blog', 'Blogging addition for your members', '{ipb.lang[\'blog\']}', '{ipb.base_url}automodule=blog', -1);";
$SQL[]	= "INSERT INTO components (com_id, com_title, com_author, com_url, com_version, com_date_added, com_menu_data, com_enabled, com_safemode, com_section, com_filename, com_description, com_url_title, com_url_uri, com_position) VALUES (3, 'Invision Chat (ParaChat)', 'Invision Power Services', 'http://chat.invisionsitetools.com', '2.1', 1113313895, 'a:1:{i:1;a:5:{s:9:\"menu_text\";s:24:\"Purchase and Information\";s:8:\"menu_url\";s:9:\"code=show\";s:13:\"menu_redirect\";i:0;s:12:\"menu_permbit\";s:0:\"\";s:13:\"menu_permlang\";s:0:\"\";}}', 0, 1, 'chatpara', 'chatpara', 'Full real-time chat system for your members', '{ipb.lang[\'live_chat\']}', '{ipb.base_url}autocom=chatpara', 2);";
$SQL[]	= "INSERT INTO components (com_id, com_title, com_author, com_url, com_version, com_date_added, com_menu_data, com_enabled, com_safemode, com_section, com_filename, com_description, com_url_title, com_url_uri, com_position) VALUES (4, 'Invision Copyright Removal', 'Invision Power Services', 'http://www.invisionboard.com', '2.1', 1113314009, 'a:1:{i:1;a:5:{s:9:\"menu_text\";s:24:\"Purchase and Information\";s:8:\"menu_url\";s:9:\"code=show\";s:13:\"menu_redirect\";i:0;s:12:\"menu_permbit\";s:0:\"\";s:13:\"menu_permlang\";s:0:\"\";}}', 1, 1, 'copyright', 'copyright', 'Allows the copyright notices to be removed from the board\'s output', '', '', 3);";

$SQL[]	= "DELETE FROM conf_settings WHERE conf_key IN ( 'csite_article_forum'     , 'csite_article_max', 'csite_article_recent_on',
														   'csite_article_recent_max', 'csite_article_len', 'csite_discuss_on',
														   'csite_discuss_max'       , 'csite_discuss_len', 'csite_poll_url',
														   'csite_online_show', 'poll_disable_noreply' );";

# UPDATE KNOWN CONF_TITLE KEYS
$SQL[]	= "UPDATE conf_settings_titles SET conf_title_keyword='general' WHERE conf_title_id=1;";
$SQL[]	= "UPDATE conf_settings_titles SET conf_title_keyword='boardoffline' WHERE conf_title_id=15;";
$SQL[]	= "UPDATE conf_settings_titles SET conf_title_keyword='calendar' WHERE conf_title_id=9;";
$SQL[]	= "UPDATE conf_settings_titles SET conf_title_keyword='converge' WHERE conf_title_id=18;";
$SQL[]	= "UPDATE conf_settings_titles SET conf_title_keyword='coppa' WHERE conf_title_id=8;";
$SQL[]	= "UPDATE conf_settings_titles SET conf_title_keyword='cpusaving' WHERE conf_title_id=2;";
$SQL[]	= "UPDATE conf_settings_titles SET conf_title_keyword='date' WHERE conf_title_id=3;";
$SQL[]	= "UPDATE conf_settings_titles SET conf_title_keyword='email' WHERE conf_title_id=12;";
$SQL[]	= "UPDATE conf_settings_titles SET conf_title_keyword='searchsetup' WHERE conf_title_id=19;";
