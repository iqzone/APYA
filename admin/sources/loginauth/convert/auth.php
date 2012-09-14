<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Login handler abstraction : Conversion method
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		2.1.0
 * @version		$Revision: 10721 $
 *
 */

class login_convert extends login_core implements interface_login
{
	/**
	 * member data property
	 *
	 * @access	public
	 * @var		array
	 */
	public $memberData;
	
	/**
	 * Login method configuration
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $method_config	= array();
	
	/**
	 * Field to look for password in
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $password_field 	= 'conv_password';

	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @param	array 		Configuration info for this method
	 * @param	array 		Custom configuration info for this method
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $method, $conf=array() )
	{
		$this->method_config	= $method;
		
		parent::__construct( $registry );
	}

	/**
	 * Authenticate the request
	 *
	 * @access	public
	 * @param	string		Username
	 * @param	string		Email Address
	 * @param	string		Password
	 * @return	boolean		Authentication successful
	 */
	public function authenticate( $username, $email_address, $password )
	{
		//-----------------------------------------
		// New system?
		//-----------------------------------------
		
		if( $this->settings['conv_login'] )
		{
			require_once( IPS_PATH_CUSTOM_LOGIN . '/convert/newauth.php' );/*noLibHook*/
			$conv = new converters( $this->registry, $this );
			$this->return_code = $conv->go( $username, $email_address, $password );
			return;
		}
		
		//-----------------------------------------
		// Did we actually convert?
		//-----------------------------------------
		
		if( $this->settings['conv_configured'] != 1 )
		{
			$this->return_code = "WRONG_AUTH";
			return false;
		}

		//-----------------------------------------
		// Check for new field
		//-----------------------------------------
		
		if( !$this->DB->checkForField( 'conv_password', 'members' ) )
		{
			$this->password_field = 'legacy_password';
		}

		$this->loadMember( $username );

		$chosen_converter	= explode( "_", $this->settings['conv_chosen'] );
		$login_handler_code = end( $chosen_converter );

		switch( $login_handler_code )
		{
			case 'vb3':
			case 'vb35':
			case 'vb37':
				$this->_authenticateVb3( $username, $password );
			break;
			
			case 'ib31':
				$this->_authenticateIb31( $username, $password );
			  break;
			   
			case 'smf11':
			case 'smf2':
				$this->_authenticateSmf11( $username, $password );
			  break;
			   
			case 'smf10':
			case 'yabbse':
				$this->_authenticateSmf( $username, $password );
			break;
			
			case 'yabb20':
			case 'yabb21':
				$this->_authenticateYabb20( $username, $password );
			break;
			
			case 'ubbt5':
				$this->_authenticateUbbthreads5( $username, $password );
			break;
			
			case 'snitz':
				$this->_authenticateSnitz( $username, $password );
			break;
			
			case 'punbb':
				$this->_authenticatePunbb( $username, $password );
			break;
			
			case 'dcforumplus':
				$this->_authenticateDcforum( $username, $password );
			break;
			
			case 'gcboards3':
				$this->_authenticateGcboards( $username, $password );
			break;
			
			case 'mybb':
				$this->_authenticateMybb( $username, $password );
			break;
			
			case 'wwwthreads':
				$this->_authenticateWwwthreads( $username, $password );
			break;
			
			case 'simpleforum':
				$this->_authenticateSimpleforum( $username, $password );
			break;
			
			case 'phpbb2':
			case 'phpbb3':
				$this->_authenticatePhpbb( $username, $password );
			break;
			
			case 'ubbthreads7':
			case 'ubbt5':
				$this->_authenticateUbbthreads5( $username, $password );
			break;
			
			case 'ubbt7':
			case 'ubbt74':
				$this->_authenticateUbbthreads74( $username, $password );
			break;
			
			case 'seoboard':
				$this->_authenticateSeoBoard( $username, $password );
			break;
			
			default:
				$this->return_code = "WRONG_AUTH";
			break;
		}
		
		if ( $this->return_code != 'SUCCESS' )
		{
			unset($this->_memberData);
		}

		return;
	}

	/**
	 * UBB.Threads 7.4.X
	 *
	 * @access	protected
	 * @param	string		Username
	 * @param	string		Password
	 * @return	boolean		Successful
	 */
	protected function _authenticateUbbthreads74( $username, $password )
	{
		if( $this->_memberData[ $this->password_field ] == md5( $this->_memberData['member_id'] . $password ) )
		{
			$this->cleanConvertData( md5( $password ) );
			$this->return_code = "SUCCESS";
			return true;
		}
		else
		{
			$this->return_code = "WRONG_AUTH";
			return false;
		}
	}
	
	/**
	 * phpBB 3
	 *
	 * @access	protected
	 * @param	string		Username
	 * @param	string		Password
	 * @return	boolean		Successful
	 */
	protected function _authenticatePhpbb( $username, $password )
	{
		$success			= false;
		$single_md5_pass	= md5( $password );
		$hash				= $this->_memberData[ $this->password_field ];

		$itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
		
		if ( strlen($hash) == 34 )
		{
			$success = ( $this->hashCryptPrivate($password, $hash, $itoa64 ) === $hash ) ? true : false;
		}
		else
		{
			$success = ( $single_md5_pass === $hash ) ? true : false;
		}

		if( $success )
		{
			$this->cleanConvertData( $single_md5_pass );
			$this->return_code = "SUCCESS";
			return true;
		}

		$this->return_code = "WRONG_AUTH";
		return false;
	}
	
	/**
	 * MyBB
	 *
	 * @access	protected
	 * @param	string		Username
	 * @param	string		Password
	 * @return	boolean		Successful
	 */
	protected function _authenticateMybb( $username, $password )
	{
		$current_password	= $this->_memberData[ $this->password_field ];
		$single_md5_pass 	= md5( $password );

		if ( $current_password == md5( md5($this->_memberData['misc']) . $single_md5_pass ) )
		{
			$this->cleanConvertData( $single_md5_pass );
			$this->return_code = "SUCCESS";
			return true;
		}
		else
		{
			$this->return_code = "WRONG_AUTH";
			return false;
		}
	}
	
	/**
	 * GC Board 3.0 BETA
	 *
	 * @access	protected
	 * @param	string		Username
	 * @param	string		Password
	 * @return	boolean		Successful
	 */
	protected function _authenticate_gcboards( $username, $password )
	{
		$myhash				= crypt( $password, 'authenticate' );
		$single_md5_pass	= md5( $password );
		$success			= false;

		if ( $myhash == $this->_memberData[ $this->password_field ] )
		{
			$success = true;
		}

		if( $success )
		{
			$this->cleanConvertData( $single_md5_pass );
			$this->return_code = "SUCCESS";
			return true;
		}

		$this->return_code = "WRONG_AUTH";
		return false;
	}
	
	/**
	 * DCForum+ authentication
	 *
	 * @access	protected
	 * @param	string		Username
	 * @param	string		Password
	 * @return	boolean		Successful
	 */
	protected function _authenticateDcforum( $username, $password )
	{
		$current_password	= $this->_memberData[ $this->password_field ];
		$crypted_password	= crypt( $password, substr($current_password, 0, 2) );
		$single_md5_pass	= md5( $password );

		if ($current_password == $crypted_password)
		{
			$this->cleanConvertData( $single_md5_pass );
			$this->return_code = "SUCCESS";
			return true;
		}
		else
		{
			$this->return_code = "WRONG_AUTH";
			return false;
		}
	}

	/**
	 * punBB authentication
	 *
	 * @access	protected
	 * @param	string		Username
	 * @param	string		Password
	 * @return	boolean		Successful
	 */
	protected function _authenticatePunbb( $username, $password )
	{
		$success			= false;
		$single_md5_pass	= md5( $password );

		if ( function_exists('sha1') )
		{
			if( sha1($password) == $this->_memberData[ $this->password_field ] )
			{
				$success = true;
			}
		}
		else if ( function_exists('mhash') )
		{
			if( bin2hex(mhash(MHASH_SHA1, $str)) == $this->_memberData[ $this->password_field ] )
			{
				$success = true;
			}
		}
		else
		{
			if( md5($password) == $this->_memberData[ $this->password_field ] )
			{
				$success = true;
			}
		}

		if( $success )
		{
			$this->cleanConvertData( $single_md5_pass );
			$this->return_code = "SUCCESS";
			return true;
		}

		$this->return_code = "WRONG_AUTH";
		return false;
	}

	/**
	 * snitz authentication
	 *
	 * @access	protected
	 * @param	string		Username
	 * @param	string		Password
	 * @return	boolean		Successful
	 */
	protected function _authenticateSnitz( $username, $password )
	{
		require_once( IPS_PATH_CUSTOM_LOGIN . '/convert/auth_sha256.php' );/*noLibHook*/
		$sha = new auth_sha256();

		if ( $this->_memberData[ $this->password_field ])
		{
			$sha256_password	= $sha->SHA256( $password );
			$single_md5_pass	= md5( $password );

			if ( $sha256_password == $this->_memberData[ $this->password_field ] )
			{
				$this->cleanConvertData( $single_md5_pass );

				$this->return_code = 'SUCCESS';
				return true;
			}
		}

		$this->return_code = 'WRONG_AUTH';
		return false;
	}

	/**
	 * vB 3 authentication
	 *
	 * @access	protected
	 * @param	string		Username
	 * @param	string		Password
	 * @return	boolean		Successful
	 */
	protected function _authenticateVb3( $username, $password )
	{
		if ( $this->_memberData['misc'])
		{
			$single_md5_pass	= md5( $password );
			$decr 				= md5( html_entity_decode( $password ) . $this->_memberData['misc'] );

			if ( $decr == $this->_memberData[ $this->password_field ] )
			{
				$this->cleanConvertData( $single_md5_pass );
				$this->return_code = 'SUCCESS';
				return true;
			}
			else
			{
				$single_md5_pass	= md5( html_entity_decode( $password ) );
				$tryagain			= md5( $single_md5_pass . $this->_memberData['misc'] );
				
				if ( $tryagain == $this->_memberData[ $this->password_field ] )
				{
					$this->cleanConvertData( $single_md5_pass );
					$this->return_code = 'SUCCESS';
					return true;
				}
			}
		}
		
		$this->return_code = 'WRONG_AUTH';
		return false;
	}

	/**
	 * iB 3.1 authentication
	 *
	 * @access	protected
	 * @param	string		Username
	 * @param	string		Password
	 * @return	boolean		Successful
	 */
	protected function _authenticateIb31( $username, $password )
	{
		$decr 				= md5( $password . $username );
		$single_md5_pass	= md5( $password );

		if ( $decr == $this->_memberData[ $this->password_field ] )
		{
			$this->cleanConvertData( $single_md5_pass );
			$this->return_code = "SUCCESS";
			return true;
		}

		$this->return_code = "WRONG_AUTH";
		return false;
	}

	/**
	 * SMF 1.1 authentication
	 *
	 * @access	protected
	 * @param	string		Username
	 * @param	string		Password
	 * @return	boolean		Successful
	 */
	protected function _authenticateSmf11( $username, $password )
	{
		$single_md5_pass = md5($password);

		if( $this->_memberData[ $this->password_field ] )
		{
			$username_low	= strtolower($username);
			$sha1_password	= sha1($username_low . $password);
			$success 		= false;

			if( $sha1_password == $this->_memberData[ $this->password_field ] )
			{
				$success = true;
			}
			else
			{
				$this->_authenticateSmf( $username, $password );

				if ( $this->return_code == "SUCCESS" )
				{
					$success = true;
				}
			}
			
			if( $success )
			{
				$this->cleanConvertData( $single_md5_pass );
				$this->return_code = "SUCCESS";
				return true;
			}

			$this->return_code = "WRONG_AUTH";
			return false;
		}
	}

	/**
	 * SMF / YABB.SE authentication
	 *
	 * @access	protected
	 * @param	string		Username
	 * @param	string		Password
	 * @return	boolean		Successful
	 */
	protected function _authenticateSmf( $username, $password )
	{
		if( $this->_memberData[ $this->password_field ] )
		{
			$single_md5_pass	= md5( $password );
			$success 			= false;

			if ( crypt( $password, substr( $password,0,2 ) ) == $this->_memberData[ $this->password_field ] )
			{
				$success = true;
			}
			else if ( strlen($this->_memberData[ $this->password_field ]) == 32  AND ( $this->md5Hmac( $password, $username ) == $this->_memberData[ $this->password_field ] ) )
			{
				$success = true;
			}
			else if ( strlen($this->_memberData[ $this->password_field ]) == 32  AND ( $single_md5_pass == $this->_memberData[ $this->password_field ] ) )
			{
				$success = true;
			}

			if( $success )
			{
				$this->cleanConvertData( $single_md5_pass );
				$this->return_code = "SUCCESS";
				return true;
			}

		}
		
		$this->return_code = "WRONG_AUTH";
		return false;
	}

	/**
	 * UBBThreads .5 authentication
	 *
	 * @access	protected
	 * @param	string		Username
	 * @param	string		Password
	 * @return	boolean		Successful
	 */
	protected function _authenticateUbbthreads5( $username, $password )
	{
		$single_md5_pass	= md5( $password );
		$success 			= false;

		if( crypt($password, $this->_memberData[ $this->password_field ]) == $this->_memberData[ $this->password_field ] )
		{
			$success = true;
		}
		else if( $single_md5_pass == $this->_memberData['legacy_password'] )
		{
			$success = true;
		}

		if( $success )
		{
			$this->cleanConvertData( $single_md5_pass );
			$this->return_code = "SUCCESS";
			return true;
		}

		$this->return_code = "WRONG_AUTH";
		return false;
	}

	/**
	 * WWWThreads authentication
	 *
	 * @access	protected
	 * @param	string		Username
	 * @param	string		Password
	 * @return	boolean		Successful
	 */
	protected function _authenticateWwwthreads( $username, $password )
	{
		$single_md5_pass = md5( $password );

		if ( $this->_memberData['misc'])
		{
			$sha1_in_db 	= (strlen($db_password_hash) == 40) ? true : false;
			$sha1_available = (function_exists('sha1') || function_exists('mhash')) ? true : false;

			if ( function_exists('sha1') ) 
			{
				$form_password_hash = sha1($str);
			} 
			else if (function_exists('mhash')) 
			{
				$form_password_hash = bin2hex(mhash(MHASH_SHA1, $str));
			} 
			else 
			{
				$form_password_hash = md5($str);
			}

			if ($sha1_in_db && $sha1_available && $db_password_hash == $form_password_hash) 
			{
				$authorized = true;
			} 
			else if (!$sha1_in_db && $db_password_hash == md5($form_password)) 
			{
				$authorized = true;
			}

			if( $authorized )
			{
				$this->cleanConvertData( $single_md5_pass );
				$this->return_code = "SUCCESS";
				return true;
			}
		}

		$this->return_code = "WRONG_AUTH";
		return false;
	}

	/**
	 * YABB 2.0 authentication
	 *
	 * @access	protected
	 * @param	string		Username
	 * @param	string		Password
	 * @return	boolean		Successful
	 */
	protected function _authenticateYabb20( $username, $password )
	{
		$myhash				= rtrim( base64_encode( pack( "H*", md5($password) ) ), "=" );
		$single_md5_pass 	= md5( $password );
		$success 			= false;

		if ( $myhash == $this->_memberData[ $this->password_field ] )
		{
			$success = true;
		}

		if( $success )
		{
			$this->cleanConvertData( $single_md5_pass );
			$this->return_code = "SUCCESS";
			return true;
		}

		$this->return_code = "WRONG_AUTH";
		return false;
	}

	/**
	 * SimpleForum authentication
	 *
	 * @access	protected
	 * @param	string		Username
	 * @param	string		Password
	 * @return	boolean		Successful
	 */
	protected function _authenticateSimpleforum( $username, $password )
	{
		$myhash				= crypt($password, 'SiMpLeFoRuM');
		$single_md5_pass	= md5( $password );
		$success 			= false;

		if ( $myhash == $this->_memberData[ $this->password_field ] )
		{
			$success = true;
		}

		if( $success )
		{
			$this->cleanConvertData( $single_md5_pass );
			$this->return_code = "SUCCESS";
			return true;
		}

		$this->return_code = "WRONG_AUTH";
		return false;
	}

	/**
	 * Load a member
	 *
	 * @access	public
	 * @param	string		Username
	 * @return	@e void
	 */
	public function loadMember( $username )
	{
		$member = $this->DB->buildAndFetch( array( 'select' => 'member_id', 'from' => 'members', 'where' => "members_l_username='" . $this->DB->addSlashes( strtolower($username) ) . "'" ) );
		
		if( $member['member_id'] )
		{
			$this->_memberData = IPSMember::load( $member['member_id'], 'extendedProfile,groups' );
		}
	}

	/**
	 * Clean up the converted data
	 *
	 * @access	public
	 * @param	string		new password
	 * @return	@e void
	 */
	public function cleanConvertData( $new_pass )
	{
		IPSMember::save( $this->_memberData['email'], array( 'core' => array( 'misc' => '', $this->password_field => '' ) ), 'email' );
		IPSMember::updatePassword( $this->_memberData['email'], $new_pass );
	}

	/**
	 * Hmac md5
	 *
	 * @access	public
	 * @param	string		data
	 * @param	string		key
	 * @return	string		hmac md5
	 */
	public function md5Hmac($data, $key)
	{
		if (strlen($key) > 64)
			$key = pack('H*', md5($key));
		$key  = str_pad($key, 64, chr(0x00));

		$k_ipad = $key ^ str_repeat(chr(0x36), 64);
		$k_opad = $key ^ str_repeat(chr(0x5c), 64);

		return md5($k_opad . pack('H*', md5($k_ipad . $data)));
	}
	
	/**
	 * Private crypt hashing for phpBB 3
	 *
	 * @access	public
	 * @param	string		Password
	 * @param	string 		Settings
	 * @param	string		Hash-lookup
	 * @return	string		phpBB3 password hash
	 */
	public function hashCryptPrivate( $password, $setting, &$itoa64 )
	{
		$output	= '*';

		// Check for correct hash
		if ( substr( $setting, 0, 3 ) != '$H$' )
		{
			return $output;
		}

		$count_log2 = strpos( $itoa64, $setting[3] );

		if ( $count_log2 < 7 || $count_log2 > 30 )
		{
			return $output;
		}

		$count	= 1 << $count_log2;
		$salt	= substr( $setting, 4, 8 );

		if ( strlen($salt) != 8 )
		{
			return $output;
		}

		/**
		 * We're kind of forced to use MD5 here since it's the only
		 * cryptographic primitive available in all versions of PHP
		 * currently in use.  To implement our own low-level crypto
		 * in PHP would result in much worse performance and
		 * consequently in lower iteration counts and hashes that are
		 * quicker to crack (by non-PHP code).
		 */
		if ( PHP_VERSION >= 5 )
		{
			$hash = md5( $salt . $password, true );

			do
			{
				$hash = md5( $hash . $password, true );
			}
			while ( --$count );
		}
		else
		{
			$hash = pack( 'H*', md5( $salt . $password ) );

			do
			{
				$hash = pack( 'H*', md5( $hash . $password ) );
			}
			while ( --$count );
		}

		$output	= substr( $setting, 0, 12 );
		$output	.= $this->_hashEncode64( $hash, 16, $itoa64 );

		return $output;
	}

	/**
	 * Private function to encode phpBB3 hash
	 *
	 * @access	protected
	 * @param	string		Input
	 * @param	count 		Iteration
	 * @param	string		Hash-lookup
	 * @return	string		phpbb3 password hash encoded bit
	 */
	protected function _hashEncode64($input, $count, &$itoa64)
	{
		$output	= '';
		$i		= 0;

		do
		{
			$value	= ord( $input[$i++] );
			$output	.= $itoa64[$value & 0x3f];

			if ( $i < $count )
			{
				$value |= ord($input[$i]) << 8;
			}

			$output .= $itoa64[($value >> 6) & 0x3f];

			if ( $i++ >= $count )
			{
				break;
			}

			if ( $i < $count )
			{
				$value |= ord($input[$i]) << 16;
			}

			$output .= $itoa64[($value >> 12) & 0x3f];

			if ($i++ >= $count)
			{
				break;
			}

			$output .= $itoa64[($value >> 18) & 0x3f];
		}
		while ( $i < $count );

		return $output;
	}

	/**
	 * Change a password
	 *
	 * @access	public
	 * @param	string		Email Address
	 * @param	string		New Password
	 * @param	string		Plain Text Password
	 * @param	string		Member Array
	 * @return	boolean		Request was successful
	 */
	public function changePass( $email, $new_pass, $plain_pass, $member )
	{
		$this->return_code = 'METHOD_NOT_DEFINED';
		return true;
	}
}