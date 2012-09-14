<?php


$TABLE[] = "CREATE TABLE portal_blocks (
  block_id int(5) NOT NULL auto_increment,
  title varchar(255) NOT NULL,
  name varchar(255) NOT NULL,
  align tinyint(1) NOT NULL default '0',
  template tinyint(1) NOT NULL default '0', 
  position int(5) NOT NULL default '0',
  block_code text NOT NULL,
  PRIMARY KEY  (block_id)
);";

$TABLE[] = "CREATE TABLE IF NOT EXISTS portal_tables_conf (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  title varchar(140) NOT NULL,
  table_name varchar(100) NOT NULL,
  primary_key_name varchar(50) NOT NULL,
  text_name TEXT NOT NULL,
  date_name varchar(50) NOT NULL,
  user_id_name varchar(50) NOT NULL,
  status int(1) DEFAULT 1,
  PRIMARY KEY (id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";

$TABLE[] = "CREATE TABLE IF NOT EXISTS portal_logbook (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  conf_table_id int(10) unsigned NOT NULL,
  action_id int(10) unsigned NOT NULL,
  user_id int(10) unsigned NOT NULL,
  screen_info text NOT NULL,
  created_at int(10) unsigned NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
";

$TABLE[] = "CREATE TABLE IF NOT EXISTS log_reply (
  log_id int(10) unsigned NOT NULL AUTO_INCREMENT,
  log_reply_id int(10) unsigned NOT NULL,
  log_member_id int(10) unsigned NOT NULL,
  log_date int(10) unsigned NOT NULL,
  log_content varchar(255) NOT NULL,
  is_rt tinyint(4) DEFAULT '0',
  PRIMARY KEY (log_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
";

?>