<?php

/* Get DB functions/prefix */
$PRE = ipsRegistry::dbFunctions()->getPrefix();
$DB  = ipsRegistry::DB();

// Remove unused column - sometimes we don't have it thought...
if ( $DB->checkForField( 'comment_queued', 'blog_comments' ) )
{
	$SQL[] = "ALTER TABLE blog_comments DROP comment_queued;";
}

$SQL[] = "ALTER TABLE blog_comments CHANGE ip_address ip_address VARCHAR( 46 ) NULL DEFAULT NULL;";
$SQL[] = "ALTER TABLE blog_trackback CHANGE ip_address ip_address VARCHAR( 46 ) NULL DEFAULT NULL;";
$SQL[] = "ALTER TABLE blog_trackback_spamlogs CHANGE ip_address ip_address VARCHAR( 46 ) NULL DEFAULT NULL;";
$SQL[] = "ALTER TABLE blog_voters CHANGE ip_address ip_address VARCHAR( 46 ) NOT NULL;";
$SQL[] = "ALTER TABLE blog_akismet_logs CHANGE log_date log_date INT( 10 ) NOT NULL DEFAULT '0';";

/* Drop unused fields */
$SQL[] = "ALTER TABLE blog_comments DROP comment_use_sig, DROP comment_use_emo, DROP comment_html_state, DROP comment_append_edit, DROP comment_edit_name;";

/* Mess up indexes */
$SQL[] = "ALTER TABLE blog_entries DROP INDEX entry_status, ADD INDEX entry_status ( entry_status, entry_last_update );";
$SQL[] = "ALTER TABLE blog_comments DROP INDEX comment_entry_id;";
$SQL[] = "ALTER TABLE blog_comments ADD INDEX ( entry_id, comment_approved );";

/* Remove custom skin choice */
$SQL[] = "UPDATE blog_blogs SET blog_skin_id=0;";