<?php
/**
 * IPS Converters
 * Application Files
 * Login functions
 * Last Updated: $Date: 2012-04-04 18:02:26 -0400 (Wed, 04 Apr 2012) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2006 - 2009 Invision Power Services, Inc.
 * @package		IPS Converters
 * @link		http://www.invisionpower.com
 * @version		$Revision: 10560 $
 */

class converters
{
	
	/**
	 * Constructor
	 *
	 * @param	ipsRegistry
	 * @return	@e void
	 **/
	public function __construct(ipsRegistry $registry, $parent)
	{
		$this->registry 	= $registry;
		$this->DB	    	= $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->parent = $parent;
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->registry->getClass( 'class_localization' )->loadLanguageFile( array( 'public_clobal' ), 'core' );
	}

	/**
	 * Work out which function to use
	 **/
	
	public function go($username, $email, $password)
	{
		$success = false;
		
		$this->parent->loadMember($username);
		
		$this->DB->build(array('select' => 'app_key', 'from' => 'conv_apps', 'where' => 'login=1'));
		$this->DB->execute();
		$cycle = array();
		while ($r = $this->DB->fetch())
		{
			$cycle[$r['app_key']] = $r['app_key'];
		}
		
		foreach ($cycle as $sw)
		{
			if ($this->$sw($username, $email, $password))
			{
				$success = true;
				break; //already true, skip other possible checks
			}
		}
		
		if( $success )
		{
			// Give them an IPB style password
			$this->parent->cleanConvertData( md5($password) );
			
			// Force the log in
			require_once( IPS_ROOT_PATH . 'sources/handlers/han_login.php' );/*noLibHook*/
	    	$this->han_login =  new han_login( $this->registry );
			$this->han_login->loginWithoutCheckingCredentials($this->parent->_memberData['member_id']);
			
			// And form our own redirect 
			$this->registry->getClass('output')->redirectScreen( $this->lang->words['partial_login'] , $this->settings['base_url'] );
		}
		else
		{
			unset($this->parent->_memberData);
			return "WRONG_AUTH";
		}
		
	}
	
	private function aef ( $username, $email, $password )
	{
		if ( $this->parent->_memberData['conv_password'] == md5 ( $this->parent->_memberData['misc'] . $password ) )
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	private function bbpress_standalone($username, $email, $password)
	{
		return $this->bbpress($username, $email, $password);
	}

	private function bbpress($username, $email, $password) 
	{
		$hash = $this->parent->_memberData['conv_password'];
		
		if ( strlen( $hash ) == 32 ) 
		{
			return (bool) ( md5( $password ) == $this->parent->_memberData['conv_password'] ); 
		}
		
		$itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
		$crypt = $this->parent->hashCryptPrivate($password, $hash, $itoa64, 'P');
		if ($crypt[0] == '*')
		{
			$crypt = crypt( $password, $hash );
		}
		
		return $crypt == $hash ? TRUE : FALSE;
	}
	
	/**
	 * Community Server authentication
	 *
	 * @access	private
	 * @param	string		Username
	 * @param	string		Password
	 * @return	boolean		Successful
	 */
	private function cs( $username, $password )
	{
		$hash = $this->parent->_memberData['conv_password'];

		$encodedHashPass = base64_encode(pack("H*", sha1(base64_decode($this->parent->_memberData['misc']) . $password)));
		$single_md5_pass = md5( $password );

		if ($encodedHashPass == $hash)
		{
			$this->return_code = "SUCCESS";
			return true;
		}
		else
		{
			$this->return_code = "WRONG_AUTH";
			return false;
		}
	}

	function CSAuth($username, $password)
	{
		$wsdl = 'https://internal.auth.com/Service.asmx?wsdl';
		$dest = 'https://interal.auth.com/Service.asmx';
		$single_md5_pass = md5( $password );

		try
		{
			$client = new SoapClient($wsdl, array('trace' => 1));
			$client->__setLocation($dest);
			$loginparams = array('username' => $username, 'password' => $password);
			$result = $client->AuthCS($loginparams);

			switch ( $result->AuthCSResult )
			{
				case 'SUCCESS':
					$this->cleanConvertData( $single_md5_pass );
					$this->return_code = $result->AuthCSResult;
					return TRUE;
				case 'WRONG_AUTH':
					$this->return_code = $result->AuthCSResult;
					return FALSE;
				default:
					$this->return_code = 'FAIL';
					return FALSE;
			}
		}
		catch(Exception $ex)
		{
			//handle error
			$this->return_code = 'FAIL';
			return FALSE;
		}
	}

	/**
	 * FudForum
	 **/
	private function fudforum($username, $email, $password)
	{
		$success = false;
		$single_md5_pass = md5( $password );
		$hash = $this->parent->_memberData['conv_password'];			
		
		if (strlen($hash) == 40)
		{
			$success = (sha1($this->parent->_memberData['misc'] . sha1($password)) === $hash) ? true : false;
		}
		else
		{
			$success = ($single_md5_pass === $hash) ? true : false;
		}

		return $success;
	}

	/**
	 * Ikonboard
	 **/
	private function ikonboard($username, $email, $password)
	{
		if ( $this->parent->_memberData['conv_password'] == crypt( $password, $this->parent->_memberData['misc'] ) )
		{
			return true;
		}
		else if ( $this->parent->_memberData['conv_password'] == md5( $password . $username ) )
		{
			return true;
		}
		else
		{
			return false;

		}
	} 

	/**
	 * Joomla!
	 */
	private function joomla ( $username, $email, $password )
	{
		if ( $this->parent->_memberData['conv_password'] == md5 ( $password . $this->parent->_memberData['misc'] ) )
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Kunena
	 */
	private function kunena ( $username, $email, $password )
	{
		// Kunena authenticates using internal Joomla functions.
		// This is required, however, if the member only converts from
		// Kunena and not Joomla + Kunena.
		return $this->joomla ( $username, $email, $password );
	}
	
	/**
	 * PHPBB
	 **/
	private function phpbb($username, $email, $password)
	{
		//$password = $_POST['password'] ? $_POST['password'] : $password;
		
		$password = html_entity_decode( $password );
		
		$success = false;
		$single_md5_pass = md5( $password );
		$hash = $this->parent->_memberData['conv_password'];
		
		$itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
		
		if (strlen($hash) == 34)
		{
			$success = ($this->parent->hashCryptPrivate($password, $hash, $itoa64) === $hash) ? true : false;
		}
		else
		{
			$success = ($single_md5_pass === $hash) ? true : false;
		}

		return $success;
	}
	
	/**
	 * PHPBB Legacy (2.x)
	 **/
	private function phpbb_legacy($username, $email, $password)
	{
		return $this->phpbb($username, $email, $password);
	}
	
	/**
	 * vBulletin 4
	 **/
	private function vbulletin($username, $email, $password)
	{
		if ($this->parent->_memberData['conv_password'] == md5(md5(str_replace('&#39;', "'", $password)) . $this->parent->_memberData['misc']))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * vBulletin Legacy (3.8)
	 **/
	private function vbulletin_legacy($username, $email, $password)
	{
		return $this->vbulletin($username, $email, $password);
	}
	
	/**
	 * vBulletin Legacy (3.6)
	 **/
	private function vbulletin_legacy36($username, $email, $password)
	{
		return $this->vbulletin($username, $email, $password);
	}
	
	/**
	 * MyBB
	 **/
	private function mybb($username, $email, $password)
	{
		if ($this->parent->_memberData['conv_password'] == md5(md5($this->parent->_memberData['misc']) . md5($password)))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * SMF
	 **/
	private function smf($username, $email, $password)
	{
		if($this->parent->_memberData['conv_password'] == sha1(strtolower($username) . html_entity_decode($password)))
		{
			return true;
		}
		else if($this->parent->_memberData['conv_password'] == sha1(strtolower($username) . $password))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * SMF LEGACY
	 **/
	private function smf_legacy($username, $email, $password)
	{
		if(sha1(strtolower($username) . $password) == $this->parent->_memberData['conv_password'])
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * WOLTLAB
	 **/
	private function woltlab($username, $email, $password)
	{
		if ( $this->parent->_memberData['conv_password'] == sha1( $this->parent->_memberData['misc'] . sha1( $this->parent->_memberData['misc'] . sha1($password) ) ) )
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * WebWiz 8.X authentication
	 *
	 * @access	private
	 * @param	string		Username
	 * @param	string		Password
	 * @return	boolean		Successful
	 */
	private function webwiz( $username, $email, $password )
	{
		$success 			= false;

		if ( webWizAuth::HashEncode($password . $this->parent->_memberData['misc']) == $this->parent->_memberData['conv_password'] )
		{
			$success = true;
		}

		return $success;
	}
	
	/**
	 * XenForo authentication
	 *
	 * @access	private
	 * @param	string		Username
	 * @param	string		Email	  
	 * @param	string		Password
	 * @return	boolean		Successful
	 */
	private function xenforo( $username, $email, $password )
	{
		$password = html_entity_decode ( $password );
		if ( extension_loaded( 'hash' ) )
		{
			$hashedPassword = hash( 'sha256', hash( 'sha256', $password ) . $this->parent->_memberData['misc'] );
		}
		else
		{
			$hashedPassword = sha1( sha1( $password ) . $this->parent->_memberData['misc'] );
		}
		
		if ( $this->parent->_memberData['conv_password'] == $hashedPassword )
		{
			return true;
		}
		else
		{
			// No match. We may have converted from vBulletin > XF > IPB, so let's try there as a last ditch attempt.
			// Doesn't make sense to me but whatever.
			if ( $this->vbulletin( $username, $email, $password ) )
			{
				return true;
			}
			else
			{
				return false;
			}
		}
	}		

	/*
	 * CUSTOM - 607195
	 */
	 private function custom_607195($username, $email, $password)
	 {
	 	$pass_info = unserialize( $this->parent->_memberData['misc'] );

	 	$clef	  = $pass_info[0];
		$chaine	  = $pass_info[1];
		$pwd_base = $this->parent->_memberData['conv_password'];
	 	$pwd_base = $this->decrypterChaine($pwd_base,$clef,$chaine);
	 	
	 	if ( $pwd_base == $password )
	 	{
	 		return TRUE;
	 	}
	 	else
	 	{
	 		return FALSE;
	 	}
	 }
	 private function decrypterChaine($p_strChaine, $p_strClee, $p_strSubst)
	{
		$strChaine = $p_strChaine;
	
		$intLng    = strlen($strChaine);
		$intLngCle = strlen($p_strClee);
	
		$intCpt2   = 0;
	
		// On remplace les lettres par des chiffres
		for ($intCpt = 0; $intCpt < 10 ; $intCpt++)
		{
			$strChaine = str_replace(substr($p_strSubst, $intCpt, 1), $intCpt, $strChaine);
		}
	
		for ($intCpt = 0 ; $intCpt < $intLng ; $intCpt += 3)
		{
			$intCarStr = substr($strChaine, $intCpt, 3);
			$intCle    = ord(substr($p_strClee, $intCpt2, 1));
			$intCar    = $intCarStr ^ $intCle;
			$strCar    = chr($intCar);
	
			if (isset($strRes)) {
				$strRes = $strRes . $strCar;
				
			} else {
				$strRes = $strCar;
				
			}
	
			if ($intCpt2 >= $intLngCle) {
				$intCpt2 = 0;
				
			} else {
				$intCpt2++;
				
			}
		}
		return $strRes;
	}
	
	/**
	 * PHP-Fusion
	 */
	private function phpfusion($username, $email, $password)
	{
		return (bool) md5( md5( $password ) ) == $this->parent->_memberData['conv_password'];
	}
	
	/**
	 * punBB
	 */
	private function fluxbb($username, $email, $password)
	{
		$success = false;
		$hash = $this->parent->_memberData['conv_password'];
		
		if ( strlen($hash) == 40 )
		{
			if ( $hash == sha1($this->parent->_memberData['misc'].sha1($password)) )
			{
				$success = true;
			}
			elseif ( $hash == sha1($password) )
			{
				$success = true;
			}
		}
		else
		{
			$success = ( md5($password) == $hash ) ? true : false;
		}
		
		return $success;
	}
	
	/**
	 * punBB
	 */
	private function punbb($username, $email, $password)
	{
		$success = false;
		$hash = $this->parent->_memberData['conv_password'];
		
		if ( strlen($hash) == 40 )
		{
			if ( $hash == sha1($this->parent->_memberData['misc'].sha1($password)) )
			{
				$success = true;
			}
			elseif ( $hash == sha1($password) )
			{
				$success = true;
			}
		}
		else
		{
			$success = ( md5($password) == $hash ) ? true : false;
		}
		
		return $success;
	}
	
	/**
	 * SimplePress Forum (link for wordpress)
	 */
	private function simplepress($username, $email, $password)
	{
		return $this->wordpress($username, $email, $password);
	}
	
	
	
	private function ubbthreads($username, $email, $password)
	{
		$hash	= $this->parent->_memberData['members_pass_hash'];
		$salt		= $this->parent->_memberData['members_pass_salt'];
	
		if ( md5( $password ) == $hash )
		{
			return true;
		}
		
		// Not using md5, UBB salts the password with the password
		// IPB already md5'd it though, *sigh*
		if ( md5( md5( $salt ) . crypt( $password, $password ) ) == $hash )
		{
			return true;
		}
		
		// Now standard IPB check.
		if ( md5( md5( $salt ) . md5( $password ) ) == $hash )
		{
			return true;
		}
		
		return false;
	}
	
	/**
	 * Wordpress (Blog)
	 */
	private function wordpress($username, $email, $password)
	{
		$success = FALSE;
		
		$password = ( trim($_POST['password']) != '' ) ? trim($_POST['password']) : $password;
		
		// If the hash is still md5...
		if ( strlen($this->parent->_memberData['conv_password']) <= 32 )
		{
			$success = ( $this->parent->_memberData['conv_password'] == md5($password) ) ? TRUE : FALSE;
		}
		// New pass hash check
		else
		{
			// Init the pass class
			$ph = new PasswordHash(8, TRUE);
			
			// Check it
			$success = $ph->CheckPassword($password, $this->parent->_memberData['conv_password']) ? TRUE : FALSE;
		}
		
		return $success;
	}
}

/**
 * Portable PHP password hashing framework.
 * @package phpass
 * @since 2.5
 * @version 0.1
 * @link http://www.openwall.com/phpass/
 */

#
# Written by Solar Designer <solar at openwall.com> in 2004-2006 and placed in
# the public domain.
#
# There's absolutely no warranty.
#
# Please be sure to update the Version line if you edit this file in any way.
# It is suggested that you leave the main version number intact, but indicate
# your project name (after the slash) and add your own revision information.
#
# Please do not change the "private" password hashing method implemented in
# here, thereby making your hashes incompatible.  However, if you must, please
# change the hash type identifier (the "$P$") to something different.
#
# Obviously, since this code is in the public domain, the above are not
# requirements (there can be none), but merely suggestions.
#

// This is needed for the SimplePress Forum login and for any other login based on this class (wordpress, bbpress, etc)
class PasswordHash {
	var $itoa64;
	var $iteration_count_log2;
	var $portable_hashes;
	var $random_state;

	function PasswordHash($iteration_count_log2, $portable_hashes)
	{
		$this->itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

		if ($iteration_count_log2 < 4 || $iteration_count_log2 > 31)
			$iteration_count_log2 = 8;
		$this->iteration_count_log2 = $iteration_count_log2;

		$this->portable_hashes = $portable_hashes;

		$this->random_state = microtime() . (function_exists('getmypid') ? getmypid() : '') . uniqid(rand(), TRUE);

	}

	function get_random_bytes($count)
	{
		$output = '';
		if (($fh = @fopen('/dev/urandom', 'rb'))) {
			$output = fread($fh, $count);
			fclose($fh);
		}

		if (strlen($output) < $count) {
			$output = '';
			for ($i = 0; $i < $count; $i += 16) {
				$this->random_state =
				    md5(microtime() . $this->random_state);
				$output .=
				    pack('H*', md5($this->random_state));
			}
			$output = substr($output, 0, $count);
		}

		return $output;
	}

	function encode64($input, $count)
	{
		$output = '';
		$i = 0;
		do {
			$value = ord($input[$i++]);
			$output .= $this->itoa64[$value & 0x3f];
			if ($i < $count)
				$value |= ord($input[$i]) << 8;
			$output .= $this->itoa64[($value >> 6) & 0x3f];
			if ($i++ >= $count)
				break;
			if ($i < $count)
				$value |= ord($input[$i]) << 16;
			$output .= $this->itoa64[($value >> 12) & 0x3f];
			if ($i++ >= $count)
				break;
			$output .= $this->itoa64[($value >> 18) & 0x3f];
		} while ($i < $count);

		return $output;
	}

	function gensalt_private($input)
	{
		$output = '$P$';
		$output .= $this->itoa64[min($this->iteration_count_log2 +
			((PHP_VERSION >= '5') ? 5 : 3), 30)];
		$output .= $this->encode64($input, 6);

		return $output;
	}

	function crypt_private($password, $setting)
	{
		$output = '*0';
		if (substr($setting, 0, 2) == $output)
			$output = '*1';

		if (substr($setting, 0, 3) != '$P$')
			return $output;

		$count_log2 = strpos($this->itoa64, $setting[3]);
		if ($count_log2 < 7 || $count_log2 > 30)
			return $output;

		$count = 1 << $count_log2;

		$salt = substr($setting, 4, 8);
		if (strlen($salt) != 8)
			return $output;

		# We're kind of forced to use MD5 here since it's the only
		# cryptographic primitive available in all versions of PHP
		# currently in use.  To implement our own low-level crypto
		# in PHP would result in much worse performance and
		# consequently in lower iteration counts and hashes that are
		# quicker to crack (by non-PHP code).
		if (PHP_VERSION >= '5') {
			$hash = md5($salt . $password, TRUE);
			do {
				$hash = md5($hash . $password, TRUE);
			} while (--$count);
		} else {
			$hash = pack('H*', md5($salt . $password));
			do {
				$hash = pack('H*', md5($hash . $password));
			} while (--$count);
		}

		$output = substr($setting, 0, 12);
		$output .= $this->encode64($hash, 16);

		return $output;
	}

	function gensalt_extended($input)
	{
		$count_log2 = min($this->iteration_count_log2 + 8, 24);
		# This should be odd to not reveal weak DES keys, and the
		# maximum valid value is (2**24 - 1) which is odd anyway.
		$count = (1 << $count_log2) - 1;

		$output = '_';
		$output .= $this->itoa64[$count & 0x3f];
		$output .= $this->itoa64[($count >> 6) & 0x3f];
		$output .= $this->itoa64[($count >> 12) & 0x3f];
		$output .= $this->itoa64[($count >> 18) & 0x3f];

		$output .= $this->encode64($input, 3);

		return $output;
	}

	function gensalt_blowfish($input)
	{
		# This one needs to use a different order of characters and a
		# different encoding scheme from the one in encode64() above.
		# We care because the last character in our encoded string will
		# only represent 2 bits.  While two known implementations of
		# bcrypt will happily accept and correct a salt string which
		# has the 4 unused bits set to non-zero, we do not want to take
		# chances and we also do not want to waste an additional byte
		# of entropy.
		$itoa64 = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

		$output = '$2a$';
		$output .= chr(ord('0') + $this->iteration_count_log2 / 10);
		$output .= chr(ord('0') + $this->iteration_count_log2 % 10);
		$output .= '$';

		$i = 0;
		do {
			$c1 = ord($input[$i++]);
			$output .= $itoa64[$c1 >> 2];
			$c1 = ($c1 & 0x03) << 4;
			if ($i >= 16) {
				$output .= $itoa64[$c1];
				break;
			}

			$c2 = ord($input[$i++]);
			$c1 |= $c2 >> 4;
			$output .= $itoa64[$c1];
			$c1 = ($c2 & 0x0f) << 2;

			$c2 = ord($input[$i++]);
			$c1 |= $c2 >> 6;
			$output .= $itoa64[$c1];
			$output .= $itoa64[$c2 & 0x3f];
		} while (1);

		return $output;
	}

	function HashPassword($password)
	{
		$random = '';

		if (CRYPT_BLOWFISH == 1 && !$this->portable_hashes) {
			$random = $this->get_random_bytes(16);
			$hash =
			    crypt($password, $this->gensalt_blowfish($random));
			if (strlen($hash) == 60)
				return $hash;
		}

		if (CRYPT_EXT_DES == 1 && !$this->portable_hashes) {
			if (strlen($random) < 3)
				$random = $this->get_random_bytes(3);
			$hash =
			    crypt($password, $this->gensalt_extended($random));
			if (strlen($hash) == 20)
				return $hash;
		}

		if (strlen($random) < 6)
			$random = $this->get_random_bytes(6);
		$hash =
		    $this->crypt_private($password,
		    $this->gensalt_private($random));
		if (strlen($hash) == 34)
			return $hash;

		# Returning '*' on error is safe here, but would _not_ be safe
		# in a crypt(3)-like function used _both_ for generating new
		# hashes and for validating passwords against existing hashes.
		return '*';
	}

	function CheckPassword($password, $stored_hash)
	{
		$hash = $this->crypt_private($password, $stored_hash);
		if ($hash[0] == '*')
			$hash = crypt($password, $stored_hash);

		return $hash == $stored_hash;
	}
}

class webWizAuth {
	static public function HashEncode($strSecret)
	{
	    if (strlen($strSecret) == 0 || strlen($strSecret) >= pow(2,61)) {
			return  "0000000000000000000000000000000000000000";
			break;
	    }

		//Initial Hex words are used for encoding Digest.  
		//These can be any valid 8-digit hex value (0 to F)
		$strH[0]="FB0C14C2";
		$strH[1]="9F00AB2E";
		$strH[2]="991FFA67";
		$strH[3]="76FA2C3F";
		$strH[4]="ADE426FA";

		for ($intPos=1; $intPos<=strlen($strSecret); $intPos=$intPos+56)
		{
			$strEncode=substr($strSecret,$intPos-1,56); //get 56 character chunks
			$strEncode= self::WordToBinary($strEncode); //convert to binary
			$strEncode= self::PadBinary($strEncode); //make it 512 bites
			$strEncode= self::BlockToHex($strEncode); //convert to hex value

			//Encode the hex value using the previous runs digest
			//If it is the first run then use the initial values above
			$strEncode= self::DigestHex($strEncode,$strH[0],$strH[1],$strH[2],$strH[3],$strH[4]);

			//Combine the old digest with the new digest
			$strH[0]= self::HexAdd(substr($strEncode,0,8),$strH[0]);
			$strH[1]= self::HexAdd(substr($strEncode,8,8),$strH[1]);
			$strH[2]= self::HexAdd(substr($strEncode,16,8),$strH[2]);
			$strH[3]= self::HexAdd(substr($strEncode,24,8),$strH[3]);
			$strH[4]= self::HexAdd(substr($strEncode,strlen($strEncode)-(8)),$strH[4]);
		}

		//This is the final Hex Digest
		$function_ret=$strH[0].$strH[1].$strH[2].$strH[3].$strH[4];

		return $function_ret;
	}
	
	private function HexToBinary($btHex)
	{
		switch ($btHex)
		{
			case "0":
			  $function_ret="0000";
			  break;
			case "1":
			  $function_ret="0001";
			  break;
			case "2":
			  $function_ret="0010";
			  break;
			case "3":
			  $function_ret="0011";
			  break;
			case "4":
			  $function_ret="0100";
			  break;
			case "5":
			  $function_ret="0101";
			  break;
			case "6":
			  $function_ret="0110";
			  break;
			case "7":
			  $function_ret="0111";
			  break;
			case "8":
			  $function_ret="1000";
			  break;
			case "9":
			  $function_ret="1001";
			  break;
			case "A":
			  $function_ret="1010";
			  break;
			case "B":
			  $function_ret="1011";
			  break;
			case "C":
			  $function_ret="1100";
			  break;
			case "D":
			  $function_ret="1101";
			  break;
			case "E":
			  $function_ret="1110";
			  break;
			case "F":
			  $function_ret="1111";
			  break;
			default:

			  $function_ret="2222";
			  break;
		}
		return $function_ret;
	}

	private function BinaryToHex($strBinary)
	{
		switch ($strBinary)
		{
			case "0000":
			  $function_ret="0";
			  break;
			case "0001":
			  $function_ret="1";
			  break;
			case "0010":
			  $function_ret="2";
			  break;
			case "0011":
			  $function_ret="3";
			  break;
			case "0100":
			  $function_ret="4";
			  break;
			case "0101":
			  $function_ret="5";
			  break;
			case "0110":
			  $function_ret="6";
			  break;
			case "0111":
			  $function_ret="7";
			  break;
			case "1000":
			  $function_ret="8";
			  break;
			case "1001":
			  $function_ret="9";
			  break;
			case "1010":
			  $function_ret="A";
			  break;
			case "1011":
			  $function_ret="B";
			  break;
			case "1100":
			  $function_ret="C";
			  break;
			case "1101":
			  $function_ret="D";
			  break;
			case "1110":
			  $function_ret="E";
			  break;
			case "1111":
			  $function_ret="F";
			  break;
			default:

			  $function_ret="Z";
			  break;
		}
	  return $function_ret;
	}

	private function WordToBinary($strWord)
	{
		$strBinary = '';
		for ($intPos=1; $intPos<=strlen($strWord); $intPos=$intPos+1)
		{
			$strTemp=substr($strWord,intval($intPos)-1,1);
			$strBinary=$strBinary . self::IntToBinary(ord($strTemp));
		}

		return $strBinary;
	}

	private function IntToBinary($intNum)
	{
		$intNew=$intNum;
		$strBinary='';
		while($intNew>1)
		{
			$dblNew=doubleval($intNew)/2;
			$intNew=round(doubleval($dblNew) - 0.1, 0);
			if (doubleval($dblNew)==doubleval($intNew))
			{
			  $strBinary="0".$strBinary;
			} else {
			  $strBinary="1".$strBinary;
			}
		}

		$strBinary=$intNew.$strBinary;
		$intTemp=strlen($strBinary)%8;

		for ($intNew=$intTemp; $intNew<=7; $intNew=$intNew+1)
		{
			$strBinary="0".$strBinary;
		}

		return $strBinary;
	}

	private function PadBinary($strBinary)
	{
		$intLen=strlen($strBinary);
		$strBinary=$strBinary."1";

		for ($intPos=strlen($strBinary); $intPos<=447; $intPos=$intPos+1)
		{
			$strBinary=$strBinary."0";
		}

		$strTemp= self::IntToBinary($intLen);

		for ($intPos=strlen($strTemp); $intPos<=63; $intPos=$intPos+1)
		{
			$strTemp="0".$strTemp;
		}

		return $strBinary.$strTemp;
	}

	private function BlockToHex($strBinary)
	{
		$strHex = '';
		for ($intPos=1; $intPos<=strlen($strBinary); $intPos=$intPos+4)
		{
			$strHex=$strHex . self::BinaryToHex(substr($strBinary,$intPos-1,4));
		}
		return $strHex;
	}

	private function DigestHex($strHex,$strH0,$strH1,$strH2,$strH3,$strH4)
	{
		//Constant hex words are used for encryption, these can be any valid 8 digit hex value
		$strK[0]="5A827999";
		$strK[1]="6ED9EBA1";
		$strK[2]="8F1BBCDC";
		$strK[3]="CA62C1D6";

		//Hex words are used in the encryption process, these can be any valid 8 digit hex value
		$strH[0]=$strH0;
		$strH[1]=$strH1;
		$strH[2]=$strH2;
		$strH[3]=$strH3;
		$strH[4]=$strH4;

		//divide the Hex block into 16 hex words
		for ($intPos=0; $intPos<=(strlen($strHex)/8)-1; $intPos=$intPos+1)
		{
			$strWords[intval($intPos)]=substr($strHex,(intval($intPos)*8)+1-1,8);
		}

		//encode the Hex words using the constants above
		//innitialize 80 hex word positions
		for ($intPos=16; $intPos<=79; $intPos=$intPos+1)
		{
			$strTemp=$strWords[intval($intPos)-3];
			$strTemp1= self::HexBlockToBinary($strTemp);
			$strTemp=$strWords[intval($intPos)-8];
			$strTemp2= self::HexBlockToBinary($strTemp);
			$strTemp=$strWords[intval($intPos)-14];
			$strTemp3= self::HexBlockToBinary($strTemp);
			$strTemp=$strWords[intval($intPos)-16];
			$strTemp4= self::HexBlockToBinary($strTemp);
			$strTemp= self::BinaryXOR($strTemp1,$strTemp2);
			$strTemp= self::BinaryXOR($strTemp,$strTemp3);
			$strTemp= self::BinaryXOR($strTemp,$strTemp4);
			$strWords[intval($intPos)]= self::BlockToHex( self::BinaryShift($strTemp,1));
		}

		//initialize the changing word variables with the initial word variables
		$strA[0]=$strH[0];
		$strA[1]=$strH[1];
		$strA[2]=$strH[2];
		$strA[3]=$strH[3];
		$strA[4]=$strH[4];

		//Main encryption loop on all 80 hex word positions
		for ($intPos=0; $intPos<=79; $intPos=$intPos+1)
		{
			$strTemp= self::BinaryShift( self::HexBlockToBinary($strA[0]),5);
			$strTemp1= self::HexBlockToBinary($strA[3]);
			$strTemp2= self::HexBlockToBinary($strWords[intval($intPos)]);
			
			switch ($intPos)
			{
				case 0:
				case 1:
				case 2:
				case 3:
				case 4:
				case 5:
				case 6:
				case 7:
				case 8:
				case 9:
				case 10:
				case 11:
				case 12:
				case 13:
				case 14:
				case 15:
				case 16:
				case 17:
				case 18:
				case 19:
					$strTemp3= self::HexBlockToBinary($strK[0]);
					$strTemp4= self::BinaryOR( self::BinaryAND( self::HexBlockToBinary($strA[1]),
					self::HexBlockToBinary($strA[2])), self::BinaryAND( self::BinaryNOT( self::HexBlockToBinary($strA[1])),
					self::HexBlockToBinary($strA[3])));
					break;
				case 20:
				case 21:
				case 22:
				case 23:
				case 24:
				case 25:
				case 26:
				case 27:
				case 28:
				case 29:
				case 30:
				case 31:
				case 32:
				case 33:
				case 34:
				case 35:
				case 36:
				case 37:
				case 38:
				case 39:
					$strTemp3= self::HexBlockToBinary($strK[1]);
					$strTemp4= self::BinaryXOR( self::BinaryXOR( self::HexBlockToBinary($strA[1]),
					self::HexBlockToBinary($strA[2])), self::HexBlockToBinary($strA[3]));
					break;
				case 40:
				case 41:
				case 42:
				case 43:
				case 44:
				case 45:
				case 46:
				case 47:
				case 48:
				case 49:
				case 50:
				case 51:
				case 52:
				case 53:
				case 54:
				case 55:
				case 56:
				case 57:
				case 58:
				case 59:
					$strTemp3= self::HexBlockToBinary($strK[2]);
					$strTemp4= self::BinaryOR( self::BinaryOR( self::BinaryAND( self::HexBlockToBinary($strA[1]),
					self::HexBlockToBinary($strA[2])), self::BinaryAND( self::HexBlockToBinary($strA[1]),
					self::HexBlockToBinary($strA[3]))), self::BinaryAND( self::HexBlockToBinary($strA[2]),
					self::HexBlockToBinary($strA[3])));
					break;
				case 60:
				case 61:
				case 62:
				case 63:
				case 64:
				case 65:
				case 66:
				case 67:
				case 68:
				case 69:
				case 70:
				case 71:
				case 72:
				case 73:
				case 74:
				case 75:
				case 76:
				case 77:
				case 78:
				case 79:
					$strTemp3= self::HexBlockToBinary($strK[3]);
					$strTemp4= self::BinaryXOR( self::BinaryXOR( self::HexBlockToBinary($strA[1]),
					self::HexBlockToBinary($strA[2])), self::HexBlockToBinary($strA[3]));
					break;
			}

			$strTemp= self::BlockToHex($strTemp);
			$strTemp1=self::BlockToHex($strTemp1);
			$strTemp2= self::BlockToHex($strTemp2);
			$strTemp3= self::BlockToHex($strTemp3);
			$strTemp4= self::BlockToHex($strTemp4);

			$strTemp= self::HexAdd($strTemp,$strTemp1);
			$strTemp= self::HexAdd($strTemp,$strTemp2);
			$strTemp= self::HexAdd($strTemp,$strTemp3);
			$strTemp= self::HexAdd($strTemp,$strTemp4);

			$strA[4]=$strA[3];
			$strA[3]=$strA[2];
			$strA[2]= self::BlockToHex( self::BinaryShift( self::HexBlockToBinary($strA[1]),30) );
			$strA[1]=$strA[0];
			$strA[0]=$strTemp;
		}


		//Concatenate the final Hex Digest
		return $strA[0].$strA[1].$strA[2].$strA[3].$strA[4];
	}

	private function HexAdd($strHex1,$strHex2)
	{
		$n1 = hexdec($strHex1);
		$n2 = hexdec($strHex2);
		$sum = $n1 + $n2;
		return sprintf("%08X", $sum);
	}

	private function BinaryShift($strBinary,$intPos)
	{
	  return substr($strBinary,strlen($strBinary)-(strlen($strBinary)-intval($intPos))).
	    substr($strBinary,0,intval($intPos));
	}

	// Function performs an exclusive or function on each position of two binary values
	private function BinaryXOR($strBin1,$strBin2)
	{
		$strBinaryFinal = '';
		for ($intPos=1; $intPos<=strlen($strBin1); $intPos=$intPos+1)
		{
			switch (substr($strBin1,intval($intPos)-1,1))
			{
				case substr($strBin2, intval($intPos)-1, 1):
					$strBinaryFinal=$strBinaryFinal."0";
					break;
				default:
					$strBinaryFinal=$strBinaryFinal."1";
					break;
			}
		}

	  return $strBinaryFinal;
	}

	// Function performs an inclusive or function on each position of two binary values
	private function BinaryOR($strBin1,$strBin2)
	{
		$strBinaryFinal = '';
		for ($intPos=1; $intPos<=strlen($strBin1); $intPos=$intPos+1)
		{
			if (substr($strBin1,intval($intPos)-1,1)=="1" || substr($strBin2,intval($intPos)-1,1)=="1")
			{
				$strBinaryFinal=$strBinaryFinal."1";
			} else {
				$strBinaryFinal=$strBinaryFinal."0";
			}
		}

	  return $strBinaryFinal;
	}
	
	// Function performs an AND function on each position of two binary values
	private function BinaryAND($strBin1,$strBin2)
	{
		$strBinaryFinal = '';
		for ($intPos=1; $intPos<=strlen($strBin1); $intPos=$intPos+1)
		{
			if (substr($strBin1,intval($intPos)-1,1)=="1" && substr($strBin2,intval($intPos)-1,1)=="1")
			{
				$strBinaryFinal=$strBinaryFinal."1";
			} else {
				$strBinaryFinal=$strBinaryFinal."0";
			}
		}
		
		return $strBinaryFinal;
	}
	
	// Function makes each position of a binary value from 1 to 0 and 0 to 1
	private function BinaryNOT($strBinary)
	{

		$strBinaryFinal = '';
		for ($intPos=1; $intPos<=strlen($strBinary); $intPos=$intPos+1)
		{
			if (substr($strBinary,intval($intPos)-1,1)=="1")
			{
			$strBinaryFinal=$strBinaryFinal."0";
			} else {
				$strBinaryFinal=$strBinaryFinal."1";
			}
		}

		return $strBinaryFinal;
	}

	// Function Converts a 8 digit/32 bit hex value to its 32 bit binary equivalent
	private function HexBlockToBinary($strHex)
	{
		$strTemp = '';
		for ($intPos=1; $intPos<=strlen($strHex); $intPos=$intPos+1)
		{
			$strTemp=$strTemp . self::HexToBinary(substr($strHex,intval($intPos)-1,1));
		}

		return $strTemp;
	}
}