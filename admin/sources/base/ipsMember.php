<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Static Member Class for IP.Board 3
 *
 * Last Updated: $Date: 2012-06-06 05:12:48 -0400 (Wed, 06 Jun 2012) $
 * </pre>
 *
 * @author 		$Author: mmecham $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		12th March 2002
 * @version		$Revision: 10870 $
 *
 * @author	Matt
 */
 
/**
 * Some constants
 */
define( 'IPS_MEMBER_PHOTO_NO_CACHE', true );

/**
* IPSMember
*
* This deals with member data and some member functions
*/
class IPSMember
{
	/**
	 * Custom fields class
	 *
	 * @var		object
	 */
	static protected $custom_fields_class;

	/**
	 * Member cache
	 *
	 * @var		array
	 */
	static protected $memberCache = array();

	/**
	 * Ignore cache
	 *
	 * @var		boolean
	 */
	static protected $ignoreCache = FALSE;

	/**
	 * Debug data
	 *
	 * @var		array
	 */
	static public $debugData = array();

	/**
	 * memberFunctions object reference
	 *
	 * @var		object
	 */
	static protected $_memberFunctions;

	/**
	 * Parsed signatures to save resources
	 *
	 * @var		array
	 */
	static protected $_parsedSignatures	= array();
	
	/**
	 * Parsed custom fields to save resources
	 *
	 * @var		array
	 */
	static protected $_parsedCustomFields	= array();
	
	/**
	 * Parsed custom fields to save resources
	 *
	 * @var		array
	 */
	static protected $_parsedCustomGroups	= array();

	/**
	 * Ban filters cache
	 *
	 * @var		array
	 */
	static public $_banFiltersCache = NULL;

	/**
	 * Member data array
	 *
	 * @var		array
	 */
	static public $data = array( 'member_id'            => 0,
								 'name'                 => "",
								 'members_display_name' => "",
								 'member_group_id'      => 0,
								 'member_forum_markers' => array() );

	/**
	 * Remapped table array used in load and save
	 *
	 * @var		array
	 */
	static protected $remap = array( 'core'               => 'members',
							       'extendedProfile'    => 'profile_portal',
							       'customFields'       => 'pfields_content',
							       'itemMarkingStorage' => 'core_item_markers_storage' );

	/**
	 * Create profile link 
	 *
	 * @param	string		User's display name
	 * @param	integer		User's DB ID
	 * @param	string		SEO display name
	 * @param	string		Classname
	 * @param	string		Title
	 * @param	int			Active/inactive (member_banned, bw_is_spammer)
	 * @return	string		Link title
	 * @since	2.0
	 */
	static public function makeProfileLink( $name, $id=0, $_seoName="", $className='', $title='', $inactive=0 )
	{
		/* Cache so we don't have to rebuild on each call */
		static $cache	= array();
		
		/* Did we just send in an array of data? */
		if ( is_array( $name ) && ! empty( $name['member_id'] ) )
		{
			$_name	   = $name;
			$name      = $_name['members_display_name'];
			$id        = $_name['member_id'];
			$_seoName  = $_name['members_seo_name'];
			$className = $_name['_className'];
			$title     = $_name['_title'];
			$inactive  = $inactive ? $inactive : self::isInactive($_name);
		}
		
		$_key = md5($id.$name.$_seoName.$className.$title.$inactive);
		
		if ( isset($cache[ $_key ]) )
		{
			return $cache[ $_key ];
		}
		
		if ( $id > 0 && ipsRegistry::member()->getProperty('g_mem_info') )
		{
			$_seoName = ( $_seoName ) ? $_seoName : IPSText::makeSeoTitle( $name );
			
			$cache[ $_key ]	= ipsRegistry::getClass('output')->getTemplate('global')->userHoverCard( array( 'member_id' => $id, 'members_display_name' => $name, 'members_seo_name' => $_seoName, '_hoverClass' => $className, '_hoverTitle' => $title, 'inactive' => $inactive ) );
		}
		else
		{
			if( $className )
			{
				$cache[ $_key ]	= "<span class='{$className}'>" . $name . "</span>";
			}
			else
			{
				$cache[ $_key ] = $name;
			}
		}
		
		return $cache[ $_key ];
	}

	/**
	 * Unpack group bitwise options
	 *
	 * @param	array
	 * @param	bool		Do not warn on overwrite
	 * @return  array
	 */
	static public function unpackGroup( $group, $silence=false, $forceReload=false )
	{	
		/* Cache to prevent having to thaw each call, which could be hundreds on a given page */
		static $cache	= array();
		
		if( !$forceReload and $cache[ $group['g_id'] ] )
		{
			return array_merge( $group, $cache[ $group['g_id'] ] );
		}
		
		/* Unpack photo limits */
		list( $p_max, $p_width, $p_height ) = explode( ":", $group['g_photo_max_vars'] );
		
		$cache[ $group['g_id'] ]['photoMaxKb']     = intval( $p_max );
		$cache[ $group['g_id'] ]['photoMaxWidth']  = intval( $p_width );
		$cache[ $group['g_id'] ]['photoMaxHeight'] = intval( $p_height );
		
		/* Unpack bitwise fields */
		$_tmp	= IPSBWOptions::thaw( $group['g_bitoptions'], 'groups', 'global' );
		
		if ( count( $_tmp ) )
		{
			foreach( $_tmp as $k => $v )
			{
				/* Trigger notice if we have DB field */
				if ( $silence === false AND isset( $group[ $k ] ) )
				{
					trigger_error( "Thawing bitwise options for GROUPS: Bitwise field '$k' has overwritten DB field '$k'", E_USER_WARNING );
				}

				$cache[ $group['g_id'] ][ $k ] = $v;
			}
		}
		
		return array_merge( $group, $cache[ $group['g_id'] ] );
	}

	/**
	 * Create a random 15 character password
	 *
	 * @return	string	Password
	 * @since	2.0
	 */
	public static function makePassword()
	{
		$pass = "";

		// Want it random you say, eh?
		// (enter evil laugh)

		$unique_id 	= uniqid( mt_rand(), TRUE );
		$prefix		= IPSMember::generatePasswordSalt();
		$unique_id .= md5( $prefix );

		usleep( mt_rand(15000,1000000) );
		// Hmm, wonder how long we slept for

		$new_uniqueid = uniqid( mt_rand(), TRUE );

		$final_rand = md5( $unique_id . $new_uniqueid );

		for ($i = 0; $i < 15; $i++)
		{
			$pass .= $final_rand{ mt_rand(0, 31) };
		}

		return $pass;
	}
	
	/**
	 * Checks to see if the logged in user can recieve mobile notifications
	 *
	 * @param	array 		$memberData		Optional, logged in user will be used if this is not passed in
	 * @return	BOOL
	 */
	static public function canReceiveMobileNotifications( $memberData=array() )
	{
		/* INIT */
		$memberData = ( is_array( $memberData ) && count( $memberData ) ) ? $memberData : ipsRegistry::member()->fetchMemberData();

		/* Check to see if notifications are enabled */
		if( ! ipsRegistry::$settings['iphone_notifications_enabled'] )
		{
			return false;
		}
		
		/* Check to see if the user has permission to get notifications */
		if( ipsRegistry::$settings['iphone_notifications_groups'] )
		{
			if( IPSMember::isInGroup( $memberData, explode( ',', ipsRegistry::$settings['iphone_notifications_groups'] ) ) )
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Format name based on group suffix/prefix
	 *
	 * @param	string		User's display name
	 * @param	integer		User's group ID
	 * @param	string  	Optional prefix override (uses group setting if not provided)
	 * @param	string  	Optional suffix override (uses group setting if not provided)
	 * @return	string		Formatted name
	 * @since	2.2
	 */
	public static function makeNameFormatted($name='', $group_id="", $prefix="", $suffix="")
	{
		if ( ipsRegistry::$settings['ipb_disable_group_psformat'] )
		{
			return $name;
		}

		if ( ! $group_id )
		{
			$group_id = 0;
		}

		$groupCache = ipsRegistry::cache()->getCache('group_cache');

		if ( ! $prefix )
		{
			if( $groupCache[ $group_id ]['prefix'] )
			{
				$prefix = $groupCache[ $group_id ]['prefix'];
			}
		}

		if( ! $suffix )
		{
			if( $groupCache[ $group_id ]['suffix'] )
			{
				$suffix = $groupCache[ $group_id ]['suffix'];
			}
		}
		
		if ( ! $name )
		{
			if( $groupCache[ $group_id ]['g_title'] )
			{
				$name = $groupCache[ $group_id ]['g_title'];
			}
		}

		return $prefix.$name.$suffix;
	}

	/**
	 * Create new member
	 * Very basic functionality at this point.
	 *
	 * @param	array 	Fields to save in the following format: array( 'members'      => array( 'email'     => 'test@test.com',
	 *																				         'joined'   => time() ),
	 *															   'extendedProfile' => array( 'signature' => 'My signature' ) );
	 *					Tables: members, pfields_content, profile_portal.
	 *					You can also use the aliases: 'core [members]', 'extendedProfile [profile_portal]', and 'customFields [pfields_content]'
	 * @param	bool	Flag to attempt to auto create a name if the desired is taken
	 * @param	bool	Bypass custom field saving (if using the sso session integration this is required as member object isn't ready yet)
	 * @param	bool	Whether or not to recache the stats so as to update the board's last member data
	 * @return	array 	Final member Data including member_id
	 *
	 * EXCEPTION CODES
	 * CUSTOM_FIELDS_EMPTY    - Custom fields were not populated
	 * CUSTOM_FIELDS_INVALID  - Custom fields were invalid
	 * CUSTOM_FIELDS_TOOBIG   - Custom fields too big
	 */
	static public function create( $tables=array(), $autoCreateName=FALSE, $bypassCfields=FALSE, $doStatsRecache=TRUE )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$finalTables	= array();
		$password		= '';
		$plainPassword	= '';
		$bitWiseFields  = ipsRegistry::fetchBitWiseOptions( 'global' );
		$md_5_password	= '';
		
		//-----------------------------------------
		// Remap tables if required
		//-----------------------------------------
		
		foreach( $tables as $table => $data )
		{
			$_name = ( isset( self::$remap[ $table ] ) ) ? self::$remap[ $table ] : $table;
			
			if ( $_name == 'members' )
			{
				/* Magic password field */
				if ( ! empty( $data['md5_hash_password'] ) )
                {
                    $md_5_password = trim( $data['md5_hash_password'] );
                    $plainPassword = null;
                    
                    unset( $data['md5_hash_password'] );
                }
                else
                {
					$password		= ( isset( $data['password'] ) ) ? trim( $data['password'] ) : self::makePassword();
					$plainPassword	= $password;
					$md_5_password	= md5( $password );
				
					unset( $data['password'] );
                }
			}
			
			$finalTables[ $_name ] = $data;
		}
		
		//-----------------------------------------
		// Custom profile field stuff
		//-----------------------------------------

		if( !$bypassCfields )
		{
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/customfields/profileFields.php', 'customProfileFields' );
	    	$fields = new $classToLoad();
	    	
	    	if ( is_array( $finalTables['pfields_content'] ) AND count( $finalTables['pfields_content'] ) )
			{
				$fields->member_data = $finalTables['pfields_content'];
			}
			
			$fields->initData( 'edit' );
	    	$fields->parseToSave( $finalTables['pfields_content'], 'register' );

			/* Check */
			/*if( count( $fields->error_fields['empty'] ) )
			{
				throw new Exception( 'CUSTOM_FIELDS_EMPTY' );
			}
			
			if( count( $fields->error_fields['invalid'] ) )
			{
				throw new Exception( 'CUSTOM_FIELDS_INVALID' );
			}
			
			if( count( $fields->error_fields['toobig'] ) )
			{
				throw new Exception( 'CUSTOM_FIELDS_TOOBIG' );
			}*/
		}

    	//-----------------------------------------
    	// Make sure the account doesn't exist
    	//-----------------------------------------
    	
    	if( $finalTables['members']['email'] )
    	{
    		$existing	= IPSMember::load( $finalTables['members']['email'], 'all' );
    		
    		if( $existing['member_id'] )
    		{
    			$existing['full']		= true;
    			$existing['timenow']	= time();
    			
    			return $existing;
    		}
    	}
    	
		//-----------------------------------------
		// Fix up usernames and display names
		//-----------------------------------------
		
		/* Ensure we have a display name */
		if( $autoCreateName AND $finalTables['members']['members_display_name'] !== FALSE )
		{
			$finalTables['members']['members_display_name'] = ( $finalTables['members']['members_display_name'] ) ? $finalTables['members']['members_display_name'] : $finalTables['members']['name'];
		}
		
		//-----------------------------------------
		// Remove some basic HTML tags
		//-----------------------------------------
		
		if( $finalTables['members']['members_display_name'] )
		{
			$finalTables['members']['members_display_name'] = str_replace( array( '<', '>', '"' ), '', $finalTables['members']['members_display_name'] );
		}
		
		if( $finalTables['members']['name'] )
		{
			$finalTables['members']['name'] 				= str_replace( array( '<', '>', '"' ), '', $finalTables['members']['name'] );
		}
		
		//-----------------------------------------
		// Make sure the names are unique
		//-----------------------------------------
		
		/* Can specify display name of FALSE to force no entry to force partial member */
		if( $finalTables['members']['members_display_name'] !== FALSE )
		{
			try
			{
				if( IPSMember::getFunction()->checkNameExists( $finalTables['members']['members_display_name'], array(), 'members_display_name', true ) === true )
				{
					if ( $autoCreateName === TRUE )
					{
						/* Now, make sure we have a unique display name */
						$max = ipsRegistry::DB()->buildAndFetch( array( 'select' => 'MAX(member_id) as max',
														 				'from'   => 'members',
														 				'where'  => "members_l_display_name LIKE '" . ipsRegistry::DB()->addSlashes( strtolower( $finalTables['members']['members_display_name'] ) ) . "%'" ) );


						if ( $max['max'] )
						{
							$_num = $max['max'] + 1;
							$finalTables['members']['members_display_name'] = $finalTables['members']['members_display_name'] . '_' . $_num;
						}
					}
					else
					{
						$finalTables['members']['members_display_name']		= '';
					}
				}
			}
			catch( Exception $e )
			{}
		}
		
		if( $finalTables['members']['name'] )
		{
			try
			{
				if( IPSMember::getFunction()->checkNameExists( $finalTables['members']['name'], array(), 'name', true ) === true )
				{
					if ( $autoCreateName === TRUE )
					{
						/* Now, make sure we have a unique username */
						$max = ipsRegistry::DB()->buildAndFetch( array( 'select' => 'MAX(member_id) as max',
														 				'from'   => 'members',
														 				'where'  => "members_l_username LIKE '" . ipsRegistry::DB()->addSlashes( strtolower( $finalTables['members']['name'] ) ) . "%'" ) );


						if ( $max['max'] )
						{
							$_num = $max['max'] + 1;
							$finalTables['members']['name'] = $finalTables['members']['name'] . '_' . $_num;
						}
					}
					else
					{
						$finalTables['members']['name'] = '';
					}
				}
			}
			catch( Exception $e )
			{}
		}

		//-----------------------------------------
		// Clean up characters
		//-----------------------------------------
		
		if( $finalTables['members']['name'] )
		{
			$userName		= IPSMember::getFunction()->cleanAndCheckName( $finalTables['members']['name'], array(), 'name' );
			
			if( $userName['errors'] )
			{
				$finalTables['members']['name']	= $finalTables['members']['email'];
			}
			else
			{
				$finalTables['members']['name']	= $userName['username'];
			}
		}
		
		if( $finalTables['members']['members_display_name'] )
		{
			$displayName	= IPSMember::getFunction()->cleanAndCheckName( $finalTables['members']['members_display_name'] );
			
			if( $displayName['errors'] )
			{
				$finalTables['members']['members_display_name']	= '';
			}
			else
			{
				$finalTables['members']['members_display_name']	= $displayName['members_display_name'];
			}
		}
			
		//-----------------------------------------
		// Populate member table(s)
		//-----------------------------------------

		$finalTables['members']['members_l_username']		= isset($finalTables['members']['name']) ? strtolower($finalTables['members']['name']) : '';
		$finalTables['members']['joined']					= $finalTables['members']['joined'] ? $finalTables['members']['joined'] : time();
		$finalTables['members']['email']					= $finalTables['members']['email'] ? $finalTables['members']['email'] : $finalTables['members']['name'] . '@' . $finalTables['members']['joined'];
		$finalTables['members']['member_group_id']			= $finalTables['members']['member_group_id'] ? $finalTables['members']['member_group_id'] : ipsRegistry::$settings['member_group'];
		$finalTables['members']['ip_address']				= $finalTables['members']['ip_address'] ? $finalTables['members']['ip_address'] : ipsRegistry::member()->ip_address;
		$finalTables['members']['members_created_remote']	= intval( $finalTables['members']['members_created_remote'] );
		$finalTables['members']['member_login_key']			= IPSMember::generateAutoLoginKey();
		$finalTables['members']['member_login_key_expire']	= ( ipsRegistry::$settings['login_key_expire'] ) ? ( time() + ( intval( ipsRegistry::$settings['login_key_expire'] ) * 86400 ) ) : 0;
		$finalTables['members']['view_sigs']				= 1;
		$finalTables['members']['bday_day']					= intval( $finalTables['members']['bday_day'] );
		$finalTables['members']['bday_month']				= intval( $finalTables['members']['bday_month'] );
		$finalTables['members']['bday_year']				= intval( $finalTables['members']['bday_year'] );
		$finalTables['members']['restrict_post']			= intval( $finalTables['members']['restrict_post'] );
		$finalTables['members']['auto_track']				= $finalTables['members']['auto_track'] ? $finalTables['members']['auto_track'] : ipsRegistry::$settings['auto_track_method'];
		$finalTables['members']['msg_count_total']			= 0;
		$finalTables['members']['msg_count_new']			= 0;
		$finalTables['members']['msg_show_notification']	= 1;
		$finalTables['members']['coppa_user']				= 0;
		$finalTables['members']['auto_track']				= substr( $finalTables['members']['auto_track'], 0, 50 );
		$finalTables['members']['last_visit']				= $finalTables['members']['last_visit'] ? $finalTables['members']['last_visit'] : time();
		$finalTables['members']['last_activity']			= $finalTables['members']['last_activity'] ? $finalTables['members']['last_activity'] : time();
		$finalTables['members']['language']					= $finalTables['members']['language'] ? $finalTables['members']['language'] : IPSLib::getDefaultLanguage();
		$finalTables['members']['member_uploader']			= ipsRegistry::$settings['uploadFormType'] ? 'flash' : 'default';
		$finalTables['members']['members_pass_salt']		= IPSMember::generatePasswordSalt(5);
		$finalTables['members']['members_pass_hash']		= IPSMember::generateCompiledPasshash( $finalTables['members']['members_pass_salt'], $md_5_password );
		$finalTables['members']['members_display_name']		= isset($finalTables['members']['members_display_name']) ? $finalTables['members']['members_display_name'] : '';
		$finalTables['members']['members_l_display_name']	= isset($finalTables['members']['members_display_name']) ? strtolower($finalTables['members']['members_display_name']) : '';
		$finalTables['members']['fb_uid']	 	            = isset($finalTables['members']['fb_uid']) ? $finalTables['members']['fb_uid'] : 0;
		$finalTables['members']['fb_emailhash']	            = isset($finalTables['members']['fb_emailhash']) ? strtolower($finalTables['members']['fb_emailhash']) : '';
		$finalTables['members']['members_seo_name']         = IPSText::makeSeoTitle( $finalTables['members']['members_display_name'] );
		$finalTables['members']['bw_is_spammer']            = intval( $finalTables['members']['bw_is_spammer'] );
		
		//-----------------------------------------
		// Insert: MEMBERS
		//-----------------------------------------
		
		ipsRegistry::DB()->setDataType( array( 'name', 'members_l_username', 'members_display_name', 'members_l_display_name', 'members_seo_name', 'email' ), 'string' );

		/* Bitwise options */
		if ( is_array( $bitWiseFields['members'] ) )
		{
			$_freeze = array();
			
			foreach( $bitWiseFields['members'] as $field )
			{
				if ( isset( $finalTables['members'][ $field ] ) )
				{
					/* Add to freezeable array */
					$_freeze[ $field ] = $finalTables['members'][ $field ];
					
					/* Remove it from the fields to save to DB */
					unset( $finalTables['members'][ $field ] );
				}
			}
			
			if ( count( $_freeze ) )
			{
				$finalTables['members']['members_bitoptions'] = IPSBWOptions::freeze( $_freeze, 'members', 'global' );
			}
		}
			
		ipsRegistry::DB()->insert( 'members', $finalTables['members'] );
	
		//-----------------------------------------
		// Get the member id
		//-----------------------------------------
		
		$finalTables['members']['member_id'] = ipsRegistry::DB()->getInsertId();

		//-----------------------------------------
		// Insert: PROFILE PORTAL
		//-----------------------------------------

		$finalTables['profile_portal']['pp_member_id']              = $finalTables['members']['member_id'];
		$finalTables['profile_portal']['pp_setting_count_friends']  = 1;
		$finalTables['profile_portal']['pp_setting_count_comments'] = 1;
		$finalTables['profile_portal']['pp_setting_count_visitors'] = 1;
		$finalTables['profile_portal']['pp_customization']			= serialize( array() );
		
		foreach( array( 'pp_last_visitors', 'pp_about_me', 'signature', 'fb_photo', 'fb_photo_thumb', 'pconversation_filters' ) as $f )
		{
			$finalTables['profile_portal'][ $f ] = ( $finalTables['profile_portal'][ $f ] ) ? $finalTables['profile_portal'][ $f ] : '';
		}
		
		ipsRegistry::DB()->insert( 'profile_portal', $finalTables['profile_portal'] );
		
		//-----------------------------------------
		// Insert into the custom profile fields DB
		//-----------------------------------------
		
		if( !$bypassCfields )
		{
			/* Check the website url field */
			$website_field = $fields->getFieldIDByKey( 'website' );
			
			if( $website_field && $fields->out_fields[ 'field_' . $website_field ] )
			{
				if( stristr( $fields->out_fields[ 'field_' . $website_field ], 'http://' ) === FALSE && stristr( $fields->out_fields[ 'field_' . $website_field ], 'https://' ) === FALSE )
				{
					$fields->out_fields[ 'field_' . $website_field ] = 'http://' . $fields->out_fields[ 'field_' . $website_field ];
				}
			}
		
			$fields->out_fields['member_id'] = $finalTables['members']['member_id'];
			
			ipsRegistry::DB()->delete( 'pfields_content', 'member_id=' . $finalTables['members']['member_id'] );
			ipsRegistry::DB()->insert( 'pfields_content', $fields->out_fields );
		}
		else
		{
			ipsRegistry::DB()->delete( 'pfields_content', 'member_id=' . $finalTables['members']['member_id'] );
			ipsRegistry::DB()->insert( 'pfields_content', array( 'member_id' => $finalTables['members']['member_id'] ) );
		}

		//-----------------------------------------
		// Insert into partial ID table
		//-----------------------------------------
		
		$full_account 	= false;
		
		if( $finalTables['members']['members_display_name'] AND $finalTables['members']['name'] AND $finalTables['members']['email'] AND $finalTables['members']['email'] != $finalTables['members']['name'] . '@' . $finalTables['members']['joined'] )
		{
			$full_account	= true;
		}
		
		if ( ! $full_account )
		{
			ipsRegistry::DB()->insert( 'members_partial', array( 'partial_member_id' => $finalTables['members']['member_id'],
														 		 'partial_date'      => $finalTables['members']['joined'],
														 		 'partial_email_ok'  => ( $finalTables['members']['email'] == $finalTables['members']['name'] . '@' . $finalTables['members']['joined'] ) ? 0 : 1,
								) 						);
		}
		
		/* Add plain password and run sync */
		$finalTables['members']['plainPassword'] = $plainPassword;
		
		IPSLib::runMemberSync( 'onCreateAccount', $finalTables['members'] );
		
		/* Remove plain password */
		unset( $finalTables['members']['plainPassword'] );
		
		//-----------------------------------------
		// Recache our stats (Ticket 627608)
		//-----------------------------------------
		
		if ( $doStatsRecache == TRUE )
		{
			ipsRegistry::cache()->rebuildCache( 'stats', 'global' );
		}
															
		return array_merge( $finalTables['members'], $finalTables['profile_portal'], !$bypassCfields ? $fields->out_fields : array(), array( 'timenow' => $finalTables['members']['joined'], 'full' => $full_account ) );
	}

	/**
	 * Save member
	 *
	 * @param 	int		Member key: Either Array, ID or email address. If it's an array, it must be in the format:
	 *					 array( 'core' => array( 'field' => 'member_id', 'value' => 1 ) ) - useful for passing custom fields through
	 * @param 	array 	Fields to save in the following format: array( 'members'      => array( 'email'     => 'test@test.com',
	 *																				         'joined'   => time() ),
	 *															   'extendedProfile' => array( 'signature' => 'My signature' ) );
	 *					Tables: members, pfields_content, profile_portal.
	 *					You can also use the aliases: 'core [members]', 'extendedProfile [profile_portal]', and 'customFields [pfields_content]'
	 * @return	boolean	True if the save was successful
	 *
	 * Exception Error Codes:
	 * NO_DATA 		  : No data to save
	 * NO_VALID_KEY    : No valid key to save
	 * NO_AUTO_LOAD    : Could not autoload the member as she does not exist
	 * INCORRECT_TABLE : Table one is attempting to save to does not exist
	 * NO_MEMBER_GROUP_ID: Member group ID is in the array but blank
	 */
	static public function save( $member_key, $save=array() )
	{
		$member_id      = 0;
		$member_email   = '';
		$member_field   = '';
		$_updated       = 0;
		$bitWiseFields  = ipsRegistry::fetchBitWiseOptions( 'global' );
		$member_k_array = array( 'members' => array(), 'pfields_content' => array(),  'profile_portal' => array() );
		$_tables        = array_keys( $save );
		$_MEMBERKEY     = 'member_id';
		$_MEMBERVALUE   = $member_key;
		
		//-----------------------------------------
		// Test...
		//-----------------------------------------

		if ( ! is_array( $save ) OR ! count( $save ) )
		{
			throw new Exception( 'NO_DATA' );
		}

		//-----------------------------------------
		// ID or email?
		//-----------------------------------------

		if ( ! is_array( $member_key ) )
		{
			if ( strstr( $member_key, '@' ) )
			{
				$_MEMBERKEY    = 'email';
				
				$member_k_array['members'] = array( 'field' => 'email',
				 									'value' => "'" . ipsRegistry::instance()->DB()->addSlashes( strtolower( $member_key ) ) . "'" );

				//-----------------------------------------
				// Check to see if we've got more than the core
				// table to save on.
				//-----------------------------------------

				$_got_more_than_core = FALSE;

				foreach( $_tables as $table )
				{
					if ( isset( self::$remap[ $table ] ) )
					{
						$table = self::$remap[ $table ];
					}

					if ( $table != 'members' )
					{
						$_got_more_than_core = TRUE;
						break;
					}
				}

				if ( $_got_more_than_core === TRUE )
				{
					/* Get the ID */
					$_memberTmp = self::load( $member_key, 'core' );
				
					if ( $_memberTmp['member_id'] )
					{
						$member_k_array['pfields_content'] = array( 'field' => 'member_id'   , 'value' => $_memberTmp['member_id'] );
						$member_k_array['profile_portal']  = array( 'field' => 'pp_member_id', 'value' => $_memberTmp['member_id'] );
					}
					else
					{
						throw new Exception( "NO_AUTO_LOAD" );
					}
				}
			}
			else
			{
				$member_k_array['members']         = array( 'field' => 'member_id'    , 'value' => intval( $member_key ) );
				$member_k_array['pfields_content'] = array( 'field' => 'member_id'    , 'value' => intval( $member_key ) );
				$member_k_array['profile_portal']  = array( 'field' => 'pp_member_id' , 'value' => intval( $member_key ) );

				self::_updateCache( $member_key, $save );
			}
		}
		else
		{
			$_member_k_array = $member_k_array;

			foreach( $member_key as $table => $data )
			{
				if ( isset( self::$remap[ $table ] ) )
				{
					$table = self::$remap[ $table ];
				}

				if ( ! in_array( $table, array_keys( $_member_k_array ) ) )
				{
					throw new Exception( 'INCORRECT_TABLE' );
				}

				$member_k_array[ $table ] = $data;
			}
		}

		//-----------------------------------------
		// Test...
		//-----------------------------------------

		if ( ! is_array( $member_k_array ) OR ! count( $member_k_array ) )
		{
			throw new Exception( 'NO_DATA' );
		}

		//-----------------------------------------
		// Now save...
		//-----------------------------------------
		
		foreach( $save as $table => $data )
		{
			if ( isset( self::$remap[ $table ] ) )
			{
				$table = self::$remap[ $table ];
			}

			if ( $table == 'profile_portal' )
			{
				$data[ $member_k_array[ $table ]['field'] ] = $member_k_array[ $table ]['value'];

				//-----------------------------------------
				// Does row exist?
				//-----------------------------------------

				$check = ipsRegistry::DB()->buildAndFetch( array( 'select' => 'pp_member_id', 'from' => 'profile_portal', 'where' => 'pp_member_id=' . $data['pp_member_id'] ) );

				if( !$check['pp_member_id'] )
				{
					ipsRegistry::DB()->insert( $table, $data );
				}
				else
				{
					ipsRegistry::DB()->update( $table, $data, 'pp_member_id=' . $data['pp_member_id'] );
				}
			}
			else if ( $table == 'pfields_content' )
			{
				$data[ $member_k_array[ $table ]['field'] ] = $member_k_array[ $table ]['value'];

				//-----------------------------------------
				// Does row exist?
				//-----------------------------------------

				$check = ipsRegistry::DB()->buildAndFetch( array( 'select' => 'member_id', 'from' => 'pfields_content', 'where' => 'member_id=' . $data['member_id'] ) );
				
				ipsRegistry::DB()->setDataType( array_keys( $data ), 'string' );

				if( !$check['member_id'] )
				{
					ipsRegistry::DB()->insert( $table, $data );
				}
				else
				{
					ipsRegistry::DB()->update( $table, $data, 'member_id=' . $data['member_id'] );
				}
			}
			else
			{
				if ( $table == 'members' )
				{
					/* Make sure we have a value for member_group_id if passed */
					if ( isset( $data['member_group_id'] ) AND ! $data['member_group_id'] )
					{
						throw new Exception( "NO_MEMBER_GROUP_ID" );
					}
					
					/* Some stuff that can end up  here */
					unset( $data['_canBeIgnored'] );
					
					/* Bitwise options */
					if ( is_array( $bitWiseFields['members'] ) )
					{
						$_freeze = array();
						
						foreach( $bitWiseFields['members'] as $field )
						{
							if ( isset( $data[ $field ] ) )
							{
								/* Add to freezeable array */
								$_freeze[ $field ] = $data[ $field ];
								
								/* Remove it from the fields to save to DB */
								unset( $data[ $field ] );
							}
						}
						
						if ( count( $_freeze ) )
						{
							$data['members_bitoptions'] = IPSBWOptions::freeze( $_freeze, 'members', 'global' );
						}
					}
					
					if( isset($data['members_display_name']) AND $data['members_display_name'] )
					{
						$data['members_l_display_name']	= strtolower($data['members_display_name']);
						$data['members_seo_name']		= IPSText::makeSeoTitle( $data['members_display_name'] );
					}
					
					if( isset($data['name']) AND $data['name'] )
					{
						$data['members_l_username']		= strtolower($data['name']);
					}
					
					ipsRegistry::DB()->setDataType( array( 'name', 'title', 'members_l_username', 'members_display_name', 'members_l_display_name', 'members_seo_name' ), 'string' );
					ipsRegistry::DB()->setDataType( array( 'msg_count_total', 'msg_count_new', 'members_bitoptions' ), 'int' );
				}
				
				ipsRegistry::DB()->update( $table, $data, $member_k_array[ $table ]['field'] . '=' . $member_k_array[ $table ]['value'] );
			}

			$_updated += ipsRegistry::instance()->DB()->getAffectedRows();
		}

		//-----------------------------------------
		// If member login key is updated during
		// session creation, this causes fatal error
		//-----------------------------------------
		
		if( is_object( ipsRegistry::member() ) )
		{
			$save[ $_MEMBERKEY ] = $_MEMBERVALUE;
			IPSLib::runMemberSync( 'onProfileUpdate', $save );
		}

		return ( $_updated > 0 ) ? TRUE : FALSE;
	}

	/**
	 * Load member
	 *
	 * @param 	string	Member key: Either ID or email address OR array of IDs when $key_type is either ID or not set OR a list of $key_type strings (email address, name, etc)
	 * @param 	string	Extra tables to load(all, none or comma delisted tables) Tables: members, pfields_content, profile_portal, groups, sessions, core_item_markers_storage.
	 *					You can also use the aliases: 'extendedProfile', 'customFields' and 'itemMarkingStorage'
	 * @param	string  Key type. Leave it blank to auto-detect or specify "id", "email", "username", "displayname".
	 * @return	array   Array containing member data
	 * <code>
	 * # Single member
	 * $member = IPSMember::load( 1, 'extendedProfile,groups' );
	 * $member = IPSMember::load( 'matt@email.com', 'all' );
	 * $member = IPSMember::load( 'MattM', 'all', 'displayname' ); // Can also use 'username', 'email' or 'id'
	 * # Multiple members
	 * $members = IPSMember::load( array( 1, 2, 10 ), 'all' );
	 * $members = IPSMember::load( array( 'MattM, 'JoeD', 'DaveP' ), 'all', 'displayname' );
	 * </code>
	 */
	static public function load( $member_key, $extra_tables='all', $key_type='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$member_value    = 0;
		$members         = array();
		$multiple_ids    = array();
		$member_field    = '';
		$joins           = array();
		$tables          = array( 'pfields_content' => 0, 'profile_portal' => 0, 'groups' => 0, 'sessions' => 0 );
		$remap           = array( 'extendedProfile'    => 'profile_portal',
							      'customFields'       => 'pfields_content');

		//-----------------------------------------
		// ID or email?
		//-----------------------------------------

		if ( ! $key_type )
		{
			if ( is_array( $member_key ) )
			{
				$multiple_ids = array_map( 'intval', $member_key ); // Bug #20908
				$member_field = 'member_id';
			}
			else
			{
				if ( strstr( $member_key, '@' ) )
				{
					$member_value = "'" . ipsRegistry::DB()->addSlashes( strtolower( $member_key ) ) . "'";
					$member_field = 'email';
				}
				else
				{
					$member_value = intval( $member_key );
					$member_field = 'member_id';
				}
			}
		}
		else
		{
			switch( $key_type )
			{
				default:
				case 'id':
					if ( is_array( $member_key ) )
					{
						$multiple_ids = $member_key;
					}
					else
					{
						$member_value = intval( $member_key );
					}
					$member_field = 'member_id';
				break;
				case 'fb_uid':
					if ( is_array( $member_key ) )
					{
						$multiple_ids = $member_key;
					}
					else
					{
						$member_value = is_numeric( $member_key ) ? $member_key : 0;
					}
					$member_field = 'fb_uid';
					
					if ( $member_value == 0 )
					{
						return array();
					}
				break;
				case 'twitter_id':
					if ( is_array( $member_key ) )
					{
						$multiple_ids = $member_key;
					}
					else
					{
						$member_value = is_numeric( $member_key ) ? $member_key : 0;
					}
					$member_field = 'twitter_id';
					
					if ( $member_value == 0 )
					{
						return array();
					}
				break;
				case 'email':
					if ( is_array( $member_key ) )
					{
						array_walk( $member_key, create_function( '&$v,$k', '$v="\'".ipsRegistry::DB()->addSlashes( strtolower( $v ) ) . "\'";' ) );
						$multiple_ids = $member_key;
					}
					else
					{
						$member_value = "'" . ipsRegistry::DB()->addSlashes( strtolower( $member_key ) ) . "'";
					}
					$member_field = 'email';
				break;
				case 'username':
					if ( is_array( $member_key ) )
					{
						array_walk( $member_key, create_function( '&$v,$k', '$v="\'".ipsRegistry::DB()->addSlashes( strtolower( $v ) ) . "\'";' ) );
						$multiple_ids = $member_key;
					}
					else
					{
						$member_value = "'" . ipsRegistry::DB()->addSlashes( strtolower( $member_key ) ) . "'";
					}
					$member_field = 'members_l_username';
				break;
				case 'displayname':
					if ( is_array( $member_key ) )
					{
						array_walk( $member_key, create_function( '&$v,$k', '$v="\'".ipsRegistry::DB()->addSlashes( strtolower( $v ) ) . "\'";' ) );
						$multiple_ids = $member_key;
					}
					else
					{
						$member_value = "'" . ipsRegistry::DB()->addSlashes( strtolower( $member_key ) ) . "'";
					}
					$member_field = 'members_l_display_name';
				break;
			}
		}
		
		//-----------------------------------------
		// Protected against member_id=0
		//-----------------------------------------
		
		if( !count($multiple_ids) OR !is_array($multiple_ids) )
		{
			if( $member_field == 'member_id' AND !$member_value )
			{
				return array();
			}
		}

		//-----------------------------------------
		// Sort out joins...
		//-----------------------------------------

		if ( $extra_tables == 'all' )
		{
			foreach( $tables as $_table => $_val )
			{
				/* Let's not load sessions unless specifically requested */
				if ( $_table == 'sessions' )
				{
					continue;
				}
				
				$tables[ $_table ] = 1;
			}
		}
		else if ( $extra_tables )
		{
			$_tables = explode( ",", $extra_tables );

			foreach( $_tables as $_t )
			{
				$_t = trim( $_t );
				
				if ( isset( $tables[ $_t ] ) )
				{
					$tables[ $_t ] = 1;
				}
				else if ( isset( self::$remap[ $_t ] ) )
				{
					if ( strstr( $tables[ self::$remap[ $_t ] ], ',' ) )
					{
						$__tables = explode( ',', $tables[ self::$remap[ $_t ] ] );

						foreach( $__tables as $__t )
						{
							$tables[ $__t ] = 1;
						}
					}
					else
					{
						$tables[ self::$remap[ $_t ] ] = 1;
					}
				}
			}
		}

		//-----------------------------------------
		// Grab used tables
		//-----------------------------------------

		$_usedTables = array();

		foreach( $tables as $_name => $_use )
		{
			if ( $_use )
			{
				$_usedTables[] = $_name;
			}
		}

		//-----------------------------------------
		// Check the cache first...
		//-----------------------------------------
		
		if ( $member_field == 'member_id' AND $member_value )
		{
			$member = self::_fetchFromCache( $member_value, $_usedTables );

			if ( $member !== FALSE )
			{
				return $member;
			}
		}
		else if( count($multiple_ids) AND is_array($multiple_ids) )
		{
			$_totalUsers	= count($multiple_ids);
			$_gotFromCache	= 0;
			$_fromCache		= array();
			
			foreach( $multiple_ids as $_memberValue )
			{
				$member = self::_fetchFromCache( $_memberValue, $_usedTables );
				
				if ( $member !== FALSE )
				{
					$_fromCache[ $member['member_id'] ]	= $member;
					$_gotFromCache++;
				}
			}

			//-----------------------------------------
			// Did we find all the members in cache?
			//-----------------------------------------
			
			if( $_gotFromCache == $_totalUsers )
			{
				return $_fromCache;
			}
		}

		self::$ignoreCache = FALSE;

		//-----------------------------------------
		// Fix up joins...
		//-----------------------------------------

		if ( $tables['pfields_content'] )
		{
			$joins[] = array( 'select' => 'p.*',
						  	  'from'   => array( 'pfields_content' => 'p' ),
						  	  'where'  => 'p.member_id=m.member_id',
						  	  'type'   => 'left' );
		}

		if ( $tables['profile_portal'] )
		{
			$joins[] = array( 'select' => 'pp.*',
						  	  'from'   => array( 'profile_portal' => 'pp' ),
							  'where'  => 'pp.pp_member_id=m.member_id',
							  'type'   => 'left' );
		}

		if ( $tables['groups'] )
		{
			$joins[] = array( 'select' => 'g.*',
			 				  'from'   => array( 'groups' => 'g' ),
							  'where'  => 'g.g_id=m.member_group_id',
						      'type'   => 'left' );
		}

		if ( $tables['sessions'] )
		{
			$joins[] = array( 'select' => 's.*',
			 				  'from'   => array( 'sessions' => 's' ),
							  'where'  => 's.member_id=m.member_id',
						      'type'   => 'left' );
		}
		
		if ( $tables['core_item_markers_storage'] )
		{
			$joins[] = array( 'select' => 'im.*',
			 				  'from'   => array( 'core_item_markers_storage' => 'im' ),
							  'where'  => 'im.item_member_id=m.member_id',
						      'type'   => 'left' );
		}
		
		if ( IPSContentCache::isEnabled() )
		{
			if ( IPSContentCache::fetchSettingValue( 'sig' ) )
			{
				$joins[] = IPSContentCache::join( 'sig' , 'm.member_id', 'ccb', 'left', 'ccb.cache_content' );
			}
		}

		//-----------------------------------------
		// Do eeet
		//-----------------------------------------

		if ( count( $joins ) )
		{
			ipsRegistry::DB()->build( array( 'select'   => 'm.*, m.member_id as my_member_id',
											 'from'     => array( 'members' => 'm' ),
											 'where'    => ( is_array( $multiple_ids ) AND count( $multiple_ids ) ) ?  'm.'. $member_field . ' IN (' . implode( ',', $multiple_ids ) . ')' : 'm.'. $member_field . '=' . $member_value,
											 'add_join' => $joins ) );
		}
		else
		{
			ipsRegistry::DB()->build( array( 'select'   => '*',
											 'from'     => 'members',
											 'where'    => ( is_array( $multiple_ids ) AND count( $multiple_ids ) ) ?  $member_field . ' IN (' . implode( ',', $multiple_ids ) . ')' : $member_field . '=' . $member_value ) );
		}

		//-----------------------------------------
		// Execute
		//-----------------------------------------

		ipsRegistry::DB()->execute();

		while( $mem = ipsRegistry::DB()->fetch() )
		{
			if ( isset( $mem['my_member_id'] ) )
			{
				$mem['member_id'] = $mem['my_member_id'];
			}
			
			$mem['full']		= true;
			
			if( !$mem['email'] OR !$mem['members_display_name'] OR $mem['email'] == $mem['name'] . '@' . $mem['joined'] )
			{
				$mem['full']	= false;
				$mem['timenow']	= $mem['joined'];
			}

			/* Clean secondary groups */
			$mem['mgroup_others'] = ($mem['mgroup_others'] != '') ? IPSText::cleanPermString($mem['mgroup_others']) : '';

			//-----------------------------------------
			// Be sure we properly apply secondary permissions
			//-----------------------------------------

			if ( $tables['groups'] )
			{
				$mem = ips_MemberRegistry::setUpSecondaryGroups( $mem );

				/* Unpack groups */
				$mem = IPSMember::unpackGroup( $mem, TRUE );
			}

			//-----------------------------------------
			// Unblockable
			//-----------------------------------------

			$mem['_canBeIgnored'] = self::isIgnorable( $mem['member_group_id'], $mem['mgroup_others'] );
			
			/* Bitwise Options */
			$mem = self::buildBitWiseOptions( $mem );
			
			/* Twitter is disabled them remove twitter tokens and such */
		 	if ( $mem['twitter_id'] && ! IPSLib::twitter_enabled() )
		 	{
		 		$mem['twitter_token']  = '';
		 		$mem['twitter_secret'] = '';
		 		$mem['twitter_id']     = '';
		 	}
	 	
			/* Add to array */
			$members[ $mem['member_id'] ] = $mem;

			//-----------------------------------------
			// Add to cache
			//-----------------------------------------

			self::_addToCache( $mem, $_usedTables );
		}
		
		//-----------------------------------------
		// Return just a single if we only sent one id
		//-----------------------------------------

		return ( is_array( $multiple_ids ) AND count( $multiple_ids ) ) ? $members : array_shift( $members );
	}

	/**
	 * Delete member(s)
	 *
	 * @param 	mixed		[Integer] member ID or [Array] array of member ids
	 * @param	boolean		Check if request is from an admin
	 * @return	boolean		Action completed successfully
	 */
	static public function remove( $id, $check_admin=true )
	{
		/* Init vars */
		$mids		= '';
		$tmp_mids 	= array();
		$emails		= array();

		//-----------------------------------------
		// Sort out thingie
		//-----------------------------------------

		if ( is_array( $id ) )
		{
			$id = IPSLib::cleanIntArray( $id );
			
			if ( count($id) )
			{
				$mids = ' IN (' . implode( ",", $id ) . ')';
			}
		}
		else
		{
			$id = intval($id);
			
			if ( $id > 0 )
			{
				$mids = ' = ' . $id;
			}
		}
		
		if ( empty($mids) )
		{
			return false;
		}
		
		//-----------------------------------------
		// Get accounts and check IDS
		//-----------------------------------------

		ipsRegistry::DB()->build( array(
										'select'	=> 'm.member_id, m.name, m.member_group_id, m.email', 
										'from'		=> array( 'members' => 'm' ),
										'where'		=> 'm.member_id' . $mids,
										'add_join'	=> array(
															array(
																'select'	=> 'g.g_access_cp',
																'from'		=> array( 'groups' => 'g' ),
																'where'		=> 'g.g_id=m.member_group_id',
																'type'		=> 'left',
																)
															)
								)		);
		ipsRegistry::DB()->execute();

		while ( $r = ipsRegistry::DB()->fetch() )
		{
			//-----------------------------------------
			// Non root admin attempting to edit root admin?
			//-----------------------------------------

			if( $check_admin )
			{
				if ( !ipsRegistry::member()->getProperty('g_access_cp') )
				{
					if ( $r['g_access_cp'] )
					{
						continue;
					}
				}
			}

			$tmp_mids[]	= $r['member_id'];
			$emails[]	= $r['email'];

			self::_removeFromCache( $r['member_id'] );
		}

		if ( ! count( $tmp_mids ) )
		{
			return false;
		}

		$mids = ' IN (' . implode( ",", $tmp_mids ) . ')';

		//-----------------------------------------
		// Get photo
		//-----------------------------------------

		$delete_files = array();

		ipsRegistry::DB()->build( array( 'select' => '*', 'from' => 'profile_portal', 'where' => 'pp_member_id' . $mids ) );
		ipsRegistry::DB()->execute();

		while( $r = ipsRegistry::DB()->fetch() )
		{
			if ( $r['pp_main_photo']  )
			{
				$delete_files[] = $r['pp_main_photo'];
			}

			if ( $r['pp_thumb_photo']  )
			{
				$delete_files[] = $r['pp_thumb_photo'];
			}

			$_customizations	= unserialize($r['pp_customization']);
			
			if( $_customizations['type'] == 'upload' AND $_customizations['bg_url'] )
			{
				$delete_files[]	= $_customizations['bg_url'];
			}
		}

		//-----------------------------------------
		// Take care of forum stuff
		//-----------------------------------------

		ipsRegistry::DB()->update( 'posts'					, array( 'author_id'  => 0 ), "author_id" . $mids );
		ipsRegistry::DB()->update( 'topics'					, array( 'starter_id' => 0 ), "starter_id" . $mids );
		ipsRegistry::DB()->update( 'topics'					, array( 'last_poster_id' => 0 ), "last_poster_id" . $mids );
		ipsRegistry::DB()->update( 'announcements'			, array( 'announce_member_id' => 0 ), "announce_member_id" . $mids );
		ipsRegistry::DB()->update( 'attachments'			, array( 'attach_member_id' => 0 ), "attach_member_id" . $mids );
		ipsRegistry::DB()->update( 'polls'					, array( 'starter_id' => 0 ), "starter_id" . $mids );
		
		/**
		 * @todo	Why is this query commented? Need to investigate and change for 3.4 if needed
		 */
		//ipsRegistry::DB()->update( 'topic_ratings'			, array( 'rating_member_id' => 0 ), "rating_member_id" . $mids );
		ipsRegistry::DB()->update( 'voters'					, array( 'member_id' => 0 ), "member_id" . $mids );
		ipsRegistry::DB()->update( 'forums'					, array( 'last_poster_id' => 0, 'last_poster_name' => '', 'seo_last_name' => '' ), "last_poster_id" . $mids );
		ipsRegistry::DB()->delete( 'core_share_links_log'	, "log_member_id" . $mids );
		ipsRegistry::DB()->delete( 'core_soft_delete_log'	, "sdl_obj_member_id" . $mids );
		ipsRegistry::DB()->delete( 'mobile_device_map'   	, "member_id" . $mids );
		ipsRegistry::DB()->update( 'rss_import'				, array( 'rss_import_mid' => 0 ), "rss_import_mid" . $mids );
		ipsRegistry::DB()->update( 'core_tags'				, array( 'tag_member_id' => 0 ), "tag_member_id" . $mids );
		
		/* Update archived posts */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/archive/writer.php', 'classes_archive_writer' );
		$archiveWriter = new $classToLoad();
		$archiveWriter->setApp('forums');
		
		$archiveWriter->update( array( 'archive_author_id' => 0 ), 'archive_author_id' . $mids );
		
		//-----------------------------------------
		// Likes - also invalidates likes cache
		// @todo - Move this into like class - problem is like object expects to know what plugin to use in bootstrap method, but we want to remove for all plugins...
		//-----------------------------------------
		
		$_likes	= array();
		
		ipsRegistry::DB()->build( array( 'select' => 'like_lookup_id', 'from' => 'core_like', 'where' => "like_member_id" . $mids ) );
		ipsRegistry::DB()->execute();
		
		while( $r = ipsRegistry::DB()->fetch() )
		{
			$_likes[]	= $r['like_lookup_id'];
		}

		if( count($_likes) )
		{
			ipsRegistry::DB()->delete( 'core_like'				, "like_member_id" . $mids );
			ipsRegistry::DB()->delete( 'core_like_cache'		, "like_cache_id IN('" . implode( "','", array_map( 'addslashes', $_likes ) ) . "')" );
		}

		//-----------------------------------------
		// Clean up profile stuff
		//-----------------------------------------

		ipsRegistry::DB()->update( 'profile_ratings'		, array( 'rating_by_member_id' => 0 ), "rating_by_member_id" . $mids );

		ipsRegistry::DB()->delete( 'profile_ratings'		, "rating_for_member_id" . $mids );

		ipsRegistry::DB()->delete( 'profile_portal'			, "pp_member_id" . $mids );
		ipsRegistry::DB()->delete( 'profile_portal_views'	, "views_member_id" . $mids );
		
		$_friendMemberIds	= array();
		
		ipsRegistry::DB()->build( array( 'select' => '*', 'from' => 'profile_friends', 'where' => "friends_member_id" . $mids . " OR friends_friend_id" . $mids ) );
		ipsRegistry::DB()->execute();
		
		while( $r = ipsRegistry::DB()->fetch() )
		{
			if( !in_array( $r['friends_friend_id'], $tmp_mids ) )
			{
				$_friendMemberIds[ $r['friends_friend_id'] ]	= $r['friends_friend_id'];
			}

			if( !in_array( $r['friends_member_id'], $tmp_mids ) )
			{
				$_friendMemberIds[ $r['friends_member_id'] ]	= $r['friends_member_id'];
			}
		}
		
		ipsRegistry::DB()->delete( 'profile_friends'		, "friends_member_id" . $mids );
		ipsRegistry::DB()->delete( 'profile_friends'		, "friends_friend_id" . $mids );
		ipsRegistry::DB()->delete( 'profile_friends_flood'	, "friends_member_id" . $mids . " OR friends_friend_id" . $mids );

		ipsRegistry::DB()->delete( 'dnames_change'			, "dname_member_id" . $mids );
		ipsRegistry::DB()->delete( 'mobile_notifications'	, "member_id" . $mids );

		//-----------------------------------------
		// Delete member...
		//-----------------------------------------

		ipsRegistry::DB()->delete( 'pfields_content'		, "member_id" . $mids );
		ipsRegistry::DB()->delete( 'members_partial'		, "partial_member_id" . $mids );
		ipsRegistry::DB()->delete( 'moderators'				, "member_id" . $mids );
		ipsRegistry::DB()->delete( 'sessions'				, "member_id" . $mids );
		ipsRegistry::DB()->delete( 'search_sessions'		, "session_member_id" . $mids );
		ipsRegistry::DB()->delete( 'upgrade_sessions'		, "session_member_id" . $mids );
		ipsRegistry::DB()->delete( 'members_warn_logs'		, "wl_member" . $mids );
		ipsRegistry::DB()->update( 'members_warn_logs'		, array( 'wl_moderator' => 0 ), "wl_moderator" . $mids );
		ipsRegistry::DB()->delete( 'member_status_actions'	, "action_member_id" . $mids );
		ipsRegistry::DB()->delete( 'member_status_actions'	, "action_status_owner" . $mids );
		ipsRegistry::DB()->delete( 'member_status_replies'	, "reply_member_id" . $mids );
		ipsRegistry::DB()->delete( 'member_status_updates'	, "status_member_id" . $mids );

		//-----------------------------------------
		// Update admin stuff and logs
		//-----------------------------------------

		ipsRegistry::DB()->delete( 'admin_permission_rows'	, "row_id_type='member' AND row_id" . $mids );
		ipsRegistry::DB()->delete( 'core_sys_cp_sessions' 	, 'session_member_id' . $mids );
		ipsRegistry::DB()->delete( 'core_sys_login' 		, 'sys_login_id' . $mids );
		ipsRegistry::DB()->update( 'upgrade_history'		, array( 'upgrade_mid' => 0 ), "upgrade_mid" . $mids );
		ipsRegistry::DB()->update( 'admin_logs'				, array( 'member_id' => 0 ), "member_id" . $mids );
		ipsRegistry::DB()->update( 'error_logs'				, array( 'log_member' => 0 ), "log_member" . $mids );
		ipsRegistry::DB()->update( 'moderator_logs'			, array( 'member_id' => 0, 'member_name' => '' ), "member_id" . $mids );

		//-----------------------------------------
		// Delete PMs
		//-----------------------------------------
		
		$messageIds		= array();
		
		ipsRegistry::DB()->build( array( 'select' => 'map_topic_id, map_user_id', 'from' => 'message_topic_user_map', 'where' => 'map_user_id' . $mids ) );
		ipsRegistry::DB()->execute();
		
		while( $r = ipsRegistry::DB()->fetch() )
		{
			$messageIds[ $r['map_user_id'] ][]	= $r['map_topic_id'];
		}

		if( count($messageIds) )
		{
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('members') . '/sources/classes/messaging/messengerFunctions.php', 'messengerFunctions', 'members' );
			$messenger		= new $classToLoad( ipsRegistry::instance() );
			
			foreach( $messageIds as $user => $topics )
			{
				$messenger->deleteTopics( $user, $topics );
			}
		}

		//-----------------------------------------
		// Fallback cleanup
		//-----------------------------------------
		
		ipsRegistry::DB()->delete( 'message_topic_user_map'	, 'map_user_id' . $mids );
		ipsRegistry::DB()->update( 'message_posts'			, array( 'msg_author_id' => 0 ), 'msg_author_id' . $mids );
		ipsRegistry::DB()->update( 'message_topics'			, array( 'mt_starter_id' => 0 ), 'mt_starter_id' . $mids );
		ipsRegistry::DB()->update( 'message_topics'			, array( 'mt_to_member_id' => 0 ), 'mt_to_member_id' . $mids );

		//-----------------------------------------
		// Delete subs, views, markers, etc.
		//-----------------------------------------

		ipsRegistry::DB()->delete( 'ignored_users'			, "ignore_owner_id" . $mids . " or ignore_ignore_id" . $mids );
		ipsRegistry::DB()->delete( 'inline_notifications'	, "notify_to_id" . $mids );
		ipsRegistry::DB()->update( 'inline_notifications'	, array( 'notify_from_id' => 0 ), 'notify_from_id' . $mids );
		ipsRegistry::DB()->delete( 'core_item_markers'		, "item_member_id" . $mids );
		ipsRegistry::DB()->delete( 'core_item_markers_storage', "item_member_id" . $mids );
		ipsRegistry::DB()->update( 'rc_comments'			, array( 'comment_by' => 0 ), "comment_by" . $mids );
		ipsRegistry::DB()->delete( 'rc_modpref'				, "mem_id" . $mids );
		ipsRegistry::DB()->update( 'rc_reports'				, array( 'report_by' => 0 ), "report_by" . $mids );
		ipsRegistry::DB()->update( 'rc_reports_index'		, array( 'updated_by' => 0 ), "updated_by" . $mids );
		ipsRegistry::DB()->delete( 'rc_reports_index'		, "seotemplate='showuser' AND exdat1" . $mids );
		ipsRegistry::DB()->delete( 'reputation_cache'		, "type='member' AND type_id" . $mids );
		ipsRegistry::DB()->delete( 'reputation_index'		, "member_id" . $mids );
		
		$cache	= ipsRegistry::cache()->getCache('report_cache');
		$cache['last_updated']	= time();
		
		ipsRegistry::cache()->setCache( 'report_cache', $cache, array( 'array' => 1 ) );

		//-----------------------------------------
		// Delete from validating..
		//-----------------------------------------

		ipsRegistry::DB()->delete( 'validating'				, "member_id" . $mids );
		ipsRegistry::DB()->delete( 'members'				, "member_id" . $mids );
		
		/* Delete from profile cache */
		if ( count( $_friendMemberIds ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('members') . '/sources/friends.php', 'profileFriendsLib', 'members' );
			$friends     = new $classToLoad( ipsRegistry::instance() );
			
			foreach( $_friendMemberIds as $_mid )
			{
				$friends->recacheFriends( array( 'member_id' => $_mid ) );
			}
		}
		
		//-----------------------------------------
		// Delete photos
		//-----------------------------------------

		if ( count($delete_files) )
		{
			foreach( $delete_files as $file )
			{
				@unlink( ipsRegistry::$settings['upload_dir'] . "/" . $file );
			}
		}

		//-----------------------------------------
		// Member Sync
		//-----------------------------------------

		IPSLib::runMemberSync( 'onDelete', $mids );
		
		/* Remove from cache */
		IPSContentCache::drop( 'sig', $tmp_mids );
		
		//-----------------------------------------
		// Get current stats...
		//-----------------------------------------

		ipsRegistry::cache()->rebuildCache( 'stats', 'global' );
		ipsRegistry::cache()->rebuildCache( 'moderators', 'forums' );
		ipsRegistry::cache()->rebuildCache( 'birthdays', 'calendar' );
		ipsRegistry::cache()->rebuildCache( 'announcements', 'forums' );
		
		return true;
	}

	/**
	 * Set up moderator, populate moderator functions
	 *
	 * @param	array 		Array of member data
	 * @return	array 		Array of member data populated with moderator details
	 */
	static public function setUpModerator( $member )
	{
		static $cache	= array();
		
		if( $cache[ $member['member_id'] ] )
		{
			return array_merge( $member, $cache[ $member['member_id'] ] );
		}

		$other_mgroups	= array();
		$return			= array();
		
		if ( $member['member_group_id'] != ipsRegistry::$settings['guest_group'] )
		{
			//-----------------------------------------
			// Sprinkle on some moderator stuff...
			//-----------------------------------------

			if ( $member['g_is_supmod'] == 1 )
			{
				$return['is_mod'] = 1;
			}
			else if ( is_array(ipsRegistry::cache()->getCache('moderators')) AND count(ipsRegistry::cache()->getCache('moderators')) )
			{
				$other_mgroups = array();

				if ( IPSText::cleanPermString( $member['mgroup_others'] ) )
				{
					$other_mgroups = explode( ",", IPSText::cleanPermString( $member['mgroup_others'] ) );
				}
			}
			
			if( is_array(ipsRegistry::cache()->getCache('moderators')) AND count(ipsRegistry::cache()->getCache('moderators')) )
			{
				$_mod_forums = isset( $member['forumsModeratorData'] ) && is_array( $member['forumsModeratorData'] ) ? $member['forumsModeratorData'] : array();
				
				foreach( ipsRegistry::cache()->getCache('moderators') as $r )
				{
					$modForumIds = explode( ',', IPSText::cleanPermString( $r['forum_id'] ) );
					
					if ( $r['member_id'] AND $r['member_id'] == $member['member_id'] )
					{
						foreach( $modForumIds as $modForumId )
						{
							$_mod_forums[ $modForumId ] = $r;
						}

						$return['is_mod'] = 1;
					}
					else if( $r['group_id'] AND $r['group_id'] == $member['member_group_id'] )
					{
						// Individual mods override group mod settings
						// If array is set, don't override it

						foreach( $modForumIds as $modForumId )
						{
							if( !is_array($_mod_forums[ $modForumId ]) OR !count($_mod_forums[ $modForumId ]) )
							{
								$_mod_forums[ $modForumId ] = $r;
							}
						}

						$return['is_mod'] = 1;
					}
					else if( $r['group_id'] AND count( $other_mgroups ) AND in_array( $r['group_id'], $other_mgroups ) )
					{
						// Individual mods override group mod settings
						// If array is set, don't override it
	
						foreach( $modForumIds as $modForumId )
						{
							if( !is_array($_mod_forums[ $modForumId ]) OR !count($_mod_forums[ $modForumId ]) )
							{
								$_mod_forums[ $modForumId ] = $r;
							}
						}
	
						$return['is_mod'] = 1;
					}						
				}
				
				$return['forumsModeratorData'] = $_mod_forums;
			}
		}

		$cache[ $member['member_id'] ]	= $return;
		return array_merge( $member, $cache[ $member['member_id'] ] );
	}
	
	/**
	 * Fetches SEO name, updating the table if required
	 *
	 * @param	array		Member data
	 * @return	string		SEO Name
	 */
	static public function fetchSeoName( $memberData )
	{
		if ( ! is_array( $memberData ) OR ! $memberData['member_id'] )
		{
			return;
		}
		
		if ( !empty( $memberData['members_seo_name'] ) )
		{
			return $memberData['members_seo_name'];
		}
		else if ( !empty( $memberData['members_display_name'] ) )
		{
			$_seoName = IPSText::makeSeoTitle( $memberData['members_display_name'] );

			ipsRegistry::DB()->update( 'members', array( 'members_seo_name' => $_seoName ), 'member_id=' . $memberData['member_id'] );
			
			return $_seoName;
		}
		else
		{
			return '-';
		}
	}
	
	/**
	 * Fetches Ignore user data
	 *
	 * @param	array		Member data
	 * @return	array		Array of ignored users
	 */
	static public function fetchIgnoredUsers( $memberData )
	{
		/* INIT */
		$ignore_users = array();
		
		if ( $memberData['member_id'] )
		{
			/* < 3.0.0 used comma delisted string. 3.0.0+ uses serialized array */
			if ( strstr( $memberData['ignored_users'], 'a:' ) )
			{
				$data = unserialize( $memberData['ignored_users'] );
				
				return ( is_array( $data ) ) ? $data : array();
			}
			else
			{
				if ( $memberData['ignored_users'] )
				{
					$_data = explode( ",", $memberData['ignored_users'] );
				
					foreach( $_data as $id )
					{
						if ( $id )
						{
							$ignore_users[ $id ] = array( 'ignore_ignore_id'  => $id,
														  'ignore_messages'   => 0,
														  'ignore_signatures' => 0,
														  'ignore_topics'     => 1 );
						}
					}
				}
				
				/* Now fetch them from the DB */
				ipsRegistry::DB()->build( array( 'select' => '*', 'from' => 'ignored_users', 'where' => "ignore_owner_id=" . $memberData['member_id'] ) );
				ipsRegistry::DB()->execute();

				while( $r = ipsRegistry::DB()->fetch() )
				{
					$ignore_users[ $r['ignore_ignore_id'] ] = array( 'ignore_ignore_id'  => $r['ignore_ignore_id'],
												  					 'ignore_messages'   => $r['ignore_messages'],
																	 'ignore_signatures' => $r['ignore_signatures'],
																	 'ignore_topics'     => $r['ignore_topics'] );
				}
				
				/* Update.... */
				self::save( $memberData['member_id'], array( 'core' => array( 'ignored_users' => serialize( $ignore_users ) ) ) );
			}
		}
		
		return $ignore_users;
	}
	
	/**
	 * Updates member.ignored_users
	 *
	 * @param	mixed		Member ID or Member data
	 * @return	array		Array of ignored users
	 */
	static public function rebuildIgnoredUsersCache( $member )
	{
		/* INIT */
		$ignore_users = array();
		
		$memberData = ( ! is_array( $member ) ) ? self::load( $member, 'all' ) : $member;
		
		/* Continue */
		if ( $memberData['member_id'] )
		{
			/* Fetch from DB */
			ipsRegistry::DB()->build( array( 'select' => '*', 'from' => 'ignored_users', 'where' => "ignore_owner_id=" . $memberData['member_id'] ) );
			ipsRegistry::DB()->execute();

			while( $r = ipsRegistry::DB()->fetch() )
			{
				$ignore_users[ $r['ignore_ignore_id'] ] = array( 'ignore_ignore_id'  => $r['ignore_ignore_id'],
											  					 'ignore_messages'   => $r['ignore_messages'],
																 'ignore_signatures' => $r['ignore_signatures'],
																 'ignore_topics'     => $r['ignore_topics'],
																 'ignore_chats'		 => $r['ignore_chats'] );
			}
		
			/* Update.... */
			self::save( $memberData['member_id'], array( 'core' => array( 'ignored_users' => serialize( $ignore_users ) ) ) );
		}
	}

	/**
	 * Retrieve the member's location
	 *
	 * @author	Brandon Farber
	 * @param	array 		Member information (including session info!)
	 * @return	array 		Member info with session info parsed
	 * @since	IPB 3.0
	 */
	static public function getLocation( $member )
	{
		$member['online_extra'] = "";
		
		//-----------------------------------------
		// Grab 'where' info
		//-----------------------------------------
		
		if( !$member['in_error'] AND $member['current_appcomponent'] AND IPSLib::appIsInstalled( $member['current_appcomponent'] ) )
		{
			$member['current_appcomponent'] = IPSText::alphanumericalClean($member['current_appcomponent']);
			
			$filename = IPSLib::getAppDir(  $member['current_appcomponent'] ) . '/extensions/coreExtensions.php';

			if ( is_file( $filename ) )
			{
				$toload = IPSLib::loadLibrary( $filename, 'publicSessions__' . $member['current_appcomponent'], $member['current_appcomponent'] );
				
				if( class_exists( $toload ) )
				{
					$loader = new $toload();
	
					if( method_exists( $loader, 'parseOnlineEntries' ) )
					{
						$tmp = $loader->parseOnlineEntries( array( $member['id'] => $member ) );
	
						// Yes, this is really id - it's session id, not member id
						if( isset( $tmp[ $member['id'] ] ) && is_array( $tmp[ $member['id'] ] ) && count( $tmp[ $member['id'] ] ) )
						{
							if ( isset( $tmp[ $member['id'] ]['_whereLinkSeo']) )
							{
								$member['online_extra'] = "{$tmp[ $member['id'] ]['where_line']} <a href='" . $tmp[ $member['id'] ]['_whereLinkSeo'] . "' title='" . $tmp[ $member['id'] ]['where_line'] . ' ' . $tmp[ $member['id'] ]['where_line_more'] . "'>" . IPSText::truncate( $tmp[ $member['id'] ]['where_line_more'], 35 ) . "</a>";
							}
							/* @link	http://community.invisionpower.com/tracker/issue-20598-where-link-not-taken-into-account-on-profile-page-if-no-where-line-more-specified/ */
							else if ( isset($tmp[ $member['id'] ]['where_link']) AND $tmp[ $member['id'] ]['where_line_more'] )
							{
								$member['online_extra'] = "{$tmp[ $member['id'] ]['where_line']} <a href='" . ipsRegistry::$settings['base_url'] . "{$tmp[ $member['id'] ]['where_link']}' title='" . $tmp[ $member['id'] ]['where_line'] . ' ' . $tmp[ $member['id'] ]['where_line_more'] . "'>" . IPSText::truncate( $tmp[ $member['id'] ]['where_line_more'], 35 ) . "</a>";
							}
							else if ( isset($tmp[ $member['id'] ]['where_link']) )
							{
								$member['online_extra'] = "<a href='" . ipsRegistry::$settings['base_url'] . "{$tmp[ $member['id'] ]['where_link']}' title='" . $tmp[ $member['id'] ]['where_line'] . "'>" . IPSText::truncate( $tmp[ $member['id'] ]['where_line'], 35 ) . "</a>";
							}
							else
							{
								$member['online_extra'] = $tmp[ $member['id'] ]['where_line'];
							}
						}
					}
				}
			}
		}

		if ( ! $member['online_extra'] )
		{
			$member['online_extra'] = $member['id'] ? ipsRegistry::getClass('class_localization')->words['board_index'] 
													: ipsRegistry::getClass('class_localization')->words['not_online'];
		}

		return $member;
	}

	/**
	 * Determine if two members are friends
	 *
	 * @author	Brandon Farber
	 * @param	integer		Member ID to check for
	 * @param	integer 	Member ID to check against (defaults to current member id)
	 * @param	bool		Only return true if the friend is approved
	 * @return	boolean		Whether they are friends or not
	 * @since	IPB 3.0
	 */
	static public function checkFriendStatus( $memberId, $checkAgainst=0, $onlyVerified=false )
	{
		/**
		 * If no member id, obviously not friends
		 */
		if( !$memberId )
		{
			return false;
		}

		/**
		 * Get member data
		 */
		$memberData	= array();

		if( !$checkAgainst )
		{
			$memberData	= ipsRegistry::instance()->member()->getProperty('_cache');
		}
		else
		{
			$member		= self::load( $checkAgainst, 'extendedProfile' );
			$memberData	= self::unpackMemberCache( $member['members_cache'] );
		}

		/**
		 * Do we have a friends cache array?
		 */
		if( !$memberData['friends'] OR !is_array($memberData['friends']) OR !count($memberData['friends']) )
		{
			return false;
		}

		/**
		 * If there is, then check it..
		 */
		if( $onlyVerified )
		{
			if( isset($memberData['friends'][ $memberId ]) AND $memberData['friends'][ $memberId ] )
			{
				return true;
			}
			
			return false;
		}
		else
		{
			return isset($memberData['friends'][ $memberId ]) ? true : false;
		}
		
		return false;
	}
	
	/**
	 * Determine if a member is ignoring another member
	 *
	 * @author	Brandon Farber
	 * @param	integer		Member ID to check for
	 * @param	integer 	Member ID to check against (defaults to current member id)
	 * @param	string		Type of ignoring to check [messages|topics].  Omit to check any type.
	 * @return	boolean		Whether the member id to check for is being ignored by the member id to check against
	 * @since	IPB 3.0
	 */
	static public function checkIgnoredStatus( $memberId, $checkAgainst=0, $type=false )
	{
		/**
		 * If no member id, obviously not ignored
		 */
		if( !$memberId )
		{
			return false;
		}

		/**
		 * Get member data
		 */
		$memberData	= array();

		if( !$checkAgainst )
		{
			/**
			 * Ignored users loaded at runtime and stored in an array...loop
			 */
			foreach( ipsRegistry::instance()->member()->ignored_users as $ignoredUser )
			{
				/**
				 * We found the user?
				 */
				if( $ignoredUser['ignore_ignore_id'] )
				{
					/**
					 * If not specifying a type, then just return
					 */
					if( !$type )
					{
						return true;
					}
					/**
					 * Otherwise verify we are ignoring that type
					 */
					else if( $ignoredUser[ 'ignore_' . $type ] )
					{
						return true;
					}
				}
			}
		}
		else
		{
			/**
			 * See if checkAgainst is ignoring memberId
			 */
			$checkAgainst	= intval($checkAgainst);
			$ignoredUser	= ipsRegistry::instance()->member()->DB()->buildAndFetch( array( 'select' => '*', 'from' => 'ignored_users', 'where' => 'ignore_owner_id=' . $checkAgainst . ' AND ignore_ignore_id=' . $memberId ) );
			
			/**
			 * No?
			 */
			if( !$ignoredUser['ignore_id'] )
			{
				return false;
			}
			/**
			 * He is?
			 */
			else
			{
				/**
				 * If not specifying a type, then just return
				 */
				if( !$type )
				{
					return true;
				}
				/**
				 * Otherwise verify we are ignoring that type
				 */
				else if( $ignoredUser[ 'ignore_' . $type ] )
				{
					return true;
				}
			}
		}

		/**
		 * If we're here (which we shouldn't be) just return false
		 */
		return false;
	}

	/**
	 * Retrieve all IP addresses a user (or multiple users) have used
	 *
	 * @param 	mixed		[Integer] member ID or [Array] array of member ids
	 * @param	string		Defaults to 'All', otherwise specify which tables to check (comma separated)
	 * @return	array		Multi-dimensional array of found IP addresses in which sections
	 */
	static public function findIPAddresses( $id, $tables_to_check='all' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$ip_addresses 	= array();
		$tables			= array(
							'admin_logs'			=> array( 'member_id', 'ip_address', 'ctime' ),
							'dnames_change'			=> array( 'dname_member_id', 'dname_ip_address', 'dname_date' ),
							'members'				=> array( 'member_id', 'ip_address', 'joined' ),
							'message_posts'			=> array( 'msg_author_id', 'msg_ip_address', 'msg_date' ),
							'moderator_logs'		=> array( 'member_id', 'ip_address', 'ctime' ),
							'posts'					=> array( 'author_id', 'ip_address', 'post_date' ),
							'member_status_updates'	=> array( 'status_author_id', 'status_author_ip', 'status_date' ),
							'profile_ratings'		=> array( 'rating_by_member_id', 'rating_ip_address', '' ),
							'sessions'				=> array( 'member_id', 'ip_address', 'running_time' ),
							'topic_ratings'			=> array( 'rating_member_id', 'rating_ip_address', '' ),
							'validating'			=> array( 'member_id', 'ip_address', 'entry_date' ),
							'voters'				=> array( 'member_id', 'ip_address', 'vote_date' ),
							'error_logs'			=> array( 'log_member', 'log_ip_address', 'log_date' ),
							);

		//-----------------------------------------
		// Check apps
		// @see http://forums.invisionpower.com/tracker/issue-16966-members-download-manag/
		//-----------------------------------------
		
		foreach( ipsRegistry::$applications as $appDir => $data )
		{
			if( is_file( IPSLib::getAppDir( $appDir ) . "/extensions/coreExtensions.php") )
			{
				$classX = IPSLib::loadLibrary( IPSLib::getAppDir( $appDir ) . "/extensions/coreExtensions.php", $appDir . '_findIpAddress', $appDir );
				
				if( class_exists( $classX . '_findIpAddress' ) )
				{
					$ipLookup	= new $classX( ipsRegistry::instance() );
					
					if( method_exists( $ipLookup, 'getTables' ) )
					{
						$tables = array_merge( $tables, $ipLookup->getTables() );
					}
				}
			}
		}

		//-----------------------------------------
		// Sort out thingie
		//-----------------------------------------

		if ( is_array( $id ) )
		{
			$id = IPSLib::cleanIntArray( $id );

			$mids = ' IN (' . implode( ",", $id ) . ')';
		}
		else
		{
			$mids = ' = ' . intval($id);
		}

		//-----------------------------------------
		// Got tables?
		//-----------------------------------------

		$_tables = explode( ',', $tables_to_check );

		if( !is_array($_tables) OR !count($_tables) )
		{
			return array();
		}

		//-----------------------------------------
		// Loop through them and grab the IPs
		//-----------------------------------------

		foreach( $tables as $tablename => $fields )
		{
			if( $tables_to_check == 'all' OR in_array( $tablename, $_tables ) )
			{
				$extra = '';

				if( $fields[2] )
				{
					$extra = ', ' . $fields[2] . ' as date';
				}

				ipsRegistry::DB()->build( array( 'select' => $fields[1] . $extra, 'from' => $tablename, 'where' => $fields[0] . $mids ) );
				ipsRegistry::DB()->execute();

				while( $r = ipsRegistry::DB()->fetch() )
				{
					if( $r[ $fields[1] ] )
					{
						$r['date']	= $r['date'] > $ip_addresses[ $r[ $fields[1] ] ][1] ? $r['date'] : ( $ip_addresses[ $r[ $fields[1] ] ][1] ? $ip_addresses[ $r[ $fields[1] ] ][1] : 0 );

						$ip_addresses[ $r[ $fields[1] ] ]	= array( intval($ip_addresses[ $r[ $fields[1] ] ][0]) + 1, $r['date'] );
					}
				}
			}
		}

		//-----------------------------------------
		// Here are your IPs kind sir.  kthxbai
		//-----------------------------------------

		return $ip_addresses;
	}

	/**
	 * Get / set member's ban info
	 *
	 * @param	array	Ban info (unit, timespan, date_end, date_start)
	 * @return	mixed
	 * @since	2.0
	 */
	static public function processBanEntry( $bline )
	{
		if ( is_array( $bline ) )
		{
			// Some systems can only handle < 2038 @link http://community.invisionpower.com/tracker/issue-36840-suspending-user/
			$endOf2037 = 2145830340;
			
			$factor = $bline['unit'] == 'd' ? 86400 : 3600;

			$date_end = time() + ( $bline['timespan'] * $factor );

			if ( $date_end < 0 OR $date_end > $endOf2037 )
			{
				$date_end = $endOf2037;
			}
			
			return time() . ':' . $date_end . ':' . $bline['timespan'] . ':' . $bline['unit'];
		}
		else
		{
			$arr = array();

			list( $arr['date_start'], $arr['date_end'], $arr['timespan'], $arr['unit'] ) = explode( ":", $bline );

			return $arr;
		}
	}

	/**
	 * Unpacks a member's cache.
	 * Left as a function for any other processing
	 *
	 * @deprecated Will be removed in 3.3
	 * @param	string	Serialized cache array
	 * @return	array	Unpacked array
	 */
	static public function unpackMemberCache( $cache_serialized_array="" )
	{
		return unserialize( $cache_serialized_array );
	}
	
	/**
	 * Fetch an item from a member's cache
	 * @param MIXED (int or memberData ) $member
	 * @param MIXED (string or array of keys) $key
	 */
	static public function getFromMemberCache( $member, $key )
	{
		$return = array();
		
		if ( is_integer( $member ) )
		{
			$member = self::load( $member, 'core' );
		}
		
		if ( IPSLib::isSerialized( $member['members_cache'] ) )
		{
			$cache = unserialize( $member['members_cache'] );
			
			$keys = ( is_array( $key ) ) ? $key : array( $key );
			
			foreach( $keys as $k )
			{
				$return[ $k ] = isset( $cache[ $k ] ) ? $cache[ $k ] : null;
			}
			
			return ( is_array( $key ) ) ? $return : $return[ $key ];
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Sets items to the cache
	 * @param MIXED (int or memberData ) $member
	 * @param array $store
	 */
	static public function setToMemberCache( $member, $store )
	{
		$cache = array();
		
		if ( is_integer( $member ) )
		{
			$member = self::load( $member, 'core' );
		}
		elseif ( $member['member_id'] and !isset( $member['members_cache'] ) )
		{
			$member = self::load( $member['member_id'], 'core' );
		}
		
		/* Check */
		if ( empty( $member['member_id'] ) or ! is_array( $store ) )
		{
			return false;
		}
		
		/* fetch current cache */
		if ( IPSLib::isSerialized( $member['members_cache'] ) )
		{
			$cache = unserialize( $member['members_cache'] );
		}
		
		/* loop and update */
		foreach( $store as $k => $v )
		{	
			$cache[ $k ] = $v;
		}
		
		/* Save */
		ipsRegistry::DB()->update( 'members', array( 'members_cache' => serialize( $cache ) ), 'member_id='.$member['member_id'] );

		/* Update local cache */
		if ( self::$data['member_id'] == $member['member_id'] )
		{
			self::$data['_cache']		 = $cache;
			self::$data['members_cache'] = serialize( $cache );
		}
	}

	
	/**
	 * Packs up member's cache
	 *
	 * Takes an existing array and updates member's DB row
	 * This will overwrite any existing entries by the same
	 * key and create new entries for non-existing rows
	 *
	 * @deprecated Will be removed in 3.3
	 * @param	integer		Member ID
	 * @param	array		New array
	 * @param	array		Current Array (optional)
	 * @return	boolean
	 */
	static public function packMemberCache( $member_id, $new_cache_array, $current_cache_array='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$member_id = intval( $member_id );

		//-----------------------------------------
		// Got a member ID?
		//-----------------------------------------

		if ( ! $member_id )
		{
			return FALSE;
		}

		//-----------------------------------------
		// Got anything to update?
		//-----------------------------------------

		if ( ! is_array( $new_cache_array ) )
		{
			return FALSE;
		}

		//-----------------------------------------
		// Got a current cache?
		//-----------------------------------------

		if ( ! is_array( $current_cache_array ) )
		{
			$member = ipsRegistry::DB()->buildAndFetch( array( 'select' => "members_cache", 'from' => 'members', 'where' => 'member_id='.$member_id ) );

			$member['members_cache'] = $member['members_cache'] ? $member['members_cache'] : array();

			$current_cache_array = @unserialize( $member['members_cache'] );
		}

		//-----------------------------------------
		// Overwrite...
		//-----------------------------------------

		foreach( $new_cache_array as $k => $v )
		{
			$current_cache_array[ $k ] = $v;
		}

		//-----------------------------------------
		// Update...
		//-----------------------------------------

		ipsRegistry::DB()->update( 'members', array( 'members_cache' => serialize( $current_cache_array ) ), 'member_id='.$member_id );

		//-----------------------------------------
		// Set member array right...
		//-----------------------------------------
		
		if ( self::$data['member_id'] == $member_id )
		{
			self::$data['_cache']			= $current_cache_array;
			self::$data['members_cache']	= serialize( $current_cache_array );
		}
	}

	/**
	 * Check forum permissions
	 *
	 * @param	string		Permission type
	 * @param	int			Forum ID to check against
	 * @return	boolean
	 * @since	2.0
	 */
	static public function checkPermissions( $perm="", $forumID=0 )
	{
		/* Bit of a hack here, ugly */
		if ( ipsRegistry::isClassLoaded('class_forums') !== TRUE )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php", 'class_forums' );
			ipsRegistry::setClass( 'class_forums', new $classToLoad( ipsRegistry::instance() ) );

			ipsRegistry::getClass('class_forums')->strip_invisible = 1;
			ipsRegistry::getClass('class_forums')->forumsInit();
		}

		return ipsRegistry::getClass( 'permissions' )->check( $perm, ipsRegistry::getClass('class_forums')->forum_by_id[ $forumID ] );
	}

	/**
	 * Set up defaults for a guest user
	 *
	 * @param	string	Guest name
	 * @return	array 	Guest record
	 * @since	2.0
	 */
    static public function setUpGuest( $name="" )
    {
		$cache = ipsRegistry::cache()->getCache('group_cache');
		$name  = $name ? $name : ( ipsRegistry::isClassLoaded('class_localization') ? ipsRegistry::getClass('class_localization')->words['global_guestname'] : 'Guest' );

    	$array = array(   'name'          		 	=> $name,
    				   	  'members_display_name' 	=> $name,
	    				  '_members_display_name' 	=> $name,
						  'members_seo_name'		=> IPSText::makeSeoTitle( $name ),
	    				  'member_id'      		 	=> 0,
	    				  'password'      		 	=> '',
	    				  'email'         		 	=> '',
	    				  'title'         		 	=> '',
	    				  'posts'					=> 0,
	    				  'member_group_id'		 	=> ipsRegistry::$settings['guest_group'],
	    				  'view_sigs'     		 	=> ipsRegistry::$settings['guests_sig'],
	    				  'member_forum_markers' 	=> array(),
	    				  'member_posts'		 	=> '',
	    				  'g_dohtml'				=> 0,
	    				  'g_title'		 			=> $cache[ ipsRegistry::$settings['guest_group'] ]['g_title'],
	    				  'member_rank_img'	 	 	=> '',
	    				  'member_joined'		 	=> '',
	    				  'member_number'		 	=> '',
	    				  'members_auto_dst'	 	=> 0,
	    				  'has_blog'			 	=> 0,
	    				  'has_gallery'			 	=> 0,
	    				  'is_mod'				 	=> 0,
	    				  'last_visit'			 	=> time(),
	    				  'login_anonymous'		 	=> '',
	    				  'mgroup_others'		 	=> '',
	    				  'org_perm_id'			 	=> '',
	    				  'auto_track'			 	=> 0,
	    				  'ignored_users'		 	=> NULL,
						  '_cache'                	=> array( 'friends' => array() ),
						  '_group_formatted'		=> self::makeNameFormatted( $cache[ ipsRegistry::$settings['guest_group'] ]['g_title'], ipsRegistry::$settings['guest_group'] ),
	    				);
	    
	    /* Add in the group image, if we have one */
		$member['member_rank_img']		= '';
		$member['member_rank_img_i']	= '';

		if ( $cache[ $array['member_group_id'] ]['g_icon'] )
		{
			$_img = $cache[ $array['member_group_id'] ]['g_icon'];
			
			if ( substr( $_img, 0, 4 ) != 'http' AND strpos( $_img, '{style_images_url}' ) === false )
			{
				$_img = ipsRegistry::$settings['_original_base_url'] . '/' . ltrim( $_img, '/' );
			}
			
			$array['member_rank_img_i']	= 'img';
			$array['member_rank_img']	= $_img;
		}
		
		return is_array( $cache[ ipsRegistry::$settings['guest_group'] ] ) ? array_merge( $array, $cache[ ipsRegistry::$settings['guest_group'] ] ) : $array;
    }

	/**
	 * Parse a member's profile photo
	 *
	 * @param	mixed	Either array of member data, or member ID to self load
	 * @param	string	Size to return tag (thumb/full/mini/small) if no size, just returns array of parsed data
	 * @param	bool	Add random string so no cache is used
	 * @return	array 	Member's photo details
	 */
    static public function buildProfilePhoto( $member, $size=null, $noCache=false )
    {
		//-----------------------------------------
		// Load the member?
		//-----------------------------------------

		if ( ! is_array( $member ) AND ( $member == intval( $member ) ) AND $member > 0 )
		{
			$member = self::load( $member, 'extendedProfile' );
		}
		else if ( $member == 0 )
		{
			$member = array();
		}
		
		/* No photo? Per this bug report, we're going to force showing profile photos, rather than hide them if you can't view profiles
			@link http://community.invisionpower.com/tracker/issue-30986-board-index-unregistered-guests-can-not-view-avatars-option-removed-from-acp */
		if ( empty( $member['pp_main_photo'] ) or $member['pp_photo_type'] == 'gravatar' /*OR ! ipsRegistry::member()->getProperty('g_mem_info')*/ )
		{
			return self::buildNoPhoto( $member, $size, $noCache );
		}
		
		/* Add RND bit to prevent CDN caching */
		$member['pp_thumb_photo'] = ( strstr( $member['pp_thumb_photo'], '?' ) ) ? $member['pp_thumb_photo'] : $member['pp_thumb_photo'] . '?_r=' . intval( $member['pp_profile_update'] );
		$member['pp_main_photo']  = ( strstr( $member['pp_main_photo'] , '?' ) ) ? $member['pp_main_photo']  : $member['pp_main_photo']  . '?_r=' . intval( $member['pp_profile_update'] );
		
		/* @link http://community.invisionpower.com/tracker/issue-32991-profile-photo-conversion */
		$member['pp_main_photo']	= str_replace( 'upload:', '', $member['pp_main_photo'] );
		$member['pp_thumb_photo']	= str_replace( 'upload:', '', $member['pp_thumb_photo'] );
		
		/* Main photo */
		$member['pp_main_photo'] = ( ! ( strstr( $member['pp_main_photo'], 'http://' ) || strstr( $member['pp_main_photo'], 'https://' ) ) ) ? ipsRegistry::$settings['upload_url'] . '/' . $member['pp_main_photo'] : $member['pp_main_photo'];
		$member['_has_photo']    = 1;
		
		/* Thumb */
		$member['pp_thumb_photo']  = ( ! ( strstr( $member['pp_thumb_photo'], 'http://' ) || strstr( $member['pp_thumb_photo'], 'https://' ) ) ) ? ipsRegistry::$settings['upload_url'] . '/' . $member['pp_thumb_photo'] : $member['pp_thumb_photo'];
		
		/* Small */
		$_data = IPSLib::scaleImage( array( 'max_height' => 50, 'max_width' => 50, 'cur_width' => $member['pp_thumb_width'], 'cur_height' => $member['pp_thumb_height'] ) );

		$member['pp_small_photo']  = $member['pp_thumb_photo'];
		$member['pp_small_width']  = $_data['img_width'];
		$member['pp_small_height'] = $_data['img_height'];
		
		/* Mini */
		$_data = IPSLib::scaleImage( array( 'max_height' => 25, 'max_width' => 25, 'cur_width' => $member['pp_thumb_width'], 'cur_height' => $member['pp_thumb_height'] ) );

		$member['pp_mini_photo']  = $member['pp_thumb_photo'];
		$member['pp_mini_width']  = $_data['img_width'];
		$member['pp_mini_height'] = $_data['img_height'];
		
		if ( $size === null )
		{
			return $member;
		}
		else
		{
			return self::buildPhotoTag( $member, $size, $noCache );
		}
    }
    	
	/**
	 * Returns a 'no photo' tag or data
	 *
	 * @param	array	Member Data
	 * @param	string	Optional: Full / thumb / mini / small - will return complete tag
	 * @param	bool	Add random string so no cache is used
	 * @param	bool	If true, ipsUserPhoto class will not be added
	 * @return	string
	 */
	static public function buildNoPhoto( $member, $size=null, $noCache=false, $noBorder=false )
	{
		/* Load member */
		if ( ! is_array( $member ) AND ( $member == intval( $member ) ) AND $member > 0 )
		{
			$member = self::load( $member, 'extendedProfile' );
		}
		else if ( $member == 0 )
		{
			$member = array();
		}
		
		//-----------------------------------------
		// Gravatar
		//-----------------------------------------
		
		if ( ipsRegistry::$settings['allow_gravatars'] and ! $member['bw_disable_gravatar'] )
		{
			$gravatarUrl = ( ipsRegistry::getClass('output')->isHTTPS ) ? 'https://secure.gravatar.com/' : 'http://www.gravatar.com/';
			$default = urlencode( ipsRegistry::$settings['img_url'] . '/profile/default_large.png' );
			$avHash = md5( strtolower( trim( $member['pp_gravatar'] ? $member['pp_gravatar'] : $member['email'] ) ) );
		
			/* Main photo */
			$member['pp_main_photo']  = "{$gravatarUrl}avatar/{$avHash}?s=125&amp;d={$default}";
			$member['pp_main_width']  = 125;
			$member['pp_main_height'] = 125;
			$member['_has_photo']     = 0;
			
	
			/* Thumb */
			$member['pp_thumb_photo']  = "{$gravatarUrl}avatar/{$avHash}?s=100&amp;d={$default}";
			$member['pp_thumb_width']  = 100;
			$member['pp_thumb_height'] = 100;
		}
		
		//-----------------------------------------
		// Normal
		//-----------------------------------------
		
		else
		{
			/* Main photo */
			$member['pp_main_photo']  = ipsRegistry::$settings['img_url'] . '/profile/default_large.png';
			$member['pp_main_width']  = 125;
			$member['pp_main_height'] = 125;
			$member['_has_photo']     = 0;
			
	
			/* Thumb */
			$member['pp_thumb_photo']  = ipsRegistry::$settings['img_url'] . '/profile/default_large.png';
			$member['pp_thumb_width']  = 100;
			$member['pp_thumb_height'] = 100;
		}
		
		/* Small */
		$_data = IPSLib::scaleImage( array( 'max_height' => 50, 'max_width' => 50, 'cur_width' => $member['pp_thumb_width'], 'cur_height' => $member['pp_thumb_height'] ) );

		$member['pp_small_photo']  = $member['pp_thumb_photo'];
		$member['pp_small_width']  = $_data['img_width'];
		$member['pp_small_height'] = $_data['img_height'];
		
		/* Mini */
		$_data = IPSLib::scaleImage( array( 'max_height' => 25, 'max_width' => 25, 'cur_width' => $member['pp_thumb_width'], 'cur_height' => $member['pp_thumb_height'] ) );

		$member['pp_mini_photo']  = $member['pp_thumb_photo'];
		$member['pp_mini_width']  = $_data['img_width'];
		$member['pp_mini_height'] = $_data['img_height'];

		
		if ( $size === null )
		{
			return $member;
		}
		else
		{
			return self::buildPhotoTag( $member, $size, $noCache, $noBorder );
		}
	}
	
	/**
	 * Tagify a member's profile photo from data
	 *
	 * @param	array	Processed member data that has photo info
	 * @param	string	Size (full/thumb/mini/small)
	 * @param	bool	Add random string so no cache is used
	 * @param	bool	If true, ipsUserPhoto class will not be added
	 * @return	string
	 */
	static public function buildPhotoTag( $member, $size='thumb', $noCache=false, $noBorder=false )
	{
		$rnd = ( $noCache === true ) ? "?_r=" . md5( uniqid() ) : '';
		$cls = '';
		
		switch( $size )
		{
			default:
			case 'thumb':
				$src = $member['pp_thumb_photo'];
				$w   = $member['pp_thumb_width'];
				$h   = $member['pp_thumb_height'];
			break;
			case 'main':
			case 'full':
				$src = $member['pp_main_photo'];
				$w   = $member['pp_main_width'];
				$h   = $member['pp_main_height'];
			break;
			case 'small':
				$_data = IPSLib::scaleImage( array( 'max_height' => 50, 'max_width' => 50, 'cur_width' => $member['pp_thumb_width'], 'cur_height' => $member['pp_thumb_height'] ) );
				$src   = $member['pp_thumb_photo'];
				$w     = $_data['img_width'];
				$h     = $_data['img_height'];
				$cls   = 'ipsUserPhoto_medium';
			break;
			case 'mini':
				$_data = IPSLib::scaleImage( array( 'max_height' => 25, 'max_width' => 25, 'cur_width' => $member['pp_thumb_width'], 'cur_height' => $member['pp_thumb_height'] ) );
				$src   = $member['pp_thumb_photo'];
				$w     = $_data['img_width'];
				$h     = $_data['img_height'];
				$cls   = 'ipsUserPhoto_mini';
			break;
			case 'inset':
				$_data = IPSLib::scaleImage( array( 'max_height' => 25, 'max_width' => 25, 'cur_width' => $member['pp_thumb_width'], 'cur_height' => $member['pp_thumb_height'] ) );
				$src   = $member['pp_thumb_photo'];
				$w     = $_data['img_width'];
				$h     = $_data['img_height'];
				$cls   = 'ipsUserPhoto_inset';
			break;
			case 'icon':
				$_data = IPSLib::scaleImage( array( 'max_height' => 16, 'max_width' => 16, 'cur_width' => $member['pp_thumb_width'], 'cur_height' => $member['pp_thumb_height'] ) );
				$src   = $member['pp_thumb_photo'];
				$w     = $_data['img_width'];
				$h     = $_data['img_height'];
				$cls   = 'ipsUserPhoto_icon';
			break;
		}
		
		$classes = $noBorder ? '' : "ipsUserPhoto {$cls}";
		
		return "<img src='" . $src . $rnd . "' width='" . $w . "' height='" . $h . "' class='" . $classes . "' />";
	}
	
	/**
	 * Parse a member for display
	 *
	 * @param	mixed	Either array of member data, or member ID to self load
	 * @param	array 	Array of flags to parse: 'signature', 'customFields', 'warn'
	 * @return	array 	Parsed member data
	 */
	static public function buildDisplayData( $member, $_parseFlags=array() )
	{
		$_NOW   = IPSDebug::getMemoryDebugFlag();
		
		/* test to see if member_title has been passed */
		if ( isset( $member['member_title'] ) )
		{
			$member['title'] = $member['member_title'];
		}
		
		//-----------------------------------------
		// Figure out parse flags
		//-----------------------------------------

		$parseFlags = array( 'signature'		=> isset( $_parseFlags['signature'] )    ? $_parseFlags['signature']    : 0,
							 'customFields'		=> isset( $_parseFlags['customFields'] ) ? $_parseFlags['customFields'] : 0,
							 'reputation'		=> isset( $_parseFlags['reputation'] )   ? $_parseFlags['reputation']   : 1,
							 'warn'				=> isset( $_parseFlags['warn'] )         ? $_parseFlags['warn']         : 1,
							 'cfSkinGroup'		=> isset( $_parseFlags['cfSkinGroup'] )  ? $_parseFlags['cfSkinGroup']  : '',
							 'cfGetGroupData'	=> isset( $_parseFlags['cfGetGroupData'] )  ? $_parseFlags['cfGetGroupData']  : '',
							 'cfLocation'		=> isset( $_parseFlags['cfLocation'] )  ? $_parseFlags['cfLocation']  : '',
							 'checkFormat'		=> isset( $_parseFlags['checkFormat'] )  ? $_parseFlags['checkFormat']  : 0,
							 'photoTagSize'		=> isset( $_parseFlags['photoTagSize'] ) ? $_parseFlags['photoTagSize'] : false,
							 'spamStatus'		=> isset( $_parseFlags['spamStatus'] )   ? $_parseFlags['spamStatus']  : 0 );

		if ( isset( $_parseFlags['__all__'] ) )
		{
			foreach( $parseFlags as $k => $v )
			{
				if( in_array( $k, array( 'cfSkinGroup', 'cfGetGroupData', 'photoTagSize' ) ) )
				{
					continue;
				}
				
				$parseFlags[ $k ] = 1;
			}

			$parseFlags['spamStatus']  = !empty( $parseFlags['spamStatus'] ) ? 1 : 0;
		}

		//-----------------------------------------
		// Load the member?
		//-----------------------------------------

		if ( ! is_array( $member ) AND ( $member == intval( $member ) AND $member > 0 ) )
		{
			$member = self::load( $member, 'all' );
		}
		
		//-----------------------------------------
		// Caching
		//-----------------------------------------
		
		static $buildMembers	= array();
		
		$_key	= $member['member_id'];
		$_arr   = serialize( $member );
		
		foreach( $parseFlags as $_flag => $_value )
		{
			$_key .= $_flag . $_value;
		}
		
		$_key	= md5($_key.$_arr);
		
		if( isset( $buildMembers[ $_key ] ) )
		{
			IPSDebug::setMemoryDebugFlag( "IPSMember::buildDisplayData: ".$member['member_id']. " - CACHED", $_NOW );
			
			return $buildMembers[ $_key ];
		}

		//-----------------------------------------
		// Basics
		//-----------------------------------------
		
		if ( ! $member['member_group_id'] )
		{
			$member['member_group_id'] = ipsRegistry::$settings['guest_group'];
		}
		
		/* Unpack bitwise if required */
		if ( ! isset( $member['bw_is_spammer'] ) )
		{
			$member = self::buildBitWiseOptions( $member );
		}

		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$rank_cache                = ipsRegistry::cache()->getCache( 'ranks' );
		$group_cache			   = ipsRegistry::cache()->getCache( 'group_cache' );
		$group_name                = self::makeNameFormatted( $group_cache[ $member['member_group_id'] ]['g_title'], $member['member_group_id'] );
		$pips                      = 0;
		$topic_id				   = intval( isset( ipsRegistry::$request[ 't' ] ) ? ipsRegistry::$request[ 't' ] : 0 );
		$forum_id				   = intval( isset( ipsRegistry::$request[ 'f' ] ) ? ipsRegistry::$request[ 'f' ] : 0 );
		
		//-----------------------------------------
		// SEO Name
		//-----------------------------------------
	
		$member['members_seo_name'] = self::fetchSeoName( $member );
		$member['_group_formatted'] = $group_name;

		//-----------------------------------------
		// Ranks
		//-----------------------------------------

		if ( is_array( $rank_cache ) AND count( $rank_cache ) )
		{
			foreach( $rank_cache as $k => $v)
			{
				if ( $member['posts'] >= $v['POSTS'] )
				{
					if ( empty( $member['title'] ) )
					{
						$member['title'] = $v['TITLE'];
					}

					$pips = $v['PIPS'];
					break;
				}
			}
		}

		//-----------------------------------------
		// Group image
		//-----------------------------------------

		$member['member_rank_img']		= '';
		$member['member_rank_img_i']	= '';

		if ( $group_cache[ $member['member_group_id'] ]['g_icon'] )
		{
			$_img = $group_cache[ $member['member_group_id'] ]['g_icon'];
			
			if ( substr( $_img, 0, 4 ) != 'http' AND strpos( $_img, '{style_images_url}' ) === false )
			{
				$_img = ipsRegistry::$settings['_original_base_url'] . '/' . ltrim( $_img, '/' );
			}
			
			$member['member_rank_img_i']	= 'img';
			$member['member_rank_img']		= $_img;
		}
		else if ( $pips AND $member['member_id'] )	/* Added member ID check per @link	http://community.invisionpower.com/tracker/issue-31761-guest-in-messanger-have-pips/ */
		{
			if ( is_numeric( $pips ) )
			{
				for ($i = 1; $i <= $pips; ++$i)
				{
					$member['member_rank_img_i']	= 'pips';
					$member['member_rank_img']		= $member['member_rank_img'] . ipsRegistry::getClass('output')->getReplacement('pip_pip');
				}
			}
			else
			{
				$member['member_rank_img_i']	= 'img';
				$member['member_rank_img']		= ipsRegistry::$settings['public_dir'] . 'style_extra/team_icons/' . $pips;
			}
		}

		//-----------------------------------------
		// Moderator data
		//-----------------------------------------
		
		if( ( $parseFlags['spamStatus'] OR $parseFlags['warn'] ) AND $member['member_id'] )
		{
			/* Possible forums class isn't init at this point */
			if ( ! ipsRegistry::isClassLoaded('class_forums' ) )
			{
				try
				{
					$viewingMember = IPSMember::setUpModerator( ipsRegistry::member()->fetchMemberData() );
					
					ipsRegistry::member()->setProperty('forumsModeratorData', $viewingMember['forumsModeratorData'] );
				}
				catch( Exception $error )
				{
					IPS_exception_error( $error );
				}
			}
			
			$moderator					= ipsRegistry::member()->getProperty('forumsModeratorData');
		}
		
		$forum_id					= isset(ipsRegistry::$request['f']) ? intval( ipsRegistry::$request['f'] ) : 0;

		//-----------------------------------------
		// Spammer status
		//-----------------------------------------

		if ( $parseFlags['spamStatus'] AND $member['member_id'] AND ipsRegistry::member()->getProperty('member_id') )
		{
			/* Defaults */
			$member['spamStatus']		= NULL;
			$member['spamImage']		= NULL;
			
			if ( !empty( $moderator[ $forum_id ]['bw_flag_spammers'] ) OR ipsRegistry::member()->getProperty('g_is_supmod') )
			{
				if ( ! ipsRegistry::$settings['warn_on'] OR ! IPSMember::isInGroup( $member, explode( ',', ipsRegistry::$settings['warn_protected'] ) ) )
				{
					if ( $member['bw_is_spammer'] )
					{
						$member['spamStatus'] = TRUE;
					}
					else
					{
						$member['spamStatus'] = FALSE;
					}
				}
			}
		}
				
		//-----------------------------------------
		// Warny porny?
		//-----------------------------------------

		$member['show_warn'] = FALSE;
		if ( $parseFlags['warn'] AND $member['member_id'] )
		{
			if ( ipsRegistry::$settings['warn_on'] and ! IPSMember::isInGroup( $member, explode( ',', ipsRegistry::$settings['warn_protected'] ) ) )
			{
				/* Warnings */
				if ( !empty($moderator[ $forum_id ]['allow_warn']) OR ipsRegistry::member()->getProperty('g_is_supmod') OR ( ipsRegistry::$settings['warn_show_own'] and ipsRegistry::member()->getProperty('member_id') == $member['member_id'] ) )
				{
					$member['show_warn'] = TRUE;
				}
			}
		}
		
		//-----------------------------------------
		// Profile fields stuff
		//-----------------------------------------

		$member['custom_fields'] = "";

		if( $parseFlags['customFields'] == 1 AND $member['member_id'] )
		{
			if ( isset( self::$_parsedCustomFields[ $member['member_id'] ] ) )
			{
				$member['custom_fields'] = self::$_parsedCustomFields[ $member['member_id'] ];
				
				if ( $parseFlags['cfGetGroupData'] AND isset( self::$_parsedCustomGroups[ $member['member_id'] ] ) AND is_array( self::$_parsedCustomGroups[ $member['member_id'] ] ) )
				{
					$member['custom_field_groups'] = self::$_parsedCustomGroups[ $member['member_id'] ];
				}
				else if( $parseFlags['cfGetGroupData'] )
				{
					$member['custom_field_groups']						= self::$custom_fields_class->fetchGroupTitles();
					self::$_parsedCustomGroups[ $member['member_id'] ]	= $member['custom_field_groups'];
				}
			}
			else
			{
				if ( !is_object( self::$custom_fields_class ) )
				{
					$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/customfields/profileFields.php', 'customProfileFields' );
					self::$custom_fields_class	= new $classToLoad();
				}
	
				if ( self::$custom_fields_class )
				{
					self::$custom_fields_class->member_data	= $member;
					self::$custom_fields_class->skinGroup	= $parseFlags['cfSkinGroup'];
					self::$custom_fields_class->initData();
					self::$custom_fields_class->parseToView( $parseFlags['checkFormat'], $parseFlags['cfLocation'] );

					$member['custom_fields']							= self::$custom_fields_class->out_fields;
					self::$_parsedCustomFields[ $member['member_id'] ]	= $member['custom_fields'];
					
					if ( $parseFlags['cfGetGroupData'] )
					{
						$member['custom_field_groups']						= self::$custom_fields_class->fetchGroupTitles();
						self::$_parsedCustomGroups[ $member['member_id'] ]	= $member['custom_field_groups'];
					}
				}
			}
		}

		//-----------------------------------------
		// Profile photo
		//-----------------------------------------

		$member = self::buildProfilePhoto( $member );

		if ( ! empty( $parseFlags['photoTagSize'] ) )
		{
			$parseFlags['photoTagSize'] = ( is_array( $parseFlags['photoTagSize'] ) ) ? $parseFlags['photoTagSize'] : array( $parseFlags['photoTagSize'] );
			
			foreach( $parseFlags['photoTagSize'] as $size )
			{
				$member['photoTag' . ucfirst( $size ) ] = self::buildPhotoTag( $member, $size );
			}
		}
			
		//-----------------------------------------
		// Signature bbcode
		//-----------------------------------------

		if ( !empty( $member['signature'] ) AND $parseFlags['signature'] )
		{
			if( isset(self::$_parsedSignatures[ $member['member_id'] ]) )
			{
				$member['signature'] = self::$_parsedSignatures[ $member['member_id'] ];
			}
			else
			{
				if ( $member['cache_content'] )
				{
					$member['signature'] = '<!--signature-cached-' . gmdate( 'r', $member['cache_updated'] ) . '-->' . $member['cache_content'];
				}
				else
				{
					IPSText::getTextClass('bbcode')->parse_bbcode			= 1;
					IPSText::getTextClass('bbcode')->parse_smilies			= 0;
					IPSText::getTextClass('bbcode')->parse_html				= $group_cache[ $member['member_group_id'] ]['g_dohtml'];
					IPSText::getTextClass('bbcode')->parse_nl2br			= 1;
					IPSText::getTextClass('bbcode')->parsing_section		= 'signatures';
					IPSText::getTextClass('bbcode')->parsing_mgroup			= $member['member_group_id'];
					IPSText::getTextClass('bbcode')->parsing_mgroup_others	= $member['mgroup_others'];

					$member['signature']	= IPSText::getTextClass('bbcode')->preDisplayParse( $member['signature'] );

					IPSContentCache::update( $member['member_id'], 'sig', $member['signature'] );
				}

				self::$_parsedSignatures[ $member['member_id'] ] = $member['signature'];
			}
		}

		//-----------------------------------------
		// If current session, reset last_activity
		//-----------------------------------------
		
		if( ! empty( $member['running_time'] ) )
		{
			$member['last_activity'] = $member['running_time'] > $member['last_activity'] ? $member['running_time'] : $member['last_activity'];
		}

		//-----------------------------------------
		// Online?
		//-----------------------------------------

		$time_limit			= time() - ( ipsRegistry::$settings['au_cutoff'] * 60 );
		$member['_online']	= 0;
		$bypass_anon		= ipsRegistry::member()->getProperty('g_access_cp') ? 1 : 0;
		
		list( $be_anon, $loggedin )	= explode( '&', empty($member['login_anonymous']) ? '0&0' : $member['login_anonymous'] );
		
		/* Is not anon but the group might be forced to? */
		if ( empty($be_anon) && self::isLoggedInAnon($member) )
		{
			$be_anon = 1;
		}
		
		/* Finally set the online flag */
		if ( ( $member['last_visit'] > $time_limit OR $member['last_activity'] > $time_limit ) AND ( $be_anon != 1 OR $bypass_anon == 1 ) AND $loggedin == 1 )
		{
			$member['_online'] = 1;
		}

		//-----------------------------------------
		// Last Active
		//-----------------------------------------

		$member['_last_active'] = ipsRegistry::getClass('class_localization')->getDate( $member['last_activity'], 'SHORT' );
		
		// Member last logged in anonymous ?
		if( $be_anon == 1 &&  ! ipsRegistry::member()->getProperty('g_access_cp') )
		{
			$member['_last_active'] = ipsRegistry::getClass('class_localization')->words['private'];
		}

		//-----------------------------------------
		// Rating
		//-----------------------------------------

		$member['_pp_rating_real'] = intval( $member['pp_rating_real'] );

		//-----------------------------------------
		// Display name formatted
		//-----------------------------------------

		$member['members_display_name_formatted'] = self::makeNameFormatted( $member['members_display_name'], $member['member_id'] ? $member['member_group_id'] : ipsRegistry::$settings['guest_group'] );

		//-----------------------------------------
		// Long display names
		//-----------------------------------------

		$member['members_display_name_short'] = IPSText::truncate( $member['members_display_name'], 16 );

		//-----------------------------------------
		// Reputation
		//-----------------------------------------

		$member['pp_reputation_points'] = $member['pp_reputation_points'] ? $member['pp_reputation_points'] : 0;
		
		if( $parseFlags['reputation'] AND $member['member_id'] )
		{
			if( ! ipsRegistry::isClassLoaded( 'repCache' ) )
			{
				$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_reputation_cache.php', 'classReputationCache' );
				ipsRegistry::setClass( 'repCache', new $classToLoad() );
			}

			$member['author_reputation']    = ipsRegistry::getClass( 'repCache' )->getReputation( $member['pp_reputation_points'] );
		}

		//-----------------------------------------
		// Other stuff not worthy of individual comments
		//-----------------------------------------

		$member['members_profile_views']	= isset($member['members_profile_views']) ? $member['members_profile_views'] : 0;
		
		/* BG customization */
		if ( $member['pp_customization'] AND !empty($member['gbw_allow_customization']) AND ! $member['bw_disable_customization'] )
		{ 
			$member['customization'] = unserialize( $member['pp_customization'] );
			
			if ( is_array( $member['customization'] ) )
			{
				/* Figure out BG URL */
				if ( $member['customization']['type'] == 'url' AND $member['customization']['bg_url'] AND $member['gbw_allow_url_bgimage'] )
				{
					$member['customization']['_bgUrl'] = $member['customization']['bg_url'];
				}
				else if ( $member['customization']['type'] == 'upload' AND $member['customization']['bg_url'] AND $member['gbw_allow_upload_bgimage'] )
				{
					$member['customization']['_bgUrl'] = ipsRegistry::$settings['upload_url'] . '/' . $member['customization']['bg_url'];
				}
				else if ( $member['customization']['bg_color'] )
				{
					$member['customization']['type'] = 'bgColor';
				}
			}
		}
				
		/* Title is ambigious */
		$member['member_title'] = $member['title'];
			
		IPSDebug::setMemoryDebugFlag( "IPSMember::buildDisplayData: ".$member['member_id']. " - Completed", $_NOW );
		
		$buildMembers[ $_key ]	= $member;
		
		return $member;
	}
	
	/**
	 * Build member's bitwise field
	 *
	 * @param	mixed		Either an array of member data or a member ID
	 * @return	array
	 */
	static public function buildBitWiseOptions( $member )
	{
		//-----------------------------------------
		// Load the member?
		//-----------------------------------------

		if ( ! is_array( $member ) AND ( $member == intval( $member ) ) )
		{
			$member = self::load( $member, 'core,extendedProfile' );
		}
	
		/* Unpack bitwise fields */
		$_tmp = IPSBWOptions::thaw( isset($member['members_bitoptions']) ? $member['members_bitoptions'] : 0, 'members', 'global' );
				
		if ( count( $_tmp ) )
		{
			foreach( $_tmp as $k => $v )
			{
				/* Trigger notice if we have DB field */
				if ( isset( $member[ $k ] ) )
				{
					trigger_error( "Thawing bitwise options for MEMBERS: Bitwise field '$k' has overwritten DB field '$k'", E_USER_WARNING );
				}

				$member[ $k ] = $v;
			}
		}

		return $member;
	}
	
	/**
	 * Returns user's avatar [DEPRICATED]
	 * @todo    Remove in 3.3 this is depricated
	 *
	 * @param	mixed		Either an array of member data or a member ID
	 * @param	bool		Whether to avoid caching
	 * @param	bool		Whether to show avatar even if view_avs is off for member
	 * @return	string		HTML
	 * @since	2.0
	 * 
	 * @deprecated
	 */
    static public function buildAvatar( $member, $no_cache=0, $overRide=0 )
    {
    	return self::buildProfilePhoto( $member, 'thumb' );
    }

	/**
	 * Checks for a DB row that matches $email
	 *
	 * @param	string 		Email address
	 * @return	boolean		Record exists
	 */
	static public function checkByEmail( $email )
	{
		$test = self::load( $email, '', 'email' );

		if ( $test['member_id'] )
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Updates member's DB row password
	 *
	 * @param	string		Key: either member_id or email
	 * @param	string		MD5-once hash of new password
	 * @return	boolean		Update successful
	 */
	static public function updatePassword( $member_key, $new_md5_pass )
	{
		if ( ! $member_key or ! $new_md5_pass )
		{
			return false;
		}

		/* Load member */
		$member = self::load( $member_key );

		$new_pass = md5( md5( $member['members_pass_salt'] ) . $new_md5_pass );

		self::save( $member_key, array( 'core' => array( 'members_pass_hash' => $new_pass ) ) );

		return true;
	}

	/**
	 * Check supplied password with database
	 *
	 * @param	string		Key: either member_id or email
	 * @param	string		MD5 of entered password
	 * @return	boolean		Password is correct
	 */
	static public function authenticateMember( $member_key, $md5_once_password )
	{
		/* Load member */
		$member = self::load( $member_key );

		if ( ! $member['member_id'] )
		{
			return FALSE;
		}

		if ( $member['members_pass_hash'] == self::generateCompiledPasshash( $member['members_pass_salt'], $md5_once_password ) )
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Generates a compiled passhash.
	 * Returns a new MD5 hash of the supplied salt and MD5 hash of the password
	 *
	 * @param	string		User's salt (5 random chars)
	 * @param	string		User's MD5 hash of their password
	 * @return	string		MD5 hash of compiled salted password
	 */
	static public function generateCompiledPasshash( $salt, $md5_once_password )
	{
		return md5( md5( $salt ) . $md5_once_password );
	}

	/**
	 * Generates a password salt.
	 * Returns n length string of any char except backslash
	 *
	 * @param	integer		Length of desired salt, 5 by default
	 * @return	string		n character random string
	 */
	static public function generatePasswordSalt($len=5)
	{
		$salt = '';

		for ( $i = 0; $i < $len; $i++ )
		{
			$num   = mt_rand(33, 126);

			if ( $num == '92' )
			{
				$num = 93;
			}

			$salt .= chr( $num );
		}

		return $salt;
	}

	/**
	 * Generates a log in key
	 *
	 * @param	integer		Length of desired random chars to MD5
	 * @return	string		MD5 hash of random characters
	 */
	static public function generateAutoLoginKey( $len=60 )
	{
		$pass = self::generatePasswordSalt( $len );

		return md5($pass);
	}
	
	/**
	 * Check to see if a member is inactive (member_banned, bw_is_spammer)
	 * @param	mixed		Either INT (member_id) OR Array of member data [MUST at least include member_group_id and mgroup_others]
	 * @return	boolean		TRUE (is inactive) - FALSE (not in inactive)
	 */
	static public function isInactive( $member )
	{
		$memberData = ( is_array( $member ) ) ? $member : self::load( $member, 'core' );
		
		return ( empty( $member['member_banned'] ) && empty( $member['bw_is_spammer'] ) && empty( $member['inactive'] ) ) ? false : true;
	}
	
	/**
	 * Check to see if a member is in a group or not
	 *
	 * @param	mixed		Either INT (member_id) OR Array of member data [MUST at least include member_group_id and mgroup_others]
	 * @param	mixed		Either INT (group ID) or array of group IDs
	 * @param	boolean		TRUE (default, check secondary groups also), FALSE (check primary only)
	 * @return	boolean		TRUE (is in group) - FALSE (not in group)
	 */
	static public function isInGroup( $member, $group, $checkSecondary=true )
	{
		$memberData = ( is_array( $member ) ) ? $member : self::load( $member, 'core' );
		$group      = ( is_array( $group ) )  ? $group  : array( $group );
		$others		= empty($memberData['mgroup_others']) ? array() : explode( ',', $memberData['mgroup_others'] );
		
		if ( ! $memberData['member_group_id'] OR ! count( $group ) )
		{
			return FALSE;
		}
		
		/* Loop */
		foreach( $group as $gid )
		{
			if ( $gid )
			{
				if ( $gid == $memberData['member_group_id'] )
				{
					return true;
				}
				
				if ( $checkSecondary AND count( $others ) AND in_array( $gid, $others ) )
				{
					return true;
				}
			}
		}
		
		return false;
	}
	
	/**
	 * Check to see if a member is banned (or not)
	 *
	 * @param	string		Type of ban check (ip/ipAddress, name, email)
	 * @param	string		String to check
	 * @return	boolean		TRUE (banned) - FALSE (not banned)
	 */
	static public function isBanned( $type, $string )
	{
		/* Try and be helpful */
		switch ( strtolower( $type ) )
		{
			case 'ip':
				$type = 'ipAddress';
			break;
			case 'emailaddress':
				$type = 'email';
			break;
			case 'username':
			case 'displayname':
				$type = 'name';
			break;
		}

		if ( $type == 'ipAddress' )
		{
			$banCache = ipsRegistry::cache()->getCache('banfilters');
		}
		else
		{
			if ( ! is_array( self::$_banFiltersCache ) )
			{
				self::$_banFiltersCache = array();

				/* Load Ban Filters */
				ipsRegistry::DB()->build( array( 'select' => '*', 'from' => 'banfilters' ) );
				ipsRegistry::DB()->execute();

				while( $r = ipsRegistry::DB()->fetch() )
				{
					self::$_banFiltersCache[ $r['ban_type'] ][] = $r['ban_content'];
				}
			}

			$banCache = self::$_banFiltersCache[ $type ];
		}

		if ( is_array( $banCache ) and count( $banCache ) )
		{
			foreach( $banCache as $entry )
			{
				$ip = str_replace( '\*', '.*', preg_quote( trim($entry), "/") );

				if ( $ip AND preg_match( "/^$ip$/", $string ) )
				{
					return TRUE;
				}
			}
		}

		return FALSE;
	}

	/**
	 * Check to see if a member is ignorable or not
	 *
	 * @param	int			Member's primary group ID
	 * @param	string		Comma delisted list of 'other' member groups
	 * @param	string		Type: 'post' or 'pm'
	 * @return	boolean		True (member is ignorable) or False (member can not be ignored)
	 */
	static public function isIgnorable( $member_group_id, $mgroup_others, $type='post' )
	{
		if( ! isset( ipsRegistry::$settings['_unblockableArray'] ) OR ! is_array( ipsRegistry::$settings['_unblockableArray'] ) )
		{
			ipsRegistry::$settings['_unblockableArray'] = ipsRegistry::$settings['cannot_ignore_groups'] ? explode( ",", IPSText::cleanPermString( ipsRegistry::$settings['cannot_ignore_groups'] ) ) : array();
		}

		$myGroups    = array( $member_group_id );

 		if ( $mgroup_others )
 		{
	 		$myGroups = array_merge( $myGroups, explode( ",", IPSText::cleanPermString( $mgroup_others ) ) );
 		}
 		
 		/* Check PMs first */
 		if ( $type == 'pm' )
 		{
 			$unblockable = explode( ",", ipsRegistry::$settings['unblockable_pm_groups'] );
 			
 			/* Override with groups */
			if ( is_array( $unblockable ) AND count( $unblockable ) )
			{
				if ( in_array( $member_group_id, $unblockable ) )
				{
					return FALSE;
				}
			}
 		}
 		
 		foreach( $myGroups as $member_group )
 		{
	 		if ( in_array( $member_group, ipsRegistry::$settings['_unblockableArray'] ) )
	 		{
		 		return FALSE;
	 		}
	 	}

		return TRUE;
	}
	
	/**
	 * Returns whether current member is logged in anonymously.
	 * 
	 * @param	array		$memberData		Member data
	 * @param	integer		$groupId		Override group ID from member data
	 * @return	@e integer	1 for anonymous or 0 otherwise
	 */
	static public function isLoggedInAnon( $memberData, $groupId=0 )
	{
		/**
		 * Falls back to 'not anonymous' if the anoymous login
		 * is disabled or the member field is empty
		 */
		$isAnon = 0;
		
		/* 1: Check if the group is forced as anonymous and override the global setting */
		$groupId	= empty($groupId) ? $memberData['member_group_id'] : $groupId;
		$groupCache	= ipsRegistry::cache()->getCache('group_cache');
		
		if ( ! empty($groupCache[ $groupId ]['g_hide_online_list']) )
		{
			$isAnon = 1;
		}
		/* 2: Anonymous login is enabled and the member field is not empty? Not anonymous then.. maybe.. */
		elseif ( empty(ipsRegistry::$settings['disable_anonymous']) && ! empty($memberData['login_anonymous']) )
		{
			$isAnon = substr( $memberData['login_anonymous'], 0, 1 );
		}
		
		return $isAnon;
	}
	
	/**
	 * Easy peasy way to grab a function from member/memberFunctions.php
	 * without having to bother setting it up each time.
	 *
	 * @return	object		memberFunctions object
	 * @author	MattMecham
	 */
	static public function getFunction()
	{
		if ( ! is_object( self::$_memberFunctions ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/member/memberFunctions.php', 'memberFunctions' );
			self::$_memberFunctions = new $classToLoad( ipsRegistry::instance() );
		}

		return self::$_memberFunctions;
	}

	/**
	 * Set the cache to ignore
	 * Works for one LOAD only! It's reset again for the next load
	 *
	 * @return	@e void
	 */
	static public function ignoreCache()
	{
		self::$ignoreCache = TRUE;
	}
	
	/**
	 * Can upload a photo
	 *
	 * @param	array	$memberData
	 * @param	bool	If true, also checks if we can use linked or gravatar photos
	 * @return	boolean
	 */
	static public function canUploadPhoto( $memberData, $checkAll=FALSE )
	{
		if ( $checkAll and ( ipsRegistry::$settings['mem_photo_url'] or ipsRegistry::$settings['allow_gravatars'] ) )
		{
			return TRUE;
		}
		
		return ( ( $memberData['g_edit_profile'] && $memberData['photoMaxKb'] && $memberData['photoMaxWidth'] && $memberData['photoMaxHeight'] ) || IN_ACP ) ? true : false;
	}
		
	/**
	 * Kludgy function to prevent long strings of HTML logic
	 *
	 * @param	array	Array of data (has_given_rep, etc)
	 * @param	array	Member Data
	 * @return	boolean
	 */
	static public function canRepUp( $data, $memberData=array() )
	{	
		return ( ( ipsRegistry::$settings['reputation_point_types'] == 'like' AND empty( $data['has_given_rep'] ) ) OR in_array( ipsRegistry::$settings['reputation_point_types'], array( 'positive', 'both' ) ) && empty( $data['has_given_rep'] ) and ipsRegistry::member()->getProperty('g_rep_max_positive') ) ? true : false;
	}
	
	/**
	 * Kludgy function to prevent long strings of HTML logic
	 *
	 * @param	array	Array of data (has_given_rep, etc)
	 * @param	array	Member Data
	 * @return	boolean
	 */
	static public function canRepDown( $data, $memberData=array() )
	{
		return ( $data['has_given_rep'] == 1 AND ipsRegistry::$settings['reputation_point_types'] == 'like' ) OR ( empty( $data['has_given_rep'] ) AND in_array( ipsRegistry::$settings['reputation_point_types'], array( 'negative', 'both' ) ) ) and ipsRegistry::member()->getProperty('g_rep_max_negative') ? true : false;
	}
	
	/**
	 * Can give rep to an item
	 *
	 * @param	array	Array of data (has_given_rep, etc)
	 * @param	array	Member data
	 */
	static public function canGiveRep( $data, $memberData )
	{
		return ( $data['has_given_rep'] == 1 AND ipsRegistry::$settings['reputation_point_types'] == 'like' ) OR ( $data['has_given_rep'] != 1 ) AND ( $memberData['member_id'] != 0 ) && ( ipsRegistry::$settings['reputation_can_self_vote'] OR ( $memberData['member_id'] != ipsRegistry::member()->getProperty('member_id') ) ) ? true : false;
	}
	
	/**
	 * Determines if we can share socially or not
	 * @param string $method (If false, it'll check all services and return true of one or more allow it) facebook/twitter
	 * @param array $memberData
	 */
	static public function canSocialShare( $method=false, $memberData=null )
	{
		$memberData = ( $memberData === null ) ? ipsRegistry::member()->fetchMemberData() : $memberData;
		
		if ( $method == false )
		{
			$method = array( 'twitter', 'facebook' );
		}
		else if ( is_string( $method ) )
		{
			$method = array( $method );
		}
		
		$canShare = false;
		
		if ( is_array( $method ) )
		{
			foreach( $method as $s )
			{
				switch( $s )
				{
					case 'twitter':
						$canShare = ( IPSLib::twitter_enabled() AND ( $memberData['twitter_token'] ) ) ? true : false;
					break;
					case 'facebook':
						$canShare = ( IPSLib::fbc_enabled() ) ? true : false;
					break;
				}
				
				if ( $canShare === true )
				{
					return true;
				}
			}
		}
		
		return $canShare;
	}
	
	/**
	 * Send out the social shares
	 * @param array $data 		array( 'title' => 'Eat Pie!', 'url' => 'http://eatpie.com/' )
	 * @param array $services	array of services to share with
	 */
	static public function sendSocialShares( array $data, $services=null, $memberData=null )
	{
		$memberData      = ( $memberData === null ) ? ipsRegistry::member()->fetchMemberData() : $memberData;
		$checkedServices = array();
		
		if ( ! count( $services ) || $services === null )
		{
			/* What are we sharing? */
			foreach( ipsRegistry::$request as $k => $v )
			{
				if ( stristr( $k, 'share_x_' ) and ! empty( $v ) )
				{
					$services[] = str_ireplace( 'share_x_', '', $k );
				}
			}
		}
		
		if( is_array($services) AND count($services) )
		{
			foreach( $services as $service )
			{
				if ( self::canSocialShare( $service, $memberData ) )
				{
					$checkedServices[] = $service;
				}
			}
		}
		
		/* Process them */
		foreach( $checkedServices as $service )
		{
			switch( $service )
			{ 
				case 'twitter':
					$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/twitter/connect.php', 'twitter_connect' );
    				$twitter     = new $classToLoad( ipsRegistry::instance(), $memberData['twitter_token'], $memberData['twitter_secret'] );
					
    				try
    				{
						$twitter->updateStatusWithUrl( $data['title'], $data['url'], TRUE );
					}
					catch( Exception $ex ) { }
				break;
				case 'facebook':
					$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/facebook/connect.php', 'facebook_connect' );
    				$facebook    = new $classToLoad( ipsRegistry::instance() );
    				
    				try
    				{
						$facebook->postLinkToWall( $data['url'], $data['title'] );
    				}
    				catch( Exception $ex ) { }
				break;
			}
		}
	}
	
	/**
	 * Adds a member to the cache
	 *
	 * @param	array 		Member Data
	 * @param	array 		Tables queried
	 * @return	@e void
	 */
	static protected function _addToCache( $memberData, $tables )
	{
		if ( ! $memberData['member_id'] OR ! is_array( $tables ) )
		{
			return FALSE;
		}

		$_tables = self::__buildTableHash( $tables );

		self::$memberCache[ $memberData['member_id'] ][ $_tables ] = $memberData;

		self::$debugData[] = "ADDED: Member ID: " . $memberData['member_id'] . " with tables " . implode( ",", $tables ). ' key ('.$_tables.')';
	}

	/**
	 * Removes a member from the cache
	 *
	 * @param	int 		Member ID
	 * @return	@e void
	 */
	static protected function _removeFromCache( $memberID )
	{
		if ( is_array( self::$memberCache[ $memberID ] ) )
		{
			unset( self::$memberCache[ $memberID ] );

			self::$debugData[] = "REMOVED: Member ID: " . $memberID;
		}
	}

	/**
	 * Removes a member from the cache
	 *
	 * @param	int 		Member ID to look for
	 * @param	array 		Tables required
	 * @return	mixed		Array of data if a match is found, or FALSE if not.
	 */
	static protected function _fetchFromCache( $memberID, $tables )
	{
		if ( self::$ignoreCache === TRUE )
		{
			return FALSE;
		}

		if ( ! $memberID OR ! is_array( $tables ) )
		{
			return FALSE;
		}

		$_tables = self::__buildTableHash( $tables );

		if ( isset( self::$memberCache[ $memberID ][ $_tables ] ) && is_array( self::$memberCache[ $memberID ][ $_tables ] ) )
		{
			self::$debugData[] = "FETCHED: Member ID: " . $memberID. ' key ('.$_tables.')';

			return self::$memberCache[ $memberID ][ $_tables ];
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Updates a member from the cache
	 *
	 * @param	int 		Member ID to update
	 * @param	array 		Array of data to update(eg: array( 'core' => 'member_login_key' => 'xxxxx' ) )
	 * @return	mixed		Array of data if a match is found, or FALSE if not.
	 */
	static protected function _updateCache( $memberID, $data )
	{
		if ( ! $memberID OR ! is_array( $data ) )
		{
			return FALSE;
		}

		if ( is_array( self::$memberCache[ $memberID ] ) )
		{
			foreach(  self::$memberCache[ $memberID ] as $tableData => $memberData )
			{
				foreach( $data as $table => $newData )
				{
					foreach( $newData as $k => $v )
					{
						self::$memberCache[ $memberID ][ $tableData ][ $k ] = $v;
					}
				}
			}

			self::$debugData[] = "Updated: Member ID: " . $memberID;

			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Build table key.
	 * Takes an array of tables and returns an MD5 comparison hash
	 *
	 * @param	array 		Array of tables
	 * @return	string		MD5 hash
	 */
	static protected function __buildTableHash( $tables )
	{
		sort( $tables );
		return md5( implode( ',', $tables ) );
	}

	/**
	 * Sends a query to the IPS Spam Service
	 *
	 * @param	string		$email		Email address to check/report
	 * @param	string		[$ip]		IP Address to check report, ipsRegistry::member()->ip_address will be used if the address is not specified
	 * @param	string		[$type]		Either register or markspam, register is default
	 * @return	string
	 */
	static public function querySpamService( $email, $ip='', $type='register', $test=0 )
	{
		/* Get the response */
		$key		= trim( ipsRegistry::$settings['ipb_reg_number'] );
		
		if( !$key )
		{
			return 0;
		}

		$domain 	= ipsRegistry::$settings['board_url'];
		$ip			= ( $ip AND ip2long( $ip ) ) ? $ip : ipsRegistry::member()->ip_address;
		$response	= false;
		$testConn	= $test ? '&debug_mode=1' : '';
		
		/* Get the file managemnet class */
		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classFileManagement.php', 'classFileManagement' );
		$query = new $classToLoad();
		$query->timeout = ipsRegistry::$settings['spam_service_timeout'];
		
		/* Query the service */
		$response = $query->getFileContents( "https://ips-spam-service.com/new-api/index.php?key={$key}&domain={$domain}&type={$type}&email={$email}&ip={$ip}{$testConn}" );
		
		if( ! $response )
		{
			return 'timeout';
		}
		
		$response		= explode( "\n", $response );
		$responseCode	= $response[0];
		$responseMsg	= $response[1];
		
		if( $test )
		{
			return $responseMsg;
		}

		/* Log Request */
		if( $type == 'register' )
		{
			ipsRegistry::DB()->insert( 'spam_service_log', array(
																	'log_date'		=> time(),
																	'log_code'		=> $responseCode,
																	'log_msg'		=> $responseMsg,
																	'email_address'	=> $email,
																	'ip_address'	=> $ip
																)
									);
		}

		return intval( $responseCode );
	}
	
	/**
	 * Check if a member's posts need to be approved
	 * Note that this only takes into the global mod queue status, individual forums, etc. may
	 * set posts to be moderated and have custom permissions
	 *
	 * @param	int|array		Member ID or array of data
	 * @return	bool|null 		If TRUE - mod queue post. If FALSE - do not. If NULL - member is banned or restricted from posting and should not be able to post at all.
	 */
	public static function isOnModQueue( $memberData )
	{
		//-----------------------------------------
		// Get Data
		//-----------------------------------------
		
		if ( !is_array( $memberData ) && $memberData > 0 )
		{
			$memberData = IPSMember::load( $memberData );
		}
		
		//-----------------------------------------
		// Check we can post at all
		//-----------------------------------------
		
		/* Banned */
		if ( $memberData['member_banned'] )
		{
			return NULL;
		}
		
		/* Suspended */
		if ( $memberData['temp_ban'] )
		{
			$data = IPSMember::processBanEntry( $memberData['temp_ban'] );
			if ( $data['date_end'] )
			{
				if ( time() >= $data['date_end'] )
				{
					IPSMember::save( $memberData['member_id'], array( 'core' => array( 'temp_ban' => 0 ) ) );
				}
				else
				{
					return NULL;
				}
			}
			else
			{
				return NULL;
			}
		}
		
		/* Restricted from posting */
		if ( $memberData['restrict_post'] )
		{
			$data = IPSMember::processBanEntry( $memberData['restrict_post'] );
			if ( $data['date_end'] )
			{
				if ( time() >= $data['date_end'] )
				{
					IPSMember::save( $memberData['member_id'], array( 'core' => array( 'restrict_post' => 0 ) ) );
				}
				else
				{
					return NULL;
				}
			}
			else
			{
				return NULL;
			}
		}
		
		//-----------------------------------------
		// So are we mod queued?
		//-----------------------------------------
		
		if ( $memberData['mod_posts'] )
		{
			$data = IPSMember::processBanEntry( $memberData['mod_posts'] );
			if ( $data['date_end'] )
			{
				if ( time() >= $data['date_end'] )
				{
					IPSMember::save( $memberData['member_id'], array( 'core' => array( 'mod_posts' => 0 ) ) );
				}
				else
				{
					return TRUE;
				}
			}
			else
			{
				return TRUE;
			}
		}
		
		//-----------------------------------------
		// How about the group?
		//-----------------------------------------
		
		if ( $memberData['g_mod_preview'] )
		{
			/* Do we only limit for x posts/days? */
			if ( $memberData['g_mod_post_unit'] )
			{
				if ( $memberData['gbw_mod_post_unit_type'] )
				{
					/* Days.. .*/
					if ( $memberData['joined'] > ( IPS_UNIX_TIME_NOW - ( 86400 * $memberData['g_mod_post_unit'] ) ) )
					{
						return TRUE;
					}
				}
				else
				{
					/* Posts */
					if ( $memberData['posts'] < $memberData['g_mod_post_unit'] )
					{
						return TRUE;
					}
				}
			}
			else
			{
				/* No limit, but still checking moderating */
				return TRUE;
			}
		}
		
		//-----------------------------------------
		// Still here - which means we're fine
		//-----------------------------------------
		
		return FALSE;
	}
	
	/**
	 * Hide / Unhide / Delete Constants
	 */
	const CONTENT_DELETE = 1;
	const CONTENT_HIDE = 2;
	const CONTENT_UNHIDE = 3;
	
	/**
	 * Can hide/unhide content
	 *
	 * @param	array	Member Data
	 * @param	int		Action - see constants above
	 * @param	int		Item's owner ID
	 */
	public static function canModerateContent( $member, $action, $owner=NULL )
	{
		if ( $member['g_is_supmod'] )
		{
			return TRUE;
		}
		elseif ( !is_null( $owner ) and $member['member_id'] == $owner )
		{
			switch ( $action )
			{
				case self::CONTENT_DELETE:
					return $member['g_delete_own_posts'];
					break;
					
				case self::CONTENT_HIDE:
					return $member['gbw_soft_delete_own'];
					break;
					
				case self::CONTENT_UNHIDE:
					return FALSE;
					break;
					
			}
		}
		
		return FALSE;
	}
	
	/**
	 * Flag an account as spammer
	 *
	 * @param	int|array	$member				Member Data
	 * @param	array		$marker				The person marking this person a spammer
	 * @param	bool		$resetLastMember	If FALSE skips resetting the last registered member
	 * @return	void
	 */
	public static function flagMemberAsSpammer( $member, $marker=NULL, $resetLastMember=TRUE )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		/* Load Member */
		if ( !is_array( $member ) )
		{
			$member = self::load( $member );
		}
		
		/* Load moderator library (we'll need this to unapprove posts and log) */
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/moderate.php', 'moderatorLibrary', 'forums' );
		$modLibrary	=  new $classToLoad( ipsRegistry::instance() );
	    	
		//-----------------------------------------
		// Do it
		//-----------------------------------------
		
		$toSave = array();
		$toSave['core']['bw_is_spammer'] = TRUE;
		
		/* Shut off twitter/FB status importing */
		$bwOptions	= IPSBWOptions::thaw( $member['tc_bwoptions'], 'twitter' );
		$bwOptions['tc_si_status']	= 0;
		$twitter	= IPSBWOptions::freeze( $bwOptions, 'twitter' );
		$bwOptions = IPSBWOptions::thaw( $member['fb_bwoptions'], 'facebook' );
		$bwOptions['fbc_si_status']	= 0;			
		$facebook	= IPSBWOptions::freeze( $bwOptions, 'facebook' );
		$toSave['extendedProfile']['tc_bwoptions']	= $twitter;
		$toSave['extendedProfile']['fb_bwoptions']	= $facebook;

		/* Do any disabling, unapproving, banning - no breaks here since if we ban, we also want to unapprove posts, etc. */
		/* Note that there are DELIBERATELY no breaks in this switch since the options are cascading (if you ban, you also want to unapprove content) */
		switch( ipsRegistry::$settings['spm_option'] )
		{
			/* Empty profile and ban account */
			case 'ban':
				
				// ban
				$toSave['core']['member_banned'] = TRUE;
				
				// wipe data
				$toSave['core']['title'] = '';
				$toSave['extendedProfile']['signature'] = '';
				$toSave['extendedProfile']['pp_about_me'] = '';
				
				// wipe photo
				$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/member/photo.php', 'classes_member_photo' );
				$photos			= new $classToLoad( ipsRegistry::instance() );
				$photos->remove( $member['member_id'] );
				
				// wipe custom fields
				$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/customfields/profileFields.php', 'customProfileFields' );
			    $fields = new $classToLoad();
			    
				$fields->member_data = $member;
				$fields->initData( 'edit' );
				$fields->parseToSave( array() );
				if ( count( $fields->out_fields ) )
				{
					$toSave['customFields']	= $fields->out_fields;
				}
				
				// wipe signature
				IPSContentCache::update( $member['member_id'], 'sig', '' );
								
			/* Unapprove posts */
			case 'unapprove':
				$modLibrary->deleteMemberContent( $member['member_id'], 'all', intval( ipsRegistry::$settings['spm_post_days'] ) * 24 );
				
			/* Disable Post/PM permission */
			case 'disable':
				$toSave['core']['restrict_post']      = 1;
				$toSave['core']['members_disable_pm'] = 2;
		}
				
		self::save( $member['member_id'], $toSave );
		
		//-----------------------------------------
		// Run memberSync
		//-----------------------------------------
		
		IPSLib::runMemberSync( 'onSetAsSpammer', array_merge( $member, $toSave ) );
		
		//-----------------------------------------
		// Let the admin know if necessary
		//-----------------------------------------
		
		if ( $marker !== NULL and ipsRegistry::$settings['spm_notify'] and ( ipsRegistry::$settings['email_in'] != $marker['email'] ) )
		{
			ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_mod' ), 'forums' );
			
			IPSText::getTextClass('email')->getTemplate( 'possibleSpammer' );

			IPSText::getTextClass('email')->buildMessage( array(	'DATE'			=> ipsRegistry::getClass('class_localization')->getDate( $member['joined'], 'LONG', 1 ),
																	'MEMBER_NAME'	=> $member['members_display_name'],
																	'IP'			=> $member['ip_address'],
																	'EMAIL'			=> $member['email'],
																	'LINK'			=> ipsRegistry::getClass('output')->buildSEOUrl( "showuser=" . $member['member_id'], 'public', $member['members_seo_name'], 'showuser' ) ) );

			IPSText::getTextClass('email')->subject	= sprintf( ipsRegistry::getClass('class_localization')->words['new_registration_email_spammer'], ipsRegistry::$settings['board_name'] );
			IPSText::getTextClass('email')->to		= ipsRegistry::$settings['email_in'];
			IPSText::getTextClass('email')->sendMail();
		}
		
		/* Reset last member? */
		if ( $resetLastMember )
		{
			self::resetLastRegisteredMember();
		}
		
		//-----------------------------------------
		// Let IPS know
		//-----------------------------------------
		
		if( ipsRegistry::$settings['spam_service_send_to_ips'] )
		{
			self::querySpamService( $member['email'], $member['ip_address'], 'markspam' );
		}
		
		//-----------------------------------------
		// Log
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_modcp' ), 'core' );
		$modLibrary->addModerateLog( 0, 0, 0, 0, ipsRegistry::getClass('class_localization')->words['flag_spam_done'] . ': ' . $member['member_id'] . ' - ' . $member['email'] );
	}
	
	/**
	 * Resets the last registered member
	 *
	 * @param	bool	$return		If TRUE returns the value instead of updating it
	 * @return	@e mixed
	 */
	public static function resetLastRegisteredMember( $return=false )
	{
		/* Init vsrs */
		$groups = array();
		$_extra = '';
		$update = array( 'last_mem_id' => 0, 'last_mem_name' => '', 'last_mem_name_seo' => '' );
		
		/* Exclude certain groups */
		foreach( ipsRegistry::cache()->getCache('group_cache') as $_gid => $_gdata )
		{
			if( $_gdata['g_hide_online_list'] || $_gid == ipsRegistry::$settings['auth_group'] )
			{
				$groups[] = $_gid;
			}
		}
		
		/* Groups to skip? */
		$_extra = count($groups) ? "member_group_id NOT IN (" . implode( ',', $groups ) . ") AND " : '';
		
		$r = ipsRegistry::DB()->buildAndFetch( array( 'select' => 'member_id, members_display_name, members_seo_name',
														  'from'   => 'members',
														  'where'  => "{$_extra} members_display_name != '' AND members_display_name " . ipsRegistry::DB()->buildIsNull( false ) . " AND member_banned=0 AND ( ! " . IPSBWOptions::sql( 'bw_is_spammer', 'members_bitoptions', 'members', 'global', 'has' ) . ")",
														  'order'  => "member_id DESC",
														  'limit'  => array( 0, 1 )
												  )		 );
		
		$update['last_mem_id']			= intval($r['member_id']);
		$update['last_mem_name']		= trim($r['members_display_name']);
		$update['last_mem_name_seo']	= trim($r['members_seo_name']);
		
		/* Update our stats or return? */
		if ( $return )
		{
			return $update;
		}
		else
		{
			$stats = ipsRegistry::cache()->getCache('stats');
			$stats = array_merge( $stats, $update );
			
			ipsRegistry::cache()->setCache( 'stats', $stats, array( 'array' => 1 ) );
		}
	}
}