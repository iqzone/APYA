<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Update captcha image
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		2.3
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_core_ajax_captcha extends ipsAjaxCommand 
{
	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry )
	{
    	switch( ipsRegistry::$request['do'] )
    	{
			default:
			case 'refresh':
    			$this->refresh();
    		break;
    		
    	}
	}
	
	/**
	 * Refresh the captcha image
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	public function refresh()
	{
		$captcha_unique_id = trim( IPSText::alphanumericalClean( ipsRegistry::$request['captcha_unique_id'] ) );
		
		$template    = $this->registry->getClass('class_captcha')->getTemplate( $captcha_unique_id );
		$newUniqueID = $this->registry->getClass('class_captcha')->captchaKey;

		$this->returnString( $newUniqueID );
	}

}