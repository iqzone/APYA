<?php

$SQL[] = "ALTER TABLE groups ADD g_blog_allowpoll TINYINT(1) NOT NULL DEFAULT '0'";

$SQL[] = "ALTER TABLE blog_entries ADD entry_poll_state tinyint(1) NOT NULL DEFAULT '0'";
$SQL[] = "ALTER TABLE blog_entries ADD entry_last_vote int(10) NOT NULL default'0'";

$SQL[] = "CREATE TABLE blog_polls (
  poll_id mediumint(8) NOT NULL auto_increment,
  entry_id int(10) NOT NULL default '0',
  start_date int(10) default NULL,
  choices text NULL,
  starter_id mediumint(8) NOT NULL default '0',
  votes smallint(5) NOT NULL default '0',
  poll_question varchar(255) default NULL,
  PRIMARY KEY (poll_id),
  KEY entry_id(entry_id)
)";

$SQL[] = "CREATE TABLE blog_voters (
  vote_id int(10) NOT NULL auto_increment,
  ip_address varchar(16) NOT NULL default '',
  vote_date int(10) NOT NULL default '0',
  entry_id int(10) NOT NULL default '0',
  member_id varchar(32) default NULL,
  PRIMARY KEY (vote_id),
  KEY entry_id(entry_id, member_id)
)";
