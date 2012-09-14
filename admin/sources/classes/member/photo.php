<?php
/**
 * <pre>
 * Photo routines
 * Last Updated: $Date: 2012-05-11 11:17:52 -0400 (Fri, 11 May 2012) $
 * </pre>
 *
 * @author		$author$
 * @copyright	(c) 2001 - 2011 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @author		MattMecham
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10727 $ 
 */

class classes_member_photo
{
	/**
	 * Registry Object Shortcuts
	 * 
	 * @var	object
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
	
	/**
	 * CONSTRUCTOR
	 *
	 * @param	object	Registry
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make registry objects */
		$this->registry		=  $registry;
		$this->DB			=  $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->lang			=  $this->registry->getClass('class_localization');
		$this->member		=  $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		=  $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
	}
	
	/**
	 * Save photo
	 * @param	array	member
	 * @param	string	Phototype
	 */
	public function save( $member, $photoType, $gravatar='', $url='' )
	{
		/* Fetch member data */
		$member = IPSMember::buildDisplayData( IPSMember::load( $member['member_id'], 'all' ) );
		
		$photo       = array();
		$bwOptions	 = IPSBWOptions::thaw( $member['fb_bwoptions'], 'facebook' );
		$tcbwOptions = IPSBWOptions::thaw( $member['tc_bwoptions'], 'twitter' );
		$memBitwise  = IPSBWOptions::thaw( $member['members_bitoptions'], 'members' );
		
		/* Perm checks */
		if ( $photoType != 'facebook' && $photoType != 'twitter' )
		{
			if ( ! $member['g_edit_profile'] )
			{
				throw new Exception( 'PROFILE_DISABLED' );
			}
		}
		
		/* Whadda-we-doing? */
		switch( $photoType )
		{
			case 'url':
				$photo = $this->importPhoto( $url, $member['member_id'] );
				$photo['pp_photo_type'] = 'custom';
				$tcbwOptions['tc_s_pic'] = 0;
				$bwOptions['fbc_s_pic']	 = 0;
			break;
			case 'custom':
				$photo = $this->uploadPhoto( $member['member_id'] );
				$photo['pp_photo_type'] = 'custom';
				$tcbwOptions['tc_s_pic'] = 0;
				$bwOptions['fbc_s_pic']	 = 0;
			break;
			case 'gravatar':
				// We don't actually need to call setGravatar - since we're setting bw_disable_gravatar to 0, it will be shown automatically
				$tcbwOptions['tc_s_pic'] = 0;
				$bwOptions['fbc_s_pic']	 = 0;
				$memBitwise['bw_disable_gravatar'] = 0;
				$photo['pp_photo_type'] = 'gravatar';
				$photo['pp_gravatar']	= $gravatar;
			break;
			case 'twitter':
				$photo = $this->setTwitterPicture( $member );
				
				if ( $photo['final_location'] )
				{
					$tcbwOptions['tc_s_pic'] = 1;
					$bwOptions['fbc_s_pic']	 = 0;
					$photo['pp_photo_type'] = 'twitter';
				}
			break;
			case 'facebook':
				$photo = $this->setFacebookPicture( $member );
				
				if ( $photo['final_location'] )
				{
					$tcbwOptions['tc_s_pic'] = 0;
					$bwOptions['fbc_s_pic']	 = 1;
					$photo['pp_photo_type']  = 'facebook';
				}
			break;
		}

		if ( $photo['status'] == 'fail' )
		{
			throw new Exception( $photo['error'] );
		}
			
		$save = array( 'pp_main_photo'		=> $photo['final_location'],
  				   	   'pp_main_width'		=> intval($photo['final_width']),
					   'pp_main_height'		=> intval($photo['final_height']),
					   'pp_thumb_photo'		=> $photo['t_final_location'],
					   'pp_thumb_width'		=> intval($photo['t_final_width']),
					   'pp_thumb_height'	=> intval($photo['t_final_height']),
					   'pp_photo_type'		=> $photo['pp_photo_type'],
					   'pp_gravatar'		=> $photo['pp_gravatar'],
					   'pp_profile_update'  => IPS_UNIX_TIME_NOW,
					   'fb_photo'			=> '',
					   'fb_photo_thumb'		=> '',
					   'fb_bwoptions'		=> IPSBWOptions::freeze( $bwOptions, 'facebook' ),
					   'tc_photo'			=> '',
					   'tc_bwoptions'		=> IPSBWOptions::freeze( $tcbwOptions, 'twitter' ) );
			
		IPSMember::save( $member['member_id'], array( 'core' => array( 'members_bitoptions' => IPSBWOptions::freeze( $memBitwise, 'members' ) ), 'extendedProfile' => $save  ) );
		
		return $save;
	}
	
	/**
	 * Import URL
	 * Fetches a URL and stores it to disk
	 */
	public function importPhoto( $url, $member_id = 0 )
	{
		/* Init */
		$return		      = array( 'error'            => '',
								   'status'           => '' );
		$member_id        = $member_id ? intval($member_id) : intval( $this->memberData['member_id'] );
		$memberData		  = IPSMember::load( $member_id );
		$p_max    		  = ( IN_ACP ) ? 10000 : $memberData['photoMaxKb'];
		$tmp_name         = $member_id . time() . rand(0,100) . '.ipb';
		$fileExtension    = IPSText::getFileExtension( $url );
		
		/* Check */
		if ( ! $member_id )
		{
			return array( 'status' => 'cannot_find_member' );
		}
					
		/* Fix up upload directory */
		$paths       = $this->_getProfileUploadPaths();
		$upload_path = $paths['path'];
		$upload_dir  = $paths['dir'];
		
		/* Do we have permission to import? */
		if ( ! $this->settings['mem_photo_url'] )
		{
			$return['status'] = 'fail';
			$return['error']  = 'err_not_save_urlphoto';
			
			return $return;
		}
		
		/* Check for valid URL */
		if ( ! stristr( $url, 'http://' ) && ! stristr( $url, 'https://' ) )
		{
			$return['status'] = 'fail';
			$return['error']  = 'err_not_correct_url_format';
			
			return $return;
		}
		
		/* Fetch img class */
		require_once( IPS_KERNEL_PATH . 'classImage.php' );/*noLibHook*/
		$image = ips_kernel_image::bootstrap( 'gd' );
		
		/* Get the file managemnet class */
		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classFileManagement.php', 'classFileManagement' );
		$query = new $classToLoad();
		$query->timeout = 15;
		
		/* Query the service */
		$response = $query->getFileContents( $url );

		if ( ! $response )
		{
			$return['status'] = 'fail';
			$return['error']  = 'err_not_contact_server';
			
			return $return;
		}
		
		/* write to disk */
		if ( ! file_put_contents( $upload_path . '/' . $tmp_name, $response ) )
		{
			$return['status'] = 'fail';
			$return['error']  = 'err_not_save_urlphoto';
			
			return $return;
		}
		
		/* Perform some checks */
		if ( $image->hasXssInfile( $upload_path . '/' . $tmp_name ) )
		{
			@unlink( $upload_path . '/' . $tmp_name );
			
			$return['status'] = 'fail';
			$return['error']  = 'err_not_save_urlphoto';
			
			return $return;
		}
		
		/* Extract data */
		$data = $image->extractImageData( $upload_path . '/' . $tmp_name );
		
		/* If it's false, then unlink file 'cos it ain't an image */
		if ( $data === false )
		{
			@unlink( $upload_path . '/' . $tmp_name );
			
			$return['status'] = 'fail';
			$return['error']  = 'err_not_save_urlphoto';
			
			return $return;
		}
		
		/* Check file size */
		$size = filesize( $upload_path . '/' . $tmp_name );
		
		if ( $size > ( $p_max * 1024 ) )
		{
			@unlink( $upload_path . '/' . $tmp_name );
			
			$return['status'] = 'fail';
			$return['error']  = 'upload_to_big';
			
			return $return;
		}
		
		/* Is a photo we accept? */
		if ( ! in_array( $data['fileType'], array( 'png', 'gif', 'jpg' ) ) )
		{
			@unlink( $upload_path . '/' . $tmp_name );
			
			$return['status'] = 'fail';
			$return['error']  = 'invalid_file_extension';
			
			return $return;
		}
		
		/* Remove any current photos */
		$this->removeUploadedPhotos( $member_id, $upload_path );
		
		/* Ok so rename it 'cos we're good */
		if ( ! @rename( $upload_path . '/' . $tmp_name, $upload_path . '/' . 'photo-'.$member_id . '.' . $data['fileType'] ) )
		{
			@unlink( $upload_path . '/' . $tmp_name );
			
			$return['status'] = 'fail';
			$return['error']  = 'err_not_save_urlphoto';
			
			return $return;
		}
		else
		{
			@chmod( $upload_path . '/' . 'photo-'.$member_id, 0777 );
			
			/* Now build sized copies */
			return $this->buildSizedPhotos( 'photo-'.$member_id . '.' . $data['fileType'], $member_id );
		}
	}
	
	/**
	 * Upload personal photo function
	 * Assumes all security checks have been performed by this point
	 *
	 * @access	public
	 * @param	integer		[Optional] member id instead of current member
	 * @return 	array  		[ error (error message), status (status message [ok/fail] ) ]
	 */
	public function uploadPhoto( $member_id = 0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$return		      = array( 'error'            => '',
								   'status'           => '',
								   'final_location'   => '',
								   'final_width'      => '',
								   'final_height'     => '',
								   't_final_location' => '',
								   't_final_width'    => '',
								   't_final_height'   => ''  );
		$member_id        = $member_id ? intval($member_id) : intval( $this->memberData['member_id'] );
		$memberData		  = IPSMember::load( $member_id );
		$real_name        = '';
		$upload_dir       = '';
		$t_real_name      = '';
		$p_max    		  = $memberData['photoMaxKb'];
		
		if ( IN_ACP )
		{
			$p_max = 10000;
		}
		
		if ( ! $member_id )
		{
			return array( 'status' => 'cannot_find_member' );
		}
					
		/* Fix up upload directory */
		$paths       = $this->_getProfileUploadPaths();
		$upload_path = $paths['path'];
		$upload_dir  = $paths['dir'];
		
		/* Check for an upload */
		if ( $_FILES['upload_photo']['name'] != "" and ($_FILES['upload_photo']['name'] != "none" ) )
		{
			if ( ! IPSMember::canUploadPhoto( $memberData ) )
			{
				$return['status'] = 'fail';
				$return['error']  = 'no_photo_upload_permission';
				
				return $return;
			}
			
			/* Remove any current photos */
			$this->removeUploadedPhotos( $member_id, $upload_path );
			
			$real_name = 'photo-'.$member_id;
			
			/* Fetch library */
			require_once( IPS_KERNEL_PATH.'classUpload.php' );/*noLibHook*/
			$upload    = new classUpload();

			/* Bit of set up */
			$upload->out_file_name     = 'photo-'.$member_id;
			$upload->out_file_dir      = $upload_path;
			$upload->max_file_size     = $p_max * 1024;
			$upload->upload_form_field = 'upload_photo';
			
			/* Set up our allowed types */
			$upload->allowed_file_ext  = array( 'gif', 'png', 'jpg', 'jpeg' );
			
			/* Upload */
			$upload->process();
			
			/* Oops, what happened? */
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
						
			/* We got this far.. */
			$real_name   = $upload->parsed_file_name;
			$t_real_name = $upload->parsed_file_name;
			
			/* Now build sized copies */
			$return = $this->buildSizedPhotos( $upload->parsed_file_name, $member_id );
		}
		
		return $return;
	}
	
	/**
	 * Remove a photo for a member
	 * @param int $member_id
	 * @return string
	 */
	public function remove( $member_id )
	{
		/* Fix up upload directory */
		$paths       = $this->_getProfileUploadPaths();
		$upload_path = $paths['path'];
		$upload_dir  = $paths['dir'];
		
		$memberData				 = IPSMember::load( $member_id );
		$bwOptions				 = IPSBWOptions::thaw( $memberData['fb_bwoptions'], 'facebook' );
		$tcbwOptions			 = IPSBWOptions::thaw( $memberData['tc_bwoptions'], 'twitter' );
		$bwOptions['fbc_s_pic']	 = 0;
		$tcbwOptions['tc_s_pic'] = 0;
		
		/* If we were using Gravatar and we have asked to remove the photo, we should disable Gravatar */
		$memBitwise = IPSBWOptions::thaw( $memberData['members_bitoptions'], 'members' );
		if ( $memberData['pp_photo_type'] == 'gravatar' or $memberData['pp_photo_type'] == 'none' )
		{
			$memBitwise['bw_disable_gravatar'] = 1;
		}
		$memBitwise = IPSBWOptions::freeze( $memBitwise, 'members' );
	
		$this->removeUploadedPhotos( $member_id, $upload_path );
		
		IPSMember::save( $member_id, array( 'core' => array( 'members_bitoptions' => $memBitwise ),
											'extendedProfile' => array( 'pp_main_photo'		=> '',
												  				   	 	'pp_main_width'		=> 0,
																	   	'pp_main_height'	=> 0,
																		'pp_thumb_photo'	=> '',
																		'pp_thumb_width'	=> 0,
																		'pp_thumb_height'	=> 0,
																		'pp_photo_type'		=> 'none',
																		'pp_gravatar'		=> '',
																		'fb_photo'			=> '',
																		'fb_photo_thumb'	=> '',
																		'fb_bwoptions'		=> IPSBWOptions::freeze( $bwOptions, 'facebook' ),
																		'tc_photo'			=> '',
																		'tc_bwoptions'		=> IPSBWOptions::freeze( $tcbwOptions, 'twitter' ),
																	 ) ) );
		return true;
	}
	
	/**
	 * Fetch a user's twitter picture
	 * 
	 * @param mixed $member	INT OR ARRAY
	 */
	public function setTwitterPicture( $member )
	{
		/* Fetch member details */
		if ( is_integer( $member ) )
		{
			$member = IPSMember::load( $member, 'all' );
		}
		else if ( isset( $member['member_id'] ) && ! isset( $member['pp_photo_type'] ) )
		{
			$member = IPSMember::load( $member['member_id'], 'all' );
		}
		
		/* Fix up upload directory */
		$paths       = $this->_getProfileUploadPaths();
		$upload_path = $paths['path'];
		$upload_dir  = $paths['dir'];
		
		/* Twitter enabled? */
		if ( IPSLib::twitter_enabled() && $member['twitter_token'] && $member['twitter_secret'] )
		{
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/twitter/connect.php', 'twitter_connect' );
			$twitter	 = new $classToLoad( $this->registry, $member['twitter_token'], $member['twitter_secret'] );
			
			$userData    = $twitter->fetchUserData();
			
			if ( $userData['profile_image_url'] )
			{
				$large = str_replace( '_normal.', '.', $userData['profile_image_url'] );
				$ext   = IPSText::getFileExtension( $userData['profile_image_url'] );
				$file  = 'photo-' . $member['member_id'] . '.' . $ext;
				$mokay = false;
				
				$content = @file_get_contents( $large );
				
				/* Try http wrappers first. Would be rude not to */
				if ( $content )
				{
					if ( @file_put_contents( $upload_path . '/' . $file, $content ) )
					{
						$mokay = true;
						return $this->buildSizedPhotos( $file, $member['member_id'] );
					}
				}
				
				/* Back up */
				if ( $mokay === false )
				{
					return array( 'final_location'    => $this->registry->output->isHTTPS ? str_replace( "http://", "https://", $userData['profile_image_url'] ) : $userData['profile_image_url'],
								  'final_width'       => 50,
								  'final_height'      => 50,
								  't_final_location'  => $this->registry->output->isHTTPS ? str_replace( "http://", "https://", $userData['profile_image_url'] ) : $userData['profile_image_url'],
								  't_final_width'     => 50,
								  't_final_height'    => 50 );
				}
			}
		}
		else
		{
			return array();
		}
	}
	
	/**
	 * Fetch a user's facebook picture
	 * 
	 * @param mixed $member	INT OR ARRAY
	 */
	public function setFacebookPicture( $member )
	{
		/* Fetch member details */
		if ( is_integer( $member ) )
		{
			$member = IPSMember::load( $member, 'all' );
		}
		else if ( isset( $member['member_id'] ) && ! isset( $member['pp_photo_type'] ) )
		{
			$member = IPSMember::load( $member['member_id'], 'all' );
		}
		
		/* Fix up upload directory */
		$paths       = $this->_getProfileUploadPaths();
		$upload_path = $paths['path'];
		$upload_dir  = $paths['dir'];
	
		/* Twitter enabled? */
		if (  IPSLib::fbc_enabled() && $member['fb_uid'] )
		{
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/facebook/connect.php', 'facebook_connect' );
			$facebook    = new $classToLoad( $this->registry );
			
			$facebook->resetApi( $member['fb_token'], $member['fb_uid'] );
			
			$userData = $facebook->fetchUserData();
			
			if ( $userData['pic_big'] || $userData['pic'] )
			{
				$large = $userData['pic_big'];
				$ext   = IPSText::getFileExtension( $userData['pic_big'] );
				$file  = 'photo-' . $member['member_id'] . '.' . $ext;
				$mokay = false;
				
				/* Try http wrappers first. Would be rude not to */
				$content = @file_get_contents( $large );
				
				if ( $content )
				{
					if ( @file_put_contents( $upload_path . '/' . $file, $content ) )
					{
						$mokay  = true;
						return $this->buildSizedPhotos( $file, $member['member_id'] );	
					}
				}
				
				/* Back up */
				if ( $mokay === false )
				{
					return array( 'final_location'    => $this->registry->output->isHTTPS ? str_replace( "http://", "https://", $userData['pic'] ) : $userData['pic'],
								  'final_width'       => 50,
								  'final_height'      => 50,
								  't_final_location'  => $this->registry->output->isHTTPS ? str_replace( "http://", "https://", $userData['pic'] ) : $userData['pic'],
								  't_final_width'     => 50,
								  't_final_height'    => 50 );
				}
			}
		}
		else
		{
			return array();
		}
	}
	
	/**
	 * Set gravatar as photie
	 * 
	 * @param string Gravatar email address
	 */
	public function setGravatar( $gravatar )
	{
		$md5Gravatar = md5( $gravatar );
		
		$_url	= "http://www.gravatar.com";
		
		if( $this->registry->output->isHTTPS )
		{
			$_url	= "https://secure.gravatar.com";
		}

		return array( 'final_location'    => $_url . "/avatar/" .$md5Gravatar . "?s=100",
					  'final_width'       => 100,
					  'final_height'      => 100,
					  't_final_location'  => $_url . "/avatar/" .$md5Gravatar . "?s=100",
					  't_final_width'     => 100,
					  't_final_height'    => 100 );	
	}
	
	/**
	 * Crop photo - takes the normal full size image crops the bad boy
	 * 
	 * @param	INT		Member ID
	 * @param	Array	Array of dims (x1, x2, y1, y2)
	 */
	public function cropPhoto( $memberId, $dims )
	{
		/* Fix up upload directory */
		$paths       = $this->_getProfileUploadPaths();
		$upload_path = $paths['path'];
		$upload_dir  = $paths['dir'];
		$return      = array( 'status' => 'fail' );
		$save		 = array();
		$x1			 = intval( $dims['x1'] );
		$x2			 = intval( $dims['x2'] );
		$y1			 = intval( $dims['y1'] );
		$y2			 = intval( $dims['y2'] );
		$pWidth		 = $this->settings['member_photo_crop'];
		$pHeight     = $this->settings['member_photo_crop'];
		 
		/* Check */
		if ( ! $memberId )
		{
			return $return;
		}
		
		/* Fetch all current data */
		$memberData = IPSMember::load( $memberId, 'all' );
		
		/* Got a PP image? */
		if ( ! $memberData['pp_main_photo'] )
		{
			return $return;
		}
		
		/* Figure out image name */
		$path = pathinfo( $memberData['pp_main_photo'] );
		$ext  = IPSText::getFileExtension( $memberData['pp_main_photo'] );
		
		if ( ! $path['filename'] )
		{
			return $return;	
		}
		
		/* Get kernel library */
		require_once( IPS_KERNEL_PATH . 'classImage.php' );/*noLibHook*/
		$img = ips_kernel_image::bootstrap( 'gd' );
		
		/* Location */
		$photoThumbLoc  = $upload_path . '/photo-thumb-' . $memberData['member_id'] . '.' . $ext;
		
		/* set up image processor */
		$settings  = array( 'image_path'	=> $upload_path, 
						    'image_file'	=> $path['filename'] . '.' . $ext,
						    'jpg_quality'	=> 100,
						    'png_quality'	=> 9 );
		
		/* Prep first pass */
		if ( $img->init( $settings ) )
		{
			/* Crop */
			$width  = $x2 - $x1;
			$height = $y2 - $y1;
			
			$return = $img->crop( $x1, $y1, $width, $height );
			
			if ( file_exists( $photoThumbLoc ) )
			{
				@unlink( $photoThumbLoc );
			}
			
			if ( $img->writeImage( $photoThumbLoc ) )
			{
				$save = $return;
			}
		}
		
		/* Crop too large? */
		if ( ! empty( $save['newWidth'] ) && ( $save['newWidth'] > $pWidth OR $save['newHeight'] > $pHeight ) )
		{
			/* Get kernel library */
			$img = ips_kernel_image::bootstrap( 'gd' );
		
			/* set up image processor */
			$settings  = array( 'image_path'	=> $upload_path, 
							    'image_file'	=> 'photo-thumb-' . $memberData['member_id'] . '.' . $ext,
							    'jpg_quality'	=> 100,
							    'png_quality'	=> 9 );
		
			if ( $img->init( $settings ) )
			{
				$return = $img->resizeImage( $pWidth, $pHeight );
				
				if ( $img->writeImage( $photoThumbLoc ) )
				{
					$save = $return;
				}
				
				unset( $img );
			}
		}
		
		/* Return some more */
		$return = array_merge( $save, $return );
		$return['status']   = 'ok';
		$return['thumb']    = $this->settings['upload_url'] . '/' . $upload_dir . 'photo-thumb-' . $memberData['member_id'] . '.' . $ext;
	
		return $return;
	}
	
	/**
	 * Get photo type - mostly here to help legacy / upgrades
	 * @param	mixed	INT or Array
	 */
	public function getPhotoType( $member )
	{		
		if ( is_integer( $member ) )
		{
			$member = IPSMember::load( $member, 'all' );
		}
		else if ( isset( $member['member_id'] ) && ! isset( $member['pp_photo_type'] ) )
		{
			$member = IPSMember::load( $member['member_id'], 'all' );
		}
		
		if ( ! empty( $member['pp_photo_type'] ) )
		{
			return $member['pp_photo_type'];
		}
		else
		{
			$bwOptions	 = IPSBWOptions::thaw( $member['fb_bwoptions'], 'facebook' );
			$tcbwOptions = IPSBWOptions::thaw( $member['tc_bwoptions'], 'twitter' );
			
			if ( ! empty( $member['pp_main_photo'] ) AND ( strpos( $member['pp_main_photo'], 'http://' ) === false OR strpos( $member['pp_main_photo'], $this->settings['original_base_url'] ) ) )
			{
				return 'custom';
			}
			
			if ( $bwOptions['fbc_s_pic'] )
			{
				return 'facebook';
			}
			
			if ( $tcbwOptions['tc_s_pic'] )
			{
				return 'twitter';
			}
						
			if ( $member['pp_gravatar'] )
			{
				return 'gravatar';
			}
			
			return 'none';
		}
	}
	
	/**
	 * Remove member uploaded photos
	 *
	 * @access	public
	 * @param	integer		Member ID
	 * @param	string		[Optional] Directory to check
	 * @return 	array  		[ error (error message), status (status message [ok/fail] ) ]
	 */
	public function removeUploadedPhotos( $id, $upload_path='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$upload_path = $upload_path ? $upload_path : $this->settings['upload_dir'];
		
		foreach( array( 'swf', 'jpg', 'jpeg', 'gif', 'png' ) as $ext )
		{
			if ( @is_file( $upload_path."/photo-".$id.".".$ext ) )
			{
				@unlink( $upload_path."/photo-".$id.".".$ext );
			}
			
			if ( @is_file( $upload_path."/photo-thumb-".$id.".".$ext ) )
			{
				@unlink( $upload_path."/photo-thumb-".$id.".".$ext );
			}
		}
	}
	
	/**
	 * Takes a photo and builds it nicely.
	 * @param string $fileLocation
	 * @param int $memberId
	 * @param bool	$skipDir	Don't add profile directory
	 */
	public function buildSizedPhotos( $fileLocation, $memberId, $skipDir=false )
	{
		$memberData		  = IPSMember::load( $memberId );
		$t_height		  = $this->settings['member_photo_crop'] ? $this->settings['member_photo_crop'] : 100;
		$t_width          = $this->settings['member_photo_crop'] ? $this->settings['member_photo_crop'] : 100;
		$p_max    		  = $memberData['photoMaxKb'];
		$p_width  		  = $memberData['photoMaxWidth'];
		$p_height 		  = $memberData['photoMaxHeight'];
		$ext              = IPSText::getFileExtension( $fileLocation );
		$needResize       = false;
		
		if ( ! $memberId )
		{
			return array( 'status' => 'cannot_find_member' );
		}
		
		if ( IN_ACP )
		{
			$memberData['photoMaxKb']     = $this->memberData['photoMaxKb'];
			$memberData['photoMaxWidth']  = $this->memberData['photoMaxWidth'];
			$memberData['photoMaxHeight'] = $this->memberData['photoMaxHeight'];
		}
				
		/* Fix up upload directory */
		$paths       = $this->_getProfileUploadPaths( $skipDir );
		$storagePath = $this->_getProfileUploadPaths();
		$upload_path = $paths['path'];
		$upload_dir  = $paths['dir'];
		
		/* Does image even exist?  If not, just return (can't rebuild a file that doesn't exist). */
		if( !file_exists( $upload_path . '/' . $fileLocation ) )
		{
			return array(
							'final_location'	=> '',
							'final_width'		=> 0,
							'final_height'		=> 0,
							't_final_location'	=> '',
							't_file_name'		=> '',
							't_final_width'		=> 0,
							't_final_height'	=> 0,
							'status'			=> 'missing_image',
							);
		}
		
		/* Get kernel library */
		require_once( IPS_KERNEL_PATH . 'classImage.php' );/*noLibHook*/
				
		/* Fetch image dims */
		$imageDimensions = @getimagesize( $upload_path . '/' . $fileLocation );
		
		/* Do we need to resize? */
		if ( ( $imageDimensions[0] > $t_width OR $imageDimensions[1] > $t_height ) )
		{
			$needResize = true;
		}
		else if ( $ext == 'gif' && ! $this->settings['member_photo_gif_animate'] )
		{
			/* Resize even if smaller to prevent animation */
			$needResize = true;
		}
		
		/* Overide if we have a GIF and want to keep it animating */
		if ( $ext == 'gif' && $this->settings['member_photo_gif_animate'] )
		{
			$needResize = false;
		}
		
		/** SQUARE THUMBS **/
		if ( $needResize )
		{
			$image = ips_kernel_image::bootstrap( 'gd' );
			
			$image->init( array( 'image_path'	  => $upload_path, 
								 'image_file'	  => $fileLocation ) );
			
			/* If we're uploading a GIF then resize to stop animations */
			if ( $ext == 'gif' && ! $this->settings['member_photo_gif_animate'] )
			{
				$image->force_resize = true;
			}
			
			$return = $image->croppedResize( $t_width, $t_height );
			
			$image->writeImage( $storagePath['path'] . '/' . 'photo-thumb-'.$memberId . '.' . $ext );
            
			$t_im['img_width']	  = $return['newWidth'];
			$t_im['img_height']	  = $return['newHeight'];
			$t_im['img_location'] = count($return) ? $storagePath['dir'] . 'photo-thumb-'.$memberId . '.' . $ext : $upload_dir . $fileLocation;
		} 
		else 
		{
			$_data = IPSLib::scaleImage( array(  'max_height' => $t_height,
												 'max_width'  => $t_width,
												 'cur_width'  => $imageDimensions[0],
												 'cur_height' => $imageDimensions[1] ) );

			$t_im['img_width']		= $_data['img_width'];
			$t_im['img_height']		= $_data['img_height'];
			$t_im['img_location']	= $upload_dir . $fileLocation;
		}
		
		/** MAIN PHOTO **/
		if ( $imageDimensions[0] > $p_width OR $imageDimensions[1] > $p_height )
		{
			$image = ips_kernel_image::bootstrap( 'gd' );
			
			$image->init( array( 'image_path'	  => $upload_path, 
								 'image_file'	  => $fileLocation ) );
            
			$return = $image->resizeImage( $p_width, $p_height );
			$image->writeImage( $storagePath['path'] . '/' . 'photo-'.$memberId . '.' . $ext );
            
			$t_real_name = $return['thumb_location'] ? $return['thumb_location'] : $fileLocation;
            
			$im['img_width']  = $return['newWidth']  ? $return['newWidth']   : $image->cur_dimensions['width'];
			$im['img_height'] = $return['newHeight'] ? $return['newHeight'] : $image->cur_dimensions['height'];
			
			$return['final_location'] = $storagePath['dir'] . $fileLocation;
		}
		else
		{
			$im['img_width']  = $imageDimensions[0];
			$im['img_height'] = $imageDimensions[1];
			
			$return['final_location'] = $upload_dir . $fileLocation;
		}
	
		/* Main photo */
		// If we don't rebuild, the image is in the original location - which may not be in the /profile folder */
		//$return['final_location'] = $storagePath['dir'] . $fileLocation;
		$return['final_width']    = $im['img_width'];
		$return['final_height']   = $im['img_height'];
		
		/* Thumb */
		/* If we don't need to resize, it's same as the main image (which may get moved during IT'S resize) */
		$return['t_final_location'] = $needResize ? $t_im['img_location'] : $return['final_location'];
		$return['t_file_name']		= $t_im['img_location'];
		$return['t_final_width']    = $t_im['img_width'];
		$return['t_final_height']   = $t_im['img_height'];

		$return['status'] = 'ok';
		return $return;
	}
	
	/**
	 * Fetch upload path and dir
	 * 
	 * @param	bool	Skip profile/ directory
	 */
	protected function _getProfileUploadPaths( $skipDir=false )
	{
		/* Fix for bug 5075 */
		$this->settings['upload_dir'] = str_replace( '&#46;', '.', $this->settings['upload_dir'] );		

		$upload_path  = $this->settings['upload_dir'];
		
		if ( ! $skipDir )
		{
			/* Create a dir if need be */
			if ( ! file_exists( $upload_path . "/profile" ) )
			{
				if ( @mkdir( $upload_path . "/profile", IPS_FOLDER_PERMISSION ) )
				{
					@file_put_contents( $upload_path . '/profile/index.html', '' );
					@chmod( $upload_path . "/profile", IPS_FOLDER_PERMISSION );
					
					# Set path and dir correct
					$upload_path .= "/profile";
					$upload_dir   = "profile/";
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
				$upload_path .= "/profile";
				$upload_dir   = "profile/";
			}
		}
		
		return array( 'path' => $upload_path, 'dir' => $upload_dir );
	}
	
	/**
	 * Returns the editor for viewing ...
	 * @param unknown_type $member
	 */
	public function getEditorHtml( array $member )
	{
		/* Fetch member data */
		$member = IPSMember::buildDisplayData( IPSMember::load( $member['member_id'], 'all' ) );
		
		$p_w       = "";
		$p_h       = "";
		$cur_photo = "";
		$rand      = urlencode( microtime() );
		$data      = array( 'currentPhoto' => array( 'tag' => '' ),
							'custom'       => array( 'tag' => '' ),
							'gravatar'     => array( 'tag' => '' ),
							'twitter'      => array( 'tag' => '' ) );
				
		/* Photo type */		
		$data['type'] = $member['pp_photo_type'] = $this->getPhotoType( $member );
		
		/* Got gravatar? */
		$member['pp_gravatar'] = ( $member['pp_gravatar'] ) ? $member['pp_gravatar'] : $member['email'];
		
		/* Quick permission check */
		if ( ! IPSMember::canUploadPhoto( $member, TRUE ) )
 		{
 			return false;
 		}
 		
 		/* Set the current photo */
 		$data['currentPhoto']['tag'] = IPSMember::buildProfilePhoto( $member, 'full', IPS_MEMBER_PHOTO_NO_CACHE );
 		
 		/* Set up custom */
 		$data['custom']['tag']  = ( $member['pp_photo_type'] != 'custom' ) ? IPSMember::buildNoPhoto( $member, 'thumb', false, true ) : "<img src='".$member['pp_thumb_photo'].'?__rand='. $rand . "' width='". $member['pp_thumb_width'] ."' height='". $member['pp_thumb_height'] ."' />";
 		
 		/* Set up Gravatar */
		$data['gravatar']['tag'] = "<img src='http://www.gravatar.com/avatar/" . md5( $member['pp_gravatar'] ) . "?s=100' alt='' />";
 		
		/* Twitter linked? */
		if ( IPSLib::twitter_enabled() && $member['twitter_token'] && $member['twitter_secret'] )
		{
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/twitter/connect.php', 'twitter_connect' );
			$twitter	 = new $classToLoad( $this->registry, $member['twitter_token'], $member['twitter_secret'] );
			
			$userData    = $twitter->fetchUserData();
			
			if ( $userData['profile_image_url'] )
			{
				$data['twitter']['tag'] = "<img src='" . str_replace( '_normal.', '.', $userData['profile_image_url'] ) . "' />";
			}
		}
		
		/* Facebook linked? */
		if ( IPSLib::fbc_enabled() && $member['fb_uid'] )
		{
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/facebook/connect.php', 'facebook_connect' );
			$facebook    = new $classToLoad( $this->registry );
			
			/* Now get the linked user */
			$linkedMemberData = IPSMember::load( intval($member['fb_uid']), 'all', 'fb_uid' );
			
			$userData = $facebook->fetchUserData();
			
			if ( $userData['pic_big'] )
			{
				$data['facebook']['tag'] = "<img src='" . $userData['pic_big'] . "' />";
			}
			else if ( $userData['pic'] )
			{
				$data['facebook']['tag'] = "<img src='" . $userData['pic'] . "' />";
			}
		}
 		
		$this->uploadFormMax = 5000*1024;
		
 		return $this->registry->getClass('output')->getTemplate('profile')->photoEditor( $data, $member );
		
	}
}