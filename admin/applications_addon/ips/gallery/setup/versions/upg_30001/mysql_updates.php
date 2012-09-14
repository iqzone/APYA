<?php

$DB  = ipsRegistry::DB();

if( !$DB->checkForField( 'app', 'rc_classes' ) )
{
$SQL[] = <<<EOF
INSERT INTO rc_classes (onoff, class_title, class_desc, author, author_url, pversion, my_class, group_can_report, mod_group_perm, extra_data, lockd) VALUES(1, 'Gallery Plugin', 'This is the plugin for making reports for the <a href=''http://www.invisiongallery.com/'' target=''_blank''>IP.Gallery</a>.', 'Invision Power Services, Inc', 'http://invisionpower.com', 'v1.0', 'gallery', ',1,2,3,4,6,', ',4,6,', 'a:2:{s:15:"report_supermod";s:1:"1";s:13:"report_bypass";s:1:"1";}', 0);
EOF;
}
else
{
$SQL[] = <<<EOF
INSERT INTO rc_classes (onoff, class_title, class_desc, author, author_url, pversion, my_class, group_can_report, mod_group_perm, extra_data, lockd, app) VALUES(1, 'Gallery Plugin', 'This is the plugin for making reports for the <a href=''http://www.invisiongallery.com/'' target=''_blank''>IP.Gallery</a>.', 'Invision Power Services, Inc', 'http://invisionpower.com', 'v1.0', 'gallery', ',1,2,3,4,6,', ',4,6,', 'a:2:{s:15:"report_supermod";s:1:"1";s:13:"report_bypass";s:1:"1";}', 0, 'gallery');
EOF;
}