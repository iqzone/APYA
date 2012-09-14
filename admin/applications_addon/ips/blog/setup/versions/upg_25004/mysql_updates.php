<?php

$PRE = ipsRegistry::dbFunctions()->getPrefix();

// Delete old links block - moved from 25000 (#34571)
$SQL[] = "DELETE c FROM {$PRE}blog_cblocks c LEFT JOIN {$PRE}blog_default_cblocks d ON c.cblock_type='default' AND c.cblock_ref_id=d.cbdef_id WHERE d.cbdef_function='get_my_links';";
$SQL[] = "DELETE FROM blog_default_cblocks WHERE cbdef_function='get_my_links';";

// Some installs may still have this old (hidden) settings group installed
$SQL[] = "DELETE FROM core_sys_settings_titles WHERE conf_title_keyword='blog_masks';";

// Reset short entries and member blogs (#34865)
#$SQL[] = "UPDATE blog_entries SET entry_short='';"; #Done in the next step
$SQL[] = "UPDATE members SET has_blog='recache' WHERE has_blog != '';";
