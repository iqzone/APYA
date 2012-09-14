<?php

// Reset short entries (#35240)
$SQL[] = "UPDATE blog_entries SET entry_short='';";

# IPB might not have it yet
if ( ! ipsRegistry::DB()->checkForField( 'blogs_recache', 'members' ) ) 
{
	$SQL[] = "ALTER TABLE members ADD blogs_recache tinyint(1) NULL DEFAULT NULL;";
	$SQL[] = "ALTER TABLE members ADD INDEX blogs_recache (blogs_recache);";
}

$SQL[] = "UPDATE members SET blogs_recache=1,has_blog='' WHERE has_blog='recache';";

# New indexes, to avoid filesorts & full table scans
$SQL[] = "ALTER TABLE blog_comments DROP INDEX comment_member_id, ADD INDEX comment_member_id ( member_id, comment_date );";
$SQL[] = "ALTER TABLE blog_category_mapping DROP INDEX map_entry_id, ADD INDEX map_blog_id ( map_blog_id, map_entry_id );";
