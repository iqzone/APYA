<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Image Ajax
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

class public_gallery_ajax_image extends ipsAjaxCommand
{
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */	
	public function doExecute( ipsRegistry $registry )
	{
		$this->_albums   = $this->registry->gallery->helper('albums');
		$this->_images   = $this->registry->gallery->helper('image');
		$this->_moderate = $this->registry->gallery->helper('moderate');
		
		/* What to do? */
		switch( $this->request['do'] )
		{
			case 'imageDetail':
				$this->_imageDetail();
			break;
			
			case 'rotate-left':
				$this->rotateImage( 'left' );
			break;
			
			case 'rotate-right':
				$this->rotateImage( 'right' );
			break;
			
			case 'add-note':
				$this->addNote();
			break;
			
			case 'edit-note':
				$this->editNote();
			break;
			
			case 'remove-note':
				$this->removeNote();
			break;
			
			case 'fetchUploads':
				$this->_fetchUploads();
			break;
			case 'uploadRemove':
				$this->_removeUpload();
			break;
			case 'addMap':
				$this->_addMap();
			break;
			case 'removeMap':
				$this->_removeMap();
			break;
			case 'moveDialogue':
				$this->_moveDialogue();
			break;
			case 'setAsPhoto':
				$this->_setAsPhoto();
			break;
			case 'setAsCover':
				$this->_setAsCover();
			break;
        }
    }
    
	/**
	 * Returns image detail... for an image..
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _moveDialogue()
	{
		/* init */
		$image = $this->registry->gallery->helper('image')->fetchImage( intval( $this->request['imageid'] ) );
		
		if ( empty( $image['id'] ) )
		{
			return false;
		}
		
		/* Will test permissions */
		$album = $this->registry->gallery->helper('albums')->fetchAlbumsById( $image['img_album_id'] );
		
		if ( $album['album_parent_id'] )
		{
			$album['_parent'] = $this->registry->gallery->helper('albums')->fetchAlbumsById( $album['album_parent_id'] );
		}
		else
		{
			$album['_parent'] = array( 'album_id' => 0, 'album_name' => $this->lang->words['as_root'] );
		}
			
		/* return */
		return $this->returnHtml( $this->registry->output->getTemplate('gallery_img')->moveDialogue( $image, $album ) );
	}
	
    /**
	 * Sets an image as a photo.
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _setAsCover()
	{
		/* init */
		$image  = $this->registry->gallery->helper('image')->fetchImage( intval( $this->request['imageId'] ) );
		$album  = $this->registry->gallery->helper('albums')->fetchAlbum( $image['img_album_id'] );
		
		if ( ! $this->_images->isOwner( $image ) && ! $this->_albums->canModerate( $album ) )
		{
			$this->returnJsonArray( array( 'status' => 'error' ) );
		}
		
		/* WOT ARE YOU LOOKING AT? */
		$this->_albums->save( array( 'album_id' => $album['album_id'], 'album_cover_img_id' => $image['id'] ) );
		
		$this->returnJsonArray( array( 'status' => 'ok' ) );
	}
	
	/**
	 * Sets an image as a photo.
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _setAsPhoto()
	{
		/* init */
		$image   = $this->registry->gallery->helper('image')->fetchImage( intval( $this->request['imageId'] ) );
		$x1      = intval( $this->request['x1'] );
		$x2      = intval( $this->request['x2'] );
		$y1      = intval( $this->request['y1'] );
		$y2      = intval( $this->request['y2'] );
		$dir     = $image['directory'] ? $image['directory'] . "/" : '';
		$save    = array();
		$tHeight = 50;
		$tWidth  = 50;
		
		/* Get max size */
		list($pMax, $pWidth, $pHeight) = explode( ":", $this->memberData['g_photo_max_vars'] );
		
		/* @todo Write an API for this in core */
		$this->settings['upload_dir'] = str_replace( '&#46;', '.', $this->settings['upload_dir'] );		

		$upload_path  = $this->settings['upload_dir'];
		
		# Preserve original path
		$_upload_path = $this->settings['upload_dir'];
		
		if ( ! file_exists( $upload_path . '/profile' ) )
		{
			if ( @mkdir( $upload_path . '/profile', IPS_FOLDER_PERMISSION ) )
			{
				@file_put_contents( $upload_path . '/profile/index.html', '' );
				@chmod( $upload_path . '/profile', IPS_FOLDER_PERMISSION );
				
				# Set path and dir correct
				$upload_path .= '/profile';
				$upload_dir   = 'profile/';
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
		
		/* Basic checks */
		if ( ! $this->_images->isOwner( $image ) )
		{
			$this->returnJsonArray( array( 'status' => 'error' ) );
		}
		
		if ( empty( $image['id'] ) )
		{
			$this->returnJsonArray( array( 'status' => 'error' ) ); 
		}
		
		/* Ensure we have a file on disk */
		if ( ! file_exists( $this->settings['gallery_images_path'] . '/' . $dir . $image['medium_file_name'] ) )
		{
			$this->returnJsonArray( array( 'status' => 'error' ) );
		}
		
		$fileExt        = IPSText::getFileExtension( $image['medium_file_name'] );
		$photoFullSize  = $upload_path . '/' . 'photo-' . $this->memberData['member_id'] . '.' . $fileExt;
		$photoThumb     = $upload_path . '/' . 'photo-thumb-' . $this->memberData['member_id'] . '.' . $fileExt;
		$photoFullLoc   = $upload_dir . 'photo-' . $this->memberData['member_id'] . '.' . $fileExt;
		$photoThumbLoc  = $upload_dir . 'photo-thumb-' . $this->memberData['member_id'] . '.' . $fileExt;
		
		/* Get kernel library */
		require_once( IPS_KERNEL_PATH . 'classImage.php' );/*noLibHook*/
		$img = ips_kernel_image::bootstrap( $this->settings['gallery_img_suite'] );
		
		/* set up image processor */
		$settings  = array( 'image_path'	=> $this->settings['gallery_images_path'] . '/' . $dir, 
						    'image_file'	=> $image['medium_file_name'],
						    'im_path'		=> $this->settings['gallery_im_path'],
						    'temp_path'		=> DOC_IPS_ROOT_PATH . '/cache/tmp',
						    'jpg_quality'	=> GALLERY_JPG_QUALITY,
						    'png_quality'	=> GALLERY_PNG_QUALITY );
		
		/* Prep Fullsize */
		if ( $img->init( $settings ) )
		{
			/* remove old photos */
			$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/member/photo.php', 'classes_member_photo' );
			$photos			= new $classToLoad( $this->registry );
			$photos->removeUploadedPhotos( $this->memberData['member_id'] );	
			
			/* Crop */
			$width  = $x2 - $x1;
			$height = $y2 - $y1;
			
			$return = $img->crop( $x1, $y1, $width, $height );
			
			if ( $img->writeImage( $photoFullSize ) )
			{
				$save['pp_main_photo']   = $photoFullLoc;
				$save['pp_main_width']   = $return['newWidth'];
				$save['pp_main_height']  = $return['newHeight'];
				$save['pp_thumb_photo']  = $photoThumbLoc;
				$save['pp_thumb_width']  = $return['newWidth'];
				$save['pp_thumb_height'] = $return['newHeight'];
				$save['fb_photo']		 = '';
				$save['fb_photo_thumb']	 = '';
			}
			
			unset( $img );
		}
		
		/* Crop too large? */
		if ( ! empty( $save['pp_main_width'] ) && ( $save['pp_main_width'] > $pWidth OR $save['pp_main_height'] > $pHeight ) )
		{
			/* Get kernel library */
			require_once( IPS_KERNEL_PATH . 'classImage.php' );/*noLibHook*/
			$img = ips_kernel_image::bootstrap( $this->settings['gallery_img_suite'] );
		
			/* set up image processor */
			$settings  = array( 'image_path'	=> $upload_path, 
							    'image_file'	=> 'photo-' . $this->memberData['member_id'] . '.' . $fileExt,
							    'im_path'		=> $this->settings['gallery_im_path'],
							    'temp_path'		=> DOC_IPS_ROOT_PATH . '/cache/tmp',
							    'jpg_quality'	=> GALLERY_JPG_QUALITY,
							    'png_quality'	=> GALLERY_PNG_QUALITY );
		
			if ( $img->init( $settings ) )
			{
				$return = $img->resizeImage( $pWidth, $pHeight );
				
				if ( $img->writeImage( $photoFullSize ) )
				{
					$save['pp_main_width']  = $return['newWidth'];
					$save['pp_main_height'] = $return['newHeight'];
				}
				
				unset( $img );
			}
		}
		
		/* Still here? thumb */
		if ( ! empty( $save['pp_main_width'] ) && ( $save['pp_main_width'] > $tWidth OR $save['pp_main_height'] > $tHeight ) )
		{
			/* Get kernel library */
			require_once( IPS_KERNEL_PATH . 'classImage.php' );/*noLibHook*/
			$img = ips_kernel_image::bootstrap( $this->settings['gallery_img_suite'] );
		
			/* set up image processor */
			$settings  = array( 'image_path'	=> $upload_path, 
							    'image_file'	=> 'photo-' . $this->memberData['member_id'] . '.' . $fileExt,
							    'im_path'		=> $this->settings['gallery_im_path'],
							    'temp_path'		=> DOC_IPS_ROOT_PATH . '/cache/tmp',
							    'jpg_quality'	=> GALLERY_JPG_QUALITY,
							    'png_quality'	=> GALLERY_PNG_QUALITY );
		
			if ( $img->init( $settings ) )
			{
				$return = $img->resizeImage( $tWidth, $tHeight );
				
				if ( $img->writeImage( $photoThumb ) )
				{
					$save['pp_thumb_photo']  = $photoThumbLoc;
					$save['pp_thumb_width']  = $return['newWidth'];
					$save['pp_thumb_height'] = $return['newHeight'];
				}
				
				unset( $img );
			}
		}
		
		/* Save photo */
		if ( count( $save ) )
		{
			IPSMember::save( $this->memberData['member_id'], array( 'extendedProfile' => $save ) );
		}
		
		/* return */
		$this->returnJsonArray( array( 'status' => 'ok', 'oldPhoto' => $this->memberData['pp_thumb_photo'], 'photo' => $this->settings['upload_url'] . '/' . $photoFullLoc, 'thumb' => $this->settings['upload_url'] . '/' . $photoThumbLoc ) );
	}
	
	/**
	 * Removes a map to the image.
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _removeMap()
	{
		/* init */
		$image = $this->registry->gallery->helper('image')->fetchImage( intval( $this->request['imageid'] ) );
		
		if ( empty( $image['id'] ) )
		{
			return false;
		}
		
		if ( $image['member_id'] != $this->memberData['member_id'] )
		{
			return $this->returnJsonError( 'its_not_you_its_me' );
		}
		
		/* Update deebee */
		$this->DB->update( 'gallery_images', array( 'image_gps_show' => 0 ), "id=" . $image['id'] );
		
		return $this->returnJsonArray( array( 'done' => 1 ) );
	}
    
	/**
	 * Adds a map to the image.
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _addMap()
	{
		/* init */
		$image = $this->registry->gallery->helper('image')->fetchImage( intval( $this->request['imageid'] ) );
		
		if ( empty( $image['id'] ) )
		{
			return false;
		}
		
		$latLon = $this->registry->gallery->helper('image')->getLatLon( $image );
		
		if ( ! $latLon )
		{
			return $this->returnJsonError( 'i_dont_know_who_you_are_anymore' );
		}
		
		if ( $image['member_id'] != $this->memberData['member_id'] )
		{
			return $this->returnJsonError( 'its_not_you_its_me' );
		}
		
		/* Update deebee */
		$this->DB->update( 'gallery_images', array( 'image_gps_show' => 1 ), "id=" . $image['id'] );
		
		return $this->returnJsonArray( array( 'latLon' => implode( ",", $latLon ) ) );
	}
	
	/**
	 * Returns image detail... for an image..
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _imageDetail()
	{
		/* init */
		$image = $this->registry->gallery->helper('image')->fetchImage( intval( $this->request['imageid'] ) );
		
		if ( empty( $image['id'] ) )
		{
			return false;
		}
		
		/* Will test permissions */
		$album = $this->registry->gallery->helper('albums')->fetchAlbum( $image['img_album_id'] );
		
		/* Make tag */
		$image['small'] = $this->registry->gallery->helper('image')->makeImageLink( $image, array( 'type' => 'small', 'coverImg' => false, 'link-type' => 'page' ) );
		
		/* return */
		return $this->returnHtml( $this->registry->output->getTemplate('gallery_img')->ajaxDetail( $image, $album ) );
	}
	
	/**
	 * Fetches all uploads for this 'session'
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _removeUpload()
	{
		/* init */
		$sessionKey = trim( $this->request['sessionKey'] );
		$uploadKey = trim( $this->request['uploadKey'] );
		
		if ( $uploadKey AND $sessionKey )
		{
			return $this->returnJsonArray( $this->registry->gallery->helper('upload')->removeUpload( $sessionKey, $uploadKey ) );
		}
	}
	
	/**
	 * Fetches all uploads for this 'session'
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _fetchUploads()
	{
		/* init */
		$sessionKey = trim( $this->request['sessionKey'] );
		$JSON       = array();
		$albumId    = intval( $this->request['album_id'] );
		
		/* Basic check */
		if ( $this->request['secure_key'] != $this->member->form_hash )
		{
			$this->returnString('no_permission');
		}
		
		return $this->returnJsonArray( $this->registry->gallery->helper('upload')->fetchSessionUploadsAsJson( $sessionKey, $albumId ) );
	}
	
	/**
	 * Adds a new note to an image
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function addNote()
	{
		/* INIT */
		$id		= intval( $this->request['img'] );
		$top	= intval( $this->request['top'] );
		$left	= intval( $this->request['left'] );
		$width	= intval( $this->request['width'] );
		$height	= intval( $this->request['height'] );
		$note	= $this->convertAndMakeSafe( $_POST['note'], TRUE );
		
		/* Make sure we have everything */
		if( ! $id || ! $top || ! $left || ! $width || ! $height || ! $note )
		{
			$this->returnString( 'missing_data' );
		}
		
		/* Fix up unicode issues */
		if ( strtolower(IPS_DOC_CHAR_SET) != 'utf-8' )
		{
			$note = IPSText::utf8ToEntities( $note );
		}
		
		/* Query the image */
		$image = $this->registry->gallery->helper('image')->fetchImage( intval( $id ) );
		
		/* Will test permissions */
		$album = $this->registry->gallery->helper('albums')->fetchAlbum( $image['img_album_id'] );
		
		if ( ! $this->registry->gallery->helper('albums')->canModerate( $album['album_id'] ) AND ( $image['member_id'] != $this->memberData['member_id'] ) )
		{ 
			$this->returnString( 'nopermission' );	
		}
		
		/* Current Notes */
		$currNotes = unserialize( $image['image_notes'] );
		$currNotes = is_array( $currNotes ) ? $currNotes : array();
		
		/* Add the note */
		$noteId = md5( time() );
		
		$currNotes[] = array(
								'id'		=> $noteId,
								'top'		=> $top,
								'left'		=> $left,
								'width'		=> $width,
								'height'	=> $height,
								'note'		=> $note
							);
							
		/* Serialize and save */
		$this->DB->update( 'gallery_images', array( 'image_notes' => serialize( $currNotes ) ), "id={$id}" );
		
		$this->returnString( 'ok|' . $noteId );
	}
	
	/**
	 * Edit an existing image note
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function editNote()
	{
		/* INIT */
		$id		= intval( $this->request['img'] );
		$top	= intval( $this->request['top'] );
		$left	= intval( $this->request['left'] );
		$width	= intval( $this->request['width'] );
		$height	= intval( $this->request['height'] );
		$note	= $this->convertAndMakeSafe( $_POST['note'], TRUE );
		$noteId	= $this->request['noteId'];
		
		/* Make sure we have everything */
		if( ! $id || ! $top || ! $left || ! $width || ! $height || ! $note || ! $noteId )
		{
			$this->returnString( 'missing_data' );
		}
		
		/* Query the image */
		$image = $this->registry->gallery->helper('image')->fetchImage( intval( $id ) );
		
		/* Will test permissions */
		$album = $this->registry->gallery->helper('albums')->fetchAlbum( $image['img_album_id'] );
		
		if ( ! $this->registry->gallery->helper('albums')->canModerate( $album['album_id'] ) AND ( $image['member_id'] != $this->memberData['member_id'] ) )
		{ 
			$this->returnString( 'nopermission' );	
		}
		
		/* Current Notes */
		$currNotes = unserialize( $image['image_notes'] );
		$currNotes = is_array( $currNotes ) ? $currNotes : array();
		
		/* Loop through and find our note */
		foreach( $currNotes as $k => $v )
		{
			/* Is this our note? */
			if( $v['id'] == $noteId )
			{
				$currNotes[$k]['top']		= $top;
				$currNotes[$k]['left']		= $left;
				$currNotes[$k]['width']		= $width;
				$currNotes[$k]['height']	= $height;
				$currNotes[$k]['note']		= $note;
				
				break;
			}
		}

		/* Serialize and save */
		$this->DB->update( 'gallery_images', array( 'image_notes' => serialize( $currNotes ) ), "id={$id}" );
		
		$this->returnString( 'ok' );
	}
	
	/**
	 * Remove an existing image note
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function removeNote()
	{
		/* INIT */
		$id			= intval( $this->request['img'] );
		$noteId		= $this->request['noteId'];
		
		/* Make sure we have everything */
		if( ! $id || ! $noteId )
		{
			$this->returnString( 'missing_data' );
		}
		
		/* Query the image */
		$image = $this->registry->gallery->helper('image')->fetchImage( intval( $id ) );
		
		/* Will test permissions */
		$album = $this->registry->gallery->helper('albums')->fetchAlbum( $image['img_album_id'] );
		
		if ( ! $this->registry->gallery->helper('albums')->canModerate( $album['album_id'] ) AND ( $image['member_id'] != $this->memberData['member_id'] ) )
		{ 
			$this->returnString( 'nopermission' );	
		}
		
		/* Current Notes */
		$currNotes = unserialize( $image['image_notes'] );
		$currNotes = is_array( $currNotes ) ? $currNotes : array();
		
		/* Loop through and find our note */
		$newNoteArray = array();
		
		foreach( $currNotes as $k => $v )
		{
			/* Is this our note? */
			if( $v['id'] != $noteId )
			{
				$newNoteArray[] = $v;
			}
		}

		/* Serialize and save */
		$this->DB->update( 'gallery_images', array( 'image_notes' => serialize( $newNoteArray ) ), "id={$id}" );
		
		$this->returnString( 'ok' );
	}

	/**
	 * Rotates an image via ajax
	 * Source could be upload table or image table
	 *
	 * @access	public
	 * @param	string	$direction	right or left
	 * @return	@e void
	 */
	public function rotateImage( $direction )
	{
		/* INIT */
		$id       = trim( $this->request['img'] );
		$album_id = 0;
		
		/* Get the image data */
		$data     = $this->registry->gallery->helper('image')->fetchImage( $id );
		$album_id = intval( $data['img_album_id'] );
		
		/* Make sure we own the image */
		if ( ! $this->registry->gallery->helper('albums')->canModerate( $album_id ) && ! $this->registry->gallery->helper('albums')->isUploadable( $album_id ) )
		{
			$this->returnString( 'nopermission' );
		}
		
		/* Rotate */
		$angle = $direction == 'left' ? 90 : -90;
		
		if( $this->registry->gallery->helper('image')->rotateImage( $data, $angle ) )
		{
			$this->returnString( 'ok' );
		}
		else
		{
			$this->returnString( 'rotate_failed' );
		}
	}

}