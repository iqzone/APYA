<?php

# 2.1.0 RC 2

$DB  = ipsRegistry::DB();
$PRE = ipsRegistry::dbFunctions()->getPrefix();


$SQL[] = "DELETE FROM skin_templates WHERE template_set_id=2 AND template_group LIKE 'skin_blog%';";
$SQL[] = "DELETE FROM skin_css WHERE css_set_id=2 AND css_group='ipblog';";



