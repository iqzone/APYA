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

class public_gallery_images_rate extends ipsCommand
{
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */	
	public function doExecute( ipsRegistry $registry )
	{
		/* Check secure key */
		if( $this->request['secure_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'nopermission', '10727.1' );
		}
		
		/* INIT */
		$id     = intval( $this->request['id'] );
		$rating = intval( $this->request['rating'] );
		$where  = $this->request['where'] == 'album' ? 'album' : 'image';
		
		/* Language */
		$this->lang->loadLanguageFile( array( 'public_gallery' ), 'gallery' );
		
		/* Do the rating */
		if ( $where == 'album' )
		{
			$result = $this->registry->gallery->helper('rate')->rateAlbum( $rating, $id, true );
		}
		else
		{
			$result = $this->registry->gallery->helper('rate')->rateImage( $rating, $id, true );
		}
		
		/* Success */
		if ( $result !== false )
		{
			if ( $where == 'album' )
			{
				$this->registry->output->redirectScreen( $this->lang->words['album_rated'], $this->settings['base_url'] . 'app=gallery&amp;album=' . $id, $result['albumData']['album_name_seo'], 'viewalbum' );
			}
			else
			{
				$this->registry->output->redirectScreen( $this->lang->words['rated'], $this->settings['base_url'] . 'app=gallery&amp;image=' . $id, $result['imageData']['caption_seo'], 'viewimage' );
			}
		}
		/* Fail. */
		else
		{
			$this->registry->output->showError( $this->registry->gallery->helper('rate')->getError(), 10727 );
		}
	}
}