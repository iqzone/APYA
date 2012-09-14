<?php

# v1.1.0

$SQL[] = "CREATE TABLE IF NOT EXISTS portal_tables_conf (
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

$SQL[] = "CREATE TABLE IF NOT EXISTS portal_logbook (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  conf_table_id int(10) unsigned NOT NULL,
  action_id int(10) unsigned NOT NULL,
  user_id int(10) unsigned NOT NULL,
  screen_info text NOT NULL,
  created_at int(10) unsigned NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
";
