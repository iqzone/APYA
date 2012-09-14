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

$PRE = trim(ipsRegistry::dbFunctions()->getPrefix());
$DB  = ipsRegistry::DB();

/* Posts table */

$SQL[] = "ALTER TABLE posts CHANGE author_name author_name VARCHAR( 255 ) NULL DEFAULT NULL,
	CHANGE ip_address ip_address VARCHAR( 46 ) NOT NULL,
	ADD post_bwoptions INT(10) UNSIGNED NOT NULL DEFAULT 0,
	ADD pdelete_time INT NOT NULL DEFAULT 0,
	ADD post_field_int INT(10) NOT NULL DEFAULT 0,
	ADD post_field_t1 TEXT NULL DEFAULT NULL,
	ADD post_field_t2 TEXT NULL DEFAULT NULL,
	DROP post_parent,
	DROP INDEX author_id,
	ADD INDEX author_id ( author_id , post_date , queued ),
	ADD INDEX queued (queued,pdelete_time);";



