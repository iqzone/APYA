<?php
/*
+--------------------------------------------------------------------------
|   IP.Board v3.3.3
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2009 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
*/

$PRE = trim(ipsRegistry::dbFunctions()->getPrefix());

/* Disable non-IPS hooks */
$SQL[] = "UPDATE core_hooks SET hook_enabled=0 WHERE hook_author!='Invision Power Services, Inc.' AND hook_author!='Invision Power Services, Inc';";

/* Cleanup some old CSS files */
$SQL[] = "DELETE FROM skin_cache WHERE cache_value_1 in ('ipb_reset','ipb_bbcode','lightbox');";
$SQL[] = "DELETE FROM skin_css WHERE css_group in ('ipb_reset','ipb_bbcode','lightbox');";
$SQL[] = "DELETE FROM skin_css_previous WHERE p_css_group in ('ipb_reset','ipb_bbcode','lightbox');";

/* Remove old settings and setting groups */
$SQL[] = "DELETE FROM core_sys_conf_settings WHERE conf_key IN( 'threaded_per_page', 'spider_anon', 'enable_sockets', 'spam_service_api_key', 'news_forum_id', 'index_news_link', 
	'csite_title', 'csite_article_date', 'csite_nav_show', 'csite_nav_contents', 'csite_fav_show', 'csite_fav_contents', 'blogs_portal_lastx', 'poll_poll_url', 'recent_topics_article_forum', 
	'recent_topics_article_max', 'portal_exclude_pinned', 'recent_topics_discuss_number', 'forum_trash_can_enable', 'forum_trash_can_id', 'forum_trash_can_use_admin', 'forum_trash_can_use_smod', 
	'forum_trash_can_use_mod', 'guests_ava', 'avatars_on', 'avatar_url', 'avatar_ext', 'avatar_def', 'avup_size_max', 'avatar_dims', 'disable_ipbsize', 'registration_qanda',
	'disable_reportpost', 'captcha_allow_fonts', 'gd_version', 'login_page_info', 'register_page_info', 'cache_calendar', 'disable_online_ip', 'topicmode_default', 'allow_skins',
	'ipb_disable_group_psformat', 'max_sig_length', 'aboutme_emoticons', 'login_change_key', 'disable_flash', 'disable_admin_anon', 'msg_allow_code', 'warn_show_rating', 'short_forum_jump',
	'ips_default_editor', 'poll_tags', 'ipb_reg_show', 'cpu_watch_update', 'report_nemail_enabled', 'report_pm_enabled', 'enable_show_as_titles', 'pre_pinned', 'pre_moved', 'pre_polls', 
	'max_bbcodes_per_post', 'post_wordwrap', 'aboutme_bbcode', 'sig_allow_ibc', 'aboutme_html', 'sig_allow_html', 'msg_allow_html', 'postpage_contents', 'topicpage_contents', 'use_mail_form',
	'resize_linked_img', 'cc_monitor' );";
$SQL[] = "DELETE FROM core_sys_settings_titles WHERE conf_title_keyword in ( 'ipbportal', 'portal_poll', 'portal_recent_topics', 'portal_blogs', 'newssetup', 'trashcansetup',
	'cookies', 'ipbreg', 'twitterconnect' );";

/* Remove portal application - cache will be rebuilt by upgrader automatically */
$SQL[] = "DELETE FROM core_applications WHERE app_directory='portal';";
$SQL[] = "DELETE FROM core_sys_module WHERE sys_module_application='portal';";
$SQL[] = "DELETE FROM upgrade_history WHERE upgrade_app='portal';";
$SQL[] = "DELETE FROM cache_store WHERE cs_key='portal';";
$SQL[] = "DELETE FROM core_sys_lang_words WHERE word_app='portal';";

/* Ensure engine isn't added */
ipsRegistry::$settings['mysql_tbl_type'] = '';

/* Ensure reputation_cache does not have duplicates for new unique key to be added */
$SQL[]	= "CREATE TABLE reputation_cache2 (SELECT * FROM `{$PRE}reputation_cache` WHERE id > 0 GROUP BY app, type, type_id);";
$SQL[]	= "DROP TABLE reputation_cache;";
$SQL[]	= "RENAME TABLE reputation_cache2 TO reputation_cache;";
$SQL[]	= "ALTER TABLE reputation_cache CHANGE id id bigint NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY(id), ADD UNIQUE KEY (app, type, type_id);";

/* This, too, causes many issues.  Have seen several customers where the delete query cannot finish, even after 8 hours. */
/*$SQL[]	= "CREATE TABLE reputation_cache_tempforupgrade SELECT id, CONCAT( app, '-', type, '-', type_id ) as cct, count( CONCAT( app, '-', type, '-', type_id ) ) AS count FROM {$PRE}reputation_cache GROUP BY cct;";
$SQL[]	= "CREATE INDEX tmpIDX ON reputation_cache_tempforupgrade ( count );";
$SQL[]	= "DELETE FROM reputation_cache WHERE id IN( select id from {$PRE}reputation_cache_tempforupgrade WHERE count > 1 );";
$SQL[]	= "DROP TABLE reputation_cache_tempforupgrade;";*/

/*
The original query can take 5+ minutes with a lot of records.  Changed to the above based on feedback in bug report.  Didn't use temp tables due to problems with some shared hosts.
@link	http://community.invisionpower.com/tracker/issue-31514-upgrade-query-taking-huge-amount-of-time

$SQL[] = "DELETE FROM reputation_cache WHERE id IN(
	SELECT id FROM (
		SELECT x.id, CONCAT( x.app, '-', x.type, '-', x.type_id ) as cct, count( CONCAT( x.app, '-', x.type, '-', x.type_id ) ) as count FROM {$PRE}reputation_cache x GROUP BY cct ORDER BY count DESC
		) as q WHERE count > 1 );";*/

/* Delete old cache store records */
$SQL[] = "delete from cache_store where cs_key IN( 'ccMonitor', 'portal' );";

