<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Skin Functions
 * Last Updated: $Date: 2011-05-05 12:03:47 +0100 (Thu, 05 May 2011) $
 * </pre>
 *
 * Owner: Matt
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		9th March 2005 11:03
 * @version		$Revision: 8644 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class skinGenerator extends skinCaching
{
	/**#@+
	 * Registry objects
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	/**#@-*/
	
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
		parent::__construct( $registry );
	}
	
	/**
	 * Return JSON payload URL
	 * @return string Skin Generator URL
	 */
	public function getJsonUrl()
	{
		$inDev = ( IN_DEV ) ? 1 : 0;
		
		return 'http://ips-skin-gen.invisionpower.com/index.php?v=' . IPB_LONG_VERSION . '&k=' . urlencode( $this->settings['ipb_reg_number'] ) . '&i=' . $inDev;
	}
	
	/**
	 * Delete a user's session
	 * @param int $skinSetId
	 */
	public function convertToFull( $skinSetId )
	{
		/* Current session? */
		$session = $this->getSessionBySkinId( $skinSetId );
		
		if ( $session['sg_member_id'] )
		{
			$this->deleteUserSession( $session['sg_member_id'] );
		}
		
		/* Tweak skin */
		$this->DB->update( 'skin_collections', array( 'set_by_skin_gen' => 0 ), 'set_id=' . $skinSetId );
	}
	
	
	/**
	 * Check to make sure we're GTG ...
	 */
	public function healthCheck()
	{
		$warnings = array();
		
		/* Can write into images */
		if ( ! is_writable( DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/style_images' ) )
		{
			$warnings[] = 'Cannot write to image directory';
		}
		
		return ( count( $warnings ) ) ? $warnings : true;
	}
	
	/**
	 * Fetches the JSON from the remote server and writes it
	 */
	public function buildLocalJsonCache()
	{
		/* Now fetch JSON data from the server and write it */
		require_once( IPS_KERNEL_PATH . 'classFileManagement.php' );/*noLibHook*/
		$files = new classFileManagement();
		
		$json = $files->getFileContents( $this->getJsonUrl() );
				
		if ( $json )
		{
			$cacheFile = IPS_CACHE_PATH . 'cache/skinGenJsonCache.js';
			
			if ( file_exists( $cacheFile ) )
			{
				@unlink( $cacheFile );
			}
			
			file_put_contents( $cacheFile, $json );
			@chmod( $cacheFile, 0777 );
		}
		else
		{
			throw new Exception( "JSON_NOT_RETURNED");
		}
		
		return true;
	}
	
	/**
	 * Saves the skin and that
	 * @param	array	css, storedSettings, storedClasses
	 * @param	int		Member Id
	 */
	public function save( array $data, $memberId )
	{
		$session = $this->getUserSession( $memberId );
		
		if ( empty( $session['sg_session_id'] ) )
		{
			throw new Exception( 'NO_SESSION_FOUND' );
		}
		
		/* Update session */
		$this->DB->update( 'skin_collections', array( 'set_skin_gen_data' => serialize( $data ) ), 'set_id=\'' . $session['sg_skin_set_id'] . '\''  );
		
		/* Write CSS_EXISTS extras css */
		try
		{
			$this->saveCSSFromAdd( $session['sg_skin_set_id'], trim( $data['css'] ), 'ipb_skingen', 999, '', 'core' );
		}
		catch( Exception $e )
		{
			if ( $e->getMessage() == 'CSS_EXISTS' )
			{
				$css = $this->fetchCSS( $session['sg_skin_set_id'] );
				$this->saveCSSFromEdit( $css['ipb_skingen']['css_id'], $session['sg_skin_set_id'], trim( $data['css'] ), 'ipb_skingen', 999, '', 'core' );
			}
		}
		
		/* Update replacements */
		$replacements = $this->fetchReplacements( $session['sg_skin_set_id'] );
		
		/* Set transparent logo */
		$this->saveReplacementFromEdit( $replacements['logo_img']['replacement_id'], $session['sg_skin_set_id'], '{style_image_url}/logo_transparent.png', 'logo_img' );
		
		/* Flag for rebuild */
		$this->flagSetForRecache( $session['sg_skin_set_id'] );
		
		/* Delete session */
		$this->deleteUserSession( $memberId );
	}
	
	/**
	 * Resets the member's skin
	 * @param	int		Member Id
	 */
	public function resetMemberAndSwitchSkin( $memberId )
	{
		$session = $this->getUserSession( $memberId );
		
		if ( empty( $session['sg_session_id'] ) )
		{
			throw new Exception( 'NO_SESSION_FOUND' );
		}
		
		IPSMember::save( $memberId, array( 'core' => array( 'bw_using_skin_gen' => 0, 'skin' => $session['sg_skin_set_id'] ) ) );
	}
	
	/**
	 * Set a user's session
	 * @param int $memberId
	 * @param array 
	 */
	public function setUserSession( $memberId, $data )
	{
		if ( ! empty( $memberId ) AND ! empty( $data['skin_set_id'] ) )
		{
			$sessionKey = md5( uniqid() );
			
			$this->deleteUserSession( $memberId );
			
			if ( empty( $data['set_skin_gen_data'] ) )
			{
				$skin = $this->fetchSkinData( $data['skin_set_id'] );
				
				$data['set_skin_gen_data'] = $skin['set_skin_gen_data'];
			}
			
			$this->DB->insert( 'skin_generator_sessions', array( 'sg_session_id'  => $sessionKey,
																 'sg_member_id'   => $memberId,
																 'sg_skin_set_id' => $data['skin_set_id'],
																 'sg_date_start'  => IPS_UNIX_TIME_NOW,
																 'sg_data'		  => ( is_array( $data ) ) ? serialize( $data ) : $data  ) );
			
			/* Flag user */
			IPSMember::save( $memberId, array( 'core' => array( 'bw_using_skin_gen' => 1 ) ) );
			
			return $sessionKey;
		}
		else
		{		
			return false;
		}
	}
	
	/**
	 * Delete a user's session
	 * @param int $memberId
	 * @param null 
	 */
	public function deleteUserSession( $memberId )
	{
		$this->DB->delete( 'skin_generator_sessions', 'sg_member_id=' . intval( $memberId ) );
		
		/* Flag user */
		IPSMember::save( $memberId, array( 'core' => array( 'bw_using_skin_gen' => 0 ) ) );
	}
	
	/**
	 * Get a user's session
	 * @param int $memberId
	 */
	public function getUserSession( $memberId )
	{
		$session = $this->DB->buildAndFetch( array( 'select' => '*',
													'from'   => 'skin_generator_sessions',
													'where'  => 'sg_member_id=' . intval( $memberId ) ) );
		
		if ( ! empty( $session['sg_session_id'] ) )
		{
			if ( IPSLib::isSerialized( $session['sg_data'] ) )
			{
				$session['sg_data_array'] = unserialize( $session['sg_data'] );
				
				if ( IPSLib::isSerialized( $session['sg_data_array']['set_skin_gen_data'] ) )
				{
					$session['skin_gen_data'] = unserialize( $session['sg_data_array']['set_skin_gen_data'] );
				}
			}
				
			return $session;
		}
		else
		{
			/* Prevent this from loading again */
			IPSMember::save( $memberId, array( 'core' => array( 'bw_using_skin_gen' => 0 ) ) );
			
			return false;
		}
	}
	
	/**
	 * Get a user's session
	 * @param int $skinSetId
	 */
	public function getSessionBySkinId( $skinSetId )
	{
		$session = $this->DB->buildAndFetch( array( 'select' => '*',
													'from'   => 'skin_generator_sessions',
													'where'  => 'sg_skin_set_id=' . intval( $skinSetId ) ) );
		
		if ( ! empty( $session['sg_session_id'] ) )
		{
			if ( IPSLib::isSerialized( $session['sg_data'] ) )
			{
				$session['sg_data_array'] = unserialize( $session['sg_data'] );
				
				if ( IPSLib::isSerialized( $session['sg_data_array']['set_skin_gen_data'] ) )
				{
					$session['skin_gen_data'] = unserialize( $session['sg_data_array']['set_skin_gen_data'] );
				}
			}
				
			return $session;
		}
		else
		{
			return false;
		}
	}
	
	
}