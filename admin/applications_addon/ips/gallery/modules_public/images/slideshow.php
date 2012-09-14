<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Slideshows
 * Last Updated: $LastChangedDate: 2011-08-29 22:12:22 -0400 (Mon, 29 Aug 2011) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @version		$Rev: 9425 $
 *
 */
 
if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_gallery_images_slideshow extends ipsCommand
{
	/**
	 * Temp output
	 *
	 * @access	private
	 * @var		string
	 */
	private $output	= null;

	/**
	 * Temp navigation bits
	 *
	 * @access	private
	 * @var		array
	 */
	private $nav	= array();

	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */	
	public function doExecute( ipsRegistry $registry )
	{
		/* Generate slideshow */
		$this->fancySlideShow();

		/* Output */
		$this->registry->getClass('output')->setTitle( IPSLIb::getAppTitle('gallery') . ' - ' . $this->settings['board_name'] );
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
	 * Handles the new slideshow introduced in 3.1.0
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function fancySlideShow()
	{
		/* INIT */
		$album  = $this->registry->gallery->helper('albums')->fetchAlbum( intval($this->request['album']) );
		$images = $this->registry->gallery->helper('image')->fetchAlbumImages( $album['album_id'], array( 'media' => false, 'offset' => 0, 'limit' => 250, 'sortKey' => $album['album_sort_options__key'], 'sortOrder' => $album['album_sort_options__dir'] ) );
		
		/* No images? */
		if ( !is_array($images) || !count($images) )
		{
			$this->registry->output->showError( 'slideshow_no_images', '1-gallery-slideshow-1' );
		}
		
		/* Build data for slideshow */
		$imageIds	= array();
		$imageData	= array();
		$lastID		= 0;
		$memberIds  = array();
		
		foreach( $images as $id => $image )
		{
			/* Make sure deleted members don't break stuff */
			$image['member_id'] = intval($image['member_id']);
			
			/* Add to array */
			$imageIds[]  = $image['id'];
			$memberIds[ $image['member_id'] ] = $image['member_id']; 
			
			/* Image Data */
			$imageData[ $image['id'] ] = $image;
			
			$lastID = $image['id'];
		}
		
		/* Remove guests and load members */
		unset($memberIds[0]);
		$members = IPSMember::load( $memberIds, 'all' );
		
		foreach( $images as $id => $image )
		{
			$imageData[ $image['id'] ]['_photo'] = IPSMember::buildProfilePhoto( $members[ $image['member_id'] ] );
		}
		
		/* Load CSS */
		$this->registry->output->clearLoadedCss();
		$this->registry->output->addToDocumentHead( 'importcss', $this->settings['css_base_url'] . 'style_css/' . $this->registry->output->skin['_csscacheid'] . '/ipgallery_slideshow.css' );
		
		$this->output .= $this->registry->output->getTemplate('gallery_albums')->slideShow( $album, implode( ',', $imageIds ), $imageData, $lastID );
		$this->registry->getClass('output')->setTitle( IPSLIb::getAppTitle('gallery') . ' - ' . $this->settings['board_name'] );
		$this->registry->output->popUpWindow( $this->output );
	}
}