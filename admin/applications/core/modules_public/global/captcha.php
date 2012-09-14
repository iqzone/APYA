<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Captcha
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		20th February 2002
 * @version		$Rev: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_core_global_captcha extends ipsCommand
{
	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		/* Load Cpatcha Class */
		$this->class_captcha = $this->registry->getClass('class_captcha');
		
		/* What to do... */
		switch( $this->request['do'] )
		{
			default:
			case 'showimage':
				$this->showImage();
			break;
			case 'refresh':
				$this->refreshImage();
			break;
		}
	}
	
	/**
	 * Show the captcha image
	 * Shows the captcha image. Good god, that was a waste of time
	 *
	 * @return	@e void
	 */
	public function showImage()
	{
		/* INIT */
		$captcha_unique_id = trim( $this->request['captcha_unique_id'] );

		/* Show Image... */
		$this->class_captcha->showImage( $captcha_unique_id );
	}
	
	/**
	 * Show the captcha image
	 * Refreshes the captcha image.
	 *
	 * @return	@e void
	 */
	public function refreshImage()
	{
		/* INIT */
		$captcha_unique_id = trim( $this->request['captcha_unique_id'] );
		
		/*  Throw away */
		$blah	= $this->class_captcha->getTemplate();
		
		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classAjax.php', 'classAjax' );
		$ajax		 = new $classToLoad();
		
		/* Show Image... */
		$ajax->returnString( $this->class_captcha->captchaKey );
		exit;
	}
}