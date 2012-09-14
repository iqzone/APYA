<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Post Image
 * Last Updated: $LastChangedDate: 2011-11-07 04:35:19 -0500 (Mon, 07 Nov 2011) $
 * </pre>
 *
 * @author 		$Author: mmecham $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @version		$Rev: 9767 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_gallery_post_image extends ipsCommand
{
	/**
	 * Temporary page output
	 *
	 * @var		$output
	 */
    public $output;

	/**
	 * Page title
	 *
	 * @var		$title
	 */
    public $title;

	/**
	 * Array of navigation bits
	 *
	 * @var		$nav
	 */
    public $nav;

	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Init */
		$image = array();
		$album = array();
		
		/* Load skins and language */
		$this->lang->loadLanguageFile( array( 'public_post' ), 'forums' );
		
		$this->_images = $this->registry->gallery->helper('image');
		$this->_media  = $this->registry->gallery->helper('media');
		$this->_upload = $this->registry->gallery->helper('upload');
		
		/* Access point? */
		if ( $this->request['img'] )
		{
			$image = $this->registry->gallery->helper('image')->validateAccess( intval($this->request['img']) );
			
			/* Set ID */
			$this->request['album_id'] = $image['img_album_id'];
		}
		
		
		/* Load album and check perms */
		if ( $this->request['album_id'] )
		{
			$album = $this->registry->gallery->helper('albums')->fetchAlbum( intval($this->request['album_id']) );
		}
		
		/* Load tagging stuff */
		if ( ! $this->registry->isClassLoaded('galleryTags') )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$this->registry->setClass( 'galleryTags', classes_tags_bootstrap::run( 'gallery', 'images' ) );
		}
		
		/* What on god's green earth are we up to? */
		switch( $this->request['do'] )
		{
			case 'form':
			default:
				$this->_addForm( $album );
				break;
			case 'process':
				$this->_process();
				break;
			case 'uploadSave':
				$this->_uploadSave();
				break;
			case 'upload':
				$this->_uploadIframe();
				break;
			case 'removeUpload':
				$this->_removeUpload();
				break;
			case 'editImage':
				$this->_editForm( $image, $album );
				break;
			case 'editImageSave':
				$this->_editSave( $image, $album );
				break;
		}

		//----------------------------
		// Output
		//----------------------------

		$this->registry->getClass('output')->setTitle( $this->title );
		$this->registry->getClass('output')->addContent( $this->output );

		if ( is_array( $this->nav ) AND count( $this->nav ) )
		{
			foreach( $this->nav as $_nav )
			{
				$this->registry->getClass('output')->addNavigation( $_nav[0], $_nav[1], $_nav[2], $_nav[3] );
			}
		}

		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Removes the thumbnail
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _removeUpload()
	{
		$type = trim( $this->request['type'] );
		$id   = trim( $this->request['id'] );
		
		$image = $this->_images->fetchImage( $id );
		
		/* AJAX Class */
		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classAjax.php', 'classAjax' );
		$this->ajax  = new $classToLoad();
		
		$return = $this->registry->gallery->helper('moderate')->removeMediaThumb( $image );
		
		if ( $return === false )
		{
			$this->ajax->returnJsonError( 'not_removed' );
		}
		else
		{
			$image['medium_file_name'] = '';
			$image['media_thumb'] = '';
			$image['tag'] = $this->registry->gallery->helper('media')->getThumb( $image );
			return $this->ajax->returnJsonArray( $image );
		}
	}
	
	/**
	 * Processes the thumbnail upload
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _uploadSave()
	{
		$type = trim( $this->request['type'] );
		$id   = ( ! empty( $this->request['album_id'] ) ) ? intval( $this->request['album_id'] ) : trim( $this->request['id'] );
		$sKey = trim( $this->request['sessionKey'] );
		
		/* AJAX Class */
		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classAjax.php', 'classAjax' );
		$this->ajax  = new $classToLoad();
		
		try
		{
			if ( $type == 'album' )
			{
				$return = $this->_process( true );
			}
			else
			{
				$return = $this->_upload->mediaThumb( $id );
			}
		}
		catch( Exception $error )
		{
			if ( $type == 'album' )
			{
				return $this->ajax->returnHtml( $this->registry->output->getTemplate( 'gallery_post' )->attachiFrameUpload( $sKey, json_encode( array( 'error' => $error->getMessage() ) ) ) );
			}
			else
			{
				return $this->ajax->returnHtml( $this->registry->output->getTemplate( 'gallery_post' )->attachiFrameMediaThumb( $id, json_encode( array( 'error' => $error->getMessage() ) ) ) );
			}
		}
		
		if ( $type == 'album' )
		{
			return $this->ajax->returnHtml( $this->registry->output->getTemplate( 'gallery_post' )->attachiFrameUpload( $sKey, json_encode( $return ) ) );
		}
		else
		{
			return $this->ajax->returnHtml( $this->registry->output->getTemplate( 'gallery_post' )->attachiFrameMediaThumb( $id, json_encode( $return ) ) );
		}
	}
	
	/**
	 * Shows the add new image form
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _uploadIframe()
	{
		$type = trim( $this->request['type'] );
		$id   = ( ! empty( $this->request['album_id'] ) ) ? intval( $this->request['album_id'] ) : trim( $this->request['id'] );
		$sKey = trim( $this->request['sessionKey'] );
		
		/* AJAX Class */
		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classAjax.php', 'classAjax' );
		$this->ajax  = new $classToLoad();
		
		if ( $type == 'album' )
		{
			$this->ajax->returnHtml( $this->registry->output->getTemplate( 'gallery_post' )->attachiFrameUpload( $sKey ) );
		}
		else
		{
			$this->ajax->returnHtml( $this->registry->output->getTemplate( 'gallery_post' )->attachiFrameMediaThumb( $id ) );
		}
	}
	
	/**
	 * Shows the add new image form
	 *
	 * @param	array	album
	 * @return	@e void
	 */
	protected function _addForm( $album=array() )
	{
		/* Quick 80s hair-do check */
		if ( ! $this->registry->gallery->getCanUpload() )
		{
			$this->registry->output->showError( 'no_img_post', 10762, null, null, 403 );
		}

		/* Check if we can upload into this album */
		if ( count( $album ) AND ! $this->registry->gallery->helper('albums')->isUploadable( $album ) AND ! $this->registry->gallery->helper('albums')->canCreateSubAlbumInside( $album ) )
		{
			$this->registry->output->showError( 'no_permission', 10764, null, null, 403 );
		}
		
		//-----------------------------------------
		// Force a form action?
		//-----------------------------------------
		
		$is_reset = 0;
		
		if ( $this->settings['upload_domain'] )
		{
			$is_reset = 1;
			$original = $this->settings['base_url'];
			
			if( $this->member->session_type == 'cookie' )
			{
				$this->settings['_upload_url'] = $this->settings['upload_domain'] . '/index.' . $this->settings['php_ext'].'?';
			}
			else
			{
				$this->settings['_upload_url'] = $this->settings['upload_domain'] . '/index.' . $this->settings['php_ext'].'?s='.$this->member->session_id.'&amp;';
			}
		}
		else
		{
			$this->settings['_upload_url'] = $this->settings['base_url'];
		}
		
		/* Configure form elements */
		$sessionKey = $this->registry->gallery->helper('upload')->generateSessionKey();
		$stats      = $this->registry->gallery->helper('upload')->fetchStats();
		
		/* Wipe album if we can't actually upload directly inside */
		if ( ! $this->registry->gallery->helper('albums')->isUploadable( $album ) )
		{
			$album = array( '_parent_id' => $album['album_id'] );
		}
		
		/* Output */
		$allowed_file_extensions = implode( ', ', array_merge( $this->registry->gallery->helper('image')->allowedExtensions(), ( $this->memberData['g_movies'] ? $this->registry->gallery->helper('media')->allowedExtensions() : array() ) ) );
		$this->output .= $this->registry->output->getTemplate('gallery_post')->uploadForm( $sessionKey, $album, $stats, $allowed_file_extensions );
		
		/* Title */
		$this->title   = IPSLIb::getAppTitle('gallery') . ' - ' . $this->settings['board_name'];
		
		/* Navigation */
		$this->nav[] = array( IPSLIb::getAppTitle('gallery'), 'app=gallery', 'false', 'app=gallery' );
		
		if ( !empty($album['album_id']) )
		{
			$lang 	= $this->request['media'] ? "nav_submit_media" : "nav_submit_post";
			
			$this->nav[] = array( $this->lang->words[ $lang ] . $album['album_name'], "app=gallery&amp;album={$album['album_id']}", $album['album_name_seo'], 'viewalbum' );
		}
		else
		{
			$this->nav[] = array( $this->lang->words['upload_media'] );
		}
	}
	
	/**
	 * Process the upload
	 *
	 * @return	@e void		JSON array
	 */
	protected function _process( $returnJson=false )
	{
		/* Init */
		$sessionKey = trim( $this->request['sessionKey'] );
		$albumId    = intval( $this->request['album_id'] );
		
		/* AJAX Class */
		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classAjax.php', 'classAjax' );
		$ajax		 = new $classToLoad();
		$msg  = '';
		$isError = 0;
		
		try
		{
			$newId = $this->registry->gallery->helper('upload')->process( $sessionKey, $albumId );
			$JSON  = $this->registry->gallery->helper('upload')->fetchSessionUploadsAsJson( $sessionKey, $albumId, 'upload_ok', 0, $newId );
		}
		catch( Exception $e )
		{
			$msg = $e->getMessage();
			
			switch( $msg )
			{
				case 'OUT_OF_DISKSPACE':
					$msg     = 'out_of_diskspace';
					$isError = 1;
				break;
				default:
				case 'FAIL':
					$msg     = 'silly_server';
					$isError = 1;
				break;
				case 'TOO_BIG':
					$msg     = 'upload_too_big';
					$isError = 1;
				break;
				case 'BAD_TYPE':
					$msg     = 'invalid_mime_type';
					$isError = 1;
				break;
				case 'NOT_VALID':
					$msg     = 'invalid_mime_type';
					$isError = 1;
				break;
				case 'ALBUM_FULL':
					$msg     = 'album_full';
					$isError = 1;
				break;
			}
			
			$JSON = $this->registry->gallery->helper('upload')->fetchSessionUploadsAsJson( $sessionKey, $albumId, strtolower( $msg ), $isError, 0 );
		}
		
		if ( $returnJson )
		{
			return $JSON;
		}
		
		return $ajax->returnJsonArray( $JSON );
	}
	
	/**
	 * Shows the edit image form
	 *
	 * @param	array		$image		Image data
	 * @param	array		$album		Album data
	 * @param	array		$errors		Errors found
	 * @param	string		$preview	Description preview
	 * @return	@e void
	 */
	protected function _editForm( $image, $album, $errors=array(), $preview='' )
	{
		if ( !$image['id'] )
		{
			$this->registry->output->showError( 'error_img_not_found', 10764.1, null, null, 403 );
		}
		
		if ( ! $this->registry->gallery->helper('albums')->canModerate($image['img_album_id']) AND $image['member_id'] != $this->memberData['member_id'] )
		{
			$this->registry->output->showError( 'no_permission', 10764.2, null, null, 403 );
		}
		
		/* Is that a preview? */
		$image['_caption']		 = isset($this->request['caption']) ? trim($this->request['caption']) : $image['caption'];
		$image['description']	 = isset($this->request['description']) ? $_POST['description'] : $image['description'];
		$image['copyright']		 = isset($this->request['copyright']) ? trim($this->request['copyright']) : $image['copyright'];
		$image['image_gps_show'] = isset($this->request['image_gps_show']) ? intval($this->request['image_gps_show']) : $image['image_gps_show'];
		
		/* Show description in editor, get editor */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$editor = new $classToLoad();
		
		$editor->setAllowBbcode( true );
		$editor->setAllowSmilies( true );
		$editor->setAllowHtml( false );
		
		/* Set content in editor */
		$image['editor'] = $editor->show( 'description', array(), $image['description'] );
		
		$where = array( 'meta_id'		 => $image['id'],
						'meta_parent_id' => intval( $album['album_id'] ),
						'member_id'	     => $this->memberData['member_id'] );
	
		if ( $_REQUEST['ipsTags_' . $image['id']] )
		{
			$where['existing_tags']	= explode( ',', IPSText::cleanPermString( $_REQUEST['ipsTags_' . $image['id']] ) );
		}
	
		if ( $this->registry->galleryTags->can( 'edit', $where ) )
		{
			$image['_tagBox'] = $this->registry->galleryTags->render('entryBox', $where);
		}
					
		/* Output */
		$this->output .= $this->registry->output->getTemplate('gallery_post')->editImageForm( $image, $album, $errors, $preview );
		
		/* Title */
		$this->title   = sprintf( $this->lang->words['editing_image'], $image['caption'] ) . ' - ' . IPSLIb::getAppTitle('gallery') . ' - ' . $this->settings['board_name'];
		
		/* Navigation */
		$this->nav[] = array( IPSLIb::getAppTitle('gallery'), 'app=gallery', 'false', 'app=gallery' );
		$this->nav[] = array( $album['album_name'], "app=gallery&amp;album={$album['album_id']}", $album['album_name_seo'], 'viewalbum' );
		$this->nav[] = array( $image['caption'], "app=gallery&amp;image={$image['id']}", $image['caption_seo'], 'viewimage' );
		$this->nav[] = array( $this->lang->words['edit_post'] );
	}
	
	/**
	 * Saves an edited image
	 *
	 * @param	array		$image		Image data
	 * @param	array		$album		Album data
	 * @return	@e void
	 */
	protected function _editSave( $image, $album )
	{
		if ( !$image['id'] )
		{
			$this->registry->output->showError( 'error_img_not_found', 10764.3, null, null, 403 );
		}
		
		if ( !$this->registry->gallery->helper('albums')->canModerate($image['img_album_id']) AND $image['member_id'] != $this->memberData['member_id'] )
		{
			$this->registry->output->showError( 'no_permission', 10764.4, null, null, 403 );
		}
		
		/* Init vars */
		$errors  = array();
		$caption = trim($this->request['caption']);
		
		/* Make sure we have everything */
		if( !$caption )
		{
			$errors[] = $this->lang->words['gerror_no_title'];
		}
		
		if ( count($errors) && !isset($this->request['preview']) )
		{
			$this->_editForm( $image, $album, $errors );
			return false;
		}
		
		/* Set up BBcode parsing */
		IPSText::getTextClass('bbcode')->parse_smilies	 = 1;
		IPSText::getTextClass('bbcode')->parse_html		 = 0;
		IPSText::getTextClass('bbcode')->parse_nl2br	 = 1;
		IPSText::getTextClass('bbcode')->parse_bbcode	 = 1;
		IPSText::getTextClass('bbcode')->parsing_section = 'gallery';
		
		$editorClass = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$editor = new $editorClass();
			
		$description = $editor->process( $_POST['description'] );
		$description = IPSText::getTextClass('bbcode')->preDbParse( $description );
		
		if ( isset($this->request['preview']) )
		{
			$this->_editForm( $image, $album, $errors, $description );
			return false;
		}
		
		/* Update array */
		$update = array( 'caption'			=> $caption,
						 'description'		=> $description,
						 'image_gps_show'	=> intval($this->request['image_gps_show']),
						 'copyright'		=> trim($this->request['copyright'])
						);
		
		/* Not uploading a new image? */
		if ( !isset($_FILES['newImage']['name']) || empty($_FILES['newImage']['name']) || empty($_FILES['newImage']['size']) || $_FILES['newImage']['name'] == "none" )
		{
			$update['caption_seo'] = IPSText::makeSeoTitle($caption);
			
			$this->DB->update( 'gallery_images', $update, 'id=' . $image['id'] );
			
			if ( ! empty( $_POST['ipsTags_' . $image['id']] ) )
			{
				$this->registry->galleryTags->replace( $_POST['ipsTags_' . $image['id']], array( 'meta_id'		  => $image['id'],
																      						 	 'meta_parent_id' => $image['img_album_id'],
																      						 	 'member_id'	  => $this->memberData['member_id'],
																      						 	 'meta_visible'   => $image['approved'] ) );
			}
				
			$image = array_merge( $image, $update );
		}
		else
		{
			$image = $this->registry->gallery->helper('upload')->editImage( 'newImage', $image['id'], $update, $image['member_id'] );
		}
		
		/* Redirect */
		$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=gallery&amp;image=' . $image['id'], $image['caption_seo'], false, 'viewimage' );
	}
}