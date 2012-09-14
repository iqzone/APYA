<?php

$PRE = trim(ipsRegistry::dbFunctions()->getPrefix());
$DB  = ipsRegistry::DB();

/* Keep db index check happy @link http://community.invisionpower.com/tracker/issue-36825-database-checker-still-failing */

if ( $DB->checkForIndex( 'cache_content_id', 'content_cache_sigs' ) )
{
	$SQL[] = "ALTER TABLE content_cache_sigs DROP KEY cache_content_id, ADD PRIMARY KEY (cache_content_id);";
}

if ( $DB->checkForIndex( 'cache_content_id', 'content_cache_posts' ) )
{
	$SQL[] = "ALTER TABLE content_cache_posts DROP KEY cache_content_id, ADD PRIMARY KEY (cache_content_id);";
}

# Update report center comments table
$SQL[] = "ALTER TABLE rc_comments ADD INDEX report_comments ( rid, approved, comment_date );";
$SQL[] = "UPDATE rc_comments SET approved=1 WHERE approved=0;";

# Add an index on our member groups fields
$SQL[] = "ALTER TABLE members ADD INDEX member_groups ( member_group_id, mgroup_others );";

# Remove the old allow dynamic images setting
$SQL[] = "DELETE FROM core_sys_conf_settings WHERE conf_key IN( 'allow_dynamic_img', 'post_order_column' );";

$SQL[] = "DELETE FROM core_share_links_caches WHERE cache_key='mosttypes';";

