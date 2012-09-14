<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Static SETUP Classes for IP.Board 3
 *
 * These classes are not required as objects. 
 * Last Updated: $Date: 2012-05-11 11:17:52 -0400 (Fri, 11 May 2012) $
 * </pre>
 *
 * @author 		Matt Mecham
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		1st December 2008
 * @version		$Revision: 10727 $
 *
 */

/**
 * Collection of methods to for installation alone
 *
 * @author	Matt
 */
class IPSInstall
{
	/**
	 * Create admin account
	 *
	 * @access	public
	 * @return	@e void
	 */
	static public function createAdminAccount()
	{
		/* Build Entry */
		$_mke_time	= ( ipsRegistry::$settings['login_key_expire'] ) ? ( time() + ( intval( ipsRegistry::$settings['login_key_expire'] ) * 86400 ) ) : 0;
		$salt     	= IPSMember::generatePasswordSalt( 5 );
		$passhash 	= IPSMember::generateCompiledPasshash( $salt, md5( IPSSetUp::getSavedData('admin_pass') ) );
		$_dname     = IPSSetUp::getSavedData('admin_user');
		
		$member = array(
						 'name'						=> $_dname,
						 'members_l_username'		=> strtolower( $_dname ),
						 'members_display_name'		=> $_dname,
						 'members_l_display_name'	=> strtolower( $_dname ),
						 'members_seo_name'			=> IPSText::makeSeoTitle( $_dname ),
						 'member_login_key'			=> IPSMember::generateAutoLoginKey(),
						 'member_login_key_expire'	=> $_mke_time,
						 'title'					=> 'Administrator',
						 'email'					=> IPSSetUp::getSavedData('admin_email') ,
						 'member_group_id'			=> 4,
						 'posts'					=> 1,
						 'joined'					=> time(),
						 'last_visit'               => time(),
						 'last_activity'			=> time(),
						 'ip_address'				=> my_getenv('REMOTE_ADDR'),
						 'view_sigs'				=> 1,
						 'restrict_post'			=> 0,
						 'msg_show_notification'	=> 1,
						 'msg_count_total'			=> 0,
						 'msg_count_new'			=> 0,
						 'coppa_user'				=> 0,
						 'language'					=> IPSLib::getDefaultLanguage(),
						 'members_auto_dst'			=> 1,
						 'member_uploader'			=> 'flash',
						 'allow_admin_mails'		=> 0,
						 'members_pass_hash'		=> $passhash,
						 'members_pass_salt'		=> $salt,
						 'has_blog'					=> '',
						 'fb_token'					=> '',
						 'ignored_users'			=> '',
						 'members_cache'			=> '',
						 'failed_logins'		    => '',
						 'bday_day'					=> 0,
						 'bday_month'				=> 0,
						 'bday_year'				=> 0
					   );
	
		/* Insert: MEMBERS */
		ipsRegistry::DB()->setDataType( array( 'name', 'members_display_name', 'members_l_username', 'members_l_display_name' ), 'string' );

		ipsRegistry::DB()->insert( 'members', $member );

		$member_id           = ipsRegistry::DB()->getInsertId();
		$member['member_id'] = $member_id;

		/* Insert into the custom profile fields DB */
		ipsRegistry::DB()->insert( 'pfields_content', array( 'member_id' => $member_id ) );
		
		/* Insert into pp */
		ipsRegistry::DB()->insert( 'profile_portal', array( 
														'pp_member_id'				=> $member_id, 
														'pp_setting_count_friends'	=> 1, 
														'signature'					=> '',
														'pconversation_filters'		=> '',
														'pp_setting_count_comments'	=> 1,
														'pp_setting_count_visitors' => 1 ) );
	}
	
	/**
	 * Writes out conf_global
	 *
	 * @access	public
	 * @return	bool	File written successfully
	 */	
	static public function writeConfiguration()
	{
		//-----------------------------------------
		// Safe mode?
		//-----------------------------------------
		
		$safe_mode = 0;

		if ( @get_cfg_var('safe_mode') )
		{
			$safe_mode = @get_cfg_var('safe_mode');
		}
		
		//-----------------------------------------
		// Set info array
		//-----------------------------------------
		
		$INFO = array( 
					   'sql_driver'     => IPSSetUp::getSavedData('sql_driver'),
					   'sql_host'       => IPSSetUp::getSavedData('db_host'),
					   'sql_database'   => IPSSetUp::getSavedData('db_name'),
					   'sql_user'       => IPSSetUp::getSavedData('db_user'),
					   'sql_pass'       => str_replace( '\'', '\\\'', IPSSetUp::getSavedData('db_pass') ),
					   'sql_tbl_prefix' => IPSSetUp::getSavedData('db_pre'),
					   'sql_debug'      => 0,
					   'sql_charset'    => '',
					
					   'board_start'    => time(),
					   'installed'      => 1,

					   'php_ext'        => 'php',
					   'safe_mode'      => $safe_mode,

					   //'base_url'       => IPSSetUp::getSavedData('install_url'),
					   'board_url'      => IPSSetUp::getSavedData('install_url'),
					   'banned_group'   => '5',
					   'admin_group'    => '4',
					   'guest_group'    => '2',
					   'member_group'   => '3',
					   'auth_group'		=> '1',
					   'use_friendly_urls' => 1,
					   '_jsDebug'          => 0
					 );
					
		//---------------------------------------------
		// Any "extra" configs required for this driver?
		//---------------------------------------------
		
		foreach( IPSSetUp::getSavedDataAsArray() as $k => $v )
		{
			if ( preg_match( "#^__sql__#", $k ) )
			{
				$k = str_replace( "__sql__", "", $k );
				
				$INFO[ $k ] = $v;
			}
		}
		
		//---------------------------------------------
		// Write to disk
		//---------------------------------------------

		$core_conf = "<"."?php\n";

		foreach( $INFO as $k => $v )
		{
			$core_conf .= '$INFO['."'".$k."'".']'."\t\t\t=\t'".$v."';\n";
		}
		
		$core_conf .= "\ndefine('IN_DEV', 0);";
		
		/* Remote archive stuff */
		$core_conf .= "\n/* Remote archive DB - complete these details if you\'re using a remote DB for the post archive */\n";
		
		foreach( array( 'archive_remote_sql_host', 'archive_remote_sql_database', 'archive_remote_sql_user', 'archive_remote_sql_pass', 'archive_remote_sql_charset' ) as $k )
		{
			$core_conf .= '$INFO['."'".$k."'".']'."\t\t\t=\t'';\n";
		}
		
		$core_conf .= "\n".'?'.'>';

		/* Write Configuration Files */
		$output[] = 'Writing configuration files...<br />';
		
		$ret = IPSSetUp::writeFile( IPSSetUp::getSavedData('install_dir') . '/conf_global.php'  , $core_conf );
		
		/* Now freeze data */
		IPSSetUp::freezeSavedData();
		
		return $ret;
	}
	
	/**
	 * Clean up conf global
	 * Removes data variables
	 *
	 * @access	public
	 * @return 	boolean
	 */
	static public function cleanConfGlobal()
	{
		if ( $contents = @file_get_contents( IPSSetUp::getSavedData('install_dir') . '/conf_global.php' ) )
		{
			if ( $contents )
			{
				$contents = preg_replace( "#/\*~~DATA~~\*/(.+?)\n/\*\*/#s", "", $contents );
			
				return IPSSetUp::writeFile( IPSSetUp::getSavedData('install_dir') . '/conf_global.php'  , $contents );
			}
		}
		
		return FALSE;
	}

}