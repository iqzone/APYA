<?php

# v1.1.0

$SQL[] = "CREATE TABLE portal_blocks (
  block_id int(5) NOT NULL auto_increment,
  title varchar(255) NOT NULL,
  name varchar(255) NOT NULL,
  align tinyint(1) NOT NULL default '0',
  template tinyint(1) NOT NULL default '0',  
  position int(5) NOT NULL default '0',
  block_code text NOT NULL,
  PRIMARY KEY  (block_id)
);";
