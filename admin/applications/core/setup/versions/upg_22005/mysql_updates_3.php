<?php
/*
+--------------------------------------------------------------------------
|   IP.Board v3.3.3
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2004 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
*/


# Nothing of interest!

$SQL[] ="ALTER TABLE groups DROP g_calendar_post;";

$SQL[] ="ALTER TABLE validating ADD prev_email VARCHAR(150) NOT NULL DEFAULT '0';";

$SQL[] ="ALTER TABLE forums CHANGE position position INT(5)  UNSIGNED default '0';";
$SQL[] ="ALTER TABLE forums ADD newest_title VARCHAR( 128 ) NULL , ADD newest_id INT( 10 ) NOT NULL default '0' ;";
$SQL[] ="ALTER TABLE forums ADD password_override VARCHAR( 255 ) AFTER password;";


$SQL[] ="ALTER TABLE search_results CHANGE topic_id topic_id text NULL;";
$SQL[] ="ALTER TABLE search_results CHANGE post_id post_id text NULL;";
$SQL[] ="ALTER TABLE search_results CHANGE query_cache query_cache text NULL;";

$SQL[] ="ALTER TABLE cache_store change cs_value cs_value mediumtext NULL;";

$SQL[] ="ALTER TABLE login_methods ADD login_user_id VARCHAR(255) NOT NULL default 'username';";
$SQL[] ="ALTER TABLE login_methods ADD login_logout_url  VARCHAR(255) NOT NULL default '';";
$SQL[] ="ALTER TABLE login_methods ADD login_login_url VARCHAR(255) NOT NULL default '';";

$SQL[] ="ALTER TABLE sessions CHANGE id id VARCHAR(60) DEFAULT '0' NOT NULL;";

$SQL[] ="ALTER TABLE voters ADD INDEX ( tid );";

$SQL[] ="ALTER TABLE faq ADD position SMALLINT( 3 ) DEFAULT '0' NOT NULL;";

$SQL[] ="ALTER TABLE polls ADD poll_only TINYINT( 1 ) DEFAULT '0' NOT NULL;";

# UPDATES
$SQL[] ="UPDATE subscription_methods SET submethod_desc='All major credit cards accepted. See <a href=\"https://www.paypal.com\" target=\"_blank\">PayPal</a> for more information.' WHERE LOWER(submethod_name)='paypal';";

$SQL[] ="UPDATE groups SET g_photo_max_vars='100:150:150';";

$SQL[] ="UPDATE attachments SET attach_rel_id=attach_pid, attach_rel_module='post' where attach_pid > 0;";
$SQL[] ="UPDATE attachments SET attach_rel_id=attach_msg, attach_rel_module='msg' where attach_msg > 0;";

$SQL[] ="UPDATE login_methods SET login_settings = '1', login_installed = '1' WHERE login_folder_name='ldap';";
$SQL[] ="UPDATE login_methods SET login_settings = '1', login_installed = '1' WHERE login_folder_name='external';";

$SQL[] ="INSERT INTO login_methods (login_title, login_description, login_folder_name, login_maintain_url, login_register_url, login_type, login_alt_login_html, login_date, login_settings, login_enabled, login_safemode, login_installed, login_replace_form, login_allow_create) VALUES ('IP.Converge', 'Internal Use Only', 'ipconverge', '', '', 'passthrough', '', 0, 0, 0, 1, 1, 0, 1);";
$SQL[] ="UPDATE forums SET permission_showtopic=1 WHERE parent_id='-1';";

# MEMBERS
$SQL[] = "UPDATE members SET members_l_display_name=LOWER(members_display_name);";
$SQL[] = "UPDATE members SET members_l_username=LOWER(name);";

