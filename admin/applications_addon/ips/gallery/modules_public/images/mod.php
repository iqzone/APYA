<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Main/Moderation
 * Moderation stuffs. Edit, pin, unpin, launch ze missiles
 * Last Updated: $LastChangedDate: 2011-11-09 09:26:36 -0500 (Wed, 09 Nov 2011) $
 * </pre>
 *
 * @author 		$Author: mmecham $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @version		$Rev: 9792 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_gallery_images_mod extends ipsCommand
{
	/**
	 * Temp output
	 *
	 * @access	public
	 * @var		string
	 */
	public $output;
	
	/**
	 * Page title
	 *
	 * @access	public
	 * @var		string
	 */
	public $title;
	
	/**
	 * Navigation bits
	 *
	 * @access	public
	 * @var		array
	 */
	public $nav;

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
		
		/* What to do */
		switch( $this->request['do'] )
		{
			case 'delete':
				$this->_delete();
			break;
			case 'rotate':
				$this->_rotate();
			break;
			case 'move':
				$this->_move();
			break;
			case 'approveToggle':
				$this->_approveToggle();
			break;
			default:
				$this->registry->output->showError( 'no_permission', 10790 );
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
				$this->registry->getClass('output')->addNavigation( $_nav[0], $_nav[1] );
			}
		}

		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Moves the image
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _approveToggle()
	{
		$image   = $this->_images->fetchImage( intval( $this->request['imageid'] ) );
		$album   = $this->_albums->fetchAlbumsById( $image['img_album_id'] );
		$visible = ( $this->request['val'] == 1 ) ? true : false;
		
		/* Quick test */
		if ( $this->request['auth_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'no_permission', '10790-toggle-1' );
		}
		
		/* Toggle approval */
		$this->_moderate->toggleVisibility( array( $image['id'] ), $visible );
		
		/* Back */
		if ( $this->request['modcp'] )
		{
			$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . 'app=core&module=modcp&fromapp=gallery&tab=' . $this->request['modcp'] );
		}
		else
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=gallery&amp;album=' . $album['album_id'], $album['album_name_seo'], false, 'viewalbum' );
		}
	}
	
	/**
	 * Moves the image
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _move()
	{
		$image = $this->_images->fetchImage( intval( $this->request['imageid'] ) );
		$from  = $this->_albums->fetchAlbumsById( $image['img_album_id'] );
		$album = $this->_albums->fetchAlbumsById( intval( $this->request['move_to_album_id'] ) );
		
		if ( empty( $image['id'] ) OR empty( $album['album_id'] ) )
		{
			$this->registry->output->showError( 'no_permission', '10790-move-1' );
		}
		
		if ( ! $this->_albums->canModerate( $album ) AND ( $image['member_id'] != $this->memberData['member_id'] ) )
		{
			$this->registry->output->showError( 'no_permission', '10790-move-2' );
		}
		
		if ( ! $this->_albums->isUploadable( $album ) AND ! $this->_albums->canModerate( $album ) )
		{
			$this->registry->output->showError( 'no_permission', '10790-move-3' );
		}
		
		/* Had enough now, just get rid of it */
		$this->_moderate->moveImages( array( $image['id'] ), $album['album_id'] );
		
		/* Back to the album */
		$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=gallery&amp;album=' . $from['album_id'], $from['album_name_seo'], false, 'viewalbum' );
	}
	
	/**
	 * Removes the specified image, permissions checked
	 *
	 * @access	protected
	 * @param	integer	$img
	 * @param	bool	[$redir]
	 * @return	@e void
	 */
	protected function _delete()
	{
		$image = $this->_images->fetchImage( intval( $this->request['imageid'] ) );
		$album = $this->_albums->fetchAlbumsById( $image['img_album_id'] );
		
		if ( empty( $image['id'] ) )
		{
			$this->registry->output->showError( 'no_permission', 10790 );
		}
		
		if ( ! $this->_albums->canModerate( $album ) AND ( $image['member_id'] != $this->memberData['member_id'] ) )
		{
			$this->registry->output->showError( 'no_permission', 10790 );
		}
		
		/* Had enough now, just get rid of it */
		$this->_moderate->deleteImages( array( $image['id'] => $image ) );
		
		/* Back */
		if ( $this->request['modcp'] )
		{
			$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . 'app=core&module=modcp&fromapp=gallery&tab=' . $this->request['modcp'] );
		}
		else
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=gallery&amp;album=' . $album['album_id'], $album['album_name_seo'], false, 'viewalbum' );
		}
	}
	
	/**
	 * Rotates an image
	 * Source could be upload table or image table
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _rotate()
	{
		/* INIT */
		$id       = intval( $this->request['imageId'] );
		$dir	  = trim( $this->request['dir'] );
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
		$angle = $dir == 'left' ? 90 : -90;
		
		if ( $this->registry->gallery->helper('image')->rotateImage( $data, $angle ) )
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=gallery&amp;image=' . $data['id'], $data['caption_seo'], false, 'viewimage' );
		}
		else
		{
			$this->registry->output->showError( 'no_permission', 10790 );
		}
	}
}