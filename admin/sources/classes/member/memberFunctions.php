<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Extended Member Functions. Disparate functions that are not required
 * on every page view.
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $ (Original: MattMecham)
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		Tuesday 1st March 2005 (11:52)
 * @version		$Revision: 10721 $
 *
 */


class memberFunctions
{
	/**#@+
	 * Registry objects
	 *
	 * @access	protected
	 * @var		object
	 */
	public $registry;
	public $DB;
	public $settings;
	public $request;
	public $lang;
	public $member;
	public $memberData	= array( 'member_id' => 0 );
	/**#@-*/
	
	/**
	 * Image class
	 *
	 * @access	public
	 * @var		object
	 */
	public $classImage;
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Main Registry  Object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make objects */
		$this->registry = $registry;
		$this->DB       = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->member   = $this->registry->member();

		if( is_object($this->registry->member()) )
		{
			$this->memberData =& $this->registry->member()->fetchMemberData();
		}
		
		$this->cache    = $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
	}
	
	/**
	 * Updates member's DB row name or members_display_name
	 *
	 * @todo 	[Future] Separate out forum specific stuff (moderators, etc) and move into hooks 
	 * 
	 * @param	string		Member id
	 * @param	string		New name
	 * @param	string		Field to update (name or display name)
	 * @return	mixed		True if update successful, otherwise exception or false
	 * 
	 * Error Codes:
	 * NO_USER				Could not load the user
	 * NO_PERMISSION		This user cannot change their display name at all
	 * NO_MORE_CHANGES		The user cannot change their display name again in this time period
	 * NO_NAME				No display name (or shorter than 3 chars was given)
	 * ILLEGAL_CHARS		The display name contains illegal characters
	 * USER_NAME_EXISTS		The username already exists
	 */
	public function updateName( $member_id, $name, $field='members_display_name', $discount=FALSE )
	{
		//-----------------------------------------
		// Load the member
		//-----------------------------------------
		
		$member   = IPSMember::load( $member_id );
		$_seoName = IPSText::makeSeoTitle( $name );
		
		if ( ! $member['member_id'] )
		{
			throw new Exception( "NO_USER" );
		}
		
		//-----------------------------------------
		// Make sure name does not exist
		//-----------------------------------------
		
		try
		{
			if ( $this->checkNameExists( $name, $member, $field ) === TRUE )
			{
				throw new Exception( "USER_NAME_EXISTS" );
			}
			else
			{
				if ( $field == 'members_display_name' )
				{
					$this->DB->setDataType( array( 'dname_previous', 'dname_current' ), 'string' );
					
					if ( $member['members_display_name'] != $name )
					{

				    	$this->DB->insert( 'dnames_change', array( 'dname_member_id'	=> $member_id,
				    											   'dname_date'			=> time(),
																   'dname_ip_address'	=> $member['ip_address'],
																   'dname_previous'		=> $member['members_display_name'],
																   'dname_current'		=> $name,
																   'dname_discount'		=> $discount ? 1 : 0 ) );
				    												  
				    }

					//-----------------------------------------
					// Still here? Change it then
					//-----------------------------------------

					IPSMember::save( $member['member_id'], array( 'core' => array( 'members_display_name' => $name, 'members_l_display_name' => strtolower( $name ), 'members_seo_name' => $_seoName ) ) );

					$this->DB->setDataType( array( 'last_poster_name', 'seo_last_name' ), 'string' );
					$this->DB->update( 'forums', array( 'last_poster_name' => $name, 'seo_last_name' => $_seoName ), "last_poster_id=" . $member['member_id'] );

					$this->DB->setDataType( array( 'member_name', 'seo_name' ), 'string' );
					$this->DB->update( 'sessions', array( 'member_name' => $name, 'seo_name' => $_seoName ), "member_id=" . $member['member_id'] );

					$this->DB->setDataType( array( 'starter_name', 'seo_first_name' ), 'string' );
					$this->DB->update( 'topics', array( 'starter_name' => $name, 'seo_first_name' => $_seoName ), "starter_id=" . $member['member_id'] );

					$this->DB->setDataType( array( 'last_poster_name', 'seo_last_name' ), 'string' );
					$this->DB->update( 'topics', array( 'last_poster_name' => $name, 'seo_last_name' => $_seoName ), "last_poster_id=" . $member['member_id'] );
				}
				else
				{
					//-----------------------------------------
					// If one gets here, one can assume that the new name is correct for one, er...one.
					// So, lets do the converteroo
					//-----------------------------------------

					IPSMember::save( $member['member_id'], array( 'core' => array( 'name' => $name, 'members_l_username' => strtolower( $name ) ) ) );

					$this->DB->setDataType( 'member_name', 'string' );
					$this->DB->update( 'moderators', array( 'member_name' => $name ), "member_id=" . $member['member_id'] );

					if ( ! $this->settings['auth_allow_dnames'] )
					{
						//-----------------------------------------
						// Not using sep. display names?
						//-----------------------------------------

						IPSMember::save( $member['member_id'], array( 'core' => array( 'members_display_name' => $name, 'members_l_display_name' => strtolower( $name ), 'members_seo_name' => $_seoName ) ) );

						$this->DB->setDataType( array( 'last_poster_name', 'seo_last_name' ), 'string' );
						$this->DB->update( 'forums', array( 'last_poster_name' => $name, 'seo_last_name' => $_seoName ), "last_poster_id=" . $member['member_id'] );

						$this->DB->setDataType( array( 'member_name', 'seo_name' ), 'string' );
						$this->DB->update( 'sessions', array( 'member_name' => $name, 'seo_name' => $_seoName ), "member_id=" . $member['member_id'] );

						$this->DB->setDataType( array( 'starter_name', 'seo_first_name' ), 'string' );
						$this->DB->update( 'topics', array( 'starter_name' => $name, 'seo_first_name' => $_seoName ), "starter_id=" . $member['member_id'] );

						$this->DB->setDataType( array( 'last_poster_name', 'seo_last_name' ), 'string' );
						$this->DB->update( 'topics', array( 'last_poster_name' => $name, 'seo_last_name' => $_seoName ), "last_poster_id=" . $member['member_id'] );
					}
				}

				//-----------------------------------------
				// Recache moderators
				//-----------------------------------------

				$this->registry->cache()->rebuildCache( 'moderators', 'forums' );

				//-----------------------------------------
				// Recache announcements
				//-----------------------------------------

				$this->registry->cache()->rebuildCache( 'announcements', 'forums' );

				//-----------------------------------------
				// Stats to Update?
				//-----------------------------------------

				$this->registry->cache()->rebuildCache( 'stats', 'core' );
				
				IPSLib::runMemberSync( 'onNameChange', $member['member_id'], $name );

				return TRUE;
			}
		}
		catch( Exception $error )
		{
			throw new Exception( $error->getMessage() );
		}
	}
	
	/**
	 * Cleans a username or display name, also checks for any errors
	 *
	 * @access	public
	 * @param	string  $name			Username or display name to clean and check
	 * @param	array	$member			[ Optional Member Array ]
	 * @param	string  $field			name or members_display_name
	 * @return	array   Returns an array with 2 keys: 'username' OR 'members_display_name' => the cleaned username, 'errors' => Any errors found
	 */
	public function cleanAndCheckName( $name, $member=array(), $field='members_display_name' )
	{
		//-----------------------------------------
		// Clean the name first
		//-----------------------------------------
		
		$cleanedName	= $this->_cleanName( $name, $field );

		if( count($cleanedName['errors']) )
		{
			if( $field == 'members_display_name' )
			{
				return array( 'members_display_name' => $cleanedName['name'], 'errors' => array( 'dname' => $cleanedName['errors'][0] ) );
			}
			else
			{
				return array( 'username' => $cleanedName['name'], 'errors' => array( 'username' => $cleanedName['errors'][0] ) );
			}
		}

		//-----------------------------------------
		// Name is clean, make sure it doesn't exist
		//-----------------------------------------
		
		try
		{
			if( !$this->checkNameExists( $cleanedName['name'], $member, $field, true, true ) )
			{
				if( $field == 'members_display_name' )
				{
					return array( 'members_display_name' => $cleanedName['name'], 'errors' => array() );
				}
				else
				{
					return array( 'username' => $cleanedName['name'], 'errors' => array() );
				}
			}
			else
			{
				if( $field == 'members_display_name' )
				{
					return array( 'members_display_name' => $cleanedName['name'], 'errors' => array( 'dname' => 'reg_error_username_taken' ) );
				}
				else
				{
					return array( 'username' => $cleanedName['name'], 'errors' => array( 'username' => 'reg_error_username_taken' ) );
				}
			}
		}
		catch( Exception $e )
		{
			//-----------------------------------------
			// Name exists, let's return appropriately
			//-----------------------------------------

			if( $field == 'members_display_name' )
			{
				switch( $e->getMessage() )
				{
					case 'NO_NAME':
						return array( 'members_display_name' => $cleanedName['name'], 'errors' => array( 'dname' => 'reg_error_no_name' ) );
					break;
					
					case 'ILLEGAL_CHARS':
						return array( 'members_display_name' => $cleanedName['name'], 'errors' => array( 'dname' => 'reg_error_chars' ) );
					break;
				}
			}
			else
			{
				switch( $e->getMessage() )
				{
					case 'NO_NAME':
						return array( 'username' => $cleanedName['name'], 'errors' => array( 'username' => 'reg_error_username_none' ) );
					break;
					
					case 'ILLEGAL_CHARS':
						return array( 'username' => $cleanedName['name'], 'errors' => array( 'username' => 'reg_error_chars' ) );
					break;
				}
			}
		}
	}
	
	/**
	 * Check for an existing display or user name
	 *
	 * @access	public
	 * @param	string	Name to check
	 * @param	array	[ Optional Member Array ]
	 * @param	string	name or members_display_name
	 * @param	bool	Ignore display name changes check (e.g. for registration)
	 * @param	bool	Do not clean name again (e.g. coming from cleanAndCheckName)
	 * @return	mixed	Either an exception or ( true if name exists. False if name DOES NOT exist )
	 * Error Codes:
	 * NO_PERMISSION		This user cannot change their display name at all
	 * NO_MORE_CHANGES		The user cannot change their display name again in this time period
	 * NO_NAME				No display name (or shorter than 3 chars was given)
	 * ILLEGAL_CHARS		The display name contains illegal characters
	 */
	public function checkNameExists( $name, $member=array(), $field='members_display_name', $ignore=false, $cleaned=false )
	{
		if( ! $cleaned )
		{
			$cleanedName	= $this->_cleanName( $name, $field );
			$name			= $cleanedName['name'];

			if( count($cleanedName['errors']) )
			{
				throw new Exception( $cleanedName['errors'][0] );
			}
		}

		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$error        = "";
		$banFilters   = array();
		$_timeCheck   = time() - 86400 * $this->memberData['g_dname_date'];
		$member       = is_array($member) ? $member : array();
		$checkField   = ( $field == 'members_display_name' ) ? 'members_l_display_name' : 'members_l_username';
		
		//-----------------------------------------
		// Public checks
		//-----------------------------------------
		
		if ( IPS_AREA != 'admin' AND $ignore != true )
		{
			if ( ! $this->settings['auth_allow_dnames'] OR $member['g_dname_changes'] == 0 OR $member['g_dname_date'] < 1 )
			{
				throw new Exception( "NO_PERMISSION" );
			}
			
			/* Check new permissions */
			$_g = $this->caches['group_cache'][ $member['member_group_id'] ];
		
			if ( $_g['g_displayname_unit'] )
			{
				if ( $_g['gbw_displayname_unit_type'] )
				{
					/* days */
					if ( $member['joined'] > ( time() - ( 86400 * $_g['g_displayname_unit'] ) ) )
					{
						throw new Exception( "NO_PERMISSION" );
					}
				}
				else
				{
					/* Posts */
					if ( $member['posts'] < $_g['g_displayname_unit'] )
					{
						throw new Exception( "NO_PERMISSION" );
					}
				}
			}
			
			//-----------------------------------------
			// Grab # changes > 24 hours
			//-----------------------------------------

			if( $member['member_id'] and $member['g_dname_changes'] != -1 )
			{
				$name_count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count, MIN(dname_date) as min_date', 'from' => 'dnames_change', 'where' => "dname_member_id=" . $member['member_id'] . " AND dname_date > $_timeCheck AND dname_discount=0" ) );
	
				$name_count['count']    = intval( $name_count['count'] );
				$name_count['min_date'] = intval( $name_count['min_date'] ) ? intval( $name_count['min_date'] ) : $_timeCheck;
	
				if ( intval( $name_count['count'] ) >= $member['g_dname_changes'] )
				{
					throw new Exception( "NO_MORE_CHANGES" );
				}
			}
		}

		//-----------------------------------------
		// Load ban filters
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'banfilters' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$banFilters[ $r['ban_type'] ][] = $r['ban_content'];
		}

		//-----------------------------------------
		// Are they banned [NAMES]?
		//-----------------------------------------
		
		if ( IPS_AREA != 'admin' )
		{
			if ( is_array( $banFilters['name'] ) and count( $banFilters['name'] ) )
			{
				foreach ( $banFilters['name'] as $n )
				{
					if ( $n == "" )
					{
						continue;
					}
					
					$n = str_replace( '\*', '.*' ,  preg_quote($n, "/") );
					
					if ( preg_match( "/^{$n}$/i", $name ) )
					{
						return TRUE;
						break;
					}
				}
			}
		}
		
		//-----------------------------------------
		// Check for existing name.
		//-----------------------------------------
		
		/* Load the handler if it's not present */
		if( ! $this->han_login )
		{
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/handlers/han_login.php', 'han_login' );
    		$this->han_login =  new $classToLoad( $this->registry );
    		$this->han_login->init();
		}
		
		if( $member['member_id'] )
		{
			$this->han_login->nameExistsCheck( $name, $member, $checkField );
	    	
			if( $this->han_login->return_code AND $this->han_login->return_code != 'METHOD_NOT_DEFINED' AND $this->han_login->return_code != 'NAME_NOT_IN_USE' )
			{
				return TRUE;
			}
		}

		$this->DB->build( array( 
									'select' => "{$field}, member_id",
									'from'   => 'members',
									'where'  => $checkField . "='" . $this->DB->addSlashes( strtolower($name) ) . "'" . ( $member['member_id'] ? " AND member_id != " . $member['member_id'] : '' ),
									'limit'  => array( 0,1 ) ) );
        
    	$this->DB->execute();
    	
    	//-----------------------------------------
    	// Got any results?
    	//-----------------------------------------
    	
    	if ( $this->DB->getTotalRows() )
 		{
    		return TRUE;
    	}
    	
		//-----------------------------------------
    	// Not allowed to select another's log in name
    	//-----------------------------------------

    	if ( $field == 'members_display_name' AND $this->settings['auth_dnames_nologinname'] )
    	{ 
    		$check_name = $this->DB->buildAndFetch( array( 'select' => "{$field}, member_id",
																	'from'   => 'members',
																	'where'  => "members_l_username='" . $this->DB->addSlashes( strtolower($name) ) . "'",
																	'limit'  => array( 0,1 ) ) );
    											 
    		if ( $this->DB->getTotalRows() )
    		{
    			if ( !$member['member_id'] OR $check_name['member_id'] != $member['member_id'] )
    			{
    				return TRUE;
				}
			}
    	}
    	
    	if ( $field == 'name' AND $this->settings['auth_dnames_nologinname'] )
    	{
    		$check_name = $this->DB->buildAndFetch( array( 'select' => "{$field}, member_id",
																	'from'   => 'members',
																	'where'  => "members_l_display_name='" . $this->DB->addSlashes( strtolower($name) ) . "'",
																	'limit'  => array( 0,1 ) ) );
    											 
    		if ( $this->DB->getTotalRows() )
    		{
    			if ( !$member['member_id'] OR $check_name['member_id'] != $member['member_id'] )
    			{
    				return TRUE;
				}
			}
    	}

		//-----------------------------------------
		// Test for unicode name
		//-----------------------------------------
		
		$unicodeName	= $this->_getUnicodeName( $name );
		
		if ( $unicodeName != $name )
		{
			//-----------------------------------------
			// Check for existing name.
			//-----------------------------------------
			
			$this->DB->build( array( 'select' => "members_display_name, member_id, email",
										   'from'   => 'members',
										   'where'  => $checkField . "='". $this->DB->addSlashes( strtolower($unicodeName) ) . "'" . ( $member['member_id'] ? " AND member_id != " . $member['member_id'] : '' ),
										   'limit'  => array( 0,1 ) ) );
													 
			$this->DB->execute();
			
			//-----------------------------------------
			// Got any results?
			//-----------------------------------------
			
			if ( $this->DB->getTotalRows() )
			{
				return TRUE;
			}
		}
    	
    	return FALSE;
	}

	/**
	 * Clean a username or display name
	 *
	 * @access	protected
	 * @param	string		Name
	 * @param	string		Field (name or members_display_name)
	 * @return	array		array( 'name' => $cleaned_name, 'errors' => array() )
	 */
	protected function _cleanName( $name, $field='members_display_name' )
	{
		$original	= $name;
		$name		= trim($name);
		
		if( $field == 'name' )
		{
			// Commented out for bug report #15354
			//$name	= str_replace( '|', '&#124;' , $name );
			
			/* Remove multiple spaces */
			$name	= preg_replace( '/\s{2,}/', " ", $name );
		}
		
		//-----------------------------------------
		// Remove line breaks
		//-----------------------------------------
		
		if( ipsRegistry::$settings['usernames_nobr'] )
		{
			$name = IPSText::br2nl( $name );
			$name = str_replace( "\n", "", $name );
			$name = str_replace( "\r", "", $name );
		}
		
		//-----------------------------------------
		// Remove sneaky spaces
		//-----------------------------------------
		
		if ( ipsRegistry::$settings['strip_space_chr'] )
    	{
    		/* use hexdec to convert between '0xAD' and chr */
			$name          = IPSText::removeControlCharacters( $name );
		}

		//-----------------------------------------
		// Trim after above ops
		//-----------------------------------------
		
		$name = trim( $name );

		//-----------------------------------------
		// Test unicode name
		//-----------------------------------------
		
		$unicode_name	= $this->_getUnicodeName( $name );

		//-----------------------------------------
		// Do we have a name?
		//-----------------------------------------
		
		if( $field == 'name' OR ( $field == 'members_display_name' AND ipsRegistry::$settings['auth_allow_dnames'] ) )
		{
			if( ! $name OR IPSText::mbstrlen( $name ) < 3  OR IPSText::mbstrlen( $name ) > ipsRegistry::$settings['max_user_name_length'] )
			{
				ipsRegistry::getClass( 'class_localization' )->loadLanguageFile( array( 'public_register' ), 'core' );
				
				$key	= $field == 'members_display_name' ? 'reg_error_no_name' : 'reg_error_username_none';

				$text	= sprintf( ipsRegistry::getClass( 'class_localization' )->words[ $key ], ipsRegistry::$settings['max_user_name_length'] );
				
				//-----------------------------------------
				// Only show note about special chars when relevant
				//-----------------------------------------
				
				if( strpos( $name, '&' ) !== false )
				{
					$text	.= ' ' . ipsRegistry::getClass( 'class_localization' )->words['reg_error_no_name_spec'];
				}
				
				return array( 'name' => $original, 'errors' => array( $text ) );
			}
		}

		//-----------------------------------------
		// Blocking certain chars in username?
		//-----------------------------------------
				
		if( ipsRegistry::$settings['username_characters'] )
		{
			$_name = html_entity_decode( $name ); // Fix for bug #30287
			$check_against = preg_quote( ipsRegistry::$settings['username_characters'], "/" );
			$check_against = str_replace( '\-', '-', $check_against ); // Fix for bug #20998
			
			if( !preg_match( "/^[" . $check_against . "]+$/iu", $_name ) )
			{
				return array( 'name' => $original, 'errors' => array( str_replace( '{chars}', ipsRegistry::$settings['username_characters'], ipsRegistry::$settings['username_errormsg'] ) ) );
			}
		}

		//-----------------------------------------
		// Manually check against bad chars
		//-----------------------------------------
		
		if( strpos( $unicode_name, '&#92;' ) !== false OR 
			strpos( $unicode_name, '&#quot;' ) !== false OR 
			strpos( $unicode_name, '&#036;' ) !== false OR
			strpos( $unicode_name, '&#lt;' ) !== false OR
			strpos( $unicode_name, '$' ) !== false OR
			strpos( $unicode_name, ']' ) !== false OR
			strpos( $unicode_name, '[' ) !== false OR
			strpos( $unicode_name, ',' ) !== false OR
			strpos( $unicode_name, '|' ) !== false OR
			strpos( $unicode_name, '&#gt;' ) !== false )
		{
			ipsRegistry::getClass( 'class_localization' )->loadLanguageFile( array( 'public_register' ), 'core' );
			
			return array( 'name' => $original, 'errors' => array( ipsRegistry::getClass( 'class_localization' )->words['reg_error_chars'] ) );
		}

		return array( 'name' => $name, 'errors' => array() );
	}
	
	/**
	 * Get unicode version of name
	 *
	 * @access	protected
	 * @param	string		Name
	 * @return	string		Unicode Name
	 */
	protected function _getUnicodeName( $name )
	{
		$unicode_name  = preg_replace_callback( '/&#([0-9]+);/si', create_function( '$matches', 'return chr($matches[1]);' ), $name );
		$unicode_name  = str_replace( "'" , '&#39;', $name );
		$unicode_name  = str_replace( "\\", '&#92;', $name );
		
		return $unicode_name;
	}

	
	
	/**
	 * Upload background image
	 * Assumes all security checks have been performed by this point
	 *
	 * @access	public
	 * @param	integer		[Optional] member id instead of current member
	 * @return 	array  		[ error (error message), status (status message [ok/fail] ) ]
	 */
	public function uploadBackgroundImage( $member_id = 0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$return		      = array( 'error'            => '',
								   'status'           => '',
								   'final_location'   => '' );
								   
		$member_id        = $member_id ? intval($member_id) : intval( $this->memberData['member_id'] );
		$p_max			  = $this->memberData['g_max_bgimg_upload'] ? intval( $this->memberData['g_max_bgimg_upload'] ) : 999999999;
		$real_name        = '';
		$upload_dir       = '';
		$final_location   = '';
		
		if( ! $member_id )
		{
			return array( 'status' => 'cannot_find_member' );
		}
				
		//-----------------------------------------
		// Sort out upload dir
		//-----------------------------------------

		/* Fix for bug 5075 */
		$this->settings['upload_dir'] = str_replace( '&#46;', '.', $this->settings['upload_dir'] );		

		$upload_path  = $this->settings['upload_dir'];
		
		# Preserve original path
		$_upload_path = $this->settings['upload_dir'];
		
		//-----------------------------------------
		// Already a dir?
		//-----------------------------------------
		
		if ( ! file_exists( $upload_path . "/bgimages" ) )
		{
			if ( @mkdir( $upload_path . "/bgimages", IPS_FOLDER_PERMISSION ) )
			{
				@file_put_contents( $upload_path . '/bgimages/index.html', '' );
				@chmod( $upload_path . "/bgimages", IPS_FOLDER_PERMISSION );
				
				# Set path and dir correct
				$upload_path .= "/bgimages";
				$upload_dir   = "bgimages/";
			}
			else
			{
				# Set path and dir correct
				$upload_dir   = "";
			}
		}
		else
		{
			# Set path and dir correct
			$upload_path .= "/bgimages";
			$upload_dir   = "bgimages/";
		}
		
		//-----------------------------------------
		// Lets check for an uploaded photo..
		//-----------------------------------------

		if ( $_FILES['bg_upload']['name'] != "" and ($_FILES['bg_upload']['name'] != "none" ) )
		{
			//-----------------------------------------
			// Are we allowed to upload this photo?
			//-----------------------------------------
			
			if ( $p_max < 0 )
			{
				$return['status'] = 'fail';
				$return['error']  = 'no_bgimg_upload_permission';
			}
			
			//-----------------------------------------
			// Remove any uploaded photos...
			//-----------------------------------------
			
			$this->removeUploadedBackgroundImages( $member_id );
			
			$real_name = 'bgimg-'.$member_id;
			
			//-----------------------------------------
			// Load the library
			//-----------------------------------------
			
			require_once( IPS_KERNEL_PATH.'classUpload.php' );/*noLibHook*/
			$upload    = new classUpload();

			//-----------------------------------------
			// Set up the variables
			//-----------------------------------------

			$upload->out_file_name     = 'bgimg-'.$member_id;
			$upload->out_file_dir      = $upload_path;
			$upload->max_file_size     = $p_max * 1024;
			$upload->upload_form_field = 'bg_upload';
			
			//-----------------------------------------
			// Populate allowed extensions
			//-----------------------------------------

			$upload->allowed_file_ext  = array( 'gif', 'png', 'jpg', 'jpeg' );
			
			//-----------------------------------------
			// Upload...
			//-----------------------------------------
			
			$upload->process();
			
			//-----------------------------------------
			// Error?
			//-----------------------------------------
			
			if ( $upload->error_no )
			{
				switch( $upload->error_no )
				{
					case 1:
						// No upload
						$return['status'] = 'fail';
						$return['error']  = 'upload_failed';
					break;
					case 2:
						// Invalid file ext
						$return['status'] = 'fail';
						$return['error']  = 'invalid_file_extension';
					break;
					case 3:
						// Too big...
						$return['status'] = 'fail';
						$return['error']  = 'upload_to_big';
					break;
					case 4:
						// Cannot move uploaded file
						$return['status'] = 'fail';
						$return['error']  = 'upload_failed';
					break;
					case 5:
						// Possible XSS attack (image isn't an image)
						$return['status'] = 'fail';
						$return['error']  = 'upload_failed';
					break;
				}
				
				return $return;
			}
						
			//-----------------------------------------
			// Still here?
			//-----------------------------------------
			
			$real_name   = $upload->parsed_file_name;
			$t_real_name = $upload->parsed_file_name;

			//-----------------------------------------
			// Check the file size (after compression)
			//-----------------------------------------
			
			if ( filesize( $upload_path . "/" . $real_name ) > ( $p_max * 1024 ) )
			{
				@unlink( $upload_path . "/" . $real_name );
				
				// Too big...
				$return['status'] = 'fail';
				$return['error']  = 'upload_to_big';
				return $return;
			}
			
			//-----------------------------------------
			// Main
			//-----------------------------------------
			
			$final_location = $upload_dir . $real_name;
			
		}
		else
		{
			$return['status'] = 'ok';
			return $return;
		}
		
		//-----------------------------------------
		// Return...
		//-----------------------------------------
		
		$return['final_location']   = $final_location;
		$return['status'] = 'ok';
		
		return $return;
	}
	
	/**
	 * Remove member uploaded background images
	 *
	 * @access	public
	 * @param	integer		Member ID
	 * @param	string		[Optional] Directory to check
	 * @return 	array  		[ error (error message), status (status message [ok/fail] ) ]
	 */
	public function removeUploadedBackgroundImages( $id, $upload_path='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$upload_path = $upload_path ? $upload_path : $this->settings['upload_dir'];

		//-----------------------------------------
		// Already a dir?
		//-----------------------------------------
		
		if ( ! file_exists( $upload_path . "/bgimages" ) )
		{
			if ( @mkdir( $upload_path . "/bgimages", IPS_FOLDER_PERMISSION ) )
			{
				@file_put_contents( $upload_path . '/index.html', '' );
				@chmod( $upload_path . "/bgimages", IPS_FOLDER_PERMISSION );
				
				# Set path and dir correct
				$upload_path .= "/bgimages";
			}
		}
		else
		{
			# Set path and dir correct
			$upload_path .= "/bgimages";
			
			//-----------------------------------------
			// Only should bother trying to delete if we didn't
			// just create the folder
			//-----------------------------------------
			
			foreach( array( 'swf', 'jpg', 'jpeg', 'gif', 'png' ) as $ext )
			{
				if ( @is_file( $upload_path."/bgimg-".$id.".".$ext ) )
				{
					@unlink( $upload_path."/bgimg-".$id.".".$ext );
				}
			}
		}
	}
	
}