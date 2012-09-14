<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Core variables file: defines caches, resets, etc.
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 * @since		3.0.0
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class coreVariables
{
	/**
	 * Fetch the caches array
	 *
	 * @access	public
	 * @return	array 		Caches
	 */
	public function fetchCaches()
	{
		//-----------------------------------------
		// Extension File: Registered Caches
		//-----------------------------------------

		$CACHE['systemvars']     = array( 'array'            => 1,
									 	  'allow_unload'     => 0,
										  'default_load'     => 1,
										  'recache_file'     => '',
										  'recache_function' => '' );
										   
		$CACHE['licenseData']	 = array( 'array'            => 1,
		    							  'allow_unload'     => 0,
										  'default_load'     => 0,
										  'recache_file'     => IPSLib::getAppDir( 'core' ) . '/modules_admin/tools/licensekey.php',
										  'recache_class'    => 'admin_core_tools_licensekey',
										  'recache_function' => 'recache' );
								   
		$CACHE['login_methods']  = array( 'array'            => 1,
		    							  'allow_unload'     => 0,
										  'default_load'     => 1,
										  'recache_file'     => IPSLib::getAppDir( 'core' ) . '/modules_admin/tools/login.php',
										  'recache_class'    => 'admin_core_tools_login',
										  'recache_function' => 'loginsRecache' );

		/* Apps and modules */
		$CACHE['vnums']           = array( 'array'            => 1,
									       'allow_unload'     => 0,
									       'default_load'     => 1,
									       'recache_file'     => IPSLib::getAppDir( 'core' ) . '/modules_admin/applications/applications.php',
										   'recache_class'    => 'admin_core_applications_applications',
									       'recache_function' => 'versionNumbersRecache' );
									
		$CACHE['app_cache']       = array( 'array'            => 1,
									       'allow_unload'     => 0,
									       'default_load'     => 1,
									       'recache_file'     => IPSLib::getAppDir( 'core' ) . '/modules_admin/applications/applications.php',
										   'recache_class'    => 'admin_core_applications_applications',
									       'recache_function' => 'applicationsRecache' );
									       
		$CACHE['navigation_tabs'] = array( 'array'            => 1,
									       'allow_unload'     => 0,
									       'default_load'     => 1,
									       'recache_file'     => IPSLib::getAppDir( 'core' ) . '/modules_admin/applications/applications.php',
										   'recache_class'    => 'admin_core_applications_applications',
									       'recache_function' => 'applicationsRecache' );

		$CACHE['module_cache']    = array( 'array'            => 1,
									       'allow_unload'     => 0,
									       'default_load'     => 1,
									       'recache_file'     => IPSLib::getAppDir( 'core' ) . '/modules_admin/applications/applications.php',
										   'recache_class'    => 'admin_core_applications_applications',
									       'recache_function' => 'moduleRecache' );

		$CACHE['app_menu_cache']  = array( 'array'            => 1,
									       'allow_unload'     => 0,
									       'default_load'     => 1,
									       'recache_file'     => IPSLib::getAppDir( 'core' ) . '/modules_admin/applications/applications.php',
										   'recache_class'    => 'admin_core_applications_applications',
									       'recache_function' => 'applicationsMenuDataRecache',
									       'acp_only'         => 1 );
							       
		$CACHE['hooks']			  = array( 'array'            => 1,
									       'allow_unload'     => 0,
									       'default_load'     => 1,
									       'recache_file'     => IPSLib::getAppDir( 'core' ) . '/modules_admin/applications/hooks.php',
										   'recache_class'    => 'admin_core_applications_hooks',
									       'recache_function' => 'rebuildHooksCache' );

		/* User agents and skins */					
		$CACHE['useragents']      = array( 'array'            => 1,
									       'allow_unload'     => 0,
									       'default_load'     => 1,
									       'recache_file'     => IPS_ROOT_PATH . 'sources/classes/useragents/userAgentFunctions.php',
										   'recache_class'    => 'userAgentFunctions',
									       'recache_function' => 'rebuildUserAgentCaches' );

		$CACHE['useragentgroups'] = array( 'array'            => 1,
									       'allow_unload'     => 0,
									       'default_load'     => 1,
									       'recache_file'     => IPS_ROOT_PATH . 'sources/classes/useragents/userAgentFunctions.php',
										   'recache_class'    => 'userAgentFunctions',
									       'recache_function' => 'rebuildUserAgentGroupCaches' );							

		$CACHE['skinsets']        = array( 'array'            => 1,
									       'allow_unload'     => 0,
									       'default_load'     => 1,
									       'recache_file'     => IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php',
										   'recache_class'    => 'skinCaching',
									       'recache_function' => 'rebuildSkinSetsCache' );
							
		$CACHE['outputformats']   = array( 'array'            => 1,
									       'allow_unload'     => 0,
									       'default_load'     => 1,
									       'recache_file'     => IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php',
										   'recache_class'    => 'skinCaching',
									       'recache_function' => 'rebuildOutputFormatCaches' );

		$CACHE['skin_remap']      = array( 'array'            => 1,
									       'allow_unload'     => 0,
									       'default_load'     => 1,
									       'recache_file'     => IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php',
										   'recache_class'    => 'skinCaching',
									       'recache_function' => 'rebuildURLMapCache' );
							

		/* Basic caches */
		$CACHE['group_cache']     = array( 'array'            => 1,
									       'allow_unload'     => 0,
									       'default_load'     => 1,
									       'recache_file'     => IPSLib::getAppDir( 'members' ) . '/modules_admin/groups/groups.php',
									       'recache_class'    => 'admin_members_groups_groups',
									       'recache_function' => 'rebuildGroupCache' );
							
		$CACHE['settings']        = array( 'array'            => 1,
									       'allow_unload'     => 0,
									       'default_load'     => 1,
									       'recache_file'     => IPSLib::getAppDir( 'core' ) . '/modules_admin/settings/settings.php',
										   'recache_class'    => 'admin_core_settings_settings',
									       'recache_function' => 'settingsRebuildCache' );		
							       
		$CACHE['lang_data']       = array( 'array'            => 1,
							               'allow_unload'     => 0,
									       'default_load'     => 1,
									       'recache_file'     => IPS_ROOT_PATH . 'sources/classes/class_localization.php',
									       'recache_class'    => 'class_localization',
									       'recache_function' => 'rebuildLanguagesCache' );							       					
															


		$CACHE['banfilters']      = array( 'array'            => 1,
									       'allow_unload'     => 0,
									       'default_load'     => 1,
									       'recache_file'     => IPSLib::getAppDir( 'members' ) . '/modules_admin/members/banfilters.php',
										   'recache_class'    => 'admin_members_members_banfilters',
									       'recache_function' => 'rebuildBanCache' );


		$CACHE['stats']           = array( 'array'            => 1,
									       'allow_unload'     => 0,
									       'default_load'     => 1,
									       'recache_file'     => IPSLib::getAppDir( 'members' ) . '/modules_admin/members/tools.php',
										   'recache_class'    => 'admin_members_members_tools',
									       'recache_function' => 'rebuildStats' );

		/* Text handling */							
		$CACHE['emoticons'] = array( 
									'array'            => 1,
									'allow_unload'     => 0,
								    'default_load'     => 0,
								    'recache_file'     => IPSLib::getAppDir( 'core' ) . '/modules_admin/posts/emoticons.php',
									'recache_class'    => 'admin_core_posts_emoticons',
								    'recache_function' => 'emoticonsRebuildCache' 
								    );


		$CACHE['badwords'] = array( 
									'array'            => 1,
									'allow_unload'     => 0,
								    'default_load'     => 1,
								    'recache_file'     => IPSLib::getAppDir( 'core' ) . '/modules_admin/posts/badwords.php',
									'recache_class'    => 'admin_core_posts_badwords',
								    'recache_function' => 'badwordsRebuildCache' 
									);

		$CACHE['bbcode'] = array( 
									'array'            => 1,
									'allow_unload'     => 0,
									'default_load'     => 1,
									'recache_file'     => IPSLib::getAppDir( 'core' ) . '/modules_admin/posts/bbcode.php',
									'recache_class'    => 'admin_core_posts_bbcode',
									'recache_function' => 'bbcodeRebuildCache' 
								);

		$CACHE['mediatag'] = array( 
									'array'            => 1,
									'allow_unload'     => 0,
									'default_load'     => 1,
									'recache_file'     => IPSLib::getAppDir( 'core' ) . '/modules_admin/posts/media.php',
									'recache_class'    => 'admin_core_posts_media',
									'recache_function' => 'recacheMediaTag' 
								);
						
		$CACHE['profilefields'] = array( 
										'array'            => 1,
									    'allow_unload'     => 0,
									    'default_load'     => 1,
									    'recache_file'     => IPSLib::getAppDir( 'members' ) . '/modules_admin/cfields/customfields.php',
										'recache_class'    => 'admin_members_cfields_customfields',
									    'recache_function' => 'rebuildCache' 
									    );
							    
		$CACHE['ranks'] = array(
								'array'				=> 1,
								'allow_unload'		=> 0,
								'default_load'		=> 0,
								'recache_file'		=> IPSLib::getAppDir( 'members' ) . '/modules_admin/members/ranks.php',
								'recache_class'		=> 'admin_members_members_ranks',
								'recache_function'	=> 'titlesRecache' 
								);
								
		$CACHE['reputation_levels'] = array(
								'array'				=> 1,
								'allow_unload'		=> 0,
								'default_load'		=> 0,
								'recache_file'		=> IPSLib::getAppDir( 'members' ) . '/modules_admin/members/reputation.php',
								'recache_class'		=> 'admin_members_members_reputation',
								'recache_function'	=> 'rebuildReputationLevelCache' 
								);

		$CACHE['rss_output_cache'] = array(
								'array'				=> 1,
								'allow_unload'		=> 0,
								'default_load'		=> 1,
								'recache_file'		=> IPSLib::getAppDir( 'core' ) . '/modules_admin/tools/cache.php',
								'recache_class'		=> 'admin_core_tools_cache',
								'recache_function'	=> 'rebuildRssCache' 
								);
								
		$CACHE['rss_export'] = array(
								'array'				=> 1,
								'allow_unload'		=> 0,
								'default_load'		=> 1,
								'skip_rebuild_when_upgrading' => 1,
								'recache_file'		=> IPSLib::getAppDir( 'forums' ) . '/modules_admin/rss/export.php',
								'recache_class'		=> 'admin_forums_rss_export',
								'recache_function'	=> 'doRssRebuildCache' 
								);
								
		$CACHE['sharelinks'] = array(
								'array'				=> 1,
								'allow_unload'		=> 0,
								'default_load'		=> 0,
								'skip_rebuild_when_upgrading' => 0,
								'recache_file'		=> IPSLib::getAppDir( 'core' ) . '/modules_admin/tools/sharelinks.php',
								'recache_class'		=> 'admin_core_tools_sharelinks',
								'recache_function'	=> 'rebuildCache' 
								);

		$CACHE['notifications'] = array(
								'array'				=> 1,
								'allow_unload'		=> 0,
								'default_load'		=> 0,
								'skip_rebuild_when_upgrading' => 0,
								'recache_file'		=> IPS_ROOT_PATH . '/sources/classes/member/notifications.php',
								'recache_class'		=> 'notifications',
								'recache_function'	=> 'rebuildNotificationsCache' 
								);

		$CACHE['report_plugins'] = array(
								'array'				=> 1,
								'allow_unload'		=> 0,
								'default_load'		=> 0,
								'skip_rebuild_when_upgrading' => 0,
								'recache_file'		=> IPSLib::getAppDir( 'core' ) . '/sources/classes/reportLibrary.php',
								'recache_class'		=> 'reportLibrary',
								'recache_function'	=> 'rebuildReportCache' 
								);

		$CACHE['report_cache'] = array(
								'array'				=> 1,
								'allow_unload'		=> 0,
								'default_load'		=> 0,
								'skip_rebuild_when_upgrading' => 1,
								'recache_file'		=> IPSLib::getAppDir( 'core' ) . '/sources/classes/reportLibrary.php',
								'recache_class'		=> 'reportLibrary',
								'recache_function'	=> 'updateCacheTime' 
								);

		if ( IN_DEV )
		{
			$CACHE['indev'] = array(
									'array'				=> 1,
									'allow_unload'		=> 0,
									'default_load'		=> 1,
									'recache_file'		=> '',
									'recache_class'		=> '',
									'recache_function'	=> '' 
									);
		}

		$_LOAD['report_cache']		= 1;
		$_LOAD['report_plugins']	= 1;
		$_LOAD['rss_output_cache']	= 1;
		
		return array( 'caches'    => $CACHE,
					  'cacheload' => $_LOAD );
	}
	
	/**
	 * Fetch the redirect mapping for short-hand urls
	 *
	 * @access	public
	 * @return	array 		Redirect mappings
	 */
	public function fetchRedirects()
	{
		$_RESET = array();

		/* Make old skin selectors work */
		if ( $_REQUEST['k'] && $_REQUEST['settingNewSkin'] )
		{
			$_RESET['app']     = 'core';
			$_RESET['module']  = 'global';
			$_RESET['section'] = 'skin';
			$_RESET['skinId']  = intval( $_REQUEST['settingNewSkin'] );
			
			/* Return here to stop other rules parsing */
			return $_RESET;
		}
		else if ( $_REQUEST['setAsMobile'] )
		{
			$_RESET['app']     = 'core';
			$_RESET['module']  = 'global';
			$_RESET['section'] = 'skin';
			$_RESET['skinId']  = 'setAsMobile';
		
			/* Return here to stop other rules parsing */
			return $_RESET;
		}
		
		###### New IPB 3.0.0 Redirects  ######
		
		/* Share links */
		if ( isset( $_REQUEST['sharelink'] ) AND ! isset( $_REQUEST['app'] ) )
		{
			/* Some set ups parse ; automagically (bah) */
			if ( ! strstr( $_REQUEST['sharelink'], ';' ) )
			{
				$_REQUEST['sharelink'] = preg_replace( "#sharelink=(.*)$#", "\\1", $_SERVER['QUERY_STRING'] );
			}

			list( $key, $url, $title ) = explode( ';', $_REQUEST['sharelink'] );
			$_RESET['url']     = $url;
			$_RESET['title']   = $title;
			$_RESET['key']     = $key;
			$_RESET['app']     = 'core';
			$_RESET['module']  = 'global';
			$_RESET['section'] = 'share';
		}
		
		if( !empty( $_REQUEST['showannouncement'] ) )
		{
			$_RESET['app']         = 'forums';
			$_RESET['module']      = 'forums';
			$_RESET['section']     = 'announcements';
			$_RESET['announce_id'] = intval( $_REQUEST['showannouncement'] );
		}

		###### Redirect IPB 2.x to IPB 3.0 URLS ######

		# IDX
		if( isset( $_REQUEST['act'] ) && $_REQUEST['act'] == 'idx' )
		{
			$_RESET['app']     = 'forums';
			$_RESET['section'] = 'boards';
			$_RESET['module']  = 'forums';
		}
		
		# FORUM
		if( !empty( $_REQUEST['showforum'] ) )
		{
			$_RESET['app']     = 'forums';
			$_RESET['module']  = 'forums';
			$_RESET['section'] = 'forums';
			$_RESET['showforum'] = intval( $_REQUEST['showforum'] );
			$_RESET['f']         = intval( $_REQUEST['showforum'] );
		}

		# TOPIC
		if( !empty( $_REQUEST['showtopic'] ) OR ( isset($_REQUEST['act']) AND $_REQUEST['act'] == 'ST' ) )
		{
			$_RESET['app']     = 'forums';
			$_RESET['module']  = 'forums';
			$_RESET['section'] = 'topics';

			if ( !$_REQUEST['t'] )
			{
				$_RESET['t']       = intval( $_REQUEST['showtopic'] );
			}
			else if( $_REQUEST['t'] AND !$_REQUEST['showtopic'] )
			{
				$_RESET['showtopic']	= intval( $_REQUEST['t'] );
			}
		}

		if( ( isset( $_REQUEST['act'] ) && $_REQUEST['act'] == 'findpost' ) || !empty( $_REQUEST['findpost'] ) )
		{
			$_RESET['pid']     = intval( ( $_REQUEST['pid'] ) ? $_REQUEST['pid'] : $_REQUEST['findpost'] );
			$_RESET['app']     = 'forums';
			$_RESET['module']  = 'forums';
			$_RESET['section'] = 'findpost';
		}

		# PROFILE
		if( isset( $_REQUEST['showuser'] ) )
		{
			$_RESET['app']		= 'members';
			$_RESET['module']	= 'profile';
			$_RESET['section']	= 'view';
			$_RESET['id']		= intval( $_REQUEST['showuser'] );
		}

		if( isset( $_REQUEST['act'] ) && $_REQUEST['act'] == 'members' )
		{
			$_RESET['app']		= 'members';
			$_RESET['module']	= 'list';
		}
		
		# RSS
		if ( isset( $_GET['act'] ) && $_GET['act'] == 'rss' && ! empty( $_GET['id'] ) )
		{
			$_RESET['app']		= 'core';
			$_RESET['module']	= 'global';
			$_RESET['section']	= 'rss';
			$_RESET['type']     = 'forums';
		}
		
		# Redirect IPN URLs to IP.Nexus / IP.Subscriptions
		if ( ( isset( $_REQUEST['act'] ) and $_REQUEST['act'] == 'paysubs' ) or ( isset( $_REQUEST['app'] ) and $_REQUEST['app'] == 'subscriptions' ) ) 
		{
			/* Brute force allow access */
			if ( ! defined( 'IPS_ENFORCE_ACCESS' ) )
			{
				define( 'IPS_ENFORCE_ACCESS', TRUE );
			}
			
			$_RESET['old'] = '1';
						
        	 // If Nexus is installed, go to Nexus
            $subs_version = IPSLib::fetchVersionNumber( 'subscriptions' );
            if ( IPSLib::extractAppLocationKey('nexus') == 'ips' && $subs_version['long'] < 10008 )
			{
				$_RESET['app'] = 'nexus';
				$_RESET['module'] = 'payments';
				$_RESET['section'] = 'receive';
				$_RESET['validate'] = 'paypal';
			}
			// Otherwise go to IP.Subscriptions
			else
			{
				$_RESET['app'] = 'subscriptions';
				
				if ( isset( $_REQUEST['CODE'] ) )
				{
					if ( $_REQUEST['CODE'] == 'incoming' )
					{
						$_RESET['module']	= 'incoming';
						$_RESET['section']	= 'receive';
						$_RESET['do']       = 'validate';						
					}
					else if ( $_REQUEST['CODE'] == 'paydone' )
					{
						$_RESET['module']	= 'incoming';
						$_RESET['section']	= 'receive';
						$_RESET['do']       = 'done';
					}
					else
					{
						$_RESET['do'] = $_REQUEST['CODE'];
					}
				}
			}
		}
		
		#Fix IP.Board 2.1 style module links
		if( isset( $_REQUEST['act'] ) && $_REQUEST['act'] == 'module' && isset( $_REQUEST['module'] ) )
		{
			$_REQUEST['autocom'] = $_REQUEST['module'];
		}
		if( isset( $_REQUEST['cmd'] ) )
		{
			$_RESET['req'] = $_REQUEST['cmd'];
		}
		
		# ALL
		if( ! isset( $_REQUEST['do'] ) AND ( isset( $_REQUEST['CODE'] ) OR isset( $_REQUEST['code'] ) ) )
		{
			$_RESET['do'] = ( $_REQUEST['CODE'] ) ? $_REQUEST['CODE'] : $_REQUEST['code'];
		}

		if( isset( $_REQUEST['autocom'] ) or isset( $_REQUEST['automodule'] ) )
		{
			$_RESET['app'] = $_REQUEST['autocom'] ? $_REQUEST['autocom'] : $_REQUEST['automodule'];
		}
		# Blog friendly urls
		else if( isset( $_GET['autocom'] ) or isset( $_GET['automodule'] ) )
		{
			$_RESET['app'] = $_GET['autocom'] ? $_GET['autocom'] : $_GET['automodule'];
		}
		
		# Calendar
		if( isset( $_REQUEST['act'] ) && $_REQUEST['act'] == 'calendar' )
		{
			$_RESET['app']     = 'calendar';
		}
		
		return $_RESET;
	}

	/**
	 * SEO templates
	 *
	 * OUT FORMAT REGEX:
	 * First array element is a regex to run to see if we've a match for the URL
	 * The second array element is the template to use the results of the parenthesis capture
	 *
	 * Special variable #{__title__} is replaced with the $title data passed to output->formatUrl( $url, $title)
	 *
	 * IMPORTANT: Remember that when these regex are used, the output has not been fully parsed so you will get:
	 * showuser={$data['member_id']} NOT showuser=1 so do not try and match numerics only!
	 *
	 * IN FORMAT REGEX
	 *
	 * This allows the registry to piece back together a URL based on the template regex
	 * So, for example: "/user/(\d+?)/", 'matches' => array(  array( 'showuser' => '$1' ) )tells IP.Board to populate 'showuser' with the result
	 * of the parenthesis capture #1
	 *
	 * @access	public
	 * @return	array 		SEO templates
	 */
	public function fetchTemplates()
	{
		$templates = array(
			# SPECIAL TEMPLATE: Used when checking permalinks. {start}permalink{end}. If you changed these templates to use something like:
			# /forums/forum-12-my-forum.html then you would need to use start => '/', end => '.html'
			# varBlock is the bit that separates the FURL from other variables. varSep is the bit that separates the vars. So if you wanted to use:
			# /forums/forum-12-my-forum.html?st/20 or /forums/forum-12-my-forum.html?st-20-view-newpost
			# You'd use 'varBlock' = '?' and 'varSep' => ','
			
			'__data__'      => array( 'start'    => '-',
									  'end'      => '/',
									  'varBlock' => '/page__',
									  'varSep'   => '__' ),
			);
			
		return $templates;
	}
	
	/**
	 * Fetch bitwise mappings
	 * You can add to any of these arrays, but you cannot remove keys or re-order them. BAD THINGS WILL HAPPEN
	 *
	 * @access	public
	 * @return	array 	Bitwise mappings
	 */
	public function fetchBitwise()
	{
		$_BITWISE = array( 'facebook' => array( 'fbc_s_pic', 'fbc_s_status', 'fbc_s_aboutme', 'fbc_si_status' ), // facebook == profile_portal.fb_bwoptions
						   'twitter'  => array( 'tc_s_pic', 'tc_s_status', 'tc_s_aboutme', 'tc_s_bgimg', 'tc_si_status' ), // twitter = profile_portal.tc_bwoptions
						   'members'  => array( 'bw_is_spammer',
						 						'bw_from_sfs',
						  						'bw_vnc_type',						 # 1 based on topic marking table, 0 based on last_visit
												'bw_forum_result_type',				 # 1 is list, 0 is forum
												'bw_no_status_update', 			 	 # 1 is yes ban, 0 is no, allow
												'bw_status_email_mine',				 # 1 is 'send me an email when anyone replies to my status updates', 0 is DO NOT DO WHAT 1 DOES OR ELSE
												'bw_status_email_all',			 	 # 1 is 'send me an email when anyone replies to any status update I replied to', 0 is OMG DO NOT DO THAT
												'bw_disable_customization',			 # 1 yeah, 0 nah boi
												'bw_local_password_set',			 # 1 means "user was created by remote, but has local password set", 0 means not, or not local user
												'bw_disable_tagging',				 # 1 means no tagging for this dude
												'bw_disable_prefixes',				 # 1 means no prefixes
												'bw_using_skin_gen',				 # 1 means yes. 0 means.. well, I think you can figure that out
												'bw_disable_gravatar',				 # If 1, Gravatar will not be used for this user
											  ),
												
						   'groups'   => array( 
												'gbw_mod_post_unit_type'		, #1  1 is days, 0 is posts
											    'gbw_ppd_unit_type'     		, #2  1 is days, 0 is posts
											    'gbw_displayname_unit_type'     , #4 1 is days, 0 is posts
											    'gbw_sig_unit_type'     		, #8 1 is days, 0 is posts
											    'gbw_promote_unit_type'     	, #16 1 is days, 0 is posts
											    'gbw_no_status_update'			, #32 1 is blocked, 0 is not. Quite simple really
											    'gbw_soft_delete'				, #64 1 is ok, 0 is not - NO LONGER USED
											    'gbw_soft_delete_own'			, #128 1 is ok, 0 is not
											    'gbw_soft_delete_own_topic'		, #256 1 is ok, 0 is not - NO LONGER USED
											    'gbw_un_soft_delete'			, #512 1 is ok, 0 is not - NO LONGER USED
											    'gbw_soft_delete_see'			, #1024 1 is ok, 0 is not - NO LONGER USED
											    'gbw_soft_delete_topic'			, #2048 1 is ok, 0 is not - NO LONGER USED
											    'gbw_un_soft_delete_topic'		, #4096 1 is ok, 0 is not - NO LONGER USED
											    'gbw_soft_delete_topic_see'		, #8192 1 is ok, 0 is not - NO LONGER USED
											    'gbw_soft_delete_reason'		, #16384 1 means can read reason, 0 means cannot - NO LONGER USED
											    'gbw_soft_delete_see_post'		, #32768 seriously. - NO LONGER USED
											    'gbw_allow_customization'		, #65536 1 YES, 0 NO
											    'gbw_allow_url_bgimage'			, #131072 ^^
											    'gbw_allow_upload_bgimage'		, #262144 ^^
											    'gbw_view_reps'					, #524288 1 allows them to view who gave rep to a post, 0 is can't
											    'gbw_no_status_import'			, #1048576 1 prevents members in group from importing statuses from services.
											    'gbw_disable_tagging'			, #2097152 1 means no tagging for this group
											    'gbw_disable_prefixes'			, #4194304 1 means cannot prefixes
											    'gbw_view_last_info'			, #8388608 1 for yes, 0 for NOOOOO
											    'gbw_view_online_lists'			, #16777216 1 for yes
											    'gbw_hide_leaders_page'			, #33554432 1 for yes
											  ) );
											
		return $_BITWISE;
	}
}
