<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Rate Image
 * Last Updated: $LastChangedDate: 2011-11-01 09:19:21 -0400 (Tue, 01 Nov 2011) $
 * </pre>
 *
 * @author 		$Author: mmecham $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @version		$Rev: 9728 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_gallery_images_sizes extends ipsCommand
{
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */	
	public function doExecute( ipsRegistry $registry )
	{
		$this->_albums = $this->registry->gallery->helper('albums');
		$this->_images = $this->registry->gallery->helper('image');
		
		/* INIT */
		$this->_viewSize();
	}
	
	/**
	 * View a size.
	 *
	 * @access	protected
	 * @return	God knows
	 */
	protected function _viewSize()
	{
		/* Fetch image */
		$image   = $this->_images->fetchImage( intval( $this->request['image'] ) );
		$size    = trim( $this->request['size'] );
		$options = array();
		
		if ( ! $image['id'] )
		{
			$this->registry->output->showError( 'no_permission', '1-gallery-images-sizes-view-0', null, null, 404 );
		}
		
		$album = $this->_albums->fetchAlbumsById( $image['img_album_id'] );
		
		if ( ! $this->_images->isViewable( $image, $album ) )
		{
			$this->registry->output->showError( 'no_permission', '1-gallery-images-sizes-view-1', null, null, 404 );
		}
		
		/* Build tag */
		switch( $size )
		{
			case 'square':
				$options = array( 'type' => 'thumb' , 'link-page' => 'none' );
			break;
			case 'small':
				$options = array( 'type' => 'small' , 'link-page' => 'none' );
			break;
			case 'medium':
				$options = array( 'type' => 'medium', 'link-page' => 'none' );
			break;
			case 'large':
				$options = array( 'type' => 'large' , 'link-page' => 'none' );
			break;
		}
		
		if ( empty( $image['_data']['sizes'] ) )
		{
			$this->_images->buildSizedCopies( $image );
			$image = $this->_images->fetchImage( intval( $this->request['img'] ) );
		}
		
		/* Build */
		$image['tag'] = $this->_images->makeImageTag( $image, $options );
		
		$output = $this->registry->output->getTemplate('gallery_img')->sizes( $image, $album, $size );
		
		/* Fetch navigation */
		$parents = $this->registry->gallery->helper('albums')->fetchAlbumParents( $album['album_id'] );
		$nav     = array( array( IPSLIb::getAppTitle('gallery'), "app=gallery", 'false', 'app=gallery' ) );
		
		$parents = array_reverse( $parents, true );
		
		foreach( $parents as $id => $data )
		{
			$nav[] = array( $data['album_name'], 'app=gallery&amp;album=' . $data['album_id'], $data['album_name_seo'], 'viewalbum' );	
		}
		
		/* add in this album */
		$nav[] = array( $album['album_name'], 'app=gallery&amp;album=' . $album['album_id'], $album['album_name_seo'], 'viewalbum' );
		
		/* add in image */
		$nav[] = array( $image['caption'], 'app=gallery&amp;image=' . $image['id'], $image['caption_seo'], 'viewimage' );
		
		$nav[] = array( $this->lang->words['size_ucfirst'] . ': ' . $this->lang->words[ $size.'_ucfirst' ] );
		
		$title = $this->lang->words['viewing_img'] . ' ' . $image['caption'] . ' - ' . $this->settings['board_name'];

		/* Output */
		$this->registry->getClass('output')->setTitle( $title );
		$this->registry->getClass('output')->addContent( $output );
		
		foreach( $nav as $_nav )
		{
			$this->registry->getClass('output')->addNavigation( $_nav[0], $_nav[1], $_nav[2], $_nav[3] );
		}

		$this->registry->getClass('output')->sendOutput();
	}
}