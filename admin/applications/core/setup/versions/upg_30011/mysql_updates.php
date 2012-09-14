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

# 3.0.4

$PRE = trim(ipsRegistry::dbFunctions()->getPrefix());
$DB  = ipsRegistry::DB();

$SQL[] = "ALTER TABLE topics ADD INDEX start_date (start_date);";

if ( ! $DB->checkForField( 'map_last_topic_reply', 'message_topic_user_map' ) )
{
	$SQL[] = "ALTER TABLE message_topic_user_map ADD map_last_topic_reply INT(10) NOT NULL default '0';";
}

// This is run in 3.0.4-3.0.5 routine as well, so no need to run twice
//$SQL[] = "UPDATE message_topic_user_map m, `{$PRE}message_topics` t SET m.map_last_topic_reply=t.mt_last_post_time WHERE m.map_topic_id=t.mt_id;";

$SQL[] = "ALTER TABLE core_sys_module CHANGE sys_module_version sys_module_version VARCHAR( 32 ) NOT NULL;";
$SQL[] = "ALTER TABLE core_sys_lang_words CHANGE word_js word_js TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';";

$SQL[] = "UPDATE core_sys_conf_settings SET conf_value=conf_default WHERE conf_key='links_external' AND conf_value='';";

$SQL[] = "ALTER TABLE members CHANGE fb_uid fb_uid BIGINT(20) NOT NULL DEFAULT '0';";

$SQL[] = "delete from core_sys_conf_settings where conf_key='spider_suit';";

$SQL[] = "DELETE FROM core_sys_conf_settings WHERE conf_key='c_cache_days';";

$SQL[] = "ALTER TABLE forums CHANGE permission_array permission_array MEDIUMTEXT NULL DEFAULT NULL;";

$SQL[] = "update profile_portal set pp_setting_count_friends = 1 where pp_setting_count_friends > 0;";
$SQL[] = "update profile_portal set pp_setting_count_comments = 1 where pp_setting_count_comments > 0;";

/* Media tag */
$tag = $DB->buildAndFetch( array( 'select' => '*',
								  'from'   => 'bbcode_mediatag',
								  'where'  => "mediatag_replace LIKE '%webjay.org%'" ) );
								  
if ( $tag['mediatag_id'] )
{
	$DB->update( 'bbcode_mediatag',
		array( 'mediatag_replace' => '<object type="application/x-shockwave-flash" data="{board_url}/public/mp3player.swf" width="300" height="40"><param name="movie" value="{board_url}/public/mp3player.swf" /><param name="FlashVars" value="mp3=$1.mp3&autoplay=1&loop=0&volume=100&showstop=1&showinfo=0" /></object>'),
		'mediatag_id=' . $tag['mediatag_id'] );
}

