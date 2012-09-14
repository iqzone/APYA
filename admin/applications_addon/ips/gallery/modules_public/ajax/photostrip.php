<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Photostrip Ajax
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

class public_gallery_ajax_photostrip extends ipsAjaxCommand
{
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */	
	public function doExecute( ipsRegistry $registry )
	{
		/* What to do? */
		switch( $this->request['do'] )
		{
			case 'slide':
				$this->slide();
			break;
        }
    }
    
	/**
	 * Builds and returns the html for sliding the photostrip to the left
	 *
	 * @access	private
	 * @return	@e void
	 */  
    private function slide()
    {
		/* Init */
		$directionPos = trim( $this->request['directionPos'] );
		$direction    = ( $this->request['direction'] == 'left' ) ? 'left' : 'right';
		$left         = intval( $this->request['left'] );
		$right		  = intval( $this->request['right'] );
		$imId         = ( $direction == 'left' ) ? $left : $right;
		
		/* Done */
		$this->returnJsonArray( $this->registry->gallery->helper('image')->fetchPhotoStrip( $imId, $direction, $directionPos ) );
	}
}