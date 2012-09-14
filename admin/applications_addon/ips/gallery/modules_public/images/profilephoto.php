<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Set Profile Photo
 * Last Updated: $LastChangedDate: 2011-12-09 15:24:24 -0500 (Fri, 09 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @version		$Rev: 9978 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_gallery_images_profilephoto extends ipsCommand
{
	/**
	 * Image data
	 *
	 * @access	private
	 * @var		array
	 */
	private $data;

	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */	
	public function doExecute( ipsRegistry $registry )
	{
		/* Logged in ? */
		if( ! $this->memberData['member_id'] )
		{
			$this->registry->output->showError( 'no_permission', 107180, null, null, 403 );
		}
				
		if ( ! $this->memberData['g_edit_profile'] )
		{
			$this->registry->getClass('output')->showError( 'members_profile_disabled', 107181, null, null, 403 );
		}
				
		if( $this->settings['gallery_web_accessible'] == 'no' )
		{
			$this->registry->output->showError( 'no_permission', 107182, null, null, 403 );
		}
		
		/* Validate Access */
		$id = intval( $this->request['id'] );
		$this->data = $this->registry->gallery->helper('image')->fetchImage( $id );
		
		/* Check the photo is in the correct album */
		$albumChk = $this->DB->buildAndFetch( array( 'select'	=> '*',
													 'from'		=> 'gallery_albums_main',
													 'where'	=> "album_id={$this->data['album_id']} AND album_owner_id={$this->data['member_id']} AND album_is_profile=1" )	);
		
		if( ! $albumChk OR ! $this->registry->gallery->helper('albums')->isOwner( $albumChk ) )
		{
			$this->registry->output->showError( 'no_permission', 107183, null, null, 404 );
		}
		
		/* Extension */
		$imgExtension = IPSText::getFileExtension( $this->data['masked_file_name'] );		
		
		/* Copy the image to a temporary file for processing */
		$srcImage	= $this->settings['upload_dir'] . '/' . $this->data['directory'] . '/' . $this->data['masked_file_name'];
		$destImage	= $this->settings['upload_dir'] . '/profile/photo-' . $this->memberData['member_id'] . '-temp.' . $imgExtension;
		copy( $srcImage, $destImage );
		
		/* Get Image Dimensions */
		$img_size	= @getimagesize( $destImage );
		$t_height	= 50;
		$t_width	= 50;
		
		/* Resize */
		list( $p_max, $p_width, $p_height ) = explode( ":", $this->memberData[ 'g_photo_max_vars' ] );
		
		if( $img_size[0] > $p_width OR $img_size[1] > $p_height )
		{
			/* Main Photo */
			require_once( IPS_KERNEL_PATH . 'classImage.php' );/*noLibHook*/
			require_once( IPS_KERNEL_PATH . 'classImageGd.php' );/*noLibHook*/
			$image = new classImageGd();
			
			$image->init( array( 
									'image_path' => $this->settings['upload_dir'] . '/profile/', 
									'image_file' => 'photo-' . $this->memberData['member_id'] . '-temp.' . $imgExtension 
						)	);
            
			$return = $image->resizeImage( $p_width, $p_height );
			$image->writeImage( $this->settings['upload_dir'] . '/' . 'profile/photo-' . $this->memberData['member_id'] . '.' . $imgExtension );

			$t_real_name = $return['thumb_location'] ? $return['thumb_location'] : 'photo-' . $this->memberData['member_id'] . '.' . $imgExtension;

			$im['img_width']  = $return['newWidth'] ? $return['newWidth'] : $image->cur_dimensions['width'];
			$im['img_height'] = $return['newHeight'] ? $return['newHeight'] : $image->cur_dimensions['height'];
            
			/* MINI Photo */
			$image->init( array( 
								'image_path'	=>  $this->settings['upload_dir'] . '/profile/', 
								'image_file'	=> $t_real_name, 
						)	);
            
			$return = $image->resizeImage( $t_width, $t_height );
			$image->writeImage( $this->settings['upload_dir'] . '/profile/' . 'photo-thumb-' . $this->memberData['member_id'] . '.' . $imgExtension );
            
			$t_im['img_width']    = $return['newWidth'];
			$t_im['img_height']   = $return['newHeight'];
			$t_im['img_location'] = count( $return ) ? 'profile/photo-thumb-' . $this->memberData['member_id'] . '.' . $imgExtension : $t_real_name;
		}
		else
		{
			/* Main Image */
			copy( $destImage, $this->settings['upload_dir'] . '/profile/photo-' . $this->memberData['member_id'] . '.' . $imgExtension );
			$im['img_width']  = $img_size[0];
			$im['img_height'] = $img_size[1];

			/* MINI Photo */
			$_data = IPSLib::scaleImage( array( 
												'max_height' => $t_height,
												'max_width'  => $t_width,
												'cur_width'  => $im['img_width'],
												'cur_height' => $im['img_height'] 
										)	);

			$t_im['img_width']  	= $_data['img_width'];
			$t_im['img_height']		= $_data['img_height'];
			$t_im['img_location']	= 'profile/photo-' . $this->memberData['member_id'] . '.' . $imgExtension;
		}
		
		/* Remove the temporary image */
		if( is_file( $destImage ) )
		{
			@unlink( $destImage );
		}
		
		$profilePhotoData = array( 
									'pp_main_photo'   => 'profile/photo-' . $this->memberData['member_id'] . '.' . $imgExtension,
									'pp_main_width'   => intval( $im['img_width'] ),
									'pp_main_height'  => intval( $im['img_height'] ),
									'pp_thumb_photo'  => $t_im['img_location'],
									'pp_thumb_width'  => intval( $t_im['img_width'] ),
									'pp_thumb_height' => intval( $t_im['img_height'] ) 
								);

		/* Save the photo */
		IPSMember::save( $this->memberData['member_id'], array( 'extendedProfile' => $profilePhotoData ) );
		
		/* Return URL */
		if( $this->request['return'] == 'usercp' )
		{
			$this->registry->output->redirectScreen( $this->lang->words['profile_photo_updated'], $this->settings['base_url'] . 'app=core&amp;module=usercp&amp;tab=members&amp;area=photo&amp;do=show' );
		}
		else
		{
			$this->registry->output->redirectScreen( $this->lang->words['profile_photo_updated'], $this->settings['base_url'] . 'app=gallery&amp;module=images&amp;section=viewimage&amp;img=' . $id, $this->data['_seo_name'] );
		}
	}
}