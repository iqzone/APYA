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

$DB = ipsRegistry::DB();

if ( $DB->checkForTable('core_like_cache') )
{
	$SQL[] = "DROP TABLE core_like_cache;";
}

if ( $DB->checkForTable('core_like') )
{
	$SQL[] = "DROP TABLE core_like;";
}

$SQL[] = "CREATE TABLE core_like_cache (
	like_cache_id		VARCHAR(32),
	like_cache_app		VARCHAR(150) NOT NULL DEFAULT '',
	like_cache_area		VARCHAR(200) NOT NULL DEFAULT '',
	like_cache_rel_id	BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
	like_cache_data		TEXT,
	like_cache_expire	INT(10) NOT NULL DEFAULT '0',
	PRIMARY KEY (like_cache_id)
);";


$SQL[] = "CREATE TABLE core_like (
	like_id				VARCHAR(32),
	like_lookup_id		VARCHAR(32),
	like_app			VARCHAR(150) NOT NULL DEFAULT '',
	like_area			VARCHAR(200) NOT NULL DEFAULT '',
	like_rel_id			BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
	like_member_id		INT(10) UNSIGNED NOT NULL DEFAULT 0,
	like_is_anon		INT(1) NOT NULL DEFAULT 0,
	like_added			INT(10) UNSIGNED NOT NULL DEFAULT 0,
	like_notify_do		INT(1) NOT NULL DEFAULT 0,
	like_notify_meta	TEXT,
	like_notify_freq	VARCHAR(200) NOT NULL DEFAULT '',
	like_notify_sent	INT(10) UNSIGNED NOT NULL DEFAULT 0,
	PRIMARY KEY (like_id),
	KEY find_rel_favs ( like_lookup_id, like_is_anon, like_added),
	KEY like_member_id (like_member_id,like_added)
);";

