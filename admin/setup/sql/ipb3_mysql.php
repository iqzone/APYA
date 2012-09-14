<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * SQL for upgrader
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		Matt Mecham
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		1st December 2008
 * @version		$Revision: 10721 $
 *
 */

$UPGRADE_HISTORY_TABLE = "CREATE TABLE upgrade_history (
  upgrade_id int(10) NOT NULL auto_increment,
  upgrade_version_id int(10) NOT NULL default '0',
  upgrade_version_human varchar(200) NOT NULL default '',
  upgrade_date int(10) NOT NULL default '0',
  upgrade_mid int(10) NOT NULL default '0',
  upgrade_notes text NOT NULL,
  upgrade_app varchar(32) NOT NULL default 'core',
  PRIMARY KEY  (upgrade_id)
);";

$UPGRADE_TABLE_FIELD   = "ALTER TABLE upgrade_history ADD upgrade_app varchar(32) NOT NULL default 'core'";

$UPGRADE_SESSION_TABLE = "CREATE TABLE upgrade_sessions (
	session_id				varchar(32)	NOT NULL default '',
	session_member_id		int(10) NOT NULL default 0,
	session_member_key		varchar(32) NOT NULL default '',
	session_start_time		int(10) NOT NULL default 0,
	session_current_time	int(10) NOT NULL default 0,
	session_ip_address		varchar(16) NOT NULL default '',
	session_section			varchar(32) NOT NULL default '',
	session_post			TEXT,
	session_get				TEXT,
	session_data			TEXT,
	session_extra			TEXT,
	PRIMARY KEY (session_id)
);";

$UPGRADE_TEMPLATE_PREVIOUS = "CREATE TABLE skin_templates_previous (
  p_template_id int(10) NOT NULL AUTO_INCREMENT,
  p_template_group varchar(255) NOT NULL DEFAULT '',
  p_template_content MEDIUMTEXT,
  p_template_name varchar(255) DEFAULT NULL,
  p_template_data TEXT,
  p_template_master_key	VARCHAR(100) NOT NULL DEFAULT '',
  p_template_long_version	VARCHAR(100) NOT NULL DEFAULT '',
  p_template_human_version	VARCHAR(100) NOT NULL DEFAULT '',
  PRIMARY KEY (p_template_id)
);";

$UPGRADE_CSS_PREVIOUS = "CREATE TABLE skin_css_previous (
  p_css_id int(10) NOT NULL AUTO_INCREMENT,
  p_css_group varchar(255) NOT NULL DEFAULT '0',
  p_css_content mediumtext,
  p_css_app varchar(200) NOT NULL DEFAULT '0',
  p_css_attributes text,
  p_css_modules varchar(250) NOT NULL DEFAULT '',
  p_css_master_key VARCHAR(100) NOT NULL DEFAULT '',
  p_css_long_version	VARCHAR(100) NOT NULL DEFAULT '',
  p_css_human_version	VARCHAR(100) NOT NULL DEFAULT '',
  PRIMARY KEY (p_css_id)
);";
