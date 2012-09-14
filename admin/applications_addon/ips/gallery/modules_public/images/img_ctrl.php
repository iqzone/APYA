<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Image Viewer, for non web accessible images
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

class public_gallery_images_img_ctrl extends ipsCommand
{
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-------------------------------------------------------
		// Check Auth
		//-------------------------------------------------------		 
		
		if( empty( $this->request['img'] ) )
		{
			$this->returnNotFound();
		}
		
		//-------------------------------------------------------
		// Get image info
		//-------------------------------------------------------
		
		$image = $this->registry->gallery->helper('image')->fetchImage( $this->request['img'] );
		
		//-------------------------------------------------------
		// Can we view?
		//-------------------------------------------------------
		
		if( !$image['id'] )
		{
			$this->returnNotFound();
		}
		
		if ( $image['album_id'] )
		{
			if ( $this->registry->gallery->helper('albums')->isPrivate( $image ) AND $image['member_id'] != $this->memberData['member_id'] AND ! $this->memberData['g_mod_albums'] )
			{
				$this->returnNoPermission();
			}
			else if ( ! $this->registry->gallery->helper('albums')->isViewable( $image ) )
			{
				$this->returnNoPermission();
			}
		}
		else
		{
			$this->returnNoPermission();
		}

		//-------------------------------------------------------
		// Sort out location
		//-------------------------------------------------------
		
		$image_loci = $image['directory'] ? $this->settings['gallery_images_path'] . '/' . $image['directory'] . '/' : $this->settings['gallery_images_path'] . '/';
		
		if( $this->request['tn'] )
		{
			$theimg = $image_loci . 'tn_' . $image['masked_file_name'];
		}
		else if( $this->request['file'] == 'med' )
		{
			$theimg = $image_loci . $image['medium_file_name'];
		}
		else if( $this->request['file'] == 'media' )
		{
			$theimg = $image_loci . $image['media_thumb'];
		}
		else if( $this->request['file'] == 'mediafull' )
		{
			$exploded_array = explode( ".", $image['masked_file_name'] );
			
			$ext = '.' . strtolower( array_pop( $exploded_array ) );
			
			if( ! $this->registry->gallery->helper('media')->isAllowedExtension( $ext ) )
			{
				$this->returnNoPermission();
			}

			$image['file_type'] = $this->registry->gallery->helper('media')->getMimeType( $ext );
			
			$theimg = $image_loci . $image['masked_file_name'];
		}
		else
		{
			$theimg = $image_loci . $image['masked_file_name'];
		}

		if( is_dir( $theimg ) OR !is_file( $theimg ) )
		{
			$this->returnNotFound();
		}
		
		//-------------------------------------------------------
		// And finally, display
		//-------------------------------------------------------
		
		$delivery = $this->request['type'] == 'download' ? 'download' : 'inline';
		$fileName = $this->request['type'] == 'download' ? $image['file_name'] : $image['masked_file_name'];
		
		header( "Content-Type: {$image['file_type']}" );
		header( "Content-Disposition: {$delivery}; filename=\"{$fileName}\"" );
		
		@ob_end_clean();
		
		if( $fh = fopen( $theimg, 'rb' ) )
		{
			while( !feof($fh) )
			{
				echo fread( $fh, 4096 );
				flush();
				@ob_flush();
			}
			
			@fclose( $fh );
		}

		exit();
	}
	
	/**
	 * Display a no permission image
	 *
	 * @return	@e void
	 */
	protected function returnNoPermission()
	{
		@ob_end_clean();
		$image 	= file_get_contents( DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/style_extra/gallery_media_types/no_permission.gif' );
		
		@header( "Content-Type: image/gif" );
		@header( "Content-Disposition: inline; filename='no_permission.gif'" );
		print $image;
		exit;
	}
	
	/**
	 * Display a not found image
	 *
	 * @return	@e void
	 */
	protected function returnNotFound()
	{
		@ob_end_clean();
		$image 	= file_get_contents( DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/style_extra/gallery_media_types/no_image_found.gif' );
		
		@header( "Content-Type: image/gif" );
		@header( "Content-Disposition: inline; filename='no_image_found.gif'" );
		print $image;
		exit;
	}
}