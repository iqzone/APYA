<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Rate Image
 * Last Updated: $LastChangedDate: 2011-05-20 06:00:55 -0400 (Fri, 20 May 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @version		$Rev: 8849 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_gallery_ajax_bbcode extends ipsAjaxCommand
{
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */	
	public function doExecute( ipsRegistry $registry )
	{
		/* Short cut */
		$this->_images = $this->registry->gallery->helper('image');
		$this->_albums = $this->registry->gallery->helper('albums');
		
		/* INIT */
		$imageIds = array_values( IPSLib::cleanIntArray( $_POST['imageIds'] ) );
		$albumIds = array_values( IPSLib::cleanIntArray( $_POST['albumIds'] ) );
		$images   = array();
		$albums   = array();
		$return   = array( 'images' => array(), 'albums' => array() );
		
		/* images */
		if ( count( $imageIds ) )
		{
			$images = $this->_images->fetchImages( null, array( 'imageIds' => $imageIds ) );
			
			foreach( $images as $id => $data )
			{
				$album = $data;
				
				if ( ! $this->_images->isViewable( $data, $album ) )
				{
					unset( $images[ $id ] );
				}
				else
				{
					/* Do HTML */
					$return['images'][ $id ] = $this->registry->output->getTemplate('gallery_global')->bbCodeImage( $data, $album );
				}
			}
		}
		
		/* albums */
		if ( count( $albumIds ) )
		{
			$albums = $this->_albums->fetchAlbumsById( $albumIds );
			
			foreach( $albums as $id => $data )
			{
				if ( ! $this->_albums->isViewable( $data ) )
				{
					unset( $albums[ $id ] );
				}
				else
				{
					/* Do HTML */
					$return['albums'][ $id ] = $this->registry->output->getTemplate('gallery_global')->bbCodeAlbum( $data );
				}
			}
		}
		
		$this->returnJsonArray( $return );
				
	}
}