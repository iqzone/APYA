<?php

$SQL[] = "ALTER TABLE blog_lastinfo CHANGE blog_tag_cloud blog_tag_cloud MEDIUMTEXT;";
$SQL[] = "UPDATE blog_lastinfo SET blog_tag_cloud='';";

if( ! ipsRegistry::DB()->checkForField( 'theme_css_overwrite', 'blog_themes' ) )
{
	$SQL[] = "ALTER TABLE blog_themes ADD theme_css_overwrite TINYINT( 1 ) NOT NULL DEFAULT '0';";
}