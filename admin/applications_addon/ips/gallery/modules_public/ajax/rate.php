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

class public_gallery_ajax_rate extends ipsAjaxCommand
{
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */	
	public function doExecute( ipsRegistry $registry )
	{
		/* INIT */
		$id     = intval( $this->request['id'] );
		$rating = intval( $this->request['rating'] );
		$where  = $this->request['where'] == 'album' ? 'album' : 'image';
		
		/* Language */
		$this->lang->loadLanguageFile( array( 'public_gallery' ), 'gallery' );
		
		/* Do the rating */
		if ( $where == 'album' )
		{
			$result = $this->registry->gallery->helper('rate')->rateAlbum( $rating, $id );
		}
		else
		{
			$result = $this->registry->gallery->helper('rate')->rateImage( $rating, $id );
		}
		
		/* Success */
		if ( $result !== false )
		{
			$return	= array( 'rating'	=> $rating,
							 'total'	=> $result['total'],
							 'average'	=> $result['aggregate'],
							 'rated'	=> 'new' );
		    $this->returnJsonArray( $return );
		}
		/* Fail. */
		else
		{
			$this->returnJsonArray( array( 'error_key' => $this->registry->gallery->helper('rate')->getError() ) );
		}
	}
}