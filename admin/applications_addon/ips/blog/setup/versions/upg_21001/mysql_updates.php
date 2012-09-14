<?php

# 2.1.0 RC 1

$DB  = ipsRegistry::DB();
$PRE = ipsRegistry::dbFunctions()->getPrefix();


$SQL[] = "ALTER TABLE blog_voters CHANGE member_id member_id INT(10) NOT NULL DEFAULT '0';";


