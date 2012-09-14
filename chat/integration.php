<?php

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/* ADVANCED */

define('SET_SESSION_NAME','');			// Session name
define('DO_NOT_START_SESSION','0');		// Set to 1 if you have already started the session
define('DO_NOT_DESTROY_SESSION','0');	// Set to 1 if you do not want to destroy session on logout
define('SWITCH_ENABLED','1');		
define('INCLUDE_JQUERY','1');	
define('FORCE_MAGIC_QUOTES','0');

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/* DATABASE */

require dirname(dirname(__FILE__)).'/conf_global.php';

// DO NOT EDIT DATABASE VALUES BELOW
// DO NOT EDIT DATABASE VALUES BELOW
// DO NOT EDIT DATABASE VALUES BELOW

define('DB_SERVER',					$INFO['sql_host']						);
define('DB_PORT',					'3306'									);
define('DB_USERNAME',				$INFO['sql_user']						);
define('DB_PASSWORD',				$INFO['sql_pass']						);
define('DB_NAME',					$INFO['sql_database']					);
define('TABLE_PREFIX',				$INFO['sql_tbl_prefix']					);
define('DB_USERTABLE',				'members'								);
define('DB_USERTABLE_NAME',			'members_display_name'					);
define('DB_USERTABLE_USERID',		'member_id'								);
define('DB_USERTABLE_LASTACTIVITY',	'last_activity'							);

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/* FUNCTIONS */

function getUserID() {
	$userid = 0;
	
	if (!empty($_COOKIE['member_id']) && !empty($_COOKIE['pass_hash'])) {
		
		$sql = ("select ".DB_USERTABLE_USERID." userid from ".TABLE_PREFIX.DB_USERTABLE." where member_id = '".mysql_real_escape_string($_COOKIE['member_id'])."' and member_login_key = '".mysql_real_escape_string($_COOKIE['pass_hash'])."'");
		$query = mysql_query($sql);
		$session = mysql_fetch_array($query);

		if ($session['userid'] > 0) {
			$userid = $session['userid'];
		}
	
	}

	return $userid;
}


function getFriendsList($userid,$time) {
	$sql = ("select DISTINCT ".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_USERID." userid, ".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_NAME." username, ".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_LASTACTIVITY." lastactivity, ".TABLE_PREFIX."profile_portal.pp_thumb_photo avatar,  CONCAT(".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_USERID.",'-',".TABLE_PREFIX.DB_USERTABLE.".members_seo_name) link,  cometchat_status.message message, cometchat_status.status from ".TABLE_PREFIX."profile_friends join ".TABLE_PREFIX.DB_USERTABLE." on  ".TABLE_PREFIX."profile_friends.friends_friend_id = ".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_USERID." left join cometchat_status on ".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_USERID." = cometchat_status.userid left join ".TABLE_PREFIX."profile_portal on ".TABLE_PREFIX."profile_portal.pp_member_id = ".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_USERID." where ".TABLE_PREFIX."profile_friends.friends_member_id = '".mysql_real_escape_string($userid)."' and friends_approved = '1' order by username asc");


	if (defined('DISPLAY_ALL_USERS') && DISPLAY_ALL_USERS == 1) {
		$sql = ("select DISTINCT ".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_USERID." userid, ".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_NAME." username, ".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_LASTACTIVITY." lastactivity, ".TABLE_PREFIX."profile_portal.pp_thumb_photo avatar,  CONCAT(".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_USERID.",'-',".TABLE_PREFIX.DB_USERTABLE.".members_seo_name) link,  cometchat_status.message message, cometchat_status.status from  ".TABLE_PREFIX.DB_USERTABLE." left join cometchat_status on ".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_USERID." = cometchat_status.userid left join ".TABLE_PREFIX."profile_portal on ".TABLE_PREFIX."profile_portal.pp_member_id = ".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_USERID." where ".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_USERID." <> '".mysql_real_escape_string($userid)."' and ('".$time."'-".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_LASTACTIVITY." < '".((ONLINE_TIMEOUT)*2)."') order by username asc");
	}
 
	return $sql;
}

function getUserDetails($userid) {
	$sql = ("select ".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_USERID." userid, ".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_NAME." username, ".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_LASTACTIVITY." lastactivity,  ".TABLE_PREFIX."profile_portal.pp_thumb_photo avatar,  CONCAT(".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_USERID.",'-',".TABLE_PREFIX.DB_USERTABLE.".members_seo_name) link, cometchat_status.message message, cometchat_status.status from ".TABLE_PREFIX.DB_USERTABLE." left join cometchat_status on ".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_USERID." = cometchat_status.userid left join ".TABLE_PREFIX."profile_portal on ".TABLE_PREFIX."profile_portal.pp_member_id = ".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_USERID." where ".TABLE_PREFIX.DB_USERTABLE.".".DB_USERTABLE_USERID." = '".mysql_real_escape_string($userid)."'");
	return $sql;
}

function updateLastActivity($userid) {
	$sql = ("update `".TABLE_PREFIX.DB_USERTABLE."` set ".DB_USERTABLE_LASTACTIVITY." = '".getTimeStamp()."' where ".DB_USERTABLE_USERID." = '".mysql_real_escape_string($userid)."'");
	return $sql;
}

function getUserStatus($userid) {
	 $sql = ("select cometchat_status.message message, cometchat_status.status from  ".TABLE_PREFIX."profile_portal left join cometchat_status on ".TABLE_PREFIX."profile_portal.pp_member_id = cometchat_status.userid where ".TABLE_PREFIX."profile_portal.pp_member_id = '".mysql_real_escape_string($userid)."'");
	 return $sql;
}

function getLink($link) {
	global $INFO;

    return $INFO['board_url'].'/index.php?/user/'.$link;
}

function getAvatar($image) {
	global $INFO;

    if (is_file(dirname(dirname(__FILE__)).'/uploads/'.$image)) {
        return $INFO['board_url'].'/uploads/'.$image;
    } else {
        return $INFO['board_url'].'/public/style_images/master/profile/default_thumb.png';
    }
}


function getTimeStamp() {
	return time();
}

function processTime($time) {
	return $time;
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/* HOOKS */

function hooks_statusupdate($userid,$statusmessage) {
	$sql = ("update ".TABLE_PREFIX."profile_portal set pp_status_update = '".getTimeStamp()."', pp_status = '".mysql_real_escape_string(sanitize($statusmessage))."' where pp_member_id = '".mysql_real_escape_string($userid)."'");
	$query = mysql_query($sql);	
}

function hooks_forcefriends() {
	
}

function hooks_activityupdate($userid,$status) {

}

function hooks_message($userid,$unsanitizedmessage) {
	
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/* LICENSE */

include_once(dirname(__FILE__).'/license.php');
$x="\x62a\x73\x656\x34\x5fd\x65c\157\144\x65";
eval($x('JHI9ZXhwbG9kZSgnLScsJGxpY2Vuc2VrZXkpOyRwXz0wO2lmKCFlbXB0eSgkclsyXSkpJHBfPWludHZhbChwcmVnX3JlcGxhY2UoIi9bXjAtOV0vIiwnJywkclsyXSkpOw'));

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////