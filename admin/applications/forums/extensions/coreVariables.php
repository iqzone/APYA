<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Core variables extensions
 * Defines the reset array, which caches to load, how to recache those caches, and the bitwise array
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $ 
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

$_RESET = array();

# ALL
if ( !empty( $_REQUEST['CODE'] ) or !empty( $_REQUEST['code'] ) )
{
	$_RESET['do'] = ( $_REQUEST['CODE'] ) ? $_REQUEST['CODE'] : $_REQUEST['code'];
}

//-----------------------------------------
// Extension File: Registered Caches
//-----------------------------------------

$_LOAD = array();

# BOARD INDEX
if ( ( empty($_GET['section']) AND empty($_GET['module']) ) OR ( $_GET['module'] == 'forums' AND $_GET['section'] == 'boards' ) )
{
	$_LOAD['ranks']				= 1;
	$_LOAD['reputation_levels']	= 1;

	# The following are IPS app caches that are used for board index hooks.
	# We need a better way to abstract these and only load them if the hooks are actually enabled and app is installed.
	$_LOAD['chatting']			= 1; // This is only for the who's chatting hook, chat tab count has a global cache
	$_LOAD['birthdays']			= 1;
	$_LOAD['calendar_events']	= 1;
	$_LOAD['calendars']			= 1;
	
	$_LOAD['support_staff']			= 1;
	$_LOAD['support_departments']	= 1;
	$_LOAD['support_statuses']		= 1;
	$_LOAD['donation_goals']		= 1;
	
	$_LOAD['blog_stats']			= 1;
	$_LOAD['blogmods']				= 1;
	
	$_LOAD['idm_cats']				= 1;
	$_LOAD['idm_mods']				= 1;
}

# TOPIC
if ( isset( $_GET['showtopic'] ) OR ( isset($_GET['module']) AND isset($_GET['section']) AND $_GET['module'] == 'forums' AND $_GET['section'] == 'topics' ) )
{
	$_LOAD['badwords']			= 1;
	$_LOAD['emoticons']			= 1;
	$_LOAD['bbcode']			= 1;
	$_LOAD['multimod']			= 1;
	$_LOAD['ranks']				= 1;
	$_LOAD['profilefields']		= 1;
	$_LOAD['reputation_levels']	= 1;
	$_LOAD['sharelinks']		= 1;
}

# Forum
if ( isset( $_GET['showforum'] ) OR ( isset($_GET['module']) AND isset($_GET['section']) AND $_GET['module'] == 'forums' AND $_GET['section'] == 'forums' ) )
{
	$_LOAD['ranks']				= 1;
	$_LOAD['reputation_levels']	= 1;
	
	// Needed for forum rules...
	$_LOAD['badwords']			= 1;
	$_LOAD['emoticons']			= 1;
	$_LOAD['bbcode']			= 1;
}

# POST and RULES
if ( isset($_GET['module']) AND ( $_GET['module'] == 'post' OR $_GET['module'] == 'extras' ) )
{
	$_LOAD['badwords']			= 1;
	$_LOAD['bbcode']			= 1;
	$_LOAD['emoticons']			= 1;
	$_LOAD['ranks']				= 1;
	$_LOAD['reputation_levels']	= 1;
}

# ANNOUNCEMENT
if ( isset( $_GET['showannouncement'] ) OR ( isset($_GET['module']) AND isset($_GET['section']) AND $_GET['module'] == 'forums' AND $_GET['section'] == 'announcements' ) )
{
        $_LOAD['badwords']                      = 1;
        $_LOAD['bbcode']                        = 1;
        $_LOAD['emoticons']                     = 1;
        $_LOAD['ranks']                         = 1;
        $_LOAD['reputation_levels']     = 1;
}

$CACHE['attachtypes'] = array( 
								'array'            => 1,
								'allow_unload'     => 0,
							    'default_load'     => 1,
							    'recache_file'     => IPSLib::getAppDir( 'forums' ) . '/modules_admin/attachments/types.php',
								'recache_class'    => 'admin_forums_attachments_types',
							    'recache_function' => 'attachmentTypeCacheRebuild' 
							);

$CACHE['multimod'] = array( 
							'array'            => 1,
							'allow_unload'     => 0,
							'default_load'     => 1,
							'recache_file'     => IPSLib::getAppDir( 'forums' ) . '/modules_admin/forums/multimods.php',
							'recache_class'    => 'admin_forums_forums_multimods',
							'recache_function' => 'multiModerationRebuildCache' 
						);
						
$CACHE['moderators'] = array( 
								'array'            => 1,
							    'allow_unload'     => 0,
							    'default_load'     => 1,
							    'recache_file'     => IPSLib::getAppDir( 'forums' ) . '/modules_admin/forums/moderator.php',
								'recache_class'    => 'admin_forums_forums_moderator',
							    'recache_function' => 'rebuildModeratorCache' 
							);
						

$CACHE['announcements'] = array( 
								'array'            => 1,
							    'allow_unload'     => 0,
						        'default_load'     => 1,
						        'recache_file'     => IPSLib::getAppDir( 'forums' ) . '/modules_public/forums/announcements.php',
							    'recache_class'    => 'public_forums_forums_announcements',
						        'recache_function' => 'announceRecache' 
						    	);

						
//-----------------------------------------
// Bitwise Options
//-----------------------------------------

$_BITWISE = array( 'moderators' => array( 'bw_flag_spammers',
										  'bw_mod_soft_delete',
										  'bw_mod_un_soft_delete',
										  'bw_mod_soft_delete_see',
										  ),
					'forums'	=> array( 'bw_disable_tagging',
										  'bw_disable_prefixes' ),
					'posts'		=> array( 'bw_post_from_mobile' ) );
